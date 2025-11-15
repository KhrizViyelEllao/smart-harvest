<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../../db_connect.php';

function delivery_fee($opt,$qty){
  return $opt==='delivery' ? 40.00 : 0.00; // flat fee
}

try {
  if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') throw new Exception('Invalid method');
  $payload = json_decode(file_get_contents('php://input'), true);
  if (!is_array($payload)) throw new Exception('Invalid JSON');

  $userId = (int)($_SESSION['user_id'] ?? ($payload['user_id'] ?? 0));
  $deliveryOpt = $payload['delivery_option'] ?? 'pickup';
  $address     = trim($payload['address'] ?? '');
  $contact     = trim($payload['contact_info'] ?? '');
  $paymentMethod = $payload['payment_method'] ?? 'cash';

  if ($userId <= 0) throw new Exception('user_id required');
  if (!in_array($deliveryOpt,['pickup','delivery'],true)) throw new Exception('Bad delivery option');
  if ($deliveryOpt==='delivery' && !$address) throw new Exception('Address required');

  $conn->begin_transaction();

  $sql = "
    SELECT ci.cart_item_id, ci.product_id, ci.quantity_kg,
           p.available_qty, p.price_per_kg, p.status
    FROM cart_items ci
    INNER JOIN products p ON p.product_id = ci.product_id
    WHERE ci.user_id = ?
    FOR UPDATE
  ";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param('i',$userId);
  $stmt->execute();
  $res = $stmt->get_result();
  $items = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
  $stmt->close();

  if (!$items) { $conn->rollback(); throw new Exception('Cart empty'); }

  $orders = [];
  foreach ($items as $it) {
    $pid = (int)$it['product_id'];
    $qty = (float)$it['quantity_kg'];
    $avail = (float)$it['available_qty'];
    $price = (float)$it['price_per_kg'];

    if (($it['status'] ?? '') !== 'available') throw new Exception('Product unavailable ID ' . $pid);
    if ($qty <= 0 || $qty > $avail) throw new Exception('Invalid qty for product ' . $pid);

    $fee = delivery_fee($deliveryOpt, $qty);
    $total = round($qty * $price + $fee, 2);

    $stmt2 = $conn->prepare("
      INSERT INTO orders (user_id, product_id, buyer_name, contact_info,
        quantity_kg, total_price, payment_method, payment_status, status,
        delivery_option, address, delivery_fee)
      VALUES (?,?,?,?,?,?,?,?,?,?,?,?)
    ");
    $buyer = $_SESSION['name'] ?? 'Consumer';
    $payStatus = 'unpaid';
    $status = 'pending';
    $stmt2->bind_param('iissddsssssd',
      $userId, $pid, $buyer, $contact, $qty, $total,
      $paymentMethod, $payStatus, $status, $deliveryOpt, $address, $fee
    );
    if(!$stmt2->execute()){ $stmt2->close(); throw new Exception('Order insert failed'); }
    $orderId = $stmt2->insert_id;
    $stmt2->close();

    $newAvail = $avail - $qty;
    $newStatus = $newAvail <= 0 ? 'sold_out' : 'available';
    $up = $conn->prepare("UPDATE products SET available_qty=?, status=? WHERE product_id=?");
    $up->bind_param('dsi', $newAvail, $newStatus, $pid);
    if(!$up->execute()){ $up->close(); throw new Exception('Stock update failed'); }
    $up->close();

    $orders[] = ['order_id'=>$orderId,'product_id'=>$pid,'quantity_kg'=>$qty,'total_price'=>$total,'delivery_fee'=>$fee];
  }

  $clr = $conn->prepare("DELETE FROM cart_items WHERE user_id=?");
  $clr->bind_param('i', $userId);
  $clr->execute();
  $clr->close();

  $conn->commit();
  echo json_encode(['success'=>true,'orders'=>$orders]);
} catch (Throwable $e) {
  if ($conn && $conn->errno === 0) { $conn->rollback(); }
  http_response_code(400);
  echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
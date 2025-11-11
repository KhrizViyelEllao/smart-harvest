<?php
session_start();
ob_start();
header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
ini_set('display_errors', '0');
ini_set('html_errors', '0');

set_error_handler(function($severity, $message, $file, $line){
  throw new ErrorException($message, 0, $severity, $file, $line);
});
register_shutdown_function(function(){
  $e = error_get_last();
  if ($e && in_array($e['type'], [E_ERROR,E_PARSE,E_CORE_ERROR,E_COMPILE_ERROR])) {
    http_response_code(500);
    if (ob_get_length()) ob_clean();
    echo json_encode(['success'=>false,'message'=>'Server error']);
  }
});

include '../../db_connect.php';

try {
  if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'consumer') {
    throw new Exception('Unauthorized');
  }
  if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    throw new Exception('Invalid method');
  }

  $raw = json_decode(file_get_contents('php://input'), true);
  if (!is_array($raw)) throw new Exception('Invalid JSON');

  $userId    = (int)$_SESSION['user_id'];
  $productId = (int)($raw['product_id'] ?? 0);
  $qty       = (float)($raw['quantity_kg'] ?? 0);
  $delivery  = $raw['delivery_option'] ?? 'pickup';
  $address   = trim($raw['address'] ?? '');
  $contact   = trim($raw['contact_info'] ?? '');
  $buyerName = $_SESSION['name'] ?? 'Consumer';
  $status    = 'pending';

  if ($productId <= 0) throw new Exception('product_id required');
  if ($qty <= 0) throw new Exception('Invalid quantity');
  if (!in_array($delivery, ['pickup','delivery'], true)) throw new Exception('Invalid delivery option');
  if ($delivery === 'delivery' && $address === '') throw new Exception('Address required for delivery');

  $conn->begin_transaction();

  // Lock product
  $stmt = $conn->prepare("SELECT available_qty, price_per_kg, status FROM products WHERE product_id=? FOR UPDATE");
  $stmt->bind_param('i', $productId);
  $stmt->execute();
  $res = $stmt->get_result();
  if ($res->num_rows === 0) { $stmt->close(); throw new Exception('Product not found'); }
  $p = $res->fetch_assoc();
  $stmt->close();

  if (($p['status'] ?? '') !== 'available') throw new Exception('Product not available');
  $available = (float)$p['available_qty'];
  $price = (float)$p['price_per_kg'];
  if ($qty > $available) throw new Exception('Quantity exceeds availability');

  $total = $qty * $price;

  // Insert order (supports optional delivery_option/address columns)
  $hasDeliveryCols = ($conn->query("SHOW COLUMNS FROM orders LIKE 'delivery_option'")->num_rows === 1)
                  && ($conn->query("SHOW COLUMNS FROM orders LIKE 'address'")->num_rows === 1);

  if ($hasDeliveryCols) {
    $stmt2 = $conn->prepare("INSERT INTO orders (user_id, product_id, buyer_name, contact_info, quantity_kg, total_price, status, delivery_option, address)
                             VALUES (?,?,?,?,?,?,?,?,?)");
    $stmt2->bind_param('iissddsss', $userId, $productId, $buyerName, $contact, $qty, $total, $status, $delivery, $address);
  } else {
    $stmt2 = $conn->prepare("INSERT INTO orders (user_id, product_id, buyer_name, contact_info, quantity_kg, total_price, status)
                             VALUES (?,?,?,?,?,?,?)");
    $stmt2->bind_param('iissdds', $userId, $productId, $buyerName, $contact, $qty, $total, $status);
  }
  if (!$stmt2) throw new Exception('DB error');
  if (!$stmt2->execute()) { $stmt2->close(); throw new Exception('Order insert failed'); }
  $orderId = $stmt2->insert_id;
  $stmt2->close();

  // Update product availability
  $newAvail = $available - $qty;
  $newStatus = $newAvail <= 0 ? 'sold_out' : 'available';

  $stmt3 = $conn->prepare("UPDATE products SET available_qty=?, status=? WHERE product_id=?");
  $stmt3->bind_param('dsi', $newAvail, $newStatus, $productId);
  if (!$stmt3->execute()) { $stmt3->close(); throw new Exception('Product update failed'); }
  $stmt3->close();

  $conn->commit();

  if (ob_get_length()) ob_clean();
  echo json_encode([
    'success' => true,
    'order_id' => $orderId,
    'remaining_qty' => $newAvail,
    'product_status' => $newStatus
  ]);
} catch (Throwable $e) {
  if ($conn && $conn->errno === 0) { /* noop */ }
  if ($conn) { $conn->rollback(); }
  http_response_code(400);
  if (ob_get_length()) ob_clean();
  echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
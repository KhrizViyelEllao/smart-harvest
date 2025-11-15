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

function calculate_delivery_fee(string $option, float $qty): float {
  if ($option === 'pickup') return 0.00;
  $base = 60.00;
  $perKg = 8.00;
  return round($base + ($perKg * max($qty, 0)), 2);
}

try {
  if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    throw new Exception('Invalid method');
  }

  $raw = json_decode(file_get_contents('php://input'), true);
  if (!is_array($raw)) throw new Exception('Invalid JSON');

  $userId    = (int)($_SESSION['user_id'] ?? ($raw['user_id'] ?? 0));
  $productId = (int)($raw['product_id'] ?? 0);
  $qty       = (float)($raw['quantity_kg'] ?? 0);
  $delivery  = $raw['delivery_option'] ?? 'pickup';
  $address   = trim($raw['address'] ?? '');
  $contact   = trim($raw['contact_info'] ?? '');
  $buyerName = $raw['buyer_name'] ?? ($_SESSION['name'] ?? 'Consumer');
  $preferred = $raw['preferred_delivery_date'] ?? null;
  $method    = $raw['payment_method'] ?? 'cash';
  $paymentStatus = $raw['payment_status'] ?? 'unpaid';

  if ($userId <= 0) throw new Exception('user_id required');
  if ($productId <= 0) throw new Exception('product_id required');
  if ($qty <= 0) throw new Exception('Invalid quantity');
  if (!in_array($delivery, ['pickup','delivery'], true)) throw new Exception('Invalid delivery option');
  if ($delivery === 'delivery' && $address === '') throw new Exception('Address required for delivery');
  if (!in_array($method, ['cash','gcash','bank','cod'], true)) throw new Exception('Invalid payment method');
  if (!in_array($paymentStatus, ['unpaid','paid','refunded'], true)) throw new Exception('Invalid payment status');

  $conn->begin_transaction();

  $stmt = $conn->prepare("SELECT available_qty, price_per_kg, status, image_url FROM products WHERE product_id=? FOR UPDATE");
  $stmt->bind_param('i', $productId);
  $stmt->execute();
  $res = $stmt->get_result();
  if ($res->num_rows === 0) { $stmt->close(); throw new Exception('Product not found'); }
  $p = $res->fetch_assoc();
  $stmt->close();

  if (($p['status'] ?? '') !== 'available') throw new Exception('Product not available');
  $available = (float)$p['available_qty'];
  if ($qty > $available) throw new Exception('Quantity exceeds availability');
  $price = (float)$p['price_per_kg'];

  $subtotal = $qty * $price;
  $deliveryFee = calculate_delivery_fee($delivery, $qty);
  $grandTotal = $subtotal + $deliveryFee;

  $colCheck = $conn->query("SHOW COLUMNS FROM orders LIKE 'delivery_option'")->num_rows === 1;

  $stmt2 = $conn->prepare("
    INSERT INTO orders (
      user_id, product_id, buyer_name, contact_info,
      quantity_kg, total_price, payment_method, payment_status,
      status, delivery_option, preferred_delivery_date, address, delivery_fee
    ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)
  ");
  $status = 'pending';
  $prefDate = $preferred ? date('Y-m-d', strtotime($preferred)) : null;
  $stmt2->bind_param(
    'iissddssssssd',
    $userId,
    $productId,
    $buyerName,
    $contact,
    $qty,
    $grandTotal,
    $method,
    $paymentStatus,
    $status,
    $delivery,
    $prefDate,
    $address,
    $deliveryFee
  );
  if (!$stmt2->execute()) { $stmt2->close(); throw new Exception('Order insert failed'); }
  $orderId = $stmt2->insert_id;
  $stmt2->close();

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
    'subtotal' => round($subtotal, 2),
    'delivery_fee' => $deliveryFee,
    'total' => round($grandTotal, 2),
    'remaining_qty' => $newAvail,
    'product_status' => $newStatus
  ]);
} catch (Throwable $e) {
  if ($conn) $conn->rollback();
  http_response_code(400);
  if (ob_get_length()) ob_clean();
  echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
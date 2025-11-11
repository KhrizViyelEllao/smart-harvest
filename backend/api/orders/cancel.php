<?php
session_start();
header('Content-Type: application/json');
include '../../db_connect.php';

try {
  if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'consumer') throw new Exception('Unauthorized');
  if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') throw new Exception('Invalid method');
  $raw = json_decode(file_get_contents('php://input'), true);
  $orderId = (int)($raw['order_id'] ?? 0);
  if ($orderId <= 0) throw new Exception('order_id required');

  $uid = (int)$_SESSION['user_id'];

  $conn->begin_transaction();

  // Get order (lock row)
  $stmt = $conn->prepare("SELECT product_id, quantity_kg, status FROM orders WHERE order_id=? AND user_id=? FOR UPDATE");
  $stmt->bind_param('ii', $orderId, $uid);
  $stmt->execute();
  $res = $stmt->get_result();
  if ($res->num_rows === 0) { $stmt->close(); throw new Exception('Order not found'); }
  $o = $res->fetch_assoc();
  $stmt->close();
  if ($o['status'] !== 'pending') throw new Exception('Only pending orders can be cancelled');

  // Restore product qty
  $pid = (int)$o['product_id']; $qty = (float)$o['quantity_kg'];

  $stmt2 = $conn->prepare("SELECT available_qty FROM products WHERE product_id=? FOR UPDATE");
  $stmt2->bind_param('i', $pid);
  $stmt2->execute();
  $res2 = $stmt2->get_result();
  if ($res2->num_rows === 0) { $stmt2->close(); throw new Exception('Product not found'); }
  $p = $res2->fetch_assoc(); $stmt2->close();

  $newAvail = (float)$p['available_qty'] + $qty;
  $newStatus = 'available';

  $stmt3 = $conn->prepare("UPDATE products SET available_qty=?, status=? WHERE product_id=?");
  $stmt3->bind_param('dsi', $newAvail, $newStatus, $pid);
  if (!$stmt3->execute()) { $stmt3->close(); throw new Exception('Product update failed'); }
  $stmt3->close();

  // Update order status
  $stmt4 = $conn->prepare("UPDATE orders SET status='cancelled' WHERE order_id=? AND user_id=?");
  $stmt4->bind_param('ii', $orderId, $uid);
  if (!$stmt4->execute()) { $stmt4->close(); throw new Exception('Cancel failed'); }
  $stmt4->close();

  $conn->commit();
  echo json_encode(['success'=>true]);
} catch (Exception $e) {
  $conn->rollback();
  http_response_code(400);
  echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
<?php

session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../../db_connect.php';

try {
  if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') throw new Exception('Invalid method');
  $payload = json_decode(file_get_contents('php://input'), true);
  if (!is_array($payload)) throw new Exception('Invalid JSON');

  $userId = (int)($_SESSION['user_id'] ?? ($payload['user_id'] ?? 0));
  $itemId = (int)($payload['cart_item_id'] ?? 0);
  $qty = (float)($payload['quantity_kg'] ?? 0);

  if ($userId <= 0 || $itemId <= 0) throw new Exception('Missing identifiers');
  if ($qty <= 0) throw new Exception('Quantity must be positive');

  $sql = "
    SELECT ci.product_id, p.available_qty
    FROM cart_items ci
    INNER JOIN products p ON p.product_id = ci.product_id
    WHERE ci.cart_item_id = ? AND ci.user_id = ?
  ";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param('ii', $itemId, $userId);
  $stmt->execute();
  $res = $stmt->get_result();
  if ($res->num_rows === 0) throw new Exception('Cart item not found');
  $row = $res->fetch_assoc();
  $stmt->close();

  $qty = min($qty, (float)$row['available_qty']);
  if ($qty <= 0) throw new Exception('Insufficient stock');

  $stmt = $conn->prepare("UPDATE cart_items SET quantity_kg=?, updated_at=NOW() WHERE cart_item_id=? AND user_id=?");
  $stmt->bind_param('dii', $qty, $itemId, $userId);
  if (!$stmt->execute()) throw new Exception('Update failed');
  $stmt->close();

  echo json_encode(['success'=>true]);
} catch (Throwable $e) {
  http_response_code(400);
  echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
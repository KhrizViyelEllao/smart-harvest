<?php

session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../../db_connect.php';

try {
  if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') throw new Exception('Invalid method');

  $payload = json_decode(file_get_contents('php://input'), true);
  if (!is_array($payload)) throw new Exception('Invalid JSON');

  $userId = (int)($_SESSION['user_id'] ?? ($payload['user_id'] ?? 0));
  $productId = (int)($payload['product_id'] ?? 0);
  $qty = (float)($payload['quantity_kg'] ?? 1);

  if ($userId <= 0) throw new Exception('user_id required');
  if ($productId <= 0) throw new Exception('product_id required');
  if ($qty <= 0) throw new Exception('Quantity must be positive');

  $stmt = $conn->prepare("SELECT available_qty, status FROM products WHERE product_id=?");
  $stmt->bind_param('i', $productId);
  $stmt->execute();
  $res = $stmt->get_result();
  if ($res->num_rows === 0) throw new Exception('Product not found');
  $prod = $res->fetch_assoc();
  $stmt->close();
  if (($prod['status'] ?? '') !== 'available') throw new Exception('Product unavailable');

  $stmt = $conn->prepare("
    INSERT INTO cart_items (user_id, product_id, quantity_kg)
    VALUES (?,?,?)
    ON DUPLICATE KEY UPDATE quantity_kg = LEAST(?, quantity_kg + VALUES(quantity_kg))
  ");
  $allowed = max(0, (float)$prod['available_qty']);
  $qty = min($qty, $allowed);
  if ($qty <= 0) throw new Exception('Insufficient stock');
  $stmt->bind_param('iidd', $userId, $productId, $qty, $allowed);
  if (!$stmt->execute()) throw new Exception('Add to cart failed');
  $stmt->close();

  echo json_encode(['success' => true]);
} catch (Throwable $e) {
  http_response_code(400);
  echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
<?php

session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../../db_connect.php';

try {
  if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') throw new Exception('Invalid method');
  $payload = json_decode(file_get_contents('php://input'), true);
  if (!is_array($payload)) throw new Exception('Invalid JSON');

  $userId   = (int)($_SESSION['user_id'] ?? ($payload['user_id'] ?? 0));
  $orderId  = (int)($payload['order_id'] ?? 0);
  $productId= (int)($payload['product_id'] ?? 0);
  $rating   = (int)($payload['rating'] ?? 0);
  $review   = trim($payload['review_text'] ?? '');

  if ($userId <= 0 || $orderId <= 0 || $productId <= 0) throw new Exception('Missing identifiers');
  if ($rating < 1 || $rating > 5) throw new Exception('Invalid rating');

  $sql = "
    SELECT order_id FROM orders
    WHERE order_id=? AND user_id=? AND product_id=? AND status='completed'
  ";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param('iii', $orderId, $userId, $productId);
  $stmt->execute();
  $res = $stmt->get_result();
  if ($res->num_rows === 0) throw new Exception('Only completed orders can review');
  $stmt->close();

  $stmt = $conn->prepare("
    INSERT INTO product_reviews (order_id, user_id, product_id, rating, review_text)
    VALUES (?,?,?,?,?)
    ON DUPLICATE KEY UPDATE rating=VALUES(rating), review_text=VALUES(review_text), created_at=NOW()
  ");
  $stmt->bind_param('iiiss', $orderId, $userId, $productId, $rating, $review);
  if (!$stmt->execute()) throw new Exception('Save failed');
  $stmt->close();

  echo json_encode(['success'=>true]);
} catch (Throwable $e) {
  http_response_code(400);
  echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
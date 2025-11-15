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

  if ($userId <= 0 || $itemId <= 0) throw new Exception('Missing identifiers');

  $stmt = $conn->prepare("DELETE FROM cart_items WHERE cart_item_id=? AND user_id=?");
  $stmt->bind_param('ii', $itemId, $userId);
  if (!$stmt->execute()) throw new Exception('Delete failed');
  $stmt->close();

  echo json_encode(['success'=>true]);
} catch (Throwable $e) {
  http_response_code(400);
  echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
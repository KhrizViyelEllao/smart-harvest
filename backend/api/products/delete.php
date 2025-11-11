<?php

header('Content-Type: application/json');
include '../../db_connect.php';
session_start();

try {
  if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'DELETE') throw new Exception('Invalid method');
  $raw = json_decode(file_get_contents('php://input'), true);
  if (!is_array($raw)) throw new Exception('Invalid JSON');

  $id = (int)($raw['product_id'] ?? 0);
  if ($id <= 0) throw new Exception('product_id required');

  $stmt = $conn->prepare("DELETE FROM products WHERE product_id=?");
  if (!$stmt) throw new Exception('Prepare failed');
  $stmt->bind_param('i', $id);
  if (!$stmt->execute()) throw new Exception('Delete failed: ' . $stmt->error);
  $stmt->close();

  echo json_encode(['success' => true]);
} catch (Exception $e) {
  http_response_code(400);
  echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
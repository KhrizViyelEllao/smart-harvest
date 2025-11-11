<?php

header('Content-Type: application/json');
include '../../db_connect.php';
session_start();

try {
  if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') throw new Exception('Invalid method');
  $raw = json_decode(file_get_contents('php://input'), true);
  if (!is_array($raw)) throw new Exception('Invalid JSON');

  $id = (int)($raw['product_id'] ?? 0);
  $status = $raw['status'] ?? '';
  if ($id <= 0) throw new Exception('product_id required');
  if (!in_array($status, ['available','sold_out'], true)) throw new Exception('Invalid status');

  $stmt = $conn->prepare("UPDATE products SET status=? WHERE product_id=?");
  if (!$stmt) throw new Exception('Prepare failed');
  $stmt->bind_param('si', $status, $id);
  if (!$stmt->execute()) throw new Exception('Update failed: ' . $stmt->error);
  $stmt->close();

  echo json_encode(['success' => true]);
} catch (Exception $e) {
  http_response_code(400);
  echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
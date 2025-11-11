<?php

header('Content-Type: application/json');
include '../../db_connect.php';
session_start();

try {
  if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') throw new Exception('Invalid method');
  $raw = json_decode(file_get_contents('php://input'), true);
  if (!is_array($raw)) throw new Exception('Invalid JSON');

  $id = (int)($raw['product_id'] ?? 0);
  $name = trim($raw['name'] ?? '');
  $desc = trim($raw['description'] ?? '');
  $price = $raw['price_per_kg'] ?? '';
  $qty = $raw['available_qty'] ?? '';

  if ($id <= 0) throw new Exception('product_id required');
  if ($name === '') throw new Exception('name required');
  if ($price === '' || !is_numeric($price) || $price < 0) throw new Exception('price invalid');
  if ($qty === '' || !is_numeric($qty) || $qty < 0) throw new Exception('qty invalid');

  $stmt = $conn->prepare("UPDATE products SET name=?, description=?, price_per_kg=?, available_qty=? WHERE product_id=?");
  if (!$stmt) throw new Exception('Prepare failed');
  $priceF = (float)$price; $qtyF = (float)$qty;
  $stmt->bind_param('ssddi', $name, $desc, $priceF, $qtyF, $id);
  if (!$stmt->execute()) throw new Exception('Update failed: ' . $stmt->error);
  $stmt->close();

  echo json_encode(['success' => true]);
} catch (Exception $e) {
  http_response_code(400);
  echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
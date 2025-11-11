<?php

header('Content-Type: application/json');
include '../../db_connect.php';
session_start();

try {
  $farmerId = $_SESSION['farmer_id'] ?? null;

  $sql = "
    SELECT
      p.product_id,
      p.harvest_id,
      p.name,
      p.description,
      p.price_per_kg,
      p.available_qty,
      p.image_url,
      p.status,
      p.created_at,
      h.actual_yield_kg,
      c.crop_name,
      f.name AS field_name
    FROM products p
    JOIN harvests h ON p.harvest_id = h.harvest_id
    LEFT JOIN crops c ON h.crop_id = c.crop_id
    LEFT JOIN fields f ON h.field_id = f.field_id
  ";
  $params = [];
  $types = '';
  if ($farmerId) {
    $sql .= " WHERE f.farmer_id = ?";
    $types = 'i';
    $params[] = $farmerId;
  }
  $sql .= " ORDER BY p.created_at DESC";

  $stmt = $conn->prepare($sql);
  if (!$stmt) throw new Exception('Prepare failed: ' . $conn->error);
  if ($types) $stmt->bind_param($types, ...$params);
  if (!$stmt->execute()) throw new Exception('Query failed: ' . $stmt->error);

  $res = $stmt->get_result();
  $data = [];
  while ($r = $res->fetch_assoc()) $data[] = $r;
  $stmt->close();

  echo json_encode(['success' => true, 'data' => $data]);
} catch (Exception $e) {
  http_response_code(500);
  echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
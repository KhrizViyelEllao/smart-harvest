<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../db_connect.php';
session_start();

try {
  $farmerId = $_SESSION['farmer_id'] ?? null;

  // Adjust selected columns to match your schema
  $sql = "SELECT field_id, name, type, geometry FROM fields";
  if ($farmerId) {
    $sql .= " WHERE farmer_id = ?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) throw new Exception('Prepare failed: '.$conn->error);
    $stmt->bind_param('i', $farmerId);
  } else {
    $stmt = $conn->prepare($sql);
    if (!$stmt) throw new Exception('Prepare failed: '.$conn->error);
  }

  $stmt->execute();
  $res = $stmt->get_result();
  $rows = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
  $stmt->close();

  echo json_encode(['success'=>true,'data'=>$rows], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../db_connect.php';
session_start();

try {
  $raw  = file_get_contents('php://input');
  $json = $raw !== '' ? json_decode($raw, true) : null;

  $fieldId = null;
  if (is_array($json) && isset($json['field_id'])) {
    $fieldId = filter_var($json['field_id'], FILTER_VALIDATE_INT);
  } elseif (isset($_POST['field_id'])) {
    $fieldId = filter_var($_POST['field_id'], FILTER_VALIDATE_INT);
  } elseif (isset($_GET['field_id'])) {
    $fieldId = filter_var($_GET['field_id'], FILTER_VALIDATE_INT);
  }

  if (!$fieldId || $fieldId <= 0) {
    http_response_code(400);
    echo json_encode(['success'=>false,'message'=>'Invalid field_id']);
    exit;
  }

  // Field info (optional)
  $field = null;
  if ($stmt = $conn->prepare("SELECT field_id, name FROM fields WHERE field_id = ?")) {
    $stmt->bind_param('i', $fieldId);
    $stmt->execute();
    $res = $stmt->get_result();
    $field = $res ? $res->fetch_assoc() : null;
    $stmt->close();
  }

  // Crops linked to this field
  $sql = "
    SELECT c.crop_id, c.crop_name, c.description
    FROM field_crops fc
    INNER JOIN crops c ON c.crop_id = fc.crop_id
    WHERE fc.field_id = ?
    ORDER BY c.crop_name
  ";
  $stmt = $conn->prepare($sql);
  if (!$stmt) throw new Exception('Prepare failed: '.$conn->error);
  $stmt->bind_param('i', $fieldId);
  $stmt->execute();
  $res = $stmt->get_result();
  $rows = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
  $stmt->close();

  echo json_encode(['success'=>true,'field'=>$field,'data'=>$rows], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}

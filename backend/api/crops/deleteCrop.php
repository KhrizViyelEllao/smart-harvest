<?php

header('Content-Type: application/json');
include '../../db_connect.php';

try {
  if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    http_response_code(405);
    throw new Exception('Method not allowed');
  }

  $raw = json_decode(file_get_contents('php://input'), true);
  $cropId = (int)($raw['crop_id'] ?? 0);
  if ($cropId <= 0) throw new Exception('Invalid crop_id');

  // Get image path
  $stmt = $conn->prepare('SELECT image_path FROM crops WHERE crop_id=?');
  $stmt->bind_param('i', $cropId);
  $stmt->execute();
  $res = $stmt->get_result();
  if ($res->num_rows === 0) throw new Exception('Crop not found');
  $img = (string)($res->fetch_assoc()['image_path'] ?? '');
  $stmt->close();

  // Delete crop (field_crops cascade)
  $del = $conn->prepare('DELETE FROM crops WHERE crop_id=?');
  $del->bind_param('i', $cropId);
  if (!$del->execute()) throw new Exception('Delete failed: '.$del->error);
  $del->close();

  // Remove image file
  if ($img && strpos($img,'assets/images/')===0) {
    $full = realpath(__DIR__ . '/../../../' . $img);
    $root = realpath(__DIR__ . '/../../../assets/images');
    if ($full && $root && strpos($full,$root)===0 && file_exists($full)) @unlink($full);
  }

  echo json_encode(['success'=>true,'message'=>'Crop deleted']);
} catch (Exception $e) {
  http_response_code(400);
  echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}

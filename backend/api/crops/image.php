<?php

// Serve crop image from BLOB
include_once __DIR__ . '/../../db_connect.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : (int)($_GET['crop_id'] ?? 0);
if ($id <= 0) {
  http_response_code(400);
  exit('Invalid id');
}

$stmt = $conn->prepare("SELECT image_path FROM crops WHERE crop_id = ?");
$stmt->bind_param('i', $id);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows === 0) {
  http_response_code(404);
  exit('Not found');
}
$stmt->bind_result($blob);
$stmt->fetch();
$stmt->free_result();
$stmt->close();

if ($blob === null || $blob === '') {
  http_response_code(404);
  exit('No image');
}

$len = strlen($blob);

// MIME detection (fallback jpeg)
$mime = 'image/jpeg';
if (class_exists('finfo')) {
  $finfo = new finfo(FILEINFO_MIME_TYPE);
  $detected = $finfo->buffer($blob);
  if ($detected && strpos($detected, 'image/') === 0) {
    $mime = $detected;
  }
}

header('Content-Type: ' . $mime);
header('Content-Length: ' . $len);
header('Cache-Control: public, max-age=31536000, immutable');
echo $blob;
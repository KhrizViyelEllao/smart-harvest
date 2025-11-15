<?php
header('Content-Type: application/json');
try {
  if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
  }

  include_once __DIR__ . '/../../db_connect.php';

  $crop_name   = trim($_POST['crop_name'] ?? '');
  $category    = trim($_POST['category'] ?? '');
  $duration    = isset($_POST['duration']) ? (int)$_POST['duration'] : null;
  $description = trim($_POST['description'] ?? '');

  if ($crop_name === '' || !$duration) {
    echo json_encode(['success' => false, 'message' => 'Crop name and duration are required.']);
    exit;
  }

  $imageData = null;
  $imageSize = 0;
  if (!empty($_FILES['image_file']['tmp_name']) && is_uploaded_file($_FILES['image_file']['tmp_name'])) {
    // Max 10MB
    if ($_FILES['image_file']['size'] > 10 * 1024 * 1024) {
      echo json_encode(['success' => false, 'message' => 'Image too large (max 10MB).']);
      exit;
    }
    $imageData = file_get_contents($_FILES['image_file']['tmp_name']);
    $imageSize = strlen($imageData);

    // Preflight against MySQL max_allowed_packet (leave ~1MB headroom)
    if ($result = $conn->query("SELECT @@max_allowed_packet AS map")) {
      if ($row = $result->fetch_assoc()) {
        $maxPacket = (int)($row['map'] ?? 0);
        if ($maxPacket && ($imageSize + 1024 * 1024) > $maxPacket) {
          $curMb = number_format($maxPacket / (1024*1024), 2);
          echo json_encode([
            'success' => false,
            'message' => "Image exceeds MySQL max_allowed_packet (${curMb} MB). Increase it to at least 16–64 MB and retry."
          ]);
          exit;
        }
      }
      $result->free();
    }
  }

  $sql = "INSERT INTO crops (crop_name, description, image_path, category, duration)
          VALUES (?, ?, ?, ?, ?)";
  $stmt = $conn->prepare($sql);
  if (!$stmt) {
    throw new Exception('Prepare failed: ' . $conn->error);
  }

  // Bind with blob placeholder, then stream actual data
  $blob = null; // placeholder for 'b' type
  $stmt->bind_param('ssbsi', $crop_name, $description, $blob, $category, $duration);
  if ($imageData !== null) {
    // parameter index 2 (0-based) corresponds to image_path
    $stmt->send_long_data(2, $imageData);
  }

  if (!$stmt->execute()) {
    // Friendlier error for max_allowed_packet
    if (stripos($stmt->error, 'max_allowed_packet') !== false) {
      throw new Exception('Image too large for current MySQL max_allowed_packet. Increase it (e.g., 64M) and retry.');
    }
    throw new Exception('Insert failed: ' . $stmt->error);
  }

  echo json_encode([
    'success' => true,
    'message' => 'New crop added!',
    'crop_id' => $stmt->insert_id
  ]);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

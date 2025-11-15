<?php
header('Content-Type: application/json');
include_once __DIR__ . '/../../db_connect.php';

$method = $_SERVER['REQUEST_METHOD'];
if (!in_array($method, ['POST','DELETE','GET'])) {
  http_response_code(405);
  echo json_encode(['success'=>false,'message'=>'Method not allowed']);
  exit;
}

function pickIntFrom($value) {
  if ($value === null) return null;
  if (is_int($value) && $value > 0) return $value;
  $s = trim((string)$value);
  if ($s === '' || strtolower($s) === 'undefined' || strtolower($s) === 'null') return null;
  if (is_numeric($s)) {
    $n = (int)$s;
    return $n > 0 ? $n : null;
  }
  if (preg_match('/(\d+)/', $s, $m)) {
    $n = (int)$m[1];
    return $n > 0 ? $n : null;
  }
  return null;
}

try {
  // Optional JSON payload
  $raw = file_get_contents('php://input');
  $json = $raw !== '' ? json_decode($raw, true) : null;
  if ($json !== null && json_last_error() !== JSON_ERROR_NONE) {
    $json = null;
  }

  // Merge possible sources
  $src = array_merge($_GET, $_POST, is_array($json) ? $json : []);
  if (isset($_SERVER['HTTP_X_CROP_ID'])) {
    $src['X_Crop_Id'] = $_SERVER['HTTP_X_CROP_ID'];
  }

  // Try multiple keys
  $keys = ['crop_id','id','cropId','cropID','X_Crop_Id'];
  $cropId = null;
  foreach ($keys as $k) {
    if (array_key_exists($k, $src)) {
      $cropId = pickIntFrom($src[$k]);
      if ($cropId) break;
    }
  }

  if (!$cropId) {
    http_response_code(400);
    echo json_encode([
      'success'=>false,
      'message'=>'Invalid or missing crop_id',
      'debug'=>['received'=>$src, 'raw'=>$raw]
    ]);
    exit;
  }

  // Verify exists (optional but clearer)
  $chk = $conn->prepare('SELECT crop_id FROM crops WHERE crop_id = ?');
  if (!$chk) throw new Exception('Prepare failed: ' . $conn->error);
  $chk->bind_param('i', $cropId);
  $chk->execute();
  $chk->store_result();
  if ($chk->num_rows === 0) {
    $chk->close();
    echo json_encode(['success'=>false,'message'=>'Crop not found']);
    exit;
  }
  $chk->close();

  $del = $conn->prepare('DELETE FROM crops WHERE crop_id = ?');
  if (!$del) throw new Exception('Prepare failed: ' . $conn->error);
  $del->bind_param('i', $cropId);
  if (!$del->execute()) throw new Exception('Delete failed: ' . $del->error);
  $affected = $del->affected_rows;
  $del->close();

  if ($affected === 0) {
    echo json_encode(['success'=>false,'message'=>'Nothing deleted']);
    exit;
  }

  echo json_encode(['success'=>true,'message'=>'Crop deleted','crop_id'=>$cropId]);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}

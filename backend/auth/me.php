<?php
$lifetime = 86400; // 24 hours
ini_set('session.gc_maxlifetime', (string)$lifetime);
session_start();
// Refresh cookie expiry on each call (sliding session)
if (ini_get('session.use_cookies')) {
  setcookie(session_name(), session_id(), [
    'expires'  => time()+$lifetime,
    'path'     => '/',
    'secure'   => !empty($_SERVER['HTTPS']),
    'httponly' => true,
    'samesite' => 'Lax',
  ]);
}
header('Content-Type: application/json');
try {
  $uid = $_SESSION['user_id'] ?? null;
  $role = $_SESSION['role'] ?? null;
  if (!$uid || !$role) {
    echo json_encode(['success'=>false]); exit;
  }
  echo json_encode(['success'=>true,'user_id'=>$uid,'role'=>$role,'name'=>($_SESSION['name'] ?? '')]);
} catch (Throwable $e) {
  http_response_code(400);
  echo json_encode(['success'=>false]);
}
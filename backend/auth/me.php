<?php
header('Content-Type: application/json');
session_start();

// Authorization disabled: always succeed
echo json_encode([
  'success' => true,
  'user_id' => $_SESSION['user_id'] ?? null,
  'name'    => $_SESSION['name']    ?? null,
  'role'    => $_SESSION['role']    ?? 'guest',
  'email'   => $_SESSION['email']   ?? null
]);
<?php

session_start();
header('Content-Type: application/json');
include '../../db_connect.php';

try {
  if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'consumer') throw new Exception('Unauthorized');
  if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') throw new Exception('Invalid method');

  $raw = json_decode(file_get_contents('php://input'), true);
  if (!is_array($raw)) throw new Exception('Invalid JSON');

  $uid = (int)$_SESSION['user_id'];
  $name = trim($raw['name'] ?? '');
  $email = trim($raw['email'] ?? '');
  $contact = trim($raw['contact_number'] ?? '');
  $address = trim($raw['address'] ?? '');
  $newPw = $raw['new_password'] ?? '';
  $currPw= $raw['current_password'] ?? '';

  if ($name === '' || $email === '') throw new Exception('Name and Email are required');
  if (!filter_var($email, FILTER_VALIDATE_EMAIL)) throw new Exception('Invalid email');

  // Fetch current user
  $stmt = $conn->prepare("SELECT email, password FROM users WHERE user_id=?");
  $stmt->bind_param('i', $uid);
  $stmt->execute();
  $res = $stmt->get_result();
  $u = $res->fetch_assoc();
  $stmt->close();

  // Unique email check if changed
  if (strcasecmp($email, $u['email']) !== 0) {
    $chk = $conn->prepare("SELECT user_id FROM users WHERE email=? AND user_id<>? LIMIT 1");
    $chk->bind_param('si', $email, $uid);
    $chk->execute(); $chk->store_result();
    if ($chk->num_rows > 0) { $chk->close(); throw new Exception('Email already in use'); }
    $chk->close();
  }

  // Build update
  if ($newPw !== '') {
    if ($currPw === '') throw new Exception('Current password required to change password');
    if (!password_verify($currPw, $u['password']) && $currPw !== $u['password']) {
      throw new Exception('Current password is incorrect');
    }
    $hash = password_hash($newPw, PASSWORD_BCRYPT);
    $stmt2 = $conn->prepare("UPDATE users SET name=?, email=?, contact_number=?, address=?, password=? WHERE user_id=?");
    $stmt2->bind_param('sssssi', $name, $email, $contact, $address, $hash, $uid);
  } else {
    $stmt2 = $conn->prepare("UPDATE users SET name=?, email=?, contact_number=?, address=? WHERE user_id=?");
    $stmt2->bind_param('ssssi', $name, $email, $contact, $address, $uid);
  }

  if (!$stmt2->execute()) { $stmt2->close(); throw new Exception('Update failed'); }
  $stmt2->close();

  // Update session mirrors
  $_SESSION['name'] = $name;
  $_SESSION['email'] = $email;
  $_SESSION['contact'] = $contact;
  $_SESSION['address'] = $address;

  echo json_encode(['success'=>true,'data'=>[
    'name'=>$name,'email'=>$email,'contact_number'=>$contact,'address'=>$address
  ]]);
} catch (Exception $e) {
  http_response_code(400);
  echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
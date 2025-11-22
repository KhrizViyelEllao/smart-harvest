<?php
declare(strict_types=1);
ob_start();
session_start();
include 'db_connect.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
  header('Location: /index.php?login_error=' . urlencode('Invalid request'));
  exit;
}

$login = trim($_POST['username'] ?? '');
$pass  = $_POST['password'] ?? '';
if ($login === '' || $pass === '') {
  header('Location: /index.php?login_error=' . urlencode('Missing credentials'));
  exit;
}

$stmt = $conn->prepare("SELECT user_id,name,role,username,email,password,is_active,address,contact_number
                        FROM users WHERE username=? OR email=? LIMIT 1");
if (!$stmt) {
  header('Location: /index.php?login_error=' . urlencode('Server error'));
  exit;
}
$stmt->bind_param('ss', $login, $login);
$stmt->execute();
$res = $stmt->get_result();
if (!$res || $res->num_rows === 0) {
  $stmt->close();
  header('Location: /index.php?login_error=' . urlencode('Invalid credentials'));
  exit;
}
$u = $res->fetch_assoc();
$stmt->close();

if ((int)$u['is_active'] !== 1) {
  header('Location: /index.php?login_error=' . urlencode('Account inactive'));
  exit;
}

$valid = password_verify($pass, $u['password']) || $pass === $u['password'];
if (!$valid) {
  header('Location: /index.php?login_error=' . urlencode('Invalid credentials'));
  exit;
}

$_SESSION['user_id']   = (int)$u['user_id'];
$_SESSION['name']      = $u['name'];
$_SESSION['role']      = $u['role'];
$_SESSION['username']  = $u['username'];
$_SESSION['email']     = $u['email'];
$_SESSION['address']   = $u['address'];
$_SESSION['contact']   = $u['contact_number'];

if ($u['role'] === 'consumer') {
  header('Location: /pages/shop.php');
} else {
  header('Location: /layout.php');
}
exit;

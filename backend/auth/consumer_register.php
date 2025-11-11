<?php

session_start();
include '../db_connect.php';

// Redirect helper
function back($q){ header('Location: /Agrilink/index.php?signup_error='.urlencode($q)); exit; }
function success($u){ header('Location: /Agrilink/index.php?signup_ok='.urlencode('Account created. Please log in.').'&prefill='.urlencode($u)); exit; }

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') back('Invalid request');

$name      = trim($_POST['name'] ?? '');
$username  = trim($_POST['username'] ?? '');
$email     = trim($_POST['email'] ?? '');
$contact   = trim($_POST['contact_number'] ?? '');
$address   = trim($_POST['address'] ?? '');
$pw        = $_POST['password'] ?? '';
$pw2       = $_POST['password_confirm'] ?? '';

if ($name==='' || $username==='' || $email==='' || $pw==='') back('Missing required fields');
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) back('Invalid email');
if ($pw !== $pw2) back('Passwords do not match');
if (strlen($pw) < 6) back('Password too short');

try {
  // Ensure users table exists (optional safety)
  $conn->query("CREATE TABLE IF NOT EXISTS users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    role ENUM('consumer','farmer','admin') NOT NULL DEFAULT 'consumer',
    username VARCHAR(80) NOT NULL UNIQUE,
    email VARCHAR(150) NOT NULL UNIQUE,
    contact_number VARCHAR(30) DEFAULT NULL,
    address VARCHAR(255) DEFAULT NULL,
    password VARCHAR(255) NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

  // Uniqueness check
  $chk = $conn->prepare("SELECT user_id FROM users WHERE username=? OR email=? LIMIT 1");
  $chk->bind_param('ss',$username,$email);
  $chk->execute();
  $chk->store_result();
  if ($chk->num_rows>0) { $chk->close(); back('Username or email already used'); }
  $chk->close();

  $hash = password_hash($pw, PASSWORD_BCRYPT);
  $role = 'consumer';
  $stmt = $conn->prepare("INSERT INTO users (name, role, username, email, contact_number, address, password) VALUES (?,?,?,?,?,?,?)");
  if(!$stmt) back('DB error');
  $stmt->bind_param('sssssss',$name,$role,$username,$email,$contact,$address,$hash);
  if(!$stmt->execute()) { $stmt->close(); back('Insert failed'); }
  $stmt->close();

  // Do NOT auto-login; show login modal instead
  success($username);
} catch (Exception $e) {
  back('Registration failed');
}
<?php
session_start();
header('Content-Type: text/html; charset=utf-8');
include '../db_connect.php';

try {
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
        header('Location: /index.php');
        exit;
    }

    $userOrEmail = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($userOrEmail === '' || $password === '') {
        header('Location: /index.php?error=' . urlencode('Please enter credentials') . '#login');
        exit;
    }

    $sql = "SELECT user_id, name, role, username, email, password, is_active
            FROM users
            WHERE (email = ? OR username = ?)
            LIMIT 1";
    $stmt = $conn->prepare($sql);
    if (!$stmt) throw new Exception('DB error');
    $stmt->bind_param('ss', $userOrEmail, $userOrEmail);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res->num_rows === 0) {
        header('Location: /index.php?error=' . urlencode('Invalid credentials') . '#login');
        exit;
    }
    $u = $res->fetch_assoc();
    $stmt->close();

    if ((int)$u['is_active'] !== 1) {
        header('Location: /index.php?error=' . urlencode('Account is inactive') . '#login');
        exit;
    }
    if (!password_verify($password, $u['password'])) {
        header('Location: /index.php?error=' . urlencode('Invalid credentials') . '#login');
        exit;
    }

    // Only allow consumers here; extend as needed
    if ($u['role'] !== 'consumer') {
        header('Location: /index.php?error=' . urlencode('Not a consumer account') . '#login');
        exit;
    }

    // Start session
    $_SESSION['user_id'] = (int)$u['user_id'];
    $_SESSION['name'] = $u['name'];
    $_SESSION['role'] = $u['role'];
    $_SESSION['username'] = $u['username'];
    $_SESSION['email'] = $u['email'];

    // Redirect after login (adjust destination as needed)
    header('Location: /index.php');
    exit;
} catch (Exception $e) {
    header('Location: /index.php?error=' . urlencode('Login failed') . '#login');
    exit;
}
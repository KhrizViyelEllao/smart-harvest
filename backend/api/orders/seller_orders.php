<?php

session_start();
header('Content-Type: application/json');
ini_set('display_errors','0'); ini_set('html_errors','0');
include '../../db_connect.php';

try {
  $roles = ['farm_owner','farmer','admin'];
  if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'] ?? '', $roles, true)) {
    http_response_code(401);
    throw new Exception('Unauthorized');
  }
  $uid = (int)$_SESSION['user_id'];
  $status = $_GET['status'] ?? '';
  $allowed = ['pending','confirmed','completed','cancelled'];
  $filterStatus = in_array($status, $allowed, true) ? $status : '';

  // Try owner via harvests
  $ownerCol = null;
  $tbl = $conn->query("SHOW TABLES LIKE 'harvests'");
  if ($tbl && $tbl->num_rows) {
    foreach (['user_id','farmer_id','owner_id','created_by'] as $c) {
      $chk = $conn->query("SHOW COLUMNS FROM harvests LIKE '$c'");
      if ($chk && $chk->num_rows === 1) { $ownerCol = $c; break; }
    }
  }

  $params = []; $types = '';
  if ($ownerCol) {
    $sql = "SELECT o.order_id,o.product_id,p.name AS product_name,o.buyer_name,o.contact_info,
                   o.quantity_kg,o.total_price,o.status,o.created_at
            FROM orders o
            INNER JOIN products p ON p.product_id=o.product_id
            INNER JOIN harvests h ON h.harvest_id=p.harvest_id
            WHERE h.`$ownerCol` = ?";
    $params[] = $uid; $types .= 'i';
    if ($filterStatus !== '') { $sql .= " AND o.status=?"; $params[]=$filterStatus; $types.='s'; }
    $sql .= " ORDER BY FIELD(o.status,'pending','confirmed','completed','cancelled'), o.created_at DESC";
  } else {
    // Fallback: no owner info, show all orders to allowed roles
    $sql = "SELECT o.order_id,o.product_id,p.name AS product_name,o.buyer_name,o.contact_info,
                   o.quantity_kg,o.total_price,o.status,o.created_at
            FROM orders o
            INNER JOIN products p ON p.product_id=o.product_id";
    if ($filterStatus !== '') { $sql .= " WHERE o.status=?"; $params[]=$filterStatus; $types.='s'; }
    $sql .= " ORDER BY FIELD(o.status,'pending','confirmed','completed','cancelled'), o.created_at DESC";
  }

  $stmt = $conn->prepare($sql);
  if ($params) $stmt->bind_param($types, ...$params);
  $stmt->execute();
  $res = $stmt->get_result();
  $data = [];
  while ($r = $res->fetch_assoc()) $data[] = $r;
  $stmt->close();

  echo json_encode(['success'=>true,'data'=>$data,'ownership'=>$ownerCol?('harvests.'.$ownerCol):'fallback']);
} catch (Exception $e) {
  http_response_code(400);
  echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
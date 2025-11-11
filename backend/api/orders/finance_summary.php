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

  // Try owner via harvests
  $ownerCol = null;
  $tbl = $conn->query("SHOW TABLES LIKE 'harvests'");
  if ($tbl && $tbl->num_rows) {
    foreach (['user_id','farmer_id','owner_id','created_by'] as $c) {
      $chk = $conn->query("SHOW COLUMNS FROM harvests LIKE '$c'");
      if ($chk && $chk->num_rows === 1) { $ownerCol = $c; break; }
    }
  }

  if ($ownerCol) {
    $sumQ = "
      SELECT
        SUM(CASE WHEN o.status='completed' THEN o.total_price ELSE 0 END) AS revenue_completed,
        SUM(CASE WHEN o.status='confirmed' THEN o.total_price ELSE 0 END) AS awaiting_completion,
        SUM(CASE WHEN o.status='pending' THEN o.total_price ELSE 0 END) AS pending_value,
        SUM(CASE WHEN o.status='cancelled' THEN o.total_price ELSE 0 END) AS cancelled_value,
        COUNT(CASE WHEN o.status='completed' THEN 1 END) AS completed_count
      FROM orders o
      INNER JOIN products p ON p.product_id=o.product_id
      INNER JOIN harvests h ON h.harvest_id=p.harvest_id
      WHERE h.`$ownerCol`=?
    ";
    $stmt=$conn->prepare($sumQ);
    $stmt->bind_param('i',$uid);
    $stmt->execute();
    $summary=$stmt->get_result()->fetch_assoc();
    $stmt->close();

    $topQ = "
      SELECT p.name, SUM(o.total_price) AS total_sales, SUM(o.quantity_kg) AS qty_sold
      FROM orders o
      INNER JOIN products p ON p.product_id=o.product_id
      INNER JOIN harvests h ON h.harvest_id=p.harvest_id
      WHERE h.`$ownerCol`=? AND o.status='completed'
      GROUP BY p.product_id,p.name
      ORDER BY total_sales DESC
      LIMIT 5
    ";
    $stmt=$conn->prepare($topQ);
    $stmt->bind_param('i',$uid);
    $stmt->execute();
    $top=[];
    $rs=$stmt->get_result();
    while($row=$rs->fetch_assoc()) $top[]=$row;
    $stmt->close();

    echo json_encode(['success'=>true,'summary'=>$summary,'top_products'=>$top,'ownership'=>'harvests.'.$ownerCol]);
  } else {
    // Fallback: compute over all orders (no seller scoping)
    $summary = $conn->query("
      SELECT
        SUM(CASE WHEN status='completed' THEN total_price ELSE 0 END) AS revenue_completed,
        SUM(CASE WHEN status='confirmed' THEN total_price ELSE 0 END) AS awaiting_completion,
        SUM(CASE WHEN status='pending' THEN total_price ELSE 0 END) AS pending_value,
        SUM(CASE WHEN status='cancelled' THEN total_price ELSE 0 END) AS cancelled_value,
        COUNT(CASE WHEN status='completed' THEN 1 END) AS completed_count
      FROM orders
    ")->fetch_assoc();

    $top = [];
    $rs = $conn->query("
      SELECT p.name, SUM(o.total_price) AS total_sales, SUM(o.quantity_kg) AS qty_sold
      FROM orders o
      INNER JOIN products p ON p.product_id=o.product_id
      WHERE o.status='completed'
      GROUP BY p.product_id,p.name
      ORDER BY total_sales DESC
      LIMIT 5
    ");
    while($row=$rs->fetch_assoc()) $top[]=$row;

    echo json_encode(['success'=>true,'summary'=>$summary,'top_products'=>$top,'ownership'=>'fallback']);
  }
} catch (Exception $e) {
  http_response_code(400);
  echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
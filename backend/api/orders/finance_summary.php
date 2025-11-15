<?php

header('Content-Type: application/json');
require_once __DIR__ . '/../../db_connect.php';

try {
  $summarySql = "
    SELECT
      COALESCE(SUM(CASE WHEN status='completed' THEN total_price END),0) AS revenue_completed,
      COALESCE(SUM(CASE WHEN status='confirmed' THEN total_price END),0) AS awaiting_completion,
      COALESCE(SUM(CASE WHEN status='pending' THEN total_price END),0)   AS pending_value,
      COALESCE(SUM(CASE WHEN status='cancelled' THEN total_price END),0) AS cancelled_value
    FROM orders
  ";
  $summaryRes = $conn->query($summarySql);
  $summary = $summaryRes ? $summaryRes->fetch_assoc() : [
    'revenue_completed'=>0,'awaiting_completion'=>0,'pending_value'=>0,'cancelled_value'=>0
  ];

  $topSql = "
    SELECT
      p.name,
      COALESCE(SUM(o.quantity_kg),0)   AS qty_sold,
      COALESCE(SUM(o.total_price),0)   AS total_sales
    FROM orders o
    INNER JOIN products p ON p.product_id = o.product_id
    WHERE o.status = 'completed'
    GROUP BY p.product_id, p.name
    ORDER BY total_sales DESC
    LIMIT 5
  ";
  $topRes = $conn->query($topSql);
  $top = $topRes ? $topRes->fetch_all(MYSQLI_ASSOC) : [];

  echo json_encode(['success'=>true,'summary'=>$summary,'top_products'=>$top], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
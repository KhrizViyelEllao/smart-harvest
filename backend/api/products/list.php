<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../../db_connect.php';

// Helper: send error JSON and proper HTTP status
function fail($msg, $code=500){
  http_response_code($code);
  echo json_encode(['success'=>false,'message'=>$msg]);
  exit;
}

mysqli_report(MYSQLI_REPORT_OFF); // prevent warnings
try {
  // Detect columns that exist in products table
  $hasHarvestId = false;
  $hasQualityCol = false;

  $colRes = $conn->query("SHOW COLUMNS FROM products");
  if($colRes){
    while($c = $colRes->fetch_assoc()){
      if(strtolower($c['Field']) === 'harvest_id') $hasHarvestId = true;
      if(strtolower($c['Field']) === 'quality')    $hasQualityCol = true;
    }
    $colRes->close();
  }

  if ($hasHarvestId) {
    // Direct join using products.harvest_id
    $sql = "
      SELECT
        p.product_id,
        p.name,
        p.description,
        p.price_per_kg,
        p.available_qty,
        p.status,
        p.image_url,
        p.created_at,
        p.harvest_id,
        ".($hasQualityCol ? "p.quality AS product_quality," : "NULL AS product_quality,")."
        h.quality AS harvest_quality,
        h.actual_yield_kg,
        c.crop_name,
        f.name AS field_name
      FROM products p
      LEFT JOIN harvests h ON h.harvest_id = p.harvest_id
      LEFT JOIN crops c ON c.crop_id = h.crop_id
      LEFT JOIN fields f ON f.field_id = h.field_id
      ORDER BY p.created_at DESC
    ";
  } else {
    // No harvest_id column: pick latest harvest per crop via subquery
    // Assumes products table has crop_id; if not, remove crop-related fields.
    $hasCropId = false;
    $colRes2 = $conn->query("SHOW COLUMNS FROM products");
    if($colRes2){
      while($c2 = $colRes2->fetch_assoc()){
        if(strtolower($c2['Field']) === 'crop_id') $hasCropId = true;
      }
      $colRes2->close();
    }

    if($hasCropId){
      $sql = "
        SELECT
          p.product_id,
          p.name,
          p.description,
          p.price_per_kg,
          p.available_qty,
          p.status,
          p.image_url,
          p.created_at,
          ".($hasQualityCol ? "p.quality AS product_quality," : "NULL AS product_quality,")."
          h.quality AS harvest_quality,
          h.actual_yield_kg,
          c.crop_name,
          f.name AS field_name
        FROM products p
        LEFT JOIN (
          SELECT h1.*
          FROM harvests h1
          INNER JOIN (
            SELECT crop_id, MAX(harvest_date) AS max_date
            FROM harvests
            GROUP BY crop_id
          ) latest ON latest.crop_id = h1.crop_id AND latest.max_date = h1.harvest_date
        ) h ON h.crop_id = p.crop_id
        LEFT JOIN crops c ON c.crop_id = h.crop_id
        LEFT JOIN fields f ON f.field_id = h.field_id
        ORDER BY p.created_at DESC
      ";
    } else {
      // No crop_id either: just return products (quality null)
      $sql = "
        SELECT
          p.product_id,
          p.name,
          p.description,
          p.price_per_kg,
          p.available_qty,
          p.status,
          p.image_url,
          p.created_at,
          ".($hasQualityCol ? "p.quality AS product_quality," : "NULL AS product_quality,")."
          NULL AS harvest_quality,
          NULL AS actual_yield_kg,
          NULL AS crop_name,
          NULL AS field_name
        FROM products p
        ORDER BY p.created_at DESC
      ";
    }
  }

  $res = $conn->query($sql);
  if(!$res){
    fail('SQL error: '.$conn->error, 500);
  }

  $data = [];
  while($row = $res->fetch_assoc()){
    // Unified quality (harvest preferred)
    $row['quality'] = $row['harvest_quality'] ?: ($row['product_quality'] ?? null);
    $data[] = $row;
  }
  $res->close();

  echo json_encode(['success'=>true,'data'=>$data]);
} catch (Throwable $e){
  fail($e->getMessage(), 500);
}
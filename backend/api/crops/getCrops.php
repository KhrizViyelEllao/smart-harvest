<?php
header('Content-Type: application/json');
include '../../db_connect.php';
session_start();

/*
  Problem: image_path is a BLOB. Returning raw binary in JSON breaks parsing (frontend => "error loading crops").
  Fix: Do NOT send the BLOB; send a has_image flag. Frontend already uses image.php?id=... to fetch the image.
*/

try {
    $farmerId = $_SESSION['farmer_id'] ?? null;

    // On–farm crops (those already assigned to fields)
    $onFarmSql = "
        SELECT
            fc.id              AS field_crop_id,
            fc.field_id,
            fc.crop_id,
            fc.planting_date,
            fc.expected_harvest,
            f.name             AS field_name,
            f.type             AS field_type,
            c.crop_name,
            c.duration,
            (c.image_path IS NOT NULL) AS has_image
        FROM field_crops fc
        INNER JOIN fields f ON fc.field_id = f.field_id
        INNER JOIN crops  c ON fc.crop_id = c.crop_id
    ";
    if ($farmerId) {
        $onFarmSql .= " WHERE f.farmer_id = ?";
    }

    $onFarmStmt = $conn->prepare($onFarmSql);
    if (!$onFarmStmt) {
        throw new Exception("Prepare failed (onFarm): " . $conn->error);
    }
    if ($farmerId) {
        $onFarmStmt->bind_param('i', $farmerId);
    }
    $onFarmStmt->execute();
    $onFarmResult = $onFarmStmt->get_result();
    $onFarm = $onFarmResult ? $onFarmResult->fetch_all(MYSQLI_ASSOC) : [];
    $onFarmStmt->close();

    // Not–yet–on–farm crops
    $notOnFarmSql = "
        SELECT
            c.crop_id,
            c.crop_name,
            c.duration,
            (c.image_path IS NOT NULL) AS has_image
        FROM crops c
    ";
    if ($farmerId) {
        $notOnFarmSql .= "
            WHERE c.crop_id NOT IN (
                SELECT fc.crop_id
                FROM field_crops fc
                INNER JOIN fields f ON fc.field_id = f.field_id
                WHERE f.farmer_id = ?
            )
        ";
    } else {
        $notOnFarmSql .= "
            WHERE c.crop_id NOT IN (SELECT crop_id FROM field_crops)
        ";
    }

    $notOnFarmStmt = $conn->prepare($notOnFarmSql);
    if (!$notOnFarmStmt) {
        throw new Exception("Prepare failed (notOnFarm): " . $conn->error);
    }
    if ($farmerId) {
        $notOnFarmStmt->bind_param('i', $farmerId);
    }
    $notOnFarmStmt->execute();
    $notOnFarmResult = $notOnFarmStmt->get_result();
    $notOnFarm = $notOnFarmResult ? $notOnFarmResult->fetch_all(MYSQLI_ASSOC) : [];
    $notOnFarmStmt->close();

    echo json_encode([
        'success'    => true,
        'onFarm'     => $onFarm,
        'notOnFarm'  => $notOnFarm
    ], JSON_UNESCAPED_UNICODE);
} catch (Exception $ex) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $ex->getMessage()
    ]);
}
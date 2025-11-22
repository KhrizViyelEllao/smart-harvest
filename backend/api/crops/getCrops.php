<?php
header('Content-Type: application/json');
include '../../db_connect.php';
session_start();

try {
    /* ============================================================
       ON-FARM CROPS (All crops assigned to ANY field - admin view)
    ============================================================ */
    $onFarmSql = "
        SELECT
            fc.id AS field_crop_id,
            fc.field_id,
            fc.crop_id,
            fc.planting_date,
            fc.expected_harvest,
            f.name AS field_name,
            f.type AS field_type,
            c.crop_name,
            c.description,
            c.duration,
            c.category,
            c.created_at,
            (LENGTH(c.image_path) > 0) AS has_image
        FROM field_crops fc
        INNER JOIN fields f ON fc.field_id = f.field_id
        INNER JOIN crops c ON fc.crop_id = c.crop_id
        WHERE fc.crop_id IS NOT NULL  -- Ensure crop_id is not NULL
    ";

    $onFarmStmt = $conn->prepare($onFarmSql);
    if (!$onFarmStmt) {
        throw new Exception("Prepare failed (onFarm): " . $conn->error);
    }
    
    $onFarmStmt->execute();
    $onFarmResult = $onFarmStmt->get_result();
    $onFarm = $onFarmResult ? $onFarmResult->fetch_all(MYSQLI_ASSOC) : [];
    $onFarmStmt->close();

    /* ============================================================
       ALL CROPS (All crops in the database - master list)
    ============================================================ */
    $allCropsSql = "
        SELECT
            c.crop_id,
            c.crop_name,
            c.description,
            c.duration,
            c.category,
            c.created_at,
            (LENGTH(c.image_path) > 0) AS has_image
        FROM crops c
        ORDER BY c.crop_name
    ";

    $allCropsStmt = $conn->prepare($allCropsSql);
    if (!$allCropsStmt) {
        throw new Exception("Prepare failed (allCrops): " . $conn->error);
    }
    $allCropsStmt->execute();
    $allCropsResult = $allCropsStmt->get_result();
    $allCrops = $allCropsResult ? $allCropsResult->fetch_all(MYSQLI_ASSOC) : [];
    $allCropsStmt->close();

    echo json_encode([
        'success'   => true,
        'onFarm'    => $onFarm,
        'notOnFarm' => $allCrops
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $ex) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $ex->getMessage()
    ]);
}
?>
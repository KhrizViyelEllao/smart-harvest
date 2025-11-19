<?php
header('Content-Type: application/json');
include '../../db_connect.php';
session_start();

/*
  Fixes applied:
  - BLOB cannot be sent in JSON → return has_image instead
  - NOT IN replaced with NOT EXISTS to avoid NULL-breaking issue
  - LENGTH(image_path) used for more reliable BLOB checking
*/

try {
    $farmerId = $_SESSION['farmer_id'] ?? null;

    /* ============================================================
       ON-FARM CROPS (Crops assigned to fields)
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
            c.duration,
            (LENGTH(c.image_path) > 0) AS has_image
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
        $onFarmStmt->bind_param("i", $farmerId);
    }

    $onFarmStmt->execute();
    $onFarmResult = $onFarmStmt->get_result();
    $onFarm = $onFarmResult ? $onFarmResult->fetch_all(MYSQLI_ASSOC) : [];
    $onFarmStmt->close();


    /* ============================================================
       NOT-YET-ON-FARM CROPS (Crops NOT assigned to any field)
       FIXED using NOT EXISTS so NULL crop_id won't break results
    ============================================================ */

    if ($farmerId) {
        // Filter by farmer
        $notOnFarmSql = "
            SELECT
                c.crop_id,
                c.crop_name,
                c.duration,
                (LENGTH(c.image_path) > 0) AS has_image
            FROM crops c
            WHERE NOT EXISTS (
                SELECT 1
                FROM field_crops fc
                INNER JOIN fields f ON fc.field_id = f.field_id
                WHERE f.farmer_id = ?
                AND fc.crop_id = c.crop_id
            )
        ";

        $notOnFarmStmt = $conn->prepare($notOnFarmSql);
        if (!$notOnFarmStmt) {
            throw new Exception("Prepare failed (notOnFarm-farmer): " . $conn->error);
        }
        $notOnFarmStmt->bind_param("i", $farmerId);

    } else {
        // No farmer filter
        $notOnFarmSql = "
            SELECT
                c.crop_id,
                c.crop_name,
                c.duration,
                (LENGTH(c.image_path) > 0) AS has_image
            FROM crops c
            WHERE NOT EXISTS (
                SELECT 1
                FROM field_crops fc
                WHERE fc.crop_id = c.crop_id
            )
        ";

        $notOnFarmStmt = $conn->prepare($notOnFarmSql);
        if (!$notOnFarmStmt) {
            throw new Exception("Prepare failed (notOnFarm): " . $conn->error);
        }
    }

    $notOnFarmStmt->execute();
    $notOnFarmResult = $notOnFarmStmt->get_result();
    $notOnFarm = $notOnFarmResult ? $notOnFarmResult->fetch_all(MYSQLI_ASSOC) : [];
    $notOnFarmStmt->close();


    /* ============================================================
       FINAL OUTPUT
    ============================================================ */
    echo json_encode([
        'success'   => true,
        'onFarm'    => $onFarm,
        'notOnFarm' => $notOnFarm
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $ex) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $ex->getMessage()
    ]);
}
?>

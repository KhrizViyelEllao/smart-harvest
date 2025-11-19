<?php

declare(strict_types=1);

date_default_timezone_set('Asia/Manila');

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../db_connect.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$conn->set_charset('utf8mb4');

$today    = new DateTimeImmutable('today');
$todayStr = $today->format('Y-m-d');

function runInsert(mysqli $conn, string $sql, string $types = '', array $params = []): void
{
    try {
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }

        if ($types !== '') {
            $stmt->bind_param($types, ...$params);
        }

        $stmt->execute();
        $affected = $stmt->affected_rows;
        $stmt->close();

        echo "[DEBUG] Query executed successfully. Rows affected: $affected\n";
    } catch (Exception $e) {
        echo "[ERROR] SQL execution failed: " . $e->getMessage() . "\n";
        file_put_contents(__DIR__ . '/../logs/daily_notifications.log', date('[Y-m-d H:i:s] ') . $e->getMessage() . PHP_EOL, FILE_APPEND);
    }
}


/**
 * 1) Tasks starting today → notify admin only
 */
$sqlTasksDue = "
INSERT INTO notifications (user_id, field_id, task_id, message, type, created_at)
SELECT u.user_id, ft.field_id, ft.field_task_id,
       CASE
           WHEN LOWER(t.task_name) LIKE '%weed%' THEN CONCAT('Weeding is due today for ', COALESCE(f.name, CONCAT('Field ', ft.field_id)), '.')
           WHEN LOWER(t.task_name) LIKE '%fertiliz%' THEN CONCAT('Fertilizer application is scheduled today for ', COALESCE(f.name, CONCAT('Field ', ft.field_id)), '.')
           ELSE CONCAT('Task \"', t.task_name, '\" is scheduled today for ', COALESCE(f.name, CONCAT('Field ', ft.field_id)), '.')
       END AS message_text,
       CASE
           WHEN LOWER(t.task_name) LIKE '%weed%' THEN 'weeding'
           WHEN LOWER(t.task_name) LIKE '%fertiliz%' THEN 'fertilizer'
           ELSE 'general'
       END AS notification_type,
       NOW()
FROM field_tasks ft
INNER JOIN tasks t ON t.task_id = ft.task_id
LEFT JOIN fields f ON f.field_id = ft.field_id
CROSS JOIN users u
WHERE ft.start_date = ?
  AND COALESCE(ft.status, 'pending') IN ('pending', 'in_progress')
  AND u.role = 'admin'
  AND NOT EXISTS (
      SELECT 1
      FROM notifications n
      WHERE n.task_id = ft.field_task_id
        AND DATE(n.created_at) = ?
        AND n.type = 
            CASE
               WHEN LOWER(t.task_name) LIKE '%weed%' THEN 'weeding'
               WHEN LOWER(t.task_name) LIKE '%fertiliz%' THEN 'fertilizer'
               ELSE 'general'
            END
  )
";
runInsert($conn, $sqlTasksDue, 'ss', [$todayStr, $todayStr]);



/**
 * 2) Harvest due in 10 days → using field_crops.expected_harvest
 */
$sqlHarvestSoon = "
INSERT INTO notifications (user_id, field_id, task_id, message, type, created_at)
SELECT u.user_id, fc.field_id, NULL,
       CONCAT(c.crop_name, ' in ', COALESCE(f.name, CONCAT('Field ', fc.field_id)),
              ' will be ready for harvest in 10 days.') AS message_text,
       'harvest',
       NOW()
FROM field_crops fc
LEFT JOIN fields f ON f.field_id = fc.field_id
LEFT JOIN crops c ON c.crop_id = fc.crop_id
CROSS JOIN users u
WHERE DATEDIFF(fc.expected_harvest, ?) = 10
  AND u.role = 'admin'
  AND NOT EXISTS (
      SELECT 1
      FROM notifications n
      WHERE n.field_id = fc.field_id
        AND n.type = 'harvest'
        AND DATE(n.created_at) = ?
        AND n.message = CONCAT(c.crop_name, ' in ', COALESCE(f.name, CONCAT('Field ', fc.field_id)),
                               ' will be ready for harvest in 10 days.')
  )
";
runInsert($conn, $sqlHarvestSoon, 'ss', [$todayStr, $todayStr]);



/**
 * 3) Harvest due today → using field_crops.expected_harvest
 */
$sqlHarvestToday = "
INSERT INTO notifications (user_id, field_id, task_id, message, type, created_at)
SELECT u.user_id, fc.field_id, NULL,
       CONCAT('Your crop ', c.crop_name, ' in ',
              COALESCE(f.name, CONCAT('Field ', fc.field_id)),
              ' is ready to harvest today.') AS message_text,
       'harvest',
       NOW()
FROM field_crops fc
LEFT JOIN fields f ON f.field_id = fc.field_id
LEFT JOIN crops c ON c.crop_id = fc.crop_id
CROSS JOIN users u
WHERE fc.expected_harvest = ?
  AND u.role = 'admin'
  AND NOT EXISTS (
      SELECT 1
      FROM notifications n
      WHERE n.field_id = fc.field_id
        AND n.type = 'harvest'
        AND DATE(n.created_at) = ?
        AND n.message = CONCAT('Your crop ', c.crop_name, ' in ',
                               COALESCE(f.name, CONCAT('Field ', fc.field_id)),
                               ' is ready to harvest today.')
  )
";
runInsert($conn, $sqlHarvestToday, 'ss', [$todayStr, $todayStr]);


if (PHP_SAPI === 'cli') {
    echo '[' . date('Y-m-d H:i:s') . "] Daily notifications processed.\n";
}


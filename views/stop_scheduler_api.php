<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

include("../includes/auth_check.php");
include("../config/connect.php");

header('Content-Type: application/json');

$scheduler_id  = intval($_POST['task_id'] ?? 0);
$complaint_id  = intval($_POST['complaint_id'] ?? 0);

if ($scheduler_id <= 0 || $complaint_id <= 0) {
    echo json_encode([
        "status" => "error",
        "message" => "Invalid input"
    ]);
    exit;
}

/* ===============================
   1️⃣ STOP SCHEDULER
================================ */
$stopScheduler = mysqli_query(
    $db_equip,
    "
    UPDATE scheduler_daily_tasks
    SET status = 0
    WHERE id = $scheduler_id
    "
);

if (!$stopScheduler) {
    echo json_encode([
        "status" => "error",
        "message" => mysqli_error($db_equip)
    ]);
    exit;
}

/* ===============================
   2️⃣ INSERT TRACKING RECORD
   (MATCHES TABLE STRUCTURE)
================================ */
$insertCron = mysqli_query(
    $db_equip,
    "
    INSERT INTO cron_scheduler_tracking
        (old_complaint_id, new_complaint_id, date_of_close)
    VALUES
        ($complaint_id, 0, NOW())
    "
);

if (!$insertCron) {
    echo json_encode([
        "status" => "error",
        "message" => mysqli_error($db_equip)
    ]);
    exit;
}

/* ===============================
   SUCCESS
================================ */
echo json_encode([
    "status" => "success"
]);

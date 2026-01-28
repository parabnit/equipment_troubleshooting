<?php
include("../includes/auth_check.php");
include("../config/connect.php");
header("Content-Type: application/json");

$task_id = intval($_POST['task_id']);

if ($task_id > 0) {

    mysqli_query(
        $db_equip,
        "UPDATE scheduler_daily_tasks 
         SET status = 1 
         WHERE id = $task_id"
    );

    echo json_encode(["status" => "success"]);
} else {
    echo json_encode(["status" => "error", "message" => "Invalid task"]);
}

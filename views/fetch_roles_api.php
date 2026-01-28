<?php
include("../includes/auth_check.php");
include("../config/connect.php");
include("../includes/common.php");

header("Content-Type: application/json");
error_reporting(E_ALL);
ini_set("display_errors", 1);

$data = [];

// Use correct DB connection
$conn = $db_equip;

// Select from new table
$stmt = $conn->prepare("SELECT role_id, role, description FROM role_master ORDER BY role_id DESC");

if ($stmt) {
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }

    $stmt->close();
} else {
    echo json_encode([
        "data" => [],
        "error" => $conn->error
    ]);
    exit;
}

echo json_encode(["data" => $data]);
?>

<?php
include("../config/connect.php");
header("Content-Type: application/json");
error_reporting(E_ALL);
ini_set("display_errors", 1);

$input = json_decode(file_get_contents("php://input"), true);

$role = $input['role'] ?? '';
$description = $input['description'] ?? '';

if ($role == "") {
    echo json_encode(["status" => "error", "message" => "Role is required"]);
    exit;
}

// Use correct DB connection
$conn = $db_equip;

// Prepared statement for new table
$stmt = $conn->prepare("INSERT INTO role_master (role, description) VALUES (?, ?)");

if ($stmt) {
    $stmt->bind_param("ss", $role, $description);

    if ($stmt->execute()) {
        echo json_encode(["status" => "success", "message" => "Role Saved"]);
    } else {
        echo json_encode(["status" => "error", "message" => $stmt->error]);
    }

    $stmt->close();
} else {
    echo json_encode(["status" => "error", "message" => $conn->error]);
}
?>

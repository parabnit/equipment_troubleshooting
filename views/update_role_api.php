<?php
include("../config/connect.php");
header("Content-Type: application/json");
error_reporting(E_ALL);
ini_set("display_errors", 1);

$input = json_decode(file_get_contents("php://input"), true);

$role_id = $input['role_id'] ?? 0;
$role = $input['role'] ?? '';
$description = $input['description'] ?? '';

if (!$role_id || $role == "") {
    echo json_encode(["status" => "error", "message" => "Invalid input"]);
    exit;
}

// Use correct DB connection
$conn = $db_equip;

// Prepared statement for update
$stmt = $conn->prepare("UPDATE role_master SET role = ?, description = ? WHERE role_id = ?");

if ($stmt) {
    $stmt->bind_param("ssi", $role, $description, $role_id);

    if ($stmt->execute()) {
        echo json_encode(["status" => "success", "message" => "Role updated successfully"]);
    } else {
        echo json_encode(["status" => "error", "message" => $stmt->error]);
    }

    $stmt->close();
} else {
    echo json_encode(["status" => "error", "message" => $conn->error]);
}
?>

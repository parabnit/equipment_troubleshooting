<?php
session_start();
if ($_SESSION['role'] != 1 && $_SESSION['role_ITadmin'] != 1) {
    die("Unauthorized access");
}

$base_path = "complaint_categories/";

// Function to strip "-ID" or " - ID" from text
function cleanLine($str) {
    return preg_replace('/\s*-\s*\d+$/', '', trim($str));
}

$action = $_POST['action'] ?? '';

// 1. ACTION: Create Category File
if ($action === 'create_file') {
    $safe_name = preg_replace('/[^a-zA-Z0-9_]/', '', trim($_POST['new_file_name']));
    $filePath = $base_path . strtolower($safe_name) . ".txt";
    if (!empty($safe_name) && !file_exists($filePath)) {
        touch($filePath);
        header("Location: manage_categories.php?status=file_created");
    } else {
        header("Location: manage_categories.php?status=error_exists");
    }
    exit();
}

// 2. ACTION: Delete Category File
if ($action === 'delete_file') {
    $filePath = $base_path . $_POST['category_key'] . ".txt";
    if (file_exists($filePath)) unlink($filePath);
    header("Location: manage_categories.php?status=file_deleted");
    exit();
}

// 3. ACTIONS: Add, Edit, Delete Options
$key = $_POST['category_key'] ?? '';
$filePath = $base_path . $key . ".txt";
if (!file_exists($filePath)) header("Location: manage_categories.php");

$lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
$lines = array_values(array_filter(array_map('trim', $lines)));
$status_code = "success";

if ($action == 'add') {
    $lines[] = cleanLine($_POST['option_value']);
    $status_code = "added";
} 
elseif ($action == 'edit') {
    $idx = $_POST['option_index'];
    if (isset($lines[$idx])) {
        $lines[$idx] = cleanLine($_POST['option_value']);
        $status_code = "updated";
    }
} 
elseif ($action == 'delete') {
    array_splice($lines, $_POST['option_index'], 1);
    $status_code = "deleted";
}

// RE-SYNC LOGIC: Always re-apply IDs (1, 2, 3...) to every line before saving
$finalLines = [];
foreach ($lines as $idx => $line) {
    $finalLines[] = cleanLine($line) . "-" . ($idx + 1);
}

file_put_contents($filePath, implode("\n", $finalLines) . (count($finalLines) > 0 ? "\n" : ""));

header("Location: manage_categories.php?status=$status_code");
exit();
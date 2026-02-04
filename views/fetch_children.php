<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include("../includes/auth_check.php");
include("../config/connect.php");
include("../includes/common.php");

if (empty($_SESSION['login'])) {
  http_response_code(401);
  exit("Unauthorized");
}

$original_id = (int)($_GET['original_id'] ?? 0);
$type        = (int)($_GET['type'] ?? 0);

if ($original_id <= 0 || $type <= 0) {
  http_response_code(400);
  exit("Invalid request");
}


$sql = "SELECT * FROM equipment_complaint
        WHERE original_id = ?
          AND complaint_id <> ?
        ORDER BY time_of_complaint ASC";


$stmt = mysqli_prepare($db_equip, $sql);
mysqli_stmt_bind_param($stmt, "ii", $original_id, $original_id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);

$rows = [];
while ($r = mysqli_fetch_assoc($res)) {

    // Convert IDs to Names
    $r['member_name'] = !empty($r['member_id']) ? getName($r['member_id']) : '';
    $r['allocated_to_name'] = !empty($r['allocated_to']) ? getName($r['allocated_to']) : '';

    // ✅ Clean complaint_description PROPERLY
    if (!empty($r['complaint_description'])) {

        $desc = $r['complaint_description'];

        // Convert literal "\n" into real line breaks
        $desc = str_replace('\\n', "\n", $desc);

        // Decode HTML entities (&quot;, &#039; etc.)
        $desc = htmlspecialchars_decode($desc, ENT_QUOTES);

        // Normalize line endings
        $desc = str_replace(["\r\n", "\r"], "\n", $desc);

        // Trim only start/end (do NOT collapse spacing)
        $desc = trim($desc);

        $r['complaint_description'] = $desc;
    } else {
        $r['complaint_description'] = '';
    }

    // Tool name logic
    if (isset($r['machine_id'])) {

        if ($r['type'] == 1 || $r['type'] == 4) {
            $r['tool_name'] = ($r['machine_id'] == 0)
                ? 'Miscellaneous'
                : getToolName($r['machine_id']);

        } elseif ($r['type'] == 2) {
            $r['tool_name'] = ($r['machine_id'] == 0)
                ? 'Miscellaneous'
                : getToolName_facility($r['machine_id']);

        } elseif ($r['type'] == 3) {
            $r['tool_name'] = ($r['machine_id'] == 0)
                ? 'Miscellaneous'
                : getToolName_safety($r['machine_id']);

        } elseif (in_array((int)$r['type'], [5,6,7,8,9], true)) {

            // ✅ FIX: Category-based complaints
            $categories = getTxtCategories((int)$r['type']);
            $catId = (string)$r['machine_id'];

            $r['tool_name'] = $categories[$catId] ?? 'Miscellaneous';

        } else {
            $r['tool_name'] = 'N/A';
        }

    } else {
        $r['tool_name'] = '';
    }
        $r['has_track'] = count(trouble_track($r['complaint_id'], '')) > 0 ? 1 : 0;


    $rows[] = $r;
    
}

mysqli_stmt_close($stmt);

header('Content-Type: application/json');
echo json_encode($rows);

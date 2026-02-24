<?php
session_start();
include("../config/connect.php");
include("../includes/common.php");

ini_set('display_errors', 1);
error_reporting(E_ALL);

/* -----------------------------
   AUTH CHECK
------------------------------ */
if (empty($_SESSION['login'])) {
    echo json_encode(["error" => "Unauthorized"]);
    exit;
}

$member_id = (int)$_SESSION['memberid'];

/* -----------------------------
   ROLE DETECTION (SAME AS MAIN PAGE)
------------------------------ */
$user_role = '';
$head = 0;

if (is_LabManager($member_id) || is_AssistLabManager($member_id) || is_PI($member_id)) {
    $user_role = 'all';
}
elseif (is_EquipmentHead($member_id)) {
    $user_role = 'head';
    $head = 1;
}
elseif (is_FacilityHead($member_id)) {
    $user_role = 'head';
    $head = 1;
}
elseif (is_SafetyHead($member_id)) {
    $user_role = 'head';
    $head = 1;
}
elseif (is_ProcessHead($member_id)) {
    $user_role = 'head';
    $head = 1;
}
elseif (is_HRHead($member_id)) {
    $user_role = 'head';
    $head = 1;
}
elseif (is_ITHead($member_id)) {
    $user_role = 'head';
    $head = 1;
}
elseif (is_PurchaseHead($member_id)) {
    $user_role = 'head';
    $head = 1;
}
elseif (is_TrainingHead($member_id)) {
    $user_role = 'head';
    $head = 1;
}
elseif (is_InventoryHead($member_id)) {
    $user_role = 'head';
    $head = 1;
}
elseif (is_AdminHead($member_id)) {
    $user_role = 'head';
    $head = 1;
}

elseif (is_EquipmentTeam($member_id)) {
    $user_role = 'team';
}
elseif (is_FacilityTeam($member_id)) {
    $user_role = 'team';
}
elseif (is_SafetyTeam($member_id)) {
    $user_role = 'team';
}
elseif (is_ProcessTeam($member_id)) {
    $user_role = 'team';
}
elseif (is_HRTeam($member_id)) {
   $user_role = 'team';
}
elseif (is_ITTeam($member_id)) {
   $user_role = 'team';
}
elseif (is_PurchaseTeam($member_id)) {
    $user_role = 'team';
}
elseif (is_TrainingTeam($member_id)) {
    $user_role = 'team';
}
elseif (is_InventoryTeam($member_id)) {
   $user_role = 'team';
}

elseif (is_AdminTeam($member_id)) {
    $user_role = 'team';

}

else {
    echo json_encode(["error" => "Access denied"]);
    exit;
}
/* -----------------------------
   DATATABLE INPUT
------------------------------ */
$input = json_decode(file_get_contents('php://input'), true);

$typeId = (int)($input['typeId'] ?? 0);

$start  = (int)($input['start'] ?? 0);
$length = (int)($input['length'] ?? 10);

$search = trim($input['search']['value'] ?? '');

$order = $input['order'][0] ?? ['column'=>0,'dir'=>'asc'];
$columns = ['allocated_to','complaint_description','days_pending'];
$orderColumn = $columns[$order['column']] ?? 'days_pending';
$orderDir = strtolower($order['dir']) === 'desc' ? 'DESC' : 'ASC';

/* -----------------------------
   BASE SQL (PENDING ONLY)
------------------------------ */
$sqlBase = "
FROM equipment_complaint c
WHERE c.status = 0
";

$params = [];
$types  = "";

/* -----------------------------
   TYPE FILTER
------------------------------ */
if ($typeId > 0) {
    $sqlBase .= " AND c.type = ? ";
    $params[] = $typeId;
    $types .= "i";
}

/* -----------------------------
   ROLE BASED FILTER (IMPORTANT)
------------------------------ */
if ($user_role === 'team') {
    // TEAM → ONLY ALLOCATED
    $sqlBase .= " AND c.allocated_to = ? ";
    $params[] = $member_id;
    $types .= "i";
}

/* -----------------------------
   SEARCH FILTER
------------------------------ */
if ($search !== '') {
    $sqlBase .= " AND c.complaint_description LIKE ? ";
    $params[] = "%{$search}%";
    $types .= "s";
}

/* -----------------------------
   TOTAL COUNT
------------------------------ */
$countSql = "SELECT COUNT(*) ".$sqlBase;
$stmt = $db_equip->prepare($countSql);
if ($types) $stmt->bind_param($types, ...$params);
$stmt->execute();
$stmt->bind_result($recordsFiltered);
$stmt->fetch();
$stmt->close();

$recordsTotal = $recordsFiltered;

/* -----------------------------
   DATA QUERY
------------------------------ */
$dataSql = "
SELECT
    c.complaint_description,
    c.allocated_to,
    DATEDIFF(NOW(), c.time_of_complaint) AS days_pending
".$sqlBase."
ORDER BY {$orderColumn} {$orderDir}
LIMIT ?, ?
";

$params[] = $start;
$params[] = $length;
$types .= "ii";

$stmt = $db_equip->prepare($dataSql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$res = $stmt->get_result();

/* -----------------------------
   FORMAT DATA (SAME AS MAIN PAGE)
------------------------------ */
$data = [];

while ($row = $res->fetch_assoc()) {

    // FIX: remove unwanted backslashes properly
    $desc = stripslashes($row['complaint_description']);

    // Replace real newlines/tabs
    $desc = str_replace(["\n", "\r", "\t"], " ", $desc);


    $data[] = [
        "allocated_to" => getName($row['allocated_to']),
        "description"  => trim($desc),
        "days_pending" => (int)$row['days_pending']
    ];
}
/* -----------------------------
   OUTPUT
------------------------------ */
echo json_encode([
    "draw"            => (int)($input['draw'] ?? 1),
    "recordsTotal"    => $recordsTotal,
    "recordsFiltered" => $recordsFiltered,
    "data"            => $data
]);

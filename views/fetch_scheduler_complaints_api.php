<?php
include("../config/connect.php");
include("../includes/common.php");

header("Content-Type: application/json");

$sql = "
SELECT 
    ec.complaint_id,
    ec.machine_id,
    ec.type,
    ec.complaint_description,
    ec.member_id AS complaint_by,
    ec.allocated_to,

    sdt.id AS task_id,
    sdt.timer,
    sdt.trigger_date,
    sdt.trigger_datetime,
    sdt.status AS task_status

FROM scheduler_daily_tasks sdt
INNER JOIN equipment_complaint ec
    ON ec.complaint_id = sdt.complaint_id

WHERE sdt.id = (
    SELECT MAX(id)
    FROM scheduler_daily_tasks
    WHERE complaint_id = ec.complaint_id
)
ORDER BY sdt.id DESC
";

$result = mysqli_query($db_equip, $sql);

$data = [];

while ($row = mysqli_fetch_assoc($result)) {

    /* Complaint raised by */
    $row['member_name'] = getName($row['complaint_by']);

    /* Tool name */
    // if ((int)$row['machine_id'] === 0) {
    //     $row['tool_name'] = "Miscellaneous";
    // } else {
    //     $row['tool_name'] = getToolName($row['machine_id']);
    // }

    $toolName = "";
    $t_name = "";


    switch ($row['type']) {
    case 1:
        $t_name = "Equipment";
        $toolName = ($row['machine_id'] == 0) ? 'Miscellaneous' : getToolName($row['machine_id']);
        break;
    case 2:
        $t_name = "Facility";
        $toolName = ($row['machine_id'] == 0) ? 'Miscellaneous' : getToolName_facility($row['machine_id']);
        break;
    case 3:
        $t_name = "Safety";
        $toolName = ($row['machine_id'] == 0) ? 'Miscellaneous' : getToolName_safety($row['machine_id']);
        break;
    case 4:
        $t_name = "Process";  
        $toolName = ($row['machine_id'] == 0) ? 'Miscellaneous' : getToolName($row['machine_id']);
        break;
    case 5:
      $t_name = "HR";
      $cats = getTxtCategories(5);
      $toolName = $cats[$row['machine_id']] ?? 'N/A';
      break;

    case 6:
        $t_name = "IT";
        $cats = getTxtCategories(6);
        $toolName = $cats[$row['machine_id']] ?? 'N/A';
        break;

    case 7:
        $t_name = "Purchase";
        $cats = getTxtCategories(7);
        $toolName = $cats[$row['machine_id']] ?? 'N/A';
        break;

    case 8:
        $t_name = "Training";
        $cats = getTxtCategories(8);
        $toolName = $cats[$row['machine_id']] ?? 'N/A';
        break;

    case 9:
        $t_name = "Inventory";
        $cats = getTxtCategories(9);
        $toolName = $cats[$row['machine_id']] ?? 'N/A';
        break;

    default:
        $team = "";
        $t_name = "";
        // or throw an exception if invalid:
        // throw new Exception("Invalid complaint type");
        break;
}
    $row['tool_name'] = $toolName;
    $row['team'] = $t_name;

    /* Allocated to */
    if (!empty($row['allocated_to'])) {
        $row['allocated_to_name'] = getName($row['allocated_to']);
    } else {
        $row['allocated_to_name'] = "-";
    }

    /* Optional: formatted trigger date (keeps original too) */
    $row['trigger_date_formatted'] = date(
        "d-m-Y H:i",
        (int)$row['trigger_date']
    );

    $data[] = $row;
}

echo json_encode([
    "data" => $data
]);

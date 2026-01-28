<?php
include("../config/connect.php");
include("../includes/common.php");

header("Content-Type: application/json");

$sql = "
SELECT 
    ec.complaint_id,
    ec.machine_id,
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
    if ((int)$row['machine_id'] === 0) {
        $row['tool_name'] = "Miscellaneous";
    } else {
        $row['tool_name'] = getToolName($row['machine_id']);
    }

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

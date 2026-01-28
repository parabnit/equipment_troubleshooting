<?php

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=complaint_history.csv');
error_reporting(0);
include '../config/connect.php';
include '../includes/common.php';
$output = fopen('php://output', 'w');

fputcsv(
	$output,
	array(
		"S.No.",
		"User Name",
		"Tool Name",
		"Complaint Description",
		"Time of Complaint",
		"Status",
		"Resolved on"
	)
);

if ($_GET['type'] != 0) {
	$data = complaint('', check_number(mysqli_real_escape_string($db_equip, $_GET['type'])), '');
}


$j = 1;
for ($i = 0; $i < count($data); $i++) {

	if ($data[$i]['status'] == 0 || $data[$i]['status'] == 1)
		$data[$i]['status_timestamp'] = '';
	else
		$data[$i]['status_timestamp'] = display_timestamp($data[$i]['status_timestamp']);

	if ($data[$i]['status'] == 0)
		$data[$i]['status'] = 'Pending';
	if ($data[$i]['status'] == 1)
		$data[$i]['status'] = 'In process';
	if ($data[$i]['status'] == 2)
		$data[$i]['status'] = 'Closed';

	if ($data[$i]['machine_id'] != $_GET['equipment'])
		continue;

	if ($_GET['type'] == 1 || $_GET['type'] == 4) {
		$data[$i]['machine_id'] = getToolName($data[$i]['machine_id']);
	} else if ($_GET['type'] == 2) {
		$data[$i]['machine_id'] = getToolName_facility($data[$i]['machine_id']);
	} else if ($_GET['type'] == 3) {
		$data[$i]['machine_id'] = getToolName_safety($data[$i]['machine_id']);
	}

	fputcsv(
		$output,
		array(
			$j++,
			getName($data[$i]['member_id']),
			$data[$i]['machine_id'],
			$data[$i]['complaint_description'],
			display_timestamp($data[$i]['time_of_complaint']),
			$data[$i]['status'],
			$data[$i]['status_timestamp']
		)
	);
}

<?php
function get_email_user($memberid)
{
    global $db_slot;
    $row = mysqli_fetch_row(mysqli_query($db_slot,"select email from login where memberid=$memberid"));
   
    return $row[0];
}
// this is for future use, if we have to send for individuals 
function sendEmail($to, $from, $subject, $body)
{
    $mail = new PHPMailer();
    $mail->isSMTP();
//    $mail->Host = "sandesh.ee.iitb.ac.in";
    $mail->setFrom($from);

    $mail->CharSet = "UTF-8";
    $mail->Encoding = "base64";
    $mail->isHTML(true);
    $subject = "IITBNF_IT: " . $subject;
    $mail->Subject = mb_encode_mimeheader($subject, 'UTF-8', 'B'); // Fix here
    $mail->Body    = $body;
    $mail->AltBody = strip_tags($body);

    $emaillist = explode(",", $to);
    foreach ($emaillist as $email) {
        if (!empty(trim($email))) {
            $mail->addAddress(trim($email));
        }
    }

    if (!$mail->send()) {
        echo "Mailer Error: " . $mail->ErrorInfo;
    }
}

function sendEmailCC($to,$cc,$from,$subject,$body)
{
    $mail = new PHPMailer();
    $mail->IsSMTP(); 
    // $mail->Host = "sandesh.ee.iitb.ac.in";
    
    $mail->CharSet = "UTF-8";
    $mail->Encoding = "base64";
    $mail->isHTML(true);

    $subject = "IITBNF_IT: " . $subject;
    $mail->Subject = $subject;
    $mail->SetFrom($from);

    // --- FIX NEWLINES ---
    // Convert literal "\n" to actual newline
    $body = str_replace("\\n", "\n", $body);

    // Convert newline to <br> for HTML
    $body_html = nl2br($body, false);

    // Set HTML body
    $mail->MsgHTML($body_html);

    // Plain text version (no HTML)
    $mail->AltBody = strip_tags(str_replace(["<br>", "<br/>", "<br />"], "\n", $body_html));

    // TO
    $emaillist = explode(",",$to);
    foreach ($emaillist as $email) {
        if(trim($email) != "") $mail->AddAddress(trim($email));
    }

    // CC
    $emaillistCC = explode(",",$cc);
    foreach ($emaillistCC as $email) {
        if(trim($email) != "") $mail->AddCC(trim($email));
    }

    // Send
    if(!$mail->Send()) {
        echo "Mailer Error: " . $mail->ErrorInfo;
    }
}


function getTeamMembers($type)
{
switch ($type) {
    case 1:
        $team = "equipment";
        break;
    case 2:
        $team = "facility";
        break;
    case 3:
        $team = "safety";
        break;
    case 4:
        $team = "process";
        break;
    default:
        $team = "";
        // or throw an exception if invalid:
        // throw new Exception("Invalid complaint type");
        break;
}

	global $db_equip;
	$stmt = $db_equip->prepare("SELECT id FROM permission WHERE $team=1");
	$stmt->execute();
	$result = $stmt->get_result();
	$members = array();
	while ($row = $result->fetch_assoc()) {
		$members[] = $row['id'];
	}
	return $members;

}

function send_complaint_closed_email($complaint_id)
{
    global $db_equip;
    $stmt = $db_equip->prepare("SELECT * FROM equipment_complaint WHERE complaint_id = ?");
    $stmt->bind_param("i", $complaint_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    if ($row) {
        $memberid        = $row['member_id'];
        $machine_id      = $row['machine_id'];
        $type            = $row['type'];
        $status_updated_by = $row['status_updated_by'];
        $description     = $row['complaint_description'];
    }

    switch ($type) {
        case 1:
            $team = "Equipment";
            break;
        case 2:
            $team = "Facility";
            break;
        case 3:
            $team = "Safety";
            break;
        case 4:
            $team = "Process";
            break;
        default:
            return "";
    }

    // --- Normalize description ---
    // 1) convert escaped "\n" to real newlines
    $plain_description = str_replace("\\n", "\n", $description);
    // 2) convert real newlines to <br> for HTML
    $description_html  = nl2br($plain_description, false); // gives <br>

    $members = getTeamMembers($type);
    $member_email = [];
    foreach ($members as $member) {
        $member_email[] = get_email_user($member);
    }
    $to    = implode(",", $member_email);

    $from    = get_email_user($row['status_updated_by']);
$cc = "deepti.rukade@gmail.com";
    $toolName = ($machine_id == 0) ? 'Miscellaneous' : getToolName($machine_id);
    $subject = "The Complaint for $team has been closed";

    $body = "<table border='0' width='100%'>\n".
        "<tr><td colspan='2'><table><tr><td colspan='2'><b>A Complaint has been closed for $team</b>,<br>\n".
        "</td></tr><tr><td colspan='2' height='10'></td></tr>\n".
        "<tr><td valign='top' align='right'><b>From: </b></td><td>".getName($memberid)."</td></tr>\n".
        "<tr><td valign='top' align='right'><b>For Tool: </b></td><td>".$toolName."</td></tr>\n".
        "<tr><td valign='top' align='right'><b>Description: </b></td><td>".$description_html."</td></tr>\n".
        "<tr><td valign='top' align='right'><b>Submitted at:</b></td><td>".date("F j, Y, g:i a", time())."</td></tr>\n".
        "</table></td></tr></table>\n";

    // remove debug echo when done
    // echo $body;

    sendEmailCC($to, $cc, $from, $subject, $body);
}

function getName($memberid)
{
	global $db_slot;
	$stmt = $db_slot->prepare("SELECT fname, lname FROM login WHERE memberid = ?");
	$stmt->bind_param("i", $memberid);
	$stmt->execute();
	$result = $stmt->get_result();
	$row = $result->fetch_assoc();
	return $row ? $row['fname'] . " " . $row['lname'] : null;
}

function getToolName($machid)
{

	global $db_slot;
	$stmt = $db_slot->prepare("SELECT name FROM resources WHERE machid = ?");
	$stmt->bind_param("i", $machid);
	$stmt->execute();
	$result = $stmt->get_result();
	$row = $result->fetch_assoc();
	return $row['name'] ?? null;
}

function getToolName_facility($machid)
{
	global $db_facility;
	$stmt = $db_facility->prepare("SELECT name FROM resources WHERE machid = ?");
	$stmt->bind_param("i", $machid);
	$stmt->execute();
	$result = $stmt->get_result();
	$row = $result->fetch_assoc();
	return $row['name'] ?? null;
}

function getToolName_safety($machid)
{
	global $db_safety;
	$stmt = $db_safety->prepare("SELECT device_name FROM safety_device WHERE device_id = ?");
	$stmt->bind_param("i", $machid);
	$stmt->execute();
	$result = $stmt->get_result();
	$row = $result->fetch_assoc();
	return $row['device_name'] ?? null;
}


function getVendorName($machid, $type)
{
    global $db_slot, $db_safety, $db_facility;
    $vendor = '';

    if ($type == 1 || $type == 4) {
        $stmt = $db_slot->prepare("SELECT local_agent_name FROM resources WHERE machid = ?");
        $stmt->bind_param("i", $machid);
    } elseif ($type == 2) {
        $stmt = $db_facility->prepare("SELECT local_agent_name FROM resources WHERE machid = ?");
        $stmt->bind_param("i", $machid);
    } elseif ($type == 3) {
        $stmt = $db_safety->prepare("SELECT local_agent_name FROM safety_device WHERE device_id = ?");
        $stmt->bind_param("i", $machid);
    } else {
        return ''; // invalid type
    }

    $stmt->execute();
    $stmt->bind_result($vendor);
    $stmt->fetch();
    $stmt->close();

    return $vendor ?: '';
}


function getlocation($machid)
{
	global $db_slot;
	$stmt = $db_slot->prepare("SELECT location FROM resources WHERE machid=? LIMIT 1");
	$stmt->bind_param("i", $machid);
	$stmt->execute();
	$location = '';
	$stmt->bind_result($location);
	$stmt->fetch();
	return $location ?: '';
}


function getTools($type)
{
	global $db_slot, $db_facility, $db_safety;
	$i = 0;
	$data = array();

	if ($type == 1 || $type == 4) {
		$stmt = $db_slot->prepare("SELECT name, model, machid FROM resources WHERE display!=? AND location!=? ORDER BY name ASC");
		$display = 3;
		$location = 'Facility';
		$stmt->bind_param("is", $display, $location);
		$stmt->execute();
		$result = $stmt->get_result();

		while ($row = $result->fetch_assoc()) {
			$data[$i]['name']  = $row['name'];
			$data[$i]['machid'] = $row['machid'];
			$data[$i]['model'] = $row['model'];
			$i++;
		}
	} else if ($type == 2) {
		$stmt = $db_facility->prepare("SELECT name, machid FROM resources WHERE type_of_tool=? ORDER BY name ASC");
		$tool = 0;
		$stmt->bind_param("i", $tool);
		$stmt->execute();
		$result = $stmt->get_result();

		while ($row = $result->fetch_assoc()) {
			$data[$i]['name']   = $row['name'];
			$data[$i]['machid'] = $row['machid'];
			$i++;
		}
	} else if ($type == 3) {
		$stmt = $db_safety->prepare("SELECT device_name, device_id FROM safety_device ORDER BY device_name ASC");
		$stmt->execute();
		$result = $stmt->get_result();

		while ($row = $result->fetch_assoc()) {
			$data[$i]['name']   = $row['device_name'];
			$data[$i]['machid'] = $row['device_id'];
			$i++;
		}
	}

	return $data;
}


function filter($data)
{
	$data = str_replace("\"", "", $data);
	$data = trim(ltrim(rtrim($data)));
	$data = addslashes($data);
	return $data;
}

function insert_complaint($member_id, $machine_id, $process_dev, $anti_cont_dev, $complaint_description, $type,$slotid)
{
	global $db_equip;
	$process_dev = ($process_dev === 'NULL') ? null : $process_dev;
	$anti_cont_dev = ($anti_cont_dev === 'NULL') ? null : $anti_cont_dev;
        $slotid = ($slotid === 'NULL') ? 0 : $slotid;
	$stmt = $db_equip->prepare("INSERT INTO equipment_complaint 
        (member_id, machine_id, process_develop, anti_contamination_develop, slotid,  complaint_description, time_of_complaint, status, type)
        VALUES (?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP(), 0, ?)");
	if (!$stmt) {
		die("Prepare failed: " . $db_equip->error);
	}
	$stmt->bind_param("iissisi", $member_id, $machine_id, $process_dev, $anti_cont_dev, $slotid, $complaint_description, $type);
	if (!$stmt->execute()) {
		die("Execute failed: " . $stmt->error);
	}
	$stmt->close();
}

function complaint($member_id, $type, $is_critical)
{
	global $db_equip;
	$data = array();

	// Case 1: Critical complaints
	if ($is_critical) {
		$sql = "SELECT a.*, b.*
                FROM equipment_troubleshooting.equipment_complaint AS a
                INNER JOIN slotbooking.resources AS b 
                ON a.machine_id = b.machid 
                WHERE model = ? 
                ORDER BY time_of_complaint DESC";
		$stmt = mysqli_prepare($db_equip, $sql);
		$model = 'Yes';
		mysqli_stmt_bind_param($stmt, "s", $model);
	}
	// Case 2: No member_id (filter by type)
	elseif ($member_id == '') {
		$sql = "SELECT * FROM equipment_complaint 
                WHERE type = ? 
                ORDER BY time_of_complaint DESC";
		$stmt = mysqli_prepare($db_equip, $sql);
		mysqli_stmt_bind_param($stmt, "i", $type);
	}
	// Case 3: Filter by member_id
	else {
		$sql = "SELECT * FROM equipment_complaint 
                WHERE member_id = ? 
                ORDER BY time_of_complaint DESC";
		$stmt = mysqli_prepare($db_equip, $sql);
		mysqli_stmt_bind_param($stmt, "i", $member_id);
	}

	// Execute & fetch results
	mysqli_stmt_execute($stmt);
	$result = mysqli_stmt_get_result($stmt);

	$i = 0;
	while ($row = mysqli_fetch_assoc($result)) {
		$data[$i]['complaint_id']          = $row['complaint_id'];
		$data[$i]['member_id']             = $row['member_id'];
		$data[$i]['machine_id']            = $row['machine_id'];
		$data[$i]['complaint_description'] = $row['complaint_description'];
		if ($type == 4) {
			$data[$i]['process_develop']           = $row['process_develop'];
			$data[$i]['anti_contamination_develop'] = $row['anti_contamination_develop'];
		}
		$data[$i]['time_of_complaint']    = $row['time_of_complaint'];
		$data[$i]['status']               = $row['status'];
		$data[$i]['status_resolved']      = $row['status_resolved'];
		$data[$i]['upload_file']          = $row['upload_file'];
		$data[$i]['type']                 = $row['type'];
		$data[$i]['allocated_to']         = $row['allocated_to'];
		$data[$i]['status_updated_by']    = $row['status_updated_by'];
		$i++;
	}

	mysqli_stmt_close($stmt);
	return $data;
}




function complaintByID($complaint_id, $type)
{
	global $db_equip;
	$stmt = $db_equip->prepare("SELECT * FROM equipment_complaint WHERE complaint_id = ? AND type = ? ORDER BY time_of_complaint DESC");
	$stmt->bind_param("ii", $complaint_id, $type);
	$stmt->execute();
	$result = $stmt->get_result();
	$row = $result->fetch_array();
	return $row;
}

function display_timestamp($timestamp)
{
	return date('d M Y h:i A', strtotime($timestamp));
}

function display_date($timestamp)
{
	return date('d M Y', strtotime($timestamp));
}

function count_day($from, $to)
{
	$f_date = new DateTime(display_date($from));
	$t_date = new DateTime(display_date($to));
	$days = $f_date->diff($t_date);
	return $days->format('%r%a');
}

function update_complaint($complaint_id, $status, $c_date, $allocated_to, $status_updated_by)
{
	global $db_equip;
	$c_date = date("Y-m-d H:i:s", strtotime($c_date)); // ✅ use 24h format

	$stmt = $db_equip->prepare("UPDATE equipment_complaint SET status=?, status_resolved=?, allocated_to=?, status_updated_by=? 
    WHERE complaint_id=?");
	$stmt->bind_param("isssi", $status, $c_date, $allocated_to, $status_updated_by, $complaint_id);
	return $stmt->execute();
}



function insert_trouble_track(
	$complaint_id,
	$status_mark_by,
	$working_team,
	$diagnosis,
	$action_taken,
	$work_done_by,
	$spare_parts,
	$cost_spare_parts,
	$procurement_time_spares,
	$comments,
	$expected_completion_date,
	$action_plan,
	$vendor_name,
	$vendor_contact,
	$vendor_interaction,
	$vendor_comments,
	$action_item_owner,
	$file
) {
	global $db_equip;

	$sql = "INSERT INTO trouble_track (
                complaint_id,
                timestamp,
                status_mark_by,
                working_team,
                diagnosis,
                action_taken,
                work_done_by,
                spare_parts,
                cost_spare_parts,
                procurement_time_spares,
                comments,
                expected_completion_date,
                action_plan,
                vendor_name,
                vendor_contact,
                vendor_interaction,
                vendor_comments,
                action_item_owner,
                file
            ) VALUES (?, CURRENT_TIMESTAMP(), ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

	$stmt = $db_equip->prepare($sql);

	$stmt->bind_param(
		"iissssssssssssssss",
		$complaint_id,
		$status_mark_by,
		$working_team,
		$diagnosis,
		$action_taken,
		$work_done_by,
		$spare_parts,
		$cost_spare_parts,
		$procurement_time_spares,
		$comments,
		$expected_completion_date,
		$action_plan,
		$vendor_name,
		$vendor_contact,
		$vendor_interaction,
		$vendor_comments,
		$action_item_owner,
		$file
	);

	return $stmt->execute();
}


function trouble_track($complaint_id, $member_id)
{
	global $db_equip;
	$i = 0;
	$data = array();

	if ($member_id == '' && $complaint_id == '') {
		$stmt = $db_equip->prepare("SELECT * FROM trouble_track ORDER BY timestamp DESC");
	}

	if ($member_id != '' && $complaint_id == '') {
		$stmt = $db_equip->prepare("SELECT * FROM trouble_track WHERE member_id=? ORDER BY timestamp DESC");
		$stmt->bind_param("i", $member_id);
	}

	if ($member_id == '' && $complaint_id != '') {
		$stmt = $db_equip->prepare("SELECT * FROM trouble_track WHERE complaint_id=? ORDER BY timestamp DESC");
		$stmt->bind_param("i", $complaint_id);
	}

	if ($member_id != '' && $complaint_id != '') {
		$stmt = $db_equip->prepare("SELECT * FROM trouble_track WHERE member_id=? AND complaint_id=? ORDER BY timestamp DESC");
		$stmt->bind_param("ii", $member_id, $complaint_id);
	}

	$stmt->execute();
	$result = $stmt->get_result();

	while ($row = $result->fetch_assoc()) {
		$data[$i]['complaint_id']             = $row['complaint_id'];
		$data[$i]['timestamp']                = $row['timestamp'];
		$data[$i]['status_mark_by']           = $row['status_mark_by'];
		$data[$i]['working_team']             = $row['working_team'];
		$data[$i]['diagnosis']                = $row['diagnosis'];
		$data[$i]['action_taken']             = $row['action_taken'];
		$data[$i]['work_done_by']             = $row['work_done_by'];
		$data[$i]['vendor_name']              = $row['vendor_name'];
		$data[$i]['vendor_contact']           = $row['vendor_contact'];
		$data[$i]['vendor_interaction']       = $row['vendor_interaction'];
		$data[$i]['vendor_comments']          = $row['vendor_comments'];
		$data[$i]['spare_parts']              = $row['spare_parts'];
		$data[$i]['cost_spare_parts']         = $row['cost_spare_parts'];
		$data[$i]['procurement_time_spares']  = $row['procurement_time_spares'];
		$data[$i]['expected_completion_date'] = $row['expected_completion_date'];
		$data[$i]['action_plan']              = $row['action_plan'];
		$data[$i]['comments']                 = $row['comments'];
		$data[$i]['action_item_owner']        = $row['action_item_owner'];
		$data[$i]['file']                     = $row['file'];
		$i++;
	}

	return $data;
}


function downtool_list($type)
{
	global $db_equip;
	$data = [];
	$sql = "SELECT machine_id, complaint_id, type 
	        FROM equipment_complaint 
	        WHERE status != 2 AND type = ? 
	        ORDER BY time_of_complaint ASC";

	$stmt = $db_equip->prepare($sql);
	$stmt->bind_param("i", $type);
	$stmt->execute();
	$result = $stmt->get_result();

	$i = 0;
	while ($row = $result->fetch_assoc()) {
		$data[$i]['machine_id'] = $row['machine_id'];
		$data[$i]['complaint_id'] = $row['complaint_id'];
		$data[$i]['type'] = $row['type'];
		$i++;
	}

	return $data;
}

function machine_down($machine_id)
{
	global $db_equip;
	$i = 0;
	$data = array();
	$sql = "SELECT * FROM `equipment_complaint` WHERE `machine_id`={$machine_id} and status!=2 and type=0 ORDER BY `time_of_complaint` ASC;";
	$result = mysqli_query($db_equip, $sql);
	while ($row = mysqli_fetch_array($result)) {
		$data[$i]['complaint_id'] = $row['complaint_id'];
		$data[$i]['member_id'] = $row['member_id'];
		$data[$i]['machine_id'] = $row['machine_id'];
		$data[$i]['complaint_description'] = $row['complaint_description'];
		$data[$i]['time_of_complaint'] = $row['time_of_complaint'];
		$data[$i]['status'] = $row['status'];
		$data[$i]['status_resolved'] = $row['status_resolved'];
		$data[$i]['upload_file'] = $row['upload_file'];
		$i++;
	}
	return $data;
}

function upload_file_complaint($file_name, $complaint_id)
{
	global $db_equip;
	$stmt = $db_equip->prepare("UPDATE equipment_complaint SET upload_file=? WHERE complaint_id=?");
	$stmt->bind_param("si", $file_name, $complaint_id);
	return $stmt->execute();
}

function AllTool_in_complaint($type)
{
	global $db_equip;
	$i = 0;
	$data = array();

	$stmt = $db_equip->prepare("SELECT DISTINCT machine_id FROM equipment_complaint WHERE type=?");
	$stmt->bind_param("i", $type);
	$stmt->execute();
	$result = $stmt->get_result();

	while ($row = $result->fetch_assoc()) {
		$data[$i]['machine_id'] = $row['machine_id'];
		$i++;
	}
	return $data;
}


function Max_No_of_complaints($tool, $type)
{
	global $db_equip;
	$data = array();
	$i = 0;

	$stmt = $db_equip->prepare("SELECT complaint_id, time_of_complaint, status, status_resolved  
                                FROM equipment_complaint 
                                WHERE machine_id=? AND type=?");
	$stmt->bind_param("ii", $tool, $type);
	$stmt->execute();
	$result = $stmt->get_result();

	while ($row = $result->fetch_assoc()) {
		$data[$i]['complaint_id']      = $row['complaint_id'];
		$data[$i]['time_of_complaint'] = $row['time_of_complaint'];
		$data[$i]['status']            = $row['status'];
		$data[$i]['status_resolved']   = $row['status_resolved'];
		$i++;
	}

	return $data;
}


function EC_date($complaint_id)
{
	global $db_equip;
	$stmt = $db_equip->prepare("SELECT expected_completion_date 
                                FROM trouble_track 
                                WHERE complaint_id=? 
                                AND expected_completion_date!='0000-00-00 00:00:00' 
                                ORDER BY timestamp DESC 
                                LIMIT 1");
	$stmt->bind_param("i", $complaint_id);
	$stmt->execute();
	$date = '';
	$stmt->bind_result($date);
	$stmt->fetch();
	return $date ?: '';
}

function getAllvendor($machine_id, $type)
{
	global $db_equip;
	$data = [];
	$vendor = getVendorName($machine_id, $type);

	// If vendor exists, add it first
	if (!empty($vendor)) {
		$data[] = ['vendor_name' => $vendor];
	}

	$sql = "SELECT DISTINCT t.vendor_name FROM trouble_track t JOIN equipment_complaint e ON t.complaint_id = e.complaint_id WHERE e.machine_id = ? AND e.type = ? AND t.vendor_name != ''";

	$stmt = $db_equip->prepare($sql);
	$stmt->bind_param("ii", $machine_id, $type);
	$stmt->execute();
	$result = $stmt->get_result();

	while ($row = $result->fetch_assoc()) {
		if (empty($vendor) || $vendor !== $row['vendor_name']) {
			$data[] = ['vendor_name' => $row['vendor_name']];
		}
	}

	return $data;
}

function process_related_update($complaint, $status)
{
	global $db_equip;
	$sql = "UPDATE equipment_complaint SET type = ? WHERE complaint_id = ?";
	$stmt = $db_equip->prepare($sql);
	$stmt->bind_param("ii", $status, $complaint);
	$stmt->execute();
}

// added by shahid on 31 Oct 2025, to reopen a complaint.
function reopen_complain($complaint_id)
{
    global $db_equip;
    $status_reopen = 1; 
    $sql = "UPDATE equipment_complaint SET status = ? WHERE complaint_id = ?";
    $stmt = $db_equip->prepare($sql);    
    $stmt->bind_param("ii", $status_reopen, $complaint_id);
    $stmt->execute();
}



function permission_details()
{
	global $db_equip;
	$data = [];
	$sql = "SELECT * FROM permission";
	$stmt = $db_equip->prepare($sql);
	$stmt->execute();
	$result = $stmt->get_result();

	$i = 0;
	while ($row = $result->fetch_assoc()) {
		$data[$i]['id'] = $row['id'];
		$data[$i]['name'] = getName($row['id']);
		$data[$i]['equipment'] = $row['equipment'];
		$data[$i]['facility'] = $row['facility'];
		$data[$i]['safety'] = $row['safety'];
		$data[$i]['process'] = $row['process'];
		$i++;
	}

	return $data;
}

function staff_list()
{
	global $db_slot;
	$data = [];
	$sql = "SELECT memberid 
	        FROM login 
	        WHERE (position = 'IITBNF Staff' OR position = 'Project Staff') 
	        AND (FROM_UNIXTIME(UNIX_TIMESTAMP(STR_TO_DATE(expiry_date, '%m/%d/%Y')), '%Y-%m-%d') >= CURDATE()) 
	        ORDER BY fname ASC";

	$stmt = $db_slot->prepare($sql);
	$stmt->execute();
	$result = $stmt->get_result();

	while ($row = $result->fetch_assoc()) {
		$data[] = $row['memberid'];
	}

	return $data;
}
function expired_memberid()
{
	global $db_slot;
	$data = [];
	$i = 0;
	$sql = "SELECT memberid FROM login WHERE DATE_FORMAT(STR_TO_DATE(expiry_date, '%m/%d/%Y'), '%Y-%m-%d') < CURDATE()";
	$stmt = $db_slot->prepare($sql);
	$stmt->execute();
	$result = $stmt->get_result();

	while ($row = $result->fetch_assoc()) {
		$data[$i] = $row['memberid'];
		$i++;
	}

	return $data;
}

function update_permission($equipment, $facility, $safety, $process, $uid)
{
	global $db_equip;
	$sql = "UPDATE permission SET equipment = ?, facility = ?, safety = ?, process = ? WHERE id = ?";
	$stmt = $db_equip->prepare($sql);
	if (!$stmt) {
		return false;
	}
	$stmt->bind_param("iiiii", $equipment, $facility, $safety, $process, $uid);
	return $stmt->execute();
}
function add_permission($equipment, $facility, $safety, $process, $uid)
{
	global $db_equip;
	$sql = "INSERT INTO permission (equipment, facility, safety, process, id) VALUES (?, ?, ?, ?, ?)";
	$stmt = $db_equip->prepare($sql);
	if (!$stmt) {
		return false;
	}
	$stmt->bind_param("iiiii", $equipment, $facility, $safety, $process, $uid);
	return $stmt->execute();
}


function user_permission_exit($member_id)
{
	global $db_equip, $db_slot;
	$sql = "select id from permission where id=$member_id;";
	$result = mysqli_query($db_equip, $sql);

	while ($row = mysqli_fetch_array($result)) {
		if (isset($row['id'])) {
			$q = "SELECT memberid FROM `login` WHERE memberid = " . $row['id'] . " and DATE_FORMAT(STR_TO_DATE(expiry_date, '%m/%d/%Y'), '%Y-%m-%d') < CURDATE()";
			$res = mysqli_query($db_slot, $q);
			$rw = mysqli_fetch_array($res);
			return true;
		} else {
			$q = "SELECT memberid FROM `login` WHERE memberid = " . $row['id'];
			$res = mysqli_query($db_slot, $q);
			$rw = mysqli_fetch_array($result);
			return false;
		}
	}
}
function sortByname($a, $b)
{
	$a = strtolower($a['name']);
	$b = strtolower($b['name']);

	if ($a == $b) {
		return 0;
	}

	return ($a < $b) ? -1 : 1;
}

function check_permission($type, $memberid)
{
	global $db_equip;
	$sql = "SELECT id FROM permission WHERE id = ? AND $type = 1";
	$stmt = $db_equip->prepare($sql);
	if (!$stmt) {
		return null;
	}

	$stmt->bind_param("i", $memberid);
	$stmt->execute();
	$result = $stmt->get_result();
	if ($result && ($row = $result->fetch_assoc())) {
		return $row['id'];
	} else {
		return null;
	}
}

function locationByType($machid, $type)
{

	global $db_slot, $db_safety, $db_facility;
	$conn = null;
	$query = "";
	$paramType = "i";
	if ($type == 1 || $type == 4) {
		$conn = $db_slot;
		$query = "SELECT location FROM resources WHERE machid = ?";
	} elseif ($type == 2) {
		$conn = $db_facility;
		$query = "SELECT location FROM resources WHERE machid = ?";
	} elseif ($type == 3) {
		$conn = $db_safety;
		$query = "SELECT location FROM safety_device WHERE device_id = ?";
	} else {
		return null;
	}

	$stmt = $conn->prepare($query);
	if (!$stmt) return null;
	$stmt->bind_param($paramType, $machid);
	$stmt->execute();
	$result = $stmt->get_result();
	$row = $result->fetch_assoc();

	if (!$row || !isset($row['location'])) {
		return null;
	}

	if ($type == 3 || $type == 2)
		return getSafetyLocation($row['location']);
	else
		return getEquipmentLocation($row['location']);
}
function getSafetyLocation($lid)
{
	global $db_slot;
	$stmt = $db_slot->prepare("SELECT location FROM lab_incharge WHERE locationid = ?");
	$stmt->bind_param("i", $lid);
	$stmt->execute();
	$result = $stmt->get_result();
	$row = $result->fetch_assoc();
	return $row['location'] ?? null;
}
function getEquipmentLocation($lid)
{
	global $db_slot;
	$stmt = $db_slot->prepare("SELECT location FROM lab_incharge WHERE locationid = ?");
	$stmt->bind_param("i", $lid);
	$stmt->execute();
	$result = $stmt->get_result();
	$row = $result->fetch_assoc();
	return $row['location'] ?? null;
}


function isFOCMember($memberid)
{
	global $db_slot;
	$stmt = $db_slot->prepare("SELECT cenlevel FROM login WHERE cenlevel = 5 AND memberid = ?");
	$stmt->bind_param("i", $memberid);
	$stmt->execute();
	$result = $stmt->get_result();
	return ($result->num_rows > 0) ? 1 : 0;
}


// this is from validation.php

function check_number($number)
{
	if (!is_numeric($number)) {
		header("location:javascript://history.go(-1)");
		exit();
	} else {
		return $number;
	}
}

function check_string_dashes($text)
{
	if (!(preg_match('/^[a-zA-Z\'., -|&|\(\)\/]+$/', $text))) {
		header("location:javascript://history.go(-1)");
		exit();
	} else {
		return $text;
	}
}

function check_string_numbers($text)
{
	if (!(preg_match('/^[a-zA-Z0-9 _.-<>|&|]+$/', $text))) {
		header("location:javascript://history.go(-1)");
		exit();
	} else {
		return $text;
	}
}

function check_date($date)
{
	if (!(preg_match('/^[0-9-\/]+$/', $date))) {
		header("location:javascript://history.go(-1)");
		exit();
	} else {
		return $date;
	}
}

function shortDesc($text){
    if ($text === null) {
        $text = '';
    }

    $text = str_replace(
        ["\\r\\n", "\\n", "\\r", "\r\n", "\r", "\n"],
        "\n",
        $text
    );

    $text = str_replace("\\", "", $text);
    return nl2br(htmlentities($text));
}

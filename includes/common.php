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
    $mail->Host = "sandesh.ee.iitb.ac.in";

    $mail->setFrom($from);
    $mail->CharSet = "UTF-8";
    $mail->Encoding = "base64";
    $mail->isHTML(true);

    $subject = "IITBNF_IT: " . $subject;
    $mail->Subject = mb_encode_mimeheader($subject, 'UTF-8', 'B');

    // 🔥 FIX HERE
    $body = str_replace(["\\r\\n", "\\n", "\\r"], "\n", $body);
    $bodyHtml = nl2br($body, false);
    $mail->Body = $bodyHtml;
    $mail->AltBody = strip_tags($body);

    foreach (explode(",", $to) as $email) {
        if (!empty(trim($email))) {
            $mail->addAddress(trim($email));
        }
    }

    if (!$mail->send()) {
        echo "Mailer Error: " . $mail->ErrorInfo;
    }
}

function sendEmailCC($to, $cc, $from, $subject, $body)
{
    $mail = new PHPMailer();
    $mail->isSMTP();
    $mail->Host = "sandesh.ee.iitb.ac.in";

    $mail->setFrom($from);
    $mail->isHTML(true);

    $subject = "IITBNF_IT: " . $subject;
    $mail->Subject = $subject;

    // 🔥 FIX HERE
    $mail->Body = $body;
    $mail->AltBody = strip_tags($body);

    foreach (explode(",", $to) as $email) {
        if (!empty(trim($email))) {
            $mail->addAddress(trim($email));
        }
    }

    foreach (explode(",", $cc) as $email) {
        if (!empty(trim($email))) {
            $mail->addCC(trim($email));
        }
    }

    if (!$mail->send()) {
        echo "Mailer Error: " . $mail->ErrorInfo;
    }
	
	
}
function getStatusLabel($status) {
    switch ((int)$status) {
        case 0: return "Pending";
        case 1: return "In Process";
        case 2: return "Closed";
        case 3: return "On Hold";
        default: return "Unknown";
    }
}



function getComplaintToolName($row) {

    $type      = (int)($row['type'] ?? 0);
    $machineId = (int)($row['machine_id'] ?? 0);

    // Equipment & Process
    if (in_array($type, [1,4])) {
        return $machineId ? getToolName($machineId) : 'Miscellaneous';
    }

    // Facility
    if ($type == 2) {
        return $machineId ? getToolName_facility($machineId) : 'Miscellaneous';
    }

    // Safety
    if ($type == 3) {
        return $machineId ? getToolName_safety($machineId) : 'Miscellaneous';
    }

    // HR, IT, Purchase, Training, Inventory (category-based)
    if (in_array($type, [5,6,7,8,9,10])) {

        $categories = getTxtCategories($type);

        // ✅ FIX: when machine_id = 0, show category name
        if ($machineId === 0) {
            return array_values($categories)[0] ?? 'Miscellaneous';
        }

        return $categories[$machineId] ?? 'Miscellaneous';
    }


    return 'N/A';
}


function getTeamMembers($type)
{
    $type = (string)$type;

    switch ($type) {
        case "equipment":
            $sql = "SELECT DISTINCT memberid FROM role WHERE role IN (1,5)";
            break;
        case "facility":
            $sql = "SELECT DISTINCT memberid FROM role WHERE role IN (2,6)";
            break;
        case "safety":
            $sql = "SELECT DISTINCT memberid FROM role WHERE role IN (3,7)";
            break;
        case "process":
            $sql = "SELECT DISTINCT memberid FROM role WHERE role IN (4,8)";
            break;
		case "hr":
			$sql = "SELECT DISTINCT memberid FROM role WHERE role IN (12,15)";
			break;
		case "it":
			$sql = "SELECT DISTINCT memberid FROM role WHERE role IN (13,16)";
			break;
		case "purchase":
			$sql = "SELECT DISTINCT memberid FROM role WHERE role IN (14,17)";
			break;
		case "training":
			$sql = "SELECT DISTINCT memberid FROM role WHERE role IN (18,20)";
			break;

		case "inventory":
			$sql = "SELECT DISTINCT memberid FROM role WHERE role IN (19,21)";
			break;

        case "admin":
        $sql = "SELECT DISTINCT memberid FROM role WHERE role IN (22,23)";
        break;

        default:
            return [];
    }

    global $db_equip;
    $stmt = $db_equip->prepare($sql);
    $stmt->execute();
    $result = $stmt->get_result();

    $members = [];
    while ($row = $result->fetch_assoc()) {
        $members[] = $row['memberid'];
    }
    return $members;
}


function getTeamHead($type)
{
    global $db_equip;

    switch ($type) {

        case 1:
            $sql = "SELECT memberid FROM role WHERE role = 1";
            break;

        case 2:
            $sql = "SELECT memberid FROM role WHERE role = 2";
            break;

        case 3:
            $sql = "SELECT memberid FROM role WHERE role = 3";
            break;

        case 4:
            $sql = "SELECT memberid FROM role WHERE role = 4";
            break;

        case 5: // HR
            $sql = "SELECT memberid FROM role WHERE role = 12";
            break;

        case 6: // IT
            $sql = "SELECT memberid FROM role WHERE role = 13";
            break;

        case 7: // Purchase
            $sql = "SELECT memberid FROM role WHERE role = 14";
            break;

        case 8: // Training
            $sql = "SELECT memberid FROM role WHERE role = 18";
            break;

        case 9: // Inventory
            $sql = "SELECT memberid FROM role WHERE role = 19";
            break;

        case 10: // Admin
            $sql = "SELECT memberid FROM role WHERE role = 22";
            break;

        case "all":
            $sql = "SELECT memberid FROM role WHERE role IN (1,2,3,4,12,13,14,18,19,22)";
            break;

        default:
            return []; // 🔒 VERY IMPORTANT
    }

    $stmt = $db_equip->prepare($sql);
    $stmt->execute();
    $result = $stmt->get_result();

    $members = [];
    while ($row = $result->fetch_assoc()) {
        $members[] = $row['memberid'];
    }

    return $members;
}


function getMemberRole($memberid)
{
	global $db_equip;
	$stmt = $db_equip->prepare("SELECT role FROM role WHERE memberid = ?");
	$stmt->bind_param("i", $memberid);
	$stmt->execute();
	$result = $stmt->get_result();
	$roles = array();
	while ($row = $result->fetch_assoc()) {
		$roles[] = $row['role'];
	}
	return $roles;
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
        case 5:
            $team = "hr";
            break;
        case 6:
            $team = "it";
            break;
        case 7:
            $team = "purchase";
            break;
        case 8:
            $team = "training";
            break;
        case 9:
            $team = "inventory";
            break;
        case 10:               
            $team = "admin";
            break;
        default:
            return "";
    }

    // --- Normalize description ---
    // 1) convert escaped "\n" to real newlines
    $plain_description = str_replace("\\n", "\n", $description);
    // 2) convert real newlines to <br> for HTML
    $description_html  = nl2br($plain_description, false); // gives <br>

    $members = getTeamMembers($team);
    $member_email = [];
    foreach ($members as $member) {
        $member_email[] = get_email_user($member);
    }
    $to    = implode(",", $member_email);

    $from    = get_email_user($row['status_updated_by']);
     $cc = "deepti.rukade@gmail.com";
    
    $toolName = getComplaintToolName($row);
    $subject = "The Task/Complaint for $team has been closed";

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

function getToolName1($machid,$type)
{
	if($type==1 || $type==4)
	{
		return getToolName($machid);
	}
	else if($type==2)
	{
		return getToolName_facility($machid);
	}
	else if($type==3)
	{
		return getToolName_safety($machid);
	}
	else
	{
		return null;
	}
	// global $db_slot;
	// $stmt = $db_slot->prepare("SELECT name FROM resources WHERE machid = ?");
	// $stmt->bind_param("i", $machid);
	// $stmt->execute();
	// $result = $stmt->get_result();
	// $row = $result->fetch_assoc();
	// return $row['name'] ?? null;
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
function insert_complaint($member_id, $machine_id, $process_dev, $anti_cont_dev, $complaint_description, $type, $team_head, $parent_id, $original_id, $scheduler)
{
    global $db_equip;

    $process_dev   = ($process_dev === 'NULL' || $process_dev === '') ? null : $process_dev;
    $anti_cont_dev = ($anti_cont_dev === 'NULL' || $anti_cont_dev === '') ? null : $anti_cont_dev;
    $parent_id     = ($parent_id === 'NULL' || $parent_id === '') ? null : (int)$parent_id;
    $original_id   = ($original_id === 'NULL' || $original_id === '') ? null : (int)$original_id;

    // allocated_to (team head) can be array or single
    if (is_array($team_head)) {
        $team_head = isset($team_head[0]) ? (int)$team_head[0] : null;
    } else {
        $team_head = ($team_head === 'NULL' || $team_head === '') ? null : (int)$team_head;
    }

    $sql = "INSERT INTO equipment_complaint
            (member_id, machine_id, process_develop, anti_contamination_develop, complaint_description, time_of_complaint, status, type, allocated_to, parent_id, original_id,scheduler)
            VALUES (?, ?, ?, ?, ?, CURRENT_TIMESTAMP(), 0, ?, ?, ?, ?, ?)";

    $stmt = $db_equip->prepare($sql);
    if (!$stmt) {
        die('Prepare failed: ' . $db_equip->error);
    }

    // i i s s s i i i i = 9 params
    $stmt->bind_param(
        "iisssiiiii",
        $member_id,
        $machine_id,
        $process_dev,
        $anti_cont_dev,
        $complaint_description,
        $type,
        $team_head,     // ✅ FIX: was $allocated_to (undefined)
        $parent_id,
        $original_id,
		$scheduler
    );

    if (!$stmt->execute()) {
        die('Execute failed: ' . $stmt->error);
    }

    $new_id = $db_equip->insert_id; // ✅ return something meaningful
    $stmt->close();

    return $new_id;
}


function insert_complaint_transfer($complaint_id, $allocated_to)
{
    global $db_equip;

    $stmt = $db_equip->prepare("SELECT * FROM equipment_complaint WHERE complaint_id=?");
    if (!$stmt) {
        die("Prepare failed: " . $db_equip->error);
    }

    $stmt->bind_param("i", $complaint_id);
    if (!$stmt->execute()) {
        die("Execute failed: " . $stmt->error);
    }

    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();

    if (!$row) {
        return 0; // complaint not found
    }

    $member_id = (int)$row['member_id'];
    $machine_id = (int)$row['machine_id'];
    $type = (int)$row['type'];
    $process_dev = $row['process_develop'];
    $anti_cont_dev = $row['anti_contamination_develop'];
    $complaint_description = $row['complaint_description'];
    $original_id = !empty($row['original_id']) ? (int)$row['original_id'] : (int)$complaint_id;

    // ✅ return the inserted row id
    return insert_complaint(
        $member_id,
        $machine_id,
        $process_dev,
        $anti_cont_dev,
        $complaint_description,
        $type,
        $allocated_to,
        $complaint_id,
        $original_id,
		0
    );
}

function getInvolvedOriginalIds($type, $member_id)
{
  global $db_equip;

  $originalIds = [];

  // ✅ find complaints where user is involved
  // involved = complaint raised by me OR allocated to me OR I added tracking
  $sql = "
    SELECT DISTINCT
      CASE
        WHEN c.original_id = 0 THEN c.complaint_id
        ELSE c.original_id
      END AS root_id
    FROM equipment_complaint c
    LEFT JOIN trouble_track t ON t.complaint_id = c.complaint_id
    WHERE c.type = ?
      AND (
        c.member_id = ?
        OR c.allocated_to = ?
        OR c.member_id = ?
      )
  ";

  $stmt = mysqli_prepare($db_equip, $sql);
  mysqli_stmt_bind_param($stmt, "iiii", $type, $member_id, $member_id, $member_id);
  mysqli_stmt_execute($stmt);
  $res = mysqli_stmt_get_result($stmt);

  while ($row = mysqli_fetch_assoc($res)) {
    if (!empty($row['root_id'])) $originalIds[] = (int)$row['root_id'];
  }

  mysqli_stmt_close($stmt);

  return array_values(array_unique($originalIds));
}



function schedule_complaint($complaint_id, $timer_days) {
	global $db_equip;

	$created_by = $_SESSION['memberid'] ?? null;
	$timer_days = (int)$timer_days;

	/* ---------------------------
	IF TIMER = 0 → NO SCHEDULER
	----------------------------*/
	if ($timer_days === 0) {
		// // Explicitly mark as NOT scheduled
		

		return null;
	}

	/* ---------------------------
	CALCULATE TRIGGER DATE (DATE-ONLY)
	----------------------------*/

	if ($timer_days === 30) {
		// Monthly → first day of next month
		$trigger_date = strtotime(date('Y-m-01', strtotime('+1 month')));
	} else {
		// Daily / Weekly / Custom
		$trigger_date = strtotime(date('Y-m-d', strtotime("+$timer_days days")));
	}

	$schedule_datetime = date("Y-m-d H:i:s", $trigger_date);

	/* ---------------------------
	INSERT SCHEDULER
	----------------------------*/

	$stmt = $db_equip->prepare("
		INSERT INTO scheduler_daily_tasks
		(complaint_id, timer, trigger_date, trigger_datetime, created_by, status)
		VALUES (?, ?, ?, ?, ?, 1)
	");

	$stmt->bind_param(
		"iiisi",
		$complaint_id,
		$timer_days,
		$trigger_date,
		$schedule_datetime,
		$created_by
	);

	$stmt->execute();
	$scheduler_id = $stmt->insert_id;
	$stmt->close();


	return $scheduler_id;
}


function insert_allocation($complaint_id,$team_head,$type,$work_description)
{
	global $db_equip;
	$if_complaint_exists = $db_equip->prepare("SELECT MAX(index_id) as id FROM allocation_track WHERE complaint_id=?");
	$if_complaint_exists->bind_param("i", $complaint_id);
	$if_complaint_exists->execute();
	$result = $if_complaint_exists->get_result();
	if ($result->num_rows > 0) 
	{
		$index = $result->fetch_assoc();
		$index_id = $index['id'] +1;		
	}
	else 
	{
		$index_id = 1;
	}
	$int_ts = time();
	$stmt = $db_equip->prepare("INSERT INTO allocation_track (index_id,complaint_id,type,work_desc,memberid,timestamp,int_timestamp) VALUES (?,?,?,?,?,CURRENT_TIMESTAMP(),?)");
	if (!$stmt) 
	{
		die("Prepare failed: " . $db_equip->error);
	}
	$stmt->bind_param("iiisii", $index_id, $complaint_id, $type,$work_description, $team_head,$int_ts);
	if (!$stmt->execute()) 
	{
		die("Execute failed: " . $stmt->error);
	}
	$stmt->close();
}

function my_complaint($member_id, $type, $scheduler)
{
    global $db_equip;

    $data = [];
    $stmt = null;

    /* ------------------------------
       Build query dynamically
    ------------------------------ */

    if ($scheduler == 0) {

        // Fetch all complaints for member (no scheduler filter)
        $sql = "
            SELECT *
            FROM equipment_complaint
            WHERE member_id = ?
            ORDER BY time_of_complaint DESC
        ";

        $stmt = mysqli_prepare($db_equip, $sql);
        mysqli_stmt_bind_param($stmt, "i", $member_id);

    } else {

        // Fetch complaints for member + scheduler
        $sql = "
            SELECT *
            FROM equipment_complaint
            WHERE member_id = ?
              AND scheduler = ?
            ORDER BY time_of_complaint DESC
        ";

        $stmt = mysqli_prepare($db_equip, $sql);
        mysqli_stmt_bind_param($stmt, "ii", $member_id, $scheduler);
    }

    /* ------------------------------
       Execute & fetch
    ------------------------------ */

    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    while ($row = mysqli_fetch_assoc($result)) {

        $data[] = [
            'complaint_id'              => $row['complaint_id'],
            'parent_id'                 => $row['parent_id'],
            'original_id'               => $row['original_id'],
            'member_id'                 => $row['member_id'],
            'machine_id'                => $row['machine_id'],
            'process_develop'           => $row['process_develop'],
            'anti_contamination_develop'=> $row['anti_contamination_develop'],
            'complaint_description'     => $row['complaint_description'],
            'time_of_complaint'         => $row['time_of_complaint'],
            'status'                    => $row['status'],
            'status_timestamp'          => $row['status_timestamp'],
            'upload_file'               => $row['upload_file'],
            'type'                      => $row['type'],
            'allocated_to'              => $row['allocated_to'],
            'scheduler'                 => $row['scheduler']
        ];
    }

    mysqli_stmt_close($stmt);
    return $data;
}


function my_allocated_complaint($member_id, $type, $scheduler, $tools_name ,$is_critical)
{
    global $db_equip;
    $data = array();

    $toolSql = '';
    if ($tools_name !== '') {
        $toolSql = " AND machine_id = ?";
    }

    if ($scheduler == 0) 
    {
        if($is_critical)
        {
            $model = "Yes"; 
            $sql = "SELECT ec.*
                FROM equipment_troubleshooting.equipment_complaint AS ec
                INNER JOIN slotbooking.resources AS r 
                ON ec.machine_id = r.machid 
                WHERE ec.allocated_to = ?
                  AND r.model = ?
                  AND ec.type = ?
                  AND (ec.scheduler = 0 OR ec.scheduler IS NULL)
                  AND ec.status != 2
                  $toolSql
                ORDER BY ec.time_of_complaint DESC";

            $stmt = mysqli_prepare($db_equip, $sql);

            if ($tools_name !== '') {
                mysqli_stmt_bind_param($stmt, "isii", $member_id, $model, $type, $tools_name);
            } else {
                mysqli_stmt_bind_param($stmt, "isi", $member_id, $model, $type);
            }
        }
        else 
        {
            $sql = "SELECT * FROM equipment_complaint
                WHERE allocated_to = ?
                  AND type = ?
                  AND (scheduler = 0 OR scheduler IS NULL)
                  AND status != 2
                  $toolSql
                ORDER BY time_of_complaint DESC";

            $stmt = mysqli_prepare($db_equip, $sql);

            if ($tools_name !== '') {
                mysqli_stmt_bind_param($stmt, "iii", $member_id, $type, $tools_name);
            } else {
                mysqli_stmt_bind_param($stmt, "ii", $member_id, $type);
            }
        }

    } 
    else 
    {

        $sql = "SELECT * FROM equipment_complaint
                WHERE allocated_to = ?
                  AND type = ?
                  AND scheduler = ?
                  $toolSql
                ORDER BY time_of_complaint DESC";

        $stmt = mysqli_prepare($db_equip, $sql);

        if ($tools_name !== '') {
            mysqli_stmt_bind_param($stmt, "iiii", $member_id, $type, $scheduler, $tools_name);
        } else {
            mysqli_stmt_bind_param($stmt, "iii", $member_id, $type, $scheduler);
        }
    }

    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    $i = 0;
    while ($row = mysqli_fetch_assoc($result)) {
        $data[$i] = $row;
        $i++;
    }

    mysqli_stmt_close($stmt);
    return $data;
}


function all_complaint($head, $type, $scheduler, $tools_name ,$is_critical)
{
    global $db_equip;

    $data = [];
    $type = (int)$type;
    $scheduler = (int)$scheduler;

    $toolSql = '';
    $critical_condition = '';
    if ($tools_name !== '') {
        $toolSql = " AND ec.machine_id = ? ";
        $tools_name = (int)$tools_name;
    }
   

    if ($scheduler === 0) 
    {

        if($is_critical)
        {
            $model = "Yes"; 
            $sql = "
                SELECT ec.*
                FROM equipment_troubleshooting.equipment_complaint AS ec
                INNER JOIN slotbooking.resources AS r 
                ON ec.machine_id = r.machid 
                LEFT JOIN equipment_complaint o
                ON o.complaint_id = ec.original_id
                WHERE r.model = ?
                AND ec.type = ?
                AND (ec.scheduler = 0 OR ec.scheduler IS NULL)
                AND ec.status != 2
                AND (
                        (ec.parent_id = 0 AND ec.original_id = 0)

                        OR

                        (ec.original_id != 0 AND o.type <> ec.type)
                    )
                $toolSql
                ORDER BY ec.time_of_complaint DESC";
                $stmt = mysqli_prepare($db_equip, $sql);
                 if ($tools_name !== '') {
                 mysqli_stmt_bind_param($stmt, "sii",$model, $type, $tools_name);
                } else {
                    mysqli_stmt_bind_param($stmt, "si",$model, $type);
                 }
        }
        else 
        {
            $sql = "
                SELECT ec.*
                FROM equipment_complaint ec
                LEFT JOIN equipment_complaint o
                
                ON o.complaint_id = ec.original_id
                WHERE ec.type = ?
                AND (ec.scheduler = 0 OR ec.scheduler IS NULL)
                AND ec.status != 2           
                AND (
                        (ec.parent_id = 0 AND ec.original_id = 0)

                        OR

                        (ec.original_id != 0 AND o.type <> ec.type)
                    )
                $toolSql
                ORDER BY ec.time_of_complaint DESC
            ";

            $stmt = mysqli_prepare($db_equip, $sql);

            if ($tools_name !== '') {
                mysqli_stmt_bind_param($stmt, "ii", $type, $tools_name);
            } else {
                mysqli_stmt_bind_param($stmt, "i", $type);
            }
        }

    }
    
    else {

        $sql = "
            SELECT ec.*
            FROM equipment_complaint ec
            LEFT JOIN equipment_complaint o
              ON o.complaint_id = ec.original_id
            WHERE ec.type = ?
              AND ec.scheduler = ?
              AND (
                    (ec.parent_id = 0 AND ec.original_id = 0)
                    OR
                    (ec.original_id != 0 AND o.type <> ec.type)
                  )
              $toolSql
            ORDER BY ec.time_of_complaint DESC
        ";

        $stmt = mysqli_prepare($db_equip, $sql);

        if ($tools_name !== '') {
            mysqli_stmt_bind_param($stmt, "iii", $type, $scheduler, $tools_name);
        } else {
            mysqli_stmt_bind_param($stmt, "ii", $type, $scheduler);
        }
    }
//    echo $sql;
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    while ($row = mysqli_fetch_assoc($result)) {
        $data[] = $row;
    }

    mysqli_stmt_close($stmt);
    return $data;
}



function closed_complaint($type, $tools_name, $is_critical)
{
	global $db_equip;
	$data = array();

    if($is_critical)
    {
        $model = "Yes"; 
        $sql = "
            SELECT a.*, b.*
            FROM equipment_troubleshooting.equipment_complaint AS a
            INNER JOIN slotbooking.resources AS b 
            ON a.machine_id = b.machid 
            WHERE b.model = ? 
              AND a.type = ? 
              AND (a.scheduler = 0 OR a.scheduler IS NULL) 
              AND a.status = 2";

        if ($tools_name !== '') {
            $sql .= " AND a.machine_id = ? ";
        }

        $sql .= " ORDER BY a.time_of_complaint DESC";

        $stmt = mysqli_prepare($db_equip, $sql);

        if ($tools_name !== '') {
            mysqli_stmt_bind_param($stmt, "sii", $model, $type, $tools_name);
        } else {
            mysqli_stmt_bind_param($stmt, "si", $model, $type);
        }
    }
    else
    {
        /* Base query */
        $sql = "SELECT * FROM equipment_complaint 
                WHERE type = ? 
                AND (scheduler = 0 OR scheduler IS NULL) 
                AND status = 2";

        /* Tool filter (SAME WAY as requested) */
        if ($tools_name !== '') {
            $sql .= " AND machine_id = ? ";
        }

        $sql .= " ORDER BY time_of_complaint DESC";

        $stmt = mysqli_prepare($db_equip, $sql);

        /* Bind params */
        if ($tools_name !== '') {
            mysqli_stmt_bind_param($stmt, "ii", $type, $tools_name);
        } else {
            mysqli_stmt_bind_param($stmt, "i", $type);
        }

    }

	
	// Execute & fetch results
	mysqli_stmt_execute($stmt);
	$result = mysqli_stmt_get_result($stmt);

	$i = 0;
	while ($row = mysqli_fetch_assoc($result)) {
		$data[$i]['complaint_id']            = $row['complaint_id'];
		$data[$i]['parent_id']               = $row['parent_id'];
		$data[$i]['original_id']             = $row['original_id'];
		$data[$i]['member_id']               = $row['member_id'];
		$data[$i]['machine_id']              = $row['machine_id'];
		$data[$i]['complaint_description']   = $row['complaint_description'];
		$data[$i]['process_develop']          = $row['process_develop'];
		$data[$i]['anti_contamination_develop'] = $row['anti_contamination_develop'];
		$data[$i]['time_of_complaint']        = $row['time_of_complaint'];
		$data[$i]['status']                   = $row['status'];
		$data[$i]['status_timestamp']         = $row['status_timestamp'];
		$data[$i]['upload_file']              = $row['upload_file'];
		$data[$i]['type']                     = $row['type'];
		$data[$i]['allocated_to']             = $row['allocated_to'];
		$data[$i]['scheduler']                = $row['scheduler'];
		$i++;
	}

	mysqli_stmt_close($stmt);
	return $data;
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
	 $sql = "SELECT *
                FROM equipment_complaint
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

	// echo $sql;

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
		$data[$i]['status_timestamp']      = $row['status_timestamp'];
		$data[$i]['upload_file']          = $row['upload_file'];
		$data[$i]['type']                 = $row['type'];
		$data[$i]['allocated_to']         = $row['allocated_to'];
		$data[$i]['status_updated_by']    = $row['status_updated_by'];
		$data[$i]['memberid']            = $row['memberid'] ?? null;
		$data[$i]['parent_id']            = $row['parent_id'];
		$data[$i]['original_id']          = $row['original_id'];
		$data[$i]['scheduler']            = $row['scheduler'];	

		$i++;
	}

	mysqli_stmt_close($stmt);
	return $data;
}


function isOriginalComplaint($complaint_id) {
  global $db_equip;

  $sql = "SELECT 1
          FROM equipment_complaint
          WHERE complaint_id = ?
            AND parent_id = 0
            AND original_id = 0
          LIMIT 1";
  $stmt = mysqli_prepare($db_equip, $sql);
  mysqli_stmt_bind_param($stmt, "i", $complaint_id);
  mysqli_stmt_execute($stmt);
  $res = mysqli_stmt_get_result($stmt);
  $ok = ($res && mysqli_num_rows($res) > 0);
  mysqli_stmt_close($stmt);
  return $ok;
}

function allChildrenClosed($original_id) {
  global $db_equip;

  // any child of this original that is NOT closed?
  $sql = "SELECT COUNT(*) AS open_cnt
          FROM equipment_complaint
          WHERE original_id = ?
            AND status <> 2";
  $stmt = mysqli_prepare($db_equip, $sql);
  mysqli_stmt_bind_param($stmt, "i", $original_id);
  mysqli_stmt_execute($stmt);
  $res = mysqli_stmt_get_result($stmt);
  $row = mysqli_fetch_assoc($res);
  mysqli_stmt_close($stmt);

  return ((int)($row['open_cnt'] ?? 0) === 0);
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

function update_complaint($complaint_id, $status, $c_date, $status_updated_by)
{
	global $db_equip;
	if ($c_date == '') {
		$c_date = "0000-00-00 00:00:00";
	}
	else {
			$c_date = date("Y-m-d H:i:s", strtotime($c_date)); // ✅ use 24h format
	}

	$stmt = $db_equip->prepare("UPDATE equipment_complaint SET status=?, status_timestamp=?, status_updated_by=?  WHERE complaint_id=?");
	

	$stmt->bind_param("isii", $status, $c_date, $status_updated_by, $complaint_id);
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
		$data[$i]['status_timestamp'] = $row['status_timestamp'];
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

	$stmt = $db_equip->prepare("SELECT complaint_id, time_of_complaint, status, status_timestamp  
                                FROM equipment_complaint 
                                WHERE machine_id=? AND type=?");
	$stmt->bind_param("ii", $tool, $type);
	$stmt->execute();
	$result = $stmt->get_result();

	while ($row = $result->fetch_assoc()) {
		$data[$i]['complaint_id']      = $row['complaint_id'];
		$data[$i]['time_of_complaint'] = $row['time_of_complaint'];
		$data[$i]['status']            = $row['status'];
		$data[$i]['status_timestamp']   = $row['status_timestamp'];
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


function get_permission_details($memberid,$role)
{
	global $db_equip;
	$data = [];
	$sql = "SELECT * FROM role where memberid=$memberid	and role='$role'";
	$stmt = $db_equip->prepare($sql);
	$stmt->execute();
	$result = $stmt->get_result();

	$i = 0;
	while ($row = $result->fetch_assoc()) {
		$data[$i]['memberid'] = $row['memberid'];
		$data[$i]['role'] = $row['role'];
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
	        WHERE (position = 'IITBNF Staff' OR position = 'Project Staff' OR position ='Faculty' ) 
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

// function check_permission($type, $memberid)
// {
// 	global $db_equip;
// 	$sql = "SELECT id FROM permission WHERE id = ? AND $type = 1";
// 	$stmt = $db_equip->prepare($sql);
// 	if (!$stmt) {
// 		return null;
// 	}

// 	$stmt->bind_param("i", $memberid);
// 	$stmt->execute();
// 	$result = $stmt->get_result();
// 	if ($result && ($row = $result->fetch_assoc())) {
// 		return $row['id'];
// 	} else {
// 		return null;
// 	}
// }

function check_permission($type, $memberid)
{
    global $db_equip;

    switch ((string)$type) {

        case '1': // Equipment
            $sql = "SELECT 1 FROM role WHERE role IN (1,5) AND memberid=$memberid";
            break;

        case '2': // Facility
            $sql = "SELECT 1 FROM role WHERE role IN (2,6) AND memberid=$memberid";
            break;

        case '3': // Safety
            $sql = "SELECT 1 FROM role WHERE role IN (3,7) AND memberid=$memberid";
            break;

        case '4': // Process
            $sql = "SELECT 1 FROM role WHERE role IN (4,8) AND memberid=$memberid";
            break;

        case '5': // HR
            $sql = "SELECT 1 FROM role WHERE role IN (12,15) AND memberid=$memberid";
            break;

        case '6': // IT
            $sql = "SELECT 1 FROM role WHERE role IN (13,16) AND memberid=$memberid";
            break;

        case '7': // Purchase
            $sql = "SELECT 1 FROM role WHERE role IN (14,17) AND memberid=$memberid";
            break;

        case '8': // Training
            $sql = "SELECT 1 FROM role WHERE role IN (18,20) AND memberid=$memberid";
            break;

        case '9': // Inventory
            $sql = "SELECT 1 FROM role WHERE role IN (19,21) AND memberid=$memberid";
            break;

        case '10': // Admin
        $sql = "SELECT 1 FROM role WHERE role IN (22,23) AND memberid=$memberid";
        break;

        case 'LA': // Lab Admin
            $sql = "SELECT 1 FROM role WHERE role IN (9,10,11) AND memberid=$memberid";
            break;

        default:
            return 0;
    }

    $result = $db_equip->query($sql);
    return ($result && $result->num_rows > 0) ? 1 : 0;
}


function get_user_role_level($type, $memberid)
{
    global $db_equip;

    switch ((string)$type) {
        case '1': // equipment
            $head_roles = [1];
            $team_roles = [5];
            break;
        case '2': // facility
            $head_roles = [2];
            $team_roles = [6];
            break;
        case '3': // safety
            $head_roles = [3];
            $team_roles = [7];
            break;
        case '4': // process
            $head_roles = [4];
            $team_roles = [8];
            break;
			
        default:
            return 'none';
    }

    $sql = "SELECT role FROM role WHERE memberid = ?";
    $stmt = $db_equip->prepare($sql);
    $stmt->bind_param("i", $memberid);
    $stmt->execute();
    $res = $stmt->get_result();

    while ($row = $res->fetch_assoc()) {
        if (in_array((int)$row['role'], $head_roles, true)) {
            return 'head';
        }
        if (in_array((int)$row['role'], $team_roles, true)) {
            return 'team';
        }
    }

    return 'none';
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

// function alphanumeric_underscore($var)
// {
// 	if(!(preg_match('/^[a-zA-Z0-9_ ]+$/', $text)))
// 	{
// 		header("location:javascript://history.go(-1)");
//    exit();
// 	}
// 	else{
// 		return $var;
// 	}
// }

/*
function date_time($time)
{
	if(!(preg_match('/^\d\d\d\d-(0?[1-9]|1[0-2])-(0?[1-9]|[12][0-9]|3[01]) (00|[0-9]|1[0-9]|2[0-3]):([0-9]|[0-5][0-9]):([0-9]|[0-5][0-9])$/g', $time)))
	{
		header("location:javascript://history.go(-1)");
   exit();
	}
	else {
	 	return $time;
	}
}
*/

// function shortDesc($text, $limit = 100)
// {
//   $text = str_replace(["\\r\\n", "\\n", "\\r", "\r\n", "\r", "\n"], "\n", $text);
//   $text = str_replace("\\", "", $text);
//   $words = preg_split('/\s+/', trim($text));
//   $short = (count($words) > $limit) ? implode(' ', array_slice($words, 0, $limit)) . '...' : $text;
//   return nl2br(htmlentities($short));
// }
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
function check_head_permission($type, $memberid)
{
    global $db_equip;
	$sql = "SELECT 1 FROM role WHERE role = $type AND memberid = $memberid";
    $result = $db_equip->query($sql);
    return ($result && $result->num_rows > 0) ? 1 : 0;
}

function getUserHeadLabels($memberid)
{
    $headTypes = [
        1  => 'Equipment Head',
        2  => 'Facility Head',
        3  => 'Safety Head',
        4  => 'Process Head',

        5  => 'Equipment Team',
        6  => 'Facility Team',
        7  => 'Safety Team',
        8  => 'Process Team',

        9  => 'Lab Manager',
        10 => 'Assistant Manager',
        11 => 'PI',

        12 => 'HR Head',
        13 => 'IT Head',
        14 => 'Purchase Head',

        15 => 'HR Team',
        16 => 'IT Team',
        17 => 'Purchase Team',

        // ✅ NEW — Training
        18 => 'Training Head',
        19 => 'Training Team',

        // ✅ NEW — Inventory
        20 => 'Inventory Head',
        21 => 'Inventory Team',

        // ✅ NEW — Admin
        22 => 'Admin Head',
        23 => 'Admin Team',
        24 => 'FOC Member',
    ];

    $heads = [];

    foreach ($headTypes as $type => $label) {
        if (check_head_permission($type, $memberid)) {
            $heads[] = $label;
        }
    }

    return $heads;
}




// added by sowjanya on 13/11/2026
function is_EquipmentHead($memberid){
	global $db_equip;
    $sql = "SELECT * FROM role WHERE memberid = $memberid AND role = 1";
    if (mysqli_num_rows(mysqli_query($db_equip, $sql)) > 0) {
        return true;
    }

    return false;
}


function is_FacilityHead($memberid){
	global $db_equip;
    $sql = "SELECT * FROM role WHERE memberid = $memberid AND role = 2";
    if (mysqli_num_rows(mysqli_query($db_equip, $sql)) > 0) {
        return true;
    }

    return false;
}


function is_SafetyHead($memberid){
	global $db_equip;
    $sql = "SELECT * FROM role WHERE memberid = $memberid AND role = 3";
    if (mysqli_num_rows(mysqli_query($db_equip, $sql)) > 0) {
        return true;
    }

    return false;
}


function is_ProcessHead($memberid){
	global $db_equip;
    $sql = "SELECT * FROM role WHERE memberid = $memberid AND role = 4";
    if (mysqli_num_rows(mysqli_query($db_equip, $sql)) > 0) {
        return true;
    }

    return false;
}

function is_EquipmentTeam($memberid){
	global $db_equip;
    $sql = "SELECT * FROM role WHERE memberid = $memberid AND role = 5";
    if (mysqli_num_rows(mysqli_query($db_equip, $sql)) > 0) {
        return true;
    }

    return false;
}

function is_FacilityTeam($memberid){
	global $db_equip;
    $sql = "SELECT * FROM role WHERE memberid = $memberid AND role = 6";
    if (mysqli_num_rows(mysqli_query($db_equip, $sql)) > 0) {
        return true;
    }

    return false;
}

function is_SafetyTeam($memberid){
	global $db_equip;
    $sql = "SELECT * FROM role WHERE memberid = $memberid AND role = 7";
    if (mysqli_num_rows(mysqli_query($db_equip, $sql)) > 0) {
        return true;
    }

    return false;
}

function is_ProcessTeam($memberid){
    global $db_equip;
    $sql = "SELECT * FROM role WHERE memberid = $memberid AND role = 8";
    if (mysqli_num_rows(mysqli_query($db_equip, $sql)) > 0) {
        return true;
    }

    return false;
}

function is_LabManager($memberid){
    global $db_equip;
    $sql = "SELECT * FROM role WHERE memberid = $memberid AND role = 9";
    if (mysqli_num_rows(mysqli_query($db_equip, $sql)) > 0) {
        return true;
    }

    return false;
}

function is_AssistLabManager($memberid){
    global $db_equip;
    $sql = "SELECT * FROM role WHERE memberid = $memberid AND role = 10";
    if (mysqli_num_rows(mysqli_query($db_equip, $sql)) > 0) {
        return true;
    }

    return false;
}

function is_PI($memberid){
    global $db_equip;
    $sql = "SELECT * FROM role WHERE memberid = $memberid AND role = 11";
    if (mysqli_num_rows(mysqli_query($db_equip, $sql)) > 0) {
        return true;
    }

    return false;
}

function is_FOC_member($memberid){
    global $db_equip;
    $sql = "SELECT * FROM role WHERE memberid = $memberid AND role = 24";
    if (mysqli_num_rows(mysqli_query($db_equip, $sql)) > 0) {
        return true;
    }

    return false;
}

function is_HRHead($memberid){
    global $db_equip;
    $sql = "SELECT * FROM role WHERE memberid = $memberid AND role = 12";
    if (mysqli_num_rows(mysqli_query($db_equip, $sql)) > 0) {
        return true;
    }

    return false;
}

function is_ITHead($memberid){
    global $db_equip;
    $sql = "SELECT * FROM role WHERE memberid = $memberid AND role = 13";
    if (mysqli_num_rows(mysqli_query($db_equip, $sql)) > 0) {
        return true;
    }

    return false;
}

function is_PurchaseHead($memberid){
    global $db_equip;
    $sql = "SELECT * FROM role WHERE memberid = $memberid AND role = 14";
    if (mysqli_num_rows(mysqli_query($db_equip, $sql)) > 0) {
        return true;
    }

    return false;
}

function is_HRTeam($memberid){
    global $db_equip;
    $sql = "SELECT * FROM role WHERE memberid = $memberid AND role = 15";
    if (mysqli_num_rows(mysqli_query($db_equip, $sql)) > 0) {
        return true;
    }

    return false;
}

function is_ITTeam($memberid){
    global $db_equip;
    $sql = "SELECT * FROM role WHERE memberid = $memberid AND role = 16";
    if (mysqli_num_rows(mysqli_query($db_equip, $sql)) > 0) {
        return true;
    }

    return false;
}

function is_PurchaseTeam($memberid){
    global $db_equip;
    $sql = "SELECT * FROM role WHERE memberid = $memberid AND role = 17";
    if (mysqli_num_rows(mysqli_query($db_equip, $sql)) > 0) {
        return true;
    }

    return false;
}
function is_TrainingHead($memberid){
    global $db_equip;
    $sql = "SELECT * FROM role WHERE memberid = $memberid AND role = 18";
    if (mysqli_num_rows(mysqli_query($db_equip, $sql)) > 0) {
        return true;
    }

    return false;
}

function is_TrainingTeam($memberid){
    global $db_equip;
    $sql = "SELECT * FROM role WHERE memberid = $memberid AND role = 20";
    if (mysqli_num_rows(mysqli_query($db_equip, $sql)) > 0) {
        return true;
    }

    return false;
}

function is_InventoryHead($memberid){
    global $db_equip;
    $sql = "SELECT * FROM role WHERE memberid = $memberid AND role = 19";
    if (mysqli_num_rows(mysqli_query($db_equip, $sql)) > 0) {
        return true;
    }

    return false;
}
function is_InventoryTeam($memberid){
    global $db_equip;
    $sql = "SELECT * FROM role WHERE memberid = $memberid AND role = 21";
    if (mysqli_num_rows(mysqli_query($db_equip, $sql)) > 0) {
        return true;
    }

    return false;
}

function is_AdminHead($memberid){
    global $db_equip;
    $sql = "SELECT * FROM role WHERE memberid = $memberid AND role = 22";
    if (mysqli_num_rows(mysqli_query($db_equip, $sql)) > 0) {
        return true;
    }

    return false;
}

function is_AdminTeam($memberid){
    global $db_equip;
    $sql = "SELECT * FROM role WHERE memberid = $memberid AND role = 23";
    if (mysqli_num_rows(mysqli_query($db_equip, $sql)) > 0) {
        return true;
    }

    return false;
}






function is_ITadmin($memberid){
	global $db_slot;
    $query = "SELECT status FROM role_permissions WHERE memberid = ? AND role = 1";
    $stmt = $db_slot->prepare($query);
    if ($stmt === false) {
        return 0;
    }
    $stmt->bind_param("i", $memberid);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        return $row['status'];
    } else {
        return 0;
    }
}

function is_Admin($memberid){
	global $db_slot;
    $qry = "SELECT is_admin FROM login WHERE memberid = ?";
    $stmt = $db_slot->prepare($qry);

    if ($stmt === false) {
        die('Prepare failed: ' . htmlspecialchars($link->error));
    }
    $stmt->bind_param("i", $memberid);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    return $row ? $row['is_admin'] : null;
}


function makeLinksClickable($text) {
    if (!$text) return '';

    // Escape HTML first
    $text = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');

    // Regex to detect URLs
    $pattern = '/(https?:\/\/[^\s]+)/i';

    // Only wrap actual URLs in <a> tag
    $replacement = '<a href="$1" target="_blank" style="color:#0d6efd;">$1</a>';

    return preg_replace($pattern, $replacement, $text);
}

function getTxtCategories($type) {
    // Base path for category files
    $basePath = __DIR__ . '/../views/complaint_categories/';

    // Map type to specific text files
    $map = [
        5  => $basePath . 'hr.txt',
        6  => $basePath . 'it.txt',
        7  => $basePath . 'purchase.txt',
        8  => $basePath . 'training.txt',
        9  => $basePath . 'inventory.txt',
        10 => $basePath . 'admin.txt',   // ✅ Added Admin
    ];

    // If type not found in map or file doesn't exist, return empty array
    if (!isset($map[$type]) || !file_exists($map[$type])) {
        return [];
    }

    // Read lines from the file
    $lines = file($map[$type], FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $data = [];

    foreach ($lines as $line) {
        // Split line into name and ID
        $parts = array_map('trim', explode('-', $line));

        // Ensure line has both name and ID
        if (count($parts) === 2) {
            [$name, $id] = $parts;
            $data[$id] = $name;
        }
    }

    return $data;
}


function renderComplaintDesc(string $text): string
{
    // Decode entities like &#039;
    $text = html_entity_decode($text, ENT_QUOTES, 'UTF-8');

    // Remove stored HTML like <br>, <p>, etc.
    $text = strip_tags($text);

    // Normalize newlines
    $text = preg_replace("/\r\n|\r|\n/", "\n", $text);

    // Convert newlines to real <br>
    return nl2br($text, false);
}

function renderExpandableText($text, $limit = 200) {
    $text = trim(htmlspecialchars_decode(strip_tags($text)));

    if (mb_strlen($text) <= $limit) {
        return nl2br(htmlspecialchars($text));
    }

    $short = mb_substr($text, 0, $limit);

    return '
      <span class="short-text">'.nl2br(htmlspecialchars($short)).'</span>
      <span class="full-text d-none">'.nl2br(htmlspecialchars($text)).'</span>
      <a href="#" class="toggle-desc ms-1">Show more</a>
    ';
}

function getOriginalComplaintId($complaint_id) {
    global $db_equip;

    $sql = "SELECT original_id 
            FROM equipment_complaint 
            WHERE complaint_id = " . (int)$complaint_id;

    $res = mysqli_query($db_equip, $sql);
    $row = mysqli_fetch_assoc($res);

    return (int)($row['original_id'] ?? 0);
}

function isComplaintClosed($complaint_id) {
    global $db_equip;

    $sql = "SELECT status 
            FROM equipment_complaint 
            WHERE complaint_id = " . (int)$complaint_id;

    $res = mysqli_query($db_equip, $sql);
    $row = mysqli_fetch_assoc($res);

    return ((int)$row['status'] === 2); // assuming 2 = Closed
}

function getComplaintTypeById($complaint_id) {
  global $db_equip;
  $sql = "SELECT type FROM equipment_complaint WHERE complaint_id = " . (int)$complaint_id;
  $res = mysqli_query($db_equip, $sql);
  $row = mysqli_fetch_assoc($res);
  return (int)($row['type'] ?? 0);
}

function canUserUpdateType($member_id, $type) {

  // Lab managers can update ALL
  if (is_LabManager($member_id) || is_AssistLabManager($member_id)) {
    return true;
  }

  switch ($type) {
    case 1: return is_EquipmentHead($member_id);
    case 2: return is_FacilityHead($member_id);
    case 3: return is_SafetyHead($member_id);
    case 4: return is_ProcessHead($member_id);
    case 5: return is_HRHead($member_id);
    case 6: return is_ITHead($member_id);
    case 7: return is_PurchaseHead($member_id);
    case 8: return is_TrainingHead($member_id);
    case 9: return is_InventoryHead($member_id);
    case 10: return is_AdminHead($member_id);   
    default: return false;
  }
}






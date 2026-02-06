<?php
include("../includes/auth_check.php");
include("../config/connect.php");
include("../includes/common.php");
include("../includes/class.phpmailer.php");
$returnUrl = $_GET['return'] ?? '';

function js_safe_utf8($str) {
  $str = (string)$str;
  $clean = @iconv('UTF-8', 'UTF-8//IGNORE', $str);
  return ($clean === false) ? '' : $clean;
}

$toolsDataArr = array(
  1 => array_map(function($t){
    return array(
      'id'    => (int)$t['machid'],
      'name'  => js_safe_utf8($t['name'] ?? ''),
      'model' => js_safe_utf8($t['model'] ?? '')
    );
  }, getTools(1)),
  2 => array_map(function($t){
    return array(
      'id'    => (int)$t['machid'],
      'name'  => js_safe_utf8($t['name'] ?? ''),
      'model' => js_safe_utf8($t['model'] ?? '')
    );
  }, getTools(2)),
  3 => array_map(function($t){
    return array(
      'id'    => (int)$t['machid'],
      'name'  => js_safe_utf8($t['name'] ?? ''),
      'model' => js_safe_utf8($t['model'] ?? '')
    );
  }, getTools(3)),
  4 => array_map(function($t){
    return array(
      'id'    => (int)$t['machid'],
      'name'  => js_safe_utf8($t['name'] ?? ''),
      'model' => js_safe_utf8($t['model'] ?? '')
    );
  }, getTools(4)), // ✅ process uses 4
);

$toolsDataJson = json_encode($toolsDataArr, JSON_UNESCAPED_UNICODE);
if ($toolsDataJson === false) $toolsDataJson = '{}';



$member_id = (int)($_SESSION['memberid'] ?? 0);
$type      = isset($_GET['type']) ? (int)$_GET['type'] : 0;

$transfer_complaint_id = isset($_GET['complaint_id']) ? (int)$_GET['complaint_id'] : 0;
$is_existing_complaint = $transfer_complaint_id > 0;


$complaintInfo = null;
$originalComplaintId = null;
$complaintHistory = [];

/**
 * 🔹 NEW: Description holder
 * Normal complaint → empty
 * Transfer complaint → auto-filled
 */
$complaint_description = '';
$complaint_process_develop = '';
$complaint_anti_cont_develop = '';


/**
 * STEP 1: Fetch selected complaint (ONLY when transfer)
 */
if ($is_existing_complaint) {

    $res = mysqli_query(
        $db_equip,
        "SELECT *
         FROM equipment_complaint
         WHERE complaint_id = $transfer_complaint_id
         LIMIT 1"
    );

    if ($res && mysqli_num_rows($res) > 0) {
      $complaintInfo = mysqli_fetch_assoc($res);

      // ✅ Auto-fill fields for transfer
      $complaint_description      = $complaintInfo['complaint_description'] ?? '';
      $complaint_process_develop = $complaintInfo['process_develop'] ?? '';
      $complaint_anti_cont_develop = $complaintInfo['anti_contamination_develop'] ?? '';
  }

}

/**
 * STEP 2: Find original complaint ID
 */
if ($complaintInfo) {
    if (!empty($complaintInfo['original_id'])) {
        $originalComplaintId = (int)$complaintInfo['original_id'];
    } else {
        $originalComplaintId = (int)$complaintInfo['complaint_id'];
    }
}


/**
 * STEP 3: Fetch FULL complaint history
 * (original + all transfers)
 */
if ($originalComplaintId) {

    $historyQuery = "
        SELECT *
        FROM equipment_complaint
        WHERE complaint_id = $originalComplaintId
           OR original_id = $originalComplaintId
        ORDER BY complaint_id DESC
    ";

    $res = mysqli_query($db_equip, $historyQuery);

    while ($row = mysqli_fetch_assoc($res)) {
        $complaintHistory[] = $row;
    }
}

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$success = false;
$isEquipmentHead = in_array($_SESSION['memberid'], getTeamHead(1));
$isFacilityHead  = in_array($_SESSION['memberid'], getTeamHead(2));
$isSafetyHead    = in_array($_SESSION['memberid'], getTeamHead(3));
$isProcessHead   = in_array($_SESSION['memberid'], getTeamHead(4));
$isHRHead   = in_array($_SESSION['memberid'], getTeamHead(5));
$isITHead   = in_array($_SESSION['memberid'], getTeamHead(6));
$isPurchaseHead   = in_array($_SESSION['memberid'], getTeamHead(7));
$isTrainingHead  = in_array($_SESSION['memberid'], getTeamHead(8));
$isInventoryHead = in_array($_SESSION['memberid'], getTeamHead(9));






$isAnyHead = ($isEquipmentHead || $isFacilityHead || $isSafetyHead || $isProcessHead || $isHRHead || $isITHead || $isPurchaseHead || $isTrainingHead ||
$isInventoryHead);

if ($_SERVER["REQUEST_METHOD"] === "POST") {
  header("Content-Type: application/json");
  $success = false;

try {
    // Read raw JSON input
      $input = json_decode(file_get_contents("php://input"), true);
      if (!$input) throw new Exception("Invalid JSON");

      $type = mysqli_real_escape_string($db_equip, $input['type']);

      // Get team head list
$team_head_arr = getTeamHead($type);

if (!empty($team_head_arr[0])) {
    $team_head = $team_head_arr[0];
} else {
     echo json_encode(["status" => "error", "message" => "Form not submitted as there is no head alloted please contact Deepti maam or IT team"]);
    exit;
}
     
      // Determine ALLOCATED TO
      // if (!empty($input['allocated_to']) || !empty($input['timer'])) {

      //     // If TIMER is selected...
      //     if (!empty($input['allocated_to'])) {
      //         // user manually selected the member
      //         $allocated_to = (int)$input['allocated_to'];  
      //     } else {
      //         // no selection -> use team head
      //         $allocated_to = $team_head;
      //     }

      // } else {
      //     // No timer → normal complaint → team head inserted
      //     $allocated_to = $team_head;
      // }
      // Determine ALLOCATED TO
      if (!empty($input['allocated_to'])) {
          // User manually selected someone
          $allocated_to = (int)$input['allocated_to'];
      } else {
          // No manual selection → always assign Team Head
          $allocated_to = $team_head;
      }

    // echo"<pre>";
    // print_r($team_head);
    // echo"</pre>";

    $tools_name = mysqli_real_escape_string($db_equip, $input['tools_name']);

    $description_decoded = base64_decode($input['description']);
    $description = mysqli_real_escape_string($db_equip, $description_decoded);

    $memberid = mysqli_real_escape_string($db_equip, $_SESSION['memberid']);

    // Optional fields
    $process_develop = '';
    if (!empty($input['process_develop'])) {
        $process_develop = base64_decode($input['process_develop']);
        $process_develop = htmlspecialchars($process_develop, ENT_QUOTES, 'UTF-8');
        $process_develop = mysqli_real_escape_string($db_equip, $process_develop);
    }

    $anti_cont_develop = '';
    if (!empty($input['anti_contamination_develop'])) {
        $anti_cont_develop = base64_decode($input['anti_contamination_develop']);
        $anti_cont_develop = htmlspecialchars($anti_cont_develop, ENT_QUOTES, 'UTF-8');
        $anti_cont_develop = mysqli_real_escape_string($db_equip, $anti_cont_develop);
    }
    $scheduler = 0;
    if(isset($input['timer']))
    {
      $scheduler = 1;
    }
    // Insert complaint
   

   if(isset($transfer_complaint_id) && $transfer_complaint_id > 0) {
        insert_complaint($memberid, $tools_name, $process_develop, $anti_cont_develop, $description, $type, $allocated_to, $transfer_complaint_id, $originalComplaintId,$scheduler);
    }
    else {
         insert_complaint($memberid, $tools_name, $process_develop, $anti_cont_develop, $description, $type, $allocated_to, null, null,$scheduler);
    }

    // Get complaint_id
    $data = complaint($memberid, $type, '');
    $complaint_id = $data[0]['complaint_id'];

    

    // Schedule complaint (scheduler table + update equipment_complaint.scheduler)
    if (isset($input['timer']) && $input['timer'] !== "" && $input['timer'] !== null) {

        schedule_complaint($complaint_id, (int)$input['timer']);
    }


    // File upload
    if (!empty($input['file']['content'])) {
        $fileInfo = $input['file'];
        $originalName = $fileInfo['name'] ?? 'file';
        $ext = pathinfo($originalName, PATHINFO_EXTENSION);

        if (strtolower($ext) === 'exe') throw new Exception("Cannot upload .exe files.");

        $ext = preg_replace('/[^a-zA-Z0-9]/', '', $ext);
        $target = "uploads/{$complaint_id}." . $ext;

        $binary = base64_decode($fileInfo['content']);
        if ($binary === false) throw new Exception("Invalid file encoding");

        if (!file_put_contents($target, $binary)) throw new Exception("Failed to save file");

        upload_file_complaint($target, $complaint_id);
    }

    // Email notifications (unchanged)
switch ($type) {
    case 1:
        $team = "equipment";
        $toolName = ($tools_name == 0) ? 'Miscellaneous' : getToolName($tools_name);
        break;
    case 2:
        $team = "facility";
        $toolName = ($tools_name == 0) ? 'Miscellaneous' : getToolName_facility($tools_name);
        break;
    case 3:
        $team = "safety";
        $toolName = ($tools_name == 0) ? 'Miscellaneous' : getToolName_safety($tools_name);
        break;
    case 4:
        $team = "process";
        $toolName = ($tools_name == 0) ? 'Miscellaneous' : getToolName($tools_name);
        break;
    case 5:
        $team = "hr";
        $toolName = "";
        break;
    case 6:
        $team = "it";
        $toolName = "";
        break;
    case 7:
        $team = "purchase";
        $toolName = "";
        break;
    case 8:
    $team = "training";
    $toolName = "";
    break;

    case 9:
    $team = "inventory";
    $toolName = "";
    break;

    default:
        $team = "";
        // or throw an exception if invalid:
        // throw new Exception("Invalid complaint type");
        break;
}
// echo $type;
// echo $team;
// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);
//$complaint_id = mysqli_insert_id($db_equip);
    $success = true;
    $from = get_email_user($memberid);
    $cc = "deepti.rukade@gmail.com";
    //$cc = "rohansghumare@gmail.com";
    $members = getTeamMembers($team);
    
    // echo "<pre>".print_r($members)."</pre>";
    if(empty($members))
      {
        echo json_encode(["status" => "error", "message" =>"Please contact IT team"]);
        exit;
      }
  if(!empty($input['timer'])) 
    {
      $member_email[] = get_email_user($allocated_to);

    }
    else
    {
        foreach ($members as $member) {
      $member_email[] = get_email_user($member);
    }
    }
    $subject = "New Task/Complaint - $team Submitted";
    //$member_email = ['30004916@iitb.ac.in','parabnitin51@gmail.com','30005869@iitb.ac.in','30005964@iitb.ac.in','pateltausif78@gmail.com','p15430@iitb.ac.in'];
$desc = $description;

// if description contains literal "\n"
$desc = str_replace(["\\r\\n", "\\n", "\\r"], "\n", $desc);

// escape HTML + convert newlines to <br>
date_default_timezone_set('Asia/Kolkata');
$desc = nl2br(htmlspecialchars_decode($desc));
$body = '<table width="100%" cellpadding="0" cellspacing="0" border="0" role="presentation">
  <tr>
    <td style="font-family: Arial, sans-serif; font-size:14px; line-height:20px; color:#111; padding:16px;">

      <!-- Header row (NO div) -->
      <table width="100%" cellpadding="0" cellspacing="0" border="0" role="presentation">
        <tr>
          <td style="font-size:16px; font-weight:bold; padding-bottom:12px;">
            A Complaint has been received for '.htmlspecialchars($team, ENT_QUOTES, "UTF-8").'
            - (Complaint ID - '.(int)$complaint_id.')
          </td>
        </tr>
      </table>
      <!-- Details table -->
      <table width="100%" cellpadding="0" cellspacing="0" border="0" role="presentation">
        <tr>
          <td width="120" valign="top" style="padding:4px 8px 4px 0; font-weight:bold;">From:</td>
          <td valign="top" style="padding:4px 0;">'.htmlspecialchars(getName($memberid), ENT_QUOTES, "UTF-8").'</td>
        </tr>
        <tr>
          <td width="120" valign="top" style="padding:4px 8px 4px 0; font-weight:bold;">For Tool:</td>
          <td valign="top" style="padding:4px 0;">'.htmlspecialchars($toolName, ENT_QUOTES, "UTF-8").'</td>
        </tr>
        <tr>
          <td width="120" valign="top" style="padding:4px 8px 4px 0; font-weight:bold;">Description:</td>
          <td valign="top" style="padding:4px 0;">'.$desc.'</td>
        </tr>
        <tr>
          <td width="120" valign="top" style="padding:4px 8px 4px 0; font-weight:bold;">Submitted at:</td>
          <td valign="top" style="padding:4px 0;">'.date("F j, Y, g:i a").'</td>
        </tr>
         <tr>
          <td colspan="2" style="padding-top:12px; color:red; font-weight:bold;">This is a testing mail</td>
        </tr>
      </table>
    </td>
  </tr>
</table>';

        $member_email = implode(",", $member_email);
    // sendEmailCC($member_email,$cc,$from,$subject, $body);
    echo json_encode(["status" => "success", "message" => "Complaint submitted successfully"]);

} catch (Exception $e) {
    error_log("Server request failed: " . $e->getMessage());
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
exit;
}


include("../includes/header.php");
?>
<?php if ($is_existing_complaint) { ?>
<style>
  #timer_block { display: none !important; }
</style>
<?php } ?>

<style>
/* Scroll */
.history-scroll {
  max-height: 600px;
  overflow-y: auto;
  padding-right: 6px;
}

/* Smooth scrollbar */
.history-scroll::-webkit-scrollbar {
  width: 6px;
}
.history-scroll::-webkit-scrollbar-thumb {
  background: #cfd4da;
  border-radius: 4px;
}
.history-scroll::-webkit-scrollbar-track {
  background: #f1f3f5;
}

/* History card */
.history-item {
  background: #f9fafb;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  padding: 12px 14px;
  margin-bottom: 12px;
  transition: box-shadow 0.2s ease;
}

.history-item:hover {
  box-shadow: 0 3px 8px rgba(0,0,0,0.08);
}

/* Assigned to */
.assigned-to {
  font-size: 13px;
  color: #0d6efd;
  font-weight: 600;
}

/* Description */
.history-desc {
  font-size: 14px;
  color: #374151;
  line-height: 1.5;
}

/* Status badges */
.status-badge {
  padding: 3px 10px;
  border-radius: 20px;
  font-size: 12px;
  font-weight: 600;
  text-transform: capitalize;
}

.status-pending {
  background: #fff3cd;
  color: #856404;
}

.status-inprocess {
  background: #d1fae5;
  color: #065f46;
}

.status-onhold {
  background: #dbeafe;
  color: #1e40af;
}

.status-closed {
  background: #e5e7eb;
  color: #111827;
}

/* Attachment */
.attachment-link {
  font-size: 13px;
  color: #0d6efd;
  text-decoration: none;
}

.attachment-link i {
  margin-right: 4px;
}

/* Common badge style – SAME AS PENDING */
.status-badge {
  display: inline-block;
  padding: 4px 12px;
  border-radius: 20px;
  font-size: 12px;
  font-weight: 600;
  line-height: 1;
  white-space: nowrap;
}

/* Pending – Yellow (already correct) */
.status-pending {
  background-color: #fff3cd;
  color: #856404;
}

/* In Process – Green */
.status-inprocess {
  background-color: #d1e7dd;
  color: #0f5132;
}

/* On Hold – Orange */
.status-onhold {
  background-color: #ffe5b4;
  color: #9a3412;
}

/* Closed – Dark Green */
.status-closed {
  background-color: #198754;
  color: #ffffff;
}



.status-badge {
  font-size: 0.75rem;
  padding: 4px 10px;
  border-radius: 20px;
}

.assigned-to {
  cursor: 
  default;
}

.history-desc {
  white-space: pre-wrap !important;
  word-break: break-word;
  overflow: visible !important;
  max-height: none !important;
  height: auto !important;
}

.history-desc {
  font-size: 13px;        /* 👈 text छोटा */
  line-height: 1.4;       /* 👈 readable spacing */
  white-space: pre-wrap;
  word-break: break-word;
}

.complaint-history .fw-bold {
  font-size: 14px;
}

.complaint-history small,
.complaint-history .assigned-to {
  font-size: 12px;
}


</style>

<?php
if ($is_existing_complaint) {
    // 🔵 Transfer complaint → unchanged
    $menuColClass    = 'col-md-3';
    $contentColClass = 'col-md-9';

    $formColClass    = 'col-md-7';
    $historyColClass = 'col-md-5';
} else {
    // 🟢 New complaint → move MENU right
    $menuColClass    = 'col-md-3 offset-md-1';
    $contentColClass = 'col-md-8';

    $formColClass    = 'col-md-10';
    $historyColClass = '';
}
?>

<main class="container-fluid py-4">

  <div class="row">
          <div class="<?= $menuColClass ?>">
      <?php include("../includes/menu.php"); ?>
    </div>
 <div class="<?= $contentColClass ?>">
    <div class="row">
        <!-- LEFT : FORM -->
           <div class="<?= $formColClass ?>">
      <div class="card shadow-sm">
        <div class="card-header bg-primary text-white">
          Complaint Form
        </div>
        <div class="card-body">
          <div class="text-center mb-3">
            <strong class="text-danger">All fields marked with * are mandatory</strong>
            <div id="msg" class="text-danger small mt-2"></div>
          </div>
            
                      <form id="complaintForm" action="" method="POST" onsubmit="return verification();">
                        <!-- Complaint Type -->
                        <div class="mb-3">
                          <label class="form-label">Complaint Type <span class="text-danger">*</span></label><br>
                          <?php
                          $types = [1 => "Equipment",2 => "Facility",3 => "Safety",4 => "Process",5 => "HR",6 => "IT",7 => "Purchase",8 => "Training",
                                    9 => "Inventory"];

                          foreach ($types as $val => $label): ?>
                            <div class="form-check form-check-inline">
                              <input class="form-check-input" type="radio" name="type" id="type_<?= $val ?>" value="<?= $val ?>" onclick="loading_tools_<?= $val ?>();" required />
                              <label class="form-check-label" for="type_<?= $val ?>"><?= $label ?></label>
                            </div>
                          <?php endforeach; ?>
                        </div>


                      <?php if ($isAnyHead) { ?>
                    <div class="row mb-3" id="timer_block" style="display:none;">
                        <div class="col-md-6">
                            <label class="form-label">Scheduler</label>
                            <select name="timer" id="timer_select" class="form-select">
                                <option value="">--- Select ---</option>
                                <option value="0">One Time</option>
                                <option value="1">Daily</option>
                                <option value="7">Weekly</option>
                                <option value="14">Bi-Weekly</option>
                                <option value="30">Monthly</option>
                                <option value="custom">No. of Days</option>
                            </select>

                            <small id="timer_note" class="text-muted"></small>
                            <div id="timer_input_container" class="mt-2"></div>
                    </div>
                      </div>
                      <?php } ?>


                        <!-- Tool + Slot ID -->
                        <div class="row mb-3 align-items-end">
                          <div class="col-md-6">
                            <label for="tools_name" class="form-label">Tool <span class="text-danger">*</span></label>
                            <select name="tools_name" id="tools_name" class="form-select" disabled required>
                              <option value="">--- Select ---</option>
                            </select>
                          </div>

                          <div class="col-md-6 d-none" id="category_block">
                            <label class="form-label">Category <span class="text-danger">*</span></label>
                            <select name="category" id="category" class="form-select" disabled>
                              <option value="">--- Select ---</option>
                            </select>
                          </div>

            <?php if ($isAnyHead) { ?>
                                <div class="col-md-6" id="allocate_block" style="display:none;">
                            <label class="form-label">
  Allocated To <span id="allocated_star" class="text-danger" style="display:none;">*</span>
</label>

                            <select name="allocated_to" id="allocated_to" class="form-select" disabled>
                                <option value="">--- Select ---</option>
                            </select>
                        </div>
            <?php } ?>
            <!-- Conditional Fields -->
            <div class="mb-3 d-none" id="process_development_row">
              <label for="process_develop" class="form-label">Process Development</label>
              <input type="text" class="form-control" name="process_develop" id="process_develop" disabled value="<?= htmlspecialchars($complaint_process_develop ?? '', ENT_QUOTES, 'UTF-8') ?>">

            </div>

            <div class="mb-3 d-none" id="anti_contamination_row">
              <label for="anti_contamination_develop" class="form-label">Anti-Contamination Development</label>
             <input type="text" class="form-control" name="anti_contamination_develop" id="anti_contamination_develop" disabled
              value="<?= htmlspecialchars($complaint_anti_cont_develop ?? '', ENT_QUOTES, 'UTF-8') ?>">

            </div>

            <!-- Description -->
                
            <div style="position: relative; margin-bottom: 36px;">
            <label for="description" class="form-label">Description of Complaint <span class="text-danger">*</span></label>
	 <textarea class="form-control"
              name="description"
              id="description"
              rows="4"
              onkeyup="countDX()"
              style="font-size:15px; padding:10px 12px; border:1px solid #b5c7e7; border-radius:6px; line-height:1.5; color: #333;"
              required disabled><?php 
                $desc = $complaint_description ?? '';
                $desc = str_replace(["\\'", '\\"', "\\\\"], ["'", '"', "\\"], $desc);
                $desc = str_replace(['\n', '\nn'], ["\n", "\n\n"], $desc);
                $desc = stripslashes($desc);
                echo htmlspecialchars(trim($desc), ENT_QUOTES, 'UTF-8'); 
              ?></textarea>


    <!-- Error message bottom-left -->
    <div id="description_error"
         style="color:#d9534f; font-size:14px; position:absolute; bottom:-24px; left:0;">
    </div>

    <!-- Character counter bottom-right -->
    <div id="dx_count"
         style="color:#555; font-size:14px; position:absolute; bottom:-24px; right:0;">
    </div>
  </div>
              <!-- File Upload -->
              <div class="mb-3">
                <label for="file" class="form-label">Upload Supporting File (optional)</label>
                <input type="file" class="form-control" name="file" id="file" disabled />
                <div class="form-text">Supported formats: jpg, png, pdf, doc, docx, etc. (No .exe)</div>
              </div>

              <!-- Submit -->
              <div class="d-grid">
		<div class="d-flex justify-content-center gap-2 mt-4">
                  <button type="submit" class="btn btn-success btn-sm px-3" id="submit" disabled>
                      Submit
                  </button>

                  <button class="btn btn-secondary btn-sm px-3" onclick="location.reload();">
                      Refresh
                  </button>

                  <?php if (!empty($returnUrl)): ?>
                      <a href="<?= htmlspecialchars($returnUrl) ?>" class="btn btn-dark btn-sm px-3">
                          ← Back
                      </a>
                  <?php endif; ?>
              </div>

              </div>
            </form>
          </div>
        </div>
      </div>
    </div>

  <?php if ($is_existing_complaint): ?>
      <div class="<?= $historyColClass ?>">
      <div class="card shadow-sm complaint-history">

        <div class="card-header bg-dark text-white">
          Complaint History
        </div>  

        <div class="card-body history-scroll">

          <?php if (!empty($complaintHistory)) { ?>
            <?php foreach ($complaintHistory as $row) { ?>

              <?php
                $componentName = getComplaintComponentName($row);

                $assignedTo = !empty($row['allocated_to'])
                  ? getName($row['allocated_to'])
                  : 'Unassigned';

                $statusLabel = strtolower(getStatusLabel($row['status']));
                $statusClass = str_replace(' ', '', $statusLabel);
              ?>

              <div class="history-item p-3 mb-3 border rounded bg-white">

                <!-- Status + Date + Assigned -->
                <div class="d-flex justify-content-between align-items-start mb-2">
                  <div>
                    <span class="status-badge status-<?= htmlspecialchars($statusClass) ?> me-2">
                      <?= ucfirst($statusLabel) ?>
                    </span>
                    <small class="text-muted">
                      <?= display_timestamp($row['time_of_complaint']) ?>
                    </small>
                  </div>

                  <span class="assigned-to text-primary fw-semibold">
                    <?= htmlspecialchars($assignedTo) ?>
                  </span>
                </div>

                <!-- Component -->
                <div class="fw-bold text-dark mb-1">
                  <?= htmlspecialchars($componentName) ?>
                </div>

                <!-- Description -->
<?php
                $desc = $row['complaint_description'] ?? '';

                // 1. Force remove literal backslash-n and backslash-r first
                $desc = str_replace(['\n', '\r'], "\n", $desc);

                // 2. Remove escaped backslashes specifically (the ones causing the \ before ')
                $desc = str_replace(["\\'", '\\"', '\\\\'], ["'", '"', '\\'], $desc);

                // 3. Brute-force the "nn" sequence. 
                // This handles "word\nnword", "word nn word", or "wordnnword"
                $desc = preg_replace('/(\s|\\\\)?nn(\s)?/i', "\n\n", $desc);

                // 4. Run stripslashes as a final safety catch for any remaining system escapes
                $desc = stripslashes($desc);

                // 5. Decode existing HTML entities so we don't double-encode them later
                $desc = htmlspecialchars_decode($desc, ENT_QUOTES);

                // 6. Clean and Output
                // We trim to remove leading/trailing junk and use nl2br for the browser
                echo nl2br(htmlspecialchars(trim($desc), ENT_QUOTES, 'UTF-8'));
                ?>


                <!-- FOOTER -->
                <div class="d-flex justify-content-between align-items-center">

                  <!-- LEFT : ATTACHMENT -->
                  <div>
                    <?php if (!empty($row['upload_file'])) { ?>
                      <a href="#"
                        class="text-secondary fs-5"
                        title="View Attachment"
                        onclick="openAttachmentModal('<?= htmlspecialchars($row['upload_file'], ENT_QUOTES) ?>'); return false;">
                        <i class="fa fa-eye"></i>
                      </a>
                    <?php } ?>
                  </div>

                  <!-- RIGHT : TRACK (same as Daily Tasks table) -->
                  <div>
                    <?php if (count(trouble_track($row['complaint_id'], '')) > 0): ?>
                      <a href="#"
                        onclick="return viewTrack(<?= (int)$row['complaint_id'] ?>, <?= (int)$type ?>);">
                        Track
                      </a>
                    <?php else: ?>
                      <span class="text-muted">No Track</span>
                    <?php endif; ?>
                  </div>

                </div>

              </div>

            <?php } ?>
          <?php } else { ?>
            <div class="text-muted text-center py-4">
              No history available
            </div>
          <?php } ?>

        </div>
      </div>
      <?php endif; ?>

    </div>
</main>
<div class="modal fade" id="attachmentModal" tabindex="-1">
  <div class="modal-dialog modal-xl modal-dialog-centered">
    <div class="modal-content">

      <div class="modal-header d-flex justify-content-between align-items-center">
        <h5 class="modal-title">Attachment Preview</h5>

        <div class="d-flex align-items-center gap-2">
          <!-- Download Button -->
          <a id="downloadAttachment"
             href="#"
             class="btn btn-sm btn-outline-primary"
             download>
            <i class="fa fa-download me-1"></i> Download
          </a>

          <!-- Close -->
          <button type="button"
                  class="btn-close"
                  data-bs-dismiss="modal">
          </button>
        </div>
      </div>

      <div class="modal-body p-0" style="height:80vh;">
        <iframe id="attachmentFrame"
                src=""
                style="width:100%;height:100%;border:0;">
        </iframe>
      </div>

    </div>
  </div>
</div>


<script>
document.addEventListener("DOMContentLoaded", function () {

  // =========================
  // ELEMENTS
  // =========================
  const form = document.getElementById("complaintForm");

  const toolsSelect   = document.getElementById("tools_name");
  const categoryBlock = document.getElementById("category_block");
  const categorySelect= document.getElementById("category");

  const desc          = document.getElementById("description");
  const fileInput     = document.getElementById("file");
  const submitBtn     = document.getElementById("submit");
  const msgBox        = document.getElementById("msg");

  const processRow    = document.getElementById("process_development_row");
  const antiRow       = document.getElementById("anti_contamination_row");
  const processInput  = document.getElementById("process_develop");
  const antiInput     = document.getElementById("anti_contamination_develop");

  const timerBlock    = document.getElementById("timer_block"); // may not exist for non-head
  const allocateBlock = document.getElementById("allocate_block"); // may not exist for non-head
  const timerSelect   = document.getElementById("timer_select"); // may not exist
  const timerNote     = document.getElementById("timer_note");   // may not exist
  const timerContainer= document.getElementById("timer_input_container"); // may not exist
  const allocatedToEl = document.getElementById("allocated_to"); // may not exist
  
  const allocatedStar = document.getElementById("allocated_star");

function setAllocatedMandatory(isMandatory) {
  if (!allocatedToEl) return;

  // required + star
  allocatedToEl.required = !!isMandatory;
  if (allocatedStar) allocatedStar.style.display = isMandatory ? "inline" : "none";

  // if mandatory, show the block (only if user can see it)
  if (allocateBlock && isMandatory) allocateBlock.style.display = "block";

  // if turning off mandatory, clear selection (optional)
  if (!isMandatory) {
    // allocatedToEl.value = "";
  }
}

// Trigger on scheduler change
if (timerSelect) {
  timerSelect.addEventListener("change", function () {
    const hasScheduler = timerSelect.value !== "" && timerSelect.value !== null;

    // Make allocated mandatory only when scheduler is chosen
    setAllocatedMandatory(hasScheduler);
  });
}

  
  // =========================
  // ALLOCATED MEMBERS DATA + LOADER
  // =========================
  const membersData = <?= json_encode([
  1 => array_map(function($id){
      return ['id'=>$id, 'name'=>getName($id)];
  }, getTeamMembers("equipment")),
  2 => array_map(function($id){
      return ['id'=>$id, 'name'=>getName($id)];
  }, getTeamMembers("facility")),
  3 => array_map(function($id){
      return ['id'=>$id, 'name'=>getName($id)];
  }, getTeamMembers("safety")),
  4 => array_map(function($id){
      return ['id'=>$id, 'name'=>getName($id)];
  }, getTeamMembers("process")),
  5 => array_map(function($id){
      return ['id'=>$id, 'name'=>getName($id)];
  }, getTeamMembers("hr")),
  6 => array_map(function($id){
      return ['id'=>$id, 'name'=>getName($id)];
  }, getTeamMembers("it")),
  7 => array_map(function($id){
      return ['id'=>$id, 'name'=>getName($id)];
  }, getTeamMembers("purchase")),
  8 => array_map(function($id){
    return ['id'=>$id, 'name'=>getName($id)];
  }, getTeamMembers("training")),

  9 => array_map(function($id){
      return ['id'=>$id, 'name'=>getName($id)];
  }, getTeamMembers("inventory")),

]) ?>;

function canSeeAllocate(type) {
  type = parseInt(type, 10);

  if (type === 1) return isEquipmentHead;
  if (type === 2) return isFacilityHead;
  if (type === 3) return isSafetyHead;
  if (type === 4) return isProcessHead;
  if (type === 5) return isHRHead;
  if (type === 6) return isITHead;
  if (type === 7) return isPurchaseHead;
  if (type === 8) return isTrainingHead;
  if (type === 9) return isInventoryHead;

  return false;
}


function loadAllocatedMembers(type) {
  if (!allocatedToEl) return;

  // ✅ Always fill list (optional) but show only if allowed
  allocatedToEl.innerHTML = `<option value="">--- Select ---</option>`;

  if (membersData[type]) {
    membersData[type].forEach(m => {
      allocatedToEl.innerHTML += `<option value="${m.id}">${m.name}</option>`;
    });
  }

  // ✅ Permission check
  if (canSeeAllocate(type)) {
    if (allocateBlock) allocateBlock.style.display = "block";
    allocatedToEl.disabled = false;
  } else {
    if (allocateBlock) allocateBlock.style.display = "none";
    allocatedToEl.disabled = true;
    allocatedToEl.value = "";
  }
}
  // expose if you want to call from HTML too
  window.loadAllocatedMembers = loadAllocatedMembers;

  // =========================
  // HELPERS
  // =========================
function enableFormFields() {
  const ids = ["tools_name", "description", "file", "submit", "category","anti_contamination_develop","process_develop"];
  ids.forEach(id => {
    const el = document.getElementById(id);
    if (el) el.disabled = false;
  });
}

  function showToolHideCategory() {
    // show tools
    if (toolsSelect) {
      toolsSelect.disabled = false;
      toolsSelect.required = true;
      toolsSelect.closest(".col-md-6").style.display = ""; // show
    }

    // hide category
    if (categoryBlock) categoryBlock.classList.add("d-none");
    if (categorySelect) {
      categorySelect.disabled = true;
      categorySelect.required = false;
      categorySelect.value = "";
    }
  }

  function hideToolShowCategory() {
    // hide tools
    if (toolsSelect) {
      toolsSelect.value = "";
      toolsSelect.disabled = true;
      toolsSelect.required = false;
      toolsSelect.closest(".col-md-6").style.display = "none";
    }

    // show category
    if (categoryBlock) categoryBlock.classList.remove("d-none");
    if (categorySelect) {
      categorySelect.disabled = false;
      categorySelect.required = true;
    }
  }

  function setProcessFieldsVisibility(type) {
    if (!processRow || !antiRow || !processInput || !antiInput) return;

    if (parseInt(type) === 4) {
      processRow.classList.remove("d-none");
      antiRow.classList.remove("d-none");
      processInput.disabled = false;
      antiInput.disabled = false;
    } else {
      processRow.classList.add("d-none");
      antiRow.classList.add("d-none");
      processInput.disabled = true;
      antiInput.disabled = true;
      processInput.value = "";
      antiInput.value = "";
    }
  }

    // 🔁 Auto-show Process fields on transfer
<?php if ($is_existing_complaint && $type == 4): ?>
  setProcessFieldsVisibility(4);

  // ✅ SIMPLE FIX: lock them again (like description)
  processInput.disabled = true;
  antiInput.disabled = true;
<?php endif; ?>



  // safe b64 for text
  function b64encode(str) {
    return btoa(unescape(encodeURIComponent(str)));
  }

  function fileToBase64(file) {
    return new Promise((resolve, reject) => {
      const reader = new FileReader();
      reader.onload = () => {
        const result = reader.result || "";
        const parts = result.split(",");
        resolve(parts.length > 1 ? parts[1] : "");
      };
      reader.onerror = reject;
      reader.readAsDataURL(file);
    });
  }

  // =========================
  // TIMER INIT (FIXES YOUR ERROR)
  // =========================
  if (timerSelect) {
    timerSelect.disabled = true;
    timerSelect.value = "";
  }
  if (timerContainer) timerContainer.innerHTML = "";
  if (timerNote) timerNote.textContent = "";

  // enable timer once any type selected
  document.querySelectorAll('input[name="type"]').forEach(radio => {
    radio.addEventListener("change", function () {
      if (timerSelect) timerSelect.disabled = false;
    });
  });

  // timer change behavior + file disabling logic
  if (timerSelect) {
    timerSelect.addEventListener("change", function () {
      const value = timerSelect.value;

      if (timerContainer) timerContainer.innerHTML = "";
      if (timerNote) timerNote.textContent = "";

      // Disable file ONLY for repeating schedules (>0 days)
      if (fileInput) {
        if (value !== "" && value !== "0") fileInput.disabled = true;
        else fileInput.disabled = false;
      }

      if (value === "0") {
        if (timerNote) timerNote.textContent = "Runs only once";
        return;
      }

      if (value === "custom") {
        if (timerNote) timerNote.textContent = "Enter number of days";
        if (timerContainer) {
          timerContainer.innerHTML = `
            <input type="number" id="custom_days"
              class="form-control form-control-sm"
              min="1" max="365"
              placeholder="e.g. 5"
              style="width:120px;">
          `;
        }
        return;
      }

      const days = parseInt(value, 10);
      if (!isNaN(days) && timerNote) timerNote.textContent = `Runs every ${days} day(s)`;
    });
  }

  // =========================
  // TYPE CHANGE: SHOW TIMER/ALLOCATE ONLY IF ALLOWED (SAME AS YOUR LOGIC)
  // =========================
  const isEquipmentHead = window.isEquipmentHead ?? <?= $isEquipmentHead ? 'true' : 'false' ?>;
  const isFacilityHead  = window.isFacilityHead  ?? <?= $isFacilityHead  ? 'true' : 'false' ?>;
  const isSafetyHead    = window.isSafetyHead    ?? <?= $isSafetyHead    ? 'true' : 'false' ?>;
  const isProcessHead   = window.isProcessHead   ?? <?= $isProcessHead   ? 'true' : 'false' ?>;
  const isHRHead   = window.isHRHead   ?? <?= $isHRHead   ? 'true' : 'false' ?>;
  const isITHead   = window.isITHead   ?? <?= $isITHead   ? 'true' : 'false' ?>;
  const isPurchaseHead   = window.isPurchaseHead   ?? <?= $isPurchaseHead   ? 'true' : 'false' ?>;
  const isTrainingHead   = window.isTrainingHead   ?? <?= $isTrainingHead   ? 'true' : 'false' ?>;
  const isInventoryHead   = window.isInventoryHead   ?? <?= $isInventoryHead   ? 'true' : 'false' ?>;

  document.querySelectorAll('input[name="type"]').forEach(radio => {
    radio.addEventListener("change", function () {
      const type = parseInt(this.value, 10);

      // process fields
      setProcessFieldsVisibility(type);
if (type === 1 || type === 2 || type === 3 || type === 4) {
  showToolHideCategory();   // ✅ ensures tool dropdown is visible again
}
      // timer/allocate reset
      if (timerBlock) timerBlock.style.display = "none";
      if (allocateBlock) allocateBlock.style.display = "none";
      if (timerSelect) timerSelect.value = "";
      if (timerContainer) timerContainer.innerHTML = "";
      if (timerNote) timerNote.textContent = "";

      // show/hide timer+allocate as per your rules
      if (type === 1 && isEquipmentHead) {
        if (timerBlock) timerBlock.style.display = "block";
        if (allocateBlock) allocateBlock.style.display = "block";
      } else if (type === 2 && isFacilityHead) {
        if (timerBlock) timerBlock.style.display = "block";
        if (allocateBlock) allocateBlock.style.display = "block";
      } else if (type === 3 && isSafetyHead) {
        if (timerBlock) timerBlock.style.display = "block";
        if (allocateBlock) allocateBlock.style.display = "block";
      } else if (type === 4 && isProcessHead) {
        if (timerBlock) timerBlock.style.display = "block";
        if (allocateBlock) allocateBlock.style.display = "block";
      } else if (type === 5 && isHRHead) {
        if (timerBlock) timerBlock.style.display = "block";
        if (allocateBlock) allocateBlock.style.display = "block";
      } else if (type === 6 && isITHead) {
        if (timerBlock) timerBlock.style.display = "block";
        if (allocateBlock) allocateBlock.style.display = "block";
      } else if (type === 7 && isPurchaseHead) {
        if (timerBlock) timerBlock.style.display = "block";
        if (allocateBlock) allocateBlock.style.display = "block";
      } else if (type === 8 && isTrainingHead) {
        if (timerBlock) timerBlock.style.display = "block";
        if (allocateBlock) allocateBlock.style.display = "block";
      } else if (type === 9 && isInventoryHead) {
        if (timerBlock) timerBlock.style.display = "block";
        if (allocateBlock) allocateBlock.style.display = "block";
      } 
      // enable common fields
      enableFormFields();
    });
  });

    // =========================
  // COUNTDX (character counter)
  // =========================
  function countDX() {
    const dx = (desc?.value || "").trim();
    const len = dx.length;

    const counter = document.getElementById("dx_count");
    if (!counter) return;

    if (len === 0) counter.innerHTML = "";
    else counter.innerHTML = "(" + len + "/100)";
  }

  // expose to inline HTML: onkeyup="countDX()"
  window.countDX = countDX;

  // initial update (useful when transfer fills description)
  countDX();

  // also update on typing (even if you remove inline onkeyup later)
  if (desc) desc.addEventListener("input", countDX);


  // =========================
  // CATEGORY LOADER (HR/IT/PURCHASE)
  // =========================
  window.loadCategoryFromTxt = function (file) {
    fetch(`complaint_categories/${file}`)
      .then(res => res.text())
      .then(text => {
        if (!categorySelect) return;

        categorySelect.innerHTML = '<option value="">--- Select ---</option>';

        text.split(/\r?\n/).forEach(line => {
          const val = line.trim();
          if (!val) return;

          const parts = val.split("-");
          if (parts.length < 2) return;

          const label = parts[0].trim();
          const value = parts[1].trim();

          categorySelect.innerHTML += `<option value="${value}">${label}</option>`;
        });

        if (categoryBlock) categoryBlock.classList.remove("d-none");
        categorySelect.disabled = false;
      });
  };

  // global tools data from PHP JSON
const toolsData = <?= $toolsDataJson ?>;

function fillToolsDropdown(type) {
  const toolsSelect = document.getElementById("tools_name");
  if (!toolsSelect) return;

  toolsSelect.innerHTML = `<option value="">--- Select ---</option>`;

  const list = toolsData[type] || [];
  list.forEach(t => {
    const icon = (t.model === "Yes") ? " 🔧" : "";
    const opt = document.createElement("option");
    opt.value = t.id;
    opt.textContent = (t.name || "") + icon;
    toolsSelect.appendChild(opt);
  });

  // Misc
  const misc = document.createElement("option");
  misc.value = "0";
  misc.textContent = "Miscellaneous";
  toolsSelect.appendChild(misc);

  toolsSelect.disabled = false;
}

// ✅ PURE JS loading functions (no php foreach)
window.loading_tools_1 = function () {
  showToolHideCategory();   // ✅ add this
  fillToolsDropdown(1);
  loadAllocatedMembers(1);
  enableFormFields();
};

window.loading_tools_2 = function () {
  showToolHideCategory();   // ✅ add this
  fillToolsDropdown(2);
  loadAllocatedMembers(2);
  enableFormFields();
};

window.loading_tools_3 = function () {
  showToolHideCategory();   // ✅ add this
  fillToolsDropdown(3);
  loadAllocatedMembers(3);
  enableFormFields();
};

window.loading_tools_4 = function () {
  showToolHideCategory();   // ✅ add this
  fillToolsDropdown(4);
  loadAllocatedMembers(4);
  enableFormFields();
};

  // HR
  window.loading_tools_5 = function () {
    hideToolShowCategory();
    enableFormFields();
    loadCategoryFromTxt("hr.txt");
    loadAllocatedMembers(5);
  };

  // IT
  window.loading_tools_6 = function () {
    hideToolShowCategory();
    enableFormFields();
    loadCategoryFromTxt("it.txt");
    loadAllocatedMembers(6);
  };

  // Purchase
  window.loading_tools_7 = function () {
    hideToolShowCategory();
    enableFormFields();
    loadCategoryFromTxt("purchase.txt");
    loadAllocatedMembers(7);
  };

  // Training
  window.loading_tools_8 = function () {
    hideToolShowCategory();
    enableFormFields();
    loadCategoryFromTxt("training.txt");
    loadAllocatedMembers(8);
  };

  // Inventory
  window.loading_tools_9 = function () {
    hideToolShowCategory();
    enableFormFields();
    loadCategoryFromTxt("inventory.txt");
    loadAllocatedMembers(9);
  };


  // =========================
  // VALIDATION (fix tool/category requirement)
  // =========================
  window.verification = function () {
    if (!msgBox) return true;
    msgBox.textContent = "";

    const type = document.querySelector('input[name="type"]:checked')?.value || "";

    // file exe check
    const f = fileInput?.value || "";
    if (f && f.toLowerCase().endsWith(".exe")) {
      msgBox.textContent = "Cannot upload .exe files.";
      fileInput.focus();
      return false;
    }

    // description check
    const dx = (desc?.value || "").trim();
    document.getElementById("description_error").innerHTML = "";
    if (!dx) {
      msgBox.textContent = "Please enter a description.";
      desc.focus();
      return false;
    }
    if (dx.length < 100) {
      document.getElementById("description_error").innerHTML = "Minimum 100 characters required.";
      desc.focus();
      return false;
    }

    // tool vs category check
    if (type === "5" || type === "6" || type === "7" || type === "8" || type === "9") {
      if (!categorySelect || !categorySelect.value) {
        msgBox.textContent = "Please select a category.";
        categorySelect?.focus();
        return false;
      }
    } else {
      if (!toolsSelect || !toolsSelect.value) {
        msgBox.textContent = "Please select a tool.";
        toolsSelect?.focus();
        return false;
      }
    }

    // allocated mandatory if scheduler selected
const schedulerSelected = (timerSelect && !timerSelect.disabled && timerSelect.value !== "");
if (schedulerSelected) {
  if (!allocatedToEl || allocatedToEl.disabled || !allocatedToEl.value) {
    msgBox.textContent = "Please select Allocated To (required for Scheduler).";
    allocatedToEl?.focus();
    return false;
  }
}


    return true;
  };

  // =========================
  // SUBMIT HANDLER (KEEP YOUR SAME FUNCTIONALITY)
  // =========================
  if (form) {
    form.addEventListener("submit", async function (e) {
      e.preventDefault();
      if (!window.verification()) return;

      const type = document.querySelector("input[name=type]:checked")?.value || "";

      const toolsName = toolsSelect?.value || "";
      const category  = categorySelect?.value || "";

      const processDevelop = processInput?.value || "";
      const antiContamination = antiInput?.value || "";
      const description = desc?.value || "";

      const MAX_FILE_SIZE_BYTES = 500 * 1024;

      // timer
      let timer = null;
      const timerSelected = (timerSelect && !timerSelect.disabled && timerSelect.value !== "");

      if (timerSelected) {
        const value = timerSelect.value;
        if (value === "custom") {
          const customInput = document.getElementById("custom_days");
          const days = parseInt(customInput?.value, 10);
          if (!days || days < 1) {
            alert("Please enter valid number of days");
            customInput?.focus();
            return;
          }
          timer = days;
        } else {
          timer = parseInt(value, 10);
        }
      }

      // file
      let fileData = null;
      if (fileInput && fileInput.files.length > 0) {
        const f = fileInput.files[0];

        if (f.name.toLowerCase().endsWith(".exe")) {
          alert("Cannot upload .exe files.");
          return;
        }
        if (f.size > MAX_FILE_SIZE_BYTES) {
          alert(`File is too large. Please upload a file smaller than ${Math.round(MAX_FILE_SIZE_BYTES / 1024)} KB.`);
          return;
        }

        const base64Content = await fileToBase64(f);
        fileData = { name: f.name, type: f.type, size: f.size, content: base64Content };
      }

      // allocated_to
      let allocatedTo = "";
      if (allocatedToEl && !allocatedToEl.disabled && allocatedToEl.value.trim() !== "") {
        allocatedTo = allocatedToEl.value.trim();
      }

      // if timer selected but allocated empty → auto assign head (your same logic)
      if (allocatedTo === "" && timerSelected) {
        if (type === "1") allocatedTo = <?= json_encode(getTeamHead(1)[0] ?? '') ?>;
        if (type === "2") allocatedTo = <?= json_encode(getTeamHead(2)[0] ?? '') ?>;
        if (type === "3") allocatedTo = <?= json_encode(getTeamHead(3)[0] ?? '') ?>;
        if (type === "4") allocatedTo = <?= json_encode(getTeamHead(4)[0] ?? '') ?>;
      }

      const payload = {
        type: type,
        tools_name:(type === "5" || type === "6" || type === "7" || type === "8" || type === "9") ? category : toolsName,

        process_develop: b64encode(processDevelop),
        anti_contamination_develop: b64encode(antiContamination),
        description: b64encode(description),
        file: fileData,
        timer: timer,
        allocated_to: allocatedTo
      };

      try {
        const res = await fetch("", {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify(payload)
        });

        const result = await res.json();
        if (result.status === "success") {
          alert(result.message);
          window.location.reload();
        } else {
          alert("Error: " + result.message);
        }
      } catch (err) {
        console.error("Request failed", err);
        alert("Request failed");
      }
    });
  }

});

  function viewTrack(complaintId, type) {
    const dialog = document.createElement('div');
    dialog.id = 'trackDialog';
    document.body.appendChild(dialog);
    $(dialog).load('view_tracks.php?complaint_id=' + complaintId + '&type=' + type).dialog({
      title: 'Complaint Tracking',
      modal: true,
      width: '95%',
      height: 600
    });
    return false;
  }
</script>



<script>
function openAttachmentModal(filePath) {
  if (!filePath) {
    alert("Attachment not found");
    return;
  }

  const iframe = document.getElementById("attachmentFrame");
  const downloadLink = document.getElementById("downloadAttachment");

  // IMPORTANT: encode path to avoid space/special-char issues
  const safePath = encodeURI(filePath);

  iframe.src = safePath;
  downloadLink.href = safePath;

  const modal = new bootstrap.Modal(document.getElementById("attachmentModal"));
  modal.show();
}
</script>


<?php include("../includes/footer.php"); ?>

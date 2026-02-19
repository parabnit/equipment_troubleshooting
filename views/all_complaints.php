<?php
// Display errors for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Include required files
include("../includes/auth_check.php");
include("../includes/header.php");
include("../config/connect.php");
include("../includes/common.php");
include("../includes/class.phpmailer.php");

// Redirect if not logged in
if (empty($_SESSION['login'])) {
  header("Location: ../logout.php");
  exit;
}



// $status = $_GET['status'] ?? null;
$status = strtolower(trim($_GET['status'] ?? ''));
$importance = $_GET['importance'] ?? 'all';

$tools_name = $_POST['tools_name'] ?? '';
$filter = isset($_POST['filter']);
$critical_button_clicked = isset($_POST['criticaltool']);

$member_id = (int)$_SESSION['memberid'];






$head = 0;

$type = isset($_GET['type']) ? (int)$_GET['type'] : 0;
$tabledata = ($importance === 'critical') ? 1 : 0;

$allowedTypes = [1,2,3,4,5,6,7,8,9,10];
if (!in_array($type, $allowedTypes, true)) {
  // invalid type = deny
  echo "<script>
    Swal.fire({ icon:'error', title:'Access Denied', text:'Invalid type.'})
    .then(()=>location.href='../logout.php');
  </script>";
  exit;
}

$user_role = null;

// 1) Lab Manager / Assistant -> can view all types
if (is_LabManager($member_id) || is_AssistLabManager($member_id) || is_PI($member_id)) {
  $user_role = 'all';
  $details = all_complaint($head, $type, 0, $tools_name,$tabledata); 
  $permission_key = check_permission('LA', $member_id); 
}

elseif(is_FOC_member($member_id)){
  $user_role = 'all';
  $details = all_complaint($head, $type, 0, $tools_name,$tabledata); 
  $permission_key = 0; 
}
// 2) For everyone else: allow only their own type
else {
  switch ($type) {
    case 1:
      if (is_EquipmentHead($member_id)) 
      { 
        $user_role = 'eqp_head'; 
        $head = 1; 
        $details = all_complaint($head, $type, 0, $tools_name, $tabledata); 

$permission_key = check_permission($type, $member_id); 
        
      }
      elseif (is_EquipmentTeam($member_id)) 
      { 
        $user_role = 'eqp_team'; 
        $details = my_allocated_complaint($member_id, $type, 0, $tools_name, $tabledata);

$permission_key = check_permission($type, $member_id); 
        
      }
      break;

    case 2:
      if (is_FacilityHead($member_id)) 
      { 
        $user_role = 'facility_head'; 
        $head = 1;
        $details = all_complaint($head, $type, 0, $tools_name, $tabledata); 

$permission_key = check_permission($type, $member_id); 
         
      }
      elseif (is_FacilityTeam($member_id)) 
        { 
          $user_role = 'facility_team'; 
          $details = my_allocated_complaint($member_id, $type, 0, $tools_name, $tabledata);

$permission_key = check_permission($type, $member_id); 
          
        }
      break;

    case 3:
      if (is_SafetyHead($member_id)) 
      { 
        $user_role = 'safety_head'; 
        $head = 1; 
        $details = all_complaint($head, $type, 0, $tools_name, $tabledata);

$permission_key = check_permission($type, $member_id); 
        
      }
      elseif (is_SafetyTeam($member_id)) 
      { 
        $user_role = 'safety_team';
        $details = my_allocated_complaint($member_id, $type, 0, $tools_name, $tabledata);

$permission_key = check_permission($type, $member_id); 
        
      }
      break;

    case 4:
      if (is_ProcessHead($member_id)) 
      { 
        $user_role = 'process_head'; 
        $head = 1; 
        $details = all_complaint($head, $type, 0, $tools_name, $tabledata); 

$permission_key = check_permission($type, $member_id); 
        
      }
      elseif (is_ProcessTeam($member_id)) 
      { 
        $user_role = 'process_team'; 
        $details = my_allocated_complaint($member_id, $type, 0, $tools_name, $tabledata);

$permission_key = check_permission($type, $member_id); 
        
      }
      break;

    case 5:
      if (is_HRHead($member_id)) 
      { 
        $user_role = 'hr_head'; 
        $head = 1;
        $details = all_complaint($head, $type, 0, $tools_name, $tabledata);

$permission_key = check_permission($type, $member_id); 
        
      }
      elseif (is_HRTeam($member_id)) 
      { 
        $user_role = 'hr_team';
        $details = my_allocated_complaint($member_id, $type, 0, $tools_name, $tabledata);

$permission_key = check_permission($type, $member_id); 
        
      }
      break;

    case 6:
      if (is_ITHead($member_id)) 
        { 
          $user_role = 'it_head'; 
          $head = 1; 
          $details = all_complaint($head, $type, 0, $tools_name, $tabledata);

$permission_key = check_permission($type, $member_id); 
          
        }
      elseif (is_ITTeam($member_id)) 
      { 
        $user_role = 'it_team';
        $details = my_allocated_complaint($member_id, $type, 0, $tools_name, $tabledata);

$permission_key = check_permission($type, $member_id); 
         
      }
      break;

    case 7:
      if (is_PurchaseHead($member_id)) 
      { 
        $user_role = 'purchase_head'; 
        $head = 1; 
        $details = all_complaint($head, $type, 0, $tools_name, $tabledata);

$permission_key = check_permission($type, $member_id); 
        
      }
      elseif (is_PurchaseTeam($member_id)) 
      { 
        $user_role = 'purchase_team';
        $details = my_allocated_complaint($member_id, $type, 0, $tools_name, $tabledata);

$permission_key = check_permission($type, $member_id); 
        
      }
      break;

    case 8:
      if (is_TrainingHead($member_id)) 
      { 
        $user_role = 'training_head'; 
        $head = 1; 
        $details = all_complaint($head, $type, 0, $tools_name, $tabledata);

$permission_key = check_permission($type, $member_id); 
        
      }
      elseif (is_TrainingTeam($member_id)) 
      {  
        $user_role = 'training_team'; 
        $details = my_allocated_complaint($member_id, $type, 0, $tools_name, $tabledata);

$permission_key = check_permission($type, $member_id); 
        
      }
      break;

    case 9:
      if (is_InventoryHead($member_id)) 
        { 
          $user_role = 'inventory_head'; 
          $head = 1; 
          $details = all_complaint($head, $type, 0, $tools_name, $tabledata);

$permission_key = check_permission($type, $member_id); 
          
        }
      elseif (is_InventoryTeam($member_id)) 
        { 
          $user_role = 'inventory_team'; 
          $details = my_allocated_complaint($member_id, $type, 0, $tools_name,  $tabledata);
          
$permission_key = check_permission($type, $member_id); 
          
        }
      break;

      case 10:
      if (is_Admin($member_id)) 
      { 
          $user_role = 'admin_head'; 
          $head = 1; 
          $details = all_complaint($head, $type, 0, $tools_name, $tabledata);

          $permission_key = check_permission($type, $member_id); 
      }
      elseif (is_AdminTeam($member_id)) 
      { 
          $user_role = 'admin_team'; 
          $details = my_allocated_complaint($member_id, $type, 0, $tools_name, $tabledata);

          $permission_key = check_permission($type, $member_id); 
      }
      break;

  }
}

// 3) Deny if still not set
if ($user_role === null) {
  echo "<script>
    Swal.fire({
      icon:'error',
      title:'Access Denied',
      text:'You are not authorized to view this page.'
    }).then(()=>location.href='../logout.php');
  </script>";
  exit;
}


//1 = Equipment Head
//2 = Facility Head
//3 = Safety Head
//4 = Process Head

//5 = Equipment Team
//6 = Facility Team
//7 = Safety Team
//8 = Process Team

//9 = Lab Manager
//10 = Assistant Manager  
//11 = PI








// echo"<pre>";
//  print_r($details); 
// echo"</pre>";

// Handle POST override for importance
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if ($filter) {
    $importance = 'all';
  } elseif ($critical_button_clicked) {
    $importance = 'critical';
  }
}
// Handle process removal
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['process_remove'])) {
  $complaint_id = mysqli_real_escape_string($db_equip, $_POST['complaint_id']);
  process_related_update($complaint_id, 1);
}

// added by shahid, Oct 31 2025 reopen complaint
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reopen'])) {
  $complaint_id = (int)$_POST['complaint_id'];
  reopen_complain($complaint_id);
}





// ----------------------
// ✅ Handle Status Update
// ----------------------


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['status_update'])) {

  $complaint_id = (int)($_POST['complaint_id'] ?? 0);
  $complaint_type = getComplaintTypeById($complaint_id);
  $updated_by   = (int)($_SESSION['memberid'] ?? 0);

  if (!canUserUpdateType($updated_by, $complaint_type)) {
    $_SESSION['flash_message'] = "
      <div class='alert alert-danger'>
        You are not authorized to update status for this department.
      </div>";
    header("Location: " . $_SERVER['REQUEST_URI']);
    exit;
  }


  $status_db    = (int)($_POST['status'] ?? -1);
  if ($status_db === 2) {
    // CLOSED → force current datetime
    $c_date = date('Y-m-d H:i:s');
  } else {
    // Others → only date from user
    $c_date = $_POST['c_date'] ?? '';
  }


  if ($complaint_id <= 0 || $status_db < 0) {
    $message = "<div class='alert alert-danger'>Please fill all required fields.</div>";
  } else {

    // ✅ BLOCK closing original if any child is not closed
    if ($status_db === 2 && isOriginalComplaint($complaint_id)) {

      if (!allChildrenClosed($complaint_id)) {

        $_SESSION['flash_message'] = "<div class='alert alert-warning'>
          Cannot close this complaint because one or more child/transferred complaints are still open.
          Please close all child complaints first.
        </div>";

        header("Location: " . $_SERVER['REQUEST_URI']);
        exit; // IMPORTANT: stop execution so update_complaint never runs
      }
    }

    // ✅ Only reaches here if allowed
    $result = update_complaint($complaint_id, $status_db, $c_date, $updated_by);

    if ($result > 0) {
      $_SESSION['flash_message'] = "<div class='alert alert-success'>Status updated successfully.</div>";

      if ($status_db === 2) {
        send_complaint_closed_email($complaint_id);
      }

      header("Location: " . $_SERVER['REQUEST_URI']);
      exit;
    } else {
      $message = "<div class='alert alert-warning'>No changes were made.</div>";
    }
  }
}


// show flash message if exists
if (!empty($_SESSION['flash_message'])) {
  $message = $_SESSION['flash_message'];
  unset($_SESSION['flash_message']); // clear it after showing
}

?>
<script type="text/javascript" src="../assets/js/datetimepicker.js"></script>
<link rel="stylesheet" href="../assets/css/datetimepicker.css" type="text/css" />

<style>
/* ==========================================================
   ✅ FINAL PROFESSIONAL DATATABLE UI FIX (Aligned + Clean)
   ========================================================== */

/* Wrapper */
.table-responsive {
  padding: 20px;
  border-radius: 18px;
  background: #ffffff;
  box-shadow: 0 10px 30px rgba(0,0,0,0.08);
}

/* DataTable controls */
.dataTables_wrapper .dataTables_filter input,
.dataTables_wrapper .dataTables_length select {
  border-radius: 10px !important;
  border: 1px solid #dce3ec !important;
  padding: 7px 12px !important;
  font-size: 13px;
}

/* Table base */
#allComplaints {
  width: 100%;
  border-collapse: collapse !important;
  margin-top: 15px;
}

/* ==========================================================
   HEADER FIX (No Pills, Clean Bar)
   ========================================================== */
#allComplaints thead th {
  background: linear-gradient(90deg, #2563eb, #4f46e5);
  color: white !important;
  font-size: 13px;
  font-weight: 700;
  padding: 14px 12px !important;
  text-transform: uppercase;
  border: none !important;
  text-align: left;
}

#allComplaints thead th:first-child {
  border-radius: 12px 0 0 12px;
}
#allComplaints thead th:last-child {
  border-radius: 0 12px 12px 0;
}

/* ==========================================================
   ROW FIX (Proper Alignment)
   ========================================================== */
#allComplaints tbody tr {
  background: #fff;
  border-bottom: 1px solid #eef2f7;
  transition: 0.25s ease;
}

#allComplaints tbody tr:hover {
  background: #f9fbff;
}

/* Cells */
#allComplaints td {
  padding: 14px 12px !important;
  font-size: 14px;
  vertical-align: middle;
  border: none !important;
}

/* Description wrap */
#allComplaints td:nth-child(3) {
  max-width: 380px;
  white-space: normal !important;
  word-break: break-word;
}

/* ==========================================================
   PARENT + CHILD DIFFERENCE
   ========================================================== */
#allComplaints tbody tr.parent-row {
  background: #f1f5ff !important;
  border-left: 5px solid #2563eb;
}

#allComplaints tbody tr.child-row {
  background: #ffffff !important;
  border-left: 5px solid #94a3b8;
}

#allComplaints tbody tr.child-row td:first-child {
  padding-left: 30px !important;
  position: relative;
}

#allComplaints tbody tr.child-row td:first-child::before {
  content: "↳";
  position: absolute;
  left: 12px;
  top: 14px;
  font-weight: bold;
  color: #64748b;
}

/* Hide child rows */
#allComplaints tbody tr.child-row.d-none {
  display: none !important;
}

/* ==========================================================
   BUTTON FIX (View History inside properly)
   ========================================================== */
#allComplaints .btn {
  border-radius: 10px !important;
  font-size: 13px;
  padding: 6px 12px;
  font-weight: 600;
}

/* Expand children button */
.view-children-btn {
  background: #2563eb !important;
  border: none !important;
  color: white !important;
}

.view-children-btn:hover {
  background: #1e40af !important;
}

/* ==========================================================
   PAGINATION FIX
   ========================================================== */
.dataTables_paginate .paginate_button {
  border-radius: 8px !important;
  padding: 6px 12px !important;
  margin: 2px;
  border: none !important;
  background: #f1f5ff !important;
}

.dataTables_paginate .paginate_button.current {
  background: #2563eb !important;
  color: white !important;
}

/* ==========================================================
   MOBILE VIEW CLEAN
   ========================================================== */
@media (max-width: 768px) {

  #allComplaints thead {
    display: none;
  }

  #allComplaints,
  #allComplaints tbody,
  #allComplaints tr,
  #allComplaints td {
    display: block;
    width: 100%;
  }

  #allComplaints tr {
    margin-bottom: 15px;
    border-radius: 14px;
    padding: 12px;
    box-shadow: 0 6px 18px rgba(0,0,0,0.08);
  }

  #allComplaints td {
    padding: 10px !important;
  }

  #allComplaints td::before {
    content: attr(data-label);
    font-size: 11px;
    font-weight: 700;
    color: #64748b;
    display: block;
    margin-bottom: 4px;
    text-transform: uppercase;
  }

  #allComplaints .btn {
    width: 100%;
    margin-top: 8px;
  }
}

.complaint-meta {
    color: maroon !important;
    font-weight: 600;
}

</style>


<div class="container-fluid">
  <div class="row">
    <div class="col-md-2">
      <?php include '../includes/menu.php'; ?>
    </div>

    <div class="col-md-10">
<h4 class="my-3">
        <!-- Page Heading -->
       <?= [
      1 => 'Equipment',
      2 => 'Facility',
      3 => 'Safety',
      4 => 'Process',
      5 => 'HR',
      6 => 'IT',
      7 => 'Purchase',
      8 => 'Training',
      9 => 'Inventory',
      10 => 'Admin'
    ][$type] ?? 'All' ?>

        <?= ucfirst($status ?? '') ?> Complaints
      </h4>
      <!-- Show success message -->
      <?php if (!empty($message)) echo $message; ?>
      <!-- Tool Filter Form -->
      <div class="d-flex align-items-center gap-3 mb-3">

      <?php if (in_array($status, ['pending', 'inprocess', 'onhold', 'all', ''])): ?>
      <form method="get" class="row g-3 align-items-center mb-3">
        <input type="hidden" name="return_to" value="all_complaints.php">

        <input type="hidden" name="type" value="<?= htmlspecialchars($type) ?>">

        <div class="col-auto">
          <label class="col-form-label"><b>Status</b></label>
        </div>

        <div class="col-auto">
         <select name="status" class="form-select form-select-sm">
          <option value="all" <?= ($status === 'all' || $status === '') ? 'selected' : '' ?>>
            All Complaints
          </option>
          <option value="pending" <?= $status === 'pending' ? 'selected' : '' ?>>
            Pending
          </option>
          <option value="inprocess" <?= $status === 'inprocess' ? 'selected' : '' ?>>
            In Process
          </option>
          <option value="onhold" <?= $status === 'onhold' ? 'selected' : '' ?>>
            On Hold
          </option>
        </select>


        </div>

        <div class="col-auto">
          <button type="submit" class="btn btn-primary btn-sm">Filter</button>
        </div>

      </form>

  <?php endif; ?>


        <form method="post" class="row g-3 align-items-center mb-3">

          <div class="col-auto">
            <label for="tools_name" class="col-form-label">Tool &amp; Category</label>
          </div>

          <div class="col-auto">
            <select name="tools_name" id="tools_name" class="form-select">
              <option value="">-- Select Tool --</option>
             <?php if (!in_array($type, [5,6,7,8,9,10])): ?>
              <option value="0" <?= $tools_name === '0' ? 'selected' : '' ?>>Miscellaneous</option>
            <?php endif; ?>

              <?php
              if (in_array($type, [5,6,7,8,9,10])) {  // Added 8 and 9 for Training & Inventory
                  $categories = getTxtCategories($type);
                  foreach ($categories as $id => $name) {
                      echo "<option value='{$id}' " . ($tools_name == $id ? 'selected' : '') . ">
                              {$name}
                            </option>";
                  }
              } else {
                  foreach (getTools($type) as $tool) {
                      echo "<option value='{$tool['machid']}' " . ($tools_name == $tool['machid'] ? 'selected' : '') . ">
                              {$tool['name']}
                            </option>";
                  }
              }
              ?>


            </select>
          </div>

          <div class="col-auto">
            <button type="submit" name="filter" class="btn btn-primary">Filter</button>
          </div>


        </form>
	<?php if ($type==1 || $type == 4): ?>
  <form method="get" class="row g-3 align-items-center mb-3">
    <input type="hidden" name="type" value="<?= htmlspecialchars($type) ?>">
    <input type="hidden" name="status" value="<?= htmlspecialchars($status) ?>">

    <?php if ($importance === 'critical'): ?>
      <!-- Toggle OFF -->
      <input type="hidden" name="importance" value="all">
      <div class="col-auto">
        <button type="submit" class="btn btn-secondary">All Tools</button>
      </div>
    <?php else: ?>
      <!-- Toggle ON -->
      <input type="hidden" name="importance" value="critical">
      <div class="col-auto">
        <button type="submit" class="btn btn-warning">Critical Tools</button>
      </div>
    <?php endif; ?>
  </form>
<?php endif; ?>



      </div>
      <!-- Complaints Table -->
      <div class="table-responsive">
        <table id="allComplaints" class="table table-bordered table-striped table-sm">
          <thead class="table-primary">
            <tr>
              <th>Member</th>
              
              <th>Tool &amp; Cat</th>
              <th>Complaint Description</th>
<?php if ($type == 4): ?>
                <th>Process Development</th>
                <th>Anti Contamination Development</th>
              <?php endif; ?>              
              <?php if ($status == 'pending' && $permission_key == 1) {
                echo "<th>Action</th>";
              } else {
                echo "<th> View Track </th>";
              } ?>
              <?php if($user_role =="all" || $head==1){ ?>
              <th class="no-sort">Transfer To Team</th>
              <?php }
              elseif($head==0){  ?>
                <th class="no-sort">View History</th>
              <?php } ?>
              <?php if($user_role=="all" || $head==1): ?>
              <th class="no-sort">Status</th>
              <?php endif; ?>
              <!-- <?php if ($status == 'pending' && $permission_key == 1): ?><th>Submit</th><?php endif; ?> -->
              <!-- <th>Expected completion date</th> -->
          
            </tr>
          </thead>
          <tbody>
            <?php foreach ($details as $d):
              // Status-based filtering
              if ($status === 'pending' && $d['status'] != 0) continue;
              if ($status === 'inprocess' && $d['status'] != 1) continue;
              if ($status === 'onhold' && $d['status'] != 3) continue;
              if ($status === 'closed' && $d['status'] != 2) continue;
              if (($status === 'all' || $status === '') && $d['status'] == 2) continue;

            ?>
                <tr class="parent-row" id="row-<?= (int)$d['complaint_id'] ?>">

                <!-- Member Info -->
    <td data-label="Member">
  <?= getName($d['allocated_to']) ?>
  <br><small><?= display_date($d['time_of_complaint']) ?></small>
  <br>
  <small class="complaint-meta">
      Complaint ID: <?= (int)$d['complaint_id'] ?>
  </small>

  <br>

  <small class="complaint-meta">
      Created by: <?= getName($d['member_id']) ?>
  </small>

  <!-- <small class="text-muted">Head: <?= ['1'=>'Equipment','2'=>'Facility','3'=>'Safety','4'=>'Process'][$type] ?? '' ?></small> -->
<?php if($user_role == "all" || $head==1){ ?>
  <div class="mt-2">
    <button
      type="button"
      class="btn btn-sm btn-outline-light w-100 view-children-btn"
      data-original="<?= (int)$d['complaint_id'] ?>"
      data-type="<?= (int)$type ?>"
    >
      Expand
    </button>
  </div>
<?php } ?>
</td>



                <!-- Tool Info -->
              <td data-label="Tool & Category">
             <?php
              // IMPORTANT: ensure row contains type
              $d['type'] = $type; // only if type is not already in query result

              $toolName = getComplaintToolName($d);
              ?>

              <?= $toolName ?>

              <?php if ($tabledata): ?>
                  <i class="fas fa-exclamation-triangle text-danger ms-1" title="Critical Tool"></i>
              <?php endif; ?>

              <?php
              $ec = EC_date($d['complaint_id']);
              if ($ec !== '') {
                  echo '<br><small class="text-muted"><b>Expected completion date:</b> ' . display_date($ec) . '</small>';
              }
              ?>
              </td>


             <!-- Complaint Description -->
                <td class="desc-cell" data-label="Complaint Description">
  <?= renderExpandableText(shortDesc(nl2br(htmlspecialchars_decode($d['complaint_description'])))) ?>
  <?php if (!empty($d['upload_file'])): ?>
    <a href="<?= htmlspecialchars($d['upload_file'], ENT_QUOTES) ?>" target="_blank" rel="noopener noreferrer">
      <i class="fa-solid fa-file-lines"></i>
    </a>
  <?php endif; ?>
</td>

<?php if ($type == 4): ?>
                  <td data-label="Process Development"><?= shortDesc($d['process_develop']) ?></td>
                  <td data-label="Anti Contamination Development"><?= shortDesc($d['anti_contamination_develop']) ?></td>
                <?php endif; ?>
                <!-- Action or Track -->
                <td style="text-align:center;" data-label="Action / Track">
                  <?php if (($status == 'pending' || $status == 'inprocess' || $status == 'onhold')  && $permission_key == 1): ?>
                    <form action="action_taken.php" method="post" enctype="multipart/form-data" style="display:inline; ">
                      <input type="hidden" name="complaint_id" value="<?= $d['complaint_id'] ?>">
                      <input type="hidden" name="member_id" value="<?= $member_id ?>">
                      <input type="hidden" name="type" value="<?= $type ?>">

                      <button type="submit" name="tracking"
                        class="btn btn-sm btn-outline-success no-border"
                        <?= $d['status'] == 2 ? 'disabled title="Already Closed"' : 'title="Take Action"' ?>>
                        <img src="../assets/images/action_taken.png" alt="Take Action" style="width:32px; height:32px;">
                      </button>

                    </form>
                  <?php endif; ?>

                  <br>
                  <br>
                  <!-- View Track -->
                  <?php if (count(trouble_track($d['complaint_id'], '')) > 0): ?>
		   <a href="#"
                    style="color:#0d6efd !important; font-weight:600; text-decoration:underline;"
                    onclick="return viewTrack(<?= $d['complaint_id'] ?>, <?= $type ?>);">
                    Track
                  </a>
                  <?php else: ?>
                    No Data
                  <?php endif; ?>
                </td>

                <?php
                $last_track_date = trouble_track($d["complaint_id"], "");
                $last_ts = $last_track_date[0]["timestamp"] ?? "";
                $last_ts = $last_ts ? display_timestamp($last_ts) : "";
                ?>

                <form
                  method="post"
                  enctype="multipart/form-data"
                  onsubmit="return check(
                    <?= (int)$d['complaint_id'] ?>,
                    '<?= addslashes($d['time_of_complaint']) ?>',
                    '<?= addslashes($last_ts) ?>'
                  )">

                  <?php
                  if ($type == 1) $allowed_ids = getTeamMembers('equipment');
                  elseif ($type == 2) $allowed_ids = getTeamMembers('facility');
                  elseif ($type == 3) $allowed_ids = getTeamMembers('safety');
                  elseif ($type == 4) $allowed_ids = getTeamMembers('process');
                  elseif ($type == 5) $allowed_ids = getTeamMembers('hr');
                  elseif ($type == 6) $allowed_ids = getTeamMembers('it');
                  elseif ($type == 7) $allowed_ids = getTeamMembers('purchase');
                  elseif ($type == 8) $allowed_ids = getTeamMembers('training');
                  elseif ($type == 9) $allowed_ids = getTeamMembers('inventory');
                  elseif ($type == 10) $allowed_ids = getTeamMembers('admin');


                  $members = [];
                  foreach ($allowed_ids as $id) {
                    $name = getName($id);
                    if ($name) {
                      $members[] = ["id" => $id, "name" => $name];
                    }
                  }
                  ?>


                  <!-- ✅ Transfer To Team -->
                 <td style="text-align:center;" data-label="Transfer / History">
                  <?php 
                  if (($head==1 || $user_role=="all") && $permission_key == 1){ ?>
 		   <a
                    href="complaint.php?complaint_id=<?= $d['complaint_id']; ?>&type=<?= $type; ?>&return=<?= urlencode($_SERVER['REQUEST_URI']); ?>"
                    class="btn btn-sm btn-outline-primary">
                    Transfer
                  </a>
		    <a
                      href="complaint_history.php?complaint_id=<?= $d['complaint_id']; ?>&type=<?= $type; ?>&return=<?= urlencode($_SERVER['REQUEST_URI']); ?>"
                      class="btn btn-sm btn-outline-primary">
                      View History
                    </a>
                  <?php }
                  elseif($head==0 && $permission_key == 1) {
                    $history = trouble_track($d["complaint_id"], "");
                    ?>
		   <a
                    href="complaint_history.php?complaint_id=<?= $d['complaint_id']; ?>&type=<?= $type; ?>&return=<?= urlencode($_SERVER['REQUEST_URI']); ?>"
                    class="btn btn-sm btn-outline-primary">
                    View History
                  </a>                   <?php } else { ?>
                    <span class="text-muted">N/A</span>
                  <?php }
                  
                  ?>
                </td>

<?php if(($head==1 || $user_role=="all") && $permission_key == 1): ?>
                  <!-- Status -->
                  <td data-label="Status">
                    <input type="hidden" name="complaint_id" value="<?= $d["complaint_id"]; ?>">

                    <select name="status"
                      id="complanit_status<?= $d['complaint_id']; ?>"
                      onchange="timeshow(<?= $d['complaint_id']; ?>);"
                      class="form-select form-select-sm mb-2">
                      <option value="0" <?= $d["status"] == 0 ? 'selected' : '' ?>>Pending</option>
                      <option value="1" <?= $d["status"] == 1 ? 'selected' : '' ?>>In Process</option>
                      <option value="2" <?= $d["status"] == 2 ? 'selected' : '' ?>>Closed</option>
                      <option value="3" <?= $d["status"] == 3 ? 'selected' : '' ?>>On Hold</option>
                    </select>

                    <input type="text"
                      id="c_date<?= $d['complaint_id']; ?>"
                      name="c_date"
                      class="form-control form-control-sm mb-2"
                      style="display:none;" />

                    <button type="submit"
                      name="status_update"
                      class="btn btn-sm btn-primary w-100">
                      Submit
                    </button>
                  </td>
                  <?php  else :?>
                    <td data-label="Status">
                      <?= ['0' => 'Pending', '1' => 'In Process', '2' => 'Closed', '3' => 'On Hold'][$d['status']] ?? 'Unknown' ?>
                    </td>
                  <?php endif; ?>

                </form>

         



                <!-- Transfer Button for Process -->
            

             <!-- added by shahid, Oct 30 2025, Remove checkbox and add button with image-->
               
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
<div id="dialog-confirm" style="display:none;">
  <p>Do you want to transfer the complaint to Equipment?</p>
</div>

<script>
  $('input[name="c_date"]').hide()

 function timeshow(j) {
    const status = $("#complanit_status" + j).val();
    const $dateInput = $("#c_date" + j);

    if (status == 2) { 
        // CLOSED → Auto set current datetime, hide calendar
        const now = new Date();

        const formatted =
            now.getFullYear() + "-" +
            String(now.getMonth() + 1).padStart(2, '0') + "-" +
            String(now.getDate()).padStart(2, '0') + " " +
            String(now.getHours()).padStart(2, '0') + ":" +
            String(now.getMinutes()).padStart(2, '0') + ":" +
            String(now.getSeconds()).padStart(2, '0');

        $dateInput.val(formatted).hide();  // hide calendar
    } 
      else if (status == 1) {  // In Process → Auto set current date, hide calendar
      const now = new Date();

      const formatted =
          now.getFullYear() + "-" +
          String(now.getMonth() + 1).padStart(2, '0') + "-" +
          String(now.getDate()).padStart(2, '0');

      $dateInput.val(formatted).hide();   // ✅ auto date, ❌ no calendar
  }

    else {
        $dateInput.hide();  // hide for Pending or On Hold
    }
}



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

  $(document).ready(function() {
    $('#allComplaints').DataTable({
    responsive: false,   // IMPORTANT — we handle mobile
    autoWidth: false,
    order: [],
    stateSave: true,
    pageLength: 5,       // better for mobile
    columnDefs: [
      { orderable: false, targets: 'no-sort' }
    ]
  });

  });


  function on_click(formSelector, checkboxSelector) {
    const $dialog = $("#dialog-confirm");

    $dialog.dialog({
      resizable: false,
      modal: true,
      title: "Confirmation",
      height: 180,
      width: 400,
      buttons: {
        "Yes": function() {
          $(this).dialog("close");
          $(formSelector).submit();
        },
        "No": function() {
          $(this).dialog("close");
          $(checkboxSelector).prop('checked', true);
        }
      },
      close: function() {
        $(checkboxSelector).prop('checked', true);
      }
    });
  }


 function check(j, complaintDateStr, lastDateStr) {
    const status = $("#complanit_status" + j).val();
    const dateVal = $("#c_date" + j).val();

    // Only validate when date field is visible & not Closed
    if (status != 2 && $("#c_date" + j).is(":visible")) {

      if (!dateVal) {
        alert("Please enter 'Date'");
        $("#c_date" + j).focus();
        return false;
      }

      // Normalize dates (ignore time)
      const selectedDate = new Date(dateVal + " 00:00:00");

      const complaintDate = new Date(complaintDateStr);
      complaintDate.setHours(0,0,0,0);

      let compareDate = complaintDate;

      if (lastDateStr) {
        compareDate = new Date(lastDateStr);
        compareDate.setHours(0,0,0,0);
      }

      // ❌ block only if earlier date
      if (selectedDate < compareDate) {
        alert("'Date' must be same or after complaint / last tracking date");
        $("#c_date" + j).focus();
        return false;
      }
    }

    return true;
  }

</script>
<script>
$(document).on("click", ".view-children-btn", function () {
  const btn = $(this);
  const originalId = btn.data("original");
  const type = btn.data("type");

  const existing = $(".child-of-" + originalId);
  if (existing.length > 0) {
    existing.toggleClass("d-none");

    const isVisible = !existing.first().hasClass("d-none");
    btn.text(isVisible ? "Hide (" + existing.length + ")" : "Expand (" + existing.length + ")");

    return;
  }


  btn.prop("disabled", true).text("Loading...");

  $.get("fetch_children.php", { original_id: originalId, type: type })
    .done(function (rows) {
      if (!rows || rows.length === 0) {
        btn.prop("disabled", false).text("No Children");
        return;
      }

      const CURRENT_USER_ROLE = <?= json_encode($user_role) ?>;
      const permission_key = <?= json_encode($permission_key) ?>;
      let html = "";

      rows.forEach(function (r) {
        // Handle "type 4" extra info inside the loop where 'r' is defined
      let processTd = "";
      let antiTd = "";
      let extra = "";
        if (type == 4) {
          processTd = `
            <td data-label="Process Development">
              ${escapeHtml(r.process_develop || "N/A")}
            </td>
          `;

          antiTd = `
            <td data-label="Anti Contamination Development">
              ${escapeHtml(r.anti_contamination_develop || "N/A")}
            </td>
          `;
        } else if(r.process_develop !=null || r.anti_contamination_develop!=null) {
          extra = `
            <br><b>Process Develop:</b> ${escapeHtml(r.process_develop || "N/A")}
            <br><b>Anti-Contamination Develop:</b> ${escapeHtml(r.anti_contamination_develop || "N/A")}
          `;
        }
        else
        {
                extra="";
        }

const statusText =
          r.status == 0 ? "Pending" :
          r.status == 1 ? "In process" :
          r.status == 2 ? "Closed" :
          r.status == 3 ? "On Hold" : "";

        let typeName = "";
        let roleKey = "";
        switch (parseInt(r.type)) {
          case 1: typeName = "Equipment"; roleKey = "eqp_head"; break;
          case 2: typeName = "Facility"; roleKey = "facility_head"; break;
          case 3: typeName = "Safety"; roleKey = "safety_head"; break;
          case 4: typeName = "Process"; roleKey = "process_head"; break;
          case 5: typeName = "HR"; roleKey = "hr_head"; break;
          case 6: typeName = "IT"; roleKey = "it_head"; break;
          case 7: typeName = "Purchase"; roleKey = "purchase_head"; break;
          case 8: typeName = "Training"; roleKey = "training_head"; break;
          case 9: typeName = "Inventory"; roleKey = "inventory_head"; break;
          case 10: typeName = "Admin"; roleKey = "admin_head"; break;

          default: typeName = "N/A"; roleKey = "N/A"; break;
        }

        const canTransfer = (CURRENT_USER_ROLE === "all" || CURRENT_USER_ROLE === roleKey) && permission_key == 1;
        const canUpdateStatus = (CURRENT_USER_ROLE === "all" || CURRENT_USER_ROLE === roleKey) && permission_key == 1;
        
  // IMPORTANT: form must submit to THIS page (so status_update works)
  let status_extra = "";

// ✅ FRONTEND ONLY: If child is CLOSED → show text only
if (r.status == 2) {
  status_extra = `
    <span class="badge bg-success">Closed</span>
  `;
}
else if (canUpdateStatus) {
  status_extra = `
    <form method="post" onsubmit="return check(${r.complaint_id}, '${escapeJs(r.time_of_complaint || "")}', '')">
      <input type="hidden" name="complaint_id" value="${r.complaint_id}">

      <select name="status"
        id="complanit_status${r.complaint_id}"
        onchange="timeshow(${r.complaint_id});"
        class="form-select form-select-sm mb-2">
        <option value="0" ${r.status == 0 ? 'selected' : ''}>Pending</option>
        <option value="1" ${r.status == 1 ? 'selected' : ''}>In Process</option>
        <option value="2" ${r.status == 2 ? 'selected' : ''}>Closed</option>
        <option value="3" ${r.status == 3 ? 'selected' : ''}>On Hold</option>


      </select>

      <input type="text"
        id="c_date${r.complaint_id}"
        name="c_date"
        class="form-control form-control-sm mb-2"
        style="display:none;" />

      <button type="submit" name="status_update" class="btn btn-sm btn-primary w-100">
        Submit
      </button>
    </form>
  `;
}
else {
  status_extra = `
    <span class="badge bg-secondary">
      ${statusText}
    </span>
  `;
}



        



        // CLEANING LOGIC FOR JS
        let cleanDesc = r.complaint_description || "";
        cleanDesc = cleanDesc.replace(/\\'/g, "'").replace(/\\"/g, '"').replace(/\\\\/g, "\\");
        cleanDesc = cleanDesc.replace(/\\n/g, "\n").replace(/\\nn/g, "\n\n");
        console.log(r.has_track);
        html += `
          <tr class="child-row child-of-${originalId}">
            <td class="child-indent" data-label="Member">
              <div><b>${escapeHtml(r.allocated_to_name || "")}</b> <span class="badge bg-secondary ms-2">Child</span></div>
              <small>${escapeHtml(r.time_of_complaint || "")}</small>
              <br><small class="text-muted">Created by: ${escapeHtml(r.member_name || "")}</small>
              <br><small class="text-muted">Complaint ID: ${r.complaint_id}</small><br>
              <small class="text-muted">Head: ${typeName}</small>
            </td>
            <td class="child-indent" data-label="Tool & Category">
              ${escapeHtml(getToolNameClient(r.tool_name, type))}
            </td>
            <td data-label="Complaint Description">
              ${renderExpandableTextJS(escapeHtml(cleanDesc).replace(/\n/g, "<br>"))}
              ${extra}
            </td>
            <td style="text-align:center;" data-label="Action / Track">
              ${canTransfer  ? `
                <form action="action_taken.php" method="post" style="display:inline;">
                  <input type="hidden" name="complaint_id" value="${r.complaint_id}">
                  <input type="hidden" name="member_id" value="<?= $member_id ?>">
                  <input type="hidden" name="type" value="${type}">
                  <button type="submit" name="tracking" class="btn btn-sm btn-outline-success no-border">
                    <img src="../assets/images/action_taken.png" style="width:24px;height:24px;">
                  </button>
                </form>
              ` : '<span class="text-muted">NA</span>'}
              <br><br>
 <br><br>
${r.has_track == 1 ? `
  <a href="#"
     style="color:#0d6efd !important; font-weight:600; text-decoration:underline;"
     onclick="return viewTrack(${r.complaint_id}, ${type});">
     Track
  </a>
` : `
  No Data
`}

            </td>
           <td style="text-align:center;" data-label="Transfer / History">
              ${canTransfer ? `
                <a href="complaint.php?complaint_id=${r.complaint_id}&type=${type}&return=${encodeURIComponent(window.location.href)}" class="btn btn-sm btn-outline-primary">Transfer</a>
              ` : '<span class="text-muted">N/A</span>'}
            </td>
            
           <td data-label="Status">${status_extra}</td>
          </tr>
        `;
      });

     $("#row-" + originalId).after(html);

      /* ✅ Auto scroll to first child on mobile */
      if (window.innerWidth < 768) {
        const firstChild = $(".child-of-" + originalId).first();
        if (firstChild.length) {
          $('html, body').animate({
            scrollTop: firstChild.offset().top - 80
          }, 400);
        }
      }

      btn.prop("disabled", false).text("Hide (" + rows.length + ")");

    })
    .fail(function () {
      btn.prop("disabled", false).text("Expand");
      alert("Failed to load child complaints.");
    });
});
/**
 * Helpers:
 * If you don't want AJAX for names/tool names, simplest is:
 * - show IDs only in children rows (member_id, machine_id, allocated_to)
 *
 * If you want names, easiest approach is:
 * - return final formatted HTML rows from fetch_children.php instead of JSON
 *
 * For now these are placeholders:
 */
function escapeHtml(str){
  return String(str ?? "")
    .replaceAll("&","&amp;")
    .replaceAll("<","&lt;")
    .replaceAll(">","&gt;")
    .replaceAll('"',"&quot;")
    .replaceAll("'","&#039;");
}
function escapeJs(str){
  return String(str ?? "")
    .replaceAll("\\", "\\\\")
    .replaceAll("'", "\\'")
    .replaceAll('"', '\\"')
    .replaceAll("\n", "\\n");
}


// PLACEHOLDER: you can keep IDs or replace this approach (recommended below)
function getNameClient(id){ return id ? (id) : ""; }

// PLACEHOLDER: same
function getToolNameClient(machineId, type){ return machineId ? (machineId) : ""; }



function renderExpandableTextJS(text, limit = 200) {
    // Ensure text is string and decode HTML entities
    text = String(text || "");
    
    // Decode HTML entities for display
    const txt = $("<textarea/>").html(text).text();

    if (txt.length <= limit) {
        // If short, just return with <br> preserved
        return txt.replace(/\n/g, "<br>");
    }

    const shortText = txt.substring(0, limit);

    return `
        <span class="short-text">${shortText}</span>
        <span class="full-text d-none">${txt.replace(/\n/g, "<br>")}</span>
        <a href="#" class="toggle-desc ms-1">Show more</a>
    `;
}
$(document).on("click", ".toggle-desc", function (e) {
  e.preventDefault();

  const link = $(this);
  const container = link.closest("td");

  container.find(".short-text, .full-text").toggleClass("d-none");
  link.text(link.text() === "Show more" ? "Show less" : "Show more");
});


</script>


<?php include '../includes/footer.php'; ?>

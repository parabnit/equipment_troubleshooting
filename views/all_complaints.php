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

$allowedTypes = [1,2,3,4,5,6,7,8,9];
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
if (is_LabManager($member_id) || is_AssistLabManager($member_id)) {
  $user_role = 'all';
  $details = all_complaint($head, $type, 0, $tools_name,$tabledata); 
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
        
      }
      elseif (is_EquipmentTeam($member_id)) 
      { 
        $user_role = 'eqp_team'; 
        $details = my_allocated_complaint($member_id, $type, 0, $tools_name, $tabledata);
        
      }
      break;

    case 2:
      if (is_FacilityHead($member_id)) 
      { 
        $user_role = 'facility_head'; 
        $head = 1;
        $details = all_complaint($head, $type, 0, $tools_name, $tabledata); 
         
      }
      elseif (is_FacilityTeam($member_id)) 
        { 
          $user_role = 'facility_team'; 
          $details = my_allocated_complaint($member_id, $type, 0, $tools_name, $tabledata);
          
        }
      break;

    case 3:
      if (is_SafetyHead($member_id)) 
      { 
        $user_role = 'safety_head'; 
        $head = 1; 
        $details = all_complaint($head, $type, 0, $tools_name, $tabledata);
        
      }
      elseif (is_SafetyTeam($member_id)) 
      { 
        $user_role = 'safety_team';
        $details = my_allocated_complaint($member_id, $type, 0, $tools_name, $tabledata);
        
      }
      break;

    case 4:
      if (is_ProcessHead($member_id)) 
      { 
        $user_role = 'process_head'; 
        $head = 1; 
        $details = all_complaint($head, $type, 0, $tools_name, $tabledata); 
        
      }
      elseif (is_ProcessTeam($member_id)) 
      { 
        $user_role = 'process_team'; 
        $details = my_allocated_complaint($member_id, $type, 0, $tools_name, $tabledata);
        
      }
      break;

    case 5:
      if (is_HRHead($member_id)) 
      { 
        $user_role = 'hr_head'; 
        $head = 1;
        $details = all_complaint($head, $type, 0, $tools_name, $tabledata);
        
      }
      elseif (is_HRTeam($member_id)) 
      { 
        $user_role = 'hr_team';
        $details = my_allocated_complaint($member_id, $type, 0, $tools_name, $tabledata);
        
      }
      break;

    case 6:
      if (is_ITHead($member_id)) 
        { 
          $user_role = 'it_head'; 
          $head = 1; 
          $details = all_complaint($head, $type, 0, $tools_name, $tabledata);
          
        }
      elseif (is_ITTeam($member_id)) 
      { 
        $user_role = 'it_team';
        $details = my_allocated_complaint($member_id, $type, 0, $tools_name, $tabledata);
         
      }
      break;

    case 7:
      if (is_PurchaseHead($member_id)) 
      { 
        $user_role = 'purchase_head'; 
        $head = 1; 
        $details = all_complaint($head, $type, 0, $tools_name, $tabledata);
        
      }
      elseif (is_PurchaseTeam($member_id)) 
      { 
        $user_role = 'purchase_team';
        $details = my_allocated_complaint($member_id, $type, 0, $tools_name, $tabledata);
        
      }
      break;

    case 8:
      if (is_TrainingHead($member_id)) 
      { 
        $user_role = 'training_head'; 
        $head = 1; 
        $details = all_complaint($head, $type, 0, $tools_name, $tabledata);
        
      }
      elseif (is_TrainingTeam($member_id)) 
      {  
        $user_role = 'training_team'; 
        $details = my_allocated_complaint($member_id, $type, 0, $tools_name, $tabledata);
        
      }
      break;

    case 9:
      if (is_InventoryHead($member_id)) 
        { 
          $user_role = 'inventory_head'; 
          $head = 1; 
          $details = all_complaint($head, $type, 0, $tools_name, $tabledata);
          
        }
      elseif (is_InventoryTeam($member_id)) 
        { 
          $user_role = 'inventory_team'; 
          $details = my_allocated_complaint($member_id, $type, 0, $tools_name,  $tabledata);
          
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

$permission_key = check_permission($type, $member_id); 

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
  $status_db    = (int)($_POST['status'] ?? -1);
  if ($status_db === 2) {
    // CLOSED → force current datetime
    $c_date = date('Y-m-d H:i:s');
  } else {
    // Others → only date from user
    $c_date = $_POST['c_date'] ?? '';
  }

  $updated_by   = (int)($_SESSION['memberid'] ?? 0);

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
/* -----------------------------
   Existing styles (keep)
------------------------------ */
.no-border {
  border: 1px solid transparent !important;
}
.no-border:hover {
  border-color: #198754 !important;
}

.table td, .table th {
  padding: 15px 10px;
}

/* Complaint Description wrap */
#allComplaints td:nth-child(3) {
  white-space: normal !important;
  word-break: break-word;
  overflow-wrap: break-word;
  max-width: 350px;
}

/* Remove horizontal scrollbar */
.table-responsive {
  overflow-x: hidden !important;
}



select {
  max-width: 200px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

/* -----------------------------
   ✅ FINAL Parent/Child Theme
   Parent = Grey | Child = White
------------------------------ */

/* Parent row (original complaint) */
#allComplaints tbody tr.parent-row > td {
  background: #e9ecef !important;  /* light grey */
  color: #111 !important;
  border-color: #cfd4da !important;
}

#allComplaints tbody tr.parent-row a,
#allComplaints tbody tr.parent-row small {
  color: #111 !important;
}

/* Child rows */
#allComplaints tbody tr.child-row > td {
  background: #ffffff !important;  /* white */
  color: #111 !important;
  border-color: #dee2e6 !important;
}

/* Child nesting indicator */
#allComplaints tbody tr.child-row td:first-child {
  padding-left: 28px !important;
  border-left: 6px solid #adb5bd !important; /* grey marker line */
  position: relative;
}

#allComplaints tbody tr.child-row td:first-child::before {
  content: "↳";
  position: absolute;
  left: 10px;
  top: 16px;
  font-weight: bold;
  color: #6c757d;
}

/* Optional: nicer "View Children" button (neutral) */
.view-children-btn {
  background: #f8f9fa !important;
  border: 1px solid #adb5bd !important;
  color: #111 !important;
}
.view-children-btn:hover {
  background: #e2e6ea !important;
}

/* FORCE blue Track link in parent rows */
#allComplaints tbody tr.parent-row a.track-link {
  color: #0d6efd !important;
  text-decoration: underline;
  font-weight: 600;
}

#allComplaints tbody tr.parent-row a.track-link:hover {
  color: #084298 !important;
}


.short-text, .full-text {
  white-space: normal;
  word-break: break-word;
}
.toggle-desc {
  font-weight: 600;
  cursor: pointer;
}

/* =================================
   MOBILE VIEW — Main Complaints Page
   ================================= */
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
    margin-bottom: 16px;
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 2px 6px rgba(0,0,0,0.08);
    padding: 8px;
  }

  #allComplaints td {
    border: none !important;
    padding: 8px 6px !important;
  }

  #allComplaints td::before {
    content: attr(data-label);
    display: block;
    font-size: 11px;
    font-weight: 600;
    color: #6c757d;
    margin-bottom: 2px;
    text-transform: uppercase;
  }

  /* Remove horizontal scrolling */
  .table-responsive {
    overflow-x: visible !important;
  }

  /* Buttons full width on mobile */
  #allComplaints .btn {
    width: 100%;
    margin-bottom: 6px;
  }

  /* Center icons */
  #allComplaints td[style*="text-align:center"] {
    text-align: left !important;
  }

  /* Child row indent reset for mobile */
  #allComplaints tbody tr.child-row td:first-child {
    padding-left: 8px !important;
    border-left: none !important;
  }
}

@media (max-width: 768px) {
  .d-flex.align-items-center.gap-3 {
    flex-direction: column;
    align-items: stretch !important;
  }
}
/* Ensure child rows show on mobile */
@media (max-width: 768px) {

  #allComplaints tbody tr.child-row {
    display: block !important;
  }

  #allComplaints tbody tr.child-row td {
    display: block !important;
    background: #f8f9fa !important;
    margin-bottom: 6px;
    border-radius: 6px;
  }

  #allComplaints tbody tr.child-row td::before {
    color: #495057;
  }
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
        <?= ['1' => 'Equipment', '2' => 'Facility', '3' => 'Safety', '4' => 'Process','HR','IT','Purchase','Training','Inventory'][$type] ?? 'All' ?> -
        <?= ucfirst($status ?? '') ?> Complaints
      </h4>
      <!-- Show success message -->
      <?php if (!empty($message)) echo $message; ?>
      <!-- Tool Filter Form -->
      <div class="d-flex align-items-center gap-3 mb-3">

      <?php if (in_array($status, ['pending', 'inprocess', 'onhold'])): ?>
      <form method="get" class="row g-3 align-items-center mb-3">

        <input type="hidden" name="type" value="<?= htmlspecialchars($type) ?>">

        <div class="col-auto">
          <label class="col-form-label"><b>Status</b></label>
        </div>

        <div class="col-auto">
          <select name="status" class="form-select form-select-sm">
            <option value="pending" <?= $status === 'pending' ? 'selected' : '' ?>>Pending</option>
            <option value="inprocess" <?= $status === 'inprocess' ? 'selected' : '' ?>>In Process</option>
            <option value="onhold" <?= $status === 'onhold' ? 'selected' : '' ?>>On Hold</option>
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
             <?php if (!in_array($type, [5,6,7,8,9])): ?>
              <option value="0" <?= $tools_name === '0' ? 'selected' : '' ?>>Miscellaneous</option>
            <?php endif; ?>

              <?php
              if (in_array($type, [5,6,7,8,9])) {  // Added 8 and 9 for Training & Inventory
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
              <th class="no-sort">Status</th>

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

            ?>
                <tr class="parent-row" id="row-<?= (int)$d['complaint_id'] ?>">

                <!-- Member Info -->
    <td data-label="Member">
  <?= getName($d['allocated_to']) ?>
  <br><small><?= display_date($d['time_of_complaint']) ?></small>
  <br><small class="text-muted">Complaint ID: <?= (int)$d['complaint_id'] ?></small><br><small class="text-muted">Created by: <?= getName($d['member_id']) ?></small>
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
              // Default tool name
              $toolName = 'Miscellaneous';

              // For Equipment & Process
              if (in_array($type, [1, 4])) {
                  if ($d['machine_id'] != 0) {
                      $toolName = getToolName($d['machine_id']);
                  }
              }
              // For Facility
              elseif ($type == 2) {
                  if ($d['machine_id'] != 0) {
                      $toolName = getToolName_facility($d['machine_id']);
                  }
              }
              // For Safety
              elseif ($type == 3) {
                  if ($d['machine_id'] != 0) {
                      $toolName = getToolName_safety($d['machine_id']);
                  }
              }
              // For category types: HR, IT, Purchase, Training, Inventory
              elseif (in_array($type, [5,6,7,8,9])) {
                  $categories = getTxtCategories($type);
                  $toolName = $categories[$d['machine_id']] ?? 'Miscellaneous';
              }
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
    else if (status == 1 || status == 3 || status == 0) {
      // OTHER STATUSES → Show DATE ONLY picker
      $dateInput.val("").show();

      $dateInput.datetimepicker({
        timepicker: false,     // ❌ no time
        format: 'Y-m-d'        // ✅ date only
      });

      $dateInput.focus();
    } 
    else {
      $dateInput.hide();
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
    const closedDate = new Date($("#c_date" + j).val());
    const complaintDate = new Date(complaintDateStr);
    const lastTrackingDate = lastDateStr ? new Date(lastDateStr) : null;
    const status = $("#complanit_status" + j).val();

    // Only validate date if NOT closed
      if (status != 2 && $("#c_date" + j).is(":visible")) {
      if (!$("#c_date" + j).val()) {
        alert("Please enter 'Date'");
        $("#c_date" + j).focus();
        return false;
      }

      if (lastTrackingDate) {
        if (closedDate < lastTrackingDate) {
          alert("'Date' must be >= 'Last Tracking date'");
          $("#c_date" + j).focus();
          return false;
        }
      } else {
        if (closedDate < complaintDate) {
          alert("'Date' must be >= 'Complaint date'");
          $("#c_date" + j).focus();
          return false;
        }
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
    existing.toggle();
    btn.text(existing.is(":visible") ? "Hide (" + existing.length + ")" : "Expand (" + existing.length + ")");
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
      let html = "";

      rows.forEach(function (r) {
        // Handle "type 4" extra info inside the loop where 'r' is defined
      let processTd = "";
      let antiTd = "";

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


  // IMPORTANT: form must submit to THIS page (so status_update works)
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
          default: typeName = "N/A"; roleKey = "N/A"; break;
        }

        const canTransfer = (CURRENT_USER_ROLE === "all" || CURRENT_USER_ROLE === roleKey);

        // CLEANING LOGIC FOR JS
        let cleanDesc = r.complaint_description || "";
        cleanDesc = cleanDesc.replace(/\\'/g, "'").replace(/\\"/g, '"').replace(/\\\\/g, "\\");
        cleanDesc = cleanDesc.replace(/\\n/g, "\n").replace(/\\nn/g, "\n\n");

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
              ${r.status != 2 ? `
                <form action="action_taken.php" method="post" style="display:inline;">
                  <input type="hidden" name="complaint_id" value="${r.complaint_id}">
                  <input type="hidden" name="member_id" value="<?= $member_id ?>">
                  <input type="hidden" name="type" value="${type}">
                  <button type="submit" name="tracking" class="btn btn-sm btn-outline-success no-border">
                    <img src="../assets/images/action_taken.png" style="width:24px;height:24px;">
                  </button>
                </form>
              ` : '<span class="text-muted">Closed</span>'}
              <br><br>
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
        <span class="short-text">${shortText}...</span>
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

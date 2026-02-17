<?php
session_start();

require_once("../config/connect.php");
require_once("../includes/common.php");

/**
 * ======================================================
 * ✅ FORM DATA HANDLER (FormData + File Upload)
 * ======================================================
 */


 if (empty($_SESSION['csrf_token'])) {
  $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit'])) {

  ob_clean();
  header("Content-Type: application/json");

  try {

      if (empty($_SESSION['login'])) {
          throw new Exception("Session expired");
      }

      if (
          empty($_POST['csrf_token']) ||
          $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')
      ) {
          throw new Exception("Invalid CSRF token");
      }

      $complaint_id = (int)($_POST['complaint_id'] ?? 0);
      $member_id    = (int)($_POST['member_id'] ?? 0);

      if (!$complaint_id || !$member_id) {
          throw new Exception("Invalid request");
      }

      function b64($v) {
          return $v ? base64_decode($v, true) ?: '' : '';
      }

      // decode fields
      $diagnosis = b64($_POST['diagnosis'] ?? '');
      $action_taken = b64($_POST['action_taken'] ?? '');

      // FILE
      $uploaded_file = '';
      if (!empty($_FILES['file']['name'])) {
          $nos = count(trouble_track($complaint_id, ''));
          $ext = strtolower(pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION));
          $uploaded_file = "uploads/{$complaint_id}_{$nos}.{$ext}";

          if (!move_uploaded_file($_FILES['file']['tmp_name'], $uploaded_file)) {
              throw new Exception("File upload failed");
          }
      }

      // ✅ Convert Expected Completion Date to MySQL format
      $expected_date = b64($_POST['expected_completion_date'] ?? '');

      if (!empty($expected_date)) {
          $dt = DateTime::createFromFormat("d-m-Y", $expected_date);
          if ($dt) {
              $expected_date = $dt->format("Y-m-d");
          }
      }

      $ok = insert_trouble_track(
          $complaint_id,
          $member_id,
          b64($_POST['working_team'] ?? ''),
          $diagnosis,
          $action_taken,
          b64($_POST['work_done_by'] ?? ''),
          b64($_POST['spare_parts'] ?? ''),
          b64($_POST['cost_spare_parts'] ?? ''),
          b64($_POST['procurement_time_spares'] ?? ''),
          b64($_POST['comments'] ?? ''),
          $expected_date,
          b64($_POST['action_plan'] ?? ''),
          b64($_POST['vendor_select'] ?? ''),
          b64($_POST['vendor_contact'] ?? ''),
          b64($_POST['interaction'] ?? ''),
          b64($_POST['feedback'] ?? ''),
          b64($_POST['action_item_owner'] ?? ''),
          $uploaded_file
      );

      if (!$ok) {
          throw new Exception("Insert failed");
      }

      // ✅ STEP 2: Update complaint status in equipment_complaint table

      $status = (int)($_POST['status'] ?? 0);
      $c_date = ($status == 2) ? date("Y-m-d H:i:s") : NULL;


      $update = mysqli_query($db_equip, "
          UPDATE equipment_complaint 
          SET status = '$status',
              status_timestamp = NOW(),
              status_updated_by = '$member_id'
          WHERE complaint_id = '$complaint_id'
      ");

      if (!$update) {
          throw new Exception("Status update failed");
      }

      /* ADD THIS */
      if ($status === 2) {
          send_complaint_closed_email($complaint_id);
      }


      echo json_encode([
          "status" => "success",
          "message" => "Action saved successfully"
      ]);
      exit;

  } catch (Throwable $e) {
      error_log($e->getMessage());
      echo json_encode([
          "status" => "error",
          "message" => $e->getMessage()
      ]);
      exit;
  }
}



/**
 * ======================================================
 * ⬇️ UI FLOW STARTS HERE (GET REQUEST)
 * ======================================================
 */
require_once("../includes/auth_check.php");
require_once("../includes/header.php");




// if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['submit'])) {
//     if (!empty($_SERVER['HTTP_REFERER'])) {
//         $_SESSION['return_url'] = $_SERVER['HTTP_REFERER'];
//     }
// }

// if (empty($_SESSION['login'])) {
//   header("Location: ../logout.php");
//   exit;
// }

// // Validate POST params
// if (!isset($_POST['complaint_id'], $_POST['member_id'], $_POST['type'])) {
//   header("Location: complaint.php");
//   exit;
// }


$complaint_id =  $_POST['complaint_id'];
$member_id    = $_POST['member_id'];
$type         = check_number($_POST['type']);

// Fetch complaint
$complaint = complaintByID($complaint_id, $type);
// echo "<pre>";
// print_r($complaint);
// echo "</pre>";
$status_map = [
  0 => 'Pending',
  1 => 'In process',
  2 => 'Closed',
  3 => 'On Hold'
];

$type_map = [
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
];

$complaint_type_text = $type_map[$_POST['type']] ?? 'Unknown';


$status_text = $status_map[$complaint['status']] ?? 'Unknown';

// echo "Complaint Status: " . $status_text;

// Fetch common data
$memberName = getName($complaint['member_id']);
$allocatedTo = !empty($complaint['allocated_to']) ? getName($complaint['allocated_to']) : null;
$toolName = 'Unknown';

// Equipment & Process
if ($_POST['type'] == 1 || $_POST['type'] == 4) {
    $toolName = getToolName($complaint['machine_id']);
}

// Facility
elseif ($_POST['type'] == 2) {
    $toolName = getToolName_facility($complaint['machine_id']);
}

// Safety
elseif ($_POST['type'] == 3) {
    $toolName = getToolName_safety($complaint['machine_id']);
}

// HR, IT, Purchase, Training, Inventory
elseif (in_array($_POST['type'], [5, 6, 7, 8, 9,10])) {

    $categories = getTxtCategories($_POST['type']);

    // when machine_id = 0 → show category name
    if ((int)$complaint['machine_id'] === 0) {
        $toolName = array_values($categories)[0] ?? 'Miscellaneous';
    } else {
        $toolName = $categories[$complaint['machine_id']] ?? 'Miscellaneous';
    }
}


$timeOfComplaint = display_timestamp($complaint['time_of_complaint']);

// Description handling
$desc = str_replace(["\\r\\n", "\\n", "\\r", "\r\n", "\r", "\n"], "\n", $complaint['complaint_description']);
$desc = str_replace("\\", "", $desc);

// $words = preg_split('/\s+/', trim($desc));
// $shortDesc = count($words) > 100 ? implode(' ', array_slice($words, 0, 100)) . '...' : $desc;
$desc = trim($desc);
$shortDesc = shortDesc($desc);

// if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit'])) {
//   function esc($db, $key)
//   {
//     return mysqli_real_escape_string($db, trim($_POST[$key] ?? ''));
//   }

//   $complaint_id = esc($db_slot, 'complaint_id');
//   $member_id    = esc($db_slot, 'member_id');

//   // Vendor fields only if Yes
//   $vendor_name = $vendor_contact = $vendor_interaction = $vendor_comments = '';
//   if ((trim($_POST['vendor_interaction'] ?? '') === 'Yes')) {
//     $vendor_name        = esc($db_slot, 'vendor_name') ?: esc($db_slot, 'vendor_select');
//     $vendor_contact     = esc($db_slot, 'vendor_contact');
//     $vendor_interaction = esc($db_slot, 'interaction');
//     $vendor_comments    = esc($db_slot, 'feedback');
//   }

//   // Handle file upload
//   $uploaded_file = '';
  if (!empty($_FILES['file']['name'])) {
    $nos = count(trouble_track($complaint_id, ''));
    $ext = strtolower(pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION));
    $filename = "uploads/{$complaint_id}_{$nos}.{$ext}";
    if (move_uploaded_file($_FILES['file']['tmp_name'], $filename)) {
      $uploaded_file = $filename;
    }
  }

//   try {
//     $message = insert_trouble_track(
//       $complaint_id,
//       $member_id,
//       esc($db_slot, 'working_team'),
//       esc($db_slot, 'diagnosis'),
//       esc($db_slot, 'action_taken'),
//       esc($db_slot, 'work_done_by'),
//       esc($db_slot, 'spare_parts'),
//       esc($db_slot, 'cost_spare_parts'),
//       esc($db_slot, 'procurement_time_spares'),
//       esc($db_slot, 'comments'),
//       esc($db_slot, 'expected_completion_date'),
//       esc($db_slot, 'action_plan'),
//       $vendor_name,
//       $vendor_contact,
//       $vendor_interaction,
//       $vendor_comments,
//       esc($db_slot, 'action_item_owner'),
//       $uploaded_file
//     );

//     // ✅ If success → redirect
//     $type = esc($db_slot, 'type');
//  // ✅ Redirect back to original page
//     if (!empty($_SESSION['return_url'])) {
//         $redirect = $_SESSION['return_url'];
//         unset($_SESSION['return_url']);
//         // header("Location: $redirect");
//     } else {
//         // header("Location: all_complaints.php?type={$type}&status=pending&importance=all");
//     }
//     exit;
//   } catch (Exception $e) {
//     error_log("DB Insert Error " . $e->getMessage());
//    // $message = "Special characters are not allowed. Please remove them and try again.";
//    $message = "DB Insert Error " . $e->getMessage();
//     // $message = "Special characters are not allowed. Please remove them and try again.";
//  echo "<script>alert(" . json_encode($message) . ");</script>";
//   }
// }

?>
<style>
/* Container spacing */
.previous-actions-list {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

/* Each action card */
.previous-actions-list .card {
  border: none;
  border-left: 6px solid transparent;
  border-image: linear-gradient(180deg, #0d6efd, #6610f2) 1;
  background: linear-gradient(135deg, #f5f7ff, #ffffff);
  border-radius: 14px;
  padding: 16px 18px;
  box-shadow: 0 6px 18px rgba(13, 110, 253, 0.08);
  transition: all 0.25s ease;
  position: relative;
  overflow: hidden;
}

/* Soft glow highlight strip */
.previous-actions-list .card::before {
  content: "";
  position: absolute;
  inset: 0;
  background: linear-gradient(
    120deg,
    rgba(13, 110, 253, 0.08),
    transparent 60%
  );
  pointer-events: none;
}

/* Hover animation */
.previous-actions-list .card:hover {
  transform: translateY(-3px) scale(1.01);
  box-shadow: 0 12px 28px rgba(13, 110, 253, 0.15);
}

/* Labels */
.previous-actions-list strong {
  color: #0d6efd;
  font-weight: 600;
  letter-spacing: 0.2px;
}

/* Paragraph text */
.previous-actions-list p {
  font-size: 14.5px;
  line-height: 1.7;
  color: #333;
  margin-bottom: 6px;
}

/* Date + user meta */
.previous-actions-list .meta {
  font-size: 12.5px;
  color: #6c757d;
  margin-bottom: 8px;
  display: flex;
  align-items: center;
  gap: 8px;
}

/* Section headings inside card */
.previous-actions-list .section-title {
  font-size: 13px;
  font-weight: 600;
  color: #495057;
  margin-top: 10px;
  margin-bottom: 4px;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

/* Divider line */
.previous-actions-list .divider {
  height: 1px;
  background: linear-gradient(to right, #dee2e6, transparent);
  margin: 10px 0;
}

/* ===== Vendor Box Super Premium ===== */
.vendor-box {
  border-radius: 18px;
  background: linear-gradient(145deg, #f5f9ff, #ffffff);
  border: 1px solid #d6e4ff;
  box-shadow:
    0 10px 25px rgba(13,110,253,0.10),
    inset 0 1px 0 rgba(255,255,255,0.8);
  padding: 16px 16px 14px;
  position: relative;
  overflow: hidden;
}

/* Subtle glow strip on left */
.vendor-box::before {
  content: "";
  position: absolute;
  left: 0;
  top: 0;
  width: 4px;
  height: 100%;
  background: linear-gradient(180deg, #0d6efd, #6610f2);
  border-radius: 4px 0 0 4px;
}

/* ===== Header ===== */
.vendor-header {
  font-size: 15px;
  font-weight: 800;
  color: #0d6efd;
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 12px;
  letter-spacing: 0.3px;
}

/* ===== Yes/No Toggle Pills ===== */
.vendor-toggle {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 10px;
  margin-bottom: 14px;
}

.vendor-radio span {
  border-radius: 14px;
  padding: 9px 0;
  font-size: 13px;
  font-weight: 700;
  border: 1px solid #d0dbf5;
  background: linear-gradient(180deg, #ffffff, #f2f6ff);
  box-shadow: inset 0 1px 0 #fff;
}

.vendor-radio input:checked + span {
  background: linear-gradient(135deg, #0d6efd, #4f46e5);
  color: #fff;
  border: none;
  box-shadow:
    0 6px 14px rgba(13,110,253,0.35),
    inset 0 1px 0 rgba(255,255,255,0.2);
  transform: translateY(-1px);
}

/* ===== Compact Groups ===== */
.form-group-compact {
  margin-bottom: 12px;
}

.form-group-compact label {
  font-size: 11.5px;
  font-weight: 700;
  color: #3b4a6b;
  text-transform: uppercase;
  letter-spacing: 0.6px;
  margin-bottom: 4px;
}

/* ===== Inputs & Textareas ===== */
.vendor-box .form-control {
  border-radius: 10px;
  border: 1px solid #dde6f7;
  background: linear-gradient(180deg, #ffffff, #f8faff);
  font-size: 13.5px;
  padding: 9px 10px;
  transition: all 0.2s ease;
}

.vendor-box .form-control:focus {
  border-color: #0d6efd;
  box-shadow:
    0 0 0 3px rgba(13,110,253,0.15),
    0 4px 10px rgba(13,110,253,0.15);
  background: #fff;
}

/* ===== Interaction Quality Pills ===== */
.vendor-quality {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}

.quality-pill span {
  padding: 6px 12px;
  border-radius: 999px;
  font-size: 12px;
  font-weight: 600;
  border: 1px solid #d6deef;
  background: linear-gradient(180deg, #ffffff, #f4f7ff);
  box-shadow: inset 0 1px 0 #fff;
  transition: all 0.2s ease;
}

.quality-pill span:hover {
  border-color: #0d6efd;
  transform: translateY(-1px);
}

.quality-pill input:checked + span {
  background: linear-gradient(135deg, #198754, #20c997);
  color: #fff;
  border: none;
  box-shadow:
    0 6px 14px rgba(25,135,84,0.35),
    inset 0 1px 0 rgba(255,255,255,0.2);
  transform: translateY(-1px) scale(1.03);
}

/* ===== Disabled State ===== */
.vendor-box input:disabled,
.vendor-box textarea:disabled {
  background: #eef2f7 !important;
  border-color: #e0e6f0;
  opacity: 0.75;
}

/* ===== Micro Animations ===== */
.vendor-box * {
  transition: box-shadow 0.2s ease, transform 0.15s ease;
}
/* ===== Premium Colorful Complaint Info ===== */
.complaint-tile {
  border-radius: 18px;
  padding: 16px 18px;
  color: #fff;
  position: relative;
  overflow: hidden;
  box-shadow: 0 12px 30px rgba(0,0,0,0.15);
  transition: all 0.25s ease;
}

.complaint-tile:hover {
  transform: translateY(-4px) scale(1.02);
  box-shadow: 0 18px 45px rgba(0,0,0,0.25);
}

.complaint-tile .label {
  font-size: 11px;
  letter-spacing: 1px;
  opacity: 0.85;
  text-transform: uppercase;
}

.complaint-tile .value {
  font-size: 18px;
  font-weight: 800;
  margin-top: 4px;
}

/* Color themes */
.tile-type {
  background: linear-gradient(135deg, #6366f1, #8b5cf6);
}

.tile-user {
  background: linear-gradient(135deg, #06b6d4, #22d3ee);
}

.tile-tool {
  background: linear-gradient(135deg, #f59e0b, #fbbf24);
  color: #1f2933;
}

.tile-status {
  background: linear-gradient(135deg, #ef4444, #f97316);
}

.tile-time {
  background: linear-gradient(135deg, #10b981, #34d399);
}

.tile-allocated {
  background: linear-gradient(135deg, #ec4899, #f472b6);
}

/* Description box colorful */
.desc-box {
  background: linear-gradient(135deg, #0f172a, #1e293b);
  color: #fff;
  border-radius: 18px;
  padding: 18px;
  box-shadow: 0 12px 35px rgba(15,23,42,0.5);
  border-left: 6px solid #38bdf8;
}

/* ===== Compact Premium Complaint Info ===== */
.complaint-tile {
  border-radius: 14px;
  padding: 10px 12px;              /* 🔽 smaller padding */
  color: #fff;
  position: relative;
  overflow: hidden;
  box-shadow: 0 8px 20px rgba(0,0,0,0.18);
  transition: all 0.2s ease;
  min-height: 64px;               /* 🔽 control height */
  display: flex;
  flex-direction: column;
  justify-content: center;
}

.complaint-tile:hover {
  transform: translateY(-2px) scale(1.01);  /* 🔽 less hover jump */
  box-shadow: 0 12px 28px rgba(0,0,0,0.28);
}

.complaint-tile .label {
  font-size: 9.5px;               /* 🔽 smaller label */
  letter-spacing: 0.8px;
  opacity: 0.85;
  text-transform: uppercase;
  line-height: 1.2;
}

.complaint-tile .value {
  font-size: 14.5px;              /* 🔽 smaller value */
  font-weight: 800;
  margin-top: 2px;
  line-height: 1.2;
}

/* Extra compact status tile */
.tile-status {
  background: linear-gradient(135deg, #ef4444, #f97316);
  padding: 6px 8px !important;
}

.complaint-tile.tile-status .value {
  font-size: 13px !important;
  font-weight: 800;
}

.complaint-tile.tile-status .label {
  font-size: 9px !important;
}

/* Premium compact status box */
.status-box {
  background: linear-gradient(135deg, #f8fafc, #eef2ff);
  border: 1px solid #e2e8f0;
}

.status-select {
  border-radius: 10px;
  letter-spacing: 0.3px;
}

/* Color hint based on selected value (optional visual boost) */
.status-select option[value="0"] { color: #f59e0b; } /* Pending */
.status-select option[value="1"] { color: #2563eb; } /* In Process */
.status-select option[value="2"] { color: #16a34a; } /* Closed */
.status-select option[value="3"] { color: #7c3aed; } /* On Hold */

.complaint-info-panel {
  overflow: hidden;
  max-height: 0;
  transition: max-height 0.5s ease;
}

.complaint-info-panel.open {
  max-height: 3000px;
}


</style>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<div class="container-fluid">
  <div class="row">
    <div class="col-md-2">
      <?php include("../includes/menu.php"); ?>
    </div>

    <div class="col-md-10">

      <!-- Complaint Info Header -->
     <!-- ================= Complaint Information (Collapsible Card) ================= -->
<div class="card mb-3 shadow-sm">

  <!-- Header with Toggle -->
  <div class="card-header bg-info text-white d-flex justify-content-between align-items-center"
       style="cursor:pointer;"
       onclick="toggleComplaintInfo()">

    <h5 class="mb-0">
      🗂️ Complaint Information
      <!-- <small class="ms-2 opacity-75">(Click to show / hide)</small> -->
    </h5>

    <button type="button"
            id="complaintToggleBtn"
            class="btn btn-sm btn-light"
            onclick="event.stopPropagation(); toggleComplaintInfo();">
      👁 Show
    </button>
  </div>

  <!-- Sliding Body -->
  <div id="complaintInfoPanel" class="complaint-info-panel">

    <div class="card-body">

      <!-- ===== Ultra Colorful Complaint Info ===== -->
      <div class="row g-3 mb-4">

        <div class="col-md-3">
          <div class="complaint-tile tile-type">
            <div class="label">Complaint Type</div>
            <div class="value">🏷️ <?= htmlspecialchars($complaint_type_text) ?></div>
          </div>
        </div>

        <div class="col-md-3">
          <div class="complaint-tile tile-user">
            <div class="label">Complaint By</div>
            <div class="value">👤 <?= htmlspecialchars($memberName) ?></div>
          </div>
        </div>

        <div class="col-md-3">
          <div class="complaint-tile tile-tool">
            <div class="label">Tool / Category</div>
            <div class="value">🛠️ <?= htmlspecialchars($toolName) ?></div>
          </div>
        </div>

        <div class="col-md-3">
          <div class="complaint-tile tile-status">
            <div class="label">Status</div>
            <div class="value">🚦 <?= htmlspecialchars($status_text) ?></div>
          </div>
        </div>

        <div class="col-md-3">
          <div class="complaint-tile tile-time">
            <div class="label">Complaint Time</div>
            <div class="value">🕒 <?= htmlspecialchars($timeOfComplaint) ?></div>
          </div>
        </div>

        <?php if ($allocatedTo): ?>
        <div class="col-md-3">
          <div class="complaint-tile tile-allocated">
            <div class="label">Allocated To</div>
            <div class="value">👨‍🔧 <?= htmlspecialchars($allocatedTo) ?></div>
          </div>
        </div>
        <?php endif; ?>

      </div>
      <!-- ===== End Ultra Colorful Complaint Info ===== -->

      <!-- Complaint Description -->
      <div class="desc-box mb-2 p-3 rounded-3"
           style="background: linear-gradient(135deg, #0f172a, #1e293b); color:#fff;">

        <div class="fw-bold mb-2">📝 Complaint Description</div>

        <div style="line-height:1.6;">
          <?= nl2br(htmlspecialchars_decode($shortDesc)) ?>
        </div>

      </div>

    </div> <!-- /.card-body -->
  </div> <!-- /#complaintInfoPanel -->

</div>
<!-- ================= End Complaint Information ================= -->


          <!-- Action + Previous Side-by-Side -->
          <div class="row">
            <!-- Action Taken -->
           <div class="col-md-6 mb-3">
            <div class="card h-100 shadow-sm">
              <div class="card-header bg-primary text-white">
                <h6 class="mb-0">Action Taken</h6>
              </div>
              <div class="card-body">

                <div id="msg" class="text-danger mb-3"></div>

                <form name="action_taken" method="post" enctype="multipart/form-data" onsubmit="return verification();">
                  <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>" />
                  <input type="hidden" name="complaint_id" value="<?= $_POST['complaint_id'] ?>">
                  <input type="hidden" name="type" value="<?= $_POST['type'] ?? '' ?>">
                  <input type="hidden" name="member_id" value="<?= $_POST['member_id'] ?>">

                  <div class="row">

                    <!-- ================= LEFT COLUMN ================= -->
                    <div class="col-md-6">

                      <div class="mb-3">
                        <label class="form-label">Action marked by</label>
                        <input type="text" readonly class="form-control" value="<?= getName($_POST['member_id']) ?>">
                      </div>

                 

                      <div style="position: relative; margin-bottom: 36px;">
                        <label class="form-label">Diagnosis/Observations <span class="text-danger">*</span></label>
                        <textarea class="form-control" name="diagnosis" id="diagnosis" rows="3" maxlength="3000" onkeyup="countDX()"
                          style="font-size:15px;padding:10px 12px;border:1px solid #b5c7e7;border-radius:6px;line-height:1.5;"></textarea>
                        <div id="diagnosis_error"
                          style="color:#d9534f; font-size:14px; position:absolute; bottom:-24px; left:0;"></div>
                        <div id="dx_count"
                          style="color:#555; font-size:14px; position:absolute; bottom:-24px; right:0;"></div>
                      </div>


                      <div class="mb-3">
                        <label class="form-label">Work done by</label>
                        <input type="text" class="form-control" name="work_done_by" id="work_done_by" maxlength="99">
                      </div>

                       <div class="mb-3">
                        <label class="form-label">Action Plan</label>
                        <textarea class="form-control" name="action_plan" id="action_plan" rows="3" maxlength="199"></textarea>
                      </div>

                      <!-- Vendor Interaction -->
                      <div class="vendor-box mb-3">

                        <div class="vendor-header">
                          🤝 Vendor Interaction
                        </div>

                        <div class="vendor-toggle">
                          <label class="vendor-radio">
                            <input type="radio" name="vendor_interaction" value="Yes"
                              onclick="display_vendor_interaction('Yes')">
                            <span>Yes</span>
                          </label>

                          <label class="vendor-radio">
                            <input type="radio" name="vendor_interaction" value="No"
                              onclick="display_vendor_interaction('No')" checked>
                            <span>No</span>
                          </label>
                        </div>

                        <div class="form-group-compact">
                          <label>Vendor Name <span class="text-danger">*</span></label>
                          <input type="text" class="form-control" id="vendor_select" name="vendor_select"
                            placeholder="Enter Vendor Name" maxlength="80">
                          <div id="alt_vendor_select" class="text-danger small"></div>
                        </div>

                        <div class="form-group-compact">
                          <label>Vendor Contact <span class="text-danger">*</span></label>
                          <textarea class="form-control" name="vendor_contact" id="vendor_contact" rows="2"
                            placeholder="Phone / Email"></textarea>
                          <div id="alt_vendor_contact" class="text-danger small"></div>
                        </div>

                        <div class="form-group-compact">
                          <label>Interaction Quality <span class="text-danger">*</span></label>
                          <div class="vendor-quality">
                            <?php
                            $options = ["Very good", "Good", "Satisfactory", "Not Satisfactory"];
                            foreach ($options as $option) {
                              echo '
                              <label class="quality-pill">
                                <input type="radio" name="interaction" value="'.$option.'">
                                <span>'.$option.'</span>
                              </label>';
                            }
                            ?>
                          </div>
                          <div id="alt_interaction" class="text-danger small"></div>
                        </div>

                        <div class="form-group-compact">
                          <label>Comments <span class="text-danger">*</span></label>
                          <textarea class="form-control" name="feedback" id="feedback" rows="2" maxlength="499"
                            placeholder="Short feedback"></textarea>
                          <div id="alt_feedback" class="text-danger small"></div>
                        </div>

                      </div>
                    </div>

                    <!-- ================= RIGHT COLUMN ================= -->
                    <div class="col-md-6">

                      <div class="mb-3">
                        <label class="form-label">Working Team</label>
                        <input type="text" class="form-control" name="working_team" id="working_team" maxlength="29">
                      </div>

                       <div style="position: relative; margin-bottom: 36px;">
                          <label class="form-label">Action Taken <span class="text-danger">*</span></label>
                          <textarea class="form-control" name="action_taken" id="action_taken" rows="3" onkeyup="countAT()"
                            style="font-size:15px;padding:10px 12px;border:1px solid #b5c7e7;border-radius:6px;line-height:1.5;"></textarea>
                          <div id="action_taken_error"
                            style="color:#d9534f; font-size:14px; position:absolute; bottom:-24px; left:0;margin-top:5px"></div>
                          <div id="char_count"
                            style="color:#555; font-size:14px; position:absolute; bottom:-24px; right:0;"></div>
                        </div>

                      <div class="mb-3">
                        <label class="form-label">Spare Parts</label>
                        <input type="text" class="form-control" name="spare_parts" id="spare_parts" maxlength="149">
                      </div>

                      <div class="mb-3">
                        <label class="form-label">Cost of Spare Parts</label>
                        <input type="text" class="form-control" name="cost_spare_parts" id="cost_spare_parts" maxlength="29">
                      </div>

                      <div class="mb-3">
                        <label class="form-label">Procurement Time of Spares</label>
                        <input type="text" class="form-control" name="procurement_time_spares" id="procurement_time_spares" maxlength="99">
                      </div>

                      <div class="mb-3">
                        <label class="form-label">Expected Completion Date</label>
                        <input type="text" class="form-control" name="expected_completion_date" id="expected_completion_date" placeholder="dd-mm-yyyy">
                      </div>

                     

                      <div class="mb-3">
                        <label class="form-label">Action Item Owner</label>
                        <input type="text" class="form-control" name="action_item_owner" id="action_item_owner" maxlength="99">
                      </div>

                      <div class="mb-3">
                        <label class="form-label">Upload File</label>
                        <input type="file" class="form-control" name="file" id="file">
                        <div id="alt_file" class="text-danger small"></div>
                      </div>

                      <div class="mb-3">
                        <label class="form-label">Additional Comments</label>
                        <textarea class="form-control" name="comments" id="comments" rows="3" maxlength="499"></textarea>
                      </div>

                      <div class="mb-2 p-2 rounded-3 shadow-sm status-box">
                        <label class="form-label fw-bold small mb-1 text-uppercase text-muted">
                          Complaint Status
                        </label>

                        <select name="status" id="complaint_status"
                          class="form-select form-select-sm fw-bold status-select" required>
                          <option value="">-- Select --</option>
                          <option value="0" <?= $complaint['status']==0 ? "selected" : "" ?>>🕒 Pending</option>
                          <option value="1" <?= $complaint['status']==1 ? "selected" : "" ?>>⚙️ In Process</option>
                          <option value="2" <?= $complaint['status']==2 ? "selected" : "" ?>>✅ Closed</option>
                          <option value="3" <?= $complaint['status']==3 ? "selected" : "" ?>>⏸️ On Hold</option>
                        </select>
                      </div>

                      <input type="hidden" name="c_date" id="c_date">

                      <div class="text-end">
                        <input type="submit" class="btn btn-primary" name="submit" value="Submit">
                      </div>

                    </div>
                  </div>
                </form>
              </div>
            </div>
          </div>



            <!-- Previous Actions -->
            <div class="col-md-6 mb-3">
              <div class="card h-100 shadow-sm">
                <div class="card-header bg-secondary text-white">
                  <h6 class="mb-0">Previous Actions</h6>
                </div>
                <div class="card-body">
                  <?php if (isset($_POST['complaint_id'])): ?>
                    <?php
                    $details = trouble_track(mysqli_real_escape_string($db_slot, $_POST['complaint_id']), '');
                    ?>

                    <?php if (count($details) > 0): ?>
                      <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="mb-0">Previous Action Taken</h6>
                        <a href="#" onclick="return view(<?= $_POST['complaint_id'] ?>, <?= $_POST['type'] ?>);" class="btn btn-sm btn-outline-primary">View</a>
                      </div>

                     <div class="previous-actions-list">

                        <?php for ($i = 0; $i < count($details) && $i < 2; $i++): ?>
                          <div class="card mb-3 border-0 shadow-sm">
                            <div class="card-body">

                              <div class="d-flex justify-content-between align-items-center mb-2">
                                <div>
                                  <span class="badge bg-secondary">
                                    <?= display_timestamp($details[$i]['timestamp']) ?>
                                  </span>
                                  <small class="text-muted ms-2">
                                    by <?= getName($details[$i]['status_mark_by']) ?>
                                  </small>
                                </div>
                              </div>
                                <p class="mb-2">
                                  <strong>🩺 Diagnosis:</strong><br>
                                  <span class="text-dark">
                                    <?php
                                      $diag = $details[$i]['diagnosis'] ?? '';

                                      // 1️⃣ Remove literal \r\n, \n, \r
                                      $diag = str_replace(['\\r\\n', '\\n', '\\r'], "\n", $diag);

                                      // 2️⃣ Remove escaped quotes and slashes
                                      $diag = str_replace(["\\'", '\\"', '\\\\'], ["'", '"', "\\"], $diag);

                                      // 3️⃣ Final safety
                                      $diag = stripslashes($diag);

                                      echo nl2br(htmlspecialchars_decode($diag));
                                    ?>
                                  </span>
                                </p>
                               <p class="mb-2">
                                <strong>⚙️ Action Taken:</strong><br>
                                <span class="text-dark">
                                  <?php
                                    $act = $details[$i]['action_taken'] ?? '';

                                    // 1️⃣ Remove literal \r\n, \n, \r
                                    $act = str_replace(['\\r\\n', '\\n', '\\r'], "\n", $act);

                                    // 2️⃣ Remove escaped quotes and slashes
                                    $act = str_replace(["\\'", '\\"', '\\\\'], ["'", '"', "\\"], $act);

                                    // 3️⃣ Final safety
                                    $act = stripslashes($act);

                                    echo nl2br(htmlspecialchars_decode($act));
                                  ?>
                                </span>
                              </p>
                              <?php if (!empty($details[$i]['action_plan'])): ?>
                                <p class="mb-2">
                                  <strong>📋 Action Plan:</strong><br>
                                  <span class="text-dark"><?= nl2br(htmlspecialchars_decode($details[$i]['action_plan'])) ?></span>
                                </p>
                              <?php endif; ?>

                             <?php if (!empty($details[$i]['expected_completion_date'])): ?>
                                <p class="mb-2">
                                  <strong>📅 Expected Completion:</strong><br>
                                  <span class="text-primary fw-semibold">
                                    <?= date("d-m-Y", strtotime($details[$i]['expected_completion_date'])) ?>
                                  </span>
                                </p>
                              <?php endif; ?>


                              <?php if (!empty($details[$i]['comments'])): ?>
                                <p class="mb-0">
                                  <strong>💬 Comments:</strong><br>
                                  <span class="text-muted"><?= nl2br(htmlspecialchars_decode($details[$i]['comments'])) ?></span>
                                </p>
                              <?php endif; ?>

                            </div>
                          </div>
                        <?php endfor; ?>

                      </div>
                    <?php else: ?>
                      <p class="text-muted">No previous actions found.</p>
                    <?php endif; ?>
                  <?php else: ?>
                    <p class="text-danger">Invalid complaint ID.</p>
                  <?php endif; ?>
                </div>
              </div>
            </div>

          </div>

        </div>
      </div>
    </div>
  </div>

  <div id=commentdialog title="Add Comment" style="display: none; font-size: 11px;"></div>
  <div id=dialog title="View tracking" style="display: none; font-size: 11px;"></div>

</div>

<script>
  $('#expected_completion_date').datepicker({
    dateFormat: 'dd-mm-yy',
    minDate: 0 // disables all dates before today
  });


  function view(complaint_id, type) {
    $('#dialog').dialog({
      height: 600,
      width: "65%",
      modal: true
    });
    $('#dialog').load("view_tracks.php?complaint_id=" + complaint_id + "&type=" + type);
    return false;
  }

  // function addComment(complaint_id) {
  //   window.open("add_comments.php?complaint_id=" + complaint_id, "Add Comment", "width=400, height=300");
  // }

  // $('#expected_completion_date').datetimepicker({
  //   timepicker: false,
  //   format: 'Y/m/d',
  //   minDate: '-1970/01/01',
  // });


  var $input = $('#vendor_name');
  var $select = $('select');
  $select.change(function() {
    modify();
  })

  function modify() {
    $input.val($select.val());
  }

  $input.on('click', function() {
    $(this).select()
  }).on('blur', function() {})
  modify();

  $(document).ready(function() {

    // Disable all vendor interaction-related inputs initially
    $('#vendor_select').prop('disabled', true);
    $('#vendor_name').prop('disabled', true);
    $('#vendor_contact').prop('disabled', true);
    $('#feedback').prop('disabled', true);
    $("input[name='interaction']").prop('disabled', true);

    // ✅ Force default state = No (sync UI + logic)
    display_vendor_interaction('No');
  });


  function display_vendor_interaction(status) {
    const enable = (status === 'Yes');

    $('#vendor_select').prop('disabled', !enable);
    $('#vendor_name').prop('disabled', !enable);
    $('#vendor_contact').prop('disabled', !enable);
    $('#feedback').prop('disabled', !enable);
    $("input[name='interaction']").prop('disabled', !enable);
  }


	// commented by shahid :- Oct 30 2025
	/*  function verification() {
    let msg = "";
    let vendorInteraction = document.querySelector('input[name="vendor_interaction"]:checked')?.value || "";
    let interactionQuality = document.querySelector('input[name="interaction"]:checked')?.value || "";

    // Collect all form values
    let fields = [
      "working_team", "diagnosis", "action_taken", "work_done_by",
      "spare_parts", "cost_spare_parts", "procurement_time_spares",
      "comments", "action_plan", "action_item_owner"
    ];
    let empty = fields.every(id => document.getElementById(id).value.trim() === "");

    let vendorName = document.getElementById("vendor_select")?.value.trim() || "";
    let vendorContact = document.getElementById("vendor_contact")?.value.trim();
    let feedback = document.getElementById("feedback")?.value.trim();

    // If everything empty
    if (empty && !vendorInteraction && vendorName === "" && vendorContact === "" && feedback === "" && !interactionQuality) {
      msg = "Cannot submit a blank form!";
    }

    // File check
    let fileVal = document.getElementById("file").value;
    if (fileVal && fileVal.split('.').pop().toLowerCase() === 'exe') {
      msg = "Cannot upload .exe file.";
      action_item_owner
    }

    // Vendor required fields if Yes
    if (vendorInteraction === 'Yes') {
      if (!vendorName) msg = "Please select or enter vendor name.";
      if (!vendorContact) msg = "Please fill vendor contact.";
      if (!interactionQuality) msg = "Please select interaction quality.";
      if (!feedback) msg = "Please enter feedback.";
    }

    if (msg) {
      document.getElementById("msg").innerHTML = msg;
      return false;
    }
    return true;
  } */

  function verification() {
    
    // 1. CLEAR ALL MESSAGES at the start. Use the IDs you created.
    document.getElementById("msg").innerHTML = "";
    document.getElementById("alt_file").innerHTML = "";
    document.getElementById("alt_vendor_select").innerHTML = "";
    document.getElementById("alt_vendor_contact").innerHTML = "";
    document.getElementById("alt_interaction").innerHTML = "";
    document.getElementById("alt_feedback").innerHTML = "";



    let vendorInteraction = document.querySelector('input[name="vendor_interaction"]:checked')?.value || "";
    let interactionQuality = document.querySelector('input[name="interaction"]:checked')?.value || "";

    let fields = [
      "working_team", "diagnosis", "action_taken", "work_done_by",
      "spare_parts", "cost_spare_parts", "procurement_time_spares",
      "comments", "action_plan", "action_item_owner"
    ];

    let empty = fields.every(id => document.getElementById(id).value.trim() === "");
    let vendorName = document.getElementById("vendor_select")?.value.trim() || "";
    let vendorContact = document.getElementById("vendor_contact")?.value.trim() || "";
    let feedback = document.getElementById("feedback")?.value.trim() || ""; 

    if (empty && !vendorInteraction && vendorName === "" && vendorContact === "" && feedback === "" && !interactionQuality) {
      document.getElementById("msg").innerHTML = "Cannot submit a blank form!"; // Use generic top message div
      document.getElementById("working_team").focus();
      return false; // STOP execution
    }

    let fileVal = document.getElementById("file").value;
    if (fileVal && fileVal.split('.').pop().toLowerCase() === 'exe') {
      document.getElementById("alt_file").innerHTML = "Cannot upload .exe file.";
      document.getElementById("file").focus();
      return false; // STOP execution
    }
// Mandatory: Diagnosis minimum 100 chars
    document.getElementById("diagnosis_error").innerHTML = "";
let dx = document.getElementById("diagnosis").value.trim();
if (dx.length === 0) {
    document.getElementById("diagnosis_error").innerHTML = "Diagnosis is required.";
    document.getElementById("diagnosis").focus();
    return false;
}
if (dx.length < 100) {
    document.getElementById("diagnosis_error").innerHTML = "Minimum 100 characters required.";
    document.getElementById("diagnosis").focus();
    return false;
}


    // your same validation pattern
    document.getElementById("action_taken_error").innerHTML = "";

let at = document.getElementById("action_taken").value.trim();
if (at.length === 0) {
    document.getElementById("action_taken_error").innerHTML = "Action Taken is required.";
    document.getElementById("action_taken").focus();
    return false;
}

if (at.length < 100) {
    document.getElementById("action_taken_error").innerHTML = "Minimum 100 characters required.";
    document.getElementById("action_taken").focus();
    return false;
}
    // Vendor required fields if Yes
    if (vendorInteraction === 'Yes') {
      if (!vendorName) {
        document.getElementById("alt_vendor_select").innerHTML = "Please enter vendor name.";
        document.getElementById("vendor_select").focus();
        return false; // STOP execution
      }
      if (!vendorContact) {
        document.getElementById("alt_vendor_contact").innerHTML = "Please enter vendor contact.";
        document.getElementById("vendor_contact").focus();
        return false; // STOP execution
      }

      if (!interactionQuality) {
        document.getElementById("alt_interaction").innerHTML = "Please select interaction quality.";
        // FIX: Cannot focus a non-existent ID. Focus the first radio input element with name="interaction"
        document.querySelector('input[name="interaction"]').focus();
        return false; // STOP execution
      }

      if (!feedback) {
        document.getElementById("alt_feedback").innerHTML = "Please add comment.";
        document.getElementById("feedback").focus();
        return false; // STOP execution
      }
    }

    // ✅ Status must be selected
    let status = document.getElementById("complaint_status").value;

    if (status === "") {
        Swal.fire({
          icon: 'warning',
          title: 'Status Required',
          text: 'Please select complaint status.'
        });

        document.getElementById("complaint_status").focus();
        return false;
    }

    // Final successful exit
    return true;
  }

function countDX() {
    let dx = document.getElementById("diagnosis").value.trim();
    let len = dx.length;

    if (len === 0) {
        document.getElementById("dx_count").innerHTML = "";
    } else {
        document.getElementById("dx_count").innerHTML = "(" + len + "/100)";
    }
}

function countAT() {
    let at = document.getElementById("action_taken").value.trim();
    let len = at.length;

    if (len === 0) {
        document.getElementById("char_count").innerHTML = "";
    } else {
        document.getElementById("char_count").innerHTML = "(" + len + "/100)";
    }
}

// =======================================
// ✅ Prevent Status Change Until Mandatory Fields Filled
// =======================================
const statusSelect = document.getElementById("complaint_status");
let lastStatus = statusSelect.value; // store initial

statusSelect.addEventListener("change", async function () {
  const newStatus = this.value;

  let diagnosis = document.getElementById("diagnosis").value.trim();
  let actionTaken = document.getElementById("action_taken").value.trim();

  // ❌ Block if mandatory fields missing
  if (diagnosis.length < 100) {
    await Swal.fire({
      icon: 'warning',
      title: 'Diagnosis Required',
      text: 'Please fill Diagnosis (minimum 100 characters) before changing status.'
    });
    this.value = lastStatus;
    document.getElementById("diagnosis").focus();
    return;
  }

  if (actionTaken.length < 100) {
    await Swal.fire({
      icon: 'warning',
      title: 'Action Taken Required',
      text: 'Please fill Action Taken (minimum 100 characters) before changing status.'
    });
    this.value = lastStatus;
    document.getElementById("action_taken").focus();
    return;
  }

  // ✅ Confirmation dialog
  const result = await Swal.fire({
    title: 'Confirm Status Change',
    text: 'Are you sure you want to change the complaint status?',
    icon: 'question',
    showCancelButton: true,
    confirmButtonText: 'Yes, change it',
    cancelButtonText: 'Cancel'
  });

  if (!result.isConfirmed) {
    this.value = lastStatus;
    return;
  }

  // ✅ Auto set close date if Closed
  if (newStatus == "2") {
    let now = new Date();
    let formatted =
      now.getFullYear() + "-" +
      String(now.getMonth() + 1).padStart(2, '0') + "-" +
      String(now.getDate()).padStart(2, '0') + " " +
      String(now.getHours()).padStart(2, '0') + ":" +
      String(now.getMinutes()).padStart(2, '0') + ":" +
      String(now.getSeconds()).padStart(2, '0');

    document.getElementById("c_date").value = formatted;
  } else {
    document.getElementById("c_date").value = "";
  }

  lastStatus = newStatus; // update stored value
});




function b64encode(str) {
  return btoa(unescape(encodeURIComponent(str || "")));
}
/**
 * Robust Base64 Decoding
 * Handles UTF-8 strings and avoids firewall inspection
 */
function b64($v) {
    if (empty($v)) return '';
    // Decode the base64 string
    $decoded = base64_decode($v, true);
    if ($decoded === false) {
        // If it wasn't actually base64, return the original (fallback)
        return $v; 
    }
    return $decoded;
}


document.addEventListener("DOMContentLoaded", function () {
  const form = document.querySelector('form[name="action_taken"]');
  if (!form) return;

  form.addEventListener("submit", async function (e) {
    e.preventDefault();

    // Run your validation first
    if (!verification()) return;

    const formData = new FormData();
    
    // 1. Core Logic Flags
    formData.append("submit", "1");
    formData.append("csrf_token", document.querySelector('input[name="csrf_token"]').value);
    formData.append("complaint_id", document.querySelector('input[name="complaint_id"]').value);
    formData.append("member_id", document.querySelector('input[name="member_id"]').value);

    formData.append("status", document.getElementById("complaint_status").value);
    formData.append("c_date", document.getElementById("c_date").value);


    // 2. Encode ALL Text Fields (This hides them from the IITB Firewall)
    const textFields = [
      "working_team", "diagnosis", "action_taken", "work_done_by",
      "spare_parts", "cost_spare_parts", "procurement_time_spares",
      "comments",

      "expected_completion_date",   // ✅ ADD THIS LINE

      "action_plan", "action_item_owner"
    ];


    textFields.forEach(id => {
      const el = document.getElementById(id);
      if (el) {
        // btoa(unescape(encodeURIComponent())) handles special characters safely
        const encodedVal = btoa(unescape(encodeURIComponent(el.value)));
        formData.append(id, encodedVal);
      }
    });

    // 3. Handle Vendor Interaction (Logic check)
    const vendorActive = document.querySelector('input[name="vendor_interaction"]:checked')?.value;
    if (vendorActive === "Yes") {
        formData.append("vendor_interaction", "Yes");
        formData.append("vendor_select", btoa(unescape(encodeURIComponent(document.getElementById("vendor_select").value))));
        formData.append("vendor_contact", btoa(unescape(encodeURIComponent(document.getElementById("vendor_contact").value))));
        formData.append("feedback", btoa(unescape(encodeURIComponent(document.getElementById("feedback").value))));
        
        const quality = document.querySelector('input[name="interaction"]:checked')?.value;
        if (quality) formData.append("interaction", btoa(unescape(encodeURIComponent(quality))));
    }

    // 4. File Handling
    const fileInput = document.getElementById("file");
    if (fileInput.files.length > 0) {
      formData.append("file", fileInput.files[0]);
    }

    try {
      // Use current path to avoid "Object Not Found" redirection issues
      const response = await fetch("action_taken.php", {
        method: "POST",
        body: formData
      });

      if (!response.ok) throw new Error("Network response was not ok (Firewall Block)");

      const result = await response.json();
      if (result.status === "success") {
        Swal.fire({
        icon: 'success',
        title: 'Saved!',
        text: 'Action saved successfully.',
        timer: 1500,
        showConfirmButton: false
      }).then(() => {
        location.reload();
      });
      } else {
        Swal.fire({
          icon: 'error',
          title: 'Error',
          text: result.message
        });
      }
    } catch (err) {
      console.error(err);
      Swal.fire({
        icon: 'error',
        title: 'Submission Failed',
        text: 'Request was blocked or failed. Please try again.'
      });

    }
  });
});

</script>
<script>
function toggleComplaintInfo() {
  const panel = document.getElementById('complaintInfoPanel');
  const btn   = document.getElementById('complaintToggleBtn');

  const isOpen = panel.classList.contains('open');

  if (isOpen) {
    panel.classList.remove('open');
    btn.innerHTML = '👁 Show';
  } else {
    panel.classList.add('open');
    btn.innerHTML = '✖ Hide';
  }
}
</script>


<?php include("../includes/footer.php"); ?>

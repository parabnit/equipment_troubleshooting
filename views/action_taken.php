<?php
// views/action_taken.php

require_once("../includes/auth_check.php");
require_once("../includes/header.php");
require_once("../config/connect.php");
require_once("../includes/common.php");

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (empty($_SESSION['login'])) {
  header("Location: ../logout.php");
  exit;
}

// Validate POST params
if (!isset($_POST['complaint_id'], $_POST['member_id'], $_POST['type'])) {
  header("Location: complaint.php");
  exit;
}


$complaint_id = mysqli_real_escape_string($db_slot, $_POST['complaint_id']);
$member_id    = mysqli_real_escape_string($db_slot, $_POST['member_id']);
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
  9 => 'Inventory'
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
elseif (in_array($_POST['type'], [5, 6, 7, 8, 9])) {

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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit'])) {
  function esc($db, $key)
  {
    return mysqli_real_escape_string($db, trim($_POST[$key] ?? ''));
  }

  $complaint_id = esc($db_slot, 'complaint_id');
  $member_id    = esc($db_slot, 'member_id');

  // Vendor fields only if Yes
  $vendor_name = $vendor_contact = $vendor_interaction = $vendor_comments = '';
  if ((trim($_POST['vendor_interaction'] ?? '') === 'Yes')) {
    $vendor_name        = esc($db_slot, 'vendor_name') ?: esc($db_slot, 'vendor_select');
    $vendor_contact     = esc($db_slot, 'vendor_contact');
    $vendor_interaction = esc($db_slot, 'interaction');
    $vendor_comments    = esc($db_slot, 'feedback');
  }

  // Handle file upload
  $uploaded_file = '';
  if (!empty($_FILES['file']['name'])) {
    $nos = count(trouble_track($complaint_id, ''));
    $ext = strtolower(pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION));
    $filename = "uploads/{$complaint_id}_{$nos}.{$ext}";
    if (move_uploaded_file($_FILES['file']['tmp_name'], $filename)) {
      $uploaded_file = $filename;
    }
  }

  try {
    $message = insert_trouble_track(
      $complaint_id,
      $member_id,
      esc($db_slot, 'working_team'),
      esc($db_slot, 'diagnosis'),
      esc($db_slot, 'action_taken'),
      esc($db_slot, 'work_done_by'),
      esc($db_slot, 'spare_parts'),
      esc($db_slot, 'cost_spare_parts'),
      esc($db_slot, 'procurement_time_spares'),
      esc($db_slot, 'comments'),
      esc($db_slot, 'expected_completion_date'),
      esc($db_slot, 'action_plan'),
      $vendor_name,
      $vendor_contact,
      $vendor_interaction,
      $vendor_comments,
      esc($db_slot, 'action_item_owner'),
      $uploaded_file
    );

    // ✅ If success → redirect
    $type = esc($db_slot, 'type');
    header("Location: all_complaints.php?type={$type}&status=pending&importance=all");
    exit;
  } catch (Exception $e) {
    error_log("DB Insert Error " . $e->getMessage());
   // $message = "Special characters are not allowed. Please remove them and try again.";
   $message = "DB Insert Error " . $e->getMessage();
    // $message = "Special characters are not allowed. Please remove them and try again.";
 echo "<script>alert(" . json_encode($message) . ");</script>";
  }
}

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


</style>


<div class="container mt-4">
  <div class="row">
    <div class="col-md-2">
      <?php include("../includes/menu.php"); ?>
    </div>

    <div class="col-md-10">

      <!-- Complaint Info Header -->
      <div class="card mb-3 shadow-sm">
        <div class="card-header bg-info text-white">
          <h5 class="mb-0">Complaint Information</h5>
        </div>
        <div class="card-body">
          <!-- Complaint Meta Info -->
          <!-- ===== Ultra Colorful Complaint Info ===== -->
          <div class="row g-2 mb-3">

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
          <div class="desc-box mb-4">
            <div class="fw-bold mb-2">📝 Complaint Description</div>
            <?= nl2br(htmlspecialchars_decode($shortDesc)) ?>
          </div>

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


                  <form action="" name="action_taken" method="post" enctype="multipart/form-data" onsubmit="return verification();">
                    <input type="hidden" name="complaint_id" value="<?= $_POST['complaint_id'] ?>">
                    <input type="hidden" name="type" value="<?= $_POST['type'] ?? '' ?>">
                    <input type="hidden" name="member_id" value="<?= $_POST['member_id'] ?>">

                    <div class="mb-3">
                      <label class="form-label">Action marked by</label>
                      <input type="text" readonly class="form-control" value="<?= getName($_POST['member_id']) ?>">
                    </div>

                    <div class="mb-3">
                      <label class="form-label">Working Team</label>
                      <input type="text" class="form-control" name="working_team" id="working_team" maxlength="29">

                    </div>

<div style="position: relative; margin-bottom: 36px;">
                       <label class="form-label">Diagnosis/Observations <span class="text-danger">*</span></label>
                      <textarea class="form-control" name="diagnosis" id="diagnosis" rows="3" maxlength="3000" onkeyup="countDX()"
                           style="font-size:15px;padding:10px 12px;border:1px solid #b5c7e7;border-radius:6px;line-height:1.5;"></textarea>
                          <div id="diagnosis_error"
                             style="color:#d9534f; font-size:14px; position:absolute; bottom:-24px; left:0;">
                            </div>
                           <div id="dx_count"
                               style="color:#555; font-size:14px; position:absolute; bottom:-24px; right:0;">
                              </div>
                           </div>
  <div  style="position: relative; margin-bottom: 36px;">
                         <label class="form-label">Action Taken <span class="text-danger">*</span></label>
                       <textarea class="form-control" name="action_taken" id="action_taken" rows="3" onkeyup="countAT()"
                          style="font-size:15px;padding:10px 12px;border:1px solid #b5c7e7;border-radius:6px;line-height:1.5;"></textarea>
                     <div id="action_taken_error"
                             style="color:#d9534f; font-size:14px; position:absolute; bottom:-24px; left:0;margin-top:5px">
                       </div>
                            <div id="char_count"
                                    style="color:#555; font-size:14px; position:absolute; bottom:-24px; right:0;">
                                </div>
                              </div>
                    <div class="mb-3">
                      <label class="form-label">Work done by</label>
                      <input type="text" class="form-control" name="work_done_by" id="work_done_by" maxlength="99">
                    </div>

                   <!-- ===== Vendor Interaction (Compact Premium UI) ===== -->
                      <div class="vendor-box mb-3">

                        <div class="vendor-header">
                          🤝 Vendor Interaction
                        </div>

                        <!-- Yes / No -->
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

                        <!-- Vendor Name -->
                        <div class="form-group-compact">
                          <label>Vendor Name <span class="text-danger">*</span></label>
                          <input type="text"
                                class="form-control"
                                id="vendor_select"
                                name="vendor_select"
                                placeholder="Enter Vendor Name"
                                maxlength="80">
                          <div id="alt_vendor_select" class="text-danger small"></div>
                        </div>

                        <!-- Vendor Contact -->
                        <div class="form-group-compact">
                          <label>Vendor Contact <span class="text-danger">*</span></label>
                          <textarea class="form-control"
                                    name="vendor_contact"
                                    id="vendor_contact"
                                    rows="2"
                                    placeholder="Phone / Email"></textarea>
                          <div id="alt_vendor_contact" class="text-danger small"></div>
                        </div>

                        <!-- Interaction Quality -->
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

                        <!-- Vendor Comments -->
                        <div class="form-group-compact">
                          <label>Comments <span class="text-danger">*</span></label>
                          <textarea class="form-control"
                                    name="feedback"
                                    id="feedback"
                                    rows="2"
                                    maxlength="499"
                                    placeholder="Short feedback"></textarea>
                          <div id="alt_feedback" class="text-danger small"></div>
                        </div>

                      </div>
                      <!-- ===== End Vendor Interaction ===== -->


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
                      <!-- <input type="date" class="form-control" name="expected_completion_date" id="expected_completion_date" lang="en-GB"> -->
                    </div>

                    <div class="mb-3">
                      <label class="form-label">Action Plan</label>
                      <textarea class="form-control" name="action_plan" id="action_plan" rows="3" maxlength="199"></textarea>
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

                    <div class="text-end">
                      <input type="submit" class="btn btn-primary" name="submit" value="Submit">
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
                                <span class="text-dark"><?= nl2br(htmlspecialchars_decode($details[$i]['diagnosis'])) ?></span>
                              </p>

                              <p class="mb-2">
                                <strong>⚙️ Action Taken:</strong><br>
                                <span class="text-dark"><?= nl2br(htmlspecialchars_decode($details[$i]['action_taken'])) ?></span>
                              </p>

                              <?php if (!empty($details[$i]['action_plan'])): ?>
                                <p class="mb-2">
                                  <strong>📋 Action Plan:</strong><br>
                                  <span class="text-dark"><?= nl2br(htmlspecialchars_decode($details[$i]['action_plan'])) ?></span>
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

</script>

<?php include("../includes/footer.php"); ?>

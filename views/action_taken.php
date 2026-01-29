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
          <div class="small mb-3">
            <div>
              Complaint by: <strong><?= $memberName ?></strong> |
              Tool: <strong><?= $toolName ?></strong> |
              Time: <strong><?= $timeOfComplaint ?></strong>|
              Complaint Status: <strong><?= $status_text ?></strong>
              <?php if ($allocatedTo): ?>
                | <span class="badge bg-warning text-dark">Allocated To: <?= $allocatedTo ?></span>
              <?php endif; ?>
            </div>
          </div>

          <!-- Complaint Description -->
          <div style="word-wrap:break-word;" class="mb-4">
            <strong>Complaint Description:</strong>
            <p class="mb-0"><?= nl2br(htmlspecialchars_decode($shortDesc)) ?></p>
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

                    <div class="mb-3">
                      <label class="form-label">Vendor Interaction</label><br>
                      <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="vendor_interaction" value="Yes" onclick="display_vendor_interaction('Yes')">
                        <label class="form-check-label">Yes</label>
                      </div>
                      <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="vendor_interaction" value="No" onclick="display_vendor_interaction('No')">
                        <label class="form-check-label">No</label>
                      </div>
                    </div>

                  

		<div class="mb-3">
                      <label class="form-label">Vendor Name <span class="text-danger">*</span> </label>

                      <input
                        type="text"
                        class="form-control"
                        id="vendor_select"
                        name="vendor_select"
                        placeholder="Enter Vendor Name"
                        maxlength="80" />
                      <div id="alt_vendor_select" class="text-danger small"></div>
                    </div>

                    <div class="mb-3">
                      <label class="form-label">Vendor Contact <span class="text-danger">*</span>  </label>
                      <textarea class="form-control" name="vendor_contact" id="vendor_contact" rows="3"></textarea>
                        <div id="alt_vendor_contact" class="text-danger small"></div>
		    </div>

                    <div class="mb-3">
                      <label class="form-label">Interaction Quality <span class="text-danger">*</span>  </label>
                      <?php
                      $options = ["Very good", "Good", "Satisfactory", "Not Satisfactory"];
                      foreach ($options as $option) {
                        echo '<div class="form-check">
                          <input class="form-check-input" type="radio" name="interaction" value="' . $option . '">
                          <label class="form-check-label">' . $option . '</label>
                        </div>';
                      }
                      ?>
			<div id="alt_interaction" class="text-danger small"></div>
                    </div>

                    <div class="mb-3">
                      <label class="form-label">Comments <span class="text-danger">*</span>  </label>
                      <textarea class="form-control" name="feedback" id="feedback" rows="3" maxlength="499"></textarea>
                    <div id="alt_feedback" class="text-danger small"></div>
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

                      <div class="table-responsive">
                        <table class="table table-bordered table-sm table-hover align-middle">
                          <thead class="table-light">
                            <tr>
                              <th width="20%">Date</th>
                              <th width="20%">Diagnosis</th>
                              <th width="20%">Action Taken</th>
                              <th width="20%">Action Plan</th>
                              <th width="20%">Comments</th>
                            </tr>
                          </thead>
                          <tbody>
                            <?php for ($i = 0; $i < count($details) && $i < 2; $i++): ?>
                              <tr>
                                <td><?= display_timestamp($details[$i]['timestamp']) ?><br><small><?= getName($details[$i]['status_mark_by']) ?></small></td>
                                <td><?= shortDesc($details[$i]['diagnosis']) ?></td>
                                <td><?= shortDesc($details[$i]['action_taken']) ?></td>
                                <td><?= shortDesc($details[$i]['action_plan']) ?></td>
                                <td><?= shortDesc($details[$i]['comments']) ?></td>
                              </tr>
                            <?php endfor; ?>
                          </tbody>
                        </table>
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

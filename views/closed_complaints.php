<?php
// Display errors for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
include("../includes/auth_check.php");
include("../includes/header.php");
include("../config/connect.php");
include("../includes/common.php");

if (empty($_SESSION['login'])) {
    header("Location: ../logout.php");
    exit;
}

$type        = $_GET['type'] ?? 1;
$member_id   = (int)$_SESSION['memberid'];
$tools_name  = $_GET['tools_name'] ?? '';
$importance  = $_GET['importance'] ?? 'all';

$tabledata = ($importance === 'critical') ? 1 : 0;

/* Role check */
if (
    is_LabManager($member_id) || is_AssistLabManager($member_id) || is_PI($member_id) || 
    is_EquipmentHead($member_id) || is_FacilityHead($member_id) ||
    is_SafetyHead($member_id) || is_ProcessHead($member_id) ||
    is_HRHead($member_id) || is_ITHead($member_id) ||
    is_PurchaseHead($member_id) || is_TrainingHead($member_id) ||
    is_InventoryHead($member_id)
) {
    $user_role = "head";
} 
elseif(is_FOC_member($member_id)) {
    $user_role = "viewer";  // FOC and Admins can see all
}
else {
    $user_role = "member";
}

/* Permissions */
$permission_key = check_permission($type, $member_id);

/* Fetch closed complaints */
$details = closed_complaint($type, $tools_name, $tabledata);

/* Critical filter */


/* Re-open complaint */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reopen'])) {

    $complaint_id = (int)$_POST['complaint_id'];

    // Get ORIGINAL complaint id
    $original_id = getOriginalComplaintId($complaint_id);

    // If this is a child complaint
    if ($original_id > 0) {

        // Check if original complaint is closed
        if (isComplaintClosed($original_id)) {

            // ❌ BLOCK reopen + Swal
            $_SESSION['flash_message'] = "
              <script>
                Swal.fire({
                  icon: 'error',
                  title: 'Cannot Re-Open',
                  html: 'This complaint cannot be reopened because the <b>original complaint</b> is already closed.<br><br>Please reopen the original complaint first.',
                  confirmButtonText: 'OK'
                });
              </script>
            ";

            header("Location: " . $_SERVER['REQUEST_URI']);
            exit;
        }
    }

    // ✅ Allowed → reopen
    reopen_complain($complaint_id);

    $_SESSION['flash_message'] = "
      <script>
        Swal.fire({
          icon: 'success',
          title: 'Reopened',
          text: 'Complaint reopened successfully.'
        });
      </script>
    ";

    header("Location: ".$_SERVER['REQUEST_URI']);
    exit;
}


$message = $_SESSION['flash_message'] ?? '';
unset($_SESSION['flash_message']);
?>

<!-- =================== STYLES =================== -->
<style>
#closedComplaints {
    border-collapse: separate;
    border-spacing: 0 10px;
}

#closedComplaints thead th {
    background: #0d6efd;
    color: #fff;
    border: none;
    font-weight: 600;
}

.parent-row td {
    background: #f8f9fa !important;
    vertical-align: top;
    border-top: 1px solid #dee2e6;
    border-bottom: 1px solid #dee2e6;
}

.parent-row td:first-child {
    border-left: 1px solid #dee2e6;
    border-radius: 8px 0 0 8px;
}

.parent-row td:last-child {
    border-right: 1px solid #dee2e6;
    border-radius: 0 8px 8px 0;
}

.member-name {
    font-weight: 600;
    font-size: 15px;
}

.tool-name {
    font-weight: 600;
    color: #0d6efd;
}

.desc {
    color: #444;
}

.status-box {
    font-weight: 600;
    color: #198754;
}

.track-link {
    font-weight: 600;
    text-decoration: underline;
}

.small-muted {
    font-size: 12px;
    color: #6c757d;
}

.btn-outline-danger {
    border-radius: 20px;
    padding: 2px 12px;
}

/* =========================
   Mobile Responsive Table
   ========================= */
@media (max-width: 768px) {

  #closedComplaints thead {
    display: none;
  }

  #closedComplaints,
  #closedComplaints tbody,
  #closedComplaints tr,
  #closedComplaints td {
    display: block;
    width: 100%;
  }

  #closedComplaints tr {
    margin-bottom: 14px;
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 2px 6px rgba(0,0,0,0.08);
    padding: 8px;
  }

  #closedComplaints td {
    border: none !important;
    padding: 6px 4px;
  }

  #closedComplaints td::before {
    content: attr(data-label);
    display: block;
    font-size: 11px;
    font-weight: 600;
    color: #6c757d;
    margin-bottom: 2px;
    text-transform: uppercase;
  }

  .track-link {
    display: inline-block;
    padding: 6px 0;
  }

}

@media (max-width: 768px) {

  #closedComplaints tr {
    background: #ffffff;
    border-radius: 14px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
    padding: 12px 14px;
    border-left: 4px solid #0d6efd;   /* visual accent */
  }

}
@media (max-width: 768px) {

  .member-name {
    font-size: 16px;
    font-weight: 700;
  }

  .tool-name {
    font-size: 14px;
    margin-top: 4px;
  }

}

@media (max-width: 768px) {

  #closedComplaints td {
    padding: 8px 0;
    border-bottom: 1px dashed #e9ecef !important;
  }

  #closedComplaints td:last-child {
    border-bottom: none !important;
  }

}
@media (max-width: 768px) {
  .btn.w-100 {
    border-radius: 20px;
    font-weight: 600;
  }
}



</style>

<div class="container-fluid">
<div class="row">
<div class="col-md-2"><?php include '../includes/menu.php'; ?></div>

<div class="col-md-10">

<h4 class="my-3">
<?= ['1'=>'Equipment','2'=>'Facility','3'=>'Safety','4'=>'Process'][$type] ?? '' ?>
 - Closed Complaints
</h4>

<?= $message ?>

<!-- ================= FILTER ================= -->
<!-- ================= FILTER ================= -->
<div class="filter-bar mb-3">
<form method="get" class="d-flex flex-wrap align-items-end gap-2">

    <input type="hidden" name="type" value="<?= $type ?>">

    <div style="min-width:300px">
        <label class="fw-bold">Tool & Category</label>
        <select name="tools_name" class="form-select form-select-sm">
            <option value="">-- Select Tool & Category --</option>

            <?php if (!in_array($type, [5,6,7,8,9,10])): ?>
                <option value="0" <?= $tools_name==='0'?'selected':'' ?>>Miscellaneous</option>
            <?php endif; ?>

            <?php
            if (in_array($type, [5,6,7,8,9,10])) {
                foreach (getTxtCategories($type) as $id=>$name) {
                    echo "<option value='{$id}' ".($tools_name==$id?'selected':'').">{$name}</option>";
                }
            } else {
                foreach (getTools($type) as $tool) {
                    echo "<option value='{$tool['machid']}' ".($tools_name==$tool['machid']?'selected':'').">
                        {$tool['name']}
                    </option>";
                }
            }
            ?>
        </select>
    </div>

    <button class="btn btn-sm btn-primary">Filter</button>

    <?php if ($type==1 || $type==4): ?>
        <?php if ($importance === 'critical'): ?>
            <input type="hidden" name="importance" value="all">
            <button class="btn btn-sm btn-outline-secondary">
                All Tools
            </button>
        <?php else: ?>
            <input type="hidden" name="importance" value="critical">
            <button class="btn btn-sm btn-warning">
                ⚠ Critical Tools
            </button>
        <?php endif; ?>
    <?php endif; ?>

</form>
</div>
<!-- ================= TABLE ================= -->
<div class="table-responsive">
<table id="closedComplaints" class="table table-bordered table-sm">
<thead>
<tr>
    <th>Member</th>
    <th>Tool & Cat</th>
    <th>Description</th>
    <th>Track</th>
    <th>Allocated To</th>
    <th>Status</th>
</tr>
</thead>

<tbody>
<?php foreach ($details as $d): ?>
<tr class="parent-row">

<td data-label="Member">
    <div class="member-name"><?= getName($d['member_id']) ?></div>
    <div class="small-muted"><?= display_date($d['time_of_complaint']) ?></div>
    <div class="small-muted">ID: <?= $d['complaint_id'] ?></div>
</td>

<td data-label="Tool & Category">
<?php
$d['type'] = $type;   // ensure type exists in row
$toolName = getComplaintToolName($d);
?>
<div class="tool-name"><?= $toolName ?></div>

<?php if ($tabledata): ?>
    <span class="badge bg-danger">Critical</span>
<?php endif; ?>

<?php
$ec = EC_date($d['complaint_id']);
if ($ec) {
    echo "<div class='small-muted mt-1'><b>Expected:</b> ".display_date($ec)."</div>";
}
?>
</td>

<td class="desc" data-label="Description"><?= shortDesc($d['complaint_description']) ?></td>

<td class="text-center" data-label="Track">
<a href="javascript:void(0);" 
   class="btn btn-sm btn-primary w-100 mt-1"
   onclick="viewTrack(<?= $d['complaint_id'] ?>,<?= $type ?>)">
   View Tracking
</a>

</td>

<td data-label="Allocated To"><?= $d['allocated_to'] ? getName($d['allocated_to']) : '' ?></td>

<td data-label="Status">
<span class="badge bg-success">Closed</span>
<div class="small-muted"><?= display_date($d['status_timestamp']) ?></div>
<div class="small-muted">
<?= count_day($d['time_of_complaint'],$d['status_timestamp']) ?> days
</div>

<?php if ($user_role=="head" ): ?>
<form method="post" class="mt-2">
    <input type="hidden" name="complaint_id" value="<?= $d['complaint_id'] ?>">
    <button name="reopen" class="btn btn-sm btn-outline-danger"
    onclick="return confirm('Reopen complaint?')">
        Re-Open
    </button>
</form>
<?php endif; ?>
</td>

</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>

</div>
</div>
</div>

<script>
$(function(){
   $('#closedComplaints').DataTable({
    order: [],
    stateSave: true,
    pagingType: "simple_numbers",
    pageLength: 5,     // MOBILE FRIENDLY
    autoWidth: false,
    responsive: false // we handle responsiveness
    });

});

function viewTrack(id, type){

  // Remove old dialog (IMPORTANT for mobile)
  $('#trackDialog').remove();

  const div = $('<div id="trackDialog"></div>');

  div.load(
    'view_tracks.php?complaint_id=' + id + '&type=' + type
  ).dialog({
    title: 'Complaint Tracking',
    width: '98%',
    height: window.innerHeight - 40,   // MOBILE SAFE
    modal: true,
    close: function(){
      $('#trackDialog').remove();      // CLEANUP
    }
  });

  return false;
}

</script>

<?php include '../includes/footer.php'; ?>

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
    is_LabManager($member_id) || is_AssistLabManager($member_id) ||
    is_EquipmentHead($member_id) || is_FacilityHead($member_id) ||
    is_SafetyHead($member_id) || is_ProcessHead($member_id) ||
    is_HRHead($member_id) || is_ITHead($member_id) ||
    is_PurchaseHead($member_id) || is_TrainingHead($member_id) ||
    is_InventoryHead($member_id)
) {
    $user_role = "head";
} else {
    $user_role = "member";
}

/* Permissions */
$permission_key = check_permission($type, $member_id);

/* Fetch closed complaints */
$details = closed_complaint($type, $tools_name, $tabledata);

/* Critical filter */


/* Re-open complaint */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reopen'])) {
    reopen_complain((int)$_POST['complaint_id']);
    $_SESSION['flash_message'] =
        "<div class='alert alert-success'>Complaint reopened successfully.</div>";
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

            <?php if (!in_array($type, [5,6,7,8,9])): ?>
                <option value="0" <?= $tools_name==='0'?'selected':'' ?>>Miscellaneous</option>
            <?php endif; ?>

            <?php
            if (in_array($type, [5,6,7,8,9])) {
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

<td>
    <div class="member-name"><?= getName($d['member_id']) ?></div>
    <div class="small-muted"><?= display_date($d['time_of_complaint']) ?></div>
    <div class="small-muted">ID: <?= $d['complaint_id'] ?></div>
</td>

<td>
<?php
$toolName = 'Miscellaneous';
if (in_array($type, [1,4]) && $d['machine_id']!=0) $toolName = getToolName($d['machine_id']);
elseif ($type==2 && $d['machine_id']!=0) $toolName = getToolName_facility($d['machine_id']);
elseif ($type==3 && $d['machine_id']!=0) $toolName = getToolName_safety($d['machine_id']);
elseif (in_array($type,[5,6,7,8,9])) {
    $cats = getTxtCategories($type);
    $toolName = $cats[$d['machine_id']] ?? 'Miscellaneous';
}
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

<td class="desc"><?= shortDesc($d['complaint_description']) ?></td>

<td class="text-center">
<a href="#" class="track-link"
onclick="return viewTrack(<?= $d['complaint_id'] ?>,<?= $type ?>)">
View
</a>
</td>

<td><?= $d['allocated_to'] ? getName($d['allocated_to']) : '' ?></td>

<td>
<div class="status-box">Closed</div>
<div class="small-muted"><?= display_date($d['status_timestamp']) ?></div>
<div class="small-muted">
<?= count_day($d['time_of_complaint'],$d['status_timestamp']) ?> days
</div>

<?php if ($user_role=="head"): ?>
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
        pagingType: "simple_numbers"
    });
});

function viewTrack(id,type){
 $('<div>').load(
  'view_tracks.php?complaint_id='+id+'&type='+type
 ).dialog({
  title:'Complaint Tracking',
  width:'95%',
  height:600,
  modal:true
 });
 return false;
}
</script>

<?php include '../includes/footer.php'; ?>

<?php
include("../includes/auth_check.php");
include("../includes/header.php");
require_once("../config/connect.php");
require_once("../includes/common.php");

if (isset($_SESSION['role_ITadmin']) && $_SESSION['role_ITadmin'] != 1) {
    echo "<script>
        Swal.fire({
            icon:'error',
            title:'Access Denied',
            text:'You are not authorized',
            confirmButtonColor:'#dc2626'
        }).then(()=>location.href='../logout.php');
    </script>";
    exit;
}

/* Fetch roles */
$role_master = mysqli_query($db_equip, "SELECT * FROM role_master ORDER BY role_id ASC");
$roles = [];
while ($row = mysqli_fetch_assoc($role_master)) $roles[] = $row;

/* Check user role */
function userHasRole($memberid, $role_id) {
  global $db_equip;
  $memberid = (int)$memberid;
  $role_id  = (int)$role_id;
  $res = mysqli_query($db_equip, "SELECT id FROM role WHERE memberid=$memberid AND role=$role_id");
  return $res && mysqli_num_rows($res) > 0;
}


/* Add user */
if (isset($_POST['assign_submit'])) {
    $uid = mysqli_real_escape_string($db_equip, $_POST['new_uid']);
    mysqli_query($db_equip, "INSERT INTO role(memberid, role, timestamp) VALUES('$uid','1',NOW())");
    echo "<script>Swal.fire('Added','User assigned','success').then(()=>location.href='permission.php')</script>";
    exit;
}

/* Delete user */
if (isset($_GET['delete_uid'])) {
    $uid = (int)$_GET['delete_uid'];
    mysqli_query($db_equip, "DELETE FROM role WHERE memberid='$uid'");
    echo "<script>Swal.fire('Deleted','Role removed','success').then(()=>location.href='permission.php')</script>";
    exit;
}

/* Update permissions */
if (isset($_POST['submit'])) {
    foreach ($_POST['uid'] as $index => $memberid) {
        mysqli_query($db_equip, "DELETE FROM role WHERE memberid='$memberid'");
        foreach ($roles as $r) {
            if (!empty($_POST['role_' . $r['role_id'] . "_$index"])) {
                mysqli_query(
                    $db_equip,
                    "INSERT INTO role(memberid, role, timestamp)
                     VALUES('$memberid','{$r['role_id']}',NOW())"
                );
            }
        }
    }
    echo "<script>Swal.fire('Updated','Permissions saved','success').then(()=>location.href='permission.php')</script>";
    exit;
}

$staff = staff_list();
?>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>
/* ===== GROUPED PERMISSION UI ===== */
.permission-wrapper {
    display: grid;
    grid-template-columns: 1fr 1fr 1fr; /* Heads | Teams | Special Permission */
    gap: 14px;
}
.permission-group {
    background: #f8fafc;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    padding: 12px 14px;
}
.permission-group h6 {
    font-size: 13px;
    font-weight: 800;
    color: #1e40af;
    margin-bottom: 8px;
}
.permission-group label {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 6px;
    font-size: 13px;
}
</style>

<main class="container py-4">
<div class="row">
<div class="col-md-3"><?php include("../includes/menu.php"); ?></div>
<div class="col-md-9">

<div class="card">
<div class="card-header">Permission Control</div>
<div class="card-body p-4">

<!-- ADD USER -->
<form method="post" class="assign-box mb-4 d-flex gap-3">
<label class="fw-bold pt-1">Name:</label>
<select name="new_uid" class="form-select w-auto" required>
<option value="">--Select User--</option>
<?php foreach ($staff as $sid):
if (mysqli_num_rows(mysqli_query($db_equip,"SELECT * FROM role WHERE memberid='$sid'"))==0): ?>
<option value="<?=$sid?>"><?=getName($sid)?></option>
<?php endif; endforeach; ?>
</select>
<button class="btn btn-success" name="assign_submit">Assign</button>
</form>

<form method="post">
<div class="table-responsive">
<table class="table align-middle">
<thead>
<tr>
<th>Name</th>
<th>Permissions</th>
<th>Delete</th>
</tr>
</thead>

<tbody>
<?php
$q = mysqli_query($db_equip,"SELECT DISTINCT memberid FROM role");
$users=[];
while($r=mysqli_fetch_assoc($q))
    $users[]=['id'=>$r['memberid'],'name'=>getName($r['memberid'])];

usort($users,fn($a,$b)=>strcasecmp($a['name'],$b['name']));
$i=0;

foreach($users as $u):
$uid=$u['id'];
?>
<tr>
<td>
<input type="hidden" name="uid[<?=$i?>]" value="<?=$uid?>">
<?=$u['name']?>
</td>

<td>
<div class="permission-wrapper">

<div class="permission-group">
<h6>Heads</h6>
<?php foreach ($roles as $r):
if (stripos($r['role'],'Head') !== false): ?>
<label>
<input type="checkbox"
name="role_<?=$r['role_id']?>_<?=$i?>"
<?=userHasRole($uid,$r['role_id'])?'checked':''?>>
<?=$r['role']?>
</label>
<?php endif; endforeach; ?>
</div>

<div class="permission-group">
<h6>Teams</h6>
<?php foreach ($roles as $r):
if (stripos($r['role'],'Team') !== false): ?>
<label>
<input type="checkbox"
name="role_<?=$r['role_id']?>_<?=$i?>"
<?=userHasRole($uid,$r['role_id'])?'checked':''?>>
<?=$r['role']?>
</label>
<?php endif; endforeach; ?>
</div>

<!-- SPECIAL -->
<div class="permission-group">
  <h6>Special Permission</h6>
  <?php foreach ($roles as $r):
  if (stripos($r['role'],'Head') === false &&
      stripos($r['role'],'Team') === false): ?>
    <label>
      <input type="checkbox"
        name="role_<?=$r['role_id']?>_<?=$i?>"
        <?=userHasRole($uid,$r['role_id'])?'checked':''?>>
      <?=$r['role']?>
    </label>
  <?php endif; endforeach; ?>
</div>


</div>
</td>

<td>
<a href="?delete_uid=<?=$uid?>" class="btn btn-danger btn-sm"
onclick="return confirmDelete(this.href)">X</a>
</td>
</tr>
<?php $i++; endforeach; ?>
</tbody>

<tfoot>
<tr>
<td colspan="3" class="text-center">
    <button class="btn btn-primary btn-update-small" name="submit">
        Update Permissions
    </button>
</td>
</tr>
</tfoot>

</table>
</div>
</form>

</div>
</div>

</div>
</div>
</main>

<script>
function confirmDelete(url){
Swal.fire({
title:'Are you sure?',
text:'This user role will be removed!',
icon:'warning',
showCancelButton:true,
confirmButtonColor:'#dc2626'
}).then(r=>{ if(r.isConfirmed) location.href=url; });
return false;
}
</script>

<?php include("../includes/footer.php"); ?>

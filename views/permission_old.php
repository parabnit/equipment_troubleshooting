<?php
include("../includes/auth_check.php");
include("../includes/header.php");
require_once("../config/connect.php");
require_once("../includes/common.php");

// Session checks
if (isset($_SESSION['role_ITadmin']) && $_SESSION['role_ITadmin'] != 1) {
  echo"<script>alert('Access Denied!'); location.href='../logout.php';</script>";
  exit;
}

$permission  = permission_details();
$staff       = staff_list();
// echo "<pre>";
// print_r($staff);
// echo "</pre>";
$exp_member  = expired_memberid();

// Handle Delete
if (isset($_GET['delete_uid'])) {
  $id = (int)$_GET['delete_uid'];
  mysqli_query($db_equip, "DELETE FROM permission WHERE id = $id");
  echo "<script>alert('Permission Deleted Successfully!'); location.href='permission.php';</script>";
  exit;
}

// Handle Assign
if (isset($_POST['assign_submit'])) {
  $uid  = mysqli_real_escape_string($db_equip, $_POST['new_uid']);
  $perm = $_POST['new_permission'];

  $types = ['equipment', 'facility', 'safety', 'process'];
  $values = array_fill_keys($types, 0);
  if (isset($values[$perm])) $values[$perm] = 1;

  add_permission($values['equipment'], $values['facility'], $values['safety'], $values['process'], $uid);
  echo "<script>alert('Permission Assigned Successfully!'); location.href='permission.php';</script>";
  exit;
}

// Handle Bulk Update
if (isset($_POST['submit'])) {
  foreach ($permission as $i => $perm) {
    if (empty($_POST['uid_' . $i])) continue;

    $uid  = mysqli_real_escape_string($db_equip, $_POST['uid_' . $i]);
    $vals = [];
    foreach (['equipment', 'facility', 'safety', 'process'] as $type) {
      $vals[$type] = isset($_POST["{$type}_{$i}"]) ? 1 : 0;
    }
    update_permission($vals['equipment'], $vals['facility'], $vals['safety'], $vals['process'], $uid);
  }
  echo "<script>alert('Permissions Updated Successfully!'); location.href='permission.php';</script>";
  exit;
}
?>

<main class="container py-4">
  <div class="row">
    <div class="col-md-3">
      <?php include("../includes/menu.php"); ?>
    </div>
    <div class="col-md-9">
      <div class="card shadow-sm">
        <div class="card-header bg-primary text-white">
          Permission Management
        </div>
        <div class="card-body p-4">

          <!-- Assign New Permission -->

          <form method="post" class="d-flex flex-column flex-md-row gap-2 align-items-stretch align-items-md-center mb-3">
            <!-- Name -->
            <div class="d-flex flex-column flex-md-row align-items-md-center">
              <label for="new_uid" class="fw-bold me-md-2 mb-1 mb-md-0">Name:</label>
              <select name="new_uid" id="new_uid" class="form-select form-select-sm w-100 w-md-auto" required>
                <option value="">---Select---</option>
                <?php foreach ($staff as $sid): ?>
                  <?php if (!user_permission_exit($sid)): ?>
                    <option value="<?= $sid ?>"><?= htmlspecialchars(getName($sid)) ?></option>
                  <?php endif; ?>
                <?php endforeach; ?>
              </select>
            </div>

            <!-- Permission -->
            <div class="d-flex flex-column flex-md-row align-items-md-center">
              <label for="new_permission" class="fw-bold me-md-2 mb-1 mb-md-0">Permission:</label>
              <select name="new_permission" id="new_permission" class="form-select form-select-sm w-100 w-md-auto" required>
                <option value="">---Select---</option>
                <?php foreach (['equipment', 'facility', 'safety', 'process'] as $type): ?>
                  <option value="<?= $type ?>"><?= ucfirst($type) ?></option>
                <?php endforeach; ?>
              </select>
            </div>

            <!-- Button -->
            <div>
              <button type="submit" name="assign_submit" class="btn btn-sm btn-primary w-100 w-md-auto">
                Assign
              </button>
            </div>
          </form>

          <!-- Edit Existing Permissions -->
          <form method="post">
            <div class="table-responsive">
              <table class="table table-bordered table-hover align-middle text-center">
                <thead class="table-primary">
                  <tr>
                    <th scope="col" style="width:40%">Name</th>
                    <th scope="col" style="width:15%">Equipment</th>
                    <th scope="col" style="width:15%">Facility</th>
                    <th scope="col" style="width:15%">Safety</th>
                    <th scope="col" style="width:15%">Process</th>
                    <th scope="col" style="width:15%">Delete</th>
                  </tr>
                </thead>
                <tbody>
                  <?php
                  usort($permission, 'sortByname');
                  foreach ($permission as $i => $perm):
                    $row_class = in_array($perm['id'], $exp_member) ? 'table-warning' : 'table-secondary';
                  ?>
                    <tr class="<?= $row_class ?>">
                      <td>
                        <input type="hidden" name="uid_<?= $i ?>" value="<?= $perm['id'] ?>" />
                        <input type="text" value="<?= htmlspecialchars($perm['name']) ?>" class="form-control form-control-sm" readonly />
                      </td>
                      <?php foreach (['equipment', 'facility', 'safety', 'process'] as $type): ?>
                        <td>
                          <input type="checkbox" name="<?= $type ?>_<?= $i ?>" value="1" <?= !empty($perm[$type]) ? 'checked' : '' ?>>
                        </td>
                      <?php endforeach; ?>
                      <td>
                        <a href="permission.php?delete_uid=<?= $perm['id'] ?>"
                          onclick="return confirm('Are you sure?');"
                          class="btn btn-sm btn-danger fw-bold">X</a>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
                <tfoot>
                  <tr>
                    <td colspan="6" class="text-center">
                      <button type="submit" name="submit" class="btn btn-primary">
                        Update All Permissions
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

<?php include("../includes/footer.php"); ?>

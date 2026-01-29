<?php
// ===========================
// Display errors for debugging
// ===========================
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// ===========================
// Includes & Auth
// ===========================
include("../includes/auth_check.php");
include("../includes/header.php");
include("../config/connect.php");
include("../includes/common.php");
include("../includes/class.phpmailer.php");

if (empty($_SESSION['login'])) {
    header("Location: ../logout.php");
    exit;
}

// ===========================
// Input Parameters
// ===========================
$type        = $_GET['type'] ?? null;
$importance  = $_GET['importance'] ?? 'all';
$tools_name  = $_POST['tools_name'] ?? '';
$show_closed = $_POST['show_closed'] ?? '0';

$member_id = (int)$_SESSION['memberid'];
$head = 0;


if (is_LabManager($member_id) || is_AssistLabManager($member_id)) {
  $user_role = 'all';
} elseif (is_EquipmentHead($member_id) && $type == 1) {
  $user_role = 'head';
  $head = 1;
} elseif (is_FacilityHead($member_id) && $type == 2) {
  $user_role = 'head';
  $head = 1;
} elseif (is_SafetyHead($member_id) && $type == 3) {
  $user_role = 'head';
  $head = 1;
} elseif (is_ProcessHead($member_id) && $type == 4) {
  $user_role = 'head';
  $head = 1;
} elseif (is_HRHead($member_id) && $type == 5) {
  $user_role = 'head';
  $head = 1;
} elseif (is_ITHead($member_id) && $type == 6) {
  $user_role = 'head';
  $head = 1;
} elseif (is_PurchaseHead($member_id) && $type == 7) {
  $user_role = 'head';
  $head = 1;
} elseif (is_TrainingHead($member_id) && $type == 8) {
  $user_role = 'head';
  $head = 1;
} elseif (is_InventoryHead($member_id) && $type == 9) {
  $user_role = 'head';
  $head = 1;
} elseif (is_EquipmentTeam($member_id) && $type == 1) {
  $user_role = 'team';
} elseif (is_FacilityTeam($member_id) && $type == 2) {
  $user_role = 'team';
} elseif (is_SafetyTeam($member_id) && $type == 3) {
  $user_role = 'team';
} elseif (is_ProcessTeam($member_id) && $type == 4) {
  $user_role = 'team';
} elseif (is_HRTeam($member_id) && $type == 5) {
  $user_role = 'team';
} elseif (is_ITTeam($member_id) && $type == 6) {
  $user_role = 'team';
} elseif (is_PurchaseTeam($member_id) && $type == 7) {
  $user_role = 'team';
}elseif (is_TrainingTeam($member_id) && $type == 8) {
  $user_role = 'team';
}elseif (is_InventoryTeam($member_id) && $type == 9) {
  $user_role = 'team';
} else {
  echo "<script>
        Swal.fire({
            icon:'error',
            title:'Access Denied',
            text:'You are not authorized to view this page.'
        }).then(()=>location.href='../logout.php');
    </script>";
  exit;
}



// ===========================
// Fetch complaints
// ===========================



// ===========================
// 👥 TEAM sees only their tasks
// 👑 HEAD sees all scheduler tasks
// ===========================
// Fetch & Filter Complaints
// $details = complaint('', $type, $tabledata);
if ($user_role == 'all') {
  $details = all_complaint($head,$type, 1,$tools_name,0);
  $permission_key = check_permission('LA',$member_id);
}
elseif ($user_role == 'head') {
  $details = all_complaint($head,$type, 1,$tools_name,0);
  $permission_key = check_permission($type,$member_id);
}

elseif ($user_role == 'team') {
  $details = my_allocated_complaint($member_id, $type, 1,$tools_name,0);
  $permission_key = check_permission($type,$member_id);
}


// echo"<pre>";
//  print_r($details); 
// echo"</pre>";
// ===========================
// Scheduler + Status Filter
// 🔁 CHANGED (exclusive toggle logic)
// ===========================
$details = array_filter($details, function ($row) use ($show_closed) {

    // Only scheduler complaints
    if (!isset($row['scheduler']) || (int)$row['scheduler'] !== 1) {
        return false;
    }

    // Toggle ON → show ONLY closed
    if ($show_closed == '1') {
        return (int)$row['status'] === 2;
    }

    // Toggle OFF → show NOT closed (pending/in process/on hold)
    return (int)$row['status'] !== 2;
});


// ===========================
// Tool / Category Filter (FIXED)
// ===========================
if ($tools_name !== '') {

    // Tools (Equipment / Facility / Safety / Process)
    if (in_array((int)$type, [1,2,3,4])) {

        $details = array_filter($details, function ($r) use ($tools_name) {
            return (string)($r['machine_id'] ?? '') === (string)$tools_name;
        });

    // Categories (IT / HR / Purchase / Training / Inventory)
    } else {

        $details = array_filter($details, function ($r) use ($tools_name) {
            return (string)($r['category_id'] ?? '') === (string)$tools_name;
        });
    }
}





?>

<?php

// ===========================
// ✅ Reopen complaint (Daily Tasks)
// ===========================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reopen'])) {
    $complaint_id = (int)($_POST['complaint_id'] ?? 0);

    if ($complaint_id > 0) {
        reopen_complain($complaint_id);
        $_SESSION['flash_message'] = "<div class='alert alert-success'>Complaint reopened successfully.</div>";
    } else {
        $_SESSION['flash_message'] = "<div class='alert alert-danger'>Invalid complaint id.</div>";
    }

    header("Location: " . $_SERVER['REQUEST_URI']);
    exit;
}
// ===========================
// ✅ Handle Status Update (like previous code)
// ===========================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['status_update'])) {

    $complaint_id = (int)($_POST['complaint_id'] ?? 0);
    $status_db    = (int)($_POST['status'] ?? -1);
    $c_date       = trim($_POST['c_date'] ?? '');
    $updated_by   = (int)($_SESSION['memberid'] ?? 0);

    // Keep allocated_to (hidden) - required by your update_complaint signature
    $allocated_to = (int)($_POST['allocated_to'] ?? 0);

    if ($complaint_id <= 0 || $status_db < 0 || $allocated_to <= 0) {
        $_SESSION['flash_message'] = "<div class='alert alert-danger'>Please fill all required fields.</div>";
        header("Location: " . $_SERVER['REQUEST_URI']);
        exit;
    }

    // If status is In Process (1) or Closed (2), ensure date exists
    if (in_array($status_db, [1,2], true) && $c_date === '') {
        $_SESSION['flash_message'] = "<div class='alert alert-danger'>Please select Date.</div>";
        header("Location: " . $_SERVER['REQUEST_URI']);
        exit;
    }
    // ===========================
// 🔒 Permission enforcement
// ===========================
if (!$head) {
    // Team can update only their own tasks
    if ($allocated_to !== $updated_by) {
        $_SESSION['flash_message'] =
            "<div class='alert alert-danger'>You are not allowed to update this task.</div>";
        header("Location: " . $_SERVER['REQUEST_URI']);
        exit;
    }
}

// Head must select allocation
if ($head && $allocated_to <= 0) {
    $_SESSION['flash_message'] =
        "<div class='alert alert-danger'>Please allocate the task to a team member.</div>";
    header("Location: " . $_SERVER['REQUEST_URI']);
    exit;
}


    $result = update_complaint($complaint_id, $status_db, $c_date, $allocated_to, $updated_by);

    if ($result > 0) {
        $_SESSION['flash_message'] = "<div class='alert alert-success'>Status updated successfully.</div>";
    } else {
        $_SESSION['flash_message'] = "<div class='alert alert-warning'>No changes were made.</div>";
    }

    header("Location: " . $_SERVER['REQUEST_URI']);
    exit;
}

// show flash message if exists
if (!empty($_SESSION['flash_message'])) {
    echo $_SESSION['flash_message'];
    unset($_SESSION['flash_message']);
}
?>


<!-- ===========================
     Styles
=========================== -->
<style>
.page-header {
  background: linear-gradient(135deg,#0d6efd,#0b5ed7);
  color:#fff;
  padding:15px 25px;
  border-radius:10px;
  margin-bottom:20px;
}
.filter-card {
  background:#f8f9fa;
  border-radius:12px;
  padding:15px;
  box-shadow:0 4px 12px rgba(0,0,0,.08);
}
.table thead {
  background:#0d6efd;
  color:white;
}
.table tbody tr:hover {
  background:#f1f5ff;
}
.badge-pending {
  background:#ffc107;
  color:#000;
}
.badge-closed {
  background:#198754;
}
.switch {
  position: relative;
  width: 55px;
  height: 30px;
}
.switch input { display:none; }
.slider {
  position:absolute;
  inset:0;
  background:#adb5bd;
  border-radius:20px;
  cursor:pointer;
  transition:.4s;
}
.slider:before {
  content:"";
  position:absolute;
  width:22px;
  height:22px;
  left:4px;
  bottom:4px;
  background:white;
  border-radius:50%;
  transition:.4s;
}
input:checked + .slider {
  background:#198754;
}
input:checked + .slider:before {
  transform:translateX(24px);
}
.desc-cell {
  max-width:350px;
  white-space:normal;
  word-break:break-word;
}

/* =========================
   MOBILE VIEW — Daily Tasks
   ========================= */
@media (max-width: 768px) {

  #DailytaskComplaints thead {
    display: none;
  }

  #DailytaskComplaints,
  #DailytaskComplaints tbody,
  #DailytaskComplaints tr,
  #DailytaskComplaints td {
    display: block;
    width: 100%;
  }

  #DailytaskComplaints tr {
    margin-bottom: 16px;
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 2px 6px rgba(0,0,0,0.08);
    padding: 8px;
  }

  #DailytaskComplaints td {
    border: none !important;
    padding: 8px 6px;
  }

  #DailytaskComplaints td::before {
    content: attr(data-label);
    display: block;
    font-size: 11px;
    font-weight: 600;
    color: #6c757d;
    margin-bottom: 2px;
    text-transform: uppercase;
  }

  .desc-cell {
    max-width: 100%;
  }

  /* Make action buttons full width on mobile */
  #DailytaskComplaints .btn,
  #DailytaskComplaints select,
  #DailytaskComplaints input[type="text"] {
    width: 100%;
  }
}

</style>
<script type="text/javascript" src="../assets/js/datetimepicker.js"></script>
<link rel="stylesheet" href="../assets/css/datetimepicker.css" type="text/css" />

<div class="container-fluid">
<div class="row">

<!-- Sidebar -->
<div class="col-md-2">
<?php include '../includes/menu.php'; ?>
</div>

<!-- Main -->
<div class="col-md-10">

<!-- Header -->
<div class="page-header d-flex justify-content-between align-items-center">
  <h4 class="mb-0">
   <?= [1=>'Equipment',2=>'Facility',3=>'Safety',4=>'Process',5=>'HR',6=>'IT',7=>'Purchase',8=>'Training', 9=>'Inventory'][$type] ?? '' ?>

    – Daily Tasks
  </h4>
  <span class="badge bg-light text-dark">Scheduler Tasks</span>
</div>

<!-- Filters -->
<form method="post" class="filter-card row g-3 align-items-end mb-4">

  <!-- Tool filter -->
  <div class="col-md-4">
    <label class="form-label fw-semibold">Tool &amp; Category</label>

   <div class="d-flex align-items-center gap-2">
      <select name="tools_name" class="form-select">
        <option value="">All Tools & Cat</option>
        <?php if (!in_array($type, [5,6,7,8,9])): ?>
        <option value="0" <?= $tools_name === '0' ? 'selected' : '' ?>>Miscellaneous</option>
        <?php endif; ?>
        <?php
        // TYPES USING TOOLS
        if (in_array((int)$type, [1,2,3,4])) {

            foreach (getTools($type) as $tool) {
                ?>
                <option value="<?= $tool['machid'] ?>" <?= $tools_name == $tool['machid'] ? 'selected' : '' ?>>
                  <?= htmlspecialchars($tool['name']) ?>
                </option>
                <?php
            }

        // TYPES USING CATEGORIES (IT, HR, PURCHASE, TRAINING, INVENTORY)
        } else {

            foreach (getTxtCategories($type) as $id => $name) {
              ?>
                <option value="<?= (int)$id ?>" <?= $tools_name == $id ? 'selected' : '' ?>>
                  <?= htmlspecialchars($name) ?>
                </option>
              <?php
            }

        }
        ?>
      </select>

      <button class="btn btn-primary px-3" style="height:38px;">
        <i class="bi bi-funnel"></i>
      </button>
    </div>

  </div>

  <!-- Toggle -->
  <div class="col-md-4">
    <label class="form-label fw-semibold">Show Closed</label><br>
    <label class="switch">
      <input type="checkbox"
             name="show_closed"
             value="1"
             <?= $show_closed == '1' ? 'checked' : '' ?>
             onchange="this.form.submit()">
      <span class="slider"></span>
    </label>
    <span class="ms-2 text-muted"><?= $show_closed ? 'Closed' : 'Pending' ?></span>
  </div>

  <div class="col-md-4"></div>
</form>


<!-- Table -->
<div class="table-responsive">
<table id="DailytaskComplaints" class="table table-bordered table-hover align-middle">

<thead>
<tr>
  <th>Member</th>
  <th>Tool</th>
  <th>Description</th>
  <?php if ($type == 4): ?>
    <th>Process Dev</th>
    <th>Anti Contamination</th>
  <?php endif; ?>
  <th>Allocation / Track</th>
  <th>Status</th>
</tr>
</thead>

<tbody>
<?php foreach ($details as $d): ?>
<tr>

<td data-label="Member">
<strong><?= getName($d['allocated_to']) ?></strong><br>
<small><?= display_date($d['time_of_complaint']) ?></small><br>
<small class="text-muted">#<?= $d['complaint_id'] ?></small>
<small class="text-muted">Created By:- <?=getName($d['member_id']) ?></small>
</td>

<td data-label="Tool & Cat">
<?php
// TOOLS (Equipment, Facility, Safety, Process)
if ($type == 1 || $type == 4) {
    echo $d['machine_id'] ? getToolName($d['machine_id']) : 'Misc';

} elseif ($type == 2) {
    echo $d['machine_id'] ? getToolName_facility($d['machine_id']) : 'Misc';

} elseif ($type == 3) {
    echo $d['machine_id'] ? getToolName_safety($d['machine_id']) : 'Misc';

// CATEGORIES (IT, HR, Purchase, Training, Inventory)
} else {
    $categories = getTxtCategories($type); // returns array [id => name]
    echo isset($categories[$d['machine_id']])
        ? htmlspecialchars($categories[$d['machine_id']])
        : 'Misc';
}


// Expected completion date
$ec = EC_date($d['complaint_id']);
if ($ec !== '') {
    echo '<br><small class="text-muted"><b>Expected completion date:</b> ' . display_date($ec) . '</small>';
}
?>


</td>

<td class="desc-cell" data-label="Description"><?= shortDesc($d['complaint_description']) ?></td>

<?php if ($type == 4): ?>
<td data-label="Process Dev"><?= shortDesc($d['process_develop']) ?></td>
<td data-label="Anti Contamination"><?= shortDesc($d['anti_contamination_develop']) ?></td>
<?php endif; ?>


<td data-label="Allocation / Track" style="text-align:center;">
   <?php if ($d['status'] != '2'): ?>
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
  <?php if (count(trouble_track($d['complaint_id'], '')) > 0): ?>
    <a href="#" onclick="return viewTrack(<?= (int)$d['complaint_id'] ?>, <?= (int)$type ?>);">Track</a>
  <?php else: ?>
    <span class="text-muted">No Track</span>
  <?php endif; ?>


</td>

<td data-label="Status">
<?php
  // normalize status safely
  $rawStatus = $d['status'] ?? 0;

  // if status is string like "pending"/"closed"
  if (!is_numeric($rawStatus)) {
    $map = [
      'pending'   => 0,
      'in process'=> 1,
      'closed'    => 2,
      'onhold'    => 3,
    ];
    $key = strtolower(trim((string)$rawStatus));
    $st = $map[$key] ?? 0;
  } else {
    $st = (int)$rawStatus;
  }
?>

<?php if ($st === 2): ?>

    <span class="badge badge-closed">Closed</span>
  <br>
  <br>
  <?php
                    $name = $d["status_updated_by"] ?? null;
                    if ($name !== null) {
                      $status_updated_name = getName($name);
                      echo htmlspecialchars($status_updated_name);
                    }
                    ?>
                    <br />
                    <?php
                    if ($d["status"] == 0) {
                      echo "Pending";
                    }
                    if ($d["status"] == 1) {
                      echo "In process";
                    }
                    if ($d["status"] == 2) {
                      echo "Closed";
                    }
                    if ($d["status"] == 3) {
                      echo "On Hold";
                    }
                    if ($d["status"] == 2) {
                      echo "<br>" . display_date($d["status_timestamp"]);
                      if (count_day($d["time_of_complaint"], $d["status_timestamp"]) == 0 || count_day($d["time_of_complaint"], $d["status_timestamp"]) == 1) {
                        echo "<br>" .
                          count_day(
                            $d["time_of_complaint"],
                            $d["status_timestamp"]
                          ) .
                          " day";
                      } else {
                        echo "<br>" .
                          count_day(
                            $d["time_of_complaint"],
                            $d["status_timestamp"]
                          ) .
                          " days";
                      }
                    }
                    ?>
                    <br>
                    <br>
 <form method="post" style="display:inline;">
      <input type="hidden" name="complaint_id" value="<?= (int)$d['complaint_id'] ?>">
      <button type="submit"
              name="reopen"
              class="btn btn-sm btn-outline-danger"
              onclick="return confirm('Are you sure you want to reopen this complaint?');">
        Re-Open
      </button>
    </form>

<?php else: ?>

  <form method="post" class="d-flex flex-column gap-2">
    <!-- keep filters when submitting -->
    <input type="hidden" name="tools_name" value="<?= htmlspecialchars($tools_name, ENT_QUOTES, 'UTF-8') ?>">
    <input type="hidden" name="show_closed" value="<?= htmlspecialchars($show_closed, ENT_QUOTES, 'UTF-8') ?>">

    <input type="hidden" name="complaint_id" value="<?= (int)$d['complaint_id'] ?>">
    <?php if ($head): ?>

  <select name="allocated_to" class="form-select form-select-sm">
    <option value="">-- Allocate To --</option>
    <?php
     // get team members for this type
      $teamMap = [
        1 => 'equipment',
        2 => 'facility',
        3 => 'safety',
        4 => 'process',
        5 => 'hr',
        6 => 'it',
        7 => 'purchase',
        8 => 'training',
        9 => 'inventory'
      ];

      $teamKey  = $teamMap[$type] ?? '';
      $team_ids = $teamKey ? getTeamMembers($teamKey) : [];


      foreach ($team_ids as $tid) {
        $tid = (int)$tid;
        $selected = ((int)($d['allocated_to'] ?? 0) === $tid) ? 'selected' : '';
        echo "<option value='{$tid}' {$selected}>"
            . htmlspecialchars(getName($tid)) .
            "</option>";
      }
    ?>
  </select>

<?php else: ?>

  <input type="hidden" name="allocated_to" value="<?= (int)($d['allocated_to'] ?? $member_id) ?>">

<?php endif; ?>


    <select
      name="status"
      id="complaint_status<?= (int)$d['complaint_id'] ?>"
      class="form-select form-select-sm"
      onchange="timeshow(<?= (int)$d['complaint_id'] ?>)"
    >
      <option value="0" <?= ($st === 0) ? 'selected' : '' ?>>Pending</option>
      <option value="1" <?= ($st === 1) ? 'selected' : '' ?>>In Process</option>
      <option value="3" <?= ($st === 3) ? 'selected' : '' ?>>On Hold</option>
      <option value="2">Closed</option>
    </select>

    <input
      type="text"
      name="c_date"
      id="c_date<?= (int)$d['complaint_id'] ?>"
      class="form-control form-control-sm"
      style="display:none; min-width:160px;"
    />

    <button type="submit" name="status_update" class="btn btn-sm btn-primary">
      Submit
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
$(document).ready(function(){
  $('#DailytaskComplaints').DataTable({
    pageLength: 5,       // better for mobile
    stateSave: true,
    order: [],
    responsive: false,  // handled by CSS
    autoWidth: false
  });

});

function timeshow(id) {
  const status = $("#complaint_status" + id).val();
  const $date = $("#c_date" + id);

  if (status == "1" || status == "2") {

    $date.show();

    // ✅ init datetimepicker ONLY once & ONLY on change
    if (!$date.data("dtp-init")) {
      $date.datetimepicker();
      $date.data("dtp-init", true);
    }

    $date.focus();

  } else {
    $date.hide().val("");
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
</script>



<?php include '../includes/footer.php'; ?>

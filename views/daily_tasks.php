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

// ✅ Store return_url ONLY when coming from another page (not self)
if (!empty($_SERVER['HTTP_REFERER'])) {
    $current = basename($_SERVER['PHP_SELF']);
    $referer = basename(parse_url($_SERVER['HTTP_REFERER'], PHP_URL_PATH));

    if ($referer !== $current) {
        $_SESSION['return_url'] = $_SERVER['HTTP_REFERER'];
    }
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
            return (string)($r['machine_id'] ?? '') === (string)$tools_name;
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
    // $c_date       = trim($_POST['c_date'] ?? '');
    date_default_timezone_set('Asia/Kolkata'); // keep consistent
$c_date = date('Y-m-d H:i:s');            // e.g. 2026-01-14 14:34:00

    $updated_by   = (int)($_SESSION['memberid'] ?? 0);

    // Keep allocated_to (hidden) - required by your update_complaint signature
    $allocated_to = (int)($_POST['allocated_to'] ?? 0);

    if ($complaint_id <= 0 || $status_db < 0) {
        $_SESSION['flash_message'] = "<div class='alert alert-danger'>Please fill all required fields.</div>";
        header("Location: " . $_SERVER['REQUEST_URI']);
        exit;
    }

    // // If status is In Process (1) or Closed (2), ensure date exists
    // if (in_array($status_db, [1,2], true) && $c_date === '') {
    //     $_SESSION['flash_message'] = "<div class='alert alert-danger'>Please select Date.</div>";
    //     header("Location: " . $_SERVER['REQUEST_URI']);
    //     exit;
    // }
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
/* ============================================
   🌟 ULTRA MODERN PREMIUM DATATABLE DESIGN 2026
   Inspired by Stripe • Notion • Apple UI
   ============================================ */

body {
  font-family: "Inter", "Poppins", sans-serif;
  background: radial-gradient(circle at top, #eef4ff, #f9fbff);
  color: #111827;
}

/* ===============================
   HEADER (Modern Gradient Glass)
   =============================== */
.page-header {
  background: linear-gradient(135deg, #3b82f6, #6366f1, #9333ea);
  padding: 22px 30px;
  border-radius: 26px;
  color: #fff;
  font-size: 22px;
  font-weight: 900;
  letter-spacing: 0.4px;
  box-shadow: 0 18px 45px rgba(99, 102, 241, 0.35);
  position: relative;
  overflow: hidden;
}

/* Glow Overlay */
.page-header::after {
  content: "";
  position: absolute;
  inset: 0;
  background: radial-gradient(circle, rgba(255,255,255,0.35), transparent);
  opacity: 0.35;
}

/* ===============================
   FILTER CARD (Glassmorphism)
   =============================== */
.filter-card {
  background: rgba(255, 255, 255, 0.55);
  backdrop-filter: blur(18px);
  border-radius: 24px;
  padding: 22px;
  border: 1px solid rgba(255, 255, 255, 0.35);
  box-shadow: 0 18px 40px rgba(0, 0, 0, 0.08);
}

/* Inputs Modern */
.filter-card input,
.filter-card select {
  width: 100%;
  border-radius: 18px;
  padding: 12px 16px;
  border: 1px solid rgba(0, 0, 0, 0.12);
  background: rgba(255,255,255,0.85);
  font-weight: 600;
  transition: 0.25s;
}

.filter-card input:focus,
.filter-card select:focus {
  outline: none;
  border-color: #6366f1;
  box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.25);
}

/* ===============================
   DATATABLE MAIN TABLE (Floating)
   =============================== */
.table {
  width: 100%;
  border-radius: 26px;
  overflow: hidden;
  background: rgba(255,255,255,0.75);
  backdrop-filter: blur(16px);
  border: 1px solid rgba(255,255,255,0.35);
  box-shadow: 0 22px 50px rgba(0, 0, 0, 0.10);
}

/* Table Head */
.table thead {
  background: rgba(99, 102, 241, 0.95);
  color: white;
  font-size: 13px;
  font-weight: 800;
  text-transform: uppercase;
  letter-spacing: 0.8px;
}

.table th {
  padding: 18px;
  border: none !important;
}

/* Table Body */
.table tbody td {
  padding: 16px;
  font-size: 14px;
  border-bottom: 1px solid rgba(0, 0, 0, 0.04);
  font-weight: 500;
}

/* Row Hover = Premium Lift */
.table tbody tr {
  transition: all 0.28s ease;
}

.table tbody tr:hover {
  background: rgba(99, 102, 241, 0.06);
  transform: translateY(-4px) scale(1.01);
  box-shadow: 0 12px 25px rgba(99, 102, 241, 0.12);
}

/* ===============================
   DESCRIPTION CELL
   =============================== */
.desc-cell {
  max-width: 420px;
  padding: 14px;
  border-radius: 18px;
  background: rgba(243, 244, 255, 0.75);
  box-shadow: inset 0 0 0 1px rgba(99, 102, 241, 0.15);
  line-height: 1.75;
}

/* ===============================
   BADGES (Pill Modern)
   =============================== */
.badge {
  padding: 8px 18px;
  border-radius: 999px;
  font-weight: 800;
  font-size: 12px;
  letter-spacing: 0.4px;
}

.badge-pending {
  background: linear-gradient(135deg, #fbbf24, #f97316);
  color: #111;
}

.badge-closed {
  background: linear-gradient(135deg, #22c55e, #16a34a);
  color: white;
}

/* ===============================
   BUTTONS (Modern Soft UI)
   =============================== */
.btn {
  border-radius: 18px !important;
  padding: 11px 16px;
  font-weight: 800;
  border: none;
  transition: 0.3s ease;
}

.btn-primary {
  background: linear-gradient(135deg, #3b82f6, #6366f1);
  color: white;
}

.btn-primary:hover {
  transform: translateY(-3px);
  box-shadow: 0 14px 28px rgba(99, 102, 241, 0.35);
}

/* Track Button */
.track-btn {
  display: inline-block;
  padding: 11px 18px;
  border-radius: 18px;
  background: linear-gradient(135deg, #6366f1, #9333ea);
  font-weight: 900;
  color: white !important;
  text-decoration: none;
  box-shadow: 0 14px 28px rgba(147, 51, 234, 0.30);
}

/* ===============================
   DATATABLE SEARCH MODERN
   =============================== */
.dataTables_filter input {
  border-radius: 18px;
  padding: 12px 16px;
  border: 1px solid rgba(0,0,0,0.12);
  background: rgba(255,255,255,0.85);
}

.dataTables_filter input:focus {
  outline: none;
  border-color: #6366f1;
  box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.25);
}

/* ===============================
   PAGINATION (Modern Pills)
   =============================== */
.dataTables_paginate .paginate_button {
  border-radius: 999px !important;
  padding: 8px 16px !important;
  margin: 4px;
  border: none !important;
  background: rgba(99, 102, 241, 0.10) !important;
  font-weight: 800;
  transition: 0.3s;
}

.dataTables_paginate .paginate_button:hover {
  background: linear-gradient(135deg, #3b82f6, #6366f1) !important;
  color: white !important;
}

.dataTables_paginate .paginate_button.current {
  background: linear-gradient(135deg, #6366f1, #9333ea) !important;
  color: white !important;
}

/* ===============================
   📱 MOBILE VIEW (Premium Cards)
   =============================== */
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

  /* Row Card */
  #DailytaskComplaints tr {
    margin-bottom: 22px;
    padding: 20px;
    border-radius: 26px;
    background: rgba(255,255,255,0.65);
    backdrop-filter: blur(18px);
    box-shadow: 0 18px 40px rgba(0,0,0,0.12);
    border: 1px solid rgba(255,255,255,0.35);
    position: relative;
    overflow: hidden;
  }

  /* Accent Strip */
  #DailytaskComplaints tr::before {
    content: "";
    position: absolute;
    left: 0;
    top: 0;
    bottom: 0;
    width: 8px;
    background: linear-gradient(180deg, #3b82f6, #9333ea);
    border-radius: 26px 0 0 26px;
  }

  #DailytaskComplaints td {
    border: none !important;
    padding: 10px 6px;
  }

  #DailytaskComplaints td::before {
    content: attr(data-label);
    font-size: 11px;
    font-weight: 900;
    color: #6b7280;
    text-transform: uppercase;
    margin-bottom: 5px;
    letter-spacing: 0.7px;
  }

  /* Full Width Buttons */
  .btn,
  .track-btn {
    width: 100%;
    display: block;
    margin-top: 10px;
    text-align: center;
  }
}

</style>


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
<input type="hidden" name="return_to" value="daily_tasks.php">
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
  <?php if($user_role=="all" || $head==1): ?>
  <th>Status</th>
  <?php endif; ?>
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

<td class="desc-cell" data-label="Description">

<?php
  $fullRaw   = $d['complaint_description'] ?? '';
  $fullDesc  = renderComplaintDesc(shortDesc($fullRaw));

  // Plain text length check (no <br>)
  $plainText = strip_tags($fullDesc);
  $isLong    = mb_strlen($plainText) > 120;

  // Short version (first 120 chars)
  $shortText = mb_substr($plainText, 0, 120);
  $shortDesc = nl2br(htmlspecialchars($shortText), false);
?>

<span class="desc-short">
  <?= $shortDesc ?>
  <?= $isLong ? '...' : '' ?>
</span>

<?php if ($isLong): ?>
  <span class="desc-full" style="display:none;">
    <?= $fullDesc ?>
  </span>

  <a href="javascript:void(0);"
     class="show-more-link text-primary fw-semibold"
     onclick="toggleDesc(this)">
     Show More
  </a>
<?php endif; ?>

</td>


<?php if ($type == 4): ?>
<td data-label="Process Dev"><?= shortDesc($d['process_develop']) ?></td>
<td data-label="Anti Contamination"><?= shortDesc($d['anti_contamination_develop']) ?></td>
<?php endif; ?>


<td data-label="Allocation / Track" style="text-align:center;">
   <?php if ($d['status'] != '2'): ?>
                    <form action="action_taken.php" method="post" enctype="multipart/form-data" style="display:inline; ">
    <input type="hidden" name="return_to" value="daily_tasks">

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
  <?php if($user_role=="all" || $head==1): ?>
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
   

  <input type="hidden" name="allocated_to" value="<?= (int)($d['allocated_to'] ?? $member_id) ?>">


<select name="status" id="complaint_status<?= (int)$d['complaint_id'] ?>" class="form-select form-select-sm">

      <option value="0" <?= ($st === 0) ? 'selected' : '' ?>>Pending</option>
      <option value="1" <?= ($st === 1) ? 'selected' : '' ?>>In Process</option>
      <option value="3" <?= ($st === 3) ? 'selected' : '' ?>>On Hold</option>
      <option value="2">Closed</option>
    </select>

  

    <button type="submit" name="status_update" class="btn btn-sm btn-primary">
      Submit
    </button>
  </form>

<?php endif; ?>
</td>
<?php endif; ?>


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

  function toggleDesc(el) {
  const $cell = $(el).closest('.desc-cell');
  const $short = $cell.find('.desc-short');
  const $full  = $cell.find('.desc-full');

  if ($full.is(':visible')) {
    $full.hide();
    $short.show();
    $(el).text('Show More');
  } else {
    $short.hide();
    $full.show();
    $(el).text('Show Less');
  }
}

</script>



<?php include '../includes/footer.php'; ?>

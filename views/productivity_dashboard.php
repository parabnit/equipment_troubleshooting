<?php
include("../includes/auth_check.php");
include("../includes/header.php");
require_once("../config/connect.php");
require_once("../includes/common.php");

$member_id = (int)$_SESSION['memberid'];

/* ================= ROLE CHECK ================= */

$is_lab_manager =
    is_LabManager($member_id) ||
    is_AssistLabManager($member_id);

/* Department Heads */
$is_equipment_head  = is_EquipmentHead($member_id);
$is_facility_head   = is_FacilityHead($member_id);
$is_safety_head     = is_SafetyHead($member_id);
$is_process_head    = is_ProcessHead($member_id);

$is_hr_head         = is_HRHead($member_id);
$is_it_head         = is_ITHead($member_id);
$is_purchase_head   = is_PurchaseHead($member_id);
$is_training_head   = is_TrainingHead($member_id);
$is_inventory_head  = is_InventoryHead($member_id);

/* Admin View Only For Manager or Heads */

/* ================= FILTER INPUT ================= */

$from = $_GET['from'] ?? date("Y-m-01");
$to   = $_GET['to'] ?? date("Y-m-d");

$selected_user = $_GET['user_id'] ?? $member_id;



/* ================= KPI FUNCTION ================= */

function getComplaintStats($user_id, $from, $to)
{
    global $db_equip;

    $sql = "
        SELECT
            SUM(CASE WHEN status = 0 THEN 1 ELSE 0 END) AS pending,
            SUM(CASE WHEN status = 1 THEN 1 ELSE 0 END) AS inprocess,
            SUM(CASE WHEN status = 2 THEN 1 ELSE 0 END) AS closed,
            SUM(CASE WHEN status = 3 THEN 1 ELSE 0 END) AS onhold
        FROM equipment_complaint
        WHERE DATE(time_of_complaint) BETWEEN ? AND ?
          AND allocated_to = ?
    ";

    $stmt = $db_equip->prepare($sql);
    $stmt->bind_param("ssi", $from, $to, $user_id);
    $stmt->execute();

    return $stmt->get_result()->fetch_assoc();
}

/* ================= TEAM USERS DROPDOWN ================= */

$team_users = [];

/* CASE 1: Lab Manager → Show ALL */
if ($is_lab_manager) {

    $all_members = array_merge(
        getTeamMembers("equipment"),
        getTeamMembers("facility"),
        getTeamMembers("safety"),
        getTeamMembers("process"),
        getTeamMembers("hr"),
        getTeamMembers("it"),
        getTeamMembers("purchase"),
        getTeamMembers("training"),
        getTeamMembers("inventory")
    );

    $all_members = array_unique($all_members);

    foreach ($all_members as $uid) {
        $team_users[$uid] = getName($uid);
    }
}

/* CASE 2: Equipment Head */
elseif ($is_equipment_head) {
    foreach (getTeamMembers("equipment") as $uid) {
        $team_users[$uid] = getName($uid);
    }
}

/* CASE 3: Facility Head */
elseif ($is_facility_head) {
    foreach (getTeamMembers("facility") as $uid) {
        $team_users[$uid] = getName($uid);
    }
}

/* CASE 4: Safety Head */
elseif ($is_safety_head) {
    foreach (getTeamMembers("safety") as $uid) {
        $team_users[$uid] = getName($uid);
    }
}

/* CASE 5: Process Head */
elseif ($is_process_head) {
    foreach (getTeamMembers("process") as $uid) {
        $team_users[$uid] = getName($uid);
    }
}

/* CASE 6: HR Head */
elseif ($is_hr_head) {
    foreach (getTeamMembers("hr") as $uid) {
        $team_users[$uid] = getName($uid);
    }
}

/* CASE 7: IT Head */
elseif ($is_it_head) {
    foreach (getTeamMembers("it") as $uid) {
        $team_users[$uid] = getName($uid);
    }
}

/* CASE 8: Purchase Head */
elseif ($is_purchase_head) {
    foreach (getTeamMembers("purchase") as $uid) {
        $team_users[$uid] = getName($uid);
    }
}

/* CASE 9: Training Head */
elseif ($is_training_head) {
    foreach (getTeamMembers("training") as $uid) {
        $team_users[$uid] = getName($uid);
    }
}

/* CASE 10: Inventory Head */
elseif ($is_inventory_head) {
    foreach (getTeamMembers("inventory") as $uid) {
        $team_users[$uid] = getName($uid);
    }
}

/* Always Add Logged User */
$team_users[$member_id] = "👤 My Dashboard";

/* ================= LOAD STATS ================= */

$stats = getComplaintStats($selected_user, $from, $to);
?>

<div class="container-fluid">
  <div class="row">

    <!-- MENU -->
    <div class="col-md-3">
      <?php include("../includes/menu.php"); ?>
    </div>

    <!-- DASHBOARD -->
    <div class="col-md-9">

      <div class="dash-header mb-4">
        <h3>📊 Productivity Dashboard</h3>
        <p class="text-muted">
          Track complaint performance team-wise with modern analytics
        </p>
      </div>

      <!-- FILTER BAR -->
      <form method="GET" class="filter-bar mb-4">

        <div class="filter-item">
          <label>From</label>
          <input type="date" name="from" value="<?= $from ?>">
        </div>

        <div class="filter-item">
          <label>To</label>
          <input type="date" name="to" value="<?= $to ?>">
        </div>

     <?php if (
            is_LabManager($_SESSION['memberid']) ||
            is_AssistLabManager($_SESSION['memberid']) ||
            is_EquipmentHead($_SESSION['memberid']) ||
            is_FacilityHead($_SESSION['memberid']) ||
            is_SafetyHead($_SESSION['memberid']) ||
            is_ProcessHead($_SESSION['memberid']) ||
            is_HRHead($_SESSION['memberid']) ||
            is_ITHead($_SESSION['memberid']) ||
            is_PurchaseHead($_SESSION['memberid']) ||
            is_TrainingHead($_SESSION['memberid']) ||
            is_InventoryHead($_SESSION['memberid'])
        ) { ?>
        <div class="filter-item">
            <label>Select User</label>
            <select name="user_id">
            <?php foreach ($team_users as $uid => $uname): ?>
                <option value="<?= $uid ?>" <?= ($uid == $selected_user ? "selected" : "") ?>>
                <?= $uname ?>
                </option>
            <?php endforeach; ?>
            </select>
        </div>
        <?php } ?>


        <button class="apply-btn">
          🚀 Apply Filter
        </button>

      </form>

      <!-- KPI Cards -->
      <div class="row g-4 mb-4">

        <div class="col-md-3">
          <div class="kpi-card blue">
            <h6>Pending</h6>
            <h2><?= $stats['pending'] ?></h2>
          </div>
        </div>

        <div class="col-md-3">
          <div class="kpi-card orange">
            <h6>In Process</h6>
            <h2><?= $stats['inprocess'] ?></h2>
          </div>
        </div>

        <div class="col-md-3">
          <div class="kpi-card green">
            <h6>Closed</h6>
            <h2><?= $stats['closed'] ?></h2>
          </div>
        </div>

        <div class="col-md-3">
          <div class="kpi-card red">
            <h6>On Hold</h6>
            <h2><?= $stats['onhold'] ?></h2>
          </div>
        </div>

      </div>

      <!-- Chart -->
      <div class="chart-card">
        <h5 class="mb-3">📈 Complaint Status Overview</h5>
        <canvas id="statusChart"></canvas>
      </div>

    </div>
  </div>
</div>

<!-- ================= MODERN UI STYLE ================= -->
<style>
/* ================= BASE ================= */
body {
  background: linear-gradient(135deg, #f8fbff, #eef4ff);
  font-family: "Inter", system-ui, sans-serif;
  color: #111827;
  font-size: 13px;
}

/* ================= HEADER ================= */
.dash-header h3 {
  font-weight: 800;
  font-size: 20px;
  margin-bottom: 2px;
  letter-spacing: 0.2px;
}

.dash-header p {
  font-size: 12px;
  opacity: 0.7;
}

/* ================= FILTER BAR ================= */
.filter-bar {
  display: flex;
  gap: 12px;
  flex-wrap: wrap;
  align-items: flex-end;
  padding: 12px 14px;
  background: linear-gradient(180deg,#ffffff,#f9fafb);
  border-radius: 14px;
  box-shadow:
    0 6px 14px rgba(0,0,0,0.06),
    inset 0 1px 0 rgba(255,255,255,0.9);
}

.filter-item {
  display: flex;
  flex-direction: column;
  font-size: 11px;
  font-weight: 700;
  min-width: 120px;
  color: #374151;
}

.filter-item label {
  margin-bottom: 4px;
}

/* Inputs */
.filter-item input,
.filter-item select {
  padding: 7px 10px;
  border-radius: 10px;
  border: 1px solid #e5e7eb;
  font-size: 12px;
  background: #fff;
  transition: all 0.2s ease;
}

.filter-item input:hover,
.filter-item select:hover {
  border-color: #c7d2fe;
}

.filter-item input:focus,
.filter-item select:focus {
  border-color: #2563eb;
  box-shadow: 0 0 0 2px rgba(37,99,235,0.18);
}

/* Apply Button */
.apply-btn {
  padding: 8px 14px;
  border-radius: 12px;
  border: none;
  background: linear-gradient(135deg,#1f2937,#2563eb);
  color: #fff;
  font-size: 12px;
  font-weight: 700;
  letter-spacing: 0.3px;
  cursor: pointer;
  transition: all 0.25s ease;
}

.apply-btn:hover {
  transform: translateY(-1px) scale(1.04);
  box-shadow: 0 6px 16px rgba(37,99,235,0.35);
}

/* ================= KPI CARDS ================= */
.kpi-card {
  padding: 14px 12px;
  min-height: 95px;
  border-radius: 14px;
  text-align: center;
  font-weight: 800;
  box-shadow:
    0 6px 14px rgba(0,0,0,0.14),
    inset 0 1px 0 rgba(255,255,255,0.15);
  transition: all 0.25s ease;
  position: relative;
  overflow: hidden;
}

/* subtle shine */
.kpi-card::after {
  content: "";
  position: absolute;
  inset: 0;
  background: linear-gradient(
    120deg,
    transparent 30%,
    rgba(255,255,255,0.15),
    transparent 70%
  );
  opacity: 0;
  transition: opacity 0.3s ease;
}

.kpi-card:hover::after {
  opacity: 1;
}

.kpi-card:hover {
  transform: translateY(-4px);
}

.kpi-card h6 {
  font-size: 11px;
  margin-bottom: 4px;
  opacity: 0.9;
  text-transform: uppercase;
  letter-spacing: 0.6px;
}

.kpi-card h2 {
  font-size: 22px;
  margin: 0;
}

/* KPI COLORS */
.blue   { background: linear-gradient(135deg,#2563eb,#60a5fa); }
.orange { background: linear-gradient(135deg,#f97316,#fbbf24); }
.green  { background: linear-gradient(135deg,#16a34a,#4ade80); }
.red    { background: linear-gradient(135deg,#dc2626,#fb7185); }

/* ================= CHART CARD ================= */
.chart-card {
  background: linear-gradient(180deg,#ffffff,#f9fafb);
  padding: 14px;
  border-radius: 14px;
  box-shadow:
    0 6px 16px rgba(0,0,0,0.08),
    inset 0 1px 0 rgba(255,255,255,0.85);
}

.chart-card h5 {
  font-size: 13px;
  font-weight: 800;
  margin-bottom: 8px;
  color: #1f2937;
}

/* SAME SIZE GRAPH */
.chart-card canvas {
  width: 100% !important;
  height: 200px !important;
}

/* ================= MOBILE ================= */
@media (max-width: 768px) {
  .chart-card canvas {
    height: 170px !important;
  }
}
</style>



<!-- Chart JS -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
new Chart(document.getElementById("statusChart"), {
  type: "bar",
  data: {
    labels: ["Pending", "In Process", "Closed", "On Hold"],
    datasets: [{
      data: [
        <?= $stats['pending'] ?>,
        <?= $stats['inprocess'] ?>,
        <?= $stats['closed'] ?>,
        <?= $stats['onhold'] ?>
      ],
      backgroundColor: [
        "#3b82f6",
        "#f59e0b",
        "#22c55e",
        "#ef4444"
      ],
      borderRadius: 6,
      barThickness: 28   // 👈 smaller bars
    }]
  },
  options: {
    responsive: true,
    maintainAspectRatio: false,   // 👈 MOST IMPORTANT
    plugins: {
      legend: { display: false }
    },
    scales: {
      x: {
        ticks: {
          font: { size: 11 }
        },
        grid: { display: false }
      },
      y: {
        ticks: {
          font: { size: 11 },
          precision: 0
        },
        grid: {
          color: "rgba(0,0,0,0.05)"
        }
      }
    }
  }
});
</script>


<?php include("../includes/footer.php"); ?>

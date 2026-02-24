<?php
include("../includes/auth_check.php");
include("../config/connect.php");
include("../includes/header.php");
include("../includes/common.php");



ini_set('display_errors', 1);
error_reporting(E_ALL);

// Hardcoded complaint types
$types = [
    1 => "Equipment",
    2 => "Facility",
    3 => "Safety",
    4 => "Process",
    5 => "HR",
    6 => "IT",
    7 => "Purchase",
    8 => "Training",
    9 => "Inventory",
    10 => "Admin"
];




?>
<?php
$recipient_id = $_SESSION['memberid'];
$notifications = getMemberNotifications($recipient_id);
?>
<style>

/* ================================
   ROOT VARIABLES
================================ */
:root {
  --dark-text: #1A2B4C;
  --secondary-text: #5a6a85;
  --menu-bg: #f8faff;
  --urgency-red: #b22222;
}

/* ================================
   DEFAULT CARD THEME (Fallback)
================================ */
.square-card {
  --card-main: #2563eb;
  --card-light: #dbeafe;
}

/* ================================
   CARD THEMES (Different Colors)
================================ */
.theme-equipment { --card-main: #3b82f6; --card-light: #dbeafe; }
.theme-facility  { --card-main: #10b981; --card-light: #d1fae5; }
.theme-safety    { --card-main: #ef4444; --card-light: #fee2e2; }
.theme-process   { --card-main: #8b5cf6; --card-light: #ede9fe; }
.theme-hr        { --card-main: #f59e0b; --card-light: #fef3c7; }
.theme-it        { --card-main: #0ea5e9; --card-light: #cffafe; }
.theme-purchase  { --card-main: #ec4899; --card-light: #fce7f3; }
.theme-training  { --card-main: #14b8a6; --card-light: #ccfbf1; }
.theme-inventory { --card-main: #64748b; --card-light: #e2e8f0; }
.theme-admin { --card-main: #7c066cff; --card-light: #e5e7eb; }


/* ================================
   DASHBOARD GRID
================================ */
.dashboard-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 28px;
}

/* ================================
   CARD DESIGN (Premium SaaS Style)
================================ */
.square-card {
  border-radius: 20px;
  background: #fff;
  border: 1px solid #e5e7eb;
  overflow: hidden;
  position: relative;
  transition: all 0.35s ease;
  box-shadow: 0 10px 30px rgba(0,0,0,0.08);
}

.square-card:hover {
  transform: translateY(-7px);
  border-color: var(--card-main);
  box-shadow: 0 18px 45px rgba(0,0,0,0.16);
}

/* ================================
   CARD HEADER (Modern Glow Look)
================================ */
.square-card-header {
  background: linear-gradient(
    135deg,
    var(--card-main),
    #111827
  );
  padding: 10px 16px !important;   /* reduced height */
  color: #fff;
  font-size: 15px;                 /* smaller title */
  font-weight: 600;

  display: flex;
  justify-content: space-between;
  align-items: center;

  position: relative;
  overflow: hidden;
}


/* Glow Overlay */
.square-card-header::after {
  content: "";
  position: absolute;
  top: -50%;
  left: -40%;
  width: 200%;
  height: 200%;
  background: radial-gradient(circle, rgba(255,255,255,0.18), transparent 70%);
  transform: rotate(20deg);
}

/* Icon Bubble */
.square-card-header::before {
  width: 34px;
  height: 34px;
  font-size: 16px;
  border-radius: 14px;
  background: rgba(255,255,255,0.18);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 20px;
  margin-right: 12px;
  box-shadow: 0 6px 14px rgba(0,0,0,0.18);
  z-index: 2;
}

/* Header Title */
.square-card-header span {
  z-index: 2;
  letter-spacing: 0.3px;
}

/* Controls Wrapper */
.square-card-header .controls {
  display: flex;
  gap: 10px;
  z-index: 2;
}

/* Dropdown Premium Look */
.square-card-header select {
  background: rgba(255,255,255,0.95);
  border: none;
  border-radius: 12px;
  padding: 7px 12px;
  font-size: 12px;
  font-weight: 600;
  cursor: pointer;
  box-shadow: 0 4px 10px rgba(0,0,0,0.12);
  transition: 0.25s;
}

.square-card-header select:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 14px rgba(0,0,0,0.18);
}

/* ================================
   CARD BODY
================================ */
.square-card-body {
  max-height: 300px;   /* reduce height */
  overflow-y: auto;
  padding: 12px;       /* slightly smaller padding */
}

/* Scrollbar */
.square-card-body::-webkit-scrollbar {
  width: 6px;
}
.square-card-body::-webkit-scrollbar-thumb {
  background: rgba(0,0,0,0.25);
  border-radius: 10px;
}

/* ================================
   TABLE STYLING (Cleaner + Theme)
================================ */
table.dataTable {
  width: 100% !important;
  border-radius: 14px;
  overflow: hidden;
  border-collapse: separate !important;
  border-spacing: 0;
}

/* Table Head */
.table thead th {
  background: #f8fafc !important;
  font-size: 11px;
  font-weight: 800;
  text-transform: uppercase;
  padding: 11px;
  color: #334155;
  border-bottom: 1px solid #e2e8f0;
}

/* Days Pending Header Highlight */
/* .square-card thead th:last-child {
  background: var(--card-light) !important;
  color: var(--card-main);
  text-align: center;
} */

/* Body Cells */
.table tbody td {
  padding: 11px;
  font-size: 13px;
  border-bottom: 1px solid #f1f5f9;
  color: #1e293b;
}

/* Hover Row */
table.dataTable tbody tr:hover {
  background: rgba(0,0,0,0.035) !important;
  cursor: pointer;
}

/* Days Pending Badge Style */
/* .table tbody td:last-child {
  font-weight: 900;
  text-align: center;
  color: var(--card-main);
  background: var(--card-light);
  border-radius: 10px;
} */

/* ================================
   PAGINATION BUTTONS
================================ */
.dataTables_wrapper .paginate_button {
  padding: 7px 14px !important;
  border-radius: 12px !important;
  border: none !important;
  background: #f3f4f6 !important;
  font-weight: 600;
  transition: 0.2s;
}

.dataTables_wrapper .paginate_button:hover {
  background: #e2e8f0 !important;
}

.dataTables_wrapper .paginate_button.current {
  background: var(--card-main) !important;
  color: white !important;
  box-shadow: 0 5px 14px rgba(0,0,0,0.2);
}

/* ================================
   RESPONSIVE
================================ */
@media(max-width:768px) {
  .dashboard-grid {
    grid-template-columns: 1fr;
  }

  .square-card-header {
    flex-direction: column;
    align-items: flex-start;
    gap: 10px;
  }

  .square-card-header .controls {
    width: 100%;
    flex-direction: column;
  }

  .square-card-header select {
    width: 100%;
  }
}


.allocated-count-badge {
  display: inline-block;
  margin-left: 8px;
  padding: 3px 8px;
  font-size: 11px;
  font-weight: 600;
  border-radius: 20px;
  background: #e2e8f0;
  color: #1e293b;
}

/* Wrap 2nd column in all DataTables */
table.dataTable tbody td:nth-child(2) {
    white-space: normal !important;
    word-break: break-word !important;
    overflow-wrap: break-word !important;
}

/* Remove inner side padding from wrappers */
.table-responsive,
.dataTables_wrapper {
    padding-left: 0 !important;
    padding-right: 0 !important;
}

/* Make table full width */
table.dataTable {
    width: 100% !important;
    margin: 0 !important;
}

table.dataTable thead th,
table.dataTable tbody td {
    padding: 8px !important;
}
/* ================================
   NOTIFICATION CARD STYLE
================================ */
/* ================================
   🔔 PREMIUM NOTIFICATION CARD
================================ */

/* Card */
.notification-card {
    position: sticky;
    top: 20px;
    max-height: 85vh;
    border-radius: 18px;
    backdrop-filter: blur(14px);
    background: linear-gradient(145deg, #ffffff, #f3f4ff);
    box-shadow: 
        0 15px 35px rgba(0,0,0,0.08),
        0 5px 15px rgba(99,102,241,0.08);
    overflow: hidden;
}

/* Scroll area */
.notification-card .square-card-body {
    max-height: 70vh;
    overflow-y: auto;
    padding: 15px;
}

/* Custom Scrollbar */
.notification-card .square-card-body::-webkit-scrollbar {
    width: 6px;
}
.notification-card .square-card-body::-webkit-scrollbar-thumb {
    background: linear-gradient(to bottom, #6366f1, #8b5cf6);
    border-radius: 10px;
}
.notification-card .square-card-body::-webkit-scrollbar-track {
    background: transparent;
}

/* ================================
   🔔 Notification Item
================================ */

.notification-item-custom {
    position: relative;
    padding: 14px;
    border-radius: 16px;
    background: rgba(255,255,255,0.75);
    backdrop-filter: blur(10px);
    margin-bottom: 14px;
    display: flex;
    align-items: flex-start;
    transition: all 0.35s ease;
    border: 1px solid rgba(99,102,241,0.08);
}

/* Hover Effect */
.notification-item-custom:hover {
    transform: translateX(6px) scale(1.01);
    background: linear-gradient(135deg, #eef2ff, #f5f3ff);
    box-shadow: 
        0 10px 25px rgba(99,102,241,0.15);
}

/* ================================
   👤 Avatar
================================ */

.notification-avatar {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    margin-right: 10px;
    border: 2px solid #e0e7ff;
    object-fit: cover;
    transition: 0.3s;
}

.notification-item-custom:hover .notification-avatar {
    transform: scale(1.08);
    border-color: #6366f1;
}

/* ================================
   ❌ Close Button (Hidden until hover)
================================ */

.notif-close-btn {
    position: absolute;
    top: 8px;
    right: 10px;
    border: none;
    background: rgba(255,255,255,0.6);
    backdrop-filter: blur(6px);
    color: #6b7280;
    width: 24px;
    height: 24px;
    border-radius: 50%;
    font-size: 14px;
    cursor: pointer;
    opacity: 0;
    transform: scale(0.8);
    transition: all 0.25s ease;
    display: flex;
    align-items: center;
    justify-content: center;
}

/* Show only on hover */
.notification-item-custom:hover .notif-close-btn {
    opacity: 1;
    transform: scale(1);
}

/* Hover style */
.notif-close-btn:hover {
    background: linear-gradient(135deg, #ef4444, #dc2626);
    color: #fff;
    box-shadow: 0 5px 12px rgba(239,68,68,0.4);
}

/* ================================
   📝 Text Styling
================================ */

.notification-item-custom strong {
    font-size: 14px;
    color: #111827;
}

.notification-item-custom p {
    font-size: 13px;
    color: #4b5563;
    margin-bottom: 4px;
}

.notification-item-custom small {
    font-size: 11px;
    color: #9ca3af;
}

/* ================================
   ❌ Premium Always-Visible Close Button
================================ */

.notif-close-btn {
    position: absolute;
    top: 10px;
    right: 10px;

    width: 30px;
    height: 30px;
    border-radius: 50%;

    border: none;
    cursor: pointer;

    background: linear-gradient(135deg, #f87171, #dc2626);
    color: #ffffff;

    display: flex;
    align-items: center;
    justify-content: center;

    box-shadow: 0 6px 14px rgba(220,38,38,0.35);
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

/* Simple click feedback (no hover dependency) */
.notif-close-btn:active {
    transform: scale(0.9);
    box-shadow: 0 3px 8px rgba(220,38,38,0.4);
}
</style>



<script src="../assets/js/jquery-3.7.1.min.js"></script>
<script src="../assets/js/jquery.dataTables.min.js"></script>
<link rel="stylesheet" href="../css/jquery.dataTables.min.css">


<main class="container-fluid py-4 px-4">
    <div class="row">

        <!-- LEFT MENU -->
        <div class="col-md-3">
            <?php include("../includes/menu.php"); ?>
        </div>

        <!-- CENTER DASHBOARD -->
        <div class="col-md-6">
            <h3 class="dashboard-title pending-title mb-4">
                Pending Complaints
            </h3>

            <div class="dashboard-grid">
                <?php foreach ($types as $tid => $tname): ?>
                    <?php
                    $icons = [
                        1 => "⚙️", 2 => "🏢", 3 => "🚨", 4 => "📈",
                        5 => "👥", 6 => "💻", 7 => "🛒",
                        8 => "🎓", 9 => "📦", 10 => "🛠️"
                    ];

                    switch ($tid) {
                        case 1: $team="equipment"; break;
                        case 2: $team="facility"; break;
                        case 3: $team="safety"; break;
                        case 4: $team="process"; break;
                        case 5: $team="hr"; break;
                        case 6: $team="it"; break;
                        case 7: $team="purchase"; break;
                        case 8: $team="training"; break;
                        case 9: $team="inventory"; break;
                        case 10: $team="admin"; break;
                        default: $team=""; break;
                    }

                    $icon = $icons[$tid] ?? '📝';
                    $members = getTeamMembers($team);
                    ?>

                    <div class="square-card theme-<?= $team ?>">
                        <div class="square-card-header">
                            <span><?= $icon ?> <?= $tname ?></span>

                            <div class="controls">
                                <select class="form-select form-select-sm month-filter-<?= $tid ?>"
                                    onchange="loadTable(<?= $tid ?>, document.querySelector('.user-filter-<?= $tid ?>').value, this.value)">
                                    <option value="">Last 3 Months</option>
                                    <?php
                                    for ($i = 0; $i < 3; $i++) {
                                        $month = date("F Y", strtotime("-$i months"));
                                        $monthValue = date("Y-m", strtotime("-$i months"));
                                        echo "<option value='$monthValue'>$month</option>";
                                    }
                                    ?>
                                </select>

                                <select class="form-select form-select-sm user-filter-<?= $tid ?>"
                                    onchange="loadTable(<?= $tid ?>, this.value, document.querySelector('.month-filter-<?= $tid ?>').value)">
                                    <option value="">All Users</option>
                                    <?php foreach ($members as $mid): ?>
                                        <option value="<?= $mid ?>">
                                            <?= htmlspecialchars(getName($mid)) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="square-card-body">
                            <div class="table-responsive">
                                <table id="table_<?= $tid ?>" class="table table-bordered table-striped" width="100%">
                                    <thead>
                                        <tr>
                                            <th>Allocated To</th>
                                            <th>Description</th>
                                        </tr>
                                    </thead>
                                    <tbody></tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                <?php endforeach; ?>
            </div>
        </div>

        <!-- RIGHT NOTIFICATION PANEL -->
     <div class="col-md-3">
        <div class="square-card notification-card">

            <div class="square-card-header notification-header">
                <span>🔔 Notifications</span>
                <span class="badge bg-light text-dark">
                    <?= count($notifications); ?>
                </span>
            </div>

            <div class="square-card-body">

                <?php if (!empty($notifications)) : ?>

                    <?php foreach ($notifications as $row) : ?>

                        <?php
                            // Rotate between 12 offline avatars
                            $avatarNumber = ($row['id'] % 12) + 1;
                        ?>

                        <div class="notification-item-custom d-flex align-items-start position-relative">

                            <!-- ❌ Close Button -->
                            <button class="notif-close-btn"
                                onclick="deleteNotification(<?= $row['id']; ?>, this)">
                                <svg viewBox="0 0 24 24" width="14" height="14">
                                    <path d="M6 6L18 18M6 18L18 6"
                                        stroke="currentColor"
                                        stroke-width="2"
                                        stroke-linecap="round"/>
                                </svg>
                            </button>

                            <!-- Offline Cartoon Avatar -->
                            <img src="../assets/avatars/avatar<?= $avatarNumber; ?>.png"
                                class="notification-avatar"
                                alt="Avatar">

                            <div class="flex-grow-1">
                                <strong>
                                    <?= htmlspecialchars($row['title']); ?>
                                </strong>

                                <p class="mb-1">
                                    <?= htmlspecialchars($row['message']); ?>
                                </p>

                                <small class="text-muted">
                                    <?= date("d M Y, h:i A", strtotime($row['created_at'])); ?>
                                </small>
                            </div>

                        </div>

                    <?php endforeach; ?>

                <?php else : ?>

                    <div class="text-center text-muted py-3">
                        No notifications found
                    </div>

                <?php endif; ?>

            </div>
        </div>
    </div>

    </div>
</main>

<script>
function loadTable(typeId, memberId = "", month = "") {

    memberId = memberId === null ? "" : String(memberId);
    month = month === null ? "" : String(month);

    $('#table_' + typeId).DataTable({
        processing: true,
        serverSide: true,
        destroy: true,
        searching: false,
        lengthChange: false,
        pageLength: 5,

        ajax: {
            url: "load_dashboard_table.php",
            type: "POST",
            contentType: "application/json",
            data: function (d) {
                d.typeId = typeId;
                d.memberId = memberId;
                d.month = month;
                return JSON.stringify(d);
            }
        },

        columns: [
            { data: "allocated_to" },
            { data: "description" }
        ],

        drawCallback: function(settings) {

            let table = $('#table_' + typeId).DataTable();
            let data = table.rows({ page: 'current' }).data().toArray();

            let counts = {};

            // Count occurrences per allocated_to in current page
            data.forEach(function(row) {
                counts[row.allocated_to] = (counts[row.allocated_to] || 0) + 1;
            });

            // Update cells
            $('#table_' + typeId + ' tbody tr').each(function(index) {
                let rowData = data[index];
                if (!rowData) return;

                let count = counts[rowData.allocated_to] || 0;

                $(this).find('td:eq(0)').html(`
                    <div>
                        <strong>${rowData.allocated_to}</strong>
                        <span class="allocated-count-badge">
                            ${count} Days pending 
                        </span>
                    </div>
                `);
            });
        }
    });
}




document.addEventListener("DOMContentLoaded", () => {
    <?php foreach ($types as $tid => $name): ?>
        loadTable(<?= $tid ?>);
    <?php endforeach; ?>
});
</script>

<script>
function deleteNotification(id, btn) {
    btn.closest('.notification-item-custom').remove();

    // Optional backend delete
    /*
    fetch("delete_notification.php?id=" + id)
        .then(response => response.text())
        .then(data => console.log(data));
    */
}
</script>


<?php include("../includes/footer.php"); ?>

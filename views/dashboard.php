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
  padding: 16px 20px !important;
  color: #fff;
  font-size: 18px;
  font-weight: 700;

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
  content: attr(data-icon);
  width: 44px;
  height: 44px;
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
  max-height: 480px;
  overflow-y: auto;
  padding: 16px;
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
.square-card thead th:last-child {
  background: var(--card-light) !important;
  color: var(--card-main);
  text-align: center;
}

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
.table tbody td:last-child {
  font-weight: 900;
  text-align: center;
  color: var(--card-main);
  background: var(--card-light);
  border-radius: 10px;
}

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

</style>



<script src="../assets/js/jquery-3.7.1.min.js"></script>
<script src="../assets/js/jquery.dataTables.min.js"></script>
<link rel="stylesheet" href="../css/jquery.dataTables.min.css">


<main class="container py-4">
    <div class="row">
        <div class="col-md-3">
          <?php include("../includes/menu.php"); ?>
        </div>

        <div class="col-md-9">
            <h3 class="dashboard-title pending-title">Pending Complaints</h3>


            <div class="dashboard-grid">
                <?php foreach ($types as $tid => $tname): ?>
                    <?php
                    // Set icons for the card header
                   $icons = [
                    1 => "⚙️",
                    2 => "🏢",
                    3 => "🚨",
                    4 => "📈",
                    5 => "👥",
                    6 => "💻",
                    7 => "🛒",
                    8 => "🎓",
                    9 => "📦",
                    10 => "🛠️"
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

    
                        <div class="square-card-header" data-icon="<?= $icon ?>">
                            <span><?= $tname ?></span>

                            <div class="controls">
                                <!-- Month dropdown (last 6 months) -->
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

                                <!-- User dropdown -->
                                <select class="form-select form-select-sm user-filter-<?= $tid ?>"
                                        onchange="loadTable(<?= $tid ?>, this.value, document.querySelector('.month-filter-<?= $tid ?>').value)">
                                    <option value="">All Users</option>
                                    <?php foreach ($members as $mid): ?>
                                        <option value="<?= $mid ?>"><?= htmlspecialchars(getName($mid)) ?></option>
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
                                            <th>Days Pending</th>
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
    </div>
</main>

<script>
function loadTable(typeId, memberId = "", month = "") {
    // ensure strings
    memberId = memberId === null ? "" : String(memberId);
    month = month === null ? "" : String(month);

    $('#table_' + typeId).DataTable({
        processing: true,
        serverSide: true,
        destroy: true,

        // Disable search + limit dropdown
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
                d.month = month; // YYYY-MM or ""
                return JSON.stringify(d);
            }
        },

        columns: [
            { data: "allocated_to" },
            { data: "description" },
            { data: "days_pending" }
        ],

        createdRow: function(row, data, dataIndex) {
            // nothing destructive — keeping as-is
        }
    });
}



document.addEventListener("DOMContentLoaded", () => {
    <?php foreach ($types as $tid => $name): ?>
        loadTable(<?= $tid ?>);
    <?php endforeach; ?>
});
</script>

<?php include("../includes/footer.php"); ?>

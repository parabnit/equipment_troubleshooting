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
    9 => "Inventory"
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
.theme-equipment {
  --card-main: #3b82f6;
  --card-light: #dbeafe;
}

.theme-facility {
  --card-main: #10b981;
  --card-light: #d1fae5;
}

.theme-safety {
  --card-main: #ef4444;
  --card-light: #fee2e2;
}

.theme-process {
  --card-main: #8b5cf6;
  --card-light: #ede9fe;
}

.theme-hr {
  --card-main: #f59e0b;
  --card-light: #fef3c7;
}

.theme-it {
  --card-main: #0ea5e9;
  --card-light: #cffafe;
}

.theme-purchase {
  --card-main: #ec4899;
  --card-light: #fce7f3;
}

.theme-training {
  --card-main: #14b8a6;
  --card-light: #ccfbf1;
}

.theme-inventory {
  --card-main: #64748b;
  --card-light: #e2e8f0;
}

/* ================================
   DASHBOARD GRID
================================ */
.dashboard-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 28px;
}

/* ================================
   CARD DESIGN
================================ */
.square-card {
  border-radius: 18px;
  background: #fff;
  border: 1px solid #e5e7eb;
  box-shadow: 0 10px 25px rgba(0,0,0,0.08);
  overflow: hidden;
  transition: all 0.3s ease;
}

.square-card:hover {
  transform: translateY(-6px);
  border: 1px solid var(--card-main);
  box-shadow: 0 18px 40px rgba(0,0,0,0.15);
}

/* ================================
   CARD HEADER (THEME BASED)
================================ */
.square-card-header {
  background: linear-gradient(
    135deg,
    var(--card-main),
    #111827
  );
  padding: 14px 18px !important;
  color: #fff;
  font-size: 18px;
  font-weight: 700;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.square-card-header::before {
  content: attr(data-icon);
  font-size: 22px;
  margin-right: 10px;
  opacity: 0.9;
}

/* Dropdowns */
.square-card-header select {
  background: rgba(255,255,255,0.95);
  border: none;
  border-radius: 10px;
  padding: 6px 10px;
  font-size: 12px;
  font-weight: 600;
  cursor: pointer;
}

.square-card-header select:hover {
  box-shadow: 0 0 0 2px rgba(255,255,255,0.6);
}

/* ================================
   CARD BODY
================================ */
.square-card-body {
  max-height: 480px;
  overflow-y: auto;
  padding: 15px;
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
   TABLE STYLING
================================ */
table.dataTable {
  width: 100% !important;
  border-radius: 12px;
  overflow: hidden;
}

/* Table Head */
.table thead th {
  background: #f3f4f6 !important;
  font-size: 11px;
  font-weight: 700;
  text-transform: uppercase;
  padding: 10px;
}

/* Theme highlight for Days Pending header */
.square-card thead th:last-child {
  background: var(--card-light) !important;
  color: var(--card-main);
  text-align: center;
}

/* Body Cells */
.table tbody td {
  padding: 10px;
  font-size: 13px;
  border-bottom: 1px solid #f1f5f9;
}

/* Hover Row */
table.dataTable tbody tr:hover {
  background: rgba(0,0,0,0.03) !important;
  cursor: pointer;
}

/* Days Pending Column */
.table tbody td:last-child {
  font-weight: 800;
  text-align: center;
  color: var(--card-main);
  background: rgba(0,0,0,0.02);
}

/* ================================
   PAGINATION BUTTONS
================================ */
.dataTables_wrapper .paginate_button {
  padding: 6px 12px !important;
  border-radius: 10px !important;
  border: none !important;
  background: #f3f4f6 !important;
  font-weight: 600;
}

.dataTables_wrapper .paginate_button.current {
  background: var(--card-main) !important;
  color: white !important;
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
                        1 => "⚙️", // Equipment
                        2 => "🏢", // Facility
                        3 => "🚨", // Safety
                        4 => "📈", // Process
                        5 => "👥", // HR
                        6 => "💻", // IT
                        7 => "🛒", // Purchase
                        8 => "🎓", // Training
                        9 => "📦"  // Inventory
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

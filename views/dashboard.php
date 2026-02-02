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
    NEW/MODIFIED STYLES
    ================================ 
    */

    :root {
        --primary-blue: #2E64AE; /* Define a main color variable */
        --light-blue: #e9effa;
        --dark-text: #1A2B4C;
        --secondary-text: #5a6a85; /* New color for secondary text/labels */
        --urgency-red: #b22222; /* Defined red for urgency */
        --menu-bg: #f8faff; /* Light background for the menu area */
    }
    
    /* ================================
        NEW: SIDEBAR MENU STYLING
    ================================ */
    /* Assuming your menu.php outputs a structure like: <div id="menu"><ul>...</ul></div> */
    .col-md-3 {
        padding-right: 25px; /* Ensure space between menu and dashboard */
    }
    
    /* General Menu Container Styling */
    .col-md-3 #menu {
        background-color: var(--menu-bg);
        border-radius: 18px;
        box-shadow: 0 5px 20px rgba(0,0,0,0.05);
        padding: 15px 0;
        margin-top: 50px; /* Align with dashboard title below the main header */
    }

    /* Menu Item List */
    .col-md-3 #menu ul {
        list-style: none;
        padding: 0;
        margin: 0;
    }

    /* Menu Links */
    .col-md-3 #menu ul li a {
        display: block;
        padding: 12px 20px;
        color: var(--dark-text);
        font-weight: 500;
        text-decoration: none;
        transition: all 0.2s;
        border-left: 4px solid transparent;
        font-size: 15px;
    }

    /* Menu Link Hover State */
    .col-md-3 #menu ul li a:hover {
        background-color: var(--light-blue);
        color: var(--primary-blue);
        border-left: 4px solid var(--primary-blue);
        padding-left: 25px; /* Slide effect on hover */
    }

    /* Active Menu Link State (if your menu system adds an 'active' class) */
    .col-md-3 #menu ul li a.active {
        background-color: var(--primary-blue);
        color: #fff;
        font-weight: 700;
        border-left: 4px solid var(--primary-blue);
        border-radius: 0 8px 8px 0; /* Rounded edge for the active tab */
        box-shadow: 2px 0 10px rgba(46,100,174,0.3);
    }
    /* END NEW MENU STYLING */


    /* Page Title */
    .dashboard-title {
        font-size: 32px;
        font-weight: 800;
        margin-bottom: 28px;
        color: var(--dark-text); 
        letter-spacing: 0.4px;
        position: relative;
        /* Adjusted padding slightly for the new column layout */
        padding-left: 20px; 
    }
    /* Add a subtle icon to the title */
    .dashboard-title::before {
        content: '⚙️'; 
        position: absolute;
        left: -15px; /* Moved icon slightly left */
        font-size: 28px;
        top: -2px;
    }


    /* Dashboard Grid (No change needed) */
    .dashboard-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 28px;
    }

    /* ================================
        CARD DESIGN (GRADIENT + ROUND)
       ================================ */
    .square-card {
        border-radius: 18px;
        background: linear-gradient(145deg, #ffffff 90%, var(--light-blue) 100%); 
        border: 1px solid #d6dcec;
        box-shadow: 0 10px 30px rgba(46,100,174,0.1); 
        overflow: hidden;
        transition: all 0.3s ease; 
    }

    /* NEW: Active/Hover State Visual Cue */
    .square-card:hover {
        transform: translateY(-5px); 
        box-shadow: 0 15px 35px rgba(46,100,174,0.18);
        border: 1px solid var(--primary-blue); 
    }

    /* Card Header */
    .square-card-header {
        background: linear-gradient(135deg, #2E64AE, #1d4c8b); 
        padding: 12px 18px !important; 
        color: #fff;
        font-size: 20px;
        font-weight: 600;
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-bottom: 2px solid rgba(255,255,255,0.15);
        border-radius: 18px 18px 0 0;
        min-height: 40px !important;
        position: relative;
    }
    
    /* MODIFIED: Card Header Icon */
    .square-card-header::before {
        content: attr(data-icon); 
        font-size: 24px;
        margin-right: 12px;
        opacity: 0.9;
        display: inline-block;
        vertical-align: middle;
        transition: transform 0.3s ease; 
    }

    .square-card-header:hover::before {
        transform: rotate(-5deg) scale(1.05); 
    }


    /* Compact Dropdown */
    .square-card-header select {
        background: rgba(255, 255, 255, 0.95);
        padding: 4px 8px !important; 
        height: 30px !important;
        font-size: 12px !important;
        border-radius: 8px !important;
        border: none;
        outline: none;
        color: var(--dark-text);
        font-weight: 600;
        width: 120px !important; 
        box-shadow: 0 1px 4px rgba(0,0,0,0.1); 
        transition: 0.2s;
        cursor: pointer; 
    }

    .square-card-header select:hover {
        box-shadow: 0 0 0 2px rgba(255,255,255,0.7);
    }

    /* Card Body */
    .square-card-body {
        padding: 18px 16px !important; 
        background: #ffffff;
        border-radius: 0 0 18px 18px;
    }

    /* ================================
            DATATABLE DESIGN
       ================================ */
    
    /* NEW: Clean Scrollbar Style (Webkit) */
    .square-card-body::-webkit-scrollbar {
        width: 6px;
        height: 6px;
    }

    .square-card-body::-webkit-scrollbar-thumb {
        background: rgba(46, 100, 174, 0.3);
        border-radius: 10px;
    }

    .square-card-body::-webkit-scrollbar-thumb:hover {
        background: var(--primary-blue);
    }


    /* Wrapper for DataTables controls */
    .dataTables_wrapper .row:first-child {
        margin-bottom: 20px; 
    }
    
    /* MODIFIED: Search Input Styling */
    .dataTables_filter input {
        border: 1px solid #cdd6e8;
        border-radius: 10px; 
        padding: 8px 14px; 
        outline: none;
        background: #fdfdff; 
        transition: all 0.3s;
        height: 38px; 
        font-size: 13px;
        color: #1b2b3c;
        width: 180px; 
    }

    .dataTables_filter input:focus {
        border-color: var(--primary-blue);
        box-shadow: 0 0 0 3px rgba(46,100,174,0.25); 
        background: #ffffff;
    }
    
    /* MODIFIED: Dropdown Styling (Show Entries) */
    .dataTables_length select {
        border: 1px solid #c7cfe0;
        border-radius: 10px;
        padding: 7px 14px;
        background: #ffffff;
        font-size: 13px;
        height: 38px;
        color: #1b2b3c;
        appearance: none; 
        background-image: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" fill="%232E64AE" viewBox="0 0 24 24"><path d="M7 10l5 5 5-5z"/></svg>'); 
        background-repeat: no-repeat;
        background-position: right 10px center;
        padding-right: 30px; 
        cursor: pointer;
    }
    
    /* MODIFIED: DataTables Label Text */
    .dataTables_length label,
    .dataTables_filter label {
        color: var(--secondary-text);
        font-weight: 500;
        font-size: 14px;
    }


    table.dataTable {
        width: 100% !important;
        border-radius: 12px !important;
        overflow: hidden;
        background: #fff;
        margin-top: 6px !important;
        border-collapse: separate !important; 
        border-spacing: 0;
    }

    /* Table Header */
    .table thead th {
        background: linear-gradient(180deg, #e7edf8, #d8e1f0) !important; 
        font-weight: 700;
        padding: 10px 10px !important; 
        color: #1b2b3c;
        border-bottom: 1px solid #cdd7e7;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        font-size: 11px; 
        vertical-align: middle;
        position: relative; 
    }
    
    /* Icons in Table Headers (No change) */
    .table thead th:nth-child(1)::before { content: '👤 '; } 
    .table thead th:nth-child(2)::before { content: '💬 '; } 
    .table thead th:nth-child(3)::before { content: '⏳ '; } 
    
    .table thead th::before {
        font-size: 14px;
        margin-right: 4px;
        opacity: 0.7;
        display: inline-block;
        vertical-align: text-top;
    }

    
    /* Specific styling for Days Pending column header */
    .table thead th:last-child {
        text-align: center;
        background: linear-gradient(180deg, #ffdcdc, #ffc0c0) !important; 
        color: var(--urgency-red);
    }


    /* Table Rows */
    .table tbody td {
        padding: 10px 10px !important; 
        background: #fff;
        border-bottom: 1px solid #eef2fa;
        color: #2a364d;
        font-size: 13px;
        line-height: 1.4;
    }

    /* MODIFIED: Hover Row */
    table.dataTable tbody tr:hover {
        background: #f0f5ff !important; 
        transition: all 0.2s ease-in-out;
        box-shadow: 0 2px 8px rgba(46,100,174,0.15); 
        transform: scale(1.008); 
        z-index: 10;
        position: relative; 
        cursor: pointer;
    }

    /* ================================
        URGENCY PULSE ANIMATION
    ================================ */
    @keyframes pulse-bg {
        0% { background-color: #fffafa; }
        50% { background-color: #ffeaea; } 
        100% { background-color: #fffafa; }
    }

    /* Target the Days Pending column cells */
    .table tbody tr td:last-child {
        text-align: center;
        font-weight: 800; 
        color: var(--urgency-red); 
        background-color: #fffafa; 
        animation: pulse-bg 4s infinite ease-in-out; 
    }
    
    /* ================================
        FOOTER/PAGINATION POLISH
    ================================ */

    /* Info Text */
    .dataTables_info {
        font-size: 13px; 
        color: var(--secondary-text);
        padding-top: 5px !important;
        font-style: italic; /* Added slight italic for distinction */
    }

    /* Pagination */
    .dataTables_wrapper .dataTables_paginate .paginate_button {
        padding: 7px 14px !important; /* Slightly more padding for bigger buttons */
        margin: 3px !important;
        border-radius: 12px !important; /* More rounded */
        background: #e7edfb !important;
        border: none !important;
        color: #2b3f57 !important;
        font-weight: 600;
        transition: all 0.2s; /* Added transition */
        font-size: 13px; /* Slightly larger text */
        box-shadow: 0 1px 4px rgba(0,0,0,0.1); /* Subtle shadow on buttons */
    }

    .dataTables_wrapper .dataTables_paginate .paginate_button:hover:not(.current) {
        background: #cddbff !important; 
        color: #1d2b48 !important;
        box-shadow: 0 2px 6px rgba(46,100,174,0.2);
    }

    .dataTables_wrapper .dataTables_paginate .paginate_button.current {
        background: var(--primary-blue) !important;
        color: #fff !important;
        font-weight: 700;
        box-shadow: 0 3px 10px rgba(46,100,174,0.5); /* Stronger shadow on active button */
    }
    
    /* "Previous" and "Next" text color refinement */
    .dataTables_wrapper .dataTables_paginate .paginate_button.disabled {
        color: #b3c0d1 !important;
        background: #f0f4f9 !important;
        box-shadow: none;
        cursor: default;
    }
    /* END FOOTER/PAGINATION POLISH */

   .square-card-header .controls {
    display: flex;
    gap: 10px;
    }

    .pending-title {
    font-size: 22px !important;   /* smaller font */
    font-weight: 600;
    margin-bottom: 20px;
    color: #2c3e50;
    }

    .dashboard-title::before {
        content: none !important;
    }

    .square-card-body {
    max-height: 500px;      /* Fixed height for the table area */
    overflow-y: auto;       /* Enable vertical scroll if table exceeds height */
    padding: 18px 16px !important;
    background: #ffffff;
    border-radius: 0 0 18px 18px;
    }

    /* ================================
        MOBILE RESPONSIVE STYLES
    ================================ */
    @media (max-width: 768px) {

    /* Make dashboard grid single column */
    .dashboard-grid {
        grid-template-columns: 1fr; /* Full width cards */
        gap: 20px; /* Reduce gap for mobile */
    }

    /* Card Header adjustments */
    .square-card-header {
        flex-direction: column; /* Stack title and controls */
        align-items: flex-start;
        gap: 8px;
        font-size: 16px;
        padding: 10px 12px !important;
    }

    .square-card-header .controls {
        flex-wrap: wrap; /* Make dropdowns wrap */
        gap: 8px;
    }

    /* Adjust dropdowns */
    .square-card-header select {
        width: 100% !important; /* Full width on mobile */
        font-size: 13px !important;
    }

    /* Make tables horizontally scrollable */
    .square-card-body table {
        display: block;         /* Force block for scrolling */
        width: 100% !important; /* Ensure full width */
        overflow-x: auto;       /* Horizontal scroll if table too wide */
        -webkit-overflow-scrolling: touch; /* Smooth scroll on iOS */
    }

    /* Reduce padding for table cells */
    .table tbody td, .table thead th {
        padding: 6px 8px !important;
        font-size: 12px;
    }

    /* Optional: reduce Days Pending animation size for mobile */
    .table tbody tr td:last-child {
        font-size: 11px;
        padding: 6px !important;
    }

    /* Ensure table description scrollbar fits mobile */
    .table-description {
        max-height: 50px;
        padding-right: 2px;
    }

    /* Pagination & info text smaller */
    .dataTables_info {
        font-size: 11px !important;
    }

    .dataTables_wrapper .dataTables_paginate .paginate_button {
        padding: 5px 10px !important;
        font-size: 11px !important;
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
    
                    <div class="square-card">
    
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

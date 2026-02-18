<?php
include("../includes/auth_check.php");
include("../includes/header.php");
include("../config/connect.php");
include("../includes/common.php");

/* Fetch complaints */
$complaints = my_complaint($_SESSION['memberid'], 0, 0);

/* Type labels + DISTINCT colors */
$typeMap = [
    1 => ['Equipment', 'badge-equipment'],   // Blue
    2 => ['Facility', 'badge-facility'],     // Teal
    3 => ['Safety', 'badge-safety'],          // Red
    4 => ['Process', 'badge-process'],        // Orange
    5 => ['HR', 'badge-hr'],                  // Grey
    6 => ['IT', 'badge-it'],                  // Dark
    7 => ['Purchase', 'badge-purchase'],      // Green
    8 => ['Training', 'badge-training'],      // Purple
    9 => ['Inventory', 'badge-inventory'],
    10 => ['Admin', 'badge-admin']     // Cyan (DIFFERENT from Facility)

];


/* Status labels + DISTINCT colors */
$statusMap = [
    0 => ['Pending', 'secondary'],     // grey (NOT yellow)
    1 => ['In Process', 'info'],
    2 => ['Closed', 'success'],
    3 => ['On Hold', 'warning']
];
?>

<style>
#complaintsTable {
    border-collapse: separate;
    border-spacing: 0 12px;
    width: 100%;
}

#complaintsTable thead th {
    background: linear-gradient(90deg, #0d6efd, #084298);
    color: #fff;
    font-weight: 600;
    border: none;
}

#complaintsTable tbody td {
    background: #f8f9fa;
    padding: 12px;
    vertical-align: top;
    border-top: 1px solid #dee2e6;
    border-bottom: 1px solid #dee2e6;
}

#complaintsTable tbody td:first-child {
    border-left: 1px solid #dee2e6;
    border-radius: 10px 0 0 10px;
}

#complaintsTable tbody td:last-child {
    border-right: 1px solid #dee2e6;
    border-radius: 0 10px 10px 0;
}

.member-name { font-weight: 600; }
.tool-name { font-weight: 600; color: #0d6efd; }
.small-muted { font-size: 12px; color: #6c757d; }

.desc {
    max-width: 420px;
    line-height: 1.5;
    word-break: break-word;
}

.desc-more { display: none; }

.desc-toggle {
    cursor: pointer;
    color: #0d6efd;
    font-weight: 600;
    font-size: 12px;
    margin-top: 4px;
}

.badge {
    font-size: 12px;
    padding: 6px 10px;
}

.track-link {
    font-weight: 600;
    text-decoration: underline;
}

/* Complaint Type Badges */
.badge-equipment { background:#0d6efd; color:#fff; }   /* Blue */
.badge-facility  { background:#20c997; color:#fff; }   /* Teal */
.badge-safety    { background:#dc3545; color:#fff; }   /* Red */
.badge-process   { background:#fd7e14; color:#fff; }   /* Orange */
.badge-hr        { background:#6c757d; color:#fff; }   /* Grey */
.badge-it        { background:#212529; color:#fff; }   /* Dark */
.badge-purchase  { background:#198754; color:#fff; }   /* Green */
.badge-training  { background:#6f42c1; color:#fff; }   /* Purple */
.badge-inventory { background:#0dcaf0; color:#000; }   /* Cyan */


/* =========================
   STEP 1: Mobile Responsive Table
   ========================= */
/* =========================
   ENHANCED Mobile View
   ========================= */
/* =========================
   PREMIUM Mobile UI
   ========================= */
@media (max-width: 768px) {

    #complaintsTable thead {
        display: none;
    }

    #complaintsTable,
    #complaintsTable tbody,
    #complaintsTable tr,
    #complaintsTable td {
        display: block;
        width: 100%;
    }

    #complaintsTable tr {
        margin-bottom: 20px;
        background: linear-gradient(180deg, #ffffff, #f8faff);
        border-radius: 18px;
        box-shadow: 0 8px 24px rgba(13,110,253,0.08);
        padding: 16px 14px;
        border: 1px solid #e3ebff;
        position: relative;
        overflow: hidden;
    }

    /* Accent strip on left */
    #complaintsTable tr::before {
        content: "";
        position: absolute;
        left: 0;
        top: 0;
        bottom: 0;
        width: 5px;
        background: linear-gradient(180deg, #0d6efd, #6610f2);
        border-radius: 18px 0 0 18px;
    }

    #complaintsTable tbody td {
        border: none !important;
        padding: 10px 8px;
        background: transparent;
        position: relative;
        z-index: 1;
    }

    #complaintsTable tbody td::before {
        content: attr(data-label);
        display: block;
        font-size: 11px;
        font-weight: 700;
        color: #6c757d;
        margin-bottom: 4px;
        text-transform: uppercase;
        letter-spacing: .5px;
    }

    /* Header feel for Member */
    td[data-label="Member"] .member-name {
        font-size: 16px;
        font-weight: 800;
        color: #0d6efd;
    }

    /* Type + Status inline pills */
    td[data-label="Type"] .badge,
    td[data-label="Status"] .badge {
        font-size: 12px;
        padding: 8px 14px;
        border-radius: 50px;
        box-shadow: 0 4px 10px rgba(0,0,0,0.08);
    }

    /* Tool highlight */
    .tool-name {
        font-size: 14px;
        font-weight: 700;
        color: #212529;
    }

    /* Description as content card */
    .desc {
        font-size: 14px;
        line-height: 1.75;
        background: #ffffff;
        border-radius: 12px;
        padding: 10px 12px;
        box-shadow: inset 0 0 0 1px #eef2ff;
    }

    /* Show more as pill button */
    .desc-toggle {
        display: inline-block;
        margin-top: 10px;
        padding: 6px 14px;
        border-radius: 50px;
        background: linear-gradient(135deg,#e7f1ff,#f1eaff);
        font-size: 12px;
        font-weight: 800;
        color: #0d6efd;
        text-transform: uppercase;
        letter-spacing: .5px;
        box-shadow: 0 4px 12px rgba(13,110,253,0.15);
    }

    /* Tracking link as button */
    .track-link {
        display: inline-block;
        margin-top: 10px;
        padding: 8px 16px;
        border-radius: 12px;
        background: linear-gradient(135deg,#0d6efd,#6610f2);
        color: #fff !important;
        font-weight: 700;
        text-decoration: none;
        box-shadow: 0 6px 16px rgba(102,16,242,0.25);
    }

    .track-link:hover {
        opacity: .9;
    }

    /* Spacing improvements */
    #complaintsTable td {
        margin-bottom: 4px;
    }
}


.badge-admin { 
    background: #7952b3; 
    color: #fff; 
}

</style>

<main class="container-fluid py-4">
<div class="row">

    <div class="col-md-2">
        <?php include("../includes/menu.php"); ?>
    </div>

    <div class="col-md-10">
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white fw-semibold">
                My Complaints
            </div>

            <div class="card-body">
                <div class="d-flex flex-wrap gap-2 align-items-end mb-3">
    <div>
        <label class="form-label mb-1 fw-semibold">Filter by Type</label>
        <select id="filterType" class="form-select form-select-sm">
            <option value="">All Types</option>
            <?php foreach ($typeMap as $k => $v): ?>
                <option value="<?= (int)$k ?>"><?= htmlspecialchars($v[0]) ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <div>
        <button type="button" id="applyFilter" class="btn btn-sm btn-primary">
            Filter
        </button>
        <button type="button" id="resetFilter" class="btn btn-sm btn-outline-secondary">
            Reset
        </button>
    </div>
</div>


            <?php if (empty($complaints)): ?>
                <div class="alert alert-info">No complaints found.</div>
            <?php else: ?>

            <div class="table-responsive">
                <?php
                    $showProcessCols = false;
                    foreach ($complaints as $cc) {
                        if ((int)$cc['type'] === 4) {
                            $showProcessCols = true;
                            break;
                        }
                    }
                ?>

            <table id="complaintsTable" class="table table-sm align-middle">
                <thead>
                    <tr>
                        <th>Member</th>
                        <th>Type</th>
                        <th class="d-none">TypeId</th>
                        <th>Component / Tool</th>
                        <th>Description</th>
                       <?php if ($showProcessCols): ?>
                            <th>Process Development</th>
                            <th>Anti-Contamination Development</th>
                        <?php endif; ?>
                        <th>Status</th>
                        <th>Tracking</th>
                    </tr>
                </thead>

                <tbody>
                <?php foreach ($complaints as $c): ?>

                    <?php
                    [$typeText, $typeColor] = $typeMap[$c['type']] ?? ['Unknown', 'dark'];
                    [$statusText, $statusColor] = $statusMap[$c['status']] ?? ['Unknown', 'dark'];

                    $toolName = getComplaintToolName($c);

                    /* RAW DB text (NO decode) */
                    $fullDesc  = trim($c['complaint_description']);
                    $shortDesc = mb_substr($fullDesc, 0, 200);
                    $hasMore   = mb_strlen($fullDesc) > 200;

                    $expectedCompletion = EC_date($c['complaint_id']);
                    ?>

                    <tr>
                        <td data-label="Member">
                            <div class="member-name"><?= htmlspecialchars(getName($c['member_id'])) ?></div>
                            <div class="small-muted"><?= display_date($c['time_of_complaint']) ?></div>
                        </td>

                        <td data-label="Type">
                            <span class="badge <?= $typeColor ?>">
                                <?= $typeText ?>
                            </span>
                        </td>

                        <td class="d-none"><?= (int)$c['type'] ?></td>


                        <td data-label="Component / Tool">
                            <div class="tool-name"><?= htmlspecialchars($toolName) ?></div>
                            <?php if ($expectedCompletion): ?>
                                <div class="small-muted">
                                    Expected: <?= display_date($expectedCompletion) ?>
                                </div>
                            <?php endif; ?>
                        </td>

                      <td class="desc" data-label="Description">
                            <span class="desc-short">
                                <?= renderComplaintDesc(shortDesc($shortDesc)) ?>
                                <?= $hasMore ? '…' : '' ?>
                            </span>

                            <?php if ($hasMore): ?>
                                <span class="desc-more">
                                    <?= renderComplaintDesc(shortDesc($fullDesc)) ?>
                                </span>
                                <div class="desc-toggle">Show More</div>
                            <?php endif; ?>

                            <?php if (!empty($c['upload_file'])): ?>
                                <a href="<?= htmlspecialchars($c['upload_file']) ?>" target="_blank">
                                    <i class="bi bi-file-earmark-text"></i>
                                </a>
                            <?php endif; ?>
                        </td>

                        <?php if ($showProcessCols): ?>

                            <?php if ((int)$c['type'] === 4): ?>
                                <td data-label="Process Development">
                                    <?= htmlspecialchars($c['process_develop'] ?? '-') ?>
                                </td>

                                <td data-label="Anti-Contamination Development">
                                    <?= htmlspecialchars($c['anti_contamination_develop'] ?? '-') ?>
                                </td>
                            <?php else: ?>
                                <td>-</td>
                                <td>-</td>
                            <?php endif; ?>

                        <?php endif; ?>


                        <td data-label="Status">
                            <span class="badge bg-<?= $statusColor ?>">
                                <?= $statusText ?>
                            </span>

                            <?php if ($c['status'] == 2 && $c['status_timestamp'] != '0000-00-00 00:00:00'): ?>
                                <div class="small-muted mt-1">
                                    <?= display_date($c['status_timestamp']) ?>
                                </div>
                                <div class="small-muted">
                                    <?= count_day($c['time_of_complaint'], $c['status_timestamp']) ?> day(s)
                                </div>
                            <?php endif; ?>
                        </td>

                        <td>
                            <?php if (count(trouble_track($c['complaint_id'], '')) > 0): ?>
                                <a href="#" class="track-link"
                                   onclick="return view(<?= (int)$c['complaint_id'] ?>, <?= (int)$c['type'] ?>);">
                                   View
                                </a>
                            <?php else: ?>
                                <span class="small-muted">No Data</span>
                            <?php endif; ?>
                        </td>
                    </tr>

                <?php endforeach; ?>
                </tbody>
            </table>
            </div>

            <?php endif; ?>

            </div>
        </div>
    </div>
</div>
</main>

<div id="dialog" title="View Tracking" style="display:none;font-size:12px;"></div>

<script>
$(function () {

    const table = $('#complaintsTable').DataTable({
        order: [],
        pageLength: 5,
        stateSave: true,
        responsive: false,
        autoWidth: false,
        columnDefs: [
            { targets: [2], visible: false, searchable: true } 
            // IMPORTANT: Update index based on where you placed TypeId column
        ]
    });

    // ⚠️ Adjust this index:
    // Member=0, Type=1, TypeId=2, Component=3, Description=4, Status=5, Tracking=6
    const typeIdColIndex = 2;

    $('#applyFilter').on('click', function () {
        const val = $('#filterType').val(); // "" or "1".."9"
        if (val === "") {
            table.column(typeIdColIndex).search("").draw();
        } else {
            table.column(typeIdColIndex).search("^" + val + "$", true, false).draw();
        }
    });

    $('#resetFilter').on('click', function () {
        $('#filterType').val("");
        table.column(typeIdColIndex).search("").draw();
    });

    $(document).on('click', '.desc-toggle', function () {
        const cell = $(this).closest('.desc');
        cell.find('.desc-short, .desc-more').toggle();
        $(this).text($(this).text() === 'Show More' ? 'Show Less' : 'Show More');
    });

});


function view(id, type) {
    $('#dialog').dialog({
        height: 600,
        width: '65%',
        modal: true
    }).load('view_tracks.php?complaint_id=' + id + '&type=' + type);

    return false;
}

</script>

<?php include("../includes/footer.php"); ?>

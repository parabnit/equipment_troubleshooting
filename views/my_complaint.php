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
    9 => ['Inventory', 'badge-inventory']     // Cyan (DIFFERENT from Facility)
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
        margin-bottom: 16px;
        background: #fff;
        border-radius: 12px;
        box-shadow: 0 2px 6px rgba(0,0,0,0.08);
        padding: 8px;
    }

    #complaintsTable tbody td {
        border: none !important;
        padding: 8px 6px;
        background: transparent;
    }

    #complaintsTable tbody td::before {
        content: attr(data-label);
        display: block;
        font-size: 11px;
        font-weight: 600;
        color: #6c757d;
        margin-bottom: 2px;
        text-transform: uppercase;
    }

    .desc {
        max-width: 100%;
    }

    .track-link {
        display: inline-block;
        margin-top: 6px;
    }
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

            <?php if (empty($complaints)): ?>
                <div class="alert alert-info">No complaints found.</div>
            <?php else: ?>

            <div class="table-responsive">
            <table id="complaintsTable" class="table table-sm align-middle">
                <thead>
                    <tr>
                        <th>Member</th>
                        <th>Type</th>
                        <th>Component / Tool</th>
                        <th>Description</th>
                        <th>Status</th>
                        <th>Tracking</th>
                    </tr>
                </thead>

                <tbody>
                <?php foreach ($complaints as $c): ?>

                    <?php
                    [$typeText, $typeColor] = $typeMap[$c['type']] ?? ['Unknown', 'dark'];
                    [$statusText, $statusColor] = $statusMap[$c['status']] ?? ['Unknown', 'dark'];

                    $toolName = getComplaintComponentName($c);

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

    $('#complaintsTable').DataTable({
        order: [],
        pageLength: 5,      // better for mobile
        stateSave: true,
        responsive: false, // we handle with CSS
        autoWidth: false
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

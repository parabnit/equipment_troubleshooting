<?php
include("../includes/auth_check.php");
include("../includes/header.php");
include("../config/connect.php");
include("../includes/common.php");

$type = isset($_GET['type']) ? check_number($_GET['type']) : 0;

$complaints = my_complaint($_SESSION['memberid'], $type, 0);

$types = [1 => "Equipment", 2 => "Facility", 3 => "Safety", 4 => "Process"];
?>

<style>
/* Card-style DataTable rows */
#complaintsTable {
    border-collapse: separate;
    border-spacing: 0 10px;
}

#complaintsTable thead th {
    background: #0d6efd;
    color: #fff;
    border: none;
    font-weight: 600;
}

#complaintsTable tbody tr td {
    background: #f8f9fa;
    vertical-align: top;
    border-top: 1px solid #dee2e6;
    border-bottom: 1px solid #dee2e6;
}

#complaintsTable tbody tr td:first-child {
    border-left: 1px solid #dee2e6;
    border-radius: 8px 0 0 8px;
}

#complaintsTable tbody tr td:last-child {
    border-right: 1px solid #dee2e6;
    border-radius: 0 8px 8px 0;
}

/* Member name */
.member-name {
    font-weight: 600;
    font-size: 15px;
}

/* Tool name */
.tool-name {
    font-weight: 600;
    color: #0d6efd;
}

/* Description */
.desc {
    color: #444;
}

/* Status badge */
.status-badge {
    font-weight: 600;
    color: #198754;
}

/* Track link */
.track-link {
    font-weight: 600;
    text-decoration: underline;
}

/* Small muted text */
.small-muted {
    font-size: 12px;
    color: #6c757d;
}
</style>

<main class="container py-4">
  <div class="row">
    <div class="col-md-3">
      <?php include("../includes/menu.php"); ?>
    </div>

    <div class="col-md-9">
      <div class="card shadow-sm">
        <div class="card-header bg-primary text-white">
          My Complaints - <?= $types[$type] ?? "Unknown" ?>
        </div>

        <div class="card-body">
          <?php if (count($complaints) === 0): ?>
            <div class="alert alert-info">No complaints found for this category.</div>
          <?php else: ?>

            <div class="table-responsive">
              <table id="complaintsTable" class="table table-sm align-middle">
                <thead>
                  <tr>
                    <th>Member</th>
                    <th>Tool</th>
                    <th>Description</th>
                    <?= $type == 4 ? '<th>Process Development</th><th>Anti Contamination</th>' : '' ?>
                    <th>Status</th>
                    <th>Tracking</th>
                  </tr>
                </thead>

                <tbody>
                <?php foreach ($complaints as $comp): ?>
                  <?php if ($comp['type'] != $type) continue; ?>

                  <?php
                  if ($comp['status'] == 0) $statusText = 'Pending';
                  elseif ($comp['status'] == 1) $statusText = 'In process';
                  elseif ($comp['status'] == 2) $statusText = 'Closed';
                  elseif ($comp['status'] == 3) $statusText = 'On Hold';
                  else $statusText = 'Unknown';

                  if ($type == 1 || $type == 4)
                    $toolName = ($comp['machine_id'] == 0) ? 'Miscellaneous' : getToolName($comp['machine_id']);
                  elseif ($type == 2)
                    $toolName = ($comp['machine_id'] == 0) ? 'Miscellaneous' : getToolName_facility($comp['machine_id']);
                  elseif ($type == 3)
                    $toolName = ($comp['machine_id'] == 0) ? 'Miscellaneous' : getToolName_safety($comp['machine_id']);
                  else
                    $toolName = 'N/A';

                  $expectedCompletion = EC_date($comp['complaint_id']);
                  ?>

                  <tr>
                    <td>
                      <div class="member-name"><?= getName($comp['member_id']) ?></div>
                      <div class="small-muted"><?= display_date($comp['time_of_complaint']) ?></div>
                    </td>

                    <td>
                      <div class="tool-name"><?= $toolName ?></div>
                      <?php if ($expectedCompletion): ?>
                        <div class="small-muted">
                          Expected: <?= display_date($expectedCompletion) ?>
                        </div>
                      <?php endif; ?>
                    </td>

                    <td class="desc">
                      <?= shortDesc(htmlspecialchars_decode($comp['complaint_description'])) ?>
                      <?= !empty($comp['upload_file'])
                        ? ' <a href="'.$comp['upload_file'].'" target="_blank">
                            <i class="bi bi-file-earmark ms-1"></i>
                          </a>'
                        : '' ?>
                    </td>

                    <?php if ($type == 4): ?>
                      <td><?= shortDesc($comp['process_develop']) ?></td>
                      <td><?= shortDesc($comp['anti_contamination_develop']) ?></td>
                    <?php endif; ?>

                    <td>
                      <div class="status-badge"><?= $statusText ?></div>
                      <?php
                      $resolved = $comp['status_timestamp'];
                      $validResolved = $resolved && $resolved !== '0000-00-00 00:00:00';
                      if ($statusText == 'Closed' && $validResolved):
                      ?>
                        <div class="small-muted"><?= display_date($resolved) ?></div>
                        <div class="small-muted">
                          <?= count_day($comp['time_of_complaint'], $resolved) ?> day(s)
                        </div>
                      <?php endif; ?>
                    </td>

                    <td>
                      <?php if (count(trouble_track($comp['complaint_id'], '')) > 0): ?>
                        <a href="#" class="track-link"
                           onclick="return view(<?= $comp['complaint_id'] ?>, <?= $type ?>);">
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

<!-- View Tracking Modal -->
<div id="dialog" title="View Tracking" style="display:none;font-size:12px;"></div>

<script>
$(document).ready(function() {
    $('#complaintsTable').DataTable({
        order: [],
        stateSave: true,
        pageLength: 10
    });
});

function view(complaint_id, type) {
    $('#dialog').dialog({
        height: 600,
        width: "60%",
        modal: true
    });
    $('#dialog').load("view_tracks.php?complaint_id=" + complaint_id + "&type=" + type);
    return false;
}
</script>

<?php include("../includes/footer.php"); ?>

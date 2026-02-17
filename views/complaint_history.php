<?php
include("../includes/auth_check.php");
include("../config/connect.php");
include("../includes/common.php");
$returnUrl = $_GET['return'] ?? '';
// Get complaint ID from URL
$complaint_id = isset($_GET['complaint_id']) ? (int)$_GET['complaint_id'] : 0;
$complaintHistory = [];

if ($complaint_id) {
    $res = mysqli_query($db_equip, "SELECT original_id, complaint_id FROM equipment_complaint WHERE complaint_id = $complaint_id LIMIT 1");
    if ($res && mysqli_num_rows($res) > 0) {
        $row = mysqli_fetch_assoc($res);
        $original_id = !empty($row['original_id']) ? (int)$row['original_id'] : (int)$row['complaint_id'];

        $historyQuery = "SELECT * FROM equipment_complaint 
                         WHERE complaint_id = $original_id 
                            OR original_id = $original_id 
                         ORDER BY complaint_id DESC";
        $resHistory = mysqli_query($db_equip, $historyQuery);

        while ($h = mysqli_fetch_assoc($resHistory)) {
            $complaintHistory[] = $h;
        }
    }
}

include("../includes/header.php");

// ---------- ORIGINAL COMPLAINT BASIC DETAILS ----------
$originalDetails = [];

if (!empty($complaintHistory)) {
    $original = end($complaintHistory);

    $typeMapping = [
        1 => "Equipment", 2 => "Facility", 3 => "Safety", 4 => "Process",
        5 => "HR", 6 => "IT", 7 => "Purchase", 8 => "Training", 9 => "Inventory",10 => "Admin"
    ];

    $originalDetails = [
        'date' => display_timestamp($original['time_of_complaint']),
        'name' => getName($original['member_id']),
        'type' => $typeMapping[(int)$original['type']] ?? 'Unknown'
    ];
}
?>

<style>
/* CSS UNCHANGED */
.card-history {
  max-width: 900px;
  margin: auto;
  margin-top: 25px;
  border-radius: 14px;
  background: #fff;
  box-shadow: 0 8px 22px rgba(0,0,0,0.08);
}

.card-history .card-header {
  background: linear-gradient(90deg, #0d6efd, #6f42c1);
  color: #fff;
  padding: 14px 18px;
  font-size: 18px;
  font-weight: 600;
}

.history-scroll {
  max-height: 75vh;
  overflow-y: auto;
  padding: 15px;
}

.history-item {
  background: #fff;
  border-radius: 10px;
  padding: 12px 15px;
  margin-bottom: 14px;
  border-left: 5px solid #0d6efd;
  position: relative;
  box-shadow: 0 4px 12px rgba(0,0,0,0.05);
}

.history-item::before {
  content: '';
  position: absolute;
  left: -9px;
  top: 16px;
  width: 16px;
  height: 16px;
  background: #0d6efd;
  border-radius: 50%;
  border: 3px solid #fff;
}

.type-badge {
  font-size: 11px;
  padding: 3px 10px;
  border-radius: 12px;
  font-weight: 600;
}

/* ✅ Admin Special Color */
.bg-purple {
  background-color: #6f42c1 !important;
  color: #fff !important;
}

.status-badge {
  font-size: 11px;
  padding: 4px 12px;
  border-radius: 20px;
  font-weight: 600;
}

.status-pending { background: #fff3cd; color: #856404; }
.status-inprocess { background: #d1e7dd; color: #0f5132; }
.status-onhold { background: #ffe5b4; color: #9a3412; }
.status-closed { background: #198754; color: #fff; }

.assigned-to {
  font-size: 12px;
  color: #0d6efd;
  font-weight: 600;
}

.fw-bold {
  font-size: 15px;
  margin-top: 6px;
}

.history-desc {
  font-size: 13px;
  line-height: 1.5;
  max-height: 90px;
  overflow: hidden;
}

.history-desc.expanded {
  max-height: none;
}

.toggle-desc {
  font-size: 12px;
  color: #0d6efd;
  cursor: pointer;
  margin-top: 4px;
}

.history-footer a {
  font-size: 12px;
  margin-right: 12px;
  color: #0d6efd;
  text-decoration: none;
}

.btn-navbar-back {
  background-color: #ffffff !important;
  color: #0d6efd !important;        /* Bootstrap primary (navbar blue) */
  border: 1px solid #0d6efd !important;
  font-weight: 500;
}

.btn-navbar-back:hover {
  background-color: #f8f9fa !important;
  color: #0a58ca !important;
  border-color: #0a58ca !important;
}
</style>

<div class="container py-4">
  <div class="row">
    <div class="col-md-3">
      <?php include("../includes/menu.php"); ?>
    </div>

    <div class="col-md-9">
      <div class="card card-history">
        <div class="card-header d-flex justify-content-between align-items-center">
          <span>Complaint History</span>
<?php if (!empty($originalDetails)) { ?>
          <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">

              <!-- LEFT INFO -->
              <div>
                  <span class="badge bg-light text-dark me-2">
                      Original Complaint
                  </span>

                  <small class="fw-semibold text-white">
                      <i class="fa fa-user me-1"></i>
                      <?= htmlspecialchars($originalDetails['name']) ?>
                      &nbsp;|&nbsp;
                      <i class="fa fa-tag me-1"></i>
                      <?= htmlspecialchars($originalDetails['type']) ?>
                      &nbsp;|&nbsp;
                      <i class="fa fa-clock me-1"></i>
                      <?= htmlspecialchars($originalDetails['date']) ?>
                  </small>
              </div>

              <!-- RIGHT BACK BUTTON -->
              <div>
                  <?php if (!empty($returnUrl)): ?>
                      <a href="<?= !empty($returnUrl) ? htmlspecialchars($returnUrl) : 'javascript:history.back()' ?>"
                        class="btn btn-navbar-back btn-sm px-3">
                        ← Back
                      </a>
                  <?php else: ?>
                      <a href="<?= !empty($returnUrl) ? htmlspecialchars($returnUrl) : 'javascript:history.back()' ?>"
                        class="btn btn-navbar-back btn-sm px-3">
                        ← Back
                      </a>
                  <?php endif; ?>
              </div>

          </div>
          <?php } ?>
        </div>

        <div class="card-body history-scroll">
          <?php if (!empty($complaintHistory)) { ?>
            <?php foreach ($complaintHistory as $index => $row) {

              $typeMapping = [
                1=>"Equipment",2=>"Facility",3=>"Safety",4=>"Process",
                5=>"HR",6=>"IT",7=>"Purchase",8=>"Training",9=>"Inventory",10=>'Admin'
              ];

              $typeColors = [
                'Equipment'=>'bg-primary text-white',
                'Facility'=>'bg-success text-white',
                'Process'=>'bg-warning text-dark',
                'Safety'=>'bg-danger text-white',
                'HR'=>'bg-info text-white',
                'IT'=>'bg-secondary text-white',
                'Purchase'=>'bg-dark text-white',
                'Training'=>'bg-warning text-dark',
                'Inventory'=>'bg-secondary text-white',
                'Admin'     => 'bg-purple text-white',
              ];

              $complaintType = $typeMapping[(int)$row['type']] ?? 'Unknown';
              $typeClass = $typeColors[$complaintType] ?? 'bg-light text-dark';

              $statusLabel = strtolower(getStatusLabel($row['status']));
              $statusClass = str_replace(' ','',$statusLabel);
              $assignedTo = !empty($row['allocated_to']) ? getName($row['allocated_to']) : 'Unassigned';

         $desc = $row['complaint_description'] ?? '';

    // 1. Completely strip all backslashes first
    $desc = stripslashes($desc);
    
    // 2. Remove any literal backslashes that stripslashes might have missed 
    // (useful if they were stored as double backslashes)
    $desc = str_replace('\\', '', $desc);

    // 3. Handle the "nn" delimiter. 
    // This looks for "nn" regardless of case and turns it into a real newline.
    $desc = preg_replace('/nn/i', "\n\n", $desc);

    // 4. Clean up any weird triple-newlines created by the step above
    $desc = preg_replace("/\n{3,}/", "\n\n", $desc);

    // 5. Convert to safe HTML and then to line breaks
    $desc = htmlspecialchars(trim($desc), ENT_QUOTES, 'UTF-8');
            ?>
            <div class="history-item">

              <!-- TYPE + STATUS + DATE -->
              <div class="d-flex align-items-center flex-wrap gap-2 mb-1">
                <span class="type-badge <?= $typeClass ?>"><?= $complaintType ?></span>
                <span class="status-badge status-<?= $statusClass ?>"><?= ucfirst($statusLabel) ?></span>
                <small class="text-muted"><?= display_timestamp($row['time_of_complaint']) ?></small>
              </div>

              <div class="assigned-to mb-1"><?= htmlspecialchars($assignedTo) ?></div>

              <div class="fw-bold"><?= htmlspecialchars(getComplaintComponentName($row)) ?></div>

<div class="history-desc" id="desc-<?= $index ?>">
                <?= nl2br($desc) ?>
            </div>
              <?php if (strlen($desc) > 150): ?>
                <div class="toggle-desc" onclick="toggleDesc(<?= $index ?>)">Show More</div>
              <?php endif; ?>

              <div class="history-footer mt-2">
                <?php if (!empty($row['upload_file'])) { ?>
                  <a href="#" onclick="openAttachmentModal('<?= htmlspecialchars($row['upload_file'],ENT_QUOTES) ?>');return false;">
                    <i class="fa fa-eye"></i> View Attachment
                  </a>
                <?php } ?>

                <?php if (count(trouble_track($row['complaint_id'],''))>0): ?>
                  <a href="#" onclick="return viewTrack(<?= (int)$row['complaint_id'] ?>,<?= (int)$row['type'] ?>);">Track</a>
                <?php else: ?>
                  <span class="text-muted">No Track</span>
                <?php endif; ?>
              </div>
            </div>
            <?php } ?>
          <?php } else { ?>
            <div class="text-center text-muted py-5">No complaint history available.</div>
          <?php } ?>
        </div>
      </div>
    </div>
  </div>
</div>
<!-- Attachment Preview Modal -->
<div class="modal fade" id="attachmentModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered">
    <div class="modal-content">

      <div class="modal-header">
        <h5 class="modal-title">Attachment Preview</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body p-0" style="height:75vh;">
        <iframe
          id="attachmentFrame"
          src=""
          style="width:100%; height:100%; border:none;"
        ></iframe>
      </div>

      <div class="modal-footer">
        <a id="downloadAttachment" href="#" download class="btn btn-primary">
          Download
        </a>
        <button class="btn btn-secondary" data-bs-dismiss="modal">
          Close
        </button>
      </div>

    </div>
  </div>
</div>
<script>
function toggleDesc(index){
  const d=document.getElementById('desc-'+index);
  d.classList.toggle('expanded');
  event.target.innerText=d.classList.contains('expanded')?'Show Less':'Show More';
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
<script>
function openAttachmentModal(filePath) {
  if (!filePath) {
    alert("Attachment not found");
    return;
  }

  const iframe = document.getElementById("attachmentFrame");
  const downloadLink = document.getElementById("downloadAttachment");

  // IMPORTANT: encode path to avoid space/special-char issues
  const safePath = encodeURI(filePath);

  iframe.src = safePath;
  downloadLink.href = safePath;

  const modal = new bootstrap.Modal(document.getElementById("attachmentModal"));
  modal.show();
}
</script>

<?php include("../includes/footer.php"); ?>

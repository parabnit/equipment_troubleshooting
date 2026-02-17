<?php
require_once '../config/connect.php';
require_once '../includes/common.php';

$complaint_id = check_number(mysqli_real_escape_string($db_equip, $_GET['complaint_id'] ?? 0));
$type         = check_number(mysqli_real_escape_string($db_equip, $_GET['type'] ?? 0));

$complaint = complaintByID($complaint_id, $type);
$details   = trouble_track($complaint_id, '');

$desc = $complaint['complaint_description'] ?? '';
$desc = str_replace(["\\r\\n", "\\n", "\\r"], "\n", $desc);
$desc = str_replace("\\", "", $desc);
$desc = nl2br(htmlspecialchars(trim($desc), ENT_QUOTES, 'UTF-8'));
?>

<style>
/* ==============================
   jQuery UI Dialog – SHOW ✕ Button
   ============================== */
.ui-dialog-titlebar-close {
  display: block !important;
  visibility: visible !important;
  opacity: 1 !important;
}

/* Optional: make close button clearer */
.ui-dialog-titlebar-close:hover {
  background: #dc3545 !important;
  border-color: #dc3545 !important;
}

/* ==============================
   Complaint Tracking Table
   ============================== */
.tracking-table {
  table-layout: fixed;
  width: 100%;
  border-collapse: collapse;
}

.tracking-table th,
.tracking-table td {
  padding: 8px 10px;
  font-size: 13px;
  vertical-align: top;
  word-break: break-word;
  white-space: normal;       /* allow wrap */
  overflow-wrap: anywhere;   /* wrap anywhere */
}

/* Header */
.tracking-table th {
  position: relative;
  background: #0d6efd;
  color: #fff;
  text-align: center;
  font-weight: 600;
}

/* Hover row */
.tracking-table tbody tr:hover {
  background: #f4f8ff;
}

/* ==============================
   Column Resize Handle
   ============================== */
.col-resizer {
  position: absolute;
  right: -4px;
  top: 0;
  width: 8px;
  height: 100%;
  cursor: col-resize;
  user-select: none;
}

.col-resizer::after {
  content: "";
  position: absolute;
  right: 2px;
  top: 0;
  width: 4px;
  height: 100%;
  background: rgba(255,255,255,0.9);
  border-left: 1px solid #fff;
  border-radius: 1px;
  transition: background 0.2s;
}

.tracking-table th:hover .col-resizer::after {
  background: #ffeb3b;
  border-left: 1px solid #ffc107;
}

/* ==============================
   Attachments
   ============================== */
file-attach,
.tracking-table td a.file-attach {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 4px 10px;
  border-radius: 20px;
  background: linear-gradient(135deg, #0d6efd, #6610f2);
  color: #fff !important;          /* White text */
  font-size: 12px;
  text-decoration: none;
}

.file-attach:hover,
.tracking-table td a.file-attach:hover {
  opacity: 0.9;
  color: #fff !important;          /* keep white on hover */
  text-decoration: none;
}
/* ==============================
   Links
   ============================== */
.tracking-table td a {
  color: #0d6efd;
  text-decoration: underline;
  word-break: break-all;
  overflow-wrap: anywhere;
  display: inline-block;
}

.tracking-table td a:hover {
  color: #084298;
  text-decoration: underline;
}

/* ==============================
   Responsive Container
   ============================== */
.table-responsive {
  overflow-x: auto;
}

/* Make close button show a proper X */
.ui-dialog-titlebar-close {
  width: 30px;
  height: 30px;
  background: none !important;
  border: none !important;
  font-size: 18px;
  font-weight: bold;
  color: #dc3545;      /* red X color */
  text-align: center;
  line-height: 28px;
}

.ui-dialog-titlebar-close:after {
  content: "×";       /* Multiplication sign = X */
}

.ui-dialog-titlebar-close:hover {
  color: #fff;
  background-color: #dc3545;
  border-radius: 50%;
}

/* ==============================
   MOBILE VIEW — Tracking Dialog
   ============================== */
@media (max-width: 768px) {

  /* Hide header */
  .tracking-table thead {
    display: none;
  }

  .tracking-table,
  .tracking-table tbody,
  .tracking-table tr,
  .tracking-table td {
    display: block;
    width: 100%;
  }

  .tracking-table tr {
    margin-bottom: 16px;
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 2px 6px rgba(0,0,0,0.08);
    padding: 8px;
  }

  .tracking-table td {
    border: none !important;
    padding: 8px 6px;
  }

  .tracking-table td::before {
    content: attr(data-label);
    display: block;
    font-size: 11px;
    font-weight: 600;
    color: #6c757d;
    margin-bottom: 2px;
    text-transform: uppercase;
  }

  /* Disable column resizer on mobile */
  .col-resizer {
    display: none !important;
  }

  /* Remove horizontal scroll */
  .table-responsive {
    overflow-x: visible;
  }

  /* Make attachments full width */
  .file-attach {
    width: 100%;
    justify-content: center;
  }
}

</style>


<div class="container mt-3">

  <h5><b>Created by:</b> <?= getName($complaint['member_id']); ?></h5>
  <p><b>Time of Complaint:</b> <?= display_timestamp($complaint['time_of_complaint']); ?></p>

<p>
  <b>Tool Name:</b>
  <span style="color:#0033FF;">
    <?= getComplaintComponentName($complaint); ?>
  </span>
</p>


  <?php if (!empty($complaint['allocated_to'])): ?>
    <p><b>Allocated To:</b> <?= getName($complaint['allocated_to']); ?></p>
  <?php endif; ?>

  <p><b>Description:</b><br><?= $desc ?></p>

  <?php if (empty($details)): ?>
    <div class="alert alert-warning">No tracking records found.</div>
  <?php else: ?>
    <div class="table-responsive">
      <table class="table table-bordered tracking-table">
        <thead>
          <tr>
            <th>Status marked by <span class="col-resizer"></span></th>
            <th>Working Team<br>Work done by <span class="col-resizer"></span></th>
            <th>Diagnosis <span class="col-resizer"></span></th>
            <th>Action Plan <span class="col-resizer"></span></th>
            <th>Action Taken <span class="col-resizer"></span></th>
            <th>Vendor Info <span class="col-resizer"></span></th>
            <th>Spare parts<br>Procurement Time <span class="col-resizer"></span></th>
            <th>Comments <span class="col-resizer"></span></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($details as $row): ?>
            <?php
            $row = array_map(fn($v) => htmlspecialchars(stripslashes($v ?? ''), ENT_QUOTES, 'UTF-8'), $row);
            ?>
            <tr>
              <td data-label="Status Marked By">
                <?= getName($row['status_mark_by']) ?><br><?= display_date($row['timestamp']) ?>
              </td>
              <td data-label="Working Team / Work Done By">
                <?= $row['working_team'] ?><br><?= $row['work_done_by'] ?>
              </td>

		<td data-label="Diagnosis">
              <?= htmlspecialchars_decode(makeLinksClickable($row['diagnosis'])) ?>
              <?php if (!empty($row['file'])): ?>
                <br>
                <a href="<?= htmlentities($row['file']) ?>" target="_blank" class="file-attach">Attachment</a>
              <?php endif; ?>
            </td>

            <td data-label="Action Plan">
              <?= htmlspecialchars_decode(makeLinksClickable($row['action_taken'])) ?>
              <?php if (!empty($row['file'])): ?>
                <br>
                <a href="<?= htmlentities($row['file']) ?>" target="_blank" class="file-attach">Attachment</a>
              <?php endif; ?>
            </td>              <td>
                <?= $row['action_taken'] ?>
                <?php if (!empty($row['file'])): ?>
                  <br>
                  <a href="<?= $row['file'] ?>" target="_blank" class="file-attach">Attachment</a>
                <?php endif; ?>
              </td>

              <td data-label="Vendor Info">
                <?= htmlspecialchars_decode($row['vendor_name']) ?><br><?= htmlspecialchars_decode($row['vendor_contact']) ?><br><?= htmlspecialchars_decode($row['vendor_comments']) ?></td>
              <td data-label="Spare Parts / Procurement">
                <?= htmlspecialchars_decode($row['spare_parts']) ?><br><?= htmlspecialchars_decode($row['cost_spare_parts']) ?><br><?= htmlspecialchars_decode($row['procurement_time_spares']) ?></td>
             <td data-label="Comments">
                <?= htmlspecialchars_decode($row['comments']) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>

<script>
(function() {
  let startX, startWidth, currentTh;

  // Attach events to all resizers
  document.querySelectorAll(".col-resizer").forEach(resizer => {
    resizer.addEventListener("mousedown", function(e) {
      currentTh = e.target.closest("th");
      startX = e.pageX;
      startWidth = currentTh.offsetWidth;

      currentTh.classList.add("resizing");

      // Attach events to document dynamically
      function onMouseMove(e) {
        if (!currentTh) return;
        let newWidth = startWidth + (e.pageX - startX);
        if (newWidth > 80) currentTh.style.width = newWidth + "px";
      }

      function onMouseUp() {
        currentTh.classList.remove("resizing");
        currentTh = null;
        document.removeEventListener("mousemove", onMouseMove);
        document.removeEventListener("mouseup", onMouseUp);
      }

      document.addEventListener("mousemove", onMouseMove);
      document.addEventListener("mouseup", onMouseUp);

      e.preventDefault(); // prevent text selection
    });
  });
})();
</script>

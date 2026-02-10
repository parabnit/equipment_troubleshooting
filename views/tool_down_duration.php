<?php
include("../includes/auth_check.php");
include("../includes/header.php");
require_once("../config/connect.php");
require_once("../includes/common.php");

// $link1 = $db_equip;

$type = isset($_GET['type']) ? check_number(mysqli_real_escape_string($db_equip, $_GET['type'])) : 0;
$tools = downtool_list($type);

foreach ($tools as &$tool) {
  $time = complaintByID($tool['complaint_id'], $type);
  $tool['down_duration'] = isset($time['time_of_complaint'])
    ? count_day($time['time_of_complaint'], date("Y-m-d"))
    : '';
}
unset($tool);

function getRowColor($duration)
{
  if ($duration > 179) return '#FF99FF';
  if ($duration > 30)  return '#FFFF99';
  if ($duration > 6)   return '#66CCFF';
  return '#A6D35E';
}
function getDurationLabel($duration)
{
  if ($duration > 179) return '> 6 months';
  if ($duration > 30)  return '>1 month < 6 months';
  if ($duration > 6)   return '> week < 1 month';
  return 'Current Week';
}

?>
<style>
  /* Modern Table Container */
.modern-table {
  border-radius: 10px;
  overflow: hidden;
  border: 1px solid #e3e6f0;
  background: #fff;
}

/* Modern Header */
.modern-thead th {
  background: linear-gradient(135deg, #0d6efd, #0a58ca);
  color: #fff;
  font-weight: 600;
  font-size: 13px;
  letter-spacing: 0.3px;
  border: none !important;
  padding: 10px;
  text-transform: uppercase;
}

/* Body Cells */
.modern-table td {
  vertical-align: middle;
  font-size: 13px;
  padding: 9px;
  border-color: #f1f3f5;
}

/* Hover Effect */
.modern-table tbody tr:hover {
  box-shadow: inset 0 0 0 9999px rgba(13,110,253,0.05);
  cursor: pointer;
  transition: all 0.2s ease;
}

/* Complaint ID Look */
.modern-table td:nth-child(2) {
  font-family: monospace;
  font-weight: 600;
  color: #0d6efd;
}

/* Duration Pill */
.modern-table td:last-child {
  font-weight: 600;
  text-align: center;
  border-radius: 20px;
}

/* Make table responsive look cleaner */
.table-responsive {
  border-radius: 12px;
  box-shadow: 0 5px 20px rgba(0,0,0,0.05);
}

.cid-pill {
  display: inline-block;
  padding: 4px 10px;
  background: #eef4ff;
  color: #1d4ed8;
  border-radius: 20px;
  font-weight: 600;
  font-family: monospace;
  font-size: 12px;
  border: 1px solid #dbeafe;
}

</style>

<div class="container-fluid">
  <div class="row">
    <div class="col-md-3">
      <?php include("../includes/menu.php"); ?>
    </div>
    <div class="col-md-9">
      <h5 class="mt-3">Tool Down Duration: <?php echo date("d M Y"); ?></h5>
      <div class="table-responsive">
        <table class="table table-hover table-sm mt-4 modern-table">
          <thead class="modern-thead">
            <tr>
              <th>Sr. No</th>
              <th>Complaint ID</th>   <!-- ✅ ADD THIS -->
              <th>Tool Name</th>
              <th>Tracking View</th>
              <th>Down Since</th>
              <th>Expected Completion</th>
              <th>Duration</th>
            </tr>
          </thead>


          <tbody>
            <?php $j = 1;
            foreach ($tools as $tool):
              if ($type == 1 && getlocation($tool['machine_id']) == 'Facility') continue;
              $duration = $tool['down_duration'];
              $color = getRowColor($duration);
            ?>
              <tr style="background-color:<?= $color ?> !important">
                <td><?= $j++ ?></td>
                 <!-- ✅ ADD THIS: Complaint ID -->
                <td>
                  <span class="cid-pill">
                    Complaint #<?= htmlspecialchars($tool['complaint_id']) ?>
                  </span>
                </td>
                <td>
                  <?php
                  if ($type == 1 || $type == 4) echo getToolName($tool['machine_id']);
                  elseif ($type == 2) echo getToolName_facility($tool['machine_id']);
                  elseif ($type == 3) echo getToolName_safety($tool['machine_id']);
                  ?>
                </td>
                <td>
                  <?php
                  $view = trouble_track($tool['complaint_id'], '');
                  echo count($view) > 0
                    ? '<a class="over" href="#" onclick="return view(' . $tool['complaint_id'] . ',' . $tool['type'] . ');">View</a>'
                    : 'No Data';
                  ?>
                </td>
                <td><?= display_timestamp(complaintByID($tool['complaint_id'], $type)['time_of_complaint']) ?></td>
                <td><?= EC_date($tool['complaint_id']) ? display_date(EC_date($tool['complaint_id'])) : '' ?></td>
                <td><?= getDurationLabel($duration) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <div id="dialog" title="View tracking" style="display: none; font-size: 11px;"></div>

      <script>
        function view(complaint_id, type) {

          $('#dialog').dialog({
            height: 600,
            width: "95%",
            modal: true
          });
          $('#dialog').load("view_tracks.php?complaint_id=" + complaint_id + "&type=" + type);
          return false;
        }
      </script>
    </div>
  </div>
</div>

<style>
  .table.table-bordered tbody tr[style] td {
    background-color: inherit !important;
  }
</style>
<?php include("../includes/footer.php"); ?>
<?php
include("../includes/auth_check.php");
include("../includes/header.php");
require_once("../config/connect.php");
require_once("../includes/common.php");

// ---------------- CONFIG ----------------
$msg = '';
$msg1 = '';

$periodicChecks = [

  'Electrical' => [
    'daily_ups_log'        => 'Daily UPS log',
    'dg_weekly'      => 'DG weekly check',
    'ir_panel'       => 'Electrical Panel IR Gun',
    'ups_battery'    => 'UPS Batteries Readings',
    'grounding'      => 'Critical Grounding Reading',
    'power_failure'  => 'Power Failure log',
    'exhaust_audit'  => 'Exhaust blower audit (Monthly)',
    'ups_fan'        => 'UPS fan weekly check'
  ],

  'AHU & Chiller' => [
    'water_filter_change_log'   => 'Water filter change log',
    'ir_ahu'         => 'IR Gun weekly – AHU & Chiller panel',
    'datalogger'     => 'Datalogger deviation data analysis',
    'chiller_level'  => 'Chiller Tank level check',
    'icms_fault'     => 'ICMS fault log',
    'valve_ir'       => 'Drier & expansion valve IR scan',
    'dehumidifier'   => 'Dehumidifier servicing',
    'ahu_cleaning'   => 'AHU ODU cleaning',
    'chiller_clean'  => 'Chiller Dry cleaning'
  ],

  'Gas Team' => [
    'non_toxic'      => 'Non toxic daily gas usage',
    'gld_weekly'     => 'GLD weekly check',
    'toxic_pressure' => 'Toxic cylinder pressure (Monthly)',
    'toxic_usage'    => 'Daily usage of toxic gases',
    'n2_plant'       => 'N2 plant checks'
  ],

  // ✅ NEW EMT SECTION
  'EMT' => [
    'hot_plates'        => 'Hot plates',
    'spinners'          => 'Spinners',
    'nano_ro_refill'    => 'Nano RO water tank refill'
  ]

];

// ---------------- FILE UPLOAD HANDLER ----------------
function handleFileUpload($field)
{
  global $msg, $msg1;

  if (empty($_FILES[$field]['tmp_name'])) {
    $msg1 = "Please select file!";
    return;
  }

  $file = $_FILES[$field];
  $ext  = pathinfo($file['name'], PATHINFO_EXTENSION);

  if (strtolower($ext) !== 'xlsx') {
    $msg1 = "Only .xlsx files are allowed!";
    return;
  }

  $dest = "../periodic_checks/{$field}.xlsx";

  if (move_uploaded_file($file['tmp_name'], $dest)) {
    chmod($dest, 0644);
    $msg = "File uploaded successfully!";
  } else {
    $msg1 = "File upload failed!";
  }
}

// ---------------- SUBMIT DETECTION ----------------
foreach ($_POST as $key => $value) {
  if (str_starts_with($key, 'Submit_')) {
    $field = str_replace('Submit_', '', $key);
    handleFileUpload($field);
    break;
  }
}
?>
<style>
  .impact-header {
    background: #fff;
    border-left: 6px solid #0d6efd;
    padding: 14px 18px;
  }

  .impact-title {
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 1.15rem;
    font-weight: 700;
    color: #0d6efd;
  }

  .impact-count {
    font-size: 0.75rem;
    background: #0d6efd;
    color: #fff;
    padding: 4px 8px;
    border-radius: 20px;
  }

  .impact-subline {
    font-size: 0.75rem;
    color: #6c757d;
    margin-top: 2px;
  }
</style>

<main class="container py-4">
  <div class="row">

    <!-- Sidebar -->
    <div class="col-md-3">
      <?php include("../includes/menu.php"); ?>
    </div>

    <!-- Content -->
    <div class="col-md-9">

      <?php if ($msg): ?>
        <div class="alert alert-success text-center fw-bold"><?= $msg ?></div>
      <?php endif; ?>

      <?php if ($msg1): ?>
        <div class="alert alert-danger text-center fw-bold"><?= $msg1 ?></div>
      <?php endif; ?>

      <form method="post" enctype="multipart/form-data">

        <?php foreach ($periodicChecks as $section => $items): ?>
          <div class="card shadow-sm mb-4">
          <div class="card-header impact-header">
  <div class="impact-title">
    <i class="bi bi-lightning-charge-fill"></i>
    <?= $section ?>
    <span class="impact-count"><?= count($items) ?> checks</span>
  </div>

</div>


            <div class="card-body table-responsive">
              <table class="table table-bordered text-center align-middle">
                <thead class="table-primary">
                  <tr>
                    <th style="width:5%">No.</th>
                    <th>Type</th>
                    <th style="width:10%">File</th>
                    <?php if (check_permission('facility', $_SESSION['memberid'])): ?>
                      <th style="width:20%">Upload</th>
                      <th style="width:10%">Submit</th>
                    <?php endif; ?>
                  </tr>
                </thead>
                <tbody>

                  <?php $i = 1; foreach ($items as $key => $label): ?>
                    <tr>
                      <td><?= $i++ ?></td>
                      <td class="text-start"><?= $label ?></td>
                      <td>
                       <?php
                          $fileName = strtolower(str_replace(' ', '_', $label));
                          $fileName = preg_replace('/[^a-z0-9_]/', '', $fileName);
                          $path = "../periodic_checks/" . $fileName . ".xlsx";

                          if (file_exists($path)) {
                            echo "<a href='{$path}' target='_blank' class='btn btn-outline-success btn-sm'>
                                    <i class='bi bi-download'></i>
                                  </a>";
                          } else {
                            echo "<span class='text-muted'>No file</span>";
                          }
                        ?>

                      <?php if (check_permission('facility', $_SESSION['memberid'])): ?>
                        <td>
                          <input type="file" name="<?= $key ?>" class="form-control" accept=".xlsx">
                        </td>
                        <td>
                          <button type="submit" name="Submit_<?= $key ?>" class="btn btn-primary btn-sm">
                            Submit
                          </button>
                        </td>
                      <?php endif; ?>
                    </tr>
                  <?php endforeach; ?>

                </tbody>
              </table>
            </div>
          </div>
        <?php endforeach; ?>

      </form>

    </div>
  </div>
</main>

<?php include("../includes/footer.php"); ?>

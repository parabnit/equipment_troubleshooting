<?php
include("../includes/auth_check.php");
include("../includes/header.php");
require_once("../config/connect.php");
require_once("../includes/common.php");


// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);

// ---- Config ----
$uploadFields = ['ahu', 'chiller', 'eblower', 'earthpit', 'ups'];

$types_eq = [
  'ahu' => 'AHU',
  'chiller' => 'Chiller',
  'eblower' => 'Exhaust Blower',
  'earthpit' => 'Earth Pit',
  'ups' => 'UPS Battery'
];

$msg = '';
$msg1 = '';

// ---- Handle File Upload ----
function handleFileUpload($field)
{
  global $msg, $msg1;

  if (empty($_FILES[$field]['tmp_name'])) {
    $msg1 = "Please select $field file!";
    return;
  }

  $file = $_FILES[$field];
  $file_type = pathinfo($file['name'], PATHINFO_EXTENSION);
  $allowed_ext = ['xlsx'];

  if (!in_array(strtolower($file_type), $allowed_ext)) {
    $msg1 = "Only .xlsx files are allowed!";
    return;
  }
  $file_dest = "../periodic_checks/{$field}.xlsx";
  if (move_uploaded_file($file['tmp_name'], $file_dest)) {
    chmod($file_dest, 0644); 
    $msg = strtoupper($field) . " file uploaded successfully!";
  } else {
    $msg1 = "Failed to upload file.";
  }
}

// ---- Detect Button Click ----
foreach ($uploadFields as $field) {
  if (isset($_POST['Submit_' . $field])) {
    handleFileUpload($field);
    break;
  }
}
?>

<main class="container py-4">
  <div class="row">
    <!-- Sidebar -->
    <div class="col-md-3">
      <?php include("../includes/menu.php"); ?>
    </div>

    <!-- Main Content -->
    <div class="col-md-9">
      <div class="card shadow-sm">
        <div class="card-header bg-primary text-white">
          Periodic Checks
        </div>
        <div class="card-body">
          <?php if ($msg): ?>
            <div class="mb-3 text-success text-center fw-bold"><?= $msg; ?></div>
          <?php endif; ?>
          <?php if ($msg1): ?>
            <div class="mb-3 text-danger text-center fw-bold"><?= $msg1; ?></div>
          <?php endif; ?>

          <form method="post" enctype="multipart/form-data">
            <div class="table-responsive">
            <table class="table table-bordered text-center align-middle">
              <thead class="table-primary">
                <tr>
                  <th>No.</th>
                  <th>Type</th>
                  <th>File</th>
                  <?php if (check_permission('facility', $_SESSION['memberid'])): ?>
                    <th>Upload</th>
                    <th>Submit</th>
                  <?php endif; ?>
                </tr>
              </thead>
              <tbody>
                <?php $i = 1; ?>
                <?php foreach ($types_eq as $key => $label): ?>
                  <tr>
                    <td><?= $i++; ?></td>
                    <td><?= $label; ?></td>
                    <td>
                      <?php
                      
                      $path = "../periodic_checks/{$key}.xlsx";
                      echo "<script>console.log('Checking file: $path');</script>";

                      if (file_exists($path)) {
                        echo "<a target='_blank' href='{$path}' class='btn btn-outline-success btn-sm' title='Download File'>
                                <i class='bi bi-download'></i>
                              </a>";
                      } else {
                        echo "<span class='text-muted'>No file found!</span>";
                      }
                      ?>
                    </td>
                    <?php if (check_permission('facility', $_SESSION['memberid'])): ?>
                      <td><input type="file" name="<?= $key; ?>" class="form-control" accept=".xlsx"></td>
                      <td><button type="submit" name="Submit_<?= $key; ?>" class="btn btn-primary">Submit</button></td>
                    <?php endif; ?>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</main>

<?php include("../includes/footer.php"); ?>
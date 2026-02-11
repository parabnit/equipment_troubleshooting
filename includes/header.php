<?php
if (session_status() === PHP_SESSION_NONE) session_start();
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>IITBNF Troubleshooting</title>

  <!-- Bootstrap 5 CSS -->
 <!-- Bootstrap 5 CSS -->
<link rel="stylesheet" href="../assets/css/bootstrap.min.css">

<!-- Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">


<!-- DataTables Bootstrap 5 CSS -->
<link rel="stylesheet" href="../assets/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="../assets/css/jquery.dataTables.min.css">

<!-- jQuery -->
<!-- <script src="../assets/js/jquery-3.6.0.min.js"></script> -->
<script src="../assets/js/jquery-3.7.1.min.js"></script>

<!-- jQuery UI -->
<link rel="stylesheet" href="../assets/css/jquery-ui.css">
<script src="../assets/js/jquery-ui.min.js"></script>

<!-- Bootstrap Icons -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">


<!-- DataTables JS -->
<script src="../assets/js/jquery.dataTables.min.js"></script>
<!-- <script src="../assets/js/dataTables.bootstrap5.min.js"></script> -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>


  
</head>

<body>

<header class="bg-primary text-white mb-4 shadow-sm position-relative" style="height:80px;">
  <div class="container-fluid position-relative h-100 d-flex justify-content-center align-items-center">
    
    <!-- Left: Logo (hidden on mobile) -->
    <div class="position-absolute top-0 start-0 h-100 d-none d-md-flex align-items-center ps-2">
      <img src="../assets/images/mainlogo.png" alt="Logo" class="h-100 w-auto">
    </div>
    
    <!-- Centered Title -->
    <h1 class="text-center fs-5 fs-md-4 m-0">
      IITBNF Troubleshooting
    </h1>
    
    <!-- Right: Date + Version (hidden on mobile) -->
    <div class="position-absolute top-0 end-0 text-end pe-3 d-none d-md-block">
      <div class="small"><?php echo date("l, d M Y h:i A"); ?></div>
      <small>Released on Feb 16, 2026 | Version 2026.02.16</small>
    </div>
  </div>
</header>

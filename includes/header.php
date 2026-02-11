<?php
if (session_status() === PHP_SESSION_NONE) session_start();
?>
<style>
/* =========================
   MODERN HEADER STYLES
========================= */
.modern-header {
  background: linear-gradient(135deg, #0d6efd, #6610f2);
  height: 90px;
  position: relative;
  box-shadow: 0 8px 25px rgba(0,0,0,0.15);
}

.header-glass {
  background: rgba(255, 255, 255, 0.12);
  backdrop-filter: blur(8px);
  border-radius: 16px;
  padding: 10px 20px;
  height: 70px;
}

.header-title {
  font-weight: 600;
  letter-spacing: 0.5px;
}

.header-meta {
  font-size: 0.75rem;
  opacity: 0.85;
}

.header-logo {
  height: 55px;
}

.header-icon {
  font-size: 1.4rem;
  opacity: 0.9;
}
</style>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Equipment Troubleshooting</title>

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

<header class="modern-header mb-4">
  <div class="container-fluid h-100 d-flex align-items-center">
    
    <div class="w-100 header-glass d-flex align-items-center justify-content-between shadow">

      <!-- Left: Logo + App Name -->
      <div class="d-flex align-items-center gap-3">
        <img src="../assets/images/mainlogo.png" 
             alt="Logo" 
             class="header-logo d-none d-md-block">

        <div class="text-white">
          <div class="d-flex align-items-center gap-2">
            <i class="bi bi-tools header-icon"></i>
            <h1 class="m-0 fs-5 fs-md-4 header-title">
              IITBNF Troubleshooting
            </h1>
          </div>
          <div class="header-meta">
            Smart Equipment Support System
          </div>
        </div>
      </div>

      <!-- Right: Date + Version -->
      <div class="text-end text-white d-none d-md-block">
        <div class="small">
          <i class="bi bi-clock-history me-1"></i>
          <?php echo date("l, d M Y h:i A"); ?>
        </div>
        <div class="header-meta">
          <i class="bi bi-info-circle me-1"></i>
          Released May 20, 2021 | Version 2025.08.13
        </div>
      </div>

    </div>
  </div>
</header>


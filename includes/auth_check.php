<?php
session_start();

if (!isset($_SESSION['login']) || empty($_SESSION['login'])) {
  header("Location: /equipment_troubleshooting/views/login.php");
  exit;
}
?>
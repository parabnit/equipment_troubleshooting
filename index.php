<?php
header("Location:../iitbnfonline/login.php");
exit();

session_start();

// If already logged in, redirect to complaints page
if (isset($_SESSION['login']) && $_SESSION['login'] != '') {
    header("Location: views/complaint.php");
    exit();
}

// Otherwise show the login form
header("Location: views/login.php");
exit();

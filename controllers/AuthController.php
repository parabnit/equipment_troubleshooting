<?php
session_start(); // Start session to access or set session variables
require_once("../config/connect.php"); // Connect to the database

// Check if form is submitted via POST and login button is clicked
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['loginsubmit'])) {

    // Get user input
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $captcha_code = trim($_POST['captcha_code']);

    // ✅ 1. Validate CAPTCHA
    if (
        empty($_SESSION['captcha_code']) || // No CAPTCHA in session
        strcasecmp($_SESSION['captcha_code'], $captcha_code) !== 0 // Not matching
    ){
        echo "Invalid Captcha Code.";
        exit;
    }

    // ✅ 2. Sanitize input and hash password
    $email = mysqli_real_escape_string($db_slot, $email);
    $encrypted_password = md5($password); // Match the stored password format

    // ✅ 3. Check if email exists
    $q = mysqli_query($db_slot, "SELECT * FROM login WHERE email = '$email'");
    if (!$q || mysqli_num_rows($q) <= 0) {
        echo "Invalid Email.";
        exit;
    }

    $row = mysqli_fetch_assoc($q); // Get user row

    // ✅ 4. Check password match
    if ($encrypted_password !== $row['password']) {
        echo "Invalid Password.";
        exit;
    }

    // ✅ 5. Check account expiry
    if (strtotime($row['expiry_date']) < strtotime(date('Y-m-d'))) {
        echo "Your account has expired.";
        exit;
    }

    // ✅ 6. Set session values on success
    $_SESSION['memberid'] = $row['memberid'];
    $_SESSION['login'] = $row['email'];
    $_SESSION['role'] = ($row['memberid'] == 1) ? 1 : 0; // Set role (admin if memberid = 1)

    // ✅ 7. Check IT admin permission
    $check_role = mysqli_query($db_slot, "SELECT memberid FROM role_permissions WHERE role = 1 AND memberid = {$row['memberid']}");
    $role_row = mysqli_fetch_assoc($check_role);
    $_SESSION['role_ITadmin'] = ($role_row && $role_row['memberid']) ? 1 : 0;

    // ✅ 8. Return success response for AJAX
    echo "ok";
    exit;
} else {
    // If accessed directly without form submission
    echo "Invalid access.";
    exit;
}

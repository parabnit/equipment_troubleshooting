<?php
function connect_db($host, $user, $pass, $dbname) {
    $conn = mysqli_connect($host, $user, $pass, $dbname);
    if (!$conn) {
        die("Connection to $dbname failed: " . mysqli_connect_error());
    }
    return $conn;
}

// All connections
$db_slot     = connect_db("localhost", "root", "Pass@1234", "slotbooking");
$db_equip    = connect_db("localhost", "root", "Pass@1234", "equipment_troubleshooting");
$db_safety   = connect_db("localhost", "root", "Pass@1234", "safety");
$db_facility = connect_db("localhost", "root", "Pass@1234", "facility_management");
?>

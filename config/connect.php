<?php
function connect_db($host, $user, $pass, $dbname) {
    $conn = mysqli_connect($host, $user, $pass, $dbname);
    if (!$conn) {
        die("Connection to $dbname failed: " . mysqli_connect_error());
    }
    return $conn;
}

// All connections
$db_slot     = connect_db("10.107.103.8", "root", "2022*Iitbnf!", "slotbooking");
$db_equip    = connect_db("10.107.103.8", "root", "2022*Iitbnf!", "iitbnf_troubleshooting");
$db_safety   = connect_db("10.107.103.8", "root", "2022*Iitbnf!", "safety");
$db_facility = connect_db("10.107.103.8", "root", "2022*Iitbnf!", "facility_management");
?>

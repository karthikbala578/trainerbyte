<?php

//for development
$host = "localhost";
$user = "root";
$pass = "";
$db   = "ipskfimy_tbadmin";
// $port = 3306;     

/* $host = "localhost";
$user = "root";
$pass = "";
$db   = "trainer_byte_cms"; */

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}
?>

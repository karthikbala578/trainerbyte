<?php

//for development
$host = "localhost";
$user = "ipskfimy_a180326";
$pass = "BhDbSb2026!";
$db   = "ipskfimy_tbadmin";



$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}
?>

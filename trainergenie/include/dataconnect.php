<?php

//for development
$host = "localhost";
$user = "root";
$pass = "";
$db   = "trainer_genie";
// $port = 3306;   



$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}
?>
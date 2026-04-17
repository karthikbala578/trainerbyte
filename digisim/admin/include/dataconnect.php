<?php

//for development
$host = "localhost";
$user = "root";
$pass = "";
$db   = "ipskfimy_tb_core";
// $port = 3306;   

/* $host = "localhost";
$user = "root";
$pass = "";
$db   = "trainergenienew"; */



$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}
?>
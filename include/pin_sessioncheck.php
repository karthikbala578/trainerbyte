<?php 
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if(!isset($_SESSION['pin']) || empty($_SESSION['pin'])){
    header("Location: logout_pin.php");
    exit;
}
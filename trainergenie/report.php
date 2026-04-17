<?php
session_start();
require "include/dataconnect.php";

if (!isset($_SESSION['team_id'])) {
    header("Location: login.php");
    exit;
}

$pageTitle = "Report";
// $pageCSS   = "./assets/styles/library.css";
require "layout/header.php";
?>

<br><br>
<center>
    <h4>Will be developed</h4>
</center>
<?php require "layout/footer.php"; ?>
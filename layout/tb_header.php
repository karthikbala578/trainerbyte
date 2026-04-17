<?php
error_reporting(E_ERROR | E_PARSE);
ini_set('display_errors', 1);
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

define('BASE_PATH', 'https://trainerbyte.com/');

$currentPage = basename($_SERVER['PHP_SELF']);



if (!isset($_SESSION['team_id']) && $currentPage !== 'login.php') {

    header("Location: " . BASE_PATH . "/login.php");

    exit;

}
?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <title><?= $pageTitle ?? 'TrainerGenie' ?></title>



    <!-- Google Font -->

    <link rel="preconnect" href="https://fonts.googleapis.com">

    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@100..900&display=swap" rel="stylesheet">



    <link rel="icon" type="image/x-icon" href="assets/images/favicon.png">





    <!-- Global CSS -->

    <link rel="stylesheet" href="<?php echo BASE_PATH ?>assets/css/global.css">
     <?php if (!empty($pageCSS)): ?>

        <link rel="stylesheet" href="<?php echo BASE_PATH . $pageCSS ?>">

    <?php endif; ?>
</head>



<body>



<?php

if (isset($_SESSION['team_id'])) {

    include __DIR__ . "/../components/navbar.php";

}

?>

    
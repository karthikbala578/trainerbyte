<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!defined('BASE_PATH')) {
    define('BASE_PATH', '/trainergenie');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= $pageTitle ?? "Admin Panel" ?></title>

    <!-- Google Font -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@100..900&display=swap" rel="stylesheet">

    <!-- Global CSS -->
    <link rel="stylesheet" href="<?= BASE_PATH ?>/admin/styles/global.css">

    <?php if (!empty($pageCSS)): ?>
        <link rel="stylesheet" href="<?= BASE_PATH . $pageCSS ?>">
    <?php endif; ?>
</head>
<body>

<?php
if (isset($_SESSION['admin_id'])) {
    include __DIR__ . "/navbar.php";
}
?>

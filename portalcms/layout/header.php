<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

define('BASE_PATH', 'https://trainerbyte.com');

$pageTitle = $pageTitle ?? 'TrainerByte CMS';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>

    <!-- Google Font (Same as design) -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">



    <!-- Page CSS -->
    <?php if (!empty($pageCSS)): ?>
        <link rel="stylesheet" href="<?= BASE_PATH . $pageCSS ?>">
    <?php endif; ?>
</head>
<body>


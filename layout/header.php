<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!defined('BASE_PATH')) {
    // define('BASE_PATH', 'https://trainerbyte.com/');
    define('BASE_PATH', 'http://localhost/trainerbyte/');
}

$pageTitle = $pageTitle ?? 'TrainerByte';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle) ?></title>

    <!-- Google Font (Same as design) -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Footer CSS -->
    <link rel="stylesheet" href="<?php echo BASE_PATH ?>/styles/footer.css">

     
     <link rel="stylesheet" href="<?php echo BASE_PATH ?>/styles/placeholder.css">


    <!-- Page CSS -->
    <?php if (!empty($pageCSS)): ?>
        <link rel="stylesheet" href="https://trainerbyte.com/<?php echo $pageCSS ?>">
    <?php endif; ?>
</head>
<body>


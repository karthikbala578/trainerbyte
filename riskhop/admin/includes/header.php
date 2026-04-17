<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
    <meta name="description" content="RiskHOP - Risk Management Game Platform">
    <meta name="theme-color" content="#2563eb">
    <title>RiskHOP - Admin Panel</title>
    
    <!-- CSS Files -->
    <link rel="stylesheet" href="<?php echo ASSETS_URL; ?>css/common.css">
    <link rel="stylesheet" href="<?php echo ASSETS_URL; ?>css/admin.css">
    <link rel="stylesheet" href="<?php echo ASSETS_URL; ?>css/responsive.css">
</head>
<body class="admin-body">
    <div class="admin-header">
        <div class="header-left">
            <h2>RiskHOP Admin</h2>
        </div>
        <div class="header-right">
            <span class="admin-name"><?php echo $_SESSION['admin_username']; ?></span>
            <a href="logout.php" class="btn btn-sm btn-secondary">Logout</a>
        </div>
    </div>
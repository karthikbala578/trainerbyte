<?php
/**
 * RiskHOP Game Header
 * Common header for all game module pages
 */

if (!defined('BASE_URL')) {
    die('Direct access not permitted');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="RiskHOP - Interactive Risk Management Game">
    <meta name="author" content="SARAS Analytics & Consulting">
    
    <!-- Favicon (optional) -->
    <link rel="icon" type="image/png" href="<?php echo ASSETS_URL; ?>images/favicon.png">
    
    <title><?php echo isset($page_title) ? $page_title . ' - RiskHOP' : 'RiskHOP - Risk Management Game'; ?></title>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" 
          integrity="sha512-iecdLmaskl7CVkqkXNQ/ZH/XLlvWZOJyj7Yy7tcenmpD1ypASozpmT/E0iPtmFIB46ZmdtAc9eNBvH0H/ZpiBw==" 
          crossorigin="anonymous" referrerpolicy="no-referrer" />
    
    <!-- Google Fonts (optional - uncomment if needed) -->
    <!-- <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet"> -->
    
    <!-- Styles -->
    <link rel="stylesheet" href="<?php echo ASSETS_URL; ?>css/common.css">
    <link rel="stylesheet" href="<?php echo ASSETS_URL; ?>css/game.css">
    <link rel="stylesheet" href="<?php echo ASSETS_URL; ?>css/responsive.css">
    
    <!-- Additional styles if specified -->
    <?php if (isset($additional_css)): ?>
        <?php foreach ($additional_css as $css_file): ?>
            <link rel="stylesheet" href="<?php echo $css_file; ?>">
        <?php endforeach; ?>
    <?php endif; ?>
    
    <style>
        /* Custom page-specific styles can be added here */
        <?php if (isset($custom_css)): ?>
            <?php echo $custom_css; ?>
        <?php endif; ?>
    </style>
</head>
<body class="<?php echo isset($body_class) ? $body_class : 'game-page'; ?>">
    
    <!-- Loading Overlay (optional) -->
    <div id="loadingOverlay" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); z-index: 9999; align-items: center; justify-content: center;">
        <div style="text-align: center; color: #fff;">
            <i class="fas fa-spinner fa-spin" style="font-size: 3rem; margin-bottom: 20px;"></i>
            <div style="font-size: 1.2rem;">Loading...</div>
        </div>
    </div>
<?php
$pageCSS = "/portalcms/styles/navbar.css";
require "header.php";

$currentPage = basename($_SERVER['PHP_SELF']);
?>

<div class="nav-container">

    <div class="nav-left">
        <div class="logo-icon">
            <span class="material-icons">⌨</span>
        </div>
        <span style="cursor:pointer;"
            onclick="window.location.href='/portalcms/index.php';">
            trainer<span class="logo-highlight">BYTE</span> <span class="cms">CMS</span>
        </span>
    </div>

    <div class="nav-center">
        <a href="/portalcms/index.php"
            class="<?= ($currentPage == 'index.php') ? 'active-nav' : '' ?>">
            Dashboard
        </a>

        <a href="/portalcms/pages/banner/banner.php"
            class="<?= ($currentPage == 'banner.php') ? 'active-nav' : '' ?>">
            Banners
        </a>

        <a href="/portalcms/pages/facilitators/facilitators.php"
            class="<?= ($currentPage == 'facilitators.php') ? 'active-nav' : '' ?>">
            Facilitators
        </a>

        <a href="/portalcms/pages/gt-templates/gt-templates.php"
            class="<?= ($currentPage == 'gt-templates.php') ? 'active-nav' : '' ?>">
            Game Types
        </a>

        <a href="/portalcms/pages/prompts/prompts.php"
            class="<?= ($currentPage == 'prompts.php' || $currentPage == 'gt-prompts.php') ? 'active-nav' : '' ?>">
            Prompts
        </a>

        <a href="/portalcms/pages/highlights/highlights.php"
            class="<?= ($currentPage == 'highlights.php') ? 'active-nav' : '' ?>">
            Highlights
        </a>

        <a href="/portalcms/pages/how-it-works/how-it-works.php"
            class="<?= ($currentPage == 'how-it-works.php') ? 'active-nav' : '' ?>">
            Steps
        </a>

        <a href="/portalcms/pages/footer-links/footer-links.php"
            class="<?= ($currentPage == 'footer-links.php') ? 'active-nav' : '' ?>">
            Footer-links
        </a>
    </div>

    <div class="nav-right">
        <span class="admin-name">
            <?= $_SESSION['ad_username'] ?? 'Administrator'; ?>
        </span>

        <form action="/portalcms/logout.php" method="post">
            <button type="submit" class="logout-btn">Logout</button>
        </form>
    </div>

</div>
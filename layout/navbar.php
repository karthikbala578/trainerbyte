<?php

$currentPage = basename($_SERVER['PHP_SELF']);

$pageCSS = "/styles/navbar.css";
require "layout/header.php";
?>

<nav class="navbar">

    <div class="nav-left">
        <a href="<?php echo BASE_PATH ?>index.php" class="logo">

            <img src="<?php echo BASE_PATH ?>assets/images/puzzle-icon.png"
                alt="TrainerByte Logo"
                class="navbar-logo-img">

            <span class="logo-text">
                trainer<span class="byte">BYTE</span>
            </span>

        </a>
    </div>

    <div class="nav-links">
        <a href="<?php echo BASE_PATH ?>index.php"
            class="<?php echo ($currentPage == 'index.php') ? 'active' : '' ?>">
            Home
        </a>

        <a href="<?php echo BASE_PATH ?>gt-templates.php"
            class="<?php echo ($currentPage == 'gt-templates.php') ? 'active' : '' ?>">
            Templates
        </a>

        <a href="<?php echo BASE_PATH ?>pricing.php"
            class="<?php echo ($currentPage == 'pricing.php') ? 'active' : '' ?>">
            Pricing
        </a>
        <?php
        if (!isset($_SESSION["team_id"])) {
        ?>
            <a href="<?php echo BASE_PATH ?>login.php"
            class="<?php echo ($currentPage == 'login.php') ? 'active' : '' ?>">
            Login
            </a>
        <?php
        }
        else{
        ?>
        <a href="<?php echo BASE_PATH ?>logout.php"
            class="<?php echo ($currentPage == 'login.php') ? 'active' : '' ?>">
            Logout
            </a>
            
        <?php
        }
        ?>
        
        

        <a href="<?php echo BASE_PATH ?>gt-templates.php"
            class="btn <?php echo ($currentPage == 'experience.php') ? 'active-btn' : '' ?>">
            Experience It Yourself
        </a>
    </div>

    <div class="hamburger" onclick="toggleMenu()">
        <span></span>
        <span></span>
        <span></span>
    </div>

</nav>

<div class="mobile-menu" id="mobileMenu">
    <a href="<?php echo BASE_PATH ?>templates.php">Templates</a>
    <a href="<?php echo BASE_PATH ?>pricing.php">Pricing</a>
    <a href="<?php echo BASE_PATH ?>login.php">Login</a>
    <a href="<?php echo BASE_PATH ?>experience.php" class="btn">Experience It Yourself</a>
</div>

<script>
    function toggleMenu() {
        document.getElementById("mobileMenu").classList.toggle("active");
    }
</script>
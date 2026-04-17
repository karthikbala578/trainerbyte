<?php
$currentPage = basename($_SERVER['PHP_SELF']);
?>

<link rel="stylesheet" href="/trainergenie/admin/styles/navbar.css">

<nav class="navbar">
    <!-- LEFT : LOGO -->
    <div class="nav-left">
        <img src="/trainergenie/assets/images/logo.png" class="logo" alt="TrainerGenie">
    </div>

    <!-- CENTER : ADMIN MENU -->
    <div class="nav-center">
        <a href="/trainergenie/admin/index.php"
           class="<?= $currentPage === 'index.php' ? 'active' : '' ?>">
           Dashboard
        </a>

        <a href="/trainergenie/admin/users.php"
           class="<?= $currentPage === 'users.php' ? 'active' : '' ?>">
           Users
        </a>

        <a href="/trainergenie/admin/exercises.php"
           class="<?= $currentPage === 'exercises.php' ? 'active' : '' ?>">
           Exercises
        </a>

        <a href="/trainergenie/admin/prompts.php"
           class="<?= $currentPage === 'prompts.php' ? 'active' : '' ?>">
           Prompts
        </a>

      
    </div>

    <!-- RIGHT : LOGOUT ONLY -->
    <div class="nav-right">
        <form action="/trainergenie/admin/logout.php" method="post">
            <button type="submit" class="nav-logout-btn">Logout</button>
        </form>
    </div>
</nav>

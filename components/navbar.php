<?php
$currentPage = basename($_SERVER['PHP_SELF']);
$navImg = $_SESSION['team_image'] ?? 'assets/images/user.png';
?>
<link rel="stylesheet" href="assets/css/navbar.css">
<nav class="navbar">
<!-- MOBILE MENU BUTTON -->
    <button class="menu-toggle" id="menuToggle">
        ☰
    </button>
</nav>
    


    

    <!-- MENU + RIGHT SECTION -->
    <div class="nav-menu" id="navMenu">
    <!-- LEFT : LOGO -->
    <div class="nav-left">
        <img src="assets/images/logo.png" class="logo" alt="Logo">
    </div>
        <!-- CENTER : MENU -->
        <div class="nav-center">
            <a href="dashboard.php"
               class="<?= $currentPage === 'dashboard.php' ? 'active' : '' ?>">
               Home
            </a>

            <a href="library.php"
               class="<?= $currentPage === 'library.php' ? 'active' : '' ?>">
               Library
            </a>

            <a href="myevent.php"
               class="<?= $currentPage === 'myevent.php' ? 'active' : '' ?>">
               My Events
            </a>

            <a href="report.php"
               class="<?= $currentPage === 'report.php' ? 'active' : '' ?>">
               Reports
            </a>
        </div>

        <!-- PROFILE + LOGOUT -->
        <div class="nav-right">
            <!-- <a href="profile.php">
                <img src="<?=  $navImg ?>" class="nav-user" alt="User">
            </a> -->

            <form action="logout.php" method="post">
                <button type="submit" class="logout-btn">Logout</button>
            </form>
        </div>

    </div>
</nav>

<script>
document.getElementById('menuToggle').addEventListener('click', function () {
    document.getElementById('navMenu').classList.toggle('active');
});
</script>

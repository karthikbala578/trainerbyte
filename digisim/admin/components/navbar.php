<?php
define('BASE_PATH', '/digisim/admin');

$currentPage = basename($_SERVER['PHP_SELF']);
$navImg = $_SESSION['team_image'] ?? '<?php echo BASE_PATH ?>/assets/images/user.png';
?>


<link rel="stylesheet" href="<?php echo BASE_PATH ?>/assets/css/navbar.css">

<nav class="navbar">
    <!-- LEFT : LOGO -->
    <div class="nav-left">
        <img src="<?php echo BASE_PATH ?>/assets/images/logo.png" class="logo" alt="Logo">
    </div>

    <!-- CENTER : MENU -->
    <div class="nav-center">
        <a href="index.php"
           class="<?= $currentPage === 'index.php' ? 'active' : '' ?>">
           Home
        </a>

       

        <a href="pages/page-container.php"
           class="<?= $currentPage === 'step1.php' ? 'active' : '' ?>">
           Digisim
        </a>

        <a href="library.php"
           class="<?= $currentPage === 'myevent.php' ? 'active' : '' ?>">
           Library
        </a>

        <!-- <a href="multistage/multistagedigisim.php"
           class="<?= $currentPage === 'simulation_setup.php' ? 'active' : '' ?>">
           MultiStage
        </a> -->
    </div>

    <!--  PROFILE + LOGOUT -->
    <div class="nav-right">
    <a href="/ms-digisim/profile.php">
        <img src="<?= $navImg ?>" class="nav-user" alt="User">
    </a>
        <form action="/ms-digisim/logout.php" method="post">
            <button type="submit" class="logout-btn">Logout</button>
        </form>
    </div>
</nav>

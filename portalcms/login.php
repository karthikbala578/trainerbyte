<?php
$pageTitle = "TrainerByte CMS | Login";
$pageCSS   = "/portalcms/styles/login.css";

if (isset($_GET['error'])) {
    if ($_GET['error'] === "invalid") {
        $errorMsg = "Username or Password is incorrect.";
    } elseif ($_GET['error'] === "empty") {
        $errorMsg = "Please enter both Username and Password.";
    }
}


require "layout/header.php";
?>

<div class="login-container">

    <div class="login-left">
        <div class="brand">
            <div class="logo-box">⌨</div>
            <h2>trainer<span>BYTE</span> <small>CMS</small></h2>
        </div>

        <h1>Welcome Back</h1>
        <p>Enter your administrative credentials to access the console.</p>

        <?php if (!empty($errorMsg)) : ?>
            <div class="error-message">
                <?= htmlspecialchars($errorMsg) ?>
            </div>
        <?php endif; ?>

        <form action="<?= BASE_PATH ?>/portalcms/login_action.php" method="POST">

            <label>Username</label>
            <input type="text" name="ad_username" placeholder="Enter admin username" required>

            <label>Password</label>
            <input type="password" name="ad_password" placeholder="Enter password" required>



            <button type="submit" class="btn-primary">
                 Sign in to Console <span class="arrow">&nbsp; ➜</span>
              
            </button>


        </form>

        <div class="login-footer">
            © 2026 trainerBYTE. All rights reserved.
        </div>
    </div>

    <div class="login-right">
        <div class="overlay">
            <div class="line-shape"></div>

            <h2>Centralized Training <br>Management.</h2>
            <p>
                Streamline your workforce education, track progress <br>in real-time,
                and manage global training assets from a <br>single secure dashboard.
            </p>
        </div>
    </div>

</div>

<?php require "layout/footer.php"; ?>
<?php
$pageTitle = "TrainerGenie Admin Login";
$pageCSS   = "/admin/styles/login.css";

session_start();

if (isset($_SESSION['admin_id'])) {
    header("Location: index.php");
    exit();
}

require "layout/header.php";
require "../include/dataconnect.php";

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    $encodedPassword = base64_encode($password);

    $stmt = $conn->prepare("
        SELECT ad_id, ad_name
        FROM tb_admin
        WHERE ad_name = ?
        AND ad_password = ?
        AND status = 1
        LIMIT 1
    ");

    $stmt->bind_param("ss", $username, $encodedPassword);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $admin = $result->fetch_assoc();

        $_SESSION['admin_id']   = $admin['ad_id'];
        $_SESSION['admin_name'] = $admin['ad_name'];

        header("Location: index.php");
        exit();
    } else {
        $error = "Invalid username or password";
    }
}
?>

<div class="auth-page">
    <div class="auth-card">

        <!-- LEFT PANEL -->
        <div class="auth-left">
            <h2 class="auth-title">LOGIN</h2>

            <?php if (!empty($error)) : ?>
                <p style="color:#e63946; margin:10px 5px;">
                    <?= $error ?>
                </p>
            <?php endif; ?>

            <form method="POST" class="auth-form">

                <div class="field">
                    <input type="text" name="username" placeholder="Admin Username" required>
                </div>

                <div class="field">
                    <input type="password" name="password" placeholder="Password" required>
                </div>

                <button type="submit" class="login-btn">
                    Login Now
                </button>

            </form>
        </div>

        <div class="auth-right">
            <img src="./images/login-pic.png" alt="TrainerGenie Admin">
        </div>

    </div>
</div>

<?php require "layout/footer.php"; ?>

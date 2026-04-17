<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
/* Redirect if already logged in */
if (isset($_SESSION["team_id"])) {
    header("Location: dashboard.php");
    exit;
}

/* Session timeout message */
$sessionMsg = "";
if (isset($_GET["timeout"])) {
    $sessionMsg = "Session expired. Please login again.";
}
$pageTitle = "Login | TrainerByte";

?>
<!DOCTYPE html>
<html>

<head>
    <title>TrainerGENIE Login</title>

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/styles/login.css">
</head>

<body>

    <?php if ($sessionMsg): ?>
        <div class="session-msg"><?php echo htmlspecialchars($sessionMsg) ?></div>
    <?php endif; ?>

    <div class="container">

        <div class="left-panel">
            <div class="overlay">
                <h1>Empower your<br>training journey.</h1>
                <p>Create, organize, and manage your learning libraries and events with the most intuitive platform for educators.</p>
            </div>
        </div>

        <div class="right-panel">
            <div class="login-card">

                <div class="brand">TrainerGENIE</div>

                <h2>Welcome back</h2>
                <p class="sub-text">Log in to your account</p>

                <form id="loginForm">

                    <label>Email Address</label>
                    <input type="email" name="team_login" placeholder="name@company.com" required>

                    <label>Password</label>
                    <div class="password-field">
                        <input type="password" name="team_password" id="password" placeholder="Enter password" required>
                        <img src="assets/images/eye-open.svg" class="eye-icon" onclick="togglePasswordImg('password', this)">
                    </div>

                    <button type="submit" class="btn-login">Sign In</button>

                </form>

                <p id="msg"></p>

                <div class="signup-text">
                    New to TrainerGENIE? <a href="signup.php">Create an account</a>
                </div>

            </div>
        </div>

    </div>

    <script>
        document.getElementById("loginForm").addEventListener("submit", function(e) {
            e.preventDefault();

            const msg = document.getElementById("msg");
            msg.innerText = "";
            msg.className = "";

            const formData = new FormData(this);

            fetch("login_action.php", {
                    method: "POST",
                    body: formData
                })
                .then(res => res.json())
                .then(data => {
                    msg.className = data.status === "success" ? "success" : "error";
                    msg.innerText = data.message;

                    if (data.status === "success") {
                        setTimeout(() => {
                            window.location.href = "dashboard.php";
                        }, 1000);
                    }
                })
                .catch(() => {
                    msg.className = "error";
                    msg.innerText = "Something went wrong.";
                });
        });

        function togglePasswordImg(id, img) {
            const input = document.getElementById(id);
            if (input.type === "password") {
                input.type = "text";
                img.src = "/trainergenie/assets/images/eye-closed.svg";
            } else {
                input.type = "password";
                img.src = "/trainergenie/assets/images/eye-open.svg";
            }
        }
    </script>

</body>

</html>
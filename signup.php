<!DOCTYPE html>
<html>

<head>
    <title>TrainerGENIE Signup</title>

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/styles/signup.css">
</head>

<body>

    <div class="container">

        <div class="left-panel">
            <div class="overlay">
                <h1>Start your<br>training journey.</h1>
                <p>Create your account and manage your learning libraries and events with ease.</p>
            </div>
        </div>

        <div class="right-panel">
            <div class="signup-card">

                <div class="brand">TrainerGENIE</div>

                <h2>Create account</h2>
                <p class="sub-text">Sign up to get started</p>

                <form id="signupForm">

                    <label>Name</label>
                    <input type="text" name="team_name" placeholder="Your name" required>

                    <label>Email</label>
                    <input type="email" name="team_login" placeholder="name@company.com" required>

                    <label>Organization</label>
                    <input type="text" name="team_org" placeholder="Company / Org name" required>

                    <label>Password</label>
                    <div class="password-field">
                        <input type="password" name="team_password" id="team_password" placeholder="Enter password" required>
                        <img src="assets/images/eye-open.svg" class="eye-icon" onclick="togglePasswordImg('team_password', this)">
                    </div>

                    <label>Confirm Password</label>
                    <div class="password-field">
                        <input type="password" name="confirm_password" id="confirm_password" placeholder="Confirm password" required>
                        <img src="assets/images/eye-open.svg" class="eye-icon" onclick="togglePasswordImg('confirm_password', this)">
                    </div>

                    <button type="submit" class="btn-signup">Sign Up</button>

                </form>

                <p id="msg"></p>

                <div class="login-text">
                    Already have an account? <a href="login.php">Sign in</a>
                </div>

            </div>
        </div>

    </div>

    <script src="js/signup.js"></script>

    <script>
        function togglePasswordImg(id, img) {
            const input = document.getElementById(id);
            if (input.type === "password") {
                input.type = "text";
                img.src = "assets/images/eye-closed.svg";
            } else {
                input.type = "password";
                img.src = "assets/images/eye-open.svg";
            }
        }
    </script>

</body>

</html>
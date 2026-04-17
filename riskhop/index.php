<?php
/**
 * RiskHOP - Main Login Page
 * File: index.php
 */

require "config.php";
require_once 'functions.php';

// If already logged in, redirect to admin
if (is_admin_logged_in()) {
    redirect(ADMIN_URL . 'index.php');
}

$error = '';

// Handle login submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = clean_input($_POST['username']);
    $password = clean_input($_POST['password']);
    
    if (empty($username) || empty($password)) {
        $error = 'Please enter both username and password.';
    } else {
        $hashed_password = md5($password);
        $query = "SELECT * FROM mg6_admin_users WHERE username = '$username' AND password = '$hashed_password'";
        $result = mysqli_query($conn, $query);
        
        if (mysqli_num_rows($result) === 1) {
            $admin = mysqli_fetch_assoc($result);
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_username'] = $admin['username'];
            $_SESSION['admin_email'] = $admin['email'];
            redirect(ADMIN_URL . 'index.php');
        } else {
            $error = 'Invalid username or password.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RiskHOP - Admin Login</title>
    <link rel="stylesheet" href="<?php echo ASSETS_URL; ?>css/common.css">
    <link rel="stylesheet" href="<?php echo ASSETS_URL; ?>css/admin.css">
</head>
<body class="login-page">
    <div class="login-container">
        <div class="login-box">
            <div class="login-header">
                <h1>RiskHOP</h1>
                <p>Admin Login</p>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" required autofocus>
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                </div>
                
                <button type="submit" class="btn btn-primary btn-block">Login</button>
            </form>
            
            <div class="login-footer">
                <p style="margin-bottom: 10px;">Don't want to login?</p>
                <a href="<?php echo BASE_URL; ?>game/new_instruction.php" class="btn btn-secondary btn-block">Play Game Here</a>
            </div>
        </div>
    </div>
</body>
</html>
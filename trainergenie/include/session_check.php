<?php
// Start session safely
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

define("SESSION_TIMEOUT", 7200); // 2 hours

// Check login
if (!isset($_SESSION["team_id"])) {
    header("Location: login.php");
    exit;
}

// Check session timeout
if (isset($_SESSION["last_activity"]) &&
    (time() - $_SESSION["last_activity"] > SESSION_TIMEOUT)) {

    session_unset();
    session_destroy();

    header("Location: login.php?timeout=1");
    exit;
}

// Update last activity time
$_SESSION["last_activity"] = time();
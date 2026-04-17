<?php
session_start();

define("SESSION_TIMEOUT", 7200);
// define("BASE_PATH", "https://trainerbyte.com/");
define("BASE_PATH", "http://localhost/trainerbyte/");

$currentUrl = strtok($_SERVER['REQUEST_URI'], '?');

// Allow public event URLs like /YEODY3
if (preg_match('/^\/[A-Za-z0-9]+$/', $currentUrl)) {
    return;
}

if (!isset($_SESSION["team_id"])) {
    header("Location: " . BASE_PATH . "login.php");
    exit;
}

if (
    isset($_SESSION["last_activity"]) &&
    (time() - $_SESSION["last_activity"] > SESSION_TIMEOUT)
) {
    session_unset();
    session_destroy();
    header("Location: " . BASE_PATH . "login.php?timeout=1");
    exit;
}

$_SESSION["last_activity"] = time();
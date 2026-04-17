<?php
session_start();

define("SESSION_TIMEOUT", 7200);
define("BASE_PATH", "https://trainerbyte.com");

if (!isset($_SESSION["ad_id"])) {
    header("Location: " . BASE_PATH . "/portalcms/login.php");
    exit;
}

if (isset($_SESSION["last_activity"]) &&
    (time() - $_SESSION["last_activity"] > SESSION_TIMEOUT)) {

    session_unset();
    session_destroy();
    header("Location: " . BASE_PATH . "/portalcms/login.php?timeout=1");
    exit;
}

$_SESSION["last_activity"] = time();

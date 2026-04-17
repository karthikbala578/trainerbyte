<?php
//for development
$host = "localhost";
$user = "ipskfimy_trainerGenie";
$pass = "Tbcore@2026";
$db   = "ipskfimy_tb_core";
// $port = 3306;   
$conn = new mysqli($host, $user, $pass, $db);
if (!$conn) {
    // For AJAX requests, return JSON error
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
        strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'Database connection failed: ' . mysqli_connect_error(),
            'error_type' => 'DatabaseError'
        ]);
        exit;
    }
    die("Connection failed: " . mysqli_connect_error());
}

// Set charset to UTF-8
mysqli_set_charset($conn, "utf8mb4");

// Session Configuration
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Base URL Configuration
define('BASE_URL', 'https://trainerbyte.com/riskhop/');
define('GAME_URL', BASE_URL . 'game/');
define('ASSETS_URL', BASE_URL . 'assets/');
define('ADMIN_URL', BASE_URL . 'admin/');
// Upload Directory
define('UPLOAD_DIR', __DIR__ . '/assets/images/wildcards/');
define('UPLOAD_URL', ASSETS_URL . 'images/wildcards/');

// Create upload directory if not exists
if (!is_dir(UPLOAD_DIR)) {
    mkdir(UPLOAD_DIR, 0755, true);
}

// Timezone
date_default_timezone_set('Asia/Kolkata');

// Error Reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/error_log.txt');

// For AJAX requests, we'll handle errors as JSON
// This will be managed in individual files

?>
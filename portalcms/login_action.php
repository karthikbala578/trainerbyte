<?php

session_start();

require "../include/dataconnect.php";


define('BASE_PATH', '/portalcms');

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: " . BASE_PATH . "/login.php");
    exit;
}

$ad_username = trim($_POST['ad_username'] ?? '');
$ad_password = trim($_POST['ad_password'] ?? '');

if (empty($ad_username) || empty($ad_password)) {
    header("Location: " . BASE_PATH . "/login.php?error=empty");
    exit;
}

// Encode password using base64
$encoded_password = base64_encode($ad_password);

$sql = "SELECT ad_id, ad_username 
        FROM tb_cms_admin 
        WHERE ad_username = ? 
        AND ad_password = ? 
        AND status = 1 
        LIMIT 1";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $ad_username, $encoded_password);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {

    $admin = $result->fetch_assoc();

    // Set session
    $_SESSION['ad_id'] = $admin['ad_id'];
    $_SESSION['ad_username'] = $admin['ad_username'];
    $_SESSION['last_activity'] = time();

    // Redirect to dashboard
    header("Location: " . BASE_PATH . "/index.php");
    exit;

} else {
    // Invalid credentials
    header("Location: " . BASE_PATH . "/login.php?error=invalid");
    exit;
}

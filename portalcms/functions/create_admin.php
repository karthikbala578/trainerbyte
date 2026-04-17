<?php

require "../include/dataconnect.php";  

$ad_username = "admin";
$plain_password = "admin";

// Convert password to Base64
$ad_password = base64_encode($plain_password);

// Check if username already exists
$check_sql = "SELECT ad_id FROM tb_cms_admin WHERE ad_username = ?";
$check_stmt = $conn->prepare($check_sql);
$check_stmt->bind_param("s", $ad_username);
$check_stmt->execute();
$check_stmt->store_result();

if ($check_stmt->num_rows > 0) {
    echo "Admin already exists!";
} else {

    $insert_sql = "INSERT INTO tb_cms_admin 
                   (ad_username, ad_password, status) 
                   VALUES (?, ?, 1)";

    $stmt = $conn->prepare($insert_sql);
    $stmt->bind_param("ss", $ad_username, $ad_password);

    if ($stmt->execute()) {
        echo "Admin user created successfully!";
    } else {
        echo "Error: " . $stmt->error;
    }

    $stmt->close();
}

$check_stmt->close();
$conn->close();
?>

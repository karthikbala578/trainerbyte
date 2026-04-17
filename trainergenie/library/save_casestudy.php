<?php
session_start();

header("Content-Type: application/json");

require "../include/dataconnect.php";

if (!isset($_SESSION['team_id'])) {
    echo json_encode(["status" => "error", "message" => "Unauthorized"]);
    exit;
}

// ✅ USE $_POST instead of JSON
$cg_id = intval($_POST['cg_id'] ?? 0);
$casestudy = trim($_POST['casestudy'] ?? '');

if ($cg_id <= 0) {
    echo json_encode(["status" => "error", "message" => "Invalid game"]);
    exit;
}

$imageName = null;

// ✅ HANDLE IMAGE UPLOAD
if (isset($_FILES['casestudy_image']) && $_FILES['casestudy_image']['error'] == 0) {

    $uploadDir = "../uploads/casestudy/";

    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $ext = pathinfo($_FILES['casestudy_image']['name'], PATHINFO_EXTENSION);
    $imageName = "case_" . time() . "." . $ext;

    $targetFile = $uploadDir . $imageName;

    if (!move_uploaded_file($_FILES['casestudy_image']['tmp_name'], $targetFile)) {
        echo json_encode(["status" => "error", "message" => "File upload failed"]);
        exit;
    }
}

// ✅ UPDATE QUERY
if ($imageName) {
    $stmt = $conn->prepare("
        UPDATE card_group 
        SET cg_ex_context_desc = ?, cg_ex_context_image = ?
        WHERE cg_id = ?
    ");
    $stmt->bind_param("ssi", $casestudy, $imageName, $cg_id);
} else {
    $stmt = $conn->prepare("
        UPDATE card_group 
        SET cg_ex_context_desc = ?
        WHERE cg_id = ?
    ");
    $stmt->bind_param("si", $casestudy, $cg_id);
}

$stmt->execute();

echo json_encode(["status" => "success"]);
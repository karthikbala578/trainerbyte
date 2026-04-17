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
$guideline = trim($_POST['guideline'] ?? '');

if ($cg_id <= 0) {
    echo json_encode(["status" => "error", "message" => "Invalid game"]);
    exit;
}

$imageName = null;

// ✅ HANDLE IMAGE UPLOAD
if (!empty($_FILES['guide_image']['name'])) {

    $uploadDir = "../uploads/guidelines/";

    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    // optional: clean filename
    $ext = pathinfo($_FILES['guide_image']['name'], PATHINFO_EXTENSION);
    $imageName = "guide_" . time() . "." . $ext;

    $targetFile = $uploadDir . $imageName;

    if (!move_uploaded_file($_FILES['guide_image']['tmp_name'], $targetFile)) {
        echo json_encode(["status" => "error", "message" => "File upload failed"]);
        exit;
    }
}

// ✅ UPDATE QUERY
if ($imageName) {
    $stmt = $conn->prepare("
        UPDATE card_group 
        SET cg_guidelines = ?, cg_play_guide_image = ?
        WHERE cg_id = ?
    ");
    $stmt->bind_param("ssi", $guideline, $imageName, $cg_id);
} else {
    $stmt = $conn->prepare("
        UPDATE card_group 
        SET cg_guidelines = ?
        WHERE cg_id = ?
    ");
    $stmt->bind_param("si", $guideline, $cg_id);
}

$stmt->execute();

echo json_encode(["status" => "success"]);
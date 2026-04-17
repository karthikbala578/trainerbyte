<?php
session_start();
header("Content-Type: application/json");

require "../include/dataconnect.php";

if (!isset($_SESSION['team_id'])) {
    echo json_encode(["status" => "error", "message" => "Unauthorized"]);
    exit;
}

$raw = file_get_contents("php://input");
$input = json_decode($raw, true);

if (!$input) {
    echo json_encode(["status" => "error", "message" => "Invalid JSON"]);
    exit;
}

$cu_id = intval($input['cu_id'] ?? 0);
$title = trim($input['title'] ?? '');
$desc  = trim($input['description'] ?? '');

if ($cu_id <= 0 || $title === '') {
    echo json_encode(["status" => "error", "message" => "Invalid data"]);
    exit;
}

$stmt = $conn->prepare("
    UPDATE card_unit
    SET cu_name = ?, cu_description = ?
    WHERE cu_id = ?
");

if (!$stmt) {
    echo json_encode(["status" => "error", "message" => $conn->error]);
    exit;
}

$stmt->bind_param("ssi", $title, $desc, $cu_id);

if (!$stmt->execute()) {
    echo json_encode(["status" => "error", "message" => $stmt->error]);
    exit;
}

echo json_encode([
    "status" => "success",
    "updated_id" => $cu_id
]);

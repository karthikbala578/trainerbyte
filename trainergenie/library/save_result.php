<?php
session_start();
header("Content-Type: application/json");
require "../include/dataconnect.php";

if (!isset($_SESSION['team_id'])) {
    echo json_encode(["status" => "error", "message" => "Unauthorized"]);
    exit;
}

$input = json_decode(file_get_contents("php://input"), true);

$cg_id  = intval($input['cg_id'] ?? 0);
$result = trim($input['result'] ?? '');

if ($cg_id <= 0) {
    echo json_encode(["status" => "error", "message" => "Invalid game"]);
    exit;
}

$stmt = $conn->prepare("
    UPDATE card_group
    SET cg_result = ?
    WHERE cg_id = ?
");
$stmt->bind_param("si", $result, $cg_id);
$stmt->execute();

echo json_encode(["status" => "success"]);

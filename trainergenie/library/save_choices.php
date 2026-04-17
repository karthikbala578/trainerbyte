<?php
session_start();
header("Content-Type: application/json");
require "../include/dataconnect.php";

if (!isset($_SESSION['team_id'])) {
    echo json_encode(["status" => "error", "message" => "Unauthorized"]);
    exit;
}

$input = json_decode(file_get_contents("php://input"), true);

$cg_id = intval($input['cg_id'] ?? 0);
$answers = $input['answers'] ?? [];

if ($cg_id <= 0 || !is_array($answers)) {
    echo json_encode(["status" => "error", "message" => "Invalid input"]);
    exit;
}

/* Normalize JSON */
$clean = [];
foreach ($answers as $i => $a) {
    if (!empty($a['title']) && !empty($a['answer'])) {
        $clean[] = [
            "order"  => $i + 1,
            "title"  => $a['title'],
            "answer" => $a['answer'],
            "ans_type" => $a['ans_type'],
            "score" => $a['score']
        ];
    }
}

$json = json_encode($clean, JSON_UNESCAPED_UNICODE);

$stmt = $conn->prepare("
    UPDATE card_group
    SET cg_answer = ?
    WHERE cg_id = ?
");
$stmt->bind_param("si", $json, $cg_id);
$stmt->execute();

echo json_encode(["status" => "success"]);

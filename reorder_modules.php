<?php
session_start();
require "include/coreDataconnect.php";

if (!isset($_SESSION['team_id'])) {
    echo json_encode(["status" => "error", "message" => "Unauthorized"]);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);

$event_id = intval($data['event_id'] ?? 0);
$modules  = $data['modules'] ?? [];

if ($event_id <= 0 || empty($modules)) {
    echo json_encode(["status" => "error", "message" => "Invalid data"]);
    exit;
}

// Check event status
$stmt = $conn->prepare("
    SELECT event_playstatus 
    FROM tb_events 
    WHERE event_id = ? AND event_team_pkid = ?
");
$stmt->bind_param("ii", $event_id, $_SESSION['team_id']);
$stmt->execute();
$status = $stmt->get_result()->fetch_assoc();

if (!$status || $status['event_playstatus'] != 1) {
    echo json_encode([
        "status" => "error",
        "message" => "Reordering allowed only in Draft"
    ]);
    exit;
}

// Update order
foreach ($modules as $m) {

    $game_id = intval($m['game_id']);
    $type    = intval($m['type']);
    $order   = intval($m['order']);

    $stmt = $conn->prepare("
        UPDATE tb_events_module 
        SET mod_order = ? 
        WHERE mod_event_pkid = ? 
        AND mod_game_id = ? 
        AND mod_type = ?
    ");

    $stmt->bind_param("iiii", $order, $event_id, $game_id, $type);
    $stmt->execute();
}

echo json_encode(["status" => "success"]);

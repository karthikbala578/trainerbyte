<?php
require "include/coreDataconnect.php";

$data = json_decode(file_get_contents("php://input"), true);

$event_id = intval($data['event_id']);
$game_id  = intval($data['game_id']);
$type     = intval($data['type']);

$stmt = $conn->prepare("
    UPDATE tb_events_module
    SET mod_status = 0
    WHERE mod_event_pkid = ? AND mod_game_id = ? AND mod_type = ?
");

$stmt->bind_param("iii", $event_id, $game_id, $type);
$stmt->execute();

echo json_encode(["status" => "removed"]);

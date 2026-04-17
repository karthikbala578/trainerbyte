<?php
session_start();
header("Content-Type: application/json");
require "../include/dataconnect.php";

$data = json_decode(file_get_contents("php://input"), true);

$event_id = intval($data['event_id'] ?? 0);
$game_id  = intval($data['game_id'] ?? 0);
$name     = trim($data['name'] ?? '');
$type     = intval($data['type'] ?? 0);

if (!$event_id || !$game_id || !$name || !$type) {
    echo json_encode(["status"=>"error","message"=>"Invalid data"]);
    exit;
}

/* check DB first (already assigned) */
$checkDb = $conn->prepare("
    SELECT 1 FROM tb_events_module
    WHERE mod_event_pkid = ?
      AND mod_type = ?
      AND mod_game_id = ?
      AND mod_status = 1
");
$checkDb->bind_param("iii", $event_id, $type, $game_id);
$checkDb->execute();
$checkDb->store_result();

if ($checkDb->num_rows > 0) {
    echo json_encode([
        "status" => "error",
        "message" => "Module already added to this event"
    ]);
    exit;
}

/*  Check session (draft) */
if (!isset($_SESSION['event_modules'][$event_id])) {
    $_SESSION['event_modules'][$event_id] = [];
}

foreach ($_SESSION['event_modules'][$event_id] as $m) {
    if ($m['game_id'] == $game_id && $m['type'] == $type) {
        echo json_encode([
            "status" => "error",
            "message" => "Module already added (draft)"
        ]);
        exit;
    }
}

/* Add to session */
$_SESSION['event_modules'][$event_id][] = [
    "type"     => $type,
    "game_id"  => $game_id,
    "name"     => $name
];

echo json_encode(["status"=>"success"]);

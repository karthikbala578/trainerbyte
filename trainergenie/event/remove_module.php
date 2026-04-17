<?php
session_start();
header("Content-Type: application/json");
require "../include/dataconnect.php";

$data = json_decode(file_get_contents("php://input"), true);

file_put_contents(
    __DIR__ . "/debug_remove.log",
    print_r($data, true),
    FILE_APPEND
);

$event_id = intval($data['event_id'] ?? 0);
$game_id  = intval($data['game_id'] ?? 0);
$type     = intval($data['type'] ?? 0);
$source   = $data['source'] ?? '';

if ($event_id <= 0 || $game_id <= 0 || $source === '') {  
    echo json_encode(["status"=>"error","message"=>"Invalid request"]);
    exit;
}

/* REMOVE FROM SESSION */
if ($source === "session") {

    if (!isset($_SESSION['event_modules'][$event_id])) {
        echo json_encode(["status"=>"error","message"=>"Nothing to remove"]);
        exit;
    }

    $_SESSION['event_modules'][$event_id] = array_values(
        array_filter(
        $_SESSION['event_modules'][$event_id],
        function ($m) use ($game_id, $type) {
            return !(
                isset($m['game_id'], $m['type']) &&
                (int)$m['game_id'] === (int)$game_id &&
                (int)$m['type'] === (int)$type
            );
        }
    )
    );

    echo json_encode(["status"=>"success"]);
    exit;
}

/* REMOVE FROM DB */
if ($source === "db") {

    $stmt = $conn->prepare("
    UPDATE tb_events_module
    SET mod_status = 0
    WHERE mod_event_pkid = ?
      AND mod_game_id = ?
      AND mod_type = ?
      AND mod_status = 1 ");
                $stmt->bind_param("iii", $event_id, $game_id, $type);
                $stmt->execute();

                if ($stmt->affected_rows === 0) {
                    echo json_encode([
                        "status" => "error",
                        "message" => "Module not found or already removed"
                    ]);
                    exit;
                }

                echo json_encode(["status" => "success"]);
                exit;

}


echo json_encode(["status"=>"error","message"=>"Unknown source"]);

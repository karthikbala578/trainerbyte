<?php
session_start();
header("Content-Type: application/json");

require "include/coreDataconnect.php";

error_reporting(E_ALL);
ini_set('display_errors', 0);

define("TYPE_MS_DIGISIM", 9);

function sendError($msg)
{
    echo json_encode(["status" => "error", "message" => $msg]);
    exit;
}

try {

    $data = json_decode(file_get_contents("php://input"), true);
    if (!$data) sendError("No JSON");

    $event_id = intval($data['event_id'] ?? 0);
    $game_id  = intval($data['game_id'] ?? 0);
    $name     = trim($data['name'] ?? '');
    $type     = intval($data['type'] ?? 0);

    if (!$event_id || !$game_id || !$name || !$type) {
        sendError("Invalid data");
    }

    /* MS DigiSim → insert multiple */
    if ($type == TYPE_MS_DIGISIM) {

        $get = $conn->prepare("
            SELECT r_digisim_pkid 
            FROM mg5_ms_rounds
            WHERE r_digisim_master_pkid = ?
            ORDER BY r_stage_no ASC
        ");

        $get->bind_param("i", $game_id);
        $get->execute();
        $res = $get->get_result();

        if ($res->num_rows == 0) sendError("No games");

        while ($row = $res->fetch_assoc()) {

            $digisim_id = intval($row['r_digisim_pkid']);

            // skip if already exists
            $check = $conn->prepare("
                SELECT 1 FROM tb_events_module
                WHERE mod_event_pkid = ?
                AND mod_type = ?
                AND mod_game_id = ?
                AND mod_status = 1
            ");
            $check->bind_param("iii", $event_id, $type, $digisim_id);
            $check->execute();
            $check->store_result();

            if ($check->num_rows > 0) continue;

            // next order
            $orderRes = $conn->query("
                SELECT IFNULL(MAX(mod_order),0)+1 AS next_order
                FROM tb_events_module
                WHERE mod_event_pkid = $event_id
            ");
            $nextOrder = intval($orderRes->fetch_assoc()['next_order']);

            // insert
            $stmt = $conn->prepare("
                INSERT INTO tb_events_module
                (mod_event_pkid, mod_type, mod_game_id, mod_order, mod_status)
                VALUES (?, ?, ?, ?, 1)
            ");
            $stmt->bind_param("iiii", $event_id, $type, $digisim_id, $nextOrder);
            $stmt->execute();
        }

        echo json_encode(["status" => "success"]);
        exit;
    }

    /* Normal modules → single insert */

    $check = $conn->prepare("
        SELECT 1 FROM tb_events_module
        WHERE mod_event_pkid = ?
        AND mod_type = ?
        AND mod_game_id = ?
        AND mod_status = 1
    ");
    $check->bind_param("iii", $event_id, $type, $game_id);
    $check->execute();
    $check->store_result();

    if ($check->num_rows > 0) sendError("Already exists");

    $orderRes = $conn->query("
        SELECT IFNULL(MAX(mod_order),0)+1 AS next_order
        FROM tb_events_module
        WHERE mod_event_pkid = $event_id
    ");
    $nextOrder = intval($orderRes->fetch_assoc()['next_order']);

    $stmt = $conn->prepare("
        INSERT INTO tb_events_module
        (mod_event_pkid, mod_type, mod_game_id, mod_order, mod_status)
        VALUES (?, ?, ?, ?, 1)
    ");
    $stmt->bind_param("iiii", $event_id, $type, $game_id, $nextOrder);

    if (!$stmt->execute()) sendError($stmt->error);

    echo json_encode(["status" => "success"]);

} catch (Throwable $e) {
    sendError($e->getMessage());
}
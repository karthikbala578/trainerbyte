<?php
session_start();
header("Content-Type: application/json");
require "../include/dataconnect.php";

$team_id = $_SESSION['team_id'] ?? 0;
$input = json_decode(file_get_contents("php://input"), true);
$ui_id = intval($input['ui_id'] ?? 0);
$step  = intval($input['step'] ?? 0);
$data  = $input['data'] ?? [];

if (!$team_id) {
    echo json_encode(["status" => "error", "message" => "Unauthorized"]);
    exit;
}

/* STEP 1: INSERT OR UPDATE DRAFT */
if ($step === 1) {
    if ($ui_id > 0) {
        $stmt = $conn->prepare("UPDATE byteguess_user_input SET ui_game_name=?, ui_game_description=?, ui_total_cards=?, ui_cards_drawn=?, ui_card_structure=?, ui_cur_step=1 WHERE ui_id=? AND ui_team_pkid=?");
        $stmt->bind_param("ssiisii", $data['ui_game_name'], $data['ui_game_description'], $data['ui_total_cards'], $data['ui_cards_drawn'], $data['ui_card_structure'], $ui_id, $team_id);
        $stmt->execute();
        echo json_encode(["status" => "success", "ui_id" => $ui_id]);
    } else {
        $stmt = $conn->prepare("INSERT INTO byteguess_user_input (ui_team_pkid, ui_game_name, ui_game_description, ui_total_cards, ui_cards_drawn, ui_card_structure, ui_cur_step) VALUES (?,?,?,?,?,?,1)");
        $stmt->bind_param("issiis", $team_id, $data['ui_game_name'], $data['ui_game_description'], $data['ui_total_cards'], $data['ui_cards_drawn'], $data['ui_card_structure']);
        $stmt->execute();
        echo json_encode(["status" => "success", "ui_id" => $stmt->insert_id]);
    }
    exit;
}

/* STEPS 2 & 3: UPDATES */
$map = [
    2 => ["ui_training_topic", "ui_industry", "ui_objective", "ui_hypothesis"],
    3 => ["ui_options", "ui_clue"]
];

$set = []; $params = []; $types = "";
foreach ($map[$step] as $col) {
    $set[] = "$col = ?";
    $params[] = is_array($data[$col]) ? json_encode($data[$col]) : $data[$col];
    $types .= "s";
}
$set[] = "ui_cur_step = ?"; $params[] = $step; $types .= "i";
$params[] = $ui_id; $params[] = $team_id; $types .= "ii";

$stmt = $conn->prepare("UPDATE byteguess_user_input SET ".implode(", ", $set)." WHERE ui_id = ? AND ui_team_pkid = ?");
$stmt->bind_param($types, ...$params);
$stmt->execute();

echo json_encode(["status" => "success"]);
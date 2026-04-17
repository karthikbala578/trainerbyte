<?php
session_start();
include("../../include/coreDataconnect.php");

$data = json_decode(file_get_contents("php://input"), true);

if(!$data){
    echo json_encode(["status"=>"error"]);
    exit;
}

$summary = json_encode($data);
$game_id = $data['game_id'];

$stmt = $conn->prepare("
    UPDATE tb_event_user_score
    SET game_summary = ?
    WHERE mod_game_id = ?
    AND user_id = ?
    AND event_id = ?
");

$stmt->bind_param(
    "siii",
    $summary,
    $game_id,
    $_SESSION['user_id'],
    $_SESSION['event_id']
);

$stmt->execute();

echo json_encode(["status"=>"success"]);
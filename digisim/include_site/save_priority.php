<?php
session_start();
include("../../include/coreDataconnect.php");

header('Content-Type: application/json');

if(!isset($_SESSION['user_id']) || !isset($_SESSION['event_id'])){
    echo json_encode([
        "status"=>"error",
        "msg"=>"Session expired"
    ]);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);

if(!$data){
    echo json_encode([
        "status"=>"error",
        "msg"=>"No JSON data received"
    ]);
    exit;
}

$summary = json_encode($data);
$game_id = $data['game_id'];

$stmt = $conn->prepare("UPDATE tb_event_user_score
SET game_summary = ?,game_status = 'completed'
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

if($stmt->execute()){
    echo json_encode([
        "status"=>"success"
    ]);
}else{
    echo json_encode([
        "status"=>"error",
        "msg"=>$stmt->error
    ]);
}
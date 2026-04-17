<?php
session_start();
header("Content-Type: application/json");
require "../include/dataconnect.php";
$team_id = $_SESSION['team_id'] ?? 0;
if (!$team_id) { echo json_encode(["status"=>"none"]); exit; }

$stmt = $conn->prepare("SELECT * FROM byteguess_user_input WHERE ui_team_pkid=? AND ui_cur_step < 5 ORDER BY ui_id DESC LIMIT 1");
$stmt->bind_param("i", $team_id);
$stmt->execute();
$res = $stmt->get_result()->fetch_assoc();
echo json_encode($res ? ["status"=>"found", "ui_id"=>$res['ui_id'], "step"=>$res['ui_cur_step'], "data"=>$res] : ["status"=>"none"]);
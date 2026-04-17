<?php

session_start();

header("Content-Type: application/json");

require "../include/coreDataconnect.php";



$data = json_decode(file_get_contents("php://input"), true);



$cg_id = intval($data['cg_id'] ?? 0);

$clues = $data['clues'] ?? [];



if (!$cg_id || !is_array($clues)) {

    echo json_encode(["status"=>"error"]);

    exit;

}



$json = json_encode($clues, JSON_UNESCAPED_UNICODE);



$stmt = $conn->prepare("

    UPDATE card_group

    SET cg_clue = ?

    WHERE cg_id = ?

");



$stmt->bind_param("si", $json, $cg_id);

$stmt->execute();



echo json_encode(["status"=>"success"]);


<?php

session_start();

header("Content-Type: application/json");

require "../include/coreDataconnect.php";



if (!isset($_SESSION['team_id'])) {

    echo json_encode(["status" => "error", "message" => "Unauthorized"]);

    exit;

}



$input = json_decode(file_get_contents("php://input"), true);



$cg_id  = intval($input['cg_id'] ?? 0);

$guideline = trim($input['guideline'] ?? '');



if ($cg_id <= 0) {

    echo json_encode(["status" => "error", "message" => "Invalid game"]);

    exit;

}



$stmt = $conn->prepare("

    UPDATE card_group 

    SET cg_guidelines = ?

    WHERE cg_id = ?

");

$stmt->bind_param("si", $guideline, $cg_id);

$stmt->execute();



echo json_encode(["status" => "success"]);


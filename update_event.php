<?php

session_start();

require "include/coreDataconnect.php";

header("Content-Type: application/json");



if (!isset($_SESSION['team_id'])) {

    echo json_encode(["status"=>"error","message"=>"Unauthorized"]);

    exit;

}



$stmt = $conn->prepare("

    UPDATE tb_events SET

        event_name = ?,

        event_description = ?,

        event_start_date = ?,

        event_validity = ?,

        event_playstatus = ?

    WHERE event_id = ?

      AND event_team_pkid = ?

");



$stmt->bind_param(

    "sssiiii",

    $_POST['event_name'],

    $_POST['event_description'],

    $_POST['event_start_date'],

    $_POST['event_validity'],

    $_POST['event_playstatus'],

    $_POST['event_id'],

    $_SESSION['team_id']

);



$stmt->execute();



echo json_encode(["status"=>"success"]);


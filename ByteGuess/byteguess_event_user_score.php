<?php

session_start();

header('Content-Type: application/json');



require_once "../include/coreDataconnect.php";



/* Read RAW JSON */

$data = json_decode(file_get_contents("php://input"), true);



/* Validate */

if (

    !$data ||

    empty($data['game_id']) ||

    empty($data['game_summary'])

) {

    echo json_encode([

        'status' => 'error',

        'message' => 'Invalid payload'

    ]);

    exit;

}



$game_id   = (int)$data['game_id'];

$game_code = $_SESSION['event_code'] ?? null;



if (!$game_code) {

    echo json_encode([

        'status' => 'error',

        'message' => 'Missing game code'

    ]);

    exit;

}



/* Store FULL JSON in one field */

$game_summary = json_encode(

    $data['game_summary'],

    JSON_UNESCAPED_UNICODE

);



$game_status = 'completed';



/* UPSERT (update if exists, else insert) */

$upd = $conn->prepare(

    "UPDATE tb_event_user_score 

     SET game_summary = ?, 

         game_status = 'completed' 

     WHERE mod_game_id = ? and event_id = ? and user_id = ?"

);

$upd->bind_param(

    "siii",

     $game_summary, 

     $game_id, 

     $_SESSION['event_id'],

    $_SESSION['user_id']
);



if ($upd->execute()) {



    $_SESSION['byteguess_completed'] = true;



    echo json_encode([

        'status'   => 'success',

        'redirect' => 'byteguess-final_results.php?game_id='

                      . $game_id . '&code=' . $game_code

    ]);

} else {

    echo json_encode([

        'status'  => 'error',

        'message' => 'DB error',

        'error'   => $stmt->error

    ]);

}
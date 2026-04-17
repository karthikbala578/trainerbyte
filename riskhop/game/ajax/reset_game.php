<?php

require_once '../../config.php';
require_once '../../functions.php';

header('Content-Type: application/json');

$data = json_decode(file_get_contents("php://input"), true);

$session_id = (int)($data['session_id'] ?? 0);

if(!$session_id){
    echo json_encode(["success"=>false]);
    exit;
}

$session = $db->query("
SELECT matrix_id
FROM mg6_game_sessions
WHERE id = $session_id
")->fetch_assoc();

$matrix_id = $session['matrix_id'];

$game = $db->query("
SELECT initial_risk_capital, dice_limit
FROM mg6_game_sessions
WHERE id = $matrix_id
")->fetch_assoc();

$initialCapital = $game['initial_risk_capital'];
$initialDice = $game['dice_limit'];

$db->query("
UPDATE mg6_game_sessions 
SET 
    current_cell = 1,
    dice_remaining = $initialDice,
    capital_remaining = $initialCapital,
    game_status = 'playing',
    start_time = NOW(),
    end_time = NULL
WHERE id = $session_id
");

$db->query("DELETE FROM mg6_player_investments WHERE session_id=$session_id");
$db->query("DELETE FROM mg6_session_wildcards_opened WHERE session_id=$session_id");

echo json_encode([
    "success"=>true,
    "data"=>[
        "current_cell"=>1,
        "dice_remaining"=>$initialDice,
        "capital_remaining"=>$initialCapital
    ]
]);
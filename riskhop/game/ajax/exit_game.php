<?php

require_once '../../config.php';
require_once '../../functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method'
    ]);
    exit;
}

$input = json_decode(file_get_contents("php://input"), true);

$session_id = isset($input['session_id']) ? (int)$input['session_id'] : 0;

$max_cell_reached = (int)($input['max_cell_reached'] ?? 0);
$total_dice_used = (int)($input['total_dice_used'] ?? 0);

$threats_protected = (int)($input['threats_protected'] ?? 0);
$threats_total = (int)($input['threats_total'] ?? 0);

$opportunities_exploited = (int)($input['opportunities_exploited'] ?? 0);
$opportunities_total = (int)($input['opportunities_total'] ?? 0);

$wildcards_opened = (int)($input['wildcards_opened'] ?? 0);

$final_capital = (int)($input['final_capital'] ?? 0);

$game_score = (int)($input['game_score'] ?? 0);

if ($session_id <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid session ID'
    ]);
    exit;
}

global $conn;

/* Update statistics */
$statsQuery = "
UPDATE mg6_game_statistics SET

max_cell_reached = '$max_cell_reached',
total_dice_used = '$total_dice_used',

threats_protected = '$threats_protected',
threats_total = '$threats_total',

opportunities_exploited = '$opportunities_exploited',
opportunities_total = '$opportunities_total',

wildcards_opened = '$wildcards_opened',

final_capital = '$final_capital',

game_score = '$game_score'

WHERE session_id = '$session_id'
";

$statsResult = mysqli_query($conn, $statsQuery);

if(!$statsResult){
    echo json_encode([
        'success' => false,
        'message' => 'Statistics update failed'
    ]);
    exit;
}

/* Mark session completed */
$sessionQuery = "
UPDATE mg6_game_sessions 
SET game_status='completed'
WHERE id='$session_id'
";

mysqli_query($conn,$sessionQuery);


/* Destroy session */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

unset($_SESSION['game_session_id']);
unset($_SESSION['matrix_id']);

session_destroy();

echo json_encode([
    'success' => true,
    'message' => 'Game completed successfully'
]);
<?php
/**
 * Continue Game
 * Resume a paused or saved game session
 */

require_once '../../config.php';
require_once '../../functions.php';
require_once '../game_engine.php';

header('Content-Type: application/json');

// Check if request is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(false, 'Invalid request method');
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

// Validate input
if (!isset($input['session_id']) || empty($input['session_id'])) {
    json_response(false, 'Session ID is required');
}

$session_id = clean_input($input['session_id']);

// Get session from database
global $conn;
$query = "SELECT * FROM mg6_game_sessions WHERE id = '$session_id'";
$result = mysqli_query($conn, $query);

if (mysqli_num_rows($result) == 0) {
    json_response(false, 'Session not found');
}

$session = mysqli_fetch_assoc($result);

// Check if game can be continued
if ($session['game_status'] == 'completed') {
    json_response(false, 'This game has already been completed');
}

if ($session['game_status'] == 'abandoned') {
    json_response(false, 'This game has been abandoned');
}

// Set session as active
$_SESSION['game_session_id'] = $session_id;
$_SESSION['matrix_id'] = $session['matrix_id'];

// Get full game data
$game_data = get_game_data($session['matrix_id']);

if (!$game_data) {
    json_response(false, 'Game data not found');
}

// Get player investments
$player_investments = get_player_investments($session_id);

// Return response with full game state
json_response(true, 'Game resumed successfully', [
    'session' => [
        'id' => $session['id'],
        'matrix_id' => $session['matrix_id'],
        'current_cell' => (int)$session['current_cell'],
        'dice_remaining' => (int)$session['dice_remaining'],
        'capital_remaining' => (int)$session['capital_remaining'],
        'game_status' => $session['game_status'],
        'start_time' => $session['start_time']
    ],
    'game_data' => $game_data,
    'investments' => $player_investments,
    'message' => 'Welcome back! Continue your game from cell ' . $session['current_cell']
]);
?>
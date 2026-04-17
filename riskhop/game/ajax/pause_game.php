<?php
/**
 * Pause Game
 * Pause the current game session
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

// Get current session
$session = get_current_session();

if (!$session || $session['id'] != $session_id) {
    json_response(false, 'Invalid session');
}

// Check if game is playing
if ($session['game_status'] != 'playing') {
    json_response(false, 'Game is not active');
}

// Game state is already saved automatically
// Just acknowledge the pause request
json_response(true, 'Game paused successfully', [
    'session_id' => $session_id,
    'current_state' => [
        'current_cell' => (int)$session['current_cell'],
        'dice_remaining' => (int)$session['dice_remaining'],
        'capital_remaining' => (int)$session['capital_remaining']
    ],
    'message' => 'Your progress has been saved. You can continue this game anytime.'
]);
?>
<?php
/**
 * Save Game State
 * Save current game state for pause/continue functionality
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
$action = isset($input['action']) ? clean_input($input['action']) : 'save';

// Get current session
$session = get_current_session();

if (!$session || $session['id'] != $session_id) {
    json_response(false, 'Invalid session');
}

// Handle different actions
switch ($action) {
    case 'pause':
        // Game is already saved in session, just acknowledge
        json_response(true, 'Game paused. You can continue later.', [
            'session_id' => $session_id,
            'current_state' => [
                'current_cell' => $session['current_cell'],
                'dice_remaining' => $session['dice_remaining'],
                'capital_remaining' => $session['capital_remaining']
            ]
        ]);
        break;
        
    case 'continue':
        // Check if session is still valid
        if ($session['game_status'] != 'playing') {
            json_response(false, 'Cannot continue this game. Status: ' . $session['game_status']);
        }
        
        // Get full game data
        $game_data = get_game_data($session['matrix_id']);
        $player_investments = get_player_investments($session_id);
        
        json_response(true, 'Game session loaded', [
            'session' => [
                'id' => $session['id'],
                'matrix_id' => $session['matrix_id'],
                'current_cell' => (int)$session['current_cell'],
                'dice_remaining' => (int)$session['dice_remaining'],
                'capital_remaining' => (int)$session['capital_remaining'],
                'game_status' => $session['game_status']
            ],
            'game_data' => $game_data,
            'investments' => $player_investments
        ]);
        break;
        
    case 'abandon':
        // Mark game as abandoned
        update_session($session_id, [
            'game_status' => 'abandoned',
            'end_time' => date('Y-m-d H:i:s')
        ]);
        
        // Clear session
        unset($_SESSION['game_session_id']);
        unset($_SESSION['matrix_id']);
        
        json_response(true, 'Game abandoned', [
            'session_id' => $session_id
        ]);
        break;
        
    default:
        // Default save action - game state is automatically saved with each move
        json_response(true, 'Game state is automatically saved', [
            'session_id' => $session_id,
            'last_saved' => date('Y-m-d H:i:s')
        ]);
}
?>
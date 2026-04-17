<?php
/**
 * Get Session
 * Return current game session data
 */

require_once '../../config.php';
require_once '../../functions.php';
require_once '../game_engine.php';

header('Content-Type: application/json');

// Check if request is GET or POST
if ($_SERVER['REQUEST_METHOD'] !== 'GET' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(false, 'Invalid request method');
}

// Get current session
$session = get_current_session();

if (!$session) {
    json_response(false, 'No active session found');
}

// Get game data
$game_data = get_game_data($session['matrix_id']);

if (!$game_data) {
    json_response(false, 'Game data not found');
}

// Get player investments
$player_investments = get_player_investments($session['id']);

// Get game moves count
global $conn;
$query = "SELECT COUNT(*) as moves FROM mg6_game_moves WHERE session_id = '{$session['id']}'";
$result = mysqli_query($conn, $query);
$total_moves = mysqli_fetch_assoc($result)['moves'];

// Calculate progress
$progress_percent = ($session['current_cell'] / $game_data['game']['total_cells']) * 100;

// Return response
json_response(true, 'Session retrieved', [
    'session' => [
        'id' => $session['id'],
        'matrix_id' => $session['matrix_id'],
        'current_cell' => (int)$session['current_cell'],
        'dice_remaining' => (int)$session['dice_remaining'],
        'capital_remaining' => (int)$session['capital_remaining'],
        'game_status' => $session['game_status'],
        'start_time' => $session['start_time']
    ],
    'game_data' => [
        'game' => $game_data['game'],
        'threats' => $game_data['threats'],
        'opportunities' => $game_data['opportunities'],
        'bonus_cells' => array_values($game_data['bonus_cells']),
        'audit_cells' => $game_data['audit_cells'],
        'wildcard_cells' => $game_data['wildcard_cells'],
        'total_cells' => $game_data['game']['total_cells']
    ],
    'investments' => $player_investments,
    'stats' => [
        'total_moves' => $total_moves,
        'progress_percent' => round($progress_percent, 2),
        'investment_count' => count($player_investments)
    ]
]);
?>
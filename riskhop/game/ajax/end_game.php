<?php
/**
 * End Game
 * Handle game completion and save final statistics
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

// Check if game is still active
if ($session['game_status'] == 'completed') {
    // Already completed, just return statistics
    global $conn;
    $query = "SELECT * FROM mg6_game_statistics WHERE session_id = '$session_id'";
    $result = mysqli_query($conn, $query);
    
    if (mysqli_num_rows($result) > 0) {
        $stats = mysqli_fetch_assoc($result);
        
        // Enrich stats with missing game data (total_cells)
        $game_data = get_game_data($session['matrix_id']);
        if ($game_data && isset($game_data['game']['total_cells'])) {
            $stats['total_cells'] = (int)$game_data['game']['total_cells'];
        } else {
            // Fallback to what's in stats or a sensible default if absolutely necessary
            $stats['total_cells'] = isset($stats['total_cells']) ? (int)$stats['total_cells'] : 0;
        }
        
        // Ensure all numeric fields are present
        $stats['threats_protected'] = $stats['threats_protected'] ?? 0;
        $stats['threats_total'] = $stats['threats_total'] ?? 0;
        $stats['opportunities_exploited'] = $stats['opportunities_exploited'] ?? 0;
        $stats['opportunities_total'] = $stats['opportunities_total'] ?? 0;
        $stats['wildcards_opened'] = $stats['wildcards_opened'] ?? 0;
        $stats['total_dice_used'] = $stats['total_dice_used'] ?? 0;
        $stats['final_capital'] = $stats['final_capital'] ?? 0;
        
        json_response(true, 'Game already completed', [
            'statistics' => $stats,
            'already_completed' => true
        ]);
    }
}

// End game and get statistics
$result = end_game($session_id);

if ($result['success']) {
    // Get game data for win/loss determination
    $game_data = get_game_data($session['matrix_id']);
    $is_win = $result['statistics']['max_cell_reached'] >= $game_data['game']['total_cells'];
    
    // Add win/loss status
    $result['statistics']['is_win'] = $is_win;
    $result['statistics']['result'] = $is_win ? 'Victory' : 'Game Over';
    
    // Calculate protection and exploitation percentages
    if ($result['statistics']['threats_total'] > 0) {
        $result['statistics']['threats_protected_percent'] = 
            round(($result['statistics']['threats_protected'] / $result['statistics']['threats_total']) * 100, 1);
    } else {
        $result['statistics']['threats_protected_percent'] = 0;
    }
    
    if ($result['statistics']['opportunities_total'] > 0) {
        $result['statistics']['opportunities_exploited_percent'] = 
            round(($result['statistics']['opportunities_exploited'] / $result['statistics']['opportunities_total']) * 100, 1);
    } else {
        $result['statistics']['opportunities_exploited_percent'] = 0;
    }
    
    json_response(true, 'Game completed successfully', [
        'statistics' => $result['statistics'],
        'already_completed' => false
    ]);
} else {
    json_response(false, $result['message']);
}
?>
<?php
/**
 * Get Cell Info
 * Return cell details for mouse hover tooltips
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
if (!isset($input['matrix_id']) || empty($input['matrix_id'])) {
    json_response(false, 'Matrix ID is required');
}

if (!isset($input['cell_number']) || !is_numeric($input['cell_number'])) {
    json_response(false, 'Valid cell number is required');
}

$matrix_id = clean_input($input['matrix_id']);
$cell_number = (int)clean_input($input['cell_number']);

// Verify matrix exists
$game_data = get_game_data($matrix_id);

if (!$game_data) {
    json_response(false, 'Invalid game');
}

// Validate cell number
if ($cell_number < 1 || $cell_number > $game_data['game']['total_cells']) {
    json_response(false, 'Invalid cell number');
}

// Get cell information
$cell_info = get_cell_info($matrix_id, $cell_number);

// Get current session for investment calculations
$session = get_current_session();
$player_investments = $session ? get_player_investments($session['id']) : [];

// Enhance cell info with additional details based on type
switch ($cell_info['type']) {
    case 'threat':
        // Get strategies for this threat
        $strategies = get_threat_strategies($cell_info['data']['id']);
        $cell_info['strategies'] = $strategies;
        
        // Calculate total points required
        $total_points = 0;
        foreach ($strategies as $strategy) {
            $total_points += $strategy['response_points'];
        }
        $cell_info['total_points_required'] = $total_points;
        
        // Calculate protection if session exists
        if ($session) {
            $cell_info['current_protection'] = calculate_threat_protection($cell_info['data']['id'], $player_investments);
        }
        break;
        
    case 'opportunity':
        // Get strategies for this opportunity
        $strategies = get_opportunity_strategies($cell_info['data']['id']);
        $cell_info['strategies'] = $strategies;
        
        // Calculate total points required
        $total_points = 0;
        foreach ($strategies as $strategy) {
            $total_points += $strategy['response_points'];
        }
        $cell_info['total_points_required'] = $total_points;
        
        // Calculate exploitation if session exists
        if ($session) {
            $cell_info['current_exploitation'] = calculate_opportunity_exploitation($cell_info['data']['id'], $player_investments);
        }
        break;
        
    case 'bonus':

    // ✅ Safe extraction
    $bonus_amount = isset($cell_info['data']['bonus_amount']) 
        ? (int)$cell_info['data']['bonus_amount'] 
        : 0;

    // ✅ Ensure value exists in both places
    $cell_info['bonus_amount'] = $bonus_amount;
    $cell_info['data']['bonus_amount'] = $bonus_amount;

break;
        
    case 'wildcard':

    global $conn;

    // Get wildcard info
    $query = "SELECT wildcard_name, wildcard_description 
              FROM mg6_riskhop_wildcards 
              WHERE matrix_id = '$matrix_id'
              ORDER BY RAND()
              LIMIT 1";

    $result = mysqli_query($conn, $query);
    $wildcard = mysqli_fetch_assoc($result);

    if ($wildcard) {
        $cell_info['data']['wildcard_name'] = $wildcard['wildcard_name'];
        $cell_info['data']['wildcard_description'] = $wildcard['wildcard_description'];
    }

    // Count total wildcard options
    $count_query = "SELECT COUNT(*) as count 
                    FROM mg6_riskhop_wildcards 
                    WHERE matrix_id = '$matrix_id'";
    $count_result = mysqli_query($conn, $count_query);
    $wildcard_count = mysqli_fetch_assoc($count_result)['count'];

    $cell_info['wildcard_options_count'] = $wildcard_count;

break;
}

// Return response
json_response(true, 'Cell info retrieved', [
    'cell_number' => $cell_number,
    'cell_info' => $cell_info
]);
?>
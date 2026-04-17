<?php
/**
 * RiskHOP Game Engine
 * Core business logic for game operations
 */

// Use absolute paths to avoid issues when included from different locations
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../functions.php';

/**
 * Get published games for game library
 */

// if (strpos($_SERVER['REQUEST_URI'], '/ajax/') !== false) {
//     error_reporting(0);
//     ini_set('display_errors', 0);
// }
function get_published_games() {
    global $conn;
    
    $query = "SELECT * FROM mg6_riskhop_matrix 
              WHERE status = 'published' 
              ORDER BY published_date DESC";
    
    $result = mysqli_query($conn, $query);
    $games = [];
    
    while ($row = mysqli_fetch_assoc($result)) {
        $games[] = $row;
    }
    
    return $games;
}

/**
 * Get complete game data for gameplay
 */
function get_game_data($matrix_id) {
    global $conn;
    
    // Get matrix details
    $query = "SELECT * FROM mg6_riskhop_matrix WHERE id = '$matrix_id' AND status = 'published'";
    $result = mysqli_query($conn, $query);
    $game = mysqli_fetch_assoc($result);
    
    if (!$game) {
        return null;
    }
    
    // Get all threats (snakes)
    $query = "SELECT * FROM mg6_riskhop_threats WHERE matrix_id = '$matrix_id'";
    $result = mysqli_query($conn, $query);
    $threats = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $threats[] = $row;
    }
    
    // Get all opportunities (ladders)
    $query = "SELECT * FROM mg6_riskhop_opportunities WHERE matrix_id = '$matrix_id'";
    $result = mysqli_query($conn, $query);
    $opportunities = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $opportunities[] = $row;
    }
    
    // Get all bonus cells
    $query = "SELECT * FROM mg6_riskhop_bonus WHERE matrix_id = '$matrix_id'";
    $result = mysqli_query($conn, $query);
    $bonus_cells = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $bonus_cells[$row['cell_number']] = $row;
    }
    
    // Get all audit cells
    $query = "SELECT * FROM mg6_riskhop_audit WHERE matrix_id = '$matrix_id'";
    $result = mysqli_query($conn, $query);
    $audit_cells = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $audit_cells[] = $row['cell_number'];
    }
    
    // Get all wildcard cells
    $query = "SELECT * FROM mg6_riskhop_wildcard_cells WHERE matrix_id = '$matrix_id'";
    $result = mysqli_query($conn, $query);
    $wildcard_cells = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $wildcard_cells[] = $row['cell_number'];
    }
    
    // Get all wildcard options
    $query = "SELECT * FROM mg6_riskhop_wildcards WHERE matrix_id = '$matrix_id'";
    $result = mysqli_query($conn, $query);
    $wildcards = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $wildcards[] = $row;
    }
    
    // Get all strategies
    $query = "SELECT * FROM mg6_riskhop_strategies";
    $result = mysqli_query($conn, $query);
    $strategies = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $strategies[$row['id']] = $row;
    }
    
    return [
        'game' => $game,
        'threats' => $threats,
        'opportunities' => $opportunities,
        'bonus_cells' => $bonus_cells,
        'audit_cells' => $audit_cells,
        'wildcard_cells' => $wildcard_cells,
        'wildcards' => $wildcards,
        'strategies' => $strategies
    ];
}

/**
 * Get strategies for a specific threat
 */
function get_threat_strategies($threat_id) {
    global $conn;
    
    $query = "SELECT s.* FROM mg6_riskhop_strategies s
              INNER JOIN mg6_threat_strategy_mapping tsm ON s.id = tsm.strategy_id
              WHERE tsm.threat_id = '$threat_id'";
    
    $result = mysqli_query($conn, $query);
    $strategies = [];
    
    while ($row = mysqli_fetch_assoc($result)) {
        $strategies[] = $row;
    }
    
    return $strategies;
}

/**
 * Get strategies for a specific opportunity
 */
function get_opportunity_strategies($opportunity_id) {
    global $conn;
    
    $query = "SELECT s.* FROM mg6_riskhop_strategies s
              INNER JOIN mg6_opportunity_strategy_mapping osm ON s.id = osm.strategy_id
              WHERE osm.opportunity_id = '$opportunity_id'";
    
    $result = mysqli_query($conn, $query);
    $strategies = [];
    
    while ($row = mysqli_fetch_assoc($result)) {
        $strategies[] = $row;
    }
    
    return $strategies;
}

/**
 * Start new game session
 */
function start_new_game($matrix_id) {
    global $conn;
    
    // Get game details
    $query = "SELECT * FROM mg6_riskhop_matrix WHERE id = '$matrix_id' AND status = 'published'";
    $result = mysqli_query($conn, $query);
    $game = mysqli_fetch_assoc($result);
    
    if (!$game) {
        return ['success' => false, 'message' => 'Game not found'];
    }
    
    // Create new game session
    $dice_remaining = $game['dice_limit'];
    $capital_remaining = $game['risk_capital'];
    
    $query = "INSERT INTO mg6_game_sessions 
              (matrix_id, current_cell, dice_remaining, capital_remaining, game_status) 
              VALUES ('$matrix_id', 1, '$dice_remaining', '$capital_remaining', 'playing')";
    
    if (mysqli_query($conn, $query)) {
        $session_id = mysqli_insert_id($conn);
        
        // Store session ID in PHP session
        $_SESSION['game_session_id'] = $session_id;
        $_SESSION['matrix_id'] = $matrix_id;
        
        return [
            'success' => true,
            'session_id' => $session_id,
            'message' => 'Game started successfully'
        ];
    }
    
    return ['success' => false, 'message' => 'Failed to start game'];
}

/**
 * Get current game session
 */
function get_current_session() {
    global $conn;
    
    if (!isset($_SESSION['game_session_id'])) {
        return null;
    }
    
    $session_id = $_SESSION['game_session_id'];
    
    $query = "SELECT * FROM mg6_game_sessions WHERE id = '$session_id'";
    $result = mysqli_query($conn, $query);
    
    return mysqli_fetch_assoc($result);
}

/**
 * Update game session
 */
function update_session($session_id, $data) {
    global $conn;
    
    $updates = [];
    foreach ($data as $key => $value) {
        $value = clean_input($value);
        $updates[] = "$key = '$value'";
    }
    
    $query = "UPDATE mg6_game_sessions SET " . implode(', ', $updates) . " WHERE id = '$session_id'";
    
    return mysqli_query($conn, $query);
}

/**
 * Save player investment
 */
function save_investment($session_id, $strategy_id, $points) {
    global $conn;
    
    $strategy_id = clean_input($strategy_id);
    $points = clean_input($points);
    
    // Check if already invested
    $query = "SELECT * FROM mg6_player_investments 
              WHERE session_id = '$session_id' AND strategy_id = '$strategy_id'";
    $result = mysqli_query($conn, $query);
    
    if (mysqli_num_rows($result) > 0) {
        // Update existing investment
        $query = "UPDATE mg6_player_investments 
                  SET investment_points = '$points' 
                  WHERE session_id = '$session_id' AND strategy_id = '$strategy_id'";
    } else {
        // Insert new investment
        $query = "INSERT INTO mg6_player_investments (session_id, strategy_id, investment_points) 
                  VALUES ('$session_id', '$strategy_id', '$points')";
    }
    
    return mysqli_query($conn, $query);
}

/**
 * Remove player investment
 */
function remove_investment($session_id, $strategy_id) {
    global $conn;
    
    $strategy_id = clean_input($strategy_id);
    
    $query = "DELETE FROM mg6_player_investments 
              WHERE session_id = '$session_id' AND strategy_id = '$strategy_id'";
    
    return mysqli_query($conn, $query);
}

/**
 * Get player investments
 */
function get_player_investments($session_id) {

    global $conn;

    $session_id = (int)$session_id;

    $query = "
        SELECT 
            pi.id,
            pi.session_id,
            pi.strategy_id,
            pi.strategy_type,
            pi.risk_id,
            pi.investment_points,
            s.strategy_name,
            s.response_points
        FROM mg6_player_investments pi
        INNER JOIN mg6_riskhop_strategies s 
            ON pi.strategy_id = s.id
        WHERE pi.session_id = $session_id
    ";

    $result = mysqli_query($conn, $query);

    $investments = [];

    while ($row = mysqli_fetch_assoc($result)) {
        $investments[] = $row;
    }

    return $investments;
}

/**
 * Calculate threat protection percentage
 */
function calculate_threat_protection($threat_id, $player_investments) {

    $total_invested = 0;

    foreach ($player_investments as $investment) {
        if (
            $investment['strategy_type'] === 'threat' &&
            $investment['risk_id'] == $threat_id
        ) {
            $total_invested += (int)$investment['investment_points'];
        }
    }

    // ✅ NEW LOGIC
    $total_required = 0;
    $required = get_threat_strategies($threat_id);

    foreach($required as $s){
        $total_required += $s['response_points'];
    }

    if($total_required == 0){
        return 100;
    }

    $percentage = ($total_invested / $total_required) * 100;

    return min(100, round($percentage));
}
/**
 * Calculate opportunity exploitation percentage
 */
function calculate_opportunity_exploitation($opportunity_id, $player_investments) {

    $total_invested = 0;

    foreach ($player_investments as $investment) {
        if (
            $investment['strategy_type'] === 'opportunity' &&
            $investment['risk_id'] == $opportunity_id
        ) {
            $total_invested += (int)$investment['investment_points'];
        }
    }

    // ✅ NEW LOGIC
    $total_required = 0;
    $required = get_opportunity_strategies($opportunity_id);

    foreach($required as $s){
        $total_required += $s['response_points'];
    }

    if($total_required == 0){
        return 100;
    }

    $percentage = ($total_invested / $total_required) * 100;

    return min(100, round($percentage));
}

/**
 * Process threat (snake) landing
 */
function process_threat($session, $threat, $player_investments) {
    $protection_percentage = calculate_threat_protection($threat['id'], $player_investments);
    
    $full_slide = $threat['cell_from'] - $threat['cell_to'];
    $actual_slide = round($full_slide * ((100 - $protection_percentage) / 100));
    
    $final_cell = $threat['cell_from'] - $actual_slide;
    
    return [
        'final_cell' => $final_cell,
        'protection_percentage' => $protection_percentage,
        'slide_distance' => $actual_slide,
        'full_slide' => $full_slide
    ];
}

/**
 * Process opportunity (ladder) landing
 */
function process_opportunity($session, $opportunity, $player_investments) {
    $exploitation_percentage = calculate_opportunity_exploitation($opportunity['id'], $player_investments);
    
    $full_climb = $opportunity['cell_to'] - $opportunity['cell_from'];
    $actual_climb = round($full_climb * ($exploitation_percentage / 100));
    
    $final_cell = $opportunity['cell_from'] + $actual_climb;
    
    return [
        'final_cell' => $final_cell,
        'exploitation_percentage' => $exploitation_percentage,
        'climb_distance' => $actual_climb,
        'full_climb' => $full_climb
    ];
}

/**
 * Save game move
 */
function save_game_move($session_id, $move_data) {
    global $conn;
    
    $move_number = clean_input($move_data['move_number']);
    $dice_value = (int) clean_input($move_data['dice_value']);
    $from_cell = clean_input($move_data['from_cell']);
    $to_cell = clean_input($move_data['to_cell']);
    $event_type = clean_input($move_data['event_type']);
    
    // Truncate description to prevent column overflow
    $raw_desc = $move_data['event_description'] ?? '';
    $event_description = clean_input(substr($raw_desc, 0, 250));
    
    $query = "INSERT INTO mg6_game_moves 
              (session_id, move_number, dice_value, from_cell, to_cell, event_type, event_description) 
              VALUES 
              ('$session_id', '$move_number', '$dice_value', '$from_cell', '$to_cell', '$event_type', '$event_description')";
    
    $result = mysqli_query($conn, $query);
    
    if (!$result) {
        // Log error (silent failure fix)
        $error_msg = date('[Y-m-d H:i:s] ') . "DB Error in save_game_move: " . mysqli_error($conn) . " | Query: $query" . PHP_EOL;
        @file_put_contents(__DIR__ . '/error_log.txt', $error_msg, FILE_APPEND);
    }
    
    return $result;
}

/**
 * End game and save statistics
 */
function end_game($session_id) {
    global $conn;
    
    // Get session data
    $query = "SELECT * FROM mg6_game_sessions WHERE id = '$session_id'";
    $result = mysqli_query($conn, $query);
    $session = mysqli_fetch_assoc($result);
    
    if (!$session) {
        return ['success' => false, 'message' => 'Session not found'];
    }
    
    // Get game data
    $game_data = get_game_data($session['matrix_id']);
    $total_cells = $game_data['game']['total_cells'];
    
    // Count moves
    $query = "SELECT COUNT(*) as total FROM mg6_game_moves WHERE session_id = '$session_id'";
    $result = mysqli_query($conn, $query);
    $total_dice_used = mysqli_fetch_assoc($result)['total'];
    
    // Count threat events
    $query = "SELECT COUNT(*) as total FROM mg6_game_moves 
              WHERE session_id = '$session_id' AND (event_type = 'snake' OR event_type = 'threat')";
    $result = mysqli_query($conn, $query);
    $threats_encountered = mysqli_fetch_assoc($result)['total'];
    
    // Count opportunity events
    $query = "SELECT COUNT(*) as total FROM mg6_game_moves 
              WHERE session_id = '$session_id' AND (event_type = 'ladder' OR event_type = 'opportunity')";
    $result = mysqli_query($conn, $query);
    $opportunities_encountered = mysqli_fetch_assoc($result)['total'];
    
    // Count wildcard events
    $query = "SELECT COUNT(*) as total FROM mg6_game_moves 
              WHERE session_id = '$session_id' AND event_type = 'wildcard'";
    $result = mysqli_query($conn, $query);
    $wildcards_opened = mysqli_fetch_assoc($result)['total'];
    
    // Calculate score (simple formula)
    $score = ($session['current_cell'] / $total_cells) * 100;
    
    // Save statistics
    $query = "INSERT INTO mg6_game_statistics 
              (session_id, max_cell_reached, total_dice_used, threats_protected, threats_total, 
               opportunities_exploited, opportunities_total, wildcards_opened, final_capital, game_score) 
              VALUES 
              ('$session_id', '{$session['current_cell']}', '$total_dice_used', '$threats_encountered', 
               '" . count($game_data['threats']) . "', '$opportunities_encountered', 
               '" . count($game_data['opportunities']) . "', '$wildcards_opened', 
               '{$session['capital_remaining']}', '$score')";
    
    mysqli_query($conn, $query);
    
    // Update session status
    update_session($session_id, ['game_status' => 'completed', 'end_time' => date('Y-m-d H:i:s')]);
    
    // Clear session
    unset($_SESSION['game_session_id']);
    unset($_SESSION['matrix_id']);
    
    return [
        'success' => true,
        'statistics' => [
            'max_cell_reached' => $session['current_cell'],
            'total_cells' => $total_cells,
            'total_dice_used' => $total_dice_used,
            'threats_protected' => $threats_encountered,
            'threats_total' => count($game_data['threats']),
            'opportunities_exploited' => $opportunities_encountered,
            'opportunities_total' => count($game_data['opportunities']),
            'wildcards_opened' => $wildcards_opened,
            'final_capital' => $session['capital_remaining'],
            'game_score' => round($score)
        ]
    ];
}

/**
 * Get cell type and details
 */
function get_cell_info($matrix_id, $cell_number) {
    $game_data = get_game_data($matrix_id);
    
    // Check if it's a threat
    foreach ($game_data['threats'] as $threat) {
        if ($threat['cell_from'] == $cell_number) {
            $strategies = get_threat_strategies($threat['id']);
            return [
                'type' => 'threat',
                'data' => $threat,
                'strategies' => $strategies
            ];
        }
    }
    
    // Check if it's an opportunity
    foreach ($game_data['opportunities'] as $opportunity) {
        if ($opportunity['cell_from'] == $cell_number) {
            $strategies = get_opportunity_strategies($opportunity['id']);
            return [
                'type' => 'opportunity',
                'data' => $opportunity,
                'strategies' => $strategies
            ];
        }
    }
    
    // Check if it's a bonus
    if (isset($game_data['bonus_cells'][$cell_number])) {
        return [
            'type' => 'bonus',
            'data' => $game_data['bonus_cells'][$cell_number]
        ];
    }
    
    // Check if it's an audit
    if (in_array($cell_number, $game_data['audit_cells'])) {
        return [
            'type' => 'audit',
            'data' => ['cell_number' => $cell_number]
        ];
    }
    
    // Check if it's a wildcard
    if (in_array($cell_number, $game_data['wildcard_cells'])) {
        return [
            'type' => 'wildcard',
            'data' => ['cell_number' => $cell_number]
        ];
    }
    
    // Neutral cell
    return [
        'type' => 'neutral',
        'data' => ['cell_number' => $cell_number]
    ];
}
?>
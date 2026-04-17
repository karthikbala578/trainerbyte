<?php
/**
 * Throw Dice
 * Handle dice roll and player movement
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
if ($session['game_status'] != 'playing') {
    json_response(false, 'Game is not active');
}

// Check if dice remaining
if ($session['dice_remaining'] <= 0) {
    json_response(false, 'No dice remaining');
}

// Get game data
$game_data = get_game_data($session['matrix_id']);

// Get move number
global $conn;
$query = "SELECT COUNT(*) as count FROM mg6_game_moves WHERE session_id = '$session_id'";
$result = mysqli_query($conn, $query);
$move_number = mysqli_fetch_assoc($result)['count'] + 1;

// Check cell type and handle events
// Roll dice (1-6)
$dice_value = rand(1, 6);

// Current position
$from_cell = (int)$session['current_cell'];

// Calculate landing position
$to_cell = $from_cell + $dice_value;

// Exact roll rule
$total_cells = (int)$game_data['game']['total_cells'];

if ($to_cell > $total_cells) {
    $to_cell = $from_cell; // stay if overshoot
}

// Default values
// Default values
$event_type = 'normal';
$event_description = '';
$outcome_percentage = 0;
$final_cell = $to_cell;

/* -----------------------------------------
   IMPORTANT FIX
   If player didn't move, skip cell events
----------------------------------------- */

$player_investments = get_player_investments($session_id);

if ($to_cell == $from_cell) {

    $cell_info = [
        'type' => 'normal',
        'data' => []
    ];

} else {

    $cell_info = get_cell_info($session['matrix_id'], $to_cell);

}

if (!$cell_info) {
    $cell_info = [
        'type' => 'normal',
        'data' => []
    ];
}


/* -------------------------------------------------------
   HANDLE CELL EVENTS
------------------------------------------------------- */

switch ($cell_info['type']) {

/* =======================
   OPPORTUNITY (LADDER)
======================= */
case 'opportunity':

    $opportunity = $cell_info['data'];

    // 🔥 Check if invested
    $hasInvestment = false;

    foreach ($player_investments as $inv) {
        if (
            $inv['strategy_type'] === 'opportunity' &&
            (int)$inv['risk_id'] === (int)$opportunity['id']
        ) {
            $hasInvestment = true;
            break;
        }
    }

    // 🔥 Force outcome = 0 if no investment
    if (!$hasInvestment) {
        $outcome_percentage = 0;
    } else {
       $result = process_opportunity($session, $opportunity, $player_investments);

$outcome_percentage = $result['exploitation_percentage'];
$final_cell = $result['final_cell'];


    }

    $event_type = 'ladder';


    $event_description = "Opportunity: {$opportunity['opportunity_name']} ({$outcome_percentage}%)";

break;


case 'threat':

    $threat = $cell_info['data'];

    // 🔥 Check if invested
    $hasInvestment = false;

    foreach ($player_investments as $inv) {
        if (
            $inv['strategy_type'] === 'threat' &&
            (int)$inv['risk_id'] === (int)$threat['id']
        ) {
            $hasInvestment = true;
            break;
        }
    }

    // 🔥 FORCE outcome = 0 if NO investment
    if (!$hasInvestment) {
        $outcome_percentage = 0;
    } else {
       $result = process_threat($session, $threat, $player_investments);

$outcome_percentage = $result['protection_percentage'];
$final_cell = $result['final_cell'];


    }

    $event_type = 'snake';


    $event_description = "Threat: {$threat['threat_name']} ({$outcome_percentage}%)";

break;


case 'bonus':

    $event_type = 'bonus';

   $bonus_amount = isset($cell_info['data']['bonus_amount']) 
    ? (int)$cell_info['data']['bonus_amount'] 
    : 0;

    $event_description = "Bonus +{$bonus_amount} capital";

    // ✅ CALCULATE NEW CAPITAL
    $new_capital = (int)$session['capital_remaining'] + $bonus_amount;

    // ✅ UPDATE DB
    update_session($session_id, [
        'capital_remaining' => $new_capital
    ]);

    // ✅ VERY IMPORTANT → UPDATE LOCAL SESSION ALSO
    $session['capital_remaining'] = $new_capital;

break;


case 'audit':

    $event_type = 'audit';
    $event_description = "Audit cell reached";

break;



case 'wildcard':

    $event_type = 'wildcard';
    $event_description = "Wildcard triggered";

break;

}

/* -----------------------------------------
   SAFETY CLAMP
----------------------------------------- */

$final_cell = min($final_cell, $total_cells);
$final_cell = max($final_cell, 1);

// Update session
$dice_remaining = $session['dice_remaining'] - 1;
update_session($session_id, [
    'current_cell' => $final_cell,
    'dice_remaining' => $dice_remaining
]);

// Save move to history
save_game_move($session_id, [
    'move_number' => $move_number,
    'dice_value' => $dice_value,
    'from_cell' => $from_cell,
    'to_cell' => $to_cell,
    'final_cell' => $final_cell,
    'event_type' => $event_type,
    'event_description' => $event_description
]);

// Get updated session
$updated_session = get_current_session();

// Check if game ended
$game_completed = false;
$game_over = false;

if ($final_cell >= $game_data['game']['total_cells']) {
    $game_completed = true;
}

if ($dice_remaining <= 0 && !$game_completed) {
    $game_over = true;
}

// Return response
json_response(true, 'Dice rolled successfully', [
    'dice_value' => $dice_value,
    'from_cell' => $from_cell,
    'to_cell' => $to_cell,
    'final_cell' => $final_cell,
    'dice_remaining' => $dice_remaining,
    'capital_remaining' => $session['capital_remaining'],
    'move_number' => $move_number,
    'event_type' => $event_type,
    'event_description' => $event_description,
    'outcome_percentage' => $outcome_percentage,
    // Explicitly pass bonus amount if applicable for easier frontend access
    'bonus_amount' => ($event_type === 'bonus') ? (int) $cell_info['data']['bonus_amount'] : 0,
    'cell_info' => $cell_info,
    'game_completed' => $game_completed,
'game_over' => $game_over
]);
?>
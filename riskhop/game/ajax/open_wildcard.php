<?php
/**
 * Open Wild Card
 * Handle wildcard selection and apply effects
 */

require_once '../../config.php';
require_once '../../functions.php';
require_once '../game_engine.php';

header('Content-Type: application/json');

/* =========================
   VALIDATE REQUEST
========================= */

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(false, 'Invalid request method');
}

$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['session_id']) || empty($input['session_id'])) {
    json_response(false, 'Session ID is required');
}

if (!isset($input['wildcard_id']) || empty($input['wildcard_id'])) {
    json_response(false, 'Wildcard ID is required');
}

$session_id = clean_input($input['session_id']);
$wildcard_id = clean_input($input['wildcard_id']);

/* =========================
   GET SESSION
========================= */

$session = get_current_session();

if (!$session || $session['id'] != $session_id) {
    json_response(false, 'Invalid session');
}

if ($session['game_status'] != 'playing') {
    json_response(false, 'Game is not active');
}

/* =========================
   GET WILDCARD
========================= */

global $conn;

$query = "SELECT * FROM mg6_riskhop_wildcards 
          WHERE id = '$wildcard_id' 
          AND matrix_id = '{$session['matrix_id']}'";

$result = mysqli_query($conn, $query);

if (mysqli_num_rows($result) == 0) {
    json_response(false, 'Invalid wildcard');
}

$wildcard = mysqli_fetch_assoc($result);

/* =========================
   CHECK IF ALREADY OPENED
========================= */

$query = "SELECT id FROM mg6_session_wildcards_opened 
          WHERE session_id = '$session_id' 
          AND wildcard_id = '$wildcard_id'";

$result = mysqli_query($conn, $query);
$already_opened = (mysqli_num_rows($result) > 0);

/* =========================
   PREPARE EFFECTS
========================= */

$capital_change = (int)$wildcard['risk_capital_effect'];
$dice_change    = (int)$wildcard['dice_effect'];
$cell_change    = (int)$wildcard['cell_effect'];

$new_capital = $session['capital_remaining'];
$new_dice    = $session['dice_remaining'];
$new_cell    = $session['current_cell'];

$game_ended = false;

/* =========================
   GET GAME DATA
========================= */

$game_data = get_game_data($session['matrix_id']);

/* =========================
   APPLY EFFECTS
========================= */

if (!$already_opened) {

    // Record wildcard opening
    $query = "INSERT INTO mg6_session_wildcards_opened 
              (session_id, wildcard_id) 
              VALUES ('$session_id', '$wildcard_id')";
    mysqli_query($conn, $query);

    /* APPLY CAPITAL */
    if ($capital_change != 0) {
        $new_capital = $session['capital_remaining'] + $capital_change;
    }

    /* APPLY DICE */
    if ($dice_change != 0) {
        $new_dice = $session['dice_remaining'] + $dice_change;
    }

    /* APPLY CELL */
    if ($cell_change != 0) {
        $new_cell = $session['current_cell'] + $cell_change;
    }

    /* VALIDATION */

    if ($new_capital < 0) $new_capital = 0;
    if ($new_dice < 0) $new_dice = 0;

    if ($new_cell < 1) $new_cell = 1;

    if ($new_cell > $game_data['game']['total_cells']) {
        $new_cell = $game_data['game']['total_cells'];
    }

    /* UPDATE SESSION */

    update_session($session_id, [
        'capital_remaining' => $new_capital,
        'dice_remaining'    => $new_dice,
        'current_cell'      => $new_cell
    ]);

    /* =========================
       LOG MOVE
    ========================== */

    $query = "SELECT COUNT(*) as moves 
              FROM mg6_game_moves 
              WHERE session_id = '$session_id'";

    $result = mysqli_query($conn, $query);
    $move_number = mysqli_fetch_assoc($result)['moves'] + 1;

    $event_description = "Wildcard: {$wildcard['wildcard_name']}. ";

    if ($capital_change > 0) {
        $event_description .= "Capital increased by {$capital_change}. ";
    } elseif ($capital_change < 0) {
        $event_description .= "Capital decreased by " . abs($capital_change) . ". ";
    }

    if ($dice_change > 0) {
        $event_description .= "Gained {$dice_change} dice. ";
    } elseif ($dice_change < 0) {
        $event_description .= "Lost " . abs($dice_change) . " dice. ";
    }

    if ($cell_change > 0) {
        $event_description .= "Moved forward {$cell_change} cells. ";
    } elseif ($cell_change < 0) {
        $event_description .= "Moved back " . abs($cell_change) . " cells. ";
    }

    save_game_move($session_id, [
        'move_number' => $move_number,
        'dice_value'  => 0,
        'from_cell'   => $session['current_cell'],
        'to_cell'     => $new_cell,
        'event_type'  => 'wildcard',
        'event_description' => $event_description
    ]);

    /* =========================
       CHECK GAME END
    ========================== */

    if ($new_dice <= 0 || $new_cell >= $game_data['game']['total_cells']) {
        $game_ended = true;
    }
}

/* =========================
   RESPONSE
========================= */

json_response(true, 'Wildcard processed', [

    'wildcard' => [
        'id' => $wildcard['id'],
        'wildcard_name' => $wildcard['wildcard_name'],
        'wildcard_description' => $wildcard['wildcard_description'],
        'wildcard_image' => $wildcard['wildcard_image']
    ],

    'effects' => [
        'capital_change' => $capital_change,
        'dice_change'    => $dice_change,
        'cell_change'    => $cell_change
    ],

    'new_values' => [
        'capital_remaining' => $new_capital,
        'dice_remaining'    => $new_dice,
        'current_cell'      => $new_cell
    ],

    'game_ended' => $game_ended,
    'already_opened' => $already_opened
]);
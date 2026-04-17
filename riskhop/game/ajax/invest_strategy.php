<?php
/**
 * Invest Strategy
 * Handle player strategy investments
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

if (!isset($input['strategies']) || !is_array($input['strategies'])) {
    json_response(false, 'Strategies array is required');
}

$session_id = intval($input['session_id']);
$strategies = $input['strategies'];

// Get current session
$session = get_current_session();

if (!$session || $session['id'] != $session_id) {
    json_response(false, 'Invalid session');
}

// Check if game is still active
if ($session['game_status'] != 'playing') {
    json_response(false, 'Game is not active');
}

global $conn;

/* -----------------------------------------------------
   1️⃣ Calculate total investment
----------------------------------------------------- */

$total_investment = 0;

foreach ($strategies as $strategy) {

    if (
        !isset($strategy['strategy_id']) ||
        !isset($strategy['points']) ||
        !isset($strategy['risk_id']) ||
        !isset($strategy['strategy_type'])
    ) {
        json_response(false, 'Invalid strategy data');
    }

    $total_investment += intval($strategy['points']);
}

/* -----------------------------------------------------
   2️⃣ Calculate previous investments
----------------------------------------------------- */

$previous_total = 0;

$prev_investments = get_player_investments($session_id);

foreach ($prev_investments as $inv) {
    if (isset($inv['investment_points'])) {
        $previous_total += intval($inv['investment_points']);
    }
}

/* -----------------------------------------------------
   3️⃣ Calculate available capital
----------------------------------------------------- */

$basis_capital = intval($session['capital_remaining']) + $previous_total;

if ($total_investment > $basis_capital) {

    json_response(false, 'Insufficient risk capital', [
        'required' => $total_investment,
        'available' => $basis_capital
    ]);

}

/* -----------------------------------------------------
   4️⃣ Clear old investments
----------------------------------------------------- */

$query = "DELETE FROM mg6_player_investments WHERE session_id = '$session_id'";
mysqli_query($conn, $query);

/* -----------------------------------------------------
   5️⃣ Save new investments
----------------------------------------------------- */

$investments_saved = [];

foreach ($strategies as $strategy) {

    $strategy_id = intval($strategy['strategy_id']);
    $points = intval($strategy['points']);
    $risk_id = intval($strategy['risk_id']);
    $strategy_type = clean_input($strategy['strategy_type']);

    /* Verify strategy exists */

    $query = "SELECT * FROM mg6_riskhop_strategies WHERE id = '$strategy_id'";
    $result = mysqli_query($conn, $query);

    if (!$result || mysqli_num_rows($result) == 0) {
        json_response(false, 'Invalid strategy ID: ' . $strategy_id);
    }

    $strategy_data = mysqli_fetch_assoc($result);

    /* Verify correct points */

    if ($points != $strategy_data['response_points']) {
        json_response(false, 'Invalid points for strategy: ' . $strategy_data['strategy_name']);
    }

    /* Prevent duplicate investment */

    $exists_query = "
        SELECT id 
        FROM mg6_player_investments
        WHERE session_id='$session_id'
        AND strategy_id='$strategy_id'
        AND risk_id='$risk_id'
        LIMIT 1
    ";

    $exists = mysqli_query($conn, $exists_query);

    if ($exists && mysqli_num_rows($exists) > 0) {
        continue;
    }

    /* Insert investment */

    $query = "
    INSERT INTO mg6_player_investments
    (session_id, strategy_id, strategy_type, risk_id, investment_points)
    VALUES
    ('$session_id','$strategy_id','$strategy_type','$risk_id','$points')
    ";

    $result = mysqli_query($conn, $query);

    if (!$result) {
        json_response(false, 'Failed to save investment for strategy: ' . $strategy_data['strategy_name']);
    }

    $investments_saved[] = [
        'strategy_id' => $strategy_id,
        'strategy_name' => $strategy_data['strategy_name'],
        'points' => $points
    ];
}

/* -----------------------------------------------------
   6️⃣ Update capital
----------------------------------------------------- */

$new_capital = $basis_capital - $total_investment;

update_session($session_id, [
    'capital_remaining' => $new_capital
]);

/* -----------------------------------------------------
   7️⃣ Get updated investments
----------------------------------------------------- */

$player_investments = get_player_investments($session_id);

/* -----------------------------------------------------
   8️⃣ Return response
----------------------------------------------------- */

json_response(true, 'Investments saved successfully', [
    'investments' => $player_investments,
    'total_invested' => $total_investment,
    'capital_remaining' => $new_capital,
    'investment_count' => count($player_investments)
]);

?>
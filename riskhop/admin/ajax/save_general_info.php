<?php

require_once '../../config.php';
require_once '../../functions.php';

if (!is_admin_logged_in()) {
    json_response(false, 'Unauthorized access');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(false, 'Invalid request method');
}

// ======================
// INPUTS
// ======================
$game_id      = isset($_POST['game_id']) ? intval($_POST['game_id']) : 0;
$game_name    = clean_input($_POST['game_name'] ?? '');
$description  = clean_input($_POST['description'] ?? '');
$risk_capital = isset($_POST['capital']) ? floatval($_POST['capital']) : 0;
$dice_limit   = isset($_POST['dice_limit']) ? intval($_POST['dice_limit']) : 0;
$board_size   = isset($_POST['board_size']) ? intval($_POST['board_size']) : 0;

$admin_id = $_SESSION['admin_id'];

// ======================
// VALIDATION
// ======================

// Game name
if (strlen($game_name) < 3 || strlen($game_name) > 50) {
    json_response(false, 'Game name must be between 3–50 characters');
}

// Description
if (strlen($description) < 10) {
    json_response(false, 'Description must be at least 10 characters');
}

// Capital
if ($risk_capital <= 0 || $risk_capital > 1000000) {
    json_response(false, 'Risk capital must be between 1 and 1,000,000');
}

// Dice
if ($dice_limit < 1 || $dice_limit > 15) {
    json_response(false, 'Dice limit must be between 1 and 15');
}

// Board size
$allowed_sizes = [6, 8, 10, 12];
if (!in_array($board_size, $allowed_sizes)) {
    json_response(false, 'Invalid board size');
}

$matrix_type = $board_size . 'x' . $board_size;
$total_cells = $board_size * $board_size;

// ======================
// UPDATE EXISTING GAME
// ======================
if ($game_id > 0) {

    // 🔥 CHECK EXISTING BOARD SIZE (IMPORTANT)
    $res = mysqli_query($conn, "SELECT matrix_type FROM mg6_riskhop_matrix WHERE id = '$game_id'");
    
    if (!$res || mysqli_num_rows($res) == 0) {
        json_response(false, 'Game not found');
    }

    $row = mysqli_fetch_assoc($res);
    $existing_size = intval(explode('x', $row['matrix_type'])[0]);

    // ❌ Prevent board size change
    if ($existing_size !== $board_size) {
        json_response(false, 'Board size cannot be changed after creation');
    }

    // ✅ UPDATE
    $query = "UPDATE mg6_riskhop_matrix SET 
              game_name = '".mysqli_real_escape_string($conn, $game_name)."',
              description = '".mysqli_real_escape_string($conn, $description)."',
              risk_capital = '$risk_capital',
              dice_limit = '$dice_limit'
              WHERE id = '$game_id'";

    if (mysqli_query($conn, $query)) {

        $_SESSION['current_game_id'] = $game_id;

        json_response(true, 'Game updated successfully', [
            'game_id' => $game_id
        ]);

    } else {
        json_response(false, 'Failed to update game: ' . mysqli_error($conn));
    }

}


// ======================
// CREATE NEW GAME
// ======================
else {

    $query = "INSERT INTO mg6_riskhop_matrix 
        (game_name, description, risk_capital, dice_limit, matrix_type, total_cells, created_by, status) 
        VALUES 
        (
        '".mysqli_real_escape_string($conn, $game_name)."',
        '".mysqli_real_escape_string($conn, $description)."',
        '$risk_capital',
        '$dice_limit',
        '$matrix_type',
        '$total_cells',
        '$admin_id',
        'draft'
        )";

    if (mysqli_query($conn, $query)) {

        $game_id = mysqli_insert_id($conn);
        $_SESSION['current_game_id'] = $game_id;

        json_response(true, 'Game created successfully', [
            'game_id' => $game_id
        ]);

    } else {
        json_response(false, 'Failed to create game: ' . mysqli_error($conn));
    }

}
?>
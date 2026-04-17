<?php
require_once '../../config.php';
require_once '../../functions.php';

if (!is_admin_logged_in()) {
    json_response(false, 'Unauthorized access');
}

$opportunity_id = intval($_POST['opportunity_id']);
$opportunity_name = clean_input($_POST['opportunity_name']);
$opportunity_description = clean_input($_POST['opportunity_description']);
$cell_from = intval($_POST['cell_from']);
$cell_to = intval($_POST['cell_to']);

// Get current opportunity data
$query = "SELECT matrix_id, cell_from, cell_to FROM mg6_riskhop_opportunities WHERE id = '$opportunity_id'";
$result = mysqli_query($conn, $query);
$opportunity = mysqli_fetch_assoc($result);
$matrix_id = $opportunity['matrix_id'];
$old_cell_from = $opportunity['cell_from'];
$old_cell_to = $opportunity['cell_to'];

// Get total cells
$query = "SELECT total_cells FROM mg6_riskhop_matrix WHERE id = '$matrix_id'";
$result = mysqli_query($conn, $query);
$game = mysqli_fetch_assoc($result);
$total_cells = $game['total_cells'];

if ($cell_to <= $cell_from) {
    json_response(false, 'Ladder must go up. TO cell must be greater than FROM cell.');
}

if ($cell_to < ($cell_from + 6)) {
    json_response(false, 'Ladder must have minimum gap of 6 cells between FROM and TO.');
}

if ($cell_to > $total_cells || $cell_from < 1) {
    json_response(false, 'Cell numbers must be within board range (1 to ' . $total_cells . ').');
}

// Check if FROM cell is used by something else (not the current opportunity)
if ($cell_from != $old_cell_from) {
    $from_check = is_cell_used($matrix_id, $cell_from);
    if ($from_check['used']) {
        json_response(false, 'Cell ' . $cell_from . ' is already used by a ' . $from_check['type'] . '. Choose different cell.');
    }
}

// Check if TO cell is used by something else (not the current opportunity)
if ($cell_to != $old_cell_to) {
    $to_check = is_cell_used($matrix_id, $cell_to);
    if ($to_check['used']) {
        json_response(false, 'Cell ' . $cell_to . ' is already used by a ' . $to_check['type'] . '. Choose different cell.');
    }
}

// Update
$query = "UPDATE mg6_riskhop_opportunities SET 
          opportunity_name = '$opportunity_name',
          opportunity_description = '$opportunity_description',
          cell_from = '$cell_from',
          cell_to = '$cell_to'
          WHERE id = '$opportunity_id'";

if (mysqli_query($conn, $query)) {
    json_response(true, 'Opportunity updated successfully');
} else {
    json_response(false, 'Failed to update opportunity');
}
?>
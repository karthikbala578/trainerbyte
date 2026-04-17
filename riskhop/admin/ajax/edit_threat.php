<?php
require_once '../../config.php';
require_once '../../functions.php';

if (!is_admin_logged_in()) {
    json_response(false, 'Unauthorized access');
}

$threat_id = intval($_POST['threat_id']);
$threat_name = clean_input($_POST['threat_name']);
$threat_description = clean_input($_POST['threat_description']);
$cell_from = intval($_POST['cell_from']);
$cell_to = intval($_POST['cell_to']);

// Get current threat data
$query = "SELECT matrix_id, cell_from, cell_to FROM mg6_riskhop_threats WHERE id = '$threat_id'";
$result = mysqli_query($conn, $query);
$threat = mysqli_fetch_assoc($result);
$matrix_id = $threat['matrix_id'];
$old_cell_from = $threat['cell_from'];
$old_cell_to = $threat['cell_to'];

// Get total cells
$query = "SELECT total_cells FROM mg6_riskhop_matrix WHERE id = '$matrix_id'";
$result = mysqli_query($conn, $query);
$game = mysqli_fetch_assoc($result);
$total_cells = $game['total_cells'];

if ($cell_from <= $cell_to) {
    json_response(false, 'Snake must go down. FROM cell must be greater than TO cell.');
}

if ($cell_from < ($cell_to + 6)) {
    json_response(false, 'Snake must have minimum gap of 6 cells between FROM and TO.');
}

if ($cell_from > $total_cells || $cell_to < 1) {
    json_response(false, 'Cell numbers must be within board range (1 to ' . $total_cells . ').');
}

if ($cell_from != $old_cell_from) {
    $from_check = is_cell_used($matrix_id, $cell_from);
    if ($from_check['used']) {
        json_response(false, 'Cell ' . $cell_from . ' is already used by a ' . $from_check['type'] . '. Choose different cell.');
    }
}

if ($cell_to != $old_cell_to) {
    $to_check = is_cell_used($matrix_id, $cell_to);
    if ($to_check['used']) {
        json_response(false, 'Cell ' . $cell_to . ' is already used by a ' . $to_check['type'] . '. Choose different cell.');
    }
}

// Update
$query = "UPDATE mg6_riskhop_threats SET 
          threat_name = '$threat_name',
          threat_description = '$threat_description',
          cell_from = '$cell_from',
          cell_to = '$cell_to'
          WHERE id = '$threat_id'";

if (mysqli_query($conn, $query)) {
    json_response(true, 'Threat updated successfully');
} else {
    json_response(false, 'Failed to update threat');
}
?>
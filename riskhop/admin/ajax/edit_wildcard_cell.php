<?php
require_once '../../config.php';
require_once '../../functions.php';

if (!is_admin_logged_in()) {
    json_response(false, 'Unauthorized access');
}

$id = intval($_POST['wildcard_cell_id']);
$matrix_id = intval($_POST['matrix_id']);
$cell_number = intval($_POST['cell_number']);

// Check conflict
$check = is_cell_used($matrix_id, $cell_number, $id); // update function if needed
if ($check['used']) {
    json_response(false, 'Cell ' . $cell_number . ' already used by ' . $check['type']);
}

// Update
$query = "UPDATE mg6_riskhop_wildcard_cells 
          SET cell_number = '$cell_number'
          WHERE id = '$id'";

if (mysqli_query($conn, $query)) {
    json_response(true, 'Wildcard cell updated successfully');
} else {
    json_response(false, 'Failed to update wildcard cell');
}
<?php
require_once '../../config.php';
require_once '../../functions.php';

if (!is_admin_logged_in()) {
    json_response(false, 'Unauthorized access');
}

$matrix_id = intval($_POST['matrix_id']);
$cell_number = intval($_POST['cell_number']);

// Check if cell is already used
$check = is_cell_used($matrix_id, $cell_number);
if ($check['used']) {
    json_response(false, 'Cell ' . $cell_number . ' is already used by a ' . $check['type']);
}

// Insert
$query = "INSERT INTO mg6_riskhop_wildcard_cells (matrix_id, cell_number) 
          VALUES ('$matrix_id', '$cell_number')";

if (mysqli_query($conn, $query)) {
    json_response(true, 'Wildcard cell added successfully');
    exit;
} else {
    json_response(false, 'Failed to add wildcard cell');
    exit;
}
?>
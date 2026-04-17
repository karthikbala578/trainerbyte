<?php
require_once '../../config.php';
require_once '../../functions.php';

if (!is_admin_logged_in()) {
    json_response(false, 'Unauthorized access');
}

$matrix_id = intval($_POST['matrix_id']);
$opportunity_name = clean_input($_POST['opportunity_name']);
$opportunity_description = clean_input($_POST['opportunity_description']);
$cell_from = intval($_POST['cell_from']);
$cell_to = intval($_POST['cell_to']);

// Get total cells
$query = "SELECT total_cells FROM mg6_riskhop_matrix WHERE id = '$matrix_id'";
$result = mysqli_query($conn, $query);
$game = mysqli_fetch_assoc($result);
$total_cells = $game['total_cells'];

// Validate
$validation = validate_opportunity_cells($matrix_id, $cell_from, $cell_to, $total_cells);
if (!$validation['valid']) {
    json_response(false, $validation['message']);
}

// Insert
$query = "INSERT INTO mg6_riskhop_opportunities 
          (matrix_id, opportunity_name, opportunity_description, cell_from, cell_to) 
          VALUES ('$matrix_id', '$opportunity_name', '$opportunity_description', '$cell_from', '$cell_to')";

if (mysqli_query($conn, $query)) {
    json_response(true, 'Opportunity added successfully', ['opportunity_id' => mysqli_insert_id($conn)]);
} else {
    json_response(false, 'Failed to add opportunity');
}
?>
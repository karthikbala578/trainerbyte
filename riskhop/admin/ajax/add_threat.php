<?php
require_once '../../config.php';
require_once '../../functions.php';

if (!is_admin_logged_in()) {
    json_response(false, 'Unauthorized access');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(false, 'Invalid request method');
}

// Get and sanitize inputs
$matrix_id = intval($_POST['matrix_id']);
$threat_name = clean_input($_POST['threat_name']);
$threat_description = clean_input($_POST['threat_description']);
$cell_from = intval($_POST['cell_from']);
$cell_to = intval($_POST['cell_to']);

// Get game data
$query = "SELECT * FROM mg6_riskhop_matrix WHERE id = '$matrix_id'";
$result = mysqli_query($conn, $query);
if (mysqli_num_rows($result) === 0) {
    json_response(false, 'Game not found');
}
$game = mysqli_fetch_assoc($result);
$total_cells = $game['total_cells'];

// Validate inputs
if (empty($threat_name) || empty($threat_description)) {
    json_response(false, 'Please fill all required fields');
}

// Validate cell positions
$validation = validate_threat_cells($matrix_id, $cell_from, $cell_to, $total_cells);
if (!$validation['valid']) {
    json_response(false, $validation['message']);
}

// Insert threat
$query = "INSERT INTO mg6_riskhop_threats 
          (matrix_id, threat_name, threat_description, cell_from, cell_to) 
          VALUES 
          ('$matrix_id', '$threat_name', '$threat_description', '$cell_from', '$cell_to')";

if (mysqli_query($conn, $query)) {
    $threat_id = mysqli_insert_id($conn);
    json_response(true, 'Threat added successfully', ['threat_id' => $threat_id]);
} else {
    json_response(false, 'Failed to add threat: ' . mysqli_error($conn));
}
?>
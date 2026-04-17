<?php
require_once '../../config.php';
require_once '../../functions.php';

if (!is_admin_logged_in()) {
    json_response(false, 'Unauthorized access');
}

// Get & sanitize inputs
$risk_id = intval($_POST['risk_id']);
$matrix_id = intval($_POST['matrix_id']);
$risk_type = clean_input($_POST['risk_type']);
$strategy_name = clean_input($_POST['strategy_name']);
$description = clean_input($_POST['description']);
$response_points = intval($_POST['response_points']);
$risk_type = clean_input($_POST['risk_type']);
// ✅ Basic validation
if (empty($strategy_name) || empty($description) || $response_points <= 0) {
    json_response(false, 'All fields are required and must be valid.');
}

if (!in_array($risk_type, ['threat', 'opportunity'])) {
    json_response(false, 'Invalid risk type.');
}

// ✅ Insert strategy
$query = "INSERT INTO mg6_riskhop_strategies 
(strategy_name, description, response_points, matrix_id, strategy_type)
VALUES ('$strategy_name', '$description', '$response_points', '$matrix_id', '$risk_type')";

if (!mysqli_query($conn, $query)) {
    json_response(false, 'Failed to add strategy.');
}

$strategy_id = mysqli_insert_id($conn);

// ✅ Map to risk
if ($risk_type === 'threat') {
    $map_query = "INSERT INTO mg6_threat_strategy_mapping 
                  (matrix_id, threat_id, strategy_id) 
                  VALUES ('$matrix_id', '$risk_id', '$strategy_id')";
} else {
    $map_query = "INSERT INTO mg6_opportunity_strategy_mapping 
                  (matrix_id, opportunity_id, strategy_id) 
                  VALUES ('$matrix_id', '$risk_id', '$strategy_id')";
}

if (!mysqli_query($conn, $map_query)) {
    json_response(false, 'Strategy added but mapping failed.');
}

// ✅ Final success response
json_response(true, 'Strategy added successfully', [
    'strategy_id' => $strategy_id
]);
?>
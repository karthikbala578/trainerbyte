<?php
require_once '../../config.php';
require_once '../../functions.php';

if (!is_admin_logged_in()) {
    json_response(false, 'Unauthorized access');
}

$strategy_id = intval($_POST['strategy_id']);
$matrix_id = intval($_POST['matrix_id']);

// 🔥 1. Delete mappings first
mysqli_query($conn, "DELETE FROM mg6_threat_strategy_mapping 
                     WHERE strategy_id = '$strategy_id' AND matrix_id = '$matrix_id'");

mysqli_query($conn, "DELETE FROM mg6_opportunity_strategy_mapping 
                     WHERE strategy_id = '$strategy_id' AND matrix_id = '$matrix_id'");

// 🔥 2. Delete strategy
$query = "DELETE FROM mg6_riskhop_strategies 
          WHERE id = '$strategy_id' AND matrix_id = '$matrix_id'";

if (mysqli_query($conn, $query)) {
    json_response(true, 'Strategy deleted completely');
} else {
    json_response(false, 'Failed to delete strategy');
}
?>
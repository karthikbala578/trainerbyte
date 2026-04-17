<?php
require_once '../../config.php';
require_once '../../functions.php';

if (!is_admin_logged_in()) {
    json_response(false, 'Unauthorized access');
}

$input = json_decode(file_get_contents('php://input'), true);

$risk_type = clean_input($input['risk_type']);
$risk_id = intval($input['risk_id']);
$matrix_id = intval($input['matrix_id']); // ✅ REQUIRED
$strategy_ids = $input['strategy_ids'];

if (!in_array($risk_type, ['threat', 'opportunity'])) {
    json_response(false, 'Invalid risk type');
}

if (empty($strategy_ids) || !is_array($strategy_ids)) {
    json_response(false, 'No strategies selected');
}

$success_count = 0;

mysqli_begin_transaction($conn);

try {

    foreach ($strategy_ids as $strategy_id) {

        $strategy_id = intval($strategy_id);

        // ✅ Ensure strategy belongs to SAME matrix
        $check = mysqli_query($conn, 
            "SELECT id FROM mg6_riskhop_strategies 
             WHERE id = '$strategy_id' 
             AND matrix_id = '$matrix_id'"
        );

        if (mysqli_num_rows($check) == 0) continue;

        // ✅ Check if already mapped
        if ($risk_type === 'threat') {
            $exists = mysqli_query($conn,
                "SELECT id FROM mg6_threat_strategy_mapping 
                 WHERE matrix_id = '$matrix_id'
                 AND threat_id = '$risk_id' 
                 AND strategy_id = '$strategy_id'"
            );
        } else {
            $exists = mysqli_query($conn,
                "SELECT id FROM mg6_opportunity_strategy_mapping 
                 WHERE matrix_id = '$matrix_id'
                 AND opportunity_id = '$risk_id' 
                 AND strategy_id = '$strategy_id'"
            );
        }

        if (mysqli_num_rows($exists) > 0) {
            continue; // 🚫 skip duplicates
        }

        // ✅ Insert mapping
        if ($risk_type === 'threat') {
            $query = "INSERT INTO mg6_threat_strategy_mapping 
                      (matrix_id, threat_id, strategy_id) 
                      VALUES ('$matrix_id', '$risk_id', '$strategy_id')";
        } else {
            $query = "INSERT INTO mg6_opportunity_strategy_mapping 
                      (matrix_id, opportunity_id, strategy_id) 
                      VALUES ('$matrix_id', '$risk_id', '$strategy_id')";
        }

        if (!mysqli_query($conn, $query)) {
            throw new Exception('Insert failed');
        }

        $success_count++;
    }

    mysqli_commit($conn);

    json_response(true, "$success_count strategies mapped successfully");

} catch (Exception $e) {

    mysqli_rollback($conn);

    json_response(false, 'Failed to map strategies');
}
?>
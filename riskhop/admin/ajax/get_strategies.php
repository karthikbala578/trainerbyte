<?php
require_once '../../config.php';
require_once '../../functions.php';

/* 🔥 VERY IMPORTANT: FORCE JSON OUTPUT */
header('Content-Type: application/json');
error_reporting(0);
ini_set('display_errors', 0);

/* 🔐 AUTH */
if (!is_admin_logged_in()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

/* 📥 INPUT */
$risk_type = isset($_GET['risk_type']) ? clean_input($_GET['risk_type']) : '';
$risk_id   = isset($_GET['risk_id']) ? intval($_GET['risk_id']) : 0;
$matrix_id = isset($_GET['matrix_id']) ? intval($_GET['matrix_id']) : 0;

/* ✅ VALIDATION */
if (!in_array($risk_type, ['threat', 'opportunity'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid risk type']);
    exit;
}

/* =========================
   LOAD CURRENT STRATEGIES
========================= */

if ($risk_type === 'threat') {

    $query = "SELECT s.*
              FROM mg6_riskhop_strategies s
              INNER JOIN mg6_threat_strategy_mapping tsm 
                ON s.id = tsm.strategy_id
              WHERE tsm.threat_id = '$risk_id'
              AND tsm.matrix_id = '$matrix_id'
              AND s.matrix_id = '$matrix_id'
              ORDER BY s.strategy_name";

} else {

    $query = "SELECT s.*
              FROM mg6_riskhop_strategies s
              INNER JOIN mg6_opportunity_strategy_mapping osm 
                ON s.id = osm.strategy_id
              WHERE osm.opportunity_id = '$risk_id'
              AND osm.matrix_id = '$matrix_id'
              AND s.matrix_id = '$matrix_id'
              ORDER BY s.strategy_name";
}

/* 🔥 EXECUTE */
$result = mysqli_query($conn, $query);

if (!$result) {
    echo json_encode([
        'success' => false,
        'message' => 'Query error',
        'error' => mysqli_error($conn)
    ]);
    exit;
}

$strategies = [];
while ($row = mysqli_fetch_assoc($result)) {
    $strategies[] = $row;
}

/* =========================
   LOAD MAPPING LIST
========================= */

if ($risk_type === 'threat') {

    $map_query = "
        SELECT s.*
        FROM mg6_riskhop_strategies s
        WHERE s.matrix_id = '$matrix_id'
        AND s.strategy_type = 'threat'
        AND s.id NOT IN (
            SELECT strategy_id 
            FROM mg6_threat_strategy_mapping
            WHERE threat_id = '$risk_id'
            AND matrix_id = '$matrix_id'
        )
        ORDER BY s.strategy_name
    ";

} else {

    $map_query = "
        SELECT s.*
        FROM mg6_riskhop_strategies s
        WHERE s.matrix_id = '$matrix_id'
        AND s.strategy_type = 'opportunity'
        AND s.id NOT IN (
            SELECT strategy_id 
            FROM mg6_opportunity_strategy_mapping
            WHERE opportunity_id = '$risk_id'
            AND matrix_id = '$matrix_id'
        )
        ORDER BY s.strategy_name
    ";
}

/* 🔥 EXECUTE */
$map_result = mysqli_query($conn, $map_query);

if (!$map_result) {
    echo json_encode([
        'success' => false,
        'message' => 'Mapping query error',
        'error' => mysqli_error($conn)
    ]);
    exit;
}

$mapping_list = [];
while ($row = mysqli_fetch_assoc($map_result)) {
    $mapping_list[] = $row;
}

/* =========================
   FINAL RESPONSE
========================= */

echo json_encode([
    'success' => true,
    'message' => 'Strategies loaded',
    'data' => [
        'strategies' => $strategies,
        'mapping_list' => $mapping_list
    ]
]);
exit;
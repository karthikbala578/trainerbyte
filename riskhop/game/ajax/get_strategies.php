<?php
require_once '../../config.php';
require_once '../../functions.php';
require_once '../game_engine.php';

header('Content-Type: application/json');

// Check request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(false, 'Invalid request method');
}

$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['matrix_id']) || empty($input['matrix_id'])) {
    json_response(false, 'Matrix ID is required');
}

$matrix_id = intval($input['matrix_id']);

$game_data = get_game_data($matrix_id);

if (!$game_data) {
    json_response(false, 'Invalid game');
}

global $conn;

$groups = [
    'threats' => [],
    'opportunities' => []
];

/* =========================
   LOAD THREATS + STRATEGIES
========================= */

$query = "
SELECT * 
FROM mg6_riskhop_threats 
WHERE matrix_id = '$matrix_id'
ORDER BY cell_from DESC
";

$result = mysqli_query($conn,$query);

while ($threat = mysqli_fetch_assoc($result)) {

    $strategies = [];

    $squery = "
    SELECT s.*
    FROM mg6_riskhop_strategies s
    INNER JOIN mg6_threat_strategy_mapping tsm
        ON s.id = tsm.strategy_id
    WHERE tsm.threat_id = '{$threat['id']}'
    AND tsm.matrix_id = '$matrix_id'
    ORDER BY s.strategy_name
    ";

    $sresult = mysqli_query($conn,$squery);

    while ($row = mysqli_fetch_assoc($sresult)) {
        $strategies[] = $row;
    }

    $groups['threats'][] = [
        'id' => $threat['id'],
        'name' => $threat['threat_name'],
        'cell_from' => $threat['cell_from'],
        'cell_to' => $threat['cell_to'],
        'strategies' => $strategies
    ];
}

/* =========================
   LOAD OPPORTUNITIES + STRATEGIES
========================= */

$query = "
SELECT * 
FROM mg6_riskhop_opportunities 
WHERE matrix_id = '$matrix_id'
ORDER BY cell_from ASC
";

$result = mysqli_query($conn,$query);

while ($opportunity = mysqli_fetch_assoc($result)) {

    $strategies = [];

    $squery = "
    SELECT s.*
    FROM mg6_riskhop_strategies s
    INNER JOIN mg6_opportunity_strategy_mapping osm
        ON s.id = osm.strategy_id
    WHERE osm.opportunity_id = '{$opportunity['id']}'
    AND osm.matrix_id = '$matrix_id'
    ORDER BY s.strategy_name
    ";

    $sresult = mysqli_query($conn,$squery);

    while ($row = mysqli_fetch_assoc($sresult)) {
        $strategies[] = $row;
    }

    $groups['opportunities'][] = [
        'id' => $opportunity['id'],
        'name' => $opportunity['opportunity_name'],
        'cell_from' => $opportunity['cell_from'],
        'cell_to' => $opportunity['cell_to'],
        'strategies' => $strategies
    ];
}

/* =========================
   BUILD FLAT STRATEGY LIST
========================= */

$all_strategies = [];
$added_ids = [];

foreach ($groups['threats'] as $group) {
    foreach ($group['strategies'] as $s) {
        if (!in_array($s['id'], $added_ids)) {
            $all_strategies[] = $s;
            $added_ids[] = $s['id'];
        }
    }
}

foreach ($groups['opportunities'] as $group) {
    foreach ($group['strategies'] as $s) {
        if (!in_array($s['id'], $added_ids)) {
            $all_strategies[] = $s;
            $added_ids[] = $s['id'];
        }
    }
}

usort($all_strategies, function($a,$b){
    return strcmp($a['strategy_name'],$b['strategy_name']);
});

/* =========================
   RESPONSE
========================= */

json_response(true, 'Strategies retrieved', [
    'strategies' => $all_strategies,
    'groups' => $groups,
    'total_count' => count($all_strategies)
]);
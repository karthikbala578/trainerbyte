<?php
require_once '../../config.php';
require_once '../../functions.php';

if (!is_admin_logged_in()) {
    json_response(false, 'Unauthorized access');
}

$matrix_id = intval($_GET['matrix_id']);

// Get game data
$query = "SELECT total_cells FROM mg6_riskhop_matrix WHERE id = '$matrix_id'";
$result = mysqli_query($conn, $query);
$game = mysqli_fetch_assoc($result);

// Get threats
$query = "SELECT * FROM mg6_riskhop_threats WHERE matrix_id = '$matrix_id'";
$result = mysqli_query($conn, $query);
$threats = [];
while ($row = mysqli_fetch_assoc($result)) {
    $threats[] = $row;
}

// Get opportunities
$query = "SELECT * FROM mg6_riskhop_opportunities WHERE matrix_id = '$matrix_id'";
$result = mysqli_query($conn, $query);
$opportunities = [];
while ($row = mysqli_fetch_assoc($result)) {
    $opportunities[] = $row;
}

// Get bonuses
$query = "SELECT * FROM mg6_riskhop_bonus WHERE matrix_id = '$matrix_id'";
$result = mysqli_query($conn, $query);
$bonuses = [];
while ($row = mysqli_fetch_assoc($result)) {
    $bonuses[] = $row;
}

// Get audits
$query = "SELECT * FROM mg6_riskhop_audit WHERE matrix_id = '$matrix_id'";
$result = mysqli_query($conn, $query);
$audits = [];
while ($row = mysqli_fetch_assoc($result)) {
    $audits[] = $row;
}

// Get wildcards
$query = "SELECT * FROM mg6_riskhop_wildcard_cells WHERE matrix_id = '$matrix_id'";
$result = mysqli_query($conn, $query);
$wildcards = [];
while ($row = mysqli_fetch_assoc($result)) {
    $wildcards[] = $row;
}

json_response(true, 'Board data loaded', [
    'total_cells' => $game['total_cells'],
    'threats' => $threats,
    'opportunities' => $opportunities,
    'bonuses' => $bonuses,
    'audits' => $audits,
    'wildcards' => $wildcards
]);
?>
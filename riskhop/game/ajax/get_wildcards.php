<?php
/**
 * Get Wildcards
 * Return available wildcard options for selection
 */

require_once '../../config.php';
require_once '../../functions.php';
require_once '../game_engine.php';

header('Content-Type: application/json');

// Check if request is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(false, 'Invalid request method');
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

// Validate input
if (!isset($input['matrix_id']) || empty($input['matrix_id'])) {
    json_response(false, 'Matrix ID is required');
}

$matrix_id = clean_input($input['matrix_id']);
$session = get_current_session();

// Verify matrix exists
$game_data = get_game_data($matrix_id);

if (!$game_data) {
    json_response(false, 'Invalid game');
}

// Get all wildcards for this game in a FIXED order
global $conn;
$query = "SELECT w.*, (CASE WHEN swo.id IS NOT NULL THEN 1 ELSE 0 END) as is_opened 
          FROM mg6_riskhop_wildcards w
          LEFT JOIN mg6_session_wildcards_opened swo ON w.id = swo.wildcard_id " . 
          ($session ? "AND swo.session_id = '{$session['id']}'" : "AND 1=0") . "
          WHERE w.matrix_id = '$matrix_id' 
          ORDER BY w.id ASC";  // Fixed order for grid consistency
$result = mysqli_query($conn, $query);

$wildcards = [];
while ($row = mysqli_fetch_assoc($result)) {
    $wildcards[] = [
        'id' => $row['id'],
        'wildcard_name' => $row['wildcard_name'],
        'wildcard_description' => $row['wildcard_description'],
        'wildcard_image' => $row['wildcard_image'],
        'is_opened' => (bool)$row['is_opened'],
        // Don't expose effects until selected if not opened
        'hidden' => $row['is_opened'] ? false : true
    ];
}

if (empty($wildcards)) {
    json_response(false, 'No wildcards available for this game');
}

// Return response
json_response(true, 'Wildcards retrieved', [
    'wildcards' => $wildcards,
    'total_count' => count($wildcards)
]);
?>
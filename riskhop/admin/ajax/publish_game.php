<?php
require_once '../../config.php';
require_once '../../functions.php';

if (!is_admin_logged_in()) {
    json_response(false, 'Unauthorized access');
}

$game_id = intval($_POST['game_id']);
$action = clean_input($_POST['action']); // 'publish', 'draft', 'discard'

if ($action === 'publish') {
    $query = "UPDATE mg6_riskhop_matrix SET status = 'published', published_date = NOW() 
              WHERE id = '$game_id'";
} elseif ($action === 'draft') {
    $query = "UPDATE mg6_riskhop_matrix SET status = 'draft' WHERE id = '$game_id'";
} elseif ($action === 'discard') {
    // Delete game and all related data (cascading delete handles related records)
    $query = "DELETE FROM mg6_riskhop_matrix WHERE id = '$game_id'";
} else {
    json_response(false, 'Invalid action');
}

if (mysqli_query($conn, $query)) {
    if ($action === 'discard') {
        json_response(true, 'Game discarded successfully', ['redirect' => true]);
    } else {
        json_response(true, 'Game ' . $action . ' successfully');
    }
} else {
    json_response(false, 'Failed to update game');
}
?>
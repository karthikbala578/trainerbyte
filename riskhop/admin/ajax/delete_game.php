<?php
require_once '../../config.php';
require_once '../../functions.php';

if (!is_admin_logged_in()) {
    json_response(false, 'Unauthorized access');
}

$game_id = intval($_POST['game_id']);

$query = "DELETE FROM mg6_riskhop_matrix WHERE id = '$game_id'";

if (mysqli_query($conn, $query)) {
    json_response(true, 'Game deleted successfully');
} else {
    json_response(false, 'Failed to delete game');
}
?>
<?php
require_once '../../config.php';
require_once '../../functions.php';

if (!is_admin_logged_in()) {
    json_response(false, 'Unauthorized access');
}

$bonus_id = intval($_POST['bonus_id']);

$query = "DELETE FROM mg6_riskhop_bonus WHERE id = '$bonus_id'";
if (mysqli_query($conn, $query)) {
    json_response(true, 'Bonus cell deleted successfully');
} else {
    json_response(false, 'Failed to delete bonus cell');
}
?>
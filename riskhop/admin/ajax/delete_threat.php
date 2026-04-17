<?php
require_once '../../config.php';
require_once '../../functions.php';

if (!is_admin_logged_in()) {
    json_response(false, 'Unauthorized access');
}

$threat_id = intval($_POST['threat_id']);

$query = "DELETE FROM mg6_riskhop_threats WHERE id = '$threat_id'";
if (mysqli_query($conn, $query)) {
    json_response(true, 'Threat deleted successfully');
} else {
    json_response(false, 'Failed to delete threat');
}
?>
<?php
require_once '../../config.php';
require_once '../../functions.php';

if (!is_admin_logged_in()) {
    json_response(false, 'Unauthorized access');
}

$opportunity_id = intval($_POST['opportunity_id']);

$query = "DELETE FROM mg6_riskhop_opportunities WHERE id = '$opportunity_id'";
if (mysqli_query($conn, $query)) {
    json_response(true, 'Opportunity deleted successfully');
} else {
    json_response(false, 'Failed to delete opportunity');
}
?>
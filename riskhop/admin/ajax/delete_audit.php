<?php
require_once '../../config.php';
require_once '../../functions.php';

if (!is_admin_logged_in()) {
    json_response(false, 'Unauthorized access');
}

$audit_id = intval($_POST['audit_id']);

$query = "DELETE FROM mg6_riskhop_audit WHERE id = '$audit_id'";
if (mysqli_query($conn, $query)) {
    json_response(true, 'Audit cell deleted successfully');
} else {
    json_response(false, 'Failed to delete audit cell');
}
?>
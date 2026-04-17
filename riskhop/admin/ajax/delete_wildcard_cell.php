<?php
require_once '../../config.php';
require_once '../../functions.php';

if (!is_admin_logged_in()) {
    json_response(false, 'Unauthorized access');
}

$wildcard_cell_id = intval($_POST['wildcard_cell_id']);

$query = "DELETE FROM mg6_riskhop_wildcard_cells WHERE id = '$wildcard_cell_id'";
if (mysqli_query($conn, $query)) {
    json_response(true, 'Wildcard cell deleted successfully');
} else {
    json_response(false, 'Failed to delete wildcard cell');
}
?>
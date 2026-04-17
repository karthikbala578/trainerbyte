<?php
require_once '../../config.php';
require_once '../../functions.php';

if (!is_admin_logged_in()) {
    json_response(false, 'Unauthorized access');
}

$wildcard_id = intval($_POST['wildcard_id']);

$query = "SELECT wildcard_image FROM mg6_riskhop_wildcards WHERE id = '$wildcard_id'";
$result = mysqli_query($conn, $query);
if ($row = mysqli_fetch_assoc($result)) {
    if (!empty($row['wildcard_image'])) {
        $image_path = UPLOAD_DIR . $row['wildcard_image'];
        if (file_exists($image_path)) {
            unlink($image_path);
        }
    }
}

$query = "DELETE FROM mg6_riskhop_wildcards WHERE id = '$wildcard_id'";
if (mysqli_query($conn, $query)) {
    json_response(true, 'Wildcard option deleted successfully');
} else {
    json_response(false, 'Failed to delete wildcard option');
}
?>
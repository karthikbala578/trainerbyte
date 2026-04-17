<?php
require_once '../../config.php';
require_once '../../functions.php';

if (!is_admin_logged_in()) {
    json_response(false, 'Unauthorized access');
}

$matrix_id = intval($_POST['matrix_id']);
$wildcard_name = clean_input($_POST['wildcard_name']);
$wildcard_description = clean_input($_POST['wildcard_description']);
$risk_capital_effect = intval($_POST['risk_capital_effect']);
$dice_effect = intval($_POST['dice_effect']);
$cell_effect = intval($_POST['cell_effect']);

// Handle image upload
$wildcard_image = '';
if (isset($_FILES['wildcard_image']) && $_FILES['wildcard_image']['error'] === UPLOAD_ERR_OK) {
    $upload_result = upload_wildcard_image($_FILES['wildcard_image']);
    if ($upload_result['success']) {
        $wildcard_image = $upload_result['filename'];
    } else {
        json_response(false, $upload_result['message']);
    }
}

// Insert
$query = "INSERT INTO mg6_riskhop_wildcards 
          (matrix_id, wildcard_name, wildcard_description, wildcard_image, 
           risk_capital_effect, dice_effect, cell_effect) 
          VALUES 
          ('$matrix_id', '$wildcard_name', '$wildcard_description', '$wildcard_image',
           '$risk_capital_effect', '$dice_effect', '$cell_effect')";

if (mysqli_query($conn, $query)) {
    json_response(true, 'Wildcard option added successfully');
} else {
    json_response(false, 'Failed to add wildcard option');
}
?>
<?php
require_once '../../config.php';
require_once '../../functions.php';

if (!is_admin_logged_in()) {
    json_response(false, 'Unauthorized access');
}

$wildcard_id = intval($_POST['wildcard_id']);
$wildcard_name = clean_input($_POST['wildcard_name']);
$wildcard_description = clean_input($_POST['wildcard_description']);
$risk_capital_effect = intval($_POST['risk_capital_effect']);
$dice_effect = intval($_POST['dice_effect']);
$cell_effect = intval($_POST['cell_effect']);

// Get current wildcard data
$current_query = "SELECT wildcard_image FROM mg6_riskhop_wildcards WHERE id = '$wildcard_id'";
$current_result = mysqli_query($conn, $current_query);
$current_data = mysqli_fetch_assoc($current_result);
$wildcard_image = $current_data['wildcard_image'];

// Handle image upload if new image is provided
if (isset($_FILES['wildcard_image']) && $_FILES['wildcard_image']['error'] == 0) {
    $allowed = ['jpg', 'jpeg', 'png', 'gif'];
    $filename = $_FILES['wildcard_image']['name'];
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    
    if (in_array($ext, $allowed) && $_FILES['wildcard_image']['size'] <= 2097152) {
        $new_filename = 'wildcard_' . time() . '_' . uniqid() . '.' . $ext;
        $upload_path = '../../uploads/wildcards/' . $new_filename;
        
        // Create directory if not exists
        if (!file_exists('../../uploads/wildcards/')) {
            mkdir('../../uploads/wildcards/', 0777, true);
        }
        
        if (move_uploaded_file($_FILES['wildcard_image']['tmp_name'], $upload_path)) {
            // Delete old image if exists
            if ($wildcard_image && file_exists('../../' . $wildcard_image)) {
                unlink('../../' . $wildcard_image);
            }
            $wildcard_image = 'uploads/wildcards/' . $new_filename;
        }
    }
}

// Update
$query = "UPDATE mg6_riskhop_wildcards SET 
          wildcard_name = '$wildcard_name',
          wildcard_description = '$wildcard_description',
          risk_capital_effect = '$risk_capital_effect',
          dice_effect = '$dice_effect',
          cell_effect = '$cell_effect',
          wildcard_image = '$wildcard_image'
          WHERE id = '$wildcard_id'";

if (mysqli_query($conn, $query)) {
    json_response(true, 'Wildcard updated successfully');
} else {
    json_response(false, 'Failed to update wildcard');
}
?>
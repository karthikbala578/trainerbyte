<?php
require_once '../../config.php';
require_once '../../functions.php';

if (!is_admin_logged_in()) {
    json_response(false, 'Unauthorized access');
}

$bonus_id = intval($_POST['bonus_id']);
$cell_number = intval($_POST['cell_number']);
$bonus_amount = intval($_POST['bonus_amount']);
$matrix_id = intval($_POST['matrix_id']);

$current_query = "SELECT cell_number FROM mg6_riskhop_bonus WHERE id = '$bonus_id'";
$current_result = mysqli_query($conn, $current_query);
$current_data = mysqli_fetch_assoc($current_result);

if ($current_data && $current_data['cell_number'] != $cell_number) {
    $check = is_cell_used($matrix_id, $cell_number);
    if ($check['used']) {
        json_response(false, 'Cell ' . $cell_number . ' is already used by a ' . $check['type']);
    }
}

// Update
$query = "UPDATE mg6_riskhop_bonus SET 
          cell_number = '$cell_number',
          bonus_amount = '$bonus_amount'
          WHERE id = '$bonus_id'";

if (mysqli_query($conn, $query)) {
    json_response(true, 'Bonus updated successfully');
} else {
    json_response(false, 'Failed to update bonus');
}
?>
<?php
require_once '../../config.php';
require_once '../../functions.php';

if (!is_admin_logged_in()) {
    json_response(false, 'Unauthorized access');
}

$matrix_id = intval($_POST['matrix_id']);
$cell_number = intval($_POST['cell_number']);

// Check if cell is already used
$check = is_cell_used($matrix_id, $cell_number);
if ($check['used']) {
    json_response(false, 'Cell ' . $cell_number . ' is already used by a ' . $check['type']);
}

if(!$id || !$cell){
    echo json_encode(['success'=>false,'message'=>'Invalid data']);
    exit;
}

$query = "UPDATE mg6_riskhop_audit 
          SET cell_number = '$cell' 
          WHERE id = '$id'";

if(mysqli_query($conn, $query)){
    echo json_encode(['success'=>true,'message'=>'Audit updated']);
} else {
    echo json_encode(['success'=>false,'message'=>'Update failed']);
}
?>
<?php



function clean_input($data) {
    global $conn;
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return mysqli_real_escape_string($conn, $data);
}


  //Check if admin is logged in
 
function is_admin_logged_in() {
    return isset($_SESSION['admin_id']) && !empty($_SESSION['admin_id']);
}


 //Redirect function
 
function redirect($url) {
    header("Location: " . $url);
    exit();
}


 //Get matrix total cells based on type
function get_total_cells($matrix_type) {
    $types = [
        '6x6' => 36,
        '8x8' => 64,
        '10x10' => 100,
        '12x12' => 144
    ];
    return $types[$matrix_type] ?? 64;
}


 //Check if cell is already used (by threat, opportunity, bonus, audit, or wildcard)
function is_cell_used($matrix_id, $cell_number, $exclude_type = null) {
    global $conn;

    $cell_number = intval($cell_number);
    $matrix_id = intval($matrix_id);

    $query = "
        SELECT 'Threat' as type FROM mg6_riskhop_threats 
        WHERE matrix_id = $matrix_id 
        AND ($cell_number IN (cell_from, cell_to))

        UNION

        SELECT 'Opportunity' FROM mg6_riskhop_opportunities 
        WHERE matrix_id = $matrix_id 
        AND ($cell_number IN (cell_from, cell_to))

        UNION

        SELECT 'Bonus' FROM mg6_riskhop_bonus 
        WHERE matrix_id = $matrix_id 
        AND cell_number = $cell_number

        UNION

        SELECT 'Audit' FROM mg6_riskhop_audit 
        WHERE matrix_id = $matrix_id 
        AND cell_number = $cell_number

        UNION

        SELECT 'Wildcard' FROM mg6_riskhop_wildcard_cells 
        WHERE matrix_id = $matrix_id 
        AND cell_number = $cell_number

        LIMIT 1
    ";

    $result = mysqli_query($conn, $query);

    if (!$result) {
        return ['used' => false, 'type' => null, 'detail' => null];
    }

    if (mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        return [
            'used' => true,
            'type' => $row['type'],
            'detail' => $row['type'] . ' position'
        ];
    }

    return ['used' => false, 'type' => null, 'detail' => null];
}

 //Validate threat cell positions

function validate_threat_cells($matrix_id, $cell_from, $cell_to, $total_cells) {
    if ($cell_from <= $cell_to) {
        return ['valid' => false, 'message' => 'Threat must go down. FROM cell must be greater than TO cell.'];
    }
    
    if ($cell_from < ($cell_to + 6)) {
        return ['valid' => false, 'message' => 'Threat must have minimum gap of 6 cells between FROM and TO.'];
    }
    
    if ($cell_from > $total_cells || $cell_to < 1) {
        return ['valid' => false, 'message' => 'Cell numbers must be within board range (1 to ' . $total_cells . ').'];
    }
    
    $from_check = is_cell_used($matrix_id, $cell_from);
    if ($from_check['used']) {
        return ['valid' => false, 'message' => 'Cell ' . $cell_from . ' is already used by a ' . $from_check['type'] . '. Choose different cell.'];
    }
    
    $to_check = is_cell_used($matrix_id, $cell_to);
    if ($to_check['used']) {
        return ['valid' => false, 'message' => 'Cell ' . $cell_to . ' is already used by a ' . $to_check['type'] . '. Choose different cell.'];
    }
    
    return ['valid' => true, 'message' => 'Valid'];
}


  //Validate opportunity cell positions
 
function validate_opportunity_cells($matrix_id, $cell_from, $cell_to, $total_cells) {
    if ($cell_to <= $cell_from) {
        return ['valid' => false, 'message' => 'Opportunity must go up. TO cell must be greater than FROM cell.'];
    }
    
    if ($cell_to < ($cell_from + 6)) {
        return ['valid' => false, 'message' => 'Opportunity must have minimum gap of 6 cells between FROM and TO.'];
    }
    
    if ($cell_to > $total_cells || $cell_from < 1) {
        return ['valid' => false, 'message' => 'Cell numbers must be within board range (1 to ' . $total_cells . ').'];
    }
    
    $from_check = is_cell_used($matrix_id, $cell_from);
    if ($from_check['used']) {
        return ['valid' => false, 'message' => 'Cell ' . $cell_from . ' is already used by a ' . $from_check['type'] . '. Choose different cell.'];
    }
    
    $to_check = is_cell_used($matrix_id, $cell_to);
    if ($to_check['used']) {
        return ['valid' => false, 'message' => 'Cell ' . $cell_to . ' is already used by a ' . $to_check['type'] . '. Choose different cell.'];
    }
    
    return ['valid' => true, 'message' => 'Valid'];
}


 //Get game configuration summary
function get_game_summary($matrix_id) {
    global $conn;
    
    // Get basic info
    $query = "SELECT * FROM mg6_riskhop_matrix WHERE id = '$matrix_id'";
    $result = mysqli_query($conn, $query);
    $game = mysqli_fetch_assoc($result);
    
    // Count threats
    $query = "SELECT COUNT(*) as count FROM mg6_riskhop_threats WHERE matrix_id = '$matrix_id'";
    $result = mysqli_query($conn, $query);
    $threats = mysqli_fetch_assoc($result)['count'];
    
    // Count opportunities
    $query = "SELECT COUNT(*) as count FROM mg6_riskhop_opportunities WHERE matrix_id = '$matrix_id'";
    $result = mysqli_query($conn, $query);
    $opportunities = mysqli_fetch_assoc($result)['count'];
    
    // Count bonus cells
    $query = "SELECT COUNT(*) as count FROM mg6_riskhop_bonus WHERE matrix_id = '$matrix_id'";
    $result = mysqli_query($conn, $query);
    $bonus = mysqli_fetch_assoc($result)['count'];
    
    // Count audit cells
    $query = "SELECT COUNT(*) as count FROM mg6_riskhop_audit WHERE matrix_id = '$matrix_id'";
    $result = mysqli_query($conn, $query);
    $audit = mysqli_fetch_assoc($result)['count'];
    
    // Count wildcard cells
    $query = "SELECT COUNT(*) as count FROM mg6_riskhop_wildcard_cells WHERE matrix_id = '$matrix_id'";
    $result = mysqli_query($conn, $query);
    $wildcards = mysqli_fetch_assoc($result)['count'];
    
    // Count wildcard options
    $query = "SELECT COUNT(*) as count FROM mg6_riskhop_wildcards WHERE matrix_id = '$matrix_id'";
    $result = mysqli_query($conn, $query);
    $wildcard_options = mysqli_fetch_assoc($result)['count'];
    
    return [
        'game' => $game,
        'threats' => $threats,
        'opportunities' => $opportunities,
        'bonus' => $bonus,
        'audit' => $audit,
        'wildcards' => $wildcards,
        'wildcard_options' => $wildcard_options
    ];
}


  //Upload wildcard image
function upload_wildcard_image($file) {
    $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
    $max_size = 2 * 1024 * 1024; // 2MB
    
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'File upload error.'];
    }
    
    if (!in_array($file['type'], $allowed_types)) {
        return ['success' => false, 'message' => 'Only JPG, PNG, GIF images allowed.'];
    }
    
    if ($file['size'] > $max_size) {
        return ['success' => false, 'message' => 'File size must be less than 2MB.'];
    }
    
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'wildcard_' . time() . '_' . rand(1000, 9999) . '.' . $extension;
    $filepath = UPLOAD_DIR . $filename;
    
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        return ['success' => true, 'filename' => $filename];
    }
    
    return ['success' => false, 'message' => 'Failed to upload file.'];
}

// Format date
function format_date($date) {
    return date('d M Y, h:i A', strtotime($date));
}

// JSON response
function json_response($success, $message, $data = null) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data
    ]);
    exit();
}

function getInstruction($instructions, $key, $type = 'content') {
    if (!isset($instructions[$key])) return '';

    if ($type === 'title') {
        return $instructions[$key]['section_title'] ?? '';
    }

    return $instructions[$key]['content'] ?? '';
}
?>
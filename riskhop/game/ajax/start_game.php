<?php
/**
 * Start Game Session - FIXED VERSION
 * Handles game initialization with proper error handling
 */

// Turn off display errors but log them
@ini_set('display_errors', 0);
@error_reporting(E_ALL);
@ini_set('log_errors', 1);

// Start output buffering
ob_start();

try {
    // Include required files
    require_once '../../config.php';
    require_once '../../functions.php';
    require_once '../game_engine.php';
    
    // Clean any output so far
    ob_end_clean();
    ob_start();
    
    // Set JSON header
    header('Content-Type: application/json; charset=utf-8');
    
    // Check request method
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }
    
    // Get and decode input
    $rawInput = file_get_contents('php://input');
    $input = json_decode($rawInput, true);
    
    // Validate JSON
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid JSON input: ' . json_last_error_msg());
    }
    
    // Validate matrix_id
    if (!isset($input['matrix_id']) || empty($input['matrix_id'])) {
        throw new Exception('Game ID is required');
    }
    
    $matrix_id = (int)clean_input($input['matrix_id']);
    
    if ($matrix_id <= 0) {
        throw new Exception('Invalid Game ID');
    }
    
    // Verify game exists and is published
    $game_data = get_game_data($matrix_id);
    
    if (!$game_data || empty($game_data)) {
        throw new Exception('Game not found or not published. Please ensure the game exists and is published in the admin panel.');
    }
    
    // Check for existing active session (FIXED: use correct session variable name)
    $existing_session = get_current_session();
    
    if ($existing_session && 
        $existing_session['game_status'] == 'playing' && 
        $existing_session['matrix_id'] == $matrix_id) {
        
        // Return existing session
        $response = [
            'success' => true,
            'message' => 'Continuing existing game',
            'data' => [
                'session_id' => $existing_session['id'],
                'matrix_id' => $existing_session['matrix_id'],
                'current_cell' => (int)$existing_session['current_cell'],
                'dice_remaining' => (int)$existing_session['dice_remaining'],
                'capital_remaining' => (int)$existing_session['capital_remaining'],
                'existing' => true
            ]
        ];
        
        ob_end_clean();
        echo json_encode($response);
        exit;
    }
    
    // Start new game session
    $result = start_new_game($matrix_id);
    
    if (!$result['success']) {
        throw new Exception($result['message'] ?? 'Failed to start game');
    }
    
    // Get session data (FIXED: use correct function)
    $session = get_current_session();
    
    if (!$session) {
        throw new Exception('Failed to retrieve session after creation');
    }
    
    // Success response
    $response = [
        'success' => true,
        'message' => 'Game started successfully',
        'data' => [
            'session_id' => $session['id'],
            'matrix_id' => $session['matrix_id'],
            'current_cell' => (int)$session['current_cell'],
            'dice_remaining' => (int)$session['dice_remaining'],
            'capital_remaining' => (int)$session['capital_remaining'],
            'existing' => false
        ]
    ];
    
    // Clear buffer and output JSON
    ob_end_clean();
    echo json_encode($response);
    exit;
    
} catch (Exception $e) {
    // Error response
    ob_end_clean();
    
    $response = [
        'success' => false,
        'message' => $e->getMessage(),
        'error_type' => get_class($e),
        'debug_info' => [
            'file' => basename($e->getFile()),
            'line' => $e->getLine()
        ]
    ];
    
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($response);
    exit;
}

// Fallback (should never reach here)
ob_end_clean();
http_response_code(500);
header('Content-Type: application/json; charset=utf-8');
echo json_encode(['success' => false, 'message' => 'Unknown error occurred']);
exit;
?>
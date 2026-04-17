<?php
session_start();
require "../include/coreDataconnect.php";

header('Content-Type: application/json');

if (!isset($_SESSION['team_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cg_id = isset($_POST['cg_id']) ? intval($_POST['cg_id']) : 0;
    $status = isset($_POST['status']) ? intval($_POST['status']) : 0;

    if ($cg_id > 0) {
        // Update the card group status from library using action btn to db
        $stmt = $conn->prepare("UPDATE card_group SET cg_status = ? WHERE cg_id = ?");
        $stmt->bind_param("ii", $status, $cg_id);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Database update failed']);
        }
        $stmt->close();
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid ID']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}

<?php
require_once "include/session_check.php";
require "include/coreDataconnect.php";

header('Content-Type: application/json');

if (!isset($_SESSION['team_id'])) {
    echo json_encode(['success' => false, 'msg' => 'Unauthorized']);
    exit;
}

$user_id  = isset($_POST['user_id'])  ? (int)$_POST['user_id']  : 0;
$event_id = isset($_POST['event_id']) ? (int)$_POST['event_id'] : 0;
$value    = isset($_POST['value'])    ? (int)$_POST['value']    : 0; // 1 = active, 0 = inactive

if ($user_id <= 0 || $event_id <= 0) {
    echo json_encode(['success' => false, 'msg' => 'Invalid parameters']);
    exit;
}

// Verify this event belongs to the session's team
$check = $conn->prepare("SELECT event_id FROM tb_events WHERE event_id = ? AND event_team_pkid = ?");
$check->bind_param("ii", $event_id, $_SESSION['team_id']);
$check->execute();
if ($check->get_result()->num_rows === 0) {
    echo json_encode(['success' => false, 'msg' => 'Event not found or access denied']);
    exit;
}

// Verify the user belongs to this event
$checkUser = $conn->prepare("SELECT id FROM tb_event_user WHERE id = ? AND event_code = ?");
$checkUser->bind_param("ii", $user_id, $event_id);
$checkUser->execute();
if ($checkUser->get_result()->num_rows === 0) {
    echo json_encode(['success' => false, 'msg' => 'User not found in this event']);
    exit;
}

$value = ($value === 1) ? 1 : 0;

$stmt = $conn->prepare("UPDATE tb_event_user SET user_is_active = ? WHERE id = ? AND event_code = ?");
$stmt->bind_param("iii", $value, $user_id, $event_id);

if ($stmt->execute()) {
    echo json_encode([
        'success'    => true,
        'is_active'  => $value,
        'msg'        => $value ? 'User activated successfully.' : 'User deactivated successfully.'
    ]);
} else {
    echo json_encode(['success' => false, 'msg' => 'Database error: ' . $conn->error]);
}

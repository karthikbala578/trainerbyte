<?php
require_once "include/session_check.php";
require_once "include/coreDataconnect.php";

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['team_id'])) {
    echo json_encode(['success' => false, 'msg' => 'Invalid request']);
    exit;
}

$action = $_POST['action'] ?? '';
$team_id = (int)$_SESSION['team_id'];
$event_id = (int)($_POST['event_id'] ?? 0);

// Helper function to check if event is accessible for modification
function checkEventStatus($conn, $eventId, $teamId) {
    $stmt = $conn->prepare("SELECT event_playstatus FROM tb_events WHERE event_id = ? AND event_team_pkid = ?");
    $stmt->bind_param("ii", $eventId, $teamId);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    if (!$res) return ['allowed' => false, 'msg' => 'Event not found'];
    if ((int)$res['event_playstatus'] === 3) {
        return ['allowed' => false, 'msg' => 'Cannot modify settings while the event is LIVE. Please close it first.'];
    }
    return ['allowed' => true, 'msg' => ''];
}

if ($action === 'update_mode') {
    $type = $_POST['type'] ?? ''; // 'progression' or 'release'
    $val = (int)($_POST['value'] ?? 1);
    
    // Validate event status
    $statusCheck = checkEventStatus($conn, $event_id, $team_id);
    if (!$statusCheck['allowed']) {
        echo json_encode(['success' => false, 'msg' => $statusCheck['msg']]);
        exit;
    }

    if ($type === 'progression') {
        $stmt = $conn->prepare("UPDATE tb_events SET event_progression = ? WHERE event_id = ? AND event_team_pkid = ?");
        $stmt->bind_param("iii", $val, $event_id, $team_id);
    } else if ($type === 'release') {
        $stmt = $conn->prepare("UPDATE tb_events SET event_release = ? WHERE event_id = ? AND event_team_pkid = ?");
        $stmt->bind_param("iii", $val, $event_id, $team_id);
    } else {
        echo json_encode(['success' => false, 'msg' => 'Invalid update type']);
        exit;
    }

    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'msg' => 'Database error']);
    }
    $stmt->close();
    exit;
}

if ($action === 'update_lock') {
    $mod_id = (int)($_POST['mod_id'] ?? 0);
    $val = (int)($_POST['value'] ?? 0);

    // To properly validate, we first need to get the event_id from the module.
    $stmt = $conn->prepare("SELECT mod_event_pkid FROM tb_events_module WHERE mod_id = ?");
    $stmt->bind_param("i", $mod_id);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($res) {
        $modEventId = (int)$res['mod_event_pkid'];
        
        // Removed checkEventStatus for update_lock so Admin can lock/unlock in real-time during Live

        $update = $conn->prepare("UPDATE tb_events_module SET mod_is_unlocked = ? WHERE mod_id = ?");
        $update->bind_param("ii", $val, $mod_id);
        if ($update->execute()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'msg' => 'Failed to update lock']);
        }
        $update->close();
    } else {
        echo json_encode(['success' => false, 'msg' => 'Module not found']);
    }
    exit;
}

echo json_encode(['success' => false, 'msg' => 'Unknown action']);

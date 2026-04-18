<?php
session_start();
require "include/coreDataconnect.php";

header('Content-Type: application/json');

if (!isset($_SESSION['team_id'])) {
    echo json_encode(['success' => false, 'msg' => 'Not authenticated']);
    exit;
}

$action   = $_POST['action']   ?? '';
$event_id = intval($_POST['event_id'] ?? 0);
$team_id  = $_SESSION['team_id'];

if (!$event_id) {
    echo json_encode(['success' => false, 'msg' => 'Invalid event']);
    exit;
}

/* -- Verify ownership -- */
$chk = $conn->prepare("SELECT event_id, event_playstatus, event_validity, event_start_date, event_url_code FROM tb_events WHERE event_id = ? AND event_team_pkid = ?");
$chk->bind_param("ii", $event_id, $team_id);
$chk->execute();
$event = $chk->get_result()->fetch_assoc();

if (!$event) {
    echo json_encode(['success' => false, 'msg' => 'Event not found']);
    exit;
}

$now         = new DateTime();
$validityDays = intval($event['event_validity']);
$startDate   = new DateTime($event['event_start_date']);
$endDate     = clone $startDate;
$endDate->modify("+{$validityDays} days");
$isExpired   = $now > $endDate; // past validity

if ($isExpired && in_array($action, ['set_live', 'set_closed'])) {
    echo json_encode(['success' => false, 'msg' => 'Cannot modify status: event validity has expired and is auto-closed.', 'status' => 4]);
    exit;
}

switch ($action) {

    /* ---- SAVE (just returns success - future: save inline edits) ---- */
    case 'save':
        echo json_encode(['success' => true, 'msg' => 'Updates to the event are saved.']);
        break;

    /* ---- PUBLISH: set status to 2 (generates URL if not already set) ---- */
    case 'publish':
        if ((int)$event['event_playstatus'] >= 2) {
            echo json_encode(['success' => true, 'msg' => 'Already published', 'status' => $event['event_playstatus'], 'url_code' => $event['event_url_code']]);
            break;
        }
        // Generate URL code if empty
        $url_code = $event['event_url_code'];
        if (!$url_code) {
            $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
            $url_code = '';
            for ($i = 0; $i < 6; $i++) $url_code .= $chars[random_int(0, 35)];
            $upd = $conn->prepare("UPDATE tb_events SET event_playstatus = 2, event_url_code = ? WHERE event_id = ? AND event_team_pkid = ?");
            $upd->bind_param("sii", $url_code, $event_id, $team_id);
        } else {
            $upd = $conn->prepare("UPDATE tb_events SET event_playstatus = 2 WHERE event_id = ? AND event_team_pkid = ?");
            $upd->bind_param("ii", $event_id, $team_id);
        }
        $upd->execute();
        echo json_encode(['success' => true, 'msg' => 'Event published.', 'status' => 2, 'url_code' => $url_code]);
        break;

    /* ---- SET LIVE: status 3 (ON) ↔ 4 (OFF→Closed) ---- */
    case 'set_live':
        $val = intval($_POST['value'] ?? 0);
        $cur = (int)$event['event_playstatus'];
        if ($val == 1) {
            // LIVE ON — requires published (>= 2)
            if ($cur < 2) {
                echo json_encode(['success' => false, 'msg' => 'Publish first before going Live.']);
                break;
            }
            // Switch from any published/closed state → Live
            $upd = $conn->prepare("UPDATE tb_events SET event_playstatus = 3 WHERE event_id = ? AND event_team_pkid = ?");
            $upd->bind_param("ii", $event_id, $team_id);
            $upd->execute();
            echo json_encode(['success' => true, 'msg' => 'Event is now Live.', 'status' => 3]);
        } else {
            // LIVE OFF → automatically Closed (4)
            if ($cur < 2) {
                echo json_encode(['success' => false, 'msg' => 'Event is not published.']);
                break;
            }
            $upd = $conn->prepare("UPDATE tb_events SET event_playstatus = 4 WHERE event_id = ? AND event_team_pkid = ?");
            $upd->bind_param("ii", $event_id, $team_id);
            $upd->execute();
            echo json_encode(['success' => true, 'msg' => 'Event set to Closed.', 'status' => 4]);
        }
        break;

    /* ---- SET CLOSED: status 4 (ON) ↔ 3 (OFF→Live) ---- */
    case 'set_closed':
        $val = intval($_POST['value'] ?? 0);
        $cur = (int)$event['event_playstatus'];
        if ($val == 1) {
            // CLOSED ON
            if ($cur < 2) {
                echo json_encode(['success' => false, 'msg' => 'Publish first.']);
                break;
            }
            $upd = $conn->prepare("UPDATE tb_events SET event_playstatus = 4 WHERE event_id = ? AND event_team_pkid = ?");
            $upd->bind_param("ii", $event_id, $team_id);
            $upd->execute();
            echo json_encode(['success' => true, 'msg' => 'Event is now Closed.', 'status' => 4]);
        } else {
            // CLOSED OFF → automatically Live (3)
            if ($isExpired) {
                echo json_encode(['success' => false, 'msg' => 'Cannot reopen: validity date has passed.']);
                break;
            }
            $upd = $conn->prepare("UPDATE tb_events SET event_playstatus = 3 WHERE event_id = ? AND event_team_pkid = ?");
            $upd->bind_param("ii", $event_id, $team_id);
            $upd->execute();
            echo json_encode(['success' => true, 'msg' => 'Event is now Live.', 'status' => 3]);
        }
        break;

    default:
        echo json_encode(['success' => false, 'msg' => 'Unknown action']);
}

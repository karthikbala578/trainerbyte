<?php
session_start();
require "../include/dataconnect.php";

if (!isset($_SESSION['team_id'])) {
    http_response_code(403);
    exit("Unauthorized");
}

$event_id = intval($_GET['event_id'] ?? 0);
$type     = $_GET['type'] ?? 'progress'; // 'progress' or 'scores'

if ($event_id <= 0) exit("Invalid event");

/* ── MODULES – use mod_type (correct column in tb_events_module) ── */
$modStmt = $conn->prepare("
    SELECT mod_game_id, mod_type, mod_order
    FROM tb_events_module
    WHERE mod_event_pkid = ? AND mod_status = 1
    ORDER BY mod_order ASC
");
$modStmt->bind_param("i", $event_id);
$modStmt->execute();
$modulesRaw = $modStmt->get_result()->fetch_all(MYSQLI_ASSOC);

$modules = [];
foreach ($modulesRaw as $m) {
    $name = "Module {$m['mod_order']}";
    if ($m['mod_type'] == 2) {
        $cg = $conn->prepare("SELECT cg_name FROM card_group WHERE cg_id = ? LIMIT 1");
        $cg->bind_param("i", $m['mod_game_id']);
        $cg->execute();
        $row = $cg->get_result()->fetch_assoc();
        if ($row) $name = $row['cg_name'];
    }
    $modules[] = ['mod_game_id' => $m['mod_game_id'], 'mod_type' => $m['mod_type'], 'module_name' => $name];
}

/* ── PARTICIPANTS via event_id ────────────────────────────────── */
$partStmt = $conn->prepare("
    SELECT eu.id AS user_id, eu.user_name
    FROM tb_event_user eu
    JOIN tb_event_user_score s ON s.user_id = eu.id
    WHERE s.event_id = ?
    GROUP BY eu.id, eu.user_name
    ORDER BY eu.user_name ASC
");
$partStmt->bind_param("i", $event_id);
$partStmt->execute();
$participants = $partStmt->get_result()->fetch_all(MYSQLI_ASSOC);

/* ── DATA MAP ──────────────────────────────────────────────────── */
$user_ids = array_column($participants, 'user_id');
$dataMap  = [];

if (!empty($user_ids)) {
    $in     = implode(',', array_fill(0, count($user_ids), '?'));
    $types  = 'i' . str_repeat('i', count($user_ids));
    $params = array_merge([$event_id], $user_ids);
    $field  = ($type === 'scores') ? 'mod_game_status' : 'game_status';

    $q = $conn->prepare("
        SELECT user_id, mod_game_id, mod_game_type, game_status, $field AS val, game_summary
        FROM tb_event_user_score
        WHERE event_id = ? AND user_id IN ($in)
    ");
    $q->bind_param($types, ...$params);
    $q->execute();
    foreach ($q->get_result()->fetch_all(MYSQLI_ASSOC) as $r) {
        $key = $r['mod_game_id'] . '_' . $r['mod_game_type'];
        
        $actualScore = $r['val'];
        if ($type === 'scores') {
            if (!empty($r['game_summary'])) {
                $summary = json_decode($r['game_summary'], true);
                if (json_last_error() === JSON_ERROR_NONE && isset($summary['final_score'])) {
                    $actualScore = intval($summary['final_score']);
                }
            }
            
            $nsCheck = strtolower(trim($r['game_status'] ?? ''));
            if ($nsCheck === '' || $nsCheck === 'not started' || $nsCheck === 'not_started') {
                $actualScore = 0;
            }
        }
        
        // Take max score if multiple entries exist
        if (!isset($dataMap[$r['user_id']][$key]) || (is_numeric($actualScore) && $actualScore > $dataMap[$r['user_id']][$key])) {
            $dataMap[$r['user_id']][$key] = $actualScore;
        }
    }
}

/* ── CSV ───────────────────────────────────────────────────────── */
$filename = "event_{$event_id}_" . ($type === 'scores' ? 'scores' : 'progress') . '.csv';
header("Content-Type: text/csv; charset=utf-8");
header("Content-Disposition: attachment; filename=\"$filename\"");
header("Pragma: no-cache");
header("Expires: 0");

$out = fopen("php://output", "w");
fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM for Excel

$header = ["Participant"];
foreach ($modules as $m) $header[] = $m['module_name'];
if ($type === 'scores') $header[] = "Total";
fputcsv($out, $header);

foreach ($participants as $p) {
    $row   = [$p['user_name']];
    $total = 0;
    foreach ($modules as $m) {
        $key = $m['mod_game_id'] . '_' . $m['mod_type'];
        $val = $dataMap[$p['user_id']][$key] ?? ($type === 'scores' ? 0 : 'not started');
        $row[] = $val;
        if ($type === 'scores') $total += intval($val);
    }
    if ($type === 'scores') $row[] = $total;
    fputcsv($out, $row);
}

fclose($out);

<?php
session_start();
require "include/coreDataconnect.php";
header("Content-Type: application/json");

if (!isset($_SESSION['team_id'])) {
    echo json_encode(["error" => "Unauthorized"]);
    exit;
}

$event_id = intval($_GET['event_id'] ?? 0);
$page     = max(1, intval($_GET['page']    ?? 1));
$per_page = intval($_GET['per_page'] ?? 10);
$search   = trim($_GET['search'] ?? '');
$offset   = ($page - 1) * $per_page;

if ($event_id <= 0) {
    echo json_encode(["error" => "Invalid event"]);
    exit;
}

/* ── MODULES ─────────────────────── mod_type = game type in this table */
$modStmt = $conn->prepare("
    SELECT mod_id, mod_game_id, mod_type, mod_order
    FROM tb_events_module
    WHERE mod_event_pkid = ? AND mod_status = 1
    ORDER BY mod_order ASC
");
$modStmt->bind_param("i", $event_id);
$modStmt->execute();
$modulesRaw = $modStmt->get_result()->fetch_all(MYSQLI_ASSOC);

/* Build module name: look up card_group for type=2, else use generic label */
$modules = [];
foreach ($modulesRaw as $m) {
    $name = "Module {$m['mod_order']}";
    if ($m['mod_type'] == 2) {
        // ByteGuess
        $cgr = $conn->prepare("SELECT cg_name FROM card_group WHERE cg_id = ? LIMIT 1");
        $cgr->bind_param("i", $m['mod_game_id']);
        $cgr->execute();
        $cg = $cgr->get_result()->fetch_assoc();
        if ($cg) $name = $cg['cg_name'];
    } elseif ($m['mod_type'] == 5) {
        // DigiSIM
        $dir = $conn->prepare("SELECT di_name FROM mg5_digisim WHERE di_id = ? LIMIT 1");
        $dir->bind_param("i", $m['mod_game_id']);
        $dir->execute();
        $di = $dir->get_result()->fetch_assoc();
        if ($di) $name = $di['di_name'];
    }
    $modules[] = [
        'mod_game_id' => $m['mod_game_id'],
        'mod_type'    => $m['mod_type'],
        'mod_order'   => $m['mod_order'],
        'module_name' => $name
    ];
}

/* ── TOTAL DISTINCT PARTICIPANTS (Filtered by Search) ──────────────── */
$searchTerm = "%$search%";
$countQuery = "
    SELECT COUNT(DISTINCT eu.id) AS total
    FROM tb_event_user eu
    JOIN tb_event_user_score s ON s.user_id = eu.id
    WHERE s.event_id = ?
";
if ($search !== '') {
    $countQuery .= " AND (eu.user_name LIKE ? OR eu.user_pin LIKE ?)";
}
$countStmt = $conn->prepare($countQuery);
if ($search !== '') {
    $countStmt->bind_param("iss", $event_id, $searchTerm, $searchTerm);
} else {
    $countStmt->bind_param("i", $event_id);
}
$countStmt->execute();
$total = $countStmt->get_result()->fetch_assoc()['total'] ?? 0;

/* ── ACTIVE / INACTIVE COUNTS ── */
$activeCountStmt = $conn->prepare("
    SELECT
        SUM(CASE WHEN COALESCE(eu.user_is_active, 1) = 1 THEN 1 ELSE 0 END) AS active_count,
        SUM(CASE WHEN COALESCE(eu.user_is_active, 1) = 0 THEN 1 ELSE 0 END) AS inactive_count
    FROM (
        SELECT DISTINCT eu.id, eu.user_is_active
        FROM tb_event_user eu
        JOIN tb_event_user_score s ON s.user_id = eu.id
        WHERE s.event_id = ?
    ) eu
");
$activeCountStmt->bind_param("i", $event_id);
$activeCountStmt->execute();
$activeCounts  = $activeCountStmt->get_result()->fetch_assoc();
$activeCount   = (int)($activeCounts['active_count']   ?? 0);
$inactiveCount = (int)($activeCounts['inactive_count'] ?? 0);



/* ── PARTICIPANTS (paginated & filtered) ───────────────────────────── */
$partQuery = "
    SELECT eu.id AS user_id, eu.user_name, eu.user_pin,
           COALESCE(eu.user_is_active, 1) AS user_is_active
    FROM tb_event_user eu
    JOIN tb_event_user_score s ON s.user_id = eu.id
    WHERE s.event_id = ?
";
if ($search !== '') {
    $partQuery .= " AND (eu.user_name LIKE ? OR eu.user_pin LIKE ?)";
}
$partQuery .= " GROUP BY eu.id, eu.user_name, eu.user_pin, eu.user_is_active ORDER BY eu.user_name ASC LIMIT ? OFFSET ?";

$partStmt = $conn->prepare($partQuery);
if ($search !== '') {
    $partStmt->bind_param("issii", $event_id, $searchTerm, $searchTerm, $per_page, $offset);
} else {
    $partStmt->bind_param("iii", $event_id, $per_page, $offset);
}
$partStmt->execute();
$participants = $partStmt->get_result()->fetch_all(MYSQLI_ASSOC);

/* ── STATUS MAP ───────────────────────────────────────────────────────── */
$user_ids  = array_column($participants, 'user_id');
$statusMap = [];

if (!empty($user_ids)) {
    $in     = implode(',', array_fill(0, count($user_ids), '?'));
    $types  = 'i' . str_repeat('i', count($user_ids));
    $params = array_merge([$event_id], $user_ids);

    $scoreStmt = $conn->prepare("
        SELECT user_id, mod_game_id, mod_game_type, game_status
        FROM tb_event_user_score
        WHERE event_id = ? AND user_id IN ($in)
    ");
    $scoreStmt->bind_param($types, ...$params);
    $scoreStmt->execute();
    $scores = $scoreStmt->get_result()->fetch_all(MYSQLI_ASSOC);

    foreach ($scores as $s) {
        /* key matches mod_game_id + mod_game_type from score  ←→  mod_game_id + mod_type from module */
        $key = $s['mod_game_id'] . '_' . $s['mod_game_type'];
        $statusMap[$s['user_id']][$key] = strtolower($s['game_status']);
    }
}

/* ── STATS ────────────────────────────────────────────────────────────── */
$completedCount = 0;
$totalCells     = 0;
foreach ($participants as $p) {
    foreach ($modules as $m) {
        $key    = $m['mod_game_id'] . '_' . $m['mod_type'];
        $status = $statusMap[$p['user_id']][$key] ?? 'not_started';
        $totalCells++;
        if ($status === 'completed') $completedCount++;
    }
}
$avgCompletion = $totalCells > 0 ? round(($completedCount / $totalCells) * 100) : 0;

/* ── TOP PERFORMER ────────────────────────────────────────────────────── */
$topStmt = $conn->prepare("
    SELECT eu.user_name,
           COUNT(CASE WHEN s.game_status = 'completed' THEN 1 END) AS done
    FROM tb_event_user_score s
    JOIN tb_event_user eu ON eu.id = s.user_id
    WHERE s.event_id = ?
    GROUP BY eu.id, eu.user_name
    ORDER BY done DESC
    LIMIT 1
");
$topStmt->bind_param("i", $event_id);
$topStmt->execute();
$topPerformer = $topStmt->get_result()->fetch_assoc();

/* ── BUILD ROWS ───────────────────────────────────────────────────────── */
$rows = [];
foreach ($participants as $p) {
    $rowStatuses = [];
    foreach ($modules as $m) {
        $key    = $m['mod_game_id'] . '_' . $m['mod_type'];
        $status = $statusMap[$p['user_id']][$key] ?? 'not_started';
        /* normalise enum value for display */
        $rowStatuses[] = str_replace('_', ' ', $status);
    }
    $rows[] = [
        'user_id'      => $p['user_id'],
        'user_name'    => $p['user_name'],
        'user_pin'     => $p['user_pin'],
        'user_is_active' => (int)($p['user_is_active'] ?? 1),
        'statuses'     => $rowStatuses
    ];
}

echo json_encode([
    'modules'    => array_column($modules, 'module_name'),
    'rows'       => $rows,
    'stats'      => [
        'total_participants' => $total,
        'active_count'       => $activeCount,
        'inactive_count'     => $inactiveCount,
        'avg_completion'     => $avgCompletion,
        'modules_count'      => count($modules),
        'top_performer'      => $topPerformer['user_name'] ?? '—',
    ],
    'pagination' => [
        'page'     => $page,
        'per_page' => $per_page,
        'total'    => $total,
        'pages'    => (int) ceil($total / $per_page)
    ]
]);
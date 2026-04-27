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

/* ── MODULES ──────────────────────────────────────────────────────────── */
$modStmt = $conn->prepare("
    SELECT mod_id, mod_game_id, mod_type, mod_order
    FROM tb_events_module
    WHERE mod_event_pkid = ? AND mod_status = 1
    ORDER BY mod_order ASC
");
$modStmt->bind_param("i", $event_id);
$modStmt->execute();
$modulesRaw = $modStmt->get_result()->fetch_all(MYSQLI_ASSOC);

$modules = [];
foreach ($modulesRaw as $m) {
    $name = "Round {$m['mod_order']}";
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

/* ── TOTAL (Filtered by Search) ───────────────────────────────────────── */
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

/* ── PARTICIPANTS (paginated & filtered) ─────────────────────────────────── */
$partQuery = "
    SELECT eu.id AS user_id, eu.user_name, eu.user_is_active
    FROM tb_event_user eu
    JOIN tb_event_user_score s ON s.user_id = eu.id
    WHERE s.event_id = ?
";
if ($search !== '') {
    $partQuery .= " AND (eu.user_name LIKE ? OR eu.user_pin LIKE ?)";
}
$partQuery .= " GROUP BY eu.id, eu.user_name, eu.user_is_active ORDER BY eu.user_name ASC LIMIT ? OFFSET ?";

$partStmt = $conn->prepare($partQuery);
if ($search !== '') {
    $partStmt->bind_param("issii", $event_id, $searchTerm, $searchTerm, $per_page, $offset);
} else {
    $partStmt->bind_param("iii", $event_id, $per_page, $offset);
}
$partStmt->execute();
$participants = $partStmt->get_result()->fetch_all(MYSQLI_ASSOC);

/* ── SCORE MAP ────────────────────────────────────────────────────────── */
$user_ids = array_column($participants, 'user_id');
$scoreMap = [];

if (!empty($user_ids)) {
    $in     = implode(',', array_fill(0, count($user_ids), '?'));
    $types  = 'i' . str_repeat('i', count($user_ids));
    $params = array_merge([$event_id], $user_ids);

    $scoreStmt = $conn->prepare("
        SELECT user_id, mod_game_id, mod_game_type, game_status, mod_game_status, game_summary
        FROM tb_event_user_score
        WHERE event_id = ? AND user_id IN ($in)
    ");
    $scoreStmt->bind_param($types, ...$params);
    $scoreStmt->execute();
    $scores = $scoreStmt->get_result()->fetch_all(MYSQLI_ASSOC);

    foreach ($scores as $s) {
        $key     = $s['mod_game_id'] . '_' . $s['mod_game_type'];
        $nsCheck = strtolower(trim($s['game_status'] ?? ''));

        // Not started → skip (leave null in scoreMap so UI shows "—")
        if ($nsCheck === '' || $nsCheck === 'not started' || $nsCheck === 'not_started') {
            // Only set null if no entry exists yet (don't overwrite a real score)
            if (!isset($scoreMap[$s['user_id']][$key])) {
                $scoreMap[$s['user_id']][$key] = null;
            }
            continue;
        }

        $val = 0; // default for in-progress / completed

        if (!empty($s['game_summary'])) {
            $summary = json_decode($s['game_summary'], true);
            if (json_last_error() === JSON_ERROR_NONE) {
                if (isset($summary['final_score'])) {
                    $val = intval($summary['final_score']);
                } elseif (isset($summary['high']) || isset($summary['medium']) || isset($summary['low'])) {
                    // DigiSIM: sum task scores across all priority bands
                    foreach (['high', 'medium', 'low'] as $band) {
                        if (!empty($summary[$band]) && is_array($summary[$band])) {
                            foreach ($summary[$band] as $task) {
                                $val += intval($task['score'] ?? 0);
                            }
                        }
                    }
                }
            }
        } else {
            // No summary: only trust mod_game_status if the game is completed
            // (avoids showing 1 for in-progress / not-started DigiSIM records)
            if ($nsCheck === 'completed') {
                $val = intval($s['mod_game_status']);
            }
        }

        // Keep the highest real score seen (null treated as -∞)
        if (!isset($scoreMap[$s['user_id']][$key]) || $scoreMap[$s['user_id']][$key] === null || $val > $scoreMap[$s['user_id']][$key]) {
            $scoreMap[$s['user_id']][$key] = $val;
        }
    }
}

/* ── GRAND STATS ──────────────────────────────────────────────────────── */
$allScores = [];
foreach ($scoreMap as $mods) {
    foreach ($mods as $score) {
        if ($score !== null) $allScores[] = $score; // exclude not-started (null)
    }
}
$totalScore = array_sum($allScores);
$classAvg   = count($allScores) > 0 ? round(array_sum($allScores) / count($allScores), 1) : 0;
$highScore  = count($allScores) > 0 ? max($allScores) : 0;
$lowScore   = count($allScores) > 0 ? min($allScores) : 0;

/* ── ROWS ─────────────────────────────────────────────────────────────── */
$rows = [];
foreach ($participants as $p) {
    $rowScores = [];
    $rowTotal  = 0;
    foreach ($modules as $m) {
        $key   = $m['mod_game_id'] . '_' . $m['mod_type'];
        $score = $scoreMap[$p['user_id']][$key] ?? null;
        $rowScores[] = $score;
        if ($score !== null) $rowTotal += $score;
    }
    $rows[] = [
        'user_id'        => $p['user_id'],
        'user_name'      => $p['user_name'],
        'user_is_active' => (int)$p['user_is_active'],
        'scores'         => $rowScores,
        'total'          => $rowTotal
    ];
}

echo json_encode([
    'modules'    => array_column($modules, 'module_name'),
    'rows'       => $rows,
    'stats'      => [
        'total_score' => $totalScore,
        'class_avg'   => $classAvg,
        'high_score'  => $highScore,
        'low_score'   => $lowScore,
    ],
    'pagination' => [
        'page'     => $page,
        'per_page' => $per_page,
        'total'    => $total,
        'pages'    => (int) ceil($total / $per_page)
    ]
]);
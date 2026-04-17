<?php
session_start();
require "include/coredataconnect.php";

if (!isset($_SESSION['team_id'])) {
    header("Location: login.php");
    exit;
}

$event_id = intval($_GET['event_id'] ?? 0);
$user_id  = intval($_GET['user_id'] ?? 0);

if ($event_id <= 0) {
    die("Invalid Event ID");
}

// 1. Get all participants for the dropdown
$partStmt = $conn->prepare("
    SELECT eu.id, eu.user_name, eu.user_pin, eu.created_date_time
    FROM tb_event_user eu
    JOIN tb_event_user_score s ON s.user_id = eu.id
    WHERE s.event_id = ?
    GROUP BY eu.id, eu.user_name, eu.user_pin, eu.created_date_time
    ORDER BY eu.user_name ASC
");
$partStmt->bind_param("i", $event_id);
$partStmt->execute();
$allParticipants = $partStmt->get_result()->fetch_all(MYSQLI_ASSOC);

if (empty($allParticipants)) {
    die("No participants found for this event.");
}

// Default to first user if none provided or invalid
$currentUser = null;
foreach ($allParticipants as $p) {
    if ($p['id'] == $user_id) {
        $currentUser = $p;
        break;
    }
}
if (!$currentUser) {
    $currentUser = $allParticipants[0];
    $user_id = $currentUser['id'];
}

// 2. Get All Modules for the Event
$modStmt = $conn->prepare("
    SELECT mod_game_id, mod_type, mod_order
    FROM tb_events_module
    WHERE mod_event_pkid = ? AND mod_status = 1
    ORDER BY mod_order ASC
");
$modStmt->bind_param("i", $event_id);
$modStmt->execute();
$modulesRaw = $modStmt->get_result()->fetch_all(MYSQLI_ASSOC);

// 3. Get User Scores
$scoreStmt = $conn->prepare("
    SELECT mod_game_id, mod_game_type, game_status, mod_game_status AS val, created_on, game_summary
    FROM tb_event_user_score
    WHERE event_id = ? AND user_id = ?
");
$scoreStmt->bind_param("ii", $event_id, $user_id);
$scoreStmt->execute();
$scoresRaw = $scoreStmt->get_result()->fetch_all(MYSQLI_ASSOC);

$scoreMap = [];
foreach ($scoresRaw as $s) {
    $key     = $s['mod_game_id'] . '_' . $s['mod_game_type'];
    $nsCheck = strtolower(trim($s['game_status'] ?? ''));
    $actualScore = 0;

    if (!empty($s['game_summary'])) {
        $summary = json_decode($s['game_summary'], true);
        if (json_last_error() === JSON_ERROR_NONE) {
            if (isset($summary['final_score'])) {
                $actualScore = intval($summary['final_score']);
            } elseif (isset($summary['high']) || isset($summary['medium']) || isset($summary['low'])) {
                foreach (['high', 'medium', 'low'] as $band) {
                    if (!empty($summary[$band]) && is_array($summary[$band])) {
                        foreach ($summary[$band] as $task) {
                            $actualScore += intval($task['score'] ?? 0);
                        }
                    }
                }
            }
        }
    } else {
        if ($nsCheck === 'completed') {
            $actualScore = intval($s['val']);
        }
    }

    if ($nsCheck === '' || $nsCheck === 'not started' || $nsCheck === 'not_started') {
        $actualScore = 0;
    }

    if (!isset($scoreMap[$key]) || $actualScore > intval($scoreMap[$key]['val'])) {
        $scoreMap[$key] = [
            'status'  => strtolower($s['game_status']),
            'val'     => $actualScore,
            'date'    => $s['created_on'],
            'summary' => $s['game_summary']
        ];
    }
}

// 4. Build Profile Data
$moduleHistory = [];
$totalScore = 0;
$completedMods = 0;

foreach ($modulesRaw as $m) {
    $name    = "Round {$m['mod_order']}";
    $subtext = "Event Module";

    if ($m['mod_type'] == 2) {
        // ByteGuess
        $cgr = $conn->prepare("SELECT cg_name FROM card_group WHERE cg_id = ? LIMIT 1");
        $cgr->bind_param("i", $m['mod_game_id']);
        $cgr->execute();
        $cg = $cgr->get_result()->fetch_assoc();
        if ($cg) {
            $name    = $cg['cg_name'];
            $subtext = "Card Group";
        }
    } elseif ($m['mod_type'] == 5) {
        // DigiSIM
        $dir = $conn->prepare("SELECT di_name FROM mg5_digisim WHERE di_id = ? LIMIT 1");
        $dir->bind_param("i", $m['mod_game_id']);
        $dir->execute();
        $di = $dir->get_result()->fetch_assoc();
        if ($di) {
            $name    = $di['di_name'];
            $subtext = "DigiSIM";
        }
    }

    $key = $m['mod_game_id'] . '_' . $m['mod_type'];
    $sc  = $scoreMap[$key] ?? ['status' => 'not started', 'val' => 0, 'date' => null];

    $rawStatus = trim(strtolower($sc['status'] ?? ''));
    $normStatus = empty($rawStatus) ? 'not started' : $rawStatus;

    $actualScoreVal = $sc['val'];
    if ($normStatus === 'not started' || $normStatus === 'not_started') {
        $actualScoreVal = 0;
    }

    $totalScore += $actualScoreVal;
    if ($normStatus === 'completed') $completedMods++;

    $moduleHistory[] = [
        'name'    => $name,
        'subtext' => $subtext,
        'status'  => $normStatus,
        'score'   => $actualScoreVal,
        'date'    => $sc['date'] ? date("M d, Y", strtotime($sc['date'])) : '—',
        'summary' => $sc['summary'] ?? ''
    ];
}

// 5. Calculate Global Rank Computation (Robust PHP-based)
$allScoresStmt = $conn->prepare("
    SELECT user_id, mod_game_id, mod_game_type, game_status, mod_game_status AS val, game_summary
    FROM tb_event_user_score
    WHERE event_id = ?
");
$allScoresStmt->bind_param("i", $event_id);
$allScoresStmt->execute();
$allScoresRaw = $allScoresStmt->get_result()->fetch_all(MYSQLI_ASSOC);

$userBestScores = [];
foreach ($allScoresRaw as $s) {
    $nsCheck = strtolower(trim($s['game_status'] ?? ''));
    $actualScore = 0;

    if (!empty($s['game_summary'])) {
        $summary = json_decode($s['game_summary'], true);
        if (json_last_error() === JSON_ERROR_NONE) {
            if (isset($summary['final_score'])) {
                $actualScore = intval($summary['final_score']);
            } elseif (isset($summary['high']) || isset($summary['medium']) || isset($summary['low'])) {
                foreach (['high', 'medium', 'low'] as $band) {
                    if (!empty($summary[$band]) && is_array($summary[$band])) {
                        foreach ($summary[$band] as $task) {
                            $actualScore += intval($task['score'] ?? 0);
                        }
                    }
                }
            }
        }
    } else {
        if ($nsCheck === 'completed') {
            $actualScore = intval($s['val']);
        }
    }

    if ($nsCheck === '' || $nsCheck === 'not started' || $nsCheck === 'not_started') {
        $actualScore = 0;
    }

    $key = $s['mod_game_id'] . '_' . $s['mod_game_type'];
    $uid = $s['user_id'];

    if (!isset($userBestScores[$uid])) $userBestScores[$uid] = [];
    if (!isset($userBestScores[$uid][$key]) || $actualScore > $userBestScores[$uid][$key]) {
        $userBestScores[$uid][$key] = $actualScore;
    }
}

$rankList = [];
foreach($allParticipants as $p) {
    $uid = $p['id'];
    $total = isset($userBestScores[$uid]) ? array_sum($userBestScores[$uid]) : 0;
    $rankList[] = ['id' => $uid, 't_score' => $total];
}
usort($rankList, function($a, $b) {
    return $b['t_score'] - $a['t_score'];
});

$myRank = 0;
$totalUsers = count($rankList);
if ($totalUsers === 0) {
    $myRank = 1;
    $totalUsers = 1;
} else {
    foreach ($rankList as $idx => $r) {
        if ($r['id'] == $user_id) {
            $myRank = $idx + 1;
            break;
        }
    }
}

$pageTitle = "User Performance View";
$pageCSS   = "/css_event/user_performance.css";
require "layout/tb_header.php";
?>

<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200">

<div class="up-page">

    <!-- Header Section -->
    <div class="up-header-area">
        <div class="up-title-block">
            <a href="view_event.php?event_id=<?= $event_id ?>" class="up-back-link" title="Back to Event">
                <span class="material-symbols-rounded">arrow_back</span>
            </a>
            <div class="up-title-text">
                <h1>User Performance View</h1>
                <p>Detailed module progress and performance metrics for specific users.</p>
            </div>
        </div>
        <div class="up-actions">
            <form method="GET" action="user_performance.php" class="up-select-form-compact">
                <input type="hidden" name="event_id" value="<?= $event_id ?>">
                <div class="up-input-wrapper-compact">
                    <span class="material-symbols-rounded icon">person_search</span>
                    <select name="user_id" id="userSelect" onchange="this.form.submit()">
                        <?php foreach($allParticipants as $p): ?>
                            <option value="<?= $p['id'] ?>" <?= $p['id'] == $user_id ? 'selected' : '' ?>>
                                <?= htmlspecialchars($p['user_name']) ?> (PIN: <?= htmlspecialchars($p['user_pin']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </form>
            <button class="up-btn up-btn-outline" onclick="window.print()" title="Export Report">
                <span class="material-symbols-rounded">download</span> Export
            </button>
        </div>
    </div>

    <!-- Main Content Section -->
    <div class="up-section">
        
        <!-- Stats Row -->
        <div class="up-stats-row">
        
        <div class="up-stat-card">
            <div class="up-stat-main">
                <div class="up-stat-left">
                    <span class="up-icon blue"><span class="material-symbols-rounded">bar_chart</span></span>
                    <span class="up-stat-title">GLOBAL RANK</span>
                </div>
                <div class="up-stat-value">#<?= $myRank ?></div>
            </div>
            <div class="up-stat-subtext green">
                <span class="material-symbols-rounded">arrow_upward</span> Top <?= round(($myRank/$totalUsers)*100) ?>% of users
            </div>
        </div>

        <div class="up-stat-card">
            <div class="up-stat-main">
                <div class="up-stat-left">
                    <span class="up-icon orange"><span class="material-symbols-rounded">stars</span></span>
                    <span class="up-stat-title">TOTAL POINTS</span>
                </div>
                <div class="up-stat-value"><?= number_format($totalScore) ?></div>
            </div>
            <div class="up-stat-subtext neutral">
                Cumulative score
            </div>
        </div>

        <div class="up-stat-card">
            <div class="up-stat-main">
                <div class="up-stat-left">
                    <span class="up-icon emerald"><span class="material-symbols-rounded">verified</span></span>
                    <span class="up-stat-title">MODULES COMPLETED</span>
                </div>
                <div class="up-stat-value"><?= $completedMods ?></div>
            </div>
            <div class="up-stat-subtext neutral">
                <?= count($modulesRaw) - $completedMods ?> remaining
            </div>
        </div>

        <div class="up-stat-card">
            <div class="up-stat-main">
                <div class="up-stat-left">
                    <span class="up-icon rose"><span class="material-symbols-rounded">timer</span></span>
                    <span class="up-stat-title">PARTICIPANT PIN</span>
                </div>
                <div class="up-stat-value"><?= htmlspecialchars($currentUser['user_pin']) ?></div>
            </div>
            <div class="up-stat-subtext neutral">
                Joined: <?= date("M d, Y", strtotime($currentUser['created_date_time'])) ?>
            </div>
        </div>

        </div>
        <!-- End Stats Row -->

        <!-- Module History Header -->
        <div class="up-card-header">
            <h3>Module Interaction History</h3>
            <div class="up-card-tools">
                <div class="up-filter">
                    <span>Filter:</span>
                    <select id="statusFilter" onchange="filterTable()">
                        <option value="All">All</option>
                        <option value="Completed">Completed</option>
                        <option value="In Progress">In Progress</option>
                        <option value="Not Started">Not Started</option>
                    </select>
                </div>
            </div>
        </div>
        
        <div class="up-table-container">
            <table class="up-history-table">
                <thead>
                    <tr>
                        <th>MODULE NAME</th>
                        <th>STATUS</th>
                        <th>SCORE</th>
                        <th>COMPLETION DATE</th>
                        <th>ACTION</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($moduleHistory as $m): 
                        $statusNorm = trim(strtolower($m['status']));
                        $statusCls = 'st-none';
                        $statusLabel = 'Not Started';
                        if ($statusNorm === 'completed') { $statusCls = 'st-done'; $statusLabel = 'Completed'; }
                        elseif ($statusNorm === 'in progress') { $statusCls = 'st-prog'; $statusLabel = 'In Progress'; }
                        
                        $scoreRaw = intval($m['score']);
                    ?>
                    <tr>
                        <td>
                            <div class="up-mod-info">
                                <span class="up-mod-icon"><span class="material-symbols-rounded">policy</span></span>
                                <div>
                                    <div class="up-mod-name"><?= htmlspecialchars($m['name']) ?></div>
                                    <div class="up-mod-sub"><?= htmlspecialchars($m['subtext']) ?></div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <span class="up-badge <?= $statusCls ?>"><?= htmlspecialchars($statusLabel) ?></span>
                        </td>
                        <td>
                            <div class="up-score-cell">
                                <span class="up-score-text" style="font-size:15px;"><b><?= $scoreRaw ?></b></span>
                            </div>
                        </td>
                        <td class="up-date-cell">
                            <?= htmlspecialchars($m['date']) ?>
                        </td>
                        <td>
                            <a href="#" class="up-action-link" onclick="openDetailsModal(this); return false;" data-summary="<?= htmlspecialchars($m['summary'], ENT_QUOTES, 'UTF-8') ?>">View Details <span class="material-symbols-rounded">chevron_right</span></a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($moduleHistory)): ?>
                        <tr><td colspan="5" style="text-align:center;color:#64748b;padding:40px;">No module data found for this user.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <div class="up-card-footer">
            <span id="showingCountText">Showing <?= count($moduleHistory) ?> of <?= count($modulesRaw) ?> total interactions</span>
        </div>

    </div><!-- /up-section -->
</div><!-- /up-page -->

<!-- Details Modal -->
<div class="up-modal-overlay" id="detailsModalOverlay" onclick="closeDetailsModal(event)">
    <div class="up-modal" id="detailsModal">
        <div class="up-modal-header">
            <h3>Module Details</h3>
            <button class="up-modal-close" onclick="closeDetailsModal()"><span class="material-symbols-rounded">close</span></button>
        </div>
        <div class="up-modal-body" id="dmBody">
            <!-- Dynamic content -->
        </div>
    </div>
</div>

<script>
function openDetailsModal(btn) {
    const overlay = document.getElementById('detailsModalOverlay');
    const body    = document.getElementById('dmBody');
    const rawData = btn.getAttribute('data-summary');

    if (!rawData || String(rawData).trim() === '') {
        body.innerHTML = '<div style="padding:40px 20px; color:#64748b; text-align:center;">No detailed activity data available for this module.</div>';
        overlay.classList.add('open');
        return;
    }

    try {
        const data = JSON.parse(rawData);

        /* ── DigiSIM summary: aggregate stats instead of task lists ── */
        if (data.high !== undefined || data.medium !== undefined || data.low !== undefined) {
            
            let totalScore = 0;
            let totalCount = 0;
            
            const bandsData = {
                high:   { label: 'High Priority',   score: 0, count: 0, color: '#ef4444', bg: '#fef2f2', border: '#fca5a5' },
                medium: { label: 'Medium Priority', score: 0, count: 0, color: '#f59e0b', bg: '#fffbeb', border: '#fcd34d' },
                low:    { label: 'Low Priority',    score: 0, count: 0, color: '#22c55e', bg: '#f0fdf4', border: '#86efac' }
            };

            ['high', 'medium', 'low'].forEach(key => {
                const items = data[key] || [];
                items.forEach(t => {
                    const s = parseInt(t.score || 0);
                    bandsData[key].score += s;
                    bandsData[key].count += 1;
                    totalScore += s;
                    totalCount += 1;
                });
            });

            const html = `
                <div style="margin-bottom: 24px;">
                    <div style="font-size: 13px; color: #64748b; margin-bottom: 16px;">
                        Game ID: <strong>${data.game_id ?? '—'}</strong> &nbsp;&bull;&nbsp; Total Tasks Completed: <strong>${totalCount}</strong>
                    </div>
                    
                    <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 16px;">
                        
                        <!-- Total Score -->
                        <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 20px; text-align: center; grid-column: 1 / -1; display: flex; justify-content: center; align-items: center; gap: 16px;">
                            <div style="font-size: 14px; font-weight: 700; color: #475569; text-transform: uppercase; letter-spacing: 0.5px;">Total Score</div>
                            <div style="font-size: 32px; font-weight: 800; color: #0f172a; line-height: 1;">${totalScore}</div>
                        </div>

                        <!-- High Priority -->
                        <div style="background: ${bandsData.high.bg}; border: 1px solid ${bandsData.high.border}; border-radius: 12px; padding: 16px; display: flex; flex-direction: column;">
                            <div style="font-size: 12px; font-weight: 700; color: ${bandsData.high.color}; text-transform: uppercase; margin-bottom: 8px;">${bandsData.high.label}</div>
                            <div style="font-size: 24px; font-weight: 800; color: #0f172a; margin-bottom: 4px;">${bandsData.high.score} <span style="font-size: 14px; font-weight: 600; color: #64748b;">pts</span></div>
                            <div style="font-size: 13px; color: #64748b; font-weight: 500;">${bandsData.high.count} tasks selected</div>
                        </div>

                        <!-- Medium Priority -->
                        <div style="background: ${bandsData.medium.bg}; border: 1px solid ${bandsData.medium.border}; border-radius: 12px; padding: 16px; display: flex; flex-direction: column;">
                            <div style="font-size: 12px; font-weight: 700; color: ${bandsData.medium.color}; text-transform: uppercase; margin-bottom: 8px;">${bandsData.medium.label}</div>
                            <div style="font-size: 24px; font-weight: 800; color: #0f172a; margin-bottom: 4px;">${bandsData.medium.score} <span style="font-size: 14px; font-weight: 600; color: #64748b;">pts</span></div>
                            <div style="font-size: 13px; color: #64748b; font-weight: 500;">${bandsData.medium.count} tasks selected</div>
                        </div>

                        <!-- Low Priority -->
                        <div style="background: ${bandsData.low.bg}; border: 1px solid ${bandsData.low.border}; border-radius: 12px; padding: 16px; display: flex; flex-direction: column; grid-column: 1 / -1;">
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <div>
                                    <div style="font-size: 12px; font-weight: 700; color: ${bandsData.low.color}; text-transform: uppercase; margin-bottom: 4px;">${bandsData.low.label}</div>
                                    <div style="font-size: 13px; color: #64748b; font-weight: 500;">${bandsData.low.count} tasks selected</div>
                                </div>
                                <div style="font-size: 24px; font-weight: 800; color: #0f172a;">${bandsData.low.score} <span style="font-size: 14px; font-weight: 600; color: #64748b;">pts</span></div>
                            </div>
                        </div>

                    </div>
                </div>
            `;

            body.innerHTML = html;
            overlay.classList.add('open');
            return;
        }

        /* ── ByteGuess / standard summary ── */
        let html = '<div class="up-details-grid">';

        if (data.total_cards !== undefined) {
            html += `
                <div class="up-d-stat"><div class="lbl">Total Cards</div><div class="val">${data.total_cards}</div></div>
                <div class="up-d-stat"><div class="lbl">Opened Cards</div><div class="val">${data.opened_cards}</div></div>
                <div class="up-d-stat"><div class="lbl">Remaining Cards</div><div class="val">${data.remaining_cards}</div></div>
                <div class="up-d-stat"><div class="lbl">Used Hints</div><div class="val">${data.used_hints}</div></div>
            `;
        }

        if (data.final_score !== undefined) {
            html += `<div class="up-d-stat"><div class="lbl">Final Score</div><div class="val" style="color:#2563eb;">${data.final_score}</div></div>`;
        }

        html += '</div>';

        if (data.final_decision) {
            html += `
                <div class="up-d-section">
                    <h4>Final Decision</h4>
                    <div class="up-d-box">
                        <strong>${data.final_decision.title || 'Decision Selected'}</strong>
                        <p>${data.final_decision.answer || ''}</p>
                    </div>
                </div>`;
        }

        if (data.total_cards === undefined && !data.final_decision) {
            html += `<div class="up-d-section"><h4>Raw Data</h4><pre class="up-d-raw">${JSON.stringify(data, null, 2)}</pre></div>`;
        }

        body.innerHTML = html;

    } catch(e) {
        body.innerHTML = '<div style="padding:20px; color:#ef4444;">Failed to parse activity data correctly.</div>';
    }

    overlay.classList.add('open');
}

function closeDetailsModal(e) {
    if (e && e.target.id !== 'detailsModalOverlay' && e.target.closest('.up-modal-close') === null) return;
    document.getElementById('detailsModalOverlay').classList.remove('open');
}

function filterTable() {
    const filter = document.getElementById('statusFilter').value.toLowerCase();
    const rows = document.querySelectorAll('.up-history-table tbody tr');
    let visibleCount = 0;
    
    rows.forEach(row => {
        if (row.querySelector('td[colspan="5"]')) return;
        const statusTd = row.querySelectorAll('td')[1];
        if (!statusTd) return;
        
        const statusText = statusTd.innerText.trim().toLowerCase();
        
        if (filter === 'all' || statusText === filter) {
            row.style.display = '';
            visibleCount++;
        } else {
            row.style.display = 'none';
        }
    });
    
    const showingText = document.getElementById('showingCountText');
    if (showingText) {
        showingText.innerText = `Showing ${visibleCount} filtered interactions`;
    }
}
</script>

<?php //require "../layout/footer.php"; ?>
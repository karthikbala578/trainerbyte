<?php
session_start();
require_once "../include/coreDataconnect.php";

$code = $_SESSION['event_code'];
$ro_id = (int)($_GET['game_id'] ?? 0);
$sim_id = $ro_id; // Assuming sim_id is the same as game_id for this context
if (!$ro_id) {
    die("Invalid game ID");
}

/* ================= FETCH DEBRIEFING ================= */
$stmt = $conn->prepare("
    SELECT di_answerkey
    FROM mg5_digisim
    WHERE di_id = ?
");
$stmt->bind_param("i", $sim_id);   // ✅ use sim_id
$stmt->execute();
$stmt->bind_result($debriefing);
$stmt->fetch();
$stmt->close();


$stmt = $conn->prepare("SELECT game_summary FROM tb_event_user_score WHERE mod_game_id = ? AND event_id = ? AND user_id = ? ORDER BY id DESC LIMIT 1 ");
$stmt->bind_param("iii", $ro_id, $_SESSION['event_id'], $_SESSION['user_id']);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
// print_r($row);
if (!$row || empty($row['game_summary'])) {
    die("Final game result not found");
}

$summary = json_decode($row['game_summary'], true);
if (!is_array($summary)) {
    die("Invalid game summary data");
}


$finalScore     = (int)($summary['final_score'] ?? 0);
$finalDecision  = $summary['final_decision'] ?? [];
$decisionTitle  = $finalDecision['title'] ?? '';
$decisionAnswer = $finalDecision['answer'] ?? '';

/* ================= GET DIGISIM CONFIG ================= */
$stmt = $conn->prepare("
    SELECT di_analysis_id, di_scoretype_id, di_scoring_logic, di_total_basis
    FROM mg5_digisim
    WHERE di_id = ?
");
$stmt->bind_param("i", $sim_id);
$stmt->execute();
$stmt->bind_result($analysisId, $scoreTypeId, $logicType, $totalBasis);
$stmt->fetch();
$stmt->close();

/* ================= SCORE MAP ================= */
$scoreMap = [];

$stmt = $conn->prepare("
    SELECT stv_id, stv_value
    FROM mg5_scoretype_value
    WHERE stv_scoretype_pkid = ?
");
$stmt->bind_param("i", $scoreTypeId);
$stmt->execute();
$res = $stmt->get_result();

while ($r = $res->fetch_assoc()) {
    $scoreMap[$r['stv_id']] = $r['stv_value'];
}
$stmt->close();

/* ================= FETCH RESPONSES ================= */
$stmt = $conn->prepare("
    SELECT dr_id, dr_score_pkid
    FROM mg5_digisim_response
    WHERE dr_digisim_pkid = ?
");
$stmt->bind_param("i", $sim_id);
$stmt->execute();
$res = $stmt->get_result();

$responses = [];
while ($r = $res->fetch_assoc()) {
    $responses[$r['dr_id']] = $r;
}
$stmt->close();

/* ================= USER MAP ================= */
$userSelections = [];

foreach ($summary as $bucket => $items) {
    if ($bucket == "game_id") continue;

    foreach ($items as $t) {
        $userSelections[$t['task_id']] = $t['score'];
    }
}

/* ================= SCORING ================= */
function calculateScore($user, $expert, $logic)
{
    if ($expert == 0) return 0;

    if ($logic == 1) return ($user >= $expert) ? 100 : 0; // atleast
    if ($logic == 2) return ($user == $expert) ? 100 : 0;  // exact

    if ($logic == 3) { // absolute
        if ($user == $expert) return 100;
        if ($expert > $user) return round(($user / $expert) * 100);
        return round(($expert / $user) * 100);
    }

    return 0;
}

$totalScore = 0;
$totalTasks = count($responses);
$answered = 0;

foreach ($responses as $id => $r) {

    $expertId = $r['dr_score_pkid'];
    $userId   = $userSelections[$id] ?? null;

    $expertVal = $scoreMap[$expertId] ?? 0;
    $userVal   = $userId ? ($scoreMap[$userId] ?? 0) : 0;

    if ($userId !== null) {
        $totalScore += calculateScore($userVal, $expertVal, $logicType);
        $answered++;
    }
}

/* ================= FINAL SCORE ================= */
if ($totalScore > 0) {
    if ($totalBasis == 1) {
        $finalScore = round($totalScore / $totalTasks);
    } else {
        $finalScore = ($answered > 0) ? round($totalScore / $answered) : 0;
    }
} else {
    $finalScore = 0;
}

/* ================= BAND ================= */
$band = null;

$stmt = $conn->prepare("
    SELECT rf_name, rf_colour, rf_help
    FROM mg5_sub_result_factor
    WHERE an_group_pkid = ?
    AND rf_status = 1
    AND ? BETWEEN rf_from AND rf_to
    LIMIT 1
");

$stmt->bind_param("ii", $analysisId, $finalScore);
$stmt->execute();
$band = $stmt->get_result()->fetch_assoc();
$stmt->close();
?>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Game De-briefing & Final Results</title>
    <link rel="stylesheet" type="text/css" href="css_site/digisim_resultPage.css">
    <link rel="stylesheet" href="assets/fontawesome/css/all.min.css">
    <script src="https://kit.fontawesome.com/84b78c6f4c.js" crossorigin="anonymous"></script>
</head>
<body>
    
    
    <div class="page-container">
        <div>
            <?php include("sidenav.php"); ?>
        </div>
        <div class="page-header">
            <h1>Game De-briefing & Final Results</h1>
            <p>Review your strategic decisions and final performance outcomes.</p>
        </div>

        <div class="results-grid">
            
            <div class="left-col">
                <div class="debrief-card">
                    <div class="debrief-header">
                        <div class="icon"><i class="fa-solid fa-comment-dots"></i></div>
                        <h2>Debriefing</h2>
                    </div>
                    
                    <div class="debrief-content">
                        <?php
                            $cleanDebriefing = !empty($debriefing)
                                ? strip_tags($debriefing, '<b><strong><em><i><ul><ol><li><br><p>')
                                : '';
                        ?>

                        <?php if (!empty($cleanDebriefing)): ?>
                            <div class="debrief-text">
                                <?php echo $cleanDebriefing; ?>
                            </div>
                        <?php else: ?>
                            <p style="color:#94a3b8;font-style:italic;">No debriefing content available.</p>
                        <?php endif; ?>

                        <?php if (!empty($decisionTitle)): ?>
                            <div class="final-decision-block" style="margin-top: 30px;">
                                <h3><i class="fa-solid fa-gavel"></i> Your Final Decision</h3>
                                <div class="decision-box">
                                    <strong><?php echo htmlspecialchars($decisionTitle); ?></strong>
                                    <p><?php echo nl2br(htmlspecialchars($decisionAnswer)); ?></p>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="right-col">
                
                <div class="side-card stats-card">
                    <div class="stats-grid">
                        <div class="circle-stat">
                            <div class="circle-wrap">
                                <svg viewBox="0 0 100 100">
                                    <circle class="circle-bg" cx="50" cy="50" r="40"></circle>
                                    <circle class="circle-progress" cx="50" cy="50" r="40" 
                                        style="stroke-dasharray: 251.2; stroke-dashoffset: <?php echo 251.2 - (251.2 * $finalScore / 100); ?>; stroke: #2563eb;">
                                    </circle>
                                </svg>
                                <div class="circle-inner">
                                    <div class="value"><?php echo $finalScore; ?>%</div>
                                    <div class="label">Score</div>
                                </div>
                            </div>
                        </div>

                        <?php if ($band): ?>
                        <div class="circle-stat">
                            <div class="circle-wrap">
                                <svg viewBox="0 0 100 100">
                                    <circle class="circle-bg" cx="50" cy="50" r="40"></circle>
                                    <circle class="circle-progress" cx="50" cy="50" r="40" 
                                        style="stroke-dasharray: 251.2; stroke-dashoffset: 0; stroke: <?php echo htmlspecialchars($band['rf_colour']); ?>;">
                                    </circle>
                                </svg>
                                <div class="circle-inner band-circle-inner">
                                    <div class="value" style="color: <?php echo htmlspecialchars($band['rf_colour']); ?>;">
                                        <?php echo htmlspecialchars($band['rf_name']); ?>
                                    </div>
                                    <div class="label">Band</div>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="side-card action-card">
                    <div class="btn-vertical-group">
                        <a href="digisim_detailedView.php?game_id=<?php echo $ro_id ?>" class="btn-modern btn-outline">
                            <i class="fa-solid fa-eye"></i> View Details
                        </a>
                        <a href="digisim_finalDecision.php?game_id=<?php echo $ro_id; ?>&code=<?php echo urlencode($code); ?>" class="btn-modern btn-primary">
                            <i class="fa-solid fa-gavel"></i> Final Decision
                        </a>
                        <!-- <hr>
                        <a href="../teaminstance_be.php?user_id=<?php echo $_SESSION['user_id']; ?>&code=<?php echo urlencode($code); ?>" class="btn-modern btn-dark">
                            <i class="fa-solid fa-arrow-left"></i> Return to Library
                        </a> -->
                    </div>
                </div>

            </div> 
        </div> 
    </div>

    <!-- EXIT POPUP -->
    <div id="exitPopup" class="overlay" style="display:none;">
        <div class="popup-box">
            <h2>Session Active</h2>
            <p>You are currently logged in. Would you like to continue your session or logout?</p>
            <div class="popup-actions">
                <button class="popup-btn btn-continue" onclick="closeExitPopup()">Continue</button>
                <button class="popup-btn btn-logout" onclick="handleLogout()">Logout</button>
            </div>
        </div>
    </div>

    <script>
        (function() {
            history.pushState(null, null, window.location.href);
            window.onpopstate = function(event) {
                const popup = document.getElementById('exitPopup');
                if (popup) { popup.style.display = 'flex'; }
                history.pushState(null, null, window.location.href);
            };
        })();

        function closeExitPopup() {
            document.getElementById('exitPopup').style.display = 'none';
        }
        // ===== SIDEBAR TOGGLE =====
    function toggleSidebar() {
        const sidebar = document.getElementById('digisim-sidebar');
        const overlay = document.getElementById('digisim-sidebarOverlay');
        if(sidebar && overlay) {
            sidebar.classList.toggle('active');
            overlay.classList.toggle('active');
        }
    }
    </script>
</body>
</html>
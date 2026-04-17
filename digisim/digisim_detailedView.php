<?php
session_start();
require_once "../include/coreDataconnect.php";

/* ================= GET SIM ID ================= */
$sim_id = (int)($_GET['game_id'] ?? 0);

if (!$sim_id) {
    die("Invalid Simulation ID");
}

/* ================= FETCH USER RESULT ================= */
$stmt = $conn->prepare("
    SELECT game_summary
    FROM tb_event_user_score
    WHERE mod_game_id = ?
    AND event_id = ?
    AND user_id = ?
    LIMIT 1
");
$stmt->bind_param("iii", $sim_id, $_SESSION['event_id'], $_SESSION['user_id']);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();

if (!$row || empty($row['game_summary'])) {
    die("Final game result not found");
}

$summary = json_decode($row['game_summary'], true);

/* ================= GET DIGISIM CONFIG ================= */
$stmt = $conn->prepare("
    SELECT di_scoretype_id, di_scoring_logic
    FROM mg5_digisim
    WHERE di_id = ?
");
$stmt->bind_param("i", $sim_id);
$stmt->execute();
$stmt->bind_result($scoreTypeId, $logicType);
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

while($r = $res->fetch_assoc()){
    $scoreMap[$r['stv_id']] = $r['stv_value'];
}
$stmt->close();

/* ================= FETCH RESPONSES ================= */
$stmt = $conn->prepare("
    SELECT dr_id, dr_tasks, dr_score_pkid
    FROM mg5_digisim_response
    WHERE dr_digisim_pkid = ?
");
$stmt->bind_param("i", $sim_id);
$stmt->execute();
$res = $stmt->get_result();

$responses = [];
while($r = $res->fetch_assoc()){
    $responses[$r['dr_id']] = $r;
}
$stmt->close();

/* ================= USER MAP ================= */
$userSelections = [];

foreach($summary as $bucket => $items){
    if($bucket == "game_id") continue;

    foreach($items as $t){
        $userSelections[$t['task_id']] = $t['score'];
    }
}

/* ================= SCORING FUNCTION ================= */
function calculateScore($user, $expert, $logic){

    if($expert == 0) return 0;

    if($logic == 1){
        return ($user >= $expert) ? 100 : 0;
    }

    if($logic == 2){
        return ($user == $expert) ? 100 : 0;
    }

    if($logic == 3){
        if($user == $expert) return 100;
        if($expert > $user) return round(($user / $expert) * 100);
        return round(($expert / $user) * 100);
    }

    return 0;
}

/* ================= BUILD METRICS ================= */
$metrics = [];

foreach($responses as $id => $r){

    $expertId = $r['dr_score_pkid'];
    $userId   = $userSelections[$id] ?? null;

    $expertVal = $scoreMap[$expertId] ?? 0;
    $userVal   = $userId ? ($scoreMap[$userId] ?? 0) : 0;

    $percent = ($userId !== null)
        ? calculateScore($userVal, $expertVal, $logicType)
        : 0;

    $metrics[] = [
        "task" => $r['dr_tasks'],
        "percent" => $percent,
        "expert" => $expertVal,
        "user" => $userVal
    ];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detailed Performance Metrics</title>
    <link rel="stylesheet" href="assets/fontawesome/css/all.min.css">
    <script src="https://kit.fontawesome.com/84b78c6f4c.js" crossorigin="anonymous"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap');

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Inter', sans-serif;
        }

        body {
            background-color: #f8fafc;
            color: #1e293b;
            padding: 20px;
            min-height: 100vh;
        }

        /* HEADER SECTION */
        .header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 5px;
            padding: 0 10px;
        }

        .back-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 44px;
            height: 44px;
            background-color: #ffffff;
            color: #64748b;
            border-radius: 50%;
            border: 1px solid #e2e8f0;
            text-decoration: none;
            font-size: 16px;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            flex-shrink: 0;
        }

        .back-btn:hover {
            background-color: #2563eb;
            color: #ffffff;
            border-color: #2563eb;
            transform: translateX(-4px);
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.2);
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .header h1 {
            font-size: 24px;
            font-weight: 800;
            color: #0f172a;
            letter-spacing: -0.5px;
        }

        .badge {
            background-color: #2563eb;
            color: #fff;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
        }

        .header-icons {
            display: flex;
            gap: 16px;
            align-items: center;
        }

        .icon-btn {
            color: #94a3b8;
            font-size: 18px;
            cursor: pointer;
            transition: color 0.2s;
            padding: 8px;
            border-radius: 8px;
        }

        .icon-btn:hover {
            color: #2563eb;
            background: #eff6ff;
        }

        /* GRID LAYOUT */
        /* GRID LAYOUT - Now with Scrollbar */
.metrics-grid {
    display: grid;
    grid-template-columns: repeat(5, 1fr);
    gap: 24px;
    /* margin-bottom: 40px; */
    
    /* Scroll Settings */
    max-height: 85vh; /* Adjust height as needed */
    overflow-y: auto;
    padding: 10px;    /* Prevents card shadows from being clipped */
    
    /* Custom Scrollbar Styling (Optional) */
    scrollbar-width: thin;
    scrollbar-color: #cbd5e1 transparent;
}

/* Custom Scrollbar for Chrome/Safari */
.metrics-grid::-webkit-scrollbar {
    width: 6px;
}
.metrics-grid::-webkit-scrollbar-track {
    background: transparent;
}
.metrics-grid::-webkit-scrollbar-thumb {
    background-color: #cbd5e1;
    border-radius: 20px;
}

/* Responsive */
@media (max-width: 1200px) {
    .metrics-grid { grid-template-columns: repeat(3, 1fr); }
}

@media (max-width: 900px) {
    .metrics-grid { grid-template-columns: repeat(2, 1fr); }
}

@media (max-width: 600px) {
    .metrics-grid { 
        grid-template-columns: 1fr; 
        max-height: none; /* Allow natural scroll on small mobile */
    }
}

        /* METRIC CARD */
        .metric-card {
            background: #ffffff;
            border-radius: 16px;
            padding: 5px 24px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.03);
            border: 1px solid #e2e8f0;
            display: flex;
            flex-direction: column;
            gap: 16px;
            transition: transform 0.2s, box-shadow 0.2s;
            height: auto;
            width: auto;
        }

        .metric-card.hidden {
            display: none;
        }

        .metric-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 24px rgba(0, 0, 0, 0.05);
        }

        .card-top {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
        }

        .icon-box {
            width: 44px;
            height: 44px;
            background-color: #eff6ff;
            color: #2563eb;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
        }

        .rating-box {
            text-align: right;
            position: relative;
        }

        .rating-val {
            font-size: 20px;
            font-weight: 800;
            color: #0f172a;
            line-height: 1;
            cursor: help;
            display: inline-block;
        }

        /* TOOLTIP STYLE */
        .rating-val:hover::after {
            content: attr(data-tooltip);
            position: absolute;
            right: 0;
            bottom: 30px;
            background: #1e293b;
            color: #fff;
            padding: 8px 12px;
            border-radius: 8px;
            font-size: 11px;
            font-weight: 500;
            white-space: nowrap;
            z-index: 10;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }

        .rating-val:hover::before {
            content: "";
            position: absolute;
            right: 15px;
            bottom: 24px;
            border: 6px solid transparent;
            border-top-color: #1e293b;
            z-index: 10;
        }

        .rating-label {
            font-size: 9px;
            font-weight: 700;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-top: 4px;
        }

        .card-body h3 {
            font-size: 16px;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 8px;
            line-height: 1.4;
        }

        .card-body p {
            font-size: 13px;
            color: #000;
            line-height: 1.7;
        }

        /* SHOW REMAINING BUTTON */
        .toggle-container {
            display: flex;
            justify-content: center;
            margin-top: 20px;
        }

        .toggle-btn {
            display: flex;
            align-items: center;
            gap: 10px;
            background: none;
            border: none;
            color: #2563eb;
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
            padding: 10px 20px;
            transition: opacity 0.2s;
        }

        .toggle-btn:hover {
            opacity: 0.8;
        }

        .toggle-btn i {
            font-size: 12px;
            transition: transform 0.3s;
        }

        .toggle-btn.expanded i {
            transform: rotate(180deg);
        }

        /* Responsive */
        @media (max-width: 768px) {
            body { padding: 20px; }
            .metrics-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>

<body>

    <div class="header">
        <div class="header-left">
            <a href="digisim_finalResult.php?game_id=<?php echo $sim_id; ?>" class="back-btn" title="Back to Results">
                <i class="fa-solid fa-arrow-left"></i>
            </a>
            <h1>Detailed Performance Metrics</h1>
            <span class="badge"><?php echo count($metrics); ?> Total</span>
        </div>
        <!-- Filter Button -->
        <!-- <div class="header-icons">
            <i class="fa-solid fa-filter icon-btn"></i>
            <i class="fa-solid fa-grip icon-btn"></i>
        </div> -->
    </div>

    <div class="metrics-grid" id="metricsGrid">
    <?php foreach ($metrics as $index => $m): ?>
        <div class="metric-card">
            <div class="card-top">
                <div>
                    
                </div>
                <div class="rating-box">
                    <div class="rating-val" data-tooltip="Your mark: <?php echo $m['user']; ?>, Expert: <?php echo $m['expert']; ?>">
                        <?php echo $m['user']; ?>/<?php echo $m['expert']; ?>
                    </div>
                    <div class="rating-label">Rating</div>
                </div>
            </div>
            <div class="card-body">
                <p><?php echo htmlspecialchars($m['task']); ?></p>
                
            </div>
        </div>
    <?php endforeach; ?>
</div>

    <!-- <?php if (count($metrics) > 6): ?>
        <div class="toggle-container">
            <button class="toggle-btn" id="toggleBtn" onclick="toggleMetrics()">
                Show Remaining <?php echo (count($metrics) - 6); ?> Metrics <i class="fa-solid fa-chevron-down"></i>
            </button>
        </div>
    <?php endif; ?> -->

    <!-- <script>
        function toggleMetrics() {
            const hiddenCards = document.querySelectorAll('.metric-card.hidden');
            const toggleBtn = document.getElementById('toggleBtn');
            const totalMetrics = <?php echo count($metrics); ?>;
            const remainingCount = totalMetrics - 6;

            const isExpanded = toggleBtn.classList.contains('expanded');

            if (isExpanded) {
                // Collapse
                const allCards = document.querySelectorAll('.metric-card');
                allCards.forEach((card, index) => {
                    if (index >= 6) {
                        card.style.display = 'none';
                        card.classList.add('hidden');
                    }
                });
                toggleBtn.classList.remove('expanded');
                toggleBtn.innerHTML = `Show Remaining ${remainingCount} Metrics <i class="fa-solid fa-chevron-down"></i>`;
            } else {
                // Expand
                hiddenCards.forEach(card => {
                    card.style.display = 'flex';
                    card.classList.remove('hidden');
                });
                toggleBtn.classList.add('expanded');
                toggleBtn.innerHTML = `Show Less <i class="fa-solid fa-chevron-down"></i>`;
            }
        }
    </script> -->

</body>
</html>
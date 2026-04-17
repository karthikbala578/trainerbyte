<?php
$pageTitle = 'Review Simulation';
$pageCSS = '/pages/page-styles/review_simulation.css';

require_once __DIR__ . '/../include/dataconnect.php';

$simId = isset($_GET['sim_id']) ? intval($_GET['sim_id']) : 0;

if ($simId <= 0) {
    header("Location: page-container.php?step=1");
    exit;
}

$simulation = null;

/* $stmt = $conn->prepare("
    SELECT *
    FROM mg5_digisim_userinput
    WHERE ui_id=? AND ui_team_pkid=?
"); */
$stmt = $conn->prepare("
    SELECT u.*, a.lg_name AS analysis_name
    FROM mg5_digisim_userinput u
    LEFT JOIN mg5_mdm_analysis a 
        ON u.ui_analysis_id = a.lg_id
    WHERE u.ui_id=? AND u.ui_team_pkid=?
");
$stmt->bind_param('ii', $simId, $_SESSION['team_id']);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: page-container.php?step=1");
    exit;
}

$simulation = $result->fetch_assoc();
$stmt->close();
// print_r($simulation);

$analysisName = $simulation['analysis_name'] ?? '';
$injects = !empty($simulation['ui_injects']) ? json_decode($simulation['ui_injects'], true) : [];
$scoreValues = !empty($simulation['ui_score_value']) ? json_decode($simulation['ui_score_value'], true) : [];

$scaleName = '';
if (!empty($simulation['ui_score_scale'])) {
    $scaleStmt = $conn->prepare("SELECT st_name FROM mg5_scoretype WHERE st_id=?");
    $scaleStmt->bind_param('i', $simulation['ui_score_scale']);
    $scaleStmt->execute();
    $res = $scaleStmt->get_result();
    if ($res->num_rows > 0) {
        $scaleName = $res->fetch_assoc()['st_name'];
    }
    $scaleStmt->close();
}

$priorityMap = [1 => 'Expert', 2 => 'Manual'];
$scoringLogicMap = [1 => 'At Least', 2 => 'Actual', 3 => 'Absolute'];
$scoringBasisMap = [1 => 'All', 2 => 'Part', 3 => 'Minimum'];
$totalBasisMap = [1 => 'All Tasks', 2 => 'Marked Tasks Only'];
$resultDisplayMap = [2 => 'Percentage', 3 => 'Raw Score', 4 => 'Legend'];

$priorityLabel = $priorityMap[$simulation['ui_priority_points']] ?? 'Not Set';
$scoringLogicLabel = $scoringLogicMap[$simulation['ui_scoring_logic']] ?? 'Not Set';
$scoringBasisLabel = $scoringBasisMap[$simulation['ui_scoring_basis']] ?? 'Not Set';
$totalBasisLabel = $totalBasisMap[$simulation['ui_total_basis']] ?? 'Not Set';
$resultDisplayLabel = $resultDisplayMap[$simulation['ui_result']] ?? 'Not Set';
?>

<!DOCTYPE html>

<html lang="en"><head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<title>Review &amp; Summary | SimArchitect Pro</title>
<!-- Fonts -->
<link href="https://fonts.googleapis.com" rel="preconnect"/>
<link crossorigin="" href="https://fonts.gstatic.com" rel="preconnect"/>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&amp;family=Manrope:wght@700;800&amp;display=swap" rel="stylesheet"/>
<!-- Icons -->
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" rel="stylesheet"/>
<style>
        :root {
            --primary: #003164;
            --primary-container: #00478d;
            --on-primary-container: #8db8ff;
            --surface-container-lowest: #ffffff;
            --surface-container-low: #f3f3f9;
            --surface-container: #ededf3;
            --surface-container-high: #e7e8ee;
            --surface-container-highest: #e2e2e8;
            --on-surface-variant: #424751;
            --on-background: #191c20;
            --background: #f9f9ff;
            --outline-variant: #c2c6d2;
            --error: #ba1a1a;
            --blue-800: #00478d;
            --blue-700: #1d4ed8;
            --blue-600: #2563eb;
            --blue-50: #eff6ff;
            --slate-50: #f8fafc;
            --slate-100: #f1f5f9;
            --slate-300: #cbd5e1;
            --slate-400: #94a3b8;
            --slate-500: #64748b;
            --slate-600: #475569;
        }

        * { box-sizing: border-box; }
        body { 
            margin: 0;
            font-family: 'Inter', sans-serif; 
            background-color: var(--background); 
            color: var(--on-background);
            /* min-height: 100vh; */
        }
        .font-manrope { font-family: 'Manrope', sans-serif; }
        .material-symbols-outlined {
            font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
            vertical-align: middle;
        }

        /* Layout */
        .main-container {
            /* max-width: 1400px; */
            /* margin: 0 auto;s */
            padding: 4.5rem 3rem 1rem 3rem;
            display: grid;
            grid-template-columns: 35% 65%;
            gap: 2rem;
            align-items: start;
            height: 80vh;
        }

        @media (max-width: 1024px) {
            .main-container {
                grid-template-columns: 1fr;
                padding: 2rem 1.5rem;
                gap: 2rem;
            }
        }

        /* Content Styling */
        .page-title { font-size: 1.5rem; line-height: 1.1; font-weight: 800; letter-spacing: -0.025em; color: var(--primary); margin-top: 0; }
        .page-subtitle { color: var(--on-surface-variant); font-size: 1rem; line-height: 1.5; }

        .image-card { border-radius: 0.75rem; overflow: hidden; box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05); background-color: var(--surface-container-lowest); margin-bottom: 1rem; }
        .image-card img { width: 100%; height: auto; display: block; }

        .stats-grid { display: grid; grid-template-columns: 1fr; gap: 1.5rem; }
        @media (min-width: 640px) { .stats-grid { grid-template-columns: repeat(2, 1fr); } }
        
        .stat-card-primary { background-color: var(--primary-container); padding: 1rem; border-radius: 0.75rem; display: flex; flex-direction: column; gap: 1rem; height: 100px; }
        .stat-card-neutral { background-color: var(--surface-container-high); padding: 1rem; border-radius: 0.75rem; display: flex; flex-direction: column; gap: 1rem; height: 100px; }
        .stat-label { font-size: 0.75rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; }
        .stat-value { font-size: 2rem; font-weight: 800; }

        .progress-card { background-color: var(--surface-container-low); padding: 1rem; border-radius: 0.75rem; }
        .progress-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: .5rem; }
        .progress-bar-bg { width: 100%; background-color: var(--surface-container-highest); border-radius: 9999px; height: 0.75rem; margin-bottom: .5rem; }
        .progress-bar-fill { height: 0.75rem; border-radius: 9999px; background: linear-gradient(to right, #1e3a8a, #3b82f6); }

        .summary{ display: flex; gap: 12rem; }
        .summary-card { background-color: var(--surface-container-lowest); padding: 1rem; border-radius: 0.75rem; box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05); border-left: 4px solid var(--blue-600); margin-bottom: 2rem; }
        .summary-card-alt { background-color: var(--surface-container-lowest); padding: 1rem; border-radius: 0.75rem; box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05); border-left: 4px solid var(--slate-300); margin-bottom: 2rem; }
        .summary-title { font-size: 1.25rem; font-weight: 800; margin-bottom: .5rem; display: flex; align-items: center; gap: 0.5rem; margin-top: 0; }
        .item { display: flex; gap: 20rem; margin-bottom: 1rem; }
        .summary-item { display: flex; flex-direction: column; gap: 0.25rem; margin-bottom: 1.5rem; width: 250px;}
        .summary-item:last-child { margin-bottom: 0; }
        .value { font-size: 15px; color: var(--on-background); font-weight: 500; }
        .summary-label { font-size: 12px; text-transform: uppercase; letter-spacing: 0.1em; color: var(--on-surface-variant); font-weight: 700; }
        .summary-value { display: flex; font-size: 15px; color: var(--on-background); font-weight: 500; }
        .badge { padding: 0.25rem 0.75rem; background-color: var(--blue-50); color: var(--blue-800); border-radius: 9999px; font-size: 0.875rem; font-weight: 700; }

        /* Modal Overlay */
#genModal {
    position: fixed;
    inset: 0;
    background: rgba(15, 23, 42, 0.6); /* Slate-900 with opacity */
    backdrop-filter: blur(4px);
    display: none; /* Hidden by default */
    align-items: center;
    justify-content: center;
    z-index: 9999;
}

/* Modal Content Card */
.modal-card {
    background: #ffffff;
    border-radius: 24px;
    width: 90%;
    max-width: 600px;
    padding: 40px;
    position: relative;
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
}

/* Floating Blue Icon */
.modal-icon-top {
    position: absolute;
    top: -24px;
    left: 40px;
    background: #2563eb;
    border: 4px solid #fff;
    border-radius: 50%;
    width: 48px;
    height: 48px;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
}

/* Action Area States */
.action-area {
    transition: all 0.5s ease;
}

.action-area.loading {
    opacity: 0.3;
    pointer-events: none;
    filter: grayscale(1);
}

/* Grid for buttons */
.modal-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
    margin-bottom: 16px;
}

.modal-option {
    background: #e7e7e7;
    border: 1px solid #f1f5f9;
    border-radius: 20px;
    padding: 24px;
    text-decoration: none;
    transition: all 0.2s;
}

.modal-option:hover {
    background: #eff6ff;
    border-color: #bfdbfe;
}

/* Spinner Animation */
.spinner {
    width: 24px;
    height: 24px;
    border: 3px solid rgba(255, 255, 255, 0.3);
    border-top-color: #fff;
    border-radius: 50%;
    animation: spin 1s linear infinite;
    margin-top: 12px;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

@media (max-width: 600px) {
    .modal-grid { grid-template-columns: 1fr; }
}
    </style>
</head>
<body>
    <?php include 'stepper.php'; ?>
<main class="main-container">
    <!-- Left Column (35%): Review & Summary part -->
    <div class="col-left">

        <div class="image-card">
            <img alt="Modern Skyscraper" src="images/final.png"/>
        </div>
        <div>
            <h1 class="page-title font-manrope">Review &amp; Summary</h1>
            <p class="page-subtitle">Please review your simulation configuration before generating content.</p>
        </div>
        <!-- <div class="timestamp">
        <span class="material-symbols-outlined" style="font-size: 0.875rem;">update</span>
        <span>Auto-saved today at 14:22 PM</span>
        </div> -->
    </div>
    <!-- Right Column (65%): Simulation Context and Processing Settings -->
    <div class="col-right">
        <div class="summary-card">
            <h2 class="summary-title font-manrope">
            <span class="material-symbols-outlined" style="color: var(--blue-700);">dashboard</span>
                            Simulation Context
            </h2>
            <div class="summary">
                <div class="summary-item">
                    <span class="summary-label">Title</span>
                    <span class="value"><?= htmlspecialchars($simulation['ui_sim_title']) ?></span>
                </div>
                <div class="summary-item">
                    <span class="summary-label">Industry</span>
                    <span class="value"><?= htmlspecialchars($simulation['ui_industry_type']) ?></span>
                </div>
            </div>
            <div class="summary">
                <div class="summary-item">
                    <span class="summary-label">Geography</span>
                    <span class="value"><?= htmlspecialchars($simulation['ui_geography']) ?></span>
                </div>
                <div class="summary-item">
                    <span class="summary-label">Scale</span>
                    <span class="value"><?= htmlspecialchars($simulation['ui_operating_scale']) ?></span>
                </div>
            </div>
            <div class="summary">
                <div class="summary-item">
                    <span class="summary-label">Language</span>
                    <span class="value"><?= htmlspecialchars($simulation['ui_lang']) ?></span>
                </div>
                <div class="summary-item">
                    <span class="summary-label">Scenario</span>
                    <span class="value"><?= htmlspecialchars($simulation['ui_scenario']) ?></span>
                </div>
            </div>
            <div class="summary">
                <div class="summary-item">
                    <span class="summary-label">Objective</span>
                    <span class="value"><?= htmlspecialchars($simulation['ui_objective']) ?></span>
                </div>
            </div>
        </div>
        <div class="summary-card-alt">
            <h2 class="summary-title font-manrope">
            <span class="material-symbols-outlined" style="color: var(--blue-700);">settings_suggest</span>
                            Configuration Summary
                        </h2>
            <div style="display: flex; gap: 25rem; align-items: center; margin-bottom: .5rem;">
                <span class="summary-label">Injects</span>
                <span class="summary-value"><?php foreach ($injects as $k => $v): if ($k != "total"): ?>
                                            <div class="chip"><?= ucfirst($k) ?>: <?= $v ?></div>
                                    <?php endif;
                                    endforeach; ?></span>
            </div>
            <div style="display: flex; gap: 25rem; align-items: center; margin-bottom: .5rem;">
                <span class="summary-label">Response Scale</span>
                <span class="summary-value"><?php foreach ($scoreValues as $label => $count): ?>
                                        <div class="scale-chip"><?= ucfirst($label) ?>: <?= $count ?></div>
                                    <?php endforeach; ?></span>
            </div>
        </div>
    </div>
</main>
<!-- Full Overlay Modal -->
<div id="genModal">
    <div class="modal-card">
        <div class="modal-icon-top">
            <div id="modalSpinner" class="spinner"></div>
            <span id="modalCheck" class="material-symbols-outlined" style="color:white; display:none;">check</span>
        </div>

        <div style="margin-bottom: 30px;">
            <h2 id="modalHeading" style="font-size: 24px; font-weight: 800; margin: 0;">Generating Your Content</h2>
            <p id="modalSubtext" style="color: #2563eb; font-weight: 500; margin-top: 8px;">We’re analyzing your requirements</p>
            <p id="processingLabel" style="color: #94a3b8; font-size: 13px; font-style: italic;">Processing...</p>
        </div>

        <div id="actionArea" class="action-area loading">
            <div class="modal-grid">
                <a id="reviewLink" href="#" class="modal-option">
                    <div style="background:#dbeafe; color:#2563eb; width:36px; height:36px; border-radius:50%; display:flex; align-items:center; justify-content:center; margin-bottom:12px;">
                        <span class="material-symbols-outlined" style="font-size:20px;">edit_note</span>
                    </div>
                    <strong style="display:block; color:#1e293b;">Review & Edit Content</strong>
                    <small style="color:#64748b;">Refine and make modifications.</small>
                </a>

                <a id="previewLink" href="#" class="modal-option">
                    <div style="background:#dbeafe; color:#2563eb; width:36px; height:36px; border-radius:50%; display:flex; align-items:center; justify-content:center; margin-bottom:12px;">
                        <span class="material-symbols-outlined" style="font-size:20px;">visibility</span>
                    </div>
                    <strong style="display:block; color:#1e293b;">Preview Experience</strong>
                    <small style="color:#64748b;">View final end-user format.</small>
                </a>
            </div>

            <a href="../library.php" class="modal-option" style="display:flex; align-items:center; justify-content:space-between;">
                <div style="display:flex; align-items:center; gap:12px;">
                    <div style="background:#ffedd5; color:#c2410c; width:40px; height:40px; border-radius:12px; display:flex; align-items:center; justify-content:center;">
                        <span class="material-symbols-outlined">library_books</span>
                    </div>
                    <div>
                        <strong style="display:block; color:#1e293b;">Return to Library</strong>
                        <small style="color:#64748b;">Manage other assets.</small>
                    </div>
                </div>
                <span class="material-symbols-outlined" style="color:#cbd5e1;">chevron_right</span>
            </a>
        </div>
    </div>
</div>
<script>
    async function handleGeneration(event, simId) {
    if (event) event.preventDefault();

    const modal = document.getElementById('genModal');
    const subtext = document.getElementById('modalSubtext');
    const actionArea = document.getElementById('actionArea');
    const heading = document.getElementById('modalHeading');
    const spinner = document.getElementById('modalSpinner');
    const check = document.getElementById('modalCheck');
    const processingLabel = document.getElementById('processingLabel');

    // 1. Show Modal
    modal.style.display = 'flex';

    // 2. Set up the 10-second message rotation
    const steps = [
        "We’re analyzing your requirements",
        "Matching patterns from similar scenarios",
        "Designing structured outputs",
        "Validating for quality and relevance"
    ];
    
    let currentStep = 0;
    const interval = setInterval(() => {
        if (currentStep < steps.length - 1) {
            currentStep++;
            subtext.innerText = steps[currentStep];
        }
    }, 10000);

    try {
        // 3. Perform the actual generation
        const response = await fetch(`../test_generate.php?sim_id=${simId}`, { method: 'POST' });
        const data = await response.json();

        if (data.success) {
            clearInterval(interval);

            // 4. Update UI to Success
            heading.innerText = "Content Generated Successfully!";
            subtext.innerText = "Your new asset is ready. Choose your next step.";
            subtext.style.color = "#64748b"; // Change from blue to gray
            processingLabel.style.display = 'none';

            // Swap icon
            spinner.style.display = 'none';
            check.style.display = 'block';

            // Unlock cards
            actionArea.classList.remove('loading');
            
            // Set links
            document.getElementById('reviewLink').href = `../manual/manual_page_container.php?step=1&digisim_id=${data.digisim_id}`;
            document.getElementById('previewLink').href = `#`;
        }
    } catch (e) {
        clearInterval(interval);
        alert("Generation failed. Please try again.");
        modal.style.display = 'none';
    }
}
</script>
</body>
</html>
<?php
// review_simulation.php - Step 4: Review all simulation settings
// NO <head> or <body> - handled by layout/header.php
// Uses mg5_ms_userinput_master and mg5_ms_stage_input tables

$pageTitle = 'Review Simulation';
require_once __DIR__ . '/../include/dataconnect.php';

$simId = isset($_GET['sim_id']) ? intval($_GET['sim_id']) : 0;

if ($simId <= 0) {
    header("Location: multistagedigisim.php?step=1");
    exit;
}

// Load master simulation data
$simulation = null;
$stmt = $conn->prepare("SELECT * FROM mg5_ms_userinput_master WHERE ui_id = ? AND ui_team_pkid = ? LIMIT 1");
$stmt->bind_param('ii', $simId, $_SESSION['team_id']);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: multistagedigisim.php?step=1");
    exit;
}
$simulation = $result->fetch_assoc();
$stmt->close();

$noStages = $simulation['ui_no_stages'] ?? 1;

// Load all stage data
$stages = [];
$stmt = $conn->prepare("SELECT * FROM mg5_ms_stage_input WHERE st_userinput_pkid = ? ORDER BY st_stage_num ASC");
$stmt->bind_param('i', $simId);
$stmt->execute();
$stagesResult = $stmt->get_result();
while ($row = $stagesResult->fetch_assoc()) {
    $stageNum = $row['st_stage_num'];
    $stages[$stageNum] = [
        'id' => $row['st_id'],
        'name' => $row['st_name'],
        'desc' => $row['st_desc'],
        'scenario' => $row['st_scenario'],
        'objective' => $row['st_objective'],
        'injects' => !empty($row['st_injects']) ? json_decode($row['st_injects'], true) : [],
        'score_scale' => $row['st_score_scale'],
        'score_value' => !empty($row['st_score_value']) ? json_decode($row['st_score_value'], true) : []
    ];
}
$stmt->close();

// Processing configuration label maps
$priorityMap = [1 => 'Expert', 2 => 'Manual'];
$scoringLogicMap = [1 => 'At Least', 2 => 'Actual', 3 => 'Absolute'];
$scoringBasisMap = [1 => 'All', 2 => 'Part', 3 => 'Minimum'];
$totalBasisMap = [1 => 'All Tasks', 2 => 'Marked Tasks Only'];
$resultDisplayMap = [1 => 'None', 2 => 'Percentage', 3 => 'Raw Score', 4 => 'Legend'];

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
<title>Review Simulation</title>
<link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
<style>
/* Base Variables & Reset */
:root {
    --primary: #3a6095;
    --primary-container: #9ec2fe;
    --secondary: #466370;
    --background: #f7f9fb;
    --surface-container-low: #f0f4f7;
    --surface-container-lowest: #ffffff;
    --on-surface: #2c3437;
    --on-surface-variant: #596064;
    --outline: #747c80;
    --outline-variant: #acb3b7;
    --font-headline: 'Manrope', sans-serif;
    --font-body: 'Inter', sans-serif;
}
* { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: var(--font-body); background-color: var(--background); color: var(--on-surface); }
.material-symbols-outlined { vertical-align: middle; font-size: 20px; }

/* Layout */
.main-content { padding: 5rem 3rem 1rem 3rem; }
.content-grid { display: flex; flex-direction: column; gap: 2rem; }
@media (min-width: 1024px) {
    .content-grid { flex-direction: row; align-items: flex-start; }
    .editorial-col { flex: 0 0 35%; max-width: 35%; position: sticky; top: 6rem; }
    .form-col { flex: 0 0 65%; max-width: 65%; }
}

/* Editorial Col */
.editorial-col { display: flex; flex-direction: column; gap: 1rem; }
.hero-image-container { position: relative; overflow: hidden; border-radius: 20px; width: 100%; }
.hero-image { width: 100%; object-fit: cover; filter: grayscale(20%) contrast(110%); }
.image-overlay { position: absolute; inset: 0; background-color: var(--primary); opacity: 0.1; mix-blend-mode: multiply; }
.editorial-text h1 { font-size: 1.5rem; font-weight: 800; line-height: 1.2; margin-bottom: 1rem; color: var(--on-surface); }
.editorial-text p { font-size: 1.125rem; color: var(--on-surface-variant); line-height: 1.6; }

/* Form Col */
.form-card {
    background-color: var(--surface-container-low); padding: 2rem 2.5rem; border-radius: 2rem;
    max-height: 80vh; overflow-y: auto; scrollbar-width: thin; scrollbar-color: var(--outline-variant) transparent;
}
.form-card::-webkit-scrollbar { width: 6px; }
.form-card::-webkit-scrollbar-track { background: transparent; margin: 20px; }
.form-card::-webkit-scrollbar-thumb { background: var(--outline-variant); border-radius: 10px; }

/* Review specific UI */
.review-section { margin-bottom: 2rem; padding-bottom: 2rem; border-bottom: 2px dashed var(--outline-variant); }
.review-section:last-of-type { border-bottom: none; }

.review-section h2 { font-size: 1.25rem; font-weight: 700; color: var(--primary); margin-bottom: 1.25rem; }
.info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1.25rem; }
.info-item label { font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em; color: var(--on-surface-variant); font-weight: 600; display: block; margin-bottom: 4px; }
.info-item p { font-size: 1rem; color: var(--on-surface); font-weight: 500; }

.stage-review-card { background: #fff; border-radius: 1rem; padding: 1.5rem; margin-bottom: 1.5rem; border: 1px solid #e2e8f0; }
.stage-review-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; padding-bottom: 0.75rem; border-bottom: 1px solid #f1f5f9; }
.stage-review-header h3 { font-size: 1.1rem; color: #1e293b; }
.stage-status { font-size: 0.75rem; font-weight: 600; padding: 4px 8px; border-radius: 4px; background: #dcfce7; color: #166534; }

.stage-detail { margin-bottom: 1rem; }
.stage-detail label { font-size: 0.8rem; font-weight: 600; color: #64748b; margin-bottom: 5px; display: block; }
.text-block { background: #f8fafc; padding: 10px; border-radius: 6px; font-size: 0.9rem; color: #334155; }

.inject-list, .score-preview { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 5px; }
.inject-item, .score-item { background: #eff6ff; color: #1e40af; padding: 4px 10px; border-radius: 12px; font-size: 0.8rem; font-weight: 500; }
.inject-total { display: inline-block; margin-bottom: 8px; font-weight: 600; color: #0f172a; }

.text-muted { color: #94a3b8; font-style: italic; }

.form-actions { margin-top: 2rem; display: flex; gap: 1rem; justify-content: flex-end; }
.btn-secondary { padding: 12px 20px; border-radius: 8px; font-weight: 600; text-decoration: none; background: #e2e8f0; color: #475569; }
.btn-primary { padding: 12px 24px; border-radius: 8px; font-weight: 600; border: none; cursor: pointer; background: #2563eb; color: #fff; }

/* Processing Overlay — matches finish.php style */
#genModal {
    position: fixed; inset: 0;
    background: rgba(15, 23, 42, 0.65);
    backdrop-filter: blur(4px);
    display: none;
    align-items: center;
    justify-content: center;
    z-index: 9999;
}
.modal-card {
    background: #ffffff; border-radius: 24px;
    width: 90%; max-width: 560px;
    padding: 48px 40px 40px;
    position: relative;
    box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25);
}
.modal-icon-top {
    position: absolute; top: -24px; left: 40px;
    background: #2563eb; border: 4px solid #fff;
    border-radius: 50%; width: 48px; height: 48px;
    display: flex; align-items: center; justify-content: center;
    box-shadow: 0 10px 15px -3px rgba(0,0,0,0.15);
}
.modal-spinner {
    width: 22px; height: 22px;
    border: 3px solid rgba(255,255,255,0.35);
    border-top-color: #fff;
    border-radius: 50%;
    animation: spin 0.9s linear infinite;
}
@keyframes spin { to { transform: rotate(360deg); } }
.modal-heading { font-size: 1.5rem; font-weight: 800; color: #0f172a; margin: 0 0 6px; }
.modal-subtext { color: #2563eb; font-weight: 500; font-size: 0.95rem; margin: 0 0 4px; }
.modal-hint { color: #94a3b8; font-size: 0.8rem; font-style: italic; margin: 0; }

/* The generate button itself */
.generate-btn {
    display: inline-flex; align-items: center; gap: 10px;
    background: linear-gradient(135deg, #1e40af 0%, #2563eb 100%);
    color: #fff; border: none; border-radius: 14px;
    padding: 14px 32px; font-size: 1rem; font-weight: 700;
    cursor: pointer; transition: all 0.2s;
    box-shadow: 0 4px 14px rgba(37,99,235,0.35);
    font-family: inherit;
}
.generate-btn:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(37,99,235,0.4); }
.generate-btn:active { transform: translateY(0); }
.generate-btn .material-symbols-outlined { font-size: 20px; }
</style>
</head>
<body>

<?php include 'ms_stepper.php'; ?>

<main class="main-content">
    <div class="content-grid">
        <div class="editorial-col">
            <div class="hero-image-container">
                <img class="hero-image" src="../pages/images/final.png" alt="Context">
                <div class="image-overlay"></div>
            </div>
            <div class="editorial-text">
                <h1>Review Simulation</h1>
                <p>Confirm your multistage simulation configuration. Once verified, simply generate the simulation to bring it to life!</p>
            </div>
        </div>

        <div class="form-col">
            <div class="form-card">
                
                <!-- Basic Information Section -->
                <section class="review-section">
                    <h2>Basic Information</h2>
                    <div class="info-grid">
                        <div class="info-item">
                            <label>Simulation Title</label>
                            <p><?= htmlspecialchars($simulation['ui_sim_title']) ?></p>
                        </div>
                        <div class="info-item">
                            <label>Description</label>
                            <p><?= nl2br(htmlspecialchars($simulation['ui_sim_desc'])) ?: '<em class="text-muted">Not provided</em>' ?></p>
                        </div>
                        <div class="info-item">
                            <label>Industry Type</label>
                            <p><?= htmlspecialchars($simulation['ui_industry_type']) ?></p>
                        </div>
                        <div class="info-item">
                            <label>Geography</label>
                            <p><?= htmlspecialchars($simulation['ui_geography'] ?: 'Global') ?></p>
                        </div>
                        <div class="info-item">
                            <label>Operating Scale</label>
                            <p><?= htmlspecialchars($simulation['ui_operating_scale'] ?: 'Standard') ?></p>
                        </div>
                        <div class="info-item">
                            <label>Language</label>
                            <p><?= htmlspecialchars($simulation['ui_lang'] ?? 'English') ?></p>
                        </div>
                    </div>
                </section>

                <!-- Processing Configuration -->
                <section class="review-section">
                    <h2>Processing Configuration</h2>
                    <div class="info-grid">
                        <div class="info-item">
                            <label>Priority Points</label>
                            <p><?= $priorityLabel ?></p>
                        </div>
                        <div class="info-item">
                            <label>Scoring Logic</label>
                            <p><?= $scoringLogicLabel ?></p>
                        </div>
                        <div class="info-item">
                            <label>Scoring Basis</label>
                            <p><?= $scoringBasisLabel ?></p>
                        </div>
                        <div class="info-item">
                            <label>Total Basis</label>
                            <p><?= $totalBasisLabel ?></p>
                        </div>
                        <div class="info-item">
                            <label>Result Display</label>
                            <p><?= $resultDisplayLabel ?></p>
                        </div>
                    </div>
                </section>

                <!-- Stages Review -->
                <section class="review-section" style="border-bottom:none;">
                    <h2>Stages Configuration</h2>
                    <div class="stages-review">
                        <?php for ($i = 1; $i <= $noStages; $i++): 
                            $stage = $stages[$i] ?? null;
                        ?>
                        <div class="stage-review-card">
                            <div class="stage-review-header">
                                <h3>Stage <?= $i ?>: <?= htmlspecialchars($stage['name'] ?? "Stage $i") ?></h3>
                                <span class="stage-status">Configured</span>
                            </div>
                            
                            <div class="stage-review-content">
                                <?php if ($stage): ?>
                                    <div class="stage-detail">
                                        <label>Description</label>
                                        <div class="text-block"><?= nl2br(htmlspecialchars($stage['desc'])) ?: '<em class="text-muted">Not provided</em>' ?></div>
                                    </div>
                                    <div class="stage-detail">
                                        <label>Scenario</label>
                                        <div class="text-block"><?= nl2br(htmlspecialchars($stage['scenario'])) ?: '<em class="text-muted">Not provided</em>' ?></div>
                                    </div>
                                    <div class="stage-detail">
                                        <label>Objectives</label>
                                        <div class="text-block"><?= nl2br(htmlspecialchars($stage['objective'])) ?: '<em class="text-muted">Not provided</em>' ?></div>
                                    </div>
                                    
                                    <div class="stage-detail">
                                        <label>Inject Distribution</label>
                                        <?php if (!empty($stage['injects']) && ($stage['injects']['total'] ?? 0) > 0): ?>
                                            <div class="inject-preview">
                                                <div class="inject-list">
                                                    <?php foreach ($stage['injects'] as $key => $val): 
                                                        if ($key !== 'total' && $val > 0):
                                                    ?>
                                                        <span class="inject-item"><?= ucfirst(str_replace('_', ' ', $key)) ?>: <?= $val ?></span>
                                                    <?php endif; endforeach; ?>
                                                </div>
                                            </div>
                                        <?php else: ?>
                                            <p class="text-muted">No injects configured</p>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="stage-detail">
                                        <label>Scoring Setup</label>
                                        <?php if (!empty($stage['score_scale'])): 
                                            $stageScaleName = '';
                                            $sStmt = $conn->prepare("SELECT st_name FROM mg5_scoretype WHERE st_id = ?");
                                            $sStmt->bind_param('i', $stage['score_scale']);
                                            $sStmt->execute();
                                            $sRes = $sStmt->get_result();
                                            if ($sRes->num_rows > 0) $stageScaleName = $sRes->fetch_assoc()['st_name'];
                                            $sStmt->close();
                                        ?>
                                            <p style="font-size:0.9rem; font-weight:600; margin-bottom:5px;">Scale: <?= htmlspecialchars($stageScaleName) ?></p>
                                            <?php if (!empty($stage['score_value']) && array_sum($stage['score_value']) > 0): ?>
                                                <div class="score-preview">
                                                    <?php foreach ($stage['score_value'] as $key => $val): if ($val > 0): ?>
                                                        <span class="score-item"><?= htmlspecialchars($key) ?>: <?= $val ?></span>
                                                    <?php endif; endforeach; ?>
                                                </div>
                                            <?php else: ?>
                                                <p class="text-muted">No score values configured</p>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <p class="text-muted">No scoring configured</p>
                                        <?php endif; ?>
                                    </div>
                                    
                                <?php else: ?>
                                    <p class="text-muted">Stage not configured</p>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endfor; ?>
                    </div>
                </section>

                <div class="form-actions">
                    <button type="button" class="generate-btn" onclick="startGeneration()">
                        <span class="material-symbols-outlined">auto_awesome</span>
                        Generate Simulation
                    </button>
                </div>
            </div>
        </div>
</div>
</main>

<!-- Full-Screen Generation Modal -->
<div id="genModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(15,23,42,0.6); backdrop-filter:blur(4px); -webkit-backdrop-filter:blur(4px); z-index:9999; align-items:center; justify-content:center;">
    <div class="modal-card" style="background:#fff; padding:48px 40px 40px; border-radius:24px; box-shadow:0 25px 50px -12px rgba(0,0,0,0.3); text-align:left; position:relative; max-width:480px; width:90%;">
        <div class="modal-icon-top">
            <div class="modal-spinner" id="modalSpinner"></div>
        </div>
        <p class="modal-heading" id="modalHeading">Generating Your Simulation</p>
        <p class="modal-subtext" id="modalSubtext">Analysing your requirements...</p>
        <p class="modal-hint" id="modalHint">This may take a while. Please don't close this tab.</p>
    </div>
</div>

<!-- Hidden form that actually submits -->
<form id="generateForm" method="POST" action="ms_generate.php?sim_id=<?= $simId ?>" style="display:none;"></form>

<script>
const messages = [
    "Analysing your requirements...",
    "Matching patterns from similar scenarios...",
    "Designing structured outputs for each stage...",
    "Validating for quality and relevance...",
    "Almost there — applying finishing touches..."
];

function startGeneration() {
    // Show modal
    const modal = document.getElementById('genModal');
    modal.style.display = 'flex';

    // Rotate subtext messages every 10 seconds
    let idx = 0;
    const subtextEl = document.getElementById('modalSubtext');
    setInterval(() => {
        idx = (idx + 1) % messages.length;
        subtextEl.style.opacity = '0';
        setTimeout(() => {
            subtextEl.textContent = messages[idx];
            subtextEl.style.opacity = '1';
        }, 300);
    }, 10000);

    subtextEl.style.transition = 'opacity 0.3s ease';

    // Submit the real form
    document.getElementById('generateForm').submit();
}
</script>
</body>
</html>
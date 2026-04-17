<?php
// stage_builder.php - Step 2: One stage per page
$pageTitle = 'Stage configuration';
require_once __DIR__ . '/../include/dataconnect.php';

$simId = isset($_GET['sim_id']) ? intval($_GET['sim_id']) : 0;
$currentStage = isset($_GET['stage']) ? intval($_GET['stage']) : 1;

if ($simId <= 0) {
    header("Location: multistagedigisim.php?step=1");
    exit;
}

// Check master simulation
$stmt = $conn->prepare("SELECT ui_no_stages FROM mg5_ms_userinput_master WHERE ui_id = ? AND ui_team_pkid = ? LIMIT 1");
$stmt->bind_param("ii", $simId, $_SESSION['team_id']);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows == 0) {
    echo "Simulation not found."; exit;
}
$noStages = $result->fetch_assoc()['ui_no_stages'];
$stmt->close();

if ($currentStage < 1 || $currentStage > $noStages) {
    header("Location: multistagedigisim.php?step=2&sim_id=$simId&stage=1");
    exit;
}

// Fetch basic dictionaries
$injectTypes = [];
$stmt = $conn->prepare("SELECT in_id, in_name, in_description FROM mg5_inject_master WHERE in_status = 1 ORDER BY in_name ASC");
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) { $injectTypes[] = $row; }
$stmt->close();

$scoreTypes = [];
$stmt = $conn->prepare("SELECT st_id, st_name FROM mg5_scoretype ORDER BY st_name ASC");
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) { $scoreTypes[] = $row; }
$stmt->close();

$allComponents = [];
$stmt = $conn->prepare("SELECT stv_id, stv_name, stv_value, stv_color, stv_scoretype_pkid FROM mg5_scoretype_value ORDER BY stv_value ASC");
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    $allComponents[$row['stv_scoretype_pkid']][] = $row;
}
$stmt->close();

// Handle form submission
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $stName = trim($_POST['st_name'] ?? '');
    $stDesc = trim($_POST['st_desc'] ?? '');
    $stScenario = trim($_POST['st_scenario'] ?? '');
    $stObj = trim($_POST['st_objective'] ?? '');
    
    // Injects processing
    $injectsArr = [];
    $totalInjects = 0;
    if (isset($_POST['injects']) && is_array($_POST['injects'])) {
        foreach ($_POST['injects'] as $key => $val) {
            $val = intval($val);
            if ($val > 0) {
                $injectsArr[$key] = $val;
                $totalInjects += $val;
            }
        }
    }
    $injectsArr['total'] = $totalInjects;
    $injectsJson = json_encode($injectsArr);
    
    // Scoring processing
    $scaleId = intval($_POST['score_scale'] ?? 0);
    $scoreValuesArr = [];
    if ($scaleId > 0 && isset($_POST['scores'][$scaleId]) && is_array($_POST['scores'][$scaleId])) {
        foreach ($_POST['scores'][$scaleId] as $compName => $val) {
            $val = intval($val);
            if ($val > 0) {
                $scoreValuesArr[$compName] = $val;
            }
        }
    }
    $scoreValuesJson = json_encode($scoreValuesArr);
    
    // DB check and upsert
    $chk = $conn->prepare("SELECT st_id FROM mg5_ms_stage_input WHERE st_userinput_pkid=? AND st_stage_num=?");
    $chk->bind_param("ii", $simId, $currentStage);
    $chk->execute();
    $chkRes = $chk->get_result();
    $existingId = $chkRes->num_rows > 0 ? $chkRes->fetch_assoc()['st_id'] : null;
    $chk->close();
    
    if ($existingId) {
        $stmt = $conn->prepare("UPDATE mg5_ms_stage_input SET 
            st_name=?, st_desc=?, st_scenario=?, st_objective=?, st_injects=?, st_score_scale=?, st_score_value=? 
            WHERE st_id=?");
        $stmt->bind_param("sssssisi", $stName, $stDesc, $stScenario, $stObj, $injectsJson, $scaleId, $scoreValuesJson, $existingId);
        $stmt->execute();
        $stmt->close();
    } else {
        $stmt = $conn->prepare("INSERT INTO mg5_ms_stage_input 
            (st_userinput_pkid, st_stage_num, st_name, st_desc, st_scenario, st_objective, st_injects, st_score_scale, st_score_value) 
            VALUES (?,?,?,?,?,?,?,?,?)");
        $stmt->bind_param("iisssssis", $simId, $currentStage, $stName, $stDesc, $stScenario, $stObj, $injectsJson, $scaleId, $scoreValuesJson);
        $stmt->execute();
        $stmt->close();
    }
    
    // Redirect logic
    if ($currentStage < $noStages) {
        header("Location: multistagedigisim.php?step=2&sim_id=$simId&stage=" . ($currentStage + 1));
    } else {
        $stmt = $conn->prepare("UPDATE mg5_ms_userinput_master SET ui_cur_stage = 3 WHERE ui_id = ?");
        $stmt->bind_param("i", $simId);
        $stmt->execute();
        $stmt->close();
        header("Location: multistagedigisim.php?step=3&sim_id=$simId");
    }
    exit;
}

// Load existing stage data for form display
$stName = "Stage $currentStage";
$stDesc = $stScenario = $stObj = '';
$savedInjects = [];
$savedScale = 0;
$savedScores = [];

$stmt = $conn->prepare("SELECT * FROM mg5_ms_stage_input WHERE st_userinput_pkid = ? AND st_stage_num = ? LIMIT 1");
$stmt->bind_param("ii", $simId, $currentStage);
$stmt->execute();
$res = $stmt->get_result();
if ($res->num_rows > 0) {
    $row = $res->fetch_assoc();
    $stName = $row['st_name'];
    $stDesc = $row['st_desc'];
    $stScenario = $row['st_scenario'];
    $stObj = $row['st_objective'];
    $savedInjects = json_decode($row['st_injects'] ?? '{}', true);
    $savedScale = intval($row['st_score_scale']);
    $savedScores = json_decode($row['st_score_value'] ?? '{}', true);
}
$stmt->close();

?>
<!DOCTYPE html>
<html lang="en"><head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<title>Stage <?= $currentStage ?> Configuration</title>
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

.form-stack { display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; }
.full-width { grid-column: span 2; }
.form-group { display: flex; flex-direction: column; gap: 0.5rem; }
.field-label { font-size: 0.875rem; font-weight: 600; color: var(--on-surface-variant); display: flex; align-items: center; gap: 5px; }

.text-input, .textarea-input {
    width: 100%; background: var(--surface-container-lowest); border: none; border-radius: 0.75rem; 
    padding: 1rem; font-family: inherit; font-size: 1rem; color: var(--on-surface);
}
.text-input:focus, .textarea-input:focus { outline: 2px solid var(--primary-container); }
.textarea-input { height: 110px; resize: vertical; }

/* ── Tooltip System ── */
.label-row { display: flex; justify-content: space-between; align-items: center; }
.info-tooltip-wrapper {
    position: relative; display: inline-flex; align-items: center; cursor: help;
}
.info-icon {
    width: 18px; height: 18px; background-color: #cbd5e1; color: #475569;
    border-radius: 50%; display: flex; align-items: center; justify-content: center;
    font-size: 11px; font-weight: 700; font-style: normal; font-family: Georgia, serif;
    line-height: 1; transition: background-color 0.2s, color 0.2s; user-select: none;
}
.info-tooltip-wrapper:hover .info-icon { background-color: #3a6095; color: #fff; }
.tooltip-content {
    visibility: hidden; opacity: 0; pointer-events: none;
    position: absolute; top: calc(100% + 8px); right: 0;
    min-width: 140px; max-width: 260px; width: max-content;
    background-color: #1e293b; color: #e2e8f0;
    padding: 9px 13px; border-radius: 8px;
    font-size: 0.75rem; line-height: 1.55; z-index: 9999;
    white-space: normal; word-break: break-word;
    box-shadow: 0 4px 16px rgba(0,0,0,0.18);
    transition: opacity 0.15s ease, visibility 0.15s ease;
}
.tooltip-content::before {
    content: ""; position: absolute; bottom: 100%; right: 8px;
    border-width: 5px; border-style: solid;
    border-color: transparent transparent #1e293b transparent;
}
.info-tooltip-wrapper:hover .tooltip-content { visibility: visible; opacity: 1; }

/* ── Inject Cards ── */
.inject-section-header {
    display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1.5rem;
}
.inject-title h3 { font-size: 1.1rem; font-weight: 800; color: var(--on-surface); margin-bottom: 4px; }
.inject-title p { font-size: 0.875rem; color: var(--on-surface-variant); }
.total-badge {
    background: #1e293b; color: #fff; border-radius: 14px; padding: 10px 18px; text-align: center; min-width: 90px; flex-shrink: 0;
}
.total-badge .badge-label { font-size: 0.6rem; font-weight: 700; letter-spacing: 0.1em; text-transform: uppercase; color: #94a3b8; display: block; margin-bottom: 4px; }
.total-badge .badge-value { font-size: 1.75rem; font-weight: 800; line-height: 1; }

.inject-cards-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem; }
@media (max-width: 700px) { .inject-cards-grid { grid-template-columns: repeat(2, 1fr); } }

.inject-card {
    background: #fff; border-radius: 1rem; padding: 1.1rem 1rem 1rem; border: 1px solid #e8eef3;
    display: flex; flex-direction: column; gap: 1rem;
}
.inject-card-header { display: flex; align-items: center; gap: 10px; }
.inject-icon {
    width: 36px; height: 36px; border-radius: 9px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;
}
.inject-icon .material-symbols-outlined { font-size: 18px; }
.inject-card-label { font-size: 0.7rem; font-weight: 700; letter-spacing: 0.1em; text-transform: uppercase; color: #64748b; }

.inject-counter { display: flex; align-items: center; gap: 12px; }
.counter-btn {
    width: 32px; height: 32px; border-radius: 50%; background: #f1f5f9; border: none; cursor: pointer;
    display: flex; align-items: center; justify-content: center; font-size: 18px; color: #475569; font-weight: 300;
    transition: background 0.15s;
}
.counter-btn:hover { background: #e2e8f0; }
.counter-display { font-size: 1.5rem; font-weight: 800; color: #1e293b; min-width: 24px; text-align: center; }
.inject-hidden { display: none; }

/* ── Score Section ── */
.score-section-header {
    display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1.25rem;
}
.score-title h3 { font-size: 1.1rem; font-weight: 800; color: var(--on-surface); margin-bottom: 4px; }
.score-title p { font-size: 0.875rem; color: var(--on-surface-variant); }
.total-badge-white {
    background: #fff; color: #2563eb; border: 1px solid #e2e8f0; border-radius: 14px; padding: 10px 18px; text-align: center; min-width: 90px; flex-shrink: 0;
}
.total-badge-white .badge-label { font-size: 0.6rem; font-weight: 700; letter-spacing: 0.1em; text-transform: uppercase; color: #94a3b8; display: block; margin-bottom: 4px; }
.total-badge-white .badge-value { font-size: 1.75rem; font-weight: 800; line-height: 1; }

.scale-tabs { display: flex; gap: 0.75rem; flex-wrap: wrap; margin-bottom: 1.5rem; }
.scale-tab {
    display: flex; align-items: center; gap: 6px; padding: 10px 20px; border: 2px solid #e2e8f0; border-radius: 12px;
    background: #fff; cursor: pointer; font-size: 0.875rem; font-weight: 600; color: #64748b; transition: all 0.15s;
}
.scale-tab:hover { border-color: #94a3b8; }
.scale-tab.active { border-color: #2563eb; color: #1e40af; }
.scale-tab input { display: none; }
.scale-tab .tab-check { font-size: 14px; color: #94a3b8; }
.scale-tab.active .tab-check { color: #2563eb; }

.freq-section-label { font-size: 0.875rem; font-weight: 600; color: #475569; margin-bottom: 0.75rem; }
.freq-row {
    display: flex; align-items: center; justify-content: space-between;
    padding: 1rem 1.25rem; background: #fff; border-radius: 12px; border: 1px solid #e8eef3;
    border-left: 4px solid #94a3b8; margin-bottom: 0.75rem;
}
.freq-row-info strong { font-size: 1rem; font-weight: 700; color: #1e293b; display: block; }
.freq-row-info span { font-size: 0.8rem; color: #64748b; }
.freq-counter { display: flex; align-items: center; gap: 14px; }
.freq-btn {
    width: 30px; height: 30px; border-radius: 50%; background: transparent; border: none; cursor: pointer;
    font-size: 18px; color: #475569; font-weight: 300; display: flex; align-items: center; justify-content: center;
    transition: background 0.15s;
}
.freq-btn:hover { background: #f1f5f9; }
.freq-val { font-size: 1.25rem; font-weight: 800; color: #1e293b; min-width: 20px; text-align: center; }
.scale-panel { display: none; }
.scale-panel.active { display: block; }
</style>
</head>
<body>

<?php include 'ms_stepper.php'; ?>

<main class="main-content">
    <div class="content-grid">
        <div class="editorial-col">
            <div class="hero-image-container">
                <img class="hero-image" src="../pages/images/context.png" alt="Context">
                <div class="image-overlay"></div>
            </div>
            <div class="editorial-text">
                <h1>Stage <?= $currentStage ?> Setup</h1>
                <p>Configure the narrative, challenge sequence and grading matrix for this specific milestone.</p>
            </div>
        </div>

        <div class="form-col">
            <div class="form-card">
                <form id="stageForm" class="form-stack" method="POST" action="multistagedigisim.php?step=2&sim_id=<?= $simId ?>&stage=<?= $currentStage ?>">
                    
                    <div class="form-group full-width">
                        <div class="label-row">
                            <label class="field-label"><span class="material-symbols-outlined">title</span>Stage Title</label>
                            <span class="info-tooltip-wrapper"><span class="info-icon">i</span><span class="tooltip-content">A clear and concise name for this stage milestone.</span></span>
                        </div>
                        <input class="text-input" type="text" name="st_name" value="<?= htmlspecialchars($stName) ?>" required>
                    </div>

                    <div class="form-group full-width">
                        <div class="label-row">
                            <label class="field-label"><span class="material-symbols-outlined">description</span>Description</label>
                            <span class="info-tooltip-wrapper"><span class="info-icon">i</span><span class="tooltip-content">One-line summary of what happens in this stage.</span></span>
                        </div>
                        <input class="text-input" type="text" name="st_desc" value="<?= htmlspecialchars($stDesc) ?>">
                    </div>

                    <div class="form-group full-width">
                        <div class="label-row">
                            <label class="field-label"><span class="material-symbols-outlined">menu_book</span>Scenario Narrative</label>
                            <span class="info-tooltip-wrapper"><span class="info-icon">i</span><span class="tooltip-content">Construct a detailed narrative describing the situation, context and challenges participants will face. Recommended 200–500 words.</span></span>
                        </div>
                        <textarea class="textarea-input" name="st_scenario"><?= htmlspecialchars($stScenario) ?></textarea>
                    </div>

                    <div class="form-group full-width">
                        <div class="label-row">
                            <label class="field-label"><span class="material-symbols-outlined">track_changes</span>Objectives</label>
                            <span class="info-tooltip-wrapper"><span class="info-icon">i</span><span class="tooltip-content">What participants should achieve by the end of this stage.</span></span>
                        </div>
                        <textarea class="textarea-input" name="st_objective"><?= htmlspecialchars($stObj) ?></textarea>
                    </div>

                    <!-- ══ INJECTS SECTION ══ -->
                    <div class="form-group full-width" style="background:#f7f9fb; border-radius:1.25rem; padding:1.5rem;">
                        <div class="inject-section-header">
                            <div class="inject-title">
                                <h3>Configure your surprise injects</h3>
                                <p>Define the intensity of the simulated event by specifying how many injects will be dispatched per medium.</p>
                            </div>
                            <div class="total-badge">
                                <span class="badge-label">Total Injects</span>
                                <span class="badge-value" id="injectTotal">0</span>
                            </div>
                        </div>

                        <?php
                        // Icon colours per inject type
                        $iconMeta = [
                            'email'    => ['bg'=>'#dbeafe','color'=>'#2563eb','icon'=>'mail'],
                            'sms'      => ['bg'=>'#d1fae5','color'=>'#059669','icon'=>'sms'],
                            'intranet' => ['bg'=>'#fde68a','color'=>'#b45309','icon'=>'hub'],
                            'social'   => ['bg'=>'#e0e7ff','color'=>'#4338ca','icon'=>'share'],
                            'news'     => ['bg'=>'#cffafe','color'=>'#0e7490','icon'=>'newspaper'],
                            'phone'    => ['bg'=>'#f3f4f6','color'=>'#374151','icon'=>'call'],
                        ];
                        $defaultMeta = ['bg'=>'#f1f5f9','color'=>'#475569','icon'=>'notifications'];
                        ?>

                        <div class="inject-cards-grid">
                            <?php foreach($injectTypes as $inj):
                                $key = strtolower($inj['in_name']);
                                $val = intval($savedInjects[$key] ?? 0);
                                $meta = $iconMeta[$key] ?? $defaultMeta;
                            ?>
                            <div class="inject-card">
                                <div class="inject-card-header">
                                    <div class="inject-icon" style="background:<?= $meta['bg'] ?>;">
                                        <span class="material-symbols-outlined" style="color:<?= $meta['color'] ?>;"><?= $meta['icon'] ?></span>
                                    </div>
                                    <span class="inject-card-label"><?= htmlspecialchars($inj['in_name']) ?></span>
                                </div>
                                <div class="inject-counter">
                                    <button type="button" class="counter-btn" onclick="changeInject('<?= $key ?>',-1)">−</button>
                                    <span class="counter-display" id="disp_<?= $key ?>"><?= $val ?></span>
                                    <button type="button" class="counter-btn" onclick="changeInject('<?= $key ?>',1)">+</button>
                                </div>
                                <input type="hidden" class="inject-hidden" name="injects[<?= $key ?>]" id="inp_<?= $key ?>" value="<?= $val ?>">
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- ══ SCORING SECTION ══ -->
                    <div class="form-group full-width" style="background:#f7f9fb; border-radius:1.25rem; padding:1.5rem;">
                        <div class="score-section-header">
                            <div class="score-title">
                                <h3>Configure Response Scale</h3>
                                <p>Select a scale set and define response requirements.</p>
                            </div>
                            <div class="total-badge-white">
                                <span class="badge-label">Total Responses</span>
                                <span class="badge-value" id="scoreTotal">0</span>
                            </div>
                        </div>

                        <!-- Scale Tabs -->
                        <div class="scale-tabs">
                            <?php foreach ($scoreTypes as $st):
                                $isActive = ($savedScale == $st['st_id']);
                            ?>
                            <label class="scale-tab <?= $isActive ? 'active' : '' ?>">
                                <input type="radio" name="score_scale" value="<?= $st['st_id'] ?>"
                                    <?= $isActive ? 'checked' : '' ?>
                                    onchange="switchScale(this, <?= $st['st_id'] ?>)">
                                <span class="material-symbols-outlined tab-check">check_circle</span>
                                <?= htmlspecialchars($st['st_name']) ?>
                            </label>
                            <?php endforeach; ?>
                        </div>

                        <!-- Frequency rows per scale -->
                        <?php
                        // Map stv_value to a colour for the left border
                        $colourMap = [1=>'#ef4444',2=>'#f97316',3=>'#22c55e',4=>'#3b82f6',5=>'#8b5cf6'];
                        foreach ($scoreTypes as $st):
                            $isActive = ($savedScale == $st['st_id']);
                            $scaleId  = $st['st_id'];
                        ?>
                        <div class="scale-panel <?= $isActive ? 'active' : '' ?>" id="scale_comps_<?= $scaleId ?>">
                            <p class="freq-section-label">Configure Frequencies</p>
                            <?php if (isset($allComponents[$scaleId])): ?>
                                <?php foreach ($allComponents[$scaleId] as $comp):
                                    $compName = $comp['stv_name'];
                                    $compVal  = ($isActive && isset($savedScores[$compName])) ? $savedScores[$compName] : 0;
                                    $colour   = $colourMap[$comp['stv_value']] ?? '#94a3b8';
                                    $uid      = 'sc_' . $scaleId . '_' . preg_replace('/\W+/','_',$compName);
                                ?>
                                <div class="freq-row" style="border-left-color:<?= $colour ?>;">
                                    <div class="freq-row-info">
                                        <strong><?= htmlspecialchars($compName) ?></strong>
                                        <span><?= strtolower($compName) ?> responses</span>
                                    </div>
                                    <div class="freq-counter">
                                        <button type="button" class="freq-btn" onclick="changeScore('<?= $uid ?>',<?= $scaleId ?>,-1)">−</button>
                                        <span class="freq-val" id="disp_<?= $uid ?>"><?= $compVal ?></span>
                                        <button type="button" class="freq-btn" onclick="changeScore('<?= $uid ?>',<?= $scaleId ?>,1)">+</button>
                                        <input type="hidden" name="scores[<?= $scaleId ?>][<?= htmlspecialchars($compName) ?>]" id="inp_<?= $uid ?>" value="<?= $compVal ?>">
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>

                </form>
            </div>
        </div>
    </div>
</main>

<script>
/* ── Inject counters ── */
function changeInject(key, delta) {
    const inp  = document.getElementById('inp_' + key);
    const disp = document.getElementById('disp_' + key);
    let val = Math.max(0, parseInt(inp.value || 0) + delta);
    inp.value = val;
    disp.textContent = val;
    updateInjectTotal();
}
function updateInjectTotal() {
    let total = 0;
    document.querySelectorAll('.inject-hidden').forEach(inp => total += parseInt(inp.value) || 0);
    document.getElementById('injectTotal').textContent = total;
}

/* ── Score counters ── */
function changeScore(uid, scaleId, delta) {
    const inp  = document.getElementById('inp_' + uid);
    const disp = document.getElementById('disp_' + uid);
    let val = Math.max(0, parseInt(inp.value || 0) + delta);
    inp.value = val;
    disp.textContent = val;
    updateScoreTotal();
}
function updateScoreTotal() {
    const activePanel = document.querySelector('.scale-panel.active');
    let total = 0;
    if (activePanel) {
        activePanel.querySelectorAll('input[type="hidden"]').forEach(inp => total += parseInt(inp.value) || 0);
    }
    document.getElementById('scoreTotal').textContent = total;
}

/* ── Scale tab switch ── */
function switchScale(radio, scaleId) {
    document.querySelectorAll('.scale-tab').forEach(el => el.classList.remove('active'));
    radio.closest('.scale-tab').classList.add('active');
    document.querySelectorAll('.scale-panel').forEach(el => el.classList.remove('active'));
    const panel = document.getElementById('scale_comps_' + scaleId);
    if (panel) panel.classList.add('active');
    updateScoreTotal();
}

/* ── Init ── */
document.addEventListener('DOMContentLoaded', () => {
    updateInjectTotal();
    updateScoreTotal();
});
</script>
</body>
</html>
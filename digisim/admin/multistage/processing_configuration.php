<?php
// processing_configuration.php - Step 3: Processing Configuration
// NO <head> or <body> - handled by layout/header.php
// Uses mg5_ms_userinput_master table

$pageTitle = 'Processing Configuration';
require_once __DIR__ . '/../include/dataconnect.php';

$simId = isset($_GET['sim_id']) ? intval($_GET['sim_id']) : 0;

if ($simId <= 0) {
    header("Location: multistagedigisim.php?step=1");
    exit;
}

$errors = [];

// Initialize form data
$priorityPoints = $scoringLogic = $scoringBasis = $totalBasis = $taskResultDisplay = null;
$thresholdValue = 10; // Default

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate and get priority points
    if (isset($_POST['priority_points']) && $_POST['priority_points'] === 'expert') {
        $priorityPoints = 1;
    } elseif (isset($_POST['priority_points']) && $_POST['priority_points'] === 'manual') {
        $priorityPoints = 2;
        $thresholdValue = isset($_POST['threshold_value']) ? intval($_POST['threshold_value']) : 10;
    } else {
        $errors['priority'] = 'Please select a priority point option';
    }

    // Validate and get scoring logic
    if (isset($_POST['scoring_logic'])) {
        switch ($_POST['scoring_logic']) {
            case 'atleast': $scoringLogic = 1; break;
            case 'actual': $scoringLogic = 2; break;
            case 'absolute': $scoringLogic = 3; break;
            default: $errors['scoring_logic'] = 'Invalid scoring logic selected';
        }
    } else {
        $errors['scoring_logic'] = 'Please select a scoring logic';
    }

    // Validate and get scoring basis
    if (isset($_POST['scoring_basis'])) {
        switch ($_POST['scoring_basis']) {
            case 'all': $scoringBasis = 1; break;
            case 'part': $scoringBasis = 2; break;
            case 'minimum': $scoringBasis = 3; break;
            default: $errors['scoring_basis'] = 'Invalid scoring basis selected';
        }
    } else {
        $errors['scoring_basis'] = 'Please select a scoring basis';
    }

    // Validate and get total basis
    if (isset($_POST['total_basis'])) {
        switch ($_POST['total_basis']) {
            case 'all_tasks': $totalBasis = 1; break;
            case 'marked_tasks': $totalBasis = 2; break;
            default: $errors['total_basis'] = 'Invalid total basis selected';
        }
    } else {
        $errors['total_basis'] = 'Please select a total basis';
    }

    // Validate and get task result display
    if (isset($_POST['task_result_display']) && is_array($_POST['task_result_display'])) {
        if (in_array('percentage', $_POST['task_result_display'])) {
            $taskResultDisplay = 2;
        } elseif (in_array('raw_score', $_POST['task_result_display'])) {
            $taskResultDisplay = 3;
        } elseif (in_array('legend', $_POST['task_result_display'])) {
            $taskResultDisplay = 4;
        } else {
            $taskResultDisplay = 1; // NA
        }
    } else {
        $taskResultDisplay = 1; // Default to NA if nothing selected
    }

    // If no errors, process form
    if (empty($errors)) {
        try {
            // Update the master simulation record
            $updateStmt = $conn->prepare("
                UPDATE mg5_ms_userinput_master 
                SET 
                    ui_priority_points = ?,
                    ui_scoring_logic = ?,
                    ui_scoring_basis = ?,
                    ui_total_basis = ?,
                    ui_result = ?,
                    ui_cur_stage = 4
                WHERE ui_id = ? AND ui_team_pkid = ?
            ");

            $updateStmt->bind_param(
                'iiiiiii',
                $priorityPoints,
                $scoringLogic,
                $scoringBasis,
                $totalBasis,
                $taskResultDisplay,
                $simId,
                $_SESSION['team_id']
            );

            $updateStmt->execute();
            
            if ($updateStmt->error) {
                throw new Exception('Database update failed: ' . $updateStmt->error);
            }
            
            $updateStmt->close();

            // Redirect to next page (review or success)
            header("Location: multistagedigisim.php?step=4&sim_id=" . $simId);
            exit;
            
        } catch (Exception $e) {
            error_log("Processing config error: " . $e->getMessage());
            $errors['database'] = 'Error: ' . $e->getMessage();
        }
    }
} else {
    // Load existing data when viewing the page
    $loadStmt = $conn->prepare("
        SELECT 
            ui_priority_points, 
            ui_scoring_logic, 
            ui_scoring_basis, 
            ui_total_basis, 
            ui_result
        FROM mg5_ms_userinput_master 
        WHERE ui_id = ? AND ui_team_pkid = ?
        LIMIT 1
    ");

    $loadStmt->bind_param('ii', $simId, $_SESSION['team_id']);
    $loadStmt->execute();
    $result = $loadStmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $priorityPoints = $row['ui_priority_points'];
        $scoringLogic = $row['ui_scoring_logic'];
        $scoringBasis = $row['ui_scoring_basis'];
        $totalBasis = $row['ui_total_basis'];
        $taskResultDisplay = $row['ui_result'];
    }

    $loadStmt->close();
}
?>
<!DOCTYPE html>
<html lang="en"><head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<title>Processing Configuration</title>
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

/* Processing specific UI */
.config-section h2 { font-size: 1.125rem; font-weight: 700; color: var(--primary); margin-bottom: 0.75rem; border-bottom: 1px solid var(--outline-variant); padding-bottom: 0.5rem; }
.section-desc { font-size: 0.875rem; color: var(--on-surface-variant); margin-bottom: 1rem; }

.options-wrapper { display: flex; flex-direction: column; gap: 0.75rem; margin-bottom: 1rem; }
.option-card {
    background: #fff; padding: 1rem 1.25rem; border: 2px solid #e2e8f0; border-radius: 0.75rem;
    cursor: pointer; position: relative; transition: all 0.2s;
}
.option-card input[type="radio"], .option-card input[type="checkbox"] { display: none; }
.option-card label { cursor: pointer; display: block; }
.option-card h3 { font-size: 1rem; font-weight: 600; color: #1e293b; margin-bottom: 0.25rem; }
.option-card p { font-size: 0.85rem; color: #64748b; margin: 0; }
.option-card.selected { border-color: #3b82f6; background-color: #f0fdf4; } /* A soft green or blue tint */
.option-card.selected h3 { color: #2563eb; }

.threshold-input { margin-top: 10px; border-top: 1px dashed #cbd5e1; padding-top: 10px; display: none; }
.threshold-input label { font-size: 0.85rem; font-weight: 600; color: #475569; display: block; margin-bottom: 5px; }
.threshold-input input { padding: 6px; border: 1px solid #cbd5e1; border-radius: 6px; width: 80px; }
.threshold-unit { font-size: 0.85rem; color: #64748b; margin-left: 5px; }

.error { color: #ef4444; font-size: 0.875rem; margin-top: 5px; font-weight: 600; }
</style>
</head>
<body>

<?php include 'ms_stepper.php'; ?>

<main class="main-content">
    <div class="content-grid">
        <div class="editorial-col">
            <div class="hero-image-container">
                <img class="hero-image" src="../pages/images/response.png" alt="Context">
                <div class="image-overlay"></div>
            </div>
            <div class="editorial-text">
                <h1>Processing Configuration</h1>
                <p>Refine how the simulation engine calculates performance metrics and awards priority points during runtime execution.</p>
            </div>
        </div>

        <div class="form-col">
            <div class="form-card">
                <?php if (isset($errors['database'])): ?>
                    <p class="error"><?= $errors['database'] ?></p>
                <?php endif; ?>

                <form id="processingForm" class="form-stack" method="POST" action="multistagedigisim.php?step=3&sim_id=<?= $simId ?>">
                    
                    <!-- Priority Points -->
                    <div class="form-group full-width config-section">
                        <h2>Priority Points</h2>
                        <div class="options-wrapper">
                            <div class="option-card <?= $priorityPoints == 1 ? 'selected' : '' ?>">
                                <input type="radio" id="priority_expert" name="priority_points" value="expert" <?= $priorityPoints == 1 ? 'checked' : '' ?> onchange="updateCardSelection(this)">
                                <label for="priority_expert">
                                    <h3>Expert</h3>
                                    <p>Automated weight calculation based on preset expert patterns</p>
                                </label>
                            </div>
                            <div class="option-card <?= $priorityPoints == 2 ? 'selected' : '' ?>">
                                <input type="radio" id="priority_manual" name="priority_points" value="manual" <?= $priorityPoints == 2 ? 'checked' : '' ?> onchange="updateCardSelection(this)">
                                <label for="priority_manual">
                                    <h3>Manual</h3>
                                    <p>Custom score assignment for granular prioritization</p>
                                </label>
                                <div class="threshold-input" id="thresholdWrapper" style="<?= $priorityPoints != 2 ? 'display:none;' : '' ?>">
                                    <label for="threshold_value">Threshold</label>
                                    <input type="number" id="threshold_value" name="threshold_value" value="<?= $thresholdValue ?? 10 ?>" min="0" max="100">
                                    <span class="threshold-unit">points</span>
                                </div>
                            </div>
                        </div>
                        <?php if (isset($errors['priority'])): ?>
                            <p class="error"><?= $errors['priority'] ?></p>
                        <?php endif; ?>
                    </div>

                    <!-- Scoring Logic -->
                    <div class="form-group config-section">
                        <h2>Scoring Logic</h2>
                        <div class="options-wrapper">
                            <div class="option-card <?= $scoringLogic == 1 ? 'selected' : '' ?>">
                                <input type="radio" id="scoring_atleast" name="scoring_logic" value="atleast" <?= $scoringLogic == 1 ? 'checked' : '' ?> onchange="updateCardSelection(this)">
                                <label for="scoring_atleast">
                                    <h3>At Least</h3>
                                    <p>Minimum threshold to pass</p>
                                </label>
                            </div>
                            <div class="option-card <?= $scoringLogic == 2 ? 'selected' : '' ?>">
                                <input type="radio" id="scoring_actual" name="scoring_logic" value="actual" <?= $scoringLogic == 2 ? 'checked' : '' ?> onchange="updateCardSelection(this)">
                                <label for="scoring_actual">
                                    <h3>Actual</h3>
                                    <p>Exact score calculation</p>
                                </label>
                            </div>
                            <div class="option-card <?= $scoringLogic == 3 ? 'selected' : '' ?>">
                                <input type="radio" id="scoring_absolute" name="scoring_logic" value="absolute" <?= $scoringLogic == 3 ? 'checked' : '' ?> onchange="updateCardSelection(this)">
                                <label for="scoring_absolute">
                                    <h3>Absolute</h3>
                                    <p>Fixed score threshold</p>
                                </label>
                            </div>
                        </div>
                        <?php if (isset($errors['scoring_logic'])): ?>
                            <p class="error"><?= $errors['scoring_logic'] ?></p>
                        <?php endif; ?>
                    </div>

                    <!-- Scoring Basis -->
                    <div class="form-group config-section">
                        <h2>Scoring Basis</h2>
                        <div class="options-wrapper">
                            <div class="option-card <?= $scoringBasis == 1 ? 'selected' : '' ?>">
                                <input type="radio" id="scoring_all" name="scoring_basis" value="all" <?= $scoringBasis == 1 ? 'checked' : '' ?> onchange="updateCardSelection(this)">
                                <label for="scoring_all">
                                    <h3>All</h3>
                                    <p>Calculate score based on the entire set of available tasks</p>
                                </label>
                            </div>
                            <div class="option-card <?= $scoringBasis == 2 ? 'selected' : '' ?>">
                                <input type="radio" id="scoring_part" name="scoring_basis" value="part" <?= $scoringBasis == 2 ? 'checked' : '' ?> onchange="updateCardSelection(this)">
                                <label for="scoring_part">
                                    <h3>Part</h3>
                                    <p>Calculate score based on a subset of tasks</p>
                                </label>
                            </div>
                            <div class="option-card <?= $scoringBasis == 3 ? 'selected' : '' ?>">
                                <input type="radio" id="scoring_minimum" name="scoring_basis" value="minimum" <?= $scoringBasis == 3 ? 'checked' : '' ?> onchange="updateCardSelection(this)">
                                <label for="scoring_minimum">
                                    <h3>Minimum</h3>
                                    <p>Calculate score based on minimum required tasks</p>
                                </label>
                            </div>
                        </div>
                        <?php if (isset($errors['scoring_basis'])): ?>
                            <p class="error"><?= $errors['scoring_basis'] ?></p>
                        <?php endif; ?>
                    </div>

                    <!-- Total Basis -->
                    <div class="form-group config-section">
                        <h2>Total Basis</h2>
                        <div class="options-wrapper">
                            <div class="option-card <?= $totalBasis == 1 ? 'selected' : '' ?>">
                                <input type="radio" id="total_all" name="total_basis" value="all_tasks" <?= $totalBasis == 1 ? 'checked' : '' ?> onchange="updateCardSelection(this)">
                                <label for="total_all">
                                    <h3>All Tasks</h3>
                                    <p>Calculate score based on the entire set of available tasks</p>
                                </label>
                            </div>
                            <div class="option-card <?= $totalBasis == 2 ? 'selected' : '' ?>">
                                <input type="radio" id="total_marked" name="total_basis" value="marked_tasks" <?= $totalBasis == 2 ? 'checked' : '' ?> onchange="updateCardSelection(this)">
                                <label for="total_marked">
                                    <h3>Marked Tasks Only</h3>
                                    <p>Only include tasks explicitly flagged for evaluation</p>
                                </label>
                            </div>
                        </div>
                        <?php if (isset($errors['total_basis'])): ?>
                            <p class="error"><?= $errors['total_basis'] ?></p>
                        <?php endif; ?>
                    </div>

                    <!-- Task Result Display -->
                    <div class="form-group config-section">
                        <h2>Task Result Display</h2>
                        <div class="options-wrapper">
                            <div class="option-card <?= $taskResultDisplay == 2 ? 'selected' : '' ?>">
                                <input type="checkbox" id="result_percentage" name="task_result_display[]" value="percentage" <?= $taskResultDisplay == 2 ? 'checked' : '' ?> onchange="updateResultSelection(this)">
                                <label for="result_percentage">
                                    <h3>Percentage</h3>
                                    <p>e.g. 85%</p>
                                </label>
                            </div>
                            <div class="option-card <?= $taskResultDisplay == 3 ? 'selected' : '' ?>">
                                <input type="checkbox" id="result_raw" name="task_result_display[]" value="raw_score" <?= $taskResultDisplay == 3 ? 'checked' : '' ?> onchange="updateResultSelection(this)">
                                <label for="result_raw">
                                    <h3>Raw Score</h3>
                                    <p>e.g. 42/50</p>
                                </label>
                            </div>
                            <div class="option-card <?= $taskResultDisplay == 4 ? 'selected' : '' ?>">
                                <input type="checkbox" id="result_legend" name="task_result_display[]" value="legend" <?= $taskResultDisplay == 4 ? 'checked' : '' ?> onchange="updateResultSelection(this)">
                                <label for="result_legend">
                                    <h3>Legend</h3>
                                    <p>Performance tiers</p>
                                </label>
                            </div>
                        </div>
                        <?php if (isset($errors['task_result'])): ?>
                            <p class="error"><?= $errors['task_result'] ?></p>
                        <?php endif; ?>
                    </div>

                </form>
            </div>
        </div>
    </div>
</main>

<script>
function updateCardSelection(el) {
    const container = el.closest('.options-wrapper');
    container.querySelectorAll('.option-card').forEach(card => card.classList.remove('selected'));
    
    if (el.closest('.option-card')) {
        el.closest('.option-card').classList.add('selected');
    }
    
    if (el.id === 'priority_manual') {
        document.getElementById('thresholdWrapper').style.display = 'block';
    } else if (el.id === 'priority_expert') {
        document.getElementById('thresholdWrapper').style.display = 'none';
    }
}

function updateResultSelection(el) {
    if (el.checked) {
        document.querySelectorAll('input[name="task_result_display[]"]').forEach(cb => {
            if (cb !== el) cb.checked = false;
        });
        el.closest('.option-card').classList.add('selected');
        document.querySelectorAll('#result_percentage, #result_raw, #result_legend').forEach(cb => {
            if (cb !== el && cb.closest('.option-card')) {
                cb.closest('.option-card').classList.remove('selected');
            }
        });
    } else {
        if (el.closest('.option-card')) el.closest('.option-card').classList.remove('selected');
    }
}

document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.config-section input[type="radio"]:checked').forEach(radio => {
        if (radio.closest('.option-card')) radio.closest('.option-card').classList.add('selected');
    });
    document.querySelectorAll('.options-wrapper input[type="checkbox"]:checked').forEach(checkbox => {
        if (checkbox.closest('.option-card')) checkbox.closest('.option-card').classList.add('selected');
    });
    
    const priorityManual = document.getElementById('priority_manual');
    const thresholdWrapper = document.getElementById('thresholdWrapper');
    if (thresholdWrapper) thresholdWrapper.style.display = priorityManual?.checked ? 'block' : 'none';
});
</script>
</body>
</html>
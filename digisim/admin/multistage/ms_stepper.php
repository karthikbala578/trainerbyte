<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../include/dataconnect.php';

$currentStep = isset($_GET['step']) ? (int)$_GET['step'] : 1;
$simId = isset($_GET['sim_id']) ? (int)$_GET['sim_id'] : 0;
$currentStage = isset($_GET['stage']) ? (int)$_GET['stage'] : 1;

$noStages = 1;
if ($simId > 0) {
    $stmt = $conn->prepare("SELECT ui_no_stages FROM mg5_ms_userinput_master WHERE ui_id = ? AND ui_team_pkid = ? LIMIT 1");
    $stmt->bind_param("ii", $simId, $_SESSION['team_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $noStages = $result->fetch_assoc()['ui_no_stages'];
    }
    $stmt->close();
}

$stageLabel = "Stages";
if ($currentStep == 2) {
    $stageLabel = "Stage $currentStage (of $noStages)";
}

$steps = [
    1 => 'Setup',
    2 => $stageLabel,
    3 => 'Processing',
    4 => 'Review'
];

$totalSteps = count($steps);
?>

<link href="https://fonts.googleapis.com/css2?family=Public+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined">

<style>
.stepper-bar {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 58px;
    background: #fff;
    border-bottom: 1px solid #e2e8f0;
    display: flex;
    align-items: center;
    z-index: 1000;
}

.stepper-inner {
    max-width: 1400px;
    width: 100%;
    margin: 0 auto;
    padding: 0 28px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 20px;
}

.stepper-track {
    display: flex;
    align-items: center;
    flex: 1;
    min-width: 0;
}

.step-item {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-shrink: 0;
}

.step-circle {
    width: 26px;
    height: 26px;
    border-radius: 50%;
    border: 2px solid #d1d5db;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
    font-weight: 700;
    color: #94a3b8;
}

.step-label {
    font-size: 12px;
    font-family: 'Public Sans', sans-serif;
    font-weight: 600; 
    color: #94a3b8;
    white-space: nowrap;
    min-width: max-content; 
}

.step-connector {
    flex: 1;
    min-width: 12px;
    max-width: 48px;
    height: 2px;
    background: #e2e8f0;
    margin: 0 6px;
}

.step-completed .step-circle {
    border-color: #3b82f6;
    background: #3b82f6;
    color: #fff;
}

.step-completed .step-label {
    color: #3b82f6;
}

.step-current .step-circle {
    border-color: #3b82f6;
    color: #3b82f6;
}

.step-current .step-label {
    color: #0f172a;
}

.stepper-nav {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-shrink: 0;
}

.stp-btn-back {
    display: flex;
    align-items: center;
    gap: 5px;
    font-size: 13px;
    font-weight: 600;
    color: #374151;
    padding: 7px 14px;
    border-radius: 8px;
    border: 1px solid #d1d5db;
    text-decoration: none;
}

.stp-btn-next {
    display: flex;
    align-items: center;
    gap: 5px;
    font-size: 13px;
    font-weight: 700;
    color: #fff;
    background: #3b82f6;
    padding: 7px 18px;
    border-radius: 8px;
    text-decoration: none;
    border: none;
    cursor: pointer;
}

.material-symbols-outlined {
    font-size: 16px;
}
</style>

<div class="stepper-bar">
<div class="stepper-inner">

    <!-- LEFT STEPPER -->
    <div class="stepper-track">
        <?php
        for ($i = 1; $i <= $totalSteps; $i++) {
            if ($i < $currentStep) $cls = "step-completed";
            elseif ($i == $currentStep) $cls = "step-current";
            else $cls = "step-upcoming";
        ?>
        <div class="step-item <?= $cls ?>">
            <div class="step-circle">
                <?php if ($i < $currentStep): ?>
                    <span class="material-symbols-outlined">check</span>
                <?php else: ?>
                    <?= $i ?>
                <?php endif; ?>
            </div>
            <div class="step-label"><?= $steps[$i] ?></div>
        </div>
        <?php if ($i < $totalSteps): ?>
            <div class="step-connector"></div>
        <?php endif; ?>
        <?php } ?>
    </div>

    <!-- RIGHT NAV -->
    <div class="stepper-nav">
        <!-- Back Navigation Logic -->
        <?php if ($currentStep == 2 && $currentStage > 1): ?>
            <a class="stp-btn-back" href="multistagedigisim.php?step=2&sim_id=<?= $simId ?>&stage=<?= $currentStage - 1 ?>">
                <span class="material-symbols-outlined">arrow_back</span> Back
            </a>
        <?php elseif ($currentStep > 1): ?>
            <?php 
                // If we are at step 3, back goes to the last stage of step 2
                $backLink = "multistagedigisim.php?step=" . ($currentStep - 1) . "&sim_id=" . $simId;
                if ($currentStep == 3) {
                    $backLink .= "&stage=" . $noStages;
                }
            ?>
            <a class="stp-btn-back" href="<?= $backLink ?>">
                <span class="material-symbols-outlined">arrow_back</span> Back
            </a>
        <?php endif; ?>

        <!-- Next Navigation Logic (Buttons submitting the page's form) -->
        <?php if ($currentStep == 1): ?>
            <button type="submit" form="setupForm" class="stp-btn-next">
                Next <span class="material-symbols-outlined">arrow_forward</span>
            </button>
        <?php elseif ($currentStep == 2): ?>
            <button type="submit" form="stageForm" class="stp-btn-next">
                <?php if ($currentStage < $noStages): ?>
                    Next Stage <span class="material-symbols-outlined">arrow_forward</span>
                <?php else: ?>
                    Next Step <span class="material-symbols-outlined">arrow_forward</span>
                <?php endif; ?>
            </button>
        <?php elseif ($currentStep == 3): ?>
            <button type="submit" form="processingForm" class="stp-btn-next">
                Next <span class="material-symbols-outlined">arrow_forward</span>
            </button>
        <?php endif; ?>
    </div>

</div>
</div>

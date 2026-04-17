<?php

$steps = [
    // 1 => 'Simulation Context',
    // 2 => 'Configure Injects',
    // 3 => 'Response Scale',
    // 4 => 'Processing Settings',
    // 5 => 'Review & Summary',
    // 6 => 'Success'

    1 => 'Start',

    2 => 'Context',

    3 => 'Injects',

    4 => 'Response',

    5 => 'Finish'
];

$currentStep = isset($_GET['step']) ? (int)$_GET['step'] : 1;
$simId = isset($_GET['sim_id']) ? (int)$_GET['sim_id'] : 0;

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

/* KEEP FLEX */
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

/* LEFT TRACK */
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

/* STATES */

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

/* RIGHT NAV */
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
<!-- 
        <?php if ($currentStep > 1 && $currentStep < $totalSteps): ?>
            <a class="stp-btn-back"
               href="page-container.php?step=<?= $currentStep - 1 ?>&sim_id=<?= $simId ?>">
                <span class="material-symbols-outlined">arrow_back</span>
                Back
            </a>
        <?php endif; ?>

        <?php if ($currentStep == 1): ?>
            <button type="submit" form="simForm" class="stp-btn-next">
                Next <span class="material-symbols-outlined">arrow_forward</span>
            </button>
            

        <?php elseif ($currentStep == 2): ?>
            <button type="submit" form="injectForm" class="stp-btn-next">
                Next <span class="material-symbols-outlined">arrow_forward</span>
            </button>

        <?php elseif ($currentStep == 3): ?>
            <button type="submit" form="scaleform" class="stp-btn-next">
                Next <span class="material-symbols-outlined">arrow_forward</span>
            </button>

        <?php elseif ($currentStep == 4): ?>
            <button type="submit" form="configForm" class="stp-btn-next">
                Next <span class="material-symbols-outlined">arrow_forward</span>
            </button>
        <?php endif; ?>

        <?php if ($currentStep == 5): ?>
            <form method="POST" action="../test_generate.php?sim_id=<?= $simId ?>" style="display:inline;">
                <button type="submit" class="stp-btn-next">Generate</button>
            </form>
        <?php endif; ?> -->

        <?php if ($currentStep > 1 && $currentStep < $totalSteps): ?>
            <a class="stp-btn-back"
               href="page-container.php?step=<?= $currentStep - 1 ?>&sim_id=<?= $simId ?>">
                <span class="material-symbols-outlined">arrow_back</span>
                Back
            </a>
        <?php endif; ?>

        <?php if ($currentStep == 1): ?>
            <button type="submit" form="simForm" class="stp-btn-next">
                Next <span class="material-symbols-outlined">arrow_forward</span>
            </button>

        <?php elseif ($currentStep == 2): ?>
            <button type="submit" form="simForm2" class="stp-btn-next">
                Next <span class="material-symbols-outlined">arrow_forward</span>
            </button>

        <?php elseif ($currentStep == 3): ?>
            <button type="submit" form="injectForm" class="stp-btn-next">
                Next <span class="material-symbols-outlined">arrow_forward</span>
            </button>

        <?php elseif ($currentStep == 4): ?>
            <button type="submit" form="scaleform" class="stp-btn-next">
                Next <span class="material-symbols-outlined">arrow_forward</span>
            </button>
        <?php endif; ?> 

        <?php if ($currentStep == 5): ?>
            <!-- <form method="POST" action="../test_generate.php?sim_id=<?= $simId ?>" style="display:inline;">
                <button type="submit" class="stp-btn-next">Generate</button>
            </form> -->
            <button type="button" 
            onclick="handleGeneration(event, <?= $simId ?>)" 
            class="stp-btn-next">
        Generate
    </button>
        <?php endif; ?>

    </div>

</div>
</div>
<!-- <script>
    async function handleGeneration(event, simId) {
    event.preventDefault(); // Stop the form from traditional submit

    const modal = document.getElementById('genModal');
    const subtext = document.getElementById('modalSubtext');
    const actionArea = document.getElementById('actionArea');
    const heading = document.getElementById('modalHeading');
    const spinner = document.getElementById('modalSpinner');
    const check = document.getElementById('modalCheck');

    // 1. Show Modal
    modal.classList.remove('hidden');

    // 2. Start Message Rotation (Every 10 seconds)
    const messages = [
        "We’re analyzing your requirements",
        "Matching patterns from similar scenarios",
        "Designing structured outputs",
        "Validating for quality and relevance",
        "Processing..."
    ];
    let msgIndex = 0;
    const msgInterval = setInterval(() => {
        if (msgIndex < messages.length - 1) {
            msgIndex++;
            subtext.innerText = messages[msgIndex] + "...";
        }
    }, 10000);

    try {
        // 3. Call Backend PHP (Replace with your actual path)
        const response = await fetch(`../test_generate.php?sim_id=${simId}`, { method: 'POST' });
        const data = await response.json();

        if (data.success) {
            // Stop rotation and set to success
            clearInterval(msgInterval);
            
            // Switch UI to Success State
            heading.innerText = "Content Generated Successfully!";
            subtext.innerText = "Your new asset is ready. Choose your next step to finalize your workflow.";
            subtext.classList.remove('text-blue-600', 'animate-pulse');
            subtext.classList.add('text-gray-500');
            
            // Swap Spinner for Checkmark
            spinner.classList.add('hidden');
            check.classList.remove('hidden');
            
            // Enable and Light up the cards
            actionArea.classList.remove('opacity-40', 'pointer-events-none');
            
            // Update Links
            document.getElementById('reviewLink').href = `digisim_edit.php?id=${data.digisim_id}`;
            document.getElementById('previewLink').href = `digisim_preview.php?id=${data.digisim_id}`;
        }
    } catch (error) {
        clearInterval(msgInterval);
        alert("An error occurred during generation.");
        modal.classList.add('hidden');
    }
}
</script> -->
<style>

    /* ===== STEPPER MAIN ===== */
    .stepper {
        display: flex;
        align-items: center;
        justify-content: center;
        max-width: 700px;
        margin: auto;
    }

    /* ===== STEP ITEM ===== */
    .step {
        display: flex;
        flex-direction: column;
        align-items: center;
        min-width: 110px;
        font-size: 12px;
        color: #94a3b8;
        transition: 0.3s ease;
    }

    /* TEXT */
    .step p {
        margin-top: 4px;
        font-weight: 500;
        font-size: 12px;
    }

    /* ===== CIRCLE ===== */
    .circle {
        width: 28px;
        height: 28px;
        border-radius: 50%;
        background: #e2e8f0;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: 0.3s ease;
    }

    .circle span {
        font-size: 16px;
    }

    /* ===== ACTIVE STEP ===== */
    .step.active .circle {
        background: linear-gradient(135deg, #2563eb, #6366f1);
        color: #fff;
        box-shadow: 0 6px 14px rgba(37, 99, 235, 0.3);
    }

    .step.active p {
        color: #2563eb;
    }

    /* ===== COMPLETED STEP ===== */
    .step.completed .circle {
        background: #22c55e;
        color: #fff;
    }

    /* ===== LINE ===== */
    .line {
        flex: 1;
        height: 2px;
        background: #cbd5f5;
        margin: 0 6px;
        border-radius: 10px;
        transition: 0.3s ease;
    }

    /* ===== ACTIVE LINE ===== */
    .line.active {
        background: linear-gradient(135deg, #2563eb, #6366f1);
    }

    /* ===== HOVER EFFECT ===== */
    .step:hover .circle {
        transform: scale(1.08);
    }

    /* ===== RESPONSIVE ===== */
    @media (max-width: 600px) {
        .step {
            min-width: 80px;
            font-size: 11px;
        }

        .circle {
            width: 24px;
            height: 24px;
        }

        .circle span {
            font-size: 14px;
        }
    }
</style>

<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet">

<div class="stepper">

    <!-- STEP 1 -->
    <div class="step <?= $step >= 1 ? 'active' : '' ?>">
        <div class="circle">
            <?php if ($step > 1): ?>
                <span class="material-symbols-outlined">check</span>
            <?php else: ?>
                <span class="material-symbols-outlined">radio_button_unchecked</span>
            <?php endif; ?>
        </div>
        <p>Event Details</p>
    </div>

    <div class="line <?= $step >= 2 ? 'active' : '' ?>"></div>

    <!-- STEP 2 -->
    <div class="step <?= $step >= 2 ? 'active' : '' ?>">
        <div class="circle">
            <?php if ($step > 2): ?>
                <span class="material-symbols-outlined">check</span>
            <?php else: ?>
                <span class="material-symbols-outlined">radio_button_unchecked</span>
            <?php endif; ?>
        </div>
        <p>Module Selection</p>
    </div>

    <div class="line <?= $step >= 3 ? 'active' : '' ?>"></div>

    <!-- STEP 3 -->
    <div class="step <?= $step >= 3 ? 'active' : '' ?>">
        <div class="circle">
            <span class="material-symbols-outlined">
                <?= $step >= 3 ? 'radio_button_checked' : 'radio_button_unchecked' ?>
            </span>
        </div>
        <p>Confirm & Launch</p>
    </div>

</div>
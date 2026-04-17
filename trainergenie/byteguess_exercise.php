<?php
session_start();
if (!isset($_SESSION['team_id'])) {
    header("Location: login.php");
    exit;
}
require "layout/header.php";
?>

<main class="exercise-container">
    <div class="wizard">

       
        <div class="wizard-steps">
            <?php for ($i = 1; $i <= 5; $i++): ?>
                <span class="step-dot <?php echo  $i === 1 ? 'active' : '' ?>" id="step-dot-<?php echo  $i ?>"></span>
            <?php endfor; ?>

            <div class="step-text">
                <?php for ($i = 1; $i <= 5; $i++): ?>
                    <span class="step-label" data-step="<?php echo  $i ?>">Step <?php echo  $i ?> of 5</span>
                <?php endfor; ?>
            </div>

            <div class="progress-bar">
                <div class="progress-fill"></div>
            </div>
        </div>

        
        <section id="wizard-box" aria-live="polite">

            
            <!-- <div class="wizard-header">
                <h3 id="wizard-title">ByteGuess Exercise</h3>
                <p id="wizard-subtitle">
                    Configure and generate an intelligent decision-making card exercise.
                </p>
            </div> -->

            <!--  Dynamic Content Area -->
            <div id="wizard-content">
                <!-- JS injects step content here -->
            </div>

        </section>

       
        <div id="loader" style="display:none;">Processing…</div>

    </div>
</main>


<link rel="stylesheet" href="assets/styles/exercise/byteguess_exercise.css?v=<?php echo  time() ?>">


<script src="js/byteguess_exercise.js"></script>

<?php require "layout/footer.php"; ?>

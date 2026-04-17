<?php
$pageTitle = "Prompts";
$pageCSS   = "/admin/styles/prompts.css";

session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

require "layout/header.php";
?>

<div class="prompts-wrapper">

    <section class="prompts-hero">


        <div class="hero-box">
            <span class="hero-pill">TrainerGenie Admin</span>
            <h1>Game Prompts</h1>
            <p>
                Manage structured prompt flows used by interactive learning games.
                Each game contains step-based prompts maintained here.
            </p>
        </div>

        <div class="back-box">
            <a href="index.php" class="back-btn">
                Back
            </a><br>
        </div>

    </section>

    <div class="game-box">
        <div class="game-card">
            <div class="content">
                <h3>Byte Guess</h3>
                <p>
                    Manage and update the structured prompts used in Byte Guess.
                    Click <b> Manage Prompts </b> to view or modify the prompt flow.
                </p>
                <a href="byteguess-prompts.php" class="manage-btn">
                    <div class="manage-prompt-box">

                        Manage Prompts

                    </div>
                </a>
            </div>

            <div class="img-box">
                <img src="./images/byteguess-prompt.png" alt="byteguess-prompt.png">
            </div>
        </div>


    </div>

</div>

<?php require "layout/footer.php"; ?>
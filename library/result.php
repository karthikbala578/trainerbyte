<?php

session_start();

require "../include/coreDataconnect.php";



if (!isset($_SESSION['team_id'])) {

    header("Location: ../login.php");

    exit;

}



$cg_id = intval($_GET['cg_id'] ?? 0);

if ($cg_id <= 0) {

    die("Invalid game");

}



$pageTitle = "Result";

$pageCSS   = "/library/result.css";

require "../layout/header.php";



/* Fetch game name + answer key */

$stmt = $conn->prepare("

    SELECT cg_name, cg_result

    FROM card_group

    WHERE cg_id = ?

");

$stmt->bind_param("i", $cg_id);

$stmt->execute();

$game = $stmt->get_result()->fetch_assoc();

?>



<div class="logic-wrap">



    <!-- HEADER -->

    <div class="editor-header">

        <a href="view_game.php?cg_id=<?= $cg_id ?>" class="back-link">

            ← Back to Game

        </a>

        <h1><?= htmlspecialchars($game['cg_name']) ?> – Answer Key</h1>

    </div>



    <!-- CONTENT -->

    <div class="logic-card">



        <label>Participant Answer Key</label>



        <textarea id="answerKey"

                  rows="10"

                  placeholder="Explain why the correct answer is strongest and how card signals connect..."><?= htmlspecialchars($game['cg_result'] ?? '') ?></textarea>



        <div class="editor-actions">

            <button class="btn primary" onclick="saveResult()">Save Answer Key</button>

        </div>



    </div>



</div>



<script>

function saveResult() {

    fetch("save_result.php", {

        method: "POST",

        headers: { "Content-Type": "application/json" },

        body: JSON.stringify({

            cg_id: <?= $cg_id ?>,

            result: document.getElementById("answerKey").value.trim()

        })

    })

    .then(res => res.text())

    .then(t => JSON.parse(t))

    .then(data => {

        if (data.status === "success") {

            alert("Answer key saved successfully");

        } else {

            alert(data.message || "Save failed");

        }

    })

    .catch(() => alert("Unexpected server error"));

}

</script>



<?php //require "../layout/footer.php"; ?>


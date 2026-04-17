<?php

session_start();

require "../include/coreDataconnect.php";



if (!isset($_SESSION['team_id'])) {

    header("Location: ../login.php");

    exit;

}



$cg_id = intval($_GET['cg_id'] ?? 0);

if ($cg_id <= 0) die("Invalid Game");



$pageTitle = "View Clues";

$pageCSS   = "/library/view_clue.css";

require "../layout/header.php";



/* Fetch Clues */

$stmt = $conn->prepare("

    SELECT cg_name, cg_clue

    FROM card_group

    WHERE cg_id = ?

");

$stmt->bind_param("i", $cg_id);

$stmt->execute();

$game = $stmt->get_result()->fetch_assoc();



$clues = json_decode($game['cg_clue'], true) ?? [];

?>



<div class="clue-wrap">



    <div class="editor-header">

        <a href="view_game.php?cg_id=<?= $cg_id ?>" class="back-link">

            ← Back to Game

        </a>

        <h1><?= htmlspecialchars($game['cg_name']) ?> – Clues</h1>

    </div>



    <div class="clue-container">



        <?php foreach ($clues as $i => $clue): ?>

            <div class="clue-card">



                <div class="clue-card-header">

                    <div class="clue-title">Clue <?= $i + 1 ?></div>

                    <div class="clue-badge">Editable</div>

                </div>



                <!-- Hidden legend -->

                <input type="hidden"

               class="clue-legend"

               value="<?= htmlspecialchars($clue['legend'] ?? '') ?>">



                <label>Clue Text</label>

                <textarea class="clue-text-edit" rows="4">

<?= htmlspecialchars($clue['clue']) ?>

        </textarea>



                <div class="clue-score">

                    <label>Penalty Score</label>

                    <input type="number"

                        class="clue-penalty"

                        value="<?= htmlspecialchars($clue['score'] ?? 0) ?>">

                </div>



            </div>

        <?php endforeach; ?>





    </div>



    <div class="editor-actions">

        <button class="btn primary" onclick="saveClues()">Save Changes</button>

    </div>



</div>



<script>

function saveClues() {



    const cards = document.querySelectorAll(".clue-card");

    let data = [];



    cards.forEach((card, i) => {



        data.push({

            legend: card.querySelector(".clue-legend").value,

            order: i + 1,

            score: card.querySelector(".clue-penalty").value,

            clue: card.querySelector(".clue-text-edit").value.trim()

        });



    });



    fetch("save_clues.php", {

        method: "POST",

        headers: {"Content-Type":"application/json"},

        body: JSON.stringify({

            cg_id: <?= $cg_id ?>,

            clues: data

        })

    })

    .then(res => res.json())

    .then(res => {

        if(res.status === "success") {

            alert("Clues updated successfully");

        }

    });

}



</script>



<?php //require "../layout/footer.php"; ?>
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



/* Fetch game title */

$stmt = $conn->prepare("

    SELECT cg_name

    FROM card_group

    WHERE cg_id = ?

");

$stmt->bind_param("i", $cg_id);

$stmt->execute();

$game = $stmt->get_result()->fetch_assoc();



$pageTitle = "Game Cards";

$pageCSS   = "/library/game_cards.css";

require "../layout/header.php";



/* Fetch cards */

$stmt = $conn->prepare("

    SELECT cu_id, cu_name, cu_description, cu_sequence

    FROM card_unit

    WHERE cu_card_group_pkid = ?

    ORDER BY cu_sequence ASC

");

$stmt->bind_param("i", $cg_id);

$stmt->execute();

$result = $stmt->get_result();



$cards = [];

while ($row = $result->fetch_assoc()) {

    $cards[] = $row;

}



$active = $cards[0] ?? null;

?>



<!-- HEADER -->

<div class="editor-header">

    <a href="view_game.php?cg_id=<?= $cg_id ?>" class="back-link">

        ← Back to Game

    </a>

    <h1><?= htmlspecialchars($game['cg_name']) ?></h1>

</div>



<!-- EDITOR -->

<div class="cards-editor">



    <!-- LEFT -->

    <div class="cards-list">

        <h3>Cards (<?= count($cards) ?>)</h3>

        <div class="cards-scroll">

        <?php foreach ($cards as $i => $card): ?>

            <div class="card-item <?= $i === 0 ? 'active' : '' ?>"

                 data-id="<?= $card['cu_id'] ?>"

                 data-title="<?= htmlspecialchars($card['cu_name']) ?>"

                 data-desc="<?= htmlspecialchars($card['cu_description']) ?>">

                <strong><?= htmlspecialchars($card['cu_name']) ?></strong>

                <span>Card <?= $card['cu_sequence'] ?></span>

            </div>

        <?php endforeach; ?>

        </div>

    </div>



    <!-- CENTER -->

    <div class="card-editor">

        <h3>Card Content</h3>



        <label>Card Title</label>

        <input type="text" id="cardTitle" value="<?= htmlspecialchars($active['cu_name'] ?? '') ?>">



        <label>Description / Scenario</label>

        <textarea id="cardDesc" rows="6"><?= htmlspecialchars($active['cu_description'] ?? '') ?></textarea>



        <div class="editor-actions">

            <button class="btn secondary" onclick="resetCard()">Discard Changes</button>

            <button class="btn primary" onclick="saveCard()">Save Card</button>

        </div>

    </div>



    <!-- RIGHT -->

    <div class="card-preview">

        <h3>Live Preview</h3>



        <div class="preview-card">

            <h4 id="previewTitle"><?= htmlspecialchars($active['cu_name'] ?? '') ?></h4>

            <p id="previewDesc"><?= htmlspecialchars($active['cu_description'] ?? '') ?></p>

        </div>

    </div>



</div>



<script>

const items = document.querySelectorAll('.card-item');

const cardTitle = document.getElementById('cardTitle');

const cardDesc = document.getElementById('cardDesc');

const previewTitle = document.getElementById('previewTitle');

const previewDesc = document.getElementById('previewDesc');



let activeId = <?= $active['cu_id'] ?? 0 ?>;

let originalTitle = cardTitle.value;

let originalDesc = cardDesc.value;



items.forEach(item => {

    item.addEventListener('click', () => {

        items.forEach(i => i.classList.remove('active'));

        item.classList.add('active');



        activeId = item.dataset.id;

        cardTitle.value = item.dataset.title;

        cardDesc.value = item.dataset.desc;



        previewTitle.textContent = item.dataset.title;

        previewDesc.textContent = item.dataset.desc;



        originalTitle = item.dataset.title;

        originalDesc = item.dataset.desc;

    });

});



cardTitle.oninput = () => previewTitle.textContent = cardTitle.value;

cardDesc.oninput = () => previewDesc.textContent = cardDesc.value;



function resetCard() {

    cardTitle.value = originalTitle;

    cardDesc.value = originalDesc;

    previewTitle.textContent = originalTitle;

    previewDesc.textContent = originalDesc;

}



function saveCard() {

    fetch("save_card.php", {

        method: "POST",

        headers: { "Content-Type": "application/json" },

        body: JSON.stringify({

            cu_id: activeId,

            title: cardTitle.value,

            description: cardDesc.value

        })

    })

    .then(res => res.json())

    .then(data => {

        if (data.status === "success") {

            alert("Card updated successfully");

        } else {

            alert("Save failed");

        }

    });

}

</script>



<?php //require "../layout/footer.php"; ?>


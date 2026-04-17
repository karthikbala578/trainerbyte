<?php
session_start();

include("../include/coreDataconnect.php");

include("byteguess_data.php");



/* Get game ID safely */
$gamedata = new GameRound();


$cg_id = $gamedata->getGameIdFromRequest();



/* Fetch game */

$gameCards = $gamedata->getCardGroupById($conn, $cg_id);

// print_r(count($gameCards['cardunit']));
$total_no_cards = count($gameCards['cardunit']);

$game_id   = (int)($_GET['game_id'] ?? 0);

$game_code = $_SESSION['event_code'] ?? '';

// $game_status = $gamedata->getLatestGameStatus($conn, $game_id);

// echo "Game Status: " . $game_status; // Debugging line
// $mod_game_status = 1;

// $stmt = $conn->prepare("SELECT * FROM tb_event_user_score WHERE user_id = ? AND event_id = ? AND mod_game_id = ? AND mod_game_status = ?");

// $stmt->bind_param("iiii", $_SESSION['user_id'], $_SESSION['event_id'], $game_id, $mod_game_status);

$stmt = $conn->prepare("SELECT * FROM tb_event_user_score WHERE user_id = ? AND event_id = ? AND mod_game_id = ?");



$stmt->bind_param("iii", $_SESSION['user_id'], $_SESSION['event_id'], $game_id);



$stmt->execute();


$row = $stmt->get_result()->fetch_assoc();


$game_status = $row['game_status'];

// print_r($row);
// echo "Game Status: " . $game_status;

/* Fetch cards */



$confirmedIds = [];



$active = $gameCards['cardunit'][0] ?? null;


$totalrevealedCards = (int)$gameCards['cardgroup']['cg_max'];  // 🔑 from DB (max open cards)



// $cardsSelected = 0;

$hints = $gameCards['cardgroup']['cg_clue'];

$hintArray = json_decode($hints, true);

if ($hintArray === null) {
    $hints_count = 0;
}else{
    $hints_count = count($hintArray);
}

// echo $hints_count;die;
$_SESSION['hints'] = $hints_count; 

// print_r($_SESSION['user_id']);

// 1. Fetch the summary if the game is completed
// Assuming $game_status == 2 means 'completed' (adjust based on your DB logic)
$isCompleted = $game_status; 
// echo "Is Completed: " . $isCompleted . "<br>";

if ($game_status === 'completed') {
    // Fetch the summary JSON from your user score table
    // Replace with your actual table and column names
    $stmtSum = $conn->prepare("SELECT game_summary FROM tb_event_user_score WHERE event_id = ? AND user_id = ? AND mod_game_id = ?");
    $stmtSum->bind_param("iii", $_SESSION['event_id'], $_SESSION['user_id'], $game_id);
    $stmtSum->execute();
    $sumResult = $stmtSum->get_result()->fetch_assoc();

    // print_r($sumResult);

    $summary = json_decode($sumResult['game_summary'] ?? '{}', true);

    // Override PHP variables with Summary Data
    $confirmedIds = $summary['opened_card_ids'] ?? [];
    $cardsSelected = count($confirmedIds);
    $used_hints = $summary['used_hints'] ?? 0;
    $remaining_from_sum = $summary['remaining_cards'] ?? 0;
    $totalrevealedCards = $summary['opened_cards'] + $summary['remaining_cards'];
    // $cardsSelected = $summary['opened_cards'];
    // echo ($cardsSelected);

} else {
    // Normal Play Mode: Fallback to existing logic
    $confirmedIds = []; // Or fetch from active session if needed
    $cardsSelected = 0;
    $used_hints = 0;
}
// echo ($cardsSelected);
?>

<!DOCTYPE html>

<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<link rel="stylesheet" type="text/css" href="css_site/byteguess_cardpage.css">

<link rel="stylesheet" type="text/css" href="css_site/footer.css">

<link rel="stylesheet" href="assets/fontawesome/css/all.min.css">

<script src="https://kit.fontawesome.com/84b78c6f4c.js" crossorigin="anonymous"></script>

<style>
    .nav-controls {
        margin-right: 7%;
    }
</style>
</head>



<body>

<?php

        $stmt = $conn->prepare("

            SELECT cg_id, cg_guidelines, cg_play_guide_image

            FROM card_group WHERE cg_id=$cg_id

        ");

        $stmt->execute();

        $result = $stmt->get_result();

        $template = $result->fetch_assoc();

        

?>

<!-- <div class="main-layout" id="mainLayout"> -->

    <?php include("sidenav.php"); ?>

<div class="main-layout" id="mainLayout">



    



    <!-- STATS BAR -->



    <div class="stats-bar" id="stats-bar">



        <div class="stat-box">

            <div class="stat-left">

                <div class="stat-title">Cards Revealed</div>

                <div class="stat-value">

                    <span id="revealedCount"><?php echo $cardsSelected; ?></span> / 

                    <span id="totalReveals"><?php echo $totalrevealedCards; ?></span>

                </div>

            </div>

        </div>



        <div class="stat-box">

            <div class="stat-left">

                <div class="stat-title">Cards Remaining</div>

                <div class="stat-value">

                    <span id="remainingCount"><?php echo $totalrevealedCards - $cardsSelected; ?></span>

                </div>

            </div>

        </div>



        <?php 
            // Determine the display logic
            $showBox = false;
            if ($isCompleted === 'completed') {
                $showBox = true; // Always show if completed
            } elseif ($isCompleted === 'in_progress' && $hints_count > 0) {
                $showBox = true; // Show if in progress AND hints exist
            }
            // If it's in_progress and hints == 0, $showBox stays false
        ?>

        <div class="stat-box" 
            style="display: <?php echo $showBox ? 'block' : 'none'; ?>; cursor: <?php echo ($isCompleted === 'in_progress') ? 'pointer' : 'default'; ?>;"
            <?php if ($isCompleted === 'in_progress' && $hints_count > 0): ?> 
                onclick='hintInfo(<?php echo htmlspecialchars(json_encode($hintArray), ENT_QUOTES, "UTF-8"); ?>)' 
            <?php endif; ?>>
            
            <div class="stat-top">
                <div class="stat-title">
                    <?php 
                        echo ($isCompleted === 'completed') ? "Hints Used" : "Remaining Hints";
                    ?>
                </div>

                <div class="stat-value">
                    <span id="hintCount">
                        <?php 
                        if ($isCompleted === 'completed') {
                            echo (int)$used_hints;
                        } else {
                            echo (int)$hints_count;
                        }
                        ?>
                    </span>
                </div>
            </div>
        </div>



    </div>

    <div id="cardView" class="overlay">



        <div class="content">



            <span id="closeBtn" class="close-btn" onclick="closeModal()" style="display: none;">BACK</span>



            



            <div class="body" id="body">



            </div>



        </div>



    </div>

    <div id="hint-view" class="hints-container" style="display: none;">
        <div id="card-container" class="cards-grid">
            </div>
        
        <div class="btn-control">
            <button onclick="closeHints()" class="back-btn">BACK</button>
        </div>
    </div>



    <?php 

        // Determine if the game is currently playable

        // echo $game_status;

        $canFlip = ($game_status === 'in_progress'); 

    ?>



    <div class="cards-grid" id="cards-grid">

        <?php foreach ($gameCards['cardunit'] as $card): 

            $isConfirmed = in_array($card['cu_id'], $confirmedIds);

            $cardTile = "card/".$card['cu_image'];

            

            // A card is "Openable" if it's already revealed OR if the game is in-progress

            $isClickable = ($isConfirmed || $canFlip);

        ?>

            <div class="card <?php echo $isConfirmed ? 'revealed' : 'locked'; ?> <?php echo (!$isClickable) ? 'disabled' : ''; ?>" 

                data-card='<?php echo htmlspecialchars(json_encode($card)); ?>'

                style="

                    background-image: <?php echo !$isConfirmed ? "url('$cardTile')" : "none"; ?>; 

                    background-size: cover;

                    background-color: <?php echo !$isConfirmed ? "#1572db" : "#9395b3"; ?>;

                    <?php if (!$isClickable) echo 'cursor: not-allowed; opacity: 0.7;'; ?>

                "

                <?php if ($isClickable): ?>

                    onclick="selectCard(this, <?php echo htmlspecialchars(json_encode($card)); ?>)"

                <?php endif; ?>>

                

                <div class="card-title"><?php echo str_pad($card['cu_name'], 2, '0', STR_PAD_LEFT); ?></div>

                <div class="card-status">

                    <?php 

                        if ($isConfirmed) echo 'REVEALED';

                        else if ($game_status === 'completed') echo 'LOCKED';

                        else echo 'TAP TO REVEAL';

                    ?>

                </div>

            </div>

        <?php endforeach; ?>

    </div>

</div>



  <!-- FOOTER -->

    <div class="ai-chat-widget">
    <div class="bot-button" id="botIcon">
        <div class="online-status"></div>
        <img src="https://img.icons8.com/ios-filled/100/ffffff/bot.png" alt="AI Bot">
    </div>
    <div id="aiBubble" class="chat-bubble">
        <h4 id="aiHeader">We're Online!</h4>
        <p id="aiMsg">How may I help you today?</p>
    </div>
</div>

<div class="footer-action" id="footer-action">
    <div class="footer-container">
        <div class="nav-controls">
            <?php if ($game_status == 'completed'): ?>
                <div class="arrow-nav full-width">
                    <div class="arrow-btn left-btn" onclick="goPrev()">PREVIOUS</div>
                    <div class="arrow-btn right-btn" onclick="goNext()">NEXT</div>
                </div>
            <?php else: ?>
                <div class="button-nav" id="finishReviewWrap">
                    <button class="finish-btn" id="finishBtn">Finish Review</button>
                </div>
                <div class="arrow-nav">
                    <div class="arrow-btn left-btn" onclick="goPrev()">PREVIOUS</div>
                    <div class="spacer"></div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>













<script>



const navEntries = performance.getEntriesByType('navigation');

// if (navEntries.length > 0 && navEntries[0].type === 'reload') {

//     console.log("F5 Refresh detected: Clearing hints and cards.");

//     sessionStorage.removeItem('revealedHintIndices');

//     sessionStorage.removeItem('totalHintPenalty');

//     sessionStorage.removeItem('selectedCards'); // This keeps your blue cards revealed unless refreshed

// }



const GAME_ID = <?php echo (int)$cg_id; ?>;



const MAX_OPEN = <?php echo (int)$totalrevealedCards; ?>;



let total_no_cards = <?php echo (int)$total_no_cards; ?>;



const finishBtn = document.getElementById("finishBtn");



if (finishBtn) {





    finishBtn.onclick = () => {

        // 1. Check if at least one card is revealed

        let revealed = Number(document.getElementById("revealedCount").innerText);



        if (revealed === 0) {

            // Trigger your AI Bot message

            showAIMessage("Action Required!", "Please reveal at least 1 card before finishing your review.");

            

            // Optional: Add a shake effect to the cards-grid to draw attention

            const grid = document.getElementById("cards-grid");

            grid.style.animation = "shake 0.5s";

            setTimeout(() => { grid.style.animation = ""; }, 500);

            return; // Stop the function here

        }



        // 2. Original Logic continues if revealed > 0

        let balancedHint = Number(document.getElementById("hintCount").innerText);

        let used_hints = <?php echo (int)$hints_count; ?> - balancedHint;

        const openedIds = JSON.parse(sessionStorage.getItem("selectedCards") || "[]");



        const gameSummary = {

            total_cards: total_no_cards,

            opened_cards: revealed,

            remaining_cards: MAX_OPEN - revealed,

            used_hints: used_hints,

            opened_card_ids: openedIds

        };



        sessionStorage.setItem("gameSummary", JSON.stringify(gameSummary));

        window.location.href = "bytedemo-submit_final.php?game_id=" + GAME_ID;

    };



}







    function hintInfo(hints) {
        let hintData = (typeof hints === 'string') ? JSON.parse(hints) : hints;
        const revealedIndices = JSON.parse(sessionStorage.getItem('revealedHintIndices') || "[]");

        const cardGrid = document.getElementById('cards-grid');
        const hintView = document.getElementById('hint-view');
        const footer = document.getElementById('footer-action');
        const container = document.getElementById('card-container');

        cardGrid.style.display = 'none';
        footer.style.display = 'none';
        hintView.style.display = 'block';

        if (hintData && Array.isArray(hintData)) {
            let html = "";

            hintData.forEach((item, index) => {
                const isRevealed = revealedIndices.includes(index);
                const jsClue = JSON.stringify(item.clue || "").replace(/"/g, '&quot;');
                const score = parseInt(item.score) || 0;

                // Determine color based on point value (Priority logic)
                let borderColor = "#1572db"; // Default Blue
                if (score >= 5) borderColor = "#e74c3c"; // High Penalty - Red
                else if (score >= 2) borderColor = "#f1c40f"; // Medium Penalty - Yellow

                if (isRevealed) {
                    // Display the revealed hint content
                    html += `
                    <div class="card revealed" style="background: #fff; color: #000; cursor: default; border: 3px solid ${borderColor};">
                        <div class="card-title" style="font-size: 14px; color: ${borderColor};">HINT ${index + 1}</div>
                        <div style="padding: 10px; font-size: 13px; text-transform: uppercase;">
                            ${item.clue}
                        </div>
                        <div class="card-status" style="color: #888;">REVEALED</div>
                    </div>`;
                } else {
                    // Display the "Locked" hint tile
                    html += `
                    <div class="card locked" onclick="selectHint(this, ${jsClue}, ${score}, ${index})" 
                        style="border: 3px solid ${borderColor};color: #000;">
                        
                        <div class="card-status">TAP TO REVEAL HINT</div>
                    </div>`;
                }
            });
            container.innerHTML = html;
        }
    }

    // Function to go back to the card grid
    function closeHints() {
        document.getElementById('cards-grid').style.display = 'grid';
        // document.getElementById('stats-bar').style.display = 'grid';
        document.getElementById('footer-action').style.display = 'block';
        document.getElementById('hint-view').style.display = 'none';
    }

    function selectHint(element, clue, score, index) {
        // 1. Save to session
        let revealed = JSON.parse(sessionStorage.getItem('revealedHintIndices') || "[]");
        if(!revealed.includes(index)) {
            revealed.push(index);
            sessionStorage.setItem('revealedHintIndices', JSON.stringify(revealed));
        }

        // 2. Update Stats (Subtract from the available hints display)
        const initialHints = <?php echo (int)$hints_count; ?>;
        document.getElementById('hintCount').innerText = initialHints - revealed.length;

        // 3. Visual Flip Effect
        element.classList.remove('locked');
        element.classList.add('revealed');
        element.style.background = "#fff";
        element.style.color = "#000";
        
        // 4. Show content immediately
        element.innerHTML = `
            <div class="card-title" style="font-size: 14px; color: #1572db;">HINT ${index + 1}</div>
            <div style="padding: 10px; font-size: 13px; text-transform: uppercase; font-weight: bold;">${clue}</div>
            <div class="card-status" style="color: #888;">REVEALED</div>
        `;

        // 1. Get the current value and ensure it's a Number (default to 0)
        let currentTotalPenalty = Number(sessionStorage.getItem('totalHintPenalty')) || 0;

        // 2. Add the new score (ensuring 'score' is also a Number)
        let newTotalPenalty = currentTotalPenalty + Number(score);

        // 3. Save it back
        sessionStorage.setItem('totalHintPenalty', newTotalPenalty);

        // Verification
        console.log("New Total:", newTotalPenalty);

        // 5. Friendly AI Notification
        if(score > 0){
            showAIMessage("Hint Activated", "A penalty of " + newTotalPenalty + " points has been applied to your score.");

        }
        
    }



// Check if the page was reloaded





const MAX_REVEALS = <?php echo $totalrevealedCards; ?>;   // max allowed reveals (e.g. 5)



let openedCount = 0;





// Define global variables from PHP

const isCompleted = <?php echo ($game_status === 'completed') ? 'true' : 'false'; ?>;

let revealedCount = <?php echo (int)$cardsSelected; ?>;



// --- 1. DOM CONTENT LOADED ---

document.addEventListener("DOMContentLoaded", function () {

    // Attach click listeners to all cards

    document.querySelectorAll(".card").forEach(function (cardEl) {

        cardEl.addEventListener("click", function () {

            // Get data from PHP-injected attribute or direct argument

            // Note: Ensure your HTML div has: data-card='<?php echo json_encode($card); ?>'

            if (this.dataset.card) {

                const cardData = JSON.parse(this.dataset.card);

                selectCard(this, cardData);

            }

        });

    });



    // STOP HERE IF COMPLETED - Don't let session storage overwrite PHP values

    if (isCompleted) {

        console.log("Review Mode: Skipping session storage sync.");

        return; 

    }



    // --- ACTIVE PLAY ONLY: Sync from Session ---

    const selectedCards = JSON.parse(sessionStorage.getItem('selectedCards') || "[]");

    revealedCount = selectedCards.length;

    

    document.getElementById("revealedCount").innerText = revealedCount;

    document.getElementById("remainingCount").innerText = Math.max(0, MAX_OPEN - revealedCount);



    document.querySelectorAll(".card").forEach(cardEl => {

        if (cardEl.dataset.card) {

            const cardData = JSON.parse(cardEl.dataset.card);

            if (selectedCards.includes(cardData.cu_id)) {

                cardEl.classList.remove('locked');

                cardEl.classList.add('revealed');

                cardEl.style.backgroundImage = "none";

                cardEl.style.backgroundColor = "#fff";

                cardEl.style.color = "#000";

                cardEl.querySelector('.card-status').innerText = 'REVEALED';

            }

        }

    });

});



// --- 2. THE SELECTION LOGIC ---

function selectCard(element, cardData) {

    // Get the live status from PHP

    const currentStatus = "<?php echo $game_status; ?>";



    // 1. If the card is already revealed, always allow viewing (Review Mode)

    if (element.classList.contains('revealed')) {

        openCard(cardData);

        return;

    }



    // 2. STRICTOR CHECK: Only allow flipping NEW cards if status is 'in-progress'

    if (currentStatus !== 'in_progress') {

        showAIMessage("Game Inactive", "You can only reveal new cards while the game is in-progress.");

        return;

    }



    // 3. Reveal limit check

    if (revealedCount >= MAX_OPEN) {

        showAIMessage("Limit Reached!", "You've used all " + MAX_OPEN + " reveals.");

        return;

    }



    // --- Proceed with revealing the card ---

    saveCardToSession(cardData.cu_id);

    element.classList.remove('locked');

    element.classList.add('revealed');

    element.style.background = "#fff"; // Revealed background color

    element.style.color = "#000";

    element.querySelector('.card-status').innerText = 'REVEALED';



    revealedCount++;

    updateStats();

    openCard(cardData);

}



// --- 3. UTILITY FUNCTIONS ---

function updateStats() {

    document.getElementById('revealedCount').innerText = revealedCount;

    document.getElementById('remainingCount').innerText = Math.max(0, MAX_OPEN - revealedCount);

    

    // Update Hint count only if not in review

    if (!isCompleted) {

        const initialHints = <?php echo (int)$hints_count; ?>;

        const used = JSON.parse(sessionStorage.getItem('revealedHintIndices') || "[]").length;

        document.getElementById('hintCount').innerText = initialHints - used;

    }

}



function saveCardToSession(cardId) {

    let idArray = JSON.parse(sessionStorage.getItem('selectedCards') || "[]");

    if (!idArray.includes(cardId)) {

        idArray.push(cardId);

        sessionStorage.setItem('selectedCards', JSON.stringify(idArray));

    }

}



// Ensure Hint Display stays correct on back-button/refresh

window.addEventListener('pageshow', function() {

    if (!isCompleted) {

        updateStats();

    }

});









    function openCard(data) {
        const modal = document.getElementById('cardView');
        const body = document.getElementById('body');
        const closeBtn = document.getElementById('closeBtn');
        const sidebar = document.getElementById('sidebar');
        const statusbar = document.getElementById('stats-bar');
        const cardpage = document.getElementById('cards-grid');
        const footer = document.getElementById('footer-action');

        // Hide everything initially
        closeBtn.style.display = "none";
        sidebar.style.display = "none";
        statusbar.style.display = "none";
        cardpage.style.display = "none";
        footer.style.display = "none"; // Keep footer hidden until image loads

        if (data.cu_treasure_image !== null) {
            body.innerHTML = `
                <div id="modalLoader" class="loader-container" style="display: flex; justify-content: center; align-items: center; height: 300px;">
                    <div class="spinner"></div>
                    <div class="loading-text" style="margin-left: 10px;">LOADING UNIT...</div>
                </div>
                <img id="modalImg" src="card/unit/${data.cu_treasure_image}" 
                    onload="handleImageLoad()" 
                    style="max-width:100%; max-height:90vh; object-fit:contain; display:none; margin:auto;">
            `;
        } else {
            // Text-based cards show footer immediately
            body.innerHTML = `
                <div class="split-guideline-wrapper">
                    <div class="guideline-visual">
                        <img src="images/reviewcard_img.jpeg" alt="Card Image">
                    </div>
                    <div class="guideline-content-area">
                        <div class="rich-text-uppercase">${data.cu_description}</div>
                    </div>
                </div>`;
            // footer.style.display = "block"; 
            closeBtn.style.display = "block";
        }

        modal.style.display = "block";
    }

    // Helper function to swap loader for image
    function handleImageLoad() {
        const loader = document.getElementById('modalLoader');
        const img = document.getElementById('modalImg');
        const closeBtn = document.getElementById('closeBtn');
        const footer = document.getElementById('footer-action');

        if (loader) loader.style.display = 'none'; // Hide Loader
        if (img) img.style.display = 'block';      // Show Image
        if (closeBtn) closeBtn.style.display = "block";
        // if (footer) footer.style.display = 'block'; // Show Back Button/Footer
    }







function closeModal() {



    document.getElementById('cardView').style.display = "none";



    document.getElementById('sidebar').style.display = "flex";



    document.getElementById('stats-bar').style.display = "grid";



    document.getElementById('cards-grid').style.display = "grid";



    document.getElementById('footer-action').style.display = "block";



}







// Close modal if user clicks outside the box



window.onclick = function(event) {



    let modal = document.getElementById('cardView');



    if (event.target == modal) {



        closeModal();



    }



}







function goNext() {



    window.location.href = "byteguess-final_results.php?game_id=" + GAME_ID;



}







function goPrev() {



    window.location.href = "byteguess_guideline.php?game_id=" + GAME_ID;



}













function showAIMessage(title, message) {



    const bubble = document.getElementById('aiBubble');



    const header = document.getElementById('aiHeader');



    const msg = document.getElementById('aiMsg');







    // Set the content



    header.innerText = title;



    msg.innerText = message;







    // Show the bubble



    bubble.classList.add('show');







    // Auto-hide after 4 seconds



    setTimeout(() => {



        bubble.classList.remove('show');



    }, 4000);



}



// function updateHintDisplay() {

//     const revealedHints = JSON.parse(sessionStorage.getItem('revealedHintIndices') || "[]");

//     const totalHints = <?php echo $hints_count; ?>;

    

//     // Calculate remaining hints

//     const remainingHints = totalHints - revealedHints.length;

    

//     const hintCountEl = document.getElementById('hintCount');

//     if (hintCountEl) {

//         hintCountEl.innerText = remainingHints;

//     }

// }







function toggleSidebar() {

    const sidebar = document.getElementById('sidebar');

    const overlay = document.getElementById('sidebarOverlay');



    // Toggle the 'active' class on both

    sidebar.classList.toggle('active');

    overlay.classList.toggle('active');

}



function updatePageName() {

    const savedName = localStorage.getItem('currentPageName');

    if (savedName) {

        document.title = savedName; // Updates the browser tab

        // If you have an actual element on the page to update:

        // document.getElementById('display-name').innerText = savedName;

    }

}



// Run when the page first loads

updatePageName();



// Run specifically when returning via the Back button

window.addEventListener('pageshow', function(event) {

    updatePageName();

});

</script>



</body>

</html>






<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Puzzle Hints UI</title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <style>

        /* style.css */

/* Container for the whole page (non-scrolling) */

body {

    margin: 0;

    height: 100vh;

    display: flex;

    flex-direction: column;

    overflow: hidden; /* Prevents the whole page from scrolling */

    background-color: #fcfcfc;

    font-family: 'Segoe UI', sans-serif;

}



/* The specific scrollable section */

.content-scroll-area {

    flex: 1; /* Takes up remaining space */

    overflow-y: auto; /* Enables vertical scroll */

    padding: 10px 20px;

}



.card-container {

    display: flex;
    gap: 20px;
    flex-wrap: wrap;
    justify-content: center;
    max-width: 1200px;
    margin: 0 auto;
    scrollbar-width: thin;
    scrollbar-color: #cbd5e0 #f7fafc;
    flex: 1;
    overflow-y: auto;
    padding: 10px 10px;
    margin-top: 30px;
    margin-bottom: 40px;
    position: relative;
    z-index: 10;
    height: 450px;
    border-bottom: 2px solid;

}



/* --- Optional: Custom Sleek Scrollbar --- */

.content-scroll-area::-webkit-scrollbar {

    width: 8px;

}



.content-scroll-area::-webkit-scrollbar-track {

    background: #f1f1f1;

}



.content-scroll-area::-webkit-scrollbar-thumb {

    background: #cbd5e0; 

    border-radius: 10px;

}



.content-scroll-area::-webkit-scrollbar-thumb:hover {

    background: #a0aec0;

}



.card {

    background: white;

    width: 260px;

    border: 1px solid #e0e4ec;

    border-radius: 12px;

    padding: 30px 25px;

    display: flex;

    flex-direction: column;

    box-shadow: 0 4px 6px rgba(0,0,0,0.02);

}



.header {

    display: flex;

    justify-content: space-between;

    align-items: center;

    margin-bottom: 25px;

}



.status {

    text-align: justify;

    text-justify: inter-word;

    hyphens: auto;

    padding: 15px;

    height: 150px;

    display: flex;

    align-items: center;

}



.points {

    color: #8a3ffc; /* Purple color from image */

    font-weight: 800;

    font-size: 0.9rem;

    letter-spacing: 0.5px;

}



.icon {

    color: #cbd5e0; /* Muted gray icon */

    font-size: 1.4rem;

}



h3 {

    color: #1a202c;

    margin: 0 0 15px 0;

    font-size: 1.3rem;

    font-weight: 700;

}



p {

    color: #718096;

    font-size: 0.95rem;

    line-height: 1.5;

    margin: 0 0 40px 0;

    flex-grow: 1; /* Pushes button to bottom */

}



.reveal-btn {

    background: white;

    border: 1px solid #e0e4ec;

    border-radius: 8px;

    padding: 12px;

    color: #2d3748;

    font-weight: 700;

    letter-spacing: 1px;

    cursor: pointer;

    transition: all 0.2s ease;

    justify-content: center;

    display: flex;

    text-transform: uppercase;

}



.reveal-btn:hover {

    background-color: #f7fafc;

    border-color: #cbd5e0;

}

/* Container on the LEFT */

.ai-chat-widget {

    position: fixed;

    bottom: 10px;

    left: 30px;

    display: flex;

    align-items: center;

    font-family: 'Segoe UI', Arial, sans-serif;

    z-index: 1000;

}

/* The Speech Bubble */

.chat-bubble {

    visibility: hidden; /* Using visibility for smoother transitions */

    opacity: 0;

    background: white;

    padding: 12px 18px;

    border-radius: 12px;

    box-shadow: 0 8px 20px rgba(0,0,0,0.15);

    margin-left: 15px;

    position: relative;

    min-width: 200px;

    order: 2;

    transition: all 0.3s ease-in-out;

    transform: translateX(-10px);

}



.chat-bubble.show {

    visibility: visible;

    opacity: 1;

    transform: translateX(0);

}



/* Arrow pointing LEFT */

.chat-bubble::after {

    content: "";

    position: absolute;

    top: 50%;

    left: -8px;

    transform: translateY(-50%);

    border-width: 8px 8px 8px 0;

    border-style: solid;

    border-color: transparent white transparent transparent;

}



.chat-bubble h4 { margin: 0 0 4px 0; color: #333; font-size: 15px; }

.chat-bubble p { margin: 0; color: #666; font-size: 13px; line-height: 1.4; }



/* Blue Bot Icon (Squircle) */

.bot-button {

    width: 60px;

    height: 60px;

    background-color: #1a73e8; /* Blue from your image */

    border-radius: 16px;

    display: flex;

    justify-content: center;

    align-items: center;

    position: relative;

    order: 1;

    box-shadow: 0 4px 15px rgba(26, 115, 232, 0.3);

}



.bot-button img { width: 35px; height: auto; }



/* Online Status Dot */

.online-status {

    width: 12px;

    height: 12px;

    background: #00c853;

    border: 2px solid white;

    border-radius: 50%;

    position: absolute;

    top: -3px;

    right: -3px;

}



/* ===== Footer Button ===== */

.footer-action{

    padding-bottom: 15px;

    text-align: right;

    flex-shrink: 0;

}



/* ===== Footer Navigation ===== */

.nav-controls{

    display:flex;

    align-items:center;

    justify-content:flex-end;

    flex-wrap:wrap;

    gap: 15px;

    margin-right: 5%;

}



.back-btn{

    background: #d5d1d1;;

    color:#000;

    border:none;

    padding:12px 22px;

    border-radius:8px;

    font-size:15px;

    cursor:pointer;

}

    </style>

</head>

<body>

<main class="content-scroll-area">

    <div class="card-container" id="card-container">

        



        

    </div>

</main>

<!-- FOOTER -->

    <div class="footer-action">

        <div class="nav-controls">

            <div class="button-nav">

                <!-- <button class="back-btn" onclick='reviewCards()'>Review Cards</button> -->
                <button class="back-btn" onclick="window.location.href='byteguess_demo19.php?game_id=<?php echo $_GET['game_id']; ?>'">
                    Review Cards
                </button>

            </div>

        </div>

    </div>



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

<script >

    // Check if the page is being reloaded/refreshed
if (performance.navigation.type === performance.navigation.TYPE_RELOAD) {
    console.info("Page refreshed: Resetting hint data.");
    
    // Clear only the hint-related session data
    sessionStorage.removeItem('revealedHintIndices');
    sessionStorage.removeItem('totalHintPenalty');
}

document.addEventListener("DOMContentLoaded", function() {
    // If the block above ran (on refresh), these will be null/empty
    const data = sessionStorage.getItem('hints');
    const revealedIndices = JSON.parse(sessionStorage.getItem('revealedHintIndices') || "[]");
    
    if (data) {
        const answers = JSON.parse(data);
        let html = "";

        answers.forEach((item, index) => {
            // Because we cleared the session on refresh, 
            // isAlreadyRevealed will always be false after a refresh.
            const isAlreadyRevealed = revealedIndices.includes(index);
            
            const jsData = JSON.stringify(item.clue).replace(/"/g, '&quot;');
            const jsScore = JSON.stringify(item.score).replace(/"/g, '&quot;');

            html += `
            <div class="card" onclick="selectCard(this, ${jsData}, ${jsScore}, ${index})">
                <div class="header">
                    <span class="points">${item.score} POINTS</span>
                </div>
                <div class="status">REVEAL TO SOLVE FASTER</div>
                <div class="reveal-btn">TAP TO REVEAL</div>
            </div>`;
        });
        document.getElementById('card-container').innerHTML = html;
    }
});

function selectCard(element, cardData, score, index) {
    let revealedHints = JSON.parse(sessionStorage.getItem('revealedHintIndices') || "[]");
    console.log("Data from hint:", revealedHints);
    // 1. Prevent double-counting if the user clicks an already revealed hint
    if (revealedHints.includes(index)) return;

    // 2. Mark this hint index as revealed
    revealedHints.push(index);
    sessionStorage.setItem('revealedHintIndices', JSON.stringify(revealedHints));

    // 3. ACCUMULATE THE PENALTY SCORE
    // 1. Get the current value and ensure it's a Number (default to 0)
    let currentTotalPenalty = Number(sessionStorage.getItem('totalHintPenalty')) || 0;

    // 2. Add the new score (ensuring 'score' is also a Number)
    let newTotalPenalty = currentTotalPenalty + Number(score);

    // 3. Save it back
    sessionStorage.setItem('totalHintPenalty', newTotalPenalty);

    // Verification
    console.log("New Total:", newTotalPenalty);

    // 4. Update UI
    showAIMessage("Hint Viewed!", "Total Penalty: " + newTotalPenalty);
    element.classList.add('revealed');
    element.querySelector('.status').innerText = cardData;
    element.querySelector('.reveal-btn').innerText = " Penalty Applied : " + score;
    
    // Optional: Update the card page hint count if you have a way to refresh it
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



// function reviewCards() {

//     // This goes back one step in history. 

//     // Browsers typically "freeze" the state of the previous page.

//     window.history.back();

// }



</script>

</body>

</html>
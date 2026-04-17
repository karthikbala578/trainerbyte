<?php
/**
 * RiskHOP Game Play
 * Main gameplay interface
 */

require_once '../config.php';
require_once '../functions.php';
require_once 'game_engine.php';

// Get current session
$session = get_current_session();

if (!$session) {
    // No active session, redirect to library
    redirect(GAME_URL . 'index.php');
}
if ($session['game_status'] !== 'playing') {
    redirect(GAME_URL . 'index.php');
}
// Get game data
$game_data = get_game_data($session['matrix_id']);
$game = $game_data['game'];

// Get player investments
$player_investments = get_player_investments($session['id']);

// Determine board class
$board_class = 'board-' . str_replace('x', 'x', strtolower($game['matrix_type']));

// Calculate grid dimensions
$grid_size = (int)explode('x', $game['matrix_type'])[0];
?>
<?php

$snake_count = count($game_data['threats'] ?? []);
$ladder_count = count($game_data['opportunities'] ?? []);

$wildcard_count = 0;
$bonus_count = 0;
$audit_count = 0;

for ($i = 1; $i <= $game['total_cells']; $i++) {

    $cell_info = get_cell_info($game['id'], $i);

    if (!$cell_info) continue;

    if ($cell_info['type'] === 'wildcard') {
        $wildcard_count++;
    }

    if ($cell_info['type'] === 'bonus') {
        $bonus_count++;
    }

    if ($cell_info['type'] === 'audit') {
        $audit_count++;
    }
}

?>
<?php
$threats = [];
$opportunities = [];

if (!empty($player_investments)) {

    foreach ($player_investments as $inv) {

        if ($inv['strategy_type'] === 'threat') {
            $threats[] = $inv;
        }

        if ($inv['strategy_type'] === 'opportunity') {
            $opportunities[] = $inv;
        }

    }

}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($game['game_name'], ENT_QUOTES, 'UTF-8'); ?> - Playing</title>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
     <!-- Styles -->
    <link rel="stylesheet" href="<?php echo ASSETS_URL; ?>css/board.css?v=<?php echo time(); ?>">
    <style>
        .btn-roll-dice.disabled{
opacity:0.5;
cursor:not-allowed;
pointer-events:none;
}
        </style>
  <script>
const ASSETS_URL = "<?php echo ASSETS_URL; ?>";
const GAME_URL = "<?php echo GAME_URL; ?>";
</script>
<script>
        // Initialize game state BEFORE loading game.js
        window.gameInitData = {
    sessionId: <?php echo $session['id']; ?>,
    matrixId: <?php echo $session['matrix_id']; ?>,
    currentCell: <?php echo $session['current_cell']; ?>,
    diceRemaining: <?php echo $session['dice_remaining']; ?>,
    capitalRemaining: <?php echo $session['capital_remaining']; ?>,
    totalCells: <?php echo $game['total_cells']; ?>,
    gameData: <?php echo json_encode($game_data); ?>,
    playerInvestments: <?php echo json_encode($player_investments); ?>,
    threats: <?php echo json_encode($threats); ?>,
    opportunities: <?php echo json_encode($opportunities); ?>
};
    </script>
     <script src="<?php echo ASSETS_URL; ?>js/game.js?v=<?php echo time(); ?>"></script>
     <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
     <script>
        // Wait for DOM and scripts to load
        if (typeof GameState !== 'undefined' && window.gameInitData) {
    Object.assign(GameState, window.gameInitData);
}
    </script>
</head>
<body>

    <div class="game-wrapper">
        <!-- MAIN CONTENT -->
        <div class="game-content">
            <div class="board-section">

                <div class="board-header">
                    <h3><?php echo htmlspecialchars($game['game_name']); ?></h3>
                </div>

                <!-- BOARD AREA -->
                <div class="board-wrapper" style="position:relative;">
                    <div id="wildcardToastArea" class="wildcard-toast-area"></div>
                    <svg class="board-arrows-svg" id="boardArrowsSvg">
                        <defs>
                            <marker id="arrowhead-snake" markerWidth="12" markerHeight="12" refX="10" refY="6" orient="auto">
                                <path d="M2,2 L2,10 L10,6 z" fill="#e74c3c"/>
                            </marker>

                            <marker id="arrowhead-ladder" markerWidth="12" markerHeight="12" refX="10" refY="6" orient="auto">
                                <path d="M2,2 L2,10 L10,6 z" fill="#27ae60"/>
                            </marker>
                        </defs>
                    </svg>
                    <div class="board-grid" style="grid-template-columns: repeat(<?php echo $grid_size; ?>, 1fr);">

                        <?php
                            $total = $game['total_cells'];
                            $size = $grid_size;

                            for ($row = $size; $row >= 1; $row--) {

                                $start = ($row - 1) * $size + 1;
                                $end = $row * $size;

                                if (($size - $row) % 2 == 0) {
                                    $cells = range($end, $start); // reverse row
                                } else {
                                    $cells = range($start, $end); // normal row
                                }

                                foreach ($cells as $i):
                                
                                    $cell_info = get_cell_info($game['id'], $i);
                                    $icon = '';

                                    
                                if ($cell_info) {

                                    if ($cell_info['type'] === 'threat') {
                                        $icon = '<div class="cell-icon icon-threat"><i class="fa-solid fa-bomb fa-lg"></i></div>';
                                    }

                                    if ($cell_info['type'] === 'opportunity') {
                                        $icon = '<div class="cell-icon icon-opportunity"><i class="fa-solid fa-sack-dollar fa-lg"></i></div>';
                                    }

                                    if ($cell_info['type'] === 'bonus') {
                                        $icon = '<div class="cell-icon icon-bonus"><i class="fas fa-star"></i></div>';
                                    }

                                    if ($cell_info['type'] === 'audit') {
                                        $icon = '<div class="cell-icon icon-audit"><i class="fas fa-search"></i></div>';
                                    }

                                    if ($cell_info['type'] === 'wildcard') {
                                        $icon = '<div class="cell-icon icon-wild"><i class="fas fa-question"></i></div>';
                                    }

                                }
                        ?>
                                <div class="cell" data-cell="<?php echo $i; ?>" data-matrix="<?php echo $game['id']; ?>">
                                    <span class="cell-number"><?php echo $i; ?></span>
                                    <?php echo $icon; ?>
                                </div>
                                <?php
                                endforeach;
                            }
                        ?>

                    </div>

                </div>

            </div>
            <!-- REVIEW PANEL -->
            <div class="review-panel">

                <!-- RISK CAPITAL -->
                <div class="panel-card capital-card">
                <div class="panel-label">
                    <i class="fas fa-coins"></i> RISK CAPITAL
                </div>
                <div class="panel-value" id="riskCapital">
                    <?php echo $session['capital_remaining']; ?>
                </div>
                </div>

                <!-- GAME STATS -->
                <div class="panel-card small">
                <div class="panel-label">
                    <i class="fas fa-dice"></i> DICE LEFT
                </div>
                <div class="panel-value highlight" id="diceRemaining">
                    <?php echo $session['dice_remaining']; ?>
                </div>
                </div>

                <div class="panel-card small">
                <div class="panel-label">
                    <i class="fas fa-location-dot"></i> CELL POSITION
                </div>
                <div class="panel-value" id="currentCellStat">
                    <?php echo $session['current_cell']; ?> / <?php echo $game['total_cells']; ?>
                </div>
            </div>
   


    <!-- REVIEW INVESTMENT -->
    <div class="investment-review">

        <div class="review-title">
            <i class="fas fa-chart-line"></i>
            REVIEW INVESTMENT
        </div>

        <!-- THREATS -->
        <div class="review-section">

            <div class="review-section-header review-item negative">
    <i class="fas fa-shield-halved"></i> THREATS MITIGATED
    <span class="badge red">
        <?php echo count($threats); ?> Active
    </span>
</div>
        </div>


        <!-- OPPORTUNITIES -->
        <div class="review-section">

           <div class="review-section-header review-item positive">
    <i class="fas fa-chart-line"></i> GROWTH OPPORTUNITIES
    <span class="badge green">
        <?php echo count($opportunities); ?> Active
    </span>
</div>
        </div>

          <button class="btn-invest" id="investBtn">
                <i class="fas fa-hand-holding-usd"></i> Invest in Strategy
            </button>

    </div>


    <!-- SINGLE DICE -->
    <div class="dice-box">

        <div class="dice-control">

                <div class="dice-display">
                    <img src="<?php echo ASSETS_URL; ?>images/dice/1.png" id="diceImage">
                </div>

                <button class="btn-roll-dice" id="rollDiceBtn">
                    <i class="fas fa-dice"></i> Roll Dice
                </button>

            </div>
        <div class="dice-note">
            Minimum roll: 1, Maximum roll: 6
        </div>

    </div>

</div>
        </div>

    </div>
    <div id="cellHoverCard" class="cell-hover-card">

        <div class="hover-header">
            <div class="hover-icon" id="hoverIcon"></div>
            <div class="hover-title" id="hoverTitle"></div>
        </div>

        <div class="hover-desc" id="hoverDesc"></div>

    </div>
<script>
function hideArrows(){

/* remove arrow highlight */
document.querySelectorAll(".arrow-path").forEach(a=>{
    a.classList.remove("active");
});

/* remove target cell highlight */
document.querySelectorAll(".target-highlight, .target-highlight-ladder, .target-highlight-snake")
.forEach(c=>{
c.classList.remove(
"target-highlight",
"target-highlight-ladder",
"target-highlight-snake"
);
});

}

function highlightArrow(cellNumber){

const arrows = document.querySelectorAll(".arrow-path");

arrows.forEach(arrow => {

const from = parseInt(arrow.dataset.from);
const to = parseInt(arrow.dataset.to);

arrow.classList.remove("active");

const targetCell = document.querySelector(`.cell[data-cell="${to}"]`);
if(targetCell){
targetCell.classList.remove(
"target-highlight",
"target-highlight-ladder",
"target-highlight-snake"
);
}

if(from === parseInt(cellNumber) || to === parseInt(cellNumber)){

arrow.classList.add("active");

if(targetCell){

if(arrow.dataset.type === "ladder"){
targetCell.classList.add("target-highlight-ladder");
}

if(arrow.dataset.type === "snake"){
targetCell.classList.add("target-highlight-snake");
}

}

}

});

}


const cells = document.querySelectorAll('.cell-icon');
const hoverCard = document.getElementById('cellHoverCard');
const hoverTitle = document.getElementById('hoverTitle');
const hoverDesc = document.getElementById('hoverDesc');
const hoverIcon = document.getElementById('hoverIcon');

cells.forEach(cell => {

    cell.addEventListener('mouseenter', async function(e){

        const parentCell = this.closest('.cell');

        const cellNumber = parentCell.dataset.cell;
        const matrixId = parentCell.dataset.matrix;

        hideArrows();

        try{

            const response = await fetch('ajax/get_cell_info.php',{
                method:'POST',
                headers:{'Content-Type':'application/json'},
                body: JSON.stringify({
                    matrix_id: matrixId,
                    cell_number: cellNumber
                })
            });

            const data = await response.json();

            if(!data.success) return;

            const info = data.data.cell_info;

            let title = '';
            let desc = '';

            switch(info.type){

                case 'threat':

                title = info.data.threat_name;
                desc = info.data.threat_description;

                hoverIcon.innerHTML = '<i class="fa-solid fa-bomb fa-lg"></i>';
                hoverIcon.style.background = '#ff6b6b';

                /* show snake */
                highlightArrow(cellNumber);

                break;


                case 'opportunity':

                title = info.data.opportunity_name;
                desc = info.data.opportunity_description;

                hoverIcon.innerHTML = '<i class="fa-solid fa-sack-dollar fa-lg"></i>';
                hoverIcon.style.background = '#2ecc71';

                /* show ladder */
                highlightArrow(cellNumber);

                break;


                case 'audit':

                title = 'Audit';
                desc = 'Allows strategy reallocation';

                hoverIcon.innerHTML = '<i class="fas fa-search"></i>';
                hoverIcon.style.background = '#3498db';

                break;


                case 'bonus':

                title = 'Bonus';
                desc = 'Bonus will be added to capital';

                hoverIcon.innerHTML = '<i class="fas fa-star"></i>';
                hoverIcon.style.background = '#f1c40f';

                break;


                case 'wildcard':

                 title = 'wildcard';
             desc = `Wild Cards introduce unexpected twists that can influence your journey—sometimes in your favor, sometimes not.`;
                hoverIcon.innerHTML = '<i class="fas fa-question"></i>';
                hoverIcon.style.background = '#9b59b6';

                break;

                default:
                return;

            }

            hoverTitle.innerText = title;
          hoverDesc.innerText = desc;
hoverDesc.style.whiteSpace = "pre-line";

            hoverCard.style.display = 'block';

        }catch(err){
            console.log(err);
        }

    });

    cell.addEventListener('mousemove', function(e){

        hoverCard.style.left = (e.clientX + 15) + 'px';
        hoverCard.style.top = (e.clientY + 15) + 'px';

    });

    cell.addEventListener('mouseleave', function(){

        hoverCard.style.display = 'none';
        hideArrows();

    });

});
</script>
<script>
    document.addEventListener("mousemove", function(e){

    const icon = e.target.closest(".cell-icon");

    if(!icon){
        hoverCard.style.display = "none";
        hideArrows();
    }

});
const board = document.querySelector(".board-grid");

board.addEventListener("mouseleave", function(){
    hoverCard.style.display = "none";
    hideArrows();
});
    </script>
<script>



    /* ==========================================
   DRAW BOARD ARROWS (REVIEW BOARD)
========================================== */

function drawBoardArrows(){

    const svg = document.getElementById('boardArrowsSvg');
    const board = document.querySelector('.board-grid');

    if(!svg || !board) return;

    svg.querySelectorAll(".arrow-path").forEach(p => p.remove());

    const gameData = <?php echo json_encode($game_data); ?>;

    const threats = gameData.threats || [];
    const opportunities = gameData.opportunities || [];

    const svgRect = svg.getBoundingClientRect();

    /* GET CELL CENTER */

    function getCellCenter(cellNumber){

    const cell = board.querySelector(`[data-cell="${cellNumber}"]`);
    if(!cell) return null;

    const rect = cell.getBoundingClientRect();

    return {
    x: rect.left + rect.width/2 - svgRect.left,
    y: rect.top + rect.height/2 - svgRect.top
    };

    }

    /* STRAIGHT LADDER ARROW */

    function createStraightArrow(from,to){

    const start = getCellCenter(from);
    const end = getCellCenter(to);

    if(!start || !end) return;

    const path = document.createElementNS('http://www.w3.org/2000/svg','path');

    path.setAttribute("d",`M ${start.x} ${start.y} L ${end.x} ${end.y}`);
    path.setAttribute("class","arrow-path ladder");
    path.setAttribute("marker-end","url(#arrowhead-ladder)");
    path.dataset.from = from;
    path.dataset.to = to;
    path.dataset.type = "ladder";

    svg.appendChild(path);

    }

    /* SNAKE ZIGZAG ARROW */

    function createSnakeArrow(from,to){

const start = getCellCenter(from);
const end = getCellCenter(to);

if(!start || !end) return;

const path = document.createElementNS('http://www.w3.org/2000/svg','path');

path.setAttribute("d",`M ${start.x} ${start.y} L ${end.x} ${end.y}`);
path.setAttribute("class","arrow-path snake");
path.setAttribute("marker-end","url(#arrowhead-snake)");

path.dataset.from = from;
path.dataset.to = to;
path.dataset.type = "snake";

svg.appendChild(path);

}

    /* DRAW ALL ARROWS */

    threats.forEach(t => createSnakeArrow(t.cell_from,t.cell_to));
    opportunities.forEach(o => createStraightArrow(o.cell_from,o.cell_to));

}

/* RUN AFTER PAGE LOAD */

window.addEventListener("load",()=>{

setTimeout(()=>{

    drawBoardArrows();

    updateArrowActivation(window.gameInitData.currentCell);

},300);

});

/* ==========================================
   PLAYER POSITION ARROW ACTIVATION
========================================== */

function updateArrowActivation(cell){

    const arrows = document.querySelectorAll(".arrow-path");

    arrows.forEach(arrow => {

        const from = parseInt(arrow.dataset.from);
        const type = arrow.dataset.type;

        arrow.classList.remove("active");

        if(from === parseInt(cell)){

            let invested = false;

            if(type === "snake"){

    const threat = window.gameInitData.gameData.threats.find(
        t => parseInt(t.cell_from) === from
    );

    if(threat){
        invested = window.gameInitData.playerInvestments.some(inv =>
            inv.strategy_type === "threat" &&
            parseInt(inv.risk_id) === parseInt(threat.id)
        );
    }

}
if(type === "ladder"){

    const opp = window.gameInitData.gameData.opportunities.find(
        o => parseInt(o.cell_from) === from
    );

    if(opp){
        invested = window.gameInitData.playerInvestments.some(inv =>
            inv.strategy_type === "opportunity" &&
            parseInt(inv.risk_id) === parseInt(opp.id)
        );
    }

}

            if(invested){
                arrow.classList.add("active");
            }

        }

    });

}
window.updateArrowActivation = updateArrowActivation;


/* ==========================================
   SAFE BOARD INIT
========================================== */


/* REDRAW ON RESIZE */

let resizeTimer;

window.addEventListener("resize",()=>{

    clearTimeout(resizeTimer);

    resizeTimer = setTimeout(drawBoardArrows,200);

});



const investBtn = document.getElementById("investBtn");

if(investBtn){
    investBtn.addEventListener("click", function () {
    window.location.href = "invest_strategy.php?from=board";
});
}
  
   let currentCell = window.gameInitData.currentCell;

if (isNaN(currentCell) || currentCell < 1) {
    currentCell = 1;
}

window.gameInitData.currentCell = currentCell;

// BOARD LIMIT
const totalCells = window.gameInitData.totalCells;
// ROLL DICE BUTTON

let isRolling = false;


const rollBtn = document.querySelector(".btn-roll-dice");

GameController.isMoving = false;
GameController.gameOverShown = false;

function unlockDice(){
    isRolling = false;
    GameController.isMoving = false;
    rollBtn.disabled = false;
    rollBtn.classList.remove("disabled");
}
rollBtn.addEventListener("click", async function () {

    /* PREVENT DOUBLE CLICK + MOVEMENT LOCK */
    if (isRolling || rollBtn.disabled || GameController.isMoving) return;

    isRolling = true;
    GameController.isMoving = true;

    rollBtn.disabled = true;
    rollBtn.classList.add("disabled");

    /* RESET AUDIT STATE */
    sessionStorage.removeItem("audit_invest_enabled");
    disableInvestButton();

    /* CHECK DICE FINISHED */
    if (GameController.diceRemaining === 0){
        handleDiceFinished();
        unlockDice();
        return;
    }

    /* CALL API */
    const result = await rollDiceAPI();

    if (!result) {
        unlockDice();
        return;
    }

    /* PLAY DICE SOUND */
    SoundController.play("dice");

    /* DICE ANIMATION */
    await animateDice();

    const diceValue = parseInt(result.dice_value);
    updateDiceImage(diceValue);

    await new Promise(r => setTimeout(r,300));

    /* UPDATE GAME STATE */
    GameController.diceRemaining = result.dice_remaining;
    GameController.capitalRemaining = result.capital_remaining;

    updateGameStats();
    updateCapitalUI();


    /* NORMALIZE DATA */

    let fromCell = parseInt(result.from_cell);
    let toCell = parseInt(result.to_cell);
    let finalCell = parseInt(result.final_cell || result.to_cell);

    let eventType = result.event_type;
    const outcome = parseFloat(result.outcome_percentage || 0);

    if(eventType === "snake"){
        eventType = "threat";
    }

    if(eventType === "ladder"){
        eventType = "opportunity";
    }

    if(fromCell === toCell && (eventType === "threat" || eventType === "opportunity")){
        eventType = "normal";
    }

    /* PLAYER MOVEMENT */

    await playMove(
        fromCell,
        toCell,
        finalCell,
        eventType,
        outcome,
        result
    );

    /* UPDATE POSITION */

    GameController.currentCell = finalCell;
    currentCell = finalCell;
    window.gameInitData.currentCell = finalCell;

    updateArrowActivation(finalCell);
    updateGameStats();

    /* GAME COMPLETED */

    if(result.game_completed){
        SoundController.play("victory");
        await finishGame();
        unlockDice();
        return;
    }

    /* GAME OVER */

    /* GAME OVER CHECK AFTER MOVE */
if(result.game_over){
    await checkGameOverSafe();
    unlockDice();
    return;
}

    /* ENABLE NEXT ROLL */

    if(GameController.currentCell >= totalCells){
        unlockDice();
        return;
    }

    if(GameController.diceRemaining === 0){
        handleDiceFinished();
        unlockDice();
    }else{
        unlockDice();
    }

});
async function animateDice(){

    /* START SOUND EXACTLY WITH ANIMATION */
    SoundController.play("dice");

    const frames = [1,2,3,4,5,6];

    for(let i = 0; i < 8; i++){

        const randomFace = frames[Math.floor(Math.random()*6)];

        updateDiceImage(randomFace);

        await new Promise(r => setTimeout(r,80));
    }
}
function glowArrow(fromCell, toCell, type){

    const arrows = document.querySelectorAll(".arrow-path");

    arrows.forEach(arrow => {

        const from = parseInt(arrow.dataset.from);
        const to = parseInt(arrow.dataset.to);

        arrow.classList.remove("glow");

        if(from === parseInt(fromCell) && to === parseInt(toCell)){

            if(type === "threat" || type === "snake"){
                arrow.classList.add("glow","snake");
            }

            if(type === "opportunity" || type === "ladder"){
                arrow.classList.add("glow","ladder");
            }

        }

    });

}
function clearArrowGlow(){

    document.querySelectorAll(".arrow-path").forEach(a=>{
        a.classList.remove("glow");
    });

}
function updateDiceImage(value){

    const diceImg = document.getElementById("diceImage");

    if(!diceImg) return;

    diceImg.src = ASSETS_URL + "images/dice/" + value + ".png";

}
function showToast(message, iconClass, bgColor, duration = 3000){

    Swal.fire({
        icon: "info",
        html: `<i class="fas ${iconClass}" style="margin-right:8px;"></i> ${message}`,
        confirmButtonText: "OK",
        confirmButtonColor: "#4c6ef5",
        backdrop: true
    });

}
function showWildcardToast(card){

    const area = document.getElementById("wildcardToastArea");
    if(!area) return;

    const lines = [];

    if(card.capital_change > 0){
        lines.push(`💰 +${card.capital_change} Capital`);
    }
    else if(card.capital_change < 0){
        lines.push(`💰 -${Math.abs(card.capital_change)} Capital`);
    }

    if(card.dice_change > 0){
        lines.push(`🎲 +${card.dice_change} Dice`);
    }
    else if(card.dice_change < 0){
        lines.push(`🎲 -${Math.abs(card.dice_change)} Dice`);
    }

    if(card.cell_change > 0){
        lines.push(`📍 +${card.cell_change} Cells`);
    }
    else if(card.cell_change < 0){
        lines.push(`📍 -${Math.abs(card.cell_change)} Cells`);
    }

    const toast = document.createElement("div");
    toast.className = "wildcard-toast";

    toast.innerHTML = `
        <b>🎲 ${card.wildcard_name}</b>
        ${lines.map(line => `<div>${line}</div>`).join("")}
    `;

    area.appendChild(toast);

    setTimeout(()=>toast.remove(), 4000);
}
async function handleSpecialCell(cellNumber, apiData = null){

    /* 🚫 Stop if event already running */
    if(GameController.specialEventActive){
        return;
    }


    GameController.specialEventActive = true;

    try{

        const cell = document.querySelector(`[data-cell="${cellNumber}"]`);
        if(!cell) return;

        /* ---------------- WILDCARD ---------------- */
        if(cell.querySelector(".icon-wild")){

            const result = await Swal.fire({
                icon: "question",
                title: "Wildcard Cell!",
                html: `
                <p>You landed on a <b>Wildcard</b>.</p>
                <p>Pick one card to reveal its effect.</p>
                `,
                showCancelButton: true,
                confirmButtonText: 'Pick Card',
                cancelButtonText: 'Skip'
            });

            if(result.isConfirmed){
                await openWildcardCards(); // 🔥 IMPORTANT (await)
            }else{
                GameController.specialEventActive = false;
            }

            return;
        }

        /* ---------------- BONUS ---------------- */
        if(cell.querySelector(".icon-bonus")){

    /* 🔊 play sound */
    SoundController.play("coins");

    /* ✅ SHOW ALERT FIRST */
   const result = await Swal.fire({
    icon: "success",
    title: "Bonus Collected!",
    html: `<p>You received <b>+${apiData?.bonus_amount || 0}</b> capital 🎉</p>`, // ✅ comma added
    confirmButtonText: "OK",
    confirmButtonColor: "#f1c40f"
});
 if(result.isConfirmed){
    /* ✅ AFTER OK → SHOW TOAST */
    showToast(
        "Bonus added to Risk Capital!",
        "fa-star",
        "#f1c40f",
        3000
    );
                }
    return;
}

        /* ---------------- AUDIT ---------------- */
        if(cell.querySelector(".icon-audit")){

            const result = await Swal.fire({
                icon: "warning",
                title: "Audit Cell!",
                html: `
                    <p>You landed on an <b>Audit Cell</b>.</p>
                    <p>Would you like to invest in a strategy?</p>
                `,
                showCancelButton: true,
                confirmButtonText: '<i class="fas fa-hand-holding-usd"></i> Invest Strategy',
                cancelButtonText: '<i class="fas fa-forward"></i> Skip'
            });

            if(result.isConfirmed){

                enableInvestButton();
                sessionStorage.setItem("audit_invest_enabled","1");

                showToast(
                    "Strategy Investment Enabled!",
                    "fa-hand-holding-usd",
                    "#27ae60"
                );

            }else{

                disableInvestButton();
                sessionStorage.removeItem("audit_invest_enabled");

            }

            return;
        }

    } finally {

    const modal = document.getElementById("wildcardContainer");

    if(modal && modal.style.display === "flex"){
        return; // don't unlock while modal open
    }

    GameController.specialEventActive = false;
    await checkGameOverSafe();
}

}
function getPlayerStrategy(cellNumber){

    const investments = window.gameInitData.playerInvestments || [];
    const threats = window.gameInitData.gameData.threats || [];
    const opportunities = window.gameInitData.gameData.opportunities || [];

    const threat = threats.find(t => parseInt(t.cell_from) === parseInt(cellNumber));

    if(threat){
        return investments.find(
            i => i.strategy_type === "threat" &&
            parseInt(i.risk_id) === parseInt(threat.id)
        );
    }

    const opportunity = opportunities.find(o => parseInt(o.cell_from) === parseInt(cellNumber));

    if(opportunity){
        return investments.find(
            i => i.strategy_type === "opportunity" &&
            parseInt(i.risk_id) === parseInt(opportunity.id)
        );
    }

    return null;
}
function updateGameStats(){

    const diceStat = document.getElementById("diceRemaining");
    const currentCellStat = document.getElementById("currentCellStat");
    const capitalStat = document.getElementById("riskCapital");

    if(diceStat){
        diceStat.textContent = GameController.diceRemaining;
    }

    if(currentCellStat){
        currentCellStat.textContent =
            `${GameController.currentCell} / ${totalCells}`;
    }

    if(capitalStat){
        capitalStat.textContent = GameController.capitalRemaining;
    }

}

async function checkGameOverSafe(){

    /* wait for animations */
    await new Promise(r => setTimeout(r,250));

    /* if player still moving → wait */
    if(GameController.isMoving) return;

    /* if special event running → wait */
    if(GameController.specialEventActive) return;

    /* if wildcard modal open → wait */
    const wildcardModal = document.getElementById("wildcardContainer");
    if(wildcardModal && wildcardModal.style.display === "flex"){
        return;
    }

    /* if dice increased by event → continue game */
    if(GameController.diceRemaining > 0) return;

    /* prevent double popup */
    if(GameController.gameOverShown) return;

    GameController.gameOverShown = true;

    SoundController.play("gameover");

    handleDiceFinished();
}




/* =========================
   MAIN MOVE FUNCTION
========================= */
async function playMove(fromCell, toCell, finalCell, eventType, outcome, result){

    GameController.isMoving = true;

    fromCell = parseInt(fromCell);
    toCell = parseInt(toCell);
    finalCell = parseInt(finalCell);

    /* =========================
       STEP 1: MOVE TO TARGET CELL
    ========================= */
    if(fromCell !== toCell){
        await movePlayerStepByStep(fromCell, toCell, "white");
    }

    highlightCell(toCell);
    movePlayerToCell(toCell);

    /* =========================
       STEP 2: HANDLE EVENT (POPUP FIRST)
    ========================= */
    if(eventType === "threat" || eventType === "opportunity"){

        await new Promise(r => setTimeout(r, 400));

        glowArrow(
            toCell,
            finalCell,
            eventType === "threat" ? "snake" : "ladder"
        );

        const investments = window.gameInitData.playerInvestments || [];
        const gameData = window.gameInitData.gameData || {};

        let strategies = [];
        let totalPoints = 0;
        let riskItem = null;

        if(eventType === "threat"){
            riskItem = gameData.threats.find(t => parseInt(t.cell_from) === toCell);
        }

        if(eventType === "opportunity"){
            riskItem = gameData.opportunities.find(o => parseInt(o.cell_from) === toCell);
        }

        if(riskItem){
            investments.forEach(inv => {

                if(inv.strategy_type === eventType &&
                   parseInt(inv.risk_id) === parseInt(riskItem.id)){

                    strategies.push(inv.strategy_name);
                    totalPoints += parseInt(inv.investment_points);
                }

            });
        }

        const strategyText = strategies.length ? strategies.join(", ") : "";

        let popupTitle = "";
        let popupHTML = "";
        let popupIcon = "info";

        /* =========================
           🔴 THREAT LOGIC
        ========================= */
        if(eventType === "threat"){

            SoundController.play("threat");

            const impact = Math.abs(riskItem.cell_from - riskItem.cell_to);
            const netImpact = Math.max(0, impact - totalPoints);

            if(strategies.length === 0){

                popupTitle = "Threat Encountered!";
                popupIcon = "error";
                popupHTML = `
                    Threat impact is <b>${impact}</b> points.<br>
                    You have not invested in protection.<br><br>
                    You move back <b>${impact}</b> cells.
                `;

            } else if(netImpact > 0){

                GameController.threatsProtected++;

                popupTitle = "Threat Encountered!";
                popupIcon = "warning";
                popupHTML = `
                    Threat impact is <b>${impact}</b> points.<br>
                    Your <b>${strategyText}</b> provides <b>${totalPoints}</b> protection.<br><br>
                    Net impact is <b>${netImpact}</b>.<br>
                    You move back <b>${netImpact}</b> cells.
                `;

            } else {

                GameController.threatsProtected++;

                popupTitle = "Threat Encountered!";
                popupIcon = "success";
                popupHTML = `
                    Threat impact is <b>${impact}</b> points.<br>
                    Your <b>${strategyText}</b> provides <b>${totalPoints}</b> protection.<br><br>
                    You are fully protected.<br>
                    Continue your journey.
                `;
            }
        }

        /* =========================
           🟢 OPPORTUNITY LOGIC
        ========================= */
        if(eventType === "opportunity"){

            SoundController.play("opportunity");

            const gain = Math.abs(riskItem.cell_to - riskItem.cell_from);

            if(strategies.length === 0){

                popupTitle = "Opportunity Encountered!";
                popupIcon = "info";
                popupHTML = `
                    Opportunity gain is <b>${gain}</b> points.<br>
                    You have not invested in leveraging it.<br><br>
                    Continue your journey.
                `;

            } else if(totalPoints < gain){

                GameController.opportunitiesUsed++;

                popupTitle = "Opportunity Encountered!";
                popupIcon = "info";
                popupHTML = `
                    Opportunity gain is <b>${gain}</b> points.<br>
                    Your <b>${strategyText}</b> provides <b>${totalPoints}</b> leverage.<br><br>
                    You move forward <b>${totalPoints}</b> cells.
                `;

            } else {

                GameController.opportunitiesUsed++;

                popupTitle = "Opportunity Encountered!";
                popupIcon = "success";
                popupHTML = `
                    Opportunity gain is <b>${gain}</b> points.<br>
                    Your <b>${strategyText}</b> provides <b>${totalPoints}</b> leverage.<br><br>
                    You fully leveraged the opportunity.<br>
                    You move forward <b>${gain}</b> cells.
                `;
            }
        }

        /* =========================
           🔥 SHOW POPUP (BLOCKING)
        ========================= */
        await Swal.fire({
            icon: popupIcon,
            title: popupTitle,
            html: popupHTML,
            confirmButtonText: "OK",
            allowOutsideClick: false
        });
    }

    /* =========================
       STEP 3: MOVE AFTER OK
    ========================= */
    if(finalCell !== toCell){

        await new Promise(r => setTimeout(r, 400));

        if(eventType === "opportunity"){
            await movePlayerStepByStep(toCell, finalCell, "green");
        }
        else if(eventType === "threat"){
            await movePlayerStepByStep(toCell, finalCell, "red");
        }
    }

    setTimeout(clearArrowGlow, 1500);

    /* =========================
       STEP 4: FINAL POSITION UPDATE
    ========================= */
    movePlayerToCell(finalCell);
    highlightCell(finalCell);

    GameController.currentCell = finalCell;
    currentCell = finalCell;
    window.gameInitData.currentCell = finalCell;

    updateArrowActivation(finalCell);
    updateGameStats();

    await handleSpecialCell(finalCell, result);

    GameController.isMoving = false;
    await checkGameOverSafe();
}
/* =========================
   STEP BY STEP PLAYER MOVE
========================= */
async function movePlayerStepByStep(from, to, glowType="white"){

    from = parseInt(from);
    to = parseInt(to);

    let step = (to > from) ? 1 : -1;

    let i = from;

    while(i !== to){

        i += step;

        if(i < 1) i = 1;
        if(i > totalCells) i = totalCells;

        /* MOVE TOKEN */
        movePlayerToCell(i);

    SoundController.play("token");


/* token jump */
jumpToken();

highlightCell(i);
glowCell(i, glowType);

        /* WAIT so token + glow appear together */
        await new Promise(r => setTimeout(r, 420));

    }
}


/* =========================
   MOVE PLAYER TOKEN
========================= */

function movePlayerToCell(cellNumber){

    const cell = document.querySelector(`[data-cell="${cellNumber}"]`);
    if(!cell) return;

   const oldToken = document.querySelector(".player-token");
if(oldToken) oldToken.remove();

const token = document.createElement("div");
token.className = "player-token";
token.innerHTML = '<i class="fas fa-chess-king"></i>';

cell.appendChild(token);
}
/* =========================
   CELL HIGHLIGHT
========================= */

function highlightCell(cellNumber){

    const cells = document.querySelectorAll('.cell');

    cells.forEach(c => c.classList.remove('active'));

    const cell = document.querySelector(`.cell[data-cell="${cellNumber}"]`);

    if(cell){
        cell.classList.add("active");
    }

}
function glowCell(cellNumber, type="white"){

    const cell = document.querySelector(`.cell[data-cell="${cellNumber}"]`);
    if(!cell) return;

    const glowClass =
        type === "red" ? "glow-red" :
        type === "green" ? "glow-green" :
        "glow-white";

    cell.classList.add(glowClass);

    /* keep glow longer so player can see movement */
    setTimeout(()=>{
        cell.classList.remove(glowClass);
    },700);

}

/* =========================
   CHECK SNAKE / LADDER
========================= */

async function checkSnakeOrLadder(cellNumber){

    const arrows = document.querySelectorAll('.arrow-path');

    for(let arrow of arrows){

        const from = parseInt(arrow.dataset.from);
        const to = parseInt(arrow.dataset.to);
        const type = arrow.dataset.type;

        if(from === cellNumber){

            const startCell = document.querySelector(`[data-cell="${from}"]`);
            const endCell = document.querySelector(`[data-cell="${to}"]`);

            if(startCell){
                startCell.classList.add("arrow-start");
            }

            if(endCell){

                if(type === "ladder"){
                    endCell.classList.add("arrow-target-green");
                    showToast("Opportunity! Climb Up 🚀","fa-arrow-up","#27ae60");
                }

                if(type === "snake"){
                    endCell.classList.add("arrow-target-red");
                    showToast("Threat! Move Down ⚠️","fa-arrow-down","#e74c3c");
                }

            }

            await new Promise(r => setTimeout(r,500));

            // MOVE STEP BY STEP
            await movePlayerStepByStep(from,to);

            highlightCell(to);

            if(startCell){
                startCell.classList.remove("arrow-start");
            }

            if(endCell){
                endCell.classList.remove("arrow-target-green");
                endCell.classList.remove("arrow-target-red");
            }

            return to;
        }
    }

    return cellNumber;
}
function enableInvestButton(){

    const btn = document.getElementById("investBtn");

    if(btn){
        btn.disabled = false;
        btn.classList.remove("disabled");
    }

}

function disableInvestButton(){

    const btn = document.getElementById("investBtn");

    if(btn){
        btn.disabled = true;
        btn.classList.add("disabled");
    }

}

window.addEventListener("load", function(){
GameController.initialDice = GameController.diceRemaining;
    const currentCell = GameController.currentCell;

    const currentCellElement = document.querySelector(
        `.cell[data-cell="${currentCell}"]`
    );

    const isAuditCell =
        currentCellElement &&
        currentCellElement.querySelector(".icon-audit");

    /* RULE 1: Before first roll (cell 1) */
    if(currentCell === 1){
        enableInvestButton();
    }

    /* RULE 2: Audit cell */
    else if(isAuditCell){
        enableInvestButton();
    }

    /* Otherwise disabled */
    else{
        disableInvestButton();
    }

    movePlayerToCell(currentCell);
    highlightCell(currentCell);

    if(window.updateArrowActivation){
        updateArrowActivation(currentCell);
    }

    SoundController.init();
});
 document.addEventListener("click", function(e){

    if(e.target.id === "exitBtn" || e.target.id === "gameOverExit"){
        exitGame();
    }

});


</script>


<div id="wildcardContainer" class="wildcard-container" style="display:none;">

<div class="wildcard-box">

<div class="wildcard-header">
<h3>🎲 Choose a Wildcard</h3>

</div>

<div id="wildcardCards"></div>

</div>
</div>

<div id="gameOverOverlay" class="gameover-overlay" style="display:none;">

    <div class="gameover-card">

        <h2 class="gameover-title">Game Over</h2>
        <p class="gameover-subtitle">No dice remaining!</p>

        <div class="gameover-stats">

            <div class="stat-row">
                <div class="icon-box blue">
                    <i class="fas fa-map"></i>
                </div>
                <span>Cells Reached</span>
                <b id="cellsReached">0</b>
            </div>

            <div class="stat-row">
                <div class="icon-box purple">
                    <i class="fas fa-dice"></i>
                </div>
                <span>Dice Used</span>
                <b id="diceUsed">0</b>
            </div>

            <div class="stat-row">
                <div class="icon-box green">
                    <i class="fas fa-dollar-sign"></i>
                </div>
                <span>Final Capital</span>
                <b id="finalCapital">0</b>
            </div>

            <div class="stat-row">
                <div class="icon-box red">
                    <i class="fas fa-shield-alt"></i>
                </div>
                <span>Threats Protected</span>
                <b id="threatsProtected">0</b>
            </div>

            <div class="stat-row">
                <div class="icon-box yellow">
                    <i class="fas fa-bolt"></i>
                </div>
                <span>Opportunities Used</span>
                <b id="opportunitiesUsed">0</b>
            </div>

            <div class="stat-row">
                <div class="icon-box indigo">
                    <i class="fas fa-question-circle"></i>
                </div>
                <span>Wildcards Opened</span>
                <b id="wildcardsOpened">0</b>
            </div>

        </div>

        <button id="gameOverExit" class="exit-btn">
            <i class="fas fa-sign-out-alt"></i> Exit Game
        </button>

    </div>

</div>
<script>
    
    const overlay = document.getElementById("wildcardContainer");
const box = document.querySelector(".wildcard-box");

overlay.addEventListener("click", function(e){

    // if click is on overlay background
    if(e.target === overlay){
        // DO NOTHING (prevent closing)
        e.stopPropagation();
    }

});

// prevent propagation from inside box
box.addEventListener("click", function(e){
    e.stopPropagation();
});

function jumpToken(){

    const token = document.querySelector(".player-token");
    if(!token) return;

    token.classList.remove("jump"); // reset animation
    void token.offsetWidth; // force reflow
    token.classList.add("jump");

}
async function openWildcardCards(){

    const container = document.getElementById("wildcardContainer");
    const cardsDiv  = document.getElementById("wildcardCards");

    container.style.display = "flex";
    cardsDiv.innerHTML = "<p>Loading...</p>";

    try{

        const res = await fetch("ajax/get_wildcards.php", {
            method: "POST",
            headers: {"Content-Type": "application/json"},
            body: JSON.stringify({
                matrix_id: GameController.matrixId
            })
        });

        const data = await res.json();

        if(!data.success){
            cardsDiv.innerHTML = "<p>No wildcards found</p>";
            return;
        }

        const wildcards = data.data.wildcards;

        cardsDiv.innerHTML = "";

        wildcards.forEach(w => {

            const card = document.createElement("div");
            card.className = "wildcard-card";

            card.innerHTML = `
                <div class="card-inner">
                    <div class="card-front">
                        🎲
                    </div>
                    <div class="card-back">
                        <h4>${w.wildcard_name}</h4>
                        <p>${w.wildcard_description}</p>
                    </div>
                </div>
            `;

            /* CLICK EVENT */
            card.addEventListener("click", async () => {

                /* prevent double click */
                if(card.classList.contains("selected")) return;

                card.classList.add("selected");

                /* small delay for flip animation */
                await new Promise(r => setTimeout(r, 400));

                await selectWildcard(w.id);
            });

            cardsDiv.appendChild(card);
        });

    }catch(err){
        console.error(err);
        cardsDiv.innerHTML = "<p>Error loading wildcards</p>";
    }
}
function buildWildcardHTML(effects){

    const lines = [];

    if(effects.capital_change !== 0){
        lines.push(
            effects.capital_change > 0
                ? `You gained ${effects.capital_change} capital.`
                : `You lost ${Math.abs(effects.capital_change)} capital.`
        );
    }

    if(effects.dice_change !== 0){
        lines.push(
            effects.dice_change > 0
                ? `You received ${effects.dice_change} additional dice.`
                : `You lost ${Math.abs(effects.dice_change)} dice.`
        );
    }

    if(effects.cell_change !== 0){
        lines.push(
            effects.cell_change > 0
                ? `You moved forward ${effects.cell_change} cells.`
                : `You moved backward ${Math.abs(effects.cell_change)} cells.`
        );
    }

    if(lines.length === 0){
        lines.push("No changes were applied.");
    }

    return `
        <div style="text-align:left; font-size:15px; line-height:1.6;">
            ${lines.map(line => `<div>${line}</div>`).join("")}
        </div>
    `;
}
async function selectWildcard(wildcardId){

    const res = await fetch("ajax/open_wildcard.php", {
        method: "POST",
        headers: {"Content-Type": "application/json"},
        body: JSON.stringify({
            session_id: GameController.sessionId,
            wildcard_id: wildcardId
        })
    });

    const data = await res.json();

    if(!data.success){
        GameController.specialEventActive = false;
        return;
    }

    const effects   = data.data.effects;
    const newValues = data.data.new_values;

    const oldCell = GameController.currentCell;
    const newCell = newValues.current_cell;

    /* =========================
       ✅ UPDATE GAME STATE (CORRECT PLACE)
    ========================= */

    GameController.capitalRemaining = newValues.capital_remaining;
    GameController.diceRemaining    = newValues.dice_remaining;

    const rollBtn = document.querySelector(".btn-roll-dice");

    if(GameController.diceRemaining <= 0){
        rollBtn.disabled = true;
        rollBtn.classList.add("disabled");
    }else{
        rollBtn.disabled = false;
        rollBtn.classList.remove("disabled");
    }

    updateGameStats();

    /* =========================
       SWAL
    ========================= */

await Swal.fire({
    icon: "info",
    title: "🎲 " + data.data.wildcard.wildcard_name,
    html: buildWildcardHTML(effects),
    confirmButtonText: "OK",
    confirmButtonColor: "#4c6ef5"
});

/* ✅ FIRST CLOSE MODAL */
document.getElementById("wildcardContainer").style.display = "none";

/* small delay for smooth UI */
await new Promise(r => setTimeout(r, 200));

/* ✅ THEN SHOW TOAST */
showWildcardToast({
    wildcard_name: data.data.wildcard.wildcard_name,
    ...effects
});

    /* =========================
       MOVE PLAYER
    ========================= */

    if(newCell !== oldCell){

        await movePlayerStepByStep(oldCell, newCell);

        movePlayerToCell(newCell);
        highlightCell(newCell);

        await handleSpecialCell(newCell);
    }

    GameController.currentCell = newCell;
    window.gameInitData.currentCell = newCell;

    updateArrowActivation(newCell);

    document.getElementById("wildcardContainer").style.display = "none";

    GameController.specialEventActive = false;

    await checkGameOverSafe();
}

/* =========================
   BLOCK BACK BUTTON
========================= */

history.pushState(null, null, location.href);

window.addEventListener('popstate', function () {

    Swal.fire({
        icon: "warning",
        title: "Action not allowed",
        text: "You cannot go back during an active game.",
        confirmButtonText: "Continue Playing"
    });

    history.pushState(null, null, location.href);
});

function exitGame(){

    allowExit = true; // ✅ IMPORTANT
/* OPTIONAL: remove listener completely (extra safe) */
 
    Swal.fire({
        title: "Exiting game...",
        allowOutsideClick:false,
        didOpen: () => Swal.showLoading()
    });


    fetch(GAME_URL + "ajax/exit_game.php", {
        method: "POST",
        headers: {
            "Content-Type": "application/json"
        },
        body: JSON.stringify({
            session_id: GameController.sessionId,

    max_cell_reached: GameController.currentCell,

    total_dice_used:
        GameController.initialDice - GameController.diceRemaining,

    threats_protected: GameController.threatsProtected,
    threats_total: GameController.totalThreats,

    opportunities_exploited: GameController.opportunitiesUsed,
    opportunities_total: GameController.totalOpportunities,

    wildcards_opened: GameController.wildcardsOpened,

    final_capital: GameController.capitalRemaining,

    
        })
    })
    .then(res => res.json())
    .then(data => {
        if(data.success){
            window.location.href = GAME_URL + "index.php";
        }else{
            Swal.fire("Error", data.message, "error");
        }

    })
    .catch(err => {
        console.error(err);
        Swal.fire("Error","Something went wrong","error");
    });
}



    </script>
    
</body>
</html>
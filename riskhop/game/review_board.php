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
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($game['game_name']); ?> - Playing</title>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
     <!-- Styles -->
    <link rel="stylesheet" href="<?php echo ASSETS_URL; ?>css/review_board.css?v=<?php echo time(); ?>">
   <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>

    <div class="game-wrapper">
        <!-- MAIN CONTENT -->
        <div class="game-content">
            <div class="board-section">

                <div class="board-header">
                   <div class="board-title-row">
        <h3>REVIEW THE BOARD</h3>
       <i class="fas fa-question-circle help-icon" onclick="openHelpModal(event)"></i>
    </div>
                  <p>
Move the mouse across all the legend cells to understand the risk profile (threats and opportunities), 
bonus, review and other options provided.
</p>

<div id="helpModal" class="help-modal">
    <div class="help-content">
       <iframe src="new_instruction.php?game_id=<?php echo $game['id']; ?>&from=help#guideline"></iframe>
        <button onclick="closeHelpModal()" class="close-help">CLOSE</button>
    </div>
</div>
                </div>

                <!-- BOARD AREA -->
                <div class="board-wrapper" style="position:relative;">
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
                                        $icon = '<div class="cell-icon icon-threat"><i class="fas fa-arrow-down"></i></div>';
                                    }

                                    if ($cell_info['type'] === 'opportunity') {
                                        $icon = '<div class="cell-icon icon-opportunity"><i class="fas fa-arrow-up"></i></div>';
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

                <h3 style="text-transform:uppercase;">Review the Legends</h3>
                <p>Please review the placement of game elements and verify the statistics before proceeding.</p>

                <div class="legend-compact">
                    <h4 class="stats-title">Statistics</h4>
                    <div class="legend-compact-item snake">
                        <div class="legend-compact-icon">
                            <i class="fas fa-arrow-down"></i>
                        </div>
                        <span>Number of Threats</span>
                        <span class="legend-count"><?php echo $snake_count; ?></span>
                    </div>

                    <div class="legend-compact-item ladder">
                        <div class="legend-compact-icon">
                            <i class="fas fa-arrow-up"></i>
                        </div>
                        <span>Number of Opportunities</span>
                        <span class="legend-count"><?php echo $ladder_count; ?></span>
                    </div>

                    <div class="legend-compact-item bonus">
                        <div class="legend-compact-icon">
                            <i class="fas fa-star"></i>
                        </div>
                        <span>Number of Bonus Cells</span>
                        <span class="legend-count"><?php echo $bonus_count; ?></span>
                    </div>

                    <div class="legend-compact-item audit">
                        <div class="legend-compact-icon">
                            <i class="fas fa-search"></i>
                        </div>
                        <span>Number of Audit Cells</span>
                        <span class="legend-count"><?php echo $audit_count; ?></span>
                    </div>

                    <div class="legend-compact-item wildcard">
                        <div class="legend-compact-icon">
                            <i class="fas fa-question"></i>
                        </div>
                        <span>Number of Wild Cards</span>
                        <span class="legend-count"><?php echo $wildcard_count; ?></span>
                    </div>

                </div>

             <div class="review-actions">
    <button type="button" id="prevBtn" class="btn-secondary">Previous</button>
    <button id="nextBtn" class="btn-primary">Next</button>
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

                hoverIcon.innerHTML = '<i class="fas fa-arrow-down"></i>';
                hoverIcon.style.background = '#ff6b6b';

                /* show snake */
                highlightArrow(cellNumber);

                break;


                case 'opportunity':

                title = info.data.opportunity_name;
                desc = info.data.opportunity_description;

                hoverIcon.innerHTML = '<i class="fas fa-arrow-up"></i>';
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
                desc = `Wild Cards introduce unexpected twists that can influence your journey—sometimes in your favor, sometimes not.

• Gain bonus capital
• Lose a portion of your investment
• Skip a turn
• Double your next gain

You can also choose to skip the Wild Card and continue your game as planned.`;

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

if(from === parseInt(cellNumber)){

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
},300);

});

/* REDRAW IF SCREEN RESIZED */

let resizeTimer;

window.addEventListener("resize",()=>{

clearTimeout(resizeTimer);

resizeTimer = setTimeout(drawBoardArrows,200);

});
document.getElementById("nextBtn").addEventListener("click", function () {

    // 🔥 DISABLE ALERT
    

    // navigate
    window.location.href = "invest_strategy.php?game_id=<?php echo $game['id']; ?>";
});
function openHelpModal(e){
    if(e){
        e.preventDefault();
        e.stopPropagation(); 
    }

    document.getElementById("helpModal").classList.add("active");
}

function closeHelpModal(){
 document.getElementById("helpModal").classList.remove("active");
}
const prevBtn = document.getElementById("prevBtn");

if(prevBtn){
    prevBtn.addEventListener("click", function(e){
        e.preventDefault();

        window.location.href = "new_instruction.php?game_id=<?php echo $game['id']; ?>&from=review#overview";
    });
}

    </script>
    
</body>
</html>
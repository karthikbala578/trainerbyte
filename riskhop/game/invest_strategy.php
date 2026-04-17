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


$from = $_GET['from'] ?? '';

if ($session && $session['current_cell'] > 1 && $from !== 'board') {
    redirect(GAME_URL . 'board.php?game_id=' . $session['matrix_id']);
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
$fromBoard = isset($_GET['from']) && $_GET['from'] === 'board';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invest Strategy</title>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
const SAVED_STRATEGIES_DB = <?php echo json_encode($player_investments); ?>;
</script>
    <script>
let savedStrategies = [];

try {

let local = JSON.parse(localStorage.getItem("selectedStrategies")) || [];
let dbStrategies = Object.values(SAVED_STRATEGIES_DB || {});

if(dbStrategies.length > 0){
    savedStrategies = dbStrategies;
    localStorage.removeItem("selectedStrategies"); // reset old cache
}else if(local.length > 0){
    savedStrategies = local;
}else{
    savedStrategies = [];
}

}catch(e){
savedStrategies = [];
}

if(savedStrategies.length && !savedStrategies[0].strategy_type){
    localStorage.removeItem("selectedStrategies");
    savedStrategies = [];
}

</script>
     <!-- Styles -->
    <link rel="stylesheet" href="<?php echo ASSETS_URL; ?>css/invest_strategy.css?v=<?php echo time(); ?>">
   <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@500;600&display=swap" rel="stylesheet">
</head>
<body>
    <div class="game-wrapper">
        <!-- MAIN CONTENT -->
        <div class="game-content">

            <!-- HEADER -->
            <div class="page-header">

                <div class="header-left">
                    <i class="fas fa-shield-alt"></i>
                    <span>Invest in strategic response</span>
                </div>

                <div class="header-right">
                    <button class="icon-btn"><i class="fas fa-bell"></i></button>
                    <button class="icon-btn"><i class="fas fa-user-circle"></i></button>
                </div>

            </div>


            <!-- TABS -->
            <div class="tabs">
                <button class="tab active">Threat response</button>
                <button class="tab">Opportunity investment</button>
            </div>
            <div class="strategy-layout">

    <!-- LEFT SIDE : STRATEGY LIST -->
    <div class="strategy-list">

        <div class="list-header">
            <h3>Strategic Response List</h3>

            <label>
                <input type="checkbox" id="selectAll">
                Select All
            </label>
        </div>

        <!-- SCROLL AREA -->
        <div class="strategy-scroll">

            <div id="threat-list"></div>

            <div id="opportunity-list" style="display:none;"></div>

        </div>

    </div>


    <!-- RIGHT SIDE PANEL -->
    <div class="side-panel">
<div class="game-name-card" title="<?php echo htmlspecialchars($game['game_name']); ?>">
    <?php echo htmlspecialchars($game['game_name']); ?>
</div>
        <!-- CAPITAL CARD -->
        <!-- CAPITAL CARD -->
<div class="capital-card">

    <div class="capital-title">
        <i class="fa-solid fa-sack-dollar capital-icon"></i>
        Risk Capital Balance
    </div>

    <!-- Remaining Capital -->
   <div class="capital-amount" id="remainingCapital">
    <i class="fa-solid fa-coins capital-icon"></i>
    <?php echo number_format($session['capital_remaining']); ?>
</div>

    <!-- Capital Progress -->
    <div class="capital-bar">
        <div class="bar-fill" id="capitalBar"></div>
    </div>

    <!-- Used -->
    <div style="font-size:12px">
        Risk Score Used
        <span id="allocatedCapital" style="float:right">
            <i class="fa-solid fa-coins capital-icon"></i> 0
        </span>
    </div>

</div>

        <!-- SUMMARY CARD -->
        <div class="summary-card">

    <h4>Investment Overview</h4>

    <div class="summary-item">
        <i class="fas fa-layer-group"></i>
        <div>
            <strong id="summaryStrategies">0 Strategies Selected</strong><br>
            Active threat & opportunity responses
        </div>
    </div>

    <div class="summary-item">
        <i class="fas fa-coins"></i>
        <div>
            <strong id="summaryUsed">0 Capacity Used</strong><br>
            Total risk capital allocated
        </div>
    </div>

    <div class="summary-item">
        <i class="fas fa-chart-line"></i>
        <div>
            <strong id="summaryRemaining">0 Remaining</strong><br>
            Available risk capital
        </div>
    </div>

</div>

    </div>

</div>
            <!-- NEXT BUTTON -->
            <div class="next-wrapper">
               <button class="prev-btn" onclick="goBackToBoard()">Previous</button>

    <button class="next-btn">
        <?php if($fromBoard): ?>
            Save & Close <i class="fas fa-arrow-right"></i>
        <?php else: ?>
            NEXT <i class="fas fa-arrow-right"></i>
        <?php endif; ?>
    </button>
            </div>


        </div>
    </div>
    
<script>

const TOTAL_RISK_CAPITAL =
<?php echo $session['capital_remaining'] + array_sum(array_column($player_investments,'investment_points')); ?>;

document.querySelectorAll(".tab").forEach(tab => {

tab.addEventListener("click", function(){

document.querySelectorAll(".tab").forEach(t => t.classList.remove("active"));
this.classList.add("active");

if(this.innerText.includes("Threat")){

document.getElementById("threat-list").style.display = "block";
document.getElementById("opportunity-list").style.display = "none";

}else{

document.getElementById("threat-list").style.display = "none";
document.getElementById("opportunity-list").style.display = "block";

}

});

});
function restoreSelections(){

selectedThreatStrategies = [];
selectedOpportunityStrategies = [];
totalUsed = 0;

if(!savedStrategies || savedStrategies.length === 0){
return;
}

savedStrategies.forEach(strategy=>{

let checkbox = document.querySelector(
`input[value="${strategy.strategy_id}"][data-type="${strategy.strategy_type}"][data-risk-id="${strategy.risk_id}"]`
);

if(!checkbox) return;

let cost = parseInt(checkbox.dataset.cost);

checkbox.checked = true;

totalUsed += cost;

let restored = {
strategy_id: strategy.strategy_id,
points: cost,
risk_id: strategy.risk_id,
strategy_type: strategy.strategy_type
};

if(strategy.strategy_type === "threat"){
selectedThreatStrategies.push(restored);
}else{
selectedOpportunityStrategies.push(restored);
}

});

updateCapitalUI();
updateSummary();

}
function loadStrategies() {

fetch("ajax/get_strategies.php", {
method: "POST",
headers: {
"Content-Type": "application/json",
},
body: JSON.stringify({
matrix_id: <?php echo $session['matrix_id']; ?>
}),
})
.then(response => response.json())
.then(data => {

if(!data.success){
console.error("Failed to load strategies");
return;
}

const res = data.data || data;
const groups = res.groups;

renderThreats(groups.threats);
renderOpportunities(groups.opportunities);

restoreSelections();
saveStrategiesToStorage();
// reset select all checkbox
document.getElementById("selectAll").checked = false;
})
.catch(error => {
console.error("Strategy load error:", error);
});

}

function renderThreats(threats){

let container = document.getElementById("threat-list");
container.innerHTML = "";

threats.forEach(threat => {

threat.strategies.forEach(strategy => {

let card = document.createElement("div");
card.className = "strategy-card";

card.innerHTML = `

<label>
<input type="checkbox"
value="${strategy.id}"
data-type="threat"
data-risk-id="${threat.id}"
data-cost="${strategy.response_points}">
</label>

<div class="card-body">

<div class="card-header">

<div>
<div class="card-title">
${strategy.strategy_name}
</div>

<div class="card-desc">
${strategy.description}
</div>

</div>

<div class="risk-score">
<i class="fa-regular fa-00"></i>
<span>${strategy.response_points} Points</span>
</div>

</div>

</div>
`;

container.appendChild(card);

});

});

}
function renderOpportunities(opps){

let container = document.getElementById("opportunity-list");
container.innerHTML = "";

opps.forEach(opp => {

opp.strategies.forEach(strategy => {

let card = document.createElement("div");
card.className = "strategy-card";
card.innerHTML = `

<label>
<input type="checkbox"
value="${strategy.id}"
data-type="opportunity"
data-risk-id="${opp.id}"
data-cost="${strategy.response_points}">
</label>

<div class="card-body">

<div class="card-header">

<div>
<div class="card-title">
${strategy.strategy_name}
</div>

<div class="card-desc">
${strategy.description}
</div>
</div>

<div class="risk-score">
<i class="fa-regular fa-00"></i>
<span>${strategy.response_points} Points</span>
</div>

</div>

</div>
`;

container.appendChild(card);

});

});

}
</script>
<script>
let selectedThreatStrategies = [];
let selectedOpportunityStrategies = [];

let totalUsed = 0;

document.addEventListener("change", function(e){

    if(e.target.matches('#threat-list input[type="checkbox"], #opportunity-list input[type="checkbox"]')){

        let cost = parseInt(e.target.dataset.cost);
        let id = e.target.value;

        let isThreat = e.target.closest("#threat-list") ? true : false;
        let isOpportunity = e.target.closest("#opportunity-list") ? true : false;

       if(e.target.checked){

if(totalUsed + cost > TOTAL_RISK_CAPITAL){

Swal.fire({
allowOutsideClick:false,
icon:'warning',
title:'Insufficient capital!',
text:'Please adjust your selections'
});

e.target.checked = false;
return;
}

totalUsed += cost;

if(isThreat){

selectedThreatStrategies =
selectedThreatStrategies.filter(s =>
!(s.strategy_id == id && s.risk_id == e.target.getAttribute("data-risk-id"))
);

selectedThreatStrategies.push({
strategy_id:id,
points:cost,
risk_id:e.target.getAttribute("data-risk-id"),
strategy_type:"threat"
});

}

if(isOpportunity){

selectedOpportunityStrategies =
selectedOpportunityStrategies.filter(s =>
!(s.strategy_id == id && s.risk_id == e.target.getAttribute("data-risk-id"))
);

selectedOpportunityStrategies.push({
strategy_id:id,
points:cost,
risk_id:e.target.getAttribute("data-risk-id"),
strategy_type:"opportunity"
});

}

}else{

totalUsed -= cost;

if(isThreat){

selectedThreatStrategies =
selectedThreatStrategies.filter(s =>
!(s.strategy_id == id && s.risk_id == e.target.getAttribute("data-risk-id"))
);

}

if(isOpportunity){

selectedOpportunityStrategies =
selectedOpportunityStrategies.filter(s =>
!(s.strategy_id == id && s.risk_id == e.target.getAttribute("data-risk-id"))
);

}

}

            updateCapitalUI();
            updateSummary();
            saveStrategiesToStorage();

        }

    });
    </script>
    <script>
function updateCapitalUI(){

let remaining = TOTAL_RISK_CAPITAL - totalUsed;
let percent = Math.min(100,(totalUsed / TOTAL_RISK_CAPITAL) * 100);

document.getElementById("remainingCapital").innerHTML =
'<i class="fa-solid fa-coins capital-icon"></i> ' + remaining;

document.getElementById("allocatedCapital").innerHTML =
'<i class="fa-solid fa-coins capital-icon"></i> ' + totalUsed;

document.getElementById("capitalBar").style.width =
percent + "%";

}
document.getElementById("selectAll").addEventListener("change", function(){

let checkboxes = document.querySelectorAll(
'#threat-list input[type="checkbox"], #opportunity-list input[type="checkbox"]'
);

if(this.checked){

let projectedTotal = 0;

checkboxes.forEach(cb=>{
projectedTotal += parseInt(cb.dataset.cost);
});

if(projectedTotal > TOTAL_RISK_CAPITAL){

Swal.fire({
allowOutsideClick:false,
icon:'warning',
title:'Insufficient Capital!',
text:'Selected strategies exceed your risk capital.'
}).then(()=>{
this.checked = false;
});

return;
}

// Select all
checkboxes.forEach(cb=>{
cb.checked = true;
});

}else{

// Unselect all
checkboxes.forEach(cb=>{
cb.checked = false;
});

}

// Recalculate everything
recalculateSelections();

});
document.querySelector(".next-btn").addEventListener("click", function(){

let allStrategies = [
...selectedThreatStrategies,
...selectedOpportunityStrategies
];

let selectedStrategies = [
...selectedThreatStrategies,
...selectedOpportunityStrategies
];

// If nothing selected
if(selectedStrategies.length === 0){

Swal.fire({
    allowOutsideClick:false,
icon:'warning',
title:'No strategies selected',
text:'You have not selected any strategy. Do you want to proceed to the board or review?',
showCancelButton:true,
confirmButtonText:'Proceed',
cancelButtonText:'Review'
}).then((result)=>{

if(result.isConfirmed){

// go to board without saving strategies
window.location.href="board.php";

}

});

return;
}


// Save strategies if selected
fetch("ajax/invest_strategy.php",{
method:"POST",
headers:{
"Content-Type":"application/json"
},
body:JSON.stringify({
session_id:<?php echo $session['id']; ?>,
strategies:selectedStrategies
})
})
.then(res=>res.json())
.then(data=>{

if(!data.success){

Swal.fire({
    allowOutsideClick:false,
icon:'error',
title:'Investment failed',
text:data.message
});
return;

}
localStorage.removeItem("selectedStrategies");
Swal.fire({
allowOutsideClick:false,
icon:'success',
title:'Your investment choices have been saved.',
html: `
        <p style="font-size:15px; line-height:1.6; color:#555;">
            Your strategic investment choices have been securely recorded.
            As the game evolves, you will have opportunities to revisit and
            refine your allocations—stay alert and adapt your strategy to
            maximize your advantage.
        </p>
    `,
    confirmButtonText: 'Continue'
}).then(()=>{
window.location.href="board.php";

});

});

});
function updateSummary(){

let threatCount = selectedThreatStrategies.length;
let oppCount = selectedOpportunityStrategies.length;

let totalStrategies = threatCount + oppCount;

let remaining = TOTAL_RISK_CAPITAL - totalUsed;

document.getElementById("summaryStrategies").innerText =
totalStrategies + " Strategies Selected";

document.getElementById("summaryUsed").innerText =
totalUsed + " Capacity Used";

document.getElementById("summaryRemaining").innerText =
remaining + " Remaining";

}
function recalculateSelections(){

selectedThreatStrategies = [];
selectedOpportunityStrategies = [];
totalUsed = 0;

let checked = document.querySelectorAll(
'#threat-list input[type="checkbox"]:checked, #opportunity-list input[type="checkbox"]:checked'
);

checked.forEach(cb=>{

let cost = parseInt(cb.dataset.cost);
let id = cb.value;
let riskId = cb.getAttribute("data-risk-id");
let type = cb.dataset.type;

totalUsed += cost;

let strategy = {
strategy_id:id,
points:cost,
risk_id:riskId,
strategy_type:type
};

if(type === "threat"){
selectedThreatStrategies.push(strategy);
}else{
selectedOpportunityStrategies.push(strategy);
}

});

updateCapitalUI();
updateSummary();
saveStrategiesToStorage();

}
document.addEventListener("DOMContentLoaded", function(){

loadStrategies();

});
window.addEventListener("pageshow", function (event) {

if (event.persisted) {
location.reload();
}

});

function saveStrategiesToStorage(){

let allStrategies = [
...selectedThreatStrategies,
...selectedOpportunityStrategies
];

localStorage.setItem(
"selectedStrategies",
JSON.stringify(allStrategies)
);

}
function goBackToBoard(){
     window.location.href = 'review_board.php?game_id=<?php echo $game['id']; ?>';
}
        </script>
</body>
</html>
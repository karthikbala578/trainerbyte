const SoundController = {

   
    dice: new Audio(ASSETS_URL + "sounds/roll-dice.mp3"),
    token: new Audio(ASSETS_URL + "sounds/player-token.mp3"),
    opportunity: new Audio(ASSETS_URL + "sounds/correct.mp3"),
    threat: new Audio(ASSETS_URL + "sounds/error.mp3"),
    increase: new Audio(ASSETS_URL + "sounds/increase.mp3"),
    decrease: new Audio(ASSETS_URL + "sounds/decrease.mp3"),
    coins: new Audio(ASSETS_URL + "sounds/coins.mp3"),
    decrease: new Audio(ASSETS_URL + "sounds/decrease.mp3"),
gameover: new Audio(ASSETS_URL + "sounds/gameOver.wav"),
victory: new Audio(ASSETS_URL + "sounds/victory.mp3"),
    init(){

      
        /* effects volume */
        this.dice.volume = 0.3;
        this.token.volume = 0.25;
        this.opportunity.volume = 0.35;
        this.threat.volume = 0.35;
        this.increase.volume = 0.35;
        this.decrease.volume = 0.35;
        this.coins.volume = 0.35;
        this.gameover.volume = 0.50;
        this.victory.volume = 0.60;
    },

   play(sound){
    if(!this[sound]) return;

    const audio = this[sound].cloneNode();
    audio.volume = this[sound].volume;
    audio.play().catch(()=>{});
}

};

const GameController = window.gameInitData ? {

    sessionId: window.gameInitData.sessionId,
    matrixId: window.gameInitData.matrixId,

    currentCell: parseInt(window.gameInitData.currentCell),
    diceRemaining: parseInt(window.gameInitData.diceRemaining),
    capitalRemaining: parseInt(window.gameInitData.capitalRemaining),

    totalCells: parseInt(window.gameInitData.totalCells),
    initialDice: parseInt(window.gameInitData.diceRemaining),

    wildcardsOpened: 0,
    threatsProtected: 0,
    opportunitiesUsed: 0,
    specialEventActive: false

} : {};
let wildcardSelected = false;
let pendingWildcardResult = null;
/* ------------------------------
   API HELPER
--------------------------------*/
async function callAPI(url, payload = {}) {

    try {

        console.log("Calling API:", url, payload);

        const response = await fetch(url, {
            method: "POST",
            headers: {
                "Content-Type": "application/json"
            },
            body: JSON.stringify(payload)
        });

        const text = await response.text();

        console.log("Raw API response:", text);

        let data;

        try {
            data = JSON.parse(text);
        } catch (e) {
            console.error("Invalid JSON response:", text);
            showToast("Server returned invalid response", "fa-times", "#e74c3c");
            return null;
        }

        if (!data.success) {

            console.warn("API returned error:", data);

            if (data.message) {
                showToast(data.message, "fa-times", "#e74c3c");
            }

            return null;
        }

        return data.data ? data.data : data;

    } catch (error) {

        console.error("API ERROR:", error);

        showToast(
            "Server connection error",
            "fa-server",
            "#e74c3c"
        );

        return null;

    }
}

/* ------------------------------
   ROLL DICE
--------------------------------*/
async function rollDiceAPI() {

    const data = await callAPI(GAME_URL + "ajax/throw_dice.php", {
        session_id: GameController.sessionId
    });

    return data;

}

/* ------------------------------
   OPEN WILDCARD
--------------------------------*/
async function openWildcard(wildcardId) {

      const effect = await callAPI(GAME_URL + "ajax/open_wildcard.php", {
        session_id: GameController.sessionId,
        wildcard_id: wildcardId
    })

    if (!effect) return;

    GameController.capitalRemaining = effect.capital_remaining;

    updateCapitalUI();

}

/* ------------------------------
   RUN AUDIT
--------------------------------*/
async function runAudit() {

    const data = await callAPI(GAME_URL + "ajax/run_audit.php", {
        session_id: GameController.sessionId
    });

    if (!data) return;

    GameController.capitalRemaining = data.capital_remaining;

    updateCapitalUI();

}

/* ------------------------------
   FINISH GAME
--------------------------------*/
async function finishGame(){


    /* CLOSE WILDCARD IF OPEN */
    const wc = document.getElementById("wildcardContainer");
    if(wc){
        wc.style.display = "none";
    }

    const response = await fetch(GAME_URL + "ajax/end_game.php", {
        method: "POST",
        headers: {
            "Content-Type": "application/json"
        },
        body: JSON.stringify({
            session_id: GameController.sessionId
        })
    });

    const data = await response.json();

    if(!data.success) return;

    const stats = data.data.statistics;

    const cardTitle = stats.is_win
        ? "Game Completed Successfully"
        : "Game Over";

    const subtitle = stats.is_win
        ? "Victory!"
        : "No dice remaining!";

    document.querySelector(".gameover-title").innerText = cardTitle;
    document.querySelector(".gameover-subtitle").innerText = subtitle;

    document.getElementById("cellsReached").innerText = stats.max_cell_reached;
    document.getElementById("diceUsed").innerText = stats.total_dice_used;
    document.getElementById("finalCapital").innerText = stats.final_capital;
    document.getElementById("threatsProtected").innerText = stats.threats_protected;
    document.getElementById("opportunitiesUsed").innerText = stats.opportunities_exploited;
    document.getElementById("wildcardsOpened").innerText = stats.wildcards_opened;

    document.getElementById("gameOverOverlay").style.display = "flex";
}
/* ------------------------------
   UPDATE UI
--------------------------------*/
function updateCapitalUI() {

     const el = document.getElementById("riskCapital");

    if (el) {
        el.textContent = GameController.capitalRemaining;
    }

}
function showGameOverCard(stats, completed){

    const overlay = document.getElementById("gameOverOverlay");
    if(overlay){
        overlay.style.display = "flex";
    }

    document.body.style.overflow = "hidden";

    const title = document.getElementById("gameOverTitle");
    const subtitle = document.getElementById("gameOverSubtitle");

    if(title && subtitle){

        if(completed){
            title.innerText = "Game Completed 🎉";
            subtitle.innerText = "You reached the final cell!";
        }else{
            title.innerText = "Game Over";
            subtitle.innerText = "No dice remaining!";
        }

    }

    document.getElementById("cellsReached").innerText =
        stats.cells_reached ?? GameController.currentCell;

    document.getElementById("diceUsed").innerText =
        stats.dice_used ?? (GameController.initialDice - GameController.diceRemaining);

    document.getElementById("finalCapital").innerText =
        stats.final_capital ?? GameController.capitalRemaining;

    document.getElementById("threatsProtected").innerText =
        stats.threats_protected ?? 0;

    document.getElementById("opportunitiesUsed").innerText =
        stats.opportunities_exploited ?? 0;

    document.getElementById("wildcardsOpened").innerText =
        stats.wildcards_opened ?? GameController.wildcardsOpened;

    const rollBtn = document.getElementById("rollDiceBtn");
    if(rollBtn){
        rollBtn.disabled = true;
    }

}


function handleDiceFinished(){
    const wc = document.getElementById("wildcardContainer");
    if(wc){
        wc.style.display = "none";
    }


   const rollBtn = document.getElementById("rollDiceBtn");

if(rollBtn){
    rollBtn.innerHTML = '<i class="fas fa-lock"></i> Dice Finished';
    rollBtn.disabled = true;
}

    disableInvestButton();

    const stats = {
        cells_reached: GameController.currentCell,
        dice_used: GameController.initialDice - GameController.diceRemaining,
        final_capital: GameController.capitalRemaining,
        threats_protected: GameController.threatsProtected,
        opportunities_exploited: GameController.opportunitiesUsed,
        wildcards_opened: GameController.wildcardsOpened
    };

    showGameOverCard(stats,false);
}

async function openWildcardCards(){

    /* prevent multiple selections */
    wildcardSelected = false;

    const response = await callAPI(
        GAME_URL + "ajax/get_wildcards.php",
        { 
            session_id: GameController.sessionId,
            matrix_id: GameController.matrixId
        }
    );

    if(!response || !response.wildcards){
        showToast("Failed to load wildcard options","fa-times","#e74c3c");
        return;
    }

    const container = document.getElementById("wildcardContainer");
    const cardsDiv = document.getElementById("wildcardCards");

    if(!container || !cardsDiv){
        console.error("Wildcard container missing in HTML");
        return;
    }

    /* clear previous cards */
    cardsDiv.innerHTML = "";

    let options = response.wildcards || [];

    /* ---------- Fisher-Yates Shuffle ---------- */
    function shuffleWildcards(arr){
        let shuffled = [...arr];

        for(let i = shuffled.length - 1; i > 0; i--){
            const j = Math.floor(Math.random() * (i + 1));
            [shuffled[i], shuffled[j]] = [shuffled[j], shuffled[i]];
        }

        return shuffled;
    }

    options = shuffleWildcards(options);

    /* ---------- Render Wildcards ---------- */
    options.forEach(option => {

        const card = document.createElement("div");
        card.classList.add("wildcard-card");
        card.setAttribute("tabindex","0");

        card.innerHTML = `
        <div class="card-inner">
            <div class="card-front">
                <i class="fas fa-question"></i>
            </div>
            <div class="card-back">
                <i class="fas fa-question-circle wildcard-icon"></i>
                <h4>${option.wildcard_name}</h4>
                <p>${option.wildcard_description || ""}</p>
            </div>
        </div>
        `;

        /* click selection */
        card.addEventListener("click", () => {
    selectWildcard(card, option);
});
        /* keyboard accessibility */
       card.addEventListener("keypress", (e)=>{
    if(e.key === "Enter"){
        selectWildcard(card, option);
    }
});

        cardsDiv.appendChild(card);

    });

    /* show modal */
    container.style.display = "flex";
    container.removeAttribute("aria-hidden");

    /* focus first card */
    const firstCard = container.querySelector(".wildcard-card");
    if(firstCard){
        firstCard.focus();
    }

}


async function selectWildcard(card, option){

    if(wildcardSelected) return; // prevent second click
    wildcardSelected = true;

    const cards = document.querySelectorAll(".wildcard-card");

    card.querySelector(".card-inner").style.transform = "rotateY(180deg)";

    // 🔒 Disable clicking on all cards
    document.querySelectorAll(".wildcard-card").forEach(c=>{
        c.style.pointerEvents = "none";
    });

    cards.forEach(c=>{
        if(c !== card){
            c.style.opacity = "0.2";
        }
    });

    await new Promise(r => setTimeout(r, 800));

    const result = await callAPI(GAME_URL + "ajax/open_wildcard.php", {
        session_id: GameController.sessionId,
        wildcard_id: option.id
    });

    if(!result){
        showToast("Wildcard failed","fa-times","#e74c3c");
        wildcardSelected = false;
        return;
    }

    GameController.wildcardsOpened++;

/* store result but DO NOT apply yet */
pendingWildcardResult = result;
/* SHOW SWEET ALERT FIRST */
/* Build preview of wildcard effect */
let preview = "";

if(result.effects.capital_change !== 0){
    preview += `
    <div class="wc-preview-row">
        💰 Capital 
        <b>${result.effects.capital_change > 0 ? "+" : ""}${result.effects.capital_change}</b>
    </div>`;
}

if(result.effects.dice_change !== 0){
    preview += `
    <div class="wc-preview-row">
        🎲 Dice 
        <b>${result.effects.dice_change > 0 ? "+" : ""}${result.effects.dice_change}</b>
    </div>`;
}

if(result.effects.cell_change !== 0){
    preview += `
    <div class="wc-preview-row">
        📍 Move 
        <b>${result.effects.cell_change > 0 ? "+" : ""}${result.effects.cell_change} Cells</b>
    </div>`;
}

if(preview === ""){
    preview = `<div class="wc-preview-row">No immediate effect</div>`;
}



const effect = result.effects;

let actionText = [];

/* CAPITAL */
if(effect.capital_change !== 0){

    actionText.push(
        effect.capital_change > 0
        ? `Gain ${effect.capital_change} risk capital.`
        : `Lose ${Math.abs(effect.capital_change)} risk capital.`
    );

}

/* DICE */
if(effect.dice_change !== 0){

    actionText.push(
        effect.dice_change > 0
        ? `Gain ${effect.dice_change} extra dice.`
        : `Lose ${Math.abs(effect.dice_change)} dice.`
    );

}

/* CELL */
if(effect.cell_change !== 0){

    actionText.push(
        effect.cell_change > 0
        ? `Move forward ${effect.cell_change} cells.`
        : `Move back ${Math.abs(effect.cell_change)} cells.`
    );

}

/* NO EFFECT */
if(actionText.length === 0){
    actionText.push(`No immediate effect.`);
}

/* Convert to HTML rows */
const actionsHTML = actionText
    .map(text => `<div class="wildcard-action-text">${text}</div>`)
    .join("");

const swalResult = await Swal.fire({

    title:`⚡ ${result.wildcard.wildcard_name}`,

    html: actionsHTML,

    confirmButtonText:"Apply Card",
    confirmButtonColor:"#6c5ce7",

    allowOutsideClick:false,
    allowEscapeKey:false,

    customClass:{
        popup:"wildcard-swal-popup"
    }

});

/* AFTER CONFIRM */
if(swalResult.isConfirmed){

    const container = document.getElementById("wildcardContainer");

    if(container){
        container.style.display="none";
    }

    wildcardSelected=false;

    applyWildcardEffect(result);

}

}
function applyWildcardEffect(result){

    const effects = result.effects;
    const newValues = result.new_values;
    const wildcard = result.wildcard;

    let message = `<b>${wildcard.wildcard_name}</b><br>`;
    message += `<p>${wildcard.wildcard_description}</p><br>`;

    /* CAPITAL */
    if(effects.capital_change != 0){
 if(effects.capital_change > 0){
        SoundController.play("increase");
    }else{
        SoundController.play("decrease");
    }
        GameController.capitalRemaining = newValues.capital_remaining;

        message += `
        <div>Risk Capital 
        <b>${effects.capital_change > 0 ? "+" : ""}${effects.capital_change}</b></div>
        `;
    }

    /* DICE */
    if(effects.dice_change != 0){

        GameController.diceRemaining = newValues.dice_remaining;

        message += `
        <div>Dice 
        <b>${effects.dice_change > 0 ? "+" : ""}${effects.dice_change}</b></div>
        `;
    }

    /* CELL */
    if(effects.cell_change != 0){

    const fromCell = GameController.currentCell;
    const newCell = parseInt(newValues.current_cell);

    message += `
    <div>Move 
    <b>${effects.cell_change > 0 ? "+" : ""}${effects.cell_change} Cells</b></div>
    `;

    /* STEP BY STEP MOVEMENT */
    movePlayerStepByStep(fromCell, newCell).then(()=>{

        GameController.currentCell = newCell;
        currentCell = newCell;
        window.gameInitData.currentCell = newCell;

        movePlayerToCell(newCell);
        highlightCell(newCell);

        updateArrowActivation(newCell);

    });

}

    updateGameStats();

showWildcardToast(wildcard, effects);

if(result.game_ended){

    const container = document.getElementById("wildcardContainer");
    if(container){
        container.style.display = "none";
    }

    finishGame();
}
}

function showWildcardToast(wildcard, effects){

    let rows = [];

    /* CAPITAL */
    if(effects.capital_change !== 0){

        if(effects.capital_change > 0){
            SoundController.play("increase");
        }else{
            SoundController.play("decrease");
        }

        rows.push(`
        <div class="toast-row">
            <div class="toast-text">
                ${effects.capital_change > 0
                    ? `You gained <b class="gain">+${effects.capital_change}</b> Risk Capital`
                    : `You lost <b class="loss">-${Math.abs(effects.capital_change)}</b> Risk Capital`}
            </div>
        </div>`);
    }

    /* DICE */
    if(effects.dice_change !== 0){

        if(effects.dice_change > 0){
            SoundController.play("increase");
        }else{
            SoundController.play("decrease");
        }

        rows.push(`
        <div class="toast-row">
            <div class="toast-text">
                ${effects.dice_change > 0
                    ? `You gained <b class="gain">+${effects.dice_change}</b> Extra Dice`
                    : `You lost <b class="loss">-${Math.abs(effects.dice_change)}</b> Dice`}
            </div>
        </div>`);
    }

    /* CELL MOVEMENT */
    if(effects.cell_change !== 0){

        SoundController.play("token");

        rows.push(`
        <div class="toast-row">
            <div class="toast-text">
                ${effects.cell_change > 0
                    ? `Moved forward <b>${effects.cell_change}</b> cells`
                    : `Moved backward <b>${Math.abs(effects.cell_change)}</b> cells`}
            </div>
        </div>`);
    }

    if(rows.length === 0){
        rows.push(`
        <div class="toast-row">
            <div class="toast-text">No immediate effect</div>
        </div>`);
    }

    const toastHTML = `
        <div class="wildcard-toast">
            ${rows.join("")}
        </div>
    `;

    showToast(toastHTML,"","#9b59b6",5000);
}
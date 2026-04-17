document.addEventListener("DOMContentLoaded", checkExistingDraft);



let ui_id = null;

let draftData = {};



/* 

   HELPERS

 */



const $ = (id) => document.getElementById(id);

const val = (id) => $(id)?.value?.trim() || "";



function showLoader(show) {

    const l = $("loader");

    if (l) l.style.display = show ? "block" : "none";

}



function setStep(n) {

    document.querySelectorAll(".step-dot").forEach(d => d.classList.remove("active"));

    const el = $(`step-dot-${n}`);

    if (el) el.classList.add("active");

}



function changeVal(id, delta) {

    const input = $(id);

    if (!input) return;



    const min = parseInt(input.getAttribute("min")) || 0;

    const next = parseInt(input.value) + delta;

    if (next >= min) input.value = next;

}



function getButtonFooter(nextAction, nextLabel = "Next", prevAction = null) {

    return `

        <div class="button-footer">

            ${prevAction ? `<button class="btn-secondary" onclick="${prevAction}">Previous</button>` : `<span></span>`}

            <button onclick="${nextAction}">${nextLabel}</button>

        </div>

    `;

}



/* 

   INIT / RESUME

 */



function checkExistingDraft() {

    showLoader(true);

    fetch("api/byteguess_check_draft.php")

        .then(r => r.json())

        .then(d => {

            if (d.status === "found") {

                ui_id = d.ui_id;

                draftData = {

                    ...d.data,

                    ui_options: typeof d.data.ui_options === "string"

                        ? JSON.parse(d.data.ui_options)

                        : d.data.ui_options

                };

                showResumePrompt(d.step);

            } else {

                loadStep1();

            }

        })

        .finally(() => showLoader(false));

}



function showResumePrompt(step) {

    $("wizard-box").innerHTML = `

        <div style="text-align:center;padding:20px;">

            <h3>Welcome Back</h3>

            <p>Continue <strong>${draftData.ui_game_name || "Untitled"}</strong>?</p>

            <div style="display:flex;gap:16px;justify-content:center;margin-top:24px;">

                <button class="btn-secondary" onclick="startOver()">Start New</button>

                <button onclick="resumeStep(${step})">Resume</button>

            </div>

        </div>

    `;

}



function resumeStep(step) {

    ({ 1: loadStep1, 2: loadStep2, 3: loadStep3, 4: loadStep4 }[step] || loadStep1)();

}



function startOver() {

    if (confirm("This will clear the current draft. Continue?")) {

        ui_id = null;

        draftData = {};

        loadStep1();

    }

}



/* 

   SAVE

 */



function saveStep(step, data, next) {

    draftData = { ...draftData, ...data };

    showLoader(true);



    fetch("api/byteguess_save_step.php", {

        method: "POST",

        headers: { "Content-Type": "application/json" },

        body: JSON.stringify({ ui_id, step, data })

    })

        .then(r => r.json())

        .then(d => {

            if (d.status === "success") {

                if (d.ui_id) ui_id = d.ui_id;

                next && next();

            } else alert(d.message || "Save failed");

        })

        .finally(() => showLoader(false));

}



/* 

   STEP 1

 */



function loadStep1() {

    setStep(1);

    $("wizard-box").innerHTML = `

        <h3>Exercise Setup</h3>



        <div class="form-grid">

            <div class="form-group form-grid-full">

                <label>Game Name</label>

                <input id="ui_game_name" value="${draftData.ui_game_name || ""}">

            </div>



            <div class="form-group form-grid-full">

                <label>Game Description</label>

                <textarea id="ui_game_description">${draftData.ui_game_description || ""}</textarea>

            </div>



            <div class="form-group">

                <label>Total Cards</label>

                <input id="ui_total_cards" type="number" min="6" value="${draftData.ui_total_cards || 12}">

            </div>



            <div class="form-group">

                <label>Cards Drawn</label>

                <input id="ui_cards_drawn" type="number" value="${draftData.ui_cards_drawn || 4}">

            </div>



            <div class="form-group form-grid-full">

                <label>Card Structure</label>

                <textarea id="ui_card_structure">${draftData.ui_card_structure || ""}</textarea>

            </div>

        </div>



        ${getButtonFooter("submitStep1()")}

    `;

}





function submitStep1() {

    saveStep(1, {

        ui_game_name: val("ui_game_name"),

        ui_game_description: val("ui_game_description"),

        ui_total_cards: +val("ui_total_cards"),

        ui_cards_drawn: +val("ui_cards_drawn"),

        ui_card_structure: val("ui_card_structure")

    }, loadStep2);

}



/* 

   STEP 2

 */

function loadStep2() {

    setStep(2);

    $("wizard-box").innerHTML = `

        <h3>Training Context</h3>



        <div class="form-grid">

            <div class="form-group">

                <label>Training Topic</label>

                <input id="ui_training_topic" value="${draftData.ui_training_topic || ""}">

            </div>



            <div class="form-group">

                <label>Industry</label>

                <input id="ui_industry" value="${draftData.ui_industry || ""}">

            </div>



            <div class="form-group">

                <label>Objective</label>

                <input id="ui_objective" value="${draftData.ui_objective || ""}">

            </div>



            <div class="form-group form-grid-full">

                <label>Hypothesis</label>

                <textarea id="ui_hypothesis">${draftData.ui_hypothesis || ""}</textarea>

            </div>

        </div>



        ${getButtonFooter("submitStep2()", "Next", "loadStep1()")}

    `;

}





function submitStep2() {

    saveStep(2, {

        ui_training_topic: val("ui_training_topic"),

        ui_industry: val("ui_industry"),

        ui_objective: val("ui_objective"),

        ui_hypothesis: val("ui_hypothesis")

    }, loadStep3);

}



/* 

   STEP 3 — OPTIONS & CLUES

 */



function loadStep3() {

    setStep(3);

    const opts = draftData.ui_options || { full: 1, partial: 1, wrong: 2 };

    const hasClues = (draftData.ui_clue || 0) > 0;



    $("wizard-box").innerHTML = `

        <h3>Options Mix & Clues</h3>



        ${optionRow("correct", "✓", "Fully Correct", "Best possible interpretation", "opt_full", opts.full, 1)}

        ${optionRow("partial", "⚠", "Partially Correct", "Plausible but incomplete", "opt_partial", opts.partial, 0)}

        ${optionRow("wrong", "✕", "Incorrect", "Convincing but wrong", "opt_wrong", opts.wrong, 1)}



        <div style="margin-top:20px;">

            <label style="display:flex;gap:10px;align-items:center;">

                <input type="checkbox" id="wants_clues" ${hasClues ? "checked" : ""} onchange="toggleClues(this.checked)">

                Enable Clues

            </label>



            <div id="clue_wrap" style="margin-top:12px;display:${hasClues ? "block" : "none"};">

                <label>Number of Clues</label>

                <div class="stepper-wrap">

                    <button onclick="changeVal('ui_clue', -1)">−</button>

                    <input id="ui_clue" type="number" min="1" value="${draftData.ui_clue || 2}" readonly>

                    <button onclick="changeVal('ui_clue', 1)">+</button>

                </div>

            </div>

        </div>



        ${getButtonFooter("submitStep3()", "Review", "loadStep2()")}

    `;

}



function optionRow(type, icon, title, desc, id, value, min) {

    return `

        <div class="option-card ${type}">

            <div class="option-left">

                <div class="option-icon">${icon}</div>

                <div class="option-info">

                    <label>${title}</label>

                    <span>${desc}</span>

                </div>

            </div>

            <div class="stepper-wrap">

                <button onclick="changeVal('${id}', -1)">−</button>

                <input id="${id}" type="number" min="${min}" value="${value}" readonly>

                <button onclick="changeVal('${id}', 1)">+</button>

            </div>

        </div>

    `;

}



function toggleClues(on) {

    const w = $("clue_wrap");

    if (w) w.style.display = on ? "block" : "none";

}



function submitStep3() {

    const hasClues = $("wants_clues")?.checked;



    saveStep(3, {

        ui_options: {

            full: +val("opt_full"),

            partial: +val("opt_partial"),

            wrong: +val("opt_wrong")

        },

        ui_clue: hasClues ? +val("ui_clue") : 0

    }, loadStep4);

}



/* 

   STEP 4 — REVIEW

 */



function loadStep4() {

    setStep(4);

    showLoader(true);



    fetch(`api/byteguess_get_review.php?ui_id=${ui_id}`)

        .then(r => r.json())

        .then(d => {

            const o = typeof d.ui_options === "string" ? JSON.parse(d.ui_options) : d.ui_options;

            $("wizard-box").innerHTML = `

                <h3>Review</h3>

                <p><strong>Game:</strong> ${d.ui_game_name}</p>

                <p><strong>Cards:</strong> ${d.ui_total_cards} / ${d.ui_cards_drawn}</p>

                <p><strong>Topic:</strong> ${d.ui_training_topic}</p>

                <p><strong>Industry:</strong> ${d.ui_industry}</p>

                <p><strong>Hypothesis:</strong> ${d.ui_hypothesis}</p>

                <p><strong>Options:</strong> Full(${o.full}), Partial(${o.partial}), Wrong(${o.wrong})</p>

                <p><strong>Clues:</strong> ${d.ui_clue || "None"}</p>



                ${getButtonFooter("generate()", "Generate", "loadStep3()")}

            `;

        })

        .finally(() => showLoader(false));

}



/* 

   GENERATE

 */



async function generate() {

    setStep(5);

    showLoader(true);



    try {

        const r = await fetch("api/byteguess_generate_game.php", {

            method: "POST",

            headers: { "Content-Type": "application/json" },

            body: JSON.stringify({ ui_id })

        });

        const d = await r.json();



        if (d.status === "success") {

            $("wizard-box").innerHTML = `

                <div style="text-align:center;padding:20px;">

                    <h3 style="color:#059669;">Exercise Created </h3>

                    <button onclick="location.reload()">Create New</button>

                </div>

            `;

        } else alert(d.message);

    } catch {

        alert("Generation failed");

    } finally {

        showLoader(false);

    }

}


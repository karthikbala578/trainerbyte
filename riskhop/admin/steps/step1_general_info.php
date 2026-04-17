
<style>
    html, body{
height:100vh;
margin:0;
overflow:hidden; /* ❗ completely remove scroll */
}
 #generalInfoForm{
    display: flex;
    flex-direction: column;
    height: 100%;
    min-height: 100%;   /* 🔥 IMPORTANT */
}
.form-body{
    flex: 1;
}
.form-footer{
    margin-top: auto;   /* 🔥 KEY LINE */
    display: flex;
    justify-content: flex-end;
}
.form-grid{
    min-height: 0;
}
.form-group{
    margin-bottom: 4px;   /* reduce from 6px */
}

#descriptionEditor{
    height: 100px;  /* was 120px */
}

.matrix-option{
    height: 100px;  /* was 115px */
}

/* DEFAULT → NO SCROLL (for <425px and >1440px) */
.form-body{
    overflow: visible;
    max-height: unset;
}

/* ✅ APPLY SCROLL ONLY BETWEEN 425px → 1440px */
@media (min-width: 425px) and (max-width: 1440px){
    .form-body{
        max-height: 70vh;
        overflow-y: auto;
        padding-right: 8px;
    }
}

/* OPTIONAL SCROLLBAR */
.form-body::-webkit-scrollbar{
    width: 6px;
}
.form-body::-webkit-scrollbar-thumb{
    background: #cbd5f5;
    border-radius: 10px;
}
</style>
    <form id="generalInfoForm">
        <div class="form-body">

            <input type="hidden" name="game_id" value="<?php echo $game_id; ?>">

            <div class="form-grid">

                <div class="form-group">
                    <label>Game Name</label>
                    <input type="text" name="game_name" id="game_name"
                        value="<?php echo $game_data ? htmlspecialchars($game_data['game_name']) : ''; ?>"
                        placeholder="Enter game name">
                    <div class="field-error" id="error_game_name"></div>
                </div>

                <div class="form-group">
                    <label>Description</label>
                    <div id="descriptionEditor"></div>
<input type="hidden" name="description" id="description">
                    <div class="field-error" id="error_description"></div>
                </div>

            </div>

            <div class="form-grid">

                <div class="form-group">
                    <label>Initial Risk Capital</label>
                    <input type="number" name="capital" id="capital"
                        value="<?php echo $game_data ? $game_data['risk_capital'] : '100'; ?>"
                        placeholder="100">
                    <div class="field-error" id="error_capital"></div>
                </div>

                <div class="form-group">
                    <label>Dice Limit</label>
                    <input type="number" name="dice_limit" id="dice_limit"
                        value="<?php echo $game_data ? $game_data['dice_limit'] : '15'; ?>"
                        placeholder="1 - 15">
                    <div class="field-error" id="error_dice_limit"></div>
                </div>

            </div>

            <div class="form-group">

                <label>Board Matrix Choice</label>

                <div class="matrix-grid <?php echo ($game_id > 0) ? 'disabled-matrix' : ''; ?>">

                    <?php
                    $selected_size = '';

                    if ($game_data && !empty($game_data['matrix_type'])) {
                        $parts = explode('x', $game_data['matrix_type']);
                        $selected_size = intval($parts[0]);
                    }
                    ?>

                    <div class="matrix-option <?php echo ($selected_size == 6) ? 'active' : ''; ?> <?php echo ($game_id > 0) ? 'locked' : ''; ?>" data-size="6">
                        <div class="matrix-preview" data-grid="6"></div>
                        Small (6×6)
                    </div>

                    <div class="matrix-option <?php echo ($selected_size == 8) ? 'active' : ''; ?> <?php echo ($game_id > 0) ? 'locked' : ''; ?>" data-size="8">
                        <div class="matrix-preview" data-grid="8"></div>
                        Medium (8×8)
                    </div>

                    <div class="matrix-option <?php echo ($selected_size == 10) ? 'active' : ''; ?> <?php echo ($game_id > 0) ? 'locked' : ''; ?>" data-size="10">
                        <div class="matrix-preview" data-grid="10"></div>
                        Large (10×10)
                    </div>

                    <div class="matrix-option <?php echo ($selected_size == 12) ? 'active' : ''; ?> <?php echo ($game_id > 0) ? 'locked' : ''; ?>" data-size="12">
                        <div class="matrix-preview" data-grid="12"></div>
                        Extra Large (12×12)
                    </div>

                </div>

                <input type="hidden" name="board_size" id="board_size" value="<?php echo $selected_size; ?>">
                <div class="field-error" id="error_board_size"></div>

            </div>
            <?php if($game_id > 0): ?>
            <small style="color:#f59e0b;">Board size cannot be changed after creation</small>
            <?php endif; ?>
        </div>
        <div class="form-footer">
            <button class="continue-btn" type="submit">
                Continue
                <i class="fa fa-arrow-right"></i>
            </button>
        </div>
    </form>



<script>
       function setError(id, msg){
    const input = document.getElementById(id);
    const error = document.getElementById("error_" + id);

    if(error) error.innerText = msg;

    if(input){
        input.classList.toggle("input-error", !!msg);
    }
}
const continueBtn = document.querySelector('.continue-btn');
document.addEventListener("DOMContentLoaded", function(){

let boardWarningShown = false;


<?php if($game_id == 0): ?>
    continueBtn.disabled = true;
    continueBtn.style.opacity = "0.6";
<?php endif; ?>
document.querySelectorAll('.matrix-option').forEach(function(el){

    el.addEventListener('click',function(){

        const selectedSize = this.dataset.size;

        // Show alert only first time (CREATE MODE only)
        if(!boardWarningShown && <?php echo ($game_id == 0 ? 'true' : 'false'); ?>){

            Swal.fire({
                title: 'Confirm Board Selection',
                text: 'This board size can be selected only once and cannot be changed later.',
                icon: 'warning',
                confirmButtonText: 'OK',
                allowOutsideClick: false,
                allowEscapeKey: false,
                confirmButtonColor: '#4f6df5'
            }).then(() => {

                boardWarningShown = true;
                applyBoardSelection(this);

            });

        } else {
            applyBoardSelection(this);
        }

    });

});
});
function applyBoardSelection(element){

    document.querySelectorAll('.matrix-option').forEach(o=>{
        o.classList.remove('active');
        o.classList.remove('matrix-error');
    });

    element.classList.add('active');

    document.getElementById('board_size').value = element.dataset.size;

    document.getElementById('error_board_size').innerText = "";

  
    continueBtn.disabled = false;
continueBtn.style.opacity = "1";
continueBtn.style.cursor = "pointer";
}
// PREVIEW GRID
document.querySelectorAll('.matrix-preview').forEach(function(grid){

    let size = parseInt(grid.dataset.grid);

    let maxSize = 60; // bigger preview
    let cellSize = Math.floor(maxSize / size);

    grid.style.gridTemplateColumns = `repeat(${size}, ${cellSize}px)`;

    for(let i=0;i<size*size;i++){
        let cell = document.createElement('div');

        cell.style.width = cellSize + "px";
cell.style.height = cellSize + "px";
cell.style.background = "#cbd5f5";   // lighter
cell.style.border = "1px solid #94a3b8"; // darker border

        grid.appendChild(cell);
    }

});


// FORM SUBMIT (AJAX - SAME OLD LOGIC)
document.getElementById('generalInfoForm').addEventListener('submit', function(e){
    e.preventDefault();

    let valid = true;
    function setErrorWrapper(id, msg){
        setError(id, msg);
        if(msg) valid = false;
    }
    const gameId = parseInt(document.querySelector('[name="game_id"]').value) || 0;


    // RESET
    ["game_name","description","capital","dice_limit","board_size"].forEach(id=>{
        setError(id, "");
    });

    let name = document.getElementById("game_name").value.trim();
    let desc = quill.root.innerHTML.trim();
    document.getElementById("description").value = desc;
    let capital = parseFloat(document.getElementById("capital").value);
    let dice = parseInt(document.getElementById("dice_limit").value);
    let board = document.getElementById("board_size").value;

    // ✅ GAME NAME
    if(!name){
        setErrorWrapper("game_name","Game name is required");
    } else if(name.length < 3){
        setErrorWrapper("game_name","Minimum 3 characters required");
    } else if(name.length > 50){
        setErrorWrapper("game_name","Maximum 50 characters allowed");
    }

    // ✅ DESCRIPTION
    if(!desc || desc === '<p><br></p>'){
        setErrorWrapper("description","Description is required");
    }else if(desc.length < 3){
        setErrorWrapper("description","Minimum 3 characters required");
    }

    
// ✅ CAPITAL (required + > 0)
if(document.getElementById("capital").value.trim() === ""){
    setErrorWrapper("capital","Required");
} else if(capital <= 0){
    setErrorWrapper("capital","Must be greater than 0");
}

// ✅ DICE LIMIT (required + > 0)
if(document.getElementById("dice_limit").value.trim() === ""){
    setErrorWrapper("dice_limit","Required");
} else if(dice <= 0){
    setErrorWrapper("dice_limit","Must be greater than 0");
}

    // ✅ BOARD SIZE
    if(!board){
        setErrorWrapper("board_size","Please select a board matrix");

        document.querySelectorAll(".matrix-option").forEach(el=>{
            el.classList.add("matrix-error");
        });

        document.querySelector('.matrix-grid').scrollIntoView({
            behavior: "smooth",
            block: "center"
        });
    }

    // ❗ IMPORTANT: Prevent board change in EDIT
    if(gameId > 0 && board != "<?php echo $selected_size; ?>"){
        setErrorWrapper("board_size","Board size cannot be changed after creation");
    }

    if(!valid){
        document.querySelector(".input-error")?.scrollIntoView({
            behavior: "smooth",
            block: "center"
        });
        return;
    }

    const formData = new FormData(this);

    fetch('ajax/save_general_info.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {

        console.log("API RESPONSE:", data);

        if(data.success){

            let newGameId = 0;

            if(data.data && data.data.game_id){
                newGameId = data.data.game_id;
            } 
            else if(data.game_id){
                newGameId = data.game_id;
            } 
            else{
                newGameId = gameId;
            }

            if(!newGameId || newGameId == 0){
                alert("Game ID not returned. Cannot proceed.");

                // 🔥 re-enable button
                continueBtn.disabled = false;
                continueBtn.innerHTML = "Continue";

                return;
            }

            // ✅ already correct (you added this 👍)
            document.querySelector('[name="game_id"]').value = newGameId;

            window.location.href = 'create_game.php?id=' + newGameId + '&step=2';

        } else {
            alert(data.message || 'Something went wrong');

            // 🔥 re-enable button
            continueBtn.disabled = false;
            continueBtn.innerHTML = "Continue";
        }
    })
    .catch(() => {
        alert('Error occurred');

        // 🔥 re-enable button
        continueBtn.disabled = false;
        continueBtn.innerHTML = "Continue";
    });
});

// =======================
// LIVE INLINE VALIDATION
// =======================

function validateField(id){
    let value = document.getElementById(id).value.trim();

    switch(id){

        case "game_name":
            if(!value){
                setError(id, "Game name is required");
            } else if(value.length < 3){
                setError(id, "Minimum 3 characters required");
            } else if(value.length > 100){
                setError(id, "Max 100 characters allowed");
            } else {
                setError(id, "");
            }
        break;

        case "description":
            if(!value){
                setError(id, "Description is required");
            } else if(value.length < 3){
                setError(id, "Minimum 3 characters required");
            } else if(value.length > 500){
                setError(id, "Maximum 500 characters allowed");
            } else {
                setError(id, "");
            }
        break;

       case "capital":
    if(value === ""){
        setError(id, "Required");
    } else if(parseFloat(value) <= 0){
        setError(id, "Must be greater than 0");
    } else {
        setError(id, "");
    }
break;

case "dice_limit":
    if(value === ""){
        setError(id, "Required");
    } else if(parseInt(value) <= 0){
        setError(id, "Must be greater than 0");
    } else {
        setError(id, "");
    }
break;
    }
}


// Attach events
["game_name","description","capital","dice_limit"].forEach(id=>{
    const el = document.getElementById(id);

    el.addEventListener("blur", () => validateField(id));   // when leave field
    el.addEventListener("input", () => validateField(id));  // while typing
});

let quill;   // ✅ GLOBAL

document.addEventListener("DOMContentLoaded", function(){

    quill = new Quill('#descriptionEditor', {
        theme: 'snow',
        placeholder: 'Enter game description...',
        modules: {
            toolbar: [
                ['bold', 'italic', 'underline'],
                [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                ['link'],
                ['clean']
            ]
        }
    });

    <?php if($game_data && !empty($game_data['description'])): ?>
        quill.root.innerHTML = <?php echo json_encode($game_data['description']); ?>;
    <?php endif; ?>

});
</script>
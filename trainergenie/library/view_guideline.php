<?php

session_start();

require "../include/dataconnect.php";



if (!isset($_SESSION['team_id'])) {

    header("Location: ../login.php");

    exit;

}



$cg_id = intval($_GET['cg_id'] ?? 0);

if ($cg_id <= 0) {

    die("Invalid game");

}



$pageTitle = "Guidelines";

$pageCSS   = "/library/result.css";

require "../layout/header.php";



/* Fetch game name + answer key */

$stmt = $conn->prepare("

    SELECT cg_name, cg_guidelines, cg_play_guide_image

    FROM card_group

    WHERE cg_id = ?

");

$stmt->bind_param("i", $cg_id);

$stmt->execute();

$game = $stmt->get_result()->fetch_assoc();

?>



<div class="logic-wrap">



    <!-- HEADER -->

    <div class="editor-header">

        <a href="view_game.php?cg_id=<?= $cg_id ?>" class="back-link">

            ← Back to Game

        </a>

        <h1><?= htmlspecialchars($game['cg_name']) ?> – Guidelines</h1>

    </div>



    <!-- CONTENT -->

    <div class="logic-card">



        <label>Game Guidelines</label>



        <textarea id="guideline"

                  rows="10"

                  placeholder="Explain why the correct answer is strongest and how card signals connect..."><?= htmlspecialchars($game['cg_guidelines'] ?? '') ?></textarea>
                <label>Upload New Guideline Image</label>
                <input type="file" id="guide_image" accept="image/*" class="form-control mt-2">
                 <?php if (!empty($game['cg_play_guide_image'])){ ?>
                
                    <div style="margin-top:15px;">
                        <label>Uploaded Guide Image</label><br>

                        <img src="../uploads/guidelines/<?= htmlspecialchars($game['cg_play_guide_image']) ?>" 
                            style="max-width:200px; border-radius:10px; margin-top:10px;">

                        <p style="margin-top:5px; font-size:12px; color:#666;">
                            <?= htmlspecialchars($game['cg_play_guide_image']) ?>
                        </p>
                    </div>

                <?php }?>
        
        <div class="editor-actions">

            <button class="btn primary" onclick="saveResult()">Save Guideline</button>

        </div>



    </div>



</div>



<script>

// function saveResult() {

//     fetch("save_guideline.php", {

//         method: "POST",

//         headers: { "Content-Type": "application/json" },

//         body: JSON.stringify({

//             cg_id: <?= $cg_id ?>,

//             guideline: document.getElementById("guideline").value.trim()

//         })

//     })

//     .then(res => res.text())

//     .then(t => JSON.parse(t))

//     .then(data => {

//         if (data.status === "success") {

//             alert("Guidelines saved successfully");

//         } else {

//             alert(data.message || "Save failed");

//         }

//     })

//     .catch(() => alert("Unexpected server error"));

// }
function saveResult() {

    let formData = new FormData();

    formData.append("cg_id", <?= $cg_id ?>);
    formData.append("guideline", document.getElementById("guideline").value.trim());

    let fileInput = document.getElementById("guide_image");
    if (fileInput.files.length > 0) {
        formData.append("guide_image", fileInput.files[0]);
    }

    fetch("save_guideline.php", {
        method: "POST",
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.status === "success") {
            alert("Guidelines saved successfully");
            location.reload(); // optional refresh
        } else {
            alert(data.message || "Save failed");
        }
    })
    .catch(() => alert("Unexpected server error"));
}
</script>
<style>
    .ck-editor__editable_inline {
    min-height: 300px;   /* 👈 change as needed */
    max-height: 600px;
    overflow-y: auto;
}
</style>
<script src="https://cdn.ckeditor.com/ckeditor5/39.0.1/classic/ckeditor.js"></script>
<script>
ClassicEditor
    .create(document.querySelector('#guideline'))
    .then(editor => {
        console.log('Editor initialized', editor);
    })
    .catch(error => {
        console.error(error);
    });

    
</script>


<?php require "../layout/footer.php"; ?>


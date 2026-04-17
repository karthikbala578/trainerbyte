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



$pageTitle = "Casestudy";

$pageCSS   = "/library/result.css";

require "../layout/header.php";



/* Fetch game name + answer key */

$stmt = $conn->prepare("

    SELECT cg_name, cg_ex_context_desc, cg_ex_context_image

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

        <h1><?= htmlspecialchars($game['cg_name']) ?> – Casestudy</h1>

    </div>



    <!-- CONTENT -->

    <div class="logic-card">



        <label>Game Casestudy</label>



        <textarea id="casestudy"

                  rows="10"

                  placeholder="Explain why the correct answer is strongest and how card signals connect..."><?= htmlspecialchars($game['cg_ex_context_desc'] ?? '') ?></textarea>
                <label>Upload New Casestudy Image</label>
                <input type="file" id="casestudy_image" accept="image/*" class="form-control mt-2">
                 <?php if (!empty($game['cg_ex_context_image'])){ ?>
                
                    <div style="margin-top:15px;">
                        <label>Uploaded Casestudy Image</label><br>

                        <img src="../uploads/casestudy/<?= htmlspecialchars($game['cg_ex_context_image']) ?>" 
                            style="max-width:200px; border-radius:10px; margin-top:10px;">

                        <p style="margin-top:5px; font-size:12px; color:#666;">
                            <?= htmlspecialchars($game['cg_ex_context_image']) ?>
                        </p>
                    </div>

                <?php }?>
        
        <div class="editor-actions">

            <button class="btn primary" onclick="saveResult()">Save Casestudy</button>

        </div>



    </div>



</div>



<script>

function saveResult() {

    let formData = new FormData();

    formData.append("cg_id", <?= $cg_id ?>);
    formData.append("casestudy", document.getElementById("casestudy").value.trim());

    let fileInput = document.getElementById("casestudy_image");
    if (fileInput.files.length > 0) {
        formData.append("casestudy_image", fileInput.files[0]);
    }

    fetch("save_casestudy.php", {
        method: "POST",
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.status === "success") {
            alert("Casestudy saved successfully");
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
    .create(document.querySelector('#casestudy'))
    .then(editor => {
        console.log('Editor initialized', editor);
    })
    .catch(error => {
        console.error(error);
    });

    
</script>

<?php require "../layout/footer.php"; ?>


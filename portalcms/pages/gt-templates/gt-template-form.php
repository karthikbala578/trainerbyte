<?php
require_once "../../../include/session_check.php";
require "../../../include/dataconnect.php";

$gt_id  = isset($_GET['gt_id']) ? intval($_GET['gt_id']) : 0;
$isEdit = $gt_id > 0;

$pageTitle = $isEdit ? "Edit Template" : "Create Template";
$pageCSS   = "/portalcms/pages/page-styles/gt-template-form.css";

require "../../layout/header.php";
require "../../layout/navbar.php";

/* ================= MASTER DATA ================= */

$categories = mysqli_query($conn, "SELECT * FROM tb_cms_category WHERE ct_status=1");
$gametypes  = mysqli_query($conn, "SELECT * FROM tb_cms_gametype WHERE gm_status=1");
$durations  = mysqli_query($conn, "SELECT * FROM tb_cms_duration WHERE dr_status=1");
$formats    = mysqli_query($conn, "SELECT * FROM tb_cms_format WHERE fm_status=1");
$complexity = mysqli_query($conn, "SELECT * FROM tb_cms_complexity WHERE cx_status=1");

$template = [];
$learning_outcomes = [];
$hiw_steps = [];
$showPopup = false;



/* ================= EDIT FETCH ================= */

if ($isEdit) {

    $res = mysqli_query($conn, "SELECT * FROM tb_cms_gametype_template WHERE gt_id=$gt_id");
    $template = mysqli_fetch_assoc($res);

    $learning_outcomes = json_decode($template['gt_learning_outcomes'], true) ?? [];


    $hiw_res = mysqli_query($conn, "SELECT * FROM tb_cms_gt_howitworks WHERE hw_template_id=$gt_id ORDER BY hw_step_no ASC");
    while ($row = mysqli_fetch_assoc($hiw_res)) {
        $hiw_steps[] = $row;
    }
}

/* ================= SAVE ================= */

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $title   = mysqli_real_escape_string($conn, $_POST['gt_title']);
    $tagline = mysqli_real_escape_string($conn, $_POST['gt_tagline']);
    $short   = mysqli_real_escape_string($conn, $_POST['gt_short_desc']);
    $full    = mysqli_real_escape_string($conn, $_POST['gt_full_desc']);
    $status  = intval($_POST['gt_status']);

    $learning_json = json_encode(array_values(array_filter($_POST['learning_outcomes'] ?? [])));

    $gt_image = $template['gt_image'] ?? '';

    if (isset($_FILES['gt_image']) && $_FILES['gt_image']['error'] == 0) {
        $uploadDir = __DIR__ . "/gt-templates-uploads/";
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
        $fileName = time() . "_" . basename($_FILES['gt_image']['name']);
        if (move_uploaded_file($_FILES['gt_image']['tmp_name'], $uploadDir . $fileName)) {
            $gt_image = $fileName;
        }
    }

    if ($isEdit) {

        mysqli_query($conn, "UPDATE tb_cms_gametype_template SET
            gt_title='$title',
            gt_tagline='$tagline',
            gt_short_desc='$short',
            gt_full_desc='$full',
            gt_image='$gt_image',
            gt_learning_outcomes='$learning_json',
            gt_status='$status',
            gt_updated_at=NOW()
            WHERE gt_id=$gt_id");

        $new_id = $gt_id;

        mysqli_query($conn, "DELETE FROM tb_cms_gt_category_map WHERE gtc_template_id=$new_id");
        mysqli_query($conn, "DELETE FROM tb_cms_gt_gametype_map WHERE gtg_template_id=$new_id");
        mysqli_query($conn, "DELETE FROM tb_cms_gt_duration_map WHERE gtd_template_id=$new_id");
        mysqli_query($conn, "DELETE FROM tb_cms_gt_format_map WHERE gtf_template_id=$new_id");
        mysqli_query($conn, "DELETE FROM tb_cms_gt_complexity_map WHERE gtx_template_id=$new_id");
        mysqli_query($conn, "DELETE FROM tb_cms_gt_howitworks WHERE hw_template_id=$new_id");
    } else {

        mysqli_query($conn, "INSERT INTO tb_cms_gametype_template
            (gt_title,gt_tagline,gt_short_desc,gt_full_desc,gt_image,gt_learning_outcomes,gt_status,gt_created_at)
            VALUES
            ('$title','$tagline','$short','$full','$gt_image','$learning_json','$status',NOW())");

        $new_id = mysqli_insert_id($conn);
    }


    $mapTables = [
        "ct_id" => "tb_cms_gt_category_map",
        "gm_id" => "tb_cms_gt_gametype_map",
        "dr_id" => "tb_cms_gt_duration_map",
        "fm_id" => "tb_cms_gt_format_map",
        "cx_id" => "tb_cms_gt_complexity_map"
    ];

    foreach ($mapTables as $postKey => $table) {

        if (!empty($_POST[$postKey])) {

            foreach ($_POST[$postKey] as $value) {

                $value = intval($value);

                switch ($postKey) {
                    case "ct_id":
                        $col1 = "gtc_template_id";
                        $col2 = "gtc_category_id";
                        break;
                    case "gm_id":
                        $col1 = "gtg_template_id";
                        $col2 = "gtg_gametype_id";
                        break;
                    case "dr_id":
                        $col1 = "gtd_template_id";
                        $col2 = "gtd_duration_id";
                        break;
                    case "fm_id":
                        $col1 = "gtf_template_id";
                        $col2 = "gtf_format_id";
                        break;
                    case "cx_id":
                        $col1 = "gtx_template_id";
                        $col2 = "gtx_complexity_id";
                        break;
                }

                mysqli_query($conn, "INSERT INTO $table ($col1, $col2)
                                     VALUES ($new_id, $value)");
            }
        }
    }


    $hiwDir = __DIR__ . "/gt-templates-hiw-uploads/";
    if (!is_dir($hiwDir)) mkdir($hiwDir, 0777, true);
    
    if (isset($_POST['hw_title'])) {
    
        foreach ($_POST['hw_title'] as $i => $hwTitle) {
    
            if (empty(trim($hwTitle))) continue;
    
            $hwImageName = "";
    
            /* IMAGE UPLOAD */
    
            if (!empty($_FILES['hw_image']['name'][$i])) {
    
                $ext = pathinfo($_FILES['hw_image']['name'][$i], PATHINFO_EXTENSION);
    
                $hwImageName = "hiw_" . time() . "_" . $i . "." . $ext;
    
                move_uploaded_file(
                    $_FILES['hw_image']['tmp_name'][$i],
                    $hiwDir . $hwImageName
                );
            }
    
            /* INSERT STEP */
    
            mysqli_query($conn, "INSERT INTO tb_cms_gt_howitworks
            (hw_template_id,hw_step_no,hw_title,hw_description,hw_image,hw_status,hw_created_at)
            VALUES
            ($new_id,
            " . ($i + 1) . ",
            '" . mysqli_real_escape_string($conn,$hwTitle) . "',
            '" . mysqli_real_escape_string($conn,$_POST['hw_description'][$i]) . "',
            '$hwImageName',
            1,
            NOW())");
        }
    }

    $showPopup = true;
}
?>

<?php if ($showPopup): ?>
    <div class="success-overlay">
        <div class="success-modal">
            <h2>Saved !</h2>
            <p>Your template has been saved successfully.</p>
            <button onclick="window.location='gt-templates.php'">OK</button>
        </div>
    </div>
<?php endif; ?>

<main class="editor-wrapper">
    <div class="back-div">
        <a href="./gt-templates.php" class="back-btn"> Back</a>

    </div>
    <form method="POST" enctype="multipart/form-data">

        <div class="editor-grid">

            <!-- LEFT -->
            <div class="editor-left">

                <div class="hero-card">
                    <input type="file" name="gt_image" id="heroInput" hidden>
                    <div class="hero-preview" onclick="heroInput.click()">
                        <img id="heroPreview"
                            src="<?= !empty($template['gt_image'])
                                        ? 'gt-templates-uploads/' . $template['gt_image']
                                        : '../../assets/images/default-banner.png' ?>">
                        <div class="hero-overlay">Click to Upload Hero Image</div>
                    </div>
                </div>

                <div class="form-card">
                    <label>Template Title</label>
                    <input type="text" name="gt_title" value="<?= $template['gt_title'] ?? '' ?>" required>

                    <label>Tagline</label>
                    <input type="text" name="gt_tagline" value="<?= $template['gt_tagline'] ?? '' ?>">

                    <label>Short Description</label>
                    <textarea name="gt_short_desc"><?= $template['gt_short_desc'] ?? '' ?></textarea>

                    <label>Main Description</label>
                    <textarea name="gt_full_desc"><?= $template['gt_full_desc'] ?? '' ?></textarea>
                </div>

                <!-- Learning Outcomes -->
                <div class="section-card">
                    <h3>Learning Outcomes</h3>
                    <div id="outcomes">
                        <?php
                        if (!empty($learning_outcomes)) {
                            foreach ($learning_outcomes as $o) {
                                echo '<div class="outcome-item">
<input type="text" name="learning_outcomes[]" value="' . htmlspecialchars($o) . '">
<button type="button" onclick="removeOutcome(this)">✕</button>
</div>';
                            }
                        } else {
                            echo '<div class="outcome-item">
<input type="text" name="learning_outcomes[]">
<button type="button" onclick="removeOutcome(this)">✕</button>
</div>';
                        }
                        ?>
                    </div>
                    <button type="button" class="mini-btn" onclick="addOutcome()">Add</button>
                </div>

                <!-- HOW IT WORKS -->
                <div class="section-card">
                    <h3>How It Works</h3>
                    <div id="hiwContainer">

                        <?php
                        if (!empty($hiw_steps)) {
                            foreach ($hiw_steps as $index => $step) {
                                echo ' <div class="hiw-box">
                                <div class="hiw-step">
<div class="step-number">' . ($index + 1) . '</div>
<label>Step Image</label>
<input type="file" name="hw_image[]">
' . (!empty($step['hw_image']) ?
                                    '<img class="hiw-preview" src="./gt-templates-hiw-uploads/' . $step['hw_image'] . '">' : '') . '
<label>Step Title</label>
<input type="text" name="hw_title[]" value="' . htmlspecialchars($step['hw_title']) . '">
<label>Description</label>
<textarea name="hw_description[]">' . htmlspecialchars($step['hw_description']) . '</textarea>
<button type="button" class="remove-step" onclick="removeStep(this)">Remove</button>
</div>
</div>';
                            }
                        }
                        ?>
                    </div>
                    <button type="button" class="mini-btn" onclick="addStep()">Add Step</button>
                </div>

            </div>

            <!-- RIGHT -->
            <div class="editor-right">

                <div class="status-card">
                    <label>Status</label>
                    <select name="gt_status">
                        <option value="1" <?= ($template['gt_status'] ?? 1) == 1 ? 'selected' : '' ?>>Active</option>
                        <option value="0" <?= ($template['gt_status'] ?? 1) == 0 ? 'selected' : '' ?>>Inactive</option>
                    </select>
                </div>

                <div class="sidebar-card">

                    <!-- CATEGORY -->
                    <label>Category</label>
                    <select id="categorySelect">
                        <option value="">Select Category</option>
                        <?php
                        mysqli_data_seek($categories, 0);
                        while ($r = mysqli_fetch_assoc($categories)) {
                            echo "<option value='{$r['ct_id']}'>{$r['ct_name']}</option>";
                        }
                        ?>
                    </select>
                    <div id="categoryTags" class="tag-container">
                        <?php
                        if ($isEdit) {
                            $res = mysqli_query($conn, "SELECT gtc_category_id, ct_name 
            FROM tb_cms_gt_category_map 
            JOIN tb_cms_category ON ct_id = gtc_category_id
            WHERE gtc_template_id=$gt_id");

                            while ($row = mysqli_fetch_assoc($res)) {
                                echo '<div class="tag-item" data-value="' . $row['gtc_category_id'] . '">
                    ' . $row['ct_name'] . '
                    <span onclick="this.parentElement.remove()">✕</span>
                    <input type="hidden" name="ct_id[]" value="' . $row['gtc_category_id'] . '">
                  </div>';
                            }
                        }
                        ?>
                    </div>

                    <!-- GAME TYPE -->
                    <label>Game Type</label>
                    <select id="gametypeSelect">
                        <option value="">Select Game Type</option>
                        <?php
                        mysqli_data_seek($gametypes, 0);
                        while ($r = mysqli_fetch_assoc($gametypes)) {
                            echo "<option value='{$r['gm_id']}'>{$r['gm_name']}</option>";
                        }
                        ?>
                    </select>
                    <div id="gametypeTags" class="tag-container">
                        <?php
                        if ($isEdit) {
                            $res = mysqli_query($conn, "SELECT gtg_gametype_id, gm_name 
            FROM tb_cms_gt_gametype_map 
            JOIN tb_cms_gametype ON gm_id = gtg_gametype_id
            WHERE gtg_template_id=$gt_id");

                            while ($row = mysqli_fetch_assoc($res)) {
                                echo '<div class="tag-item" data-value="' . $row['gtg_gametype_id'] . '">
                    ' . $row['gm_name'] . '
                    <span onclick="this.parentElement.remove()">✕</span>
                    <input type="hidden" name="gm_id[]" value="' . $row['gtg_gametype_id'] . '">
                  </div>';
                            }
                        }
                        ?>
                    </div>

                    <!-- DURATION -->
                    <label>Duration</label>
                    <select id="durationSelect">
                        <option value="">Select Duration</option>
                        <?php
                        mysqli_data_seek($durations, 0);
                        while ($r = mysqli_fetch_assoc($durations)) {
                            echo "<option value='{$r['dr_id']}'>{$r['dr_name']}</option>";
                        }
                        ?>
                    </select>
                    <div id="durationTags" class="tag-container">
                        <?php
                        if ($isEdit) {
                            $res = mysqli_query($conn, "SELECT gtd_duration_id, dr_name 
            FROM tb_cms_gt_duration_map 
            JOIN tb_cms_duration ON dr_id = gtd_duration_id
            WHERE gtd_template_id=$gt_id");

                            while ($row = mysqli_fetch_assoc($res)) {
                                echo '<div class="tag-item" data-value="' . $row['gtd_duration_id'] . '">
                    ' . $row['dr_name'] . '
                    <span onclick="this.parentElement.remove()">✕</span>
                    <input type="hidden" name="dr_id[]" value="' . $row['gtd_duration_id'] . '">
                  </div>';
                            }
                        }
                        ?>
                    </div>

                    <!-- FORMAT -->
                    <label>Format</label>
                    <select id="formatSelect">
                        <option value="">Select Format</option>
                        <?php
                        mysqli_data_seek($formats, 0);
                        while ($r = mysqli_fetch_assoc($formats)) {
                            echo "<option value='{$r['fm_id']}'>{$r['fm_name']}</option>";
                        }
                        ?>
                    </select>
                    <div id="formatTags" class="tag-container">
                        <?php
                        if ($isEdit) {
                            $res = mysqli_query($conn, "SELECT gtf_format_id, fm_name 
            FROM tb_cms_gt_format_map 
            JOIN tb_cms_format ON fm_id = gtf_format_id
            WHERE gtf_template_id=$gt_id");

                            while ($row = mysqli_fetch_assoc($res)) {
                                echo '<div class="tag-item" data-value="' . $row['gtf_format_id'] . '">
                    ' . $row['fm_name'] . '
                    <span onclick="this.parentElement.remove()">✕</span>
                    <input type="hidden" name="fm_id[]" value="' . $row['gtf_format_id'] . '">
                  </div>';
                            }
                        }
                        ?>
                    </div>

                    <!-- COMPLEXITY -->
                    <label>Complexity</label>
                    <select id="complexitySelect">
                        <option value="">Select Complexity</option>
                        <?php
                        mysqli_data_seek($complexity, 0);
                        while ($r = mysqli_fetch_assoc($complexity)) {
                            echo "<option value='{$r['cx_id']}'>{$r['cx_name']}</option>";
                        }
                        ?>
                    </select>
                    <div id="complexityTags" class="tag-container">
                        <?php
                        if ($isEdit) {
                            $res = mysqli_query($conn, "SELECT gtx_complexity_id, cx_name 
            FROM tb_cms_gt_complexity_map 
            JOIN tb_cms_complexity ON cx_id = gtx_complexity_id
            WHERE gtx_template_id=$gt_id");

                            while ($row = mysqli_fetch_assoc($res)) {
                                echo '<div class="tag-item" data-value="' . $row['gtx_complexity_id'] . '">
                    ' . $row['cx_name'] . '
                    <span onclick="this.parentElement.remove()">✕</span>
                    <input type="hidden" name="cx_id[]" value="' . $row['gtx_complexity_id'] . '">
                  </div>';
                            }
                        }
                        ?>
                    </div>

                </div>

                <button type="submit" class="save-btn">
                    <?= $isEdit ? "Update Template" : "Create Template" ?>
                </button>

            </div>

        </div>
    </form>
</main>

<script>
    heroInput.onchange = e => {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = ev => heroPreview.src = ev.target.result;
            reader.readAsDataURL(file);
        }
    };

    function addOutcome() {
        document.getElementById("outcomes").insertAdjacentHTML("beforeend",
            '<div class="outcome-item"><input type="text" name="learning_outcomes[]"><button type="button" onclick="removeOutcome(this)">✕</button></div>');
    }

    function removeOutcome(btn) {
        btn.parentElement.remove();
    }

    function addStep() {
        const container = document.getElementById("hiwContainer");
        const count = container.querySelectorAll(".hiw-step").length + 1;
        container.insertAdjacentHTML("beforeend",
            ` <div class="hiw-box">
                    <div class="hiw-step">
                    <div class="step-number">${count}</div>
                    <label>Step Image</label>
                    <input type="file" name="hw_image[]">
                    <label>Step Title</label>
                    <input type="text" name="hw_title[]">
                    <label>Description</label>
                    <textarea name="hw_description[]"></textarea>
                    <button type="button" onclick="removeStep(this)">Remove</button>
                    </div>
            
            </div>`);
    }

    function removeStep(btn) {
        btn.parentElement.remove();
        document.querySelectorAll(".step-number").forEach((el, i) => el.innerText = i + 1);
    }

    document.addEventListener("change", function(e) {
        if (e.target.name === "hw_image[]") {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = ev => {
                    let img = document.createElement("img");
                    img.classList.add("hiw-preview");
                    img.src = ev.target.result;
                    e.target.after(img);
                };
                reader.readAsDataURL(file);
            }
        }
    });


    /*  MULTI SELECT TAG SYSTEM */

    function setupMultiSelect(selectId, tagContainerId, inputName) {

        const select = document.getElementById(selectId);
        const container = document.getElementById(tagContainerId);

        select.addEventListener("change", function() {

            const value = this.value;
            const text = this.options[this.selectedIndex].text;

            if (!value) return;

            // Prevent duplicate
            if (container.querySelector(`[data-value="${value}"]`)) {
                select.value = "";
                return;
            }

            const tag = document.createElement("div");
            tag.className = "tag-item";
            tag.setAttribute("data-value", value);

            tag.innerHTML = `
        ${text}
        <span onclick="this.parentElement.remove()">✕</span>
        <input type="hidden" name="${inputName}[]" value="${value}">
    `;

            container.appendChild(tag);

            select.value = "";
        });
    }

    /* Initialize for all masters */

    setupMultiSelect("categorySelect", "categoryTags", "ct_id");
    setupMultiSelect("gametypeSelect", "gametypeTags", "gm_id");
    setupMultiSelect("durationSelect", "durationTags", "dr_id");
    setupMultiSelect("formatSelect", "formatTags", "fm_id");
    setupMultiSelect("complexitySelect", "complexityTags", "cx_id");

</script>

<?php require "../../layout/footer.php"; ?>
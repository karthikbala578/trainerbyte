<?php
require_once "../../../include/session_check.php";
require "../../../include/dataconnect.php";

$pageTitle = "Banner Content Editor | trainerBYTE CMS";
$pageCSS   = "/portalcms/pages/page-styles/banner.css";
require "../../layout/header.php";
require "../../layout/navbar.php";

$banners = mysqli_query($conn, "SELECT * FROM tb_cms_banner ORDER BY bn_id ASC");

$edit_id  = isset($_GET['id']) ? intval($_GET['id']) : 0;
$new_mode = isset($_GET['new']) && intval($_GET['new']) === 1;

$edit_banner = null;

if ($edit_id > 0 && !$new_mode) {
    $result = mysqli_query($conn, "SELECT * FROM tb_cms_banner WHERE bn_id = $edit_id");
    $edit_banner = mysqli_fetch_assoc($result);
}
?>

<main class="banner-page">
<div class="back-div">
        <a href="../../index.php" class="back-btn"> Back</a>

 </div>

    <div class="banner-tabs">
        <?php
        $count = 1;
        mysqli_data_seek($banners, 0);
        while ($row = mysqli_fetch_assoc($banners)) { ?>
            <a href="banner.php?id=<?= $row['bn_id'] ?>"
                class="<?= (!$new_mode && $edit_id == $row['bn_id']) ? 'active' : '' ?>">
                Banner <?= $count ?>
            </a>
        <?php $count++;
        } ?>

        <a href="banner.php?new=1"
            class="add-new <?= ($new_mode) ? 'active' : '' ?>">
            + Add New Banner
        </a>
    </div>

    <div class="banner-header-bar">
        <div class="status-wrapper">
            <span class="status-title">Banner Status</span>

            <div class="status-toggle">
                <label class="switch">
                    <input type="checkbox" id="status_input"
                        <?= (!isset($edit_banner['bn_status']) || $edit_banner['bn_status'] == 1) ? 'checked' : '' ?>>
                    <span class="slider"></span>
                </label>
                <span class="status-label">Active</span>
            </div>
        </div>
    </div>

    <div class="banner-container">

        <div class="editor">

            <input type="hidden" id="bn_id" value="<?= $edit_banner['bn_id'] ?? 0 ?>">

            <label>Pill Text</label>
            <input type="text" id="pill_input"
                value="<?= $edit_banner['bn_pill_text'] ?? '' ?>">

            <label>Main Title</label>
            <textarea id="title_input"><?= $edit_banner['bn_title'] ?? '' ?></textarea>

            <label>Short Description</label>
            <textarea id="desc_input"><?= $edit_banner['bn_short_desc'] ?? '' ?></textarea>

            <label>Image</label>
            <input type="file" id="image_input">

            <button class="btn-primary" onclick="saveBanner()">Publish Changes</button>

        </div>

        <div class="preview">

            <div class="preview-content">
                <div class="pill" id="preview_pill">
                    <?= $edit_banner['bn_pill_text'] ?? 'Preview Pill Text' ?>
                </div>

                <h1 id="preview_title">
                    <?= $edit_banner['bn_title'] ?? 'Preview Title' ?>
                </h1>

                <p id="preview_desc">
                    <?= $edit_banner['bn_short_desc'] ?? 'Preview Description' ?>
                </p>
            </div>
            <div class="preview-image">
                <?php
                $default_img = "default-banner.png";

                if (!empty($edit_banner['bn_image'])) {
                    $image_to_show = $edit_banner['bn_image'];
                } else {
                    $image_to_show = $default_img;
                }
                ?>

                <img id="preview_img"
                    src="./banner-uploads/<?= $image_to_show ?>"
                    alt="Preview Img">
            </div>



        </div>

    </div>

</main>

<script>
    document.getElementById('pill_input').addEventListener('input', function() {
        document.getElementById('preview_pill').innerText = this.value;
    });

    document.getElementById('title_input').addEventListener('input', function() {
        document.getElementById('preview_title').innerText = this.value;
    });

    document.getElementById('desc_input').addEventListener('input', function() {
        document.getElementById('preview_desc').innerText = this.value;
    });

    document.getElementById('image_input').addEventListener('change', function(e) {
        const reader = new FileReader();
        reader.onload = function() {
            document.getElementById('preview_img').src = reader.result;
        }
        reader.readAsDataURL(e.target.files[0]);
    });

    const statusInput = document.getElementById("status_input");
    const statusLabel = document.querySelector(".status-label");

    function updateStatusText() {
        statusLabel.textContent = statusInput.checked ? "Active" : "Inactive";
    }

    updateStatusText();
    statusInput.addEventListener("change", updateStatusText);

    function saveBanner() {

        let formData = new FormData();

        formData.append("bn_id", document.getElementById("bn_id").value);
        formData.append("bn_pill_text", document.getElementById("pill_input").value);
        formData.append("bn_title", document.getElementById("title_input").value);
        formData.append("bn_short_desc", document.getElementById("desc_input").value);

        let statusValue = statusInput.checked ? 1 : 0;
        formData.append("bn_status", statusValue);

        let imageFile = document.getElementById("image_input").files[0];
        if (imageFile) {
            formData.append("bn_image", imageFile);
        }

        fetch("./banner_save.php", {
                method: "POST",
                body: formData
            })
            .then(res => res.text())
            .then(data => {
                let response = data.trim();

                if (response !== "error" && response !== "") {
                    alert("Banner Saved Successfully");

                    window.location.href = "banner.php?id=" + response;
                } else {
                    alert("Error saving banner");
                }
            });

    }
</script>

<?php require "../../layout/footer.php"; ?>
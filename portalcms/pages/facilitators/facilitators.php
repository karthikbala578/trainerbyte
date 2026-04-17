<?php

require_once "../../../include/session_check.php";
require "../../../include/dataconnect.php";

$pageTitle = "Facilitator Management | trainerBYTE CMS";
$pageCSS   = "/portalcms/pages/page-styles/facilitators.css";

require "../../layout/header.php";
require "../../layout/navbar.php";

/*  HANDLE CREATE / UPDATE */
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $id     = intval($_POST['fc_id']);
    $name   = mysqli_real_escape_string($conn, $_POST['fc_full_name']);
    $desg   = mysqli_real_escape_string($conn, $_POST['fc_designation']);
    $org    = mysqli_real_escape_string($conn, $_POST['fc_organization']);
    $email  = mysqli_real_escape_string($conn, $_POST['fc_email']);
    $status = isset($_POST['fc_status']) ? intval($_POST['fc_status']) : 1;

    $logoName = "";
    $logoSql  = "";

    /* ===== IMAGE UPLOAD ===== */
    if (isset($_FILES['fc_logo']) && $_FILES['fc_logo']['error'] === 0) {

        $uploadDir = __DIR__ . "/facilitators-uploads/";

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $fileName = time() . "_" . basename($_FILES['fc_logo']['name']);
        $targetPath = $uploadDir . $fileName;

        if (move_uploaded_file($_FILES['fc_logo']['tmp_name'], $targetPath)) {
            $logoName = $fileName;
            $logoSql = ", fc_logo='$fileName'";
        } else {
            die("Upload failed. Check folder permissions.");
        }
    }


    /* ===== CREATE ===== */
    if ($id == 0) {

        $password = base64_encode($_POST['fc_password']);

        $sql = "INSERT INTO tb_cms_facilitator
                (fc_full_name, fc_designation, fc_organization, fc_email, fc_password, fc_logo, fc_status)
                VALUES
                ('$name','$desg','$org','$email','$password','$logoName',1)";
    }
    /* ===== UPDATE ===== */ else {

        $passSql = "";

        if (!empty($_POST['fc_password'])) {
            $password = base64_encode($_POST['fc_password']);
            $passSql = ", fc_password='$password'";
        }

        $sql = "UPDATE tb_cms_facilitator SET
                fc_full_name='$name',
                fc_designation='$desg',
                fc_organization='$org',
                fc_email='$email',
                fc_status='$status'
                $logoSql
                $passSql
                WHERE fc_id=$id";
    }

    if (mysqli_query($conn, $sql)) {

        $_SESSION['notify'] = ($id == 0)
            ? "Facilitator Created Successfully!"
            : "Facilitator Updated Successfully!";

        $_SESSION['notify_type'] = ($id == 0) ? "success" : "info";
    } else {

        $_SESSION['notify'] = "Database Error: " . mysqli_error($conn);
        $_SESSION['notify_type'] = "error";
    }

    header("Location: facilitators.php");
    exit();
}

/* ===== FETCH DATA ===== */
$result = mysqli_query($conn, "SELECT * FROM tb_cms_facilitator ORDER BY fc_id DESC");
?>

<main class="facilitator-page">
<div class="back-div">
        <a href="../../index.php" class="back-btn"> Back</a>

 </div>

    <?php if (isset($_SESSION['notify'])): ?>
        <div class="notify-box <?php echo $_SESSION['notify_type']; ?>">
            <?php
            echo $_SESSION['notify'];
            unset($_SESSION['notify']);
            unset($_SESSION['notify_type']);
            ?>
        </div>
    <?php endif; ?>

    <!-- ================= GRID ================= -->
    <div id="view_grid">

        <div class="page-header">
            <h1>Facilitators & Trainers</h1>
            <p>Manage existing profiles or register new facilitators.</p>
        </div>

        <div class="facilitator-grid">

            <!-- CREATE CARD FIRST -->
            <div class="fc-card add-new-card" onclick="openCreator()">
                <div class="add-icon-box">
                    <svg viewBox="0 0 24 24" class="add-icon">
                        <path d="M12 5v14M5 12h14" stroke-width="2" stroke-linecap="round" />
                    </svg>
                </div>
                <h3>Add Facilitator</h3>
                <p>Register New Profile</p>
            </div>

            <!-- EXISTING -->
            <?php while ($row = mysqli_fetch_assoc($result)):

                $img = !empty($row['fc_logo'])
                    ? "./facilitators-uploads/" . $row['fc_logo']
                    : "../../assets/images/placeholder.png";

                $json = htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8');
            ?>

                <div class="fc-card" onclick='openEditor(<?php echo $json; ?>)'>
                    <div class="fc-status-dot <?php echo ($row['fc_status'] == 1) ? 'status-active' : 'status-inactive'; ?>"></div>

                    <div class="fc-img-wrapper">
                        <img src="<?php echo $img; ?>">
                    </div>

                    <h3><?php echo $row['fc_full_name']; ?></h3>
                    <span class="fc-designation"><?php echo $row['fc_designation']; ?></span>
                    <span class="fc-org"><?php echo $row['fc_organization']; ?></span>

                    <button class="btn-edit" type="button">
                        <span class="material-symbols-outlined"></span> Edit Profile
                    </button>
                </div>

            <?php endwhile; ?>

        </div>
    </div>

    <!-- ================= FORM ================= -->
    <div id="view_form" class="form-section" style="display:none;">

        <div class="form-container">

            <form method="POST" enctype="multipart/form-data">

                <input type="hidden" id="inp_id" name="fc_id" value="0">

                <div class="form-header">
                    <h2 id="form_title">Register New Facilitator</h2>
                    <button type="button" class="btn-close-form" onclick="closeForm()">×</button>
                </div>

                <div class="form-body">

                    <div class="form-group">
                        <label>Full Name</label>
                        <input type="text" id="inp_name" name="fc_full_name" required>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Designation</label>
                            <input type="text" id="inp_desg" name="fc_designation">
                        </div>
                        <div class="form-group">
                            <label>Organization</label>
                            <input type="text" id="inp_org" name="fc_organization">
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" id="inp_email" name="fc_email" required>
                    </div>

                    <!-- LOGO FIELD -->
                    <div class="form-group">
                        <label>Profile Logo</label>

                        <div class="upload-box" onclick="document.getElementById('inp_file').click()">
                            <span class="material-symbols-outlined">cloud_upload</span>
                            <p>Click to upload image</p>
                        </div>

                        <input type="file"
                            id="inp_file"
                            name="fc_logo"
                            hidden
                            accept="image/*"
                            onchange="previewImage(this)">

                        <div class="upload-preview" id="preview_box" style="display:none;">
                            <img id="img_preview" src="">
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Password</label>
                        <input type="password" id="inp_pass" name="fc_password">
                    </div>

                    <div class="form-group">
                        <label>Status</label>
                        <select id="inp_status" name="fc_status">
                            <option value="1">Active</option>
                            <option value="0">Inactive</option>
                        </select>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn-submit">
                            Save Details
                        </button>
                    </div>

                </div>
            </form>

        </div>
    </div>

</main>

<script>
    function openCreator() {
        document.getElementById('view_grid').style.display = 'none';
        document.getElementById('view_form').style.display = 'flex';

        document.getElementById('form_title').innerText = "Register New Facilitator";

        document.getElementById('inp_id').value = "0";
        document.getElementById('inp_name').value = "";
        document.getElementById('inp_desg').value = "";
        document.getElementById('inp_org').value = "";
        document.getElementById('inp_email').value = "";
        document.getElementById('inp_pass').value = "";
        document.getElementById('inp_pass').required = true;
        document.getElementById('preview_box').style.display = "none";
    }

    function openEditor(data) {

        document.getElementById('view_grid').style.display = 'none';
        document.getElementById('view_form').style.display = 'flex';

        document.getElementById('form_title').innerText = "Edit: " + data.fc_full_name;

        document.getElementById('inp_id').value = data.fc_id;
        document.getElementById('inp_name').value = data.fc_full_name;
        document.getElementById('inp_desg').value = data.fc_designation;
        document.getElementById('inp_org').value = data.fc_organization;
        document.getElementById('inp_email').value = data.fc_email;
        document.getElementById('inp_status').value = data.fc_status;

        document.getElementById('inp_pass').required = false;

        if (data.fc_logo) {
            document.getElementById('preview_box').style.display = 'block';
            document.getElementById('img_preview').src =
                "./facilitators-uploads/" + data.fc_logo;
        } else {
            document.getElementById('preview_box').style.display = 'none';
        }
    }

    function closeForm() {
        document.getElementById('view_form').style.display = 'none';
        document.getElementById('view_grid').style.display = 'block';
    }

    function previewImage(input) {
        if (input.files && input.files[0]) {
            var reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('img_preview').src = e.target.result;
                document.getElementById('preview_box').style.display = 'block';
            }
            reader.readAsDataURL(input.files[0]);
        }
    }

    setTimeout(() => {
        const notify = document.querySelector('.notify-box');
        if (notify) {
            notify.style.opacity = "0";
            notify.style.transition = "0.5s";
            setTimeout(() => notify.remove(), 500);
        }
    }, 3000);
</script>

<?php require "../../layout/footer.php"; ?>
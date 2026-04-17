<?php
require_once "include/session_check.php";
require "include/dataconnect.php";

$team_id = $_SESSION['team_id'] ?? null;
if (!$team_id) {
    header("Location: login.php");
    exit;
}

/* image upload */
if (isset($_FILES['team_image']) && $_FILES['team_image']['error'] === 0) {

    $ext = strtolower(pathinfo($_FILES['team_image']['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg','jpeg','png','webp'];

    if (in_array($ext, $allowed)) {

        $newName = time() . "_" . $team_id . "." . $ext;
        $uploadDir = $_SERVER['DOCUMENT_ROOT'] . "/trainergenie/upload-images/profile-pics/";
        $path = "/trainergenie/upload-images/profile-pics/" . $newName;

        move_uploaded_file($_FILES['team_image']['tmp_name'], $uploadDir . $newName);

        $stmt = $conn->prepare(
            "UPDATE team SET team_image=? WHERE team_id=?"
        );
        $stmt->bind_param("si", $path, $team_id);
        $stmt->execute();

        $_SESSION['team_image'] = $path;

        header("Location: profile.php?edit=1");
        exit;
    }
}

/* update name */
if (isset($_POST['edit_profile_submit'])) {

    $name = trim($_POST['team_name']);

    $stmt = $conn->prepare(
        "UPDATE team SET team_name=? WHERE team_id=?"
    );
    $stmt->bind_param("si", $name, $team_id);
    $stmt->execute();

    header("Location: profile.php?updated=name");
    exit;
}

/* update password */
if (isset($_POST['change_password_submit'])) {

    if ($_POST['new_password'] === $_POST['confirm_password']) {

        $pwd = base64_encode($_POST['new_password']);

        $stmt = $conn->prepare(
            "UPDATE team SET team_password=? WHERE team_id=?"
        );
        $stmt->bind_param("si", $pwd, $team_id);
        $stmt->execute();

        header("Location: profile.php?updated=password");
        exit;
    }
}

/* fetch user */
$stmt = $conn->prepare(
    "SELECT team_name, team_login, team_image FROM team WHERE team_id=?"
);
$stmt->bind_param("i", $team_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

/* page meta */
$pageTitle = "User Profile";
$pageCSS   = "/assets/styles/profile.css";

require "layout/header.php";
?>

<div class="profile-card">
    <img src="<?= $user['team_image'] ?: '/trainergenie/assets/images/user.png' ?>" class="avatar">

    <h3><?= htmlspecialchars($user['team_name']) ?></h3>
    <p><?= htmlspecialchars($user['team_login']) ?></p>

    <a href="?edit=1"><button>Edit Profile</button></a>
    <a href="?password=1"><button class="secondary">Change Password</button></a>

    <?php if (($_GET['updated'] ?? '') === 'name'): ?>
        <p class="success">✔ Saved successfully</p>
    <?php elseif (($_GET['updated'] ?? '') === 'password'): ?>
        <p class="success">✔ Password updated</p>
    <?php endif; ?>
</div>

<?php if (isset($_GET['edit'])): ?>
<div class="popup">
    <form method="post" enctype="multipart/form-data" class="box">
        <a href="profile.php" class="close">✕</a>

        <div class="edit-avatar">
            <img src="<?= $user['team_image'] ?: '/trainergenie/assets/images/user.png' ?>">
        </div>

        <label class="upload-btn">
            Update Profile Picture
            <input type="file" name="team_image" hidden onchange="this.form.submit()">
        </label>

        <label>Name</label>
        <input type="text" name="team_name" value="<?= htmlspecialchars($user['team_name']) ?>" required>

        <button type="submit" name="edit_profile_submit">Save</button>
    </form>
</div>
<?php endif; ?>

<?php if (isset($_GET['password'])): ?>
<div class="popup">
    <form method="post" class="box">
        <a href="profile.php" class="close">✕</a>

        <div class="password-field">
            <input type="password" id="new_password" name="new_password" placeholder="New password" required>
            <img src="/trainergenie/assets/images/eye-open.svg" class="eye-img"
                 onclick="togglePasswordImg('new_password', this)">
        </div>

        <div class="password-field">
            <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm password" required>
            <img src="/trainergenie/assets/images/eye-open.svg" class="eye-img"
                 onclick="togglePasswordImg('confirm_password', this)">
        </div>

        <button type="submit" name="change_password_submit">Update</button>
    </form>
</div>
<?php endif; ?>

<script>
function togglePasswordImg(id, img) {
    const input = document.getElementById(id);
    if (input.type === "password") {
        input.type = "text";
        img.src = "/trainergenie/assets/images/eye-closed.svg";
    } else {
        input.type = "password";
        img.src = "/trainergenie/assets/images/eye-open.svg";
    }
}
</script>

<?php require "layout/footer.php"; ?>

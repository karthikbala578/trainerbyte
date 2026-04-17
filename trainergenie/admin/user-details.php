<?php
$pageTitle = "User Details";
$pageCSS   = "/admin/styles/user-details.css";

session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

require "layout/header.php";
require "../include/dataconnect.php";

$teamId = (int) ($_GET['team_id'] ?? 0);
if (!$teamId) {
    header("Location: users.php");
    exit();
}

/* ACTIONS */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if ($_POST['action'] === 'toggle_status') {
        $newStatus = (int) $_POST['new_status'];
        $stmt = $conn->prepare("UPDATE tb_team SET team_status = ? WHERE team_id = ?");
        $stmt->bind_param("ii", $newStatus, $teamId);
        $stmt->execute();
        $_SESSION['flash'] = $newStatus ? "User enabled successfully." : "User disabled successfully.";
    }

  

    header("Location: user-details.php?team_id=$teamId");
    exit();
}

/* USER */
$stmt = $conn->prepare("SELECT * FROM tb_team WHERE team_id = ?");
$stmt->bind_param("i", $teamId);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
if (!$user) {
    header("Location: users.php");
    exit();
}

/* TOTAL GAMES COUNT */
$stmt = $conn->prepare("
    SELECT COUNT(cg.cg_id) AS total_games
    FROM card_group cg
    JOIN byteguess_category bc
        ON cg.byteguess_pkid = bc.lg_id
    WHERE bc.lg_team_pkid = ?
");
$stmt->bind_param("i", $teamId);
$stmt->execute();
$stats = $stmt->get_result()->fetch_assoc();
$totalGames = $stats['total_games'] ?? 0;
?>

<div class="user-details-wrapper">

    <?php if (!empty($_SESSION['flash'])): ?>
        <div class="flash-message">
            <?= htmlspecialchars($_SESSION['flash']) ?>
        </div>
        <?php unset($_SESSION['flash']); ?>
    <?php endif; ?>

    <div class="profile-card">

        <div class="profile-header">
            <div class="avatar">
                <?php if (!empty($user['team_image'])): ?>
                    <img src="<?= htmlspecialchars($user['team_image']) ?>">
                <?php else: ?>
                    <span><?= strtoupper(substr($user['team_name'], 0, 1)) ?></span>
                <?php endif; ?>
            </div>

            <div class="profile-info">
                <h2><?= htmlspecialchars($user['team_name']) ?></h2>
                <p><?= htmlspecialchars($user['team_login']) ?></p>
                <span class="status <?= $user['team_status'] ? 'active' : 'inactive' ?>">
                    <?= $user['team_status'] ? 'Active' : 'Inactive' ?>
                </span>
            </div>
        </div>

        <div class="details-grid">
            <div class="detail-item">
                <span>Organization</span>
                <strong><?= htmlspecialchars($user['team_org'] ?? 'Individual') ?></strong>
            </div>

            <div class="detail-item">
                <span>Joined On</span>
                <strong><?= date("M d, Y", strtotime($user['team_datetime'])) ?></strong>
            </div>

            <div class="detail-item">
                <span>User ID</span>
                <strong>#<?= $user['team_id'] ?></strong>
            </div>

            <div class="detail-item stat-highlight">
                <span>Total Games Created</span>
                <strong><?= $totalGames ?></strong>
            </div>
        </div>

        <div class="profile-actions">
            <form method="post">
                <input type="hidden" name="action" value="toggle_status">
                <input type="hidden" name="new_status" value="<?= $user['team_status'] ? 0 : 1 ?>">
                <button class="btn-warning">
                    <?= $user['team_status'] ? 'Disable User' : 'Enable User' ?>
                </button>
            </form>

            
        </div>

        <a href="users.php" class="back-btn">
            Back
        </a>


    </div>

</div>

<?php require "layout/footer.php"; ?>
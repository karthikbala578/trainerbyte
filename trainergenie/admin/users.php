<?php
$pageTitle = "Users";
$pageCSS   = "/admin/styles/users.css";

session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

require "layout/header.php";
require "../include/dataconnect.php";

$users = [];
$stmt = $conn->query("
    SELECT 
        team_id,
        team_name,
        team_login,
        team_org,
        team_datetime,
        team_status,
        team_image
    FROM tb_team
    ORDER BY team_datetime DESC
");

while ($row = $stmt->fetch_assoc()) {
    $users[] = $row;
}
?>

<div class="users-wrapper">

    <!-- HERO SECTION -->
    <section class="users-hero">
        <div class="hero-content">
            <span class="hero-pill">TrainerGenie Admin</span>
            <h1>Users</h1>
            <p>
                Manage and monitor all teams using TrainerGenie.
                View user details, organization, and account status at a glance.
            </p>
        </div>

        <div class="back-box">
            <a href="index.php" class="back-btn">
                    Back
                </a><br>
        </div>

    </section>

    <!-- USERS GRID -->
    <?php if (count($users) === 0): ?>

        <!-- EMPTY STATE -->
        <div class="empty-state">
            <h3>No users found</h3>
            <p>Once teams register, they’ll appear here.</p>
        </div>

    <?php else: ?>

        <div class="users-grid">
            <?php foreach ($users as $user): ?>

                <div class="user-card">

                    <!-- USER HEADER -->
                    <div class="user-header">

                        <div class="user-avatar">
                            <?php if (!empty($user['team_image'])): ?>
                                <img src="<?= htmlspecialchars($user['team_image']) ?>" alt="User">
                            <?php else: ?>
                                <span>
                                    <?= strtoupper(substr($user['team_name'], 0, 1)) ?>
                                </span>
                            <?php endif; ?>
                        </div>

                        <div class="user-info">
                            <h3><?= htmlspecialchars($user['team_name']) ?></h3>
                            <p><?= htmlspecialchars($user['team_login']) ?></p>
                        </div>

                    </div>

                    <!-- USER META -->
                    <div class="user-meta">

                        <span class="org-badge">
                            <?= htmlspecialchars($user['team_org'] ?? 'Individual') ?>
                        </span>

                        <div class="meta-row">
                            <span>Joined</span>
                            <strong>
                                <?= date("M d, Y", strtotime($user['team_datetime'])) ?>
                            </strong>
                        </div>

                        <div class="meta-row">
                            <span>Status</span>
                            <?php if ($user['team_status'] == 1): ?>
                                <span class="status active">Active</span>
                            <?php else: ?>
                                <span class="status inactive">Inactive</span>
                            <?php endif; ?>
                        </div>

                    </div>

                    <!-- ACTIONS -->
                    <div class="user-actions">
                        <a href="user-details.php?team_id=<?= $user['team_id'] ?>"
                            class="btn-link">
                            View Details
                        </a>

                    </div>  

                </div>

            <?php endforeach; ?>
        </div>

    <?php endif; ?>

</div>

<?php require "layout/footer.php"; ?>
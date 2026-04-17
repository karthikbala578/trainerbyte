<?php
$pageTitle = "TrainerGenie Admin Dashboard";
$pageCSS   = "/admin/styles/index.css";

session_start();

// Redirect if not logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

require "layout/header.php";
require "../include/dataconnect.php";

$adminName = $_SESSION['admin_name'] ?? 'Admin';

/* Fetch dashboard counts */

// Total users (tb_team)
$teamCount = 0;
$res = $conn->query("SELECT COUNT(*) AS total FROM tb_team");
if ($row = $res->fetch_assoc()) {
    $teamCount = $row['total'];
}

// Total genie exercises 
$exerciseCount = 0;
$res = $conn->query("SELECT COUNT(*) AS total FROM tb_exercise_type");
if ($row = $res->fetch_assoc()) {
    $exerciseCount = $row['total'];
}
?>

<div class="dash-wrapper">

    <div class="dash-hero">

        <div class="hero-left">
            <span class="hero-badge">Admin Dashboard</span>

            <h1 class="hero-title">
                Welcome back
                <span class="wave">👋</span>
            </h1>

            <h2 class="hero-name">
                <?= htmlspecialchars($adminName) ?>
            </h2>

            <p class="hero-sub">
                You’re logged in with full administrative access
            </p>
        </div>

        <div class="hero-right">
            <div class="hero-icon">⚙️</div>
        </div>

    </div>

    <div class="welcome-banner">

        <div class="welcome-text">
            <h2>TrainerGenie Admin Overview</h2>
            <p>
                TrainerGenie allows you to manage registered users and
                maintain Genie exercises used across teams. Use the
                sections below to navigate and control platform data
                efficiently.
            </p>
        </div>

        <div class="welcome-illustration">
            <div class="illus-shape one"></div>
            <div class="illus-shape two"></div>
            <div class="illus-shape three"></div>
        </div>

    </div>

    <!-- ================= ACTION CARDS ================= -->
    <div class="action-grid">

        <!-- Users -->
        <a href="users.php" class="action-card">
            <h3>Users</h3>
            <div class="big-number"><?= $teamCount ?></div>
            <p>Registered TrainerGenie teams</p>
            <span class="action-link">View Users →</span>
        </a>

        <!-- Genie Exercises -->
        <a href="exercises.php" class="action-card">
            <h3>Genie Exercises</h3>
            <div class="big-number"><?= $exerciseCount ?></div>
            <p>Exercises available in the platform</p>
            <span class="action-link">Manage Exercises →</span>
        </a>

    </div>

    <!-- ================= PROMPTS OVERVIEW ================= -->
    <div class="prompts-overview">

        <div class="prompts-text">
            <h2>Game Prompts Management</h2>
            <p>
                The Prompts section allows you to view, organize, and update
                structured prompt flows used by interactive learning games.
                Each prompt sequence is maintained step-by-step to ensure
                consistent behavior across the platform.
            </p>
            <p>
                Navigate to the Prompts page to review existing prompts,
                apply updates, or maintain prompt structure as required.
            </p>

            <a href="prompts.php" class="prompts-btn">
                Go to Prompts →
            </a>
        </div>

        <div class="prompts-visual">
            <div class="visual-box"></div>
        </div>

    </div>


</div>

<?php require "layout/footer.php"; ?>
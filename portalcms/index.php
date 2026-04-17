<?php

require_once "include/session_check.php";


$pageTitle = "TrainerByte CMS | Dashboard";
$pageCSS   = "/portalcms/styles/index.css";

require "layout/header.php";
require "layout/navbar.php";
require "../include/dataconnect.php";

$bannerCount = mysqli_fetch_assoc(
    mysqli_query($conn, "SELECT COUNT(*) as t FROM tb_cms_banner")
)['t'];

$gametypeCount = mysqli_fetch_assoc(
    mysqli_query($conn, "SELECT COUNT(*) as t FROM tb_cms_gametype_template")
)['t'];

$promptCount = mysqli_fetch_assoc(
    mysqli_query($conn, "SELECT COUNT(*) as t FROM tb_cms_gt_prompt")
)['t'];

$highlightCount = mysqli_fetch_assoc(
    mysqli_query($conn, "SELECT COUNT(*) as t FROM tb_cms_highlight")
)['t'];

$howCount = mysqli_fetch_assoc(
    mysqli_query($conn, "SELECT COUNT(*) as t FROM tb_cms_howitworks")
)['t'];

$facilitatorCount = mysqli_fetch_assoc(
    mysqli_query($conn, "SELECT COUNT(*) as t FROM tb_cms_facilitator")
)['t'];

?>

<div class="dashboard-wrapper">

    <div class="dashboard-header">
        <p class="tab">CMS Home > <span class="tab-name"> Dashboard</span></p>
        <h1>Content Overview</h1>
        <p>Manage and monitor all trainerBYTE application content modules.</p>
    </div>

    <div class="card-grid">

        <div class="dashboard-card">
            <div class="card-icon">
                <img src="assets/icons/banner-icon.png" alt="Banner Icon">
            </div>
            <div class="card-title">Manage Banner</div>
            <div class="card-count"><?= $bannerCount ?></div>
            <div class="card-subtext">Promotional Banners</div>
            <a href="./pages/banner/banner.php" class="card-btn">
                Edit Banner Content
                <span class="edit-icon">
                    <svg viewBox="0 0 24 24" width="18" height="18">
                        <path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zm18-11.5a1 1 0 0 0 0-1.41l-1.34-1.34a1 1 0 0 0-1.41 0l-1.83 1.83 3.75 3.75L21 5.75z" />
                    </svg>
                </span>
            </a>

        </div>

        <div class="dashboard-card">
            <div class="card-icon">
                <img src="assets/icons/subscriber-icon.png" alt="Facilitator Icon">
            </div>
            <div class="card-title">Manage Facilitators</div>
            <div class="card-count"><?= $facilitatorCount ?></div>
            <div class="card-subtext">Total Users</div>
            <a href="./pages/facilitators/facilitators.php" class="card-btn">
                Manage Facilitators
                <span class="edit-icon">
                    <svg viewBox="0 0 24 24" width="18" height="18">
                        <path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zm18-11.5a1 1 0 0 0 0-1.41l-1.34-1.34a1 1 0 0 0-1.41 0l-1.83 1.83 3.75 3.75L21 5.75z" />
                    </svg>
                </span>
            </a>
        </div>

        <div class="dashboard-card">
            <div class="card-icon">
                <img src="assets/icons/gametype-icon.png" alt="Game Type Icon">
            </div>
            <div class="card-title">Manage Game-type</div>
            <div class="card-count"><?= $gametypeCount ?></div>
            <div class="card-subtext">Game Categories</div>
            <a href="./pages/gt-templates/gt-templates.php" class="card-btn">
                Configure Types
                <span class="edit-icon">
                    <svg viewBox="0 0 24 24" width="18" height="18">
                        <path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zm18-11.5a1 1 0 0 0 0-1.41l-1.34-1.34a1 1 0 0 0-1.41 0l-1.83 1.83 3.75 3.75L21 5.75z" />
                    </svg>
                </span>
            </a>
        </div>

        <div class="dashboard-card">
            <div class="card-icon">
                <img src="assets/icons/prompt-icon.png" alt="Prompt Icon">
            </div>
            <div class="card-title">Manage Prompts</div>
            <div class="card-count"><?= $promptCount ?></div>
            <div class="card-subtext">AI Prompt Library</div>
            <a href="./pages/prompts/prompts.php" class="card-btn">
                Update Library
                <span class="edit-icon">
                    <svg viewBox="0 0 24 24" width="18" height="18">
                        <path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zm18-11.5a1 1 0 0 0 0-1.41l-1.34-1.34a1 1 0 0 0-1.41 0l-1.83 1.83 3.75 3.75L21 5.75z" />
                    </svg>
                </span>
            </a>
        </div>

        <div class="dashboard-card">
            <div class="card-icon">
                <img src="assets/icons/highlight-icon.png" alt="Highlight Icon">
            </div>
            <div class="card-title">Portal Highlights</div>
            <div class="card-count"><?= $highlightCount ?></div>
            <div class="card-subtext">Featured Slots</div>
            <a href="./pages/highlights/highlights.php" class="card-btn">
                Edit Highlights
                <span class="edit-icon">
                    <svg viewBox="0 0 24 24" width="18" height="18">
                        <path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zm18-11.5a1 1 0 0 0 0-1.41l-1.34-1.34a1 1 0 0 0-1.41 0l-1.83 1.83 3.75 3.75L21 5.75z" />
                    </svg>
                </span>
            </a>
        </div>

        <div class="dashboard-card">
            <div class="card-icon">
                <img src="assets/icons/howitworks-icon.png" alt="How It Works Icon">
            </div>
            <div class="card-title">How it Works</div>
            <div class="card-count"><?= $howCount ?></div>
            <div class="card-subtext">Tutorial Steps</div>
            <a href="./pages/how-it-works/how-it-works.php" class="card-btn">
                Modify Steps
                <span class="edit-icon">
                    <svg viewBox="0 0 24 24" width="18" height="18">
                        <path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zm18-11.5a1 1 0 0 0 0-1.41l-1.34-1.34a1 1 0 0 0-1.41 0l-1.83 1.83 3.75 3.75L21 5.75z" />
                    </svg>
                </span>
            </a>
        </div>

        <div class="dashboard-card">
            <div class="card-icon">
                <img src="assets/icons/edit-icon.png" alt="Footer Links Icon">
            </div>

            <div class="card-title">Footer Links</div>

            <div class="card-count">
                <?php
                $footerCount = mysqli_fetch_assoc(
                    mysqli_query($conn, "SELECT COUNT(*) as t FROM tb_cms_footer_links")
                )['t'];
                echo $footerCount;
                ?>
            </div>

            <div class="card-subtext">Company & Terms Content</div>

            <a href="./pages/footer-links/footer-links.php" class="card-btn">
                Manage Footer Links
                <span class="edit-icon">
                    <svg viewBox="0 0 24 24" width="18" height="18">
                        <path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zm18-11.5a1 1 0 0 0 0-1.41l-1.34-1.34a1 1 0 0 0-1.41 0l-1.83 1.83 3.75 3.75L21 5.75z" />
                    </svg>
                </span>
            </a>
        </div>

    </div>

</div>

<?php require "layout/footer.php"; ?>
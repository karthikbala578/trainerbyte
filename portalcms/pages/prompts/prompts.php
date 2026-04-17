<?php
require_once "../../../include/session_check.php";
require "../../../include/dataconnect.php";

$pageTitle = "Prompt Management | trainerBYTE CMS";
$pageCSS   = "/portalcms/pages/page-styles/prompts.css";

require "../../layout/header.php";
require "../../layout/navbar.php";

/* Fetch All Game Type Templates */

$sql = "
    SELECT 
        gt.gt_id,
        gt.gt_title,
        gt.gt_status,
        gt.gt_created_at,
        COUNT(pr.pr_id) AS prompt_count
    FROM tb_cms_gametype_template gt
    LEFT JOIN tb_cms_gt_prompt pr 
        ON gt.gt_id = pr.pr_template_id
    GROUP BY gt.gt_id
    ORDER BY gt.gt_id DESC
";

$result = mysqli_query($conn, $sql);
?>

<main class="prompts-wrapper">

    <div class="back-div">
        <a href="../../index.php" class="back-btn"> Back</a>

    </div>

    <div class="page-header">
        <h1>Prompt Management</h1>
        <p>Select a game type to manage its prompt steps</p>
    </div>

    <div class="gt-grid">

        <?php while ($row = mysqli_fetch_assoc($result)): ?>

            <div class="gt-card">

                <div class="gt-card-header">
                    <h3><?= htmlspecialchars($row['gt_title']); ?></h3>

                    <span class="status-badge <?= $row['gt_status'] ? 'active' : 'inactive'; ?>">
                        <?= $row['gt_status'] ? 'Active' : 'Inactive'; ?>
                    </span>
                </div>

                <div class="gt-card-body">
                    <p>
                        <strong><?= $row['prompt_count']; ?></strong>
                        Prompt Step(s) Configured
                    </p>

                    <small>
                        Created: <?= date("d M Y", strtotime($row['gt_created_at'])); ?>
                    </small>
                </div>

                <div class="gt-card-footer">
                    <a href="gt-prompts.php?gt_id=<?= $row['gt_id']; ?>"
                        class="manage-btn">
                        Manage Prompts
                    </a>
                </div>

            </div>

        <?php endwhile; ?>

    </div>

</main>

<?php require "../../layout/footer.php"; ?>
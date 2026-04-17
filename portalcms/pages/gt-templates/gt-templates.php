<?php
require_once "../../../include/session_check.php";
require "../../../include/dataconnect.php";

$pageTitle = "Game Template Management | trainerBYTE CMS";
$pageCSS   = "/portalcms/pages/page-styles/gt-templates.css";

require "../../layout/header.php";
require "../../layout/navbar.php";

$sql = "SELECT * FROM tb_cms_gametype_template 
        ORDER BY gt_id DESC";
$result = mysqli_query($conn, $sql);
?>

<main class="gt-wrapper">
<div class="back-div">
        <a href="../../index.php" class="back-btn"> Back</a>

 </div>

    <!-- Header -->
    <div class="gt-header">
        <div>
            <h1>Game Template Management</h1>
            <p>Create, edit, and organize your gamified training templates.</p>
        </div>
        <a href="./gt-template-form.php" class="btn-primary">
            <span>＋</span> Create New Template
        </a>
    </div>

    <div class="gt-grid">

        <!-- LEFT SIDE -->
        <div class="gt-list-card">

            <div class="gt-table-head">
                <div>Template Name</div>
                <div>Category</div>
                <div>Status</div>
                <div></div>
            </div>

            <?php while($row = mysqli_fetch_assoc($result)): ?>

            <div class="gt-row">

                <div class="gt-info">
                    <div class="gt-thumb">
                        <?php if(!empty($row['gt_image'])): ?>
                            <img src="./gt-templates-uploads/<?php echo $row['gt_image']; ?>">
                        <?php else: ?>
                            <div class="gt-placeholder">🖼</div>
                        <?php endif; ?>
                    </div>
                    <div>
                        <h3><?php echo $row['gt_title']; ?></h3>
                        <span class="gt-meta">
                            Last edited <?php echo date("d M Y", strtotime($row['gt_updated_at'])); ?>
                        </span>
                    </div>
                </div>

                <div class="gt-category">
                    Category
                </div>

                <div>
                    <span class="status 
                        <?php echo ($row['gt_status']==1)?'status-active':'status-draft'; ?>">
                        <?php echo ($row['gt_status']==1)?'ACTIVE':'DRAFT'; ?>
                    </span>
                </div>

                <div class="gt-actions">
                    <a href="gt-template-form.php?gt_id=<?php echo $row['gt_id']; ?>" class="edit-btn">Edit</a>
                 
                </div>

            </div>

            <?php endwhile; ?>

        </div>

        <!-- RIGHT SIDE -->
        <aside class="gt-master-card">

            <div class="master-header">
                <h2>Master Data</h2>
                <p>Management Panel</p>
            </div>

            <div class="master-links">

                <a href="./gt-master-manage.php?type=category">
                    <span>📂</span> Category
                </a>

                <a href="./gt-master-manage.php?type=gametype">
                    <span>🎮</span> Game Type
                </a>

                <a href="./gt-master-manage.php?type=duration">
                    <span>⏱</span> Duration
                </a>

                <a href="./gt-master-manage.php?type=format">
                    <span>🧩</span> Format
                </a>

                <a href="./gt-master-manage.php?type=complexity">
                    <span>📊</span> Complexity
                </a>

            </div>

           

        </aside>

    </div>

</main>

<?php require "../../layout/footer.php"; ?>
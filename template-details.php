<?php

$pageTitle = "Template Details";
$pageCSS   = "/styles/template-details.css";

require "layout/header.php";
require "layout/navbar.php";
require "include/dataconnect.php";

$template_id = intval($_GET['id']);


/* TEMPLATE */

$template_sql = "SELECT *
FROM tb_cms_gametype_template
WHERE gt_id = $template_id
AND gt_status = 1";

$template = mysqli_fetch_assoc(mysqli_query($conn, $template_sql));


/* CATEGORY */

$cat_sql = "SELECT ct.ct_name
FROM tb_cms_gt_category_map m
JOIN tb_cms_category ct ON ct.ct_id = m.gtc_category_id
WHERE m.gtc_template_id = $template_id";

$cat_result = mysqli_query($conn, $cat_sql);


/* GAMETYPE */

$game_sql = "SELECT gm.gm_name
FROM tb_cms_gt_gametype_map m
JOIN tb_cms_gametype gm ON gm.gm_id = m.gtg_gametype_id
WHERE m.gtg_template_id = $template_id";

$game_result = mysqli_query($conn, $game_sql);


/* DURATION */

$dur_sql = "SELECT dr.dr_name
FROM tb_cms_gt_duration_map m
JOIN tb_cms_duration dr ON dr.dr_id = m.gtd_duration_id
WHERE m.gtd_template_id = $template_id";

$dur_result = mysqli_query($conn, $dur_sql);


/* FORMAT */

$format_sql = "SELECT fm.fm_name
FROM tb_cms_gt_format_map m
JOIN tb_cms_format fm ON fm.fm_id = m.gtf_format_id
WHERE m.gtf_template_id = $template_id";

$format_result = mysqli_query($conn, $format_sql);


/* COMPLEXITY */

$cx_sql = "SELECT cx.cx_name
FROM tb_cms_gt_complexity_map m
JOIN tb_cms_complexity cx ON cx.cx_id = m.gtx_complexity_id
WHERE m.gtx_template_id = $template_id";

$cx_result = mysqli_query($conn, $cx_sql);


/* HOW IT WORKS */

$how_sql = "SELECT *
FROM tb_cms_gt_howitworks
WHERE hw_template_id = $template_id
AND hw_status = 1
ORDER BY hw_step_no ASC";

$how_result = mysqli_query($conn, $how_sql);


/* LEARNING OUTCOMES */

$outcomes = json_decode($template['gt_learning_outcomes'], true);

?>


<section class="template-details">

    <div class="back-div">
        <a href="index.php" class="back-btn">Back</a>
    </div>



    <div class="template-layout">


        <!-- LEFT CONTENT -->

        <div class="template-main">


            <div class="template-hero">

                <img src="<?= BASE_PATH ?>/portalcms/pages/gt-templates/gt-templates-uploads/<?= $template['gt_image']; ?>">

                <div class="hero-overlay">

                    <h1><?= htmlspecialchars($template['gt_title']); ?></h1>

                    <p><?= htmlspecialchars($template['gt_tagline']); ?></p>

                    <a href="#" class="try-btn">Try Template</a>

                </div>

            </div>



            <div class="template-desc">

                <h3>About This Template</h3>

                <p><?= nl2br(htmlspecialchars($template['gt_full_desc'])); ?></p>

            </div>



            <div class="learning-outcomes">

                <h3>Learning Outcomes</h3>

                <div class="outcomes-grid">

                    <?php foreach ($outcomes as $item) { ?>

                        <div class="outcome-card">

                            <div class="check">✓</div>

                            <p><?= htmlspecialchars($item) ?></p>

                        </div>

                    <?php } ?>

                </div>

            </div>



            <div class="how-works">

                <h3>How It Works</h3>

                <?php while ($hw = mysqli_fetch_assoc($how_result)) { ?>

                    <div class="how-card">

                        <div class="step"><?= $hw['hw_step_no'] ?></div>

                        <div class="how-content">

                            <h4><?= htmlspecialchars($hw['hw_title']); ?></h4>

                            <p><?= htmlspecialchars($hw['hw_description']); ?></p>

                        </div>

                        <?php if ($hw['hw_image']) { ?>

                            <div class="how-image">

                                <img src="<?= BASE_PATH ?>/portalcms/pages/gt-templates/gt-templates-hiw-uploads/<?= $hw['hw_image']; ?>">

                            </div>

                        <?php } ?>

                    </div>

                <?php } ?>

            </div>

        </div>



        <!-- RIGHT PANEL -->

        <div class="template-side">

            <div class="config-card">

                <h3>Template Configuration</h3>


                <div class="config-item">

                    <label>Category</label>

                    <div class="tags">

                        <?php while ($row = mysqli_fetch_assoc($cat_result)) { ?>
                            <span><?= $row['ct_name'] ?></span>
                        <?php } ?>

                    </div>

                </div>



                <div class="config-item">

                    <label>Game Type</label>

                    <div class="tags">

                        <?php while ($row = mysqli_fetch_assoc($game_result)) { ?>
                            <span><?= $row['gm_name'] ?></span>
                        <?php } ?>

                    </div>

                </div>



                <div class="config-item">

                    <label>Duration</label>

                    <div class="tags">

                        <?php while ($row = mysqli_fetch_assoc($dur_result)) { ?>
                            <span><?= $row['dr_name'] ?></span>
                        <?php } ?>

                    </div>

                </div>



                <div class="config-item">

                    <label>Format</label>

                    <div class="tags">

                        <?php while ($row = mysqli_fetch_assoc($format_result)) { ?>
                            <span><?= $row['fm_name'] ?></span>
                        <?php } ?>

                    </div>

                </div>



                <div class="config-item">

                    <label>Complexity</label>

                    <div class="tags">

                        <?php while ($row = mysqli_fetch_assoc($cx_result)) { ?>
                            <span><?= $row['cx_name'] ?></span>
                        <?php } ?>

                    </div>

                </div>

            </div>

        </div>


    </div>

</section>


<?php require "layout/footer.php"; ?>
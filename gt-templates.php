<?php

$pageTitle = "Game Templates";
$pageCSS   = "/styles/gt-templates.css";

require "layout/header.php";
require "layout/navbar.php";
require "include/dataconnect.php";


$sql = "
SELECT 
    gt.gt_id,
    gt.gt_title,
    gt.gt_tagline,
    gt.gt_short_desc,
    gt.gt_image,

    GROUP_CONCAT(DISTINCT ct.ct_name) AS categories,
    GROUP_CONCAT(DISTINCT dr.dr_name) AS durations,
    GROUP_CONCAT(DISTINCT gm.gm_name) AS gametypes,
    GROUP_CONCAT(DISTINCT cx.cx_name) AS complexities

FROM tb_cms_gametype_template gt

LEFT JOIN tb_cms_gt_category_map gtc 
    ON gtc.gtc_template_id = gt.gt_id
LEFT JOIN tb_cms_category ct 
    ON ct.ct_id = gtc.gtc_category_id

LEFT JOIN tb_cms_gt_duration_map gtd 
    ON gtd.gtd_template_id = gt.gt_id
LEFT JOIN tb_cms_duration dr 
    ON dr.dr_id = gtd.gtd_duration_id

LEFT JOIN tb_cms_gt_gametype_map gtg 
    ON gtg.gtg_template_id = gt.gt_id
LEFT JOIN tb_cms_gametype gm 
    ON gm.gm_id = gtg.gtg_gametype_id

LEFT JOIN tb_cms_gt_complexity_map gtx 
    ON gtx.gtx_template_id = gt.gt_id
LEFT JOIN tb_cms_complexity cx 
    ON cx.cx_id = gtx.gtx_complexity_id

WHERE gt.gt_status = 1

GROUP BY gt.gt_id
ORDER BY gt.gt_id DESC
";

$result = mysqli_query($conn, $sql);

?>

<section class="templates-page">

    <div class="back-div">
        <a href="index.php" class="back-btn">Back</a>
    </div>

    <div class="templates-hero">

        <h1>Game Templates</h1>

        <p>
            Explore interactive training game templates designed to make learning engaging, collaborative,
            and impactful for teams and organizations.
        </p>

    </div>


    <div class="templates-container">
        <div class="filter-bar">

            <select id="filterCategory">
                <option value="">All Categories</option>
                <?php
                $res = mysqli_query($conn, "SELECT * FROM tb_cms_category WHERE ct_status=1");
                while ($r = mysqli_fetch_assoc($res)) {
                    echo "<option value='{$r['ct_id']}'>{$r['ct_name']}</option>";
                }
                ?>
            </select>


            <select id="filterGameType">
                <option value="">All Game Types</option>
                <?php
                $res = mysqli_query($conn, "SELECT * FROM tb_cms_gametype WHERE gm_status=1");
                while ($r = mysqli_fetch_assoc($res)) {
                    echo "<option value='{$r['gm_id']}'>{$r['gm_name']}</option>";
                }
                ?>
            </select>


            <select id="filterDuration">
                <option value="">All Durations</option>
                <?php
                $res = mysqli_query($conn, "SELECT * FROM tb_cms_duration WHERE dr_status=1");
                while ($r = mysqli_fetch_assoc($res)) {
                    echo "<option value='{$r['dr_id']}'>{$r['dr_name']}</option>";
                }
                ?>
            </select>


            <select id="filterComplexity">
                <option value="">All Complexity</option>
                <?php
                $res = mysqli_query($conn, "SELECT * FROM tb_cms_complexity WHERE cx_status=1");
                while ($r = mysqli_fetch_assoc($res)) {
                    echo "<option value='{$r['cx_id']}'>{$r['cx_name']}</option>";
                }
                ?>
            </select>

            <button id="resetFilters" class="reset-btn">
                Reset Filters
            </button>

        </div>

        <div class="templates-grid" id="templatesGrid">

            <?php while ($row = mysqli_fetch_assoc($result)) { ?>

                <a href="template-details.php?id=<?= $row['gt_id'] ?>" class="template-card">

                    <div class="template-img">

                        <img src="<?= BASE_PATH ?>/portalcms/pages/gt-templates/gt-templates-uploads/<?= $row['gt_image'] ?>">

                        <span class="game-type">
                            <?= $row['gametypes'] ?>
                        </span>

                    </div>

                    <div class="template-body">

                        <h3><?= htmlspecialchars($row['gt_title']) ?></h3>

                        <p class="tagline">
                            <?= htmlspecialchars($row['gt_tagline']) ?>
                        </p>

                        <p class="desc">
                            <?= htmlspecialchars($row['gt_short_desc']) ?>
                        </p>

                        <div class="meta">

                            <?php
                            $cats = explode(",", $row['categories']);
                            foreach ($cats as $c) {
                            ?>
                                <span><?= trim($c) ?></span>
                            <?php } ?>

                            <span><?= $row['durations'] ?></span>

                            <span class="level">
                                <?= $row['complexities'] ?>
                            </span>

                        </div>

                    </div>

                </a>

            <?php } ?>

        </div>

    </div>

</section>

<script>
    function loadTemplates() {

        let category = document.getElementById("filterCategory").value;
        let gametype = document.getElementById("filterGameType").value;
        let duration = document.getElementById("filterDuration").value;
        let complexity = document.getElementById("filterComplexity").value;

        let formData = new FormData();
        formData.append("category", category);
        formData.append("gametype", gametype);
        formData.append("duration", duration);
        formData.append("complexity", complexity);

        fetch("filter-templates.php", {
                method: "POST",
                body: formData
            })
            .then(res => res.text())
            .then(data => {
                document.getElementById("templatesGrid").innerHTML = data;
            });

    }

    document.querySelectorAll(".filter-bar select").forEach(el => {
        el.addEventListener("change", loadTemplates);
    });



    document.getElementById("resetFilters").addEventListener("click", function() {

        document.getElementById("filterCategory").value = "";
        document.getElementById("filterGameType").value = "";
        document.getElementById("filterDuration").value = "";
        document.getElementById("filterComplexity").value = "";

        loadTemplates();

    });
</script>


<?php require "layout/footer.php"; ?>
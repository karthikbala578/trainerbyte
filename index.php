<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
$pageTitle = "TrainerByte Dashboard";
$pageCSS = "/styles/index.css";

require "layout/header.php";
require "layout/navbar.php";
require "include/dataconnect.php";

$sql = "SELECT * FROM tb_cms_banner 
        WHERE bn_status = 1 
        ORDER BY bn_id DESC";
$result = mysqli_query($conn, $sql);

//template listing sql
$template_sql = "
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
    AND ct.ct_status = 1

LEFT JOIN tb_cms_gt_duration_map gtd 
    ON gtd.gtd_template_id = gt.gt_id
LEFT JOIN tb_cms_duration dr 
    ON dr.dr_id = gtd.gtd_duration_id 
    AND dr.dr_status = 1

LEFT JOIN tb_cms_gt_gametype_map gtg 
    ON gtg.gtg_template_id = gt.gt_id
LEFT JOIN tb_cms_gametype gm 
    ON gm.gm_id = gtg.gtg_gametype_id 
    AND gm.gm_status = 1

LEFT JOIN tb_cms_gt_complexity_map gtx 
    ON gtx.gtx_template_id = gt.gt_id
LEFT JOIN tb_cms_complexity cx 
    ON cx.cx_id = gtx.gtx_complexity_id 
    AND cx.cx_status = 1

WHERE gt.gt_status = 1

GROUP BY gt.gt_id
ORDER BY gt.gt_id DESC
LIMIT 3
";

$template_result = mysqli_query($conn, $template_sql);

//highlights SQL

$highlight_sql = "SELECT * FROM tb_cms_highlight 
                  WHERE hl_status = 1 
                  ORDER BY hl_id DESC";
$highlight_result = mysqli_query($conn, $highlight_sql);


// How It Works SQL
$how_sql = "SELECT * FROM tb_cms_howitworks 
            WHERE hw_status = 1 
            ORDER BY hw_step_no ASC";
$how_result = mysqli_query($conn, $how_sql);
?>




<section class="hero">
    <div class="hero-container">
        <div class="hero-track" id="slides">

            <?php while ($row = mysqli_fetch_assoc($result)) { ?>
                <div class="hero-slide">
                    <div class="hero-content">

                        <div class="hero-text">
                            <?php if (!empty($row['bn_pill_text'])) { ?>
                                <span class="pill"><?php echo htmlspecialchars($row['bn_pill_text']); ?></span>
                            <?php } ?>

                            <h1><?php echo htmlspecialchars($row['bn_title']); ?></h1>
                            <p><?php echo htmlspecialchars($row['bn_short_desc']); ?></p>

                            <div class="hero-actions">
                                <a href="#" class="btn-primary">Start Building</a>
                                <a href="#hiw" class="btn-outline">How It Works</a>
                            </div>
                        </div>

                        <div class="hero-image">
                            <div class="image-frame">

                                <img src="<?php echo BASE_PATH ?>/portalcms/pages/banner/banner-uploads/<?php echo htmlspecialchars($row['bn_image']); ?>" alt="">
                            </div>
                        </div>
                    </div>
                </div>
            <?php } ?>

        </div>
    </div>
</section>

<section class="templates-section">
    <div class="container">
        <div class="templates-header">
            <h2>Most Popular Templates</h2>
            <a href="gt-templates.php" class="view-all">View All Templates →</a>
        </div>

        <div class="templates-grid">

            <?php while ($row = mysqli_fetch_assoc($template_result)) { ?>

                <a href="template-details.php?id=<?php echo $row['gt_id']; ?>" class="template-card">

                    <div class="template-image">

                        <?php if ($row['gametypes']) { ?>
                            <span class="game-pill">
                                <?php echo htmlspecialchars($row['gametypes']); ?>
                            </span>
                        <?php } ?>

                        <img src="<?php echo BASE_PATH ?>/portalcms/pages/gt-templates/gt-templates-uploads/<?php echo htmlspecialchars($row['gt_image']); ?>" alt="">
                    </div>

                    <div class="template-body">

                        <h3 class="template-title">
                            <?php echo htmlspecialchars($row['gt_title']); ?>
                        </h3>

                        <?php if (!empty($row['gt_tagline'])) { ?>
                            <p class="template-tagline">
                                <?php echo htmlspecialchars($row['gt_tagline']); ?>
                            </p>
                        <?php } ?>

                        <p class="template-desc">
                            <?php echo htmlspecialchars($row['gt_short_desc']); ?>
                        </p>

                        <div class="template-meta">

                            <?php
                            if ($row['categories']) {
                                $categories = explode(',', $row['categories']);
                                foreach ($categories as $cat) {
                            ?>
                                    <span class="meta-chip">
                                        <?php echo htmlspecialchars(trim($cat)); ?>
                                    </span>
                            <?php
                                }
                            }
                            ?>

                            <?php if ($row['durations']) { ?>
                                <span class="meta-chip">
                                    <?php echo htmlspecialchars($row['durations']); ?>
                                </span>
                            <?php } ?>

                            <?php if ($row['complexities']) { ?>
                                <span class="difficulty-chip">
                                    <?php echo htmlspecialchars($row['complexities']); ?>
                                </span>
                            <?php } ?>

                        </div>

                    </div>

                </a>

            <?php } ?>

        </div>
    </div>
</section>

<section class="highlights-section">
    <h2 class="highlights-heading">Why Choose TrainerByte?</h2>

    <div class="highlights-container">
        <div class="highlights-track">

            <?php
            $count = 0;

            while ($hl = mysqli_fetch_assoc($highlight_result)) {

                if ($count % 3 == 0) {
                    echo '<div class="highlight-slide">';
                }
            ?>

                <div class="highlight-card">
                    <div class="highlight-icon">
                        <img src="<?php echo BASE_PATH ?>/portalcms/pages/highlights/highlights-uploads/<?php echo htmlspecialchars($hl['hl_icon']); ?>" alt="">
                    </div>

                    <h3 class="highlight-title">
                        <?php echo htmlspecialchars($hl['hl_title']); ?>
                    </h3>
                    <br>
                    <p class="highlight-desc">
                        <?php echo htmlspecialchars($hl['hl_description']); ?>
                    </p>
                </div>

            <?php
                $count++;

                if ($count % 3 == 0) {
                    echo '</div>';
                }
            }

            // If last slide not closed
            if ($count % 3 != 0) {
                echo '</div>';
            }
            ?>

        </div>
    </div>

</section>

<section id="hiw" class="how-main-section">

    <div class="how-blue-container">

        <h2 class="how-title-main">How It Works</h2>

        <div class="how-cards-wrapper">

            <?php while ($hw = mysqli_fetch_assoc($how_result)) { ?>

                <div class="how-card-box">

                    <div class="how-step-circle">
                        <?php echo htmlspecialchars($hw['hw_step_no']); ?>
                    </div>

                    <h3 class="how-card-title">
                        <?php echo htmlspecialchars($hw['hw_title']); ?>
                    </h3>

                    <p class="how-card-desc">
                        <?php echo htmlspecialchars($hw['hw_description']); ?>
                    </p>

                    <?php if (!empty($hw['hw_image'])) { ?>
                        <div class="how-card-image">
                            <img src="<?php echo BASE_PATH ?>/portalcms/pages/how-it-works/howitworks-uploads/<?php echo htmlspecialchars($hw['hw_image']); ?>" alt="">
                        </div>
                    <?php } ?>

                </div>

            <?php } ?>

        </div>

    </div>

</section>

<script>
    const track = document.getElementById("slides");
    let slides = document.querySelectorAll(".hero-slide");

    if (slides.length > 1) {

        let index = 1;
        let interval = null;
        const speed = 3000;

        const firstClone = slides[0].cloneNode(true);
        const lastClone = slides[slides.length - 1].cloneNode(true);

        track.appendChild(firstClone);
        track.insertBefore(lastClone, slides[0]);

        slides = document.querySelectorAll(".hero-slide");

        track.style.transform = "translateX(-100%)";

        function startSlider() {
            if (interval) return;
            interval = setInterval(() => {
                index++;
                track.style.transition = "transform 0.8s ease-in-out";
                track.style.transform = `translateX(-${index * 100}%)`;
            }, speed);
        }

        function stopSlider() {
            clearInterval(interval);
            interval = null;
        }

        track.addEventListener("transitionend", () => {
            if (slides[index] === firstClone) {
                track.style.transition = "none";
                index = 1;
                track.style.transform = `translateX(-${index * 100}%)`;
            }
            if (slides[index] === lastClone) {
                track.style.transition = "none";
                index = slides.length - 2;
                track.style.transform = `translateX(-${index * 100}%)`;
            }
        });

        document.addEventListener("visibilitychange", () => {
            if (document.hidden) stopSlider();
            else startSlider();
        });

        track.addEventListener("mousedown", stopSlider);
        track.addEventListener("mouseup", startSlider);
        track.addEventListener("touchstart", stopSlider);
        track.addEventListener("touchend", startSlider);

        startSlider();
    }


    //highlight slider


    const highlightTrack = document.querySelector(".highlights-track");
    const highlightSlides = document.querySelectorAll(".highlight-slide");

    let highlightIndex = 0;
    const highlightSpeed = 4000;

    function nextHighlight() {
        highlightIndex++;

        if (highlightIndex >= highlightSlides.length) {
            highlightIndex = 0;
        }

        highlightTrack.style.transform = `translateX(-${highlightIndex * 100}%)`;
    }

    setInterval(nextHighlight, highlightSpeed);
</script>


<?php
  require "./layout/footer.php"

?>


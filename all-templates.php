<?php
$pageTitle = "All Templates";
$pageCSS   = "/assets/styles/all-templates.css";

require "layout/tb_header.php";
require "include/coreDataconnect.php";

/* Fetch all exercise templates */
$stmt = $conn->prepare("
    SELECT ex_id, ex_name, ex_des, ex_tag, ex_image, ex_type
    FROM tb_exercise_type
    ORDER BY ex_id ASC
");
$stmt->execute();
$result = $stmt->get_result();

/* Exercise routing map */
$exerciseRoutes = [
    1 => "digihunt_exercise.php",     // Treasure
    2 => "byteguess_exercise.php",    // Card
    3 => "pixelquest_exercise.php",   // Survival
    4 => "bitbargain_exercise.php"    // Bargain
];
?>

<section class="templates-section">
    <div class="back-div">
        <a href="index.php" class="back-btn"> <img src="./assets/images/back-icon.png  "> Back</a>

    </div>


    <div class="section-header">
        <h2>All Exercise Templates</h2>
    </div>

    <div class="templates-grid">

        <?php if ($result->num_rows === 0): ?>
            <p>No templates available.</p>
        <?php else: ?>
            <?php while ($template = $result->fetch_assoc()): ?>

                <?php
                $redirectPage = $exerciseRoutes[$template['ex_type']] ?? "index.php";
                ?>

                <div class="template-card">

                    <div class="card-img">
                        <span class="tag">
                            <?= htmlspecialchars($template['ex_tag'] ?? 'General') ?>
                        </span>

                        <?php if (!empty($template['ex_image'])): ?>
                            <img
                                src="./upload-images/exercise-pics/<?= htmlspecialchars($template['ex_image']) ?>"
                                alt="<?= htmlspecialchars($template['ex_name']) ?>">
                        <?php else: ?>
                            <div class="img-placeholder">
                                No Image
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="card-body">
                        <h3><?= htmlspecialchars($template['ex_name']) ?></h3>
                        <p><?= htmlspecialchars($template['ex_des']) ?></p>

                        <button onclick="window.location.href='<?= $redirectPage ?>?ex_id=<?= $template['ex_id'] ?>'">
                            Create Session
                        </button>
                    </div>

                </div>

            <?php endwhile; ?>
        <?php endif; ?>

    </div>

</section>

<?php //require "layout/footer.php"; ?>
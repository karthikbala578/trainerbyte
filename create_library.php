<?php
require_once "include/session_check.php";
require "include/coreDataconnect.php";

if (!isset($_SESSION['team_id'])) {
    header("Location: login.php");
    exit;
}

//$pageTitle = "Create Library";
// $pageCSS   = "assets/styles/create_library.css";
// require "layout/tb_header.php";
//$pageTitle = "TrainerGenie Dashboard";
$pageCSS   = "assets/styles/index.css";

require "layout/tb_header.php";
$stmt = $conn->prepare("
    SELECT ex_id, ex_name, ex_des, ex_tag, ex_image, ex_type
    FROM tb_exercise_type
    ORDER BY ex_id ASC
    ");
$stmt->execute();
$result = $stmt->get_result();

?>

<div class="index-container">
    <section class="templates-section">

        <div class="section-header">
            <h2>Exercise Library</h2>
            <a href="all-templates.php" class="view-all">
                View all templates →
            </a>
        </div>

        <div class="templates-grid">
            <?php if ($result->num_rows === 0): ?>
                <p>No templates available.</p>
            <?php else: ?>
                <?php while ($template = $result->fetch_assoc()): ?>
                    <div class="template-card">

                    <div class="card-img">
                        <span class="tag">
                            <?php echo  htmlspecialchars($template['ex_tag'] ?? 'General') ?>
                        </span>

                        <?php if (!empty($template['ex_image'])): ?>
                            <img
                                src="./upload-images/exercise-pics/<?php echo  htmlspecialchars($template['ex_image']) ?>"
                                alt="<?php echo  htmlspecialchars($template['ex_name']) ?>">
                        <?php else: ?>
                            <div class="img-placeholder">
                                No Image
                            </div>
                        <?php endif; ?>
                    </div>

                        <div class="card-body">
                            <h3><?php echo  htmlspecialchars($template['ex_name']) ?></h3>
                            <p><?php echo  htmlspecialchars($template['ex_des']) ?></p>

                            <?php
                            $exerciseRoutes = [
                                1 => "digihunt_exercise.php",
                                2 => "byteguess_exercise.php",
                                3 => "pixelquest_exercise.php",
                                4 => "bitbargain_exercise.php",
                                5 => "digisim/admin/pages/page-container.php",
                                6 => "riskhop/admin/"
                            ];

                            $redirectPage = $exerciseRoutes[$template['ex_type']] ?? "index.php";
                            ?>

                            <button onclick="window.location.href='<?php echo  $redirectPage ?>'">
                                Create Library
                            </button>
                        </div>


                    </div>
                <?php endwhile; ?>
            <?php endif; ?>
        </div>

    </section>
</div>
<!-- <div class="cl-page">
    <h1 class="cl-title">Exercise Library</h1>
    
    <div class="cl-grid" id="typeGrid">
        <button class="cl-btn active" data-type="byteguess_exercise.php">ByteGuess</button>
        <button class="cl-btn" data-type="#">Pixel Quest</button>
        <button class="cl-btn" data-type="#">DigiHunt</button>
        <button class="cl-btn" data-type="#">Bit Bargain</button>
        <button class="cl-btn" data-type="#">RiskHOP</button>
        <button class="cl-btn" data-type="#">TrustTrap</button>
        <button class="cl-btn" data-type="#">BountyBid</button>
        <button class="cl-btn" data-type="#">DigiSim</button>
    </div>

    <div class="cl-actions">
        
        <button class="btn-primary" id="btnCreateSession">CREATE LIBRARY</button>
    </div>
</div> -->

<script>
    const buttons = document.querySelectorAll('.cl-btn');
    let selectedType = 'byteguess_exercise.php';

    buttons.forEach(btn => {
        btn.addEventListener('click', () => {
            // Remove active from all
            buttons.forEach(b => b.classList.remove('active'));
            // Add active to clicked
            btn.classList.add('active');
            selectedType = btn.getAttribute('data-type');
        });
    });

    document.getElementById('btnCreateSession').addEventListener('click', () => {
        if (selectedType === '#' || !selectedType) {
            alert("This exercise creation file is not yet ready. As mentioned, only ByteGuess is currently being wired up. Please select ByteGuess for now.");
        } else {
            window.location.href = selectedType;
        }
    });
</script>

<?php //require "layout/footer.php"; ?>
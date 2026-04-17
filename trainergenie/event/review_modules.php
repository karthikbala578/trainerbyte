<?php
session_start();
require "../include/dataconnect.php";

$event_id = intval($_GET['event_id'] ?? 0);

if (!$event_id) {
    die("Invalid event");
}

/* Fetch already assigned CARD GAMES from DB */
$assignedStmt = $conn->prepare("
    SELECT
        em.mod_order,
        em.mod_type,
        cg.cg_name
    FROM tb_events_module em
    JOIN card_group cg
        ON em.mod_game_id = cg.cg_id
    WHERE em.mod_event_pkid = ?
      AND em.mod_status = 1
    ORDER BY em.mod_order ASC
");
$assignedStmt->bind_param("i", $event_id);
$assignedStmt->execute();
$assignedModules = $assignedStmt->get_result()->fetch_all(MYSQLI_ASSOC);

/* Get newly added (session) modules */
$stagedModules = $_SESSION['event_modules'][$event_id] ?? [];

$nothingToReview = empty($assignedModules) && empty($stagedModules);

/* if (empty($assignedModules) && empty($stagedModules)) {
    die("Nothing to review");
} */

$pageTitle = "Review Event Modules";
$pageCSS   = "/event/review_modules.css";
require "../layout/header.php";

/* Wizard Step */
$step = 3;
include "../components/wizard_steps.php";
?>

<div class="review-wrap">

    <!-- HEADER -->
    <div class="review-header">
        <h1>Review Event Modules</h1>
        <p>Confirm the sequence before adding new modules to the event</p>
    </div>

    <?php if ($nothingToReview): ?>
        <div class="empty-state">
            <h2>Nothing to review</h2>
            <p>No modules have been added to this event yet.</p>

            <a href="add_modules.php?event_id=<?php echo  $event_id ?>" class="btn secondary">
                ← Previous
            </a>
        </div>
    <?php else: ?>
    <!-- REVIEW CARD -->
    <div class="review-card">

        <h3>Module Sequence</h3>

        <div class="review-list">

            <!-- EXISTING MODULES -->
            <?php foreach ($assignedModules as $m): ?>
                <div class="review-item locked">
                    <span class="order"><?php echo  $m['mod_order'] ?></span>
                    <span class="name"><?php echo  htmlspecialchars($m['cg_name']) ?></span>
                    <span class="tag">Already Added</span>
                </div>
            <?php endforeach; ?>

            <!-- NEW MODULES -->
            <?php
            $startOrder = count($assignedModules);
                $startOrder = count($assignedModules);
                foreach ($stagedModules as $i => $m):
                ?>
                    <div class="review-item draft">
                        <span class="order"><?php echo $startOrder + $i + 1 ?></span>
                        <span class="name"><?php echo htmlspecialchars($m['name']) ?></span>
                        <span class="tag draft">New</span>
                    </div>
                <?php endforeach; ?>
        </div>

        <!-- ACTIONS -->
        <div class="review-actions">
            <a href="add_modules.php?event_id=<?php echo  $event_id ?>" class="btn secondary">
                ← Previous
            </a>

            <?php if (!empty($stagedModules)): ?>
                <form method="post" action="commit_modules.php">
                    <input type="hidden" name="event_id" value="<?php echo  $event_id ?>">
                    <button class="btn primary">
                        Confirm & Add Modules
                    </button>
                </form>
            <?php endif; ?>
        </div>

    </div>
    <?php endif; ?>

</div>

<?php require "../layout/footer.php"; ?>

<?php
session_start();
require "include/coreDataconnect.php";

if (!isset($_SESSION['team_id'])) {
    header("Location: login.php");
    exit;
}

$event_id = intval($_GET['event_id'] ?? 0);
if (!$event_id) die("Invalid event");

/* FETCH EVENT */
$stmt = $conn->prepare("
    SELECT * FROM tb_events 
    WHERE event_id = ? AND event_team_pkid = ?
");
$stmt->bind_param("ii", $event_id, $_SESSION['team_id']);
$stmt->execute();
$event = $stmt->get_result()->fetch_assoc();

/* FETCH MODULES */
$res = $conn->query("
    SELECT mod_order, mod_type, mod_game_id
    FROM tb_events_module
    WHERE mod_event_pkid = $event_id AND mod_status = 1
    ORDER BY mod_order ASC
");

/* MODULE NAME */
function getModuleName($conn, $type, $id)
{
    if ($type == 2) {
        $q = $conn->query("SELECT cg_name FROM card_group WHERE cg_id = $id");
        if ($q && $r = $q->fetch_assoc()) return $r['cg_name'];
    }
    if ($type == 4) {
        $q = $conn->query("SELECT p_name FROM prioritization WHERE p_id = $id");
        if ($q && $r = $q->fetch_assoc()) return $r['p_name'];
    }
    if ($type == 5) {
        $q = $conn->query("SELECT di_name FROM mg5_digisim WHERE di_id = $id");
        if ($q && $r = $q->fetch_assoc()) return $r['di_name'];
    }
    return "Module";
}

$pageTitle = "Final Review";
$pageCSS   = "/css_event/review_modules.css";

require "layout/tb_header.php";

/* STEP */
$step = 3;
?>

<div class="stepper-div">
    <?php include "components/wizard_steps.php"; ?>
</div>

<div class="final-wrap">



    <!-- HEADER -->
    <div class="final-header">
        <h1>Final Review</h1>
        <p>Please verify all details before launching your event</p>
    </div>

    <div class="final-grid">

        <!-- LEFT SIDE -->
        <div>

            <!-- EVENT OVERVIEW -->
            <div class="card">
                <h3>Event Overview</h3>

                <div class="overview-grid">

                    <div>
                        <label>Event Name</label>
                        <p><?= htmlspecialchars($event['event_name']) ?></p>
                    </div>

                    <div>
                        <label>Passcode</label>
                        <p><?= $event['event_passcode'] ?: '-' ?></p>
                    </div>

                    <div>
                        <label>Start Date</label>
                        <p><?= date('d M Y', strtotime($event['event_start_date'])) ?></p>
                    </div>

                    <div>
                        <label>Validity</label>
                        <p><?= $event['event_validity'] ?> days</p>
                    </div>

                    <div class="full">
                        <label>Description</label>
                        <p><?= $event['event_description'] ?: '-' ?></p>
                    </div>

                </div>
            </div>

            <!-- MODULE SEQUENCE -->
            <div class="card">
                <h3>Module Sequence</h3>

                <div class="module-list">

                    <?php
                    $i = 1;
                    while ($row = $res->fetch_assoc()):

                        $name = getModuleName($conn, $row['mod_type'], $row['mod_game_id']);

                        $icon = "widgets";
                        if ($row['mod_type'] == 2) $icon = "style";
                        if ($row['mod_type'] == 4) $icon = "view_list";
                        if ($row['mod_type'] == 5) $icon = "memory";
                    ?>

                        <div class="module-item">

                            <span class="index"><?= $i++ ?></span>

                            <span class="material-symbols-outlined icon"><?= $icon ?></span>

                            <div class="module-info">
                                <strong><?= htmlspecialchars($name) ?></strong>
                            </div>

                        </div>

                    <?php endwhile; ?>

                </div>

            </div>

        </div>

        <!-- RIGHT SIDE -->
        <div>

            <!-- POSTER -->
            <div class="card">
                <h4>Event Poster</h4>

                <img src="upload-images/events/<?= $event['event_coverimage'] ?? 'default_event.jpeg' ?>"
                    class="poster">
            </div>

            <!-- ACTION -->
            <div class="card action-card">

                <h3>Ready to go?</h3>

                <ul class="feature-list">

                    <li>
                        <span class="material-symbols-outlined icon green">link</span>
                        Participants can access the event using a secure link
                    </li>

                    <li>
                        <span class="material-symbols-outlined icon blue">analytics</span>
                        Live analytics will be available as soon as attendees join
                    </li>

                </ul>

                <form method="post" action="process_create_event.php">
                    <input type="hidden" name="event_id" value="<?= $event_id ?>">
                    <input type="hidden" name="launch_event" value="1">

                    <button class="btn primary">
                        <span class="material-symbols-outlined">rocket_launch</span>
                        Confirm & Launch
                    </button>
                </form>

                <a href="add_modules.php?event_id=<?= $event_id ?>" class="btn secondary">
                    <span class="material-symbols-outlined">arrow_back</span>
                    Back to Edit
                </a>

            </div>

        </div>

    </div>

</div>

<div id="successModal" class="modal-overlay">
    <div class="modal-box">

        <div class="modal-icon">
            <span class="material-symbols-outlined big-icon ">check_circle</span>
        </div>

        <h2>Event Created Successfully</h2>
        <p>Your event is now ready to use.</p>

        <a href="view_event.php?event_id=<?= $event_id ?>" class=" launch-btn">
            Go to Manage Event to Get Link
        </a>

    </div>
</div>

<script>
    const params = new URLSearchParams(window.location.search);

    if (params.get("success") == "1") {
        document.getElementById("successModal").classList.add("show");
    }
</script>

<?php //require "layout/footer.php"; ?>
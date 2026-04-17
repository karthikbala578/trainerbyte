<?php
session_start();
require "../include/dataconnect.php";

if (!isset($_SESSION['team_id'])) {
    header("Location: ../login.php");
    exit;
}

$event_id = intval($_GET['event_id'] ?? 0);
if ($event_id <= 0) die("Invalid Event");

$stmt = $conn->prepare("
    SELECT *
    FROM tb_events
    WHERE event_id = ?
      AND event_team_pkid = ?
");
$stmt->bind_param("ii", $event_id, $_SESSION['team_id']);
$stmt->execute();
$event = $stmt->get_result()->fetch_assoc();

if (!$event) die("Event not found");

$pageTitle = $event['event_name'];
$pageCSS   = "/event/view_event.css";
require "../layout/header.php";
?>

<div class="event-page">

    <!-- TOP BAR -->
    <div class="event-topbar">
        <a href="../myevent.php" class="back-btn">← Back</a>
    </div>

    <!-- MAIN CONTENT -->
    <div class="event-view">

        <!-- COVER -->
        <div class="event-cover">
            <img src="../upload-images/events/<?php echo  htmlspecialchars($event['event_coverimage']) ?>" alt="Event Cover">
        </div>

        <!-- DETAILS -->
        <div class="event-details">

            <h1><?php echo  htmlspecialchars($event['event_name']) ?></h1>

            <?php if ($event['event_description']): ?>
                <p class="desc"><?php echo  htmlspecialchars($event['event_description']) ?></p>
            <?php endif; ?>

            <div class="info-grid">
                <div>
                    <strong>Start Date</strong>
                    <span><?php echo  date("d M Y", strtotime($event['event_start_date'])) ?></span>
                </div>
                <div>
                    <strong>Validity</strong>
                    <span><?php echo  $event['event_validity'] ?> days</span>
                </div>
                <div>
                    <strong>Passcode</strong>
                    <span><?php echo  htmlspecialchars($event['event_passcode']) ?></span>
                </div>
                <div>
                    <strong>Status</strong>
                    <span class="status">
                        <?php echo  match ($event['event_playstatus']) {
                            1 => 'Not Started',
                            2 => 'Open',
                            3 => 'In Progress',
                            4 => 'Completed'
                        } ?>
                    </span>
                </div>
            </div>

            <div class="actions">
                <a href="add_modules.php?event_id=<?php echo  $event_id ?>" class="btn primary">
                    Add Modules
                </a>

                <button class="btn secondary" onclick="openEditModal()">
                    Edit Event
                </button>
            </div>

        </div>
    </div>
</div>


<!-- EDIT MODAL -->
<div class="modal" id="editModal">
    <div class="modal-card">
        <h3>Edit Event</h3>

        <form id="editEventForm">
            <input type="hidden" name="event_id" value="<?php echo  $event_id ?>">

            <label>Event Name</label>
            <input type="text" name="event_name" value="<?php echo  htmlspecialchars($event['event_name']) ?>">

            <label>Description</label>
            <textarea name="event_description"><?php echo  htmlspecialchars($event['event_description']) ?></textarea>

            <label>Start Date</label>
            <input type="date" name="event_start_date"
                   value="<?php echo  date('Y-m-d', strtotime($event['event_start_date'])) ?>">

            <label>Validity (days)</label>
            <input type="number" name="event_validity" value="<?php echo  $event['event_validity'] ?>">

            <label>Status</label>
            <select name="event_playstatus">
                <option value="1" <?php echo  $event['event_playstatus']==1?'selected':'' ?>>Not Started</option>
                <option value="2" <?php echo  $event['event_playstatus']==2?'selected':'' ?>>Open</option>
                <option value="3" <?php echo  $event['event_playstatus']==3?'selected':'' ?>>In Progress</option>
                <option value="4" <?php echo  $event['event_playstatus']==4?'selected':'' ?>>Completed</option>
            </select>

            <div class="modal-actions">
                <button type="button" class="btn secondary" onclick="closeEditModal()">Cancel</button>
                <button type="submit" class="btn primary">Save</button>
            </div>
        </form>
    </div>
</div>

<script>
function openEditModal() {
    document.getElementById("editModal").style.display = "flex";
}
function closeEditModal() {
    document.getElementById("editModal").style.display = "none";
}

document.getElementById("editEventForm").addEventListener("submit", e => {
    e.preventDefault();

    fetch("update_event.php", {
        method: "POST",
        body: new FormData(e.target)
    })
    .then(res => res.json())
    .then(data => {
        if (data.status === "success") {
            location.reload();
        } else {
            alert(data.message);
        }
    });
});
</script>

<?php require "../layout/footer.php"; ?>

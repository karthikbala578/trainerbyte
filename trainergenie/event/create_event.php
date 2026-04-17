<?php
session_start();
require "../include/dataconnect.php";

if (!isset($_SESSION['team_id'])) {
    header("Location: ../login.php");
    exit;
}

/* Fetch existing event if editing */
$event_id = intval($_GET['event_id'] ?? 0);
$event = null;

if ($event_id > 0) {
    $stmt = $conn->prepare("
        SELECT *
        FROM tb_events
        WHERE event_id = ? AND event_team_pkid = ?
    ");
    $stmt->bind_param("ii", $event_id, $_SESSION['team_id']);
    $stmt->execute();
    $event = $stmt->get_result()->fetch_assoc();
}

$pageTitle = $event ? "Edit Event" : "Create Event";
$pageCSS   = "/event/create_event.css";
require "../layout/header.php";

/* Wizard Step */
$step = 1;
include "../components/wizard_steps.php";

$errors = $_SESSION['event_errors'] ?? [];
unset($_SESSION['event_errors']);
?>

<div class="create-event-wrap">

    <h1><?php echo  $event ? "Edit Event" : "Create Event" ?></h1>

    <?php if ($errors): ?>
        <div class="error-box"><?php echo  implode("<br>", $errors) ?></div>
    <?php endif; ?>

    <form method="post"
        action="process_create_event.php"
        enctype="multipart/form-data"
        class="event-form">

        <input type="hidden" name="event_id"
            value="<?php echo  $event['event_id'] ?? '' ?>">

        <input type="hidden" name="existing_image"
            value="<?php echo  $event['event_coverimage'] ?? 'default_event.jpeg' ?>">

        <div class="form-grid">

            <!-- LEFT -->
            <div class="form-left">

                <label>Event Name *</label>
                <input type="text"
                    name="event_name"
                    value="<?php echo  htmlspecialchars($event['event_name'] ?? '') ?>"
                    required>

                <label>Description</label>
                <textarea name="event_description"><?php echo  htmlspecialchars($event['event_description'] ?? '') ?></textarea>

                <label>Cover Image</label>

                <div class="drop-zone" id="dropZone">
                    <input type="file" name="event_coverimage" id="fileInput" accept="image/*">

                    <p id="dropText">
                        <?php if (!empty($event['event_coverimage'])): ?>
                            Current image:
                            <strong><?php echo  htmlspecialchars($event['event_coverimage']) ?></strong>
                        <?php else: ?>
                            Drag & drop image here<br><span>or click to upload</span>
                        <?php endif; ?>
                    </p>
                </div>

            </div>

            <!-- RIGHT -->
            <div class="form-right">

                <label>Start Date *</label>
                <input type="date"
                    name="event_start_date"
                    value="<?php echo  !empty($event['event_start_date'])
                                ? date('Y-m-d', strtotime($event['event_start_date']))
                                : '' ?>"
                    required>

                <label>Validity (days) *</label>
                <input type="number"
                    name="event_validity"
                    min="1"
                    value="<?php echo  $event['event_validity'] ?? '' ?>"
                    required>

                <label>Passcode</label>
                <input type="text"
                    name="event_passcode"
                    value="<?php echo  htmlspecialchars($event['event_passcode'] ?? '') ?>">

                <label>Play Status</label>
                <select name="event_playstatus">
                    <?php
                    $status = $event['event_playstatus'] ?? 1;
                    ?>
                    <option value="1" <?php echo  $status == 1 ? 'selected' : '' ?>>Not Started</option>
                    <option value="2" <?php echo  $status == 2 ? 'selected' : '' ?>>OPEN</option>
                    <option value="3" <?php echo  $status == 3 ? 'selected' : '' ?>>WIP</option>
                    <option value="4" <?php echo  $status == 4 ? 'selected' : '' ?>>COMPLETED</option>
                </select>

            </div>
        </div>

        <button class="btn primary">
            <?php echo  $event ? "Update & Continue" : "Create Event" ?>
        </button>

    </form>
</div>

<script>
    const dropZone = document.getElementById("dropZone");
    const fileInput = document.getElementById("fileInput");
    const dropText = document.getElementById("dropText");

    fileInput.addEventListener("change", () => {
        if (fileInput.files.length > 0) {
            dropText.innerHTML =
                `Selected file:<br><strong>${fileInput.files[0].name}</strong>`;
        }
    });

    dropZone.addEventListener("dragover", e => {
        e.preventDefault();
        dropZone.classList.add("dragover");
    });

    dropZone.addEventListener("dragleave", () => {
        dropZone.classList.remove("dragover");
    });

    dropZone.addEventListener("drop", e => {
        e.preventDefault();
        dropZone.classList.remove("dragover");

        if (e.dataTransfer.files.length) {
            fileInput.files = e.dataTransfer.files;
            dropText.innerHTML =
                `Selected file:<br><strong>${e.dataTransfer.files[0].name}</strong>`;
        }
    });
</script>

<?php require "../layout/footer.php"; ?>
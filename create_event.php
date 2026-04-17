<?php

session_start();

require "include/coreDataconnect.php";



if (!isset($_SESSION['team_id'])) {

    header("Location: login.php");

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

$pageCSS   = "css_event/create_event.css";
require "layout/header.php";
require "layout/tb_header.php";



/* Wizard Step */

$step = 1;




$errors = $_SESSION['event_errors'] ?? [];

unset($_SESSION['event_errors']);

?>


<div class="stepper-div">
    <?php include "components/wizard_steps.php"; ?>

</div>

<div class="create-event-wrap">
    <div class="page-header">
        <div class="step-title">
            <h1><?php echo  $event ? "Edit Event" : "Create Event" ?></h1>
        </div>

        <a href="javascript:history.back()" class="back-btn">
            <span class="material-symbols-outlined">arrow_back</span>
            Back
        </a>
    </div>

    <?php if ($errors): ?>

        <div class="error-box"><?php echo  implode("<br>", $errors) ?></div>

    <?php endif; ?>



    <form method="post"
        action="process_create_event.php"
        enctype="multipart/form-data"
        class="event-form">

        <input type="hidden" name="event_id"
            value="<?php echo $event['event_id'] ?? '' ?>">

        <input type="hidden" name="existing_image"
            value="<?php echo $event['event_coverimage'] ?? 'default_event.jpeg' ?>">

        <!-- MAIN GRID -->
        <div class="form-grid-new">

            <!-- LEFT -->
            <div class="left">

                <label>Event Name</label>
                <input type="text"
                    name="event_name"
                    placeholder="e.g. Annual Compliance Training 2024"
                    value="<?php echo htmlspecialchars($event['event_name'] ?? '') ?>"
                    required>

                <label>Description</label>
                <textarea name="event_description"
                    placeholder="Describe the purpose and goals of this training event..."><?php echo htmlspecialchars($event['event_description'] ?? '') ?></textarea>

            </div>

            <!-- RIGHT -->
            <div class="right">

                <!-- IMAGE -->
                <div class="upload-box" id="dropZone">
                    <input type="file" name="event_coverimage" id="fileInput" accept="image/*">

                    <div class="upload-content" id="dropText">
                        <span class="material-symbols-outlined upload-icon">upload</span>
                        <p>Event Banner Image</p>
                    </div>
                    <img width="100" height="100" src="upload-images/events/<?= htmlspecialchars($event['event_coverimage'] ?: 'default_event.jpg') ?>">

                </div>

                

            </div>

        </div>
<!-- STATUS -->
                
        <!-- DATE ROW -->
        <div class="date-row">
            <div class="input-icon">
                <label>Event Status</label>
                <select name="event_playstatus">
                    <?php $status = $event['event_playstatus'] ?? 1; ?>
                    <option value="1" <?= $status == 1 ? 'selected' : '' ?>>Draft</option>
                    <option value="2" <?= $status == 2 ? 'selected' : '' ?>>Published</option>
                    <option value="3" <?= $status == 3 ? 'selected' : '' ?>>In Progress</option>
                    <option value="4" <?= $status == 4 ? 'selected' : '' ?>>Closed</option>
                </select>
            </div>
            <div class="input-icon">
                <label>Start Date</label>
                <!-- <span class="material-symbols-outlined calendar">calendar_month</span> -->
                <input type="date"
                    name="event_start_date"
                    value="<?php echo !empty($event['event_start_date'])
                                ? date('Y-m-d', strtotime($event['event_start_date']))
                                : '' ?>"
                    required>
            </div>
    </div><div class="date-row">
            <div class="input-icon">
                <label>Validity (End Date)</label>
                <!-- <span class="material-symbols-outlined">schedule</span> -->
                <input type="number"
                    name="event_validity"
                    placeholder="Days"
                    value="<?php echo $event['event_validity'] ?? '' ?>"
                    required>
            </div>

            <div class="input-icon">
                <label>Passcode</label>
                <!-- <span class="material-symbols-outlined">lock</span> -->
                <input type="text"
                    name="event_passcode"
                    placeholder="Optional access code"
                    value="<?php echo htmlspecialchars($event['event_passcode'] ?? '') ?>">
            </div>

        </div>

        <!-- FOOTER -->
        <div class="form-footer">
            <button class="next-btn">
                Next: Modules
                <span class="material-symbols-outlined">arrow_forward</span>
            </button>
        </div>

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



<?php // require "../layout/footer.php"; ?>
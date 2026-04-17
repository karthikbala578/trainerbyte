<?php
require_once "include/session_check.php";
require "include/coreDataconnect.php";

if (!isset($_SESSION['team_id'])) {
    header("Location: login.php");
    exit;
}

$event_id = isset($_POST['event_id'])
    ? (int)$_POST['event_id']
    : (isset($_SESSION['event_id']) ? (int)$_SESSION['event_id'] : 0);
$_SESSION['event_id'] = $event_id;
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
<style>
  :root {
    --primary-color: #4285f4;
    --success-color: #34a853;
    --bg-color: #f8f9fa;
    --border-color: #dadce0;
  }
  .input-group {
    display: flex;
    border: 2px solid var(--border-color);
    border-radius: 8px;
    overflow: hidden;
    transition: border-color 0.2s ease;
  }

  .input-group:focus-within {
    border-color: var(--primary-color);
  }

  #urlInput {
    flex: 1;
    border: none;
    padding: 8px 5px;
    font-size: 14px;
    color: #3c4043;
    outline: none;
    background: transparent;
  }

  #copyBtn {
    background-color: var(--primary-color);
    color: white;
    border: none;
    padding: 0 20px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s ease;
    min-width: 75px;
  }

  #copyBtn:hover {
    background-color: #1a73e8;
  }

  #copyBtn:active {
    transform: scale(0.96);
  }

  /* Success State Class */
  #copyBtn.copied {
    background-color: var(--success-color);
  }
</style>
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
                <form method="post" action="process_create_event.php">
                    <input type="hidden" name="event_id" value="<?= $event_id ?>">
                    <input type="hidden" name="launch_event" value="1">

                    <button class="btn primary">
                        <span class="material-symbols-outlined">rocket_launch</span>
                       Confirm & Create Event URL
                    </button>
                </form>
                
                 <form method="POST" action="add_modules.php">
                    <input type="hidden" name="event_id" value="<?= $event_id ?>">
                    <button class="btn secondary">
                        <span class="material-symbols-outlined">arrow_back</span>
                    Back to Edit
                    </button>
                </form>
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
            <div class="input-group">
                <input type="text" value="http://localhost/trainerbyte/<?= htmlspecialchars($event['event_url_code']); ?>" id="urlInput" readonly>
                <button onclick="copyUrl()" id="copyBtn">Copy</button>
            </div>
            <form method="POST" action="view_event.php">
                    <input type="hidden" name="event_id" value="<?= $event_id ?>">
                    <button class="launch-btn"> OK
                    </button>
     </div>
</div>

<script>
    const params = new URLSearchParams(window.location.search);

    if (params.get("success") == "1") {
        document.getElementById("successModal").classList.add("show");
    }

  function copyUrl() {
    const copyText = document.getElementById("urlInput");
    const button = document.getElementById("copyBtn");

    navigator.clipboard.writeText(copyText.value).then(() => {
      // Add the success class and change text
      button.innerText = "Copied!";
      button.classList.add("copied");
      
      setTimeout(() => {
        button.innerText = "Copy";
        button.classList.remove("copied");
      }, 2000);
    });
  }
</script>

<?php //require "layout/footer.php"; ?>
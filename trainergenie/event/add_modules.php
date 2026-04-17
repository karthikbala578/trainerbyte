<?php
session_start();
require "../include/dataconnect.php";

if (!isset($_SESSION['team_id'])) {
    header("Location: ../login.php");
    exit;
}

$event_id = intval($_GET['event_id'] ?? 0);
if ($event_id <= 0) {
    die("Invalid Event");
}

/* Page setup */
$pageTitle = "Add Modules to Event";
$pageCSS   = "/event/add_modules.css";
require "../layout/header.php";
/* Wizard Step */
$step = 2;
include "../components/wizard_steps.php";

/* Fetch event */
// $eventStmt = $conn->prepare("SELECT *
//     FROM tb_events ORDER BY event_id DESC LIMIT 1
//    ");
// //$eventStmt->bind_param("ii", $event_id, $_SESSION['team_id']);
// $eventStmt->execute();
// $event = $eventStmt->get_result()->fetch_assoc();
// print_r($event);
$eventStmt = $conn->prepare("
    SELECT event_name
    FROM tb_events
    WHERE event_id = ? AND event_team_pkid = ?
");
$eventStmt->bind_param("ii", $event_id, $_SESSION['team_id']);
$eventStmt->execute();
$event = $eventStmt->get_result()->fetch_assoc();
if (!$event) {
    die("Event not found");
}
$moduleSources = [

    //1 => ['table' => 'card_group', 'id' => 'cg_id', 'name' => 'cg_name'], //

    2 => ['table' => 'card_group', 'id' => 'cg_id', 'name' => 'cg_name'], // ByteGuess

    5 => ['table' => 'mg5_digisim', 'id' => 'di_id', 'name' => 'di_name'], // DigiSIm

    6 => ['table' => 'mg6_riskhop_matrix', 'id' => 'id', 'name' => 'game_name'], // RiskHop

    // 7 => ['table' => 'mg7_games', 'id' => 'id', 'name' => 'title'], // TrustTrap

    // 8 => ['table' => 'mg8_games', 'id' => 'id', 'name' => 'title'] // BoundyBid

];
/* ================= BUILD UNION SQL ================= */
$unions = [];
foreach ($moduleSources as $type => $cfg) {
    $unions[] = "
        SELECT {$cfg['id']} AS module_id,
               $type AS module_type,
               {$cfg['name']} AS module_name
        FROM {$cfg['table']}
    ";
}
$unionSql = implode(" UNION ALL ", $unions);
$assignedStmt = $conn->prepare("
    SELECT
        em.mod_game_id,
        em.mod_type,
        em.mod_order,
        u.module_name
    FROM tb_events_module em
    JOIN (
        $unionSql
    ) u
        ON em.mod_game_id = u.module_id
       AND em.mod_type = u.module_type
    WHERE em.mod_event_pkid = ?
      AND em.mod_status = 1
    ORDER BY em.mod_order ASC
");

$assignedStmt->bind_param("i", $event_id);
$assignedStmt->execute();
$assignedModules = $assignedStmt->get_result()->fetch_all(MYSQLI_ASSOC);
// $assignedStmt->bind_param("i", $event_id);
// $assignedStmt->execute();
// $assignedModules = $assignedStmt->get_result()->fetch_all(MYSQLI_ASSOC);

/* Get staged modules (session*/
$stagedModules = $_SESSION['event_modules'][$event_id] ?? [];

        $stmt = $conn->prepare("SELECT ex_id, ex_name, ex_type FROM tb_exercise_type ORDER BY ex_id ASC ");
        $stmt->execute();
        $result = $stmt->get_result();
?>
<div class="module-page">

    <!-- LEFT : EXERCISE LIBRARY -->
    <div class="library-panel">
        <h3>Exercise Library</h3>
        <div class="game-tabs">
            <?php 
            $first = true;
            while ($template = $result->fetch_assoc()) { ?>
                    <button class="tab <?php echo $first ? 'active' : ''; ?>" 
                        onclick="loadGames(<?php echo (int)$template['ex_type']; ?>, this)">
                        <?php echo htmlspecialchars($template['ex_name']); ?>
                    </button>
            <?php
            $first = false; } ?>

    </div>
        <input type="text"
            class="search-input"
            placeholder="Search exercises..."
            onkeyup="filterExercises(this.value)">

        <div id="exerciseList" class="panel-scroll">
            <!-- Loaded dynamically -->
        </div>
    </div>

    <!-- right -->
    <div class="sequence-panel">
        <h3>Event Sequence</h3>
        <p class="sub"><?php echo  htmlspecialchars($event['event_name']) ?></p>

        <div id="sequenceList" class="panel-scroll">

    <?php if (empty($assignedModules) && empty($stagedModules)): ?>
        <div class="empty-seq">No modules added yet</div>
    <?php endif; ?>

    <!-- already assigned modules (DB) -->
    <?php foreach ($assignedModules as $i => $m): ?>
        <div class="sequence-item locked">
            <span class="order"><?php echo $i + 1 ?></span>
            <span><?php echo htmlspecialchars($m['module_name']) ?></span>

           <button class="remove-btn"
                onclick="removeModule(
                    <?php echo (int)$m['mod_game_id'] ?>,
                    <?php echo (int)$m['mod_type'] ?>,
                    'db'
                )">
                –
            </button>
        </div>
    <?php endforeach; ?>

    <!-- staged modules (SESSION – ALL TYPES) -->
    <?php
    $startOrder = count($assignedModules);
    foreach ($stagedModules as $i => $m):
    ?>
        <div class="sequence-item">
            <span class="order"><?php echo $startOrder + $i + 1 ?></span>
            <span><?php echo htmlspecialchars($m['name']) ?></span>

            <button class="remove-btn"
                onclick="removeModule(
                    <?php echo $m['game_id'] ?>,
                    <?php echo $m['type'] ?>,
                    'session'
                )">
                –
            </button>
        </div>
    <?php endforeach; ?>

</div>


        

        <div class="actions">
            <a href="create_event.php?event_id=<?php echo  $event_id ?>" class="btn secondary">
                ← Previous
            </a>

            <!-- <a href="../myevent.php" class="btn secondary">Cancel</a> -->
            <a href="review_modules.php?event_id=<?php echo  $event_id ?>"
                class="btn primary">
                Review →
            </a>
        </div>
    </div>

</div>

<script>
    const EVENT_ID = <?php echo  $event_id ?>;
    
    /* tab */
    function setActiveTab(btn) {
        document.querySelectorAll(".tab").forEach(t => t.classList.remove("active"));
        btn.classList.add("active");
    }

    function loadGames(type, btn) {
        setActiveTab(btn);

        fetch("load_games.php?type=" + type)
            .then(res => res.text())
            .then(html => {
                document.getElementById("exerciseList").innerHTML = html;
            });
    }

    /* default load card games */
    window.onload = function() {
        loadGames(2, document.querySelector(".tab.active"));
    };

    function addToEvent(gameId, name, type) {
        fetch("stage_module.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify({
                    event_id: EVENT_ID,
                    game_id: gameId,
                    name: name,
                    type: type
                })
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === "success") {
                    location.reload();
                } else {
                    alert(data.message);
                }
            });
    }

    function filterExercises(text) {
        text = text.toLowerCase();

        document.querySelectorAll(".exercise-card").forEach(card => {
            const titleEl = card.querySelector("strong");
            const titleText = titleEl ? titleEl.innerText.toLowerCase() : "";

            card.style.display = titleText.includes(text) ? "flex" : "none";
        });
    }

    function removeModule(gameId, type, source) {

        if (!confirm("Remove this module from event?")) return;

        fetch("remove_module.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify({
                    event_id: EVENT_ID,
                    game_id: gameId,
                    type: type,
                    source: source
                })
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === "success") {
                    location.reload();
                } else {
                    alert(data.message);
                }
            });
    }
</script>

<?php require "../layout/footer.php"; ?>
<?php
session_start();
require "include/coreDataconnect.php";

if (!isset($_SESSION['team_id'])) {
    header("Location: login.php");
    exit;
}

$event_id = intval($_GET['event_id'] ?? 0);
if ($event_id <= 0) die("Invalid Event");

$pageTitle = "Add Modules to Event";
$pageCSS   = "/css_event/add_modules.css";
require "layout/tb_header.php";

$step = 2;

/* FETCH EVENT */
$stmt = $conn->prepare("
    SELECT event_name, event_playstatus 
    FROM tb_events 
    WHERE event_id = ? AND event_team_pkid = ?
");

$stmt->bind_param("ii", $event_id, $_SESSION['team_id']);
$stmt->execute();
$event = $stmt->get_result()->fetch_assoc();

/* TYPES */
$typeStmt = $conn->prepare("SELECT ex_id, ex_name, ex_type FROM tb_exercise_type");
$typeStmt->execute();
$result = $typeStmt->get_result();

/* ICON MAP */
$iconMap = [
    2 => "style",
    3 => "grid_view",
    4 => "view_list",
    5 => "memory",
    6 => "hub",
    7 => "psychology",
    8 => "emoji_events"
];



?>

<div class="stepper-div">
    <?php include "components/wizard_steps.php"; ?>
</div>

<div class="module-page">

    <div class="sidebar-panel">
        <h4>Classifications</h4>

        <div class="sidebar-tabs-wrapper">
            <div class="sidebar-tabs">
                <button class="side-tab active" data-type="0" onclick="loadGames(this)">
                    <span class="material-symbols-outlined">apps</span>
                    <span class="tab-text">
                        All Modules
                    </span>
                </button>

                <?php while ($row = $result->fetch_assoc()): ?>
                    <?php $icon = $iconMap[$row['ex_type']] ?? 'apps'; ?>

                    <button class="side-tab"
                        data-type="<?= $row['ex_type'] ?>"
                        onclick="loadGames(this)">
                        <span class="material-symbols-outlined"><?= $icon ?></span>
                        <span class="tab-text">
                            <?= htmlspecialchars($row['ex_name']) ?>
                        </span>
                    </button>
                <?php endwhile; ?>
            </div>
        </div>


    </div>

    <div class="library-panel">
        <h3>Available Modules</h3>
        <div id="exerciseList" class="grid-view"></div>
    </div>

    <div class="sequence-panel">
        <h3>Selected Modules</h3>

        <!-- STATUS MESSAGE DRAFT/OTHER -->
        <div id="reorderMessage"></div>

        <div id="sequenceList"></div>

        <div class="actions">
            <a href="create_event.php?event_id=<?= $event_id ?>" class="btn secondary">← Previous</a>
            <a href="review_modules.php?event_id=<?= $event_id ?>" class="btn primary">Next →</a>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>

<script>
    const EVENT_ID = <?= $event_id ?>;
    let currentOrder = [];

    function handleAdd(btn) {
        const id = btn.dataset.id;
        const name = btn.dataset.name;
        const type = btn.dataset.type;

        addToEvent(id, name, type, btn);
    }

    function setActiveTab(btn) {
        document.querySelectorAll(".side-tab").forEach(t => t.classList.remove("active"));
        btn.classList.add("active");
    }

    function loadGames(btn) {
        setActiveTab(btn);

        let type = btn.dataset.type || 0;

        fetch("load_games.php?type=" + type)
            .then(res => res.text())
            .then(html => {
                document.getElementById("exerciseList").innerHTML = html;
                syncAddedState();
            });
    }

    function addToEvent(id, name, type, btn) {

        const card = btn.closest(".exercise-card");

        if (card.classList.contains("added")) {

            fetch("remove_module.php", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json"
                    },
                    body: JSON.stringify({
                        event_id: EVENT_ID,
                        game_id: id,
                        type: type
                    })
                })
                .then(res => res.json())
                .then(data => {
                    if (data.status !== "removed") return;

                    card.classList.remove("added");
                    btn.innerHTML = '<span class="">ADD</span>';

                    loadSequence();
                });

            return;
        }

        fetch("stage_module.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify({
                    event_id: EVENT_ID,
                    game_id: id,
                    name: name,
                    type: type
                })
            })
            .then(res => res.json())
            .then(data => {

                if (data.status !== "success") {
                    console.error(data.message);
                    alert(data.message);
                    return;
                }

                card.classList.add("added");
                btn.innerHTML = '<span class="material-symbols-outlined">check</span>';

                loadSequence();
            });
    }


    const EVENT_STATUS = <?= $event['event_playstatus'] ?? 1 ?>; // for checking status draft

    function loadSequence() {
        fetch("get_sequence.php?event_id=" + EVENT_ID)
            .then(res => res.text())
            .then(html => {

                const sequenceContainer = document.getElementById("sequenceList");
                const msgBox = document.getElementById("reorderMessage");

                // Set modules
                sequenceContainer.innerHTML = html;

                // Clear previous message
                msgBox.innerHTML = "";

                // Destroy previous sortable (important)
                if (sequenceContainer.sortableInstance) {
                    sequenceContainer.sortableInstance.destroy();
                }

                // DRAFT MODE
                if (EVENT_STATUS == 1) {

                    // Show green info
                    msgBox.innerHTML = `
                    <div class="reorder-info success">
                        <span class="material-symbols-outlined">drag_indicator</span>
                        Drag the icon to reorder modules
                    </div>
                `;

                    // Enable drag
                    sequenceContainer.sortableInstance = new Sortable(sequenceContainer, {
                        animation: 150,
                        handle: ".drag",
                        onEnd: function() {

                            currentOrder = [];

                            document.querySelectorAll(".sequence-item").forEach((item, index) => {
                                currentOrder.push({
                                    game_id: item.dataset.id,
                                    type: item.dataset.type,
                                    order: index + 1
                                });
                            });

                            fetch("reorder_modules.php", {
                                    method: "POST",
                                    headers: {
                                        "Content-Type": "application/json"
                                    },
                                    body: JSON.stringify({
                                        event_id: EVENT_ID,
                                        modules: currentOrder
                                    })
                                })
                                .then(res => res.json())
                                .then(data => {
                                    console.log("Reorder response:", data);
                                });
                        }
                    });

                }
                // NON-DRAFT MODE
                else {

                    // Show warning
                    msgBox.innerHTML = `
                    <div class="reorder-info warning">
                        <span class="material-symbols-outlined">lock</span>
                        Change event status to <b>Draft</b> to reorder modules
                    </div>
                `;

                    // Disable drag UI
                    document.querySelectorAll(".drag").forEach(d => {
                        d.style.pointerEvents = "none";
                        d.style.opacity = "0.4";
                        d.style.cursor = "not-allowed";
                    });
                }
            });
    }

    function removeFromSequence(id, type) {
        fetch("remove_module.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify({
                    event_id: EVENT_ID,
                    game_id: id,
                    type: type
                })
            })
            .then(res => res.json())
            .then(() => {
                loadSequence();
                syncAddedState();
            });
    }

    function syncAddedState() {
        fetch("get_sequence_ids.php?event_id=" + EVENT_ID)
            .then(res => res.json())
            .then(ids => {

                document.querySelectorAll(".exercise-card").forEach(card => {

                    let btn = card.querySelector("button");
                    if (!btn) return;

                    let id = btn.dataset.id;
                    if (!id) return;

                    if (ids.includes(parseInt(id))) {
                        card.classList.add("added");
                        btn.innerHTML = '<span class="material-symbols-outlined">check</span>';
                    } else {
                        card.classList.remove("added");
                        btn.innerHTML = '<span class="">ADD</span>';
                    }
                });
            });
    }

    window.onload = () => {
        loadGames(document.querySelector(".side-tab.active"));
        loadSequence();
    };
</script>

<?php //require "../layout/footer.php"; ?>
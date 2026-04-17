<?php
// 1. CONFIGURATION & INITIALIZATION

error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
include("../include/coreDataconnect.php");

// Get and validate SIM ID
$sim_id = isset($_GET['game_id']) ? intval($_GET['game_id']) : 0;
if ($sim_id <= 0) {
    die("Invalid Simulation ID");
}


// 2. DATA FETCHING - USER GAME SUMMARY
$stmt = $conn->prepare("
    SELECT game_summary, game_status
    FROM tb_event_user_score
    WHERE user_id = ? 
    AND event_id = ? 
    AND mod_game_id = ? 
    AND mod_game_status = 1
");
$stmt->bind_param("iii", $_SESSION['user_id'], $_SESSION['event_id'], $sim_id);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();

$gameSummary = null;
$isCompleted = false;

if ($row) {
    if (!empty($row['game_summary'])) {
        $gameSummary = json_decode($row['game_summary'], true);
    }
    if ($row['game_status'] === 'completed') {
        $isCompleted = true;
    }
}
$stmt->close();

// 4. DATA FETCHING - SCORE TYPE & BUCKETS
$stmt = $conn->prepare("
    SELECT d.di_name,
     d.di_scoretype_id, 
     d.di_priority_point, 
     d.di_priority_manual, 
     d.di_scoring_basis,
     d.di_min_select,
     d.di_exact_select,
     st.st_desc, 
     st.st_display_name
    FROM mg5_digisim d
    JOIN mg5_scoretype st ON d.di_scoretype_id = st.st_id
    WHERE d.di_id = ?
");
$stmt->bind_param("i", $sim_id);
$stmt->execute();
$stmt->bind_result($simName, $scoreTypeId, $priorityType, $priorityManual,$scoringBasis,$minSelect,$exactSelect, $scoreTypeDesc, $scoreTypeDisplayname);
$stmt->fetch();
$stmt->close();

// 3. DATA FETCHING - RESPONSES & SCORES
$stmt = $conn->prepare("
    SELECT 
        dr.dr_id,
        dr.dr_order,
        dr.dr_tasks,
        stv.stv_value
    FROM mg5_digisim_response dr
    LEFT JOIN mg5_scoretype_value stv
        ON dr.dr_score_pkid = stv.stv_id
    WHERE dr.dr_digisim_pkid = ?
    ORDER BY RAND()
");
$stmt->bind_param("i", $sim_id);
$stmt->execute();
$result = $stmt->get_result();

$responses = [];
$totalScore = 0;

while ($row = $result->fetch_assoc()) {
    $row['score'] = intval($row['stv_value'] ?? 0);
    $responses[] = $row;

    // ONLY calculate sum if type = 1
    if ($priorityType == 1) {
        $totalScore += $row['score'];
    }
}

// IF manual scoring
if ($priorityType == 2) {
    $totalScore = intval($priorityManual);
}
$stmt->close();



$buckets = [];
$stmt = $conn->prepare("
    SELECT 
        stv_id,
        stv_name,
        stv_color,
        stv_value
    FROM mg5_scoretype_value
    WHERE stv_scoretype_pkid = ?
    ORDER BY stv_id ASC
");
$stmt->bind_param("i", $scoreTypeId);
$stmt->execute();
$res = $stmt->get_result();

while ($row = $res->fetch_assoc()) {
    $buckets[] = $row;
}
$stmt->close();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Priority Ranker - Digisim</title>
    <link rel="stylesheet" href="/assets/fontawesome/css/all.min.css">
    <link rel="stylesheet" type="text/css" href="css_site/digisim_finalDecision.css">
    <script src="https://kit.fontawesome.com/84b78c6f4c.js" crossorigin="anonymous"></script>
</head>
<body>

<!-- TOP BAR WITH SIDENAV                                                     -->
<div class="topbar">
    <?php include("sidenav.php"); ?>
    <div class="logo"><?php echo htmlspecialchars($simName ?? ''); ?></div>
    <div class="actions">
        <!-- <button class="btn primary" id="resetBtn">
            <i class="fa-solid fa-rotate-right"></i> Reset
        </button> -->
        <!-- <?php if ($isCompleted): ?>
            <button class="btn primary" onclick="goToFinalResult()">
                <i class="fa-solid fa-eye"></i> View Results
            </button>
        <?php endif; ?> -->
    </div>
</div>

<!-- PRIORITY ZONE HEADER                                                     -->

<!-- <div class="pz-header">
    <div class="pz-left">
        <h2>Simulation Responses</h2>
        <p>Click a bucket to expand, then drag tasks between priorities.</p>
    </div>
</div> -->

<!-- MAIN LAYOUT                                                              -->
<div class="main-layout">
    
    <!-- LEFT PANEL: CAROUSEL + BUCKETS + EXPANDED VIEW -->
    <div class="left-panel">
        
        <!-- AVAILABLE CARDS (Carousel) -->
        <!-- <div class="dock" id="availableItems">
            <div class="carousel-wrapper" id="carousel-wrapper">
                <button class="arrow left" id="leftArrow">❮</button>
                <div class="carousel" id="dock">
                    <?php if (empty($responses)): ?>
                        <p style="padding: 20px; color: #64748b;">No responses found.</p>
                    <?php else: ?>
                        <?php foreach ($responses as $r): ?>
                            <div class="card"
                                 draggable="true"
                                 data-id="<?php echo $r['dr_id']; ?>"
                                 data-score="<?php echo $r['score']; ?>">
                                <div class="card-content">
                                    <?php echo htmlspecialchars($r['dr_tasks']); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                <button class="arrow right" id="rightArrow">❯</button>
            </div>
        </div> -->
        <div id="unassignedTasksContainer">
            <div class="dock-header">
                <h2>Unassigned Tasks</h2>
                <div class="carousel-controls">
                    <button class="arrow-round" id="leftArrow">❮</button>
                    <div class="counter-badge" style="margin-top: 13px;">
                        <span id="availableCount"><?php echo count($responses); ?></span>
                        <small>LEFT</small>
                    </div>
                    <button class="arrow-round" id="rightArrow">❯</button>
                </div>
            </div>

            <div class="dock" id="availableItems">
                <div class="carousel-wrapper" id="carousel-wrapper">
                    <div class="carousel" id="dock">
                        <?php if (empty($responses)): ?>
                            <p style="padding: 20px; color: #64748b;">No responses found.</p>
                        <?php else: ?>
                            <?php foreach ($responses as $r): ?>
                                <div class="card" draggable="true" data-id="<?php echo $r['dr_id']; ?>" data-score="<?php echo $r['score']; ?>">
                                    <div class="card-content">
                                        <?php echo htmlspecialchars($r['dr_tasks']); ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- BUCKETS (Drop Zones) -->
        <div class="bucket-container">
            <?php foreach ($buckets as $b):
                $hex = $b['stv_color'];
                list($r, $g, $b_val) = sscanf($hex, "#%02x%02x%02x");
            ?>
                <div class="bucket-box drop-zone"
                     id="zone-<?php echo $b['stv_id']; ?>"
                     data-id="<?php echo $b['stv_id']; ?>"
                     data-name="<?php echo strtolower($b['stv_name']); ?>"
                     data-score="<?php echo $b['stv_value']; ?>"
                     style="background-color: rgba(<?php echo "$r,$g,$b_val"; ?>, 0.1); border: 2px solid <?php echo $hex; ?>;"
                     onclick="showExpandedView('<?php echo $b['stv_id']; ?>')">
                    
                    <span class="bucket-name" style="color: <?php echo $hex; ?>;">
                        <?php echo strtoupper($b['stv_name']); ?>
                    </span>
                    <div class="bucket-count" id="count-<?php echo $b['stv_id']; ?>" style="color: <?php echo $hex; ?>;">
                        Drag & drop cards here
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- EXPANDED VIEW (Hidden by default) -->
        <div id="activePriorityView" class="expanded-view" style="display:none;">
            <div class="expanded-container">
                <div class="carousel-wrapper">
                    <button class="arrow left" id="extLeftArrow">❮</button>
                    <div class="carousel" id="detailContent"></div>
                    <button class="arrow right" id="extRightArrow">❯</button>
                </div>
            </div>
        </div>

    </div>

    <!-- RIGHT PANEL: SCORE + CHAT -->
    <div class="right-panel">
        
        <!-- SCORE PANEL -->
        <div class="score-panel">
            <!-- <div style="padding-bottom: 10px; border-bottom: 1px solid #eee;display: flex; flex-direction: column;justify-content: center;align-items: center;">

                <span style="font-size: 11px; font-weight: 700; color: #94a3b8; letter-spacing: 1px;"><?php echo htmlspecialchars($scoreTypeDisplayname ?? ''); ?></span>


            </div>
            
            <div class="bucket-points" style="display: flex; justify-content: center;">
                <div class="count-points" id="scoreBox" style="width: 55px; height: 55px; border-radius: 10%; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 34px; color: #19a362; border: 1px solid;">
                    <?php echo $totalScore; ?>
                </div>
            </div>
            <div class="text" style="display: flex; justify-content: center;">
                <?php echo htmlspecialchars($scoreTypeDesc ?? ''); ?>
            </div> -->
            <div class="priority-card">
                <div class="priority-header">
                    <i class="fa-solid fa-chart-simple priority-icon"></i>
                    <span class="priority-label"><?php echo htmlspecialchars($scoreTypeDisplayname ?? 'PRIORITY SCALE'); ?></span>
                </div>

                <div class="priority-main">
                    <span id="scoreBox" class="priority-number"><?php echo $totalScore; ?></span>
                    
                </div>
            </div>
            <span class="priority-subtext"><?php echo htmlspecialchars($scoreTypeDesc ?? 'TASKS LEFT'); ?></span>
            
            <!-- Notification Bell -->
            <div class="notification-wrapper">
                <div class="notification-btn" onclick="goToMessages()">
                    <img src="images/bell-icons.png" width="25%" border="0" alt="Notifications">
                    <span id="notif-count" class="notification-dot" style="display:none;">0</span>
                </div>
                <div class="bellmsgtxt" onclick="goToMessages()" style="display: flex; justify-content: center;">Message Center</div>
            </div>
        </div>

        <!-- AI CHAT WIDGET -->
        <div class="ai-chat-widget">
            <div id="aiBubble" class="chat-bubble">
                <span class="msgclo" onClick="closemsg();" >Hide</span>
                <h4 id="aiHeader">We're Online!</h4>
                <p id="aiMsg">How may I help you today?</p>
            </div>
            <div class="bot-button" id="botIcon">
                <div class="online-status"></div>
                <img src="https://img.icons8.com/ios-filled/100/ffffff/bot.png" alt="AI Bot">
            </div>
        </div>

    </div>
</div>

<!-- FOOTER: SUBMIT BUTTON                                                    -->
<div class="footer-wrapper">
    <div class="footer-action">
        <div class="arrow-nav">
            <div class="arrow-btn left" onclick="goPrev()">PREVIOUS</div>
            <button class="arrow-btn primary" id="submitBtn">Submit</button>
        </div>
    </div>
</div>

<!-- RESET MODAL                                                              -->
<div id="resetModal" class="modal-overlay" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <i class="fa-solid fa-triangle-exclamation"></i>
            <h3>Reset Progress?</h3>
        </div>
        <p>This will move all tasks back to the dock and clear your current priority scores. This action cannot be undone.</p>
        <div class="modal-actions">
            <button class="btn-secondary" onclick="closeResetModal()">Cancel</button>
            <button class="btn-danger" id="confirmResetBtn">Confirm Reset</button>
        </div>
    </div>
</div>

<!-- JAVASCRIPT - BUCKET-TO-BUCKET DRAG + SCORE LOGIC                         -->
<script>
function goPrev(){
    window.location.href = 'digisim_messageCenter.php?game_id=<?php echo $sim_id ?>';
}
    // ===== GLOBAL STATE =====
    const triggeredTasks = new Set();
    const TOTAL_POINTS = <?php echo $totalScore; ?>;
    const savedSummary = <?php echo json_encode($gameSummary); ?>;
    const isCompleted = <?php echo $isCompleted ? 'true' : 'false'; ?>;

    // for scoring basis
    const SCORING_BASIS = <?php echo (int)$scoringBasis; ?>;
    const EXACT_SELECT = <?php echo (int)$exactSelect; ?>;
    const MIN_SELECT   = <?php echo (int)$minSelect; ?>;
    const TOTAL_TASKS  = <?php echo count($responses); ?>;
    const PRIORITY_TYPE = <?php echo (int)$priorityType; ?>;

    function goToFinalResult() {
        window.location.href = "digisim_finalResult.php?game_id=<?php echo $sim_id ?>";
    }

    // ===== NOTIFICATION HELPER =====
    function showNotification(channel) {
        const box = document.createElement("div");
        box.className = "notify";
        box.innerText = "New message in " + channel;
        document.body.appendChild(box);
        setTimeout(() => { box.remove(); }, 3000);
    }

    // ===== NAVIGATION HELPER =====
    function goToMessages() {
        window.location.href = "digisim_messageCenter.php?game_id=<?php echo $sim_id ?>";
    }

    // ===== UI UPDATERS =====
    function updateCounts() {
        document.querySelectorAll(".drop-zone").forEach(zone => {
            const id = zone.dataset.id;
            const count = zone.querySelectorAll(".card").length;
            const countDisplay = document.getElementById("count-" + id);
            if (countDisplay) {
                // countDisplay.innerText = count;
                if (count === 0) {
                    // Display the helper text from your image
                    countDisplay.innerText = "Drag & drop cards here";
                    // countDisplay.style.color = "inherit"; // reset to original color
                    countDisplay.style.fontSize = '12px';
                    countDisplay.classList.remove('count-active');
                } else {
                    // Display the number count
                    countDisplay.innerText = count;
                    countDisplay.style.fontSize = '18px';
                    countDisplay.classList.add('count-active');
                }
            }
        });
    }

    //  SCORE CALCULATION: Recalculates remaining score based on bucket assignments 
    function updateScore() {
        //  NO SCORING MODE
        if (PRIORITY_TYPE === 0) {
            document.getElementById("scoreBox").innerText = "-";
            return 999999; // allow unlimited
        }
        let usedScore = 0;
        document.querySelectorAll(".drop-zone .card").forEach(card => {
            const parentZone = card.closest(".drop-zone");
            if (!parentZone) return;
            const bucketScore = parseInt(parentZone.dataset.score) || 0;
            usedScore += bucketScore;
        });
        let remaining = TOTAL_POINTS - usedScore;
        if (remaining < 0) remaining = 0;
        document.getElementById("scoreBox").innerText = remaining;
        return remaining;
    }

    function updateDockVisibility() {
        const mainContainer = document.getElementById('unassignedTasksContainer');
        const dockContainer = document.getElementById('availableItems');
        const dockScrollArea = document.getElementById('dock');
        const availableCountDisplay = document.getElementById('availableCount');

        if (!mainContainer || !dockScrollArea) return;

        const cardsInDock = dockScrollArea.querySelectorAll('.card').length;

        if (availableCountDisplay) {
            availableCountDisplay.innerText = cardsInDock;
        }
            
        //  Always show dock
        dockContainer.style.display = 'flex';

        //  Show message if empty
        let emptyMsg = dockScrollArea.querySelector('.empty-msg');

        if (cardsInDock === 0) {
            mainContainer.style.display = 'none';
            // if (!emptyMsg) {
            //     emptyMsg = document.createElement('div');
            //     emptyMsg.className = 'empty-msg';
            //     emptyMsg.innerText = 'No responses available';
            //     dockScrollArea.appendChild(emptyMsg);
            // }
            
        } else {
            mainContainer.style.display = 'block';
            if (typeof updateCarouselArrows === "function") {
                updateCarouselArrows();
            }
            // if (emptyMsg) emptyMsg.remove();
        }
    }

    // ===== DRAG INIT FOR CARDS =====
    function initCardEvents(card) {
        card.setAttribute("draggable", "true");
        
        card.addEventListener("dragstart", (e) => {

            /// block it already submitted
            if (isCompleted) {
                e.preventDefault();
                return;
            }
            // // 1. Close the extended/expanded view immediately
            // const expandedView = document.getElementById('activePriorityView');
            // if (expandedView) {
            //     expandedView.style.display = 'none';
            //     expandedView.setAttribute('data-active-id', '');
            // }

            // // 2. Remove highlights from all buckets
            // document.querySelectorAll('.bucket-box').forEach(box => {
            //     box.classList.remove('active-bucket');
            //     box.style.removeProperty('--bucket-glow');
            // });

            card.classList.add("dragging");
            e.dataTransfer.setData("text/plain", card.dataset.id);

            //  ADD THIS
            e.dataTransfer.effectAllowed = "move";

            //  Fix ghost disappearing issue
            e.dataTransfer.setDragImage(card, 10, 10);
            
            // Only remove highlight, DO NOT hide expanded view
            document.querySelectorAll('.bucket-box').forEach(box => {
                box.classList.remove('active-bucket');
                box.style.removeProperty('--bucket-glow');
            });
            
        });

        card.addEventListener("dragend", () => {
            card.classList.remove("dragging");
        });
    }

    //  UNIVERSAL DROP HANDLER - BUCKET-TO-BUCKET + SCORE ADJUSTMENT 
    function handleDrop(e, targetContainer) {
        e.preventDefault();
        //  BLOCK EVERYTHING
        if (isCompleted) {
            return;
        }
        const cardId = e.dataTransfer.getData("text/plain");
        
        // Find the ORIGINAL card (not the clone in expanded view)
        const realCard = document.querySelector(`.card[data-id="${cardId}"]:not(.in-expanded-view)`);

        // Find dragged element (could be clone or original)
        const draggedElement = document.querySelector(`.card.dragging`);
        if (!realCard) return;

        
        // Get source and target bucket info for score calculation
        const sourceZone = realCard.closest(".drop-zone");
        const targetZone = targetContainer.classList.contains('drop-zone') ? targetContainer : null;
        const isSameBucket = sourceZone && targetZone && sourceZone.dataset.id === targetZone.dataset.id;
        
        // Calculate score difference if moving between buckets
        let scoreChange = 0;
        if (sourceZone && targetZone) {
            // Moving from one bucket to another
            const sourceScore = parseInt(sourceZone.dataset.score) || 0;
            const targetScore = parseInt(targetZone.dataset.score) || 0;
            scoreChange = sourceScore - targetScore; // Positive = freeing score, Negative = using more
        } else if (!sourceZone && targetZone) {
            // Moving from dock to bucket - check remaining score
            const targetScore = parseInt(targetZone.dataset.score) || 0;
            const currentRemaining = parseInt(document.getElementById("scoreBox").innerText);
            if (targetScore > currentRemaining) {
                alert("Not enough score remaining to place this task here!");
                return;
            }
        }
        // If moving to dock, score is freed automatically by updateScore()
        
        // Move the card
        targetContainer.appendChild(realCard);
        // Only remove clone if moving to DIFFERENT bucket
        if (
            draggedElement &&
            draggedElement.classList.contains("in-expanded-view") &&
            !isSameBucket
        ) {
            draggedElement.remove();
        }
        
        // Update UI
        updateCounts();
        updateScore();
        updateDockVisibility();
        
        // Trigger notification API only on first drop into any bucket
        if (targetZone && !triggeredTasks.has(cardId)) {
            triggeredTasks.add(cardId);
            triggerNotification(cardId);
        }
        
        // Save state
        saveToDB();
        const activeId = document.getElementById('activePriorityView').getAttribute('data-active-id');
        if (activeId) {
            showExpandedView(activeId); // refresh
        }
        toggleSubmitButton();
        updateCarouselArrows();
    }

    // ===== TRIGGER NOTIFICATION API =====
    function triggerNotification(taskId) {
        const dropCount = document.querySelectorAll(".drop-zone .card").length;
        fetch("include_site/digisim_trigger.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({
                sim_id: <?php echo $sim_id ?>,
                task_id: taskId,
                count: dropCount
            })
        })
        .then(res => res.json())
        .then(data => {
            if (data.status === "success") {
                data.messages.forEach(msg => showBotNotification(msg.ch_level));
            }
        });
    }

    // ===== SHOW BOT NOTIFICATION =====
    function showBotNotification(channel) {
        const bubble = document.getElementById("aiBubble");
        const header = document.getElementById("aiHeader");
        const msg = document.getElementById("aiMsg");
        header.innerText = "New Notification";
        msg.innerText = "New message received in " + channel;
        bubble.classList.add("show");
        // to  show the notification count on bell icon
        // notification logic
        const notification = document.getElementById('notif-count');
        let count = parseInt(notification.innerText) || 0;

        count++;
        notification.innerText = count;

        //  show only if > 0
        if (count > 0) {
            notification.style.display = "flex";
        } else {
            notification.style.display = "none";
        }
        setTimeout(() => { bubble.classList.remove("show"); }, 4000);
    }
    function closemsg(){
        const bubble = document.getElementById('aiBubble');	
        // $(bubble).fadeOut();
        bubble.classList.remove('show');
    }

    // EXPANDED VIEW: Click bucket to expand, drag from there 
    window.showExpandedView = function(bucketId) {
        const viewArea = document.getElementById('activePriorityView');
        const contentArea = document.getElementById('detailContent');
        const sourceZone = document.getElementById('zone-' + bucketId);
        
        // Reset all buckets
        document.querySelectorAll('.bucket-box').forEach(box => {
            box.classList.remove('active-bucket');
            box.style.removeProperty('--bucket-glow');
        });
        
        if (!sourceZone) return;
        
        const bucketColor = sourceZone.querySelector('.bucket-name').style.color;
        sourceZone.classList.add('active-bucket');
        sourceZone.style.setProperty('--bucket-glow', bucketColor);
        
        // Show expanded view
        viewArea.style.display = 'flex';
        viewArea.style.justifyContent = 'center';
        viewArea.setAttribute('data-active-id', bucketId);
        
        // Populate with CLONES of cards (so originals stay in bucket)
        contentArea.innerHTML = '';
        const hiddenCards = sourceZone.querySelectorAll('.card');
        
        if (hiddenCards.length === 0) {
            contentArea.innerHTML = '<div style="padding:20px; color:#64748b; width:100%; text-align:center;">No tasks assigned.</div>';
        } else {
            hiddenCards.forEach(card => {
                const clone = card.cloneNode(true);
                clone.dataset.id = card.dataset.id;
                clone.classList.add('in-expanded-view');
                clone.style.display = 'block';
                clone.style.borderLeft = `4px solid ${bucketColor}`;
                clone.style.background = `#ebebeb`;
                // Make clone draggable with same ID reference
                // FIX HERE
                if (!isCompleted) {
                    initCardEvents(clone);   // allow drag
                } else {
                    clone.setAttribute("draggable", "false"); // block drag
                }
                contentArea.appendChild(clone);
            });
        }
        viewArea.scrollIntoView({ behavior: 'smooth' });
        setTimeout(() => {
            updateExpandedArrows();
        }, 100);
    };

    // ===== SAVE TO DB =====
    function saveToDB() {
        const data = { game_id: <?php echo $sim_id; ?> };
        document.querySelectorAll(".drop-zone").forEach(zone => {
            const bucketName = zone.dataset.name;
            data[bucketName] = [];
            zone.querySelectorAll(".card").forEach(card => {
                data[bucketName].push({
                    task_id: card.dataset.id,
                    score: card.dataset.score
                });
            });
        });
        fetch("./include_site/save_temp_state.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify(data)
        });
    }
    // for the submit button to only be active when at least 1 card is placed in a bucket
    function toggleSubmitButton() {
        const totalPlaced = document.querySelectorAll(".drop-zone .card").length;
        const submitBtn = document.getElementById("submitBtn");

        if (!submitBtn) return;

        if (totalPlaced === 0) {
            submitBtn.disabled = true;
            submitBtn.style.opacity = "0.5";
            submitBtn.style.cursor = "not-allowed";
        } else {
            submitBtn.disabled = false;
            submitBtn.style.opacity = "1";
            submitBtn.style.cursor = "pointer";
        }
    }

    // ===== DOM READY =====
    document.addEventListener("DOMContentLoaded", function() {
        
        // Carousel arrows for dock
        const dockCarousel = document.getElementById('dock');
        const leftBtn = document.getElementById('leftArrow');
        const rightBtn = document.getElementById('rightArrow');
        if (dockCarousel && leftBtn && rightBtn) {
            rightBtn.onclick = () => dockCarousel.scrollBy({ left: 300, behavior: 'smooth' });
            leftBtn.onclick = () => dockCarousel.scrollBy({ left: -300, behavior: 'smooth' });
        }
        
        // Carousel arrows for expanded view
        // const expContent = document.getElementById('detailContent');
        // document.getElementById('expandedRightArrow').onclick = () => expContent.scrollBy({ left: 250, behavior: 'smooth' });
        // document.getElementById('expandedLeftArrow').onclick = () => expContent.scrollBy({ left: -250, behavior: 'smooth' });
        
        // Initialize all original cards
        document.querySelectorAll(".card").forEach(card => initCardEvents(card));
        
        // Attach drop listeners to ALL buckets
        document.querySelectorAll(".drop-zone").forEach(zone => {
            zone.addEventListener("dragover", e => {
                e.preventDefault();
                const bucketColor = zone.querySelector('.bucket-name').style.color;
                // Optional: visual feedback during drag
                zone.style.borderColor = bucketColor;
            });
            zone.addEventListener("dragleave", e => {
                // Reset border
                const bucketId = zone.dataset.id;
                const bucket = document.getElementById('zone-' + bucketId);
                if (bucket) {
                    const hex = bucket.style.borderColor || '#ccc';
                    zone.style.borderColor = hex;
                }
            });
            zone.addEventListener("drop", e => handleDrop(e, zone));
        });
        
        // Attach drop listener to dock (return cards)
        const dock = document.getElementById("dock");
        if(dock) {
            dock.addEventListener("dragover", e => e.preventDefault());
            dock.addEventListener("drop", e => handleDrop(e, dock));
        }
        
        // Restore saved state
        if (savedSummary) {
            Object.keys(savedSummary).forEach(bucket => {
                if (bucket === "game_id") return;
                const zone = document.querySelector(`.drop-zone[data-name="${bucket}"]`);
                if (!zone) return;
                savedSummary[bucket].forEach(item => {
                    const card = document.querySelector(`.card[data-id="${item.task_id}"]`);
                    if (card) zone.appendChild(card);
                });
            });
            updateCounts();
            updateScore();
            toggleSubmitButton();
        }
        
        // Lock if completed
        if (isCompleted) {
            document.querySelectorAll(".card").forEach(c => c.draggable = false);
            const sb = document.getElementById("submitBtn");
            // if(sb) { sb.disabled = true; sb.innerText = "Already Submitted"; }
            if(sb) { 
                sb.disabled = true; sb.innerText = "View Results"; 
                // sb.innerHTML = '<i class="fa-solid fa-eye" style="margin-right: 8px;"></i> View Results';
        
                // // 3. Keep it disabled if you don't want them to click yet
                // sb.disabled = true;
            }
        }
        
        updateDockVisibility();
        toggleSubmitButton();

        // Initial check
        updateCarouselArrows();
        
        // Check again if the window is resized
        window.addEventListener('resize', updateCarouselArrows);


        
    });
    document.addEventListener('click', function (e) {
        const expanded = document.getElementById('activePriorityView');
        const bucket = e.target.closest('.bucket-box');
        const expandedArea = e.target.closest('#activePriorityView');

        // if click is NOT inside bucket or expanded view → close it
        if (!bucket && !expandedArea) {
            hideExpandedView();
        }
    });
    function hideExpandedView() {
        const view = document.getElementById('activePriorityView');

        if (view) {
            view.style.display = 'none';
            view.setAttribute('data-active-id', '');
        }

        // remove active bucket highlight
        document.querySelectorAll('.bucket-box').forEach(box => {
            box.classList.remove('active-bucket');
            box.style.removeProperty('--bucket-glow');
        });
    }

    // ===== RESET MODAL =====
    function openResetModal() {
        document.getElementById('resetModal').style.display = 'flex';
    }
    function closeResetModal() {
        document.getElementById('resetModal').style.display = 'none';
    }

    const resetBtn = document.getElementById("resetBtn");
    if (resetBtn) {
        resetBtn.addEventListener("click", openResetModal);
    }
    
    document.getElementById("confirmResetBtn").addEventListener("click", function() {
        const dock = document.getElementById("dock");
        const expandedView = document.getElementById("activePriorityView");
        
        // Move all cards back to dock
        document.querySelectorAll(".card:not(.in-expanded-view)").forEach(card => {
            dock.appendChild(card);
        });
        
        // Reset bucket visuals
        document.querySelectorAll('.bucket-box').forEach(box => {
            box.classList.remove('active-bucket');
        });
        
        // Hide expanded view
        if (expandedView) {
            expandedView.style.display = "none";
            expandedView.setAttribute('data-active-id', '');
        }
        
        closeResetModal();
        updateCounts();
        updateScore();
        updateDockVisibility();
        saveToDB();
        toggleSubmitButton();
        updateCarouselArrows();
    });
    
    window.onclick = function(event) {
        const modal = document.getElementById('resetModal');
        if (event.target == modal) {
            closeResetModal();
        }
    }

    // ===== FINAL SUBMISSION =====
    document.getElementById("submitBtn").addEventListener("click", function() {

        //  CHECK if any response is placed
        const totalPlaced = document.querySelectorAll(".drop-zone .card").length;

        /*  VALIDATION  */

        // 1️ALL
        if (SCORING_BASIS === 1) {
            if (totalPlaced !== TOTAL_TASKS) {
                alert("You must assign ALL responses before submitting.");
                return;
            }
        }

        // 2️ EXACT
        if (SCORING_BASIS === 2) {
            if (totalPlaced !== EXACT_SELECT) {
                alert("You must assign exactly " + EXACT_SELECT + " responses.");
                return;
            }
        }

        // 3️ MINIMUM
        if (SCORING_BASIS === 3) {
            if (totalPlaced < MIN_SELECT) {
                alert("You must assign at least " + MIN_SELECT + " responses.");
                return;
            }
        }
        const rankingData = { game_id: <?php echo $sim_id; ?> };

        document.querySelectorAll(".drop-zone").forEach(zone => {
            const bucketName = zone.dataset.name;
            rankingData[bucketName] = [];
            zone.querySelectorAll(".card").forEach(card => {
                rankingData[bucketName].push({
                    task_id: card.dataset.id,
                    score: card.dataset.score
                });
            });
        });

        fetch("./include_site/save_priority.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify(rankingData)
        })
        .then(res => res.json())
        .then(data => {
            if (data.status === "success") {
                window.location.href = "digisim_finalResult.php?game_id=<?php echo $sim_id ?>";
            } else {
                alert("Error: " + data.msg);
            }
        })
        .catch(err => {
            alert("Fetch error: " + err);
        });
    });

    // ===== SIDEBAR TOGGLE =====
    function toggleSidebar() {
        const sidebar = document.getElementById('digisim-sidebar');
        const overlay = document.getElementById('digisim-sidebarOverlay');
        if(sidebar && overlay) {
            sidebar.classList.toggle('active');
            overlay.classList.toggle('active');
        }
    }

    function updateCarouselArrows() {
    const dock = document.getElementById('dock');
    const leftBtn = document.getElementById('leftArrow');
    const rightBtn = document.getElementById('rightArrow');

    if (!dock || !leftBtn || !rightBtn) return;

    // Check if the content is wider than the visible area
    const isScrollable = dock.scrollWidth > dock.clientWidth;

    if (isScrollable) {
        leftBtn.classList.add('visible');
        rightBtn.classList.add('visible');
    } else {
        leftBtn.classList.remove('visible');
        rightBtn.classList.remove('visible');
    }
}

function updateExpandedArrows() {
    const list = document.getElementById('detailContent');
    const leftBtn = document.getElementById('extLeftArrow');
    const rightBtn = document.getElementById('extRightArrow');

    if (!list || !leftBtn || !rightBtn) return;

    // Check if the actual content is wider than the visible area
    const isScrollable = list.scrollWidth > list.clientWidth;

    if (isScrollable) {
        leftBtn.style.display = 'flex';
        rightBtn.style.display = 'flex';
    } else {
        leftBtn.style.display = 'none';
        rightBtn.style.display = 'none';
    }
}

// Click events for the expanded arrows
document.getElementById('extLeftArrow').onclick = () => {
    document.getElementById('detailContent').scrollBy({ left: -300, behavior: 'smooth' });
};

document.getElementById('extRightArrow').onclick = () => {
    document.getElementById('detailContent').scrollBy({ left: 300, behavior: 'smooth' });
};
</script>
</body>
</html>
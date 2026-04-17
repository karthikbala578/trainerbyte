<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
include("../include/dataconnect.php");

// Get and validate SIM ID
$sim_id = isset($_GET['game_id']) ? intval($_GET['game_id']) : 0;
if ($sim_id <= 0) {
    die("Invalid Simulation ID");
}

//DATA FETCHING - USER GAME SUMMARY
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

// DATA FETCHING - RESPONSES & SCORES
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
    $totalScore += $row['score'];
    $responses[] = $row;
}
$stmt->close();

// Fetch score type ID for this simulation
$stmt = $conn->prepare("
    SELECT di_scoretype_id
    FROM mg5_digisim
    WHERE di_id = ?
");
$stmt->bind_param("i", $sim_id);
$stmt->execute();
$stmt->bind_result($scoreTypeId);
$stmt->fetch();
$stmt->close();

// Fetch bucket definitions
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
    <title>Digisim Responses</title>
    <link rel="stylesheet" href="css_site/demo.css">
    <style>
        /* ===== BASE STYLES ===== */
        body {
            font-family: Arial;
            background: #f5f7fa;
            margin: 0;
            padding: 20px;
        }
        .container {
            max-width: 900px;
            margin: auto;
        }
        h2 {
            margin-bottom: 20px;
        }

        /* ===== CARD STYLES ===== */
        .card {
            background: white;
            padding: 15px;
            margin-bottom: 12px;
            border-radius: 8px;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.08);
        }
        .order {
            font-size: 12px;
            color: gray;
            margin-bottom: 6px;
        }
        .content {
            font-size: 14px;
        }
        .card.locked {
            opacity: 0.6;
            cursor: not-allowed;
        }

        /* ===== SCORE BOX ===== */
        .score-box {
            position: fixed;
            right: 30px;
            top: 30px;
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            text-align: center;
        }
        .score-value {
            font-size: 28px;
            font-weight: bold;
            color: #36cb85;
        }

        /* ===== BUCKET STYLES ===== */
        .bucket-container {
            display: flex;
            gap: 20px;
            margin-top: 20px;
        }
        .bucket-box {
            flex: 1;
            background: #fff;
            border-radius: 12px;
            padding: 15px;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.08);
        }
        .bucket-header {
            display: flex;
            flex-flow: column;
            justify-content: center;
            align-items: center;
            margin-bottom: 10px;
            font-weight: 600;
        }
        .bucket-left {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
        }
        .bucket-count {
            background: #eee;
            padding: 4px 10px;
            border-radius: 8px;
            font-size: 12px;
        }
        /* .drop-zone {
            min-height: 140px;
            border: 2px dashed #ccc;
            border-radius: 10px;
            padding: 10px;
        } */

        /* ===== NOTIFICATIONS ===== */
        .notification-wrapper {
            position: fixed;
            top: 20px;
            right: 120px;
        }
        .notification-btn {
            background: #1e293b;
            color: white;
            padding: 10px 16px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .notification-btn:hover {
            background: #334155;
        }
        .notify {
            position: fixed;
            top: 80px;
            right: 20px;
            background: #1e293b;
            color: white;
            padding: 10px 15px;
            border-radius: 8px;
            z-index: 999;
        }

        /* ===== UPDATED BUCKET STYLES ===== */
.priority-dashboard {
    display: flex;
    justify-content: center;
    gap: 15px;
    padding: 20px 0;
    overflow-x: auto;
}

.bucket-box {
    flex: 1;
    height: 60px;
    max-width: 60px;
    min-width: 60px;
    background: #fff;
    border-radius: 12px;
    padding: 20px 10px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
    text-align: center;
    cursor: pointer;
    transition: transform 0.2s;
    border: 1px solid transparent;
}

.bucket-box:hover {
    transform: translateY(-3px);
}

.bucket-name {
    font-size: 11px;
    font-weight: 800;
    letter-spacing: 1px;
    display: block;
    margin-bottom: 10px;
    color: #64748b; /* Default fallback */
}

.count-badge {
    font-size: 42px; /* Large number like the image */
    font-weight: 700;
    margin: 5px 0;
}

/* .drop-zone {
    min-height: 100px; 
    border: 2px dashed transparent; 
    border-radius: 10px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
} */

/* Hide the actual cards inside the bucket boxes, only show count */
.drop-zone .card {
    display: none;
}

/* Highlight zone when dragging over */
.drop-zone.drag-over {
    background: rgba(0,0,0,0.05);
    border-color: #cbd5e1;
}
    </style>
</head>
<body>

<!-- TOP BAR -->

<div class="topbar">

    <?php include("sidenav.php"); ?><div class="logo"> Priority Ranker</div>

    <div class="actions">

        <button class="btn primary" id="submitBtn">Submit Ranking</button>

        <button class="btn" id="resetBtn">Reset</button>

    </div>

</div>

<!-- HEADER -->

<div class="pz-header">

    <div class="pz-left">

        <h2>Priority Zone</h2>

    </div>

</div>

<div class="main-layout">

    



    <!-- LEFT PANEL -->

    <div class="left-panel">

        <!-- AVAILABLE CARDS -->

        <div class="dock" id="availableItems">

            <!-- <h3>Available Items</h3> -->

            <div class="carousel-wrapper" id="carousel-wrapper">

                <button class="arrow left" id="leftArrow">❮</button>



                <div class="carousel" id="dock">

                    <?php if (empty($responses)): ?>
                        <p>No responses found.</p>
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

        </div>


        <div class="priority-dashboard">

            <?php 
                foreach ($buckets as $b): 
                    $hex = $b['stv_color']; // e.g., #bd3d6a
                    
                    // Convert Hex to RGB for the background tint
                    list($r, $g, $bl) = sscanf($hex, "#%02x%02x%02x");
                    $bgColor = "rgba($r, $g, $bl, 0.1)"; 
            ?>
                <div class="bucket-box" 
                    style="background-color: <?php echo $bgColor; ?>; border: 1px solid rgba(<?php echo "$r,$g,$bl"; ?>, 0.2);"
                    onclick="showBucketDetails('<?php echo $b['stv_id']; ?>', '<?php echo strtoupper($b['stv_name']); ?>')">
                    
                    <span class="bucket-name" style="color: <?php echo $hex; ?>">
                        <?php echo strtoupper($b['stv_name']); ?>
                    </span>

                    <div class="drop-zone" id="zone-<?php echo $b['stv_id']; ?>" data-name="<?php echo strtolower($b['stv_name']); ?>">
                        <div class="count-badge" id="count-<?php echo $b['stv_id']; ?>" style="color: <?php echo $hex; ?>">
                            0
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>

           

        </div>



        <div id="activePriorityView" class="expanded-view" style="display:none;">
            <div class="expanded-header">
                <span class="expanded-label" id="detailTitle">EXPANDED: HIGH PRIORITY TASKS</span>
            </div>
            
            <div class="expanded-container">
                
                <div class="carousel-wrapper">
                    <button class="arrow left" id="expandedLeftArrow">❮</button>

                    <div class="carousel" id="detailContent">
                        </div>

                    <button class="arrow right" id="expandedRightArrow">❯</button>
                </div>
            </div>
        </div>



        



    </div>



    <!-- RIGHT PANEL -->

    <div class="right-panel">



        <!-- SCORE -->

        <div class="score-panel">

            <div style="padding-bottom: 10px; border-bottom: 1px solid #eee;display: flex; flex-direction: column;justify-content: center;align-items: center;">

                <span style="font-size: 11px; font-weight: 700; color: #94a3b8; letter-spacing: 1px;">PRIORITY BUCKETS</span>

                <p style="font-size: 11px; color: #64748b; margin: 4px 0 0;">Drag tasks here to reassign</p>

            </div>



            <div class="bucket-points" style="display: flex; justify-content: center;">

                <div class="count-points" id="priorityPoints" style="width: 55px;height: 55px;border-radius: 10%;display: flex;

                    align-items: center;justify-content: center;font-weight: bold;font-size: 34px;color: #36cb85;background: cadetblue;">

                    <?php echo $totalScore; ?>

                </div>

            </div>


        </div>



        <!-- CHAT -->



        <div class="ai-chat-widget">

            <div id="aiBubble" class="chat-bubble">

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

<!-- ACTION BUTTONS & NOTIFICATIONS                             -->
<button id="submitBtn">Submit</button>

<div class="notification-wrapper">
    <div class="notification-btn" onclick="goToMessages()">
        <span>🔔</span>
        <span>Notifications</span>
    </div>
</div>

<!-- JS                                   -->
<script>
    // Global state
    const triggeredTasks = new Set();
    const TOTAL_POINTS = <?php echo $totalScore; ?>;
    const savedSummary = <?php echo json_encode($gameSummary); ?>;
    const isCompleted = <?php echo $isCompleted ? 'true' : 'false'; ?>;

    // Notification helper
    function showNotification(channel) {
        const box = document.createElement("div");
        box.className = "notify";
        box.innerText = "New message in " + channel;
        document.body.appendChild(box);
        setTimeout(() => { box.remove(); }, 3000);
    }

    // Navigation helper
    function goToMessages() {
        window.location.href = "digisim_messageCenter.php?game_id=<?php echo $sim_id ?>";
    }

    //  RESTORE STATE & LOCK IF COMPLETED
    document.addEventListener("DOMContentLoaded", function() {
        
        // Restore previously saved bucket assignments
        if (savedSummary) {
            Object.keys(savedSummary).forEach(bucket => {
                if (bucket === "game_id") return;
                const zone = document.querySelector(`.drop-zone[data-name="${bucket}"]`);
                if (!zone) return;
                savedSummary[bucket].forEach(item => {
                    const card = document.querySelector(`.card[data-id="${item.task_id}"]`);
                    if (!card) return;
                    zone.appendChild(card);
                });
            });
            updateCounts();
            updateScore();
        }

        // Lock interface if already completed
        if (isCompleted) {
            document.querySelectorAll(".card").forEach(card => {
                card.draggable = false;
                card.classList.add("locked");
            });
            const submitBtn = document.getElementById("submitBtn");
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerText = "Already Submitted";
            }
        }
    });

    // JS - UI UPDATERS
    function updateCounts() {
        document.querySelectorAll(".drop-zone").forEach(zone => {
            const id = zone.id.replace("zone-", "");
            const count = zone.querySelectorAll(".card").length;
            document.getElementById("count-" + id).innerText = count;
        });
    }

    function updateScore() {
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
    }

    // js- DRAG & DROP HANDLERS
    document.querySelectorAll(".card").forEach(card => {
        card.addEventListener("dragstart", () => {
            card.classList.add("dragging");
        });
        card.addEventListener("dragend", () => {
            card.classList.remove("dragging");
        });
    });

    document.querySelectorAll(".drop-zone").forEach(zone => {
        zone.addEventListener("dragover", e => e.preventDefault());

        zone.addEventListener("drop", e => {
            e.preventDefault();
            const card = document.querySelector(".dragging");
            if (!card) return;
            if (card.parentNode === zone) return; // Already in this bucket

            const bucketScore = parseInt(zone.dataset.score) || 0;
            const currentRemaining = parseInt(document.getElementById("scoreBox").innerText);

            if (bucketScore > currentRemaining) {
                alert("Not enough score remaining!");
                return;
            }

            zone.appendChild(card);
            updateScore();
            updateCounts();

            // Trigger notification API (prevent duplicates)
            const taskId = card.dataset.id;
            if (triggeredTasks.has(taskId)) return;
            triggeredTasks.add(taskId);

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
                    data.messages.forEach(msg => showNotification(msg.ch_level));
                }
            });

            // Save 
            saveToDB();
        });
    });

    // SAVE  STATE
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

    //  FINAL SUBMISSION
    document.getElementById("submitBtn").addEventListener("click", function() {
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

    const dock = document.getElementById('dock');

        const leftArrow = document.getElementById('leftArrow');
        const rightArrow = document.getElementById('rightArrow');
        const scrollAmount = 220; 

        rightArrow.addEventListener('click', () => {
            dock.scrollBy({ left: scrollAmount, behavior: 'smooth' });
        });

        leftArrow.addEventListener('click', () => {
            dock.scrollBy({ left: -scrollAmount, behavior: 'smooth' });
        });

        dock.addEventListener('scroll', () => {
            const scrollLeft = dock.scrollLeft;
            const maxScroll = dock.scrollWidth - dock.clientWidth;
            leftArrow.style.opacity = scrollLeft <= 0 ? "0.3" : "1";
            rightArrow.style.opacity = scrollLeft >= maxScroll - 1 ? "0.3" : "1";
        });

    

</script>
</body>
</html>
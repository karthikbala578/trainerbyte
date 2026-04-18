<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();
$code = $_SESSION['code'] ?? '';
$userid = $_SESSION['user_id'] ?? '';
// echo "Code: " . $code . " | UserID: " . $userid; // Debugging line
$_SESSION['event_code'] = $code;
$_SESSION['user_id'] = $userid;


if (empty($code)) {
    die("Invalid access code");
}

require "include/coreDataconnect.php";

require "include/pin_sessioncheck.php";

require "include_site/roundfunction_be.php";

$stmt = $conn->prepare("SELECT * FROM tb_events WHERE event_url_code = ?");

$stmt->bind_param("s", $code);

$stmt->execute();

$row = $stmt->get_result()->fetch_assoc();

$event_id = (int)$row['event_id'];

$_SESSION['event_id'] = $event_id;

/* ----------------------------

   DEFAULT EVENT IMAGE

----------------------------- */

$defaultImage = 'upload-images/events/default_event.jpeg';

$eventImage   = $defaultImage;



/* ----------------------------

   FETCH EVENT USING CODE

----------------------------- */

$stmt = $conn->prepare("

    SELECT *

    FROM tb_events

    WHERE event_url_code = ?

    LIMIT 1

");

$stmt->bind_param("s", $code);

$stmt->execute();

$res = $stmt->get_result();

// print_r($res->fetch_assoc()['event_id']); die;


if ($res->num_rows === 0) {

    die("Event not found");

}



$event = $res->fetch_assoc();

$event_id = (int)$event['event_id'];

/* ---- ACCESS GATE: check playstatus ---- */
$ps = (int)($event['event_playstatus'] ?? 1);
$isClosed = ($ps === 4); // allow page but intercept START
if ($ps < 2) {
    // NOT YET PUBLISHED — block entirely
    http_response_code(403);
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
      <meta charset="UTF-8">
      <meta name="viewport" content="width=device-width,initial-scale=1.0">
      <title>Event Not Ready</title>
      <link rel="stylesheet" href="assets/css/gamepage.css">
      <style>
        body { display:flex; align-items:center; justify-content:center; min-height:100vh; background:#f3f6fb; margin:0; font-family:'Inter',sans-serif; }
        .gate-box { text-align:center; background:#fff; padding:48px 40px; border-radius:20px; box-shadow:0 10px 32px rgba(0,0,0,.08); max-width:400px; width:90%; }
        .gate-icon { font-size:56px; margin-bottom:16px; }
        .gate-box h2 { font-size:22px; font-weight:700; color:#111827; margin:0 0 10px; }
        .gate-box p  { font-size:15px; color:#6b7280; line-height:1.6; margin:0; }
        .gate-badge  { display:inline-block; margin-top:20px; background:#fef9c3; color:#854d0e; font-size:12px; font-weight:700; padding:5px 14px; border-radius:20px; letter-spacing:.5px; }
      </style>
    </head>
    <body>
      <div class="gate-box">
        <div class="gate-icon">⏳</div>
        <h2>Event Not Available Yet</h2>
        <p>This event hasn't been published yet. Please check back later or contact your trainer.</p>
        <span class="gate-badge">NOT PUBLISHED</span>
      </div>
    </body>
    </html>
    <?php
    exit;
}
/* ---- END ACCESS GATE ---- */

// 1. Get all modules for this event
$mod_status = 1;
$stmt = $conn->prepare("SELECT mod_type, mod_game_id FROM tb_events_module WHERE mod_event_pkid = ? AND mod_status = ?");
$stmt->bind_param("ii", $event_id, $mod_status);
$stmt->execute();
$modules = $stmt->get_result();
$mod_game_status = 1;
// 2. Prepare the Check and Insert statements once (better performance)
$check = $conn->prepare("SELECT COUNT(*) as total FROM tb_event_user_score WHERE user_id = ? AND event_id = ? AND mod_game_id = ? AND mod_game_status = ?");
$ins = $conn->prepare("INSERT INTO tb_event_user_score (user_id, event_id, mod_game_id, mod_game_type, mod_game_status) VALUES (?, ?, ?, ?, ?)");


// 3. Loop through each module found
while ($row = $modules->fetch_assoc()) {
  //  print_r($row);
    $game_id = $row['mod_game_id'];
    $game_type = $row['mod_type'];
    // echo $_SESSION['user_id'];
    // Check if THIS specific game_id exists for THIS user
    $check->bind_param("iiii", $_SESSION['user_id'], $event_id, $game_id, $mod_game_status);
    $check->execute();
    $count_res = $check->get_result()->fetch_assoc();

    if ($count_res['total'] == 0) {
        // echo $_SESSION['user_id'];
        // If it doesn't exist, insert it
        $ins->bind_param("iiiii", $_SESSION['user_id'], $event_id, $game_id, $game_type, $mod_game_status);
        $ins->execute();
    }
}

$check->close();
$ins->close();
$stmt->close();



// print_r($game_ids); die;

/* ================= MODULE SOURCES ================= */

$moduleSources = [

    // 1 => ['table' => 'mdm_learning_group', 'id' => 'lg_id', 'name' => 'lg_name'], //DigiHunt

    2 => ['table' => 'card_group', 'id' => 'cg_id', 'name' => 'cg_name'], // ByteGuess

 //   3 => ['table' => 'survival_group', 'id' => 'sg_id', 'name' => 'sg_name'], // PixelQuest
// 4 => ['table' => 'mg5_digisim', 'id' => 'di_id', 'name' => 'di_name'], // Bit Bargin
    5 => ['table' => 'mg5_digisim', 'id' => 'di_id', 'name' => 'di_name'], // DigiSIm

    6 => ['table' => 'mg6_riskhop_matrix', 'id' => 'id', 'name' => 'game_name'], // RiskHop
//
  //  7 => ['table' => 'mg7_games', 'id' => 'id', 'name' => 'title'], // TrustTrap

  //  8 => ['table' => 'mg8_games', 'id' => 'id', 'name' => 'title'],// BoundyBid
    9 =>['table' => 'mg5_digisim', 'id' => 'di_id', 'name' => 'di_name']  // Digisim Multi

];

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



/* ----------------------------

   EVENT IMAGE

----------------------------- */

if (!empty($event['event_coverimage']) &&

    file_exists('upload-images/events/' . $event['event_coverimage'])) {

    $eventImage = 'upload-images/events/' . $event['event_coverimage'];

}



/* ----------------------------

   FETCH MODULES (ORDER + NAME FIXED)

----------------------------- */

$stmtMod = $conn->prepare("

    SELECT
        em.mod_id,
        em.mod_type,
        em.mod_game_id,
        em.mod_order,
        em.mod_is_unlocked,
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

$stmtMod->bind_param("i", $event_id);

$stmtMod->execute();

$modules = $stmtMod->get_result();



/* ----------------------------

   GameRound object (KEEP)

----------------------------- */

$gameround = new GameRound();


$totalModulesCount = 0;
$completedModulesCount = 0;

$totalModulesCount = $modules->num_rows;
$gamestatus = 'completed';
// echo $event_id, $userid;
$mod_status = 1;
$progressQuery = $conn->prepare("
    SELECT COUNT(*) as completed_count 
    FROM tb_event_user_score 
    WHERE event_id = ? AND user_id = ? AND mod_game_status = ? AND game_status = ?
");
$progressQuery->bind_param("iiis", $event_id, $userid, $mod_status, $gamestatus);
$progressQuery->execute();
$progressResult = $progressQuery->get_result()->fetch_assoc();
// print_r($progressResult);
$completedModulesCount = $progressResult['completed_count'] ?? 0;

// Calculate percentage
$percentage = ($totalModulesCount > 0) ? round(($completedModulesCount / $totalModulesCount) * 100) : 0;

?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="assets/css/gamepage.css">
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
<link rel="stylesheet" href="assets/fontawesome/css/all.min.css">
<script src="https://kit.fontawesome.com/84b78c6f4c.js" crossorigin="anonymous"></script>
<style>

#mainLayout {
    padding-left: 40px;
    margin-top: 30px;
}

.overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100vw;
    height: 100vh;
    background: rgba(0, 0, 0, 0.85); /* Darken the background */
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 9999; /* Highest priority */
    color: white;
}

/* Add transition for smoothness */
#exitPopup {
    transition: opacity 0.3s ease;
}

a{
    text-decoration: none;
}
.module-info {
    display: flex;
    align-items: center;
    gap: 5px; 

}

.label{
    margin-top: 10px;
}
.module-card {
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: #fff;
    padding: 18px 25px;
    margin-bottom: 15px;
    border-radius: 12px;
    border: 1px solid #edf2f7;
    gap: 25px;
}

/* Blue Highlight for the "Next Up" module */
.module-card.active {
    border: 2px solid #3182ce;
    box-shadow: 0 4px 12px rgba(49, 130, 206, 0.1);
}

.play-icon-bg {
    color: #3182ce;
}

.btn-review {
    background: #a8a9aa;
    color: white;
    padding: 10px 30px;
    border-radius: 8px;
    font-weight: bold;
    text-decoration: none;
}

.btn-start {
    background: #3182ce;
    color: white;
    padding: 10px 30px;
    border-radius: 8px;
    font-weight: bold;
    text-decoration: none;
}

.module-card.locked {
    opacity: 0.6;
    background: #f8fafc;
}

/* 1. Set a fixed height and enable scrolling */
.modules {
    max-height: 500px; /* Adjust this value based on your layout needs */
    overflow-y: auto;  /* Shows scrollbar only when content overflows */
    padding-right: 10px; /* Prevents the scrollbar from overlapping text */
    height: 80%;
    scrollbar-width: thin;
    scrollbar-color: #cbd5e0 #f1f1f1;
}

/* 2. Style the scrollbar to make it look modern (Chrome/Safari/Edge) */
.modules::-webkit-scrollbar {
    width: 8px;
}

.modules::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 10px;
}

.modules::-webkit-scrollbar-thumb {
    background: #cbd5e0; /* Matches your 'locked' color */
    border-radius: 10px;
}

.modules::-webkit-scrollbar-thumb:hover {
    background: #a0aec0;
}
.footer-wrapper {
    position: fixed;
    bottom: 0;
    left: 0;
    width: 100%;
    height: 80px;
    background: #fff;
    /* border-top: 1px solid #eee; */
    display: flex;
    justify-content: flex-end;
    align-items: center;
    padding: 0 40px;
    z-index: 1000;
}

</style>

</head>

<body>
<!-- <div class="main-layout" id="mainLayout"> -->
    <?php include("sidenav.php"); ?>
<div class="main-layout" id="mainLayout">



    <div class="page-content">



        <!-- LEFT -->

        <div class="page-left">

            <img src="<?php echo htmlspecialchars($eventImage); ?>" alt="Event Image">


            <h1><?php echo htmlspecialchars($event['event_name']); ?></h1>

            <p><?php //echo htmlspecialchars($event['event_description']); ?></p>

        </div>



        <!-- RIGHT -->

        <div class="page-right">

            <!-- modules / progress -->
            <div class="progress-box">
                <div class="progress-header">
                    <strong>Progress</strong>
                    <span><?php echo $percentage; ?>%</span>
                </div>

                <small>
                    <?php echo $completedModulesCount; ?> of <?php echo $totalModulesCount; ?> modules completed
                </small>

                <div class="progress-bar">
                    <div style="width: <?php echo $percentage; ?>%; height: 100%; background-color: #3182ce; transition: width 0.5s ease;"></div>
                </div>
            </div>

            <!-- MODULES -->
            <section class="modules">

                <h3>Modules</h3>

                <?php if ($isClosed): ?>
                <div class="closed-notice">
                    🔒 This event is <strong>Closed</strong>. You can review your completed results but cannot start new modules.
                </div>
                <?php endif; ?>


                <?php if ($modules->num_rows === 0): ?>

                    <p>No modules available.</p>

                <?php endif; ?>


                <?php
                $event_progression = (int)($event['event_progression']);
                if ($event_progression <= 0) $event_progression = 1;
                $event_release     = (int)($event['event_release']);
                if ($event_release <= 0) $event_release = 1;

                $i = 1;
                $foundNext = false; // Tracks if we've found the Next sequence module

                while ($m = $modules->fetch_assoc()):
                    $current_mod_game_id = (int)$m['mod_game_id'];
                    $mod_game_status = 1;
                    
                    $statusStmt = $conn->prepare("SELECT * FROM tb_event_user_score WHERE event_id = ? AND user_id = ? AND mod_game_id = ? AND mod_game_status = ?");
                    $statusStmt->bind_param("iiii", $event_id, $userid, $current_mod_game_id, $mod_game_status);
                    $statusStmt->execute();
                    $statusRes = $statusStmt->get_result()->fetch_assoc();
                    $db_status = $statusRes['game_status'] ?? 'not_started';

                    $isCompleted = ($db_status == 'completed');
                    $isUnlocked  = true; // default for auto

                    if ($event_release == 2) {
                        // Manual Release
                        $isUnlocked = ((int)$m['mod_is_unlocked'] == 1);
                    }

                    if ($isCompleted) {
                        $state = 'completed';
                        // Even if it's completed, it counts as "passed" for the sequence.
                        // We don't set $foundNext = true until we hit the first UNCOMPLETED one.
                    } else {
                        if ($event_progression == 1) { // SEQUENCE
                            if ($isUnlocked && !$foundNext) {
                                $state = 'active';
                                $foundNext = true; // Block subsequent modules
                            } else {
                                $state = 'locked';
                                if (!$isUnlocked && !$foundNext) {
                                    // It's this module's turn in the sequence, but it's locked by Admin!
                                    // We STILL must block subsequent modules from becoming active.
                                    $foundNext = true;
                                }
                            }
                        } else { // RANDOM
                            if ($isUnlocked) {
                                $state = 'active';
                            } else {
                                $state = 'locked';
                            }
                        }
                    }

                    $startUrl = '#';

                        switch ((int)$statusRes['mod_game_type']) {
                            // 🟣 DigiHunt
                            // case 1:

                            //     $_SESSION['mod_game_id'] = (int)$m['mod_game_id'];
                            //     $startUrl = "digihunt/digihunt_casestudy.php";
                            //     $resultUrl = "digihunt/digihunt_final_results.php";
                            //     break;
                            // 🟣 ByteGuess
                            case 2:

                                $_SESSION['mod_game_id'] = (int)$m['mod_game_id'];
                                $startUrl = "byteguess/byteguess_companyintro.php";
                                $resultUrl = "byteguess/byteguess-final_results.php";
                                break;
                            // 🔵 PixelQuest
                            case 3:
                                $_SESSION['mod_game_id'] = $m['mod_game_id'];
                                $startUrl = "PixelQuest/pixelquest_casestudy.php";
                                $resultUrl = "PixelQuest/pixelquest_final_results.php";
                                break;
                            // 🔵 DigiSim
                            case 5:
                            case 9:
                                $_SESSION['mod_game_id'] = $m['mod_game_id'];
                                $startUrl = "digisim/digisim_casestudy.php";
                                $resultUrl = "digisim/digisim_finalResult.php";
                                break;
                            // 🔵 RiskHop
                            case 6:
                                $_SESSION['mod_game_id'] = $m['mod_game_id'];
                                $startUrl = "riskhop/game/new_instruction.php";
                                break;

                            // 🟢 TrustTrap
                            case 7:
                                $_SESSION['mod_game_id'] = $m['mod_game_id'];
                                $startUrl = "TrustTrap/user/game_intro.php";
                                break;
                             // 🟢 BountyBid    
                            case 8:
                                $_SESSION['mod_game_id'] = $m['mod_game_id'];
                                $startUrl = "BountyBid/user/mg8_game_intro.php";
                                break;
                        }

                        
                    //$startUrl = "ByteGuess/byteguess_companyintro.php?id=1&game_id=" . $current_mod_game_id;
                ?>

                <div class="module-card <?php echo $state; ?>">
                    <div class="module-info">
                        <div class="status-icon">
                            <?php if ($state == 'completed'): ?>
                                <i class="fas fa-check-circle" style="color: #48bb78;"></i>
                            <?php elseif ($state == 'active'): ?>
                                <div class="play-icon-bg"><i class="fas fa-play"></i></div>
                            <?php else: ?>
                                <i class="fas fa-lock" style="color: #cbd5e0;"></i>
                            <?php endif; ?>
                        </div>
                        <div class="text-content">
                            <small style="font-size: 1rem;"> <?php echo $i; ?></small>
                            <strong style="<?php echo ($state == 'locked') ? 'color: #a0aec0;' : ''; ?>">
                                <?php echo htmlspecialchars($m['module_name']); ?>
                            </strong>
                        </div>
                    </div>

                    <div class="actions">
                        <?php if ($state == 'completed'): ?>
                            <span class="label completed" style="color: #48bb78;">ANALYSIS</span>
                            <a href="<?php echo $resultUrl; ?>?game_id=<?php echo $current_mod_game_id; ?>" class="btn-review">Review</a>
                        <!-- <?php //elseif ($state == 'active'): ?>
                            <span class="label next" style="color: #3182ce;">NEXT UP</span>
                            <a href="<?php echo $startUrl; ?>" class="btn-start" onclick="sessionStorage.clear();">START</a> -->
                        <?php elseif ($state == 'active'): ?>
                            <span class="label next" style="color: #3182ce;">NEXT UP</span>
                            
                            <?php if ($isClosed): ?>
                            <!-- CLOSED: intercept START with popup -->
                            <button class="btn-start"
                                onclick="showClosedPopup()">
                                START
                            </button>
                            <?php else: ?>
                            <a href="<?php echo $startUrl; ?>?game_id=<?php echo $current_mod_game_id; ?>" 
                            class="btn-start" 
                            onclick="sessionStorage.clear();">
                                START
                            </a>
                            <?php endif; ?>
    
                        <?php else: ?>
                            <span class="label locked" style="color: #a0aec0;"></span>
                            <button class="btn-locked" disabled>LOCKED</button>
                        <?php endif; ?>
                    </div>
                </div>

                <?php $i++; endwhile; ?>

            </section>

        </div>

    </div>
</div>



<!-- FOOTER -->
<div class="footer-wrapper">
    
    <div class="ai-chat-widget">
        <div id="aiBubble" class="chat-bubble">
            <span class="msgclo" onClick="closemsg();" >Hide</span>
            <h4 id="aiHeader">We're Online!</h4>
            <p id="aiMsg">How may I help you today?</p>
        </div>
        <div class="bot-button" id="botIcon">
            <div class="online-status"></div>
            <img src="byteguess/images/bot.png" alt="AI Bot">
        </div>
    </div>
</div>


<div id="exitPopup" class="overlay" style="display:none;">
    <div class="content" style="max-width: 400px; text-align: center;">
        <div class="guideline-content-area">
            <h2 class="intro-title">Session Active</h2>
            <p>You are currently logged in. Would you like to continue your session or logout?</p>

            <div style="display: flex; gap: 15px; justify-content: center; margin-top: 25px;">
                <button class="arrow-btn right" onclick="closeExitPopup()">CONTINUE</button>
                <button class="arrow-btn left" onclick="handleLogout()" style="background: #e53e3e; color: white; border: none;">LOGOUT</button>
            </div>
        </div>
    </div>
</div>



<script>

 window['msgai'] = <?php echo json_encode($insGameDetails['msgAI'] ?? ''); ?>;


function toggleSidebar() {
    const sidebar = document.getElementById('common-sidebar');
    const overlay = document.getElementById('common-sidebarOverlay');

    // Toggle the 'active' class on both
    sidebar.classList.toggle('active');
    overlay.classList.toggle('active');
}

// 1. Immediately push a state to trap the back button
(function() {
    // Add a state so there is something to "go back" from
    history.pushState(null, null, window.location.href);

    window.onpopstate = function(event) {
        // Show the popup
        const popup = document.getElementById('exitPopup');
        if (popup) {
            popup.style.display = 'flex';
        }

        // Push the state back in so the "Back" button remains trapped
        history.pushState(null, null, window.location.href);
    };
})();

// 2. Button Actions
function closeExitPopup() {
    document.getElementById('exitPopup').style.display = 'none';
}

function handleLogout() {
    // Redirect to your logout script
    // window.location.href = 'http://localhost/trainergenie/<?php //echo $code; ?>'; 
    window.location.href = 'http://localhost/trainerbyte/<?php echo $code; ?>'; 
}

function showAIMessage(title, message) {
    const bubble = document.getElementById('aiBubble');
    const header = document.getElementById('aiHeader');
    const msg = document.getElementById('aiMsg');

    // Set the content
    header.innerText = title;
    msg.innerText = message;

    // Show the bubble
    bubble.classList.add('show');

}
//const company = <?php //echo json_encode(trim($event['event_description'])); ?>;
const company = <?php echo json_encode(!empty(trim($event['event_description'] ?? '')) ? trim($event['event_description'])
        : trim($event['event_name'] ?? '')
); ?>;
showAIMessage("", company);

function closemsg(){
    const bubble = document.getElementById('aiBubble');	
    // $(bubble).fadeOut();
    bubble.classList.remove('show');
}
</script>

<!-- ── CLOSED POPUP ── -->
<div id="closedPopup" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:99999;display:none;align-items:center;justify-content:center;" onclick="if(event.target===this)this.style.display='none'">
    <div style="background:#fff;border-radius:18px;padding:36px 32px;max-width:340px;width:90%;text-align:center;box-shadow:0 12px 40px rgba(0,0,0,.2);animation:popIn .2s ease;">
        <div style="font-size:48px;margin-bottom:12px">🔒</div>
        <h2 style="font-size:20px;font-weight:700;color:#111827;margin:0 0 10px">Event is Closed</h2>
        <p style="font-size:14px;color:#6b7280;line-height:1.6;margin:0 0 20px">This event has been closed by the organizer. You can still review your completed results.</p>
        <button onclick="document.getElementById('closedPopup').style.display='none'"
            style="background:#2563eb;color:#fff;border:none;border-radius:10px;padding:10px 28px;font-size:14px;font-weight:600;cursor:pointer;">
            OK
        </button>
    </div>
</div>

<style>
@keyframes popIn { from{transform:scale(.9);opacity:0} to{transform:scale(1);opacity:1} }
.closed-notice {
    background: #fef2f2;
    color: #b91c1c;
    border: 1px solid #fecaca;
    border-radius: 10px;
    padding: 10px 16px;
    font-size: 13px;
    font-weight: 500;
    margin-bottom: 14px;
    line-height: 1.5;
}
</style>

<script>
function showClosedPopup() {
    const popup = document.getElementById('closedPopup');
    popup.style.display = 'flex';
}
</script>

</body>
</html>

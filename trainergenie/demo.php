<?php

ob_start();

session_start();



$code = $_GET['code'] ?? '';
$userid = $_GET['user_id'] ?? '';

$_SESSION['event_code'] = $code;
$_SESSION['user_id'] = $userid;

if (empty($code)) {

    die("Invalid access code");

}

require "include/dataconnect.php";

require "include_site/roundfunction_be.php";

$stmt = $conn->prepare("SELECT * FROM tb_events WHERE event_url_code = ?");

$stmt->bind_param("s", $code);

$stmt->execute();

$row = $stmt->get_result()->fetch_assoc();

$event_id = (int)$row['event_id'];

// $stmt = $conn->prepare("SELECT * FROM tb_event_user_score WHERE event_id = ? and user_id = ?");

// $stmt->bind_param("ii",$event_id, $userid);

// $stmt->execute();

// $row = $stmt->get_result()->fetch_assoc();
// print_r("uddh dhdhud d hduhd hduhuh");
// print_r($row);

// // $game_id =$row['game_id'];

// // $game_code = $row['game_code'];
// if (!($row))  {

//     $game_status = 'not_started';

// }else{
//     $game_status = $row['game_status'];
// }

// echo "Game Status: " . $game_status;

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

$check = $conn->prepare("SELECT COUNT(*) as total FROM tb_event_user_score WHERE user_id = ? AND event_id = ?");

$check->bind_param("ii", $userid, $event_id);

$check->execute();

$res = $check->get_result()->fetch_assoc();

if ($res['total'] == 0) {

    $stmt = $conn->prepare(

        "SELECT mod_game_id

        FROM tb_events_module 

        WHERE mod_event_pkid = ? and mod_status = 1"

    );

    $stmt->bind_param("i", $event_id);

    $stmt->execute();

    $modules_ids = $stmt->get_result();

    $game_ids = [];
    while ($row = $modules_ids->fetch_assoc()) {
        $game_ids[] = $row['mod_game_id'];
    }

    // print_r($game_ids); die;


    $ins = $conn->prepare(

        "INSERT INTO tb_event_user_score

        (user_id, event_id, mod_game_id)

        VALUES (?, ?, ?)"

    );

    for ($i = 0; $i < count($game_ids); $i++) {
        
        $ins->bind_param("iii", $_SESSION['user_id'], $event_id, $game_ids[$i]);
        $ins->execute();
        
        // echo "Inserted Game ID: " . $ins->insert_id . "<br>";
    }
    
    $ins->close();
    // echo "First time login: 3 game slots created.";
}



// print_r($game_ids); die;

/* ================= MODULE SOURCES ================= */

$moduleSources = [

    1 => ['table' => 'card_group', 'id' => 'cg_id', 'name' => 'cg_name'],

    2 => ['table' => 'card_group', 'id' => 'cg_id', 'name' => 'cg_name'],

    5 => ['table' => 'mg6_riskhop_matrix', 'id' => 'id', 'name' => 'game_name'],

    8 => ['table' => 'mg7_games', 'id' => 'id', 'name' => 'title'],

    9 => ['table' => 'mg8_games', 'id' => 'id', 'name' => 'title'] // 🔥 BountyBid

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

// Re-run a quick check or use the previous modules result
// Since we are using $modules->fetch_assoc() in the loop later, 
// we should count them properly here or use $modules->num_rows.

$totalModulesCount = $modules->num_rows;

// To get the completed count without affecting the main loop pointer:
$progressQuery = $conn->prepare("
    SELECT COUNT(*) as completed_count 
    FROM tb_event_user_score 
    WHERE event_id = ? AND user_id = ? AND game_status = 'completed'
");
$progressQuery->bind_param("ii", $event_id, $userid);
$progressQuery->execute();
$progressResult = $progressQuery->get_result()->fetch_assoc();

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
    margin-top: 50px;
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
}

/* Blue Highlight for the "Next Up" module */
.module-card.active {
    border: 2px solid #3182ce;
    box-shadow: 0 4px 12px rgba(49, 130, 206, 0.1);
}

.play-icon-bg {
    background: #ebf8ff;
    color: #3182ce;
    width: 36px;
    height: 36px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
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
</style>

</head>

<body>
<!-- <div class="main-layout" id="mainLayout"> -->
    <?php include("ByteGuess/sidenav.php"); ?>
<div class="main-layout" id="mainLayout">

    

    <div class="page-content">



        <!-- LEFT -->

        <div class="page-left">

            <img src="<?php echo htmlspecialchars($eventImage); ?>" alt="Event Image">



            <span class="badge">PROFESSIONAL GAMING SERIES</span>

            <h1><?php echo htmlspecialchars($event['event_name']); ?></h1>

            <p><?php echo htmlspecialchars($event['event_description']); ?></p>

        </div>



        <!-- RIGHT -->

        <div class="page-right">

            <!-- modules / progress -->
            <div class="progress-box">
    <div class="progress-header">
        <strong>Course Progress</strong>
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

                <h3>Learning Modules</h3>



                <?php if ($modules->num_rows === 0): ?>

                    <p>No modules available.</p>

                <?php endif; ?>


<?php
$i = 1;
$foundNext = false; // This tracks if we've assigned the "Next Up" slot yet

while ($m = $modules->fetch_assoc()):
    $current_mod_game_id = (int)$m['mod_game_id'];

    // Check individual status from the database
    $statusStmt = $conn->prepare("SELECT game_status FROM tb_event_user_score WHERE event_id = ? AND user_id = ? AND mod_game_id = ?");
    $statusStmt->bind_param("iii", $event_id, $userid, $current_mod_game_id);
    $statusStmt->execute();
    $statusRes = $statusStmt->get_result()->fetch_assoc();
    $db_status = $statusRes['game_status'] ?? 'not_started';

    // Logic:
    // 1. If DB says 'completed', show Completed.
    // 2. If not completed and we haven't found the 'Next' one yet, this is the Active one.
    // 3. Otherwise, it's locked.
    
    if ($db_status == 'completed') {
        $state = 'completed';
    } elseif (!$foundNext) {
        $state = 'active';
        $foundNext = true; // Mark that we found the playable module; others will now lock
    } else {
        $state = 'locked';
    }

    $startUrl = "ByteGuess/byteguess_companyintro.php?id=1&game_id=" . $current_mod_game_id;
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
            <small> MODULE <?php echo str_pad($i, 2, '0', STR_PAD_LEFT); ?></small>
            <strong style="<?php echo ($state == 'locked') ? 'color: #a0aec0;' : ''; ?>">
                <?php echo htmlspecialchars($m['module_name']); ?>
            </strong>
        </div>
    </div>

    <div class="actions">
        <?php if ($state == 'completed'): ?>
            <span class="label completed" style="color: #48bb78;">COMPLETED</span>
            <a href="<?php echo $startUrl; ?>" class="btn-review">Review</a>
        <?php elseif ($state == 'active'): ?>
            <span class="label next" style="color: #3182ce;">NEXT UP</span>
            <a href="<?php echo $startUrl; ?>" class="btn-start">START</a>
        <?php else: ?>
            <span class="label locked" style="color: #a0aec0;">LOCKED</span>
            <button class="btn-locked" disabled>LOCKED</button>
        <?php endif; ?>
    </div>
</div>

<?php $i++; endwhile; ?>

            </section>

        </div>

    </div>
</div>


  

    <div class="ai-chat-widget">
        <div class="bot-button" id="botIcon">
            <div class="online-status"></div>
            <img src="https://img.icons8.com/ios-filled/100/ffffff/bot.png" alt="AI Bot">
        </div>

        <div id="aiBubble" class="chat-bubble">
            <h4 id="aiHeader">We're Online!</h4>
            <p id="aiMsg">How may I help you today?</p>
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
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebarOverlay');

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
    window.location.href = 'http://localhost/trainergenie/<?php echo $code; ?>'; 
}

</script>

</body>
</html>

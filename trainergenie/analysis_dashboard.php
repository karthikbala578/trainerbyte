<?php

ob_start();

session_start();

include("include/dataconnect.php");

$code = $_SESSION['event_code'];

$stmt = $conn->prepare("SELECT * FROM tb_events

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

// 1. Get all modules for this event
$mod_status = 1;
$stmt = $conn->prepare("SELECT mod_type, mod_game_id FROM tb_events_module WHERE mod_event_pkid = ? AND mod_status = ?");
$stmt->bind_param("ii", $event_id, $mod_status);
$stmt->execute();
$modules = $stmt->get_result();
$mod_game_status = 1;
// 2. Prepare the Check and Insert statements once (better performance)

$all_rounds = []; // Initialize an empty array
while ($row = $modules->fetch_assoc()) {
    $game_id = $row['mod_game_id'];
    $game_type = $row['mod_type'];

    $check = $conn->prepare("SELECT * FROM tb_event_user_score WHERE user_id = ? AND event_id = ? AND mod_game_id = ? AND mod_game_status = ?");
    $check->bind_param("iiii", $_SESSION['user_id'], $event_id, $game_id, $mod_game_status);
    $check->execute();
    $result = $check->get_result();
    
    if($data = $result->fetch_assoc()){
        $all_rounds[] = $data; // Append the row to our list
    }
}
// ================================


// Initialize counts
$totalGamesCount = count($all_rounds); // Counts all elements in the array
$completedGamesCount = 0;

foreach ($all_rounds as $round) {
    // Check if the game_status key exists and equals 'completed'
    if (isset($round['game_status']) && $round['game_status'] === 'completed') {
        $completedGamesCount++;
    }
}

// Optional: Store them in session or variables for display
$currentProgress = ($totalGamesCount > 0) ? ($completedGamesCount / $totalGamesCount) * 100 : 0;
// print_r($totalGamesCount);
$currentRound = 6;

$globalRank = '#12';

$totalScore = 42850;

?>

<!DOCTYPE html>

<html>

<head>

<meta charset="UTF-8">

<title>ERM Sandbox - Scorecard</title>



<link rel="stylesheet" href="assets/css/byteguess_scorecard.css">

<script src="https://kit.fontawesome.com/84b78c6f4c.js" crossorigin="anonymous"></script>



</head>

<body>
<?php include("sidenav.php"); ?>
<div class="app">


<!-- MAIN -->
    
    <div class="main">



        <div class="topbar">

            <div style="display:flex;align-items:center;gap:12px;">

                <div class="header-title">

                    <h2>Multi-Round Game Scorecard</h2>

                    <p>Track your progression and review past decisions across all simulation cycles.</p>

                </div>

            </div>


            <div class="stats">

                <div class="stat">Games Completed<br><b><?php echo $completedGamesCount; ?> / <?php echo $totalGamesCount; ?></b></div>

                <!-- <div class="stat">GLOBAL RANK<br><b><?php //echo $totalGamesCount; ?></b></div> -->

            </div>

        </div>



        <div class="grid">
            <?php 
                $firstLocked = false; 
                $totalOverallScore = 0;
                
                // 1. Initialize our counters
                $totalModGameCount = count($all_rounds);
                $completedCount = 0;

                foreach($all_rounds as $i => $r) { 
                    if (!is_array($r)) continue;

                    // 2. Count completed rounds
                    if (isset($r['game_status']) && $r['game_status'] == 'completed') {
                        $completedCount++;
                    }

                    // Safely decode the summary
                    $summaryData = json_decode($r['game_summary'] ?? '{}', true);
                    
                    $roundScore = (is_array($summaryData) && isset($summaryData['final_score'])) 
                                ? (int)$summaryData['final_score'] 
                                : 0;

                    $totalOverallScore += $roundScore;
                    $status = $r['game_status'] ?? 'not_started';

                    $current_mod_game_id = (int)$r['mod_game_id'];

                    // Check individual status from the database
                    $statusStmt = $conn->prepare("SELECT * FROM tb_event_user_score WHERE event_id = ? AND user_id = ? AND mod_game_id = ?");
                    $statusStmt->bind_param("iii", $event_id, $_SESSION['user_id'], $current_mod_game_id);
                    $statusStmt->execute();
                    $statusRes = $statusStmt->get_result()->fetch_assoc();
                    // print_r($statusRes);

                    // Logic:
                    // 1. If DB says 'completed', show Completed.
                    // 2. If not completed and we haven't found the 'Next' one yet, this is the Active one.
                    // 3. Otherwise, it's locked.

                    $startUrl = '#';
                    
                        switch ((int)$statusRes['mod_game_type']) {
                            // 🟣 ByteGuess
                            case 2:
                                $startUrl = "ByteGuess/byteguess_companyintro.php?id=1&game_id=" . (int)$r['mod_game_id'];
                                $resultUrl = "ByteGuess/byteguess-final_results.php?game_id=" . (int)$r['mod_game_id']."&code=".$code;
                                break;
                            // 🔵 PixelQuest
                            case 3:
                                $startUrl = "PixelQuest/pixelquest_casestudy.php?game_id=" . (int)$r['mod_game_id'];
                                $resultUrl = "PixelQuest/pixelquest_final_results.php?game_id=" . (int)$r['mod_game_id']."&code=".$code;
                                break;
                            // 🔵 DigiSim
                            case 5:
                                $startUrl = "DigiSIM/digisim_casestudy.php?game_id=" . (int)$r['mod_game_id'];
                               $resultUrl = "DigiSIM/digisim_finalResult.php?game_id=" . (int)$r['mod_game_id']."&code=".$code;
                                break;
                            // 🔵 RiskHop
                            case 6:
                                $startUrl = "RiskHOP/game/instruction.php?game_id=" . (int)$r['mod_game_id'];
                                break;

                            // 🟢 TrustTrap
                            case 7:
                                $startUrl = "TrustTrap/user/game_intro.php?game_id=" . (int)$r['mod_game_id'];
                                break;
                             // 🟢 BountyBid    
                            case 8:
                                $startUrl = "BountyBid/user/mg8_game_intro.php?game_id=" . (int)$r['mod_game_id'];
                                break;
                        }
            ?>

                <?php if($status == 'completed'): ?>
                    <div class="card completed">
                        <div class="card-header">ROUND <?php echo $i + 1; ?> <span class="check-icon">✔</span></div>
                        <div class="card-body">
                            <div class="small">SCORE EARNED</div>
                            <div class="score"><?php echo number_format($roundScore); ?></div>
                            <a href="<?php echo $resultUrl; ?>" class="btn btn-black">View Summary</a>
                        </div>
                    </div>

                <?php elseif($status == 'not_started' OR $status == 'in_progress'): ?>
                    
                    <?php if(!$firstLocked): $firstLocked = true; ?>
                        <div class="card active active-card">
                            <div class="card-header">ROUND <?php echo str_pad($i + 1, 2, '0', STR_PAD_LEFT); ?> <span class="active-tag">PLAY NOW</span></div>
                            <div class="card-body">
                                <div class="small">ESTIMATED SCORE</div>
                                <div class="score">0</div>
                                <a href="<?php echo $startUrl; ?>" class="btn btn-play">▶ Start Game</a>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="card locked-card">
                            <div class="locked-header">ROUND <?php echo str_pad($i + 1, 2, '0', STR_PAD_LEFT); ?> <span><i class="fa-solid fa-lock"></i></span></div>
                            <div class="locked-body">
                                <div class="big-lock"><i class="fa-solid fa-lock"></i></div>
                                Locked
                            </div>
                        </div>
                    <?php endif; ?>

                <?php endif; ?>

            <?php } // End Foreach ?>
        </div>

        <div class="total-score-banner">
            <h3>Total Event Score: <?php echo number_format($totalOverallScore); ?></h3>
        </div>


    </div>

</div>



<script>

function toggleSidebar() {
    const sidebar = document.getElementById('common-sidebar');
    const overlay = document.getElementById('common-sidebarOverlay');

    // Toggle the 'active' class on both
    sidebar.classList.toggle('active');
    overlay.classList.toggle('active');
}




</script>

</body>

</html>


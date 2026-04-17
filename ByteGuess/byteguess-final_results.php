<?php



session_start();







/* ======================================================



   DB & HELPERS



====================================================== */



require_once "../include/coreDataconnect.php";



require_once "byteguess_data.php";







/* ======================================================



   INPUT VALIDATION



====================================================== */



$cg_id = (int)($_GET['game_id'] ?? 0);



$code  = $_GET['code'] ?? ($_SESSION['event_code'] ?? '');







if ($cg_id <= 0 || empty($code)) {



    die("Invalid request");



}







/* ======================================================



   FETCH GAME (DE-BRIEFING CONTENT)



====================================================== */

$gamedata = new GameRound();





$game = $gamedata->getCardGroupById($conn, $cg_id);







if (!$game) {



    die("Game not found");



}







/* ======================================================



   FETCH FINAL GAME SUMMARY (JSON)



====================================================== */



$stmt = $conn->prepare("



    SELECT game_summary



    FROM tb_event_user_score



    WHERE mod_game_id = ?



      AND event_id = ? AND user_id = ?



    LIMIT 1



");



$stmt->bind_param("iii", $cg_id, $_SESSION['event_id'], $_SESSION['user_id']);



$stmt->execute();



$row = $stmt->get_result()->fetch_assoc();







if (!$row || empty($row['game_summary'])) {



    die("Final game result not found");



}







/* ======================================================



   DECODE SUMMARY



====================================================== */



$summary = json_decode($row['game_summary'], true);







if (!is_array($summary)) {



    die("Invalid game summary data");



}







/* ======================================================



   EXTRACT VALUES



====================================================== */



$finalScore     = (int)($summary['final_score'] ?? 0);



$finalDecision  = $summary['final_decision'] ?? [];







$decisionTitle  = $finalDecision['title'] ?? '';



$decisionAnswer = $finalDecision['answer'] ?? '';



?>







<!DOCTYPE html>



<html lang="en">



<head>



<meta charset="UTF-8">



<title>Game De-briefing & Final Results</title>



<link rel="stylesheet" type="text/css" href="css_site/byteguess_finalpage.css">



<style>

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

</style>



</head>







<body>







<div class="container">







    <!-- HEADER -->



    <div class="header">



        <h1>Analysis & De-briefing</h1>



    </div>







    <!-- CONTENT GRID -->



    <div class="grid">







        <!-- LEFT : DE-BRIEFING -->



        <div class="card">

            <div class="brief-data">



                <h2>De-briefing</h2>



                <div class="brief">



                    <p><?php echo nl2br(htmlspecialchars($game['cg_result'] ?? '')); ?></p>



                </div>




            </div>



        </div>







        <!-- RIGHT : SCORE -->



        <div class="card">



            <div class="score-box">



                <div>YOUR SCORE</div>



                <div class="score"><?php echo $finalScore; ?></div>



            </div>


            <div class="brief-data">


                    <h3>Your Decision</h3>

                    <strong><?php echo htmlspecialchars($decisionTitle); ?></strong>

                    <p><?php echo nl2br(htmlspecialchars($decisionAnswer)); ?></p>
            </div>




            <div class="rtnbth" style="margin-top:15px;padding-bottom: 15px;">



                <a href="../teaminstance_be.php?user_id=<?php echo $_SESSION['user_id']; ?>&code=<?php echo urlencode($code); ?>"



                   class="btn">



                   Return to Module Index



                </a>



            </div>



        </div>







    </div>







</div>



<div id="exitPopup" class="overlay" style="display:none;">

    <div class="content" style="max-width: 400px; text-align: center;">

        <div class="guideline-content-area">

            <h2 class="intro-title">Session Active</h2>

            <p>You are currently logged in. Would you like to continue your session or logout?</p>

            

            <div style="display: flex; gap: 15px; justify-content: center; margin-top: 25px;">

                <button class="arrows-btn right" onclick="closeExitPopup()">CONTINUE</button>

                <button class="arrows-btn left" onclick="handleLogout()" style="background: #6b89ec; border: none;">Module Index</button>

            </div>

        </div>

    </div>

</div>



<script>

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
        window.location.href = '../teaminstance_be.php?user_id=<?php echo $_SESSION['user_id']; ?>&code=<?php echo $_SESSION['event_code']; ?>';
    }

</script>

</body>



</html>
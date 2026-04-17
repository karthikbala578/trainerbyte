<?php

session_start();

include("../include/coreDataconnect.php");

$ro_id = $_GET['game_id'];

$mod_game_status = 1;

$stmt = $conn->prepare("SELECT * FROM tb_event_user_score WHERE user_id = ? AND event_id = ? AND mod_game_id = ? AND mod_game_status = ?");
$stmt->bind_param("iiii", $_SESSION['user_id'], $_SESSION['event_id'], $ro_id, $mod_game_status);
$stmt->execute();

$row = $stmt->get_result()->fetch_assoc();
$game_status = $row['game_status'];

if($game_status != 'completed') {

    $upd = $conn->prepare(
        "UPDATE tb_event_user_score 
        SET game_status = 'in_progress' 
        WHERE mod_game_id = ? AND event_id = ? AND user_id = ? AND mod_game_status = ?"
    );

    $upd->bind_param("iiii", $ro_id, $_SESSION['event_id'], $_SESSION['user_id'], $mod_game_status);
    $upd->execute();
}

/* ================= FETCH FROM mg5_digisim ================= */

$stmt = $conn->prepare("
    SELECT di_name, di_casestudy, di_coverimg 
    FROM mg5_digisim
    WHERE di_id = ? AND di_status = 1
");

$stmt->bind_param("i", $ro_id);
$stmt->execute();

$game = $stmt->get_result()->fetch_assoc();

if (!$game) {
    die("Invalid game ID");
}

/* ================= JSON DECODE ================= */
$caseImage = $game['di_coverimg'];
$caseData = json_decode($game['di_casestudy'], true);

$companyName = $caseData['company_name'] ?? '';
$title       = $caseData['title'] ?? $game['di_name'];
$intro       = $caseData['introduction'] ?? '';

?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" type="text/css" href="css_site/digisim.css">
<link rel="stylesheet" href="assets/fontawesome/css/all.min.css">
<script src="https://kit.fontawesome.com/84b78c6f4c.js" crossorigin="anonymous"></script>

<style>

    .main-layout {
        display: flex;
        transition: all 0.3s ease;
        padding: 20px;
    }

    .content-card {
        width: 100%;
    }

    .split-casestudy-wrapper {
        display: flex;
        height: 560px;
        width: 100%;
        padding: 25px 10px;
        justify-content: center;
        align-items: center;
    }

    .casestudy-visual {
        flex: 1;
        background: #f8fafc;
        height: 100%;
    }

    .casestudy-visual img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .casestudy-content-area {
        flex: 1.2;
        display: flex;
        flex-direction: column;
        padding: 0px;
        height: 100%;
        overflow: hidden;
    }

    .rich-text-uppercase {
        /* text-transform: uppercase; */
        letter-spacing: 1px;
        line-height: 1.8;
        color: #4a5568;
        flex: 1;
        overflow-y: auto;
        padding: 0px 20px;
        scrollbar-width: thin;
        scrollbar-color: #717172 #f7fafc;
    }

    .rich-text-uppercase li {
        margin-bottom: 15px;
        padding-left: 5px;
    }

    @media (max-width: 600px) {
        .split-casestudy-wrapper {
            flex-direction: column;
            height: 550px;
            margin-top: 17px;
        }

        .casestudy-visual {
            height: 250px;
        }

        .casestudy-content-area {
            padding: 20px 20px;
        }
    }

    .overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100vw;
        height: 100vh;
        background: rgba(0, 0, 0, 0.85);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 9999;
        color: white;
    }

    #exitPopup {
        transition: opacity 0.3s ease;
    }

</style>

</head>

<body>

<?php include("sidenav.php"); ?>

<?php
    // ... your existing session and database code ...

    // 1. Determine the Image based on the database logic
    if (empty($caseImage)) {
        $image_html = '<img src="images/BG Context.jpg" alt="Company Image" class="responsive-img">';
    } else {
        $image_html = '<img src="' . $caseImage . '" alt="Company Image" class="responsive-img">';
    }

    // 2. Determine the Rich Text Content logic
   
    $content_body = '<div class="admin-rendered-content">' . $intro . '</div>';

    // 3. Wrap everything into one big PHP variable using Heredoc (<<<EOD)
    $full_page_content = <<<EOD
    <div class="main-layout" id="mainLayout">
        <main class="content-card">
            <div class="split-casestudy-wrapper">
                
                <div class="casestudy-visual">
                    $image_html
                </div>

                <div class="casestudy-content-area">
                    <div class="rich-text-uppercase">
                        <h2 class="intro-title">
                            $title
                        </h2>
                        <?php if ($companyName): ?>
                            <p><strong><?php echo htmlspecialchars($companyName); ?></strong></p>
                        <?php endif; ?>
                        <h2 class="intro-title">Exercise Context</h2>
                        $content_body
                    </div>
                </div>

            </div>
        </main>
    </div>
    EOD;

    // 4. Now you can echo that variable anywhere you want
    echo $full_page_content;
?>



<!-- FOOTER -->
<div class="footer-wrapper">
    

    <div class="footer-action">
        <div class="arrow-nav">
            <!-- <div class="arrow-btn left" onclick="goPrev()">PREVIOUS</div> -->
            <div class="arrow-btn right" onclick="goNext(<?php echo $ro_id; ?>)">NEXT</div>
        </div>
    </div>
    <div class="ai-chat-widget">
        <div id="aiBubble" class="chat-bubble">
            <span class="msgclo" onClick="closemsg();" >Hide</span>
            <h4 id="aiHeader">We're Online!</h4>
            <p id="aiMsg">How may I help you today?</p>
        </div>
        <div class="bot-button" id="botIcon">
            <div class="online-status"></div>
            <img src="images/bot.png" alt="AI Bot">
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
                <button class="arrows-btn left" onclick="handleLogout()" style="background: #e53e3e; color: white; border: none;">LOGOUT</button>
            </div>
        </div>
    </div>
</div>

<script>
function goNext(comId){
    window.location.href = 'digisim_messageCenter.php?game_id=' + comId;
}

    function showAIMessage(title, message) {
        const bubble = document.getElementById('aiBubble');
        const header = document.getElementById('aiHeader');
        const msg = document.getElementById('aiMsg');

        if (!bubble || !header || !msg) return; // Safety check

        // Set the content
        header.innerText = title;
        msg.innerText = message;

        // Show the bubble
        bubble.classList.add('show');

        // Auto-hide after 4 seconds
        // setTimeout(() => {
        //     bubble.classList.remove('show');
        // }, 4000);
    }

    function closemsg(){
        const bubble = document.getElementById('aiBubble');	
        // $(bubble).fadeOut();
        bubble.classList.remove('show');
    }

    // Ensure the welcome message triggers after the page loads
    document.addEventListener('DOMContentLoaded', () => {
        // Small delay so the user sees the animation after the page appears
        //setTimeout(() => {
            showAIMessage("", "Take a moment to review the context and guidelines before continuing to the Message Centre and Response screens.");
        //}, 500);
    });
    function toggleSidebar() {
        const sidebar = document.getElementById('digisim-sidebar');
        const overlay = document.getElementById('digisim-sidebarOverlay');

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

    
</script>

</body>
</html>
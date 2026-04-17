<?php

session_start();

include("../include/dataconnect.php");
include("digisim_data.php");

$ro_id = $_GET['game_id'];

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
/* SAME STYLES (UNCHANGED) */
.main-layout { display: flex; transition: all 0.3s ease; }
.split-guideline-wrapper { display: flex; height: 560px; width: 100%; padding: 20px 10px; }
.guideline-visual { flex: 1; background: #f8fafc; }
.guideline-visual img { width: 100%; height: 100%; object-fit: cover; }
.guideline-content-area { flex: 1.2; display: flex; flex-direction: column; padding: 0px; height: 100%; overflow: hidden; }
.rich-text-uppercase { letter-spacing: 1px; line-height: 1.8; color: #4a5568; flex: 1; overflow-y: auto; padding: 0px 20px; scrollbar-width: thin; scrollbar-color: #717172 #f7fafc; }
.rich-text-uppercase li { margin-bottom: 15px; padding-left: 5px; }

@media (max-width: 600px) {
    .split-guideline-wrapper { flex-direction: column; height: 550px; margin-top: 17px; }
    .guideline-visual { height: 250px; }
    .guideline-content-area { padding: 20px 0px; }
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

$image_html = '<img src="images/BG Play guideline.jpg" alt="Guideline Image" class="responsive-img">';

$content_body = '
    <h2 class="intro-title">🎯 How to Approach This Simulation</h2>

    <p>
    In this simulation, you will be placed in a dynamic and evolving scenario where your decisions will directly influence outcomes. Your goal is to respond thoughtfully, balancing speed, accuracy, and strategic thinking.
    </p>

    <br>• <strong>Understand the Situation:</strong> Carefully read all provided information before making decisions.
    <br>• <strong>Think Critically:</strong> Evaluate options logically and anticipate consequences.
    <br>• <strong>Balance Priorities:</strong> Consider risk, impact, and urgency while responding.
    <br>• <strong>Consider Stakeholders:</strong> Your actions may affect multiple parties—act responsibly.
    <br>• <strong>Stay Focused:</strong> Keep the overall objective in mind throughout the exercise.

    <br><br>
    <p>
    Remember, there may not always be a single “correct” answer — what matters is how effectively you justify and execute your decisions.
    </p>
';

/* SAME STRUCTURE */
$full_page_content = <<<EOD
<div class="main-layout" id="mainLayout">
    <main class="content-card">
        <div class="split-guideline-wrapper">
            
            <div class="guideline-visual">
                $image_html
            </div>

            <div class="guideline-content-area">
                <div class="rich-text-uppercase">
                    $content_body
                </div>
            </div>

        </div>
    </main>
</div>
EOD;

echo $full_page_content;

?>

<!-- FOOTER -->
<div class="footer-wrapper">
    

    <div class="footer-action">
        <div class="arrow-nav">
            <div class="arrow-btn left" onclick="goPrev()">PREVIOUS</div>
            <div class="arrow-btn right" onclick="goNext(<?php echo $ro_id; ?>)">NEXT</div>
        </div>
    </div>
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

<script>

const GAME_ID = <?php echo (int)$ro_id ?>;

function goPrev(){
    window.location.href = 'digisim_casestudy.php?game_id=' + GAME_ID;
}

function goNext(){
    window.location.href = 'digisim_messageCenter.php?game_id=' + GAME_ID;
}

/* JS  */

function showAIMessage(title, message) {
    const bubble = document.getElementById('aiBubble');
    const header = document.getElementById('aiHeader');
    const msg = document.getElementById('aiMsg');

    header.innerText = title;
    msg.innerText = message;

    bubble.classList.add('show');

    setTimeout(() => {
        bubble.classList.remove('show');
    }, 4000);
}

function toggleSidebar() {
    const sidebar = document.getElementById('digisim-sidebar');
    const overlay = document.getElementById('digisim-sidebarOverlay');

    sidebar.classList.toggle('active');
    overlay.classList.toggle('active');
}

(function() {
    history.pushState(null, null, window.location.href);

    window.onpopstate = function(event) {
        const popup = document.getElementById('exitPopup');
        if (popup) {
            popup.style.display = 'flex';
        }
        history.pushState(null, null, window.location.href);
    };
})();

function closeExitPopup() {
    document.getElementById('exitPopup').style.display = 'none';
}

function handleLogout() {
    // logout logic
}

</script>

</body>
</html>
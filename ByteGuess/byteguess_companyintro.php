<?php

session_start();

include("../include/coreDataconnect.php");

include("byteguess_data.php");




$cg_id = $_GET['game_id'];
$stmt = $conn->prepare("SELECT * FROM tb_event_user_score WHERE user_id = ? AND event_id = ? AND mod_game_id = ?");

$stmt->bind_param("iii", $_SESSION['user_id'], $_SESSION['event_id'], $cg_id);

$stmt->execute();

$row = $stmt->get_result()->fetch_assoc();

$game_status = $row['game_status'];
if($game_status != 'completed') {
    $upd = $conn->prepare(
        "UPDATE tb_event_user_score 
        SET game_status = 'in_progress' 
        WHERE mod_game_id = ? and event_id = ? and user_id = ?"
    );

    // Bind the parameters
    // "iiss" corresponds to: integer, integer, string, string
    $upd->bind_param("iii", $cg_id, $_SESSION['event_id'], $_SESSION['user_id']);
    $upd->execute();
}

// Execute the statement
// if ($upd->execute()) {
//     echo "Record updated successfully.";
// } else {
//     echo "Error updating record: " . $conn->error;
// }

$companydata = new GameRound();

 $allCompanies = $companydata->getCompanies($conn, $cg_id);

if (!$allCompanies) {
    die("Invalid game ID");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" type="text/css" href="css_site/byteguess.css">
<link rel="stylesheet" type="text/css" href="css_site/footer.css">
<link rel="stylesheet" href="assets/fontawesome/css/all.min.css">
<script src="https://kit.fontawesome.com/84b78c6f4c.js" crossorigin="anonymous"></script>

<style>

    .main-layout {
        display: flex;
        transition: all 0.3s ease;
    }

    /* Card internal split */

    .split-guideline-wrapper {

        display: flex;

        height: 560px; /* Match your content-card max-height */

        width: 100%;

        padding: 20px 10px;

    }



    .guideline-visual {

        flex: 1;

        background: #f8fafc;

    }



    .guideline-visual img {

        width: 100%;

        height: 100%;

        object-fit: cover;

    }



    .guideline-content-area {

        flex: 1.2; /* Text side is slightly wider */

        display: flex;

        flex-direction: column;

        padding: 0px;
        
        display: flex;

        flex-direction: column;

        height: 100%; /* Important: takes height from the parent .split-guideline-wrapper */

        overflow: hidden; /* Prevents the whole area from scrolling */

    }



    /* Uppercase Rich Text Styling */

    .rich-text-uppercase {

        text-transform: uppercase;

        letter-spacing: 1px;

        line-height: 1.8;

        color: #4a5568;

        flex: 1; /* Grows to take up all space between title and footer */

        overflow-y: auto; /* Enables the scrollbar */

        padding: 0px 20px;

        

        /* Firefox scrollbar styling */

        scrollbar-width: thin;

        scrollbar-color: #717172 #f7fafc;

    }



    .rich-text-uppercase li {

        margin-bottom: 15px;

        padding-left: 5px;

    }



    /* Mobile Responsiveness */

    @media (max-width: 600px) {

        .split-guideline-wrapper {

            flex-direction: column;

            height: 550px;

            margin-top: 17px;
        }

        

        .guideline-visual {

            height: 250px;

        }

        

        .guideline-content-area {

            padding: 20px 20px;

        }

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

</style>

</head>

<body>
<?php
        $stmt = $conn->prepare("
            SELECT cg_id, cg_ex_context_image, cg_ex_context_desc
            FROM card_group WHERE cg_id=$cg_id
        ");
        $stmt->execute();
        $result = $stmt->get_result();
        $template = $result->fetch_assoc();
?>
<!-- <div class="main-layout" id="mainLayout"> -->
    <?php include("sidenav.php"); ?>
<div class="main-layout" id="mainLayout">

    

    <main class="content-card">

    <div class="split-guideline-wrapper">

        

        

        <div class="guideline-visual">

            <?php if($template['cg_ex_context_image'] == null || $template['cg_ex_context_image'] == '') { ?>
                <img src="images/BG Context.jpg" alt="Company Image" class="responsive-img">
            <?php } else { ?>
                <img src="<?php echo $template['cg_ex_context_image']; ?>" alt="Company Image" class="responsive-img">
            <?php } ?>

        </div>

        <div class="guideline-content-area">
            <div class="rich-text-uppercase">
                
                <?php 
                    // 1. Remove markdown bold stars
                    $clean_data = str_replace('**', '', $template['cg_ex_context_desc']);

                    // 2. Extract the Heading (text before the first "1.")
                    $split_heading = preg_split('/(?=1\.\s)/', $clean_data, 2);
                    $heading = trim($split_heading[0] ?? 'Instructions');
                    
                    // 3. Split the remaining content into individual points
                    // This looks for "1. ", "2. ", etc., and starts a new array element
                    $points_content = $split_heading[1] ?? '';
                    $points = preg_split('/(?=\d\.\s)/', $points_content, -1, PREG_SPLIT_NO_EMPTY);
                ?>

                <h2 class="intro-title"><?php echo htmlspecialchars($heading); ?></h2>

                <div class="instruction-body">
                    <?php foreach ($points as $point): 
                        // Separate the "Number. Title" from the "Description"
                        $parts = explode(':', $point, 2);
                        $title = trim($parts[0]);
                        $desc = isset($parts[1]) ? trim($parts[1]) : '';
                    ?>
                        <div class="step-container">
                            <span class="step-title"><?php echo htmlspecialchars($title); ?></span>
                            <p class="step-text"><?php echo htmlspecialchars($desc); ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>

            </div>
        </div>

        



    </div>

    </main>
</div>

  <!-- FOOTER -->
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

<div class="footer-action" id="footer-action">
    <div class="footer-container">
        <div class="nav-controls">
            <div class="button-nav">
                <!-- <button class="finish-btn" onclick="goToResults()">Finish Review</button> -->
            </div>

            <div class="arrow-nav">
                <div class=" arrow-btn " ></div>
                <div class=" arrow-btn right-btn" onclick="goNext(<?php echo $cg_id; ?>)">NEXT</div>
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
    function goNext(comId){
        // const n = comId;
        window.location.href = 'byteguess_guideline.php?game_id=' + comId;
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

        // Auto-hide after 4 seconds
        setTimeout(() => {
            bubble.classList.remove('show');
        }, 4000);
    }
    const company = <?php echo json_encode(trim($allCompanies['cg_description'])); ?>;

    showAIMessage("Welcome!", "You've welcome to " + company + " company for today.");

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
        window.location.href = '../teaminstance_be.php?user_id=<?php echo $_SESSION['user_id']; ?>&code=<?php echo $_SESSION['event_code']; ?>';
    }
</script>

</body>
</html>

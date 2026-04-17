<?php

session_start();



include("../include/coreDataconnect.php");



include("byteguess_data.php");



$cg_id = $_GET['game_id'];



$companydata = new GameRound();



$allCompanies = $companydata->getCompanies($conn, $cg_id);







// $title = "Game Guidelines"; // Default fall back



// if (preg_match('/\*\*(.*?)\*\*/', $allCompanies['cg_guidelines'], $matches)) {



//     echo $title = $matches[1]; // Captures "How to Play & Complete the Game"



// }







// 2. Remove that title from the string so it doesn't repeat in the content



$clean_string = preg_replace('/\*\*.*?\*\*/', '', $allCompanies['cg_guidelines'], 1);







// 3. Split the remaining content by the numbers (1., 2., etc.)



// $parts = preg_split('/\s*\d+\.\s+/', $clean_string, -1, PREG_SPLIT_NO_EMPTY);



// Get the current step from URL, default to 1



$currentStep = isset($_GET['step']) ? (int)$_GET['step'] : 1;



$parts = preg_split('/(?=\d+\.)/', $clean_string, -1, PREG_SPLIT_NO_EMPTY);



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



        flex: 1.2;



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



            height: 560px;



            margin-top: 7px;



        }



        



        .guideline-visual {



            height: 250px;



        }



        



        .guideline-content-area {



            padding: 20px 20px;



        }



    }



</style>



</head>



<body>

<?php

        $stmt = $conn->prepare("

            SELECT cg_id, cg_guidelines, cg_play_guide_image

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

            <?php if($template['cg_play_guide_image'] == null || $template['cg_play_guide_image'] == '') { ?>
                <img src="images/BG Play guideline.jpg" alt="Company Image" class="responsive-img">
            <?php } else { ?>
                <img src="<?php echo $template['cg_play_guide_image']; ?>" alt="Company Image" class="responsive-img">
            <?php } ?>

        </div>

        <div class="guideline-content-area">
            <div class="rich-text-uppercase">
                
                <?php 
                    // 1. Remove markdown bold stars
                    $clean_data = str_replace('**', '', $template['cg_guidelines']);

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

                <div class=" arrow-btn left-btn" onclick="goPrev()">PREVIOUS</div>

                <div class=" arrow-btn right-btn" onclick="goNext(<?php echo $allCompanies['cg_id']; ?>)">NEXT</div>

            </div>

        </div>
    </div>
</div>













<script>

const GAME_ID = <?php echo (int)$cg_id ?>;







function goPrev(){



    window.location.href = 'byteguess_companyintro.php?game_id=' + GAME_ID;



}







function goNext(){



    window.location.href = 'byteguess_demo19.php?game_id=' + GAME_ID;



}



function toggleSidebar() {

    const sidebar = document.getElementById('sidebar');

    const overlay = document.getElementById('sidebarOverlay');



    // Toggle the 'active' class on both

    sidebar.classList.toggle('active');

    overlay.classList.toggle('active');

}

</script>



</body>

</html>


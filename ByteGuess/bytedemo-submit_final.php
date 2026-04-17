<?php 



session_start();



$isCompleted = $_SESSION['byteguess_completed'] ?? false;



include("../include/coreDataconnect.php");







$cg_id = intval($_GET['game_id'] ?? 0);



if ($cg_id <= 0) {



    die("Invalid game");



}







/* Fetch game */



$stmt = $conn->prepare("SELECT * FROM card_group WHERE cg_id = ?");



$stmt->bind_param("i", $cg_id);



$stmt->execute();



$game = $stmt->get_result()->fetch_assoc();







if (!$game || empty($game['cg_answer'])) {



    die("No final answers found");



}



?>



<!DOCTYPE html>



<html lang="en">



<head>



<meta charset="UTF-8">



<title>Final Decision</title>







<link rel="stylesheet" href="css_site/byteguess_finalsubmit.css">



<script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>



<link rel="stylesheet" href="assets/fontawesome/css/all.min.css">



<script src="https://kit.fontawesome.com/84b78c6f4c.js" crossorigin="anonymous"></script>



<style>



.option-card {



    border: 2px solid #ddd;



    border-radius: 12px;



    padding: 20px;



    /* margin-bottom: 15px; */



    cursor: pointer;



    transition: all 0.3s ease;



    background-color: #fff;



}







.option-card:hover {



    background-color: #f8fbff;



    border-color: #b3d4ff;



}







.option-card.selected-card {



    border-color: #007bff;



    background-color: #e7f1ff;



    box-shadow: 0 0 10px rgba(0, 123, 255, 0.2);



}





</style>



</head>







<body>



<?php include("sidenav.php"); ?>



<div class="container">



    <!-- TITLE -->



    <div class="title">



        <div>

            <h2>Select Your Final Conclusion</h2>



            <p>



                Based on the cards you reviewed, analyze the patterns and choose the most logical conclusion.



                Once submitted, this decision cannot be changed.



            </p>

        </div>



        <div>

            <button class="review-btn" onclick="reviewCards()">Review Cards</button>

        </div>



    </div>







    <!-- OPTIONS -->



    <div class="options-wrapper">



        <div class="options-grid" id="display-area"></div>



    </div>







</div>







<!-- FOOTER -->



<div class="footer">



    <div style="color:#666;font-size:13px;">



        Selection is final. Double check your cards.



    </div>



    <button class="submit-btn" onclick="submitDecision()">Submit Final Decision</button>



</div>







<script>



    const GAME_ID = <?php echo $cg_id; ?>;







const gameSummary = JSON.parse(



    sessionStorage.getItem("gameSummary")



);







if (!gameSummary) {



    alert("Game summary missing. Please review cards again.");



    window.location.href =



        "byteguess_demo19.php?game_id=" + GAME_ID;



}



/* GLOBAL STATE */



let finalData = [];



let currentSelection = null;



/* LOAD DATA */



document.addEventListener("DOMContentLoaded", function () {







    finalData = <?php echo json_encode(json_decode($game['cg_answer'], true)); ?>;







    console.log("Loaded finalData:", finalData);







    if (!Array.isArray(finalData)) {



        console.error("finalData is not an array");



        return;



    }







    let html = "";







    finalData.forEach((item, index) => {



        html += `



        <div class="option-card" onclick="selectOption(${index}, this)">



            <div class="option-text">



                <h4>${item.title}</h4>



                <p>${item.answer ?? "No description available"}</p>



            </div>



            <div class="option-image">



                <img src="../assets/images/cu_image.jpeg" width="50" height="50">



            </div>



        </div>`;



    });







    document.getElementById("display-area").innerHTML = html;



});







/* CARD SELECTION */



function selectOption(index, element) {







    if (!finalData[index]) {



        console.error("Invalid index", index);



        return;



    }







    currentSelection = finalData[index];







    document.querySelectorAll(".option-card").forEach(card =>



        card.classList.remove("selected-card")



    );







    element.classList.add("selected-card");







    console.log("Selected:", currentSelection);



}







/* SUBMIT */



function submitDecision() {







    if (!currentSelection) {



        alert("Please select a conclusion first!");



        return;



    }



    let hintscore = sessionStorage.getItem("totalHintPenalty");



    let finalscore = (currentSelection.score ?? 0) - (hintscore ? parseInt(hintscore) : 0);

    if(finalscore < 0) finalscore = 0;

    // console.log("Final Score:", finalscore);





    const finalPayload = {



        game_id: GAME_ID,



        game_summary: {



            ...gameSummary,



            final_decision: {



                order : currentSelection.order,



                title : currentSelection.title,



                answer: currentSelection.answer



            },



            final_score: finalscore



        }



    };







    console.log("Final Payload:", finalPayload);







    $.ajax({



        url: 'byteguess_event_user_score.php',



        type: 'POST',



        contentType: 'application/json',



        data: JSON.stringify({



            game_id: GAME_ID,



            game_summary: {



                ...gameSummary,



                final_decision: {



                    order : currentSelection.order,



                    title : currentSelection.title,



                    answer: currentSelection.answer



                },



                final_score: finalscore



            }



        }),



        success: function (response) {



            if (response.status === 'success') {



                sessionStorage.removeItem("gameSummary");



                window.location.href = response.redirect;



            } else {



                alert(response.message);



            }



        }



    });



}











/* NAV ACTIONS */



function reviewCards() {



    localStorage.setItem('currentPageName', 'New Updated Name');



    // Then when they go back...

    window.history.back();

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
<?php
ob_start();
session_start();

if(!isset($_GET['game_id'])){
	header('Location: index.php');
	exit;
}
else{
	include("../include/dataconnect.php");
	//include("include/randam.php");
	include("roundfunction.php");
	
	$userid 	= $_SESSION['user_id'];
	$gameid		= $_GET['game_id'];

	// echo $accountpkid;
	// print_r($instanceDetails);
	//=== Call Class
	$gameround = new GameRound();

	//== Instance Game Details
	$insGameDetails = $gameround->insGameDetails($conn,$userid,$gameid);
	// print_r($insGameDetails);	
	//=== Side Menu
	$topMenu		= $gameround->topnav();	
	//=== Body Load
	$bodyLoad		= $gameround->bodyLoad($insGameDetails);	
	//=== Results & Analysis
	// $resultsDiv		= $gameround->resultsDiv();
	
	//print '<pre>';
	//print_r($insGameDetails);
}
?>
<!doctype html>
<html lang="en">
<head>
	<meta charset="UTF-8" />
	<title>Micro Game</title>
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link rel="stylesheet" type="text/css" href="css_site/byteguess_finalsubmit.css">
	<link rel="stylesheet" type="text/css" href="css_site/byteguess.css">
	<?php /*?><link rel="stylesheet" type="text/css" href="css_site/msgstyles.css?rand=97" /><?php */?>
	<link rel="stylesheet" type="text/css" href="css_site/chat.css?rand=97" />
	<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
	<script src="js_site/instance.js?rand=97" type="text/javascript"></script>
	<script>window['cround'] = <?php echo $insGameDetails['current_round']?>; 
	        window['inscomp'] = <?php echo $insGameDetails['completed']?>;
	</script>
</head>
<body>
<div id="pleaswait" >
	<div class="pleasewait1"></div>
	<div class="pleasewait2">
		<table width="100%" border="0" cellspacing="0" cellpadding="0" height="100%">
		  <tr>
			<td align="center" valign="middle" height="100%" class="waitxt"><p>processing please wait</p><img src="images/pageloader.gif" border="0" /></td>
		  </tr>
		</table>
	</div>
</div>

<!-- <?php //echo $sideMenu.$bodyLoad?> -->
 <?php echo $topMenu.$bodyLoad?>
 <!-- FOOTER -->

    <div class="footer-action" >

        <div class="nav-controls">

            <div class="button-nav">

                <!-- <button class="finish-btn" onclick="goToResults()">Finish Review</button> -->

            </div>



            <div class="arrow-nav">

                <div class=" arrow-btn left" onclick="goPrev()"></div>

                <div class=" arrow-btn right" onclick="goNext(<?php echo $cg_id; ?>)">Next</div>

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
<script>window['msgai'] = <?php echo $insGameDetails['msgAI']?>;</script>
<script src="js_site/gameplay.js?rand=97" type="text/javascript"></script>
<?php /*?><script src="js_site/msgscript.js?rand=97" type="text/javascript"></script><?php */?>

</body>
</html>
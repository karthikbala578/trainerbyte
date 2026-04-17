<?php

include("include/coreDataconnect.php");

// ================================

// HARD-CODED DATA (DB SIMULATION)

// ================================

$rounds = [

    1 => ['score'=>8400,  'status'=>'completed'],

    2 => ['score'=>9150,  'status'=>'completed'],

    3 => ['score'=>7200,  'status'=>'completed'],

    4 => ['score'=>10100, 'status'=>'completed'],

    5 => ['score'=>8000,  'status'=>'active'],

    6 => ['score'=>null,  'status'=>'locked'],

    7 => ['score'=>null,  'status'=>'locked'],

    8 => ['score'=>null,  'status'=>'locked'],

    9 => ['score'=>null,  'status'=>'locked'],

];



$currentRound = 6;

$globalRank = '#12';

$totalScore = 42850;

?>

<!DOCTYPE html>

<html>

<head>

<meta charset="UTF-8">

<title>ERM Sandbox - Scorecard</title>



<link rel="stylesheet" type="text/css" href="css_site/byteguess_scorecard.css">

<script src="https://kit.fontawesome.com/84b78c6f4c.js" crossorigin="anonymous"></script>



</head>

<body>

<div class="app">



<!-- SIDENAV -->

<div class="sidenav" id="sidenav">

    <div class="sidenav-header">

        <div class="brand"><img src="<?php echo $website?>images/ERM sandbox.png"  alt="SARAS Simulations" height="50" title="SARAS Simulations" border="0" /></a></div>

    </div>



    <div class="nav">

        <a href="#"><i class="fa-solid fa-home"></i> Home</a>

        <a href="#" class="active"><i class="fa-solid fa-bar-chart"></i> Scorecard</a>

        <a href="#"><i class="fa-solid fa-chart-line"></i> Analytics</a>

        <a href="#"><i class="fa-solid fa-file-lines"></i> Reports</a>

        <a href="#"><i class="fa-solid fa-gear"></i> Settings</a>

    </div>



    <div class="score-box">

        <b>Total Aggregate Score</b><br>

        <div style="font-size:18px;font-weight:700;"><?php echo number_format($totalScore); ?></div>

        <small style="color:green;">+12% from Round 4</small>

    </div>



    <div class="support">Support</div>

</div>



<div class="nav-overlay" id="navOverlay" onclick="closeNav()"></div>



<!-- MAIN -->

<div class="main">



<div class="topbar">

    <div style="display:flex;align-items:center;gap:12px;">

        <div class="toggle-btn" onclick="toggleNav()">☰</div>

        <div class="header-title">

            <h2>Multi-Round Game Scorecard</h2>

            <p>Track your progression and review past decisions across all simulation cycles.</p>

        </div>

    </div>



    <div class="stats">

        <div class="stat">CURRENT ROUND<br><b><?php echo $currentRound; ?> / 10</b></div>

        <div class="stat">GLOBAL RANK<br><b><?php echo $globalRank; ?></b></div>

    </div>

</div>



<div class="grid">

<?php 

$firstLocked=false; 

$totalScore = 0;

foreach($rounds as $i=>$r){ 

    if (!is_null($r['score'])) {

        $totalScore += $r['score'];

    }?>



<?php if($r['status']=='completed'){ ?>

<div class="card completed">

    <div class="card-header">ROUND <?php echo str_pad($i,2,'0',STR_PAD_LEFT); ?><span>⚙</span></div>

    <div class="card-body">

        <div class="small">SCORE EARNED</div>

        <div class="score"><?php echo number_format($r['score']); ?></div>

        <a href="#" class="btn btn-black">View Game</a>

        <a href="#" class="btn btn-light">View De-briefing</a>

    </div>

</div>



<?php }elseif($r['status']=='active'){ ?>

<div class="card active active-card">

    <div class="card-header">ROUND <?php echo str_pad($i,2,'0',STR_PAD_LEFT); ?> <span class="active-tag">ACTIVE</span></div>

    <div class="card-body">

        <div class="small">ESTIMATED SCORE</div>

        <div class="score"><?php echo number_format($r['score']); ?></div>

        <a href="#" class="btn btn-play">▶ Resume Game</a>

    </div>

</div>



<?php }else{ ?>



<?php if(!$firstLocked){ $firstLocked=true; ?>

<div class="card locked-card">

    <div class="locked-header">ROUND <?php echo str_pad($i,2,'0',STR_PAD_LEFT); ?><span><i class="fa-solid fa-lock"></i></span></div>

    <div class="locked-body">

        <div class="big-lock"><i class="fa-solid fa-lock"></i></div>

        Complete Round <?php echo $i-1; ?> to unlock

    </div>

</div>

<?php }else{ ?>

<div class="card locked-card">

    <div class="locked-header">ROUND <?php echo str_pad($i,2,'0',STR_PAD_LEFT); ?><span><i class="fa-solid fa-lock"></i></span></div>

    <div class="locked-body simple-lock">Locked</div>

</div>

<?php } ?>



<?php } ?>

<?php } ?>

</div>



<div class="footer-btn">

    <a href="byteguess_companyintro.php">⬅ Return to Game Library</a>

</div>



</div>

</div>



<script>

function toggleNav(){

    const nav = document.getElementById('sidenav');

    const overlay = document.getElementById('navOverlay');



    nav.classList.toggle('open');

    overlay.style.display = nav.classList.contains('open') ? 'block' : 'none';

}



function closeNav(){

    document.getElementById('sidenav').classList.remove('open');

    document.getElementById('navOverlay').style.display = 'none';

}



</script>

</body>

</html>


<?php

require_once '../config.php';
require_once '../functions.php';

if (!is_admin_logged_in()) {
    redirect(BASE_URL . 'index.php');
}

// Check if this is a NEW game request
if (isset($_GET['new']) && $_GET['new'] == 1) {
    // Clear any existing game session
    unset($_SESSION['current_game_id']);
    $game_id = 0;
} else {
    // Get game ID if editing
    $game_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
}

$step = isset($_GET['step']) ? intval($_GET['step']) : 1;

// If game_id exists, fetch game data
$game_data = null;
if ($game_id > 0) {
    $query = "SELECT * FROM mg6_riskhop_matrix WHERE id = '$game_id'";
    $result = mysqli_query($conn, $query);
    if (mysqli_num_rows($result) > 0) {
        $game_data = mysqli_fetch_assoc($result);
    } else {
        redirect(ADMIN_URL . 'index.php');
    }
}

// Store game_id in session for easy access across steps
if ($game_id > 0) {
    $_SESSION['current_game_id'] = $game_id;
} elseif (isset($_SESSION['current_game_id']) && !isset($_GET['new'])) {
    // Only use session game_id if NOT creating a new game
    $game_id = $_SESSION['current_game_id'];
    // Fetch game data
    $query = "SELECT * FROM mg6_riskhop_matrix WHERE id = '$game_id'";
    $result = mysqli_query($conn, $query);
    if (mysqli_num_rows($result) > 0) {
        $game_data = mysqli_fetch_assoc($result);
    }
}

if ($step < 1) $step = 1;
if ($step > 4) $step = 4;

// If no game created yet and trying to access step 2+, redirect to step 1
if (!$game_id && $step > 1) {
    $step = 1;
}
$page_title = "";

switch ($step) {
    case 1:
        $page_title = "Configure Game Board";
        break;

    case 2:
        $page_title = "Configure Threats & Opportunities";
        break;

    case 3:
        $page_title = "Configure bonus, audit, wild cards";
        break;

    case 4:
        $page_title = "Review & Publish";
        break;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>RiskHOP - Configure Game Board</title>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<link href="https://cdn.jsdelivr.net/npm/quill@1.3.7/dist/quill.snow.css" rel="stylesheet">
<style>

/* GLOBAL FONT */
body{
font-family: "Inter", -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif;
color:#111827;
line-height:1.5;
}

/* PAGE MAIN HEADING */
.page-title,
h1{
font-size:26px;
font-weight:600;
color:#111827;
margin:0 0 10px 0;
}

/* SECTION TITLE (Card heading) */
.section-title,
h2{
font-size:22px;
font-weight:600;
color:#111827;
margin:0 0 8px 0;
}

/* SUB HEADING */
.sub-heading,
h3{
font-size:18px;
font-weight:600;
color:#374151;
margin:0 0 6px 0;
}

/* SMALL TITLE */
.small-title,
h4{
font-size:16px;
font-weight:600;
color:#374151;
margin:0 0 4px 0;
}

/* SUBTITLE / DESCRIPTION */
.subtitle{
font-size:15px;
color:#6b7280;
font-weight:400;
margin-bottom:12px;
}

/* PARAGRAPH TEXT */
p,
.paragraph{
font-size:15px;
color:#6b7280;
line-height:1.6;
margin-bottom:14px;
}

/* LABELS */
label{
font-size:16px;
font-weight:600;
color:#374151;
}

/* SMALL HELP TEXT */
.helper-text{
font-size:13px;
color:#9ca3af;
}

.admin-wrapper{
display:flex;
min-height:100vh;
width:100%;
}

.admin-content{
flex:1;
 padding:40px 50px;  
background:#f5f6fa;
height:100vh;
display:flex;
flex-direction:column;
overflow:hidden;
width:100%;
box-sizing:border-box;
margin-left:80px;
}
.admin-sidebar{
width:80px;
position:fixed;
left:0;
top:0;
height:100vh;
background:#fff;
border-right:1px solid #e5e7eb;
}


/* HEADER */

.content-header h1{
font-size:26px;
font-weight:600;
margin-bottom:25px;
}


/* STEP PROGRESS */

/* STEP PROGRESS */

.step-progress{
display:flex;
align-items:center;
gap:0;
}

.step{
display:flex;
align-items:center;
justify-content:center;
position:relative;
cursor:pointer;
width:160px;   /* increase spacing */
}

/* connector line */

.step:not(:last-child)::after{
content:"";
position:absolute;
top:50%;
left:50%;
width:140px;   /* increase this value */
height:2px;
background:#d1d5db;
transform:translateY(-50%);
z-index:1;
}
/* circle */

.step-number{
width:34px;
height:34px;
border-radius:50%;
background:#e5e7eb;
display:flex;
align-items:center;
justify-content:center;
font-weight:600;
font-size:14px;
border:2px solid #d1d5db;
z-index:2;
}

/* active */

.step.active .step-number{
background:#4f6df5;
color:#fff;
border-color:#4f6df5;
}

/* completed connector */

.step.active:not(:last-child)::after{
background:#4f6df5;
}

/* TOOLTIP */

.step::before{
content:attr(data-title);
position:absolute;
bottom:50px;
background:#111;
color:#fff;
padding:6px 10px;
border-radius:6px;
font-size:12px;
white-space:nowrap;
opacity:0;
transform:translateY(5px);
transition:all .2s ease;
pointer-events:none;
}

.step:hover::before{
opacity:1;
transform:translateY(0);
}
#descriptionEditor {
    height: 80px;
    border-radius: 10px;
}
/* CARD */
.card{
background:#fff;
border-radius:12px;
box-shadow:0 6px 20px rgba(0,0,0,0.05);
width:95%;


height: calc(100vh - 120px); /* adjust based on header */
    display: flex;
    flex-direction: column;

overflow:hidden;
padding:12px 18px;
}
.card h2{
    margin: 0 0 6px 0;   /* 🔥 proper spacing */
    font-size:22px;
    font-weight:600;
}

.card p{
    margin: 0 0 14px 0;  /* 🔥 breathing space */
    color:#6b7280;
    font-size:15px;
}
.card form{
margin-top:2px;
}
/* FORM */
.form-group label{
    margin-bottom:4px;  /* was 10px */
    font-size:14px;
}
.form-grid{
display:grid;
grid-template-columns:1fr 1fr;
 gap:8px;            /* was 12px */
    margin-bottom:4px;
}

.form-group{
display:flex;
flex-direction:column;
margin-bottom:2px;
position:relative;
}

.field-error{
  min-height:16px;  /* was 22px */
    font-size:12px;
    margin-top:2px;
color:#ef4444;

line-height:1.2;
}

.input-error{
border-color:#ef4444 !important;
box-shadow:0 0 0 2px rgba(239,68,68,0.15);
}

.form-group label{
font-size:16px;
 margin-bottom:10px;
font-weight:600;
color:#333;
}
.form-group input,
.form-group textarea{
padding:8px 10px;   /* was 12px 14px */
    font-size:14px;
border:1px solid #d1d5db;
border-radius:8px;
transition:all 0.2s;
}
.form-group input:focus,
.form-group textarea:focus{
outline:none;
border-color:#4f6df5;
box-shadow:0 0 0 2px rgba(79,109,245,0.15);
}



/* BOARD MATRIX */
.matrix-grid{
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap:14px;              /* 🔥 increased spacing */
    margin-top:10px;       /* 🔥 little breathing space from label */
      justify-items: center; 
}
.matrix-option{
    width: 100%;
    max-width: 180px;
    height:90px;           /* 🔥 slight increase */
    font-size:13px;
    padding: 8px;          /* 🔥 better inner spacing */
}
.form-group .matrix-grid{
    margin-top: 15px;
}
#error_board_size{
    min-height:20px;
    margin-top:6px;
}
.form-group:has(.matrix-grid){
    margin-top: 10px;   /* space above label */
}
.matrix-option{
border:1px solid #d1d5db;
border-radius:12px;
cursor:pointer;
font-size:14px;
background:#fff;
transition:all 0.25s ease;

/* layout */
display:flex;
flex-direction:column;
align-items:center;
justify-content:center;


/* better UI */
box-shadow:0 2px 6px rgba(0,0,0,0.04);
}
.matrix-option:hover{
border-color:#4f6df5;
}

.matrix-option.active{
border:2px solid #4f6df5;
background:#eef2ff;
transform:translateY(-2px);
box-shadow:0 6px 14px rgba(79,109,245,0.2);
}

.matrix-icon{
font-size:24px;
margin-bottom:8px;
color:#9ca3af;
}


/* BUTTON */

.continue-btn{
  margin-top:6px;     /* was 10px */
    padding:10px 18px;  
margin-left:auto;
display:flex;
align-items:center;
gap:8px;
background:#4f6df5;
color:#fff;
border:none;
border-radius:8px;
cursor:pointer;
font-size:16px;
font-weight:500;
}
.top-header{
display:flex;
align-items:center;
justify-content:space-between;
margin-bottom:8px; 
width:100%;
}

.header-title h1{
font-size:26px;
font-weight:600;
margin:0;
}

.matrix-preview{
display:grid;
justify-content:center;
  margin-bottom: 6px;
    padding: 4px;
background:#f8fafc; /* light board bg */
border-radius:6px;
}

.matrix-preview div{
width:6px;
height:6px;
background:#94a3b8; /* darker */
border:1px solid #e2e8f0; /* 🔥 grid lines visible */
}

.matrix-option.active .matrix-preview div{
background:#4f6df5;
}
textarea{
resize:none;
height:60px;
}
.matrix-error{
border:2px solid #ef4444 !important;
background:#fff5f5;
}
.disabled-matrix .matrix-option {
    pointer-events: none;
    opacity: 0.6;
    cursor: not-allowed;
}
.locked {
    pointer-events: none;
    opacity: 0.6;
    cursor: not-allowed;
}

.card-body{
    flex: 1;
    min-height: 0;
    
    padding-right: 6px;
}
</style>

</head>
<body>

<div class="admin-wrapper">

    <div class="admin-sidebar"></div>

    <div class="admin-content">

        <div class="top-header">

            <div class="header-title">
               <h1 class="page-title"><?php echo $page_title; ?></h1>
            </div>
            <!-- STEP INDICATOR -->
            <div class="step-progress">


                <div class="step <?php echo $step >= 1 ? 'active' : ''; ?>" data-step="1" data-title="General Info">
                <div class="step-number">1</div>
                </div>

                <div class="step <?php echo $step >= 2 ? 'active' : ''; ?>" data-step="2" data-title="Board Setup">
                <div class="step-number">2</div>
                </div>

                <div class="step <?php echo $step >= 3 ? 'active' : ''; ?>" data-step="3" data-title="Game Mechanism">
                <div class="step-number">3</div>
                </div>

                <div class="step <?php echo $step >= 4 ? 'active' : ''; ?>" data-step="4" data-title="Review & Publish">
                <div class="step-number">4</div>
                </div>


            </div>

        </div>






       <div class="card">

    <div class="card-body">
        <?php if($step == 1): ?>
            <?php include 'steps/step1_general_info.php'; ?>
        <?php elseif($step == 2): ?>
            <?php include 'steps/step2_board_setup.php'; ?>
        <?php elseif($step == 3): ?>
            <?php include 'steps/step3_game_mechanism.php'; ?>
        <?php elseif($step == 4): ?>
            <?php include 'steps/step4_review_publish.php'; ?>
        <?php endif; ?>
    </div>

</div>
    </div>
</div>



<script src="https://cdn.jsdelivr.net/npm/quill@1.3.7/dist/quill.min.js"></script>
</body>
</html>
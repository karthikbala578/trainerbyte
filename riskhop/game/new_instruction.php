<?php
/**
 * RiskHOP Game Instructions - Enhanced Interactive Design
 * Mandatory instruction page before starting game
 */

require_once '../config.php';
require_once '../functions.php';
require_once 'game_engine.php';

// Get game ID from URL
$game_id = isset($_GET['game_id']) ? clean_input($_GET['game_id']) : null;

if (!$game_id) {
    redirect(GAME_URL . 'index.php');
}
$session = get_current_session();

$is_iframe = isset($_GET['from']) && $_GET['from'] === 'help';

$from = $_GET['from'] ?? '';

if ($session && $session['game_status'] == 'playing' && !$is_iframe && $from !== 'review') {
    redirect(GAME_URL . 'board.php?game_id=' . $session['matrix_id']);
}
// Get game data
$game_data = get_game_data($game_id);

if (!$game_data) {
    redirect(GAME_URL . 'index.php');
}

$game = $game_data['game'];
// Fetch legends
$legends_query = $conn->query("SELECT * FROM mg6_legends WHERE status = 1 ORDER BY id ASC");
$legends = [];

if ($legends_query && $legends_query->num_rows > 0) {
    while ($row = $legends_query->fetch_assoc()) {
        $legends[] = $row;
    }
}

// Fetch instructions
$instructions_query = $conn->query("
    SELECT * FROM mg6_player_instructions 
    WHERE status = 1 
    ORDER BY id ASC
");

$instructions = [];
while ($row = $instructions_query->fetch_assoc()) {
    $instructions[$row['section_key']] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instructions - <?php echo htmlspecialchars($game['game_name']); ?></title>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    
    <style>
  html, body {
    height: 100%;
    margin: 0;
    overflow: hidden;
    background: #f9fafb;
    color: #0f172a;
}
body {
    font-family: 'Inter', sans-serif;
    font-weight: 400;
    letter-spacing: -0.01em;
    -webkit-font-smoothing: antialiased;
}
/* ===== Layout ===== */
.layout {
    display: flex;
    height: 100vh;
}

/* ===== Sidebar ===== */
.left-nav {
    width: 260px;
    height: 100vh;
    position: fixed;
    top: 0;
    left: 0;

    background: #f1f3f9;
    border-right: 1px solid #e5e7eb;
    padding: 30px 20px;
}

/* Nav Items */
.nav-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 16px;
    border-radius: 12px;
    font-size: 14px;
    font-weight: 500;
    letter-spacing: -0.01em;
    color: #6b7280;
    cursor: pointer;
    margin-bottom: 8px;
    transition: all 0.2s ease;
}

.nav-item i {
    font-size: 14px;
}

.nav-item:hover {
    background: #e8ecff;
    color: #4f46e5;
}

.nav-item.active {
    background: #e0e7ff;
    color: #4f46e5;
    font-weight: 600;
}

/* ===== Main Content ===== */
.main-content {
    position: fixed;
    left: 260px;
    top: 0;
    right: 0;
    bottom: 0;

    padding: 60px 70px; /* reduced side padding */

    overflow-y: auto;
    overflow-x: hidden;

    scroll-behavior: smooth;

  background: linear-gradient(to bottom, #fdfefe, #f7f9fc);

    display: block; /* remove center flex alignment */
}

/* Hide scrollbar cleanly */
.main-content::-webkit-scrollbar {
    width: 0;
    height: 0;
}

.main-content {
    scrollbar-width: none;
}

/* SECTION STYLE */
.instruction-section {
    margin: 0 auto;
    padding: 40px 0;   /* reduced from 60px */
    border-bottom: 1px solid #f1f5f9;
}

/* HERO SECTION (Overview) */
.hero-section {
    min-height: auto;  /* remove 80vh */
    padding-top: 40px;
    padding-bottom: 40px;
}
.hero-section .game-tag {
    margin-bottom: 28px;
}
.instruction-section.active-section {
    background: white;
    padding: 60px;
    border-radius: 18px;
    box-shadow: 0 20px 60px rgba(15, 23, 42, 0.06);
}
.start-game-btn {
    position: absolute;
    right: 80px;
    top: 60px;
}
.instruction-section h2,
.instruction-section h3,
.instruction-section h4 {
    font-weight: 700;
    color: #0f172a;
    letter-spacing: -0.02em;
}

.legend-text h4 {
    font-size: 18px;
    margin-bottom: 6px;
}
/* Small section tag */
.game-tag {
    display: inline-flex;
    align-items: center;
    justify-content: center;

    font-size: 12px;
    font-weight: 600;
    letter-spacing: 1.5px;
    text-transform: uppercase;

    color: #4f46e5;
    background: #eef2ff;

    padding: 8px 18px;
    border-radius: 999px;

    border: 1px solid #e0e7ff;

    margin-bottom: 24px;
}
/* Main Title (Hero Feel) */
.game-title {
    font-size: 64px;          /* larger than section title */
    font-weight: 400;         /* NOT bold */
    line-height: 1.08;
    letter-spacing: -0.03em;

    margin: 20px 0 30px 0;

    color: #0f172a;           /* slightly deeper tone */
    max-width: 950px;
}
.instruction-section h2 {
    font-size: 32px;
    font-weight: 500;
    letter-spacing: -0.01em;
    margin-bottom: 12px;
    color: #1e293b;
}
/* Description */
.game-description {
    font-size: 20px;
    line-height: 1.75;
    font-weight: 400;          /* lighter like screenshot */
    color: #475569;            /* soft slate grey */
    max-width: 800px;          /* tighter reading width */
    margin-top: 10px;
    white-space: pre-line;     /* allows line breaks from DB */
}
/* Text content */
.instruction-section p,
.instruction-section li {
    font-size: 17px;
    line-height: 1.9;
    font-weight: 400;
    color: #4b5563;
}

.instruction-section ul {
    padding-left: 20px;
    margin-top: 15px;
}
.instruction-section ul li {
    margin-bottom: 10px;
}
.instruction-section strong {
    font-weight: 600;
    color: #111827;
}
.legend-item {
    background: #ffffff;
    border: 1px solid #eef2f7;
    border-radius: 18px;
    padding: 28px;

    display: flex;
    flex-direction: column;
    align-items: flex-start;
    gap: 18px;

    transition: all 0.3s ease;
}

.legend-item:hover {
    transform: translateY(-6px);
    box-shadow: 0 20px 40px rgba(15, 23, 42, 0.08);
}

.legend-icon {
    width: 52px;
    height: 52px;
    border-radius: 14px;
    background: #eef2ff;

    display: flex;
    align-items: center;
    justify-content: center;

    color: #4f46e5;
    font-size: 18px;
}

/* ===== Button ===== */
.start-game-btn {
    margin-top: 40px;
    padding: 16px 42px;
    border: none;
    border-radius: 14px;
    background: linear-gradient(135deg, #6366f1, #4f46e5);
    color: white;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
}

.start-game-btn:hover {
    transform: translateY(-3px);
    box-shadow: 0 15px 30px rgba(79, 70, 229, 0.25);
}
#game-end {
    border-bottom: none;
}

.legends-section {
    border-bottom: none;
    padding-top: 60px;
    padding-bottom: 60px;
}

/* Header */
.legend-header {
    margin-bottom: 50px;
}
.instruction-section .legend-title {
    margin: 0;
    padding-top: 10px;   /* small top breathing */
}

.legend-pill {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: linear-gradient(135deg, #6366f1, #4f46e5);
    color: white;
    font-size: 12px;
    font-weight: 600;
    padding: 8px 16px;
    border-radius: 999px;
    margin-bottom: 20px;
}

.legend-title {
    font-size: 42px;
    font-weight: 700;
    margin: 0;
    color: #0f172a;
}

.legend-underline {
    width: 50px;
    height: 4px;
    background: #6366f1;
    border-radius: 4px;
    margin-top: 14px;
}



/* ===== LEGEND GRID ===== */
.legend-grid{
    display:grid;
    grid-template-columns:repeat(auto-fit,minmax(260px,1fr));
    gap:30px;
}

/* ===== LEGEND CARD ===== */
.legend-card{
    background: #ffffff;
    border-radius: 18px;
    padding: 26px 28px;
    border: 1px solid #eef2f7;

    display: flex;
    align-items: flex-start;
    gap: 20px;

    width: 100%;
    box-sizing: border-box;

    transition: all 0.3s ease;
}
.legend-content p{
    word-break: normal;
    overflow-wrap: break-word;
}
.legend-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 15px 40px rgba(15, 23, 42, 0.06);
}

/* ===== ICON BOX ===== */
.legend-circle {
    width: 56px;
    height: 56px;
    min-width: 56px;

    border-radius: 14px;

    display: flex;
    align-items: center;
    justify-content: center;

    font-size: 18px;
}

/* ===== TEXT AREA ===== */
.legend-content {
    display: flex;
    flex-direction: column;
}

.legend-content h4 {
    font-size: 16px;
    font-weight: 600;
    margin: 0 0 6px 0;
    color: #0f172a;
}

.legend-content p {
    font-size: 14px;
    color: #6b7280;
    margin: 0;
    line-height: 1.6;
}
/* ==============================
   GUIDELINE SECTION UPGRADE
============================== */

.guideline-intro {
    font-size: 18px;
    line-height: 1.8;
    margin-bottom: 40px;
    width: 100%;
}


/* Two cards layout */
.guideline-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 30px;
    margin-bottom: 40px;
}

/* Card Design */
.guideline-card {
    background: #ffffff;
    border-radius: 18px;
    padding: 28px;
    border: 1px solid #eef2f7;

    display: flex;
    gap: 20px;
    align-items: flex-start;

    transition: all 0.3s ease;
}

.guideline-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 15px 40px rgba(15, 23, 42, 0.06);
}

/* Icon */
.guideline-icon {
    width: 52px;
    height: 52px;
    min-width: 52px;
    border-radius: 14px;

    display: flex;
    align-items: center;
    justify-content: center;

    font-size: 18px;
    color: white;
}

/* Color variations */
.guideline-card.negative .guideline-icon {
    background: #ef4444;
}

.guideline-card.positive .guideline-icon {
    background: #22c55e;
}

/* Example Box */
.example-box {
    background: linear-gradient(135deg, #eef2ff, #f8fafc);
    padding: 25px 30px;
    border-radius: 16px;
    border: 1px solid #e0e7ff;
    width: 100%;
}
.guideline-section {
    max-width: 1200px;
    margin: 0 auto;
}
.example-box h4 {
    margin: 0 0 10px 0;
    font-size: 16px;
    font-weight: 600;
    color: #4f46e5;
}

.example-box p {
    margin: 0;
    font-size: 15px;
    line-height: 1.7;
}
/* ==============================
   RESOURCES SECTION – PREMIUM STRIP STYLE
============================== */

.resources-section {
    max-width: 1200px;
    margin: 0 auto;
}

.resources-intro {
    font-size: 18px;
    line-height: 1.8;
    margin-bottom: 50px;
    color: #475569;
}

/* Wrapper */
.resources-wrapper {
    display: flex;
    flex-direction: column;
    gap: 40px;
}

/* Row */
.resource-row {
    display: flex;
    align-items: center;
    gap: 40px;
    padding: 30px 0;
    border-top: 1px solid #e5e7eb;
}

.resource-row:last-child {
    border-bottom: 1px solid #e5e7eb;
}

/* Left Side */
.resource-left {
    min-width: 200px;
}

.resource-number {
    font-size: 48px;
    font-weight: 700;
    color: #4f46e5;
    line-height: 1;
}

.resource-label {
    font-size: 14px;
    text-transform: uppercase;
    letter-spacing: 1.5px;
    font-weight: 600;
    color: #64748b;
    margin-top: 8px;
}

/* Divider Line */
.resource-divider {
    width: 1px;
    height: 60px;
    background: #e5e7eb;
}

/* Right Side */
.resource-right {
    font-size: 17px;
    line-height: 1.8;
    color: #475569;
    max-width: 600px;
}

/* Hover Effect */
.resource-row:hover .resource-number {
    transform: translateX(5px);
    transition: 0.3s ease;
}
/* ==============================
   HOW TO PLAY – VERTICAL TIMELINE
============================== */

/* ==============================
   HOW TO PLAY – PERFECT TIMELINE
============================== */

.how-play-section {
    max-width: 1100px;
      margin: 0 auto;
}

.steps-timeline {
    position: relative;
    margin-top: 50px;
    padding-left: 110px; /* space for circle */
}

/* Vertical Line (Perfectly Centered) */
.steps-timeline::before {
    content: "";
    position: absolute;
    left: 28px;
    top: 28px;
    height: calc(100% - 56px); /* circle height */
    width: 2px;
    background: #6366f1;
}

/* Step Item */
.step-item {
    position: relative;
    margin-bottom: 70px;
}

.step-item:last-child {
    margin-bottom: 0;
}

/* Step Number (Circle) */
.step-number {
    position: absolute;
    left: -110px;
    top: 0;

    width: 56px;
    height: 56px;
    border-radius: 50%;

    background: #ffffff;
    border: 2px solid #6366f1;

    display: flex;
    align-items: center;
    justify-content: center;

    font-weight: 700;
    font-size: 16px;
    color: #6366f1;

    z-index: 2;
    transition: 0.3s ease;
}

/* Content */
.step-content {
    padding-top: 4px;
}

.step-content h4 {
    font-size: 20px;
    font-weight: 600;
    margin: 0 0 8px 0;
    color: #0f172a;
}

.step-content p {
    margin: 0;
    font-size: 16px;
    line-height: 1.7;
    color: #475569;
    max-width: 650px;
}

/* Hover */
.step-item:hover .step-number {
    background: #6366f1;
    color: white;
    transform: scale(1.08);
}

/* ==============================
   WHEN INVEST – PREMIUM STYLE
============================== */

.invest-intro {
    font-size: 18px;
    margin-bottom: 40px;
    color: #475569;
}

.invest-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 30px;
}

.invest-card {
    background: #ffffff;
    border-radius: 18px;
    padding: 24px;
    border: 1px solid #eef2f7;

    display: flex;
    gap: 18px;
    align-items: flex-start;

    transition: 0.3s ease;
}

.invest-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 15px 40px rgba(15, 23, 42, 0.06);
}

.invest-card i {
    font-size: 20px;
    margin-top: 4px;
}

.invest-card h4 {
    margin: 0 0 6px 0;
    font-size: 16px;
    font-weight: 600;
    color: #0f172a;
}

.invest-card p {
    margin: 0;
    font-size: 14px;
    color: #6b7280;
    line-height: 1.6;
}

/* Bottom Note */
.invest-note {
    margin-top: 40px;
    padding: 20px 25px;
    border-radius: 14px;
    background: linear-gradient(135deg, #eef2ff, #f8fafc);
    border: 1px solid #e0e7ff;
    font-size: 15px;
    color: #475569;
}
.invest-card .legend-icon {
    width: 42px;
    height: 42px;
    flex-shrink: 0;
}
.invest-initial {
    width: 42px;
    height: 42px;
    min-width: 42px;

    border-radius: 12px;
    background: #eef2ff;

    display: flex;
    align-items: center;
    justify-content: center;
}

.invest-initial i {
    color: #6366f1;
    font-size: 18px;
}
/* ==============================
   GAME END – ICON BASED STYLE
============================== */

#game-end > p:first-of-type {
    font-size: 18px;
    line-height: 1.8;
    color: #475569;
    margin-bottom: 20px;
}

#game-end ul {
    list-style: none;
    padding: 0;
    margin: 0;
}

#game-end ul li {
    position: relative;
    padding-left: 60px;
    margin-bottom: 20px;

    font-size: 19px;
    line-height: 1.9;
    color: #334155;

    transition: 0.3s ease;
}

/* Icon styling */
#game-end .end-icon {
    position: absolute;
    left: 0;
    top: 4px;
    font-size: 22px;
}

/* Victory color */
#game-end .win {
    color: #16a34a;
}

/* Game over color */
#game-end .lose {
    color: #dc2626;
}

/* Subtle hover */
#game-end ul li:hover {
    transform: translateX(6px);
    color: #0f172a;
}

#game-end p:last-of-type {
    font-size: 17px;
    line-height: 1.9;
    color: #475569;
    width: 100%;
    max-width: 100%;   /* remove restriction */
}
#game-end strong {
    font-weight: 600;
    color: #111827;
}

/* ==============================
   INSTRUCTION FOOTER – PREMIUM CTA
============================== */

.instruction-footer {
   
    padding-top: 20px;
    border-top: 1px solid #e2e8f0;

    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 40px;

    flex-wrap: wrap;
}

/* Info Section */
.footer-info {
    display: flex;
    align-items: flex-start;
    gap: 14px;
    max-width: 600px;
}

.footer-info i {
    font-size: 18px;
    color: #6366f1;
    margin-top: 3px;
}

.footer-info p {
    font-size: 16px;
    line-height: 1.8;
    color: #475569;
    margin: 0;
}

/* Start Button */
.btn-start-game {
    display: inline-flex;
    align-items: center;
    gap: 10px;

    font-size: 17px;
    font-weight: 600;

    background: linear-gradient(135deg, #6366f1, #4f46e5);
    color: #fff;

    border: none;
    border-radius: 8px;

    padding: 14px 28px;
    cursor: pointer;

    transition: all 0.3s ease;
}

.btn-start-game i {
    font-size: 18px;
}

/* Hover Effect */
.btn-start-game:hover {
    transform: translateY(-3px);
    box-shadow: 0 10px 25px rgba(79, 70, 229, 0.25);
}

.btn-start-game:active {
    transform: translateY(0);
}

#wildcards {
    position: relative;
}

/* Paragraph styling */
#wildcards p {
    font-size: 19px;
    line-height: 2;
    margin-bottom: 28px;
    color: #475569;
     max-width: 100%;
    transition: 0.3s ease;
}

/* Slight highlight on hover (text only) */
#wildcards:hover p {
    color: #334155;
}

/* Bullet list */
#wildcards ul {
    list-style: none;
    padding: 0;
    margin: 40px 0;
}

/* Bullet items */
#wildcards ul li {
    position: relative;
    padding-left: 36px;
    margin-bottom: 24px;
    font-size: 18px;
    line-height: 1.9;
    color: #475569;
    transition: 0.3s ease;
}

/* Premium floating dot */
#wildcards ul li::before {
    content: "";
    position: absolute;
    left: 0;
    top: 14px;
    width: 10px;
    height: 10px;
    border-radius: 50%;
    background: linear-gradient(135deg, #6366f1, #8b5cf6);
    box-shadow: 0 0 14px rgba(99, 102, 241, 0.4);
    transition: 0.3s ease;
}

/* Bullet hover effect */
#wildcards ul li:hover {
    transform: translateX(4px);
    color: #0f172a;
}

#wildcards ul li:hover::before {
    transform: scale(1.2);
}

/* Strong emphasis */
#wildcards strong {
    font-weight: 600;
    color: #111827;
}
/* Responsive */
@media (max-width: 900px) {
    .invest-grid {
        grid-template-columns: 1fr;
    }
}
/* Tablet */
@media (max-width: 900px) {

    .steps-timeline{
        padding-left: 90px;
    }

    .steps-timeline::before{
        left: 24px;
        top: 26px;
        bottom: 26px;
    }

    .step-number{
        left: -90px;
        width: 52px;
        height: 52px;
        font-size: 15px;
    }

    .step-content h4{
        font-size: 18px;
    }

    .step-content p{
        font-size: 15px;
        max-width: 100%;
    }

}
/* Responsive */
@media (max-width: 768px) {
    .steps-timeline {
        padding-left: 80px;
    }

    .steps-timeline::before {
        left: 20px;
        top: 24px;
        bottom: 24px;
    }

    .step-number {
        left: -80px;
        width: 48px;
        height: 48px;
        font-size: 14px;
    }
}
/* Responsive */
@media (max-width: 900px) {
    .resource-row {
        flex-direction: column;
        align-items: flex-start;
        gap: 20px;
    }

    .resource-divider {
        display: none;
    }

    .resource-left {
        min-width: auto;
    }
}
/* Responsive */
@media (max-width: 900px) {
    .guideline-grid {
        grid-template-columns: 1fr;
    }
}

/* Responsive */

@media (max-width: 900px) {
.legend-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    .legend-card {
        padding: 22px;
        gap: 16px;
    }

    .legend-circle {
        width: 50px;
        height: 50px;
        min-width: 50px;
        font-size: 16px;
    }

    .legend-content h4 {
        font-size: 15px;
    }

    .legend-content p {
        font-size: 13px;
    }

}

@media (max-width: 600px) {

    .legend-card {
        padding: 20px;
        gap: 14px;
    }

    .legend-circle {
        width: 46px;
        height: 46px;
        min-width: 46px;
        font-size: 15px;
    }

    .legend-content h4 {
        font-size: 14px;
    }

    .legend-content p {
        font-size: 13px;
        line-height: 1.5;
    }

    .steps-timeline{
        padding-left: 70px;
        margin-top: 35px;
    }

    .steps-timeline::before{
        left: 18px;
        top: 24px;
        bottom: 24px;
        width: 2px;
    }

    .step-item{
        margin-bottom: 50px;
    }

    .step-number{
        left: -70px;
        width: 44px;
        height: 44px;
        font-size: 13px;
    }

    .step-content h4{
        font-size: 16px;
    }

    .step-content p{
        font-size: 14px;
        line-height: 1.6;
    }
}
        </style>
    </head>
<body class="instruction-page-body">
    <div class="layout">

        <!-- Left Navigation -->
        <div class="left-nav">
            <div class="nav-item active" data-target="overview">
                <i class="fas fa-compass"></i> Game Overview
            </div>

            <div class="nav-item" data-target="resources">
                <i class="fas fa-map"></i> Your Resources
            </div>

            <div class="nav-item" data-target="legends">
                <i class="fas fa-chart-bar"></i> Legends
            </div>

            <div class="nav-item" data-target="guideline">
                <i class="fas fa-cog"></i> Playing guideline
            </div>

            <div class="nav-item" data-target="how-to-play">
                <i class="fas fa-gamepad"></i> How to Play
            </div>

            <div class="nav-item" data-target="when-invest">
                <i class="fas fa-hand-holding-usd"></i> When Can You Invest?
            </div>

            <div class="nav-item" data-target="wildcards">
                <i class="fas fa-magic"></i> What are wild cards?
            </div>

            <div class="nav-item" data-target="tips">
                <i class="fas fa-lightbulb"></i> Winning Tips
            </div>

            <div class="nav-item" data-target="game-end">
                <i class="fas fa-flag-checkered"></i> Game End
            </div>
        </div>

        <!-- Main Content -->
        <div class="main-content">

            <section class="instruction-section hero-section" id="overview">
                <div class="legend-header">
                    <h2 class="legend-title">Game Overview</h2>
                    <div class="legend-underline"></div>
                </div>
              <h1 class="legend-title">
    <?php echo htmlspecialchars($game['game_name']); ?>
</h1>


             <p class="game-description">
    <?php echo html_entity_decode($game['description']); ?>
</p>
            </section >
            

            <section class="instruction-section resources-section" id="resources">

                <div class="legend-header">
                    <h2 class="legend-title">Your Resources</h2>
                    <div class="legend-underline"></div>
                </div>

                <p class="resources-intro">
    <?php echo nl2br(getInstruction($instructions, 'resources_intro')); ?>
</p>

                <div class="resources-wrapper">

                    <!-- Risk Capital -->
                    <div class="resource-row">
                        <div class="resource-left">
                            <div class="resource-number">
                                <?php echo $game['risk_capital']; ?>
                            </div>
                            <div class="resource-label">Risk Capital</div>
                        </div>

                        <div class="resource-divider"></div>

                        <div class="resource-right">
    <?php echo nl2br(getInstruction($instructions, 'resources_capital')); ?>
</div>
                    </div>

                    <!-- Dice Limit -->
                    <div class="resource-row">
                        <div class="resource-left">
                            <div class="resource-number">
                                <?php echo $game['dice_limit']; ?>
                            </div>
                            <div class="resource-label">Dice Throws</div>
                        </div>

                        <div class="resource-divider"></div>

                      <div class="resource-right">
    <?php echo nl2br(getInstruction($instructions, 'resources_dice')); ?>
</div>
                    </div>

                </div>

            </section>

                <section class="instruction-section legends-section" id="legends">

                    <div class="legend-header">
                        <h2 class="legend-title">Legend</h2>
                        <div class="legend-underline"></div>
                    </div>
                    <div class="legend-grid">

                        <?php foreach($legends as $legend): ?>

                            <div class="legend-card">

                                <div class="legend-circle"
                                    style="background: <?php echo $legend['bg_color']; ?>;
                                            color: <?php echo $legend['text_color']; ?>;">

                                    <i class="fas <?php echo htmlspecialchars($legend['icon_class']); ?>"></i>

                                </div>

                                <!-- WRAP TEXT INSIDE THIS DIV -->
                                <div class="legend-content">
                                    <h4><?php echo htmlspecialchars($legend['title']); ?></h4>
                                    <p><?php echo htmlspecialchars($legend['description']); ?></p>
                                </div>

                            </div>

                        <?php endforeach; ?>

                    </div>

                </section>

            <section class="instruction-section guideline-section" id="guideline">

                <div class="legend-header">
                    <h2 class="legend-title">Playing Guidelines</h2>
                    <div class="legend-underline"></div>
                </div>

                <p class="guideline-intro">
    <?php echo nl2br(getInstruction($instructions, 'guideline_intro')); ?>
</p>

                <div class="guideline-grid">

                    <!-- Protection Card -->
                    <div class="guideline-card negative">
                        <div class="guideline-icon">
                            <i class="fas fa-shield-alt"></i>
                        </div>
                        <div>
                            <h4><?php echo getInstruction($instructions, 'guideline_risk', 'title'); ?></h4>
<p><?php echo nl2br(getInstruction($instructions, 'guideline_risk')); ?></p>
                        </div>
                    </div>

                    <!-- Growth Card -->
                    <div class="guideline-card positive">
                        <div class="guideline-icon">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <div>
                           <h4><?php echo getInstruction($instructions, 'guideline_opportunity', 'title'); ?></h4>
<p><?php echo nl2br(getInstruction($instructions, 'guideline_opportunity')); ?></p>
                        </div>
                    </div>

                </div>

                <!-- Example Box -->
                <div class="example-box">
                    <h4><i class="fas fa-lightbulb"></i> <?php echo getInstruction($instructions, 'guideline_example', 'title'); ?></h4>
                   <p>
    <?php echo nl2br(getInstruction($instructions, 'guideline_example')); ?>
</p>
                </div>

            </section>

            <section class="instruction-section how-play-section" id="how-to-play">

                <div class="legend-header">
                    <h2 class="legend-title">How to Play</h2>
                    <div class="legend-underline"></div>
                </div>
                <?php
$steps = json_decode(getInstruction($instructions, 'how_to_play'), true);
?>

                <div class="steps-timeline">

                    <?php if (!empty($steps)): ?>
                    <?php foreach ($steps as $index => $step): ?>
                        <div class="step-item">
                            <div class="step-number">
                                <?php echo str_pad($index + 1, 2, '0', STR_PAD_LEFT); ?>
                            </div>
                            <div class="step-content">
                                <h4><?php echo htmlspecialchars($step['title']); ?></h4>
                                <p><?php echo htmlspecialchars($step['description']); ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <?php endif; ?>

                </div>

            </section>
            <?php
                // Helper function to get legend by title
                if (!function_exists('getLegendByTitle')) {
                    function getLegendByTitle($legends, $title) {
                        foreach ($legends as $legend) {
                            if (strtolower($legend['title']) === strtolower($title)) {
                                return $legend;
                            }
                        }
                        return null;
                    }
                }
                $bonusLegend = getLegendByTitle($legends, 'Bonus');
                $auditLegend = getLegendByTitle($legends, 'Audit');
                $wildLegend  = getLegendByTitle($legends, 'Wild Card');
            ?>
            <section class="instruction-section how-invest-section" id="when-invest">
                <div class="legend-header">
                    <h2 class="legend-title">When Can You Invest?</h2>
                    <div class="legend-underline"></div>
                </div>
                <p class="invest-intro">
        <?php echo nl2br(getInstruction($instructions, 'when_invest')); ?>
    </p>
                <div class="invest-grid">

                    <!-- Initial Allocation -->
                    <div class="invest-card">
                        <div class="invest-icon invest-initial">
                        <i class="fas fa-play-circle"></i>
                        </div>
                        <div>
                            <h4>Initial Allocation</h4>
                           <p>Before rolling the dice, you must allocate your risk capital into strategies.</p>
                        </div>
                    </div>

                    <!-- Bonus Cell -->
                    <?php if ($bonusLegend): ?>
                        <div class="invest-card">
                            <div class="legend-circle"
                                style="background: <?php echo $bonusLegend['bg_color']; ?>;
                                        color: <?php echo $bonusLegend['text_color']; ?>;">
                                <i class="fas <?php echo htmlspecialchars($bonusLegend['icon_class']); ?>"></i>
                            </div>
                            <div>
                                <h4>Bonus Cell</h4>
                                <p>Landing on a bonus cell grants additional capital for reinvestment.</p>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Audit Cell -->
                    <?php if ($auditLegend): ?>
                        <div class="invest-card">
                            <div class="legend-circle"
                                style="background: <?php echo $auditLegend['bg_color']; ?>;
                                        color: <?php echo $auditLegend['text_color']; ?>;">
                                <i class="fas <?php echo htmlspecialchars($auditLegend['icon_class']); ?>"></i>
                            </div>
                            <div>
                                <h4>Audit Cell</h4>
                                <p>Review and modify your strategy investments mid-game.</p>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Wild Card Gain -->
                    <?php if ($wildLegend): ?>
                        <div class="invest-card">
                            <div class="legend-circle"
                                style="background: <?php echo $wildLegend['bg_color']; ?>;
                                        color: <?php echo $wildLegend['text_color']; ?>;">
                                <i class="fas <?php echo htmlspecialchars($wildLegend['icon_class']); ?>"></i>
                            </div>
                            <div>
                                <h4>Wild Card Gain</h4>
                                <p>If a wild card increases your capital, you may reinvest the gained amount.</p>
                            </div>
                        </div>
                    <?php endif; ?>

                </div>
            </section>

            <section class="instruction-section" id="wildcards">
                <div class="legend-header">
                    <h2 class="legend-title">What are wild cards?</h2>
                    <div class="legend-underline"></div>
                </div>
                <p><?php echo nl2br(getInstruction($instructions, 'wildcards_intro')); ?></p>

<ul>
    <?php 
    $wildcards = json_decode(getInstruction($instructions, 'wildcards_list'), true);
    if ($wildcards):
        foreach ($wildcards as $item): ?>
            <li><i><?php echo htmlspecialchars($item); ?></i></li>
    <?php endforeach; endif; ?>
</ul>
                <p><i>You can also choose to skip the Wild Card and continue your game as planned</i></p>
            </section>

            <section class="instruction-section" id="tips">
                <div class="legend-header">
                    <h2 class="legend-title">Winning Tips</h2>
                    <div class="legend-underline"></div>
                </div>
               <ul>
<?php 
$tips = json_decode(getInstruction($instructions, 'tips'), true);
if ($tips):
    foreach ($tips as $tip): ?>
        <li><i><?php echo htmlspecialchars($tip); ?></i></li>
<?php endforeach; endif; ?>
</ul>
            </section>

            <section class="instruction-section" id="game-end">

                <div class="legend-header">
                    <h2 class="legend-title">Game End</h2>
                    <div class="legend-underline"></div>
                </div>
                <p><?php echo nl2br(getInstruction($instructions, 'game_end_intro')); ?></p>

<ul>
<?php 
$game_end = json_decode(getInstruction($instructions, 'game_end_list'), true);
if ($game_end):
    foreach ($game_end as $item): ?>
        <li><i><?php echo htmlspecialchars($item); ?></i></li>
<?php endforeach; endif; ?>
</ul>

<p><?php echo nl2br(getInstruction($instructions, 'game_end_footer')); ?></p>
               
            </section>
            <!-- Start Game Button -->
            <div class="instruction-footer">

                <div class="footer-info">
                    <i class="fas fa-info-circle"></i>
                    <p>
                        You can access these instructions anytime during the game by clicking the instruction icon in the sidebar.
                    </p>
                </div>

                <button class="btn-start-game" onclick="startGamePlay()">
                    <i class="fas fa-play-circle"></i>
                    <span>Enter</span>
                </button>

            </div>
           

        </div>
 
    </div>
    <!-- Scripts -->
    <script src="<?php echo ASSETS_URL; ?>js/common.js"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function () {

            const navItems = document.querySelectorAll('.nav-item');
            const sections = document.querySelectorAll('.instruction-section');
            const mainContent = document.querySelector('.main-content');

            // CLICK → SCROLL TO SECTION
            navItems.forEach(item => {

                item.addEventListener('click', function () {

                    const targetId = this.dataset.target;
                    const targetSection = document.getElementById(targetId);

                    if (targetSection) {

                        mainContent.scrollTo({
                            top: targetSection.offsetTop - 80,
                            behavior: 'smooth'
                        });

                        navItems.forEach(nav => nav.classList.remove('active'));
                        this.classList.add('active');
                    }

                });

            });

            // AUTO HIGHLIGHT NAV WHILE SCROLLING
            mainContent.addEventListener('scroll', () => {

                let current = "";

                sections.forEach(section => {
                    const sectionTop = section.offsetTop - 150;

                    if (mainContent.scrollTop >= sectionTop) {
                        current = section.getAttribute("id");
                    }
                });

                navItems.forEach(nav => {
                    nav.classList.remove("active");
                    if (nav.dataset.target === current) {
                        nav.classList.add("active");
                    }
                });

            });

        });

        function startGamePlay() {
        // Show loading state
            const btn = document.querySelector('.btn-start-game');
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Enter...';
            btn.disabled = true;

            // Initialize game session
            fetch('ajax/start_game.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    matrix_id: <?php echo $game_id; ?>
                })
            })
            .then(response => {
                // First get the text
                return response.text();
            })
            .then(text => {
                // Try to parse as JSON
                try {
                    const data = JSON.parse(text);
                    
                    if (data.success) {
                        // Redirect to play page
                        window.location.href = 'review_board.php?game_id=<?php echo $game_id; ?>';
                    } else {
                        alert(data.message || 'Failed to start game.');
                        btn.innerHTML = '<i class="fas fa-play-circle"></i> Start Playing';
                        btn.disabled = false;
                    }
                } catch (parseError) {
                    // JSON parse failed - show the actual response
                    console.error('Parse Error:', parseError);
                    console.error('Response Text:', text);
                    
                    // Show user-friendly error
                    alert('Server Error: Unable to start game.\n\nPlease check:\n1. Database is running\n2. Game is published\n3. Check browser console (F12) for details');
                    
                    btn.innerHTML = '<i class="fas fa-play-circle"></i> Start Playing';
                    btn.disabled = false;
                }
            })
            .catch(error => {
                console.error('Fetch Error:', error);
                alert('Network Error: Failed to connect to server.\n\nPlease check:\n1. XAMPP Apache is running\n2. File paths are correct');
                btn.innerHTML = '<i class="fas fa-play-circle"></i> Start Playing';
                btn.disabled = false;
            });
        }
    </script>
    <script>
        document.querySelectorAll('.nav-item').forEach(item => {

            item.addEventListener('click', () => {

                document.querySelectorAll('.nav-item').forEach(i => i.classList.remove('active'));
                item.classList.add('active');

                const target = document.getElementById(item.dataset.target);

                target.scrollIntoView({
                    behavior: "smooth",
                    block: "start"
                });

            });

        });

   
    </script>
</body>
</html>
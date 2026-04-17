<?php

$grid_size = 6; // fallback

if (!empty($game_data['matrix_type'])) {
    $parts = explode('x', $game_data['matrix_type']);
    $grid_size = intval($parts[0]);
}

$total_cells = $grid_size * $grid_size;
?>

<?php
if (!$game_data) {
    echo '<div class="alert alert-warning">Please complete Step 3 first.</div>';
    return;
}

$matrix_id = $game_data['id'];
$total_cells = $game_data['total_cells'];
$summary = get_game_summary($matrix_id);
$game = $summary['game'];
// Get bonus cells
$bonus_query = "SELECT * FROM mg6_riskhop_bonus WHERE matrix_id = '$matrix_id' ORDER BY cell_number";
$bonus_result = mysqli_query($conn, $bonus_query);

// Get audit cells
$audit_query = "SELECT * FROM mg6_riskhop_audit WHERE matrix_id = '$matrix_id' ORDER BY cell_number";
$audit_result = mysqli_query($conn, $audit_query);

// Get wildcard cells
$wildcard_cells_query = "SELECT * FROM mg6_riskhop_wildcard_cells WHERE matrix_id = '$matrix_id' ORDER BY cell_number";
$wildcard_cells_result = mysqli_query($conn, $wildcard_cells_query);

// Get wildcard options
$wildcards_query = "SELECT * FROM mg6_riskhop_wildcards WHERE matrix_id = '$matrix_id' ORDER BY id ASC";
$wildcards_result = mysqli_query($conn, $wildcards_query);
?>
<?php

$threats_query = "
    SELECT id, cell_from, cell_to
    FROM mg6_riskhop_threats
    WHERE matrix_id = $matrix_id
";
$threats_result = mysqli_query($conn, $threats_query);

$opportunities_query = "
    SELECT id, cell_from, cell_to
    FROM mg6_riskhop_opportunities
    WHERE matrix_id = $matrix_id
";
$opportunities_result = mysqli_query($conn, $opportunities_query);
$threats_data = [];
$threat_from_cells = [];

if ($threats_result && mysqli_num_rows($threats_result) > 0) {
    while ($t = mysqli_fetch_assoc($threats_result)) {
        $from = (int)$t['cell_from'];
        $to   = (int)$t['cell_to'];

        $threat_from_cells[] = $from;

        $threats_data[] = [
            'from' => $from,
            'to'   => $to
        ];
    }
}

$opportunities_data = [];
$opportunity_from_cells = [];

if ($opportunities_result && mysqli_num_rows($opportunities_result) > 0) {
    while ($o = mysqli_fetch_assoc($opportunities_result)) {
        $from = (int)$o['cell_from'];
        $to   = (int)$o['cell_to'];

        $opportunity_from_cells[] = $from;

        $opportunities_data[] = [
            'from' => $from,
            'to'   => $to
        ];
    }
}

// BONUS CELLS
$bonus_cells = [];
if ($bonus_result && mysqli_num_rows($bonus_result) > 0) {
    mysqli_data_seek($bonus_result, 0);
    while ($b = mysqli_fetch_assoc($bonus_result)) {
        $bonus_cells[] = (int)$b['cell_number'];
    }
}

// AUDIT CELLS
$audit_cells = [];
if ($audit_result && mysqli_num_rows($audit_result) > 0) {
    mysqli_data_seek($audit_result, 0); // ✅ ADD THIS
    while($a = mysqli_fetch_assoc($audit_result)) {
        $audit_cells[] = (int)$a['cell_number'];
    }
}

// WILDCARD CELLS
$wildcard_cells = [];
if ($wildcard_cells_result && mysqli_num_rows($wildcard_cells_result) > 0) {
    mysqli_data_seek($wildcard_cells_result, 0); // ✅ ADD THIS
    while($wc = mysqli_fetch_assoc($wildcard_cells_result)) {
        $wildcard_cells[] = (int)$wc['cell_number'];
    }
}

$bonus_count = mysqli_num_rows($bonus_result);
$audit_count = mysqli_num_rows($audit_result);
$wildcard_cells_count = mysqli_num_rows($wildcard_cells_result);
$wildcard_options_count = mysqli_num_rows($wildcards_result);
?>
<style>
    html, body{
min-height:100vh;
margin:0;
overflow-x:hidden;   /* allow vertical scroll */
overflow-y:auto;
}
    .setup-panel,
.accordion-content {
    overflow-y: auto;
    scroll-behavior: smooth;

    /* Firefox */
    scrollbar-width: none;

    /* IE/Edge */
    -ms-overflow-style: none;
}

/* Chrome, Safari */
.setup-panel::-webkit-scrollbar,
.accordion-content::-webkit-scrollbar {
    display: none;
}
.step2-wrapper{
    display:flex;
    gap:20px;
padding-bottom: 80px; 
     min-height: calc(100vh - 140px); 
    overflow:visible;  /* ✅ allow content */
}
/* LEFT SIDE */
.board-preview-section{
    flex:0 0 40%;
    max-width:50%;

    display:flex;
    align-items:flex-start;   /* 🔥 move board to top */
    justify-content:center;

    padding:10px 10px;        /* 🔥 reduce top space */

    box-sizing:border-box;
}
/* 🔥 CARD LIKE 2ND IMAGE */
.board-card{
    width:100%;
    height:100%;

    display:flex;
    align-items:center;     /* ✅ ensures perfect centering */
    justify-content:center;
}
.board-arrows-svg{
    position:absolute;
    top:0;
    left:0;
    width:100%;
    height:100%;

    pointer-events:none;
    z-index:5;
}

/* arrows */
.arrow-path{
    stroke-width:1.5;
    fill:none;
    opacity:0;
    transition: opacity 0.25s ease, stroke-width 0.2s ease;
}

.arrow-path.ladder{
    stroke:#2ecc71;
}

.arrow-path.snake{
    stroke:#ff6b6b;
}

/* ACTIVE HOVER */
.arrow-path.active{
    opacity:1;
    stroke-width:2;
}

/* TARGET CELL HIGHLIGHT */
.target-highlight-ladder{
    background:#dcfce7 !important;
}

.target-highlight-snake{
    background:#fee2e2 !important;
}
/* 🔥 PERFECT GRID ALIGNMENT */
.board-preview{
    display:grid;
    grid-template-columns: repeat(<?php echo $grid_size; ?>, 1fr);
    gap:4px;

    width:100%;
    max-width:560px;

    aspect-ratio:1 / 1;     /* ✅ makes perfect square */
}
/* 🔥 BIGGER CELLS */
.cell{
    width:100%;
    aspect-ratio:1/1;

    position:relative;   /* ✅ REQUIRED for icons */

    display:flex;
    align-items:center;
    justify-content:center;

    background:#f9fafb;  /* ✅ ADD THIS (was missing) */
    border-radius:6px;
}


/* optional: even tighter hover */
.cell:hover{
    background:#e2e8f0;
}


/* RIGHT PANEL */
.setup-panel{
    flex:1;
    display:flex;
    flex-direction:column;
    height:100%;
    min-height:100%;   /* ✅ ADD THIS */
     padding:16px;
}
.setup-left{
    display:flex;
    align-items:center;
    gap:10px;
}

.setup-icon{
    width:30px;
    height:30px;
    border-radius:6px;
    display:flex;
    align-items:center;
    justify-content:center;
    font-size:14px;
}

.snake-icon{
    background:#ff6b6b;
    color:#fff;
}
.board-preview-section h3{
    margin-bottom:6px;
}

.board-preview-section p{
    margin-bottom:14px;
}
.ladder-icon{
    background:#2ecc71;
    color:#fff;
}

/* FOOTER */
.step-footer{
    display:flex;
    justify-content:space-between;
    align-items:center;   /* 🔥 vertical align */
    margin-top:25px; 
}
.footer-left{
    display:flex;
    flex-direction:column;
    align-items:flex-start;
    gap:12px;   /* 🔥 spacing between legend & button */
}
.page-footer .prev-btn{
       background:#fff;
    border:2px solid #4f6df5;   /* 🔥 FIX: full border */
    color:#4f6df5;
    padding:10px 16px;
    border-radius:8px;
    cursor:pointer;
    margin-top:0;   
    font-size: 15px;
    
}


.page-footer .prev-btn:hover{
    background:#eef2ff;       /* light blue */
    border-color:#3b5bdb;     /* darker blue */
    color:#3b5bdb;
}

.cell-icon.wildcard{
    width:26px;   /* increase width */
    height:26px;
}

.cell-icon.wildcard i{
    font-size:14px;   /* bigger icon */
    padding:6px;      /* slightly more space */
}
.next-btn{
    background:linear-gradient(135deg,#4f6df5,#6366f1);
    box-shadow:0 6px 14px rgba(79,109,245,0.3);
    color:#fff;
    border:none;
    padding:10px 18px;
    border-radius:8px;
    cursor:pointer;
}
.next-btn:hover{
    transform:translateY(-1px);
}
/* 🔥 LEGEND STYLE */
.legend-row{
    display:flex;
margin-top:0;   
    gap:10px;
}

.legend-item{
    display:flex;
    align-items:center;
    gap:8px;
    padding:8px 14px;
    border-radius:8px;
    font-size:13px;
    font-weight:500;
    background:#f8fafc;
    border:1px solid #e5e7eb;
}

/* 🔥 COLORS */
.legend-item.threat{
    color:#fff;
}

.legend-item.threat i{

    background:#ff6b6b;
    padding:6px;
    border-radius:6px;
}

.legend-item.opportunity{
    color:#fff;
}

.legend-item.opportunity i{
    background:#2ecc71;
    padding:6px;
    border-radius:6px;
}
/* Threat text color */
.legend-item.threat span{
    color:#dc2626; /* red */
}

/* Opportunity text color */
.legend-item.opportunity span{
    color:#16a34a; /* green */
}


/* 🔥 CONTINUE BUTTON HOVER */
.next-btn:hover{
    background:#3b5bdb;        /* darker blue */
}
.sub-text{
    color:#6b7280;
    font-size:14px;
    margin-bottom:14px;
}

.count-text{
    font-size:12px;
    color:#6b7280;
}

/* ACCORDION */
.accordion{
    margin-bottom:12px;
     
}

/* HIDDEN CONTENT */
.accordion-content{
    display: none;
    background:#f8fafc;
    border:1px solid #e5e7eb;
    border-top:none;
    border-radius:0 0 10px 10px;
    padding:14px;

    max-height: 300px;
    overflow-y: auto;

    min-height: 0;       /* keep */
}
.accordion-content::-webkit-scrollbar {
    width: 8px;
}

.accordion-content{
    scroll-behavior: smooth;
}
/* ACTIVE */
.accordion.active .accordion-content{
    display:block;
}

/* BUTTON */
.add-btn{
    width:100%;
    background: linear-gradient(135deg,#4f6df5,#6366f1);
    color:#fff;
    border:none;
    padding:10px;
    border-radius:6px;
    margin-bottom:12px;
    cursor:pointer;
}

.add-btn.green{
    background:#22c55e;
}

/* LIST ITEM */
.item-row{
    display:flex;
    justify-content:space-between;
    align-items:center;
    padding:10px;
    border:1px solid #e5e7eb;
    border-radius:8px;
    margin-bottom:8px;
    background:#fff;
}

/* ACTIONS */
.item-actions{
    display:flex;
    align-items:center;
    gap:10px;
}

.item-actions a{
    font-size:12px;
    color:#4f6df5;
    text-decoration:none;
}

.item-actions i{
    cursor:pointer;
    color:#6b7280;
}

/* ARROW ROTATE */
.accordion.active .arrow{
    transform:rotate(180deg);
}

.modal{
    display:none;
    position:fixed;
    top:0;
    left:0;
    width:100%;
    height:100%;
    background:rgba(0,0,0,0.6);
    backdrop-filter: blur(4px); /* 🔥 modern glass effect */
    justify-content:center;
    align-items:center;
    z-index:999;
}

.modal-content{
    background: #fff;
    width: 700px;              /* 🔥 increased width */
    max-width: 100%;
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 20px 60px rgba(0,0,0,0.2);
    animation: modalFade 0.25s ease;
}
/* smooth animation */
@keyframes modalFade{
    from{
        transform: translateY(30px);
        opacity: 0;
    }
    to{
        transform: translateY(0);
        opacity: 1;
    }
}
.modal-header{
    padding:18px 20px;
    display:flex;
    justify-content:space-between;
    align-items:center;
    font-weight:600;
    font-size:18px;

    background:linear-gradient(135deg,#4f6df5,#6366f1);
    color:#fff;
    border-bottom:none;
}

.modal-close{
    cursor:pointer;
    font-size:20px;
    color:#6b7280;
    transition:0.2s;
}

.modal-close:hover{
    color:#111;
}

.modal-body{
     padding: 20px;
}
.form-group{
    margin-bottom: 16px;
}

.form-group label{
    display:block;
    font-size:13px;
    font-weight:600;
    margin-bottom:6px;
    color:#374151;
}

.form-group input,
.form-group textarea{
   width:100%;
    box-sizing: border-box;  
    padding:10px 12px;
    border:1px solid #d1d5db;
    border-radius:8px;
    font-size:14px;
    transition:0.2s;
}

.form-group input:focus,
.form-group textarea:focus{
    border-color:#4f6df5;
    outline:none;
    box-shadow:0 0 0 3px rgba(79,109,245,0.1);
}

textarea{
    resize:none;
    min-height:80px;
}
.form-row{
    display:flex;
    gap:16px;
    width:100%;            /* ✅ ensure full width */
}

.form-row .form-group{
    flex:1;
    min-width:0;           /* ✅ prevents overflow */
}
input[type="number"]{
    width:100%;
}
.form-actions{
    display:flex;
    justify-content:flex-end;
    gap:10px;
    margin-top:10px;
}

.form-actions button{
    padding:10px 16px;
    border-radius:8px;
    font-size:14px;
    cursor:pointer;
    border:none;
}

/* cancel */
.form-actions button:first-child{
    background:#f3f4f6;
    color:#374151;
}

.form-actions button:first-child:hover{
    background:#e5e7eb;
}

/* save */
.form-actions button:last-child{
    background:#4f6df5;
    color:#fff;
}

.form-actions button:last-child:hover{
    background:#3b5bdb;
}
/* 🔴 ERROR STATE */
/* 🔴 ERROR STATE (STRONGER) */
.form-group input.error,
.form-group textarea.error{
    border-color:#ef4444 !important;
    background:#fff5f5;
}

/* 🔥 RED GLOW ON FOCUS ERROR */
.form-group input.error:focus,
.form-group textarea.error:focus{
    box-shadow:0 0 0 3px rgba(239,68,68,0.25);
}

/* 🔴 ERROR TEXT */
.error-text{
    font-size:12px;
    color:#ef4444;
    margin-top:4px;
}

.cell-number{
    position:absolute;
    top:6px;
    left:6px;
    font-size:13px;
    color:#6b7280;
}

/* ICON BASE */
.cell-icon{
    position:absolute;
     top:50%;
    left:50%;
    transform: translate(-50%, -50%);

    width:22px;
    height:22px;

    border-radius:8px;

    display:flex;
    align-items:center;
    justify-content:center;

    color:#fff;
    font-size:11px;

    transition: transform 0.2s ease;
}

/* 🔴 THREAT (PULSE) */
.cell-icon.threat{
    background:#ff6b6b;
    animation: pulseRed 1.6s infinite;
}

/* 🟢 OPPORTUNITY (GLOW) */
.cell-icon.opportunity{
    background:#2ecc71;
    animation: glowGreen 1.8s infinite;
}
.cell-icon:hover{
    transform: translate(-50%, -50%) scale(1.25);
    z-index: 10;
}
/* 🔥 PULSE ANIMATION */
@keyframes pulseRed{
    0%{
        transform: translate(-50%, -50%) scale(1);
        box-shadow: 0 0 0 0 rgba(255,107,107,0.6);
    }
    70%{
        transform: translate(-50%, -50%) scale(1.1);
        box-shadow: 0 0 0 10px rgba(255,107,107,0);
    }
    100%{
        transform: translate(-50%, -50%) scale(1);
        box-shadow: 0 0 0 0 rgba(255,107,107,0);
    }
}

/* ✨ GLOW ANIMATION */
@keyframes glowGreen{
    0%{
        transform: translate(-50%, -50%) scale(1);
        box-shadow: 0 0 6px rgba(46,204,113,0.4);
    }
    50%{
        transform: translate(-50%, -50%) scale(1.08);
        box-shadow: 0 0 14px rgba(46,204,113,0.9);
    }
    100%{
        transform: translate(-50%, -50%) scale(1);
        box-shadow: 0 0 6px rgba(46,204,113,0.4);
    }
}



/* LEFT CONTENT */
.strategy-info{
    display:flex;
    flex-direction:column;
    justify-content:center;
    gap:4px;
    flex:1;              /* 🔥 take full space */
    min-width:0;         /* 🔥 prevent overflow */
}

.strategy-name{
    font-weight:600;
    font-size:14px;
    color:#111827;
}

.strategy-points{
    font-size:12px;
    color:#4f6df5;
    font-weight:500;
}



/* 🔥 NEW RIGHT PANEL DESIGN */
.setup-card.new-ui{
    border-radius:14px;
    padding:16px 18px;
    border:none;
    margin-bottom:14px;

    display:flex;
    align-items:center;
    justify-content:flex-start;

    cursor:pointer;
    transition:0.25s;
}

/* ICON BOX */
.setup-card.new-ui .setup-icon{
    width:44px;
    height:44px;
    border-radius:12px;

    display:flex;
    align-items:center;
    justify-content:center;

    font-size:18px;
    color:#fff;
}

/* TEXT */
.setup-card.new-ui span{
    font-size:16px;
    font-weight:500;
}

/* COLORS EXACTLY LIKE IMAGE */

/* 🟡 BONUS */
.bonus-icon{
    background:#facc15;
}
.setup-card.new-ui:nth-child(3){
    background:#fef3c7;
}

/* 🔵 AUDIT */
.audit-icon{
    background:#3b82f6;
}
.setup-card.new-ui:nth-child(4){
    background:#dbeafe;
}

/* 🟣 WILDCARD */
.wildcard-icon{
    background:#9333ea;
}
.setup-card.new-ui:nth-child(5){
    background:#ede9fe;
}

/* HOVER EFFECT */
.setup-card.new-ui:hover{
    transform:translateY(-2px);
    box-shadow:0 8px 20px rgba(0,0,0,0.08);
}
/* CARD BASE */
.setup-box{
    display:flex;
    justify-content:space-between;
    align-items:center;
    padding:16px 18px;
    border-radius:12px;
    margin-bottom:12px;
    cursor:pointer;
    transition:0.2s;
}

/* COLORS */
.setup-box.yellow{
    background:#fef3c7;
}
.setup-box.blue{
    background:#dbeafe;
}
.setup-box.purple{
    background:#ede9fe;
}
.setup-box.purple-light{
    background:#f5f3ff;
}

/* ICON */
.setup-icon{
    width:40px;
    height:40px;
    border-radius:10px;
    display:flex;
    align-items:center;
    justify-content:center;
    color:#fff;
    font-size:16px;
}

/* ICON COLORS */
.yellow .setup-icon{ background:#facc15; }
.blue .setup-icon{ background:#3b82f6; }
.purple .setup-icon{ background:#8b5cf6; }
.purple-light .setup-icon{ background:#a78bfa; }

/* TEXT */
.setup-box strong{
    font-size:15px;
    color:#1f2937;
}

/* HOVER */
.setup-box:hover{
    transform:translateY(-2px);
    box-shadow:0 6px 16px rgba(0,0,0,0.08);
}

/* ARROW */
.arrow{
    color:#6b7280;
    transition:0.2s;
}

/* ROTATE WHEN ACTIVE */
.accordion.active .arrow{
    transform:rotate(90deg);
}

/* BONUS ROW */
.bonus-row{
    display:flex;
    justify-content:space-between;
    align-items:center;
    padding:12px 14px;
    border-radius:10px;
    background:#fff;
    border:1px solid #e5e7eb;
    transition:0.2s;
}

.bonus-row:hover{
    box-shadow:0 4px 12px rgba(0,0,0,0.06);
    transform:translateY(-1px);
}

/* LEFT SIDE */
.bonus-info{
    display:flex;
    align-items:center;
    gap:10px;
}

/* STAR ICON */
.bonus-icon{
    background:#facc15;
    color:#fff;
    padding:6px;
    border-radius:6px;
    font-size:12px;
}

/* TEXT */
.bonus-text{
    font-size:14px;
    font-weight:500;
    color:#1f2937;
}

/* ACTION ICONS */
.item-actions{
    display:flex;
    gap:12px;
}

/* ICON BASE */
.item-actions i{
    font-size:14px;
    cursor:pointer;
    color:#9ca3af;
    transition:0.2s;
}

/* ✏️ EDIT HOVER */
.item-actions .edit-icon:hover{
    color:#22c55e;   /* green */
}

/* 🗑 DELETE HOVER */
.item-actions .delete-icon:hover{
    color:#ef4444;   /* red */
}
/* BADGE BASE */
.bonus-badge{
    padding:5px 10px;
    border-radius:999px;
    font-size:12px;
    font-weight:600;
    color:#fff;
    margin-left:8px;
}

/* ✅ POSITIVE (GREEN) */
.bonus-badge.positive{
    background:#22c55e;
}
.cell-icon.bonus{
    background:#fef3c7;   /* light bg */
}

.cell-icon.bonus i{
     background:#facc15; /* icon box */
    color:#fff;
    padding:5px;
    border-radius:6px;
}
.cell-icon.audit{
    background:#dbeafe;
}

.cell-icon.audit i{
    background:#3b82f6;
    color:#fff;
    padding:5px;
    border-radius:6px;
}
.cell-icon.wildcard{
    background:#ede9fe;
}

.cell-icon.wildcard i{
    background:#9333ea;
    color:#fff;
    padding:5px;
    border-radius:6px;
}

/* 🔥 SCROLL ONLY WHEN NEEDED */
.scrollable-list{
    max-height: none;     /* default no scroll */
    overflow-y: visible;
}

/* 🔥 ACTIVATE SCROLL WHEN MORE ITEMS */
.scrollable-list.active-scroll{
    max-height: 220px;    /* adjust if needed */
    overflow-y: auto;
}

/* 🔥 PRIMARY SCROLLBAR */
.scrollable-list.active-scroll::-webkit-scrollbar{
    width: 6px;
}

.scrollable-list.active-scroll::-webkit-scrollbar-thumb{
    background: #4f6df5;   /* 🔥 PRIMARY COLOR */
    border-radius: 10px;
}

.scrollable-list.active-scroll::-webkit-scrollbar-track{
    background: transparent;
}

/* Firefox */
.scrollable-list.active-scroll{
    scrollbar-width: thin;
    scrollbar-color: #4f6df5 transparent;
}
.modal-close-btn{
    width:38px;
    height:38px;

    border:none;
    border-radius:10px;

    background: rgba(255,255,255,0.15);
    color:#fff;

    display:flex;
    align-items:center;
    justify-content:center;

    font-size:16px;
    cursor:pointer;

    transition: all 0.25s ease;
}

/* hover */
.modal-close-btn:hover{
    background: rgba(255,255,255,0.25);
    transform: rotate(90deg) scale(1.05);
}

/* click effect */
.modal-close-btn:active{
    transform: scale(0.95);
}

/* 🎯 ONLY WILDCARD MODAL */
.wildcard-modal{
     padding: 20px 20px 60px;  /* 🔥 less top, more bottom */
    align-items: flex-start;
    overflow-y: auto;
}

/* MAIN BOX */
.wildcard-modal .modal-content{
    max-width: 650px;
    margin: auto;
    margin-top: 10px;     /* 🔥 reduce top gap */
    margin-bottom: 40px; 

    border-radius: 18px;
    overflow: hidden;

    background: linear-gradient(180deg, #ffffff, #fafafa);
    box-shadow: 0 25px 70px rgba(0,0,0,0.25);

    display: flex;
    flex-direction: column;
    max-height: calc(100vh - 80px);
}

/* HEADER */
.wildcard-modal .modal-header{
    background: linear-gradient(135deg, #9333ea, #a855f7);
    padding: 20px 22px;
    font-size: 18px;
    font-weight: 600;
    letter-spacing: 0.3px;
}

/* CLOSE BUTTON */
.wildcard-modal .modal-close-btn{
    background: rgba(255,255,255,0.2);
}

/* BODY */
.wildcard-modal .modal-body{
    padding: 22px;
    overflow-y: auto;
    max-height: calc(100vh - 160px);
}

/* INPUT FIELDS */
.wildcard-modal .form-group input,
.wildcard-modal .form-group textarea{
    border-radius: 10px;
    border: 1px solid #e5e7eb;
    transition: all 0.25s ease;
}

/* FOCUS EFFECT */
.wildcard-modal .form-group input:focus,
.wildcard-modal .form-group textarea:focus{
    border-color: #a855f7;
    box-shadow: 0 0 0 3px rgba(168,85,247,0.15);
}

/* EFFECT BOX (INFO) */
.wildcard-modal .alert{
    background: #faf5ff;
    border: 1px solid #e9d5ff;
    color: #6b21a8;
    border-radius: 10px;
    padding: 12px 14px;
    font-size: 13px;
}

/* BUTTONS */
.wildcard-modal .form-actions{
    margin-top: 18px;
}

.wildcard-modal .form-actions button:last-child{
    background: linear-gradient(135deg,#9333ea,#a855f7);
    box-shadow: 0 8px 20px rgba(168,85,247,0.3);
}

/* HOVER */
.wildcard-modal .form-actions button:last-child:hover{
    transform: translateY(-1px);
    background: #7e22ce;
}
/* ✨ WILDCARD ERROR STYLE IMPROVEMENT */
.wildcard-modal .form-group input.error,
.wildcard-modal .form-group textarea.error{
    border-color:#ef4444;
    background:#fff5f5;
}

.wildcard-modal .error-text{
    color:#ef4444;
    font-size:12px;
    margin-top:4px;
    font-weight:500;
}

/* FLEX ALIGN */
.item-content{
    display:flex;
    align-items:center;
    gap:10px;
}

/* CELL BADGE */
.cell-badge{
    background:#eef2ff;
    color:#4f6df5;
    font-size:12px;
    font-weight:600;
    padding:5px 10px;
    border-radius:999px;
}

/* DESCRIPTION */
.item-desc{
    font-size:13px;
    color:#374151;
    font-weight:500;
}
/* 🔥 2-COLUMN GRID FOR WILDCARD FORM */
.wildcard-grid{
    display:grid;
    grid-template-columns: 1fr 1fr;
    gap:16px;
}

/* FULL WIDTH FIELDS */
.wildcard-full{
    grid-column: 1 / -1;
}
.badge{
display:inline-block;
padding:4px 8px;
font-size:12px;
font-weight:600;
border-radius:6px;
margin-left:6px;
}

.bg-primary{
background:#4f6df5;
color:#fff;
}
.summary-list{
    display:grid;
    grid-template-columns:repeat(3,1fr);
    gap:10px;

    max-height:300px;     /* ✅ control height */
    overflow-y:auto;      /* ✅ scroll instead */
}

/* 🔥 each card equal height */
.summary-row{
    display:flex;
    align-items:center;
    justify-content:space-between;

    padding:10px 12px;

    background:#f9fafb;
    border-radius:12px;

    min-height:56px;
}


.summary-row:hover{
    transform:translateY(-2px);
    box-shadow:0 6px 16px rgba(0,0,0,0.06);
}

.summary-left{
    display:flex;
    align-items:center;
    gap:12px;
}

.summary-icon{
    width:42px;
    height:42px;
    border-radius:12px;

    display:flex;
    align-items:center;
    justify-content:center;

    color:#fff;
    font-size:16px;
}

/* ✅ MATCH BOARD ICON COLORS EXACTLY */
.summary-icon.snake{ background:#ef4444; }      /* 🔴 Threat */
.summary-icon.ladder{ background:#22c55e; }     /* 🟢 Opportunity */
.summary-icon.bonus{ background:#facc15; }      /* 🟡 Bonus */
.summary-icon.audit{ background:#3b82f6; }      /* 🔵 Audit */
.summary-icon.wildcard{ background:#9333ea; }   /* 🟣 Wildcard */
.summary-icon.options{ background:#6b7280; }    /* ⚙️ Options (neutral) */

.summary-title{
    font-weight:600;
    font-size:14px;
}

.summary-sub{
    font-size:12px;
    color:#6b7280;
}

.summary-count{
 font-size:20px;
    font-weight:700;
}

.summary-count.red{ color:#ef4444; }
.summary-count.blue{ color:#22c55e; }   /* was blue → now green */
.summary-count.purple{ color:#9333ea; }

.summary-footer{
    margin-top:20px;
}
.page-footer{
    position: sticky;
    bottom: 0;

    display:flex;
    justify-content:space-between;
    align-items:center;

    padding:14px 18px;
    background:#fff;
    

    z-index:100;
}

/* RIGHT */
.footer-right{
    display:flex;
    gap:14px;
    flex-wrap:wrap; /* ✅ mobile wrap */
}

/* BUTTON BASE */
/* BASE BUTTON */
.page-footer button{
    padding:10px 18px;
    border-radius:10px;
    font-size:14px;
    font-weight:500;
    cursor:pointer;
    border:none;
    display:flex;
    align-items:center;
    gap:8px;
    transition:all 0.2s ease;
     position: relative;
    z-index: 20;
}


/* 🗑️ DELETE (Danger) */
.btn-danger{
    background:#ef4444;
    color:#fff;
}

.btn-danger:hover{
    background:#dc2626;
}

/* 💾 SAVE DRAFT (Neutral) */
.btn-secondary{
    background:#f3f4f6;
    color:#374151;
}

.btn-secondary:hover{
    background:#e5e7eb;
}

/* 🚀 PUBLISH (PRIMARY – IMPORTANT) */
.btn-success{
    background:linear-gradient(135deg,#4f6df5,#6366f1);
    color:#fff;
    box-shadow:0 6px 14px rgba(79,109,245,0.3);
}

.btn-success:hover{
    background:#3b5bdb;
    transform:translateY(-1px);
}
.setup-top{
    margin-bottom:8px;
}

.config-title{
     margin: 10px 0 12px;
}



/* INFO TILE CONTAINER */
.game-info-tiles{
    display:flex;
    gap:12px;
    flex-wrap:wrap;
    margin-bottom:10px;
}

/* TILE CARD */
.game-info-tile{
    flex:1;
    min-width:110px;

    background:#f8fafc;
    border:1px solid #e5e7eb;
    border-radius:10px;

    padding:10px 12px;

    display:flex;
    flex-direction:column;

    transition:0.2s;
}

/* HOVER */
.game-info-tile:hover{
    transform:translateY(-2px);
    box-shadow:0 6px 14px rgba(0,0,0,0.06);
}

/* LABEL */
.tile-label{
    font-size:12px;
    color:#6b7280;
}

/* MAIN VALUE */
.tile-value{
    font-size:16px;
    font-weight:600;
    color:#111827;
}

/* SUB TEXT */
.tile-sub{
    font-size:11px;
    color:#9ca3af;
}
@media (min-width:768px) and (max-width:1440px){

    /* =========================
       GLOBAL FIX
    ========================= */
    html, body{
        overflow-y:auto;
        overflow-x:hidden;
        height:auto;
    }

    .admin-content{
        height:auto !important;
        overflow:visible !important;
    }

    .card{
        height:auto !important;
        min-height:auto !important;
        overflow:visible !important;
    }

    /* =========================
       MAIN LAYOUT
    ========================= */
    .step2-wrapper{
        flex-direction:row;
        align-items:flex-start;
        gap:16px;
        padding-bottom:40px;
    }

    .step2-content{
        display:flex;
        flex-direction:row;
        gap:16px;
        align-items:flex-start;
    }

    /* =========================
       BOARD (LEFT SIDE)
    ========================= */
    .board-preview-section{
        flex:0 0 42%;
        max-width:42%;
    }

    .board-preview{
        max-width:100%;
        aspect-ratio:1/1;
        gap:3px;
    }

    /* CELLS */
    .cell{
        border-radius:6px;
    }

    /* ICON PERFECT CENTER */
    .cell-icon{
        width:60%;
        height:60%;
        max-width:20px;
        max-height:20px;
    }

    .cell-icon i{
        font-size:11px;
    }

    /* NUMBER */
    .cell-number{
        font-size:10px;
        top:4px;
        left:4px;
    }

    /* =========================
       RIGHT PANEL
    ========================= */
    .setup-panel{
        flex:1;
        padding:10px;
    }

    /* =========================
       SUMMARY → 2 COLUMN GRID
    ========================= */
    .summary-list{
        grid-template-columns:repeat(2,1fr); /* 🔥 IMPORTANT */
        gap:10px;
        max-height:none;
        overflow:visible;
    }

    .summary-row{
        padding:10px;
        min-height:60px;
    }

    .summary-icon{
        width:36px;
        height:36px;
        font-size:14px;
    }

    .summary-count{
        font-size:18px;
    }

    /* =========================
       INFO TILES
    ========================= */
    .game-info-tiles{
        gap:10px;
    }

    .game-info-tile{
        flex:1 1 30%;
        min-width:120px;
    }

    /* =========================
       FOOTER (NO STICKY BUG)
    ========================= */
    .page-footer{
        position:relative !important;   /* 🔥 remove sticky */
        margin-top:20px;
        flex-wrap:wrap;
        gap:10px;
    }

    .footer-right{
        justify-content:flex-end;
        width:100%;
    }

    .page-footer button{
        flex:0 0 auto;
    }

}

.step2-content{
    display:flex;
    gap:20px;
    flex:1;              /* ✅ takes remaining height */
    min-height:0;        /* ✅ required for scroll fix */
}
</style>

<div class="step2-wrapper">
<div class="step2-content">
    <!-- LEFT -->
    <div class="board-preview-section">
        <div class="board-card">
            <div style="position:relative; width:100%; height:100%;">
                <svg class="board-arrows-svg" id="boardArrowsSvg">
                    <defs>
                        <marker id="arrowhead-snake" markerWidth="12" markerHeight="12" refX="10" refY="6" orient="auto">
                            <path d="M2,2 L2,10 L10,6 z" fill="#ff6b6b"/>
                        </marker>

                        <marker id="arrowhead-ladder" markerWidth="12" markerHeight="12" refX="10" refY="6" orient="auto">
                            <path d="M2,2 L2,10 L10,6 z" fill="#2ecc71"/>
                        </marker>
                    </defs>
                </svg>
                <div class="board-preview">

                    <?php
                    for ($row = $grid_size; $row >= 1; $row--) {

                        $start = ($row - 1) * $grid_size + 1;
                        $end   = $row * $grid_size;

                        if (($grid_size - $row) % 2 == 0) {
                        $cells = range($end, $start);
                        } else {
                        $cells = range($start, $end);
                        }

                        foreach ($cells as $i):
                        ?>
                        <div class="cell" data-cell="<?php echo $i; ?>">

                            <span class="cell-number"><?php echo $i; ?></span>

                            <!-- 🔴 THREAT -->
                            <?php if (in_array($i, $threat_from_cells)): ?>
                                <div class="cell-icon threat">
                                      <i class="fa-solid fa-bomb"></i>
                                </div>
                            <?php endif; ?>

                            <!-- 🟢 OPPORTUNITY -->
                            <?php if (in_array($i, $opportunity_from_cells)): ?>
                                <div class="cell-icon opportunity">
                                     <i class="fa-solid fa-sack-dollar"></i>
                                </div>
                            <?php endif; ?>

                            <!-- 🟡 BONUS -->
                            <?php if (in_array($i, $bonus_cells)): ?>
                                <div class="cell-icon bonus">
                                    <i class="fas fa-star"></i>
                                </div>
                            <?php endif; ?>

                            <!-- 🔵 AUDIT -->
                            <?php if (in_array($i, $audit_cells)): ?>
                                <div class="cell-icon audit">
                                    <i class="fas fa-search"></i>
                                </div>
                            <?php endif; ?>

                            <!-- 🟣 WILDCARD -->
                            <?php if (in_array($i, $wildcard_cells)): ?>
                                <div class="cell-icon wildcard">
                                    <i class="fas fa-question"></i>
                                </div>
                            <?php endif; ?>

                        </div>
                        <?php endforeach; } ?>

                    </div>
                </div>
            </div>
            <!-- LEGENDS -->

        </div>

        <!-- RIGHT -->

        <!-- RIGHT PANEL -->
        <div class="setup-panel">
            <div class="setup-top">
                <div class="game-info-tiles">

                    <div class="game-info-tile">
                        <span class="tile-label">Board</span>
                        <div class="tile-value">
                            <?php echo $game['matrix_type']; ?>
                        </div>
                        <div class="tile-sub">
                            <?php echo $game['total_cells']; ?> cells
                        </div>
                    </div>

                    <div class="game-info-tile">
                        <span class="tile-label">Risk Capital</span>
                        <div class="tile-value">
                            <?php echo $game['risk_capital']; ?>
                        </div>
                    </div>

                    <div class="game-info-tile">
                        <span class="tile-label">Dice Limit</span>
                        <div class="tile-value">
                            <?php echo $game['dice_limit']; ?>
                        </div>
                    </div>

                </div>
                <h3 style="margin-bottom:18px;">Configuration Summary</h3>
            </div>                        
            <div class="summary-list">

                <!-- Snake -->
                <div class="summary-row">
                    <div class="summary-left">
                        <div class="summary-icon snake">
                             <i class="fa-solid fa-bomb fa-lg"></i>
                        </div>
                        <div>
                            <div class="summary-title">Threat</div>
                            <div class="summary-sub"><?php echo count($threats_data); ?> Threats detected</div>
                        </div>
                    </div>
                    <div class="summary-count red">
                        <?php echo count($threats_data); ?>
                    </div>
                </div>

                <!-- Ladder -->
                <div class="summary-row">
                    <div class="summary-left">
                        <div class="summary-icon ladder">
                            <i class="fa-solid fa-sack-dollar fa-lg"></i>
                        </div>
                        <div>
                            <div class="summary-title">Opportunity</div>
                            <div class="summary-sub"> <?php echo count($opportunities_data); ?> Opportunities</div>
                        </div>
                    </div>
                    <div class="summary-count blue">
                        <?php echo count($opportunities_data); ?>
                    </div>
                </div>

                <!-- Bonus -->
                <div class="summary-row">
                    <div class="summary-left">
                        <div class="summary-icon bonus">
                            <i class="fas fa-star"></i>
                        </div>
                        <div>
                            <div class="summary-title">Bonus Cells</div>
                            <div class="summary-sub">Strategic rewards</div>
                        </div>
                    </div>
                    <div class="summary-count purple">
                        <?php echo $bonus_count; ?>
                    </div>
                </div>

                <!-- Audit -->
                <div class="summary-row">
                    <div class="summary-left">
                        <div class="summary-icon audit">
                            <i class="fas fa-search"></i>
                        </div>
                        <div>
                            <div class="summary-title">Audit Cells</div>
                            <div class="summary-sub">Security checkpoints</div>
                        </div>
                    </div>
                    <div class="summary-count purple">
                        <?php echo $audit_count; ?>
                    </div>
                </div>

                <!-- Wildcard Cells -->
                <div class="summary-row">
                    <div class="summary-left">
                        <div class="summary-icon wildcard">
                        <i class="fas fa-question"></i>
                        </div>
                        <div>
                            <div class="summary-title">Wildcard Cells</div>
                            <div class="summary-sub">Random mechanics</div>
                        </div>
                    </div>
                    <div class="summary-count purple">
                        <?php echo $wildcard_cells_count; ?>
                    </div>
                </div>

                <!-- Wildcard Options -->
                <div class="summary-row">
                    <div class="summary-left">
                        <div class="summary-icon options">
                            <i class="fas fa-cog"></i>
                        </div>
                        <div>
                            <div class="summary-title">Wildcard Options</div>
                            <div class="summary-sub">Custom settings</div>
                        </div>
                    </div>
                    <div class="summary-count purple">
                        <?php echo $wildcard_options_count; ?>
                    </div>
                </div>

            </div>

          

        </div>
        

    </div>
    </div>
    <div class="page-footer">

                <div class="footer-left">
                     <button class="prev-btn"
            onclick="window.location.href='create_game.php?id=<?php echo $game_id; ?>&step=3'">
            ← Previous
        </button>
                </div>

                <div class="footer-right">
                    <button type="button" class="btn btn-secondary" onclick="saveAsDraft()">
                        <i class="fas fa-save"></i> Save as Draft
                    </button>

                    <button type="button" class="btn btn-success" onclick="publishGame()">
                        <i class="fas fa-check"></i> Publish
                    </button>

                </div>
            </div>
</div>


          
<script>
    const Toast = Swal.mixin({
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 7000,
        timerProgressBar: true
    });

</script>

<script>
const threatsData = <?php echo json_encode($threats_data); ?>;
const opportunitiesData = <?php echo json_encode($opportunities_data); ?>;
</script>
<script>

/* =========================
   BOARD ARROW DRAWING LOGIC
========================= */

function drawBoardArrows(){

    const svg = document.getElementById('boardArrowsSvg');
    const board = document.querySelector('.board-preview');

    if(!svg || !board) return;

    // Clear existing arrows
    svg.querySelectorAll(".arrow-path").forEach(el => el.remove());

    const svgRect = svg.getBoundingClientRect();

    // Get center position of a cell
    function getCenter(cellNumber){
        const cell = board.querySelector(`[data-cell="${cellNumber}"]`);
        if(!cell) return null;

        const rect = cell.getBoundingClientRect();

        return {
            x: rect.left + rect.width/2 - svgRect.left,
            y: rect.top + rect.height/2 - svgRect.top
        };
    }

    // Create arrow line
    function createArrow(from, to, type){

        const start = getCenter(from);
        const end   = getCenter(to);

        if(!start || !end) return;

        const path = document.createElementNS('http://www.w3.org/2000/svg','path');

        path.setAttribute("d", `M ${start.x} ${start.y} L ${end.x} ${end.y}`);
        path.setAttribute("class", "arrow-path " + type);

        path.setAttribute("marker-end",
            type === "ladder"
            ? "url(#arrowhead-ladder)"
            : "url(#arrowhead-snake)"
        );

        path.dataset.from = from;
        path.dataset.to   = to;
        path.dataset.type = type;

        svg.appendChild(path);
    }

    // Draw all threats (snakes)
    threatsData.forEach(t => {
        createArrow(t.from, t.to, "snake");
    });

    // Draw all opportunities (ladders)
    opportunitiesData.forEach(o => {
        createArrow(o.from, o.to, "ladder");
    });
}


/* =========================
   HOVER EFFECT
========================= */

document.querySelectorAll(".cell-icon").forEach(icon => {

    icon.addEventListener("mouseenter", function(){

        const cell = this.closest(".cell");
        const cellNum = parseInt(cell.dataset.cell);

        document.querySelectorAll(".arrow-path").forEach(a => {

            const from = parseInt(a.dataset.from);
            const to   = parseInt(a.dataset.to);

            // reset all
            a.classList.remove("active");

            const target = document.querySelector(`.cell[data-cell="${to}"]`);
            if(target){
                target.classList.remove("target-highlight-ladder","target-highlight-snake");
            }

            // show only matching arrow
            if(from === cellNum){

                a.classList.add("active");

                if(target){
                    if(a.dataset.type === "ladder"){
                        target.classList.add("target-highlight-ladder");
                    }
                    if(a.dataset.type === "snake"){
                        target.classList.add("target-highlight-snake");
                    }
                }
            }
        });
    });

    icon.addEventListener("mouseleave", function(){

        // hide all arrows
        document.querySelectorAll(".arrow-path").forEach(a => {
            a.classList.remove("active");
        });

        // remove highlights
        document.querySelectorAll(".cell").forEach(c => {
            c.classList.remove("target-highlight-ladder","target-highlight-snake");
        });

    });

});


/* =========================
   INITIALIZE
========================= */

window.addEventListener("load", () => {
    setTimeout(drawBoardArrows, 300);
});

window.addEventListener("resize", () => {
    setTimeout(drawBoardArrows, 200);
});

// 🎯 HOVER EFFECT (delegated - better performance)
document.addEventListener("mouseover", function(e){

    const icon = e.target.closest(".cell-icon");
    if(!icon) return;

    const cell = icon.closest(".cell");
    const cellNum = parseInt(cell.dataset.cell);

    document.querySelectorAll(".arrow-path").forEach(a => {

        const from = parseInt(a.dataset.from);
        const to   = parseInt(a.dataset.to);

        // reset
        a.classList.remove("active");

        const target = document.querySelector(`.cell[data-cell="${to}"]`);
        if(target){
            target.classList.remove("target-highlight-ladder","target-highlight-snake");
        }

        // activate only matching
        if(from === cellNum){

            a.classList.add("active");

            if(target){
                if(a.dataset.type === "ladder"){
                    target.classList.add("target-highlight-ladder");
                }
                if(a.dataset.type === "snake"){
                    target.classList.add("target-highlight-snake");
                }
            }
        }
    });
});


// 🎯 REMOVE HOVER
document.addEventListener("mouseout", function(e){

    if(!e.target.closest(".cell-icon")) return;

    document.querySelectorAll(".arrow-path").forEach(a => {
        a.classList.remove("active");
    });

    document.querySelectorAll(".cell").forEach(c => {
        c.classList.remove("target-highlight-ladder","target-highlight-snake");
    });
});


// 🚀 INIT
document.addEventListener("DOMContentLoaded", () => {
    setTimeout(drawBoardArrows, 200);
});




/* HOVER LOGIC */
document.querySelectorAll(".cell-icon").forEach(icon => {

    icon.addEventListener("mouseenter", function(){

        const cell = this.closest(".cell");
        const cellNum = parseInt(cell.dataset.cell);

        document.querySelectorAll(".arrow-path").forEach(a => {

            const from = parseInt(a.dataset.from);
            const to = parseInt(a.dataset.to);

            // reset all
            a.classList.remove("active");

            const target = document.querySelector(`.cell[data-cell="${to}"]`);
            if(target){
                target.classList.remove("target-highlight-ladder","target-highlight-snake");
            }

            // ✅ show ONLY this arrow
            if(from === cellNum){

                a.classList.add("active");

                if(target){
                    if(a.dataset.type === "ladder"){
                        target.classList.add("target-highlight-ladder");
                    }
                    if(a.dataset.type === "snake"){
                        target.classList.add("target-highlight-snake");
                    }
                }
            }

        });

    });

    icon.addEventListener("mouseleave", function(){

        // ❌ hide ALL arrows
        document.querySelectorAll(".arrow-path").forEach(a => {
            a.classList.remove("active");
        });

        // ❌ remove highlights
        document.querySelectorAll(".cell").forEach(c => {
            c.classList.remove("target-highlight-ladder","target-highlight-snake");
        });

    });

});

</script>

<script>

/* =========================
   GAME ACTION HANDLERS
========================= */

function publishGame(){

    Swal.fire({
        title: 'Publish Game?',
        text: 'This game will be available for players.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Publish',
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#4f6df5'
    }).then((result) => {

        if(result.isConfirmed){
            updateGameStatus('publish');
        }

    });

}

function saveAsDraft(){

    updateGameStatus('draft');

}

/* =========================
   UPDATE GAME STATUS
========================= */

function updateGameStatus(action){

    const btn = event.target.closest("button");
    const originalText = btn.innerHTML;

    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';

    fetch('ajax/publish_game.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'game_id=<?php echo $matrix_id; ?>&action=' + action
    })
    .then(res => res.json())
    .then(data => {

        if(data.success){

            Toast.fire({
                icon: 'success',
                title: data.message || 'Action completed successfully'
            });

            setTimeout(() => {
                window.location.href = '<?php echo ADMIN_URL; ?>index.php';
            }, 3000);

        }else{

            Toast.fire({
                icon: 'error',
                title: data.message || 'Something went wrong'
            });

            btn.disabled = false;
            btn.innerHTML = originalText;

        }

    })
    .catch(() => {

        Toast.fire({
            icon: 'error',
            title: 'Network error. Please try again.'
        });

        btn.disabled = false;
        btn.innerHTML = originalText;

    });

}

</script>
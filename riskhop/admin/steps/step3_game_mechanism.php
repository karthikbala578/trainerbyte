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
    echo '<div class="alert alert-warning">Please complete Step 2 first.</div>';
    return;
}

$matrix_id = $game_data['id'];
$total_cells = $game_data['total_cells'];

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
    display: flex;
    gap: 30px;
    flex: 1;
    min-height: auto;   /* ✅ fix */
}
/* LEFT SIDE */
.board-preview-section{
    flex: 0 0 480px;   /* slightly smaller */
    display:flex;
    flex-direction:column;
    justify-content:flex-start;
}
/* 🔥 CARD LIKE 2ND IMAGE */
.board-card{
    width:100%;
    max-width:490px;

    aspect-ratio:1/1;   /* ✅ ADD THIS */
    height:auto;        /* ✅ ADD THIS */

    display:flex;
    align-items:center;
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
    gap:6px;

    width:100%;
    height:100%;          /* 🔥 fill card */
    aspect-ratio:1/1;

    max-height:100%;      /* 🔥 prevents overflow */
}
/* 🔥 BIGGER CELLS */
.cell{
    width: 100%;
    aspect-ratio: 1 / 1;   /* 🔥 perfect square */
    height: 37px;
    background:#f1f5f9;
    border-radius:6px;
position: relative;
    display:flex;
    align-items:center;
    justify-content:center;

    font-size: clamp(8px, 1.2vw, 14px); /* 🔥 responsive text */
}

/* RIGHT SIDE */
.setup-panel{
    width:58%;
}

/* optional: even tighter hover */
.cell:hover{
    background:#e2e8f0;
}


/* RIGHT PANEL */
.setup-panel{
    flex:1;
    min-height: 0;
    overflow-y: auto;   /* ✅ ENABLE SCROLL */
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
    position: relative;   /* 🔥 REMOVE sticky */
    margin-top: 50px;     /* spacing from board */

    display: flex;
    justify-content: space-between;
    align-items: center;
}
.footer-left{
    display:flex;
    flex-direction:column;
    align-items:flex-start;
    gap:12px;   /* 🔥 spacing between legend & button */
}

.prev-btn{
       background:#fff;
    border:2px solid #4f6df5;   /* 🔥 FIX: full border */
    color:#4f6df5;
    padding:10px 16px;
    border-radius:8px;
    cursor:pointer;
    margin-top:0;   
    font-size: 15px;
    
}

.next-btn{
    background: linear-gradient(135deg, #5b6dfc, #4f46e5); /* smoother gradient */
    color: #fff;
    border: none;

    padding: 12px 22px;              /* slightly bigger */
    border-radius: 10px;             /* more rounded like image */

    font-size: 15px;
    font-weight: 500;

    cursor: pointer;

    box-shadow: 0 10px 20px rgba(79, 70, 229, 0.25); /* soft glow */
    
}

.next-btn::after{
    content: " →";
    margin-left: 6px;
    
}
.next-btn:hover{
    background:#3b5bdb;        /* darker blue */
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
/* 🔥 PREVIOUS BUTTON HOVER */
.prev-btn:hover{
    background:#eef2ff;       /* light blue */
    border-color:#3b5bdb;     /* darker blue */
    color:#3b5bdb;
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
   font-size:14px;
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
    background:#facc15;   /* icon box */
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
/* Modal title text color */
.modal-header h3{
    color:#ffffff;
    margin:0;
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
.cell-icon.wildcard{
    width:26px;   /* increase width */
    height:26px;
}

.cell-icon.wildcard i{
    font-size:12px;   /* bigger icon */
    padding:6px;      /* slightly more space */
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
@media (max-width: 1440px) and (min-width: 768px){

    /* ❌ STOP PAGE SCROLL */
    body{
        overflow: hidden;
    }

    /* 🔥 MAIN WRAPPER (FULL HEIGHT) */
    .card{
        height: calc(100vh - 80px); /* adjust header height */
        display: flex;
        flex-direction: column;
    }

    /* 🔥 SCROLL ONLY THIS AREA */
    .card-body{
        flex: 1;
        overflow-y: auto;
        min-height: 0;
        padding-bottom: 20px; /* space above footer */
    }

    /* 🔥 YOUR EXISTING LAYOUT */
    .step2-wrapper{
        flex: 1;
        min-height: 0;
    }

   

    /* 🔥 RIGHT PANEL SCROLL INSIDE */
    .setup-panel{
        max-height: 100%;
        overflow-y: auto;
    }

    /* 🔥 FOOTER ALWAYS VISIBLE */
     .step-footer{
        margin-top: 150px; 
        position: relative; /* normal flow */
    }
}
</style>

<div class="step2-wrapper">

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
        
                        
    </div>

    <!-- RIGHT -->
    <div class="setup-panel">

        <!-- BONUS -->
        <div class="accordion">
            <div class="setup-box yellow" onclick="toggleAccordion('bonusBox')">
                <div class="setup-left">
                    <div class="setup-icon">
                        <i class="fas fa-star"></i>
                    </div>
                    <div>
                        <strong>
                        Bonus Cells
                        <span class="badge bg-primary text-white"><?php echo $bonus_count; ?></span>
                        </strong>
                    </div>
                </div>
                <i class="fas fa-chevron-right arrow"></i>
            </div>

            <div id="bonusBox" class="accordion-content scrollable-list">

                <button class="add-btn" onclick="openBonusModal()">+ Add Bonus</button>

                <?php if(mysqli_num_rows($bonus_result) > 0): ?>
                    <?php mysqli_data_seek($bonus_result, 0); // ✅ RESET POINTER ?>
                    <?php while($b = mysqli_fetch_assoc($bonus_result)): ?>
                    <div class="item-row bonus-row">

                        <div class="bonus-info">
                            <i class="fas fa-star bonus-icon"></i>
                                <span class="bonus-text">
                                Cell <?php echo $b['cell_number']; ?>
                            </span>

                            <?php 
                                $amount = abs((int)$b['bonus_amount']); // ✅ always positive
                            ?>

                            <span class="bonus-badge positive">
                                +<?php echo $amount; ?> Risk Capital
                            </span>
                        </div>

                        <div class="item-actions">
                            <i class="fas fa-pen edit-icon"
                            onclick="editBonus(<?php echo $b['id']; ?>, <?php echo $b['cell_number']; ?>, <?php echo $b['bonus_amount']; ?>)"></i>

                            <i class="fas fa-trash delete-icon"
                            onclick="deleteBonus(<?php echo $b['id']; ?>)"></i>
                        </div>

                    </div>
                    <?php endwhile; ?>
                    <?php else: ?>
                        <div class="empty-state-modern">No bonus added</div>
                    <?php endif; ?>

                </div>
            </div>

            <!-- AUDIT -->
            <div class="accordion">
                <div class="setup-box blue" onclick="toggleAccordion('auditBox')">
                    <div class="setup-left">
                        <div class="setup-icon">
                            <i class="fas fa-search"></i>
                        </div>
                        <div>
                            <strong>
                            Audit Cells
                            <span class="badge bg-primary text-white"><?php echo $audit_count; ?></span>
                            </strong>
                        </div>
                    </div>
                    <i class="fas fa-chevron-right arrow"></i>
                </div>

                <div id="auditBox" class="accordion-content scrollable-list">

                    <button class="add-btn blue" onclick="openAuditModal()">+ Add Audit</button>

                    <?php if(mysqli_num_rows($audit_result) > 0): ?>
                    <?php mysqli_data_seek($audit_result, 0); // ✅ ADD THIS ?>
                    <?php while($a = mysqli_fetch_assoc($audit_result)): ?>
                    <div class="item-row">
                        <div class="item-content">
                            <span class="cell-badge">Cell <?php echo $a['cell_number']; ?></span>
                            <span class="item-desc">Audit Trigger</span>
                        </div>

                        <div class="item-actions">
                            <i class="fas fa-pen edit-icon"
                            onclick="editAudit(<?php echo $a['id']; ?>, <?php echo $a['cell_number']; ?>)"></i>

                            <i class="fas fa-trash delete-icon"
                            onclick="deleteAudit(<?php echo $a['id']; ?>)"></i>
                        </div>
                    </div>
                    <?php endwhile; ?>
                    <?php else: ?>
                        <div class="empty-state-modern">No audit cells added</div>
                    <?php endif; ?>

                </div>
            </div>

            <!-- WILDCARD -->
            <div class="accordion">
                <div class="setup-box purple" onclick="toggleAccordion('wildcardBox')">
                    <div class="setup-left">
                        <div class="setup-icon">
                            <i class="fas fa-question"></i>
                        </div>
                        <div>
                            <strong>
                            Wild Cards
                            <span class="badge bg-primary text-white"><?php echo $wildcard_cells_count; ?></span>
                            </strong>
                        </div>
                    </div>
                    <i class="fas fa-chevron-right arrow"></i>
                </div>

                <div id="wildcardBox" class="accordion-content scrollable-list">
                    <button class="add-btn" onclick="openWildcardCellModal()">+ Add Wildcard</button>

                    <?php if(mysqli_num_rows($wildcard_cells_result) > 0): ?>
                    <?php mysqli_data_seek($wildcard_cells_result, 0); // ✅ ADD THIS ?>
                    <?php while($wc = mysqli_fetch_assoc($wildcard_cells_result)): ?>
                    <div class="item-row">
                        <div class="item-content">
                            <span class="cell-badge">Cell <?php echo $wc['cell_number']; ?></span>
                            <span class="item-desc">Triggers wildcard</span>
                        </div>

                        <div class="item-actions">
                            <i class="fas fa-pen edit-icon"
                            onclick="editWildcardCell(<?php echo $wc['id']; ?>, <?php echo $wc['cell_number']; ?>)"></i>

                            <i class="fas fa-trash delete-icon"
                            onclick="deleteWildcardCell(<?php echo $wc['id']; ?>)"></i>
                        </div>
                    </div>
                    <?php endwhile; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- WILDCARD OPTIONS -->
            <div class="accordion">
                <div class="setup-box purple-light" onclick="toggleAccordion('wildOptBox')">
                    <div class="setup-left">
                        <div class="setup-icon">
                            <i class="fas fa-cog"></i>
                        </div>
                        <div>
                            <strong>
                            Wildcard Options
                            <span class="badge bg-primary text-white"><?php echo $wildcard_options_count; ?></span>
                            </strong>
                        </div>
                    </div>
                    <i class="fas fa-chevron-right arrow"></i>
                </div>

                <div id="wildOptBox" class="accordion-content scrollable-list">
                    <button class="add-btn" onclick="openWildcardOptionModal()">+ Add Wildcard Option</button>
                    <?php if(mysqli_num_rows($wildcards_result) > 0): ?>
                    <?php mysqli_data_seek($wildcards_result, 0); // ✅ ADD THIS ?>
                    <?php while($w = mysqli_fetch_assoc($wildcards_result)): ?>
                    <div class="item-row">
                        <div>
                            <strong><?php echo $w['wildcard_name']; ?></strong><br>
                        </div>

                        <div class="item-actions">
                                <i class="fas fa-pen edit-icon"
                                onclick="editWildcardOption(
                            <?php echo $w['id']; ?>,
                            `<?php echo addslashes($w['wildcard_name']); ?>`,
                            `<?php echo addslashes($w['wildcard_description']); ?>`,
                            '<?php echo $w['wildcard_image']; ?>',
                            <?php echo (int)$w['risk_capital_effect']; ?>,
                            <?php echo (int)$w['dice_effect']; ?>,
                            <?php echo (int)$w['cell_effect']; ?>
                            )"></i>

                            <i class="fas fa-trash delete-icon"
                            onclick="deleteWildcardOption(<?php echo $w['id']; ?>)"></i>
                        </div>
                    </div>
                    <?php endwhile; ?>
                    <?php endif; ?>
                </div>
            </div>

        </div>

    </div>

<!-- FOOTER -->
<div class="step-footer">

    <!-- LEFT SIDE -->
    <div class="footer-left">

        <button class="prev-btn"
            onclick="window.location.href='create_game.php?id=<?php echo $game_id; ?>&step=2'">
            ← Previous
        </button>

        </div>
            <!-- RIGHT SIDE -->
            <div>
                <button class="next-btn"
                    onclick="window.location.href='create_game.php?id=<?php echo $game_id; ?>&step=4'">
                    Continue
                </button>
            </div>

            </div>

           

        </div>
    </div>
</div>

<!-- BONUS MODAL -->
<div id="bonusModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="bonusModalTitle">Add Bonus Cell</h3>
            <button class="modal-close-btn" onclick="closeBonusModal()">
    <i class="fas fa-times"></i>
</button>
        </div>

        <div class="modal-body">
            <form id="bonusForm">
                <input type="hidden" id="bonus_id" name="bonus_id">
                <input type="hidden" name="matrix_id" value="<?php echo $matrix_id; ?>">

                <div class="form-group">
                    <label>Cell Number</label>
                    <input type="number" id="bonus_cell_number" name="cell_number">
                </div>

                <div class="form-group">
                    <label>Bonus Amount</label>
                    <input type="number" id="bonus_amount" name="bonus_amount">
                </div>

                <div class="form-actions">
                    <button type="button" onclick="closeBonusModal()">Cancel</button>
                    <button type="submit" id="bonusSubmitBtn">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>
 <!-- THREAT MODAL -->
            <div id="auditModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="auditModalTitle">Add Audit Cell</h3>
           <button class="modal-close-btn" onclick="closeAuditModal()">
    <i class="fas fa-times"></i>
</button>
        </div>

        <div class="modal-body">
            <form id="auditForm" novalidate>

                <input type="hidden" id="audit_id" name="audit_id">
                <input type="hidden" name="matrix_id" value="<?php echo $matrix_id; ?>">

                <div class="form-group">
                    <label>Cell Number *</label>
                    <input type="number" id="audit_cell" name="cell_number">
                </div>

                <div class="form-actions">
                    <button type="button" onclick="closeAuditModal()">Cancel</button>
                    <button type="submit" id="auditSubmitBtn">+Add More</button>
                </div>

            </form>
        </div>
    </div>
</div>

            <!-- OPPORTUNITY MODAL -->
            <div id="wildcardCellModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="wildCardModalTitle">Add Wildcard Cell</h3>
           <button class="modal-close-btn" onclick="closeWildcardCellModal()">
    <i class="fas fa-times"></i>
</button>

        </div>

        <div class="modal-body">
            <form id="wildcardCellForm" novalidate>
                <input type="hidden" name="matrix_id" value="<?php echo $matrix_id; ?>">

                <div class="form-group">
                    <label>Cell Number *</label>
                    <input type="number" id="wildcard_cell" name="cell_number">
                </div>

                <div class="form-actions">
                    <button type="button" onclick="closeWildcardCellModal()">Cancel</button>
                    <button type="submit" id="wildCardSubmitBtn">+Add More</button>
                </div>
            </form>
        </div>
    </div>
</div>
            <!-- STRATEGY MODAL -->
<div id="wildcardOptionModal" class="modal wildcard-modal">
    <div class="modal-content" style="max-width: 600px;">
        <div class="modal-header">
            <h3 id="wildcardOptionModalTitle">Add Wildcard Option</h3>
           <button class="modal-close-btn" onclick="closeWildcardOptionModal()">
    <i class="fas fa-times"></i>
</button>
        </div>
        <div class="modal-body">
            <form id="wildcardOptionForm" enctype="multipart/form-data">

    <input type="hidden" id="wildcard_id" name="wildcard_id">
    <input type="hidden" name="matrix_id" value="<?php echo $matrix_id; ?>">

    <div class="wildcard-grid">

        <!-- NAME -->
        <div class="form-group">
            <label>Wildcard Name *</label>
            <input type="text" id="wildcard_name" name="wildcard_name" placeholder="Enter name">
        </div>

        <!-- IMAGE -->
        <div class="form-group">
            <label>Image (Optional)</label>
            <input type="file" name="wildcard_image">

             <img id="wildcard_preview"
         style="display:none;margin-top:10px;width:80px;border-radius:6px;">
        </div>

        <!-- DESCRIPTION FULL -->
        <div class="form-group wildcard-full">
            <label>Description *</label>
            <textarea id="wildcard_description" name="wildcard_description"></textarea>
        </div>

        <!-- EFFECTS -->
        <div class="form-group">
            <label>Risk Capital</label>
            <input type="number" id="risk_capital_effect" name="risk_capital_effect">
        </div>

        <div class="form-group">
            <label>Dice Effect</label>
            <input type="number" id="dice_effect" name="dice_effect">
        </div>

        <div class="form-group wildcard-full">
            <label>Cell Movement</label>
            <input type="number" id="cell_effect" name="cell_effect">
        </div>

    </div>

    <div class="form-actions">
        <button type="button" onclick="closeWildcardOptionModal()">Cancel</button>
        <button type="submit" id="wildcardOptionSubmitBtn">+Add More</button>
    </div>

</form>
        </div>
    </div>
</div>
<script>
  
function setFieldError(input, message){
    input.classList.add('error');

    const parent = input.closest('.form-group');

    let error = parent.querySelector('.error-text');
    if(!error){
        error = document.createElement('div');
        error.className = 'error-text';
        parent.appendChild(error);
    }

    error.innerText = message;
}

function clearError(input){
    input.classList.remove('error');

    const parent = input.closest('.form-group'); // ✅ FIX
    const error = parent.querySelector('.error-text');

    if(error){
        error.remove();
    }
}

function toggleAccordion(id){
    document.querySelectorAll('.accordion').forEach(acc => {
        if(acc.querySelector('.accordion-content').id !== id){
            acc.classList.remove('active');
        }
    });

    const box = document.getElementById(id);
    const parent = box.closest('.accordion');
    parent.classList.toggle('active');
}


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


function openBonusModal(){

    const form = document.getElementById('bonusForm');

    form.reset();

    document.getElementById('bonus_id').value = '';

    document.getElementById('bonusModalTitle').innerText = 'Add Bonus Cell';
    document.getElementById('bonusSubmitBtn').innerText = '+Add More';

    document.getElementById('bonusModal').style.display='flex';
    // ✅ AUTO FOCUS
    setTimeout(()=>{
        document.getElementById('bonus_cell_number')?.focus();
    },150);
}

function editBonus(id, cell, amount){
    openBonusModal();
    document.getElementById('bonus_id').value=id;
    document.getElementById('bonus_cell_number').value=cell;
    document.getElementById('bonus_amount').value=amount;

    document.getElementById('bonusModalTitle').innerText = 'Edit Bonus Cell';
    document.getElementById('bonusSubmitBtn').innerText = 'Update';
}

document.getElementById('bonusForm').addEventListener('submit', function(e){
    e.preventDefault();

    let valid=true;

    const cell = document.getElementById('bonus_cell_number');
    const amount = document.getElementById('bonus_amount');

    [cell,amount].forEach(clearError);

    if(!cell.value){
        setFieldError(cell,'Enter cell');
        valid=false;
    }

 if(!amount.value){
    setFieldError(amount,'Enter amount');
    valid=false;
} else if(parseInt(amount.value) <= 0){
    setFieldError(amount,'Only positive values allowed');
    valid=false;
}

    if(!valid){
        document.querySelector('.error')?.focus();
        return;
    }

    const formData = new FormData(this);
    const id = document.getElementById('bonus_id').value;

    fetch(id ? 'ajax/edit_bonus.php' : 'ajax/add_bonus.php',{
        method:'POST',
        body:formData
    })
    .then(r=>r.json())
    .then(res=>{
        if(res.success){
    closeBonusModal();

    Swal.fire({
        icon:'success',
        title:res.message,
        confirmButtonColor:'#4f6df5'
    }).then(() => {
         reloadWithModal('bonus');
    });

}else{
    Swal.fire({
        icon:'error',
        title:'Error',
        text:res.message,
        confirmButtonColor:'#ef4444'
    });
}
    });
});
function closeBonusModal(){
    document.getElementById('bonusModal').style.display='none';
}
function deleteBonus(id){
    Swal.fire({
        title:'Delete Bonus?',
        icon:'warning',
        showCancelButton:true,
        confirmButtonColor:'#ef4444'
    }).then(r=>{
        if(r.isConfirmed){
            const fd=new FormData();
            fd.append('bonus_id',id);

            fetch('ajax/delete_bonus.php',{
                method:'POST',
                body:fd
            })
            .then(r=>r.json())
            .then(res=>{
    Swal.fire({
        icon:'success',
        title:res.message,
        confirmButtonColor:'#4f6df5'
    }).then(() => {
        reloadWithModal('bonus'); 
    });
});
        }
    });
}
function openAuditModal(){

    const form = document.getElementById('auditForm');

    form.reset();

    document.getElementById('audit_id').value = '';

    document.getElementById('auditModalTitle').innerText = 'Add Audit Cell';
    document.getElementById('auditSubmitBtn').innerText = '+Add More';

    document.getElementById('auditModal').style.display='flex';
     // ✅ AUTO FOCUS
    setTimeout(()=>{
        document.getElementById('audit_cell')?.focus();
    },150);
}
function closeAuditModal(){
    document.getElementById('auditModal').style.display='none';
}

document.getElementById('auditForm').addEventListener('submit', function(e){
    e.preventDefault();

    let valid = true;
    const cell = this.querySelector('input[name="cell_number"]');

    clearError(cell);

    if(!cell.value){
        setFieldError(cell, 'Please enter cell number');
        valid = false;
    }

    if(!valid){
        document.querySelector('.error')?.focus();
        return;
    }

    const formData = new FormData(this);

   const id = document.getElementById('audit_id').value;

fetch(id ? 'ajax/edit_audit.php' : 'ajax/add_audit.php',{
    method:'POST',
    body:formData
})
.then(res => res.text()) // 🔥 first get raw response
.then(text => {
    let data;
    try {
        data = JSON.parse(text);
    } catch(e) {
        console.error("Invalid JSON:", text);
      Swal.fire({
    icon: 'error',
    title: 'Error',
    text: 'Server error',
    confirmButtonColor: '#ef4444'
});
        return;
    }

    if(data.success){
    closeAuditModal();

    Swal.fire({
        icon:'success',
        title:data.message,
        confirmButtonColor:'#4f6df5'
    }).then(() => {
         reloadWithModal('audit');
    });

} else {
    Swal.fire({
        icon:'error',
        title:'Error',
        text:data.message,
        confirmButtonColor:'#ef4444'
    });
}
});
});

function deleteAudit(id){
    Swal.fire({
        title:'Delete Audit?',
        icon:'warning',
        showCancelButton:true,
        confirmButtonColor:'#ef4444'
    }).then(r=>{
        if(r.isConfirmed){
            const fd = new FormData();
            fd.append('audit_id', id);

            fetch('ajax/delete_audit.php',{
    method:'POST',
    body:fd
})
.then(res => res.json())
.then(res => {

    if(res.success){
    Swal.fire({
        icon:'success',
        title:res.message,
        confirmButtonColor:'#4f6df5'
    }).then(() => {
       reloadWithModal('audit');
    });
} else {
    Swal.fire({
        icon:'error',
        title:'Error',
        text:res.message,
        confirmButtonColor:'#ef4444'
    });
}

})
.catch(() => {
   Swal.fire({
    icon: 'error',
    title: 'Error',
    text: 'Server error',
    confirmButtonColor: '#ef4444'
});
});
        }
    });
}
function openWildcardCellModal(){

    const form = document.getElementById('wildcardCellForm');

    form.reset();

    const hidden = document.getElementById('wildcard_cell_id');
    if(hidden) hidden.value = '';

    document.getElementById('wildCardModalTitle').innerText = 'Add Wildcard Cell';
    document.getElementById('wildCardSubmitBtn').innerText = '+Add More';

    document.getElementById('wildcardCellModal').style.display='flex';
      setTimeout(()=>{
        document.getElementById('wildcard_cell')?.focus();
    },150);
}
document.getElementById('wildcardCellForm').addEventListener('submit', function(e){
    e.preventDefault();

    let valid = true;
    const cell = document.getElementById('wildcard_cell');
    const maxCell = <?php echo $total_cells; ?>;

    clearError(cell);

    if(!cell.value){
        setFieldError(cell,'Enter cell number');
        valid = false;
    } 
    else if(cell.value <= 0){
        setFieldError(cell,'Must be greater than 0');
        valid = false;
    }
    else if(cell.value > maxCell){
        setFieldError(cell,'Max allowed is ' + maxCell);
        valid = false;
    }

    if(!valid){
        document.querySelector('.error')?.focus();
        return;
    }

    const formData = new FormData(this);
    const id = document.getElementById('wildcard_cell_id')?.value;

    fetch(id ? 'ajax/edit_wildcard_cell.php' : 'ajax/add_wildcard_cell.php',{
        method:'POST',
        body:formData
    })
    .then(r=>r.json())
    .then(res=>{
       if(res.success){
    closeWildcardCellModal();

    Swal.fire({
        icon:'success',
        title:res.message,
        confirmButtonColor:'#4f6df5'
    }).then(() => {
        reloadWithModal('wildcardCell');
    });

}else{
    Swal.fire({
        icon:'error',
        title:'Error',
        text:res.message,
        confirmButtonColor:'#ef4444'
    });
}
    })
    .catch(()=>{
        Swal.fire({
    icon:'error',
    title:'Error',
    text:'Server error',
    confirmButtonColor:'#ef4444'
});
    });
});

function deleteWildcardCell(id){
    Swal.fire({
        title:'Delete Wildcard Cell?',
        icon:'warning',
        showCancelButton:true,
        confirmButtonColor:'#ef4444'
    }).then(r=>{
        if(r.isConfirmed){
            const fd=new FormData();
            fd.append('wildcard_cell_id', id); // ✅ FIXED KEY

            fetch('ajax/delete_wildcard_cell.php',{
                method:'POST',
                body:fd
            })
            .then(r=>r.json())
            .then(res=>{
               if(res.success){
    Swal.fire({
        icon:'success',
        title:res.message,
        confirmButtonColor:'#4f6df5'
    }).then(() => {
       reloadWithModal('wildcardCell');
    });
}else{
    Swal.fire({
        icon:'error',
        title:'Error',
        text:res.message,
        confirmButtonColor:'#ef4444'
    });
}
            })
            .catch(()=>{
               Swal.fire({
    icon: 'error',
    title: 'Error',
    text: 'Server error',
    confirmButtonColor: '#ef4444'
});
            });
        }
    });
}
function openWildcardOptionModal(){

    const form = document.getElementById('wildcardOptionForm');

    form.reset();

    document.getElementById('wildcard_id').value='';

    document.getElementById('wildcardOptionModalTitle').innerText='Add Wildcard Option';
    document.getElementById('wildcardOptionSubmitBtn').innerText='+Add More';

    // hide preview
    const preview = document.getElementById('wildcard_preview');
    preview.src='';
    preview.style.display='none';

    document.getElementById('wildcardOptionModal').style.display='flex';

    setTimeout(()=>{
        document.getElementById('wildcard_name').focus();
    },200);
}

function closeWildcardOptionModal(){
    document.getElementById('wildcardOptionModal').style.display='none';
}

function editWildcardOption(id, name, desc, image, risk, dice, cell){

    openWildcardOptionModal();

    document.getElementById('wildcard_id').value = id;

    document.getElementById('wildcard_name').value = name || '';
    document.getElementById('wildcard_description').value = desc || '';

    document.getElementById('risk_capital_effect').value = risk ?? 0;
    document.getElementById('dice_effect').value = dice ?? 0;
    document.getElementById('cell_effect').value = cell ?? 0;

    // 🔥 Show existing image
    const preview = document.getElementById('wildcard_preview');

    if(image){
        preview.src = '../../' + image;
        preview.style.display = 'block';
    }else{
        preview.style.display = 'none';
    }

    document.getElementById('wildcardOptionModalTitle').innerText = 'Edit Wildcard Option';
    document.getElementById('wildcardOptionSubmitBtn').innerText = 'Update';
}

document.getElementById('wildcardOptionForm').addEventListener('submit', function(e){
    e.preventDefault();

    let valid = true;

    const name = document.getElementById('wildcard_name');
    const desc = document.getElementById('wildcard_description');
    const risk = document.getElementById('risk_capital_effect');
    const dice = document.getElementById('dice_effect');
    const cell = document.getElementById('cell_effect');

    [name, desc, risk, dice, cell].forEach(clearError);

    // NAME
    if(name.value.trim() === ''){
        setFieldError(name,'Enter wildcard name');
        valid = false;
    }

    // DESCRIPTION
    if(desc.value.trim() === ''){
        setFieldError(desc,'Enter description');
        valid = false;
    }

    // NUMBER VALIDATION
    function validateNumber(input, label){
        let val = input.value.trim();

        if(val === ''){
            input.value = 0;
            return true;
        }

        val = parseInt(val);

        if(isNaN(val)){
            setFieldError(input, label + ' must be number');
            return false;
        }

        return true;
    }

    if(!validateNumber(risk,'Risk Capital')) valid = false;
    if(!validateNumber(dice,'Dice Effect')) valid = false;
    if(!validateNumber(cell,'Cell Movement')) valid = false;

    // ❌ STOP
    if(!valid){
        const firstError = document.querySelector('#wildcardOptionForm .error');

        if(firstError){
            firstError.focus();
            firstError.scrollIntoView({behavior:'smooth', block:'center'});
        }
        return;
    }

    // ✅ SUBMIT
    const formData = new FormData(this);
    const id = document.getElementById('wildcard_id').value;

    fetch(id ? 'ajax/edit_wildcard_option.php' : 'ajax/add_wildcard_option.php',{
        method:'POST',
        body:formData
    })
    .then(r=>r.json())
    .then(res=>{
        if(res.success){
    closeWildcardOptionModal();

    Swal.fire({
        icon:'success',
        title:res.message,
        confirmButtonColor:'#4f6df5'
    }).then(() => {
        reloadWithModal('wildcardOption');
    });

}else{
    Swal.fire({
        icon:'error',
        title:'Error',
        text:res.message,
        confirmButtonColor:'#ef4444'
    });
}
    })
    .catch(()=>{
        Swal.fire({
    icon:'error',
    title:'Error',
    text:'Server error',
    confirmButtonColor:'#ef4444'
});
    });
});
function deleteWildcardOption(id){

    Swal.fire({
        title:'Delete Wildcard Option?',
        text:'This cannot be undone',
        icon:'warning',
        showCancelButton:true,
        confirmButtonColor:'#ef4444',
        confirmButtonText:'Yes, delete it'
    }).then(result => {

        if(result.isConfirmed){

            const fd = new FormData();
            fd.append('wildcard_id', id);

            fetch('ajax/delete_wildcard_option.php',{
                method:'POST',
                body:fd
            })
            .then(res => res.json())
            .then(res => {

               if(res.success){
    Swal.fire({
        icon:'success',
        title:res.message,
        confirmButtonColor:'#4f6df5'
    }).then(() => {
        reloadWithModal('wildcardOption');
    });
} else {
    Swal.fire({
        icon:'error',
        title:'Error',
        text:res.message,
        confirmButtonColor:'#ef4444'
    });
}

            })
            .catch(()=>{
               Swal.fire({
    icon: 'error',
    title: 'Error',
    text: 'Server error',
    confirmButtonColor: '#ef4444'
});
            });
        }
    });
}

function closeAuditModal(){
    document.getElementById('auditModal').style.display='none';
}

function closeWildcardCellModal(){
    document.getElementById('wildcardCellModal').style.display='none';
}


function applyConditionalScroll(){
    document.querySelectorAll('.scrollable-list').forEach(box => {

        // count only rows (ignore button)
        const items = box.querySelectorAll('.item-row');

        if(items.length > 2){
            box.classList.add('active-scroll');
        }else{
            box.classList.remove('active-scroll');
        }
    });
}

// run on load
document.addEventListener("DOMContentLoaded", applyConditionalScroll);

// run after accordion open (important)
function toggleAccordion(id){
    document.querySelectorAll('.accordion').forEach(acc => {
        if(acc.querySelector('.accordion-content').id !== id){
            acc.classList.remove('active');
        }
    });

    const box = document.getElementById(id);
    const parent = box.closest('.accordion');
    parent.classList.toggle('active');

    setTimeout(applyConditionalScroll, 100); // 🔥 ensure DOM ready
}
// ✅ REMOVE ERROR ON TYPING (ALL INPUTS & TEXTAREAS)
document.addEventListener('input', function(e) {
    if (e.target.matches('input, textarea')) {
        clearError(e.target);
    }
});
function editAudit(id, cell){
    // Open modal
    openAuditModal();

    // Fill values
    document.getElementById('audit_id').value = id;
    document.getElementById('audit_cell').value = cell;

    // Change title & button
    document.getElementById('auditModalTitle').innerText = 'Edit Audit Cell';
    document.getElementById('auditSubmitBtn').innerText = 'Update';
}
function editWildcardCell(id, cell){
    openWildcardCellModal();

    document.getElementById('wildcard_cell').value = cell;

    // store id
    let hidden = document.getElementById('wildcard_cell_id');
    if(!hidden){
        hidden = document.createElement('input');
        hidden.type = 'hidden';
        hidden.id = 'wildcard_cell_id';
        hidden.name = 'wildcard_cell_id';
        document.getElementById('wildcardCellForm').appendChild(hidden);
    }

    hidden.value = id;

    // Change title & button
    document.getElementById('wildCardModalTitle').innerText = 'Edit WildCard Cell';
    document.getElementById('wildCardSubmitBtn').innerText = 'Update';
}
function refreshSetupPanel(){

    fetch(window.location.href)
    .then(res => res.text())
    .then(html => {

        const parser = new DOMParser();
        const doc = parser.parseFromString(html, 'text/html');

        const newPanel = doc.querySelector('.setup-panel');
        const newBoard = doc.querySelector('.board-preview');

        document.querySelector('.setup-panel').innerHTML = newPanel.innerHTML;
        document.querySelector('.board-preview').innerHTML = newBoard.innerHTML;

        applyConditionalScroll();
        drawBoardArrows();

    });

}
function reloadWithModal(modalName){
    localStorage.setItem('reopenModal', modalName);
    location.reload();
}
document.addEventListener("DOMContentLoaded", () => {

    const modal = localStorage.getItem('reopenModal');

    if(!modal) return;

    setTimeout(() => {

        switch(modal){

            case 'bonus':
                openBonusModal();
                document.getElementById('bonus_cell_number')?.focus();
                break;

            case 'audit':
                openAuditModal();
                document.getElementById('audit_cell')?.focus();
                break;

            case 'wildcardCell':
                openWildcardCellModal();
                document.getElementById('wildcard_cell')?.focus();
                break;

            case 'wildcardOption':
                openWildcardOptionModal();
                document.getElementById('wildcard_name')?.focus();
                break;
        }

        localStorage.removeItem('reopenModal');

    }, 300);

});
</script>
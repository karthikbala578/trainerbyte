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
    echo '<div class="alert alert-warning">Please complete Step 1 first.</div>';
    return;
}

$matrix_id = $game_data['id'];
$total_cells = $game_data['total_cells'];

// Get existing threats with strategies
$threats_query = "SELECT t.*, 
                  GROUP_CONCAT(DISTINCT CONCAT(s.id, ':', s.strategy_name, ':', s.response_points) SEPARATOR '||') as strategies
                  FROM mg6_riskhop_threats t
                  LEFT JOIN mg6_threat_strategy_mapping tsm ON t.id = tsm.threat_id
                  LEFT JOIN mg6_riskhop_strategies s ON tsm.strategy_id = s.id
                  WHERE t.matrix_id = '$matrix_id'
                  GROUP BY t.id
                  ORDER BY t.id ASC";
$threats_result = mysqli_query($conn, $threats_query);

// Get existing opportunities with strategies
$opportunities_query = "SELECT o.*, 
                        GROUP_CONCAT(DISTINCT CONCAT(s.id, ':', s.strategy_name, ':', s.response_points) SEPARATOR '||') as strategies
                        FROM mg6_riskhop_opportunities o
                        LEFT JOIN mg6_opportunity_strategy_mapping osm ON o.id = osm.opportunity_id
                        LEFT JOIN mg6_riskhop_strategies s ON osm.strategy_id = s.id
                        WHERE o.matrix_id = '$matrix_id'
                        GROUP BY o.id
                        ORDER BY o.id ASC";
$opportunities_result = mysqli_query($conn, $opportunities_query);

$snake_count = is_object($threats_result) ? mysqli_num_rows($threats_result) : 0;
$ladder_count = is_object($opportunities_result) ? mysqli_num_rows($opportunities_result) : 0;

if ($threats_result && $snake_count > 0) {
    mysqli_data_seek($threats_result, 0);
}

if ($opportunities_result && $ladder_count > 0) {
    mysqli_data_seek($opportunities_result, 0);
}

// Get all existing strategies
$all_strategies_query = "SELECT * FROM mg6_riskhop_strategies 
                         WHERE matrix_id = '$matrix_id'
                         ORDER BY strategy_name";
$all_strategies_result = mysqli_query($conn, $all_strategies_query);
$all_strategies = [];
while ($strategy = mysqli_fetch_assoc($all_strategies_result)) {
    $all_strategies[] = $strategy;
}
?>
<?php
$threat_from_cells = [];

if ($threats_result && $snake_count > 0) {
    mysqli_data_seek($threats_result, 0);
    while ($t = mysqli_fetch_assoc($threats_result)) {
        $threat_from_cells[] = $t['cell_from'];
    }
}

$opportunity_from_cells = [];

if ($opportunities_result && $ladder_count > 0) {
    mysqli_data_seek($opportunities_result, 0);
    while ($o = mysqli_fetch_assoc($opportunities_result)) {
        $opportunity_from_cells[] = $o['cell_from'];
    }
}

// BONUS
$bonus_query = "SELECT cell_number FROM mg6_riskhop_bonus WHERE matrix_id = '$matrix_id'";
$bonus_result = mysqli_query($conn, $bonus_query);

$bonus_cells = [];
if ($bonus_result && mysqli_num_rows($bonus_result) > 0) {
    while ($b = mysqli_fetch_assoc($bonus_result)) {
        $bonus_cells[] = (int)$b['cell_number'];
    }
}

// AUDIT
$audit_query = "SELECT cell_number FROM mg6_riskhop_audit WHERE matrix_id = '$matrix_id'";
$audit_result = mysqli_query($conn, $audit_query);

$audit_cells = [];
if ($audit_result && mysqli_num_rows($audit_result) > 0) {
    while ($a = mysqli_fetch_assoc($audit_result)) {
        $audit_cells[] = (int)$a['cell_number'];
    }
}

// WILDCARD
$wildcard_query = "SELECT cell_number FROM mg6_riskhop_wildcard_cells WHERE matrix_id = '$matrix_id'";
$wildcard_result = mysqli_query($conn, $wildcard_query);

$wildcard_cells = [];
if ($wildcard_result && mysqli_num_rows($wildcard_result) > 0) {
    while ($wc = mysqli_fetch_assoc($wildcard_result)) {
        $wildcard_cells[] = (int)$wc['cell_number'];
    }
}
?>
<style>
    :root{
    --fs-xs: clamp(10px, 0.7vw, 12px);
    --fs-sm: clamp(12px, 0.8vw, 14px);
    --fs-md: clamp(14px, 1vw, 16px);
    --fs-lg: clamp(16px, 1.2vw, 20px);
    --fs-xl: clamp(18px, 1.5vw, 24px);
}
.main-container {
    display: flex;
    flex-direction: column;
    min-height: 100vh;   /* ✅ allow expansion */
    height: auto;        /* ✅ critical fix */
}
.step2-wrapper{
    display: flex;
    gap: 30px;
    flex: 1;
    min-height: auto;   /* ✅ fix */
}
/* LEFT SIDE */
.board-preview-section{
   flex: 0 0 500px;   
    display:flex;
    flex-direction:column;
    justify-content:flex-start;
}
/* 🔥 CARD LIKE 2ND IMAGE */
.board-card{
    width: 100%;
    max-width: 480px;

    aspect-ratio: 1 / 1;   /* 🔥 ALWAYS square */
    
    display: flex;
    align-items: center;
    justify-content: center;

    height: auto;          /* ❌ remove fixed height */
    max-height: none;      /* ❌ remove vh restriction */
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
    gap:5px;

    width:100%;
    height:100%;          /* 🔥 fill card */
    aspect-ratio:1/1;

    max-height:100%;      /* 🔥 prevents overflow */
}
/* 🔥 BIGGER CELLS */
.cell{
    width: 100%;
    aspect-ratio: 1 / 1;   /* 🔥 perfect square */
     height: 35px;
    background:#f1f5f9;
    border-radius:6px;
position: relative;
    display:flex;
    align-items:center;
    justify-content:center;

   font-size: clamp(10px, 1.4vw, 18px);
}



/* optional: even tighter hover */
.cell:hover{
    background:#e2e8f0;
}



.setup-card{
    background:#f9fafb;
    border-radius:10px;
    padding:16px;
    margin-bottom:14px;
    display:flex;
    justify-content:space-between;
    align-items:center;
    cursor:pointer;
    border:1px solid #e5e7eb;
    transition:0.2s;
}
.setup-card strong{
    font-size: var(--fs-md);
}
.prev-btn,
.next-btn{
    font-size: var(--fs-md);
}
.setup-card:hover{
    border-color:#4f6df5;
    background:#eef2ff;
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
    font-size: var(--fs-xl);
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
    margin-top: 60px;     /* spacing from board */

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
  font-size: var(--fs-sm);
    margin-bottom:14px;
}

.count-text{
   font-size: var(--fs-xs);
    color:#6b7280;
}

/* ACCORDION */
.accordion{
    margin-bottom:12px;
}

/* HIDDEN CONTENT */
.accordion-content{
    display:none;
    background:#f8fafc;
    border:1px solid #e5e7eb;
    border-top:none;
    border-radius:0 0 10px 10px;
    padding:14px;

    max-height: 340px;   /* 🔥 ADD THIS */
    overflow: visible;    /* 🔥 ADD THIS */
}

/* ACTIVE */
.accordion.active .accordion-content{
    display:block;
}

/* BUTTON */
.add-btn{
    width:100%;
    background:#ff6b6b;
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

.modal-header h3,
#strategyModal .modal-header h3{
    font-size: var(--fs-xl);
    color:#ffffff !important;
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
    font-size:22px;
    cursor:pointer;
    color:#fff;              /* white icon like strategy modal */
    opacity:0.8;
    transition:0.25s ease;

    width:34px;
    height:34px;
    border-radius:8px;

    display:flex;
    align-items:center;
    justify-content:center;
}

.modal-close:hover{
    opacity:1;
    transform:scale(1.1);
    background:rgba(255,255,255,0.15);
}

.modal-body{
     padding: 20px;
}
.form-group{
    margin-bottom: 16px;
}

.form-group label{
    display:block;
   font-size: var(--fs-xs);
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
   font-size: var(--fs-sm);
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
.form-group input.error,
.form-group textarea.error{
    border-color: #ef4444;
    box-shadow: 0 0 0 3px rgba(239,68,68,0.15);
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
/* 🔥 BASE ICON */
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

/* 🔥 SCROLL CONTAINER */
.threat-scroll{
    max-height: 170px;   /* ✅ ~3 items only */
    overflow-y: auto;
    padding-right: 4px;
}
.threat-scroll::after{
    content: "";
    position: sticky;
    bottom: 0;
    height: 20px;
    display: block;
    background: linear-gradient(to top, #f8fafc, transparent);
}
/* 🔥 CUSTOM SCROLLBAR */
.threat-scroll::-webkit-scrollbar{
    width: 4px;   /* 🔥 smaller */
}

.threat-scroll::-webkit-scrollbar-thumb{
    background: #4f6df5;   /* primary color */
    border-radius: 10px;
}

/* 🔥 LIGHTER HOVER */
.threat-scroll::-webkit-scrollbar-thumb:hover{
    background: #6f86ff;   /* lighter shade */
}
/* TRACK */
.threat-scroll::-webkit-scrollbar-track{
    background: #f1f5f9;
    border-radius: 10px;
}

/* 🔥 SCROLL CONTAINER */
.opportunity-scroll{
    max-height: 170px;   /* ✅ ~3 items only */
    overflow-y: auto;
    padding-right: 4px;
}
.opportunity-scroll::after{
    content: "";
    position: sticky;
    bottom: 0;
    height: 20px;
    display: block;
    background: linear-gradient(to top, #f8fafc, transparent);
}
/* 🔥 CUSTOM SCROLLBAR */
.opportunity-scroll::-webkit-scrollbar{
    width: 4px;   /* 🔥 smaller */
}

.opportunity-scroll::-webkit-scrollbar-thumb{
    background: #4f6df5;  /* softer color */
    border-radius: 10px;
}

.opportunity-scroll::-webkit-scrollbar-thumb:hover{
    background: #6f86ff;
}
/* TRACK */
.opportunity-scroll::-webkit-scrollbar-track{
    background: #f1f5f9;
    border-radius: 10px;
}
/* EDIT ICON */
.item-actions .fa-pen:hover{
    color:#22c55e;
}

/* DELETE ICON */
.item-actions .fa-trash:hover{
    color:#ef4444;
}
/* 🔥 STRATEGY LIST */
#strategyList{
    display:grid;
    grid-template-columns: repeat(2, 1fr);

    gap:18px;                  /* 🔥 increased from 12px → 18px */

    max-height:240px;
    overflow-y:auto;
}
.strategy-section{
    background:#f9fafb;
    border:1px solid #e5e7eb;
    border-radius:12px;
    padding:12px;
}
/* CARD STYLE */
.strategy-card{
    display:flex;
    align-items:center;
    justify-content:space-between;
    
    padding:16px 18px;          /* 🔥 increased height */
    min-height:64px;            /* 🔥 consistent height */

    border:1px solid #e5e7eb;
    border-radius:12px;
    background:#fff;

    gap:14px;                   /* 🔥 more spacing inside */

    transition:0.2s ease;
}
#strategyModal .modal-body{
    padding:18px 20px;
    overflow-x: hidden;   /* ✅ stop horizontal scroll */
}
.strategy-card:hover{
    transform:translateY(-2px);
    box-shadow:0 8px 20px rgba(0,0,0,0.08);
    border-color:#4f6df5;
}


.strategy-delete:hover{
    background:#ef4444;
    color:#fff;
}

/* LEFT CONTENT */
.strategy-info{
   display:flex;
    flex-direction:column;
    gap:6px;                  /* 🔥 increased spacing */

    flex:1;
    min-width:0;
}
.strategy-delete{
    
    margin-left:auto;    /* 🔥 FORCE RIGHT END */
}
.strategy-name{
     font-weight:600;
    font-size:14px;
    color:#111827;
     word-break: break-word;
}

.strategy-points{
    font-size: var(--fs-xs);
     font-size:12px;
    color:#4f6df5;
}

/* DELETE ICON */
.strategy-delete{
    flex-shrink:0;       /* 🔥 prevents shifting */
    width:34px;
    height:34px;

    display:flex;
    align-items:center;
    justify-content:center;

    border-radius:8px;
    background:#fee2e2;
    color:#ef4444;

    cursor:pointer;
}


/* EMPTY STATE */
.empty-state-modern{
    text-align:center;
    padding:12px 10px;   /* 🔥 reduced padding */
    color:#9ca3af;
    font-size:13px;
}
#strategyForm{
    background:#f9fafb;
    padding:15px;
    border-radius:10px;
    border:1px solid #e5e7eb;
}
.map-item{
    display:flex;
    align-items:center;
    gap:10px;
    padding:8px 10px;
    border-radius:8px;
    cursor:pointer;
    transition:0.2s;
}

.map-item:hover{
    background:#eef2ff;
}

.map-item input{
    accent-color:#4f6df5;
}
#strategyModal .modal-content{
    width: 900px;              /* 🔥 increased width */
    max-width: 95%;

    height: 650px;             /* 🔥 increased height */
    max-height: 90vh;          /* 🔥 responsive for smaller screens */

    display: flex;
    flex-direction: column;

    border-radius: 16px;
    overflow: hidden;

    box-shadow: 0 25px 70px rgba(0,0,0,0.25);
}
#strategyModal .modal-header{
    display:flex;
    justify-content:space-between;
    align-items:center;

    padding:16px 20px;
    font-size:17px;
    font-weight:600;

    background:linear-gradient(135deg,#4f6df5,#6366f1);
    color:#fff;
}

#strategyModal .modal-close{
    font-size:22px;
    cursor:pointer;
    color:#fff;
    opacity:0.8;
    transition:0.2s;
}
.add-strategy-wrapper{
    margin-top: 12px;     /* 🔥 reduced */
    padding-top: 10px;    /* 🔥 reduced */
    border-top: 1px solid #eee;
}
#strategyModal .modal-close:hover{
    opacity:1;
    transform:scale(1.1);
}
#strategyModal .modal-body{
    flex:1;
    display:flex;
    flex-direction:column;
    gap:12px;
overflow-y: auto;   /* 🔥 enables scroll inside modal */
    padding: 16px;
}
#strategyList{
    display:grid;
    grid-template-columns: repeat(2, 1fr);  /* ✅ 2 per row */
    gap:12px;

    max-height: 240px;
    overflow-y:auto;
    overflow-x:hidden;                      /* ✅ no horizontal scroll */
}
.map-existing{
    display:grid;
    grid-template-columns: repeat(2, 1fr); /* 🔥 2 columns */
    gap:10px;

    max-height: 220px;   /* 🔥 4 items visible */
    overflow-y: auto;
}

/* SCROLLBAR */
.map-existing::-webkit-scrollbar{
    width:5px;
}

.map-existing::-webkit-scrollbar-thumb{
    background: linear-gradient(135deg,#4f6df5,#6366f1);
    border-radius:10px;
}

.map-existing::-webkit-scrollbar-track{
    background:#f1f5f9;
}
#strategyModal .modal-body > div {
    flex-shrink: 0;
}

#strategyModal .prev-btn{
    width:100%;
    text-align:center;
}
/* scrollbar */
#strategyList::-webkit-scrollbar{
    width:5px;
}

#strategyList::-webkit-scrollbar-thumb{
    background: linear-gradient(135deg,#4f6df5,#6366f1);
    border-radius:10px;
}

#strategyList::-webkit-scrollbar-track{
    background:#f1f5f9;
}
.new-strategy {
    animation: fadeIn 0.4s ease;
}
.setup-panel{
    flex: 1;
    overflow: visible;   /* ✅ remove inner scroll */
    padding-right: 10px;
}
@keyframes fadeIn {
    from {opacity:0; transform:translateY(5px);}
    to {opacity:1; transform:translateY(0);}
}
/* ✅ Tablet range → allow full page scroll */
@media (max-width: 1440px) and (min-width: 768px){

    body{
        overflow-y: auto;   /* ✅ full page scroll */
    }

    .main-container{
        height: auto;       /* ❌ remove fixed height */
        min-height: 100vh;  /* keep structure */
    }

    .step2-wrapper{
        height: auto;       /* ❌ remove viewport lock */
        overflow: visible;  /* ✅ allow content flow */
        flex-direction: row;
    }

    .setup-panel{
        overflow: visible;  /* ❌ remove inner scroll */
        max-height: none;
    }

    .board-card{
        max-height: none;   /* prevent cutting */
    }

    .step-footer{
        position: relative; /* normal flow */
    }
}
@media (max-width: 1440px) and (min-width: 768px){

    /* 🔥 LIMIT CARD HEIGHT */
    .card{
        height: calc(100vh - 100px);   /* adjust based on header */
        overflow: hidden;              /* prevent outer overflow */
        display: flex;
        flex-direction: column;
    }

    /* 🔥 MAKE CARD BODY SCROLL */
    .card-body{
        flex: 1;
        overflow-y: auto;              /* ✅ FULL CARD SCROLL */
        min-height: 0;                 /* IMPORTANT for flex scroll */
    }

    /* 🔥 KEEP INNER LAYOUT NORMAL */
    .step2-wrapper{
        height: auto;
        overflow: visible;
    }

    .setup-panel{
        overflow: visible;
    }
}

/* 🟡 BONUS */
.cell-icon.bonus{
    background:#facc15;
    animation: glowYellow 1.5s infinite;
}

/* 🔵 AUDIT */
.cell-icon.audit{
    background:#3b82f6;
    animation: glowBlue 1.5s infinite;
}

/* 🟣 WILDCARD */
.cell-icon.wildcard{
    background:#a855f7;
    animation: glowPurple 1.5s infinite;
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

                            <?php if (in_array($i, $threat_from_cells)): ?>
                            <div class="cell-icon threat">
                             <i class="fa-solid fa-bomb fa-lg"></i>
                            </div>
                            <?php endif; ?>

                            <?php if (in_array($i, $opportunity_from_cells)): ?>
                            <div class="cell-icon opportunity">
                                 <i class="fa-solid fa-sack-dollar fa-lg"></i>
                            </div>
                            <?php endif; ?>
                             <?php if (in_array($i, $bonus_cells)): ?>
<div class="cell-icon bonus">
    <i class="fas fa-star"></i>
</div>
<?php endif; ?>

<?php if (in_array($i, $audit_cells)): ?>
<div class="cell-icon audit">
      <i class="fas fa-search"></i>
</div>
<?php endif; ?>

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
        <div class="setup-panel">

            <h3>Board Setup</h3>
            <p class="sub-text">
                Configure Threats and Opportunities 
            </p>

            <!-- Threat BLOCK -->
            <div class="accordion">

                <div class="setup-card" onclick="toggleAccordion('snakeBox')">
                    <div class="setup-left">
                        <div class="setup-icon snake-icon">
                            <i class="fa-solid fa-bomb fa-lg"></i>
                        </div>
                        <div>
                            <strong>Threats</strong>
                            <div class="count-text">
                                (<?php echo $snake_count; ?> Threats)
                            </div>
                        </div>
                    </div>
                    <i class="fas fa-chevron-down arrow"></i>
                </div>

                <!-- EXPAND CONTENT -->
                <div id="snakeBox" class="accordion-content">

                    <button class="add-btn" onclick="openThreatModal()">+ Add New Threat</button>
                    <div class="threat-scroll">
                        <?php 
                            mysqli_data_seek($threats_result, 0); // ✅ FIX
                            if(mysqli_num_rows($threats_result) > 0): 
                        ?>
                        <?php while($t = mysqli_fetch_assoc($threats_result)): ?>
            
                        <?php 
                        $strategy_count = $t['strategies'] ? count(explode('||', $t['strategies'])) : 0;
                        ?>

                        <div class="item-row">
                            <span>
                                <?php echo $t['threat_name']; ?> Threat from <?php echo $t['cell_from']; ?> to <?php echo $t['cell_to']; ?>
                            </span>

                            <div class="item-actions">
                    
                            <!-- STRATEGY -->
                            <a href="javascript:void(0)" 
                            onclick="openStrategyModal('threat', <?php echo $t['id']; ?>, '<?php echo htmlspecialchars($t['threat_name']); ?>')">
                            <?php echo $strategy_count > 0 ? $strategy_count . ' Strategies' : 'Add strategies'; ?>
                            </a>

                            <!-- EDIT -->
                            <i class="fas fa-pen"
                                onclick="editThreat(
                                <?php echo $t['id']; ?>,
                                '<?php echo htmlspecialchars($t['threat_name']); ?>',
                                '<?php echo htmlspecialchars($t['threat_description']); ?>',
                                <?php echo $t['cell_from']; ?>,
                                <?php echo $t['cell_to']; ?>
                                )">
                            </i>

                            <!-- DELETE -->
                            <i class="fas fa-trash"
                            onclick="deleteThreat(<?php echo $t['id']; ?>)">
                            </i>

                        </div>
                    </div>

                    <?php endwhile; ?>
                    <?php else: ?>
                    <div class="empty-state-modern">
                        <p>No threats added</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Opportunity BLOCK -->
        <div class="accordion">

            <div class="setup-card" onclick="toggleAccordion('ladderBox')">
                <div class="setup-left">
                    <div class="setup-icon ladder-icon">
                        <i class="fa-solid fa-sack-dollar fa-lg"></i>
                    </div>
                    <div>
                        <strong>Opportunity</strong>
                        <div class="count-text">
                            (<?php echo $ladder_count; ?> Opportunities)
                        </div>
                    </div>
                </div>
                <i class="fas fa-chevron-down arrow"></i>
            </div>

            <!-- EXPAND CONTENT -->
            <div id="ladderBox" class="accordion-content">

                <button class="add-btn green" onclick="openOpportunityModal()">+ Add New Opportunity</button>
                <div class="opportunity-scroll">
                    <?php 
                    mysqli_data_seek($opportunities_result, 0); // 🔥 ADD THIS
                    if(mysqli_num_rows($opportunities_result) > 0): 
                    ?>
                    <?php while($o = mysqli_fetch_assoc($opportunities_result)): ?>

                    <?php 
                    $strategy_count = $o['strategies'] ? count(explode('||', $o['strategies'])) : 0;
                    ?>

                    <div class="item-row">
                        <span>
                            <?php echo $o['opportunity_name']; ?>  Opportunity from <?php echo $o['cell_from']; ?> to <?php echo $o['cell_to']; ?>
                        </span>

                        <div class="item-actions">

                            <!-- STRATEGY -->
                            <a href="javascript:void(0)" 
                                onclick="openStrategyModal('opportunity', <?php echo $o['id']; ?>, '<?php echo htmlspecialchars($o['opportunity_name']); ?>')">
                                <?php echo $strategy_count > 0 ? $strategy_count . ' Strategies' : 'Add strategies'; ?>
                            </a>

                            <!-- EDIT -->
                            <i class="fas fa-pen"
                                onclick="editOpportunity(
                                    <?php echo $o['id']; ?>,
                                    '<?php echo htmlspecialchars($o['opportunity_name']); ?>',
                                    '<?php echo htmlspecialchars($o['opportunity_description']); ?>',
                                    <?php echo $o['cell_from']; ?>,
                                    <?php echo $o['cell_to']; ?>
                                )">
                            </i>

                            <!-- DELETE -->
                            <i class="fas fa-trash"
                            onclick="deleteOpportunity(<?php echo $o['id']; ?>)">
                            </i>

                        </div>
                    </div>

                    <?php endwhile; ?>
                    <?php else: ?>
                    <div class="empty-state-modern">
                        <p>No opportunities added</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

    </div>

</div>

<!-- FOOTER -->
<div class="step-footer">

    <!-- LEFT SIDE -->
    <div class="footer-left">

        <button class="prev-btn"
            onclick="window.location.href='create_game.php?id=<?php echo $game_id; ?>&step=1'">
            ← Previous
        </button>

        </div>
            <!-- RIGHT SIDE -->
            <div>
                <button class="next-btn"
                    onclick="window.location.href='create_game.php?id=<?php echo $game_id; ?>&step=3'">
                    Continue
                </button>
            </div>

            </div>

            <!-- THREAT MODAL -->
            <div id="threatModal" class="modal">
                <div class="modal-content">
                    <div class="modal-header">
                        <h3 id="threatModalTitle">Add Threat</h3>
                        <span class="modal-close" onclick="closeThreatModal()">&times;</span>
                    </div>

                    <div class="modal-body">
                        <form id="threatForm" novalidate>
                            <input type="hidden" id="threat_id" name="threat_id">
                            <input type="hidden" name="matrix_id" value="<?php echo $matrix_id; ?>">

                            <div class="form-group">
                                <label>Threat Name *</label>
                                <input type="text" id="threat_name" name="threat_name" >
                            </div>

                            <div class="form-group">
                                <label>Description *</label>
                                <textarea id="threat_description" name="threat_description"></textarea>
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label>FROM (Higher)</label>
                                    <input type="number" id="threat_cell_from" name="cell_from" min="1" max="<?php echo $total_cells; ?>">
                                </div>

                                <div class="form-group">
                                    <label>TO (Lower)</label>
                                    <input type="number" id="threat_cell_to" name="cell_to" min="1" max="<?php echo $total_cells; ?>">
                                </div>
                            </div>

                            <div class="form-actions">
                                <button type="button" onclick="closeThreatModal()">Cancel</button>
                            <button type="submit" id="threatSubmitBtn">Save</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- OPPORTUNITY MODAL -->
            <div id="opportunityModal" class="modal">
                <div class="modal-content">
                    <div class="modal-header">
                        <h3 id="opportunityModalTitle">Add Opportunity</h3>
                        <span class="modal-close" onclick="closeOpportunityModal()">&times;</span>
                    </div>

                    <div class="modal-body">
                        <form id="opportunityForm" novalidate>
                            <input type="hidden" id="opportunity_id" name="opportunity_id">
                            <input type="hidden" name="matrix_id" value="<?php echo $matrix_id; ?>">

                            <div class="form-group">
                                <label>Opportunity Name *</label>
                                <input type="text" id="opportunity_name" name="opportunity_name">
                            </div>

                            <div class="form-group">
                                <label>Description *</label>
                                <textarea id="opportunity_description" name="opportunity_description"></textarea>
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label>FROM (Lower)</label>
                                    <input type="number" id="opp_cell_from" name="cell_from">
                                </div>

                                <div class="form-group">
                                    <label>TO (Higher)</label>
                                    <input type="number" id="opp_cell_to" name="cell_to">
                                </div>
                            </div>

                            <div class="form-actions">
                                <button type="button" onclick="closeOpportunityModal()">Cancel</button>
                                <button type="submit" id="opportunitySubmitBtn">Save</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <!-- STRATEGY MODAL -->
            <div id="strategyModal" class="modal">
                <div class="modal-content" style="width:750px;">
                    
                    <div class="modal-header">
                        <h3 id="strategyModalTitle">Manage Strategies</h3>
                        <span class="modal-close" onclick="closeStrategyModal()">&times;</span>
                    </div>

                    <div class="modal-body">

                        <input type="hidden" id="strategy_risk_id">
                        <input type="hidden" id="strategy_risk_type">
                        <input type="hidden" id="strategy_matrix_id" value="<?php echo $matrix_id; ?>">

                        <!-- LOADED STRATEGIES -->
                        <div id="strategyList" class="strategy-list"></div>

                        <!-- ADD NEW -->
                        <div id="addStrategyWrapper" class="add-strategy-wrapper">
                            <div class="strategy-section">
                                <h4>Add New Strategy</h4>

                                <form id="strategyForm">
                                    <div class="form-group">
                                        <input type="text" name="strategy_name" placeholder="Strategy name">
                                    </div>

                                    <div class="form-group">
                                        <textarea name="description" placeholder="Description"></textarea>
                                    </div>

                                    <div class="form-group">
                                        <input type="number"  min="1" name="response_points" placeholder="Risk Capital">
                                    </div>

                                    <button type="submit" class="next-btn">Add Strategy</button>
                                </form>
                            </div>
                        </div>

                        <!-- MAP EXISTING -->
                    <div id="mapExistingWrapper" style="margin-top:20px;">
                <h4>Existing Strategies</h4>

                <!-- 🔥 ADD THIS CONTAINER -->
                <div id="mapExistingSection" class="map-existing"></div>

            </div>

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
  let lastAddedStrategyId = null;
    function openThreatModal(){

    document.getElementById('threatModal').style.display = 'flex';

    document.getElementById('threatForm').reset();

    document.getElementById('threatModalTitle').innerText = 'Add Threat';
    document.getElementById('threatSubmitBtn').innerText = 'Save';

    document.getElementById('threat_id').value = '';
    // ✅ AUTO FOCUS FIRST INPUT
    setTimeout(() => {
        document.getElementById('threat_name')?.focus();
    }, 100);
}
    function closeThreatModal(){
        document.getElementById('threatModal').style.display = 'none';
    }

    document.getElementById('threatForm').addEventListener('submit', function(e){
        e.preventDefault();

        let valid = true;

        const name = document.getElementById('threat_name');
        const desc = document.getElementById('threat_description');
        const fromInput = document.getElementById('threat_cell_from');
        const toInput = document.getElementById('threat_cell_to');

        const from = parseInt(fromInput.value) || 0;
        const to   = parseInt(toInput.value) || 0;

        const maxCell = <?php echo $total_cells; ?>;

        // CLEAR OLD ERRORS
        [name, desc, fromInput, toInput].forEach(clearError);

        // ✅ NAME
     // ✅ NAME VALIDATION (3–10 chars)
if(name.value.trim() === ''){
    setFieldError(name, 'Please enter a threat name.');
    valid = false;
} else if(name.value.trim().length < 3){
    setFieldError(name, 'Minimum 3 characters required.');
    valid = false;
} else if(name.value.trim().length > 100){
    setFieldError(name, 'Maximum 100 characters allowed.');
    valid = false;
}

// ✅ DESCRIPTION VALIDATION (5–15 chars)
if(desc.value.trim() === ''){
    setFieldError(desc, 'Please enter a description.');
    valid = false;
} else if(desc.value.trim().length < 3){
    setFieldError(desc, 'Minimum 3 characters required.');
    valid = false;
} else if(desc.value.trim().length > 500){
    setFieldError(desc, 'Maximum 500 characters allowed.');
    valid = false;
}

        // ✅ FROM VALIDATION
        if(!fromInput.value){
            setFieldError(fromInput, 'Please enter the starting cell.');
            valid = false;
        } else if(from <= 0){
            setFieldError(fromInput, 'Cell number must be greater than 0.');
            valid = false;
        } else if(from > maxCell){
            setFieldError(fromInput, 'Cell cannot exceed ' + maxCell + '.');
            valid = false;
        }

        // ✅ TO VALIDATION
        if(!toInput.value){
            setFieldError(toInput, 'Please enter the ending cell.');
            valid = false;
        } else if(to <= 0){
            setFieldError(toInput, 'Cell number must be greater than 0.');
            valid = false;
        } else if(to > maxCell){
            setFieldError(toInput, 'Cell cannot exceed ' + maxCell + '.');
            valid = false;
        }

        // ✅ LOGIC VALIDATION
        if(from && to){
            if(from <= to){
                setFieldError(fromInput, 'Starting cell must be higher than ending cell.');
                setFieldError(toInput, 'Ending cell must be lower than starting cell.');
                valid = false;
            }

            if((from - to) < 6){
                setFieldError(toInput, 'The gap between cells must be at least 6.');
                valid = false;
            }
        }

        // ❌ STOP IF INVALID
        if(!valid){
            document.querySelector('.error')?.focus();
            return;
        }

        // ✅ SUBMIT
        const formData = new FormData(this);
const threatId = document.getElementById('threat_id').value;

const url = threatId 
    ? 'ajax/edit_threat.php' 
    : 'ajax/add_threat.php';

        fetch(url, {
    method: 'POST',
    body: formData
})
        .then(res => res.json()) // ✅ IMPORTANT
        .then(response => {
            if(response.success){

                closeThreatModal();

                setTimeout(() => {
                    Swal.fire({
                        icon:'success',
                        title: response.message,
                        confirmButtonColor:'#4f6df5'
                    }).then(() => {
                        // ✅ ONLY AFTER OK CLICK
                        location.reload();
                    });
                }, 300);

            } else {
                Swal.fire({
                    icon:'error',
                    title: response.message,
                });     
            }

        }).catch(err => {
            Swal.fire({
                icon:'error',
                title: 'Server error'
            });
            console.error(err);
        });
    });
function deleteThreat(id){

    Swal.fire({
        title: 'Delete Threat?',
        text: "This action cannot be undone.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        confirmButtonText: 'Yes, delete it!'
    }).then((result) => {

        if(result.isConfirmed){

            const formData = new FormData();
            formData.append('threat_id', id);

            fetch('ajax/delete_threat.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(response => {

                if(response.success){
                   Swal.fire({
    icon:'success',
    title: response.message,
    confirmButtonColor:'#4f6df5'
}).then(() => {
    location.reload();
});

                } else {

                    Swal.fire({
                        icon: 'error',
                        title: response.message
                    });
                }

            }).catch(() => {
                Swal.fire({
                    icon: 'error',
                    title: 'Server error'
                });
            });
        }
    });
}
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
document.querySelectorAll('#threatForm input, #threatForm textarea')
.forEach(input => {

    input.addEventListener('input', () => {

        const value = input.value.trim();

        if(input.id === 'threat_name'){

            if(value.length >= 3 && value.length <= 10){
                clearError(input);
            }

        }

        if(input.id === 'threat_description'){

            if(value.length >= 5 && value.length <= 15){
                clearError(input);
            }

        }

    });

});
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

function editThreat(id, name, description, from, to){

    document.getElementById('threatModal').style.display = 'flex';

    document.getElementById('threatModalTitle').innerText = 'Edit Threat';
    document.getElementById('threatSubmitBtn').innerText = 'Update';

    document.getElementById('threat_id').value = id;
    document.getElementById('threat_name').value = name;
    document.getElementById('threat_description').value = description;
    document.getElementById('threat_cell_from').value = from;
    document.getElementById('threat_cell_to').value = to;
    setTimeout(() => {
        document.getElementById('threat_name')?.focus();
    }, 100);
}

function openOpportunityModal(){
    document.getElementById('opportunityModal').style.display = 'flex';

    document.getElementById('opportunityForm').reset();
    document.getElementById('opportunityModalTitle').innerText = 'Add Opportunity';
    document.getElementById('opportunitySubmitBtn').innerText = 'Save';

    document.getElementById('opportunity_id').value = '';
      // ✅ AUTO FOCUS
    setTimeout(() => {
        document.getElementById('opportunity_name')?.focus();
    }, 100);
}

function closeOpportunityModal(){
    document.getElementById('opportunityModal').style.display = 'none';
}
document.getElementById('opportunityForm').addEventListener('submit', function(e){
    e.preventDefault();

    let valid = true;

    const name = document.getElementById('opportunity_name');
    const desc = document.getElementById('opportunity_description');
    const fromInput = document.getElementById('opp_cell_from');
    const toInput = document.getElementById('opp_cell_to');

    const from = parseInt(fromInput.value) || 0;
    const to   = parseInt(toInput.value) || 0;

    const maxCell = <?php echo $total_cells; ?>;

    [name, desc, fromInput, toInput].forEach(clearError);

    // NAME (3–10)
    if(name.value.trim().length < 3){
        setFieldError(name, 'Minimum 3 characters required');
        valid = false;
    } else if(name.value.trim().length > 100){
        setFieldError(name, 'Maximum 100 characters allowed');
        valid = false;
    }

    // DESC (5–15)
    if(desc.value.trim().length < 5){
        setFieldError(desc, 'Minimum 5 characters required');
        valid = false;
    } else if(desc.value.trim().length > 500){
        setFieldError(desc, 'Maximum 500 characters allowed');
        valid = false;
    }

    // FROM
    if(!fromInput.value){
        setFieldError(fromInput, 'Enter FROM cell');
        valid = false;
    }

    // TO
    if(!toInput.value){
        setFieldError(toInput, 'Enter TO cell');
        valid = false;
    }

    // LOGIC (ladder)
    if(from && to){
        if(to <= from){
            setFieldError(toInput, 'TO must be greater than FROM');
            valid = false;
        }

        if((to - from) < 6){
            setFieldError(toInput, 'Minimum gap is 6');
            valid = false;
        }
    }

    if(!valid){
        document.querySelector('.error')?.focus();
        return;
    }

    const formData = new FormData(this);
    const id = document.getElementById('opportunity_id').value;

    const url = id 
        ? 'ajax/edit_opportunity.php' 
        : 'ajax/add_opportunity.php';

    fetch(url, {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(response => {

        if(response.success){

            closeOpportunityModal();

            Swal.fire({
    icon:'success',
    title: response.message,
    confirmButtonColor:'#4f6df5'
}).then(() => {
    location.reload();
});

        } else {
             Swal.fire({
                icon: 'error',
                title: response.message
            });
        }

    }).catch(() => {
         Swal.fire({
            icon: 'error',
            title: 'Server error'
        });
    });

});
function editOpportunity(id, name, description, from, to){

    openOpportunityModal();

    document.getElementById('opportunityModalTitle').innerText = 'Edit Opportunity';
    document.getElementById('opportunitySubmitBtn').innerText = 'Update';

    document.getElementById('opportunity_id').value = id;
    document.getElementById('opportunity_name').value = name;
    document.getElementById('opportunity_description').value = description;
    document.getElementById('opp_cell_from').value = from;
    document.getElementById('opp_cell_to').value = to;
}
function deleteOpportunity(id){

    Swal.fire({
        title: 'Delete Opportunity?',
        text: "This cannot be undone.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        confirmButtonText: 'Yes, delete it!'
    }).then((result) => {

        if(result.isConfirmed){

            const formData = new FormData();
            formData.append('opportunity_id', id);

            fetch('ajax/delete_opportunity.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(response => {

                if(response.success){
                   Swal.fire({
    icon:'success',
    title: response.message,
    confirmButtonColor:'#4f6df5'
}).then(() => {
    location.reload();
});
                } else {
                     Swal.fire({
                        icon: 'error',
                        title: response.message
                    });
                }

            });
        }
    });
}
document.querySelectorAll('#opportunityForm input, #opportunityForm textarea')
.forEach(input => {

    input.addEventListener('input', () => {

        const value = input.value.trim();

        // NAME
        if(input.id === 'opportunity_name'){
            if(value.length >= 3 && value.length <= 25){
                clearError(input);
            }
        }

        // DESCRIPTION
        if(input.id === 'opportunity_description'){
            if(value.length >= 5 && value.length <= 50){
                clearError(input);
            }
        }

        // FROM / TO numbers
        if(input.id === 'opp_cell_from' || input.id === 'opp_cell_to'){
            if(value !== '' && parseInt(value) > 0){
                clearError(input);
            }
        }

    });

});
</script>

<script>
const threatsData = <?php
    $temp = [];
    if ($threats_result && $snake_count > 0) {
        mysqli_data_seek($threats_result, 0);
        while ($t = mysqli_fetch_assoc($threats_result)) {
            $temp[] = [
                'from' => (int)$t['cell_from'],
                'to'   => (int)$t['cell_to']
            ];
        }
    }
    echo json_encode($temp);
?>;

const opportunitiesData = <?php
    $temp = [];
    if ($opportunities_result && $ladder_count > 0) {
        mysqli_data_seek($opportunities_result, 0);
        while ($o = mysqli_fetch_assoc($opportunities_result)) {
            $temp[] = [
                'from' => (int)$o['cell_from'],
                'to'   => (int)$o['cell_to']
            ];
        }
    }
    echo json_encode($temp);
?>;

function drawBoardArrows(){

    const svg = document.getElementById('boardArrowsSvg');
    const board = document.querySelector('.board-preview');

    if(!svg || !board) return;

    svg.querySelectorAll(".arrow-path").forEach(el => el.remove());

    const svgRect = svg.getBoundingClientRect();

    function getCenter(cellNumber){
        const cell = board.querySelector(`[data-cell="${cellNumber}"]`);
        if(!cell) return null;

        const rect = cell.getBoundingClientRect();

        return {
            x: rect.left + rect.width/2 - svgRect.left,
            y: rect.top + rect.height/2 - svgRect.top
        };
    }

    function createArrow(from, to, type){

        const start = getCenter(from);
        const end = getCenter(to);

        if(!start || !end) return;

        const path = document.createElementNS('http://www.w3.org/2000/svg','path');

        path.setAttribute("d",`M ${start.x} ${start.y} L ${end.x} ${end.y}`);
        path.setAttribute("class","arrow-path " + type);

        path.setAttribute("marker-end",
            type === "ladder"
            ? "url(#arrowhead-ladder)"
            : "url(#arrowhead-snake)"
        );

        path.dataset.from = from;
        path.dataset.to = to;
        path.dataset.type = type;

        svg.appendChild(path);
    }

    threatsData.forEach(t => createArrow(t.from, t.to, "snake"));
    opportunitiesData.forEach(o => createArrow(o.from, o.to, "ladder"));
}

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

/* INIT */
window.addEventListener("load", () => {
    setTimeout(drawBoardArrows, 300);
});

window.addEventListener("resize", () => {
    setTimeout(drawBoardArrows, 200);
});
let modalOpenedAt = null;

function openStrategyModal(type, risk_id, name){

    const now = new Date();
    modalOpenedAt = now.toISOString().slice(0,19).replace('T',' ');

    document.getElementById('strategyModal').style.display = 'flex';

    document.getElementById('strategy_risk_id').value = risk_id;
    document.getElementById('strategy_risk_type').value = type;

    document.getElementById('strategyModalTitle').innerText =
        'Strategies for ' + name;

    loadStrategies();

    // ✅ AUTO FOCUS FIRST INPUT
    setTimeout(() => {
        document.querySelector('#strategyForm input[name="strategy_name"]')?.focus();
    }, 200);
}
function closeStrategyModal(){
    document.getElementById('strategyModal').style.display = 'none';
    location.reload();
}
function loadStrategies(){

    const risk_id   = document.getElementById('strategy_risk_id').value;
    const risk_type = document.getElementById('strategy_risk_type').value;
    const matrix_id = document.getElementById('strategy_matrix_id').value;

    const strategyList = document.getElementById('strategyList');
    const mapWrapper   = document.getElementById('mapExistingWrapper');
    const mapContainer = document.querySelector('#strategyModal .map-existing');
    const addWrapper   = document.getElementById('addStrategyWrapper');

    fetch(`ajax/get_strategies.php?risk_id=${risk_id}&risk_type=${risk_type}&matrix_id=${matrix_id}&t=${Date.now()}`)
    .then(res => res.json())
    .then(res => {

        const strategies   = res.data?.strategies || [];
        const mapping_list = res.data?.mapping_list || [];

        let html = '';

        /* ======================
           CURRENT STRATEGIES
        ====================== */

        if(strategies.length === 0){

            // ❌ Hide list completely
            if(strategyList){
                strategyList.style.display = 'none';
                strategyList.innerHTML = '';
            }

            // 🔥 REMOVE GAP ABOVE FORM
            if(addWrapper){
                addWrapper.style.marginTop = "0px";
                addWrapper.style.paddingTop = "0px";
                addWrapper.style.borderTop = "none";
            }

        } else {

            // ✅ SHOW LIST
            strategies.forEach(s => {

                const isNew = s.id == lastAddedStrategyId;

                html += `
                <div class="strategy-card ${isNew ? 'new-strategy' : ''}">
                    <div class="strategy-info">
                        <div class="strategy-name">${s.strategy_name}</div>
                        <div class="strategy-points">(+${s.response_points}) RC</div>
                    </div>

                    <div class="strategy-delete"
                        onclick="removeStrategy(${s.id})">
                        <i class="fas fa-trash"></i>
                    </div>
                </div>`;
            });

            if(strategyList){
                strategyList.innerHTML = html;
                strategyList.style.display = 'grid';
                strategyList.scrollTop = 0;
            }

            // 🔥 ADD SPACING ONLY WHEN DATA EXISTS
            if(addWrapper){
                addWrapper.style.marginTop = "12px";
                addWrapper.style.paddingTop = "10px";
                addWrapper.style.borderTop = "1px solid #eee";
            }
        }

        /* ======================
           EXISTING STRATEGIES (MAP)
        ====================== */

        if(mapContainer){
            mapContainer.innerHTML = '';
        }

        if(mapping_list.length === 0){

            // ❌ Hide heading + section completely
            if(mapWrapper){
                mapWrapper.style.display = 'none';
            }

        } else {

            let mapHtml = '';

            mapping_list.forEach(s => {
                mapHtml += `
                <label class="map-item">
                    <input type="checkbox" class="map-strategy" value="${s.id}"
                        onchange="autoMapStrategy(this)">
                    ${s.strategy_name}
                </label>`;
            });

            if(mapContainer){
                mapContainer.innerHTML = mapHtml;
            }

            // ✅ Show section
            if(mapWrapper){
                mapWrapper.style.display = 'block';
            }
        }

        lastAddedStrategyId = null;

    })
    .catch(err => {

        console.error('Error loading strategies:', err);

        if(strategyList){
            strategyList.innerHTML =
                '<div class="empty-state-modern">Failed to load strategies</div>';
            strategyList.style.display = 'flex';
        }

        if(mapWrapper){
            mapWrapper.style.display = 'none';
        }
    });
}
document.getElementById('strategyForm').addEventListener('submit', function(e){

    e.preventDefault();

    let valid = true;

    const name   = this.querySelector('[name="strategy_name"]');
    const desc   = this.querySelector('[name="description"]');
    const points = this.querySelector('[name="response_points"]');

    // 🔥 CLEAR OLD ERRORS
    [name, desc, points].forEach(clearError);

    /* ======================
       NAME VALIDATION
    ====================== */
    if(name.value.trim() === ''){
        setFieldError(name, 'Please enter strategy name');
        valid = false;
    } 
    else if(name.value.trim().length < 3){
        setFieldError(name, 'Minimum 3 characters required');
        valid = false;
    }
    else if(name.value.trim().length > 100){
        setFieldError(name, 'Maximum 100 characters allowed');
        valid = false;
    }

    /* ======================
       DESCRIPTION
    ====================== */
    if(desc.value.trim() === ''){
        setFieldError(desc, 'Please enter description');
        valid = false;
    }
    else if(desc.value.trim().length < 5){
        setFieldError(desc, 'Minimum 5 characters required');
        valid = false;
    }
    else if(desc.value.trim().length > 500){
        setFieldError(desc, 'Maximum 500 characters allowed');
        valid = false;
    }

    /* ======================
       POINTS
    ====================== */
    const pts = parseInt(points.value);

    if(!points.value){
        setFieldError(points, 'Please enter risk capital');
        valid = false;
    }
    else if(isNaN(pts) || pts <= 0){
        setFieldError(points, 'Risk capital must be greater than 0');
        valid = false;
    }

    // ❌ STOP IF INVALID (NO SWAL)
    if(!valid){
        document.querySelector('#strategyForm .error')?.focus();
        return;
    }

    /* ======================
       SUBMIT
    ====================== */

    const formData = new FormData(this);

    formData.append('risk_id', document.getElementById('strategy_risk_id').value);
    formData.append('risk_type', document.getElementById('strategy_risk_type').value);
    formData.append('matrix_id', document.getElementById('strategy_matrix_id').value);

    fetch('ajax/add_strategy.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(res => {

        if(res.success){

            this.reset();

            lastAddedStrategyId = res.data?.id || res.data?.strategy_id;

            loadStrategies(); // reload list

        } else {
            // ✅ only backend error → show swal
            Swal.fire({
                icon:'error',
                title: res.message
            });
        }
    });

});
document.querySelectorAll('#strategyForm input, #strategyForm textarea')
.forEach(input => {

    input.addEventListener('input', () => {

        const value = input.value.trim();

        if(input.name === 'strategy_name'){
            if(value.length >= 3){
                clearError(input);
            }
        }

        if(input.name === 'description'){
            if(value.length >= 5){
                clearError(input);
            }
        }

        if(input.name === 'response_points'){
            if(value !== '' && parseInt(value) > 0){
                clearError(input);
            }
        }

    });

});
function mapExistingStrategies(){

    const selected = [];

    document.querySelectorAll('.map-strategy:checked').forEach(cb => {
        selected.push(cb.value);
    });

    if(selected.length === 0){
        alert('Select at least one strategy');
        return;
    }

    const matrix_id = document.getElementById('strategy_matrix_id').value; // ✅ FIX

    fetch('ajax/map_existing_strategies.php', {
        method: 'POST',
        body: JSON.stringify({
            risk_id: document.getElementById('strategy_risk_id').value,
            risk_type: document.getElementById('strategy_risk_type').value,
            matrix_id: matrix_id, // ✅ FIX
            strategy_ids: selected
        })
    })
    .then(res => res.json())
    .then(res => {

        Swal.fire({
    icon:'success',
    title: res.message,
    confirmButtonColor:'#4f6df5'
});
        loadStrategies();

    });
}
function removeStrategy(strategy_id){

    const formData = new FormData();

    formData.append('strategy_id', strategy_id);
    formData.append('risk_id', document.getElementById('strategy_risk_id').value);
    formData.append('risk_type', document.getElementById('strategy_risk_type').value);
    formData.append('matrix_id', document.getElementById('strategy_matrix_id').value); // ✅ FIX

    fetch('ajax/remove_strategy.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(res => {

        Swal.fire({
    icon:'success',
    title: res.message,
    confirmButtonColor:'#4f6df5'
});
        loadStrategies();

    });
}

function autoMapStrategy(checkbox){

    if(!checkbox.checked) return;

    checkbox.disabled = true; // 🔥 prevent double click

    const strategy_id = checkbox.value;

    fetch('ajax/map_existing_strategies.php', {
        method: 'POST',
        body: JSON.stringify({
            risk_id: document.getElementById('strategy_risk_id').value,
            risk_type: document.getElementById('strategy_risk_type').value,
            matrix_id: document.getElementById('strategy_matrix_id').value,
            strategy_ids: [strategy_id]
        })
    })
    .then(res => res.json())
    .then(res => {

        Swal.fire({
            icon: 'success',
            title: 'Strategy Added',
            text: res.message,
            confirmButtonColor: '#4f6df5'
        });

        loadStrategies();

    })
    .catch(() => {
        checkbox.disabled = false;

        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Failed to map strategy'
        });
    });
}
</script>
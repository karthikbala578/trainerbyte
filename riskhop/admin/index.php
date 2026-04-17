<?php
require_once '../config.php';
require_once '../functions.php';

if (!is_admin_logged_in()) {
    redirect(BASE_URL . 'index.php');
}

/* PAGINATION SETTINGS */
$limit = 6;

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$page = max($page, 1);

/* TOTAL RECORDS */
$total_query = mysqli_query($conn,"SELECT COUNT(*) as total FROM mg6_riskhop_matrix");
$total_row = mysqli_fetch_assoc($total_query);
$total_records = (int)$total_row['total'];


/*
|--------------------------------------------------------------------------
| PAGE 1 shows: Add Card + 5 Games
| OTHER PAGES show: 6 Games
|--------------------------------------------------------------------------
*/

if($page == 1){

    $games_per_page = $limit - 1; // 5 games
    $offset = 0;

}else{

    $games_per_page = $limit; // 6 games
    $offset = ($limit - 1) + (($page - 2) * $limit);

}


/*
|--------------------------------------------------------------------------
| TOTAL PAGE CALCULATION
|--------------------------------------------------------------------------
*/

if($total_records <= ($limit - 1)){

    $total_pages = 1;

}else{

    $remaining_records = $total_records - ($limit - 1);
    $total_pages = 1 + ceil($remaining_records / $limit);

}


/*
|--------------------------------------------------------------------------
| SAFETY (prevent invalid page numbers)
|--------------------------------------------------------------------------
*/

if($page > $total_pages){
    $page = $total_pages;
}


/*
|--------------------------------------------------------------------------
| FETCH PAGINATED RECORDS
|--------------------------------------------------------------------------
*/

$query = "SELECT *
          FROM mg6_riskhop_matrix
          ORDER BY created_date DESC
          LIMIT $games_per_page OFFSET $offset";

$games_result = mysqli_query($conn,$query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RiskHOP - Dashboard</title>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<style>
html, body{
    min-height:100vh;
    margin:0;
    overflow-x:hidden;
}
body{
    font-family: "Inter", -apple-system, BlinkMacSystemFont, 
                 "Segoe UI", Roboto, Arial, sans-serif;

    color:#111827;
    line-height:1.5;

    -webkit-font-smoothing: antialiased;
    -moz-osx-font-smoothing: grayscale;
}
.admin-wrapper{
    display:flex;
    min-height:100vh;
    width:100%;
}
.admin-content{
    flex:1;
    padding:30px 40px;
    background:#f5f6fa;
    min-height:100vh;
    display:flex;
    flex-direction:column;
    width:100%;
    box-sizing:border-box;
}
.content-header h1{
    font-size:26px;
    font-weight:600;
    color:#111827;
}
.games-grid{
flex:1;
overflow-y:auto;

display:grid;
grid-template-columns:repeat(2,minmax(0,1fr));
gap:30px;
}
.game-card{
    background:#fff;
    border-radius:14px;
    padding:26px;
    box-shadow:0 1px 4px rgba(0,0,0,0.06);
    transition:all .25s ease;

    display:flex;
    flex-direction:column;
    gap:14px;
}
.game-card:hover{
    transform:translateY(-3px);
    box-shadow:0 6px 20px rgba(0,0,0,0.08);
}
.game-card-header{
    display:flex;
    justify-content:space-between;
    align-items:center;
    margin-bottom:10px;
}

.game-card-header h3{
    font-size:16.5px;
    font-weight:600;
    color:#111827;
}
.badge{
    padding:4px 10px;
    border-radius:20px;
    font-size:12px;
    font-weight:600;
}

.badge-published{
    background:#e6f9f0;
    color:#1a9c64;
}

.badge-draft{
    background:#fff5d6;
    color:#c99500;
}
.game-card-body p{
    font-size:14px;
    line-height:1.6;
    color:#6b7280;
    margin-bottom:18px;

   
   white-space: normal;
    line-height: 1.6;
}
.game-card-body p:hover{
    text-decoration:underline;
}
.game-card-body{
    flex:1;
    display:flex;
    flex-direction:column;
}
.game-info{
    display:grid;
    grid-template-columns:repeat(4,1fr);
    gap:20px;
    border-top:1px solid #ececec;
    padding-top:18px;
    margin-top:10px;
}

.info-item{
    display:flex;
    flex-direction:column;
}

.label{
     font-size:11px;
    letter-spacing:0.5px;
    color:#9ca3af;
    text-transform:uppercase;
}

.value{
    font-size:14px;
    font-weight:600;
    color:#111827;
}
.game-card-footer{
    display:flex;
    gap:10px;
    margin-top:auto;
    
}
.btn{
    padding:7px 16px;
    border-radius:8px;
     font-size:13px;
    font-weight:500;
    border:none;
    cursor:pointer;
}

.btn-success{
    background:#4f6cf7;
    color:#fff;
    text-decoration:none;
}

.btn-secondary{
    background:#eef0f4;
    color:#333;
    text-decoration:none;
}

.btn-danger{
    background:#eef0f4;
    color:#666;
    text-decoration:none;
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

.admin-content{
margin-left:80px;
flex:1;
}
.icon-delete{
background:transparent;
border:none;
cursor:pointer;

font-size:16px;
color:#9ca3af;

display:flex;
align-items:center;
justify-content:center;

width:34px;
height:34px;
border-radius:8px;

transition:all .2s ease;
}

.icon-delete:hover{
color:#ef4444;
background:#fff1f1;
transform:scale(1.1);
}
.icon-edit{
background:transparent;
border:none;
cursor:pointer;

font-size:16px;
color:#9ca3af;

display:flex;
align-items:center;
justify-content:center;

width:34px;
height:34px;
border-radius:8px;

text-decoration:none;

transition:all .2s ease;
}

.icon-edit:hover{
color:#22c55e;
background:#f0fdf4;
transform:scale(1.1);
}
.tooltip{
position:relative;
display:flex;
align-items:center;
justify-content:center;
}

.tooltip-text{
position:absolute;
bottom:120%;
left:50%;
transform:translateX(-50%);

background:#eef0f4;
    color:#333;

font-size:12px;
padding:5px 8px;
border-radius:6px;

white-space:nowrap;

opacity:0;
visibility:hidden;

transition:all .2s ease;
}

.tooltip:hover .tooltip-text{
opacity:1;
visibility:visible;
transform:translateX(-50%) translateY(-3px);
}
.game-desc{
    font-size:14px;
    color:#6b7280;
    line-height:1.6;
}

/* Proper list styling */
.game-desc ul{
    padding-left:18px;
    list-style-type: disc;
}

.game-desc ol{
    padding-left:18px;
    list-style-type: decimal;
}

.game-desc li{
    margin-bottom:6px;
}

/* Quill indent support */
.game-desc .ql-indent-1{
    margin-left:20px;
}
.game-desc .ql-indent-2{
    margin-left:40px;
}
.tooltip-desc{
    position: relative;
    cursor: pointer;
}

/* HIDDEN FULL DESCRIPTION */
.tooltip-desc-content{
    position: absolute;
    left: 0;
    top: 110%;

    width: 500px;              /* 🔥 increased width */
    max-width: 90vw;           /* 🔥 prevent overflow on small screens */

    max-height: 260px;
    overflow-y: auto;

    background: #fff;
    border-radius: 12px;
    padding: 16px;

    box-shadow: 0 12px 30px rgba(0,0,0,0.15);
    border: 1px solid #eee;

    font-size: 13px;
    line-height: 1.6;

    opacity: 0;
    visibility: hidden;
    transition: 0.2s ease;

    z-index: 999;
}

/* SHOW ON HOVER */
.tooltip-desc:hover .tooltip-desc-content{
    opacity: 1;
    visibility: visible;
    transform: translateY(5px);
}

/* SCROLLBAR */
.tooltip-desc-content::-webkit-scrollbar{
    width:6px;
}
.tooltip-desc-content::-webkit-scrollbar-thumb{
    background:#cbd5f5;
    border-radius:6px;
}
</style>
</head>
<body>
<div class="admin-wrapper">
 
    
    <div class="admin-sidebar">
        <!-- hamburger / sidebar space -->
    </div>
    <div class="admin-content">
        <div class="content-header">
            <h1>Game Library</h1>
        </div>
        
        <div class="games-grid">
            <!-- ADD NEW GAME CARD - UPDATED DESIGN -->
         <?php if($page == 1): ?>
<div class="game-card add-new-game-card">
    <a href="instruction.php?from=library" class="add-new-game-link">

        <div class="add-icon">+</div>

        <h3>Create New Game</h3>

        <p>Start building a new RiskHOP board with custom rules and settings.</p>

    </a>
</div>
<?php endif; ?>
            
            <!-- EXISTING GAMES -->
            <?php if (mysqli_num_rows($games_result) > 0): ?>
                <?php while ($game = mysqli_fetch_assoc($games_result)): ?>
                    <div class="game-card">
                        <div class="game-card-header">
                            <h3><?php echo htmlspecialchars($game['game_name']); ?></h3>
                            <span class="badge badge-<?php echo $game['status']; ?>">
                                <?php echo ucfirst($game['status']); ?>
                            </span>
                        </div>
                        
                        <div class="game-card-body">
                            <?php
$fullDesc = htmlspecialchars_decode($game['description']);

/* Strip only for preview (keep HTML for tooltip) */
$plainText = trim(preg_replace('/\s+/', ' ', strip_tags($fullDesc)));

/* Limit to 150 chars */
if (mb_strlen($plainText) > 150) {
    $shortText = mb_substr($plainText, 0, 150) . '...';
} else {
    $shortText = $plainText;
}
?>

<div class="game-desc tooltip-desc">

    <!-- SHORT TEXT -->
    <?php echo htmlspecialchars($shortText); ?>

    <!-- FULL HTML (ON HOVER) -->
    <div class="tooltip-desc-content">
        <?php echo $fullDesc; ?>
    </div>

</div>
                            
                            <div class="game-info">
                                <div class="info-item">
                                    <span class="label">Board</span>
                                    <span class="value"><?php echo $game['matrix_type']; ?></span>
                                </div>
                                <div class="info-item">
                                    <span class="label">Risk Capital</span>
                                    <span class="value"><?php echo $game['risk_capital']; ?></span>
                                </div>
                                <div class="info-item">
                                    <span class="label">Dice Limit</span>
                                    <span class="value"><?php echo $game['dice_limit']; ?></span>
                                </div>
                                <div class="info-item">
                                    <span class="label">Created</span>
                                    <span class="value"><?php echo date('d M Y', strtotime($game['created_date'])); ?></span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="game-card-footer">
                          <a href="create_game.php?id=<?php echo $game['id']; ?>" class="icon-edit tooltip">
    <i class="fa-solid fa-pen-to-square"></i>
    <span class="tooltip-text">Edit Game</span>
</a>
                            <?php if ($game['status'] === 'draft'): ?>
                                <a href="create_game.php?id=<?php echo $game['id']; ?>&step=4" class="btn btn-sm btn-success">Publish</a>
                            <?php endif; ?>
                           <button class="icon-delete tooltip" onclick="deleteGame(<?php echo $game['id']; ?>)">
    <i class="fa-solid fa-trash"></i>
    <span class="tooltip-text">Delete Game</span>
</button>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php endif; ?>
        </div>
       <?php if($total_pages > 1): ?>

<div class="pagination">

    <!-- Previous -->
    <?php if($page > 1): ?>
        <a class="page-nav" href="?page=<?php echo $page-1 ?>&limit=<?php echo $limit ?>">
            ‹ Previous
        </a>
    <?php else: ?>
        <span class="page-nav disabled">‹ Previous</span>
    <?php endif; ?>


    <?php
    $range = 2;

    $start = max(1, $page - $range);
    $end   = min($total_pages, $page + $range);

    /* FIRST PAGE */
    if($start > 1){
        echo '<a class="page-number" href="?page=1&limit='.$limit.'">1</a>';
        if($start > 2){
            echo '<span class="dots">...</span>';
        }
    }

    /* MIDDLE PAGES */
    for($i = $start; $i <= $end; $i++){
        if($i == $page){
            echo '<span class="page-number active">'.$i.'</span>';
        }else{
            echo '<a class="page-number" href="?page='.$i.'&limit='.$limit.'">'.$i.'</a>';
        }
    }

    /* LAST PAGE */
    if($end < $total_pages){
        if($end < $total_pages-1){
            echo '<span class="dots">...</span>';
        }
        echo '<a class="page-number" href="?page='.$total_pages.'&limit='.$limit.'">'.$total_pages.'</a>';
    }
    ?>


    <!-- Next -->
    <?php if($page < $total_pages): ?>
        <a class="page-nav" href="?page=<?php echo $page+1 ?>&limit=<?php echo $limit ?>">
            Next ›
        </a>
    <?php else: ?>
        <span class="page-nav disabled">Next ›</span>
    <?php endif; ?>

</div>

<?php endif; ?>

    </div>
   
</div>

<style>
/* ADD NEW GAME CARD - SPECIAL STYLING */
.add-new-game-card{
background:linear-gradient(135deg,#5b6df6,#4f6cf7);
color:#fff;
text-align:center;

display:flex;
flex-direction:column;
justify-content:center;

border:2px dashed rgba(255,255,255,0.4);
}
.add-new-game-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 10px 25px rgba(0,0,0,0.15);
}
.add-new-game-link{
display:flex;
flex-direction:column;
align-items:center;
justify-content:center;
gap:14px;

padding:30px;
width:100%;
height:100%;

color:#fff;
text-decoration:none;
}

.add-icon{
width:70px;
height:70px;
border-radius:50%;
background:rgba(255,255,255,0.25);
display:flex;
align-items:center;
justify-content:center;
font-size:40px;
font-weight:300;
transition:all .3s ease;
}

.add-new-game-card:hover .add-icon{
transform:scale(1.1) rotate(90deg);
background:rgba(255,255,255,0.35);
}

.add-new-game-link h3{
font-size:22px;
font-weight:600;
}

.add-new-game-link p{
font-size:14px;
opacity:.9;
max-width:240px;
line-height:1.4;
}

.game-card-footer {
    justify-content: flex-start;
}

.pagination-wrapper{
display:flex;
justify-content:flex-end;
align-items:center;
gap:10px;
margin-top:30px;
}
.pagination{
margin-top:20px;
display:flex;
justify-content:flex-end;
align-items:center;
gap:8px;
flex-shrink:0;
}
.page-number,
.page-nav{
padding:8px 14px;
border-radius:8px;
background:#f1f3f7;
color:#374151;
font-size:13px;
font-weight:500;
text-decoration:none;
transition:all .2s ease;
}

.page-number:hover,
.page-nav:hover{
background:#4f6cf7;
color:#fff;
}


.page-number.active{
background:#4f6cf7;
color:#fff;
cursor:default;
}

.page-nav.disabled{
opacity:.5;
pointer-events:none;
}

.dots{
padding:0 6px;
color:#999;
font-size:14px;
}
</style>

<script>
function deleteGame(gameId) {

Swal.fire({
    title: 'Delete Game?',
    text: "This action cannot be undone.",
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#ef4444',
    cancelButtonColor: '#9ca3af',
    confirmButtonText: 'Yes, delete it',
    cancelButtonText: 'Cancel',
    reverseButtons: true
}).then((result) => {

    if (result.isConfirmed) {

        fetch('ajax/delete_game.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'game_id=' + gameId
        })
        .then(response => response.json())
        .then(data => {

            if (data.success) {

                Swal.fire({
                    icon: 'success',
                    title: 'Deleted!',
                    text: 'The game has been deleted.',
                    timer: 1500,
                    showConfirmButton: false
                });

                setTimeout(() => {
                    location.reload();
                }, 1500);

            } else {

                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: data.message
                });

            }

        });

    }

});

}
</script>

</body>
</html>
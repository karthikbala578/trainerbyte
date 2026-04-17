<?php
/**
 * RiskHOP Game Library - Enhanced Interactive Design
 * Display all published games with modern UI
 */

require_once '../config.php';
require_once '../functions.php';
require_once 'game_engine.php';

// Get all published games
$games = get_published_games();

$current_session = get_current_session();

if (!$current_session) {
    $current_session = null;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RiskHOP - Game Library</title>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Styles -->
    <link rel="stylesheet" href="<?php echo ASSETS_URL; ?>css/common.css">
    <link rel="stylesheet" href="<?php echo ASSETS_URL; ?>css/game1.css">
    <link rel="stylesheet" href="<?php echo ASSETS_URL; ?>css/responsive.css">
    <link rel="stylesheet" href="<?php echo ASSETS_URL; ?>css/index.css">
    
    
    <style>
        
    </style>
</head>
<body>
    <!-- Floating Particles -->
    <div class="particles">
        <div class="particle" style="width: 10px; height: 10px; left: 10%; animation-duration: 15s; animation-delay: 0s;"></div>
        <div class="particle" style="width: 15px; height: 15px; left: 20%; animation-duration: 18s; animation-delay: 2s;"></div>
        <div class="particle" style="width: 8px; height: 8px; left: 30%; animation-duration: 20s; animation-delay: 4s;"></div>
        <div class="particle" style="width: 12px; height: 12px; left: 40%; animation-duration: 16s; animation-delay: 1s;"></div>
        <div class="particle" style="width: 10px; height: 10px; left: 50%; animation-duration: 19s; animation-delay: 3s;"></div>
        <div class="particle" style="width: 14px; height: 14px; left: 60%; animation-duration: 17s; animation-delay: 5s;"></div>
        <div class="particle" style="width: 9px; height: 9px; left: 70%; animation-duration: 21s; animation-delay: 2s;"></div>
        <div class="particle" style="width: 11px; height: 11px; left: 80%; animation-duration: 15s; animation-delay: 4s;"></div>
        <div class="particle" style="width: 13px; height: 13px; left: 90%; animation-duration: 18s; animation-delay: 1s;"></div>
    </div>

    <div class="game-library">
        <!-- Header -->
        <div class="game-library-header">
            <h1><i class="fas fa-gamepad"></i> RiskHOP Game Library</h1>
            <p>Choose a game to start learning risk management through interactive gameplay</p>
        </div>

        <!-- Games Grid -->
        <?php if (count($games) > 0): ?>
            <div class="games-grid">
                <?php foreach ($games as $game): ?>
                    <?php
                    // Get game summary
                    $summary = get_game_summary($game['id']);
                    ?>
                    <div class="game-card" data-game-id="<?php echo $game['id']; ?>">
                        <!-- Card Banner -->
                        <div class="game-card-banner">
                            <i class="fas fa-dice-d20 game-card-icon"></i>
                        </div>
                        
                        <!-- Card Content -->
                        <div class="game-card-content">
                            <div class="game-card-header">
                                <h3><?php echo htmlspecialchars($game['game_name']); ?></h3>
                                <div class="game-card-meta">
                                    <div class="meta-item">
                                        <i class="fas fa-th"></i>
                                        <span><?php echo $game['matrix_type']; ?></span>
                                    </div>
                                    <div class="meta-item">
                                        <i class="fas fa-dice"></i>
                                        <span><?php echo $game['dice_limit']; ?> Dice</span>
                                    </div>
                                    <div class="meta-item">
                                        <i class="fas fa-coins"></i>
                                        <span><?php echo $game['risk_capital']; ?> Capital</span>
                                    </div>
                                </div>
                            </div>

                            <div class="game-card-description">
                                <?php echo htmlspecialchars($game['description']); ?>
                            </div>

                            <!-- Stats Preview -->
                            <div class="game-stats-preview">
                                <div class="stat-preview-item">
                                    <span class="stat-preview-value threats"><?php echo $summary['threats']; ?></span>
                                    <span class="stat-preview-label"><i class="fas fa-arrow-down"></i> Threats</span>
                                </div>
                                <div class="stat-preview-item">
                                    <span class="stat-preview-value opportunities"><?php echo $summary['opportunities']; ?></span>
                                    <span class="stat-preview-label"><i class="fas fa-arrow-up"></i> Opportunities</span>
                                </div>
                                <div class="stat-preview-item">
                                    <span class="stat-preview-value wildcards"><?php echo $summary['wildcards']; ?></span>
                                    <span class="stat-preview-label"><i class="fas fa-question"></i> Wildcards</span>
                                </div>
                            </div>

                            <!-- Play Button -->
                            <button class="btn-play-game" onclick="startGame(<?php echo $game['id']; ?>, this)">
                                <i class="fas fa-play"></i> Play Game
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <!-- Empty State -->
            <div class="empty-state">
                <i class="fas fa-folder-open"></i>
                <h2>No Games Available</h2>
                <p>There are currently no published games. Please check back later.</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Continue Game Popup -->
    

    <script>
    const ASSETS_URL = "<?php echo ASSETS_URL; ?>";
</script>

<script src="<?php echo ASSETS_URL; ?>js/common.js"></script>

    <script>
        /**
         * Start new game
         */
      function startGame(gameId, el){

    const hasSession = <?php echo json_encode($current_session && $current_session['game_status'] == 'playing'); ?>;

    if(hasSession){
        Swal.fire({
            icon: "warning",
            title: "Game in Progress",
            text: "Finish or continue your current game first.",
        });
        return;
    }

    el.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Loading...';
    el.style.pointerEvents = 'none';

    setTimeout(() => {
        window.location.href = 'new_instruction.php?game_id=' + gameId;
    }, 500);
}
        /**
         * Continue existing game
         */
        function continueGame(gameId, el) {

    if(el){
        el.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Loading...';
        el.style.pointerEvents = 'none';
    }

    setTimeout(() => {
        window.location.href = 'board.php?game_id=' + gameId;
    }, 500);
}

        // Add hover effect to game cards
        document.querySelectorAll('.game-card').forEach(card => {
            card.addEventListener('click', function(e) {
                if (!e.target.classList.contains('btn-play-game') && !e.target.closest('.btn-play-game')) {
                    const gameId = this.dataset.gameId;
                    this.style.transform = 'scale(0.98)';
                    setTimeout(() => {
                        window.location.href = 'instruction.php?game_id=' + gameId;
                    }, 200);
                }
            });
        });

        // Add loading animation on page load
        window.addEventListener('load', () => {
            document.body.style.opacity = '0';
            document.body.style.transition = 'opacity 0.5s';
            setTimeout(() => {
                document.body.style.opacity = '1';
            }, 100);
        });

       let continueTriggered = false;

window.addEventListener("load", function(){

    const hasSession = <?php echo json_encode($current_session && isset($current_session['game_status']) && $current_session['game_status'] == 'playing'); ?>;

    if(hasSession){

        Swal.fire({
            icon: "info",
            title: "Continue Game?",
            text: "You have an unfinished game.",
            showCancelButton: true,
            confirmButtonText: "Continue",
            cancelButtonText: "Go to Library"
        }).then(result => {

            if(result.isConfirmed && !continueTriggered){

                continueTriggered = true;

                continueGame(<?php echo isset($current_session['matrix_id']) ? (int)$current_session['matrix_id'] : 0; ?>);
            }

        });
    }
});
    </script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</body>
</html>
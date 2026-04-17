<?php require_once 'includes/header.php'; ?>
<?php require_once 'includes/sidebar.php'; ?>

<div class="main-content">
    <div class="page-header">
        <h1>Game Library</h1>
        <a href="game/step1_general_info.php" class="btn-primary">+ Add New Game</a>
    </div>
    
    <div class="games-grid">
        <?php
        $games = getAllGames();
        if (count($games) > 0):
            foreach ($games as $game):
        ?>
            <div class="game-card">
                <div class="game-card-header">
                    <h3><?php echo htmlspecialchars($game['game_name']); ?></h3>
                    <div class="game-status <?php echo $game['status']; ?>">
                        <?php echo ucfirst($game['status']); ?>
                    </div>
                </div>
                
                <div class="game-card-body">
                    <p><?php echo htmlspecialchars($game['game_description']); ?></p>
                    
                    <div class="game-meta">
                        <div class="meta-item">
                            <span class="label">Board Size:</span>
                            <span class="value"><?php echo $game['board_size']; ?>×<?php echo $game['board_size']; ?></span>
                        </div>
                        <div class="meta-item">
                            <span class="label">Risk Capital:</span>
                            <span class="value"><?php echo $game['risk_capital']; ?></span>
                        </div>
                        <div class="meta-item">
                            <span class="label">Dice Limit:</span>
                            <span class="value"><?php echo $game['dice_limit']; ?></span>
                        </div>
                    </div>
                </div>
                
                <div class="game-card-footer">
                    <a href="game/step1_general_info.php?id=<?php echo $game['matrix_id']; ?>" class="btn-edit">Edit</a>
                    <a href="../game/play.php?game=<?php echo $game['matrix_id']; ?>" class="btn-play" target="_blank">Play</a>
                    <button onclick="deleteGame(<?php echo $game['matrix_id']; ?>)" class="btn-delete">Delete</button>
                </div>
            </div>
        <?php
            endforeach;
        else:
        ?>
            <div class="empty-state">
                <h2>No games created yet</h2>
                <p>Click "Add New Game" to create your first game!</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
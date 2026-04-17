<?php
/**
 * RiskHOP Game Stats Sidebar
 * Displays real-time game statistics
 */

if (!defined('BASE_URL')) {
    die('Direct access not permitted');
}

/**
 * Render complete stats sidebar
 * 
 * @param array $session - Current game session
 * @param array $game_data - Complete game data
 * @param array $player_investments - Player's current investments
 * @return void
 */
function render_stats_sidebar($session, $game_data, $player_investments) {
    ?>
    <div class="game-stats-sidebar">
        
        <!-- Main Stats Card -->
        <?php render_main_stats($session, $game_data, $player_investments); ?>
        
        <!-- Legend Card -->
        <?php render_board_legend(); ?>
        
        <!-- Dice Section -->
        <?php render_dice_section($session, $game_data); ?>
        
        <!-- Progress Card -->
        <?php render_progress_card($session, $game_data); ?>
        
    </div>
    <?php
}

/**
 * Render main stats card
 * 
 * @param array $session - Current game session
 * @param array $game_data - Complete game data
 * @param array $player_investments - Player investments
 * @return void
 */
function render_main_stats($session, $game_data, $player_investments) {
    ?>
    <div class="stats-card">
        <h3><i class="fas fa-chart-line"></i> Game Stats</h3>
        
        <!-- Risk Capital -->
        <div class="stat-item">
            <span class="stat-label">
                <i class="fas fa-coins"></i> Risk Capital
            </span>
            <span class="stat-value capital" id="capitalValue">
                <?php echo number_format($session['capital_remaining']); ?>
            </span>
        </div>
        
        <!-- Dice Remaining -->
        <div class="stat-item">
            <span class="stat-label">
                <i class="fas fa-dice"></i> Dice Remaining
            </span>
            <span class="stat-value dice" id="diceValue">
                <?php echo $session['dice_remaining']; ?>
            </span>
        </div>
        
        <!-- Strategies Invested -->
        <div class="stat-item">
            <span class="stat-label">
                <i class="fas fa-shield-alt"></i> Strategies
            </span>
            <span class="stat-value strategy" id="strategyCount">
                <?php echo count($player_investments); ?>
            </span>
        </div>
        
        <!-- Current Position -->
        <div class="stat-item">
            <span class="stat-label">
                <i class="fas fa-map-marker-alt"></i> Position
            </span>
            <span class=" " id="currentCellValue">
                <?php echo $session['current_cell']; ?> / <?php echo $game_data['game']['total_cells']; ?>
            </span>
        </div>
    </div>
    <?php
}

/**
 * Render dice section
 * 
 * @param array $session - Current game session
 * @param array $game_data - Complete game data
 * @return void
 */
function render_dice_section($session, $game_data) {
    ?>
    <div class="dice-section">
        <h3 style="margin: 0 0 15px 0; text-align: center; color: #2c3e50;">
            <i class="fas fa-dice"></i> Roll Dice
        </h3>
        
        <!-- Dice Display -->
        <div class="dice-display" id="diceDisplay">
            <img src="<?php echo ASSETS_URL; ?>images/dice/1.png" alt="Dice" id="diceImage">
        </div>
        
        <!-- Dice Roll Button -->
        <button class="btn-roll-dice" id="rollDiceBtn" 
                <?php echo ($session['dice_remaining'] <= 0) ? 'disabled' : ''; ?>>
            <i class="fas fa-dice"></i> Roll Dice
        </button>
        
        <!-- Investment Button -->
        <?php
        // Determine if investment should be enabled on load based on current cell
        $current_cell = $session['current_cell'];
        $can_invest = false;
        
        // Use game_data to check cell type (Logic mirrored from get_cell_info)
        // Check Audit
        if (in_array($current_cell, $game_data['audit_cells'])) {
            $can_invest = true;
        }
        // Check Bonus
        elseif (isset($game_data['bonus_cells'][$current_cell])) {
            $can_invest = true;
        }
        // Check Wildcard
        elseif (in_array($current_cell, $game_data['wildcard_cells'])) {
            $can_invest = true;
        }
        
        // Note: Logic for Threats/Opportunities enabling is subtle; usually investment happens BEFORE landing? 
        // Or is it only enabled on specific cells?
        // Based on JS logic: "enableInvestmentButton()" is called for Audit, Bonus, Wildcard.
        // JS explicitly DISABLES it for normal, threat, opportunity.
        // So we strictly follow that pattern.
        ?>
        <button class="btn-invest" id="investBtn" <?php echo ($can_invest) ? '' : 'disabled'; ?>>
            <i class="fas fa-hand-holding-usd"></i> Invest Strategies
        </button>
        
        <!-- Dice Info -->
        <div style="margin-top: 15px; text-align: center; font-size: 0.85rem; color: #7f8c8d;">
            <i class="fas fa-info-circle"></i> 
            <?php 
            if ($session['dice_remaining'] > 0) {
                echo $session['dice_remaining'] . ' throw' . ($session['dice_remaining'] > 1 ? 's' : '') . ' remaining';
            } else {
                echo 'No dice remaining';
            }
            ?>
        </div>
    </div>
    <?php
}

/**
 * Render progress card
 * 
 * @param array $session - Current game session
 * @param array $game_data - Complete game data
 * @return void
 */
function render_progress_card($session, $game_data) {
    $progress_percent = ($session['current_cell'] / $game_data['game']['total_cells']) * 100;
    $capital_percent = ($session['capital_remaining'] / $game_data['game']['risk_capital']) * 100;
    $dice_percent = ($session['dice_remaining'] / $game_data['game']['dice_limit']) * 100;
    ?>
    <div class="stats-card">
        <h3><i class="fas fa-chart-bar"></i> Progress</h3>
        
        <!-- Board Progress -->
        <div class="progress-item">
            <div class="progress-label">
                <span>Board Progress</span>
                <span><?php echo number_format($progress_percent, 1); ?>%</span>
            </div>
            <div class="progress-bar">
                <div class="progress-fill" style="width: <?php echo $progress_percent; ?>%; background: #3498db;"></div>
            </div>
        </div>
        
        <!-- Capital Remaining -->
        <div class="progress-item">
            <div class="progress-label">
                <span>Capital Left</span>
                <span><?php echo number_format($capital_percent, 1); ?>%</span>
            </div>
            <div class="progress-bar">
                <div class="progress-fill" style="width: <?php echo $capital_percent; ?>%; background: #27ae60;"></div>
            </div>
        </div>
        
        <!-- Dice Remaining -->
        <div class="progress-item">
            <div class="progress-label">
                <span>Dice Left</span>
                <span><?php echo number_format($dice_percent, 1); ?>%</span>
            </div>
            <div class="progress-bar">
                <div class="progress-fill" style="width: <?php echo $dice_percent; ?>%; background: #f39c12;"></div>
            </div>
        </div>
    </div>
    
    <style>
        .progress-item {
            margin-bottom: 15px;
        }
        .progress-item:last-child {
            margin-bottom: 0;
        }
        .progress-label {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
            font-size: 0.9rem;
            color: #555;
        }
        .progress-bar {
            height: 8px;
            background: #ffffffff;
            border-radius: 4px;
            overflow: hidden;
        }
        .progress-fill {
            height: 100%;
            transition: width 0.5s ease;
            border-radius: 4px;
        }
    </style>
    <?php
}

/**
 * Render investment summary
 * Shows current strategy investments
 * 
 * @param array $player_investments - Player's current investments
 * @return void
 */
function render_investment_summary($player_investments) {
    if (empty($player_investments)) {
        return;
    }
    
    $total_invested = 0;
    foreach ($player_investments as $investment) {
        $total_invested += $investment['investment_points'];
    }
    ?>
    <div class="stats-card">
        <h3><i class="fas fa-list"></i> Your Investments</h3>
        
        <div style="margin-bottom: 15px; padding: 10px; background: #f8f9fa; border-radius: 6px; text-align: center;">
            <div style="font-size: 0.85rem; color: #7f8c8d;">Total Invested</div>
            <div style="font-size: 1.5rem; font-weight: 700; color: #9b59b6;">
                <?php echo $total_invested; ?> pts
            </div>
        </div>
        
        <div class="investment-summary-list">
            <?php foreach ($player_investments as $investment): ?>
                <div class="investment-summary-item">
                    <div style="flex: 1;">
                        <div style="font-size: 0.9rem; color: #2c3e50; font-weight: 600;">
                            <?php echo htmlspecialchars($investment['strategy_name']); ?>
                        </div>
                    </div>
                    <div style="font-size: 0.9rem; color: #27ae60; font-weight: 600;">
                        <?php echo $investment['investment_points']; ?> pts
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <button onclick="openInvestmentModal()" 
                style="width: 100%; margin-top: 15px; padding: 10px; background: #ecf0f1; color: #2c3e50; border: none; border-radius: 6px; cursor: pointer; font-weight: 600;">
            <i class="fas fa-eye"></i> View Details
        </button>
    </div>
    
    <style>
        .investment-summary-list {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        .investment-summary-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 12px;
            background: #f8f9fa;
            border-radius: 6px;
            font-size: 0.85rem;
        }
    </style>
    <?php
}

/**
 * Render mini game summary
 * Shows threats and opportunities counts
 * 
 * @param array $game_data - Complete game data
 * @return void
 */
function render_game_summary($game_data) {
    $threats_count = count($game_data['threats']);
    $opportunities_count = count($game_data['opportunities']);
    $wildcards_count = count($game_data['wildcard_cells']);
    $bonus_count = count($game_data['bonus_cells']);
    ?>
    <div class="stats-card">
        <h3><i class="fas fa-info-circle"></i> Board Info</h3>
        
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
            <div class="info-box">
                <div class="info-icon" style="color: #e74c3c;">
                    <i class="fas fa-arrow-down"></i>
                </div>
                <div class="info-value"><?php echo $threats_count; ?></div>
                <div class="info-label">Snakes</div>
            </div>
            
            <div class="info-box">
                <div class="info-icon" style="color: #27ae60;">
                    <i class="fas fa-arrow-up"></i>
                </div>
                <div class="info-value"><?php echo $opportunities_count; ?></div>
                <div class="info-label">Ladders</div>
            </div>
            
            <div class="info-box">
                <div class="info-icon" style="color: #9b59b6;">
                    <i class="fas fa-question"></i>
                </div>
                <div class="info-value"><?php echo $wildcards_count; ?></div>
                <div class="info-label">Wildcards</div>
            </div>
            
            <div class="info-box">
                <div class="info-icon" style="color: #f39c12;">
                    <i class="fas fa-star"></i>
                </div>
                <div class="info-value"><?php echo $bonus_count; ?></div>
                <div class="info-label">Bonuses</div>
            </div>
        </div>
    </div>
    
    <style>
        .info-box {
            text-align: center;
            padding: 15px 10px;
            background: #f8f9fa;
            border-radius: 8px;
        }
        .info-icon {
            font-size: 1.5rem;
            margin-bottom: 8px;
        }
        .info-value {
            font-size: 1.5rem;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 5px;
        }
        .info-label {
            font-size: 0.8rem;
            color: #7f8c8d;
        }
    </style>
    <?php
}

/**
 * Render mobile-friendly collapsed stats
 * For smaller screens
 * 
 * @param array $session - Current game session
 * @return void
 */
function render_mobile_stats($session) {
    ?>
    <div class="mobile-stats-bar">
        <div class="mobile-stat">
            <i class="fas fa-coins"></i>
            <span id="mobileCapital"><?php echo $session['capital_remaining']; ?></span>
        </div>
        <div class="mobile-stat">
            <i class="fas fa-dice"></i>
            <span id="mobileDice"><?php echo $session['dice_remaining']; ?></span>
        </div>
        <div class="mobile-stat">
            <i class="fas fa-map-marker-alt"></i>
            <span id="mobileCell"><?php echo $session['current_cell']; ?></span>
        </div>
    </div>
    
    <style>
        .mobile-stats-bar {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            background: #fff;
            padding: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            z-index: 100;
            justify-content: space-around;
        }
        .mobile-stat {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 5px;
            font-size: 0.9rem;
        }
        .mobile-stat i {
            font-size: 1.2rem;
            color: #3498db;
        }
        .mobile-stat span {
            font-weight: 700;
            color: #2c3e50;
        }
        
        @media (max-width: 768px) {
            .mobile-stats-bar {
                display: flex;
            }
            .game-stats-sidebar {
                padding-top: 60px;
            }
        }
    </style>
    <?php
}
?>
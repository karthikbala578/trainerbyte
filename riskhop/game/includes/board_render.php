<?php
/**
 * RiskHOP Board Renderer
 * Dynamically renders game board based on matrix configuration
 */

if (!defined('BASE_URL')) {
    die('Direct access not permitted');
}

/**
 * Render complete game board
 * 
 * @param array $game_data - Complete game data from get_game_data()
 * @param array $session - Current game session data
 * @return void
 */
function render_game_board($game_data, $session) {
    $game = $game_data['game'];
    $total_cells = $game['total_cells'];
    $grid_size = (int)explode('x', $game['matrix_type'])[0];
    $board_class = 'board-' . str_replace('x', 'x', strtolower($game['matrix_type']));
    
    echo '<div class="game-board ' . $board_class . '" id="gameBoard">';
    
    // Render cells (bottom to top, snake & ladder style)
    $cells_per_row = $grid_size;
    $rows = $grid_size;
    
    for ($row = $rows; $row >= 1; $row--) {
        // Calculate start and end cell numbers for this row
        $row_start_cell = ($row - 1) * $cells_per_row + 1;
        $row_end_cell = $row * $cells_per_row;
        
        // ODD rows (1st, 3rd, 5th...): Go LEFT to RIGHT
        // EVEN rows (2nd, 4th, 6th...): Go RIGHT to LEFT (zigzag)
        if ($row % 2 == 1) {
            // Odd row - LEFT to RIGHT
            for ($cell = $row_start_cell; $cell <= $row_end_cell; $cell++) {
                if ($cell <= $total_cells) {
                    render_board_cell($cell, $game_data, $session);
                }
            }
        } else {
            // Even row - RIGHT to LEFT (reverse)
            for ($cell = $row_end_cell; $cell >= $row_start_cell; $cell--) {
                if ($cell <= $total_cells) {
                    render_board_cell($cell, $game_data, $session);
                }
            }
        }
    }
    
    echo '</div>';
}

/**
 * Render individual board cell
 * 
 * @param int $cell_number - Cell number to render
 * @param array $game_data - Complete game data
 * @param array $session - Current game session
 * @return void
 */
function render_board_cell($cell_number, $game_data, $session) {
    $cell_info = get_cell_info($game_data['game']['id'], $cell_number);
    $is_current = ($session['current_cell'] == $cell_number);
    $is_start = ($cell_number == 1);
    $is_finish = ($cell_number == $game_data['game']['total_cells']);
    
    // Cell classes
    $cell_classes = ['board-cell'];
    if ($is_current) $cell_classes[] = 'current-cell';
    if ($is_start) $cell_classes[] = 'start-cell';
    if ($is_finish) $cell_classes[] = 'finish-cell';
    $cell_classes[] = 'cell-type-' . $cell_info['type'];
    
    echo '<div class="' . implode(' ', $cell_classes) . '" data-cell="' . $cell_number . '" data-type="' . $cell_info['type'] . '">';
    
    // Cell number
    echo '<span class="cell-number">' . $cell_number . '</span>';
    
    // Special markers for start and finish
    if ($is_start) {
        echo '<span class="cell-marker start-marker">START</span>';
    }
    if ($is_finish) {
        echo '<span class="cell-marker finish-marker">FINISH</span>';
    }
    
    // Display icon based on cell type
    render_cell_icon($cell_info);
    
    // Player token
    if ($is_current) {
        render_player_token($session);
    }
    
    // Cell connections (snakes and ladders visual paths)
    render_cell_connections($cell_number, $cell_info);
    
    echo '</div>';
}

/**
 * Render cell icon based on type
 * 
 * @param array $cell_info - Cell information from get_cell_info()
 * @return void
 */
function render_cell_icon($cell_info) {
    $icons = [
        'threat' => '<i class="cell-icon snake fas fa-arrow-down" title="Snake (Threat)"></i>',
        'opportunity' => '<i class="cell-icon ladder fas fa-arrow-up" title="Ladder (Opportunity)"></i>',
        'bonus' => '<i class="cell-icon bonus fas fa-star" title="Bonus Points"></i>',
        'audit' => '<i class="cell-icon audit fas fa-search" title="Audit Cell"></i>',
        'wildcard' => '<i class="cell-icon wildcard fas fa-question" title="Wild Card"></i>',
    ];
    
    if (isset($icons[$cell_info['type']])) {
        echo $icons[$cell_info['type']];
    }
}

/**
 * Render player token
 * 
 * @param array $session - Current game session
 * @return void
 */
function render_player_token($session) {
    // Generate player initials or icon
    $player_name = isset($session['player_name']) ? $session['player_name'] : 'Player';
    $initials = strtoupper(substr($player_name, 0, 2));
    
    echo '<div class="player-token" title="Your Position">';
    echo $initials;
    echo '</div>';
}

/**
 * Render visual connections for snakes and ladders
 * 
 * @param int $cell_number - Current cell number
 * @param array $cell_info - Cell information
 * @return void
 */
function render_cell_connections($cell_number, $cell_info) {
    // This creates visual SVG paths for snakes and ladders
    // Implementation can be enhanced with SVG drawing
    
    if ($cell_info['type'] == 'threat' || $cell_info['type'] == 'opportunity') {
        $data = $cell_info['data'];
        $from = $data['cell_from'] ?? $cell_number;
        $to = $data['cell_to'] ?? $cell_number;
        
        if ($from == $cell_number) {
            echo '<span class="connection-indicator" data-from="' . $from . '" data-to="' . $to . '">';
            echo $cell_info['type'] == 'threat' ? '↓' : '↑';
            echo '</span>';
        }
    }
}

/**
 * Render board legend
 * 
 * @return void
 */
function render_board_legend() {
    ?>
    <div class="legend-card">
        <h3>Legend</h3>
        <div class="legend-compact">
            <div class="legend-compact-item">
                <div class="legend-compact-icon snake">
                    <i class="fas fa-arrow-down"></i>
                </div>
                <span>Snake: Strategic Risk</span>
            </div>
            <div class="legend-compact-item">
                <div class="legend-compact-icon ladder">
                    <i class="fas fa-arrow-up"></i>
                </div>
                <span>Ladder: Advancement</span>
            </div>
            <div class="legend-compact-item">
                <div class="legend-compact-icon wildcard">
                    <i class="fas fa-question"></i>
                </div>
                <span>Wild Card: Random Event</span>
            </div>
            <div class="legend-compact-item">
                <div class="legend-compact-icon bonus">
                    <i class="fas fa-star"></i>
                </div>
                <span>Bonus: Extra Points</span>
            </div>
            <div class="legend-compact-item">
                <div class="legend-compact-icon audit">
                    <i class="fas fa-search"></i>
                </div>
                <span>Audit: Compliance Check</span>
            </div>
        </div>
    </div>
    <?php
}

/**
 * Get cell visual position for SVG paths
 * Helper function for drawing snakes and ladders
 * 
 * @param int $cell_number - Cell number
 * @param int $grid_size - Grid size (e.g., 8 for 8x8)
 * @return array - ['x' => x_position, 'y' => y_position]
 */
function get_cell_position($cell_number, $grid_size) {
    $row = ceil($cell_number / $grid_size);
    $col = (($row % 2) == 1) ? 
           (($cell_number - 1) % $grid_size) + 1 : 
           $grid_size - (($cell_number - 1) % $grid_size);
    
    return [
        'row' => $row,
        'col' => $col,
        'x' => ($col - 1) * 100 + 50, // Center of cell
        'y' => ($grid_size - $row) * 100 + 50
    ];
}

/**
 * Render SVG overlay for snakes and ladders paths
 * This creates visual connections between cells
 * 
 * @param array $game_data - Complete game data
 * @return void
 */
function render_board_overlay($game_data) {
    $game = $game_data['game'];
    $grid_size = (int)explode('x', $game['matrix_type'])[0];
    
    echo '<svg class="board-overlay" viewBox="0 0 ' . ($grid_size * 100) . ' ' . ($grid_size * 100) . '">';
    
    // Draw snakes
    foreach ($game_data['threats'] as $threat) {
        $from_pos = get_cell_position($threat['cell_from'], $grid_size);
        $to_pos = get_cell_position($threat['cell_to'], $grid_size);
        
        echo '<path class="snake-path" ';
        echo 'd="M' . $from_pos['x'] . ',' . $from_pos['y'] . ' ';
        echo 'Q' . (($from_pos['x'] + $to_pos['x']) / 2) . ',' . (($from_pos['y'] + $to_pos['y']) / 2 - 50) . ' ';
        echo $to_pos['x'] . ',' . $to_pos['y'] . '" ';
        echo 'stroke="#e74c3c" stroke-width="3" fill="none" opacity="0.6"/>';
    }
    
    // Draw ladders
    foreach ($game_data['opportunities'] as $opportunity) {
        $from_pos = get_cell_position($opportunity['cell_from'], $grid_size);
        $to_pos = get_cell_position($opportunity['cell_to'], $grid_size);
        
        echo '<path class="ladder-path" ';
        echo 'd="M' . $from_pos['x'] . ',' . $from_pos['y'] . ' ';
        echo 'Q' . (($from_pos['x'] + $to_pos['x']) / 2) . ',' . (($from_pos['y'] + $to_pos['y']) / 2 + 50) . ' ';
        echo $to_pos['x'] . ',' . $to_pos['y'] . '" ';
        echo 'stroke="#27ae60" stroke-width="3" fill="none" opacity="0.6"/>';
    }
    
    echo '</svg>';
}
?>
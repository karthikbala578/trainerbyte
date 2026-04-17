<?php
/**
 * RiskHOP Diagnostic Test Script
 * Run this file to test all components: http://localhost/RiskHOP/test_diagnostic.php
 */

// Enable all error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html><html><head><title>RiskHOP Diagnostic</title>";
echo "<style>
body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    max-width: 1000px;
    margin: 50px auto;
    padding: 20px;
    background: #f5f5f5;
}
.test-section {
    background: white;
    padding: 20px;
    margin: 20px 0;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}
.success { color: #28a745; font-weight: bold; }
.error { color: #dc3545; font-weight: bold; }
.warning { color: #ffc107; font-weight: bold; }
h1 { color: #333; border-bottom: 3px solid #007bff; padding-bottom: 10px; }
h2 { color: #007bff; margin-top: 0; }
pre { background: #f8f9fa; padding: 10px; border-radius: 4px; overflow-x: auto; }
.status { padding: 5px 10px; border-radius: 4px; display: inline-block; margin: 5px 0; }
.status-pass { background: #d4edda; color: #155724; }
.status-fail { background: #f8d7da; color: #721c24; }
</style></head><body>";

echo "<h1>🔧 RiskHOP Diagnostic Test</h1>";

// Test 1: PHP Version
echo "<div class='test-section'>";
echo "<h2>1. PHP Version Check</h2>";
$php_version = phpversion();
if (version_compare($php_version, '7.4.0', '>=')) {
    echo "<span class='status status-pass'>✓ PASS</span> PHP Version: <strong>$php_version</strong>";
} else {
    echo "<span class='status status-fail'>✗ FAIL</span> PHP Version: <strong>$php_version</strong> (Minimum required: 7.4.0)";
}
echo "</div>";

// Test 2: Required PHP Extensions
echo "<div class='test-section'>";
echo "<h2>2. PHP Extensions</h2>";
$required_extensions = ['mysqli', 'json', 'session'];
foreach ($required_extensions as $ext) {
    if (extension_loaded($ext)) {
        echo "<span class='status status-pass'>✓</span> <strong>$ext</strong> extension is loaded<br>";
    } else {
        echo "<span class='status status-fail'>✗</span> <strong>$ext</strong> extension is NOT loaded<br>";
    }
}
echo "</div>";

// Test 3: Database Connection
echo "<div class='test-section'>";
echo "<h2>3. Database Connection</h2>";
require_once 'config.php';

if ($conn) {
    echo "<span class='status status-pass'>✓ PASS</span> Database connection successful<br>";
    echo "<pre>";
    echo "Host: " . DB_HOST . "\n";
    echo "Database: " . DB_NAME . "\n";
    echo "User: " . DB_USER;
    echo "</pre>";
} else {
    echo "<span class='status status-fail'>✗ FAIL</span> Database connection failed: " . mysqli_connect_error();
}
echo "</div>";

// Test 4: Database Tables
if ($conn) {
    echo "<div class='test-section'>";
    echo "<h2>4. Database Tables</h2>";
    
    $required_tables = [
        'mg6_admin_users',
        'mg6_riskhop_matrix',
        'mg6_riskhop_threats',
        'mg6_riskhop_opportunities',
        'mg6_riskhop_strategies',
        'mg6_threat_strategy_mapping',
        'mg6_opportunity_strategy_mapping',
        'mg6_riskhop_bonus',
        'mg6_riskhop_audit',
        'mg6_riskhop_wildcard_cells',
        'mg6_riskhop_wildcards',
        'mg6_game_sessions',
        'mg6_player_investments',
        'mg6_game_moves',
        'mg6_game_statistics',
        'mg6_session_wildcards_opened'
    ];
    
    $all_tables_exist = true;
    foreach ($required_tables as $table) {
        $result = mysqli_query($conn, "SHOW TABLES LIKE '$table'");
        if (mysqli_num_rows($result) > 0) {
            echo "<span class='status status-pass'>✓</span> Table <strong>$table</strong> exists<br>";
        } else {
            echo "<span class='status status-fail'>✗</span> Table <strong>$table</strong> is MISSING<br>";
            $all_tables_exist = false;
        }
    }
    
    if (!$all_tables_exist) {
        echo "<br><span class='warning'>⚠️ Some tables are missing. Please run the riskhop.sql file in phpMyAdmin.</span>";
    }
    echo "</div>";
}

// Test 5: Published Games
if ($conn) {
    echo "<div class='test-section'>";
    echo "<h2>5. Published Games</h2>";
    
    $query = "SELECT * FROM mg6_riskhop_matrix WHERE status = 'published'";
    $result = mysqli_query($conn, $query);
    
    if ($result && mysqli_num_rows($result) > 0) {
        echo "<span class='status status-pass'>✓ PASS</span> Found " . mysqli_num_rows($result) . " published game(s)<br><br>";
        echo "<table border='1' cellpadding='10' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>ID</th><th>Game Name</th><th>Matrix</th><th>Dice Limit</th><th>Risk Capital</th><th>Status</th></tr>";
        
        while ($row = mysqli_fetch_assoc($result)) {
            echo "<tr>";
            echo "<td>{$row['id']}</td>";
            echo "<td>{$row['game_name']}</td>";
            echo "<td>{$row['matrix_type']}</td>";
            echo "<td>{$row['dice_limit']}</td>";
            echo "<td>{$row['risk_capital']}</td>";
            echo "<td><span class='success'>{$row['status']}</span></td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<span class='status status-fail'>✗ FAIL</span> No published games found<br>";
        echo "<span class='warning'>⚠️ You need to create and publish a game from the admin panel</span>";
    }
    echo "</div>";
}

// Test 6: File Structure
echo "<div class='test-section'>";
echo "<h2>6. File Structure</h2>";

$required_files = [
    'config.php',
    'functions.php',
    'game/game_engine.php',
    'game/instruction.php',
    'game/ajax/start_game.php',
    'game/ajax/get_session.php',
    'assets/js/game.js',
    'assets/css/game.css'
];

$all_files_exist = true;
foreach ($required_files as $file) {
    if (file_exists(__DIR__ . '/' . $file)) {
        echo "<span class='status status-pass'>✓</span> File <strong>$file</strong> exists<br>";
    } else {
        echo "<span class='status status-fail'>✗</span> File <strong>$file</strong> is MISSING<br>";
        $all_files_exist = false;
    }
}
echo "</div>";

// Test 7: Session Test
echo "<div class='test-section'>";
echo "<h2>7. Session Support</h2>";

if (session_status() === PHP_SESSION_ACTIVE) {
    echo "<span class='status status-pass'>✓ PASS</span> PHP Sessions are working<br>";
    echo "<pre>Session ID: " . session_id() . "</pre>";
} else {
    echo "<span class='status status-fail'>✗ FAIL</span> PHP Sessions are NOT working";
}
echo "</div>";

// Test 8: Game Engine Functions
if ($conn) {
    echo "<div class='test-section'>";
    echo "<h2>8. Game Engine Functions</h2>";
    
    require_once 'functions.php';
    require_once 'game/game_engine.php';
    
    $functions_to_test = [
        'get_game_data',
        'get_published_games',
        'start_new_game',
        'get_current_session',
        'get_threat_strategies',
        'get_opportunity_strategies'
    ];
    
    foreach ($functions_to_test as $func) {
        if (function_exists($func)) {
            echo "<span class='status status-pass'>✓</span> Function <strong>$func()</strong> is defined<br>";
        } else {
            echo "<span class='status status-fail'>✗</span> Function <strong>$func()</strong> is NOT defined<br>";
        }
    }
    echo "</div>";
}

// Test 9: AJAX Endpoint Test
echo "<div class='test-section'>";
echo "<h2>9. Start Game AJAX Test</h2>";

if ($conn && mysqli_num_rows(mysqli_query($conn, "SELECT * FROM mg6_riskhop_matrix WHERE status='published'")) > 0) {
    $game = mysqli_fetch_assoc(mysqli_query($conn, "SELECT id FROM mg6_riskhop_matrix WHERE status='published' LIMIT 1"));
    $game_id = $game['id'];
    
    echo "<p>Testing game start for Game ID: <strong>$game_id</strong></p>";
    echo "<button onclick='testStartGame($game_id)' style='padding: 10px 20px; background: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer;'>
    🎮 Test Start Game
    </button>";
    echo "<div id='testResult' style='margin-top: 20px;'></div>";
    
    echo "<script>
    function testStartGame(gameId) {
        document.getElementById('testResult').innerHTML = '<p>Testing... Please wait...</p>';
        
        fetch('game/ajax/start_game.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ matrix_id: gameId })
        })
        .then(response => response.text())
        .then(text => {
            console.log('Response:', text);
            try {
                const data = JSON.parse(text);
                if (data.success) {
                    document.getElementById('testResult').innerHTML = 
                        '<div class=\"status status-pass\">✓ SUCCESS</div>' +
                        '<pre>' + JSON.stringify(data, null, 2) + '</pre>';
                } else {
                    document.getElementById('testResult').innerHTML = 
                        '<div class=\"status status-fail\">✗ FAILED</div>' +
                        '<pre>' + JSON.stringify(data, null, 2) + '</pre>';
                }
            } catch (e) {
                document.getElementById('testResult').innerHTML = 
                    '<div class=\"status status-fail\">✗ JSON PARSE ERROR</div>' +
                    '<p>Response is not valid JSON:</p>' +
                    '<pre>' + text + '</pre>';
            }
        })
        .catch(error => {
            document.getElementById('testResult').innerHTML = 
                '<div class=\"status status-fail\">✗ NETWORK ERROR</div>' +
                '<pre>' + error + '</pre>';
        });
    }
    </script>";
} else {
    echo "<span class='status status-fail'>✗ SKIP</span> Cannot test - No published games available";
}
echo "</div>";

// Summary
echo "<div class='test-section' style='background: #e7f3ff; border-left: 4px solid #007bff;'>";
echo "<h2>📊 Summary</h2>";
echo "<p><strong>If all tests pass above, your RiskHOP game should work!</strong></p>";
echo "<p>Next steps:</p>";
echo "<ol>";
echo "<li>If you see any <span class='status status-fail'>✗ FAIL</span> status, fix those issues first</li>";
echo "<li>Make sure at least one game is published in the admin panel</li>";
echo "<li>Clear your browser cache (Ctrl + Shift + Delete)</li>";
echo "<li>Try starting a game from: <a href='game/instruction.php?game_id=1' target='_blank'>game/instruction.php?game_id=1</a></li>";
echo "</ol>";
echo "</div>";

echo "</body></html>";

// Close connection
if ($conn) {
    mysqli_close($conn);
}
?>
<?php
session_start();
require "include/coreDataconnect.php";

$event_id = intval($_GET['event_id'] ?? 0);

$res = $conn->query("
    SELECT * FROM tb_events_module
    WHERE mod_event_pkid = $event_id AND mod_status = 1
    ORDER BY mod_order ASC
");

if (!$res) {
    echo $conn->error;
    exit;
}

if ($res->num_rows == 0) {
    echo "<p style='text-align:center;color:#999;'>No modules added</p>";
    exit;
}

while ($row = $res->fetch_assoc()):

    $name = "Unknown";

    /* TYPE 2 → BYTEGUESS */
    if ($row['mod_type'] == 2) {
        $q = $conn->query("SELECT cg_name FROM card_group WHERE cg_id = {$row['mod_game_id']}");
        if ($q && $r = $q->fetch_assoc()) {
            $name = $r['cg_name'];
        }
    }

    /* TYPE 4 → DIGIHUNT */ elseif ($row['mod_type'] == 4) {
        $q = $conn->query("SELECT p_name FROM prioritization WHERE p_id = {$row['mod_game_id']}");
        if ($q && $r = $q->fetch_assoc()) {
            $name = $r['p_name'];
        }
    }

    /* TYPE 5 → DIGISIM */ elseif ($row['mod_type'] == 5) {
        $q = $conn->query("SELECT di_name FROM mg5_digisim WHERE di_id = {$row['mod_game_id']}");
        if ($q && $r = $q->fetch_assoc()) {
            $name = $r['di_name'];
        }
    }

    /* TYPE 6 → RISKHOP */ elseif ($row['mod_type'] == 6) {
        $q = $conn->query("SELECT game_name FROM mg6_riskhop_matrix WHERE id = {$row['mod_game_id']}");
        if ($q && $r = $q->fetch_assoc()) {
            $name = $r['game_name'];
        }
    }

    /* TYPE 9 → MULTISTAGE DIGISIM */ 
    elseif ($row['mod_type'] == 9) {
        $q = $conn->query("SELECT di_name FROM mg5_digisim WHERE di_id = {$row['mod_game_id']}");
        if ($q && $r = $q->fetch_assoc()) {
            $name = $r['di_name'];
        }
    }
?>

    <div class="sequence-item"
        data-id="<?= $row['mod_game_id'] ?>"
        data-type="<?= $row['mod_type'] ?>">

        <span class="drag material-symbols-outlined">drag_indicator</span>

        <span class="title"><?= htmlspecialchars($name) ?></span>

        <button class="remove-btn"
            onclick="removeFromSequence(<?= $row['mod_game_id'] ?>, <?= $row['mod_type'] ?>)">
            <span class="material-symbols-outlined">close</span>
        </button>

    </div>

<?php endwhile; ?>
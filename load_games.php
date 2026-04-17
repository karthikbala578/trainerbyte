<?php
session_start();
require "include/coreDataconnect.php";

$type = intval($_GET['type'] ?? 0);
$team_id = $_SESSION['team_id'] ?? 0;

if (!$team_id) exit("Invalid request");

/* TYPE MAP */
$typeMap = [
    2 => ['icon' => 'style', 'color' => 'blue', 'label' => 'ByteGuess'],
    4 => ['icon' => 'view_list', 'color' => 'orange', 'label' => 'DigiHunt'],
    5 => ['icon' => 'memory', 'color' => 'purple', 'label' => 'DigiSim'],
    6 => ['icon' => 'hub', 'color' => 'purple', 'label' => 'RiskHOP']
];

$modules = [];

/* ================= BYTEGUESS ================= */
if ($type === 0 || $type == 2) {

    $stmt = $conn->prepare("
        SELECT cg_id AS id, cg_name AS name, cg_description AS des
        FROM card_group
        WHERE cg_status = 1
        AND byteguess_pkid = ? ORDER BY cg_id DESC
    ");
    $stmt->bind_param("i", $team_id);
    $stmt->execute();
    $res = $stmt->get_result();

    while ($r = $res->fetch_assoc()) {
        $r['type'] = 2;
        $modules[] = $r;
    }
}

// /* ================= DIGIHUNT ================= */
// if ($type === 0 || $type == 4) {

//     $stmt = $conn->prepare("
//         SELECT p_id AS id, p_name AS name, p_description AS des
//         FROM prioritization
//         WHERE p_team_pkid = ?
//         ORDER BY p_id DESC
//     ");
//     $stmt->bind_param("i", $team_id);
//     $stmt->execute();
//     $res = $stmt->get_result();

//     while ($r = $res->fetch_assoc()) {
//         $r['type'] = 4;
//         $modules[] = $r;
//     }
// }

/* ================= DIGISIM ================= */
if ($type === 0 || $type == 5) {

    $stmt = $conn->prepare("
        SELECT di_id AS id, di_name AS name, di_description AS des
        FROM mg5_digisim
        WHERE di_status = 1
        AND di_digisim_category_pkid =?
        ORDER BY di_id DESC
    ");
    $stmt->bind_param("i", $team_id);
    $stmt->execute();
    $res = $stmt->get_result();

    while ($r = $res->fetch_assoc()) {
        $r['type'] = 5;
        $modules[] = $r;
    }
}
/* ================= RISKHOP ================= */
if ($type === 0 || $type == 6) {

    $stmt = $conn->prepare("SELECT id AS id, game_name AS name, description AS des
        FROM mg6_riskhop_matrix
        WHERE status = 'published'      
        ORDER BY id DESC
    ");
   // $stmt->bind_param("i", $team_id);
    $stmt->execute();
    $res = $stmt->get_result();

    while ($r = $res->fetch_assoc()) {
        $r['type'] = 6;
        $modules[] = $r;
    }
}
// for multistage digisim
if ($type === 0 || $type == 9) {

    $stmt = $conn->prepare("
        SELECT ms_id AS id, ms_name AS name, ms_desc AS des
        FROM mg5_ms_digisim_master
        WHERE ms_status = 1
        AND ms_team_pkid = ?
        ORDER BY ms_id DESC
    ");
    $stmt->bind_param("i", $team_id);
    $stmt->execute();
    $res = $stmt->get_result();

    while ($r = $res->fetch_assoc()) {
        $r['type'] = 9;
        $modules[] = $r;
    }
}

/* ================= EMPTY ================= */
if (empty($modules)) {

    $msg = ($type === 0)
        ? "No modules available for your team"
        : "No modules available in this category";

    echo "
    <div class='empty-state'>
        <span class='material-symbols-outlined'>inventory_2</span>
        <p>$msg</p>
    </div>
    ";
    exit;
}

/* ================= RENDER ================= */
foreach ($modules as $row):

    $t = $row['type'];
    $meta = $typeMap[$t];
?>

    <div class="exercise-card">
        <!-- TITLE -->
        <strong><?= htmlspecialchars($row['name']) ?></strong>

        <!-- PILL -->
        <?php if ($type === 0): ?>
            <span class="type-pill"><?= $meta['label'] ?></span>
        <?php endif; ?>

        <!-- DESC -->
        <p><?= htmlspecialchars(substr($row['des'] ?? '', 0, 80)) ?></p>

        <!-- ADD BUTTON (FIXED) -->
        <button
            data-id="<?= $row['id'] ?>"
            data-name="<?= htmlspecialchars($row['name'], ENT_QUOTES) ?>"
            data-type="<?= $t ?>"
            onclick="handleAdd(this)">

            <span class="material-symbols-outlined">add</span>
        </button>

    </div>

<?php endforeach; ?>
<?php
session_start();
require "../include/dataconnect.php";

$type = intval($_GET['type'] ?? 0);
$team_id = $_SESSION['team_id'] ?? 0;
if (!$team_id || !$type) {
    exit("Invalid request");
}
/* CARD GAMES */
if ($type === 2) {

    $stmt = $conn->prepare("
        SELECT cg_id AS id, cg_name AS name, cg_description AS des
        FROM card_group
        WHERE cg_status = 1
          AND byteguess_pkid  = ?
        ORDER BY createddate DESC
    ");
    $stmt->bind_param("i", $team_id);
}

// /* PRIORITIZATION  */
// elseif ($type === 2) {

//     $stmt = $conn->prepare("
//         SELECT p_id AS id, p_name AS name, p_description AS des
//         FROM prioritization
//         WHERE p_team_pkid = ?
//         ORDER BY p_id DESC
//     ");
//     $stmt->bind_param("i", $team_id);
// }

// /* TREASURE HUNT (PLACEHOLDER) */
// elseif ($type === 3) {

//     $stmt = $conn->prepare("
//         SELECT th_id AS id, th_name AS name, th_description AS des
//         FROM treasure_hunt
//         WHERE th_team_pkid = ?
//         ORDER BY th_id DESC
//     ");
//     $stmt->bind_param("i", $team_id);
//}// DigiSim
elseif ($type === 5) {
    // $stmt = $conn->prepare("SELECT ro_id AS id, ro_name AS name, ro_long_desc AS des
    //     FROM gm_round  WHERE ro_status = ? AND ro_type = ? ORDER BY ro_id DESC");
    $stmt = $conn->prepare("SELECT di_id AS id, di_name AS name, di_description AS des
        FROM mg5_digisim WHERE di_status = 1  AND di_digisim_category_pkid=? ORDER BY di_id DESC");
    $stmt->bind_param("i", $team_id);
}// RiskHop
elseif ($type === 6) {

    $stmt = $conn->prepare("
        SELECT id AS id, game_name AS name, description AS des,status
        FROM mg6_riskhop_matrix
        WHERE status = ?
        ORDER BY id DESC");
    $status = "published";
    $stmt->bind_param("s", $status);
}// Trust Trap
// elseif ($type === 7) {

//     $stmt = $conn->prepare("
//         SELECT id AS id, title AS name, description AS des FROM mg7_games
//         WHERE status = ?
//         ORDER BY id DESC
//     ");
//     $status = "active";
//     $stmt->bind_param("s", $status);
// }//BountyBid
// elseif ($type === 8) {
//     $stmt = $conn->prepare("
//         SELECT id AS id, title AS name, description AS des FROM mg8_games WHERE status = ? ORDER BY id DESC");
//     $status = "active";
//     $stmt->bind_param("s", $status);
// }
/* 🚨 INVALID TYPE */
else {
    http_response_code(400);
    exit("Unsupported game type");
}

/* FINAL SAFETY CHECK */
if (!$stmt) {
    exit("Query preparation failed: " . $conn->error);
}

$stmt->execute();
$res = $stmt->get_result();

while ($row = $res->fetch_assoc()):
    $clean = $row['des'];

    while (html_entity_decode($clean, ENT_QUOTES, 'UTF-8') !== $clean) {
        $clean = html_entity_decode($clean, ENT_QUOTES, 'UTF-8');
    }
?>
<div class="exercise-card">
    <div class="info">
        <strong><?php echo  htmlspecialchars($row['name']) ?></strong>
        <p><?php echo $clean; ?></p>    </div>

    <button class="add-btn"
        onclick="addToEvent(
            <?php echo  $row['id'] ?>,
            '<?php echo  addslashes($row['name']) ?>',
            <?php echo  $type ?>
        )">
        +
    </button>
</div>
<?php endwhile; ?>

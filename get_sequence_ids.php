<?php
require "include/coreDataconnect.php";

$event_id = intval($_GET['event_id']);

$res = $conn->query("
    SELECT mod_game_id FROM tb_events_module
    WHERE mod_event_pkid = $event_id AND mod_status = 1
");

$ids = [];

while ($row = $res->fetch_assoc()) {
    $ids[] = (int)$row['mod_game_id'];
}

echo json_encode($ids);

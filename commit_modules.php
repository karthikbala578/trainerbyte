<?php
session_start();

require "../include/dataconnect.php";

if (!isset($_SESSION['team_id'])) {
    die("Unauthorized");
}
$teamId=$_SESSION['team_id'];
$event_id = intval($_POST['event_id'] ?? 0);
//echo $event_id;
if ($event_id <= 0) {
    die("Invalid event id");
}
/* Check staged modules */
if (
    !isset($_SESSION['event_modules']) ||
    !isset($_SESSION['event_modules'][$event_id]) ||
    empty($_SESSION['event_modules'][$event_id])
) {
    die("No modules to save");
}

$modules = $_SESSION['event_modules'][$event_id];
//print_r($modules);

/* Prepare insert */
$stmt = $conn->prepare("
    INSERT INTO tb_events_module
    (mod_event_pkid, mod_type, mod_game_id, mod_order)
    VALUES (?, ?, ?, ?)
");

if (!$stmt) {
    die("SQL Prepare Failed: " . $conn->error);
}

$order = 1;

foreach ($modules as $m) {

    if (
        !isset($m['type'], $m['game_id'])
    ) {
        die("Invalid module data");
    }

    $stmt->bind_param(
        "iiii",
        $event_id,
        $m['type'],
        $m['game_id'],
        $order
    );

    if (!$stmt->execute()) {
        die("Insert failed: " . $stmt->error);
    }

    $order++;
}
  // Start Code By Maria Set URL with  tb_event_user 
        // $stmt_eu = $conn->prepare("SELECT * FROM tb_team WHERE team_id = ? ");
        // $stmt_eu->bind_param("i",$teamId);
        // $stmt_eu->execute();
        // $result_eu= $stmt_eu->get_result();
        // if ($result_eu->num_rows === 1) {
           // $row_eu = $result_eu->fetch_assoc();
                $stmt_etp = $conn->prepare("SELECT * FROM tb_events WHERE event_id = ? ");
                $stmt_etp->bind_param("i",$event_id);
                $stmt_etp->execute();
                $result_etp= $stmt_etp->get_result();
                if($row_etp = $result_etp->fetch_assoc()){
                $event_id        = $row_etp['event_id'];
                $event_team_pkid = $row_etp['event_team_pkid'];
                $event_passcode  = $row_etp["event_passcode"];                           
                $event_url_code  = $row_etp["event_url_code"];
                }
         
       // }
   
    // End Code By Maria
/* Clear staged modules */
unset($_SESSION['event_modules'][$event_id]);

  

/* Redirect */
 header("Location: ../confirmUrl.php?eid=".$event_id."&code=".$event_url_code);
 exit;
?>


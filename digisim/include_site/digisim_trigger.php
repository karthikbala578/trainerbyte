<?php
session_start();
include("../../include/coreDataconnect.php");

header('Content-Type: application/json');

/*  SESSION CHECK  */
if(!isset($_SESSION['user_id']) || !isset($_SESSION['event_id'])){
    echo json_encode(["status"=>"error","msg"=>"Session expired"]);
    exit;
}

/*  INPUT  */
$data = json_decode(file_get_contents("php://input"), true);

$sim_id    = intval($data['sim_id'] ?? 0);
$task_id   = intval($data['task_id'] ?? 0);
$dropCount = intval($data['count'] ?? 0);

if(!$sim_id || !$task_id){
    echo json_encode(["status"=>"error","msg"=>"Invalid data"]);
    exit;
}

$user_id  = $_SESSION['user_id'];
$event_id = $_SESSION['event_id'];

/*  GET RESPONSE ORDER  */
$stmt = $conn->prepare("
    SELECT dr_order
    FROM mg5_digisim_response
    WHERE dr_id = ?
");
$stmt->bind_param("i", $task_id);
$stmt->execute();
$stmt->bind_result($taskOrder);
$stmt->fetch();
$stmt->close();

if(!$taskOrder){
    echo json_encode(["status"=>"error","msg"=>"Invalid task"]);
    exit;
}

/*  COLLECT TRIGGER MESSAGES  */
$messages = [];

/*  TASK TRIGGER (dm_trigger = 2)  */
$stmt = $conn->prepare("
    SELECT dm.dm_id, sc.ch_level
    FROM mg5_digisim_message dm
    JOIN mg5_sub_channels sc 
        ON dm.dm_injectes_pkid = sc.ch_id
    WHERE dm.dm_digisim_pkid = ?
      AND dm.dm_trigger = 2
      AND dm.dm_event = ?
");
$stmt->bind_param("ii", $sim_id, $taskOrder);
$stmt->execute();
$res = $stmt->get_result();

while($row = $res->fetch_assoc()){
    $messages[] = $row;
}
$stmt->close();

/*  PROGRESSIVE TRIGGER (dm_trigger = 3)  */
$stmt = $conn->prepare("
    SELECT dm.dm_id, sc.ch_level
    FROM mg5_digisim_message dm
    JOIN mg5_sub_channels sc 
        ON dm.dm_injectes_pkid = sc.ch_id
    WHERE dm.dm_digisim_pkid = ?
      AND dm.dm_trigger = 3
      AND dm.dm_event = ?
");
$stmt->bind_param("ii", $sim_id, $dropCount);
$stmt->execute();
$res = $stmt->get_result();

while($row = $res->fetch_assoc()){
    $messages[] = $row;
}
$stmt->close();

/*  INSERT ONLY NEW MESSAGES  */
$newMessages = [];

foreach($messages as $msg){

    $dm_id = $msg['dm_id'];

    /*  CHECK DUPLICATE */
    $check = $conn->prepare("
        SELECT id 
        FROM mg5_digisim_user_message
        WHERE user_id = ? 
        AND event_id = ? 
        AND sim_id = ? 
        AND dm_id = ?
    ");
    $check->bind_param("iiii", $user_id, $event_id, $sim_id, $dm_id);
    $check->execute();
    $check->store_result();

    if($check->num_rows == 0){

        /*  INSERT NEW MESSAGE */
        $insert = $conn->prepare("
            INSERT INTO mg5_digisim_user_message
            (user_id, event_id, sim_id, dm_id, is_read)
            VALUES (?, ?, ?, ?, 0)
        ");
        $insert->bind_param("iiii", $user_id, $event_id, $sim_id, $dm_id);
        $insert->execute();
        $insert->close();

        /*  ADD ONLY NEW MESSAGE FOR RESPONSE */
        $newMessages[] = $msg;
    }

    $check->close();
}

/*  RETURN ONLY NEW  */
echo json_encode([
    "status"   => "success",
    "messages" => $newMessages
]);
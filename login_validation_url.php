<?php

require "include/coreDataconnect.php";

session_start();



header('Content-Type: application/json');



$pin   = $_POST['pin']  ?? '';
$_SESSION['pin'] = $pin;

$code  = $_POST['code'] ?? '';

$_SESSION['code'] = $code; 

$uname = trim($_POST['uname'] ?? '');

$mode  = $_POST['mode'] ?? ''; // ðŸ”‘ NEW



if ($pin === '' || $code === '') {

    echo json_encode(['status'=>'error','message'=>'Invalid request']);

    exit;

}



/* ---------- EVENT ---------- */

$stmt = $conn->prepare(

    "SELECT event_id, event_team_pkid 

     FROM tb_events 

     WHERE event_url_code=?"

);

$stmt->bind_param("s", $code);

$stmt->execute();

$res = $stmt->get_result();



if ($res->num_rows === 0) {

    echo json_encode(['status'=>'error','message'=>'Invalid event code']);

    exit;

}



$event = $res->fetch_assoc();

$event_id = $event['event_id'];

$teamid   = $event['event_team_pkid'];



/* ---------- CHECK PIN EXISTS ---------- */

// 1. Search for the PIN globally first
$stmt = $conn->prepare(

    "SELECT id, user_name 

     FROM tb_event_user 

     WHERE event_code = ? 

       AND user_pin = ?"

);

$stmt->bind_param("is", $event_id, $pin);

$stmt->execute();

$res = $stmt->get_result();
// $_SESSION['user_id'] = $res->fetch_assoc()['id'];
// print_r($res); die;


if ($mode === 'check') {



    if ($res->num_rows > 0) {

        $user = $res->fetch_assoc();

        echo json_encode([

            'exists'   => true,

            'message'  => 'Welcome back '.$user['user_name'].' â€” great to see you again!',

            'redirect' => 'teaminstance_be.php?user_id='.$user['id'].'&code='.$code

        ]);

    } else {

        echo json_encode([

            'exists'  => false,

            'message' => 'Almost done! Please enter your name to complete the process.'

        ]);

    }

    exit;

}

/* ---------- EXISTING USER FINAL ---------- */

if ($res->num_rows > 0) {

    $row = $res->fetch_assoc();



    $_SESSION['user_id']   = $row['id'];

    $_SESSION['user_name'] = $row['user_name'];

    $_SESSION['event_id']  = $event_id;



    echo json_encode([

        'status'   => 'success',

        'redirect' => 'teaminstance_be.php?user_id='.$_SESSION['user_id'].'&code='.$code

    ]);

    exit;

}


/* ---------- NEW USER INSERT ---------- */

if ($uname === '') {

    echo json_encode(['status'=>'error','message'=>'Name required']);

    exit;

}



$ins = $conn->prepare(

    "INSERT INTO tb_event_user

     (team_id, event_code, user_name, user_pin)

     VALUES (?, ?, ?, ?)"

);

$ins->bind_param("iiss", $teamid, $event_id, $uname, $pin);



if ($ins->execute()) {



    $_SESSION['user_id']   = $ins->insert_id;

    $_SESSION['user_name'] = $uname;

    $_SESSION['event_id']  = $event_id;



    echo json_encode([

        'status'   => 'success',

        'redirect' => 'teaminstance_be.php?user_id='.$_SESSION['user_id'].'&code='.$code

    ]);

    exit;

}



echo json_encode(['status'=>'error','message'=>'Insert failed']);


/* ---------- New USER GAME STATUS ---------- */
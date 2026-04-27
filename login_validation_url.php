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

    "SELECT event_id, event_team_pkid, event_playstatus, event_max_participants

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

$maxParticipants = (int)($event['event_max_participants'] ?? 0);

/* ---------- EVENT STATUS GATE ---------- */
$eventStatus = (int)($event['event_playstatus'] ?? 1);
if ($eventStatus < 2) {
    echo json_encode(['status' => 'error', 'message' => 'This event is not yet available. Please check back later.']);
    exit;
}
/* ---------------------------------------- */

/* ---------- PARTICIPANT CAP HELPER ---------- */
// Returns true if the event is full (cap > 0 and current count >= cap)
function isEventFull($conn, $event_id, $maxParticipants) {
    if ($maxParticipants <= 0) return false; // 0 = unlimited
    $c = $conn->prepare("SELECT COUNT(*) AS cnt FROM tb_event_user WHERE event_code = ?");
    $c->bind_param("i", $event_id);
    $c->execute();
    $row = $c->get_result()->fetch_assoc();
    return ((int)$row['cnt']) >= $maxParticipants;
}
/* ---------------------------------------- */



/* ---------- CHECK PIN EXISTS ---------- */

// 1. Search for the PIN globally first
$stmt = $conn->prepare(

    "SELECT id, user_name, COALESCE(user_is_active, 1) AS user_is_active

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

        // Block inactive users even at the PIN-check stage
        if ((int)($user['user_is_active'] ?? 1) === 0) {
            echo json_encode([
                'exists'  => true,
                'blocked' => true,
                'message' => 'Your access to this event has been restricted by the moderator. Please contact your trainer.'
            ]);
            exit;
        }

        echo json_encode([

            'exists'   => true,

            'message'  => 'Welcome back '.$user['user_name'].' – great to see you again!',

            'redirect' => 'teaminstance_be.php?user_id='.$user['id'].'&code='.$code

        ]);

    } else {

        // New PIN — check participant cap BEFORE asking for their name
        if (isEventFull($conn, $event_id, $maxParticipants)) {
            echo json_encode([
                'exists'   => false,
                'full'     => true,
                'message'  => 'This event has reached its maximum number of participants. Please contact your trainer.'
            ]);
            exit;
        }

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

    // Block inactive users from logging in
    if ((int)($row['user_is_active'] ?? 1) === 0) {
        echo json_encode([
            'status'  => 'error',
            'message' => 'Your access to this event has been restricted by the moderator. Please contact your trainer.'
        ]);
        exit;
    }



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

// Hard cap check — double-check at insert time
if (isEventFull($conn, $event_id, $maxParticipants)) {
    echo json_encode([
        'status'  => 'error',
        'message' => 'This event has reached its maximum number of participants. Please contact your trainer.'
    ]);
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
<?php
session_start();
require "../include/dataconnect.php";

if (!isset($_SESSION['team_id'])) {
    header("Location: ../login.php");
    exit;
}
// Code By Maria to generate code
function generateCode($length = 6) {
    $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $code = '';

    for ($i = 0; $i < $length; $i++) {
        $code .= $chars[random_int(0, strlen($chars) - 1)];
    }

    return $code;
}
// end by maria
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $event_id  = intval($_POST['event_id'] ?? 0);

    $name       = trim($_POST['event_name'] ?? '');
    $desc       = trim($_POST['event_description'] ?? '');
    $start_date = $_POST['event_start_date'] ?? '';
    $validity   = intval($_POST['event_validity'] ?? 0);
    $passcode   = trim($_POST['event_passcode'] ?? 'saras');
    $status     = intval($_POST['event_playstatus'] ?? 1);

    if ($name === '' || $start_date === '' || $validity <= 0) {
        $errors[] = "Please fill all required fields";
    }

    /* Image */
    $image = $_POST['existing_image'] ?? 'default_event.jpeg';

    if (!empty($_FILES['event_coverimage']['name'])) {

        $targetDir = "../upload-images/events/";

        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0777, true);
        }

        $ext = strtolower(pathinfo($_FILES['event_coverimage']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg','jpeg','png','webp'];

        if (!in_array($ext, $allowed)) {
            $errors[] = "Invalid image type";
        } else {
            $image = uniqid("event_") . "." . $ext;

            move_uploaded_file(
                $_FILES['event_coverimage']['tmp_name'],
                $targetDir . $image
            );
        }
    }

    if (!$errors) {
        $code = generateCode();
        /* UPDATE EVENT */
        if ($event_id > 0) {

            $stmt = $conn->prepare("
                UPDATE tb_events SET
                    event_name = ?,
                    event_description = ?,
                    event_coverimage = ?,
                    event_start_date = ?,
                    event_validity = ?,
                    event_passcode = ?,
                    event_playstatus = ?
                WHERE event_id = ?
            ");

            $stmt->bind_param(
                "ssssisii",
                $name,
                $desc,
                $image,
                $start_date,
                $validity,
                $passcode,
                $status,
                $event_id
            );

            $stmt->execute();

        }
        else {

            /* INSERT EVENT */
            $stmt = $conn->prepare("
                INSERT INTO tb_events
                (event_team_pkid, event_name, event_description,
                 event_coverimage, event_start_date,
                 event_validity, event_passcode, event_playstatus,event_url_code)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");

            $stmt->bind_param(
                "issssisis",
                $_SESSION['team_id'],
                $name,
                $desc,
                $image,
                $start_date,
                $validity,
                $passcode,
                $status,
                $code
            );

            $stmt->execute();
            $event_id = $stmt->insert_id;
        }

        /* Go Step 2 */
        header("Location: add_modules.php?event_id=".$event_id);
        exit;
    }

    $_SESSION['event_errors'] = $errors;
    header("Location: create_event.php".($event_id ? "?event_id=".$event_id : ""));
    exit;
}

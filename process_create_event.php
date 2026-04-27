<?php

session_start();

require "include/coreDataconnect.php";



if (!isset($_SESSION['team_id'])) {

    header("Location: login.php");

    exit;
}

// Code By Maria to generate code

function generateCode($length = 6)
{

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

    /* ================= LAUNCH EVENT ================= */
    if (isset($_POST['launch_event'])) {

        $event_id = intval($_POST['event_id']);

        if (!$event_id) {
            die("Invalid event");
        }

        $stmt = $conn->prepare("
            UPDATE tb_events 
            SET event_playstatus = 2
            WHERE event_id = ? AND event_team_pkid = ?
        ");

        $stmt->bind_param("ii", $event_id, $_SESSION['team_id']);
        $stmt->execute();

        header("Location: review_modules.php?event_id=$event_id&success=1");
        exit;
    }

    /* ================= CREATE / UPDATE ================= */

    $event_id     = intval($_POST['event_id'] ?? 0);
    $name         = trim($_POST['event_name'] ?? '');
    $desc         = trim($_POST['event_description'] ?? '');
    $start_date   = $_POST['event_start_date'] ?? '';
    $validity     = intval($_POST['event_validity'] ?? 0);
    $passcode     = trim($_POST['event_passcode'] ?? '');
    $status       = intval($_POST['event_playstatus'] ?? 1);
    $max_p        = max(0, intval($_POST['event_max_participants'] ?? 0)); // 0 = unlimited

    if ($name === '' || $start_date === '' || $validity <= 0) {
        $errors[] = "Please fill all required fields";
    }

    /* IMAGE */
    $image = $_POST['existing_image'] ?? 'default_event.jpeg';

    if (!empty($_FILES['event_coverimage']['name'])) {

        $targetDir = __DIR__ . "/upload-images/events/";

        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0777, true);
        }

        $ext = strtolower(pathinfo($_FILES['event_coverimage']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];

        if (!in_array($ext, $allowed)) {
            $errors[] = "Invalid image type";
        } else {

            if ($_FILES['event_coverimage']['error'] !== 0) {
                $errors[] = "File upload error";
            } else {

                $image = uniqid("event_") . "." . $ext;
                $targetPath = $targetDir . $image;

                move_uploaded_file(
                    $_FILES['event_coverimage']['tmp_name'],
                    $targetPath
                );
            }
        }
    }

    if (!$errors) {

        $code = generateCode();

        if ($event_id > 0) {

            /* UPDATE */
            $stmt = $conn->prepare("
                UPDATE tb_events SET
                    event_name = ?,
                    event_description = ?,
                    event_coverimage = ?,
                    event_start_date = ?,
                    event_validity = ?,
                    event_passcode = ?,
                    event_playstatus = ?,
                    event_max_participants = ?
                WHERE event_id = ?
            ");

            $stmt->bind_param(
                "ssssisiii",
                $name,
                $desc,
                $image,
                $start_date,
                $validity,
                $passcode,
                $status,
                $max_p,
                $event_id
            );

            $stmt->execute();
        } else {

            /* INSERT */
            $stmt = $conn->prepare("
                INSERT INTO tb_events
                (event_team_pkid, event_name, event_description,
                 event_coverimage, event_start_date,
                 event_validity, event_passcode, event_playstatus, event_url_code, event_max_participants)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");

            $stmt->bind_param(
                "issssisisi",
                $_SESSION['team_id'],
                $name,
                $desc,
                $image,
                $start_date,
                $validity,
                $passcode,
                $status,
                $code,
                $max_p
            );

            $stmt->execute();
            $event_id = $stmt->insert_id;
        }

        header("Location: add_modules.php?event_id=" . $event_id);
        exit;
    }

    $_SESSION['event_errors'] = $errors;

    header("Location: create_event.php" . ($event_id ? "?event_id=" . $event_id : ""));
    exit;
}

<?php
include("../include/dataconnect.php");

$id = $_POST['id'];

$stmt = $conn->prepare("
UPDATE digisim_message
SET dm_read_status = 1
WHERE dm_id = ?
");

$stmt->bind_param("i",$id);
$stmt->execute();
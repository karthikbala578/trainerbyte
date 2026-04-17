<?php
session_start();
include("../include/dataconnect.php");

$simID = $_GET['sim_id'];

$sql = "
SELECT ch_id, ch_level, ch_image
FROM digisim_channels
WHERE ch_digisim_pkid = ?
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $simID);
$stmt->execute();

$res = $stmt->get_result();

$data = [];

while($row = $res->fetch_assoc()){
    $data[] = $row;
}

echo json_encode($data);
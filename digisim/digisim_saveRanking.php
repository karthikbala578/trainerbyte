<?php
// Get the JSON contents from the fetch request
$json = file_get_contents('php://input');
$data = json_decode($json, true);

if ($data) {
    $highItems = $data['high']; // Array of high priority cards
    $mediumItems = $data['medium'];
    $lowItems = $data['low'];

    // Example: Save to database
    foreach($highItems as $item) {
        $id = $item['id'];
        // UPDATE user_score tabe for store sumary ans game status as completed...
    }
    
    echo json_encode(["status" => "success"]);
}
?>
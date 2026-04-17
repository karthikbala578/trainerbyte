<?php



// 1. CONFIGURATION & ERROR HANDLING

ini_set('display_errors', 0); 

ini_set('max_execution_time', 300); // 5 minutes max

ini_set('memory_limit', '512M');

error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);



session_start();

header("Content-Type: application/json");



// 2. DEPENDENCIES

require "../include/dataconnect.php";

require "../config.php";

require "../openai_call.php";



// 3. INPUT VALIDATION

$team_id = $_SESSION['team_id'] ?? 0;

$input   = json_decode(file_get_contents("php://input"), true);

$ui_id   = intval($input['ui_id'] ?? 0);



if (!$team_id || !$ui_id) {

    echo json_encode(["status" => "error", "message" => "Invalid request: Missing Team ID or UI ID"]);

    exit;

}



// 4. FETCH USER INPUT DRAFT

$stmt = $conn->prepare("

    SELECT *

    FROM byteguess_user_input

    WHERE ui_id = ?

      AND ui_team_pkid = ?

");

$stmt->bind_param("ii", $ui_id, $team_id);

$stmt->execute();

$ui = $stmt->get_result()->fetch_assoc();



if (!$ui) {

    echo json_encode(["status" => "error", "message" => "Draft not found"]);

    exit;

}



// 5. FETCH PROMPTS

$pstmt = $conn->prepare("SELECT * FROM byteguess_prompts LIMIT 1");

$pstmt->execute();

$prompts = $pstmt->get_result()->fetch_assoc();



if (!$prompts) {

    echo json_encode(["status" => "error", "message" => "Prompts not found"]);

    exit;

}



// HELPER: Template Filler

function fill_prompt($tpl, $vars) {

    foreach ($vars as $k => $v) {

        $val = is_array($v) ? json_encode($v) : $v;

        $tpl = str_replace('{{' . $k . '}}', $val, $tpl);

    }

    return $tpl;

}



$common = [

    'ui_total_cards'    => $ui['ui_total_cards'],

    'ui_cards_drawn'    => $ui['ui_cards_drawn'],

    'ui_training_topic' => $ui['ui_training_topic'],

    'ui_industry'       => $ui['ui_industry'],

    'ui_objective'      => $ui['ui_objective'],

    'ui_hypothesis'     => $ui['ui_hypothesis'],

    'ui_card_structure' => $ui['ui_card_structure']

];



// 6. GET CATEGORY ID (CRITICAL FIX)

$cat_stmt = $conn->prepare("

    SELECT * FROM tb_team WHERE team_id = ? LIMIT 1

");

$cat_stmt->bind_param("i", $team_id);

$cat_stmt->execute();

$cat_result = $cat_stmt->get_result()->fetch_assoc();

$targetId = $cat_result['team_id'] ?? null;



if (!$targetId) {

    echo json_encode(["status" => "error", "message" => "No Category found for this Team."]);

    exit;

}



// 7. CREATE CARD GROUP

$stmt = $conn->prepare("

    INSERT INTO card_group

    (cg_name, cg_description, cg_max, byteguess_pkid, cg_status)

    VALUES (?, ?, ?, ?, 1)

");



$stmt->bind_param(

    "ssii",

    $ui['ui_game_name'],

    $ui['ui_game_description'],

    $ui['ui_cards_drawn'],

    $targetId

);



if (!$stmt->execute()) {

    echo json_encode(["status" => "error", "message" => "DB Error: " . $stmt->error]);

    exit;

}

$cg_id = $stmt->insert_id;





// --- START AI MESSAGE CHAIN ---



$messages = [];



/* PROMPT 2: INITIAL SETUP */

$messages[] = [

    "role" => "user",

    "content" => fill_prompt($prompts['step1_setup'], $common)

];

$messages[] = [

    "role" => "assistant",

    "content" => callOpenAI($messages)

];



/* PROMPT 3: SCENARIO */

$messages[] = [

    "role" => "user",

    "content" => fill_prompt($prompts['step2_scenario'], $common)

];

$messages[] = [

    "role" => "assistant",

    "content" => callOpenAI($messages)

];



/* PROMPT 4: CARDS */

$messages[] = [

    "role" => "user",

    "content" => fill_prompt($prompts['step3_cards'], $common)

];



$cardsRaw = callOpenAI($messages);

$messages[] = ["role" => "assistant", "content" => $cardsRaw];



// --- PARSE & INSERT CARDS ---



$blocks = preg_split('/\*\*Card\s+\d+:/i', $cardsRaw);

array_shift($blocks); 



$stmt = $conn->prepare("

    INSERT INTO card_unit

    (cu_card_group_pkid, cu_sequence, cu_name, cu_image, cu_description, cu_status)

    VALUES (?, ?, ?, 'cu_image.jpeg', ?, 1)

");



$seq = 1;

foreach ($blocks as $block) {

    $lines = preg_split("/\R/", trim($block));

    $title = trim(str_replace("**", "", array_shift($lines)));

    $text  = trim(implode("\n", $lines));



    if ($title && $text) {

        $stmt->bind_param("iiss", $cg_id, $seq, $title, $text);

        $stmt->execute();

        $seq++;

    }

}



//   PROMPT 5: OPTIONS //





$opts = json_decode($ui['ui_options'], true);



$optVars = array_merge($common, [

    'opt_full'    => $opts['full'],

    'opt_partial' => $opts['partial'],

    'opt_wrong'   => $opts['wrong']

]);



$messages[] = [

    "role" => "user",

    "content" => fill_prompt($prompts['step4_options'], $optVars)

];



$optionsRaw = callOpenAI($messages);

$messages[] = ["role"=>"assistant","content"=>$optionsRaw];



// PARSE OPTIONS //

preg_match_all(

    '/<!--type:(full|partial|wrong)-->\s*\*\*Option\s+\d+:\s*(.*?)\*\*\s*(.*?)(?=\n<!--type:|\z)/s',

    $optionsRaw,

    $matches,

    PREG_SET_ORDER

);



$answers = [];

$order = 1;



foreach ($matches as $m) {

    $answers[] = [

        "order"    => $order++,

        "title"    => trim($m[2]),

        "answer"   => trim($m[3]),

        "ans_type" => $m[1],

        "score"    => 0

    ];

}



$stmt = $conn->prepare("

    UPDATE card_group

    SET cg_answer = ?

    WHERE cg_id = ?

");

$stmt->bind_param("si", json_encode($answers,JSON_UNESCAPED_UNICODE), $cg_id);

$stmt->execute();



// PROMPT 6: ANSWER KEY //



$messages[] = [

    "role" => "user",

    "content" => $prompts['step5_answer_key']

];

$answerKey = callOpenAI($messages);



$stmt = $conn->prepare("

    UPDATE card_group

    SET cg_result = ?

    WHERE cg_id = ?

");

$stmt->bind_param("si",$answerKey,$cg_id);

$stmt->execute();



// CLUES //



$num_clues = intval($ui['ui_clue'] ?? 0);



if ($num_clues > 0) {



    $messages[] = [

        "role" => "user",

        "content" => fill_prompt(

            $prompts['step6_clues'],

            ['num_clues'=>$num_clues]

        )

    ];



    $cluesRaw = callOpenAI($messages);



    $lines = array_slice(

        array_filter(array_map('trim',preg_split("/\R/",$cluesRaw))),

        0,

        $num_clues

    );



    $finalClues = [];

    foreach ($lines as $i=>$text) {

        $finalClues[] = [

            "legend" => chr(65+$i),

            "score"  => "0",

            "clue"   => preg_replace('/^\d+[\.\)\s]+|[*•-]\s+/','',$text),

            "order"  => $i+1

        ];

    }



    $stmt = $conn->prepare("

        UPDATE card_group

        SET cg_clue = ?

        WHERE cg_id = ?

    ");

    $stmt->bind_param("si",json_encode($finalClues,JSON_UNESCAPED_UNICODE),$cg_id);

    $stmt->execute();

}



//   FINALIZE //



$stmt = $conn->prepare("

    UPDATE byteguess_user_input

    SET ui_cur_step = 6

    WHERE ui_id = ?

");

$stmt->bind_param("i",$ui_id);

$stmt->execute();



// --- START NEW STEP 7: GUIDELINES ---



if (isset($prompts['step7_guidelines'])) {

    $messages[] = [

        "role" => "user",

        "content" => $prompts['step7_guidelines']

    ];



    $guidelines = callOpenAI($messages);



    $stmt = $conn->prepare("

        UPDATE card_group 

        SET cg_guidelines = ? 

        WHERE cg_id = ?

    ");

    $stmt->bind_param("si", $guidelines, $cg_id);

    $stmt->execute();

}



// --- END NEW STEP 7: GUIDELINES ---



//  FINALIZE //

$stmt = $conn->prepare("

    UPDATE byteguess_user_input

    SET ui_cur_step = 7 

    WHERE ui_id = ?

");





echo json_encode([

    "status"=>"success",

    "cg_id"=>$cg_id

]);


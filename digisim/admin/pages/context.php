<?php

// 1. Start session and connect
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../include/dataconnect.php';

// 2. Identify if we are editing an existing record or creating a new one
$simId = isset($_GET['sim_id']) ? intval($_GET['sim_id']) : 0;
$scenario = '';
$objectives = '';
$errors = [];

// 3. HANDLE POST (Form Submission)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $scenario = $_POST['scenario'] ?? '';
    $objectives = $_POST['objectives'] ?? '';

    if (empty($scenario)) $errors['scenario'] = 'Scenario is required';
    if (empty($objectives)) $errors['objectives'] = 'Objectives are required';
    
    // Check if we have the prerequisite data from Page 1
    // if (!isset($_SESSION['step1_data']) && $simId == 0) {
    //     header("Location: page-container.php?step=1");
    //     exit;
    // }
    
    // print_r($_SESSION['step1_data']);
    if (empty($errors)) {
    // $s1 = $_SESSION['step1_data'] ?? [];

    if ($simId > 0) {
        // UPDATE existing record (If they came from Step 3 back to Step 2)
        $stmt = $conn->prepare("UPDATE mg5_digisim_userinput SET 
            ui_scenario=?, 
            ui_objective=?, 
            ui_cur_step=2 
            WHERE ui_id=? AND ui_team_pkid=?");
        
        $stmt->bind_param('ssii', $scenario, $objectives, $simId, $_SESSION['team_id']);
    } 
    // else {
    //     // THE BIG INSERT (Combining Page 1 + Page 2)
    //     // Count: 10 Columns total
    //     $stmt = $conn->prepare("INSERT INTO mg5_digisim_userinput (
    //         ui_team_pkid, ui_sim_title, ui_sim_desc, ui_industry_type, 
    //         ui_geography, ui_operating_scale, ui_lang, ui_scenario, 
    //         ui_objective, ui_cur_step
    //     ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 2)");

    //     // Bind String: i (int) followed by 8 s (strings)
    //     $stmt->bind_param(
    //         'issssssss', 
    //         $_SESSION['team_id'], 
    //         $s1['sim_title'], 
    //         $s1['sim_desc'], 
    //         $s1['industry_type'], 
    //         $s1['geography'], 
    //         $s1['operating_scale'], 
    //         $s1['language'], 
    //         $scenario, 
    //         $objectives
    //     );
    // }

    if ($stmt->execute()) {
        $finalId = ($simId > 0) ? $simId : $stmt->insert_id;
        // Keep the session until you're sure they don't want to go back to Step 1
        // or unset($_SESSION['step1_data']); 
        header("Location: page-container.php?step=3&sim_id=" . $finalId);
        exit;
    } else {
        // DEBUG: If it still fails, this will tell you WHY
        die("Database Error: " . $stmt->error);
    }
}
} 
// 4. HANDLE GET (Loading data to show in textareas)
else if ($simId > 0) {
    // Load from DB if record exists
    $loadStmt = $conn->prepare("SELECT ui_scenario, ui_objective FROM mg5_digisim_userinput WHERE ui_id=? AND ui_team_pkid=? LIMIT 1");
    $loadStmt->bind_param("ii", $simId, $_SESSION['team_id']);
    $loadStmt->execute();
    $res = $loadStmt->get_result();
    if ($row = $res->fetch_assoc()) {
        $scenario = $row['ui_scenario'];
        $objectives = $row['ui_objective'];
    }
    $loadStmt->close();
}

// if (session_status() === PHP_SESSION_NONE) {
//     session_start();
// }

// require_once __DIR__ . '/../include/dataconnect.php';

// // If someone tries to access Page 2 without doing Page 1, send them back
// if (!isset($_SESSION['step1_data'])) {
//     header("Location: page-container.php?step=1");
//     exit;
// }

// $simId = isset($_GET['sim_id']) ? intval($_GET['sim_id']) : 0;
// $pageTitle = 'Simulation Setup - Step 2';
// $pageCSS = '/pages/page-styles/simulation_setup.css';

// $scenario = '';
// $objectives = '';
// $errors = [];

// if ($_SERVER['REQUEST_METHOD'] === 'POST') {
//     $scenario = $_POST['scenario'] ?? '';
//     $objectives = $_POST['objectives'] ?? '';

//     if (empty($scenario)) $errors['scenario'] = 'Scenario is required';
//     if (empty($objectives)) $errors['objectives'] = 'Objectives are required';

//     if (empty($errors)) {
//         $s1 = $_SESSION['step1_data'];

//         if ($simId > 0) {
//             // UPDATE existing record
//             $stmt = $conn->prepare("UPDATE mg5_digisim_userinput SET 
//                 ui_sim_title=?, ui_sim_desc=?, ui_industry_type=?, ui_geography=?, 
//                 ui_operating_scale=?, ui_lang=?, ui_scenario=?, ui_objective=?, ui_cur_step=2 
//                 WHERE ui_id=? AND ui_team_pkid=?");
//             $stmt->bind_param('ssssssssii', 
//                 $s1['sim_title'], $s1['sim_desc'], $s1['industry_type'], $s1['geography'],
//                 $s1['operating_scale'], $s1['language'], $scenario, $objectives, 
//                 $simId, $_SESSION['team_id']);
//         } else {
//             // FINAL INSERT (Combines Page 1 + Page 2)
//             $stmt = $conn->prepare("INSERT INTO mg5_digisim_userinput (
//                 ui_team_pkid, ui_sim_title, ui_sim_desc, ui_industry_type, 
//                 ui_geography, ui_operating_scale, ui_lang, ui_scenario, ui_objective, ui_cur_step
//             ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 2)");
//             $stmt->bind_param('issssssss', 
//                 $_SESSION['team_id'], $s1['sim_title'], $s1['sim_desc'], $s1['industry_type'], 
//                 $s1['geography'], $s1['operating_scale'], $s1['language'], $scenario, $objectives);
//         }

//         if ($stmt->execute()) {
//             $finalId = ($simId > 0) ? $simId : $stmt->insert_id;
//             // Clear Step 1 session data now that it is safely in the DB
//             unset($_SESSION['step1_data']);
//             header("Location: page-container.php?step=3&sim_id=" . $finalId);
//             exit;
//         }
//     }
// }else if ($simId > 0 && $_SERVER['REQUEST_METHOD'] !== 'POST') {



//         $loadStmt = $conn->prepare("

//         SELECT * FROM mg5_digisim_userinput

//         WHERE ui_id=? AND ui_team_pkid=? LIMIT 1");



//         $loadStmt->bind_param("ii", $simId, $_SESSION['team_id']);

//         $loadStmt->execute();



//         $result = $loadStmt->get_result();



//         if ($result->num_rows > 0) {



//             $row = $result->fetch_assoc();



//             $simTitle = $row['ui_sim_title'];

//             $simDesc = $row['ui_sim_desc'];

//             $industryType = $row['ui_industry_type'];

//             $geography = $row['ui_geography'];

//             $operatingScale = $row['ui_operating_scale'];

//             $language = $row['ui_lang'];

//             $scenario = $row['ui_scenario'];

//             $objectives = $row['ui_objective'];

//         }



//         $loadStmt->close();

//     }
?>

<!DOCTYPE html>

<html lang="en"><head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<title>Industrial Atelier - New Setup</title>
<link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&amp;family=Inter:wght@400;500;600&amp;display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" rel="stylesheet"/>
<style>
        /* CSS Variables for Theme Colors */
        :root {
            --primary: #3a6095;
            --primary-container: #9ec2fe;
            --on-primary: #f8f8ff;
            --on-primary-container: #0f3d70;
            --secondary: #466370;
            --background: #f7f9fb;
            --surface: #f7f9fb;
            --surface-container: #eaeff2;
            --surface-container-low: #f0f4f7;
            --surface-container-lowest: #ffffff;
            --on-surface: #2c3437;
            --on-surface-variant: #596064;
            --outline: #747c80;
            --outline-variant: #acb3b7;
            --font-headline: 'Manrope', sans-serif;
            --font-body: 'Inter', sans-serif;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: var(--font-body);
            background-color: var(--background);
            color: var(--on-surface);
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }

        /* Material Symbols Utility */
        .material-symbols-outlined {
            font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
            vertical-align: middle;
            font-size: 24px;
        }

        .icon-fill {
            font-variation-settings: 'FILL' 1;
        }

        .gradient-btn {
            width: 100%;
            background: linear-gradient(135deg, #3a6095 0%, #9ec2fe 100%);
            color: white;
            padding: 0.75rem;
            border: none;
            border-radius: 0.75rem;
            font-weight: 700;
            font-size: 0.875rem;
            cursor: pointer;
            box-shadow: 0 1px 2px rgba(0,0,0,0.05);
            transition: opacity 0.2s, box-shadow 0.2s;
        }

        .gradient-btn:hover {
            opacity: 0.9;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }

        .footer-links {
            margin-top: 1.5rem;
            padding-top: 1.5rem;
            border-top: 1px solid var(--outline-variant);
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
        }

        .footer-link {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.5rem 1.5rem;
            text-decoration: none;
            color: var(--on-surface-variant);
            font-size: 0.875rem;
            transition: color 0.2s;
        }

        .footer-link:hover {
            color: var(--primary);
        }

        /* Main Content */
        .main-content {
            /* margin-left: 256px; */
            padding: .5rem 3rem 1rem 3rem;
            /* max-width: 1440px; */
            
        }

        .content-grid {
            display: flex;
            flex-direction: column;
            gap: 2rem;
        }

        @media (min-width: 1024px) {
            .content-grid {
                flex-direction: row; /* Side-by-side on desktop */
                align-items: flex-start;
            }

            /* Left Column - 40% */
            .editorial-col {
                flex: 0 0 35%; 
                max-width: 35%;
            }
            .form-col {
                flex: 0 0 65%;
                max-width: 65%;
            }
        }

        /* Left Column */
        .editorial-col {
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .hero-image-container {
            position: relative;
            overflow: hidden;
            border-radius: 20px;
            aspect-ratio: 16 / 9;
        }

        .hero-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
            filter: grayscale(20%) contrast(110%);
        }

        .image-overlay {
            position: absolute;
            inset: 0;
            background-color: var(--primary);
            opacity: 0.1;
            mix-blend-mode: multiply;
        }

        .editorial-text {
            max-width: 36rem;
        }

        .editorial-text h1 {
            font-size: 1.5rem;
            font-weight: 800;
            color: var(--on-surface);
            letter-spacing: -0.025em;
            line-height: 1.15;
            margin-bottom: 1rem;
        }

        .editorial-text p {
            font-size: 1.125rem;
            color: var(--on-surface-variant);
            line-height: 1.625;
        }

        .editorial-info {
            max-width: 36rem;
        }

        .editorial-info h2 {
            font-size: 1.2rem;
            font-weight: 800;
            color: var(--on-surface);
            letter-spacing: -0.025em;
            line-height: 1.15;
            margin-bottom: 1rem;
        }

        .editorial-info p {
            font-size: 1rem;
            color: var(--on-surface-variant);
            line-height: 1.625;
        }

        /* Right Column / Form */
        .form-col {
            flex: 1;
            width: 100%;
        }

        @media (min-width: 1024px) {
            .form-col {
                position: sticky;
            }
        }


        /* Ensure the sticky behavior works with the scrollable card */
        @media (min-width: 1024px) {
            .form-col {
                position: sticky;
                top: 6rem; /* Matches your main-content padding-top */
            }
        }

        .form-stack {
            display: flex;
            flex-direction: column;
            gap: 1.1rem;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .label-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .field-label {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.875rem;
            font-weight: 600;
            color: var(--on-surface-variant);
        }

        .field-label .material-symbols-outlined {
            font-size: 0.75rem;
            color: var(--secondary);
        }

        /* .info-btn {
            background: none;
            border: none;
            color: var(--outline);
            cursor: pointer;
            font-size: 0.875rem;
        } */

        /* Remove scroll and switch to grid */
        .form-card {
            background-color: var(--surface-container-low);
            padding: 2.5rem;
            border-radius: 2rem;
            box-shadow: 0 1px 2px rgba(0,0,0,0.05);
            max-height: none; /* Remove height limit */
            overflow: visible; /* Ensure tooltips aren't cut off */
        }

        .form-stack {
            display: grid;
            grid-template-columns: 1fr 1fr; /* Two equal columns */
            gap: 1.5rem 2rem;
        }

        /* Make wide fields (like Title and Scale) span both columns */
        .full-width {
            grid-column: span 2;
        }

        /* Tooltip Content Box - Positioned to the LEFT of the icon */
        .tooltip-content {
            visibility: hidden;
            opacity: 0;
            position: absolute;
            top: 50%;
            transform: translateY(-50%); 
            
            /* Position to the left of the 'i' icon */
            right: calc(100% + 12px); 
            left: auto; 
            
            width: 280px; 
            background-color: rgb(173 172 172 / 95%);
            color: #000;
            padding: 12px 16px;
            border-radius: 8px;
            font-size: 0.75rem;
            line-height: 1.5;
            box-shadow: 0 8px 24px rgba(0,0,0,0.2);
            z-index: 100;
            transition: opacity 0.3s, visibility 0.3s;
            pointer-events: none;
            white-space: nowrap; /* Prevents weird wrapping for the list */
        }

        /* Tooltip Triangle Arrow - Pointing Right towards the icon */
        .tooltip-content::after {
            content: "";
            position: absolute;
            top: 50%;
            left: 100%; /* Arrow on the right edge of the tooltip box */
            transform: translateY(-50%);
            border-width: 6px;
            border-style: solid;
            border-color: transparent transparent transparent var(--on-surface);
        }

        /* Show on Hover */
        .info-tooltip-wrapper:hover .tooltip-content {
            visibility: visible;
            opacity: 1;
        }
        
        /* Tooltip Wrapper */
        .info-tooltip-wrapper {
            position: relative;
            display: inline-flex;
            align-items: center;
            cursor: help;
        }

        /* The "i" Circle */
        .info-icon {
            width: 18px;
            height: 18px;
            background-color: var(--outline-variant);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-family: serif; /* Classic 'i' look */
            font-weight: bold;
            transition: background-color 0.2s;
        }

        /* .info-tooltip-wrapper:hover .info-icon {
            background-color: var(--primary);
        } */

        .text-input, .select-input, .textarea-input {
            width: 100%;
            background-color: var(--surface-container-lowest);
            border: none;
            border-radius: 0.75rem;
            padding: 1rem 1.25rem;
            color: var(--on-surface);
            box-shadow: 0 1px 2px rgba(0,0,0,0.05);
            font-family: inherit;
            font-size: 1rem;
            transition: ring 0.2s;
        }

        .text-input::placeholder, .textarea-input::placeholder {
            color: var(--outline-variant);
        }

        .text-input:focus, .select-input:focus, .textarea-input:focus {
            outline: 2px solid var(--primary-container);
            outline-offset: -2px;
        }

        .select-wrapper {
            position: relative;
        }

        .select-input {
            appearance: none;
            cursor: pointer;
        }

        .select-arrow {
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
            pointer-events: none;
            color: var(--outline);
        }

        .textarea-input {
            resize: none;
            height: 170px;
        }

        
    </style>
</head>
<body>

<?php include 'stepper.php'; ?>
<main class="main-content">
    <div class="content-grid">
        <!-- Left Side -->
        <div class="editorial-col">
            <div class="hero-image-container">
                <img alt="Industrial assembly line" class="hero-image" src="images/context.png"/>
                <div class="image-overlay"></div>
            </div>
            <div class="editorial-text">
                <h1>Help me with a brief about scenario you have in mind</h1>
                <p>Define the narrative framework that will drive the simulation logic and user interactions.</p>
            </div>
            <div class="editorial-info">
                <h2>Share the simulation objective you prefer to achieve</h2>
                <p>Your input is the fuel! High-quality details here defines a classy simulation later. If you want an epic outcome, give us a killer script.</p>
            </div>

        </div>
        <!-- Right Side Form -->
        <div class="form-col">
            <div class="form-card">
                <form class="form-stack" method="POST" id="simForm2">     

                    <div class="form-group full-width">
                        <div class="label-row">
                            <label class="field-label">
                                <span class="material-symbols-outlined">description</span>
                                Scenario
                            </label>
                            <div class="info-tooltip-wrapper">
                                <span class="info-icon">i</span>
                                <div class="tooltip-content">
                                    Construct a comprehensive narrative that <br>bridges the general context with specific<br> situational details. To maximize realism, <br>include "twists" or complicating factors—such<br> as pending deadlines, regulatory shifts, or <br>resource constraints. This ensures the<br> simulation logic remains consistent with your<br> intended exercise outcomes and the exact<br> pressures being tested.<br><br>

                                    Illustration: <br><br>

                                    As the organization prepares for monsoon<br> season, we must test our recovery capability <br>during a 5–7 day disruption affecting two<br> coastal facilities housing 5,000 employees. <br>The Twist: The disruption coincides with the<br> scheduled production release for three <br>high-impact client contracts, creating a direct <br>conflict between safety protocols and critical<br> delivery SLAs.<br><br>

                                    This scenario simulates a sophisticated data<br> fraud scheme discovered within our financial<br> processing hub. The exercise tests forensic<br> protocols and the integrity of 5,000 sensitive<br> accounts over a one-week investigation. The <br>Twist: The regulator has recently intensified<br> governance requirements and increased<br> non-compliance penalties, significantly raising<br> the stakes for any delay in reporting or<br> mitigation.<br><br>

                                    This simulation evaluates technology recovery<br> during a wide-scale ransomware attack<br> affecting coastal data centers and 5,000 staff<br> members. We will test network isolation and<br> business continuity over a 7-day period of<br> restricted access. The Twist: A key third-party<br> security vendor is currently undergoing a <br>system upgrade, limiting our external support<br> options and forcing a reliance on internal <br>legacy protocols.


                                </div>
                            </div>
                        </div>
                        <!-- <textarea class="textarea-input"  placeholder="Scale and conditions..." rows="3"></textarea> -->
                        <!-- <textarea class="textarea-input" name="scenario" placeholder="Provide a 3–5 sentence narrative bridging context with specific situational details and twists to add realism. Refer to tooltip for illustrations."
                            rows="3"><?php //htmlspecialchars($scenario) ?></textarea> -->
                        <textarea class="textarea-input" name="scenario" 
                            placeholder="Provide a 3–5 sentence narrative bridging context with specific situational details and twists to add realism. Refer to tooltip for illustrations."
                            rows="3" ><?= htmlspecialchars($scenario ?? '') ?></textarea>
                        
                    </div>

                    <div class="form-group full-width">
                        <div class="label-row">
                            <label class="field-label">
                                <span class="material-symbols-outlined">description</span>
                                Objectives
                            </label>
                            <div class="info-tooltip-wrapper">
                                <span class="info-icon">i</span>
                                <div class="tooltip-content">
                                    Provide an explicit narration of the<br> specific capabilities being tested. <br>Clearly define whether the focus is on initial <br>preparation, executive decision-making, <br>or the recovery phase. This ensures<br> the simulation generates tasks that directly <br>measure participant readiness in your <br>targeted areas.<br><br>

                                    Illustration: <br><br>

                                    The objective is to evaluate the readiness <br>of people, processes, and technology<br> in sustaining a 7-day regional outage. <br>This exercise will specifically test the validity<br> of Business Continuity (BC) documentation, <br>legal compliance during a crisis, management <br>of time-sensitive contracts, and the impact of <br>environmental constraints on resource <br>deployment.<br><br>

                                    The objective is to test the organization’s<br> internal investigative and communication <br>protocols following the discovery of systematic<br> fraud. Participants will be exercised on their <br>ability to isolate compromised data, manage <br>regulatory reporting requirements, and <br>navigate the legal implications of an internal <br>breach while maintaining stakeholder trust.<br><br>

                                    The objective is to assess the coordination<br> between technical recovery teams and <br>executive leadership during a ransomware <br>event. This simulation focuses on the decision-<br>making process regarding system failovers, <br>the effectiveness of the disaster recovery(DR)<br> timeline, and the ability to manage external <br>communications under the pressure of <br>restricted data access.

                                </div>
                            </div>
                        </div>
                        <!-- <textarea class="textarea-input"  placeholder="Scale and conditions..." rows="3"></textarea> -->
                        <!-- <textarea class="textarea-input" name="objectives" placeholder="State the specific goals of this simulation (e.g., test recovery protocols or decision-making). Aim for 2–4 sentences; see tooltip for detailed illustrations." 
                             rows="3"><?php //htmlspecialchars($objectives) ?></textarea> -->
                        <textarea class="textarea-input" name="objectives" 
                            placeholder="State the specific goals of this simulation (e.g., test recovery protocols or decision-making). Aim for 2–4 sentences; see tooltip for detailed illustrations." 
                            rows="3"><?= htmlspecialchars($objectives ?? '') ?></textarea>
                        
                    </div>

                </form>
            </div>
        </div>
    </div>
</main>

</body></html>
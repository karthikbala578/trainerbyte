<?php
    $simId = isset($_GET['sim_id']) ? intval($_GET['sim_id']) : 0;

    $pageTitle = 'Simulation Setup';
    $pageCSS = '/pages/page-styles/simulation_setup.css';

    require_once __DIR__ . '/../include/dataconnect.php';

    $simTitle = $simDesc = $industryType = $geography = $operatingScale = $language = '';
    // Check if session data exists from a previous entry
    // $s1 = $_SESSION['step1_data'] ?? [];

    // // Initialize variables: Priority 1: Session, Priority 2: Empty String
    // $simTitle       = $s1['sim_title'] ?? '';
    // $simDesc        = $s1['sim_desc'] ?? '';
    // $industryType   = $s1['industry_type'] ?? '';
    // $geography      = $s1['geography'] ?? '';
    // $operatingScale = $s1['operating_scale'] ?? '';
    // $language       = $s1['language'] ?? '';
    $errors = [];

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {

        if (empty($_POST['sim_title'])) {
            $errors['sim_title'] = 'Simulation title is required';
        } else {
            $simTitle = htmlspecialchars($_POST['sim_title']);
        }

        $simDesc = !empty($_POST['sim_desc']) ? htmlspecialchars($_POST['sim_desc']) : '';

        if (empty($_POST['industry_type'])) {
            $errors['industry_type'] = 'Industry type is required';
        } else {
            $industryType = htmlspecialchars($_POST['industry_type']);
        }

        $geography = !empty($_POST['geography']) ? htmlspecialchars($_POST['geography']) : '';
        $operatingScale = !empty($_POST['operating_scale']) ? htmlspecialchars($_POST['operating_scale']) : '';

        if (empty($_POST['language'])) {
            $errors['language'] = 'Language is required';
        } else {
            $language = htmlspecialchars($_POST['language']);
        }

        // $scenario = !empty($_POST['scenario']) ? $_POST['scenario'] : '';
        // $objectives = !empty($_POST['objectives']) ? $_POST['objectives'] : '';
    // if (!empty($errors)) {
    //     // This will tell you EXACTLY which field is stopping the redirect
    //     echo "<pre>"; print_r($errors); echo "</pre>"; die();
    // }
        if (empty($errors)) {

            if ($simId > 0) {

                $updateStmt = $conn->prepare("
                UPDATE mg5_digisim_userinput 
                SET 
                ui_sim_title=?,
                ui_sim_desc=?,
                ui_industry_type=?,
                ui_geography=?,
                ui_operating_scale=?,
                ui_lang=?,
                ui_cur_step=1
                WHERE ui_id=? AND ui_team_pkid=?");

                $updateStmt->bind_param(
                    'ssssssii',
                    $simTitle,
                    $simDesc,
                    $industryType,
                    $geography,
                    $operatingScale,
                    $language,
                    $simId,
                    $_SESSION['team_id']
                );

                $updateStmt->execute();
                $updateStmt->close();
            } else {

                $insertStmt = $conn->prepare("
                INSERT INTO mg5_digisim_userinput(
                ui_team_pkid,ui_sim_title,ui_sim_desc,
                ui_industry_type,ui_geography,
                ui_operating_scale,ui_lang,
                ui_cur_step
                ) VALUES (?,?,?,?,?,?,?,1)");

                $insertStmt->bind_param(
                    'issssss',
                    $_SESSION['team_id'],
                    $simTitle,
                    $simDesc,
                    $industryType,
                    $geography,
                    $operatingScale,
                    $language,
                );

                $insertStmt->execute();
                $simId = $insertStmt->insert_id;
                $insertStmt->close();
            }

            header("Location: page-container.php?step=2&sim_id=" . $simId);
            exit;
        }
    } else if ($simId > 0 && $_SERVER['REQUEST_METHOD'] !== 'POST') {

        $loadStmt = $conn->prepare("
        SELECT * FROM mg5_digisim_userinput
        WHERE ui_id=? AND ui_team_pkid=? LIMIT 1");

        $loadStmt->bind_param("ii", $simId, $_SESSION['team_id']);
        $loadStmt->execute();

        $result = $loadStmt->get_result();

        if ($result->num_rows > 0) {

            $row = $result->fetch_assoc();

            $simTitle = $row['ui_sim_title'];
            $simDesc = $row['ui_sim_desc'];
            $industryType = $row['ui_industry_type'];
            $geography = $row['ui_geography'];
            $operatingScale = $row['ui_operating_scale'];
            $language = $row['ui_lang'];
        }

        $loadStmt->close();
    }
//     if (session_status() === PHP_SESSION_NONE) {
//     session_start();
// }

// require_once __DIR__ . '/../include/dataconnect.php';

// $simId = isset($_GET['sim_id']) ? intval($_GET['sim_id']) : 0;
// $pageTitle = 'Simulation Setup';
// $pageCSS = '/pages/page-styles/simulation_setup.css';

// // Initialize variables with Session data (if user clicked 'Back' from Step 2)
// $simTitle = $_SESSION['step1_data']['sim_title'] ?? '';
// $simDesc = $_SESSION['step1_data']['sim_desc'] ?? '';
// $industryType = $_SESSION['step1_data']['industry_type'] ?? '';
// $geography = $_SESSION['step1_data']['geography'] ?? '';
// $operatingScale = $_SESSION['step1_data']['operating_scale'] ?? '';
// $language = $_SESSION['step1_data']['language'] ?? '';

// $errors = [];

// if ($_SERVER['REQUEST_METHOD'] === 'POST') {
//     // 1. Validation Logic
//     if (empty($_POST['sim_title'])) {
//         $errors['sim_title'] = 'Simulation title is required';
//     }
//     if (empty($_POST['industry_type'])) {
//         $errors['industry_type'] = 'Industry type is required';
//     }
//     if (empty($_POST['language'])) {
//         $errors['language'] = 'Language is required';
//     }

//     if (empty($errors)) {
//         // 2. STORE DATA IN SESSION INSTEAD OF DATABASE
//         $_SESSION['step1_data'] = [
//             'sim_title'       => htmlspecialchars($_POST['sim_title']),
//             'sim_desc'        => htmlspecialchars($_POST['sim_desc']),
//             'industry_type'   => htmlspecialchars($_POST['industry_type']),
//             'geography'       => htmlspecialchars($_POST['geography']),
//             'operating_scale' => htmlspecialchars($_POST['operating_scale']),
//             'language'        => htmlspecialchars($_POST['language'])
//         ];

//         // 3. Move to Page 2 (No sim_id yet because nothing is in the DB)
//         header("Location: page-container.php?step=2");
//         exit;
//     }
// } else if ($simId > 0 && $_SERVER['REQUEST_METHOD'] !== 'POST') {



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
            padding: 5rem 3rem 1rem 3rem;
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
            width: 100%;
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

        /* Update the form-card to handle internal scrolling */
        .form-card {
            background-color: var(--surface-container-low);
            padding: 2rem 2.5rem; /* Increased top padding */
            border-radius: 2rem;
            box-shadow: 0 1px 2px rgba(0,0,0,0.05);
            
            /* SCROLL LOGIC START */
            max-height: 80vh; /* Limits height to 70% of the screen */
            overflow-y: auto;  /* Enables vertical scroll */
            overflow-x: hidden; /* Prevents horizontal jitter */
            /* SCROLL LOGIC END */
        }

        /* Custom "Gamified" Scrollbar for Chrome/Safari/Edge */
        .form-card::-webkit-scrollbar {
            width: 6px;
        }

        .form-card::-webkit-scrollbar-track {
            background: transparent;
            margin: 20px; /* Keeps scrollbar away from rounded corners */
        }

        .form-card::-webkit-scrollbar-thumb {
            background: var(--outline-variant);
            border-radius: 10px;
        }

        .form-card::-webkit-scrollbar-thumb:hover {
            background: var(--primary-container);
        }

        /* Firefox scrollbar support */
        .form-card {
            scrollbar-width: thin;
            scrollbar-color: var(--outline-variant) transparent;
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
            /* bottom: 120%;  */
            right: 0;      /* Align to the right of the wrapper */
            
            width: 280px; 
            background-color: rgb(173 172 172 / 95%);
            color: #000;
            padding: 12px 16px;
            border-radius: 8px;
            font-size: 0.75rem;
            line-height: 1.5;
            z-index: 1000; /* Ensure it stays on top */
            white-space: normal; /* Allow text to wrap naturally */
        }

        /* Change arrow to point down */
        .tooltip-content::after {
            top: 100%;
            left: 90%;
            border-color: var(--on-surface) transparent transparent transparent;
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

        .info-tooltip-wrapper:hover .info-icon {
            background-color: var(--primary);
        }

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
                <img alt="Industrial assembly line" class="hero-image" src="images/start.png"/>
                <div class="image-overlay"></div>
            </div>
            <div class="editorial-text">
                <h1>Let us get started!, give us a brief about simulation context you have in mind!</h1>
                <p>Our intelligent engine uses these parameters to architect the optimal simulation environment for your specific industrial requirements.</p>
            </div>

        </div>
        <!-- Right Side Form -->
        <div class="form-col">
            <div class="form-card">
                <form class="form-stack" method="POST" id="simForm">
                    <div class="form-group full-width">
                        <div class="label-row">
                            <label class="field-label">
                                <span class="material-symbols-outlined">title</span>
                                Simulation Title
                            </label>
                            <div class="info-tooltip-wrapper">
                                <span class="info-icon">i</span>
                                <div class="tooltip-content" style="top: 1%;">
                                    This 5–7-word action-oriented title, <br>highlighting a clear, relatable business <br>challenge, will be the simulation title visible <br>to all library participants.<br><br>

                                        1.Prepare to Survive unexpected disasters. <br>
                                        2.Win Over the media backlash crisis. <br>
                                        3.Let's Build our operational resilience.

                                </div>
                            </div>
                        </div>
                        <!-- <input class="text-input" placeholder="Simulation library name" type="text"/> -->
                        <input class="text-input" type="text" name="sim_title"

                                placeholder="Use 5-7 words to define the challenge (e.g., Prepare for Ransomware Attack)"

                                value="<?= htmlspecialchars($simTitle) ?>">
                    </div>

                    <div class="form-group full-width">
                        <div class="label-row">
                            <label class="field-label">
                                <span class="material-symbols-outlined">description</span>
                                Simulation Description
                            </label>
                            <!-- <div class="info-tooltip-wrapper">
                                <span class="info-icon">i</span>
                                <div class="tooltip-content">
                                    PIN must be 6 digits and cannot: <br>
                                    • Contain all identical digits (e.g., 111111) <br>
                                    • Be consecutive numbers (e.g., 123456) <br>
                                    • Include any digit repeated three or more times (e.g., 111222)
                                </div>
                            </div> -->
                        </div>
                        <!-- <textarea class="textarea-input"  placeholder="Scale and conditions..." rows="3"></textarea> -->
                        <textarea class="textarea-input" name="sim_desc"><?= htmlspecialchars($simDesc) ?></textarea>
                    </div>


                    <div class="form-group full-width">
                        <div class="label-row">
                            <label class="field-label">
                                <span class="material-symbols-outlined">factory</span>
                                Industry Type
                            </label>
                            <div class="info-tooltip-wrapper">
                                <span class="info-icon">i</span>
                                <div class="tooltip-content">
                                    Feed a broad industry or leave blank for <br>generic simulations. Avoid specifics like <br>company name or functional names to ensure <br>balanced, high-quality content.

                                </div>
                            </div>
                        </div>
                        <!-- <div class="select-wrapper">
                            <select class="select-input">
                                <option>Select sector</option>
                                <option>Aerospace</option>
                                <option>Automotive</option>
                            </select>
                            <span class="material-symbols-outlined select-arrow">expand_more</span>
                        </div> -->
                        <input class="text-input" type="text" name="industry_type"

                                placeholder="Select a broad industry"

                                value="<?= htmlspecialchars($industryType) ?>">
                    </div>

                    <div class="form-group full-width">
                        <div class="label-row">
                            <label class="field-label">
                                <span class="material-symbols-outlined">public</span>
                                Geography
                            </label>
                            <div class="info-tooltip-wrapper">
                                <span class="info-icon">i</span>
                                <div class="tooltip-content">
                                    Leave blank for a global feel or select a broad <br>region to add local culture and flavor to your <br>content.

                                </div>
                            </div>
                        </div>
                        <!-- <input class="text-input" placeholder="e.g. North America" type="text"/> -->
                         <input class="text-input" type="text" name="geography"

                                placeholder=""

                                value="<?= htmlspecialchars($geography) ?>" >
                    </div>


                    <div class="form-group full-width">
                        <div class="label-row">
                            <label class="field-label">
                                <span class="material-symbols-outlined">analytics</span>
                                Operational Scale
                            </label>
                            <div class="info-tooltip-wrapper">
                                <span class="info-icon">i</span>
                                <div class="tooltip-content">
                                    Summarize the operational scope, scale, and <br>infrastructure to define the specific boundary <br>and environment for your simulation.<br><br>

                                    The organization operates through three <br>regional hubs managing 1,500 employees<br> with a centralized cloud database. All branches <br>rely on a unified digital banking platform<br> and a high-speed fiber-optic backbone for<br> real-time transactions.<br><br>

                                    There are fifty urban storefronts supported by<br> two massive automated distribution centers <br>and a fleet of 100 delivery vehicles. The <br>network uses decentralized local servers<br> but maintains a strict, 24/7 synchronized<br> inventory management system.<br><br>

                                    The simulation covers a high-output factory<br> floor and a separate administrative wing <br>housing 800 specialized staff. Operations<br> depend on legacy industrial control <br>systems integrated with a modern IoT <br>monitoring network across multiple <br>assembly lines.

                                </div>
                            </div>
                        </div>
                        <!-- <textarea class="textarea-input" placeholder="Scale and conditions..." rows="3"></textarea> -->
                       
                        <input type="text" name="operating_scale" class="text-input"
                                placeholder="Summarize the scope, scale, and infrastructure defining the environment"
                                value="<?= htmlspecialchars($operatingScale) ?>">
                    </div>

                    
                    
                    <div class="form-group full-width">
                        <div class="label-row">
                            <label class="field-label">
                                <span class="material-symbols-outlined">translate</span>
                                Language
                            </label>
                            <div class="info-tooltip-wrapper">
                                <span class="info-icon">i</span>
                                <div class="tooltip-content">
                                    Choose the primary language for your <br>simulation content (output). While our engine<br> handles the heavy lifting, you can always <br>manually edit the output to perfectly match <br>local nuances or specific cultural flavors. 

                                </div>
                            </div>
                        </div>
                        <!-- <div class="select-wrapper">
                            <select class="select-input">
                                <option>English</option>
                                <option>German</option>
                            </select>
                            <span class="material-symbols-outlined select-arrow">expand_more</span>
                        </div> -->
                        <div class="select-wrapper">
                            <select class="select-input" name="language">

                                <option value="">Select the primary language for content generation</option>

                                <option value="English" <?= ($language == 'English') ? 'selected' : '' ?>>English</option>

                                <option value="Spanish" <?= ($language == 'Spanish') ? 'selected' : '' ?>>Spanish</option>

                            </select>
                            <span class="material-symbols-outlined select-arrow">expand_more</span>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</main>

</body></html>
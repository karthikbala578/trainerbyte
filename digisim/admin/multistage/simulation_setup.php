<?php
// simulation_setup.php - Step 1: Basic simulation info

$pageTitle = 'Multi Stage Simulation Setup';
require_once __DIR__ . '/../include/dataconnect.php';

$simId = isset($_GET['sim_id']) ? intval($_GET['sim_id']) : 0;

$simTitle = $simDesc = $industryType = $geography = $operatingScale = '';
$noStages = '';
$language = 'English';
$errors = [];

// Handle traditional form submit (since ms_stepper.php uses submit)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $simTitle = trim($_POST['sim_title'] ?? '');
    $simDesc = trim($_POST['sim_desc'] ?? '');
    $industryType = trim($_POST['industry_type'] ?? '');
    $geography = trim($_POST['geography'] ?? '');
    $operatingScale = trim($_POST['operating_scale'] ?? '');
    $language = trim($_POST['language'] ?? 'English');
    $noStages = intval($_POST['no_stages'] ?? 0);

    if (empty($simTitle)) $errors['sim_title'] = 'Simulation title is required';
    if (empty($industryType)) $errors['industry_type'] = 'Industry type is required';
    if ($noStages <= 0) $errors['no_stages'] = 'Please enter valid number of stages';

    if (empty($errors)) {
        if ($simId > 0) {
            $stmt = $conn->prepare("UPDATE mg5_ms_userinput_master SET 
                ui_sim_title=?, ui_sim_desc=?, ui_industry_type=?, ui_geography=?, 
                ui_operating_scale=?, ui_lang=?, ui_no_stages=? 
                WHERE ui_id=? AND ui_team_pkid=?");
            $stmt->bind_param("ssssssiii", $simTitle, $simDesc, $industryType, $geography, $operatingScale, $language, $noStages, $simId, $_SESSION['team_id']);
        } else {
            $stmt = $conn->prepare("INSERT INTO mg5_ms_userinput_master 
                (ui_team_pkid, ui_sim_title, ui_sim_desc, ui_industry_type, ui_geography, 
                 ui_operating_scale, ui_lang, ui_no_stages, ui_cur_stage, ui_created_at) 
                VALUES (?, ?,?,?,?,?,?,?, 1, NOW())");
            $stmt->bind_param("issssssi", $_SESSION['team_id'], $simTitle, $simDesc, $industryType, $geography, $operatingScale, $language, $noStages);
        }
        $stmt->execute();
        
        if ($simId === 0) {
            $simId = $conn->insert_id;
        }
        $stmt->close();

        // Redirect to step 2, stage 1
        header("Location: multistagedigisim.php?step=2&sim_id=" . $simId . "&stage=1");
        exit;
    }
} else if ($simId > 0) {
    // Load existing data if editing
    $stmt = $conn->prepare("SELECT * FROM mg5_ms_userinput_master WHERE ui_id = ? AND ui_team_pkid = ? LIMIT 1");
    $stmt->bind_param("ii", $simId, $_SESSION['team_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $simTitle = $row['ui_sim_title'];
        $simDesc = $row['ui_sim_desc'];
        $industryType = $row['ui_industry_type'];
        $geography = $row['ui_geography'];
        $operatingScale = $row['ui_operating_scale'];
        $language = $row['ui_lang'] ?? 'English';
        $noStages = $row['ui_no_stages'];
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en"><head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<title>Industrial Atelier - Multistage Setup</title>
<link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
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

        /* Main Content */
        .main-content {
            padding: 5rem 3rem 1rem 3rem;   
        }

        .content-grid {
            display: flex;
            flex-direction: column;
            gap: 2rem;
        }

        @media (min-width: 1024px) {
            .content-grid {
                flex-direction: row; 
                align-items: flex-start;
            }

            .editorial-col {
                flex: 0 0 35%; 
                max-width: 35%;
            }
            .form-col {
                flex: 0 0 65%;
                max-width: 65%;
                position: sticky;
                top: 6rem;
            }
        }

        /* Left Column */
        .editorial-col {
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

        .form-stack {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem 2rem;
        }

        .full-width {
            grid-column: span 2;
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

        .form-card {
            background-color: var(--surface-container-low);
            padding: 2rem 2.5rem; 
            border-radius: 2rem;
            box-shadow: 0 1px 2px rgba(0,0,0,0.05);
            max-height: 80vh; 
            overflow-y: auto;  
            overflow-x: hidden; 
            scrollbar-width: thin;
            scrollbar-color: var(--outline-variant) transparent;
        }

        .form-card::-webkit-scrollbar { width: 6px; }
        .form-card::-webkit-scrollbar-track { background: transparent; margin: 20px; }
        .form-card::-webkit-scrollbar-thumb { background: var(--outline-variant); border-radius: 10px; }
        .form-card::-webkit-scrollbar-thumb:hover { background: var(--primary-container); }

        /* ── Tooltip System ── */
        .info-tooltip-wrapper {
            position: relative;
            display: inline-flex;
            align-items: center;
            cursor: help;
        }
        .info-icon {
            width: 18px; height: 18px;
            background-color: #cbd5e1;
            color: #475569;
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 11px; font-weight: 700; font-style: normal;
            font-family: Georgia, serif;
            line-height: 1;
            transition: background-color 0.2s, color 0.2s;
            user-select: none;
        }
        .info-tooltip-wrapper:hover .info-icon {
            background-color: #3a6095;
            color: #fff;
        }
        .tooltip-content {
            visibility: hidden;
            opacity: 0;
            pointer-events: none;
            position: absolute;
            top: calc(100% + 8px);
            right: 0;
            /* Auto-size: narrow for short text, wider for long */
            min-width: 140px;
            max-width: 260px;
            width: max-content;
            background-color: #1e293b;
            color: #e2e8f0;
            padding: 9px 13px;
            border-radius: 8px;
            font-size: 0.75rem;
            line-height: 1.55;
            z-index: 9999;
            white-space: normal;
            word-break: break-word;
            box-shadow: 0 4px 16px rgba(0,0,0,0.18);
            transition: opacity 0.15s ease, visibility 0.15s ease;
        }
        /* caret pointing up */
        .tooltip-content::before {
            content: "";
            position: absolute;
            bottom: 100%;
            right: 8px;
            border-width: 5px;
            border-style: solid;
            border-color: transparent transparent #1e293b transparent;
        }
        .info-tooltip-wrapper:hover .tooltip-content {
            visibility: visible;
            opacity: 1;
        }

        .text-input, .select-input, .textarea-input {
            width: 100%; background-color: var(--surface-container-lowest); border: none; border-radius: 0.75rem;
            padding: 1rem 1.25rem; color: var(--on-surface); box-shadow: 0 1px 2px rgba(0,0,0,0.05); font-family: inherit; font-size: 1rem;
        }
        .text-input::placeholder, .textarea-input::placeholder { color: var(--outline-variant); }
        .text-input:focus, .select-input:focus, .textarea-input:focus { outline: 2px solid var(--primary-container); outline-offset: -2px; }

        .select-wrapper { position: relative; }
        .select-input { appearance: none; cursor: pointer; }
        .select-arrow { position: absolute; right: 1rem; top: 50%; transform: translateY(-50%); pointer-events: none; color: var(--outline); }
        .textarea-input { resize: none; height: 110px; }

</style>
</head>
<body>

<?php include 'ms_stepper.php'; ?>

<main class="main-content">
    <div class="content-grid">
        <!-- Left Side -->
        <div class="editorial-col">
            <div class="hero-image-container">
                <img alt="Industrial assembly line" class="hero-image" src="../pages/images/start.png"/>
                <div class="image-overlay"></div>
            </div>
            <div class="editorial-text">
                <h1>Let us get started! Give us a brief about the multi-stage scenario you have in mind.</h1>
                <p>Our intelligent engine uses these parameters to architect the optimal simulation setup for your specific industrial requirements.</p>
            </div>
        </div>

        <!-- Right Side Form -->
        <div class="form-col">
            <div class="form-card">
                <?php if (!empty($errors)): ?>
                    <div style="color:red; margin-bottom:1rem; font-weight:bold;">
                        Please fix the errors below.
                    </div>
                <?php endif; ?>
                <form class="form-stack" method="POST" id="setupForm" action="multistagedigisim.php?step=1&sim_id=<?= $simId ?>">
                    
                    <div class="form-group full-width">
                        <div class="label-row">
                            <label class="field-label"><span class="material-symbols-outlined">title</span>Simulation Title</label>
                            <span class="info-tooltip-wrapper"><span class="info-icon">i</span><span class="tooltip-content">Use 5–7 action-oriented words to title the simulation challenge clearly.</span></span>
                        </div>
                        <input class="text-input" type="text" name="sim_title" placeholder="Use 5-7 words to define the challenge" value="<?= htmlspecialchars($simTitle) ?>">
                        <?php if(isset($errors['sim_title'])) echo "<span style='color:red;font-size:12px;'>{$errors['sim_title']}</span>"; ?>
                    </div>

                    <div class="form-group full-width">
                        <div class="label-row">
                            <label class="field-label"><span class="material-symbols-outlined">description</span>Simulation Description</label>
                            <span class="info-tooltip-wrapper"><span class="info-icon">i</span><span class="tooltip-content">Briefly describe the simulation's purpose, background and intended challenge for participants.</span></span>
                        </div>
                        <textarea class="textarea-input" name="sim_desc"><?= htmlspecialchars($simDesc) ?></textarea>
                    </div>

                    <div class="form-group full-width">
                        <div class="label-row">
                            <label class="field-label"><span class="material-symbols-outlined">factory</span>Industry Type</label>
                            <span class="info-tooltip-wrapper"><span class="info-icon">i</span><span class="tooltip-content">e.g. Healthcare, Finance, Energy</span></span>
                        </div>
                        <input class="text-input" type="text" name="industry_type" placeholder="Select a broad industry" value="<?= htmlspecialchars($industryType) ?>">
                        <?php if(isset($errors['industry_type'])) echo "<span style='color:red;font-size:12px;'>{$errors['industry_type']}</span>"; ?>
                    </div>

                    <div class="form-group full-width">
                        <div class="label-row">
                            <label class="field-label"><span class="material-symbols-outlined">public</span>Geography</label>
                            <span class="info-tooltip-wrapper"><span class="info-icon">i</span><span class="tooltip-content">Region or country where the scenario is set. e.g. India, EU, Global</span></span>
                        </div>
                        <input class="text-input" type="text" name="geography" value="<?= htmlspecialchars($geography) ?>">
                    </div>

                    <div class="form-group full-width">
                        <div class="label-row">
                            <label class="field-label"><span class="material-symbols-outlined">analytics</span>Operational Scale</label>
                            <span class="info-tooltip-wrapper"><span class="info-icon">i</span><span class="tooltip-content">e.g. Remote Only, Hybrid, On-Site</span></span>
                        </div>
                        <input type="text" name="operating_scale" class="text-input" placeholder="Summarize the scope" value="<?= htmlspecialchars($operatingScale) ?>">
                    </div>

                    <div class="form-group">
                        <div class="label-row">
                            <label class="field-label"><span class="material-symbols-outlined">translate</span>Language</label>
                            <span class="info-tooltip-wrapper"><span class="info-icon">i</span><span class="tooltip-content">Language for generated content</span></span>
                        </div>
                        <div class="select-wrapper">
                            <select class="select-input" name="language">
                                <option value="English" <?= ($language == 'English') ? 'selected' : '' ?>>English</option>
                                <option value="Spanish" <?= ($language == 'Spanish') ? 'selected' : '' ?>>Spanish</option>
                            </select>
                            <span class="material-symbols-outlined select-arrow">expand_more</span>
                        </div>
                    </div>

                    <div class="form-group">
                        <div class="label-row">
                            <label class="field-label"><span class="material-symbols-outlined">format_list_numbered</span>Number of Stages</label>
                            <span class="info-tooltip-wrapper"><span class="info-icon">i</span><span class="tooltip-content">Enter a value between 1 and 10</span></span>
                        </div>
                        <input type="number" name="no_stages" class="text-input" min="1" max="10" placeholder="1 to 10" value="<?= htmlspecialchars($noStages) ?>">
                        <?php if(isset($errors['no_stages'])) echo "<span style='color:red;font-size:12px;'>{$errors['no_stages']}</span>"; ?>
                    </div>

                </form>
            </div>
        </div>
    </div>
</main>
</body></html>
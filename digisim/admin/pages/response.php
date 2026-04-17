<?php

$pageTitle = 'Configure Response Scale';
$pageCSS = '/pages/page-styles/score_scale.css';

require_once __DIR__ . '/../include/dataconnect.php';

$simId = isset($_GET['sim_id']) ? intval($_GET['sim_id']) : 0;

if ($simId <= 0) {
    header("Location: page-container.php?step=1");
    exit;
}

$errors = [];
$selectedScaleId = null;
$existingValues = [];

/* LOAD CURRENT DATA */
$stmt = $conn->prepare("
    SELECT ui_score_scale, ui_score_value
    FROM mg5_digisim_userinput
    WHERE ui_id=? AND ui_team_pkid=?
");
$stmt->bind_param("ii", $simId, $_SESSION['team_id']);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows > 0) {
    $row = $res->fetch_assoc();
    $selectedScaleId = $row['ui_score_scale'];
    if (!empty($row['ui_score_value'])) {
        $existingValues = json_decode($row['ui_score_value'], true);
    }
}
$stmt->close();

/* LOAD SCALE TYPES */
$scoreTypes = [];
$q = $conn->query("SELECT st_id, st_name FROM mg5_scoretype");
while ($r = $q->fetch_assoc()) {
    $scoreTypes[] = $r;
}

/* LOAD SCALE COMPONENTS */
$scaleComponents = [];
$c = $conn->query("
    SELECT stv_scoretype_pkid, stv_name, stv_value, stv_color
    FROM mg5_scoretype_value
    ORDER BY stv_value
");
while ($row = $c->fetch_assoc()) {
    $scaleComponents[$row['stv_scoretype_pkid']][] = $row;
}

/* FORM SUBMIT */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $selectedScaleId = intval($_POST['selected_scale']);

    if (!$selectedScaleId) {
        $errors['scale'] = "Please select a scale";
    }
    $scaleValues = [];
    $total = 0;

    if (isset($scaleComponents[$selectedScaleId])) {

        foreach ($scaleComponents[$selectedScaleId] as $component) {

            $name = $component['stv_name'];
            $field = 'component_' . $name;

            $val = isset($_POST[$field]) ? intval($_POST[$field]) : 0;

            $scaleValues[$name] = $val;
            $total += $val;
        }
    }

    if ($total <= 0) {
        $errors['total'] = "Total responses must be greater than zero";
    } else {
        $json = json_encode($scaleValues);
        $update = $conn->prepare("
            UPDATE mg5_digisim_userinput
            SET ui_score_scale=?, ui_score_value=?,ui_analysis_id = 1,ui_priority_points = 1,ui_scoring_logic = 1,ui_scoring_basis = 3,ui_min_select = 1,ui_total_basis = 1,ui_result = 4, ui_cur_step=4
            WHERE ui_id=? AND ui_team_pkid=?
        ");
        $update->bind_param("isii", $selectedScaleId, $json, $simId, $_SESSION['team_id']);
        $update->execute();
        $update->close();
        header("Location: page-container.php?step=5&sim_id=" . $simId);
        exit;
    }
}
?>

<!DOCTYPE html>

<html lang="en"><head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<title>SimuArch - Step 4: Configure Response Scale</title>
<!-- Material Symbols -->
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" rel="stylesheet"/>
<!-- Google Fonts -->
<link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;600;700;800&amp;family=Inter:wght@400;500;600&amp;display=swap" rel="stylesheet"/>
<style>
        :root {
            --primary: #00478d;
            --primary-container: #005eb8;
            --on-primary: #ffffff;
            --on-primary-container: #c8daff;
            --primary-fixed: #d6e3ff;
            
            --secondary: #48626e;
            --secondary-container: #cbe7f5;
            --on-secondary: #ffffff;
            --secondary-fixed: #cbe7f5;
            
            --tertiary: #793100;
            --tertiary-container: #9f4300;
            
            --error: #ba1a1a;
            --error-container: #ffdad6;
            
            --background: #f8fafc;
            --surface: #ffffff;
            --surface-variant: #e1e2ea;
            --surface-container-lowest: #ffffff;
            --surface-container-low: #f1f5f9;
            --surface-container: #ecedf6;
            --surface-container-high: #e7e8f0;
            --surface-container-highest: #e1e2ea;
            
            --on-surface: #0f172a;
            --on-surface-variant: #475569;
            --outline: #94a3b8;
            --outline-variant: #e2e8f0;

            --font-headline: 'Manrope', sans-serif;
            --font-body: 'Inter', sans-serif;
            
            --radius-sm: 0.125rem;
            --radius-md: 0.375rem;
            --radius-lg: 0.5rem;
            --radius-xl: 1rem;
            --radius-full: 9999px;

            --nav-height: 64px;
            --footer-height: 72px;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            background-color: var(--background);
            color: var(--on-surface);
            font-family: var(--font-body);
            height: 100vh;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        .material-symbols-outlined {
            font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
            display: inline-block;
            vertical-align: middle;
        }

        /* Layout Container */
        .app-container {
            display: flex;
            flex: 1;
            overflow: hidden;
        }

        /* Main Content 3-Column Grid */
        .main-canvas {
            flex: 1;
            padding: 5rem 3rem 1rem 3rem;
            overflow-y: auto;
            scrollbar-width: thin;
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

        .form-card {
            background-color: var(--surface-container-low);
            padding: .1rem .5rem;
            border-radius: 2rem;
            box-shadow: 0 1px 2px rgba(0,0,0,0.05);
            max-height: none; /* Remove height limit */
            overflow: visible; /* Ensure tooltips aren't cut off */
        }

        /* Column 2 & 3 General Card Style */
        .config-card {
            /* background-color: var(--surface);
            border-radius: var(--radius-xl); */
            padding: .5rem 1.5rem;
            /* border: 1px solid var(--outline-variant);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05); */
            display: flex;
            flex-direction: column;
            gap: .5rem;
        }

        /* .section-label {
            font-family: var(--font-headline);
            font-size: 0.75rem;
            font-weight: 800;
            color: var(--outline);
            letter-spacing: 0.1em;
            text-transform: uppercase;
        } */

        .info-text {
            flex: 1;
            margin-bottom: 2rem; /* Adds space before the cards start */
        }

        .info-text h1 {
            font-family: 'Manrope', sans-serif;
            font-size: 1.5rem; /* Large, bold heading */
            font-weight: 800;
            color: #001b3d; /* Dark navy from image */
            margin-bottom: 0.25rem;
            letter-spacing: -0.01em;
        }

        .info-text p {
            font-family: 'Inter', sans-serif;
            color: #4e6874; /* Muted secondary text color */
            font-size: 0.95rem;
            line-height: 1.5;
            margin: 0;
        }

        /* .status-badge {
            align-self: flex-start;
            background: #eff6ff;
            padding: 0.5rem 1rem;
            border-radius: var(--radius-full);
            display: flex;
            align-items: center;
            gap: 0.5rem;
            border: 1px solid #dbeafe;
        }

        .pulse-dot {
            width: 0.5rem;
            height: 0.5rem;
            background-color: #2563eb;
            border-radius: var(--radius-full);
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% { transform: scale(0.95); opacity: 0.5; }
            50% { transform: scale(1.05); opacity: 1; }
            100% { transform: scale(0.95); opacity: 0.5; }
        }

        .badge-text {
            font-family: var(--font-headline);
            font-size: 0.7rem;
            font-weight: 800;
            color: #1d4ed8;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        } */

        .total-summary-card {
            background-color: #ffffff;
            padding: .25rem .2rem;
            border-radius: 12px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-width: 150px;
            border: 1px solid #ecedf6; /* surface-container */
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        }

        .summary-label {
            font-family: 'Manrope', sans-serif;
            font-size: 0.75rem;
            font-weight: 700;
            color: #48626e; /* secondary color */
            letter-spacing: 0.12em;
            text-transform: uppercase;
            margin-bottom: 0.25rem;
            opacity: 0.8;
        }

        .total-val {
            font-family: 'Manrope', sans-serif;
            font-size: 2.5rem;
            font-weight: 800;
            color: #2563eb; /* The vibrant blue from your image */
            line-height: 1;
        }

        /* Scale Grid Selection (Column 2) */
        .selection-list {
            display: flex;
            flex-direction: row;
            justify-content: center;
            gap: 0.75rem;
        }

        .selection-card {
            background-color: var(--surface);
            padding: 1rem 1.25rem;
            border-radius: var(--radius-lg);
            border: 2px solid var(--outline-variant);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            /* gap: 1rem; */
            cursor: pointer;
            width: 185px;
            transition: all 0.2s ease;
        }

        .selection-card input[type="radio"] {
            position: absolute;
            opacity: 0;
            width: 0;
            height: 0;
        }

        .selection-card.active,
        .selection-card:has(input[type="radio"]:checked) {
            border-color: #2563eb;
            background: #eff6ff;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15);
        }

        .selection-card:hover {
            border-color: #94a3b8;
            background-color: var(--surface-container-low);
        }

        .selection-card.selected {
            border-color: #2563eb;
            background-color: #eff6ff;
        }

        .card-icon {
            font-size: 1.5rem;
            color: var(--outline);
        }

        .selection-card.selected .card-icon {
            color: #2563eb;
            font-variation-settings: 'FILL' 1;
        }

        .card-content {
            flex: 1;
            justify-content: center;
            flex-direction: column;
            align-items: center;
            display: flex;
        }

        .card-info-label {
            font-family: var(--font-headline);
            font-size: 0.7rem;
            font-weight: 800;
            color: var(--outline);
            text-transform: uppercase;
        }

        .selected .card-info-label {
            color: #2563eb;
            display: flex;
            justify-content: center;
        }

        .card-info-value {
            font-family: var(--font-body);
            font-size: 0.875rem;
            font-weight: 600;
            color: var(--on-surface);
        }

        /* Frequency Adjusters (Column 3) */
        .scale-item-list {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .scale-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0.75rem 1rem;
            background-color: var(--surface-container-low);
            border-radius: var(--radius-lg);
            border-left: 4px solid transparent;
        }

        /* .scale-row.high { border-left-color: #dc2626; }
        .scale-row.medium { border-left-color: #2563eb; }
        .scale-row.low { border-left-color: #64748b; } */

        .scale-name {
            font-family: var(--font-headline);
            font-size: 0.9375rem;
            font-weight: 700;
            color: var(--on-surface);
        }

        .scale-tag {
            font-size: 0.7rem;
            font-weight: 700;
            text-transform: uppercase;
        }

        .scale-tag.high { color: #dc2626; }
        .scale-tag.medium { color: #2563eb; }
        .scale-tag.low { color: #64748b; }

        .counter-group {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            background-color: var(--surface);
            border: 1px solid var(--outline-variant);
            border-radius: var(--radius-md);
            padding: 0.25rem;
        }

        .counter-btn {
            width: 1.75rem;
            height: 1.75rem;
            display: flex;
            align-items: center;
            justify-content: center;
            border: none;
            border-radius: 4px;
            background: transparent;
            color: var(--on-surface-variant);
            cursor: pointer;
            transition: all 0.2s;
        }

        .counter-btn:hover {
            background-color: var(--surface-container-low);
        }

        .counter-value {
            width: 1.5rem;
            text-align: center;
            font-family: var(--font-headline);
            font-size: 1rem;
            font-weight: 800;
            color: var(--on-surface);
        }

        /* Allocation Section (Column 3 Bottom) */
        .allocation-container {
            border-top: 1px solid var(--outline-variant);
            padding-top: 1.5rem;
            margin-top: 0.5rem;
        }

        .allocation-label-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.75rem;
        }

        .allocation-ratio {
            font-size: 0.7rem;
            font-weight: 800;
            color: var(--outline);
            text-transform: uppercase;
        }

        .progress-bar {
            height: 0.5rem;
            background-color: var(--surface-container-low);
            border-radius: var(--radius-full);
            overflow: hidden;
            display: flex;
        }

        .progress-segment.high { background-color: #dc2626; width: 33.3%; }
        .progress-segment.medium { background-color: #2563eb; width: 41.7%; }
        .progress-segment.low { background-color: #64748b; width: 25%; }

        @media (max-width: 768px) {
            
            .sidebar { display: none; }
            .nav-links { display: none; }
        }
    </style>
</head>
<body>
<?php include 'stepper.php'; ?>
<div class="app-container">

    <main class="main-canvas">
        <div class="content-grid">
            <!-- Left Side -->
            <div class="editorial-col">
                <div class="hero-image-container">
                    <img alt="Industrial assembly line" class="hero-image" src="images/response.png"/>
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
                    <form method="POST" id="scaleform">
                        <div class="column-2 column-content">
                            
                            <div class="config-card">
                                <div style="display: flex; justify-content: space-between; align-items: center;">
                                    <!-- <span class="section-label">Scale Selection</span> -->
                                    <div class="info-text">
                                        <h1>Configure Response Scale</h1>
                                        <p>Select a scale set and define response requirements.</p>
                                    </div>
                                    <!-- <div class="status-badge">
                                        <span class="pulse-dot"></span>
                                        <span class="badge-text">12 Required</span>
                                    </div> -->
                                    <div class="total-summary-card">
                                        <h3 class="summary-label">Total Responses</h3>
                                        <div class="total-val" id="totalResponses"><?= array_sum($existingValues) ?></div>
                                    </div>
                                </div>

                                <input type="hidden" name="selected_scale" id="selectedScaleInput" value="high_medium_low">
                                
                                <div class="selection-list">
                                    <?php foreach ($scoreTypes as $scale): ?>
                                        <label class="selection-card <?= $selectedScaleId == $scale['st_id'] ? 'active' : '' ?>">
                                            <input type="radio" name="selected_scale" 
                                                value="<?= $scale['st_id'] ?>"
                                                <?= $selectedScaleId == $scale['st_id'] ? 'checked' : '' ?>>
                                                <span class="material-symbols-outlined card-icon">check_circle</span>
                                                    <div class="card-content">
                                                        <!-- <p class="card-info-label">Current</p> -->
                                                        <p class="card-info-value"><?= $scale['st_name'] ?></p>
                                                    </div>
                                            
                                            
                                        </label>
                                    <?php endforeach; ?>
                                    <!-- <div class="selection-card selected" >
                                        <span class="material-symbols-outlined card-icon">check_circle</span>
                                        <div class="card-content">
                                            <p class="card-info-label">Current</p>
                                            <p class="card-info-value">High / Medium / Low</p>
                                        </div>
                                    </div>
                                    
                                    <div class="selection-card" onclick="selectScale('numeric', this)">
                                        <span class="material-symbols-outlined card-icon">numbers</span>
                                        <div class="card-content">
                                            <p class="card-info-label">Numeric</p>
                                            <p class="card-info-value">1 - 5 Points</p>
                                        </div>
                                    </div>

                                    <div class="selection-card" onclick="selectScale('binary', this)">
                                        <span class="material-symbols-outlined card-icon">rule</span>
                                        <div class="card-content">
                                            <p class="card-info-label">Binary</p>
                                            <p class="card-info-value">Pass / Fail</p>
                                        </div>
                                    </div> -->
                                </div>
                            </div>
                            <!-- for errors -->
                            <?php if (isset($errors['scale'])): ?>
                                <p class="error"><?= $errors['scale'] ?></p>
                            <?php endif; ?>

                            <div class="config-card">
                                <span class="section-label">Configure Frequencies</span>
                                <div id="noScale" class="empty-state">
                                    Select a scale to configure values
                                </div>
                                <?php foreach ($scaleComponents as $scaleId => $components): ?>
                                    <div class="scale-group" data-scale="<?= $scaleId ?>" style="display:none;">
                                        <?php foreach ($components as $comp):
                                            $name = $comp['stv_name'];
                                            $value = $existingValues[$name] ?? 0;
                                            $class = strtolower($name);
                                            $color = $comp['stv_color'];
                                        ?>
                                        <div class="scale-item-list">
                                    
                                            <div class="scale-row <?= strtolower($name) ?>" style="border-left-color: <?= strtolower($color) ?>">
                                                <div>
                                                    <!-- <h4 class="scale-name <?= $class ?>"><?= strtoupper(substr($name, 0, 1)) ?></h4> -->
                                                    <!-- <p class="scale-tag high">Critical</p> -->
                                                    <div>
                                                        <strong><?= htmlspecialchars($name) ?></strong>
                                                        <p><?= strtolower($name) ?> responses</p>
                                                    </div>
                                                </div>
                                                <div class="counter-group ">
                                                    <button type="button" class="counter-btn minus"><span class="material-symbols-outlined" style="font-size: 1rem">remove</span></button>
                                                    <input type="number" name="component_<?= htmlspecialchars($name) ?>" value="<?= $value ?>" min="0" class="counter-value scale-input" style="width: 33px; border: none; background: transparent; text-align: center;">
                                                    <button type="button" class="counter-btn plus"><span class="material-symbols-outlined" style="font-size: 1rem">add</span></button>
                                                </div>
                                            </div>
                                        </div>
                                            <!-- <div class="component-row">
                                                <div class="component-info">
                                                    <span class="priority-icon <?= $class ?>">
                                                        <?= strtoupper(substr($name, 0, 1)) ?>
                                                    </span>
                                                    <div>
                                                        <strong><?= htmlspecialchars($name) ?></strong>
                                                        <p><?= strtolower($name) ?> responses</p>
                                                    </div>
                                                </div>

                                                <div class="counter-group">
                                                    <button type="button" class="minus">−</button>
                                                    <input type="number" name="component_<?= htmlspecialchars($name) ?>"
                                                        value="<?= $value ?>" min="0" class="scale-input">
                                                    <button type="button" class="plus">+</button>
                                                </div>
                                            </div> -->
                                        <?php endforeach; ?>
                                    </div>
                                <?php endforeach; ?>
                                <!-- <div class="scale-item-list">
                                    
                                    <div class="scale-row high">
                                        <div>
                                            <h4 class="scale-name">High</h4>
                                            <p class="scale-tag high">Critical</p>
                                        </div>
                                        <div class="counter-group">
                                            <button type="button" class="counter-btn" onclick="updateQty('high', -1)"><span class="material-symbols-outlined" style="font-size: 1rem">remove</span></button>
                                            <input type="number" name="freq_high" id="input_high" value="4" class="counter-value" readonly style="width: 30px; border: none; background: transparent; text-align: center;">
                                            <button type="button" class="counter-btn" onclick="updateQty('high', 1)"><span class="material-symbols-outlined" style="font-size: 1rem">add</span></button>
                                        </div>
                                    </div>

                                    <div class="scale-row medium">
                                        <div>
                                            <h4 class="scale-name">Medium</h4>
                                            <p class="scale-tag medium">Standard</p>
                                        </div>
                                        <div class="counter-group">
                                            <button type="button" class="counter-btn" onclick="updateQty('medium', -1)"><span class="material-symbols-outlined" style="font-size: 1rem">remove</span></button>
                                            <input type="number" name="freq_medium" id="input_medium" value="5" class="counter-value" readonly style="width: 30px; border: none; background: transparent; text-align: center;">
                                            <button type="button" class="counter-btn" onclick="updateQty('medium', 1)"><span class="material-symbols-outlined" style="font-size: 1rem">add</span></button>
                                        </div>
                                    </div>

                                    <div class="scale-row low">
                                        <div>
                                            <h4 class="scale-name">Low</h4>
                                            <p class="scale-tag low">Minor</p>
                                        </div>
                                        <div class="counter-group">
                                            <button type="button" class="counter-btn" onclick="updateQty('low', -1)"><span class="material-symbols-outlined" style="font-size: 1rem">remove</span></button>
                                            <input type="number" name="freq_low" id="input_low" value="3" class="counter-value" readonly style="width: 30px; border: none; background: transparent; text-align: center;">
                                            <button type="button" class="counter-btn" onclick="updateQty('low', 1)"><span class="material-symbols-outlined" style="font-size: 1rem">add</span></button>
                                        </div>
                                    </div>
                                </div> -->

                                <!-- <div class="allocation-container">
                                    <div class="allocation-label-row">
                                        <span class="section-label" style="margin-bottom: 0">Resource Allocation</span>
                                        <span class="allocation-ratio" id="ratioText">Ratio 4:5:3</span>
                                    </div>
                                    <div class="progress-bar">
                                        <div class="progress-segment high" id="bar_high" style="width: 33%"></div>
                                        <div class="progress-segment medium" id="bar_medium" style="width: 42%"></div>
                                        <div class="progress-segment low" id="bar_low" style="width: 25%"></div>
                                    </div>
                                </div> -->
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </main>
</div>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        const radios = document.querySelectorAll("input[name='selected_scale']");
        const groups = document.querySelectorAll(".scale-group");
        const empty = document.getElementById("noScale");
        const inputs = document.querySelectorAll(".scale-input");
        const totalDisplay = document.getElementById("totalResponses");

        function showGroup(scaleId) {
            groups.forEach(group => group.style.display = "none");
            const target = document.querySelector('.scale-group[data-scale="' + scaleId + '"]');
            if (target) {
                target.style.display = "flex";
                target.style.flexDirection = "column";
                target.style.gap = "12px";
                empty.style.display = "none";
            } else {
                empty.style.display = "block";
            }
        }

        radios.forEach(radio => {
            radio.addEventListener("change", function() {
                showGroup(this.value);
            });
        });

        const checked = document.querySelector("input[name='selected_scale']:checked");
        if (checked) showGroup(checked.value);

        function updateTotal() {
            let total = 0;
            document.querySelectorAll(".scale-group:not([style*='display: none']) .scale-input")
                .forEach(input => total += parseInt(input.value) || 0);
            totalDisplay.textContent = total;
        }

        document.querySelectorAll(".plus").forEach(btn => {
            btn.addEventListener("click", function() {
                const input = this.parentElement.querySelector("input");
                input.value = parseInt(input.value || 0) + 1;
                updateTotal();
            });
        });

        document.querySelectorAll(".minus").forEach(btn => {
            btn.addEventListener("click", function() {
                const input = this.parentElement.querySelector("input");
                let value = parseInt(input.value || 0);
                if (value > 0) input.value = value - 1;
                updateTotal();
            });
        });

        inputs.forEach(input => input.addEventListener("input", updateTotal));
        updateTotal();
    });
</script>
</body></html>

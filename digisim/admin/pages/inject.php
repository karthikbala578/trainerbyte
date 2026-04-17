<?php

// Set page title and CSS

$simId = isset($_GET['sim_id']) ? intval($_GET['sim_id']) : 0;



if ($simId <= 0) {

    //header("Location: page-container.php?step=1");

    exit;

}



$pageTitle = 'Configure Inject Distribution';

$pageCSS = '/pages/page-styles/inject_distribution.css';







// Include database connection

require_once __DIR__ . '/../include/dataconnect.php';

$injectTypes = [];



$injectStmt = $conn->prepare("

    SELECT in_id, in_name, in_description

    FROM mg5_inject_master

    WHERE in_status = 1

    ORDER BY in_id ASC

");



$injectStmt->execute();

$result = $injectStmt->get_result();



while ($row = $result->fetch_assoc()) {

    $injectTypes[] = $row;

}



$injectStmt->close();









// Initialize form data

$injectsData = [];



foreach ($injectTypes as $type) {

    $key = strtolower($type['in_name']);

    $injectsData[$key] = 0;

}



$injectsData['total'] = 0;







$errors = [];



// Handle form submission

if ($_SERVER['REQUEST_METHOD'] === 'POST') {



    $total = 0;

    $injectsArray = [];



    foreach ($injectTypes as $type) {



        $key = strtolower($type['in_name']);

        $value = isset($_POST[$key]) ? intval($_POST[$key]) : 0;



        $injectsArray[$key] = $value;

        $total += $value;

    }



    if ($total <= 0) {

        $errors['total'] = 'Total injects must be greater than zero';

    }



    if (empty($errors)) {



        $injectsArray['total'] = $total;

        $injectsJson = json_encode($injectsArray);



        $updateStmt = $conn->prepare("

            UPDATE mg5_digisim_userinput

            SET ui_injects = ?,

                ui_cur_step = 3

            WHERE ui_id = ? AND ui_team_pkid = ?



        ");



        $updateStmt->bind_param('sii', $injectsJson, $simId, $_SESSION['team_id']);

        $updateStmt->execute();

        $updateStmt->close();



        header("Location: page-container.php?step=4&sim_id=" . $simId);

        exit;

    }

} else {

    // Load existing data if available

    $loadStmt = $conn->prepare("SELECT ui_injects FROM mg5_digisim_userinput WHERE ui_id = ?");

    $loadStmt->bind_param('i', $simId);

    $loadStmt->execute();

    $result = $loadStmt->get_result();



    if ($result->num_rows > 0) {

        $row = $result->fetch_assoc();

        if (!empty($row['ui_injects'])) {

            $existingData = json_decode($row['ui_injects'], true);



            if (is_array($existingData)) {

                $injectsData = array_merge($injectsData, $existingData);

            }

        }

    }

    $loadStmt->close();

}

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
            --primary: #00478d;
            --primary-container: #005eb8;
            --on-primary: #ffffff;
            --primary-fixed: #d6e3ff;
            --primary-fixed-dim: #a9c7ff;
            --on-primary-fixed: #001b3d;
            --on-primary-fixed-variant: #00468c;
            
            --secondary: #48626e;
            --secondary-container: #cbe7f5;
            --on-secondary: #ffffff;
            --secondary-fixed: #cbe7f5;
            --on-secondary-fixed: #021f29;
            --on-secondary-container: #4e6874;

            --tertiary: #793100;
            --tertiary-container: #9f4300;
            --tertiary-fixed: #ffdbcb;
            --on-tertiary: #ffffff;
            
            --background: #f9f9ff;
            --on-background: #191c21;
            
            --surface: #f9f9ff;
            --on-surface: #191c21;
            --surface-variant: #e1e2ea;
            --on-surface-variant: #424752;
            --surface-container-lowest: #ffffff;
            --surface-container-low: #f2f3fb;
            --surface-container: #ecedf6;
            --surface-container-high: #e7e8f0;
            --surface-container-highest: #e1e2ea;
            
            --outline: #727783;
            --outline-variant: #c2c6d4;
            
            --font-headline: 'Manrope', sans-serif;
            --font-body: 'Inter', sans-serif;
            
            --radius-default: 0.125rem;
            --radius-lg: 0.25rem;
            --radius-xl: 0.5rem;
            --radius-full: 0.75rem;
            --radius-card: 0.75rem;
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

            .content-right {
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

         /* Right Column / Form */
        .content-right {
            flex: 1;
            width: 100%;
        }

        @media (min-width: 1024px) {
            .content-right {
                position: sticky;
            }
        }

        /* Container for the Right-Side Header Info */
        .info-col {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            /* margin-bottom: 2rem; */
            gap: 2rem;
            /* border-bottom: 1px solid var(--outline-variant); */
            padding-bottom: 2rem;
        }

        .info-text {
            flex: 1;
        }

        .info-text h1 {
            font-family: var(--font-headline);
            font-size: 1.5rem;
            font-weight: 800;
            color: var(--on-surface);
            margin-bottom: 0.5rem;
        }

        .info-text p {
            color: var(--on-surface-variant);
            font-size: 1rem;
            line-height: 1.5;
            max-width: 500px;
        }

        /* Total Summary Card - High Contrast Theme */
        .total-summary-card {
            background-color: var(--on-surface); /* Dark Navy/Black anchor */
            color: var(--surface);
            padding: 1.25rem 2rem;
            border-radius: var(--radius-card);
            display: flex;
            flex-direction: column;
            align-items: center;
            min-width: 180px;
            box-shadow: 0 12px 24px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
        }

        .total-summary-card:hover {
            transform: translateY(-2px);
        }

        .total-summary-card h3 {
            font-family: var(--font-headline);
            font-size: 0.75rem;
            font-weight: 700;
            letter-spacing: 0.12em;
            opacity: 0.7;
            margin-bottom: 0.25rem;
        }

        .total-summary-card .total-val {
            font-family: var(--font-headline);
            font-size: 2.5rem;
            font-weight: 800;
            line-height: 1;
        }

        /* Responsive adjustment for the info row */
        @media (max-width: 768px) {
            .info-col {
                flex-direction: column;
                align-items: flex-start;
                gap: 1.5rem;
            }
            
            .total-summary-card {
                width: 100%;
                align-items: center;
            }
        }

        .inject-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 1.5rem;
        }

        @media (min-width: 640px) {
            .inject-grid {
                grid-template-columns: repeat(3, .6fr);
            }
        }

        .inject-card {
            background-color: var(--surface-container-lowest);
            padding: 1.5rem;
            border-radius: var(--radius-xl);
            box-shadow: 0 8px 32px rgba(25, 28, 33, 0.08);
            border: 1px solid rgba(194, 198, 212, 0.15);
            display: flex;
            flex-direction: column;
            gap: 1rem;
            width: 200px;
        }

        .card-header {
            display: flex;
            gap: 12px;
            align-items: center;
        }

        /* Container for the icon and the hidden text */
        .tooltip-wrapper {
            position: relative;
            display: inline-flex;
            margin-left: auto; /* Pushes icon to the right of the header */
            cursor: help;
            align-items: center;
        }

        .info-icon {
            font-size: 18px !important;
            opacity: 0.6;
            transition: opacity 0.3s;
        }

        .info-icon:hover {
            opacity: 1;
        }

        /* Container for the icon */
        /* .tooltip-wrapper {
            position: relative;
            display: flex;
            align-items: center;
            cursor: help;
        } */

        .info-icon {
            font-size: 18px !important;
            color: #666;
            transition: color 0.2s;
        }

        /* The Tooltip Box - Positioned to the LEFT */
        .tooltip-text {
            visibility: hidden;
            width: 200px;
            background-color: rgb(173 172 172 / 95%);
            color: #000;
            border: 1px solid #444;
            padding: 10px;
            position: absolute;
            z-index: 100;
            
            /* Position to the LEFT of the icon */
            right: 140%; 
            top: 50%;
            transform: translateY(-50%); /* Centers the box vertically */
            
            opacity: 0;
            transition: opacity 0.2s ease-in-out;
            font-size: 0.75rem;
            pointer-events: none;
            box-shadow: -5px 5px 15px rgba(0,0,0,0.5);
        }

        /* The Arrow - Positioned on the RIGHT side of the box, Centered Vertically */
        .tooltip-text::after {
            content: "";
            position: absolute;
            top: 50%;
            left: 100%; /* Place it on the right edge of the tooltip box */
            margin-top: -6px; /* Centers the arrow tip */
            border-width: 6px;
            border-style: solid;
            /* Arrow points right towards the icon */
            border-color: transparent transparent transparent #444; 
        }

        /* Hover States */
        .tooltip-wrapper:hover .tooltip-text {
            visibility: visible;
            opacity: 1;
        }

        .tooltip-wrapper:hover .info-icon {
            color: #00d4ff; /* Tactical blue highlight on hover */
        }

        .icon-box {
            padding: 0.75rem;
            border-radius: var(--radius-lg);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .channel-label {
            font-size: 0.75rem;
            font-weight: 700;
            font-family: var(--font-headline);
            letter-spacing: 0.1em;
            text-transform: uppercase;
        }

        .counter-controls {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-top: 1rem;
        }

        .control-btn {
            width: 2.5rem;
            height: 2.5rem;
            border-radius: 9999px;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: var(--surface-container-high);
            color: var(--primary);
            border: none;
            cursor: pointer;
            transition: all 0.2s;
        }

        .control-btn:hover {
            background-color: var(--primary-container);
            color: white;
        }

        .counter-value {
            font-size: 1.875rem;
            font-weight: 800;
            font-family: var(--font-headline);
        }

        /* Styling the number input to look like a clean readout */
.counter-value.channel-input {
    width: 60px;
    background: transparent;
    border: none;
    font-family: var(--font-headline); /* Manrope */
    font-size: 1.5rem;
    font-weight: 800;
    color: var(--on-surface);
    text-align: center;
    padding: 0;
    margin: 0;
    outline: none;
    /* Prevent user from accidentally typing letters if it's a number input */
    -moz-appearance: textfield;
}

/* Remove the 'spin' arrows for Chrome, Safari, Edge, and Opera */
.counter-value.channel-input::-webkit-outer-spin-button,
.counter-value.channel-input::-webkit-inner-spin-button {
    -webkit-appearance: none;
    margin: 0;
}

/* Optional: Add a subtle hover state to show it's editable */
.counter-value.channel-input:hover,
.counter-value.channel-input:focus {
    color: var(--primary);
    cursor: default;
}

        /* Channel-specific styles */
        .bg-email { background-color: var(--primary-fixed-dim); color: var(--primary); }
        .text-email { color: var(--primary); }

        .bg-sms { background-color: var(--secondary-fixed); color: var(--secondary); }
        .text-sms { color: var(--secondary); }

        .bg-tv { background-color: var(--tertiary-fixed); color: var(--tertiary); }
        .text-tv { color: var(--tertiary); }

        .bg-news { background-color: var(--primary-fixed); color: var(--on-primary-fixed-variant); }
        .text-news { color: var(--on-primary-fixed-variant); }

        .bg-social { background-color: var(--secondary-container); color: var(--on-secondary-container); }
        .text-social { color: var(--on-secondary-container); }

        .bg-voice { background-color: var(--surface-container-highest); color: var(--on-surface); }
        .text-voice { color: var(--on-surface); }


        
    </style>
</head>
<body>

<?php include 'stepper.php'; ?>
<main class="main-content">
    <div class="content-grid">
        <!-- Left Side -->
        <div class="editorial-col">
            <div class="hero-image-container">
                <img alt="Industrial assembly line" class="hero-image" src="images/injects.png"/>
                <div class="image-overlay"></div>
            </div>
            <div class="editorial-text">
                <h1>I know you want to deliver surprise injects, give me count of injects across multiple channels</h1>
                <p>Define the intensity of the simulated event by specifying how many injects will be dispatched per medium. Each channel provides a unique tactical pressure point for the participants.</p>
            </div>

        </div>
        <!-- Right Column -->
<div class="content-right">
    <div class="info-col">
        <div class="info-text">
            <h1>Configure your surprise injects</h1>
            <p>Define the intensity of the simulated event by specifying how many injects will be dispatched per medium.</p>
        </div>
                
        <div class="total-summary-card">
            <h3>TOTAL INJECTS</h3>
            <div class="total-val" id="totalDisplay"><?= $injectsData['total'] ?></div>
            <input type="hidden" id="total" name="total" value="<?= $injectsData['total'] ?>">
        </div>
    </div>
    <form method="POST" id="injectForm">
        <div class="inject-grid">
                    <?php 
                    // Mapping DB names to Icons/Colors
                    $configMap = [
                        'email'    => ['icon' => 'mail',                'class' => 'email'],
                        'sms'      => ['icon' => 'sms',                 'class' => 'sms'],
                        'intranet' => ['icon' => 'network_intel_node',  'class' => 'tv'],
                        'news'     => ['icon' => 'newspaper',           'class' => 'news'],
                        'social'   => ['icon' => 'share',               'class' => 'social'],
                        'phone'    => ['icon' => 'call',                'class' => 'voice']
                    ];

                    foreach ($injectTypes as $type): 
                        $key = strtolower($type['in_name']);
                        $value = $injectsData[$key] ?? 0;
                        $ui = $configMap[$key] ?? ['icon' => 'send', 'class' => 'voice'];
                    ?>
                        <div class="inject-card">
                            <!-- <div class="card-header">
                                <div class="icon-box bg-<?= $ui['class'] ?>">
                                    <span class="material-symbols-outlined"><?= $ui['icon'] ?></span>
                                </div>
                                <span class="channel-label text-<?= $ui['class'] ?>"><?= htmlspecialchars($type['in_name']) ?></span>
                            </div> -->
                            <div class="card-header">
                                <div class="icon-box bg-<?= $ui['class'] ?>">
                                    <span class="material-symbols-outlined"><?= $ui['icon'] ?></span>
                                </div>
                                <span class="channel-label text-<?= $ui['class'] ?>"><?= htmlspecialchars($type['in_name']) ?></span>
                                
                                <div class="tooltip-wrapper">
                                    <span class="material-symbols-outlined info-icon">info</span>
                                    <span class="tooltip-text">
                                        Define the communication volume and medium to deliver your scenario. To maintain high engagement without overwhelming participants, a single-stage simulation typically performs best with 6 to 10 overall injects.<br><br>

                                        Under 6 Injects: May fail to provide a comprehensive picture of the crisis or provide enough data for informed decision-making.<br><br>

                                        Over 10 Injects: Risks "information overload," where key scenario "twists" may be lost in the volume of data.

                                        <!-- <strong>Alignment Tip:</strong><br>
                                        Ensure <?= htmlspecialchars($type['in_name']) ?> content is centered for mobile displays. -->
                                    </span>
                                </div>
                            </div>
                            <div class="counter-controls">
                                <button type="button" class="control-btn minus-btn minus">
                                    <span class="material-symbols-outlined">remove</span>
                                </button>
                                
                                <!-- <input type="number" 
                                       name="<?= $key ?>" 
                                       class="counter-value channel-input" 
                                       value="<?= $value ?>" 
                                       min="0"> -->
                                <input type="number" 
                                    name="<?= $key ?>" 
                                    class="counter-value channel-input" 
                                    value="<?= $value ?>" 
                                    min="0"
                                    readonly>
                                <!-- <span class="counter-value" name="<?= $key ?>" 
                                       class="counter-value-input channel-input" 
                                       value="<?= $value ?>" 
                                       min="0">0</span> -->

                                <button type="button" class="control-btn plus-btn plus">
                                    <span class="material-symbols-outlined">add</span>
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
    </form>
</div>
    </div>
</main>
<script>

    document.addEventListener('DOMContentLoaded', function() {



        const inputs = document.querySelectorAll('.channel-input');

        const totalInput = document.getElementById('total');

        const totalDisplay = document.getElementById('totalDisplay');



        function updateTotal() {



            let total = 0;



            inputs.forEach(input => {

                total += parseInt(input.value) || 0;

            });



            totalInput.value = total;

            totalDisplay.textContent = total;



        }



        inputs.forEach(input => {

            input.addEventListener('input', updateTotal);

        });





        document.querySelectorAll('.plus').forEach(btn => {

            btn.addEventListener('click', function() {



                let input = this.parentElement.querySelector('input');

                input.value = parseInt(input.value || 0) + 1;



                updateTotal();



            });

        });





        document.querySelectorAll('.minus').forEach(btn => {

            btn.addEventListener('click', function() {



                let input = this.parentElement.querySelector('input');

                let value = parseInt(input.value || 0);



                if (value > 0) {

                    input.value = value - 1;

                }



                updateTotal();



            });

        });



        updateTotal();



    });

</script>
</body></html>
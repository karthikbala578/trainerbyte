<?php
session_start();
$pageTitle = "Simulation Generated";
require_once __DIR__ . '/../include/dataconnect.php';

$msId = isset($_GET['ms_id']) ? intval($_GET['ms_id']) : 0;

if ($msId <= 0) {
    header("Location: ../index.php");
    exit;
}

// Load master name for display
$msName = "Multi-Stage Simulation";
$stmt = $conn->prepare("SELECT ms_name FROM mg5_ms_digisim_master WHERE ms_id = ? LIMIT 1");
if ($stmt) {
    $stmt->bind_param("i", $msId);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res->num_rows > 0) {
        $msName = $res->fetch_assoc()['ms_name'];
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Simulation Generated</title>
<link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body {
    font-family: 'Inter', sans-serif;
    background: #f8fafc;
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 2rem;
}
.material-symbols-outlined {
    font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
    vertical-align: middle;
}

/* Central card */
.success-card {
    background: #fff;
    border-radius: 28px;
    padding: 48px 40px 40px;
    max-width: 560px;
    width: 100%;
    box-shadow: 0 25px 50px -12px rgba(0,0,0,0.12);
    position: relative;
    text-align: left;
}

/* Floating check icon */
.success-icon-badge {
    position: absolute;
    top: -26px;
    left: 40px;
    width: 52px; height: 52px;
    background: linear-gradient(135deg, #16a34a, #22c55e);
    border: 4px solid #fff;
    border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    box-shadow: 0 8px 20px rgba(34,197,94,0.35);
}
.success-icon-badge .material-symbols-outlined {
    color: #fff; font-size: 26px;
    font-variation-settings: 'FILL' 1, 'wght' 600, 'GRAD' 0, 'opsz' 24;
}

.success-heading {
    font-family: 'Manrope', sans-serif;
    font-size: 1.6rem;
    font-weight: 800;
    color: #0f172a;
    margin-top: 8px;
    margin-bottom: 6px;
}
.success-sub {
    font-size: 0.95rem;
    color: #64748b;
    line-height: 1.55;
    margin-bottom: 32px;
}
.sim-name-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: #eff6ff;
    color: #1e40af;
    border-radius: 20px;
    padding: 5px 14px;
    font-size: 0.85rem;
    font-weight: 600;
    margin-bottom: 28px;
}

/* Action grid */
.action-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 14px;
    margin-bottom: 14px;
}
@media (max-width: 480px) { .action-grid { grid-template-columns: 1fr; } }

.action-card {
    background: #f1f5f9;
    border: 1px solid #e2e8f0;
    border-radius: 18px;
    padding: 22px 20px;
    text-decoration: none;
    transition: all 0.2s;
    display: block;
}
.action-card:hover {
    background: #eff6ff;
    border-color: #bfdbfe;
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(37,99,235,0.1);
}
.action-card-icon {
    width: 38px; height: 38px;
    border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
    margin-bottom: 12px;
}
.action-card strong {
    display: block;
    font-size: 0.9rem;
    font-weight: 700;
    color: #1e293b;
    margin-bottom: 3px;
}
.action-card small {
    font-size: 0.78rem;
    color: #64748b;
}

/* Wide library card */
.library-card {
    background: #f1f5f9;
    border: 1px solid #e2e8f0;
    border-radius: 18px;
    padding: 18px 20px;
    text-decoration: none;
    display: flex;
    align-items: center;
    justify-content: space-between;
    transition: all 0.2s;
}
.library-card:hover {
    background: #fff7ed;
    border-color: #fed7aa;
    transform: translateY(-1px);
}
.library-card-left { display: flex; align-items: center; gap: 14px; }
.library-icon {
    width: 44px; height: 44px;
    background: #ffedd5; color: #c2410c;
    border-radius: 12px;
    display: flex; align-items: center; justify-content: center;
}
.library-card strong { display: block; font-size: 0.9rem; font-weight: 700; color: #1e293b; margin-bottom: 2px; }
.library-card small { font-size: 0.78rem; color: #64748b; }
.chevron { color: #cbd5e1; }
</style>
</head>
<body>
<div class="success-card">

    <div class="success-icon-badge">
        <span class="material-symbols-outlined">check</span>
    </div>

    <h1 class="success-heading">Simulation Generated!</h1>
    <p class="success-sub">Your multistage simulation content has been created successfully. All stages have been combined into one unified experience.</p>

    <div class="sim-name-badge">
        <span class="material-symbols-outlined" style="font-size:16px; color:#2563eb;">auto_awesome</span>
        <?= htmlspecialchars($msName) ?>
    </div>

    <div class="action-grid">
        <a href="/trainerbyte/library.php" class="action-card">
            <div class="action-card-icon" style="background:#dbeafe; color:#2563eb;">
                <span class="material-symbols-outlined" style="font-size:20px;">edit_note</span>
            </div>
            <strong>Review Content</strong>
            <small>Browse and refine the generated stages.</small>
        </a>
        <a href="/trainerbyte/library.php" class="action-card">
            <div class="action-card-icon" style="background:#dcfce7; color:#16a34a;">
                <span class="material-symbols-outlined" style="font-size:20px;">event</span>
            </div>
            <strong>Create an Event</strong>
            <small>Schedule a session with this simulation.</small>
        </a>
    </div>

    <a href="/trainerbyte/library.php" class="library-card">
        <div class="library-card-left">
            <div class="library-icon">
                <span class="material-symbols-outlined">library_books</span>
            </div>
            <div>
                <strong>Return to Library</strong>
                <small>Manage all your simulations and assets.</small>
            </div>
        </div>
        <span class="material-symbols-outlined chevron">chevron_right</span>
    </a>

</div>
</body>
</html>

<?php
$pageTitle = "Byte Guess Prompts";
$pageCSS   = "/admin/styles/byteguess-prompts.css";

session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

require "layout/header.php";
require "../include/dataconnect.php";

$res = $conn->query("SELECT * FROM byteguess_prompts LIMIT 1");
$prompt = $res->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $column = $_POST['column'];
    $value  = $_POST['value'];
    $id     = (int) $_POST['id'];

    $allowed = [
        'step1_setup',
        'step2_scenario',
        'step3_cards',
        'step4_options',
        'step5_answer_key',
        'step6_clues',
        'step7_guidelines'
    ];

    if (in_array($column, $allowed)) {
        $stmt = $conn->prepare("UPDATE byteguess_prompts SET $column = ? WHERE id = ?");
        $stmt->bind_param("si", $value, $id);
        $stmt->execute();
    }

    header("Location: byteguess-prompts.php");
    exit();
}

$steps = [
    'step1_setup'      => 'Step 1 : Setup',
    'step2_scenario'   => 'Step 2 : Scenario',
    'step3_cards'      => 'Step 3 : Cards',
    'step4_options'    => 'Step 4 : Options',
    'step5_answer_key' => 'Step 5 : Answer Key',
    'step6_clues'      => 'Step 6 : Clues',
    'step7_guidelines' => 'Step 7 : guidelines',
];
?>

<div class="prompts-wrapper">

<section class="prompts-hero">
        

        <div class="hero-box">
            <span class="hero-pill">TrainerGenie Admin</span>
            <h1>Game Prompts</h1>
            <p>
                Manage structured prompt flows used by interactive learning games.
                Each game contains step-based prompts maintained here.
            </p>
        </div>

        <div class="back-box">
            <a href="prompts.php" class="back-btn">
                Back
            </a><br>
        </div>

    </section>


    <?php foreach ($steps as $col => $label): ?>
        <div class="step-card">
            <div class="step-head">
                <h3><?= $label ?></h3>
                <button class="edit-btn"
                    onclick="openEditModal(
    '<?= $col ?>',
    '<?= $label ?>',
    `<?= htmlspecialchars(addslashes($prompt[$col])) ?>`
)">
                    Edit
                </button>
            </div>
            <p><?= nl2br(htmlspecialchars($prompt[$col])) ?></p>
        </div>
    <?php endforeach; ?>

</div>

<!-- MODAL -->
<div class="modal-overlay" id="editModal">
    <div class="modal-box">

        <h2 id="modalTitle">Edit Prompt</h2>


        <form method="post">
            <input type="hidden" name="id" value="<?= $prompt['id'] ?>">
            <input type="hidden" name="column" id="modalColumn">

            <div class="modal-scroll">
                <textarea name="value" id="modalValue"></textarea>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn-secondary" onclick="closeModal()">Cancel</button>
                <button type="submit" class="btn-primary">Save</button>
            </div>
        </form>

    </div>
</div>

<script>
    function openEditModal(column, title, value) {
        document.getElementById('modalColumn').value = column;
        document.getElementById('modalValue').value = value;
        document.getElementById('modalTitle').innerText = 'Edit – ' + title;
        document.getElementById('editModal').classList.add('show');
    }

    function closeModal() {
        document.getElementById('editModal').classList.remove('show');
    }
</script>


<?php require "layout/footer.php"; ?>
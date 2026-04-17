<?php
session_start();
require "../include/dataconnect.php";

if (!isset($_SESSION['team_id'])) {
    header("Location: ../login.php");
    exit;
}

$cg_id = intval($_GET['cg_id'] ?? 0);
if ($cg_id <= 0) {
    die("Invalid game");
}

$pageTitle = "Manage Choices";
$pageCSS   = "/library/manage_choices.css";
require "../layout/header.php";

$stmt = $conn->prepare("
    SELECT cg_name, cg_answer
    FROM card_group
    WHERE cg_id = ?
");
$stmt->bind_param("i", $cg_id);
$stmt->execute();
$game = $stmt->get_result()->fetch_assoc();

$answers = json_decode($game['cg_answer'], true) ?? [];
?>

<div class="choices-wrap">

    <div class="editor-header">
        <a href="view_game.php?cg_id=<?= $cg_id ?>" class="back-link">
            ← Back to Game
        </a>
        <h1><?= htmlspecialchars($game['cg_name']) ?> – Answer Choices</h1>
    </div>

    <!-- CENTERED CONTAINER -->
    <div class="answers-container">

        <?php foreach ($answers as $index => $ans): ?>
            <div class="answer-card">

                <div class="answer-inner">

                    <label>Answer Title</label>
                    <input type="text"
                        class="answer-title"
                        value="<?= htmlspecialchars($ans['title']) ?>">

                    <label>Answer Text</label>
                    <textarea class="answer-text" rows="4"><?= htmlspecialchars($ans['answer']) ?></textarea>

                    <div class="meta-row">

                        <div class="meta-field">
                            <label>Answer Type</label>

                            <?php
                            $type = $ans['ans_type'] ?? 'wrong';

                            $typeLabel = match ($type) {
                                'full' => 'Fully Correct',
                                'partial' => 'Partially Correct',
                                'wrong' => 'Distractor',
                                default => ucfirst($type)
                            };
                            ?>

                            <span class="ans-tag <?= $type ?>">
                                <?= $typeLabel ?>
                            </span>

                            <!--  preserve value -->
                            <input type="hidden"
                                class="answer-type"
                                value="<?= htmlspecialchars($type) ?>">
                        </div>

                        <!-- SCORE -->
                        <div class="meta-field">
                            <label>Score</label>
                            <input type="number"
                                class="answer-score"
                                value="<?= htmlspecialchars($ans['score'] ?? 0) ?>">
                        </div>

                    </div>


                </div>

            </div>
        <?php endforeach; ?>


    </div>

    <div class="editor-actions">
        <button class="btn primary" onclick="saveChoices()">Save Changes</button>
    </div>

</div>


<script>
    function saveChoices() {

        const cards = document.querySelectorAll('.answer-card');
        let data = [];

        cards.forEach((card, i) => {

            data.push({
                order: i + 1,
                title: card.querySelector('.answer-title').value.trim(),
                answer: card.querySelector('.answer-text').value.trim(),
                ans_type: card.querySelector('.answer-type').value,
                score: parseInt(card.querySelector('.answer-score').value || 0)
            });

        });

        fetch("save_choices.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify({
                    cg_id: <?= $cg_id ?>,
                    answers: data
                })
            })
            .then(res => res.json())
            .then(res => {
                if (res.status === "success") {
                    alert("Choices updated successfully");
                } else {
                    alert(res.message || "Save failed");
                }
            });

    }
</script>

<?php require "../layout/footer.php"; ?>
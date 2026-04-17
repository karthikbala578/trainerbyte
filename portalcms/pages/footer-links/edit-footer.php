<?php
require_once "../../../include/session_check.php";
require "../../../include/dataconnect.php";

$pageTitle = "Manage Prompts | trainerBYTE CMS";
$pageCSS   = "/portalcms/pages/page-styles/gt-prompts.css";

require "../../layout/header.php";
require "../../layout/navbar.php";

$gt_id = isset($_GET['gt_id']) ? intval($_GET['gt_id']) : 0;
if ($gt_id <= 0) {
    header("Location: prompts.php");
    exit;
}

$gtQuery = mysqli_query($conn, "SELECT * FROM tb_cms_gametype_template WHERE gt_id = $gt_id");
$gameType = mysqli_fetch_assoc($gtQuery);
if (!$gameType) {
    header("Location: prompts.php");
    exit;
}

/* SAVE */
if (isset($_POST['save_all'])) {

    // UPDATE EXISTING
    if (isset($_POST['existing'])) {
        foreach ($_POST['existing'] as $pr_id => $data) {

            $title   = mysqli_real_escape_string($conn, $data['title']);
            $content = mysqli_real_escape_string($conn, $data['content']);
            $status  = isset($data['status']) ? 1 : 0;

            mysqli_query($conn, "
                UPDATE tb_cms_gt_prompt
                SET pr_title='$title',
                    pr_content='$content',
                    pr_status=$status,
                    pr_updated_at=NOW()
                WHERE pr_id=$pr_id
                AND pr_template_id=$gt_id
            ");
        }
    }

    // INSERT NEW
    if (isset($_POST['new'])) {

        $maxQuery = mysqli_query($conn, "
            SELECT MAX(pr_step_no) as max_step 
            FROM tb_cms_gt_prompt 
            WHERE pr_template_id=$gt_id
        ");
        $maxData = mysqli_fetch_assoc($maxQuery);
        $step = ($maxData['max_step'] ?? 0) + 1;

        foreach ($_POST['new'] as $data) {

            $title   = mysqli_real_escape_string($conn, $data['title']);
            $content = mysqli_real_escape_string($conn, $data['content']);
            $status  = isset($data['status']) ? 1 : 0;

            mysqli_query($conn, "
                INSERT INTO tb_cms_gt_prompt
                (pr_template_id, pr_step_no, pr_title, pr_content, pr_status, pr_created_at)
                VALUES
                ($gt_id, $step, '$title', '$content', $status, NOW())
            ");

            $step++;
        }
    }

    header("Location: gt-prompts.php?gt_id=$gt_id&saved=1");
    exit;
}

/* DELETE */
if (isset($_GET['delete'])) {

    $pr_id = intval($_GET['delete']);

    mysqli_query($conn, "
        DELETE FROM tb_cms_gt_prompt
        WHERE pr_id=$pr_id
        AND pr_template_id=$gt_id
    ");

    // reorder steps
    $result = mysqli_query($conn, "
        SELECT pr_id FROM tb_cms_gt_prompt
        WHERE pr_template_id=$gt_id
        ORDER BY pr_step_no ASC
    ");

    $step = 1;
    while ($row = mysqli_fetch_assoc($result)) {
        mysqli_query($conn, "
            UPDATE tb_cms_gt_prompt
            SET pr_step_no=$step
            WHERE pr_id={$row['pr_id']}
        ");
        $step++;
    }

    header("Location: gt-prompts.php?gt_id=$gt_id");
    exit;
}

/* FETCH */
$prompts = mysqli_query($conn, "
    SELECT * FROM tb_cms_gt_prompt
    WHERE pr_template_id=$gt_id
    ORDER BY pr_step_no ASC
");
?>

<!-- QUILL -->
<link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">

<main class="gt-prompts-wrapper">

    <div class="back-div">
        <a href="prompts.php" class="back-btn">Back</a>
    </div>

    <div class="page-header">
        <div>
            <h2>Prompt Management</h2>
            <p>Game Type: <strong><?= htmlspecialchars($gameType['gt_title']); ?></strong></p>
        </div>
        <button type="button" onclick="addStep()" class="add-btn">+ Add New Step</button>
    </div>

    <form method="POST" onsubmit="saveAllContent()">

        <div class="steps-container" id="stepsContainer">

            <?php if (mysqli_num_rows($prompts) == 0): ?>

                <div class="empty-state">
                    <h3>No Prompts Yet</h3>
                    <button type="button" onclick="addStep()" class="empty-add-btn">
                        + Create First Prompt
                    </button>
                </div>

            <?php else: ?>

                <?php while ($row = mysqli_fetch_assoc($prompts)): ?>

                    <div class="step-card">
                        <div class="step-header">
                            <span class="step-badge">Step <?= $row['pr_step_no']; ?></span>
                            <a href="?gt_id=<?= $gt_id ?>&delete=<?= $row['pr_id']; ?>" class="delete-btn">Remove</a>
                        </div>

                        <input type="text"
                               name="existing[<?= $row['pr_id']; ?>][title]"
                               value="<?= htmlspecialchars($row['pr_title']); ?>"
                               class="input-title">

                        <!-- QUILL -->
                        <div class="quill-editor" id="editor-<?= $row['pr_id']; ?>">
                            <?= $row['pr_content']; ?>
                        </div>

                        <input type="hidden"
                               name="existing[<?= $row['pr_id']; ?>][content]"
                               id="hidden-<?= $row['pr_id']; ?>">

                        <label>
                            <input type="checkbox"
                                   name="existing[<?= $row['pr_id']; ?>][status]"
                                   <?= $row['pr_status'] ? 'checked' : ''; ?>>
                            Active
                        </label>
                    </div>

                <?php endwhile; ?>
            <?php endif; ?>

        </div>

        <div class="save-bar">
            <button type="submit" name="save_all" class="save-btn">
                Save All Prompts
            </button>
        </div>

    </form>
</main>

<script src="https://cdn.quilljs.com/1.3.6/quill.min.js"></script>

<script>
let quillEditors = {};
let newStepCount = 0;

/* INIT */
function initQuill(id) {
    const quill = new Quill(`#${id}`, {
        theme: 'snow',
        modules: {
            toolbar: [
                ['bold','italic','underline'],
                [{'list':'ordered'},{'list':'bullet'}],
                ['link'],
                ['clean']
            ]
        }
    });

    quillEditors[id] = quill;
}

/* LOAD EXISTING */
window.onload = function () {
    document.querySelectorAll(".quill-editor").forEach(el => {
        initQuill(el.id);
    });

    <?php if (isset($_GET['saved'])): ?>
        alert("Saved Successfully!");
    <?php endif; ?>
};

/* ADD STEP */
function addStep() {
    newStepCount++;

    const container = document.getElementById("stepsContainer");

    const editorId = `new-editor-${newStepCount}`;

    const card = document.createElement("div");
    card.className = "step-card";

    card.innerHTML = `
        <div class="step-header">
            <span class="step-badge">New Step</span>
            <a class="delete-btn" onclick="this.closest('.step-card').remove()">Remove</a>
        </div>

        <input type="text"
               name="new[${newStepCount}][title]"
               placeholder="Prompt Title"
               class="input-title">

        <div class="quill-editor" id="${editorId}"></div>

        <input type="hidden"
               name="new[${newStepCount}][content]"
               id="new-hidden-${newStepCount}">

        <label>
            <input type="checkbox" name="new[${newStepCount}][status]" checked>
            Active
        </label>
    `;

    container.appendChild(card);

    initQuill(editorId);
}

/* SAVE CONTENT */
function saveAllContent() {

    Object.keys(quillEditors).forEach(id => {

        let content = quillEditors[id].root.innerHTML;

        if (id.startsWith("editor-")) {
            let prId = id.replace("editor-", "");
            document.getElementById(`hidden-${prId}`).value = content;
        }

        if (id.startsWith("new-editor-")) {
            let newId = id.replace("new-editor-", "");
            document.getElementById(`new-hidden-${newId}`).value = content;
        }
    });
}
</script>

<?php require "../../layout/footer.php"; ?>
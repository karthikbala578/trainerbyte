<?php
$pageTitle = "Genie Exercises";
$pageCSS   = "/admin/styles/exercises.css";

session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

require "layout/header.php";
require "../include/dataconnect.php";

/* DELETE EXERCISE */
if (isset($_POST['delete_exercise'])) {

    $deleteId = (int) $_POST['delete_id'];

    // Get image name
    $stmt = $conn->prepare("
        SELECT ex_image
        FROM tb_exercise_type
        WHERE ex_id = ?
    ");
    $stmt->bind_param("i", $deleteId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    // Delete image file
    if (!empty($row['ex_image'])) {
        $imgPath = "../upload-images/exercise-pics/" . $row['ex_image'];
        if (file_exists($imgPath)) {
            unlink($imgPath);
        }
    }

    // Delete record
    $stmt = $conn->prepare("
        DELETE FROM tb_exercise_type
        WHERE ex_id = ?
    ");
    $stmt->bind_param("i", $deleteId);
    $stmt->execute();

    header("Location: exercises.php");
    exit();
}

/* ADD / UPDATE EXERCISE */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['delete_exercise'])) {

    $exId = $_POST['ex_id'] ?? '';
    $name = trim($_POST['ex_name']);
    $des  = trim($_POST['ex_des']);
    $tag  = trim($_POST['ex_tag'] ?? 'General');

    $uploadDir = "../upload-images/exercise-pics/";
    $imageName = null;

    // Image upload (optional)
    if (!empty($_FILES['ex_image']['name'])) {
        $ext = strtolower(pathinfo($_FILES['ex_image']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];

        if (in_array($ext, $allowed)) {
            $imageName = uniqid("ex_") . "." . $ext;
            move_uploaded_file($_FILES['ex_image']['tmp_name'], $uploadDir . $imageName);
        }
    }

    // ---------- UPDATE ----------
    if (!empty($exId)) {

        if ($imageName) {
            $stmt = $conn->prepare("
                UPDATE tb_exercise_type
                SET ex_name = ?, ex_des = ?, ex_tag = ?, ex_image = ?
                WHERE ex_id = ?
            ");
            $stmt->bind_param("ssssi", $name, $des, $tag, $imageName, $exId);
        } else {
            $stmt = $conn->prepare("
                UPDATE tb_exercise_type
                SET ex_name = ?, ex_des = ?, ex_tag = ?
                WHERE ex_id = ?
            ");
            $stmt->bind_param("sssi", $name, $des, $tag, $exId);
        }

        $stmt->execute();
    }

    // ---------- INSERT ----------
    else {
        $stmt = $conn->prepare("
            INSERT INTO tb_exercise_type (ex_name, ex_des, ex_tag, ex_image)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->bind_param("ssss", $name, $des, $tag, $imageName);
        $stmt->execute();
    }

    header("Location: exercises.php");
    exit();
}

/*FETCH EXERCISES */
$exercises = [];
$res = $conn->query("
    SELECT ex_id, ex_name, ex_des, ex_tag, ex_image
    FROM tb_exercise_type
    ORDER BY ex_id DESC
");
while ($row = $res->fetch_assoc()) {
    $exercises[] = $row;
}
?>

<div class="exercise-wrapper">

    <!-- HERO SECTION -->
    <section class="exercise-hero">
        <div class="hero-content">
            <span class="hero-pill">TrainerGenie Admin</span>

            <h1>Genie Exercises</h1>

            <p>
                Create, manage, and organize interactive training exercises
                used across your platform. Everything in one place.
            </p>

            <button class="btn-primary" onclick="openAddModal()">
                + Create New Exercise
            </button>
        </div>

        <div class="back-box">
            <a href="index.php" class="back-btn">
                    Back
                </a><br>
        </div>
    </section>

    <!-- EXERCISE GRID -->
    <section class="exercise-section">

        <div class="exercise-grid">
            <?php foreach ($exercises as $ex): ?>
                <div class="exercise-card">

                    <div class="exercise-img">
                        <span class="tag">
                            <?= htmlspecialchars($ex['ex_tag'] ?? 'General') ?>
                        </span>

                        <?php if (!empty($ex['ex_image'])): ?>
                            <img src="/trainergenie/upload-images/exercise-pics/<?= htmlspecialchars($ex['ex_image']) ?>">
                        <?php else: ?>
                            <div class="img-placeholder">No Image</div>
                        <?php endif; ?>
                    </div>

                    <div class="exercise-body">
                        <h3><?= htmlspecialchars($ex['ex_name']) ?></h3>
                        <p><?= htmlspecialchars(substr($ex['ex_des'], 0, 95)) ?>...</p>

                        <div class="card-actions">

                            <button class="btn-link"
                                onclick="openEditModal(
                                    '<?= $ex['ex_id'] ?>',
                                    '<?= htmlspecialchars(addslashes($ex['ex_name'])) ?>',
                                    '<?= htmlspecialchars(addslashes($ex['ex_des'])) ?>',
                                    '<?= htmlspecialchars(addslashes($ex['ex_tag'] ?? '')) ?>'
                                )">
                                Edit
                            </button>

                            <form method="post"
                                  onsubmit="return confirm('Delete this exercise permanently?');">
                                <input type="hidden" name="delete_id" value="<?= $ex['ex_id'] ?>">
                                <button type="submit"
                                        name="delete_exercise"
                                        class="btn-delete">
                                    Delete
                                </button>
                            </form>

                        </div>
                    </div>

                </div>
            <?php endforeach; ?>
        </div>

    </section>

</div>

<!-- ================= MODAL ================= -->
<div class="modal-overlay" id="exerciseModal">
    <div class="modal-box">

        <h2 id="modalTitle">Add Exercise</h2>

        <form method="post" enctype="multipart/form-data">
            <input type="hidden" name="ex_id" id="ex_id">

            <div class="form-group">
                <label>Exercise Name</label>
                <input type="text" name="ex_name" id="ex_name" required>
            </div>

            <div class="form-group">
                <label>Exercise Tag</label>
                <input type="text" name="ex_tag" id="ex_tag" required>
            </div>

            <div class="form-group">
                <label>Description</label>
                <textarea name="ex_des" id="ex_des" rows="4" required></textarea>
            </div>

            <div class="form-group">
                <label>Exercise Image</label>
                <input type="file" name="ex_image">
                <small>Leave empty to keep existing image</small>
            </div>

            <div class="modal-actions">
                <button type="button" class="btn-secondary" onclick="closeModal()">Cancel</button>
                <button type="submit" class="btn-primary" id="modalBtn">Save</button>
            </div>
        </form>

    </div>
</div>

<script>
function openAddModal() {
    document.getElementById('ex_id').value = '';
    document.getElementById('ex_name').value = '';
    document.getElementById('ex_des').value = '';
    document.getElementById('ex_tag').value = '';
    document.getElementById('modalTitle').innerText = 'Add Exercise';
    document.getElementById('modalBtn').innerText = 'Save Exercise';
    document.getElementById('exerciseModal').classList.add('show');
}

function openEditModal(id, name, des, tag) {
    document.getElementById('ex_id').value = id;
    document.getElementById('ex_name').value = name;
    document.getElementById('ex_des').value = des;
    document.getElementById('ex_tag').value = tag;
    document.getElementById('modalTitle').innerText = 'Edit Exercise';
    document.getElementById('modalBtn').innerText = 'Update Exercise';
    document.getElementById('exerciseModal').classList.add('show');
}

function closeModal() {
    document.getElementById('exerciseModal').classList.remove('show');
}
</script>

<?php require "layout/footer.php"; ?>

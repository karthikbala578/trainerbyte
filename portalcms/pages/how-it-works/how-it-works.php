<?php
require_once "../../../include/session_check.php";
require "../../../include/dataconnect.php";

$pageTitle = "How It Works | trainerBYTE CMS";
$pageCSS   = "/portalcms/pages/page-styles/how-it-works.css";

require "../../layout/header.php";
require "../../layout/navbar.php";

if (isset($_POST['reorder_ajax'])) {
    $order = $_POST['order'];
    foreach ($order as $index => $id) {
        $step = $index + 1;
        mysqli_query($conn, "UPDATE tb_cms_howitworks SET hw_step_no='$step' WHERE hw_id='$id'");
    }
    echo "success";
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && !isset($_POST['reorder_ajax'])) {

    $id     = intval($_POST['hw_id']);
    $title  = mysqli_real_escape_string($conn, $_POST['hw_title']);
    $desc   = mysqli_real_escape_string($conn, $_POST['hw_description']);
    $status = intval($_POST['hw_status']);

    $imageName = "";
    $imgSql    = "";

    if (isset($_FILES['hw_image']) && $_FILES['hw_image']['error'] === 0) {
        $uploadDir = __DIR__ . "/howitworks-uploads/";
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

        $fileName = time() . "_" . basename($_FILES['hw_image']['name']);
        $target   = $uploadDir . $fileName;

        if (move_uploaded_file($_FILES['hw_image']['tmp_name'], $target)) {
            $imageName = $fileName;
            $imgSql = ", hw_image='$fileName'";
        }
    }

    if ($id == 0) {
        $next = mysqli_fetch_row(mysqli_query($conn, "SELECT IFNULL(MAX(hw_step_no),0)+1 FROM tb_cms_howitworks"))[0];
        mysqli_query($conn, "INSERT INTO tb_cms_howitworks
        (hw_step_no,hw_title,hw_description,hw_image,hw_status,hw_created_at)
        VALUES('$next','$title','$desc','$imageName','$status',NOW())");
    } else {
        mysqli_query($conn, "UPDATE tb_cms_howitworks SET
        hw_title='$title',
        hw_description='$desc',
        hw_status='$status'
        $imgSql,
        hw_updated_at=NOW()
        WHERE hw_id='$id'");
    }

    header("Location: how-it-works.php");
    exit;
}

$result = mysqli_query($conn, "SELECT * FROM tb_cms_howitworks ORDER BY hw_step_no ASC");
?>

<main class="hiw-page">
<div class="back-div">
        <a href="../../index.php" class="back-btn"> Back</a>

 </div>

    <div class="hiw-header">
        <div>
            <h1>How It Works</h1>
            <p>Manage homepage steps</p>
        </div>
        <button class="add-btn" onclick="openModal()">+ Add Step</button>
    </div>

    <div class="card-container" id="sortable">

        <?php while ($row = mysqli_fetch_assoc($result)):
            $img = !empty($row['hw_image']) ? "./howitworks-uploads/" . $row['hw_image'] : "../../assets/images/default-banner.png";
            $json = htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8');
        ?>

            <div class="step-card" draggable="true" data-id="<?php echo $row['hw_id']; ?>">

                <div class="card-left">
                    <div class="drag-handle">☰</div>
                    <div class="step-number"><?php echo str_pad($row['hw_step_no'], 2, "0", STR_PAD_LEFT); ?></div>
                </div>

                <div class="card-content">
                    <img src="<?php echo $img; ?>">
                    <div class="content-text">
                        <h3><?php echo $row['hw_title']; ?></h3>
                        <p><?php echo $row['hw_description']; ?></p>
                        <span class="badge <?php echo $row['hw_status'] == 1 ? 'active' : 'inactive'; ?>">
                            <?php echo $row['hw_status'] == 1 ? 'Active' : 'Inactive'; ?>
                        </span>
                    </div>
                </div>

                <div class="card-actions">
                    <button class="edit-btn" onclick='editStep(event, <?php echo $json; ?>)'>Edit</button>
                </div>

            </div>

        <?php endwhile; ?>

    </div>

    <div id="modal" class="modal">
        <div class="modal-box">

            <div class="modal-top">
                <h2 id="modalTitle">Add Step</h2>
                <button onclick="closeModal()">×</button>
            </div>

            <form method="POST" enctype="multipart/form-data">

                <input type="hidden" name="hw_id" id="hw_id" value="0">

                <div class="form-group">
                    <label>Title</label>
                    <input type="text" name="hw_title" id="hw_title" required>
                </div>

                <div class="form-group">
                    <label>Description</label>
                    <textarea name="hw_description" id="hw_description" rows="4" required></textarea>
                </div>

                <div class="form-group">
                    <label>Image</label>
                    <input type="file" name="hw_image">
                </div>

                <div class="form-group">
                    <label>Status</label>
                    <select name="hw_status" id="hw_status">
                        <option value="1">Active</option>
                        <option value="0">Inactive</option>
                    </select>
                </div>

                <div class="modal-actions">
                    <button type="submit" class="save-btn">Save</button>
                    <button type="button" onclick="closeModal()" class="cancel-btn">Cancel</button>
                </div>

            </form>

        </div>
    </div>

</main>

<script>
    function openModal() {
        document.getElementById('modalTitle').innerText = "Add Step";
        document.getElementById('hw_id').value = 0;
        document.getElementById('hw_title').value = "";
        document.getElementById('hw_description').value = "";
        document.getElementById('hw_status').value = 1;
        document.getElementById('modal').style.display = "flex";
    }

    function editStep(e, data) {
        e.stopPropagation();
        document.getElementById('modalTitle').innerText = "Edit Step";
        document.getElementById('hw_id').value = data.hw_id;
        document.getElementById('hw_title').value = data.hw_title;
        document.getElementById('hw_description').value = data.hw_description;
        document.getElementById('hw_status').value = data.hw_status;
        document.getElementById('modal').style.display = "flex";
    }

    function closeModal() {
        document.getElementById('modal').style.display = "none";
    }

    const sortable = document.getElementById("sortable");
    let dragged = null;

    sortable.addEventListener("dragstart", e => {
        dragged = e.target.closest(".step-card");
        dragged.classList.add("dragging");
    });

    sortable.addEventListener("dragover", e => {
        e.preventDefault();
        const target = e.target.closest(".step-card");
        if (!target || target === dragged) return;
        const rect = target.getBoundingClientRect();
        const offset = e.clientY - rect.top;
        if (offset > rect.height / 2) {
            sortable.insertBefore(dragged, target.nextSibling);
        } else {
            sortable.insertBefore(dragged, target);
        }
    });

    sortable.addEventListener("dragend", () => {
        dragged.classList.remove("dragging");

        const cards = document.querySelectorAll(".step-card");
        let order = [];

        cards.forEach((card, index) => {
            order.push(card.dataset.id);
            card.querySelector(".step-number").innerText = String(index + 1).padStart(2, "0");
        });

        fetch("how-it-works.php", {
            method: "POST",
            headers: {
                "Content-Type": "application/x-www-form-urlencoded"
            },
            body: "reorder_ajax=1&" + order.map((id, i) => "order[" + i + "]=" + id).join("&")
        });
    });
</script>

<?php require "../../layout/footer.php"; ?>
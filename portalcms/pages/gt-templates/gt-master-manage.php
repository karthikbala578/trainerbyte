<?php
require_once "../../../include/session_check.php";
require "../../../include/dataconnect.php";

$type = $_GET['type'] ?? '';

$masters = [
    "category" => [
        "table" => "tb_cms_category",
        "id"    => "ct_id",
        "name"  => "ct_name",
        "status" => "ct_status",
        "label" => "Category"
    ],
    "gametype" => [
        "table" => "tb_cms_gametype",
        "id"    => "gm_id",
        "name"  => "gm_name",
        "status" => "gm_status",
        "label" => "Game Type"
    ],
    "duration" => [
        "table" => "tb_cms_duration",
        "id"    => "dr_id",
        "name"  => "dr_name",
        "status" => "dr_status",
        "label" => "Duration"
    ],
    "format" => [
        "table" => "tb_cms_format",
        "id"    => "fm_id",
        "name"  => "fm_name",
        "status" => "fm_status",
        "label" => "Format"
    ],
    "complexity" => [
        "table" => "tb_cms_complexity",
        "id"    => "cx_id",
        "name"  => "cx_name",
        "status" => "cx_status",
        "label" => "Complexity"
    ],
];

if (!array_key_exists($type, $masters)) {
    die("Invalid master type.");
}

$config = $masters[$type];

$pageTitle = $config['label'] . " Management | trainerBYTE CMS";
$pageCSS   = "/pages/page-styles/gt-master-manage.css";

require "../../layout/header.php";
require "../../layout/navbar.php";

/* SAVE */
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $id     = intval($_POST['item_id']);
    $name   = mysqli_real_escape_string($conn, $_POST['item_name']);
    $status = intval($_POST['item_status']);

    if ($id == 0) {
        $sql = "INSERT INTO {$config['table']}
                ({$config['name']}, {$config['status']})
                VALUES ('$name', '$status')";
    } else {
        $sql = "UPDATE {$config['table']} SET
                {$config['name']}='$name',
                {$config['status']}='$status'
                WHERE {$config['id']}=$id";
    }

    mysqli_query($conn, $sql);
    header("Location: gt-master-manage.php?type=$type");
    exit();
}

$result = mysqli_query(
    $conn,
    "SELECT * FROM {$config['table']}
     ORDER BY {$config['id']} DESC"
);
?>



<main class="master-wrapper">
<div class="back-div">
        <a href="gt-templates.php" class="back-btn"> Back</a>

 </div>
   
    <!-- HEADER -->
    <div class="master-header">
    

        <div class="header-block">

            <div class="header-top">

                <h1><?php echo $config['label']; ?> Management</h1>
            </div>

            <p>Manage <?php echo strtolower($config['label']); ?> master data.</p>

        </div>

        <button class="btn-primary" onclick="openCreator()">+ Add New</button>

    </div>

    <!-- GRID -->
    <div class="master-grid">

        <!-- LEFT LIST -->
        <div class="master-list">
            <?php while ($row = mysqli_fetch_assoc($result)):
                $json = htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8');
            ?>
                <div class="master-item"
                    onclick='selectItem(<?php echo $json; ?>, this)'>

                    <div class="item-name">
                        <?php echo $row[$config['name']]; ?>
                    </div>

                    <div class="item-status 
                        <?php echo ($row[$config['status']] == 1) ? 'active' : 'inactive'; ?>">
                        <?php echo ($row[$config['status']] == 1) ? 'Active' : 'Inactive'; ?>
                    </div>

                </div>
            <?php endwhile; ?>
        </div>

        <!-- RIGHT PREVIEW -->
        <div class="master-preview">

            <div id="emptyState" class="empty-state">
                <div class="empty-icon">📂</div>
                <h3>Select any item</h3>
                <p>Choose a master item from the left panel to edit.</p>
            </div>

            <div id="previewContent" style="display:none;">
                <h2 id="previewTitle"></h2>
                <div id="previewStatus" class="status-badge"></div>
                <div class="preview-actions">
                    <button class="edit-btn" onclick="openEditor()">Edit</button>
                </div>
            </div>

        </div>

    </div>

    <!-- MODAL -->

    <div id="formModal" class="modal">
        <div class="modal-content">

            <div class="modal-header">
                <h2 id="modalTitle">Add Item</h2>
                <button type="button" onclick="closeModal()" class="close-btn">×</button>
            </div>

            <form method="POST">

                <input type="hidden" id="inp_id" name="item_id" value="0">

                <div class="form-group">
                    <label>Category Name</label>
                    <input type="text" id="inp_name" name="item_name" required>
                </div>

                <div class="form-group">
                    <label>Status</label>
                    <select id="inp_status" name="item_status">
                        <option value="1">Active</option>
                        <option value="0">Inactive</option>
                    </select>
                </div>

                <div class="modal-actions">
                    <button type="button" class="btn-secondary" onclick="closeModal()">
                        Cancel
                    </button>

                    <button type="submit" class="btn-primary-modal">
                        Save
                    </button>
                </div>

            </form>

        </div>
    </div>

</main>

<script>
    let selectedData = null;

    function selectItem(data, el) {
        document.querySelectorAll('.master-item')
            .forEach(i => i.classList.remove('selected'));
        el.classList.add('selected');

        selectedData = data;

        document.getElementById("emptyState").style.display = "none";
        document.getElementById("previewContent").style.display = "block";

        document.getElementById("previewTitle").innerText =
            data["<?php echo $config['name']; ?>"];

        const statusEl = document.getElementById("previewStatus");

        if (data["<?php echo $config['status']; ?>"] == 1) {
            statusEl.innerText = "Active";
            statusEl.className = "status-badge active";
        } else {
            statusEl.innerText = "Inactive";
            statusEl.className = "status-badge inactive";
        }
    }

    function openCreator() {
        selectedData = null;
        document.getElementById("modalTitle").innerText =
            "Add <?php echo $config['label']; ?>";
        document.getElementById("formModal").classList.add("show");
        document.getElementById("inp_id").value = 0;
        document.getElementById("inp_name").value = "";
        document.getElementById("inp_status").value = 1;
    }

    function openEditor() {
        if (!selectedData) return;
        document.getElementById("modalTitle").innerText =
            "Edit <?php echo $config['label']; ?>";
        document.getElementById("formModal").classList.add("show");
        document.getElementById("inp_id").value =
            selectedData["<?php echo $config['id']; ?>"];
        document.getElementById("inp_name").value =
            selectedData["<?php echo $config['name']; ?>"];
        document.getElementById("inp_status").value =
            selectedData["<?php echo $config['status']; ?>"];
    }

    function closeModal() {
        document.getElementById("formModal").classList.remove("show");
    }

    window.addEventListener("click", function(e) {
        const modal = document.getElementById("formModal");
        if (e.target === modal) {
            modal.classList.remove("show");
        }
    });
</script>

<?php require "../../layout/footer.php"; ?>
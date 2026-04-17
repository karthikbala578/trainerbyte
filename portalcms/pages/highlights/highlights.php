<?php
require_once "../../../include/session_check.php";
require "../../../include/dataconnect.php";

$pageTitle = "Highlights Management | trainerBYTE CMS";
$pageCSS   = "/portalcms/pages/page-styles/highlights.css";

require "../../layout/header.php";
require "../../layout/navbar.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $id     = intval($_POST['hl_id']);
    $title  = mysqli_real_escape_string($conn, $_POST['hl_title']);
    $desc   = mysqli_real_escape_string($conn, $_POST['hl_description']);
    $status = isset($_POST['hl_status']) ? intval($_POST['hl_status']) : 1;

    $iconName = "";
    $iconSql  = "";

    if (isset($_FILES['hl_icon']) && $_FILES['hl_icon']['error'] === 0) {

        $uploadDir = __DIR__ . "/highlights-uploads/";

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $fileName = time() . "_" . basename($_FILES['hl_icon']['name']);
        $targetPath = $uploadDir . $fileName;

        if (move_uploaded_file($_FILES['hl_icon']['tmp_name'], $targetPath)) {
            $iconName = $fileName;
            $iconSql = ", hl_icon='$fileName'";
        }
    }

    if ($id == 0) {
        $sql = "INSERT INTO tb_cms_highlight
                (hl_title, hl_description, hl_icon, hl_status, hl_created_at)
                VALUES
                ('$title','$desc','$iconName','$status',NOW())";
    } else {
        $sql = "UPDATE tb_cms_highlight SET
                hl_title='$title',
                hl_description='$desc',
                hl_status='$status'
                $iconSql,
                hl_updated_at=NOW()
                WHERE hl_id=$id";
    }

    mysqli_query($conn, $sql);
    header("Location: highlights.php");
    exit();
}

$result = mysqli_query($conn, "SELECT * FROM tb_cms_highlight ORDER BY hl_id DESC");
?>

<main class="highlight-page">



<div class="left-panel">
<div class="back-div">
        <a href="../../index.php" class="back-btn"> Back</a>

 </div>

    <div class="panel-top">
        <div>
            <h1>Portal Highlights</h1>
            <p>Manage portal feature highlights</p>
        </div>
        <button class="add-btn" onclick="openCreator()">+ Add New</button>
    </div>

    <div class="highlight-list">

        <?php while($row = mysqli_fetch_assoc($result)):
            $img = !empty($row['hl_icon'])
                ? "./highlights-uploads/".$row['hl_icon']
                : "../../assets/images/default-banner.png"; 
            $json = htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8');
        ?>

        <div class="hl-item" onclick='selectHighlight(<?php echo $json; ?>, this)'>

            <div class="hl-icon">
                <img src="<?php echo $img; ?>">
            </div>

            <div class="hl-info">
                <h3><?php echo $row['hl_title']; ?></h3>
                <span class="status-dot <?php echo ($row['hl_status']==1)?'active':'inactive'; ?>"></span>
            </div>

        </div>

        <?php endwhile; ?>

    </div>

</div>

<div class="right-panel">

    <div class="preview-box">

        <div class="preview-icon">
            <img id="previewIcon" src="../../assets/images/default-banner.png">
        </div>

        <h2 id="previewTitle">Select a Highlight</h2>

        <p id="previewDesc">
            Click any highlight from the left panel to preview and edit it here.
        </p>

        <div id="previewStatus" class="preview-status active">
            Active
        </div>

        <button class="edit-btn" onclick="openEditor()">Edit Highlight</button>

    </div>

</div>

<div id="formModal" class="modal">

    <div class="modal-content">

        <div class="modal-header">
            <h2 id="modalTitle">Add Highlight</h2>
            <button type="button" onclick="closeModal()" class="modal-close">×</button>
        </div>

        <form method="POST" enctype="multipart/form-data" class="modal-form">

            <input type="hidden" id="inp_id" name="hl_id" value="0">

            <div class="form-group">
                <label>Highlight Title</label>
                <input type="text" id="inp_title" name="hl_title" required>
            </div>

            <div class="form-group">
                <label>Description</label>
                <textarea id="inp_desc" name="hl_description" rows="4" required></textarea>
            </div>

            <div class="form-group">
                <label>Icon Image</label>
                <input type="file" name="hl_icon" accept="image/*">
            </div>

            <div class="form-group">
                <label>Status</label>
                <select id="inp_status" name="hl_status">
                    <option value="1">Active</option>
                    <option value="0">Inactive</option>
                </select>
            </div>

            <div class="modal-actions">
                <button type="submit" class="save-btn">Save Highlight</button>
                <button type="button" onclick="closeModal()" class="close-btn">Cancel</button>
            </div>

        </form>

    </div>

</div>

</main>

<script>

let selectedData = null;

function selectHighlight(data, el){
    document.querySelectorAll('.hl-item').forEach(i=>i.classList.remove('selected'));
    el.classList.add('selected');
    selectedData = data;

    document.getElementById("previewIcon").src =
        data.hl_icon ? "./highlights-uploads/" + data.hl_icon
        : "../../assets/images/default-banner.png";

    document.getElementById("previewTitle").innerText = data.hl_title;
    document.getElementById("previewDesc").innerText = data.hl_description;

    const statusEl = document.getElementById("previewStatus");

    if(data.hl_status == 1){
        statusEl.innerText = "Active";
        statusEl.className = "preview-status active";
    }else{
        statusEl.innerText = "Inactive";
        statusEl.className = "preview-status inactive";
    }
}

function openCreator(){
    selectedData = null;
    document.getElementById('modalTitle').innerText = "Add Highlight";
    document.getElementById('formModal').style.display = 'flex';
    document.getElementById('inp_id').value = "0";
    document.getElementById('inp_title').value = "";
    document.getElementById('inp_desc').value = "";
    document.getElementById('inp_status').value = "1";
}

function openEditor(){
    if(!selectedData) return;
    document.getElementById('modalTitle').innerText = "Edit Highlight";
    document.getElementById('formModal').style.display = 'flex';
    document.getElementById('inp_id').value = selectedData.hl_id;
    document.getElementById('inp_title').value = selectedData.hl_title;
    document.getElementById('inp_desc').value = selectedData.hl_description;
    document.getElementById('inp_status').value = selectedData.hl_status;
}

function closeModal(){
    document.getElementById('formModal').style.display = 'none';
}

</script>

<?php require "../../layout/footer.php"; ?>
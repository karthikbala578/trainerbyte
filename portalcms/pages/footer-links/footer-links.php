<?php

require_once "../../../include/session_check.php";
require "../../../include/dataconnect.php";

$pageTitle = "Footer Links Management";
$pageCSS   = "/portalcms/pages/page-styles/footer-links.css";

require "../../layout/header.php";
require "../../layout/navbar.php";

$q = mysqli_query($conn, "SELECT * FROM tb_cms_footer_links");

?>

<div class="dashboard-wrapper">

    <div class="back-div">
        <a href="../../index.php" class="back-btn"> Back</a>

    </div>

    <div class="dashboard-header">
        <p class="tab">CMS Home > Footer Links</p>
        <h1>Manage Footer Content</h1>
        <p>Edit content for Company and Terms links shown in the website footer.</p>
    </div>

    <div class="table-container">

        <table class="cms-table">

            <thead>
                <tr>
                    <th>ID</th>
                    <th>Title</th>
                    <th>Last Updated</th>
                    <th>Action</th>
                </tr>
            </thead>

            <tbody>

                <?php while ($row = mysqli_fetch_assoc($q)) { ?>

                    <tr>
                        <td><?= $row['fl_id'] ?></td>

                        <td><?= $row['fl_title'] ?></td>

                        <td><?= $row['fl_updated_at'] ?></td>

                        <td>

                            <a href="edit-footer.php?id=<?= $row['fl_id'] ?>"
                                class="edit-btn">

                                Edit

                            </a>

                        </td>

                    </tr>

                <?php } ?>

            </tbody>

        </table>

    </div>

</div>

<?php require "../../layout/footer.php"; ?>
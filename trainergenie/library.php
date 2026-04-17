<?php
require_once "include/session_check.php";
require "include/dataconnect.php";



if (!isset($_SESSION['team_id'])) {

    header("Location: login.php");

    exit;

}



$pageTitle = "Exercise Library";

$pageCSS   = "/assets/styles/library.css";

require "layout/header.php";



/* Fetch lg_id(s) for this team */
    $stmt = $conn->prepare("

        SELECT cg_id, cg_name, cg_status, createddate

        FROM card_group

        WHERE byteguess_pkid = ?

        ORDER BY cg_id DESC

    ");

    $stmt->bind_param("i", $_SESSION['team_id']);

    $stmt->execute();

    $games = $stmt->get_result();

?>



<div class="library-wrap">



    <!-- HEADER -->

    <h1 class="library-title">Exercise Library</h1>



    <!-- TOP CARD -->

    <div class="library-top">

        <div class="create-card">

            <h3>Create Exercise Library</h3>

            <p>Organize your card games into reusable learning experiences.</p>

            <a href="/trainergenie/byteguess_exercise.php" class="btn primary">Create Library →</a>

        </div>

    </div>

    <br>



    <h1 class="library-title">My Library</h1>



    <div class="library-search">

        <input

            type="text"

            placeholder="Search exercises..."

            onkeyup="searchLibrary(this.value)"

            class="search-input">

    </div>



    <!-- FILTERS -->

    <div class="

    -filters">

        <button class="filter active">All Types</button>

        <button class="filter active">Card Games</button>

    </div>



    <!-- TABLE -->

    <div class="library-table">



        <div class="table-head">

            <div>Exercise Name</div>

            <div>Type</div>

            <div>Status</div>

            <div>Last Modified</div>

            <div>Actions</div>

        </div>



        <?php if ($games && $games->num_rows > 0): ?>
           
            <?php while ($g = $games->fetch_assoc()){ ?>

                <div class="table-row">

                    <div class="name">

                        <strong><?= htmlspecialchars($g['cg_name']) ?></strong>



                    </div>



                    <div>

                        <span class="badge blue">Card Game</span>

                    </div>



                    <div>

                        <?php if ($g['cg_status'] == 1): ?>

                            <span class="status published">Published</span>

                        <?php else: ?>

                            <span class="status draft">Draft</span>

                        <?php endif; ?>

                    </div>



                    <div>

                        <?= date("M d, Y", strtotime($g['createddate'] ?? 'now')) ?>

                    </div>



                    <div>

                        <a href="library/view_game.php?cg_id=<?= $g['cg_id'] ?>" class="action-link">

                            View →

                        </a>

                    </div>

                </div>

            <?php } ?>

        <?php else: ?>

            <div class="empty">

                No card games created yet.

            </div>

        <?php endif; ?>



    </div>



</div>

<script>

function searchLibrary(text) {



    text = text.toLowerCase();



    document.querySelectorAll(".table-row").forEach(row => {



        const name = row.querySelector(".name strong")

                        .innerText

                        .toLowerCase();



        row.style.display = name.includes(text)

            ? "grid"

            : "none";



    });

}

</script>



<?php require "layout/footer.php"; ?>
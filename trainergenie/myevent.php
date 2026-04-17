<?php

require_once "include/session_check.php";   

require "include/dataconnect.php";



if (!isset($_SESSION['team_id'])) {

    header("Location: login.php");

    exit;

}



$pageTitle = "My Events";

$pageCSS   = "/assets/styles/myevent.css";

require "layout/header.php";



/* Fetch events */

$stmt = $conn->prepare("

    SELECT

        event_id,

        event_name,

        event_description,

        event_coverimage,

        event_start_date,

        event_playstatus

    FROM tb_events

    WHERE event_team_pkid = ?

      AND event_status = 1

    ORDER BY event_createddate DESC

");

$stmt->bind_param("i", $_SESSION['team_id']);

$stmt->execute();

$events = $stmt->get_result();

?>



<div class="event-wrap">



    <!-- HEADER -->

    <h1 class="event-title">Events</h1>



    <!-- CREATE EVENT CARD -->

    <div class="event-top">

        <div class="create-event">

            <h3>Add Exercises to Events</h3>

            <p>

                Schedule specific exercises for your upcoming

                training sessions and workshops.

            </p>

            <a href="event/create_event.php" class="btn primary">

                Schedule Event →

            </a>

        </div>

    </div>

    <br>



    <h1 class="event-title">My Events</h1>



    <!-- events list -->

    <div class="event-list">



        <?php if ($events->num_rows === 0): ?>

            <div class="empty-state">

                No events created yet.

            </div>

        <?php endif; ?>



        <?php while ($e = $events->fetch_assoc()): ?>

            <div class="event-card clickable"

                onclick="location.href='event/view_event.php?event_id=<?= $e['event_id'] ?>'">





                <!-- image -->



                <div class="event-image">

                    <img src="./upload-images/events/<?= htmlspecialchars(

                        $e['event_coverimage']

                    ) ?>" alt="Event Cover">

                </div>



                <!-- info -->

                <div class="event-info">

                    <h3><?= htmlspecialchars($e['event_name']) ?></h3>



                    <?php if ($e['event_description']): ?>

                        <p class="event-desc"><?= htmlspecialchars($e['event_description']) ?></p>

                    <?php endif; ?>



                    <div class="meta">

                        <span>

                            📅 <?= date("d M Y", strtotime($e['event_start_date'])) ?>

                        </span>



                        <span class="status status-<?= $e['event_playstatus'] ?>">

                            <?= match ($e['event_playstatus']) {

                                1 => 'Not Started',

                                2 => 'Open',

                                3 => 'In Progress',

                                4 => 'Completed',

                                default => 'Unknown'

                            } ?>

                        </span>

                    </div>

                </div>



                <!-- actions -->

                <!-- <div class="event-actions">

                    <a href="event/add_modules.php?event_id=<?= $e['event_id'] ?>"

                       class="btn small">

                        Add modules

                    </a>

                </div> -->



                



            </div>

        <?php endwhile; ?>



    </div>

</div>



<?php require "layout/footer.php"; ?>


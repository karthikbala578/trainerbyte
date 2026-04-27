<?php
require_once "include/session_check.php";
require "include/coreDataconnect.php";

if (!isset($_SESSION['team_id'])) {
    header("Location: login.php");
    exit;
}

$pageTitle = "Training Events";
$pageCSS   = "assets/styles/myevent.css";
require "layout/tb_header.php";

/* FETCH EVENTS */
$stmt = $conn->prepare("
    SELECT event_id, event_name, event_description,
           event_coverimage, event_start_date, event_playstatus
    FROM tb_events
    WHERE event_team_pkid = ?
      AND event_status = 1
    ORDER BY event_createddate DESC
");
$stmt->bind_param("i", $_SESSION['team_id']);
$stmt->execute();
$events = $stmt->get_result();

/* COUNTS */
$completed = $inprogress = $upcoming = 0;

$eventData = [];
while ($row = $events->fetch_assoc()) {
    $eventData[] = $row;

    if ($row['event_playstatus'] == 4) $completed++;
    elseif ($row['event_playstatus'] == 3) $inprogress++;
    else $upcoming++;
}
?>

<div class="event-page">

    <!-- HEADER -->
    <div class="event-header-bar" style="margin-left: 80px;">
        <div>
            <h1>Training Events</h1>
            <p>Schedule and monitor live training sessions.</p>

            <div class="stats">
                <span><span class="dot green"></span> Completed: <?= $completed ?></span>
                <span><span class="dot blue"></span> In Progress: <?= $inprogress ?></span>
                <span><span class="dot orange"></span> Upcoming: <?= $upcoming ?></span>
            </div>
        </div>

        <a href="create_event.php" class="btn-create">
            + Create New Event
        </a>
    </div>

    <!-- EVENTS GRID -->
    <div class="event-grid">

        <?php if (count($eventData) === 0): ?>
            <div class="empty">No events found</div>
        <?php endif; ?>

        <?php foreach ($eventData as $e):

            $statusText = match ($e['event_playstatus']) {
                1 => 'DRAFT',
                2 => 'PUBLISHED',
                3 => 'IN PROGRESS',
                4 => 'CLOSED',
                default => 'UNKNOWN'
            };

            $statusClass = match ($e['event_playstatus']) {
                1 => 'not-started',
                2 => 'open',
                3 => 'in-progress',
                4 => 'completed',
                default => 'unknown'
            };

            $desc = trim($e['event_description'] ?? '');
        ?>

            <div class="card">

                <div class="card-img">
                    <img src="upload-images/events/<?= htmlspecialchars($e['event_coverimage'] ?: 'default.jpg') ?>">

                    <span class="badge <?= $statusClass ?>">
                        <?= $statusText ?>
                    </span>
                </div>

                <div class="card-body">

                    <div class="card-top">
                        <h3><?= htmlspecialchars($e['event_name']) ?></h3>
                        <span><?= date("M d, Y", strtotime($e['event_start_date'])) ?></span>
                    </div>

                    <!-- DESCRIPTION (FIXED) -->
                    <p class="desc">
                        <?= $desc ? htmlspecialchars($desc) : 'No description available for this event.' ?>
                    </p>

                    <div class="card-actions">
                        <a href="view_event.php?event_id=<?= $e['event_id'] ?>" class="btn primary">
                            Manage
                        </a>
                    </div>

                </div>

            </div>

        <?php endforeach; ?>

    </div>

</div>

<?php //require "layout/footer.php"; ?>
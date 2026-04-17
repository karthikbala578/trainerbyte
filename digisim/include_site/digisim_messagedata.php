<?php
include("../../include/dataconnect.php");

$action = $_GET['action'];

if($action == "get_list"){

    $sim_id = $_GET['sim_id'];
    $channel_id = $_GET['channel_id'];

    // Get channel name
    $channel_sql = "SELECT ch_level FROM mg5_sub_channels WHERE ch_id = '$channel_id'";
    $channel_res = mysqli_query($conn,$channel_sql);
    $channel = mysqli_fetch_assoc($channel_res);

    $type = $channel['ch_level'];

    $sql = "SELECT *
            FROM mg5_digisim_message
            WHERE dm_digisim_pkid = '$sim_id'
            AND dm_injectes_pkid = '$channel_id'
            AND (dm_trigger = 1 OR (dm_trigger IN (2,3) AND dm_triggered = 1)
)
            ORDER BY dm_id DESC";

    $result = mysqli_query($conn,$sql);

    while($row = mysqli_fetch_assoc($result)){

        $class = ($row['dm_read_status'] == 0)
            ? 'message-item unread'
            : 'message-item';
?>

<div class="<?php echo $class; ?>"
     onclick="loadMessageDetail('<?php echo $row['dm_id']; ?>', this)">

    <div class="item-top">
        <span class="category-tag"><?php echo ucfirst($type); ?></span>
    </div>

    <div class="item-subject">
        <?php echo htmlspecialchars($row['dm_subject']); ?>
    </div>

    <div class="item-snippet">
        <?php echo substr(strip_tags($row['dm_message']),0,80); ?>
    </div>

</div>

<?php
    }
}

if($action == "get_detail"){

    $id = intval($_GET['id']);

    // mark message as read
    mysqli_query($conn,"UPDATE mg5_digisim_message 
                        SET dm_read_status = 1
                        WHERE dm_id = $id");

    // fetch message
    $sql = "SELECT dm_subject, dm_message
            FROM mg5_digisim_message
            WHERE dm_id = $id";

    $result = mysqli_query($conn,$sql);
    $row = mysqli_fetch_assoc($result);
?>

<div class="message-full">

<h3><?php echo htmlspecialchars($row['dm_subject']); ?></h3>

<div class="message-body">
<?php echo $row['dm_message']; ?>
</div>

</div>

<?php } 

if($action == "run_triggers"){

    header('Content-Type: application/json');

    $sim_id  = $_GET['sim_id'];
    $task_id = $_GET['task_id'];
    $count   = $_GET['count'];

    $messages = [];

    /* TASK TRIGGERS */

    mysqli_query($conn,"
        UPDATE mg5_digisim_message
        SET dm_triggered = 1
        WHERE dm_digisim_pkid='$sim_id'
        AND dm_event='$task_id'
        AND dm_trigger=2
        AND dm_triggered=0
    ");

    $task_sql = "SELECT dm.dm_subject, dm.dm_message, sc.ch_level
                 FROM mg5_digisim_message dm
                 JOIN sub_channels sc ON dm.dm_injectes_pkid=sc.ch_id
                 WHERE dm.dm_digisim_pkid='$sim_id'
                 AND dm.dm_event='$task_id'
                 AND dm.dm_trigger=2";

    $task_res = mysqli_query($conn,$task_sql);

    while($row=mysqli_fetch_assoc($task_res)){
        $messages[] = $row;
    }

    /* PROGRESSIVE TRIGGERS */

    mysqli_query($conn,"
        UPDATE digisim_message
        SET dm_triggered = 1
        WHERE dm_digisim_pkid='$sim_id'
        AND dm_event='$count'
        AND dm_trigger=3
        AND dm_triggered=0
    ");

    $prog_sql = "SELECT dm.dm_subject, dm.dm_message, sc.ch_level
                 FROM digisim_message dm
                 JOIN sub_channels sc ON dm.dm_injectes_pkid=sc.ch_id
                 WHERE dm.dm_digisim_pkid='$sim_id'
                 AND dm.dm_event='$count'
                 AND dm.dm_trigger=3";

    $prog_res = mysqli_query($conn,$prog_sql);

    while($row=mysqli_fetch_assoc($prog_res)){
        $messages[] = $row;
    }

    if(count($messages) > 0){
        echo json_encode([
            "status"=>"success",
            "messages"=>$messages
        ]);
    }else{
        echo json_encode([
            "status"=>"none"
        ]);
    }

}
?>

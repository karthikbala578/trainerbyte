<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
include("../include/coreDataconnect.php");

$ro_id = $_GET['game_id'];
$mod_game_status = 1;

/*  USER CHECK  */
$stmt = $conn->prepare("SELECT * FROM tb_event_user_score WHERE user_id = ? AND event_id = ? AND mod_game_id = ? AND mod_game_status = ?");
$stmt->bind_param("iiii", $_SESSION['user_id'], $_SESSION['event_id'], $ro_id, $mod_game_status);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();

/* SIM ID */
$sim_id = $ro_id;

/*  GET INJECT GROUP  */
$stmt = $conn->prepare("
    SELECT di_injects_id
    FROM mg5_digisim
    WHERE di_id = ?
");
$stmt->bind_param("i", $sim_id);
$stmt->execute();
$stmt->bind_result($injectGroupId);
$stmt->fetch();
$stmt->close();

if (!$injectGroupId) {
    die("No injects found");
}


// get chanel list

$stmt = $conn->prepare("
    SELECT 
        sc.ch_id, 
        sc.ch_level, 
        sc.ch_image,

        COUNT(DISTINCT CASE WHEN dm.dm_trigger = 1 THEN dm.dm_id END) AS start_count,
        COUNT(DISTINCT um.id) AS triggered_count,
        SUM(CASE WHEN um.is_read = 0 THEN 1 ELSE 0 END) AS unread_count

    FROM mg5_sub_channels sc

    LEFT JOIN mg5_digisim_message dm 
        ON dm.dm_injectes_pkid = sc.ch_id
        AND dm.dm_digisim_pkid = ?

    LEFT JOIN mg5_digisim_user_message um
        ON um.dm_id = dm.dm_id
        AND um.user_id = ?
        AND um.event_id = ?
        AND um.sim_id = ?

    WHERE sc.in_group_pkid = ?

    GROUP BY sc.ch_id

    HAVING 
        start_count > 0     -- show START
        OR triggered_count > 0 -- show TRIGGERED

    ORDER BY sc.ch_sequence ASC
");

$stmt->bind_param("iiiii",
    $sim_id,
    $_SESSION['user_id'],
    $_SESSION['event_id'],
    $sim_id,
    $injectGroupId
);
$stmt->execute();
$result = $stmt->get_result();

$msgArray = [];
while ($row = $result->fetch_assoc()) {
    $msgArray[] = $row;
}
$stmt->close();

/*  DEFAULT CHANNEL  */
$selectedChannel = isset($_GET['channel']) 
    ? intval($_GET['channel']) 
    : (isset($msgArray[0]['ch_id']) ? $msgArray[0]['ch_id'] : 0);

/*  GET SELECTED CHANNEL NAME   */
$selectedChannelName = "";

foreach($msgArray as $m){
    if($m['ch_id'] == $selectedChannel){
        $selectedChannelName = $m['ch_level'];
        break;
    }
}



$stmt = $conn->prepare("
    SELECT 
        dm.dm_id, 
        dm.dm_subject, 
        dm.dm_message,
        dm.dm_attachment,
        um.is_read

    FROM mg5_digisim_message dm

    LEFT JOIN mg5_digisim_user_message um
        ON um.dm_id = dm.dm_id
        AND um.user_id = ?
        AND um.event_id = ?
        AND um.sim_id = ?

    WHERE dm.dm_digisim_pkid = ?
    AND dm.dm_injectes_pkid = ?
    AND (
        dm.dm_trigger = 1
        OR um.id IS NOT NULL
    )

    ORDER BY dm.dm_id ASC
");
$stmt->bind_param("iiiii",
    $_SESSION['user_id'],
    $_SESSION['event_id'],
    $sim_id,
    $sim_id,
    $selectedChannel
);
$stmt->execute();
$messages = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

/*  INSERT START MESSAGES INTO USER TABLE  */
foreach($messages as $msg){

    $dm_id = $msg['dm_id'];

    $check = $conn->prepare("
        SELECT id FROM mg5_digisim_user_message
        WHERE user_id = ? AND event_id = ? AND sim_id = ? AND dm_id = ?
    ");

    $check->bind_param("iiii",
        $_SESSION['user_id'],
        $_SESSION['event_id'],
        $sim_id,
        $dm_id
    );

    $check->execute();
    $check->store_result();

    if($check->num_rows == 0){

        $ins = $conn->prepare("
            INSERT INTO mg5_digisim_user_message
            (user_id, event_id, sim_id, dm_id, is_read)
            VALUES (?, ?, ?, ?, 1)
        ");

        $ins->bind_param("iiii",
            $_SESSION['user_id'],
            $_SESSION['event_id'],
            $sim_id,
            $dm_id
        );

        $ins->execute();
    }

    $check->close();
}


// mark all as read for this channel
$stmt = $conn->prepare("
    UPDATE mg5_digisim_user_message um
    JOIN mg5_digisim_message dm 
        ON dm.dm_id = um.dm_id
    SET um.is_read = 1
    WHERE um.user_id = ?
    AND um.event_id = ?
    AND um.sim_id = ?
    AND dm.dm_injectes_pkid = ?
");

$stmt->bind_param("iiii",
    $_SESSION['user_id'],
    $_SESSION['event_id'],
    $sim_id,
    $selectedChannel
);
$stmt->execute();
$stmt->close();

/*  ICON FUNCTION  */
function getChannelIcon($channelName) {
    $map = [
        'email' => 'fa-envelope',
        'sms' => 'fa-comment',
        'intranet' => 'fa-network-wired',
        'social' => 'fa-hashtag',
        'news' => 'fa-newspaper',
        'phone' => 'fa-phone'
    ];

    $key = strtolower(trim($channelName));

    return $map[$key] ?? 'fa-bell';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet"/>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link rel="stylesheet" href="css_site/digisim_messageCenter.css">
</head>

<body>
<div class="container">
    <header class="main-header">
        <div class="header-left">
            <?php include("sidenav.php"); ?>
            <!-- <span class="material-symbols-outlined bell-icon">notifications</span> -->
            <img src="images/bell.png" width="10%" border="0">
            <h1 class="logo-text">Message Center</h1>
        </div>
        
        <div class="header-instructions">
            <!-- <span class="material-symbols-outlined">info</span> -->
            <p class="inject-desc">
                Welcome to Message Centre. Click on each message channel on the left to view current messages. Each channel may contain multiple messages, so be sure to explore them all. As you progress, new messages will appear in real-time—stay alert for updates that may influence your next steps.
            </p>
        </div>
    </header>
<div class="main-layout-wrapper">

<!--  SIDEBAR  -->
<aside class="sidebar-list">
    <!-- <div class="logo-container">
        <?php //include("sidenav.php"); ?>
        <span class="logo-text">Message Center</span>
    </div> -->

    <nav class="nav-section">
        <!-- <div class="list-header">
            <p class="inject-desc">
                Welcome to Message Centre. Click on each message channel on the left to view current messages. Each channel may contain multiple messages, so be sure to explore them all. As you progress, new messages will appear in real-time—stay alert for updates that may influence your next steps.
            </p>
        </div> -->
        <?php foreach ($msgArray as $index => $item): ?>
            <a class="nav-item <?php echo ($selectedChannel == $item['ch_id']) ? 'active' : ''; ?>" 
                href="?game_id=<?php echo $sim_id ?>&channel=<?php echo $item['ch_id']; ?>">

                <div class="nav-item-left">
                    <!-- <img src="images/channels/<?php echo $item['ch_image']; ?>" width="25"> -->
                    <i class="fa-solid <?php echo getChannelIcon($item['ch_level']); ?> channel-icon"></i>
                    <span><?php echo $item['ch_level']; ?></span>
                </div>

                <!--UNREAD BADGE -->
                <?php if(!empty($item['unread_count']) && $item['unread_count'] > 0): ?>
                    <span class="badge"><?php echo $item['unread_count']; ?></span>
                <?php endif; ?>
            </a>
        <?php endforeach; ?>
    </nav>
</aside>

<!--  MESSAGE LIST  -->
<main class="list-column">
    <div class="list-header">
        <?php if(!empty($selectedChannelName)): ?>
            <div class="channel-title">
                <?php echo htmlspecialchars($selectedChannelName); ?>
            </div>
        <?php endif; ?>

    </div>

    <div class="scroll-area">

        <?php if(empty($messages)): ?>
            <p style="padding:15px;">No messages</p>
        <?php else: ?>

            <?php foreach ($messages as $msg): 

                $subject = $msg['dm_subject'];
                $message = $msg['dm_message'];
                $attachment = $msg['dm_attachment'] ?? '';  

                // check unread
                $isUnread = !empty($msg['is_read']) ? false : true;
            ?>

            <div class="message-item <?php echo $isUnread ? 'unread' : ''; ?>"
                onclick='showMessage(
                    <?php echo json_encode($subject); ?>,
                    <?php echo json_encode($message); ?>,
                    <?php echo json_encode($attachment); ?>,
                    this
                )'>

                <!-- <div class="msg-subject"><?php echo $subject; ?></div> -->
                 <div class="msg-header">
                    <div class="msg-subject"><?php echo $subject; ?></div>

                    <?php if($isUnread): ?>
                        <span class="unread-badge">NEW</span>
                    <?php endif; ?>
                </div>

                <div class="msg-preview">
                    <?php echo substr(strip_tags($message), 0, 60); ?>...
                </div>
            </div>

            <?php endforeach; ?>

        <?php endif; ?>

    </div>
</main>

<!--  MESSAGE DETAIL  -->
<section class="content-column">
    <div class="message-body-container">
        <h3 id="v-sender">Select a message</h3>
        
        <div id="v-body">
            </div>

        <div class="attachment-section">
            <div class="attachment-header">
                <i class="fa-solid fa-paperclip"></i> 
                <span>Attachments</span>
            </div>
            
            <!-- attachments -->
             <div id="v-attachment" class="attachment-grid">
                <!-- Attachments will be dynamically inserted here by JS -->
            </div>
        </div>
    </div>
</section>

</div>
</div>

<!--  FOOTER  -->
<div class="footer-wrapper">
    

    <div class="footer-action">
        <div class="arrow-nav">
            <div class="arrow-btn left" onclick="goPrev()">PREVIOUS</div>
            <div class="arrow-btn right" onclick="goNext(<?php echo $ro_id; ?>)">NEXT</div>
        </div>
    </div>
    <div class="ai-chat-widget">
        <div id="aiBubble" class="chat-bubble">
            <span class="msgclo" onClick="closemsg();" >Hide</span>
            <h4 id="aiHeader">We're Online!</h4>
            <p id="aiMsg">How may I help you today?</p>
        </div>
        <div class="bot-button" id="botIcon">
            <div class="online-status"></div>
            <img src="images/bot.png" alt="AI Bot">
        </div>
    </div>
</div>

<script>

/* SHOW MESSAGE */
function showMessage(subject, body, attachment, el){
    document.getElementById("v-sender").innerText = subject;
    document.getElementById("v-body").innerHTML = body;
    // remove unread style
    if (el) {
        el.classList.remove("unread");

        const badge = el.querySelector(".unread-badge");
        if (badge) badge.remove();
    }
    // highlight selected
    document.querySelectorAll(".message-item").forEach(i => i.classList.remove("active"));
    if (el) el.classList.add("active");

    //  ATTACHMENT LOGIC 
    const attachmentContainer = document.getElementById("v-attachment");
    
    // Clear container if no attachment
    if (!attachment || attachment.trim() === "" || attachment === "NULL" || attachment === null) {
        attachmentContainer.innerHTML = "";
        return;
    }

    // 1. Extract file extension
    const extension = attachment.split('.').pop().toLowerCase();
    const filePath = "attachment/" + attachment;  // Adjust path if needed
    
    // 2. Determine icon, label, and color by file type
    let iconClass = "fa-file";
    let typeLabel = "Document";
    let themeColor = "#94a3b8"; // Default Grey

    if (['png', 'jpg', 'jpeg', 'gif', 'webp'].includes(extension)) {
        iconClass = "fa-file-image";
        typeLabel = "Image File";
        themeColor = "#10b981"; // Green
    } else if (extension === 'pdf') {
        iconClass = "fa-file-pdf";
        typeLabel = "PDF Document";
        themeColor = "#ef4444"; // Red
    } else if (['doc', 'docx'].includes(extension)) {
        iconClass = "fa-file-word";
        typeLabel = "Word Document";
        themeColor = "#3b82f6"; // Blue
    } 

    // 3. Generate attachment card HTML
    attachmentContainer.innerHTML = `
        <div class="attachment-wrapper">
            <span class="attachment-label">ATTACHED SYSTEM ASSET</span>
            <a href="${filePath}" target="_blank" class="file-card">
                <div class="file-icon" style="color: ${themeColor}">
                    <i class="fa-solid ${iconClass}"></i>
                </div>
                <div class="file-info">
                    <div class="file-name">${attachment}</div>
                    <div class="file-type">${typeLabel} • Click to open in new tab</div>
                </div>
                <div class="file-action">
                    <i class="fa-solid fa-arrow-up-right-from-square"></i>
                </div>
            </a>
        </div>
    `;
}

/* NAVIGATION */
function goPrev(){
    window.location.href = 'digisim_casestudy.php?game_id=<?php echo $sim_id ?>';
}

function goNext(){
    window.location.href = 'digisim_finalDecision.php?game_id=<?php echo $sim_id ?>';
}

function toggleSidebar() {
    const sidebar = document.getElementById('digisim-sidebar');
    const overlay = document.getElementById('digisim-sidebarOverlay');
    sidebar.classList.toggle('active');
    overlay.classList.toggle('active');
}

    function showAIMessage(title, message) {
        const bubble = document.getElementById('aiBubble');
        const header = document.getElementById('aiHeader');
        const msg = document.getElementById('aiMsg');

        if (!bubble || !header || !msg) return; // Safety check

        // Set the content
        header.innerText = title;
        msg.innerText = message;

        // Show the bubble
        bubble.classList.add('show');

        // Auto-hide after 4 seconds
        // setTimeout(() => {
        //     bubble.classList.remove('show');
        // }, 4000);
    }

    function closemsg(){
        const bubble = document.getElementById('aiBubble');	
        // $(bubble).fadeOut();
        bubble.classList.remove('show');
    }

    // Ensure the welcome message triggers after the page loads
    document.addEventListener('DOMContentLoaded', () => {
        // Small delay so the user sees the animation after the page appears
        if (!sessionStorage.getItem('welcomeShown')) {
        
            // Trigger the message immediately
            showAIMessage("", "Welcome to the Message Centre! Select a channel or message from the left panel to view it. New updates and notifications may appear as you play—stay alert!");
            
            // Mark as shown so it doesn't repeat on channel switches
            sessionStorage.setItem('welcomeShown', 'true');
        }

        // Auto-load the first message if one exists
        const firstMessage = document.querySelector(".message-item");
        if (firstMessage) {
            // We use a tiny delay here just to ensure the UI is ready to receive the click
            setTimeout(() => {
                firstMessage.click();
            }, 100);
        }
    });

    /* function getFileIcon(filename) {
    const ext = filename.split('.').pop().toLowerCase();
    
    switch(ext) {
        case 'pdf': 
            return { icon: 'fa-file-pdf', class: 'pdf' };
        case 'doc':
        case 'docx': 
            return { icon: 'fa-file-word', class: 'word' };
        case 'jpg':
        case 'jpeg':
        case 'png': 
            return { icon: 'fa-file-image', class: 'image' };
        default: 
            return { icon: 'fa-file', class: 'generic' };
    }
} */
</script>

</body>
</html>
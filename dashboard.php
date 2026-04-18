<?php
//require_once "include/session_check.php";
require "include/coreDataconnect.php";
$url = $_SERVER['REQUEST_URI'];
$code= basename($url);

//$code = trim($url, '/');
//if (!empty($code) && $code !== 'dashboard.php') { this line for live
    if (!empty($code) && $code !== 'dashboard.php') {

$defaultImage = 'upload-images/events/default_event.jpeg';
$eventImage   = $defaultImage;
$stmt = $conn->prepare(
    "SELECT * 
     FROM tb_events 
     WHERE event_url_code=?"
);
$stmt->bind_param("s", $code);
$stmt->execute();
$res = $stmt->get_result();

    if ($res->num_rows > 0) {
        $row = $res->fetch_assoc();
    
        if (!empty($row['event_coverimage']) &&
            file_exists('upload-images/events/'.$row['event_coverimage'])) {
    
            $eventImage = 'upload-images/events/'.$row['event_coverimage'];
        }

        /* ---- STATUS GATE: block only unpublished events ---- */
        $ps = (int)($row['event_playstatus'] ?? 1);
        if ($ps < 2) {
            $gateTitle = 'Event Not Available Yet';
            $gateIcon  = '⏳';
            $gateMsg   = 'This event hasn\'t been published yet. Please check back later or contact your trainer.';
            $gateBadge = 'NOT PUBLISHED';
            $gateBadgeClass = 'background:#fef9c3;color:#854d0e;';
            ?>
            <!DOCTYPE html>
            <html lang="en">
            <head>
              <meta charset="UTF-8">
              <meta name="viewport" content="width=device-width,initial-scale=1.0">
              <title><?= htmlspecialchars($gateTitle) ?></title>
              <style>
                @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');
                * { box-sizing:border-box; margin:0; padding:0; }
                body { display:flex; align-items:center; justify-content:center; min-height:100vh; background:#f3f6fb; font-family:'Inter',sans-serif; }
                .gate-box { text-align:center; background:#fff; padding:48px 40px; border-radius:20px; box-shadow:0 10px 32px rgba(0,0,0,.08); max-width:420px; width:90%; }
                .gate-poster { width:100%; max-height:160px; object-fit:cover; border-radius:12px; margin-bottom:24px; }
                .gate-icon  { font-size:52px; margin-bottom:16px; }
                .gate-box h2 { font-size:22px; font-weight:700; color:#111827; margin-bottom:10px; }
                .gate-box p  { font-size:15px; color:#6b7280; line-height:1.65; }
                .gate-badge  { display:inline-block; margin-top:20px; font-size:12px; font-weight:700; padding:5px 14px; border-radius:20px; letter-spacing:.5px; <?= $gateBadgeClass ?> }
                .event-name  { font-size:14px; font-weight:600; color:#374151; margin-bottom:6px; }
              </style>
            </head>
            <body>
              <div class="gate-box">
                <?php if (!empty($eventImage)): ?>
                <img src="<?= htmlspecialchars($eventImage) ?>" class="gate-poster" alt="Event">
                <?php endif; ?>
                <div class="event-name"><?= htmlspecialchars($row['event_name'] ?? '') ?></div>
                <div class="gate-icon"><?= $gateIcon ?></div>
                <h2><?= htmlspecialchars($gateTitle) ?></h2>
                <p><?= htmlspecialchars($gateMsg) ?></p>
                <span class="gate-badge"><?= $gateBadge ?></span>
              </div>
            </body>
            </html>
            <?php
            exit;
        }
        /* ---- END STATUS GATE ---- */
    }

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>ERM SANDBOX Login</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<link rel="stylesheet" type="text/css" href="assets/css/login_event_url.css">

</head>
<body>

<div class="wrapper">
<div class="container">

    <!-- LEFT -->
<div class="left" style="background: url('<?php echo $eventImage; ?>') center / cover no-repeat;">
  <div class="brand">
    
  </div>
  <!-- <div class="badge">PROFESSIONAL GAMING SERIES</div> -->
    <div class="event-card">
        <h1><span><?php echo $row['event_name']; ?></span></h1>
        <p>
            <?php echo $row['event_description']; ?>
        </p>
    </div>

</div>


    <!-- RIGHT -->
    <div class="right">
   
        <form class="login-box" id="pin-form"> 
            <h2>Welcome</h2>
            <div class="sub">Set a 6-digit PIN + name for the first time, just the PIN when you return.</div>

            <div class="code-header">
                <div class="left-title-container">
                    <span class="left-title">6-DIGIT ACCESS CODE</span>

                    <div class="info-tooltip-wrapper">
                        <span class="info-icon">i</span>
                        <div class="tooltip-content">
                            PIN must be 6 digits and cannot : <br>
                                • Contain all identical digits (e.g., 111111) <br>
                                • Be consecutive numbers (e.g., 123456) <br>
                                • Include any digit repeated three or more times (e.g., 111222)
                        </div>
                    </div>
                </div>
    
                <span class="right-title">VERIFY 6-DIGIT</span>
            </div>
            <div class="code-box">
                <label class="sr-only" for="p1">Digit 1</label>
                <input type="text" maxlength="1" class="pin" id="p1" name="p1">
                <label class="sr-only" for="p2">Digit 2</label>
                <input type="text" maxlength="1" class="pin" id="p2" name="p2">
                <label class="sr-only" for="p3">Digit 3</label>
                <input type="text" maxlength="1" class="pin" id="p3" name="p3">
                <label class="sr-only" for="p4">Digit 4</label>
                <input type="text" maxlength="1" class="pin" id="p4" name="p4">
                <label class="sr-only" for="p5">Digit 5</label>
                <input type="text" maxlength="1" class="pin" id="p5" name="p5">
                <label class="sr-only" for="p6">Digit 6</label>
                <input type="text" maxlength="1" class="pin" id="p6" name="p6">
            </div>
            <div class="code-header">
                <span class="left-title"></span>
                <span class="right-title"><p class="forgot-pin" onclick="showForgotMsg()">Forgot PIN?</p></span>
            </div>
            <p id="forgot-msg" class="hidden-msg">If you have forgotten your PIN, please contact your trainer or event moderator for assistance.</p>
            
            <div id="username-section" style="display: none;">
                <div class="code-header">
                    <span class="left-title">ENTER YOUR NAME / TEAM NAME</span>
                    <span class="right-title"></span>
                </div>
                <div>
                    <input type="text" name="uname" id="uname" class="input" placeholder="E.g. Logistics Squad A" required>
                </div>
                <div id="newuserstatus-msg" style="min-height: 20px; margin-top: 15px;"></div>
            </div>

            <button type="submit" class="btn">VERIFY & LOGIN</button>
            <div id="status-msg" style="min-height: 20px; margin-top: 15px; font-weight: bold;"></div>
        </form>
    </div>

</div>
</div>

<script>
const pins=document.querySelectorAll('.pin');

pins.forEach((input,i)=>{
    input.addEventListener('input',()=>{
        input.value=input.value.replace(/[^0-9]/g,'');
        if(input.value && pins[i+1]) pins[i+1].focus();
    });

    input.addEventListener('keydown',(e)=>{
        if(e.key==='Backspace' && !input.value && pins[i-1]) pins[i-1].focus();
    });
});

function showForgotMsg() {
    document.getElementById('forgot-msg').style.display = 'block';
}
function isWeakPin(pin) {
    // 1. Check for all identical digits (e.g., 111111)
    const isAllIdentical = /^(\d)\1{5}$/.test(pin);

    // 2. Check for consecutive digits (e.g., 123456 or 654321)
    const sequence = "01234567890 09876543210";
    const isConsecutive = sequence.includes(pin);

    // 3. Check if any digit is repeated 3 or more times (Total Count)
    // Example: 111222 (fails), 121314 (fails because '1' appears thrice)
    let hasTripleDigit = false;
    for (let i = 0; i <= 9; i++) {
        const count = pin.split(i.toString()).length - 1;
        if (count >= 3) {
            hasTripleDigit = true;
            break;
        }
    }

    return isAllIdentical || isConsecutive || hasTripleDigit;
}
const boxes = document.querySelectorAll('.pin');
const statusMsg = document.getElementById('status-msg');
const newuserStatus = document.getElementById('newuserstatus-msg');
const userSection = document.getElementById('username-section');
const unameInput = document.getElementById('uname').value;

boxes.forEach((box, index) => {
    box.addEventListener('input', () => {
        // Auto-focus next box
        if (box.value && index < boxes.length - 1) {
            boxes[index + 1].focus();
        }

        // Combine all 6 digits
        let fullPin = "";
        boxes.forEach(b => fullPin += b.value);

        if (fullPin.length === 6) {
            if (isWeakPin(fullPin)) {
                statusMsg.innerText = "The entered PIN does not meet the required guideline; please refer to the information icon for details.";
                statusMsg.style.color = "#045204";

                // 1. Clear the boxes so they can try again
                boxes.forEach(box => box.value = "");
                boxes[0].focus();

                // 2. Clear the status message after 3 seconds
                setTimeout(() => {
                    statusMsg.innerText = "";
                }, 3000); 

            } else {
                // PIN is strong enough, now check the Database
                checkPinAvailability(fullPin);
            }
        }

    });

});

function checkPinAvailability(pin) {
    const sessionCode = <?php echo json_encode($code); ?>;

    fetch('login_validation_url.php', {
        method: 'POST',
        headers: {'Content-Type':'application/x-www-form-urlencoded'},
        body: new URLSearchParams({
            pin: pin,
            code: sessionCode,
            mode: 'check'
        })
    })
    .then(res => res.json())
    .then(data => {

        if(data.status === 'error'){
            statusMsg.innerText = data.message;
            statusMsg.style.color = "red";
            boxes.forEach(box => box.value = "");
            boxes[0].focus();
            
            setTimeout(() => {
                statusMsg.innerText = "";
            }, 4000);

        }else if (data.exists === false) {
            newuserStatus.innerText = data.message;
            newuserStatus.style.color = "blue";
            // userSection.style.display = "block";
            showUsernameField();
            document.getElementById('uname').focus();
            setTimeout(() => {
                newuserStatus.innerText = "";
                newuserStatus.style.display = "none";
            }, 4000);
            
        } else if (data.exists === true) {

            // 1. Display the "Welcome back" message
            const statusMsg = document.getElementById('status-msg');
            statusMsg.innerText = data.message;
            statusMsg.style.color = "#045204"; // Green color

            // 2. Change button text from "VERIFY & LOGIN" to "ENTER"
            const loginBtn = document.querySelector('.btn');
            loginBtn.innerText = "ENTER";

            // 3. Optional: Clear message after a few seconds (as we did before)
            setTimeout(() => {
                statusMsg.innerText = "";
            }, 4000);

            // We can change the button's behavior or redirect immediately
            loginBtn.onclick = function(e) {
                e.preventDefault();
                window.location.href = data.redirect;
            };

            // window.location.href = data.redirect;

        }

    })

    .catch(() => {

        statusMsg.innerText = "Connection error";

        statusMsg.style.color = "red";

    });

}

function showUsernameField() {
    const userSection = document.getElementById('username-section');
    userSection.style.display = 'block'; // Only the button below will move
}
</script>
<script>
const form = document.getElementById('pin-form');

form.addEventListener('submit', function(e) {
    e.preventDefault();

    let pin = '';
    boxes.forEach(b => pin += b.value);

    const uname = document.getElementById('uname').value.trim();
    const sessionCode = <?php echo json_encode($code); ?>;

    fetch('login_validation_url.php', {
        method: 'POST',
        headers: {'Content-Type':'application/x-www-form-urlencoded'},
        body: new URLSearchParams({
            pin: pin,
            code: sessionCode,
            uname: uname
        })
    })
    .then(res => res.json())
    .then(data => {
        if (data.status === 'success') {
            window.location.href = data.redirect;
        } else {
            statusMsg.innerText = data.message;
            statusMsg.style.color = "red";
        }
    });
});

</script>


</body>
</html>

<?php }else{
$pageTitle = "TrainerGenie Dashboard";
$pageCSS   = "assets/styles/index.css";

require "layout/tb_header.php";
$stmt = $conn->prepare("
    SELECT ex_id, ex_name, ex_des, ex_tag, ex_image, ex_type
    FROM tb_exercise_type
    ORDER BY ex_id ASC
    ");
$stmt->execute();
$result = $stmt->get_result();

?>

<div class="index-container">
    <div class="bg-img-container">
        <img class="bg-pic" src="./upload-images/index-bg-pic.png" alt="index-bg-pic">


        <div class="bg-text">
            <h1>Ready to empower your team?
            </h1>
            <p> Select a proven template below or start from scratch to build interactive training sessions in minutes.</p>
        </div>

    </div>

    <section class="templates-section">

        <div class="section-header">
            <h2>Popular Exercise Templates</h2>
            <a href="all-templates.php" class="view-all">
                View all templates →
            </a>
        </div>

        <div class="templates-grid">
            <?php if ($result->num_rows === 0): ?>
                <p>No templates available.</p>
            <?php else: ?>
                <?php while ($template = $result->fetch_assoc()): ?>
                    <div class="template-card">

                    <div class="card-img">
                        <span class="tag">
                            <?php echo  htmlspecialchars($template['ex_tag'] ?? 'General') ?>
                        </span>

                        <?php if (!empty($template['ex_image'])): ?>
                            <img
                                src="./upload-images/exercise-pics/<?php echo  htmlspecialchars($template['ex_image']) ?>"
                                alt="<?php echo  htmlspecialchars($template['ex_name']) ?>">
                        <?php else: ?>
                            <div class="img-placeholder">
                                No Image
                            </div>
                        <?php endif; ?>
                    </div>

                        <div class="card-body">
                            <h3><?php echo  htmlspecialchars($template['ex_name']) ?></h3>
                            <p><?php echo  htmlspecialchars($template['ex_des']) ?></p>

                            <?php
                            $exerciseRoutes = [
                                1 => "digihunt_exercise.php",
                                2 => "library.php",
                                3 => "pixelquest_exercise.php",
                                4 => "bitbargain_exercise.php"
                            ];

                            $redirectPage = $exerciseRoutes[$template['ex_type']] ?? "index.php";
                            ?>

                            <button onclick="window.location.href='<?php echo  $redirectPage ?>'">
                                Create Session
                            </button>
                        </div>


                    </div>
                <?php endwhile; ?>
            <?php endif; ?>
        </div>

    </section>




</div>
<?php } ?>
<?php //require "layout/footer.php"; ?>

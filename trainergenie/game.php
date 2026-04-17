<?php
session_start();
require "include/dataconnect.php";
include "include_site/roundfunction_be.php";

/* -----------------------
   GET MODULE ID
------------------------ */
$modId = $_GET['mod'] ?? 0;
if (!$modId) {
    die("Invalid module");
}

/* -----------------------
   LOAD MODULE CONTENT
------------------------ */
$gameround = new GameRound();
$content = $gameround->getModuleContent($conn, $modId);

if (!$content) {
    die("Module content not found");
}
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title><?php echo htmlspecialchars($content['cg_name']); ?></title>
<link rel="stylesheet" href="assets/css/gamepage.css">
</head>
<body>

<h1><?php echo htmlspecialchars($content['cg_name']); ?></h1>

<p><?php echo nl2br(htmlspecialchars($content['cg_description'])); ?></p>

<h3>How to Play</h3>
<?php echo nl2br($content['cg_guidelines']); ?>

<h3>Clue</h3>
<?php echo nl2br($content['cg_clue']); ?>

</body>
</html>

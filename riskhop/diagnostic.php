<?php
echo "<h1>Diagnostic Report</h1>";
echo "<b>Server Time:</b> " . date('Y-m-d H:i:s') . "<br>";
echo "<b>Absolute Path:</b> " . __FILE__ . "<br>";
echo "<b>Document Root:</b> " . $_SERVER['DOCUMENT_ROOT'] . "<br>";
echo "<b>Script Name:</b> " . $_SERVER['SCRIPT_NAME'] . "<br>";
echo "<b>Current Work Dir:</b> " . getcwd() . "<br>";
?>

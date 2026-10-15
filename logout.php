<?php
require_once 'config.php';
destroySession();
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Location: login.php");
exit();
?>
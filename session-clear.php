<?php
// session-clear.php - Clear session for testing
session_start();
session_destroy();
echo "Session destroyed. <a href='session-test.php'>Go back to test</a>";
?>
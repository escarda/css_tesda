<?php
// session-test.php - Debug session persistence
session_start();

// Initialize or increment hit counter
if (!isset($_SESSION['hits'])) {
    $_SESSION['hits'] = 0;
}
$_SESSION['hits']++;

// Display session information
echo "Session ID: " . session_id() . "<br>";
echo "Hits: " . $_SESSION['hits'] . "<br>";
echo "Session Data: <pre>";
print_r($_SESSION);
echo "</pre>";

// Check if session is working properly
echo "<hr>";
echo "Session Status: " . session_status() . " (2 = active)<br>";
echo "Session Save Path: " . session_save_path() . "<br>";

// Link to continue testing
echo '<br><a href="session-test.php">Refresh to increment</a> | ';
echo '<a href="quiz.php?category=1">Go to Quiz</a> | ';
echo '<a href="session-clear.php">Clear Session</a>';
?>
<?php
// test_save.php - Test the save functionality
require_once 'config.php';

if (!isLoggedIn()) {
    die("Please login first. <a href='login.php'>Login</a>");
}

echo "<h2>Testing Save Game Score</h2>";

$user_id = $_SESSION['user_id'];
$test_score = 500;
$coc = 1;

echo "User ID: $user_id<br>";
echo "Testing COC $coc with score: $test_score<br><br>";

// Test using POST simulation
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['SCRIPT_NAME']) . '/save_game_score.php');
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, 'coc=' . $coc . '&score=' . $test_score . '&games_played=1');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
curl_close($ch);

echo "API Response: " . $response . "<br><br>";

// Verify in database
$verify = $conn->query("SELECT * FROM user_game_scores WHERE user_id = $user_id AND coc_number = $coc");
if ($verify && $verify->num_rows > 0) {
    $row = $verify->fetch_assoc();
    echo "<h3>Database Record:</h3>";
    echo "High Score: " . $row['high_score'] . "<br>";
    echo "Games Played: " . $row['games_played'] . "<br>";
    echo "Created: " . $row['created_at'] . "<br>";
} else {
    echo "No record found in database.";
}

echo "<br><br><a href='coc1_game.php'>Go back to game</a>";
?>
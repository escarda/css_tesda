<?php
// test_save_direct.php - Test save function directly
require_once 'config.php';

session_start();

if (!isset($_SESSION['user_id'])) {
    echo "Please login first. <a href='login.php'>Login</a>";
    exit();
}

$user_id = $_SESSION['user_id'];
$test_score = 600;
$coc = 1;

echo "<h2>Testing Save Function for User ID: $user_id</h2>";

// Test insert into user_game_scores
$check = $conn->prepare("SELECT id, high_score, games_played FROM user_game_scores WHERE user_id = ? AND coc_number = ?");
$check->bind_param("ii", $user_id, $coc);
$check->execute();
$result = $check->get_result();

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $new_high = max($row['high_score'], $test_score);
    $new_games = $row['games_played'] + 1;
    
    $update = $conn->prepare("UPDATE user_game_scores SET high_score = ?, games_played = ? WHERE user_id = ? AND coc_number = ?");
    $update->bind_param("iiii", $new_high, $new_games, $user_id, $coc);
    
    if ($update->execute()) {
        echo "✅ Score updated! New high score: $new_high, Games played: $new_games<br>";
    } else {
        echo "❌ Update failed: " . $update->error . "<br>";
    }
    $update->close();
} else {
    $insert = $conn->prepare("INSERT INTO user_game_scores (user_id, coc_number, high_score, games_played) VALUES (?, ?, ?, ?)");
    $insert->bind_param("iiii", $user_id, $coc, $test_score, 1);
    
    if ($insert->execute()) {
        echo "✅ New record inserted! Score: $test_score<br>";
    } else {
        echo "❌ Insert failed: " . $insert->error . "<br>";
    }
    $insert->close();
}
$check->close();

// Show updated scores
echo "<h3>Updated Scores for User ID: $user_id</h3>";
$scores = $conn->query("SELECT * FROM user_game_scores WHERE user_id = $user_id ORDER BY coc_number");
if ($scores && $scores->num_rows > 0) {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>COC</th><th>High Score</th><th>Games Played</th><th>Last Updated</th></tr>";
    while ($row = $scores->fetch_assoc()) {
        echo "<tr>";
        echo "<td>COC {$row['coc_number']}</td>";
        echo "<td>{$row['high_score']}</td>";
        echo "<td>{$row['games_played']}</td>";
        echo "<td>{$row['updated_at']}</td>";
        echo "</tr>";
    }
    echo "</table>";
}
?>
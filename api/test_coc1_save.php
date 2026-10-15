<?php
// test_coc1_save.php - Test COC 1 save functionality
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    echo "Please login first. <a href='login.php'>Login</a>";
    exit();
}

$user_id = $_SESSION['user_id'];
$test_score = 500;

echo "<h2>Testing COC 1 Save for User ID: $user_id</h2>";

// Test insert
$stmt = $conn->prepare("INSERT INTO game_scores (user_id, coc_number, score, played_at) VALUES (?, 1, ?, NOW())");
$stmt->bind_param("ii", $user_id, $test_score);

if ($stmt->execute()) {
    echo "✅ Test score saved successfully!<br>";
    
    // Get all scores for this user
    $result = $conn->query("SELECT * FROM game_scores WHERE user_id = $user_id AND coc_number = 1 ORDER BY id DESC");
    if ($result && $result->num_rows > 0) {
        echo "<h3>Your COC 1 Scores:</h3>";
        echo "<table border='1' cellpadding='8'>";
        echo "<tr><th>ID</th><th>Score</th><th>Date</th></tr>";
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>{$row['id']}</td>";
            echo "<td>{$row['score']}</td>";
            echo "<td>{$row['played_at']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
} else {
    echo "❌ Error: " . $stmt->error . "<br>";
}

$stmt->close();

// Get high score
$best = $conn->query("SELECT MAX(score) as best FROM game_scores WHERE user_id = $user_id AND coc_number = 1");
$best_score = $best->fetch_assoc();
echo "<br>Your High Score: " . ($best_score['best'] ?? 0);

$total = $conn->query("SELECT COUNT(*) as total FROM game_scores WHERE user_id = $user_id AND coc_number = 1");
$total_games = $total->fetch_assoc();
echo "<br>Total Games Played: " . ($total_games['total'] ?? 0);

echo "<br><br><a href='coc1_game.php'>Go to COC 1 Game</a>";
?>
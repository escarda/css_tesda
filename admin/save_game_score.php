<?php
// save_game_score.php - With debugging
require_once 'config.php';

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');

// Log the request
$input = file_get_contents('php://input');
error_log("Save request received: " . $input);

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not logged in', 'session' => $_SESSION]);
    exit();
}

$user_id = $_SESSION['user_id'];
$coc = isset($_POST['coc']) ? intval($_POST['coc']) : 1;
$score = isset($_POST['score']) ? intval($_POST['score']) : 0;
$games_played = isset($_POST['games_played']) ? intval($_POST['games_played']) : 1;

// Log the values
error_log("User: $user_id, COC: $coc, Score: $score");

// Rest of your save logic...

// For COC 2 and 3 (game_scores table)
if ($coc == 2 || $coc == 3) {
    $insert = $conn->prepare("INSERT INTO game_scores (user_id, coc_number, score, played_at) VALUES (?, ?, ?, NOW())");
    $insert->bind_param("iii", $user_id, $coc, $score);
    
    if ($insert->execute()) {
        // Get high score for this user and COC
        $high_query = $conn->prepare("SELECT MAX(score) as best FROM game_scores WHERE user_id = ? AND coc_number = ?");
        $high_query->bind_param("ii", $user_id, $coc);
        $high_query->execute();
        $high_result = $high_query->get_result();
        $best = $high_result->fetch_assoc();
        
        echo json_encode([
            'success' => true, 
            'high_score' => $best['best'] ?? $score,
            'games_played' => 1
        ]);
        $high_query->close();
    } else {
        echo json_encode(['success' => false, 'error' => $insert->error]);
    }
    $insert->close();
} else {
    // For COC 1 and 4 (user_game_scores table)
    $check = $conn->prepare("SELECT id, high_score, games_played FROM user_game_scores WHERE user_id = ? AND coc_number = ?");
    $check->bind_param("ii", $user_id, $coc);
    $check->execute();
    $result = $check->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $new_high = max($row['high_score'], $score);
        $new_games = $row['games_played'] + $games_played;
        
        $update = $conn->prepare("UPDATE user_game_scores SET high_score = ?, games_played = ? WHERE user_id = ? AND coc_number = ?");
        $update->bind_param("iiii", $new_high, $new_games, $user_id, $coc);
        
        if ($update->execute()) {
            echo json_encode(['success' => true, 'high_score' => $new_high, 'games_played' => $new_games]);
        } else {
            echo json_encode(['success' => false, 'error' => $update->error]);
        }
        $update->close();
    } else {
        $insert = $conn->prepare("INSERT INTO user_game_scores (user_id, coc_number, high_score, games_played) VALUES (?, ?, ?, ?)");
        $insert->bind_param("iiii", $user_id, $coc, $score, $games_played);
        
        if ($insert->execute()) {
            echo json_encode(['success' => true, 'high_score' => $score, 'games_played' => $games_played]);
        } else {
            echo json_encode(['success' => false, 'error' => $insert->error]);
        }
        $insert->close();
    }
    $check->close();
}

$conn->close();
?>
<?php
// save_coc4_score.php - Save COC 4 Game Score
require_once 'config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit();
}

$user_id = $_SESSION['user_id'];
$score = isset($_POST['score']) ? intval($_POST['score']) : 0;
$games_played = isset($_POST['games_played']) ? intval($_POST['games_played']) : 1;
$current_time = date('Y-m-d H:i:s');

// Check if record exists
$check = $conn->prepare("SELECT id, high_score, games_played FROM user_game_scores WHERE user_id = ? AND coc_number = 4");
$check->bind_param("i", $user_id);
$check->execute();
$result = $check->get_result();

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $new_high = max($row['high_score'], $score);
    $new_games = $row['games_played'] + $games_played;
    
    $update = $conn->prepare("UPDATE user_game_scores 
                              SET high_score = ?, 
                                  games_played = ?, 
                                  last_played = ?,
                                  updated_at = ?,
                                  played_at = ?
                              WHERE user_id = ? AND coc_number = 4");
    $update->bind_param("iisssi", $new_high, $new_games, $current_time, $current_time, $current_time, $user_id);
    
    if ($update->execute()) {
        echo json_encode(['success' => true, 'high_score' => $new_high, 'games_played' => $new_games]);
    } else {
        echo json_encode(['success' => false, 'error' => $update->error]);
    }
    $update->close();
} else {
    $insert = $conn->prepare("INSERT INTO user_game_scores (user_id, coc_number, high_score, games_played, last_played, created_at, updated_at, played_at) 
                              VALUES (?, 4, ?, ?, NOW(), NOW(), NOW(), NOW())");
    $insert->bind_param("iii", $user_id, $score, $games_played);
    
    if ($insert->execute()) {
        echo json_encode(['success' => true, 'high_score' => $score, 'games_played' => $games_played]);
    } else {
        echo json_encode(['success' => false, 'error' => $insert->error]);
    }
    $insert->close();
}
$check->close();
$conn->close();
?>
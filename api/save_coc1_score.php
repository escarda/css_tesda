<?php
// save_coc1_score.php - Save COC 1 Game Score (for auto-save)
require_once 'config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit();
}

$user_id = $_SESSION['user_id'];
$score = isset($_POST['score']) ? intval($_POST['score']) : 0;

// Insert into game_scores table
$stmt = $conn->prepare("INSERT INTO game_scores (user_id, coc_number, score, played_at) VALUES (?, 1, ?, NOW())");
$stmt->bind_param("ii", $user_id, $score);

if ($stmt->execute()) {
    // Get updated stats
    $best = $conn->query("SELECT MAX(score) as best FROM game_scores WHERE user_id = $user_id AND coc_number = 1");
    $best_score = $best->fetch_assoc();
    
    $total = $conn->query("SELECT COUNT(*) as total FROM game_scores WHERE user_id = $user_id AND coc_number = 1");
    $total_games = $total->fetch_assoc();
    
    echo json_encode([
        'success' => true, 
        'high_score' => $best_score['best'] ?? $score,
        'games_played' => $total_games['total'] ?? 1
    ]);
} else {
    echo json_encode(['success' => false, 'error' => $stmt->error]);
}

$stmt->close();
$conn->close();
?>
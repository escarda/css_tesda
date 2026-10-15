<?php
// save_troubleshooting_score.php
require_once 'config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit();
}

$user_id = $_SESSION['user_id'];
$score = isset($_POST['score']) ? intval($_POST['score']) : 0;
$problems_fixed = isset($_POST['problems_fixed']) ? intval($_POST['problems_fixed']) : 0;
$attempts = isset($_POST['attempts']) ? intval($_POST['attempts']) : 0;

// Insert score
$stmt = $conn->prepare("INSERT INTO troubleshooting_scores (user_id, score, problems_fixed, attempts) VALUES (?, ?, ?, ?)");
$stmt->bind_param("iiii", $user_id, $score, $problems_fixed, $attempts);

if ($stmt->execute()) {
    // Get best score
    $best_stmt = $conn->prepare("SELECT MAX(score) as best FROM troubleshooting_scores WHERE user_id = ?");
    $best_stmt->bind_param("i", $user_id);
    $best_stmt->execute();
    $best_result = $best_stmt->get_result();
    $best = $best_result->fetch_assoc();
    
    echo json_encode(['success' => true, 'best_score' => $best['best'] ?? $score]);
} else {
    echo json_encode(['success' => false, 'error' => $conn->error]);
}

$stmt->close();
?>
<?php
// save_windows_server_score.php - Saves Windows Server 2012 R2 Installation Simulator results
require_once '../config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

$user_id = $_SESSION['user_id'];
$score = isset($_POST['score']) ? (int)$_POST['score'] : 0;
$steps_completed = isset($_POST['steps_completed']) ? (int)$_POST['steps_completed'] : 0;

// Keep values inside the expected range (15 steps x 10 points)
if ($score < 0 || $score > 150 || $steps_completed < 0 || $steps_completed > 15) {
    echo json_encode(['success' => false, 'message' => 'Invalid score']);
    exit();
}

$stmt = $conn->prepare("INSERT INTO windows_server_install_scores (user_id, score, steps_completed, completed_at) VALUES (?, ?, ?, NOW())");
$stmt->bind_param("iii", $user_id, $score, $steps_completed);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'score' => $score, 'steps_completed' => $steps_completed]);
} else {
    echo json_encode(['success' => false, 'message' => 'Database error']);
}

$stmt->close();
$conn->close();
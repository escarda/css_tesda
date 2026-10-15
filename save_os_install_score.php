<?php
// save_os_install_score.php
require_once 'config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit();
}

$user_id = $_SESSION['user_id'];
$score = isset($_POST['score']) ? intval($_POST['score']) : 0;
$steps_completed = isset($_POST['steps_completed']) ? intval($_POST['steps_completed']) : 0;

$stmt = $conn->prepare("INSERT INTO os_installation_scores (user_id, score, steps_completed) VALUES (?, ?, ?)");
$stmt->bind_param("iii", $user_id, $score, $steps_completed);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => $conn->error]);
}

$stmt->close();
?>
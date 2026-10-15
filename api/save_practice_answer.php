<?php

require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    exit();
}

$user_id = $_SESSION['user_id'];
$question_id = intval($_POST['question_id'] ?? 0);
$answer = $_POST['answer'] ?? '';
$is_correct = intval($_POST['is_correct'] ?? 0);
$coc = intval($_POST['coc'] ?? 1);

// Save to session
$_SESSION["answer_q{$question_id}"] = $answer;
$_SESSION["is_correct_q{$question_id}"] = $is_correct;
$_SESSION["show_feedback_q{$question_id}"] = true;

// Save to database
$check_query = "SELECT id FROM user_practice WHERE user_id = ? AND question_id = ?";
$stmt = $conn->prepare($check_query);
$stmt->bind_param("ii", $user_id, $question_id);
$stmt->execute();
$exists = $stmt->get_result()->num_rows > 0;
$stmt->close();

if ($exists) {
    $update_query = "UPDATE user_practice SET user_answer = ?, is_correct = ?, updated_at = NOW() WHERE user_id = ? AND question_id = ?";
    $stmt = $conn->prepare($update_query);
    $stmt->bind_param("siii", $answer, $is_correct, $user_id, $question_id);
} else {
    $insert_query = "INSERT INTO user_practice (user_id, question_id, coc_number, user_answer, is_correct) VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($insert_query);
    $stmt->bind_param("iiisi", $user_id, $question_id, $coc, $answer, $is_correct);
}
$stmt->execute();
$stmt->close();

echo json_encode(['success' => true]);
?>
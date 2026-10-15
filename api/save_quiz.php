<?php
require_once 'config.php';

if (!isLoggedIn()) {
    http_response_code(401);
    die(json_encode(['error' => 'Unauthorized']));
}

$data = json_decode(file_get_contents('php://input'), true);

$user_id = $_SESSION['user_id'];
$category_id = $data['category_id'];
$score = $data['score'];
$total = $data['total'];
$answers = $data['answers'];
$questions = $data['questions'];
$time_taken = $data['time_taken'];

// Save quiz
$sql = "INSERT INTO quizzes (user_id, category_id, score, total_questions, time_taken) 
        VALUES ($user_id, $category_id, $score, $total, $time_taken)";
$conn->query($sql);
$quiz_id = $conn->insert_id;

// Save answers
foreach ($answers as $index => $answer) {
    if ($answer !== null) {
        $question_id = $questions[$index]['id'];
        $is_correct = ($answer == $questions[$index]['correct_answer']) ? 1 : 0;
        
        $sql = "INSERT INTO quiz_answers (quiz_id, question_id, user_answer, is_correct) 
                VALUES ($quiz_id, $question_id, '$answer', $is_correct)";
        $conn->query($sql);
    }
}

echo json_encode(['success' => true, 'quiz_id' => $quiz_id]);
?>
<?php
// api/questions.php
// CSS TESDA Reviewer - Questions API
// 30 random questions per quiz

require_once '../config.php';

header('Content-Type: application/json; charset=utf-8');

// Require login
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);

    echo json_encode([
        'success' => false,
        'message' => 'Authentication required.',
        'data' => []
    ]);
    exit;
}

// GET only
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);

    echo json_encode([
        'success' => false,
        'message' => 'Only GET requests are allowed.',
        'data' => []
    ]);
    exit;
}

// Get category ID
$category_id = isset($_GET['category_id'])
    ? (int)$_GET['category_id']
    : 0;

if ($category_id <= 0) {
    http_response_code(400);

    echo json_encode([
        'success' => false,
        'message' => 'Category ID is required.',
        'data' => []
    ]);
    exit;
}

try {

    /*
     * Use the existing system function.
     * It already:
     * - filters by category
     * - selects active questions
     * - randomizes the questions
     */
    $questions = getQuestionsByCategory($category_id);

    /*
     * Limit every quiz to 30 questions.
     */
    $questions = array_slice($questions, 0, 30);

    $data = [];

    foreach ($questions as $question) {

        $data[] = [
            'id' => (int)$question['id'],
            'category_id' => $category_id,

            'question_text' => $question['question_text'],

            'option_a' => $question['option_a'],
            'option_b' => $question['option_b'],
            'option_c' => $question['option_c'],
            'option_d' => $question['option_d'],

            /*
             * Do NOT send correct_answer.
             */
            'explanation' => $question['explanation'] ?? ''
        ];
    }

    echo json_encode([
        'success' => true,
        'message' => 'Quiz questions loaded successfully.',
        'data' => $data,
        'count' => count($data),
        'total_questions' => 30,
        'time_limit_seconds' => 300,
        'time_limit_minutes' => 5
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {

    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' => 'Failed to load questions.',
        'data' => [],
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
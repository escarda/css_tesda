<?php
// api/questions.php
// CSS TESDA Reviewer - Questions API

require_once '../config.php';

header('Content-Type: application/json; charset=utf-8');

// Require logged-in user
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);

    echo json_encode([
        'success' => false,
        'message' => 'Authentication required.',
        'data' => []
    ]);
    exit;
}

// Only GET is allowed
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
        'message' => 'A valid category_id is required.',
        'data' => []
    ]);
    exit;
}

try {

    /*
     * Use the existing getQuestionsByCategory()
     * function from config.php.
     */
    $questions = getQuestionsByCategory($category_id);

    $safe_questions = [];

    foreach ($questions as $question) {

        /*
         * Do NOT send correct_answer to Android.
         * The app should not receive the answers before
         * the user submits the quiz.
         */
        $safe_questions[] = [
            'id' => (int)$question['id'],
            'question_text' => $question['question_text'],
            'option_a' => $question['option_a'],
            'option_b' => $question['option_b'],
            'option_c' => $question['option_c'],
            'option_d' => $question['option_d'],
            'explanation' => $question['explanation'] ?? null
        ];
    }

    echo json_encode([
        'success' => true,
        'message' => 'Questions loaded successfully.',
        'data' => $safe_questions
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
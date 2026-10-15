<?php
require_once 'config.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirect('login.php');
}

// ============================================
// QUIZ TIMER CONFIG
// ============================================
define('QUIZ_TIME_LIMIT', 300); // 5 minutes, in seconds
define('QUIZ_QUESTIONS_PER_ATTEMPT', 30); // each COC quiz draws this many questions per attempt

// Saves the final quiz result + per-question answers to the database.
// Shared by: normal completion (last question answered), explicit
// timeout submission, and the passive time-check on page load.
function save_quiz_results($conn, &$quiz, $user_id, $category_id) {
    $score = $quiz['score'];
    $total = $quiz['total_questions'];

    $insert = $conn->prepare("
        INSERT INTO quizzes (user_id, category_id, score, total_questions, completed_at)
        VALUES (?, ?, ?, ?, NOW())
    ");

    $insert->bind_param(
        "iiii",
        $user_id,
        $category_id,
        $score,
        $total
    );

    $insert->execute();

    $quiz_id = $conn->insert_id;
    $quiz['quiz_id'] = $quiz_id;

    $insert->close();

    $insert_answer = $conn->prepare("
        INSERT INTO quiz_answers (quiz_id, question_id, user_answer, is_correct)
        VALUES (?, ?, ?, ?)
    ");

    foreach ($quiz['answers'] as $answer) {
        $insert_answer->bind_param(
            "iisi",
            $quiz_id,
            $answer['question_id'],
            $answer['selected_answer'],
            $answer['is_correct']
        );

        $insert_answer->execute();
    }

    $insert_answer->close();
}

// Get category ID from URL
$category_id = isset($_GET['category'])
    ? intval($_GET['category'])
    : 1;

$user_id = $_SESSION['user_id'];

// Validate category exists
$stmt = $conn->prepare("SELECT * FROM categories WHERE id = ?");
$stmt->bind_param("i", $category_id);
$stmt->execute();

$category_check = $stmt->get_result();

if ($category_check->num_rows == 0) {
    redirect('dashboard.php');
}

$category = $category_check->fetch_assoc();
$stmt->close();

// ============================================
// QUIZ SESSION KEY
// ============================================
$quiz_key = 'quiz_' . $category_id;

// Default shape of a fresh quiz session.
function fresh_quiz_state() {
    return [
        'current_question' => 0,
        'score' => 0,
        'answers' => [],
        'questions' => [],
        'total_questions' => 0,
        'completed' => false,
        'quiz_id' => null,
        'start_time' => null,
        'started' => false
    ];
}

// ============================================
// HANDLE RESTART - DO THIS FIRST
// ============================================
if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['action']) &&
    $_POST['action'] === 'restart'
) {
    $_SESSION[$quiz_key] = fresh_quiz_state();

    session_write_close();

    header("Location: quiz.php?category=$category_id");
    exit();
}

// ============================================
// HANDLE START - USER PRESSED "START QUIZ"
// ============================================
if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['action']) &&
    $_POST['action'] === 'start'
) {
    if (!isset($_SESSION[$quiz_key])) {
        $_SESSION[$quiz_key] = fresh_quiz_state();
    }

    $_SESSION[$quiz_key]['started'] = true;

    session_write_close();

    header("Location: quiz.php?category=$category_id");
    exit();
}

// ============================================
// HANDLE TIMEOUT
// ============================================
if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['action']) &&
    $_POST['action'] === 'timeout'
) {
    if (
        isset($_SESSION[$quiz_key]) &&
        !$_SESSION[$quiz_key]['completed'] &&
        !empty($_SESSION[$quiz_key]['questions'])
    ) {
        $quiz = &$_SESSION[$quiz_key];

        $quiz['completed'] = true;

        save_quiz_results(
            $conn,
            $quiz,
            $user_id,
            $category_id
        );
    }

    session_write_close();

    header("Location: quiz.php?category=$category_id");
    exit();
}

// ============================================
// HANDLE ANSWER SUBMISSION
// ============================================
if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['answer_letter']) &&
    isset($_POST['action']) &&
    $_POST['action'] === 'answer'
) {
    if (!isset($_SESSION[$quiz_key])) {
        $_SESSION[$quiz_key] = fresh_quiz_state();
        $_SESSION[$quiz_key]['started'] = true;
    }

    $quiz = &$_SESSION[$quiz_key];

    if ($quiz['completed']) {
        session_write_close();

        header("Location: quiz.php?category=$category_id");
        exit();
    }

    // Check timer
    if (
        $quiz['start_time'] &&
        (time() - $quiz['start_time']) >= QUIZ_TIME_LIMIT
    ) {
        $quiz['completed'] = true;

        save_quiz_results(
            $conn,
            $quiz,
            $user_id,
            $category_id
        );

        session_write_close();

        header("Location: quiz.php?category=$category_id");
        exit();
    }

    $answer_letter = $_POST['answer_letter'];
    $current_index = $quiz['current_question'];

    if (
        empty($quiz['questions']) ||
        !isset($quiz['questions'][$current_index])
    ) {
        session_write_close();

        header("Location: quiz.php?category=$category_id");
        exit();
    }

    $current_question = $quiz['questions'][$current_index];

    $is_correct = (
        $answer_letter == $current_question['correct_answer']
    );

    // Record answer
    $quiz['answers'][$current_index] = [
        'question_id' => $current_question['id'],
        'selected_answer' => $answer_letter,
        'correct_answer' => $current_question['correct_answer'],
        'is_correct' => $is_correct
    ];

    if ($is_correct) {
        $quiz['score']++;
    }

    // Move to next question
    $quiz['current_question'] = $current_index + 1;

    // Check if quiz is complete
    if (
        $quiz['current_question'] >=
        $quiz['total_questions']
    ) {
        $quiz['completed'] = true;

        save_quiz_results(
            $conn,
            $quiz,
            $user_id,
            $category_id
        );
    }

    session_write_close();

    header("Location: quiz.php?category=$category_id");
    exit();
}

// ============================================
// CREATE SESSION IF NOT EXISTS
// ============================================
if (!isset($_SESSION[$quiz_key])) {
    $_SESSION[$quiz_key] = fresh_quiz_state();
}

$quiz = &$_SESSION[$quiz_key];

// ============================================
// LOAD QUESTIONS
// ============================================
if (
    $quiz['started'] &&
    empty($quiz['questions'])
) {
    $stmt = $conn->prepare("
        SELECT
            id,
            question_text,
            option_a,
            option_b,
            option_c,
            option_d,
            correct_answer,
            explanation
        FROM questions
        WHERE category_id = ?
        AND is_active = 1
        ORDER BY RAND()
        LIMIT ?
    ");

    $limit = QUIZ_QUESTIONS_PER_ATTEMPT;

    $stmt->bind_param(
        "ii",
        $category_id,
        $limit
    );

    $stmt->execute();

    $questions_query = $stmt->get_result();

    $questions = [];

    while ($row = $questions_query->fetch_assoc()) {

        $options = [];

        $letters = ['A', 'B', 'C', 'D'];

        $option_values = [
            $row['option_a'],
            $row['option_b'],
            $row['option_c'],
            $row['option_d']
        ];

        foreach (
            $letters as $index => $letter
        ) {
            if (!empty($option_values[$index])) {

                $options[] = [
                    'id' => $letter,
                    'text' => $option_values[$index],
                    'is_correct' => (
                        $letter ==
                        $row['correct_answer']
                    )
                ];
            }
        }

        $questions[] = [
            'id' => $row['id'],
            'question_text' => $row['question_text'],
            'options' => $options,
            'correct_answer' => $row['correct_answer'],
            'explanation' => $row['explanation']
        ];
    }

    $stmt->close();

    $quiz['questions'] = $questions;

    $quiz['total_questions'] =
        count($questions);

    if ($quiz['current_question'] == 0) {
        $quiz['current_question'] = 0;
    }

    $quiz['score'] = 0;
    $quiz['answers'] = [];
    $quiz['completed'] = false;

    // Timer starts only after Start Quiz
    $quiz['start_time'] = time();
}

// ============================================
// PRE-START QUESTION COUNT
// ============================================
$available_count = 0;
$questions_this_attempt = 0;

if (!$quiz['started']) {

    $cnt_stmt = $conn->prepare("
        SELECT COUNT(*) AS cnt
        FROM questions
        WHERE category_id = ?
        AND is_active = 1
    ");

    $cnt_stmt->bind_param(
        "i",
        $category_id
    );

    $cnt_stmt->execute();

    $available_count =
        (int) $cnt_stmt
        ->get_result()
        ->fetch_assoc()['cnt'];

    $cnt_stmt->close();

    $questions_this_attempt =
        min(
            $available_count,
            QUIZ_QUESTIONS_PER_ATTEMPT
        );
}

// ============================================
// GET CURRENT STATE
// ============================================
$total_questions =
    $quiz['total_questions'];

$current_index =
    $quiz['current_question'];

$current_question =
    isset($quiz['questions'][$current_index])
        ? $quiz['questions'][$current_index]
        : null;

// Progress
$progress =
    $total_questions > 0
        ? round(
            ($current_index / $total_questions) * 100
        )
        : 0;

$passing_score = 75;

$score_percentage =
    $total_questions > 0
        ? round(
            ($quiz['score'] / $total_questions) * 100
        )
        : 0;

$is_passed =
    $score_percentage >= $passing_score;

// ============================================
// TIME TRACKING
// ============================================
$time_remaining = QUIZ_TIME_LIMIT;

if (
    !empty($quiz['start_time']) &&
    $total_questions > 0
) {
    $elapsed =
        time() - $quiz['start_time'];

    $time_remaining =
        max(
            0,
            QUIZ_TIME_LIMIT - $elapsed
        );

    // Safety timeout
    if (
        $time_remaining <= 0 &&
        !$quiz['completed']
    ) {
        $quiz['completed'] = true;

        save_quiz_results(
            $conn,
            $quiz,
            $user_id,
            $category_id
        );

        $is_passed =
            (
                $quiz['total_questions'] > 0 &&
                round(
                    ($quiz['score'] /
                    $quiz['total_questions']) * 100
                ) >= $passing_score
            );

        $score_percentage =
            $quiz['total_questions'] > 0
                ? round(
                    ($quiz['score'] /
                    $quiz['total_questions']) * 100
                )
                : 0;
    }
}

$header_question_count =
    $quiz['started']
        ? $total_questions
        : $questions_this_attempt;

// Get global brightness
$global_brightness = getGlobalBrightness();

?>
<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <?php include __DIR__ . '/includes/theme_preload.php'; ?>

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        Quiz -
        <?php echo htmlspecialchars($category['name']); ?>
        -
        <?php echo SITE_NAME; ?>
    </title>

    <link
        href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&family=Rajdhani:wght@300;400;600&display=swap"
        rel="stylesheet"
    >

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css"
    >

    <style>

        /* =========================================
           THEME VARIABLES
        ========================================= */

        :root {

            --global-brightness: 100%;

            --bg-primary: #0a0f1e;
            --bg-secondary: #050812;
            --bg-card: rgba(10, 20, 40, 0.7);
            --bg-card-hover: rgba(10, 20, 40, 0.9);
            --bg-sidebar: rgba(5, 8, 18, 0.95);
            --bg-input: rgba(0, 0, 0, 0.5);
            --bg-stat: rgba(0, 0, 0, 0.3);
            --bg-table: rgba(10, 20, 40, 0.7);

            --text-primary: #e0e0ff;
            --text-secondary: #8899cc;
            --text-muted: #94a3b8;
            --text-white: #ffffff;
            --text-dark: #050812;

            --border-color: rgba(0, 255, 157, 0.3);
            --border-light: rgba(0, 255, 157, 0.1);

            --shadow-color: rgba(0, 255, 157, 0.2);
            --shadow-glow: rgba(0, 255, 157, 0.4);

            --cyber-bg1: #0a0f1e;
            --cyber-bg2: #050812;
            --grid-color: rgba(0, 255, 157, 0.03);

            --card-bg: rgba(10, 20, 40, 0.7);
            --card-border: rgba(0, 255, 157, 0.3);

            --welcome-border: 2px solid #00ff9d;

            

            --correct-color: #00ff9d;
            --wrong-color: #ff3366;
            --option-hover: rgba(0, 255, 157, 0.1);
            --option-selected: rgba(0, 255, 157, 0.15);
        }

        /* =========================================
           LIGHT THEME
        ========================================= */

        [data-theme="light"] {

            --bg-primary: #d5dce6;
            --bg-secondary: #c8d0dd;
            --bg-card: rgba(255, 255, 255, 0.92);
            --bg-card-hover: rgba(255, 255, 255, 0.98);
            --bg-sidebar: rgba(240, 244, 248, 0.95);
            --bg-input: rgba(255, 255, 255, 0.95);
            --bg-stat: rgba(0, 0, 0, 0.04);
            --bg-table: rgba(255, 255, 255, 0.92);

            --text-primary: #1a2332;
            --text-secondary: #3d4a5e;
            --text-muted: #5a6b7e;
            --text-white: #1a2332;
            --text-dark: #1a2332;

            --border-color: rgba(0, 160, 100, 0.4);
            --border-light: rgba(0, 160, 100, 0.2);

            --shadow-color: rgba(0, 160, 100, 0.15);
            --shadow-glow: rgba(0, 160, 100, 0.3);

            --cyber-bg1: #d5dce6;
            --cyber-bg2: #c8d0dd;
            --grid-color: rgba(0, 160, 100, 0.06);

            --card-bg: rgba(255, 255, 255, 0.92);
            --card-border: rgba(0, 160, 100, 0.35);

            --welcome-border: 2px solid #00aa77;

            --scrollbar-track: #c8d0dd;
            --scrollbar-thumb: #00aa77;

            --option-hover: rgba(0, 160, 100, 0.08);
            --option-selected: rgba(0, 160, 100, 0.12);
        }

        /* =========================================
           IMPORTANT BRIGHTNESS FIX
           Applies to BOTH DARK and LIGHT modes.
        ========================================= */

        html {
            --global-brightness: 100%;
        }

        body {
            filter: brightness(
                var(--global-brightness, 100%)
            );
        }

        /* =========================================
           RESET
        ========================================= */

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        /* =========================================
           BODY
        ========================================= */

        body {
            font-family: 'Rajdhani', sans-serif;
            background: var(--bg-primary);
            color: var(--text-primary);
            min-height: 100vh;
            transition:
                background 0.3s ease,
                color 0.3s ease;
        }

        /* =========================================
           CYBER BACKGROUND
        ========================================= */

        .cyber-bg {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -2;
            background:
                linear-gradient(
                    135deg,
                    var(--cyber-bg1) 0%,
                    var(--cyber-bg2) 100%
                );
            transition: background 0.3s ease;
        }

        .grid-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image:
                linear-gradient(
                    var(--grid-color) 1px,
                    transparent 1px
                ),
                linear-gradient(
                    90deg,
                    var(--grid-color) 1px,
                    transparent 1px
                );
            background-size: 50px 50px;
            z-index: -1;
            animation: gridMove 20s linear infinite;
            transition: background-image 0.3s ease;
        }

        @keyframes gridMove {
            0% {
                transform: translate(0, 0);
            }

            100% {
                transform: translate(50px, 50px);
            }
        }

        /* =========================================
           CONTAINER
        ========================================= */

        .quiz-container {
            max-width: 900px;
            margin: 40px auto;
            padding: 0 20px;
        }

        /* =========================================
           HEADER
        ========================================= */

        .quiz-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            flex-wrap: wrap;
            gap: 15px;
        }

        .quiz-header h1 {
            font-family: 'Orbitron', sans-serif;
            font-size: 1.8rem;
            background:
                linear-gradient(
                    135deg,
                    #00ff9d,
                    #0066ff
                );
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .quiz-meta {
            display: flex;
            align-items: center;
            gap: 20px;
            flex-wrap: wrap;
        }

        .quiz-meta .badge {
            padding: 8px 16px;
            border-radius: 20px;
            border: 1px solid var(--border-color);
            font-size: 0.85rem;
            color: var(--text-secondary);
        }

        .quiz-meta .badge i {
            margin-right: 8px;
            color: #00ff9d;
        }

        #timer-badge {
            font-variant-numeric: tabular-nums;
            transition:
                color 0.3s ease,
                border-color 0.3s ease;
        }

        #timer-badge.time-warning {
            color: var(--wrong-color);
            border-color: var(--wrong-color);
        }

        #timer-badge.time-warning i {
            color: var(--wrong-color);
        }

        @keyframes timer-pulse {
            0%,
            100% {
                opacity: 1;
            }

            50% {
                opacity: 0.5;
            }
        }

        #timer-badge.time-warning {
            animation: timer-pulse 1s infinite;
        }

        /* =========================================
           PROGRESS
        ========================================= */

        .progress-section {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 16px;
            padding: 20px 25px;
            margin-bottom: 30px;
            backdrop-filter: blur(10px);
            transition:
                background 0.3s ease,
                border 0.3s ease;
        }

        .progress-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 12px;
            flex-wrap: wrap;
            gap: 10px;
        }

        .progress-header .question-counter {
            font-size: 1rem;
            color: var(--text-secondary);
        }

        .progress-header .question-counter span {
            color: #00ff9d;
            font-weight: bold;
            font-size: 1.2rem;
        }

        .progress-header .score-display {
            font-size: 1rem;
            color: var(--text-secondary);
        }

        .progress-header .score-display span {
            color: #00ff9d;
            font-weight: bold;
            font-size: 1.2rem;
        }

        .progress-bar-container {
            width: 100%;
            height: 8px;
            background: var(--bg-stat);
            border-radius: 10px;
            overflow: hidden;
        }

        .progress-bar-fill {
            height: 100%;
            background:
                linear-gradient(
                    90deg,
                    #00ff9d,
                    #0066ff
                );
            border-radius: 10px;
            transition: width 0.5s ease;
        }

        /* =========================================
           QUESTION CARD
        ========================================= */

        .question-card {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 20px;
            padding: 35px;
            backdrop-filter: blur(10px);
            transition:
                background 0.3s ease,
                border 0.3s ease;
            animation: fadeIn 0.4s ease;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .question-number {
            font-size: 0.9rem;
            color: var(--text-secondary);
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid var(--border-light);
        }

        .question-text {
            font-size: 1.4rem;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 30px;
            line-height: 1.6;
        }

        .options-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 12px;
        }

        .option-btn {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 16px 20px;
            background: var(--bg-stat);
            border: 2px solid var(--border-light);
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s ease;
            color: var(--text-primary);
            font-size: 1rem;
            font-family: 'Rajdhani', sans-serif;
            width: 100%;
            text-align: left;
        }

        .option-btn:hover:not(.disabled) {
            border-color: #00ff9d;
            background: var(--option-hover);
            transform: translateX(5px);
        }

        .option-btn .option-letter {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: rgba(0, 255, 157, 0.1);
            border: 1px solid var(--border-color);
            color: #00ff9d;
            font-weight: bold;
            font-size: 0.9rem;
            flex-shrink: 0;
            transition: all 0.3s ease;
        }

        .option-btn:hover:not(.disabled) .option-letter {
            background: rgba(0, 255, 157, 0.2);
            border-color: #00ff9d;
        }

        .option-btn .option-text {
            flex: 1;
        }

        /* =========================================
           BUTTONS
        ========================================= */

        .quiz-actions {
            margin-top: 30px;
            display: flex;
            gap: 15px;
            justify-content: flex-end;
            flex-wrap: wrap;
        }

        .btn {
            padding: 12px 30px;
            border: none;
            border-radius: 10px;
            font-family: 'Rajdhani', sans-serif;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-primary {
            background:
                linear-gradient(
                    135deg,
                    #00ff9d,
                    #0066ff
                );
            color: var(--text-dark);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow:
                0 5px 20px var(--shadow-glow);
        }

        .btn-secondary {
            background: transparent;
            border: 1px solid var(--border-color);
            color: var(--text-secondary);
        }

        .btn-secondary:hover {
            border-color: #00ff9d;
            color: #00ff9d;
            background: var(--option-hover);
        }

        .btn-danger {
            background: transparent;
            border: 1px solid var(--wrong-color);
            color: var(--wrong-color);
        }

        .btn-danger:hover {
            background: rgba(255, 51, 102, 0.1);
            transform: translateY(-2px);
        }

        .btn-success {
            background: #00ff9d;
            color: var(--text-dark);
        }

        .btn-success:hover {
            transform: translateY(-2px);
            box-shadow:
                0 5px 20px rgba(0, 255, 157, 0.3);
        }

        .btn-back {
            padding: 10px 20px;
            background: transparent;
            border: 1px solid var(--border-color);
            color: var(--text-secondary);
            border-radius: 8px;
            text-decoration: none;
            font-family: 'Rajdhani', sans-serif;
            font-size: 0.9rem;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }

        .btn-back:hover {
            border-color: #00ff9d;
            color: #00ff9d;
        }

        /* =========================================
           EMPTY / START / RESULT
        ========================================= */

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 20px;
        }

        .empty-state i {
            font-size: 4rem;
            margin-bottom: 20px;
        }

        .empty-state .icon-pass {
            color: #00ff9d;
        }

        .empty-state .icon-fail {
            color: #ff3366;
        }

        .empty-state .icon-start {
            color: #00ff9d;
        }

        .empty-state h2 {
            color: var(--text-secondary);
            margin-bottom: 10px;
        }

        .empty-state p {
            color: var(--text-muted);
            margin-bottom: 20px;
        }

        .start-screen h2 {
            color: var(--text-primary);
            font-family: 'Orbitron', sans-serif;
            font-size: 1.6rem;
            margin-bottom: 10px;
        }

        .start-screen .start-desc {
            color: var(--text-secondary);
            font-size: 1rem;
            max-width: 480px;
            margin: 0 auto 25px;
            line-height: 1.6;
        }

        .start-info {
            display: flex;
            gap: 30px;
            justify-content: center;
            flex-wrap: wrap;
            margin: 25px 0 30px;
        }

        .start-info .detail-item {
            text-align: center;
        }

        .start-info .detail-item .label {
            color: var(--text-secondary);
            font-size: 0.85rem;
        }

        .start-info .detail-item .value {
            font-size: 1.5rem;
            font-weight: bold;
            color: #00ff9d;
            font-family: 'Orbitron', sans-serif;
        }

        .btn-start {
            padding: 16px 45px;
            font-size: 1.1rem;
        }

        .result-score {
            font-size: 3rem;
            font-weight: bold;
            font-family: 'Orbitron', sans-serif;
            margin: 20px 0;
        }

        .result-score.pass {
            color: #00ff9d;
        }

        .result-score.fail {
            color: #ff3366;
        }

        .result-details {
            display: flex;
            gap: 30px;
            justify-content: center;
            flex-wrap: wrap;
            margin: 20px 0;
        }

        .result-details .detail-item {
            text-align: center;
        }

        .result-details .detail-item .label {
            color: var(--text-secondary);
            font-size: 0.85rem;
        }

        .result-details .detail-item .value {
            font-size: 1.5rem;
            font-weight: bold;
            color: #00ff9d;
        }

        .result-actions {
            display: flex;
            gap: 15px;
            justify-content: center;
            flex-wrap: wrap;
            margin-top: 20px;
        }

        /* =========================================
           SCROLLBAR
        ========================================= */

        ::-webkit-scrollbar {
            width: 10px;
        }

        ::-webkit-scrollbar-track {
            background: var(--scrollbar-track);
            border-radius: 10px;
        }

        ::-webkit-scrollbar-thumb {
            background: var(--scrollbar-thumb);
            border-radius: 10px;
        }

        /* =========================================
           MOBILE
        ========================================= */

        @media (max-width: 768px) {

            .quiz-container {
                margin: 20px auto;
                padding: 0 15px;
            }

            .quiz-header {
                flex-direction: column;
                align-items: flex-start;
            }

            .quiz-header h1 {
                font-size: 1.4rem;
            }

            .quiz-meta {
                width: 100%;
                justify-content: space-between;
            }

            .question-card {
                padding: 20px;
            }

            .question-text {
                font-size: 1.1rem;
            }

            .option-btn {
                padding: 14px 16px;
                font-size: 0.95rem;
            }

            .option-btn .option-letter {
                width: 30px;
                height: 30px;
                font-size: 0.8rem;
            }

            .quiz-actions {
                flex-direction: column;
            }

            .quiz-actions .btn {
                width: 100%;
                justify-content: center;
            }

            .result-details,
            .start-info {
                gap: 15px;
            }

            .result-score {
                font-size: 2rem;
            }
        }

        @media (max-width: 480px) {

            .progress-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 5px;
            }
        }

        /* =========================================
           BRIGHTNESS WIDGET
        ========================================= */

        #quiz-brightness-widget {
            position: fixed;
            right: 20px;
            bottom: 20px;
            z-index: 2147483647;
        }

    </style>

</head>

<body>

    <div class="cyber-bg"></div>
    <div class="grid-overlay"></div>

    <div class="quiz-container">

        <!-- HEADER -->
        <div class="quiz-header">

            <div>

                <a
                    href="dashboard.php"
                    class="btn-back"
                >
                    <i class="fas fa-arrow-left"></i>
                    Back to Dashboard
                </a>

                <h1 style="margin-top: 15px;">

                    <i
                        class="fas fa-pencil-alt"
                        style="
                            color:#00ff9d;
                            -webkit-text-fill-color:#00ff9d;
                        "
                    ></i>

                    <?php
                    echo htmlspecialchars(
                        $category['name']
                    );
                    ?>
                    Quiz

                </h1>

            </div>

            <div class="quiz-meta">

                <span class="badge">

                    <i class="fas fa-question-circle"></i>

                    <?php
                    echo $header_question_count;
                    ?>
                    Questions

                </span>

                <?php
                if (
                    $quiz['started'] &&
                    $total_questions > 0 &&
                    !$quiz['completed']
                ):
                ?>

                    <span
                        class="badge"
                        id="timer-badge"
                    >

                        <i class="fas fa-stopwatch"></i>

                        <span id="quizTimer">

                            <?php
                            echo sprintf(
                                '%d:%02d',
                                intdiv(
                                    $time_remaining,
                                    60
                                ),
                                $time_remaining % 60
                            );
                            ?>

                        </span>

                    </span>

                <?php else: ?>

                    <span class="badge">

                        <i class="fas fa-clock"></i>

                        5 Min Time Limit

                    </span>

                <?php endif; ?>

            </div>

        </div>

        <!-- TIMEOUT FORM -->

        <form
            method="POST"
            action=""
            id="timeoutForm"
            style="display:none;"
        >

            <input
                type="hidden"
                name="action"
                value="timeout"
            >

        </form>

        <?php if (
            $quiz['completed'] &&
            $total_questions > 0
        ): ?>

            <!-- QUIZ COMPLETED -->

            <div class="empty-state">

                <i
                    class="fas fa-check-circle
                    <?php
                    echo $is_passed
                        ? 'icon-pass'
                        : 'icon-fail';
                    ?>"
                ></i>

                <h2>
                    Quiz Completed!
                </h2>

                <div
                    class="result-score
                    <?php
                    echo $is_passed
                        ? 'pass'
                        : 'fail';
                    ?>"
                >
                    <?php
                    echo $score_percentage;
                    ?>%
                </div>

                <div class="result-details">

                    <div class="detail-item">

                        <div class="label">
                            Score
                        </div>

                        <div class="value">
                            <?php
                            echo $quiz['score'];
                            ?>
                            /
                            <?php
                            echo $total_questions;
                            ?>
                        </div>

                    </div>

                    <div class="detail-item">

                        <div class="label">
                            Result
                        </div>

                        <div
                            class="value"
                            style="
                                color:
                                <?php
                                echo $is_passed
                                    ? '#00ff9d'
                                    : '#ff3366';
                                ?>
                            "
                        >

                            <?php
                            echo $is_passed
                                ? '✅ PASSED'
                                : '❌ FAILED';
                            ?>

                        </div>

                    </div>

                    <div class="detail-item">

                        <div class="label">
                            Passing Score
                        </div>

                        <div
                            class="value"
                            style="color:#ffaa00;"
                        >
                            <?php
                            echo $passing_score;
                            ?>%
                        </div>

                    </div>

                </div>

                <form
                    method="POST"
                    action=""
                >

                    <input
                        type="hidden"
                        name="action"
                        value="restart"
                    >

                    <div class="result-actions">

                        <?php if (!$is_passed): ?>

                            <button
                                type="submit"
                                class="btn btn-primary"
                            >

                                <i class="fas fa-redo"></i>

                                Retake Quiz

                            </button>

                        <?php else: ?>

                            <a
                                href="dashboard.php"
                                class="btn btn-success"
                            >

                                <i class="fas fa-check-circle"></i>

                                Proceed to Next COC

                            </a>

                        <?php endif; ?>

                    </div>

                </form>

            </div>

        <?php elseif (!$quiz['started']): ?>

            <!-- START SCREEN -->

            <div class="empty-state start-screen">

                <i
                    class="fas fa-play-circle icon-start"
                ></i>

                <h2>

                    <?php
                    echo htmlspecialchars(
                        $category['name']
                    );
                    ?>
                    Quiz

                </h2>

                <p class="start-desc">

                    You'll have
                    <?php
                    echo intdiv(
                        QUIZ_TIME_LIMIT,
                        60
                    );
                    ?>
                    minutes to answer
                    <?php
                    echo $questions_this_attempt;
                    ?>
                    questions once you start, picked at random from this COC's question bank.

                    The timer keeps running once it begins, even if you close this page,
                    so make sure you're ready before you press Start.

                </p>

                <div class="start-info">

                    <div class="detail-item">

                        <div class="label">
                            Questions
                        </div>

                        <div class="value">
                            <?php
                            echo $questions_this_attempt;
                            ?>
                        </div>

                    </div>

                    <div class="detail-item">

                        <div class="label">
                            Time Limit
                        </div>

                        <div class="value">
                            <?php
                            echo intdiv(
                                QUIZ_TIME_LIMIT,
                                60
                            );
                            ?>
                            min
                        </div>

                    </div>

                    <div class="detail-item">

                        <div class="label">
                            Passing Score
                        </div>

                        <div
                            class="value"
                            style="color:#ffaa00;"
                        >
                            <?php
                            echo $passing_score;
                            ?>%
                        </div>

                    </div>

                </div>

                <?php if (
                    $questions_this_attempt > 0
                ): ?>

                    <form
                        method="POST"
                        action=""
                    >

                        <input
                            type="hidden"
                            name="action"
                            value="start"
                        >

                        <button
                            type="submit"
                            class="btn btn-primary btn-start"
                        >

                            <i class="fas fa-play"></i>

                            Start Quiz

                        </button>

                    </form>

                <?php else: ?>

                    <p
                        style="
                            color:#ffaa00;
                            margin-top:10px;
                        "
                    >

                        <i
                            class="fas fa-exclamation-triangle"
                        ></i>

                        No questions are available for
                        this category yet.

                    </p>

                    <a
                        href="dashboard.php"
                        class="btn btn-secondary"
                        style="margin-top:15px;"
                    >

                        <i class="fas fa-home"></i>

                        Return to Dashboard

                    </a>

                <?php endif; ?>

            </div>

        <?php elseif (
            $total_questions > 0 &&
            !$quiz['completed'] &&
            $current_question
        ): ?>

            <!-- PROGRESS -->

            <div class="progress-section">

                <div class="progress-header">

                    <span class="question-counter">

                        Question

                        <span>
                            <?php
                            echo $current_index + 1;
                            ?>
                        </span>

                        of
                        <?php
                        echo $total_questions;
                        ?>

                    </span>

                    <span class="score-display">

                        Score:

                        <span>
                            <?php
                            echo $quiz['score'];
                            ?>
                        </span>

                    </span>

                </div>

                <div class="progress-bar-container">

                    <div
                        class="progress-bar-fill"
                        style="
                            width:
                            <?php
                            echo $progress;
                            ?>%;
                        "
                    ></div>

                </div>

            </div>

            <!-- QUESTION -->

            <div class="question-card">

                <div class="question-number">

                    <i
                        class="fas fa-question-circle"
                        style="color:#00ff9d;"
                    ></i>

                    Question
                    <?php
                    echo $current_index + 1;
                    ?>

                </div>

                <div class="question-text">

                    <?php
                    echo htmlspecialchars(
                        $current_question['question_text']
                    );
                    ?>

                </div>

                <form
                    method="POST"
                    action=""
                    id="quizForm"
                >

                    <input
                        type="hidden"
                        name="action"
                        value="answer"
                    >

                    <div class="options-grid">

                        <?php

                        foreach (
                            $current_question['options']
                            as $option
                        ):

                        ?>

                            <button
                                type="submit"
                                name="answer_letter"
                                value="<?php
                                echo $option['id'];
                                ?>"
                                class="option-btn"
                            >

                                <span
                                    class="option-letter"
                                >
                                    <?php
                                    echo $option['id'];
                                    ?>
                                </span>

                                <span
                                    class="option-text"
                                >
                                    <?php
                                    echo htmlspecialchars(
                                        $option['text']
                                    );
                                    ?>
                                </span>

                            </button>

                        <?php endforeach; ?>

                    </div>

                </form>

                <div class="quiz-actions">

                    <form
                        method="POST"
                        action=""
                        style="display:inline;"
                    >

                        <input
                            type="hidden"
                            name="action"
                            value="restart"
                        >

                        <button
                            type="submit"
                            class="btn btn-danger"
                            onclick="
                                return confirm(
                                    'Are you sure you want to restart this quiz?'
                                );
                            "
                        >

                            <i class="fas fa-redo"></i>

                            Restart Quiz

                        </button>

                    </form>

                </div>

            </div>

        <?php else: ?>

            <!-- NO QUESTIONS -->

            <div class="empty-state">

                <i
                    class="fas fa-exclamation-triangle"
                    style="color:#ffaa00;"
                ></i>

                <h2>
                    No Questions Available
                </h2>

                <p>
                    There are currently no questions
                    for this category.
                    Please check back later.
                </p>

                <a
                    href="dashboard.php"
                    class="btn btn-primary"
                >

                    <i class="fas fa-home"></i>

                    Return to Dashboard

                </a>

            </div>

        <?php endif; ?>

    </div>

    <!-- =========================================
         QUIZ JAVASCRIPT
    ========================================= -->

    <script>

        // =========================================
        // ANSWER SUBMISSION
        // =========================================

        document
            .getElementById('quizForm')
            ?.addEventListener(
                'submit',
                function(e) {

                    const submitter =
                        e.submitter;

                    if (
                        submitter &&
                        submitter.name ===
                        'answer_letter'
                    ) {

                        let hidden =
                            this.querySelector(
                                'input[name="answer_letter"][type="hidden"]'
                            );

                        if (!hidden) {

                            hidden =
                                document.createElement(
                                    'input'
                                );

                            hidden.type =
                                'hidden';

                            hidden.name =
                                'answer_letter';

                            this.appendChild(
                                hidden
                            );
                        }

                        hidden.value =
                            submitter.value;

                        submitter.removeAttribute(
                            'name'
                        );
                    }

                    const buttons =
                        this.querySelectorAll(
                            '.option-btn'
                        );

                    buttons.forEach(
                        function(btn) {

                            btn.innerHTML =
                                '<i class="fas fa-spinner fa-spin"></i> Submitting...';

                            btn.disabled =
                                true;
                        }
                    );

                }
            );

        // =========================================
        // KEYBOARD NAVIGATION
        // =========================================

        document.addEventListener(
            'keydown',
            function(e) {

                if (
                    e.target.tagName ===
                    'INPUT' ||
                    e.target.tagName ===
                    'TEXTAREA'
                ) {
                    return;
                }

                const num =
                    parseInt(e.key);

                if (
                    num >= 1 &&
                    num <= 4
                ) {

                    const options =
                        document.querySelectorAll(
                            '.option-btn'
                        );

                    if (
                        options[num - 1] &&
                        !options[num - 1].disabled
                    ) {

                        options[num - 1].click();
                    }
                }
            }
        );

        // =========================================
        // DEBUG
        // =========================================

        document
            .getElementById('quizForm')
            ?.addEventListener(
                'submit',
                function() {
                    console.log(
                        'Quiz form submitted!'
                    );
                }
            );

        // =========================================
        // QUIZ TIMER
        // =========================================

        <?php
        if (
            $quiz['started'] &&
            $total_questions > 0 &&
            !$quiz['completed']
        ):
        ?>

        (function() {

            let timeRemaining =
                <?php
                echo (int)$time_remaining;
                ?>;

            const display =
                document.getElementById(
                    'quizTimer'
                );

            const badge =
                document.getElementById(
                    'timer-badge'
                );

            let submitted = false;

            function render() {

                if (!display) {
                    return;
                }

                const m =
                    Math.floor(
                        timeRemaining / 60
                    );

                const s =
                    timeRemaining % 60;

                display.textContent =
                    m +
                    ':' +
                    String(s)
                        .padStart(2, '0');

                if (
                    badge &&
                    timeRemaining <= 30
                ) {

                    badge.classList.add(
                        'time-warning'
                    );
                }
            }

            function timeUp() {

                if (submitted) {
                    return;
                }

                submitted = true;

                clearInterval(
                    interval
                );

                const form =
                    document.getElementById(
                        'timeoutForm'
                    );

                if (form) {
                    form.submit();
                }
            }

            render();

            if (
                timeRemaining <= 0
            ) {
                timeUp();
            }

            const interval =
                setInterval(
                    function() {

                        timeRemaining--;

                        if (
                            timeRemaining <= 0
                        ) {

                            timeRemaining = 0;

                            render();

                            timeUp();

                            return;
                        }

                        render();

                    },
                    1000
                );

        })();

        <?php endif; ?>

    </script>

    <!-- =========================================
         BRIGHTNESS WIDGET
    ========================================= -->

    <div id="quiz-brightness-widget">

        <?php
        include __DIR__ .
            '/includes/brightness_widget.php';
        ?>

    </div>

    <!-- =========================================
         THEME TOGGLE
    ========================================= -->

    <?php
    include __DIR__ .
        '/includes/theme_toggle.php';
    ?>

    <!-- =========================================
         BRIGHTNESS CONTROL SCRIPT
    ========================================= -->

    <script>

    (function () {

        'use strict';

        function applyQuizBrightness(value) {

            let brightness =
                parseFloat(value);

            if (!isFinite(brightness)) {
                return;
            }

            // Support values such as 0.5
            if (
                brightness > 0 &&
                brightness <= 1
            ) {
                brightness *= 100;
            }

            // Safe range
            brightness =
                Math.max(
                    10,
                    Math.min(
                        200,
                        brightness
                    )
                );

            document.documentElement
                .style
                .setProperty(
                    '--global-brightness',
                    brightness + '%'
                );

            try {

                localStorage.setItem(
                    'css_tesda_brightness',
                    String(brightness)
                );

            } catch (e) {}

        }

        function bindQuizBrightness() {

            const controls =
                document.querySelectorAll(
                    '#quiz-brightness-widget input[type="range"],' +
                    'input[type="range"][id*="bright" i],' +
                    'input[type="range"][name*="bright" i],' +
                    'input[type="range"][class*="bright" i]'
                );

            controls.forEach(
                function(control) {

                    if (
                        control.dataset
                            .quizBrightnessBound ===
                        '1'
                    ) {
                        return;
                    }

                    control.dataset
                        .quizBrightnessBound =
                        '1';

                    control.addEventListener(
                        'input',
                        function() {

                            applyQuizBrightness(
                                this.value
                            );

                        }
                    );

                    control.addEventListener(
                        'change',
                        function() {

                            applyQuizBrightness(
                                this.value
                            );

                        }
                    );

                }
            );
        }

        // =========================================
        // RESTORE SAVED BRIGHTNESS
        // =========================================

        try {

            const savedBrightness =
                localStorage.getItem(
                    'css_tesda_brightness'
                );

            if (
                savedBrightness !== null
            ) {

                applyQuizBrightness(
                    savedBrightness
                );

            } else {

                applyQuizBrightness(100);

            }

        } catch (e) {

            applyQuizBrightness(100);

        }

        // Initial binding
        bindQuizBrightness();

        // =========================================
        // WATCH FOR DYNAMIC WIDGET
        // =========================================

        if (
            window.MutationObserver
        ) {

            const observer =
                new MutationObserver(
                    function() {
                        bindQuizBrightness();
                    }
                );

            observer.observe(
                document.body,
                {
                    childList: true,
                    subtree: true
                }
            );
        }

        // =========================================
        // EXISTING BRIGHTNESS EVENT
        // =========================================

        window.addEventListener(
            'brightnessChanged',
            function(event) {

                if (
                    event &&
                    event.detail !==
                    undefined
                ) {

                    applyQuizBrightness(
                        event.detail
                    );
                }

            }
        );

        // =========================================
        // SYNC BETWEEN TABS / PAGES
        // =========================================

        window.addEventListener(
            'storage',
            function(event) {

                if (
                    event.key ===
                    'css_tesda_brightness' &&
                    event.newValue !== null
                ) {

                    applyQuizBrightness(
                        event.newValue
                    );
                }

            }
        );

    })();

    </script>

</body>
</html>
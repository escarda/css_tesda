<?php
// practice_mode.php - Practice Mode with instant feedback

require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Get COC parameter
$coc = isset($_GET['coc']) ? intval($_GET['coc']) : 1;
if ($coc < 1 || $coc > 4) $coc = 1;

// Get category_id for the selected COC
$cat_query = "SELECT id, name FROM categories WHERE coc_number = ? LIMIT 1";
$stmt = $conn->prepare($cat_query);
$stmt->bind_param("i", $coc);
$stmt->execute();
$cat_result = $stmt->get_result();
$category = $cat_result->fetch_assoc();
$category_id = $category ? $category['id'] : $coc;
$stmt->close();

// Get questions for practice
$questions_per_page = 10;
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$offset = ($page - 1) * $questions_per_page;

// Get total questions count
$count_query = "SELECT COUNT(*) as total FROM questions WHERE coc_number = ? AND is_active = 1";
$stmt = $conn->prepare($count_query);
$stmt->bind_param("i", $coc);
$stmt->execute();
$total_questions = $stmt->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_questions / $questions_per_page);
$stmt->close();

// Get questions for current page
$questions_query = "SELECT * FROM questions WHERE coc_number = ? AND is_active = 1 ORDER BY RAND() LIMIT ? OFFSET ?";
$stmt = $conn->prepare($questions_query);
$stmt->bind_param("iii", $coc, $questions_per_page, $offset);
$stmt->execute();
$questions = $stmt->get_result();
$stmt->close();

// Get user progress for this COC
$progress_query = "SELECT COUNT(*) as completed FROM user_practice WHERE user_id = ? AND coc_number = ?";
$stmt = $conn->prepare($progress_query);
$stmt->bind_param("ii", $user_id, $coc);
$stmt->execute();
$progress_result = $stmt->get_result();
$completed = $progress_result->fetch_assoc()['completed'];
$stmt->close();

$progress_percentage = $total_questions > 0 ? round(($completed / $total_questions) * 100) : 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Practice Mode - COC <?php echo $coc; ?> | CSS TESDA Reviewer</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;14..32,400;14..32,500;14..32,600;14..32,700;14..32,800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: #0a0f1e;
            color: #e0e0ff;
            min-height: 100vh;
            overflow-x: hidden;
        }

        .cyber-bg {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -2;
            background: radial-gradient(circle at 20% 30%, #0f172a, #020617);
        }

        .grid-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: linear-gradient(rgba(0, 255, 157, 0.03) 1px, transparent 1px), linear-gradient(90deg, rgba(0, 255, 157, 0.03) 1px, transparent 1px);
            background-size: 50px 50px;
            z-index: -1;
            animation: gridMove 20s linear infinite;
        }

        @keyframes gridMove {
            0% { transform: translate(0, 0); }
            100% { transform: translate(50px, 50px); }
        }

        /* Navigation */
        .navbar {
            background: rgba(15, 23, 42, 0.95);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid rgba(0, 255, 157, 0.2);
            padding: 0.8rem 2rem;
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .nav-container {
            max-width: 1400px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .logo {
            font-size: 1.4rem;
            font-weight: 800;
            background: linear-gradient(135deg, #00ff9d, #3b82f6);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            text-decoration: none;
        }

        .nav-links {
            display: flex;
            gap: 1.5rem;
            align-items: center;
            flex-wrap: wrap;
        }

        .nav-links a {
            color: #94a3b8;
            text-decoration: none;
            transition: all 0.3s;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.9rem;
        }

        .nav-links a:hover, .nav-links a.active {
            color: #00ff9d;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 1rem;
            background: rgba(0, 255, 157, 0.1);
            padding: 0.4rem 1rem;
            border-radius: 40px;
        }

        .user-info i {
            color: #00ff9d;
        }

        /* Main Container */
        .container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 2rem;
        }

        /* Progress Bar */
        .progress-section {
            background: rgba(15, 23, 42, 0.6);
            border-radius: 20px;
            padding: 1rem 1.5rem;
            margin-bottom: 2rem;
            border: 1px solid rgba(0, 255, 157, 0.15);
        }

        .progress-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.5rem;
            font-size: 0.85rem;
            color: #94a3b8;
        }

        .progress-bar-container {
            width: 100%;
            height: 8px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            overflow: hidden;
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #00ff9d, #3b82f6);
            border-radius: 10px;
            transition: width 0.5s ease;
        }

        /* COC Selector */
        .coc-selector {
            display: flex;
            gap: 1rem;
            justify-content: center;
            margin-bottom: 2rem;
            flex-wrap: wrap;
        }

        .coc-btn {
            padding: 10px 24px;
            background: rgba(15, 23, 42, 0.6);
            border: 1px solid rgba(0, 255, 157, 0.2);
            border-radius: 40px;
            color: #94a3b8;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
        }

        .coc-btn:hover, .coc-btn.active {
            background: rgba(0, 255, 157, 0.15);
            border-color: #00ff9d;
            color: #00ff9d;
        }

        /* Question Card */
        .question-card {
            background: rgba(15, 23, 42, 0.7);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(0, 255, 157, 0.15);
            border-radius: 24px;
            padding: 1.8rem;
            margin-bottom: 1.5rem;
            transition: all 0.3s;
        }

        .question-card.correct {
            border-color: #00ff9d;
            background: rgba(0, 255, 157, 0.05);
        }

        .question-card.incorrect {
            border-color: #ef4444;
            background: rgba(239, 68, 68, 0.05);
        }

        .question-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
            flex-wrap: wrap;
            gap: 0.5rem;
        }

        .question-number {
            font-size: 0.8rem;
            color: #00ff9d;
            background: rgba(0, 255, 157, 0.1);
            padding: 4px 12px;
            border-radius: 20px;
        }

        .question-text {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
            line-height: 1.5;
        }

        /* Options */
        .options {
            display: flex;
            flex-direction: column;
            gap: 0.8rem;
            margin-bottom: 1.5rem;
        }

        .option {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem;
            background: rgba(0, 0, 0, 0.3);
            border: 1px solid rgba(0, 255, 157, 0.15);
            border-radius: 16px;
            cursor: pointer;
            transition: all 0.3s;
        }

        .option:hover {
            background: rgba(0, 255, 157, 0.05);
            border-color: #00ff9d;
        }

        .option.selected {
            background: rgba(0, 255, 157, 0.1);
            border-color: #00ff9d;
        }

        .option.correct-answer {
            background: rgba(0, 255, 157, 0.2);
            border-color: #00ff9d;
        }

        .option.wrong-answer {
            background: rgba(239, 68, 68, 0.2);
            border-color: #ef4444;
        }

        .option-prefix {
            width: 36px;
            height: 36px;
            background: rgba(0, 255, 157, 0.1);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            color: #00ff9d;
        }

        .option-text {
            flex: 1;
            font-size: 0.95rem;
        }

        .option-icon {
            width: 24px;
            text-align: center;
        }

        .option-icon.correct {
            color: #00ff9d;
        }

        .option-icon.wrong {
            color: #ef4444;
        }

        /* Feedback */
        .feedback {
            margin-top: 1rem;
            padding: 1rem;
            background: rgba(0, 0, 0, 0.3);
            border-radius: 16px;
            display: none;
        }

        .feedback.show {
            display: block;
            animation: fadeIn 0.3s ease;
        }

        .feedback.correct-feedback {
            border-left: 4px solid #00ff9d;
        }

        .feedback.incorrect-feedback {
            border-left: 4px solid #ef4444;
        }

        .feedback-title {
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .feedback-title.correct {
            color: #00ff9d;
        }

        .feedback-title.incorrect {
            color: #ef4444;
        }

        .feedback-text {
            color: #94a3b8;
            font-size: 0.9rem;
            line-height: 1.5;
        }

        /* Buttons */
        .action-buttons {
            display: flex;
            gap: 1rem;
            justify-content: space-between;
            margin-top: 1rem;
            flex-wrap: wrap;
        }

        .btn {
            padding: 12px 28px;
            border: none;
            border-radius: 50px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            font-size: 0.9rem;
        }

        .btn-primary {
            background: linear-gradient(135deg, #00ff9d, #3b82f6);
            color: #0f172a;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px -8px rgba(0, 255, 157, 0.4);
        }

        .btn-outline {
            background: transparent;
            border: 2px solid #00ff9d;
            color: #00ff9d;
        }

        .btn-outline:hover {
            background: #00ff9d;
            color: #0f172a;
        }

        .btn-danger {
            background: transparent;
            border: 2px solid #ef4444;
            color: #ef4444;
        }

        .btn-danger:hover {
            background: #ef4444;
            color: white;
        }

        /* Pagination */
        .pagination {
            display: flex;
            justify-content: center;
            gap: 0.5rem;
            margin-top: 2rem;
            flex-wrap: wrap;
        }

        .pagination a, .pagination span {
            padding: 8px 16px;
            background: rgba(15, 23, 42, 0.6);
            border-radius: 10px;
            text-decoration: none;
            color: #94a3b8;
            transition: all 0.3s;
        }

        .pagination a:hover, .pagination .active {
            background: #00ff9d;
            color: #0f172a;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 4rem;
            background: rgba(15, 23, 42, 0.6);
            border-radius: 24px;
        }

        .empty-state i {
            font-size: 4rem;
            color: #00ff9d;
            margin-bottom: 1rem;
            opacity: 0.5;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @media (max-width: 768px) {
            .container { padding: 1rem; }
            .question-card { padding: 1.2rem; }
            .option { padding: 0.8rem; }
            .btn { padding: 10px 20px; }
            .nav-links { width: 100%; justify-content: center; }
        }
    </style>
</head>
<body>
    <div class="cyber-bg"></div>
    <div class="grid-overlay"></div>

    <nav class="navbar">
        <div class="nav-container">
            <a href="dashboard.php" class="logo">CSS TESDA Reviewer</a>
            <div class="nav-links">
                <a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                <a href="coc1_review.php"><i class="fas fa-microchip"></i> COC 1</a>
                <a href="coc2_review.php"><i class="fas fa-network-wired"></i> COC 2</a>
                <a href="coc3_review.php"><i class="fas fa-server"></i> COC 3</a>
                <a href="coc4_review.php"><i class="fas fa-tools"></i> COC 4</a>
                <a href="quiz_selection.php"><i class="fas fa-question-circle"></i> Quiz</a>
                <a href="practice_mode.php" class="active"><i class="fas fa-chalkboard-user"></i> Practice</a>
                <div class="user-info">
                    <i class="fas fa-user"></i>
                    <span><?php echo htmlspecialchars($_SESSION['full_name'] ?? $_SESSION['username']); ?></span>
                    <a href="logout.php" style="color: #ef4444;"><i class="fas fa-sign-out-alt"></i></a>
                </div>
            </div>
        </div>
    </nav>

    <div class="container">
        <!-- Progress Section -->
        <div class="progress-section">
            <div class="progress-header">
                <span><i class="fas fa-chart-line"></i> Your Progress</span>
                <span><?php echo $completed; ?> / <?php echo $total_questions; ?> questions mastered (<?php echo $progress_percentage; ?>%)</span>
            </div>
            <div class="progress-bar-container">
                <div class="progress-fill" style="width: <?php echo $progress_percentage; ?>%"></div>
            </div>
            <p style="font-size: 0.75rem; color: #64748b; margin-top: 0.5rem;">
                <i class="fas fa-info-circle"></i> Practice at your own pace. Select an answer to see instant feedback.
            </p>
        </div>

        <!-- COC Selector -->
        <div class="coc-selector">
            <a href="?coc=1" class="coc-btn <?php echo $coc == 1 ? 'active' : ''; ?>">COC 1: Install & Configure</a>
            <a href="?coc=2" class="coc-btn <?php echo $coc == 2 ? 'active' : ''; ?>">COC 2: Set-up Networks</a>
            <a href="?coc=3" class="coc-btn <?php echo $coc == 3 ? 'active' : ''; ?>">COC 3: Set-up Servers</a>
            <a href="?coc=4" class="coc-btn <?php echo $coc == 4 ? 'active' : ''; ?>">COC 4: Maintain & Repair</a>
        </div>

        <!-- Questions -->
        <?php if ($questions->num_rows > 0): ?>
            <?php 
            $q_index = $offset + 1;
            while ($question = $questions->fetch_assoc()): 
                $question_id = $question['id'];
                $user_answer_key = "answer_q{$question_id}";
                $saved_answer = isset($_SESSION[$user_answer_key]) ? $_SESSION[$user_answer_key] : '';
                $show_feedback_key = "show_feedback_q{$question_id}";
                $show_feedback = isset($_SESSION[$show_feedback_key]) ? $_SESSION[$show_feedback_key] : false;
                $is_correct_key = "is_correct_q{$question_id}";
                $is_correct = isset($_SESSION[$is_correct_key]) ? $_SESSION[$is_correct_key] : null;
            ?>
            <div class="question-card" id="question-<?php echo $question_id; ?>" data-question-id="<?php echo $question_id; ?>" data-correct="<?php echo $question['correct_answer']; ?>">
                <div class="question-header">
                    <span class="question-number">Question <?php echo $q_index; ?></span>
                    <span class="question-category" style="font-size: 0.7rem; color: #64748b;">
                        <i class="fas fa-tag"></i> <?php echo ucfirst($question['category']); ?>
                    </span>
                </div>
                <div class="question-text"><?php echo htmlspecialchars($question['question_text']); ?></div>
                
                <div class="options">
                    <?php 
                    $options = ['A' => $question['option_a'], 'B' => $question['option_b'], 'C' => $question['option_c'], 'D' => $question['option_d']];
                    foreach ($options as $letter => $text): 
                        $is_selected = ($saved_answer == $letter);
                        $is_correct_letter = ($letter == $question['correct_answer']);
                        $option_class = '';
                        $icon_html = '';
                        
                        if ($show_feedback) {
                            if ($is_correct_letter) {
                                $option_class = 'correct-answer';
                                $icon_html = '<i class="fas fa-check-circle"></i>';
                            } elseif ($is_selected && !$is_correct_letter) {
                                $option_class = 'wrong-answer';
                                $icon_html = '<i class="fas fa-times-circle"></i>';
                            }
                            if ($is_selected) {
                                $option_class .= ' selected';
                            }
                        } elseif ($is_selected) {
                            $option_class = 'selected';
                        }
                    ?>
                        <div class="option <?php echo $option_class; ?>" data-letter="<?php echo $letter; ?>" onclick="selectAnswer(<?php echo $question_id; ?>, '<?php echo $letter; ?>')">
                            <div class="option-prefix"><?php echo $letter; ?></div>
                            <div class="option-text"><?php echo htmlspecialchars($text); ?></div>
                            <div class="option-icon <?php echo ($is_correct_letter && $show_feedback) ? 'correct' : (($is_selected && !$is_correct_letter && $show_feedback) ? 'wrong' : ''); ?>">
                                <?php echo $icon_html; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="feedback <?php echo $show_feedback ? 'show' : ''; ?> <?php echo $is_correct ? 'correct-feedback' : 'incorrect-feedback'; ?>" id="feedback-<?php echo $question_id; ?>">
                    <div class="feedback-title <?php echo $is_correct ? 'correct' : 'incorrect'; ?>">
                        <i class="fas fa-<?php echo $is_correct ? 'check-circle' : 'times-circle'; ?>"></i>
                        <?php echo $is_correct ? 'Correct!' : 'Incorrect'; ?>
                    </div>
                    <div class="feedback-text">
                        <?php echo nl2br(htmlspecialchars($question['explanation'] ?? 'No explanation available.')); ?>
                    </div>
                </div>
            </div>
            <?php 
                $q_index++;
            endwhile; 
            ?>
        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-question-circle"></i>
                <h3>No Questions Available</h3>
                <p>There are no practice questions for COC <?php echo $coc; ?> yet.</p>
                <a href="dashboard.php" class="btn btn-primary" style="margin-top: 1rem;">Go to Dashboard</a>
            </div>
        <?php endif; ?>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="?coc=<?php echo $coc; ?>&page=<?php echo $page-1; ?>">&laquo; Previous</a>
                <?php endif; ?>
                
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <a href="?coc=<?php echo $coc; ?>&page=<?php echo $i; ?>" class="<?php echo $i == $page ? 'active' : ''; ?>"><?php echo $i; ?></a>
                <?php endfor; ?>
                
                <?php if ($page < $total_pages): ?>
                    <a href="?coc=<?php echo $coc; ?>&page=<?php echo $page+1; ?>">Next &raquo;</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <!-- Action Buttons -->
        <div class="action-buttons">
            <button class="btn btn-outline" onclick="resetAnswers()">
                <i class="fas fa-undo-alt"></i> Reset Answers
            </button>
            <a href="quiz.php?coc=<?php echo $coc; ?>" class="btn btn-primary">
                <i class="fas fa-play"></i> Take Timed Quiz
            </a>
        </div>
    </div>

    <script>
        function selectAnswer(questionId, selectedLetter) {
            const questionCard = document.getElementById(`question-${questionId}`);
            const correctAnswer = questionCard.dataset.correct;
            const isCorrect = (selectedLetter === correctAnswer);
            
            // Update option styling
            const options = questionCard.querySelectorAll('.option');
            options.forEach(option => {
                const letter = option.dataset.letter;
                option.classList.remove('selected', 'correct-answer', 'wrong-answer');
                
                if (letter === correctAnswer) {
                    option.classList.add('correct-answer');
                    const iconDiv = option.querySelector('.option-icon');
                    if (iconDiv) {
                        iconDiv.innerHTML = '<i class="fas fa-check-circle"></i>';
                        iconDiv.classList.add('correct');
                    }
                }
                
                if (letter === selectedLetter && !isCorrect) {
                    option.classList.add('wrong-answer', 'selected');
                    const iconDiv = option.querySelector('.option-icon');
                    if (iconDiv) {
                        iconDiv.innerHTML = '<i class="fas fa-times-circle"></i>';
                        iconDiv.classList.add('wrong');
                    }
                }
                
                if (letter === selectedLetter && isCorrect) {
                    option.classList.add('correct-answer', 'selected');
                }
            });
            
            // Show feedback
            const feedbackDiv = document.getElementById(`feedback-${questionId}`);
            const feedbackTitle = feedbackDiv.querySelector('.feedback-title');
            const feedbackText = feedbackDiv.querySelector('.feedback-text');
            
            if (isCorrect) {
                questionCard.classList.add('correct');
                questionCard.classList.remove('incorrect');
                feedbackTitle.innerHTML = '<i class="fas fa-check-circle"></i> Correct!';
                feedbackTitle.classList.add('correct');
                feedbackTitle.classList.remove('incorrect');
                feedbackDiv.classList.add('correct-feedback');
                feedbackDiv.classList.remove('incorrect-feedback');
            } else {
                questionCard.classList.add('incorrect');
                questionCard.classList.remove('correct');
                feedbackTitle.innerHTML = '<i class="fas fa-times-circle"></i> Incorrect';
                feedbackTitle.classList.add('incorrect');
                feedbackTitle.classList.remove('correct');
                feedbackDiv.classList.add('incorrect-feedback');
                feedbackDiv.classList.remove('correct-feedback');
            }
            
            feedbackDiv.classList.add('show');
            
            // Save to session via AJAX
            fetch('save_practice_answer.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `question_id=${questionId}&answer=${selectedLetter}&is_correct=${isCorrect ? 1 : 0}&coc=<?php echo $coc; ?>`
            });
        }
        
        function resetAnswers() {
            if (confirm('Are you sure you want to reset all your answers? Your progress will be cleared.')) {
                fetch('reset_practice.php?coc=<?php echo $coc; ?>', {
                    method: 'POST'
                }).then(() => {
                    location.reload();
                });
            }
        }
        
        // Auto-save scroll position
        window.addEventListener('beforeunload', function() {
            sessionStorage.setItem('practice_scroll_pos', window.scrollY);
        });
        
        window.addEventListener('load', function() {
            const scrollPos = sessionStorage.getItem('practice_scroll_pos');
            if (scrollPos) {
                window.scrollTo(0, parseInt(scrollPos));
                sessionStorage.removeItem('practice_scroll_pos');
            }
        });
    </script>
     <?php $global_brightness = getGlobalBrightness(); include __DIR__ . '/includes/brightness_widget.php'; ?>
     <?php include __DIR__ . '/includes/theme_toggle.php'; ?>
</body>
</html>
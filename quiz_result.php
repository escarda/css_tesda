<?php
require_once 'config.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

$quiz_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Get quiz details with user and category info
$quiz_query = "
    SELECT q.*, u.username, u.full_name, u.id as user_id, 
           c.name as category_name, c.icon as category_icon,
           (q.score / q.total_questions * 100) as percentage,
           CASE 
               WHEN (q.score / q.total_questions * 100) >= 90 THEN 'Excellent'
               WHEN (q.score / q.total_questions * 100) >= 75 THEN 'Passed'
               ELSE 'Failed'
           END as status
    FROM quizzes q
    JOIN users u ON q.user_id = u.id
    JOIN categories c ON q.category_id = c.id
    WHERE q.id = $quiz_id AND q.user_id = {$_SESSION['user_id']}
";

$quiz_result = $conn->query($quiz_query);

if ($quiz_result->num_rows == 0) {
    redirect('dashboard.php');
}

$quiz = $quiz_result->fetch_assoc();

// Get detailed answers for this quiz
$answers_query = "
    SELECT qa.*, q.question_text, q.option_a, q.option_b, q.option_c, q.option_d, 
           q.correct_answer, q.explanation
    FROM quiz_answers qa
    JOIN questions q ON qa.question_id = q.id
    WHERE qa.quiz_id = $quiz_id
    ORDER BY qa.id
";

$answers = $conn->query($answers_query);

// Get correct and incorrect counts
$correct_count = 0;
$incorrect_count = 0;
$answers_data = [];

if ($answers && $answers->num_rows > 0) {
    while ($row = $answers->fetch_assoc()) {
        $answers_data[] = $row;
        if ($row['is_correct']) {
            $correct_count++;
        } else {
            $incorrect_count++;
        }
    }
}

// Calculate time taken in readable format
$minutes = floor($quiz['time_taken'] / 60);
$seconds = $quiz['time_taken'] % 60;
$time_display = $minutes . ' min ' . $seconds . ' sec';

// Get user's rank for this category
$rank_query = "
    SELECT COUNT(*) + 1 as rank
    FROM quizzes q2
    WHERE q2.category_id = {$quiz['category_id']}
    AND (q2.score / q2.total_questions * 100) > {$quiz['percentage']}
";
$rank_result = $conn->query($rank_query);
$rank = $rank_result->fetch_assoc()['rank'];

// Get total attempts for this category
$total_attempts_query = "
    SELECT COUNT(*) as total
    FROM quizzes
    WHERE category_id = {$quiz['category_id']}
";
$total_attempts = $conn->query($total_attempts_query)->fetch_assoc()['total'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quiz Result - <?php echo SITE_NAME; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&family=Rajdhani:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Rajdhani', sans-serif;
            background: #0a0f1e;
            color: #e0e0ff;
            min-height: 100vh;
            overflow-x: hidden;
        }

        /* Animated Background */
        .cyber-bg {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -2;
            background: linear-gradient(135deg, #0a0f1e 0%, #050812 100%);
        }

        .grid-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: 
                linear-gradient(rgba(0, 255, 157, 0.03) 1px, transparent 1px),
                linear-gradient(90deg, rgba(0, 255, 157, 0.03) 1px, transparent 1px);
            background-size: 50px 50px;
            z-index: -1;
            animation: gridMove 20s linear infinite;
        }

        @keyframes gridMove {
            0% { transform: translate(0, 0); }
            100% { transform: translate(50px, 50px); }
        }

        .floating-elements {
            position: fixed;
            width: 100%;
            height: 100%;
            z-index: -1;
        }

        .floating-element {
            position: absolute;
            color: rgba(0, 255, 157, 0.03);
            font-family: 'Orbitron', monospace;
            font-size: 1.2rem;
            white-space: nowrap;
            animation: float 20s linear infinite;
        }

        @keyframes float {
            from { transform: translateY(100vh) rotate(0deg); }
            to { transform: translateY(-100vh) rotate(360deg); }
        }

        /* Main Container */
        .result-wrapper {
            min-height: 100vh;
            padding: 40px 20px;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .result-container {
            max-width: 1200px;
            width: 100%;
        }

        /* Result Header */
        .result-header {
            background: rgba(10, 20, 40, 0.7);
            backdrop-filter: blur(20px);
            border: 2px solid #00ff9d;
            border-radius: 30px;
            padding: 40px;
            margin-bottom: 30px;
            box-shadow: 0 0 30px rgba(0, 255, 157, 0.2);
            position: relative;
            overflow: hidden;
            animation: glowPulse 3s infinite;
        }

        @keyframes glowPulse {
            0%, 100% { box-shadow: 0 0 30px rgba(0, 255, 157, 0.2); }
            50% { box-shadow: 0 0 50px rgba(0, 255, 157, 0.4); }
        }

        .result-header::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(0, 255, 157, 0.1) 0%, transparent 70%);
            animation: rotate 20s linear infinite;
        }

        @keyframes rotate {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        .back-button {
            margin-bottom: 20px;
            position: relative;
            z-index: 1;
        }

        .back-button a {
            color: #00ff9d;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 10px 20px;
            border: 1px solid #00ff9d;
            border-radius: 50px;
            transition: all 0.3s;
        }

        .back-button a:hover {
            background: #00ff9d;
            color: #050812;
            transform: translateX(-5px);
        }

        .result-badge {
            display: inline-block;
            padding: 8px 20px;
            background: rgba(0, 255, 157, 0.1);
            border: 1px solid #00ff9d;
            border-radius: 50px;
            color: #00ff9d;
            font-size: 0.9rem;
            margin-bottom: 20px;
        }

        .result-title {
            display: flex;
            align-items: center;
            gap: 20px;
            margin-bottom: 30px;
            flex-wrap: wrap;
        }

        .category-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #00ff9d, #0066ff);
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            color: #050812;
        }

        .title-text h1 {
            font-family: 'Orbitron', sans-serif;
            font-size: 2.5rem;
            color: #00ff9d;
            margin-bottom: 5px;
        }

        .title-text p {
            color: #8899cc;
            font-size: 1.1rem;
        }

        /* Score Circle */
        .score-circle-container {
            display: flex;
            justify-content: center;
            margin: 30px 0;
        }

        .score-circle {
            width: 200px;
            height: 200px;
            border-radius: 50%;
            background: conic-gradient(
                <?php 
                $color = $quiz['percentage'] >= 75 ? '#00ff9d' : ($quiz['percentage'] >= 50 ? '#ffaa00' : '#ff3366');
                echo $color . ' 0deg ' . ($quiz['percentage'] * 3.6) . 'deg, #2a2f3e ' . ($quiz['percentage'] * 3.6) . 'deg 360deg';
                ?>
            );
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            animation: circleFill 1.5s ease-out;
        }

        @keyframes circleFill {
            from { transform: rotate(-90deg); }
            to { transform: rotate(0); }
        }

        .score-inner {
            width: 170px;
            height: 170px;
            background: #0a0f1e;
            border-radius: 50%;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            border: 2px solid <?php echo $color; ?>;
        }

        .score-number {
            font-family: 'Orbitron', sans-serif;
            font-size: 3.5rem;
            font-weight: bold;
            color: <?php echo $color; ?>;
            line-height: 1;
        }

        .score-label {
            color: #8899cc;
            font-size: 0.9rem;
            text-transform: uppercase;
        }

        /* Status Badge */
        .status-badge {
            display: inline-block;
            padding: 12px 30px;
            border-radius: 50px;
            font-family: 'Orbitron', sans-serif;
            font-weight: bold;
            font-size: 1.2rem;
            margin: 20px 0;
        }

        .status-Excellent {
            background: rgba(0, 255, 157, 0.2);
            color: #00ff9d;
            border: 2px solid #00ff9d;
        }

        .status-Passed {
            background: rgba(0, 102, 255, 0.2);
            color: #0066ff;
            border: 2px solid #0066ff;
        }

        .status-Failed {
            background: rgba(255, 51, 102, 0.2);
            color: #ff3366;
            border: 2px solid #ff3366;
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin: 40px 0;
        }

        .stat-card {
            background: rgba(0, 0, 0, 0.3);
            border: 1px solid rgba(0, 255, 157, 0.2);
            border-radius: 15px;
            padding: 20px;
            text-align: center;
            transition: all 0.3s;
        }

        .stat-card:hover {
            border-color: #00ff9d;
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 255, 157, 0.2);
        }

        .stat-icon {
            font-size: 2rem;
            color: #00ff9d;
            margin-bottom: 10px;
        }

        .stat-value {
            font-family: 'Orbitron', sans-serif;
            font-size: 2rem;
            color: #00ff9d;
            line-height: 1;
        }

        .stat-label {
            color: #8899cc;
            font-size: 0.9rem;
        }

        /* Progress Bars */
        .progress-section {
            background: rgba(0, 0, 0, 0.3);
            border: 1px solid rgba(0, 255, 157, 0.2);
            border-radius: 15px;
            padding: 20px;
            margin: 30px 0;
        }

        .progress-item {
            margin: 15px 0;
        }

        .progress-header {
            display: flex;
            justify-content: space-between;
            color: #8899cc;
            margin-bottom: 5px;
        }

        .progress-bar-container {
            width: 100%;
            height: 10px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 5px;
            overflow: hidden;
        }

        .progress-bar-fill {
            height: 100%;
            background: linear-gradient(90deg, #00ff9d, #0066ff);
            border-radius: 5px;
            transition: width 1s ease;
            position: relative;
            overflow: hidden;
        }

        .progress-bar-fill::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
            animation: shimmer 2s infinite;
        }

        @keyframes shimmer {
            0% { transform: translateX(-100%); }
            100% { transform: translateX(100%); }
        }

        /* Answers Section */
        .answers-section {
            background: rgba(10, 20, 40, 0.7);
            backdrop-filter: blur(20px);
            border: 2px solid rgba(0, 255, 157, 0.3);
            border-radius: 30px;
            padding: 40px;
            margin-top: 30px;
        }

        .answers-section h2 {
            font-family: 'Orbitron', sans-serif;
            color: #00ff9d;
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .answer-card {
            background: rgba(0, 0, 0, 0.3);
            border: 1px solid rgba(0, 255, 157, 0.2);
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 20px;
            transition: all 0.3s;
        }

        .answer-card:hover {
            border-color: #00ff9d;
            transform: translateX(5px);
        }

        .answer-card.correct {
            border-left: 5px solid #00ff9d;
        }

        .answer-card.incorrect {
            border-left: 5px solid #ff3366;
        }

        .question-number {
            color: #00ff9d;
            font-size: 0.9rem;
            margin-bottom: 10px;
        }

        .question-text {
            color: #e0e0ff;
            font-size: 1.1rem;
            margin-bottom: 20px;
            font-weight: 500;
        }

        .options-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 10px;
            margin-bottom: 20px;
        }

        .option-item {
            padding: 12px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .option-prefix {
            width: 25px;
            height: 25px;
            border-radius: 5px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
        }

        .option-correct {
            background: rgba(0, 255, 157, 0.2);
            border: 1px solid #00ff9d;
        }

        .option-correct .option-prefix {
            background: #00ff9d;
            color: #050812;
        }

        .option-wrong {
            background: rgba(255, 51, 102, 0.2);
            border: 1px solid #ff3366;
        }

        .option-wrong .option-prefix {
            background: #ff3366;
            color: white;
        }

        .option-user-selected {
            position: relative;
        }

        .option-user-selected::after {
            content: '✓ Your Answer';
            position: absolute;
            top: -10px;
            right: 10px;
            background: #00ff9d;
            color: #050812;
            padding: 2px 10px;
            border-radius: 12px;
            font-size: 0.7rem;
            font-weight: bold;
        }

        .explanation {
            margin-top: 15px;
            padding: 15px;
            background: rgba(0, 0, 0, 0.2);
            border-radius: 8px;
            color: #8899cc;
            font-style: italic;
            border-left: 3px solid #00ff9d;
        }

        .explanation i {
            color: #00ff9d;
            margin-right: 10px;
        }

        /* Action Buttons */
        .action-buttons {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 40px;
            flex-wrap: wrap;
        }

        .btn {
            padding: 15px 40px;
            border: none;
            border-radius: 10px;
            font-family: 'Orbitron', sans-serif;
            font-size: 1.1rem;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
        }

        .btn-primary {
            background: linear-gradient(45deg, #00ff9d, #0066ff);
            color: #050812;
        }

        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 30px rgba(0, 255, 157, 0.4);
        }

        .btn-secondary {
            background: transparent;
            border: 2px solid #00ff9d;
            color: #00ff9d;
        }

        .btn-secondary:hover {
            background: #00ff9d;
            color: #050812;
            transform: translateY(-3px);
        }

        .btn-share {
            background: transparent;
            border: 2px solid #0066ff;
            color: #0066ff;
        }

        .btn-share:hover {
            background: #0066ff;
            color: white;
            transform: translateY(-3px);
        }

        /* Certificate */
        .certificate-badge {
            display: flex;
            align-items: center;
            gap: 15px;
            background: rgba(0, 255, 157, 0.1);
            border: 1px solid #00ff9d;
            border-radius: 15px;
            padding: 20px;
            margin-top: 30px;
        }

        .certificate-badge i {
            font-size: 3rem;
            color: #00ff9d;
        }

        .certificate-text h3 {
            color: #00ff9d;
            margin-bottom: 5px;
        }

        .certificate-text p {
            color: #8899cc;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .result-header {
                padding: 30px 20px;
            }

            .title-text h1 {
                font-size: 2rem;
            }

            .score-circle {
                width: 150px;
                height: 150px;
            }

            .score-inner {
                width: 130px;
                height: 130px;
            }

            .score-number {
                font-size: 2.5rem;
            }

            .answers-section {
                padding: 20px;
            }
        }

        /* Confetti Animation */
        .confetti {
            position: fixed;
            width: 10px;
            height: 10px;
            background-color: #00ff9d;
            position: absolute;
            left: 50%;
            animation: confetti 5s ease-in-out infinite;
            z-index: 9999;
        }

        @keyframes confetti {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="cyber-bg"></div>
    <div class="grid-overlay"></div>
    
    <!-- Floating Binary Elements -->
    <div class="floating-elements" id="floatingElements"></div>

    <div class="result-wrapper">
        <div class="result-container">
            <!-- Result Header -->
            <div class="result-header">
                <div class="back-button">
                    <a href="dashboard.php">
                        <i class="fas fa-arrow-left"></i> Back to Dashboard
                    </a>
                </div>

                <div class="result-badge">
                    <i class="fas fa-check-circle"></i> Quiz Completed on <?php echo date('F d, Y', strtotime($quiz['completed_at'])); ?>
                </div>

                <div class="result-title">
                    <div class="category-icon">
                        <i class="fas fa-<?php echo $quiz['category_icon'] ?? 'clipboard-list'; ?>"></i>
                    </div>
                    <div class="title-text">
                        <h1><?php echo htmlspecialchars($quiz['category_name']); ?></h1>
                        <p><i class="fas fa-user"></i> <?php echo htmlspecialchars($quiz['full_name']); ?></p>
                    </div>
                </div>

                <!-- Score Circle -->
                <div class="score-circle-container">
                    <div class="score-circle">
                        <div class="score-inner">
                            <span class="score-number"><?php echo round($quiz['percentage']); ?>%</span>
                            <span class="score-label">Score</span>
                        </div>
                    </div>
                </div>

                <!-- Status Badge -->
                <div style="text-align: center;">
                    <span class="status-badge status-<?php echo $quiz['status']; ?>">
                        <i class="fas fa-<?php echo $quiz['status'] == 'Failed' ? 'times-circle' : 'check-circle'; ?>"></i>
                        <?php echo $quiz['status']; ?>
                    </span>
                </div>

                <!-- Stats Grid -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon"><i class="fas fa-star"></i></div>
                        <div class="stat-value"><?php echo $quiz['score']; ?>/<?php echo $quiz['total_questions']; ?></div>
                        <div class="stat-label">Correct Answers</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon"><i class="fas fa-clock"></i></div>
                        <div class="stat-value"><?php echo $time_display; ?></div>
                        <div class="stat-label">Time Taken</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon"><i class="fas fa-trophy"></i></div>
                        <div class="stat-value">#<?php echo $rank; ?></div>
                        <div class="stat-label">Rank (out of <?php echo $total_attempts; ?>)</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon"><i class="fas fa-calendar"></i></div>
                        <div class="stat-value"><?php echo date('M d', strtotime($quiz['completed_at'])); ?></div>
                        <div class="stat-label">Date</div>
                    </div>
                </div>

                <!-- Progress Bars -->
                <div class="progress-section">
                    <div class="progress-item">
                        <div class="progress-header">
                            <span>Performance</span>
                            <span><?php echo round($quiz['percentage']); ?>%</span>
                        </div>
                        <div class="progress-bar-container">
                            <div class="progress-bar-fill" style="width: <?php echo $quiz['percentage']; ?>%"></div>
                        </div>
                    </div>
                    <div class="progress-item">
                        <div class="progress-header">
                            <span>Correct Answers</span>
                            <span><?php echo $correct_count; ?>/<?php echo $quiz['total_questions']; ?></span>
                        </div>
                        <div class="progress-bar-container">
                            <div class="progress-bar-fill" style="width: <?php echo ($correct_count / $quiz['total_questions']) * 100; ?>%"></div>
                        </div>
                    </div>
                    <div class="progress-item">
                        <div class="progress-header">
                            <span>Incorrect Answers</span>
                            <span><?php echo $incorrect_count; ?>/<?php echo $quiz['total_questions']; ?></span>
                        </div>
                        <div class="progress-bar-container">
                            <div class="progress-bar-fill" style="width: <?php echo ($incorrect_count / $quiz['total_questions']) * 100; ?>%; background: linear-gradient(90deg, #ff3366, #ff0066);"></div>
                        </div>
                    </div>
                </div>

                <!-- Certificate Badge for Passed Students -->
                <?php if ($quiz['percentage'] >= 75): ?>
                <div class="certificate-badge">
                    <i class="fas fa-certificate"></i>
                    <div class="certificate-text">
                        <h3>Congratulations! You Passed!</h3>
                        <p>You've demonstrated competency in <?php echo htmlspecialchars($quiz['category_name']); ?>. You can now proceed to the next COC.</p>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <!-- Detailed Answers Section -->
            <div class="answers-section">
                <h2><i class="fas fa-clipboard-check"></i> Detailed Answer Review</h2>
                
                <?php foreach ($answers_data as $index => $answer): ?>
                <div class="answer-card <?php echo $answer['is_correct'] ? 'correct' : 'incorrect'; ?>">
                    <div class="question-number">Question <?php echo $index + 1; ?></div>
                    <div class="question-text"><?php echo htmlspecialchars($answer['question_text']); ?></div>
                    
                    <div class="options-grid">
                        <?php 
                        $options = [
                            'A' => $answer['option_a'],
                            'B' => $answer['option_b'],
                            'C' => $answer['option_c'],
                            'D' => $answer['option_d']
                        ];
                        
                        foreach ($options as $letter => $text): 
                            $is_correct = $letter == $answer['correct_answer'];
                            $is_user = $letter == $answer['user_answer'];
                            $option_class = '';
                            
                            if ($is_correct && $is_user) {
                                $option_class = 'option-correct option-user-selected';
                            } elseif ($is_correct) {
                                $option_class = 'option-correct';
                            } elseif ($is_user) {
                                $option_class = 'option-wrong option-user-selected';
                            } elseif (!$is_correct && !$is_user) {
                                $option_class = '';
                            }
                        ?>
                        <div class="option-item <?php echo $option_class; ?>">
                            <span class="option-prefix"><?php echo $letter; ?></span>
                            <span><?php echo htmlspecialchars($text); ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <?php if (!empty($answer['explanation'])): ?>
                    <div class="explanation">
                        <i class="fas fa-info-circle"></i>
                        <?php echo htmlspecialchars($answer['explanation']); ?>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Action Buttons -->
            <div class="action-buttons">
                <a href="quiz.php?category=<?php echo $quiz['category_id']; ?>" class="btn btn-primary">
                    <i class="fas fa-redo-alt"></i> Take Again
                </a>
                <a href="game.php?coc=<?php echo $quiz['category_id']; ?>" class="btn btn-secondary">
                    <i class="fas fa-gamepad"></i> Play Game
                </a>
                <button class="btn btn-share" onclick="shareResult()">
                    <i class="fas fa-share-alt"></i> Share Result
                </button>
            </div>
        </div>
    </div>

    <script>
        // Floating binary elements
        function createFloatingElements() {
            const container = document.querySelector('.floating-elements');
            const binary = ['0', '1', '101', '010', 'CSS', 'NCII', 'TESDA', 'PASS', 'FAIL', 'SCORE', 'QUIZ', 'DONE'];
            
            for (let i = 0; i < 30; i++) {
                const element = document.createElement('div');
                element.className = 'floating-element';
                element.textContent = binary[Math.floor(Math.random() * binary.length)];
                element.style.left = Math.random() * 100 + '%';
                element.style.animationDuration = (Math.random() * 20 + 10) + 's';
                element.style.animationDelay = Math.random() * 10 + 's';
                container.appendChild(element);
            }
        }

        // Share result function
        function shareResult() {
            const text = `I scored <?php echo $quiz['score']; ?>/<?php echo $quiz['total_questions']; ?> (<?php echo round($quiz['percentage']); ?>%) on <?php echo htmlspecialchars($quiz['category_name']); ?> at CSS TESDA Reviewer!`;
            
            if (navigator.share) {
                navigator.share({
                    title: 'My Quiz Result',
                    text: text,
                    url: window.location.href
                }).catch(console.error);
            } else {
                // Fallback - copy to clipboard
                navigator.clipboard.writeText(text).then(() => {
                    alert('Result copied to clipboard!');
                });
            }
        }

        // Confetti effect for passed students
        <?php if ($quiz['percentage'] >= 75): ?>
        function createConfetti() {
            for (let i = 0; i < 50; i++) {
                setTimeout(() => {
                    const confetti = document.createElement('div');
                    confetti.className = 'confetti';
                    confetti.style.left = Math.random() * 100 + '%';
                    confetti.style.top = '-10px';
                    confetti.style.backgroundColor = ['#00ff9d', '#0066ff', '#ffaa00'][Math.floor(Math.random() * 3)];
                    confetti.style.width = Math.random() * 10 + 5 + 'px';
                    confetti.style.height = confetti.style.width;
                    confetti.style.animationDuration = Math.random() * 3 + 2 + 's';
                    document.body.appendChild(confetti);
                    
                    setTimeout(() => {
                        confetti.remove();
                    }, 5000);
                }, i * 100);
            }
        }
        
        // Create confetti on page load
        window.addEventListener('load', createConfetti);
        <?php endif; ?>

        // Initialize floating elements
        createFloatingElements();

        // Animate progress bars on load
        window.addEventListener('load', function() {
            const bars = document.querySelectorAll('.progress-bar-fill');
            bars.forEach(bar => {
                const width = bar.style.width;
                bar.style.width = '0';
                setTimeout(() => {
                    bar.style.width = width;
                }, 100);
            });
        });
    </script>
     <?php $global_brightness = getGlobalBrightness(); include __DIR__ . '/includes/brightness_widget.php'; ?>
     <?php include __DIR__ . '/includes/theme_toggle.php'; ?>
</body>
</html>
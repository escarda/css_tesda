<?php
// view_result_details.php - View detailed results for any activity (Quiz, Game, Simulation)
require_once __DIR__ . '/../config.php';
loadTheme();
?>
<?php
// Check if admin is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$type = isset($_GET['type']) ? $_GET['type'] : 'quiz';

// ============================================
// GET DETAILS BASED ON TYPE
// ============================================

$details = null;
$answers = [];

// 1. QUIZ DETAILS
if ($type == 'quiz') {
    $sql = "SELECT 
                q.*,
                u.username,
                u.full_name,
                u.email,
                c.name as category_name,
                c.icon as category_icon,
                ROUND((q.score / q.total_questions) * 100, 1) as percentage,
                CASE 
                    WHEN (q.score / q.total_questions * 100) >= 90 THEN 'Excellent'
                    WHEN (q.score / q.total_questions * 100) >= 75 THEN 'Passed'
                    ELSE 'Failed'
                END as status
            FROM quizzes q
            JOIN users u ON q.user_id = u.id
            JOIN categories c ON q.category_id = c.id
            WHERE q.id = $id";
    
    $result = $conn->query($sql);
    if ($result && $result->num_rows > 0) {
        $details = $result->fetch_assoc();
        $details['activity_name'] = 'Quiz';
        $details['icon'] = $details['category_icon'] ?? 'clipboard-list';
        $details['score_display'] = $details['score'] . '/' . $details['total_questions'];
        $details['max_score'] = $details['total_questions'];
        
        // Get answers for quiz
        $answers_sql = "SELECT 
                            qa.*,
                            q.question_text,
                            q.option_a,
                            q.option_b,
                            q.option_c,
                            q.option_d,
                            q.correct_answer,
                            q.explanation
                        FROM quiz_answers qa
                        JOIN questions q ON qa.question_id = q.id
                        WHERE qa.quiz_id = $id
                        ORDER BY qa.id";
        $answers_result = $conn->query($answers_sql);
        if ($answers_result && $answers_result->num_rows > 0) {
            while ($row = $answers_result->fetch_assoc()) {
                $answers[] = $row;
            }
        }
    }
}

// 2. COC 1 GAME DETAILS (Insane Mode PC Assembly)
elseif ($type == 'coc1_game') {
    $sql = "SELECT 
                gs.id,
                gs.user_id,
                gs.coc_number,
                gs.high_score as score,
                gs.games_played,
                gs.played_at as completed_at,
                gs.created_at,
                u.username,
                u.full_name,
                u.email,
                'COC 1 - PC Assembly Game' as activity_name,
                'COC 1' as category_name,
                '🎮' as icon,
                LEAST(gs.high_score, 100) as percentage,
                gs.high_score as score_display,
                100 as max_score,
                CASE 
                    WHEN gs.high_score >= 800 THEN 'Excellent'
                    WHEN gs.high_score >= 500 THEN 'Good'
                    WHEN gs.high_score >= 300 THEN 'Passed'
                    ELSE 'Failed'
                END as status
            FROM user_game_scores gs
            JOIN users u ON gs.user_id = u.id
            WHERE gs.id = $id AND gs.coc_number = 1";
    
    $result = $conn->query($sql);
    if ($result && $result->num_rows > 0) {
        $details = $result->fetch_assoc();
        $details['score_display'] = $details['score'] . ' points';
    }
}

// 3. COC 2 GAME DETAILS (RJ45 Cable Crimping)
elseif ($type == 'coc2_game') {
    $sql = "SELECT 
                g.id,
                g.user_id,
                g.coc_number,
                g.score,
                g.played_at as completed_at,
                u.username,
                u.full_name,
                u.email,
                'COC 2 - RJ45 Cable Crimping' as activity_name,
                'COC 2' as category_name,
                '🔌' as icon,
                LEAST(g.score, 100) as percentage,
                g.score as score_display,
                100 as max_score,
                CASE 
                    WHEN g.score >= 900 THEN 'Excellent'
                    WHEN g.score >= 700 THEN 'Good'
                    WHEN g.score >= 500 THEN 'Passed'
                    ELSE 'Failed'
                END as status
            FROM game_scores g
            JOIN users u ON g.user_id = u.id
            WHERE g.id = $id AND g.coc_number = 2";
    
    $result = $conn->query($sql);
    if ($result && $result->num_rows > 0) {
        $details = $result->fetch_assoc();
        $details['score_display'] = $details['score'] . ' points';
    }
}

// 4. COC 3 GAME DETAILS (Server Admin)
elseif ($type == 'coc3_game') {
    $sql = "SELECT 
                g.id,
                g.user_id,
                g.coc_number,
                g.score,
                g.played_at as completed_at,
                u.username,
                u.full_name,
                u.email,
                'COC 3 - Server Admin Games' as activity_name,
                'COC 3' as category_name,
                '🎲' as icon,
                LEAST(g.score, 100) as percentage,
                g.score as score_display,
                100 as max_score,
                CASE 
                    WHEN g.score >= 900 THEN 'Excellent'
                    WHEN g.score >= 700 THEN 'Good'
                    WHEN g.score >= 500 THEN 'Passed'
                    ELSE 'Failed'
                END as status
            FROM game_scores g
            JOIN users u ON g.user_id = u.id
            WHERE g.id = $id AND g.coc_number = 3";
    
    $result = $conn->query($sql);
    if ($result && $result->num_rows > 0) {
        $details = $result->fetch_assoc();
        $details['score_display'] = $details['score'] . ' points';
    }
}

// 5. COC 4 GAME DETAILS (Stress Test PC Building)
elseif ($type == 'coc4_game') {
    $sql = "SELECT 
                gs.id,
                gs.user_id,
                gs.coc_number,
                gs.high_score as score,
                gs.games_played,
                gs.played_at as completed_at,
                gs.created_at,
                u.username,
                u.full_name,
                u.email,
                'COC 4 - Stress Test PC Building' as activity_name,
                'COC 4' as category_name,
                '💀' as icon,
                LEAST(gs.high_score, 100) as percentage,
                gs.high_score as score_display,
                100 as max_score,
                CASE 
                    WHEN gs.high_score >= 800 THEN 'Excellent'
                    WHEN gs.high_score >= 500 THEN 'Good'
                    WHEN gs.high_score >= 300 THEN 'Passed'
                    ELSE 'Failed'
                END as status
            FROM user_game_scores gs
            JOIN users u ON gs.user_id = u.id
            WHERE gs.id = $id AND gs.coc_number = 4";
    
    $result = $conn->query($sql);
    if ($result && $result->num_rows > 0) {
        $details = $result->fetch_assoc();
        $details['score_display'] = $details['score'] . ' points';
    }
}

// 6. INSTALLATION SIMULATION DETAILS
elseif ($type == 'installation_sim') {
    $sql = "SELECT 
                gs.id,
                gs.user_id,
                gs.coc_number,
                gs.high_score as score,
                gs.games_played,
                gs.played_at as completed_at,
                u.username,
                u.full_name,
                u.email,
                'PC Assembly Simulator' as activity_name,
                'Installation Simulation' as category_name,
                '🔧' as icon,
                LEAST(gs.high_score, 100) as percentage,
                gs.high_score as score_display,
                100 as max_score,
                CASE 
                    WHEN gs.high_score >= 800 THEN 'Excellent'
                    WHEN gs.high_score >= 500 THEN 'Good'
                    WHEN gs.high_score >= 300 THEN 'Passed'
                    ELSE 'Failed'
                END as status
            FROM user_game_scores gs
            JOIN users u ON gs.user_id = u.id
            WHERE gs.id = $id AND gs.coc_number = 1";
    
    $result = $conn->query($sql);
    if ($result && $result->num_rows > 0) {
        $details = $result->fetch_assoc();
        $details['score_display'] = $details['score'] . ' points';
    }
}

// 7. OS INSTALLATION SIMULATION DETAILS
elseif ($type == 'os_install_sim') {
    $sql = "SELECT 
                os.id,
                os.user_id,
                os.score,
                os.steps_completed,
                os.completed_at,
                u.username,
                u.full_name,
                u.email,
                'OS Installation Simulator' as activity_name,
                'OS Installation' as category_name,
                '💿' as icon,
                LEAST(os.score, 100) as percentage,
                os.score as score_display,
                100 as max_score,
                CASE 
                    WHEN os.score >= 140 THEN 'Excellent'
                    WHEN os.score >= 100 THEN 'Good'
                    WHEN os.score >= 70 THEN 'Passed'
                    ELSE 'Failed'
                END as status
            FROM os_installation_scores os
            JOIN users u ON os.user_id = u.id
            WHERE os.id = $id";
    
    $result = $conn->query($sql);
    if ($result && $result->num_rows > 0) {
        $details = $result->fetch_assoc();
        $details['score_display'] = $details['score'] . ' points';
    }
}

// 8. TROUBLESHOOTING SIMULATION DETAILS
elseif ($type == 'troubleshooting_sim') {
    $sql = "SELECT 
                ts.id,
                ts.user_id,
                ts.score,
                ts.problems_fixed,
                ts.attempts,
                ts.played_at as completed_at,
                u.username,
                u.full_name,
                u.email,
                'Troubleshooting Simulator' as activity_name,
                'Troubleshooting' as category_name,
                '🛠️' as icon,
                LEAST(ts.score, 100) as percentage,
                ts.score as score_display,
                100 as max_score,
                CASE 
                    WHEN ts.score >= 1000 THEN 'Excellent'
                    WHEN ts.score >= 700 THEN 'Good'
                    WHEN ts.score >= 400 THEN 'Passed'
                    ELSE 'Failed'
                END as status
            FROM troubleshooting_scores ts
            JOIN users u ON ts.user_id = u.id
            WHERE ts.id = $id";
    
    $result = $conn->query($sql);
    if ($result && $result->num_rows > 0) {
        $details = $result->fetch_assoc();
        $details['score_display'] = $details['score'] . ' points';
    }
}

// If no details found, redirect
if (!$details) {
    header("Location: view_results.php");
    exit();
}

// Format time if exists
$minutes = 0;
$seconds = 0;
if (isset($details['time_taken'])) {
    $minutes = floor($details['time_taken'] / 60);
    $seconds = $details['time_taken'] % 60;
}

// Get rank for this activity
$rank = 1;
if ($type == 'quiz') {
    $rank_query = "SELECT COUNT(*) + 1 as rank FROM quizzes WHERE category_id = {$details['category_id']} AND (score / total_questions * 100) > {$details['percentage']}";
    $rank_result = $conn->query($rank_query);
    if ($rank_result) $rank = $rank_result->fetch_assoc()['rank'];
}

// Compute score circle color safely
$percentage = isset($details['percentage']) ? floatval($details['percentage']) : 0;
$color = $percentage >= 75 ? '#00ff9d' : ($percentage >= 50 ? '#ffaa00' : '#ff3366');
$deg = $percentage * 3.6;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Result Details | <?php echo htmlspecialchars($details['activity_name']); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&family=Rajdhani:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Rajdhani', sans-serif;
            background: #0a0f1e;
            color: #e0e0ff;
            min-height: 100vh;
        }
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
            background-image: linear-gradient(rgba(0, 255, 157, 0.03) 1px, transparent 1px), linear-gradient(90deg, rgba(0, 255, 157, 0.03) 1px, transparent 1px);
            background-size: 50px 50px;
            z-index: -1;
            animation: gridMove 20s linear infinite;
        }
        @keyframes gridMove {
            0% { transform: translate(0, 0); }
            100% { transform: translate(50px, 50px); }
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }

        .top-nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .back-button a, .dashboard-button a {
            color: #00ff9d;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            border: 1px solid #00ff9d;
            border-radius: 40px;
            transition: all 0.3s;
        }
        .back-button a:hover, .dashboard-button a:hover {
            background: #00ff9d;
            color: #050812;
        }
        .dashboard-button a {
            border-color: #0066ff;
            color: #0066ff;
        }
        .dashboard-button a:hover {
            background: #0066ff;
            color: #050812;
        }

        .result-card {
            background: rgba(10, 20, 40, 0.7);
            backdrop-filter: blur(20px);
            border: 2px solid #00ff9d;
            border-radius: 30px;
            padding: 2rem;
            margin-bottom: 2rem;
        }

        .result-header {
            display: flex;
            align-items: center;
            gap: 20px;
            margin-bottom: 2rem;
            flex-wrap: wrap;
        }
        .result-icon {
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
        .result-title h1 {
            font-family: 'Orbitron', sans-serif;
            font-size: 1.8rem;
            color: #00ff9d;
        }
        .result-title p {
            color: #8899cc;
        }

        .score-circle {
            width: 180px;
            height: 180px;
            margin: 0 auto 1.5rem;
            border-radius: 50%;
            background: conic-gradient(
                <?php echo $color . ' 0deg ' . $deg . 'deg, #2a2f3e ' . $deg . 'deg 360deg'; ?>
            );
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .score-inner {
            width: 150px;
            height: 150px;
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
            font-size: 2.5rem;
            font-weight: bold;
            color: <?php echo $color; ?>;
        }
        .score-label {
            font-size: 0.8rem;
            color: #8899cc;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 1rem;
            margin: 1.5rem 0;
        }
        .stat-box {
            background: rgba(0, 0, 0, 0.3);
            border: 1px solid rgba(0, 255, 157, 0.2);
            border-radius: 15px;
            padding: 1rem;
            text-align: center;
        }
        .stat-value {
            font-family: 'Orbitron', sans-serif;
            font-size: 1.5rem;
            color: #00ff9d;
        }
        .stat-label {
            font-size: 0.7rem;
            color: #8899cc;
        }

        .status-badge {
            display: inline-block;
            padding: 8px 24px;
            border-radius: 40px;
            font-family: 'Orbitron', sans-serif;
            font-weight: bold;
            margin: 1rem 0;
        }
        .status-Excellent { background: rgba(0, 255, 157, 0.2); border: 1px solid #00ff9d; color: #00ff9d; }
        .status-Good { background: rgba(0, 102, 255, 0.2); border: 1px solid #0066ff; color: #0066ff; }
        .status-Passed { background: rgba(0, 102, 255, 0.2); border: 1px solid #0066ff; color: #0066ff; }
        .status-Failed { background: rgba(239, 68, 68, 0.2); border: 1px solid #ef4444; color: #ef4444; }

        .answers-section {
            background: rgba(10, 20, 40, 0.7);
            backdrop-filter: blur(20px);
            border: 2px solid rgba(0, 255, 157, 0.3);
            border-radius: 30px;
            padding: 2rem;
            margin-top: 2rem;
        }
        .answers-section h2 {
            font-family: 'Orbitron', sans-serif;
            color: #00ff9d;
            margin-bottom: 1.5rem;
        }
        .answer-card {
            background: rgba(0, 0, 0, 0.3);
            border: 1px solid rgba(0, 255, 157, 0.2);
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 1rem;
        }
        .answer-card.correct { border-left: 4px solid #00ff9d; }
        .answer-card.incorrect { border-left: 4px solid #ef4444; }
        .question-text { font-size: 1rem; margin-bottom: 1rem; }
        .user-answer { margin-top: 0.5rem; padding: 0.5rem; background: rgba(0,0,0,0.2); border-radius: 8px; }
        .correct-answer { color: #00ff9d; }
        .wrong-answer { color: #ef4444; }
        .explanation { margin-top: 1rem; padding: 0.5rem; background: rgba(0,0,0,0.2); border-radius: 8px; font-style: italic; color: #8899cc; }

        .btn {
            padding: 10px 24px;
            border-radius: 40px;
            font-family: 'Orbitron', sans-serif;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            border: none;
        }
        .btn-primary {
            background: linear-gradient(45deg, #00ff9d, #0066ff);
            color: #050812;
        }
        .btn-secondary {
            background: transparent;
            border: 2px solid #00ff9d;
            color: #00ff9d;
        }
        .btn-dashboard {
            background: transparent;
            border: 2px solid #0066ff;
            color: #0066ff;
        }

        @media (max-width: 768px) {
            .container { padding: 1rem; }
            .result-header { flex-direction: column; text-align: center; }
            .result-icon { margin: 0 auto; }
        }
    </style>
</head>
<body>
    <div class="cyber-bg"></div>
    <div class="grid-overlay"></div>

    <div class="container">
        <!-- Top Navigation: Back to Results (left) + Dashboard (right) -->
        <div class="top-nav">
            <div class="back-button">
                <a href="view_results.php">
                    <i class="fas fa-arrow-left"></i> Back to Results
                </a>
            </div>
            <div class="dashboard-button">
                <a href="/proj/dashboard.php">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a>
            </div>
        </div>

        <!-- Result Card -->
        <div class="result-card">
            <div class="result-header">
                <div class="result-icon">
                    <i class="fas fa-<?php echo $details['icon'] ?? 'gamepad'; ?>"></i>
                </div>
                <div class="result-title">
                    <h1><?php echo htmlspecialchars($details['activity_name']); ?></h1>
                    <p><i class="fas fa-user"></i> <?php echo htmlspecialchars($details['full_name']); ?> (<?php echo htmlspecialchars($details['username']); ?>)</p>
                    <p><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($details['email']); ?></p>
                </div>
            </div>

            <div style="text-align: center;">
                <div class="score-circle">
                    <div class="score-inner">
                        <span class="score-number"><?php echo round($percentage); ?>%</span>
                        <span class="score-label">Score</span>
                    </div>
                </div>
                <div>
                    <span class="status-badge status-<?php echo str_replace(' ', '', $details['status']); ?>">
                        <i class="fas fa-<?php echo $details['status'] == 'Failed' ? 'times-circle' : 'check-circle'; ?>"></i>
                        <?php echo $details['status']; ?>
                    </span>
                </div>
            </div>

            <!-- Statistics -->
            <div class="stats-grid">
                <div class="stat-box">
                    <div class="stat-value"><?php echo $details['score_display']; ?></div>
                    <div class="stat-label">Total Score</div>
                </div>
                <div class="stat-box">
                    <div class="stat-value"><?php echo date('M d, Y', strtotime($details['completed_at'])); ?></div>
                    <div class="stat-label">Date Completed</div>
                </div>
                <?php if (isset($details['games_played'])): ?>
                <div class="stat-box">
                    <div class="stat-value"><?php echo $details['games_played']; ?></div>
                    <div class="stat-label">Games Played</div>
                </div>
                <?php endif; ?>
                <?php if (isset($details['steps_completed'])): ?>
                <div class="stat-box">
                    <div class="stat-value"><?php echo $details['steps_completed']; ?>/15</div>
                    <div class="stat-label">Steps Completed</div>
                </div>
                <?php endif; ?>
                <?php if (isset($details['problems_fixed'])): ?>
                <div class="stat-box">
                    <div class="stat-value"><?php echo $details['problems_fixed']; ?>/12</div>
                    <div class="stat-label">Problems Fixed</div>
                </div>
                <?php endif; ?>
                <?php if (isset($details['attempts'])): ?>
                <div class="stat-box">
                    <div class="stat-value"><?php echo $details['attempts']; ?></div>
                    <div class="stat-label">Total Attempts</div>
                </div>
                <?php endif; ?>
                <div class="stat-box">
                    <div class="stat-value">#<?php echo $rank; ?></div>
                    <div class="stat-label">Global Rank</div>
                </div>
            </div>
        </div>

        <!-- Detailed Answers (Only for Quizzes) -->
        <?php if ($type == 'quiz' && count($answers) > 0): ?>
        <div class="answers-section">
            <h2><i class="fas fa-clipboard-list"></i> Detailed Answer Review</h2>
            <?php foreach ($answers as $index => $answer): ?>
            <div class="answer-card <?php echo $answer['is_correct'] ? 'correct' : 'incorrect'; ?>">
                <div class="question-number" style="color: #00ff9d; margin-bottom: 0.5rem;">Question <?php echo $index + 1; ?></div>
                <div class="question-text"><?php echo htmlspecialchars($answer['question_text']); ?></div>
                <div class="user-answer">
                    <strong>Your Answer:</strong> 
                    <span class="<?php echo $answer['is_correct'] ? 'correct-answer' : 'wrong-answer'; ?>">
                        <?php echo htmlspecialchars($answer['user_answer']); ?>
                    </span>
                </div>
                <div class="correct-answer">
                    <strong>Correct Answer:</strong> <?php echo htmlspecialchars($answer['correct_answer']); ?>
                </div>
                <?php if (!empty($answer['explanation'])): ?>
                <div class="explanation">
                    <i class="fas fa-info-circle"></i> <?php echo htmlspecialchars($answer['explanation']); ?>
                </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- For Games/Simulations - Show Performance Metrics -->
        <?php if ($type != 'quiz'): ?>
        <div class="answers-section">
            <h2><i class="fas fa-chart-line"></i> Performance Summary</h2>
            <div class="answer-card">
                <p><strong>Activity Type:</strong> <?php echo ucfirst(str_replace('_', ' ', $type)); ?></p>
                <p><strong>Category:</strong> <?php echo htmlspecialchars($details['category_name']); ?></p>
                <p><strong>Final Score:</strong> <?php echo $details['score_display']; ?></p>
                <p><strong>Percentage:</strong> <?php echo round($percentage); ?>%</p>
                <p><strong>Status:</strong> <?php echo $details['status']; ?></p>
                <p><strong>Completed:</strong> <?php echo date('F d, Y - h:i A', strtotime($details['completed_at'])); ?></p>
                <?php if (isset($details['games_played'])): ?>
                <p><strong>Total Games Played:</strong> <?php echo $details['games_played']; ?></p>
                <?php endif; ?>
                <?php if (isset($details['steps_completed'])): ?>
                <p><strong>Steps Completed:</strong> <?php echo $details['steps_completed']; ?> out of 15</p>
                <?php endif; ?>
                <?php if (isset($details['problems_fixed'])): ?>
                <p><strong>Problems Fixed:</strong> <?php echo $details['problems_fixed']; ?> out of 12</p>
                <?php endif; ?>
                <?php if (isset($details['attempts'])): ?>
                <p><strong>Total Attempts:</strong> <?php echo $details['attempts']; ?></p>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Action Buttons -->
        <div style="display: flex; gap: 1rem; justify-content: center; margin-top: 2rem; flex-wrap: wrap;">
            <a href="view_results.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Results
            </a>
            <a href="/proj/dashboard.php" class="btn btn-dashboard">
                <i class="fas fa-tachometer-alt"></i> Dashboard
            </a>
            <button onclick="window.print()" class="btn btn-primary">
                <i class="fas fa-print"></i> Print Report
            </button>
        </div>
    </div>
</body>
</html>
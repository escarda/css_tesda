<?php
require_once 'config.php';

if (!isAdmin()) {
    redirect('../login.php');
}

$user_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Get user details
$user_query = $conn->query("SELECT * FROM users WHERE id = $user_id");
if ($user_query->num_rows == 0) {
    redirect('users.php');
}
$user = $user_query->fetch_assoc();

// Get user's quiz results
$quizzes = $conn->query("
    SELECT q.*, c.name as category_name,
           (q.score / q.total_questions * 100) as percentage
    FROM quizzes q
    JOIN categories c ON q.category_id = c.id
    WHERE q.user_id = $user_id
    ORDER BY q.completed_at DESC
");

// Get user's game scores
$games = $conn->query("
    SELECT g.*, c.name as category_name
    FROM game_scores g
    JOIN categories c ON g.category_id = c.id
    WHERE g.user_id = $user_id
    ORDER BY g.played_at DESC
");

// Calculate statistics
$avg_score = $conn->query("
    SELECT AVG(score/total_questions*100) as avg 
    FROM quizzes WHERE user_id = $user_id
")->fetch_assoc()['avg'];

$total_quizzes = $quizzes->num_rows;
$total_games = $games->num_rows;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Details - Admin Panel</title>
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
            background-image: 
                linear-gradient(rgba(0,255,157,0.03) 1px, transparent 1px),
                linear-gradient(90deg, rgba(0,255,157,0.03) 1px, transparent 1px);
            background-size: 50px 50px;
            z-index: -1;
            animation: gridMove 20s linear infinite;
        }

        @keyframes gridMove {
            0% { transform: translate(0,0); }
            100% { transform: translate(50px,50px); }
        }

        .dashboard {
            display: grid;
            grid-template-columns: 280px 1fr;
            min-height: 100vh;
        }

        .sidebar {
            background: rgba(5,8,18,0.95);
            backdrop-filter: blur(10px);
            border-right: 2px solid #00ff9d;
            padding: 30px 0;
        }

        .sidebar-header {
            text-align: center;
            padding: 0 20px 30px;
            border-bottom: 1px solid rgba(0,255,157,0.2);
        }

        .sidebar-header i {
            font-size: 3rem;
            color: #00ff9d;
        }

        .sidebar-header h2 {
            font-family: 'Orbitron', sans-serif;
            color: #00ff9d;
        }

        .sidebar-nav {
            padding: 20px;
        }

        .sidebar-nav ul {
            list-style: none;
        }

        .sidebar-nav a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 15px;
            color: #8899cc;
            text-decoration: none;
        }

        .sidebar-nav a:hover,
        .sidebar-nav a.active {
            background: rgba(0,255,157,0.1);
            color: #00ff9d;
        }

        .main-content {
            padding: 40px;
            overflow-y: auto;
            max-height: 100vh;
        }

        .user-header {
            background: rgba(10,20,40,0.7);
            border: 2px solid #00ff9d;
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            gap: 30px;
            flex-wrap: wrap;
        }

        .user-avatar {
            width: 100px;
            height: 100px;
            background: linear-gradient(135deg, #00ff9d, #0066ff);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            color: #050812;
        }

        .user-info h1 {
            font-family: 'Orbitron', sans-serif;
            color: #00ff9d;
            margin-bottom: 10px;
        }

        .user-meta {
            display: flex;
            gap: 30px;
            color: #8899cc;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: rgba(10,20,40,0.7);
            border: 1px solid rgba(0,255,157,0.3);
            border-radius: 15px;
            padding: 20px;
        }

        .stat-value {
            font-size: 2rem;
            color: #00ff9d;
            font-family: 'Orbitron', sans-serif;
        }

        .stat-label {
            color: #8899cc;
        }

        .section {
            background: rgba(10,20,40,0.7);
            border: 2px solid rgba(0,255,157,0.3);
            border-radius: 20px;
            padding: 20px;
            margin-bottom: 30px;
        }

        .section h2 {
            color: #00ff9d;
            margin-bottom: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            text-align: left;
            padding: 15px;
            color: #00ff9d;
            border-bottom: 2px solid rgba(0,255,157,0.3);
        }

        td {
            padding: 15px;
            border-bottom: 1px solid rgba(0,255,157,0.1);
            color: #8899cc;
        }

        .btn {
            padding: 10px 20px;
            border: 1px solid #00ff9d;
            background: transparent;
            color: #00ff9d;
            border-radius: 8px;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn:hover {
            background: #00ff9d;
            color: #050812;
        }
    </style>
</head>
<body>
    <div class="cyber-bg"></div>
    <div class="grid-overlay"></div>

    <div class="dashboard">
        <div class="sidebar">
            <div class="sidebar-header">
                <i class="fas fa-crown"></i>
                <h2>ADMIN PANEL</h2>
            </div>
            <nav class="sidebar-nav">
                <ul>
                    <li><a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                    <li><a href="users.php" class="active"><i class="fas fa-users"></i> Users</a></li>
                    <li><a href="manage_questions.php"><i class="fas fa-question-circle"></i> Questions</a></li>
                    <li><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                </ul>
            </nav>
        </div>

        <main class="main-content">
            <div class="user-header">
                <div class="user-avatar">
                    <?php echo strtoupper(substr($user['full_name'], 0, 1)); ?>
                </div>
                <div class="user-info">
                    <h1><?php echo $user['full_name']; ?></h1>
                    <div class="user-meta">
                        <span><i class="fas fa-user"></i> @<?php echo $user['username']; ?></span>
                        <span><i class="fas fa-envelope"></i> <?php echo $user['email']; ?></span>
                        <span><i class="fas fa-calendar"></i> Joined <?php echo date('M d, Y', strtotime($user['created_at'])); ?></span>
                    </div>
                </div>
                <a href="send_message.php?user=<?php echo $user_id; ?>" class="btn">
                    <i class="fas fa-envelope"></i> Send Message
                </a>
            </div>

            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-value"><?php echo $total_quizzes; ?></div>
                    <div class="stat-label">Quizzes Taken</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?php echo round($avg_score ?? 0, 1); ?>%</div>
                    <div class="stat-label">Average Score</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?php echo $total_games; ?></div>
                    <div class="stat-label">Games Played</div>
                </div>
            </div>

            <div class="section">
                <h2><i class="fas fa-clipboard-list"></i> Quiz History</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Category</th>
                            <th>Score</th>
                            <th>Percentage</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($quiz = $quizzes->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $quiz['category_name']; ?></td>
                            <td><?php echo $quiz['score']; ?>/<?php echo $quiz['total_questions']; ?></td>
                            <td><?php echo round($quiz['percentage'], 1); ?>%</td>
                            <td><?php echo date('M d, Y H:i', strtotime($quiz['completed_at'])); ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>

            <div class="section">
                <h2><i class="fas fa-gamepad"></i> Game History</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Category</th>
                            <th>Score</th>
                            <th>Level</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($game = $games->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $game['category_name']; ?></td>
                            <td><?php echo $game['score']; ?></td>
                            <td><?php echo $game['level_completed']; ?></td>
                            <td><?php echo date('M d, Y H:i', strtotime($game['played_at'])); ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
     <?php $global_brightness = getGlobalBrightness(); include __DIR__ . '/includes/brightness_widget.php'; ?>
     <?php include __DIR__ . '/includes/theme_toggle.php'; ?>
</body>
</html>
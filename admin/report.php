<?php
// report.php - Admin Reports Dashboard (No is_admin column)

require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Simple admin check - add user IDs that should be admin here
$admin_users = [1]; // Add your user ID here (change 1 to your actual user ID)
$is_admin = in_array($user_id, $admin_users);

if (!$is_admin) {
    header("Location: dashboard.php");
    exit();
}

// Get date filter
$date_filter = isset($_GET['date_filter']) ? $_GET['date_filter'] : 'all';
$date_condition = "";
if ($date_filter == 'today') {
    $date_condition = "AND DATE(completed_at) = CURDATE()";
} elseif ($date_filter == 'week') {
    $date_condition = "AND completed_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
} elseif ($date_filter == 'month') {
    $date_condition = "AND completed_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
}

// Get COC filter
$coc_filter = isset($_GET['coc_filter']) ? (int)$_GET['coc_filter'] : 0;
$coc_condition = $coc_filter > 0 ? "AND q.category_id = $coc_filter" : "";

// Get user filter
$user_filter = isset($_GET['user_filter']) ? (int)$_GET['user_filter'] : 0;
$user_condition = $user_filter > 0 ? "AND q.user_id = $user_filter" : "";

// ============ STATISTICS ============

// Total users
$total_users = $conn->query("SELECT COUNT(*) as count FROM users")->fetch_assoc()['count'];

// Total quizzes taken
$total_quizzes = $conn->query("SELECT COUNT(*) as count FROM quizzes $date_condition")->fetch_assoc()['count'];

// Average score
$avg_score = $conn->query("SELECT AVG(score/total_questions*100) as avg FROM quizzes $date_condition")->fetch_assoc()['avg'];
$avg_score = round($avg_score ?? 0, 1);

// Pass rate (score >= 75%)
$pass_count = $conn->query("SELECT COUNT(*) as count FROM quizzes WHERE score/total_questions*100 >= 75 $date_condition")->fetch_assoc()['count'];
$pass_rate = $total_quizzes > 0 ? round(($pass_count / $total_quizzes) * 100, 1) : 0;

// Total games played
$total_games = $conn->query("SELECT COUNT(*) as count FROM game_scores $date_condition")->fetch_assoc()['count'];

// Top performers
$top_performers = $conn->query("
    SELECT u.full_name, u.username, AVG(q.score/q.total_questions*100) as avg_score, COUNT(q.id) as quizzes_taken
    FROM users u
    JOIN quizzes q ON u.id = q.user_id
    GROUP BY u.id
    ORDER BY avg_score DESC
    LIMIT 5
");

// Recent quizzes
$recent_quizzes = $conn->query("
    SELECT q.*, u.full_name, u.username, c.name as category_name
    FROM quizzes q
    JOIN users u ON q.user_id = u.id
    JOIN categories c ON q.category_id = c.id
    WHERE 1=1 $date_condition $coc_condition $user_condition
    ORDER BY q.completed_at DESC
    LIMIT 50
");

// Quiz results by COC
$coc_results = $conn->query("
    SELECT 
        c.id as coc_id,
        c.name as coc_name,
        COUNT(q.id) as total_takes,
        AVG(q.score/q.total_questions*100) as avg_score,
        SUM(CASE WHEN q.score/q.total_questions*100 >= 75 THEN 1 ELSE 0 END) as passed,
        SUM(CASE WHEN q.score/q.total_questions*100 < 75 THEN 1 ELSE 0 END) as failed
    FROM categories c
    LEFT JOIN quizzes q ON c.id = q.category_id
    GROUP BY c.id
    ORDER BY c.id
");

// Game scores by type
$game_stats = $conn->query("
    SELECT 
        game_type,
        COUNT(*) as plays,
        AVG(score) as avg_score,
        MAX(score) as high_score
    FROM game_scores
    GROUP BY game_type
");

// Daily activity (last 7 days)
$daily_activity = $conn->query("
    SELECT 
        DATE(completed_at) as date,
        COUNT(*) as quizzes_taken,
        AVG(score/total_questions*100) as avg_score
    FROM quizzes
    WHERE completed_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    GROUP BY DATE(completed_at)
    ORDER BY date DESC
");

// All users for filter
$all_users = $conn->query("SELECT id, full_name, username FROM users ORDER BY full_name");

$page_title = "Admin Reports | CSS TESDA Reviewer";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
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
        .navbar {
            background: rgba(10, 20, 40, 0.9);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(0, 255, 157, 0.2);
            padding: 1rem 2rem;
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
            font-family: 'Orbitron', sans-serif;
            font-size: 1.5rem;
            font-weight: 800;
            background: linear-gradient(135deg, #00ff9d, #0066ff);
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
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .nav-links a:hover, .nav-links a.active { color: #00ff9d; }
        .user-info {
            display: flex;
            align-items: center;
            gap: 1rem;
            color: #00ff9d;
        }
        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 2rem;
        }
        .page-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        .page-header h1 {
            font-family: 'Orbitron', sans-serif;
            font-size: 2rem;
            background: linear-gradient(135deg, #00ff9d, #0066ff);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 0.5rem;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        .stat-card {
            background: rgba(0, 0, 0, 0.4);
            border: 1px solid rgba(0, 255, 157, 0.3);
            border-radius: 20px;
            padding: 1.5rem;
            text-align: center;
            transition: all 0.3s;
        }
        .stat-card:hover {
            transform: translateY(-5px);
            border-color: #00ff9d;
        }
        .stat-icon {
            font-size: 2rem;
            color: #00ff9d;
            margin-bottom: 0.5rem;
        }
        .stat-number {
            font-family: 'Orbitron', sans-serif;
            font-size: 2rem;
            font-weight: bold;
            color: #00ff9d;
        }
        .stat-label {
            font-size: 0.8rem;
            color: #8899cc;
            margin-top: 0.3rem;
        }
        .filter-bar {
            background: rgba(0, 0, 0, 0.3);
            border-radius: 16px;
            padding: 1rem;
            margin-bottom: 2rem;
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            align-items: center;
            justify-content: center;
        }
        .filter-select {
            background: rgba(0, 0, 0, 0.5);
            border: 1px solid rgba(0, 255, 157, 0.3);
            border-radius: 8px;
            padding: 0.5rem 1rem;
            color: #e0e0ff;
            font-family: 'Rajdhani', sans-serif;
            cursor: pointer;
        }
        .filter-btn {
            background: linear-gradient(45deg, #00ff9d, #0066ff);
            border: none;
            border-radius: 8px;
            padding: 0.5rem 1rem;
            color: #050812;
            font-weight: bold;
            cursor: pointer;
        }
        .section-title {
            font-family: 'Orbitron', sans-serif;
            color: #00ff9d;
            margin: 1.5rem 0 1rem;
            font-size: 1.3rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .data-table {
            width: 100%;
            background: rgba(10, 20, 40, 0.7);
            border: 1px solid rgba(0, 255, 157, 0.2);
            border-radius: 16px;
            overflow: hidden;
            margin-bottom: 2rem;
        }
        .data-table th {
            background: rgba(0, 255, 157, 0.1);
            color: #00ff9d;
            padding: 1rem;
            text-align: left;
            font-weight: 600;
        }
        .data-table td {
            padding: 0.8rem 1rem;
            border-bottom: 1px solid rgba(0, 255, 157, 0.1);
            color: #cbd5e1;
        }
        .data-table tr:hover {
            background: rgba(0, 255, 157, 0.05);
        }
        .badge-pass {
            background: rgba(0, 255, 0, 0.2);
            color: #00ff9d;
            padding: 0.2rem 0.5rem;
            border-radius: 20px;
            font-size: 0.7rem;
        }
        .badge-fail {
            background: rgba(255, 0, 0, 0.2);
            color: #ff6666;
            padding: 0.2rem 0.5rem;
            border-radius: 20px;
            font-size: 0.7rem;
        }
        .progress-bar {
            width: 100%;
            height: 6px;
            background: rgba(255,255,255,0.1);
            border-radius: 3px;
            overflow: hidden;
        }
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #00ff9d, #0066ff);
            border-radius: 3px;
        }
        .export-buttons {
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
            margin-bottom: 1rem;
        }
        .btn-export {
            background: rgba(0, 0, 0, 0.5);
            border: 1px solid #00ff9d;
            border-radius: 8px;
            padding: 0.5rem 1rem;
            color: #00ff9d;
            cursor: pointer;
            transition: all 0.3s;
        }
        .btn-export:hover {
            background: #00ff9d;
            color: #050812;
        }
        @media (max-width: 768px) {
            .container { padding: 1rem; }
            .stats-grid { grid-template-columns: repeat(2, 1fr); }
            .data-table { font-size: 0.8rem; overflow-x: auto; display: block; }
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
                <a href="report.php" class="active"><i class="fas fa-chart-bar"></i> Reports</a>
                <div class="user-info">
                    <i class="fas fa-user"></i>
                    <span><?php echo htmlspecialchars($_SESSION['full_name'] ?? $_SESSION['username']); ?></span>
                    <a href="logout.php" style="color: #ef4444;"><i class="fas fa-sign-out-alt"></i></a>
                </div>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="page-header">
            <h1><i class="fas fa-chart-line"></i> Admin Reports Dashboard</h1>
            <p>System analytics, user performance, and quiz statistics</p>
        </div>

        <!-- Statistics Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-users"></i></div>
                <div class="stat-number"><?php echo $total_users; ?></div>
                <div class="stat-label">Total Users</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-pencil-alt"></i></div>
                <div class="stat-number"><?php echo $total_quizzes; ?></div>
                <div class="stat-label">Quizzes Taken</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-percent"></i></div>
                <div class="stat-number"><?php echo $avg_score; ?>%</div>
                <div class="stat-label">Average Score</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
                <div class="stat-number"><?php echo $pass_rate; ?>%</div>
                <div class="stat-label">Pass Rate (75%+)</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-gamepad"></i></div>
                <div class="stat-number"><?php echo $total_games; ?></div>
                <div class="stat-label">Games Played</div>
            </div>
        </div>

        <!-- Filter Bar -->
        <div class="filter-bar">
            <form method="GET" action="" style="display: flex; flex-wrap: wrap; gap: 1rem; align-items: center;">
                <select name="date_filter" class="filter-select">
                    <option value="all" <?php echo $date_filter == 'all' ? 'selected' : ''; ?>>All Time</option>
                    <option value="today" <?php echo $date_filter == 'today' ? 'selected' : ''; ?>>Today</option>
                    <option value="week" <?php echo $date_filter == 'week' ? 'selected' : ''; ?>>Last 7 Days</option>
                    <option value="month" <?php echo $date_filter == 'month' ? 'selected' : ''; ?>>Last 30 Days</option>
                </select>
                <select name="coc_filter" class="filter-select">
                    <option value="0">All COCs</option>
                    <option value="1" <?php echo $coc_filter == 1 ? 'selected' : ''; ?>>COC 1: Hardware</option>
                    <option value="2" <?php echo $coc_filter == 2 ? 'selected' : ''; ?>>COC 2: Networks</option>
                    <option value="3" <?php echo $coc_filter == 3 ? 'selected' : ''; ?>>COC 3: Servers</option>
                    <option value="4" <?php echo $coc_filter == 4 ? 'selected' : ''; ?>>COC 4: Maintenance</option>
                </select>
                <select name="user_filter" class="filter-select">
                    <option value="0">All Users</option>
                    <?php while($user = $all_users->fetch_assoc()): ?>
                    <option value="<?php echo $user['id']; ?>" <?php echo $user_filter == $user['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($user['full_name']); ?>
                    </option>
                    <?php endwhile; ?>
                </select>
                <button type="submit" class="filter-btn"><i class="fas fa-filter"></i> Apply Filter</button>
                <a href="report.php" class="filter-btn" style="background: rgba(0,0,0,0.5); color:#00ff9d;">Reset</a>
            </form>
        </div>

        <!-- COC Performance -->
        <div class="section-title">
            <i class="fas fa-chart-simple"></i> Performance by COC
        </div>
        <table class="data-table">
            <thead>
                <tr>
                    <th>COC</th>
                    <th>Total Takes</th>
                    <th>Average Score</th>
                    <th>Passed</th>
                    <th>Failed</th>
                    <th>Pass Rate</th>
                </tr>
            </thead>
            <tbody>
                <?php while($coc = $coc_results->fetch_assoc()): ?>
                <tr>
                    <td><?php echo htmlspecialchars($coc['coc_name']); ?></td>
                    <td><?php echo $coc['total_takes']; ?></td>
                    <td><?php echo round($coc['avg_score'] ?? 0, 1); ?>%</td>
                    <td><span class="badge-pass"><?php echo $coc['passed']; ?></span></td>
                    <td><span class="badge-fail"><?php echo $coc['failed']; ?></span></td>
                    <td>
                        <?php 
                        $pass_rate_coc = $coc['total_takes'] > 0 ? round(($coc['passed'] / $coc['total_takes']) * 100, 1) : 0;
                        ?>
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: <?php echo $pass_rate_coc; ?>%"></div>
                        </div>
                        <?php echo $pass_rate_coc; ?>%
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>

        <!-- Top Performers -->
        <div class="section-title">
            <i class="fas fa-trophy"></i> Top Performers
        </div>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Rank</th>
                    <th>User</th>
                    <th>Average Score</th>
                    <th>Quizzes Taken</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $rank = 1;
                while($performer = $top_performers->fetch_assoc()): 
                ?>
                <tr>
                    <td>
                        <?php if($rank == 1): ?>🥇
                        <?php elseif($rank == 2): ?>🥈
                        <?php elseif($rank == 3): ?>🥉
                        <?php else: echo $rank; endif; ?>
                    </td>
                    <td><?php echo htmlspecialchars($performer['full_name']); ?> (<?php echo htmlspecialchars($performer['username']); ?>)</td>
                    <td><?php echo round($performer['avg_score'], 1); ?>%</td>
                    <td><?php echo $performer['quizzes_taken']; ?></td>
                </tr>
                <?php $rank++; endwhile; ?>
            </tbody>
        </table>

        <!-- Game Statistics -->
        <div class="section-title">
            <i class="fas fa-gamepad"></i> Game Statistics
        </div>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Game Type</th>
                    <th>Total Plays</th>
                    <th>Average Score</th>
                    <th>High Score</th>
                </tr>
            </thead>
            <tbody>
                <?php while($game = $game_stats->fetch_assoc()): ?>
                <tr>
                    <td><?php echo ucfirst(str_replace('_', ' ', $game['game_type'])); ?></td>
                    <td><?php echo $game['plays']; ?></td>
                    <td><?php echo round($game['avg_score'], 1); ?></td>
                    <td><?php echo $game['high_score']; ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>

        <!-- Daily Activity -->
        <div class="section-title">
            <i class="fas fa-calendar-day"></i> Last 7 Days Activity
        </div>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Quizzes Taken</th>
                    <th>Average Score</th>
                </tr>
            </thead>
            <tbody>
                <?php while($day = $daily_activity->fetch_assoc()): ?>
                <tr>
                    <td><?php echo date('M d, Y', strtotime($day['date'])); ?></td>
                    <td><?php echo $day['quizzes_taken']; ?></td>
                    <td><?php echo round($day['avg_score'] ?? 0, 1); ?>%</td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>

        <!-- Recent Quiz Results -->
        <div class="section-title">
            <i class="fas fa-list"></i> Recent Quiz Results
        </div>
        <div class="export-buttons">
            <button class="btn-export" onclick="exportToCSV()"><i class="fas fa-file-csv"></i> Export to CSV</button>
            <button class="btn-export" onclick="window.print()"><i class="fas fa-print"></i> Print Report</button>
        </div>
        <table class="data-table" id="quizTable">
            <thead>
                <tr>
                    <th>User</th>
                    <th>COC</th>
                    <th>Score</th>
                    <th>Percentage</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                <?php while($quiz = $recent_quizzes->fetch_assoc()): 
                    $percentage = ($quiz['score'] / $quiz['total_questions']) * 100;
                ?>
                <tr>
                    <td><?php echo htmlspecialchars($quiz['full_name']); ?></td>
                    <td><?php echo htmlspecialchars($quiz['category_name']); ?></td>
                    <td><?php echo $quiz['score']; ?>/<?php echo $quiz['total_questions']; ?></td>
                    <td style="color: <?php echo $percentage >= 75 ? '#00ff9d' : '#ff6666'; ?>">
                        <?php echo round($percentage, 1); ?>%
                    </td>
                    <td><?php echo date('M d, Y h:i A', strtotime($quiz['completed_at'])); ?></td>
                </tr>
                <?php endwhile; ?>
                <?php if($recent_quizzes->num_rows == 0): ?>
                <tr>
                    <td colspan="5" style="text-align: center;">No quiz results found</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <script>
        function exportToCSV() {
            let csv = [];
            let rows = document.querySelectorAll('#quizTable tr');
            
            for (let i = 0; i < rows.length; i++) {
                let row = [], cols = rows[i].querySelectorAll('td, th');
                for (let j = 0; j < cols.length; j++) {
                    row.push('"' + cols[j].innerText.replace(/"/g, '""') + '"');
                }
                csv.push(row.join(','));
            }
            
            let blob = new Blob([csv.join('\n')], { type: 'text/csv' });
            let url = URL.createObjectURL(blob);
            let a = document.createElement('a');
            a.href = url;
            a.download = 'quiz_report_<?php echo date('Y-m-d'); ?>.csv';
            a.click();
            URL.revokeObjectURL(url);
        }
    </script>
     <?php $global_brightness = getGlobalBrightness(); include __DIR__ . '/includes/brightness_widget.php'; ?>
      <?php include __DIR__ . '/includes/theme_toggle.php'; ?>
</body>
</html>
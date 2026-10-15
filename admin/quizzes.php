<?php
require_once 'config.php';

if (!isAdmin()) {
    redirect('../login.php');
}

// Get filter parameters
$category_filter = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$date_filter = isset($_GET['date']) ? $_GET['date'] : '';
$user_filter = isset($_GET['user']) ? (int)$_GET['user'] : 0;

// Build query with filters
$where_clauses = [];
if ($category_filter > 0) {
    $where_clauses[] = "q.category_id = $category_filter";
}
if ($date_filter) {
    $where_clauses[] = "DATE(q.completed_at) = '$date_filter'";
}
if ($user_filter > 0) {
    $where_clauses[] = "q.user_id = $user_filter";
}

$where_sql = '';
if (!empty($where_clauses)) {
    $where_sql = 'WHERE ' . implode(' AND ', $where_clauses);
}

// Get all quiz results with user and category details
$quizzes_query = "
    SELECT q.*, u.username, u.full_name, c.name as category_name,
           (q.score / q.total_questions * 100) as percentage,
           CASE 
               WHEN (q.score / q.total_questions * 100) >= 90 THEN 'Excellent'
               WHEN (q.score / q.total_questions * 100) >= 75 THEN 'Passed'
               ELSE 'Failed'
           END as status
    FROM quizzes q
    JOIN users u ON q.user_id = u.id
    JOIN categories c ON q.category_id = c.id
    $where_sql
    ORDER BY q.completed_at DESC
";

$quizzes_result = $conn->query($quizzes_query);
if ($quizzes_result === false) {
    die("Error in query: " . $conn->error);
}

// Get categories for filter
$categories_query = "SELECT * FROM categories ORDER BY id";
$categories_result = $conn->query($categories_query);
if ($categories_result === false) {
    die("Error in categories query: " . $conn->error);
}

// Get users for filter
$users_query = "SELECT id, username, full_name FROM users WHERE role = 'student' ORDER BY full_name";
$users_result = $conn->query($users_query);
if ($users_result === false) {
    die("Error in users query: " . $conn->error);
}

// Calculate statistics
$stats = [];

$total_quizzes = $conn->query("SELECT COUNT(*) as count FROM quizzes")->fetch_assoc()['count'];
$total_students = $conn->query("SELECT COUNT(DISTINCT user_id) as count FROM quizzes")->fetch_assoc()['count'];
$avg_score = $conn->query("SELECT AVG(score/total_questions*100) as avg FROM quizzes")->fetch_assoc()['avg'];
$passing_rate = $conn->query("
    SELECT COUNT(*) * 100.0 / (SELECT COUNT(*) FROM quizzes) as rate 
    FROM quizzes WHERE (score/total_questions*100) >= 75
")->fetch_assoc()['rate'];

$stats = [
    'total' => $total_quizzes,
    'students' => $total_students,
    'avg_score' => round($avg_score ?? 0, 1),
    'passing_rate' => round($passing_rate ?? 0, 1)
];

// Get performance by COC
$coc_performance = $conn->query("
    SELECT c.id, c.name, 
           COUNT(q.id) as quiz_count,
           AVG(q.score/q.total_questions*100) as avg_score
    FROM categories c
    LEFT JOIN quizzes q ON c.id = q.category_id
    GROUP BY c.id
    ORDER BY c.id
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quiz Results - Admin Panel</title>
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

        /* Dashboard Layout */
        .dashboard {
            display: grid;
            grid-template-columns: 280px 1fr;
            min-height: 100vh;
        }

        /* Sidebar */
        .sidebar {
            background: rgba(5, 8, 18, 0.95);
            backdrop-filter: blur(10px);
            border-right: 2px solid #00ff9d;
            padding: 30px 0;
            position: relative;
            box-shadow: 10px 0 30px rgba(0, 255, 157, 0.1);
        }

        .sidebar::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 2px;
            height: 100%;
            background: linear-gradient(to bottom, transparent, #00ff9d, transparent);
        }

        .sidebar-header {
            text-align: center;
            padding: 0 20px 30px;
            border-bottom: 1px solid rgba(0, 255, 157, 0.2);
        }

        .sidebar-header i {
            font-size: 3rem;
            color: #00ff9d;
            margin-bottom: 15px;
            animation: pulse 2s infinite;
        }

        .sidebar-header h2 {
            font-family: 'Orbitron', sans-serif;
            color: #00ff9d;
            font-size: 1.3rem;
            margin-bottom: 5px;
        }

        .welcome {
            color: #8899cc;
            font-size: 0.9rem;
        }

        .admin-badge {
            color: #00ff9d;
            font-size: 0.8rem;
            margin-top: 5px;
        }

        .sidebar-nav {
            padding: 20px;
        }

        .sidebar-nav ul {
            list-style: none;
        }

        .sidebar-nav li {
            margin-bottom: 5px;
        }

        .sidebar-nav a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 15px;
            color: #8899cc;
            text-decoration: none;
            border-radius: 8px;
            transition: all 0.3s;
        }

        .sidebar-nav a:hover,
        .sidebar-nav a.active {
            background: rgba(0, 255, 157, 0.1);
            color: #00ff9d;
        }

        .sidebar-nav i {
            width: 20px;
        }

        .sidebar-footer {
            position: absolute;
            bottom: 0;
            width: 100%;
            padding: 20px;
            border-top: 1px solid rgba(0, 255, 157, 0.2);
        }

        /* Main Content */
        .main-content {
            padding: 40px;
            overflow-y: auto;
            max-height: 100vh;
        }

        .main-content h1 {
            font-family: 'Orbitron', sans-serif;
            font-size: 2.5rem;
            margin-bottom: 10px;
            background: linear-gradient(135deg, #00ff9d, #0066ff);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .subtitle {
            color: #8899cc;
            margin-bottom: 30px;
            font-size: 1.1rem;
        }

        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: rgba(10, 20, 40, 0.7);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(0, 255, 157, 0.3);
            border-radius: 15px;
            padding: 20px;
            transition: all 0.3s;
        }

        .stat-card:hover {
            border-color: #00ff9d;
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(0, 255, 157, 0.2);
        }

        .stat-value {
            font-family: 'Orbitron', sans-serif;
            font-size: 2rem;
            color: #00ff9d;
        }

        .stat-label {
            color: #8899cc;
            font-size: 0.9rem;
            text-transform: uppercase;
        }

        /* Filter Bar */
        .filter-bar {
            background: rgba(10, 20, 40, 0.7);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(0, 255, 157, 0.3);
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 30px;
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            align-items: center;
        }

        .filter-group {
            display: flex;
            align-items: center;
            gap: 10px;
            background: rgba(0, 0, 0, 0.3);
            padding: 5px 15px;
            border-radius: 8px;
            border: 1px solid rgba(0, 255, 157, 0.2);
        }

        .filter-group i {
            color: #00ff9d;
        }

        .filter-input, .filter-select {
            background: transparent;
            border: none;
            color: #e0e0ff;
            padding: 8px;
            font-family: 'Rajdhani', sans-serif;
            min-width: 150px;
        }

        .filter-input:focus, .filter-select:focus {
            outline: none;
        }

        .filter-select {
            cursor: pointer;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            font-family: 'Orbitron', sans-serif;
            font-size: 0.9rem;
            cursor: pointer;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
        }

        .btn-primary {
            background: linear-gradient(45deg, #00ff9d, #0066ff);
            color: #050812;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(0, 255, 157, 0.4);
        }

        .btn-secondary {
            background: transparent;
            border: 1px solid #00ff9d;
            color: #00ff9d;
        }

        .btn-secondary:hover {
            background: rgba(0, 255, 157, 0.1);
            transform: translateY(-2px);
        }

        /* Performance Charts */
        .charts-section {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 25px;
            margin-bottom: 30px;
        }

        .chart-container {
            background: rgba(10, 20, 40, 0.7);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(0, 255, 157, 0.3);
            border-radius: 15px;
            padding: 20px;
        }

        .chart-container h3 {
            color: #00ff9d;
            margin-bottom: 20px;
            font-family: 'Orbitron', sans-serif;
        }

        .bar-chart {
            display: flex;
            align-items: flex-end;
            justify-content: space-around;
            height: 200px;
            margin-top: 20px;
        }

        .bar-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            width: 80px;
        }

        .bar {
            width: 40px;
            background: linear-gradient(180deg, #00ff9d, #0066ff);
            border-radius: 10px 10px 0 0;
            transition: height 0.3s;
            position: relative;
        }

        .bar-value {
            position: absolute;
            top: -25px;
            left: 50%;
            transform: translateX(-50%);
            color: #00ff9d;
            font-size: 0.9rem;
            white-space: nowrap;
        }

        .bar-label {
            margin-top: 10px;
            color: #8899cc;
            font-size: 0.9rem;
            text-align: center;
        }

        /* Quizzes Table */
        .table-container {
            background: rgba(10, 20, 40, 0.7);
            backdrop-filter: blur(10px);
            border: 2px solid rgba(0, 255, 157, 0.3);
            border-radius: 20px;
            padding: 20px;
            overflow-x: auto;
            margin-bottom: 30px;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
        }

        .data-table th {
            text-align: left;
            padding: 15px;
            color: #00ff9d;
            font-family: 'Orbitron', sans-serif;
            border-bottom: 2px solid rgba(0, 255, 157, 0.3);
        }

        .data-table td {
            padding: 15px;
            border-bottom: 1px solid rgba(0, 255, 157, 0.1);
            color: #8899cc;
        }

        .data-table tr:hover {
            background: rgba(0, 255, 157, 0.05);
        }

        .status-badge {
            padding: 3px 10px;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: bold;
        }

        .status-Excellent {
            background: rgba(0, 255, 157, 0.2);
            color: #00ff9d;
            border: 1px solid #00ff9d;
        }

        .status-Passed {
            background: rgba(0, 102, 255, 0.2);
            color: #0066ff;
            border: 1px solid #0066ff;
        }

        .status-Failed {
            background: rgba(255, 51, 102, 0.2);
            color: #ff3366;
            border: 1px solid #ff3366;
        }

        .score-bar {
            width: 100px;
            height: 5px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 5px;
            overflow: hidden;
            margin-top: 5px;
        }

        .score-fill {
            height: 100%;
            background: linear-gradient(90deg, #00ff9d, #0066ff);
            transition: width 0.3s;
        }

        .action-btn {
            padding: 5px 10px;
            border: 1px solid #00ff9d;
            background: transparent;
            color: #00ff9d;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.3s;
            margin: 0 2px;
        }

        .action-btn:hover {
            background: #00ff9d;
            color: #050812;
        }

        /* Pagination */
        .pagination {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-top: 30px;
        }

        .page-btn {
            padding: 8px 15px;
            background: transparent;
            border: 1px solid #00ff9d;
            color: #00ff9d;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.3s;
        }

        .page-btn:hover,
        .page-btn.active {
            background: #00ff9d;
            color: #050812;
        }

        /* Export Section */
        .export-section {
            background: rgba(10, 20, 40, 0.7);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(0, 255, 157, 0.3);
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 30px;
            display: flex;
            gap: 15px;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
        }

        .export-buttons {
            display: flex;
            gap: 10px;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }

        /* Responsive */
        @media (max-width: 768px) {
            .dashboard {
                grid-template-columns: 1fr;
            }
            
            .sidebar {
                display: none;
            }
            
            .main-content {
                padding: 20px;
            }
            
            .charts-section {
                grid-template-columns: 1fr;
            }
            
            .filter-bar {
                flex-direction: column;
                align-items: stretch;
            }
            
            .filter-group {
                width: 100%;
            }
            
            .filter-input, .filter-select {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="cyber-bg"></div>
    <div class="grid-overlay"></div>

    <div class="dashboard">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="sidebar-header">
                <i class="fas fa-crown"></i>
                <h2>ADMIN PANEL</h2>
                <p class="welcome">Welcome, <?php echo htmlspecialchars($_SESSION['full_name']); ?>!</p>
                <p class="admin-badge"><i class="fas fa-shield-alt"></i> Administrator</p>
            </div>
            
            <nav class="sidebar-nav">
                <ul>
                    <li><a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                    <li><a href="users.php"><i class="fas fa-users"></i> Manage Users</a></li>
                    <li><a href="questions.php"><i class="fas fa-question-circle"></i> Questions</a></li>
                    <li><a href="categories.php"><i class="fas fa-tags"></i> Categories</a></li>
                    <li><a href="quizzes.php" class="active"><i class="fas fa-clipboard-list"></i> Quiz Results</a></li>
                    <li><a href="games.php"><i class="fas fa-gamepad"></i> Game Scores</a></li>
                    <li><a href="../dashboard.php"><i class="fas fa-arrow-left"></i> Back to User View</a></li>
                    <li><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                </ul>
            </nav>
        </div>

        <!-- Main Content -->
        <main class="main-content">
            <h1><i class="fas fa-clipboard-list"></i> Quiz Results</h1>
            <p class="subtitle">View and analyze all quiz attempts from students</p>

            <!-- Statistics Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-value"><?php echo $stats['total']; ?></div>
                    <div class="stat-label">Total Quizzes</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?php echo $stats['students']; ?></div>
                    <div class="stat-label">Active Students</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?php echo $stats['avg_score']; ?>%</div>
                    <div class="stat-label">Average Score</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?php echo $stats['passing_rate']; ?>%</div>
                    <div class="stat-label">Passing Rate</div>
                </div>
            </div>

            <!-- Performance Charts -->
            <div class="charts-section">
                <div class="chart-container">
                    <h3><i class="fas fa-chart-bar"></i> Performance by COC</h3>
                    <div class="bar-chart">
                        <?php 
                        $coc_performance->data_seek(0);
                        while ($coc = $coc_performance->fetch_assoc()): 
                            $height = $coc['avg_score'] ?? 0;
                        ?>
                        <div class="bar-item">
                            <div class="bar" style="height: <?php echo $height * 1.5; ?>px;">
                                <span class="bar-value"><?php echo round($height, 1); ?>%</span>
                            </div>
                            <div class="bar-label">COC <?php echo $coc['id']; ?></div>
                        </div>
                        <?php endwhile; ?>
                    </div>
                </div>

                <div class="chart-container">
                    <h3><i class="fas fa-chart-pie"></i> Summary</h3>
                    <div style="display: flex; justify-content: space-around; align-items: center; height: 200px;">
                        <div style="text-align: center;">
                            <div style="font-size: 3rem; color: #00ff9d;"><?php echo $stats['passing_rate']; ?>%</div>
                            <div style="color: #8899cc;">Passing Rate</div>
                        </div>
                        <div style="text-align: center;">
                            <div style="font-size: 3rem; color: #0066ff;"><?php echo $stats['total']; ?></div>
                            <div style="color: #8899cc;">Total Quizzes</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filter Bar -->
            <div class="filter-bar">
                <div class="filter-group">
                    <i class="fas fa-tag"></i>
                    <select class="filter-select" id="categoryFilter">
                        <option value="0">All Categories</option>
                        <?php 
                        $categories_result->data_seek(0);
                        while ($cat = $categories_result->fetch_assoc()): 
                        ?>
                        <option value="<?php echo $cat['id']; ?>" <?php echo $category_filter == $cat['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($cat['name']); ?>
                        </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <div class="filter-group">
                    <i class="fas fa-user"></i>
                    <select class="filter-select" id="userFilter">
                        <option value="0">All Students</option>
                        <?php 
                        $users_result->data_seek(0);
                        while ($user = $users_result->fetch_assoc()): 
                        ?>
                        <option value="<?php echo $user['id']; ?>" <?php echo $user_filter == $user['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($user['full_name']); ?>
                        </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <div class="filter-group">
                    <i class="fas fa-calendar"></i>
                    <input type="date" class="filter-input" id="dateFilter" value="<?php echo $date_filter; ?>">
                </div>
                
                <button class="btn btn-primary" onclick="applyFilters()">
                    <i class="fas fa-filter"></i> Apply Filters
                </button>
                
                <button class="btn btn-secondary" onclick="clearFilters()">
                    <i class="fas fa-times"></i> Clear
                </button>
            </div>

            <!-- Export Section -->
            <div class="export-section">
                <span style="color: #00ff9d;"><i class="fas fa-download"></i> Export Data:</span>
                <div class="export-buttons">
                    <a href="export_quizzes.php?format=csv" class="btn btn-secondary">CSV</a>
                    <a href="export_quizzes.php?format=excel" class="btn btn-secondary">Excel</a>
                    <a href="export_quizzes.php?format=pdf" class="btn btn-secondary">PDF</a>
                    <a href="export_quizzes.php?format=print" class="btn btn-secondary" target="_blank">Print</a>
                </div>
            </div>

            <!-- Quizzes Table -->
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Student</th>
                            <th>Category</th>
                            <th>Score</th>
                            <th>Percentage</th>
                            <th>Status</th>
                            <th>Time Taken</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($quizzes_result && $quizzes_result->num_rows > 0): ?>
                            <?php while ($quiz = $quizzes_result->fetch_assoc()): ?>
                            <tr>
                                <td>#<?php echo $quiz['id']; ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($quiz['full_name']); ?></strong>
                                    <br><small style="color: #8899cc;">@<?php echo $quiz['username']; ?></small>
                                </td>
                                <td><?php echo htmlspecialchars($quiz['category_name']); ?></td>
                                <td><?php echo $quiz['score']; ?>/<?php echo $quiz['total_questions']; ?></td>
                                <td>
                                    <?php echo round($quiz['percentage'], 1); ?>%
                                    <div class="score-bar">
                                        <div class="score-fill" style="width: <?php echo $quiz['percentage']; ?>%"></div>
                                    </div>
                                </td>
                                <td>
                                    <span class="status-badge status-<?php echo $quiz['status']; ?>">
                                        <?php echo $quiz['status']; ?>
                                    </span>
                                </td>
                                <td>
                                    <?php 
                                    $minutes = floor($quiz['time_taken'] / 60);
                                    $seconds = $quiz['time_taken'] % 60;
                                    echo $minutes . 'm ' . $seconds . 's';
                                    ?>
                                </td>
                                <td><?php echo date('M d, Y H:i', strtotime($quiz['completed_at'])); ?></td>
                                <td>
                                    <button class="action-btn" onclick="viewQuiz(<?php echo $quiz['id']; ?>)">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button class="action-btn" onclick="deleteQuiz(<?php echo $quiz['id']; ?>)">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="9" style="text-align: center; padding: 40px;">
                                    <i class="fas fa-clipboard-list" style="font-size: 3rem; color: #8899cc; margin-bottom: 10px;"></i>
                                    <p>No quiz results found.</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="pagination">
                <button class="page-btn"><i class="fas fa-chevron-left"></i></button>
                <button class="page-btn active">1</button>
                <button class="page-btn">2</button>
                <button class="page-btn">3</button>
                <button class="page-btn">4</button>
                <button class="page-btn">5</button>
                <button class="page-btn"><i class="fas fa-chevron-right"></i></button>
            </div>
        </main>
    </div>

    <!-- View Quiz Modal -->
    <div id="viewQuizModal" class="modal" style="display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.8);">
        <div style="background: #0a1a2f; margin: 5% auto; padding: 30px; border: 2px solid #00ff9d; border-radius: 20px; width: 90%; max-width: 600px; position: relative;">
            <span onclick="closeViewModal()" style="color: #00ff9d; float: right; font-size: 28px; cursor: pointer;">&times;</span>
            <h2 style="color: #00ff9d; margin-bottom: 20px;">Quiz Details</h2>
            <div id="viewQuizContent" style="color: #e0e0ff;">
                <!-- Content will be loaded via AJAX -->
            </div>
        </div>
    </div>

    <script>
        function applyFilters() {
            const category = document.getElementById('categoryFilter').value;
            const user = document.getElementById('userFilter').value;
            const date = document.getElementById('dateFilter').value;
            
            window.location.href = `quizzes.php?category=${category}&user=${user}&date=${date}`;
        }

        function clearFilters() {
            window.location.href = 'quizzes.php';
        }

        function viewQuiz(id) {
            document.getElementById('viewQuizModal').style.display = 'block';
            document.getElementById('viewQuizContent').innerHTML = '<p style="text-align: center;">Loading quiz details...</p>';
            
            // Simulate AJAX load
            setTimeout(() => {
                document.getElementById('viewQuizContent').innerHTML = `
                    <p><strong>Quiz ID:</strong> #${id}</p>
                    <p><strong>Details:</strong> Full quiz details view coming soon!</p>
                    <p>This will show all questions and answers from the quiz.</p>
                `;
            }, 500);
        }

        function deleteQuiz(id) {
            if (confirm('Are you sure you want to delete this quiz result? This action cannot be undone.')) {
                alert('Delete functionality will be implemented with AJAX');
            }
        }

        function closeViewModal() {
            document.getElementById('viewQuizModal').style.display = 'none';
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('viewQuizModal');
            if (event.target == modal) {
                modal.style.display = 'none';
            }
        }
    </script>
     <?php $global_brightness = getGlobalBrightness(); include __DIR__ . '/includes/brightness_widget.php'; ?>
     <?php include __DIR__ . '/includes/theme_toggle.php'; ?>
</body>
</html>
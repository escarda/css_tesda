<?php
require_once '../config.php';

if (!isAdmin()) {
    redirect('../login.php');
}

// Handle CRUD operations
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add'])) {
        $category_id = (int)$_POST['category_id'];
        $question = $conn->real_escape_string($_POST['question']);
        $opt_a = $conn->real_escape_string($_POST['opt_a']);
        $opt_b = $conn->real_escape_string($_POST['opt_b']);
        $opt_c = $conn->real_escape_string($_POST['opt_c']);
        $opt_d = $conn->real_escape_string($_POST['opt_d']);
        $correct = $conn->real_escape_string($_POST['correct']);
        $explanation = $conn->real_escape_string($_POST['explanation']);
        $difficulty = $conn->real_escape_string($_POST['difficulty']);
        
        $sql = "INSERT INTO questions (category_id, question_text, option_a, option_b, option_c, option_d, correct_answer, explanation, difficulty) 
                VALUES ($category_id, '$question', '$opt_a', '$opt_b', '$opt_c', '$opt_d', '$correct', '$explanation', '$difficulty')";
        
        if ($conn->query($sql)) {
            $message = "Question added successfully!";
            $message_type = "success";
        } else {
            $message = "Error: " . $conn->error;
            $message_type = "error";
        }
    }
    elseif (isset($_POST['edit'])) {
        $id = (int)$_POST['id'];
        $category_id = (int)$_POST['category_id'];
        $question = $conn->real_escape_string($_POST['question']);
        $opt_a = $conn->real_escape_string($_POST['opt_a']);
        $opt_b = $conn->real_escape_string($_POST['opt_b']);
        $opt_c = $conn->real_escape_string($_POST['opt_c']);
        $opt_d = $conn->real_escape_string($_POST['opt_d']);
        $correct = $conn->real_escape_string($_POST['correct']);
        $explanation = $conn->real_escape_string($_POST['explanation']);
        $difficulty = $conn->real_escape_string($_POST['difficulty']);
        
        $sql = "UPDATE questions SET 
                category_id = $category_id,
                question_text = '$question',
                option_a = '$opt_a',
                option_b = '$opt_b',
                option_c = '$opt_c',
                option_d = '$opt_d',
                correct_answer = '$correct',
                explanation = '$explanation',
                difficulty = '$difficulty'
                WHERE id = $id";
        
        if ($conn->query($sql)) {
            $message = "Question updated successfully!";
            $message_type = "success";
        } else {
            $message = "Error: " . $conn->error;
            $message_type = "error";
        }
    }
    elseif (isset($_POST['delete'])) {
        $id = (int)$_POST['id'];
        $sql = "DELETE FROM questions WHERE id = $id";
        
        if ($conn->query($sql)) {
            $message = "Question deleted successfully!";
            $message_type = "success";
        } else {
            $message = "Error: " . $conn->error;
            $message_type = "error";
        }
    }
    elseif (isset($_POST['bulk_delete'])) {
        $ids = $_POST['question_ids'];
        if (!empty($ids)) {
            $ids_string = implode(',', array_map('intval', $ids));
            $sql = "DELETE FROM questions WHERE id IN ($ids_string)";
            
            if ($conn->query($sql)) {
                $message = "Selected questions deleted successfully!";
                $message_type = "success";
            } else {
                $message = "Error: " . $conn->error;
                $message_type = "error";
            }
        }
    }
}

// Get filter parameters
$category_filter = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$difficulty_filter = isset($_GET['difficulty']) ? $_GET['difficulty'] : '';
$search = isset($_GET['search']) ? $_GET['search'] : '';

// Build query with filters
$where_clauses = [];
if ($category_filter > 0) {
    $where_clauses[] = "q.category_id = $category_filter";
}
if ($difficulty_filter) {
    $where_clauses[] = "q.difficulty = '$difficulty_filter'";
}
if ($search) {
    $search_escaped = $conn->real_escape_string($search);
    $where_clauses[] = "(q.question_text LIKE '%$search_escaped%' OR q.option_a LIKE '%$search_escaped%' OR q.option_b LIKE '%$search_escaped%' OR q.option_c LIKE '%$search_escaped%' OR q.option_d LIKE '%$search_escaped%')";
}

$where_sql = '';
if (!empty($where_clauses)) {
    $where_sql = 'WHERE ' . implode(' AND ', $where_clauses);
}

// Get questions with filters
$questions_query = "
    SELECT q.*, c.name as category_name 
    FROM questions q 
    JOIN categories c ON q.category_id = c.id 
    $where_sql
    ORDER BY q.id DESC
";

$questions_result = $conn->query($questions_query);
if ($questions_result === false) {
    die("Error in query: " . $conn->error);
}

// Get categories for filter
$categories_query = "SELECT * FROM categories ORDER BY id";
$categories_result = $conn->query($categories_query);
if ($categories_result === false) {
    die("Error in categories query: " . $conn->error);
}

// Get statistics
$stats = [
    'total' => 0,
    'easy' => 0,
    'medium' => 0,
    'hard' => 0,
];

$total_query = $conn->query("SELECT COUNT(*) as count FROM questions");
if ($total_query) {
    $stats['total'] = $total_query->fetch_assoc()['count'];
}

$easy_query = $conn->query("SELECT COUNT(*) as count FROM questions WHERE difficulty = 'easy'");
if ($easy_query) {
    $stats['easy'] = $easy_query->fetch_assoc()['count'];
}

$medium_query = $conn->query("SELECT COUNT(*) as count FROM questions WHERE difficulty = 'medium'");
if ($medium_query) {
    $stats['medium'] = $medium_query->fetch_assoc()['count'];
}

$hard_query = $conn->query("SELECT COUNT(*) as count FROM questions WHERE difficulty = 'hard'");
if ($hard_query) {
    $stats['hard'] = $hard_query->fetch_assoc()['count'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Questions - Admin Panel</title>
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

        /* Action Bar */
        .action-bar {
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

        .btn {
            padding: 12px 25px;
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

        .btn-danger {
            background: transparent;
            border: 1px solid #ff3366;
            color: #ff3366;
        }

        .btn-danger:hover {
            background: rgba(255, 51, 102, 0.1);
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

        .filter-input {
            background: transparent;
            border: none;
            color: #e0e0ff;
            padding: 8px;
            font-family: 'Rajdhani', sans-serif;
            min-width: 150px;
        }

        .filter-input:focus {
            outline: none;
        }

        .filter-select {
            background: rgba(0, 0, 0, 0.3);
            border: 1px solid rgba(0, 255, 157, 0.2);
            color: #e0e0ff;
            padding: 8px 15px;
            border-radius: 8px;
            font-family: 'Rajdhani', sans-serif;
        }

        /* Questions Table */
        .questions-container {
            background: rgba(10, 20, 40, 0.7);
            backdrop-filter: blur(10px);
            border: 2px solid rgba(0, 255, 157, 0.3);
            border-radius: 20px;
            padding: 20px;
            overflow-x: auto;
        }

        .questions-table {
            width: 100%;
            border-collapse: collapse;
        }

        .questions-table th {
            text-align: left;
            padding: 15px;
            color: #00ff9d;
            font-family: 'Orbitron', sans-serif;
            border-bottom: 2px solid rgba(0, 255, 157, 0.3);
        }

        .questions-table td {
            padding: 15px;
            border-bottom: 1px solid rgba(0, 255, 157, 0.1);
            color: #8899cc;
        }

        .questions-table tr:hover {
            background: rgba(0, 255, 157, 0.05);
        }

        .question-text {
            max-width: 300px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .difficulty-badge {
            padding: 3px 10px;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: bold;
        }

        .difficulty-easy {
            background: rgba(0, 255, 157, 0.2);
            color: #00ff9d;
            border: 1px solid #00ff9d;
        }

        .difficulty-medium {
            background: rgba(255, 170, 0, 0.2);
            color: #ffaa00;
            border: 1px solid #ffaa00;
        }

        .difficulty-hard {
            background: rgba(255, 51, 102, 0.2);
            color: #ff3366;
            border: 1px solid #ff3366;
        }

        .action-btns {
            display: flex;
            gap: 5px;
        }

        .action-btn {
            padding: 5px 10px;
            border: 1px solid #00ff9d;
            background: transparent;
            color: #00ff9d;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.3s;
        }

        .action-btn:hover {
            background: #00ff9d;
            color: #050812;
        }

        .action-btn.delete:hover {
            border-color: #ff3366;
            background: #ff3366;
            color: white;
        }

        /* Modal */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.8);
            backdrop-filter: blur(5px);
        }

        .modal-content {
            background: #0a1a2f;
            margin: 5% auto;
            padding: 30px;
            border: 2px solid #00ff9d;
            border-radius: 20px;
            width: 90%;
            max-width: 700px;
            max-height: 80vh;
            overflow-y: auto;
            position: relative;
            animation: slideIn 0.3s;
        }

        @keyframes slideIn {
            from {
                transform: translateY(-50px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid rgba(0, 255, 157, 0.3);
        }

        .modal-header h2 {
            color: #00ff9d;
            font-family: 'Orbitron', sans-serif;
        }

        .close {
            color: #00ff9d;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }

        .close:hover {
            color: #ff3366;
        }

        /* Form Styles */
        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #00ff9d;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.85rem;
            letter-spacing: 1px;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px 15px;
            background: rgba(0, 0, 0, 0.3);
            border: 1px solid rgba(0, 255, 157, 0.3);
            border-radius: 8px;
            color: #e0e0ff;
            font-family: 'Rajdhani', sans-serif;
            font-size: 1rem;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #00ff9d;
            box-shadow: 0 0 10px rgba(0, 255, 157, 0.3);
        }

        .options-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
        }

        /* Alert Messages */
        .alert {
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            animation: slideIn 0.3s;
        }

        .alert-success {
            background: rgba(0, 255, 157, 0.1);
            border: 1px solid #00ff9d;
            color: #00ff9d;
        }

        .alert-error {
            background: rgba(255, 51, 102, 0.1);
            border: 1px solid #ff3366;
            color: #ff3366;
        }

        /* Checkbox */
        .checkbox-column {
            width: 30px;
        }

        .question-checkbox {
            width: 18px;
            height: 18px;
            cursor: pointer;
            accent-color: #00ff9d;
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
            
            .options-grid {
                grid-template-columns: 1fr;
            }
            
            .action-bar {
                flex-direction: column;
            }
            
            .btn {
                width: 100%;
                justify-content: center;
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
                    <li><a href="questions.php" class="active"><i class="fas fa-question-circle"></i> Manage Questions</a></li>
                    <li><a href="categories.php"><i class="fas fa-tags"></i> Categories</a></li>
                    <li><a href="quizzes.php"><i class="fas fa-clipboard-list"></i> Quiz Results</a></li>
                    <li><a href="games.php"><i class="fas fa-gamepad"></i> Game Scores</a></li>
                    <li><a href="../dashboard.php"><i class="fas fa-arrow-left"></i> Back to User View</a></li>
                    <li><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                </ul>
            </nav>
        </div>

        <!-- Main Content -->
        <main class="main-content">
            <h1><i class="fas fa-question-circle"></i> Manage Questions</h1>
            <p class="subtitle">Create, edit, and organize questions for all COC assessments</p>

            <!-- Statistics Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-value"><?php echo $stats['total']; ?></div>
                    <div class="stat-label">Total Questions</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?php echo $stats['easy']; ?></div>
                    <div class="stat-label">Easy</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?php echo $stats['medium']; ?></div>
                    <div class="stat-label">Medium</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?php echo $stats['hard']; ?></div>
                    <div class="stat-label">Hard</div>
                </div>
            </div>

            <!-- Alert Messages -->
            <?php if ($message): ?>
            <div class="alert alert-<?php echo $message_type; ?>">
                <i class="fas fa-<?php echo $message_type == 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                <?php echo $message; ?>
            </div>
            <?php endif; ?>

            <!-- Action Bar -->
            <div class="action-bar">
                <button class="btn btn-primary" onclick="showAddModal()">
                    <i class="fas fa-plus"></i> Add New Question
                </button>
                <button class="btn btn-secondary" onclick="showImportModal()">
                    <i class="fas fa-upload"></i> Import CSV
                </button>
                <a href="export_questions.php" class="btn btn-secondary">
                    <i class="fas fa-download"></i> Export CSV
                </a>
                <button class="btn btn-danger" onclick="deleteSelected()" id="deleteSelectedBtn" style="display: none;">
                    <i class="fas fa-trash"></i> Delete Selected (<span id="selectedCount">0</span>)
                </button>
            </div>

            <!-- Filter Bar -->
            <div class="filter-bar">
                <div class="filter-group">
                    <i class="fas fa-search"></i>
                    <input type="text" class="filter-input" placeholder="Search questions..." id="searchInput" value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <div class="filter-group">
                    <i class="fas fa-tag"></i>
                    <select class="filter-select" id="categoryFilter">
                        <option value="0">All Categories</option>
                        <?php 
                        if ($categories_result && $categories_result->num_rows > 0):
                            $categories_result->data_seek(0);
                            while ($cat = $categories_result->fetch_assoc()): 
                        ?>
                        <option value="<?php echo $cat['id']; ?>" <?php echo $category_filter == $cat['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($cat['name']); ?>
                        </option>
                        <?php 
                            endwhile;
                        endif;
                        ?>
                    </select>
                </div>
                <div class="filter-group">
                    <i class="fas fa-signal"></i>
                    <select class="filter-select" id="difficultyFilter">
                        <option value="">All Difficulties</option>
                        <option value="easy" <?php echo $difficulty_filter == 'easy' ? 'selected' : ''; ?>>Easy</option>
                        <option value="medium" <?php echo $difficulty_filter == 'medium' ? 'selected' : ''; ?>>Medium</option>
                        <option value="hard" <?php echo $difficulty_filter == 'hard' ? 'selected' : ''; ?>>Hard</option>
                    </select>
                </div>
                <button class="btn btn-secondary" onclick="applyFilters()">
                    <i class="fas fa-filter"></i> Apply Filters
                </button>
                <button class="btn btn-secondary" onclick="clearFilters()">
                    <i class="fas fa-times"></i> Clear
                </button>
            </div>

            <!-- Questions Table -->
            <div class="questions-container">
                <form id="bulkDeleteForm" method="POST">
                    <input type="hidden" name="bulk_delete" value="1">
                    <table class="questions-table">
                        <thead>
                            <tr>
                                <th class="checkbox-column">
                                    <input type="checkbox" class="question-checkbox" id="selectAll" onclick="toggleAll(this)">
                                </th>
                                <th>ID</th>
                                <th>Category</th>
                                <th>Question</th>
                                <th>Options</th>
                                <th>Correct</th>
                                <th>Difficulty</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($questions_result && $questions_result->num_rows > 0): ?>
                                <?php while ($row = $questions_result->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <input type="checkbox" name="question_ids[]" value="<?php echo $row['id']; ?>" class="question-checkbox" onchange="updateSelectedCount()">
                                    </td>
                                    <td>#<?php echo $row['id']; ?></td>
                                    <td><?php echo htmlspecialchars($row['category_name']); ?></td>
                                    <td class="question-text" title="<?php echo htmlspecialchars($row['question_text']); ?>">
                                        <?php echo htmlspecialchars(substr($row['question_text'], 0, 50)) . '...'; ?>
                                    </td>
                                    <td>
                                        <small>
                                            A: <?php echo htmlspecialchars(substr($row['option_a'], 0, 15)); ?>...<br>
                                            B: <?php echo htmlspecialchars(substr($row['option_b'], 0, 15)); ?>...<br>
                                            C: <?php echo htmlspecialchars(substr($row['option_c'], 0, 15)); ?>...<br>
                                            D: <?php echo htmlspecialchars(substr($row['option_d'], 0, 15)); ?>...
                                        </small>
                                    </td>
                                    <td style="color: #00ff9d; font-weight: bold;"><?php echo $row['correct_answer']; ?></td>
                                    <td>
                                        <span class="difficulty-badge difficulty-<?php echo $row['difficulty'] ?? 'medium'; ?>">
                                            <?php echo ucfirst($row['difficulty'] ?? 'Medium'); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="action-btns">
                                            <button class="action-btn" onclick='editQuestion(<?php echo json_encode($row); ?>)'>
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="action-btn" onclick="viewQuestion(<?php echo $row['id']; ?>)">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button class="action-btn delete" onclick="deleteQuestion(<?php echo $row['id']; ?>)">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" style="text-align: center; padding: 40px;">
                                        <i class="fas fa-question-circle" style="font-size: 3rem; color: #8899cc; margin-bottom: 10px;"></i>
                                        <p>No questions found. Click "Add New Question" to create one.</p>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </form>
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

    <!-- Add/Edit Question Modal -->
    <div id="questionModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalTitle">Add New Question</h2>
                <span class="close" onclick="closeModal()">&times;</span>
            </div>
            
            <form method="POST" action="" id="questionForm">
                <input type="hidden" name="id" id="questionId">
                
                <div class="form-group">
                    <label><i class="fas fa-tag"></i> Category</label>
                    <select name="category_id" id="category_id" required>
                        <option value="">Select Category</option>
                        <?php 
                        if ($categories_result && $categories_result->num_rows > 0):
                            $categories_result->data_seek(0);
                            while ($cat = $categories_result->fetch_assoc()): 
                        ?>
                        <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                        <?php 
                            endwhile;
                        endif;
                        ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label><i class="fas fa-question"></i> Question</label>
                    <textarea name="question" id="question" rows="3" required placeholder="Enter the question text..."></textarea>
                </div>
                
                <div class="options-grid">
                    <div class="form-group">
                        <label><i class="fas fa-a"></i> Option A</label>
                        <input type="text" name="opt_a" id="opt_a" required placeholder="Enter option A">
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-b"></i> Option B</label>
                        <input type="text" name="opt_b" id="opt_b" required placeholder="Enter option B">
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-c"></i> Option C</label>
                        <input type="text" name="opt_c" id="opt_c" required placeholder="Enter option C">
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-d"></i> Option D</label>
                        <input type="text" name="opt_d" id="opt_d" required placeholder="Enter option D">
                    </div>
                </div>
                
                <div class="options-grid">
                    <div class="form-group">
                        <label><i class="fas fa-check-circle"></i> Correct Answer</label>
                        <select name="correct" id="correct" required>
                            <option value="">Select Correct Answer</option>
                            <option value="A">A</option>
                            <option value="B">B</option>
                            <option value="C">C</option>
                            <option value="D">D</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-signal"></i> Difficulty</label>
                        <select name="difficulty" id="difficulty" required>
                            <option value="easy">Easy</option>
                            <option value="medium" selected>Medium</option>
                            <option value="hard">Hard</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label><i class="fas fa-info-circle"></i> Explanation (Optional)</label>
                    <textarea name="explanation" id="explanation" rows="2" placeholder="Explain why this answer is correct..."></textarea>
                </div>
                
                <div style="display: flex; gap: 10px; justify-content: flex-end;">
                    <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
                    <button type="submit" name="add" id="submitBtn" class="btn btn-primary">Add Question</button>
                    <button type="submit" name="edit" id="updateBtn" class="btn btn-primary" style="display: none;">Update Question</button>
                </div>
            </form>
        </div>
    </div>

    <!-- View Question Modal -->
    <div id="viewModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Question Details</h2>
                <span class="close" onclick="closeViewModal()">&times;</span>
            </div>
            <div id="viewContent" style="color: #e0e0ff;">
                <!-- Content will be loaded here -->
            </div>
        </div>
    </div>

    <!-- Import Modal (Simplified) -->
    <div id="importModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Import Questions</h2>
                <span class="close" onclick="closeImportModal()">&times;</span>
            </div>
            <div style="text-align: center; padding: 20px;">
                <i class="fas fa-cloud-upload-alt" style="font-size: 4rem; color: #00ff9d; margin-bottom: 20px;"></i>
                <h3 style="color: #00ff9d; margin-bottom: 10px;">CSV Import Coming Soon!</h3>
                <p style="color: #8899cc;">This feature is under development.</p>
                <button class="btn btn-secondary" onclick="closeImportModal()" style="margin-top: 20px;">Close</button>
            </div>
        </div>
    </div>

    <script>
        // Modal functions
        function showAddModal() {
            document.getElementById('modalTitle').textContent = 'Add New Question';
            document.getElementById('questionForm').reset();
            document.getElementById('questionId').value = '';
            document.getElementById('submitBtn').style.display = 'block';
            document.getElementById('updateBtn').style.display = 'none';
            document.getElementById('questionModal').style.display = 'block';
        }

        function editQuestion(question) {
            document.getElementById('modalTitle').textContent = 'Edit Question';
            document.getElementById('questionId').value = question.id;
            document.getElementById('category_id').value = question.category_id;
            document.getElementById('question').value = question.question_text;
            document.getElementById('opt_a').value = question.option_a;
            document.getElementById('opt_b').value = question.option_b;
            document.getElementById('opt_c').value = question.option_c;
            document.getElementById('opt_d').value = question.option_d;
            document.getElementById('correct').value = question.correct_answer;
            document.getElementById('explanation').value = question.explanation || '';
            document.getElementById('difficulty').value = question.difficulty || 'medium';
            
            document.getElementById('submitBtn').style.display = 'none';
            document.getElementById('updateBtn').style.display = 'block';
            document.getElementById('questionModal').style.display = 'block';
        }

        function viewQuestion(id) {
            document.getElementById('viewContent').innerHTML = '<p style="text-align: center; padding: 20px;">Loading question details...</p>';
            document.getElementById('viewModal').style.display = 'block';
            
            // You can implement AJAX here to load full question details
            setTimeout(() => {
                document.getElementById('viewContent').innerHTML = `
                    <div style="padding: 20px;">
                        <p><strong>Question ID:</strong> #${id}</p>
                        <p><strong>Details:</strong> Full question view coming soon!</p>
                    </div>
                `;
            }, 500);
        }

        function deleteQuestion(id) {
            if (confirm('Are you sure you want to delete this question? This action cannot be undone.')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `<input type="hidden" name="id" value="${id}"><input type="hidden" name="delete" value="1">`;
                document.body.appendChild(form);
                form.submit();
            }
        }

        function deleteSelected() {
            const checkboxes = document.querySelectorAll('input[name="question_ids[]"]:checked');
            if (checkboxes.length === 0) {
                alert('Please select at least one question to delete.');
                return;
            }
            
            if (confirm(`Are you sure you want to delete ${checkboxes.length} selected questions?`)) {
                document.getElementById('bulkDeleteForm').submit();
            }
        }

        function toggleAll(source) {
            const checkboxes = document.querySelectorAll('input[name="question_ids[]"]');
            checkboxes.forEach(cb => cb.checked = source.checked);
            updateSelectedCount();
        }

        function updateSelectedCount() {
            const checkboxes = document.querySelectorAll('input[name="question_ids[]"]:checked');
            const count = checkboxes.length;
            const deleteBtn = document.getElementById('deleteSelectedBtn');
            
            if (count > 0) {
                deleteBtn.style.display = 'inline-flex';
                document.getElementById('selectedCount').textContent = count;
            } else {
                deleteBtn.style.display = 'none';
            }
        }

        function closeModal() {
            document.getElementById('questionModal').style.display = 'none';
        }

        function closeViewModal() {
            document.getElementById('viewModal').style.display = 'none';
        }

        function showImportModal() {
            document.getElementById('importModal').style.display = 'block';
        }

        function closeImportModal() {
            document.getElementById('importModal').style.display = 'none';
        }

        function applyFilters() {
            const category = document.getElementById('categoryFilter').value;
            const difficulty = document.getElementById('difficultyFilter').value;
            const search = document.getElementById('searchInput').value;
            
            window.location.href = `questions.php?category=${category}&difficulty=${difficulty}&search=${encodeURIComponent(search)}`;
        }

        function clearFilters() {
            window.location.href = 'questions.php';
        }

        // Close modals when clicking outside
        window.onclick = function(event) {
            const modals = ['questionModal', 'viewModal', 'importModal'];
            modals.forEach(modalId => {
                const modal = document.getElementById(modalId);
                if (event.target == modal) {
                    modal.style.display = 'none';
                }
            });
        }

        // Initialize selected count on page load
        document.addEventListener('DOMContentLoaded', function() {
            updateSelectedCount();
        });

        // Keyboard shortcut for new question (Ctrl+N)
        document.addEventListener('keydown', function(e) {
            if (e.ctrlKey && e.key === 'n') {
                e.preventDefault();
                showAddModal();
            }
        });
    </script>
</body>
</html>
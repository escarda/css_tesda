<?php
// manage_questions.php - Dashboard-aligned design
include "../config.php";

// Check if admin is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: /proj/login.php");
    exit();
}

// Define base path for all links
$base = '/proj';

// Function to check if column exists
function columnExists($conn, $table, $column) {
    $result = $conn->query("SHOW COLUMNS FROM $table LIKE '$column'");
    return $result->num_rows > 0;
}

// Check and add missing columns if needed
if (!columnExists($conn, 'questions', 'coc_number')) {
    $conn->query("ALTER TABLE questions ADD COLUMN coc_number INT DEFAULT 1");
}
if (!columnExists($conn, 'questions', 'category')) {
    $conn->query("ALTER TABLE questions ADD COLUMN category VARCHAR(50) DEFAULT 'general'");
}
if (!columnExists($conn, 'questions', 'is_active')) {
    $conn->query("ALTER TABLE questions ADD COLUMN is_active TINYINT DEFAULT 1");
}

// Handle question deletion
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $stmt = $conn->prepare("DELETE FROM questions WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        $_SESSION['message'] = "Question deleted successfully!";
        $_SESSION['message_type'] = "success";
    } else {
        $_SESSION['message'] = "Error deleting question: " . $conn->error;
        $_SESSION['message_type'] = "danger";
    }
    $stmt->close();
    header("Location: /proj/admin/manage_questions.php");
    exit();
}

// Handle status toggle
if (isset($_GET['toggle'])) {
    $id = intval($_GET['toggle']);
    $stmt = $conn->prepare("SELECT is_active FROM questions WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $new_status = $row['is_active'] == 1 ? 0 : 1;
        $stmt->close();
        $stmt = $conn->prepare("UPDATE questions SET is_active = ? WHERE id = ?");
        $stmt->bind_param("ii", $new_status, $id);
        $stmt->execute();
        $_SESSION['message'] = "Question status updated!";
        $_SESSION['message_type'] = "success";
    }
    $stmt->close();
    header("Location: /proj/admin/manage_questions.php");
    exit();
}

// Pagination and filtering
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$limit = 12;
$offset = ($page - 1) * $limit;

$search = isset($_GET['search']) ? $_GET['search'] : '';
$coc_filter = isset($_GET['coc']) ? $_GET['coc'] : '';
$category_filter = isset($_GET['category']) ? $_GET['category'] : '';

$where_clauses = [];
$params = [];
$types = "";

if (!empty($search)) {
    $where_clauses[] = "(question_text LIKE ? OR option_a LIKE ? OR option_b LIKE ? OR option_c LIKE ? OR option_d LIKE ?)";
    $search_param = "%$search%";
    for ($i = 0; $i < 5; $i++) {
        $params[] = $search_param;
        $types .= "s";
    }
}
if (!empty($coc_filter)) {
    $where_clauses[] = "coc_number = ?";
    $params[] = $coc_filter;
    $types .= "i";
}
if (!empty($category_filter)) {
    $where_clauses[] = "category = ?";
    $params[] = $category_filter;
    $types .= "s";
}

$where_sql = !empty($where_clauses) ? "WHERE " . implode(" AND ", $where_clauses) : "";

// Get total count
$count_sql = "SELECT COUNT(*) as total FROM questions $where_sql";
$count_stmt = $conn->prepare($count_sql);
if (!empty($params)) {
    $count_stmt->bind_param($types, ...$params);
}
$count_stmt->execute();
$total_questions = $count_stmt->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_questions / $limit);
$count_stmt->close();

// Get questions
$sql = "SELECT id, question_text, option_a, option_b, option_c, option_d, correct_answer, 
               explanation, coc_number, category, is_active 
        FROM questions $where_sql ORDER BY coc_number ASC, id DESC LIMIT ? OFFSET ?";
$params[] = $limit;
$params[] = $offset;
$types .= "ii";
$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$questions = $stmt->get_result();
$stmt->close();

// Get filter counts
$coc_counts = [];
$coc_result = $conn->query("SELECT coc_number, COUNT(*) as count FROM questions GROUP BY coc_number");
while ($row = $coc_result->fetch_assoc()) {
    $coc_counts[$row['coc_number']] = $row['count'];
}
$category_counts = [];
$cat_result = $conn->query("SELECT category, COUNT(*) as count FROM questions WHERE category IS NOT NULL AND category != '' GROUP BY category");
while ($row = $cat_result->fetch_assoc()) {
    $category_counts[$row['category']] = $row['count'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Questions | CSS TESDA Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&family=Rajdhani:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <script>
        // Apply saved theme IMMEDIATELY (before render) to avoid flash
        (function() {
            var savedTheme = localStorage.getItem('theme') || 'dark';
            if (savedTheme === 'light') {
                document.documentElement.setAttribute('data-theme', 'light');
            }
        })();
    </script>

    <style>
        :root {
            --bg-primary: #0a0f1e;
            --bg-secondary: #050812;
            --bg-sidebar: rgba(10, 20, 40, 0.9);
            --bg-card: rgba(0, 0, 0, 0.3);
            --bg-card-hover: rgba(0, 0, 0, 0.4);
            --bg-input: rgba(0, 0, 0, 0.4);
            --bg-pagination: rgba(0, 0, 0, 0.3);

            --text-primary: #e0e0ff;
            --text-secondary: #8899cc;
            --text-muted: #94a3b8;
            --text-dark: #050812;

            --border-color: rgba(0, 255, 157, 0.2);
            --border-light: rgba(0, 255, 157, 0.1);
            --border-strong: rgba(0, 255, 157, 0.3);

            --cyber-bg1: #0a0f1e;
            --cyber-bg2: #050812;
            --grid-color: rgba(0, 255, 157, 0.03);

            --accent: #00ff9d;
            --accent-blue: #0066ff;

            --scrollbar-track: #1a1a2e;
            --scrollbar-thumb: #00ff9d;
        }

        [data-theme="light"] {
            --bg-primary: #d5dce6;
            --bg-secondary: #c8d0dd;
            --bg-sidebar: rgba(240, 244, 248, 0.95);
            --bg-card: rgba(255, 255, 255, 0.92);
            --bg-card-hover: rgba(255, 255, 255, 0.98);
            --bg-input: rgba(255, 255, 255, 0.95);
            --bg-pagination: rgba(255, 255, 255, 0.9);

            --text-primary: #1a2332;
            --text-secondary: #3d4a5e;
            --text-muted: #5a6b7e;
            --text-dark: #1a2332;

            --border-color: rgba(0, 160, 100, 0.35);
            --border-light: rgba(0, 160, 100, 0.2);
            --border-strong: rgba(0, 160, 100, 0.5);

            --cyber-bg1: #d5dce6;
            --cyber-bg2: #c8d0dd;
            --grid-color: rgba(0, 160, 100, 0.06);

            --accent: #00aa77;
            --accent-blue: #0066cc;

            --scrollbar-track: #c8d0dd;
            --scrollbar-thumb: #00aa77;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Rajdhani', sans-serif;
            background: var(--bg-primary);
            color: var(--text-primary);
            overflow-x: hidden;
            transition: background 0.3s ease, color 0.3s ease;
        }

        .cyber-bg {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            z-index: -2;
            background: linear-gradient(135deg, var(--cyber-bg1) 0%, var(--cyber-bg2) 100%);
            transition: background 0.3s ease;
        }

        .grid-overlay {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background-image: linear-gradient(var(--grid-color) 1px, transparent 1px),
                              linear-gradient(90deg, var(--grid-color) 1px, transparent 1px);
            background-size: 50px 50px; z-index: -1;
            animation: gridMove 20s linear infinite;
        }

        @keyframes gridMove {
            0% { transform: translate(0, 0); }
            100% { transform: translate(50px, 50px); }
        }

        .dashboard-container { display: flex; min-height: 100vh; }

        .sidebar {
            width: 280px;
            background: var(--bg-sidebar);
            backdrop-filter: blur(10px);
            border-right: 1px solid var(--border-color);
            padding: 2rem 1.5rem;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            z-index: 10;
            transition: background 0.3s ease, border 0.3s ease;
        }

        .logo {
            font-family: 'Orbitron', sans-serif;
            font-size: 1.4rem;
            font-weight: 800;
            background: linear-gradient(135deg, var(--accent), var(--accent-blue));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 2rem;
            text-align: center;
        }

        /* ============================================
           THEME TOGGLE BUTTON
           ============================================ */
        .theme-toggle-btn {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 15px;
            background: rgba(0, 102, 255, 0.08);
            border: 1px solid rgba(0, 102, 255, 0.25);
            border-radius: 10px;
            margin-bottom: 1.5rem;
            cursor: pointer;
            transition: all 0.3s ease;
            user-select: none;
        }

        .theme-toggle-btn:hover {
            background: rgba(0, 102, 255, 0.15);
            border-color: var(--accent-blue);
            transform: scale(1.02);
        }

        .theme-toggle-btn:active { transform: scale(0.97); }

        .theme-toggle-btn i {
            color: var(--accent-blue);
            font-size: 1.2rem;
            width: 24px;
            text-align: center;
            transition: transform 0.4s ease;
        }

        .theme-toggle-btn:hover i { transform: rotate(20deg); }

        .theme-toggle-btn .theme-text {
            color: var(--text-secondary);
            font-size: 0.9rem;
            flex: 1;
        }

        .theme-toggle-btn .theme-badge {
            font-family: 'Orbitron', sans-serif;
            font-size: 0.75rem;
            color: var(--accent-blue);
            background: rgba(0, 102, 255, 0.1);
            padding: 2px 10px;
            border-radius: 20px;
            transition: all 0.3s ease;
        }

        .nav-menu { list-style: none; }
        .nav-item { margin-bottom: 0.5rem; }
        .nav-link {
            display: flex; align-items: center; gap: 12px;
            padding: 12px 16px;
            color: var(--text-muted);
            text-decoration: none;
            border-radius: 12px;
            transition: all 0.3s;
        }
        .nav-link:hover, .nav-link.active {
            background: rgba(0, 255, 157, 0.1);
            color: var(--accent);
        }
        .nav-link i { width: 24px; }

        .main-content { flex: 1; margin-left: 280px; padding: 2rem; }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .page-header h1 {
            font-family: 'Orbitron', sans-serif;
            font-size: 1.8rem;
            background: linear-gradient(135deg, var(--accent), var(--accent-blue));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .btn {
            padding: 10px 24px;
            border: none;
            border-radius: 50px;
            font-family: 'Orbitron', sans-serif;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .btn-primary {
            background: linear-gradient(45deg, var(--accent), var(--accent-blue));
            color: var(--text-dark);
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 255, 157, 0.4);
        }
        .btn-danger { background: #dc2626; color: white; }
        .btn-danger:hover { background: #b91c1c; }
        .btn-warning { background: #f59e0b; color: var(--text-dark); }
        .btn-sm { padding: 5px 12px; font-size: 0.75rem; }

        .filter-card {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 20px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            transition: background 0.3s ease, border 0.3s ease;
        }
        .filter-form {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            align-items: flex-end;
        }
        .filter-group { flex: 1; min-width: 160px; }
        .filter-group label {
            display: block;
            margin-bottom: 5px;
            font-size: 0.8rem;
            color: var(--text-secondary);
        }
        .filter-group input, .filter-group select {
            width: 100%;
            padding: 8px 12px;
            background: var(--bg-input);
            border: 1px solid var(--border-strong);
            border-radius: 10px;
            color: var(--text-primary);
            font-family: 'Rajdhani', sans-serif;
            transition: background 0.3s ease, color 0.3s ease, border 0.3s ease;
        }

        .table-card {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 20px;
            overflow: hidden;
            transition: background 0.3s ease, border 0.3s ease;
        }
        .table-container { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; }
        th, td {
            padding: 14px 16px;
            text-align: left;
            border-bottom: 1px solid var(--border-light);
        }
        th {
            background: rgba(0, 255, 157, 0.05);
            font-family: 'Orbitron', sans-serif;
            font-weight: 600;
            color: var(--accent);
        }
        tr:hover { background: rgba(0, 255, 157, 0.02); }

        .badge {
            padding: 4px 12px;
            border-radius: 50px;
            font-size: 0.7rem;
            font-weight: 600;
        }
        .badge-active { background: rgba(0, 255, 157, 0.2); color: var(--accent); }
        .badge-inactive { background: rgba(220, 38, 38, 0.2); color: #ef4444; }
        .badge-coc { background: rgba(59, 130, 246, 0.2); color: #60a5fa; }

        .question-preview {
            max-width: 280px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .action-buttons { display: flex; gap: 8px; }

        .pagination {
            display: flex;
            justify-content: center;
            gap: 8px;
            margin-top: 1.5rem;
            padding-bottom: 0.5rem;
        }
        .pagination a, .pagination span {
            padding: 8px 14px;
            background: var(--bg-pagination);
            border-radius: 10px;
            text-decoration: none;
            color: var(--text-primary);
            transition: all 0.3s;
        }
        .pagination a:hover, .pagination .active {
            background: var(--accent);
            color: var(--text-dark);
        }

        .alert {
            padding: 12px 20px;
            border-radius: 12px;
            margin-bottom: 1.5rem;
        }
        .alert-success {
            background: rgba(0, 255, 157, 0.1);
            border: 1px solid var(--accent);
            color: var(--accent);
        }
        .alert-danger {
            background: rgba(220, 38, 38, 0.1);
            border: 1px solid #dc2626;
            color: #ef4444;
        }

        .stats-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
            color: var(--text-secondary);
        }

        ::-webkit-scrollbar { width: 10px; }
        ::-webkit-scrollbar-track {
            background: var(--scrollbar-track);
            border-radius: 10px;
        }
        ::-webkit-scrollbar-thumb {
            background: var(--scrollbar-thumb);
            border-radius: 10px;
        }

        @media (max-width: 768px) {
            .sidebar { width: 80px; padding: 1rem 0.5rem; }
            .sidebar .logo, .sidebar .nav-link span, .sidebar .theme-toggle-btn .theme-text, .sidebar .theme-toggle-btn .theme-badge { display: none; }
            .main-content { margin-left: 80px; }
            .nav-link { justify-content: center; }
            .theme-toggle-btn { justify-content: center; }
        }
    </style>
</head>
<body>
    <div class="cyber-bg"></div>
    <div class="grid-overlay"></div>

    <div class="dashboard-container">
        <!-- Sidebar with ABSOLUTE links -->
        <aside class="sidebar">
            <div class="logo">CSS Admin</div>

            <!-- Theme Toggle Button -->
            <div class="theme-toggle-btn" id="themeToggle" title="Toggle dark/light mode">
                <i class="fas fa-moon" id="themeIcon"></i>
                <span class="theme-text">Theme</span>
                <span class="theme-badge" id="themeBadge">Dark</span>
            </div>

            <ul class="nav-menu">
                <li class="nav-item"><a href="/proj/dashboard.php" class="nav-link"><i class="fas fa-tachometer-alt"></i><span>Dashboard</span></a></li>
                <li class="nav-item"><a href="/proj/admin/manage_questions.php" class="nav-link active"><i class="fas fa-question-circle"></i><span>Questions</span></a></li>
                <li class="nav-item"><a href="/proj/admin/manage_users.php" class="nav-link"><i class="fas fa-users"></i><span>Users</span></a></li>
                <li class="nav-item"><a href="/proj/admin/view_results.php" class="nav-link"><i class="fas fa-chart-line"></i><span>View Results</span></a></li>
                <li class="nav-item"><a href="/proj/logout.php" class="nav-link"><i class="fas fa-sign-out-alt"></i><span>Logout</span></a></li>
            </ul>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <div class="page-header">
                <h1><i class="fas fa-question-circle"></i> Manage Questions</h1>
                <a href="/proj/admin/add_question.php" class="btn btn-primary"><i class="fas fa-plus"></i> Add Question</a>
            </div>

            <?php if (isset($_SESSION['message'])): ?>
                <div class="alert alert-<?php echo $_SESSION['message_type']; ?>">
                    <?php echo $_SESSION['message']; unset($_SESSION['message']); unset($_SESSION['message_type']); ?>
                </div>
            <?php endif; ?>

            <!-- Filter Card -->
            <div class="filter-card">
                <form method="GET" class="filter-form">
                    <div class="filter-group">
                        <label><i class="fas fa-search"></i> Search</label>
                        <input type="text" name="search" placeholder="Question text or options..." value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    <div class="filter-group">
                        <label><i class="fas fa-layer-group"></i> COC Number</label>
                        <select name="coc">
                            <option value="">All COCs</option>
                            <option value="1" <?php echo $coc_filter == '1' ? 'selected' : ''; ?>>COC 1: Install & Configure</option>
                            <option value="2" <?php echo $coc_filter == '2' ? 'selected' : ''; ?>>COC 2: Set-up Networks</option>
                            <option value="3" <?php echo $coc_filter == '3' ? 'selected' : ''; ?>>COC 3: Set-up Servers</option>
                            <option value="4" <?php echo $coc_filter == '4' ? 'selected' : ''; ?>>COC 4: Maintain & Repair</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label><i class="fas fa-tag"></i> Category</label>
                        <select name="category">
                            <option value="">All Categories</option>
                            <option value="hardware" <?php echo $category_filter == 'hardware' ? 'selected' : ''; ?>>Hardware</option>
                            <option value="networking" <?php echo $category_filter == 'networking' ? 'selected' : ''; ?>>Networking</option>
                            <option value="software" <?php echo $category_filter == 'software' ? 'selected' : ''; ?>>Software</option>
                            <option value="troubleshooting" <?php echo $category_filter == 'troubleshooting' ? 'selected' : ''; ?>>Troubleshooting</option>
                            <option value="safety" <?php echo $category_filter == 'safety' ? 'selected' : ''; ?>>Safety</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label>&nbsp;</label>
                        <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-filter"></i> Apply</button>
                        <a href="/proj/admin/manage_questions.php" class="btn btn-warning btn-sm"><i class="fas fa-undo"></i> Reset</a>
                    </div>
                </form>
            </div>

            <!-- Stats Row -->
            <div class="stats-row">
                <span><i class="fas fa-database"></i> Total Questions: <?php echo $total_questions; ?></span>
                <span><i class="fas fa-chart-line"></i> Page <?php echo $page; ?> of <?php echo $total_pages ?: 1; ?></span>
            </div>

            <!-- Table Card -->
            <div class="table-card">
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Question</th>
                                <th>COC</th>
                                <th>Category</th>
                                <th>Correct</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($questions->num_rows > 0): ?>
                                <?php while ($row = $questions->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo $row['id']; ?></td>
                                        <td class="question-preview" title="<?php echo htmlspecialchars($row['question_text']); ?>">
                                            <?php echo htmlspecialchars(substr($row['question_text'], 0, 55)) . (strlen($row['question_text']) > 55 ? '...' : ''); ?>
                                        </td>
                                        <td><span class="badge badge-coc">COC <?php echo $row['coc_number']; ?></span></td>
                                        <td><?php echo htmlspecialchars(ucfirst($row['category'] ?? 'General')); ?></td>
                                        <td><strong><?php echo strtoupper($row['correct_answer']); ?></strong></td>
                                        <td>
                                            <span class="badge <?php echo $row['is_active'] ? 'badge-active' : 'badge-inactive'; ?>">
                                                <?php echo $row['is_active'] ? 'Active' : 'Inactive'; ?>
                                            </span>
                                        </td>
                                        <td class="action-buttons">
                                            <a href="/proj/admin/edit_question.php?id=<?php echo $row['id']; ?>" class="btn btn-primary btn-sm"><i class="fas fa-edit"></i></a>
                                            <a href="?toggle=<?php echo $row['id']; ?>" class="btn btn-warning btn-sm" onclick="return confirm('Toggle status?')"><i class="fas fa-power-off"></i></a>
                                            <a href="?delete=<?php echo $row['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Delete this question?')"><i class="fas fa-trash"></i></a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" style="text-align: center; padding: 40px;">
                                        <i class="fas fa-inbox" style="font-size: 48px; opacity: 0.5;"></i>
                                        <p>No questions found. <a href="/proj/admin/add_question.php">Add your first question</a></p>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?php echo $page-1; ?>&search=<?php echo urlencode($search); ?>&coc=<?php echo $coc_filter; ?>&category=<?php echo urlencode($category_filter); ?>">&laquo; Prev</a>
                    <?php endif; ?>
                    <?php for ($i = max(1, $page-2); $i <= min($total_pages, $page+2); $i++): ?>
                        <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&coc=<?php echo $coc_filter; ?>&category=<?php echo urlencode($category_filter); ?>" class="<?php echo $i == $page ? 'active' : ''; ?>"><?php echo $i; ?></a>
                    <?php endfor; ?>
                    <?php if ($page < $total_pages): ?>
                        <a href="?page=<?php echo $page+1; ?>&search=<?php echo urlencode($search); ?>&coc=<?php echo $coc_filter; ?>&category=<?php echo urlencode($category_filter); ?>">Next &raquo;</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </main>
    </div>

    <script>
        // ============================================
        // THEME TOGGLE — Dark / Light mode
        // ============================================
        (function() {
            var themeToggle = document.getElementById('themeToggle');
            var themeIcon = document.getElementById('themeIcon');
            var themeBadge = document.getElementById('themeBadge');

            function applyTheme(theme) {
                if (theme === 'light') {
                    document.documentElement.setAttribute('data-theme', 'light');
                    themeIcon.className = 'fas fa-sun';
                    themeBadge.textContent = 'Light';
                } else {
                    document.documentElement.removeAttribute('data-theme');
                    themeIcon.className = 'fas fa-moon';
                    themeBadge.textContent = 'Dark';
                }
                localStorage.setItem('theme', theme);
            }

            // Apply the already-saved theme on load
            var savedTheme = localStorage.getItem('theme') || 'dark';
            applyTheme(savedTheme);

            // Toggle on click
            themeToggle.addEventListener('click', function() {
                var current = localStorage.getItem('theme') || 'dark';
                applyTheme(current === 'dark' ? 'light' : 'dark');
            });
        })();
    </script>
    <?php include __DIR__ . '/../includes/theme_toggle.php'; ?>
</body>
</html>
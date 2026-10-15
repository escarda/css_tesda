<?php
// add_question.php - Dashboard-aligned design (readable text + responsive sidebar)
require_once '../config.php';

// Base path is detected automatically (works at domain root or in a /proj folder)
$base = rtrim(str_replace('\\', '/', dirname(dirname($_SERVER['SCRIPT_NAME']))), '/');
if ($base === '.' || $base === '/') { $base = ''; }

// Check if admin is logged in using the isAdmin() function
if (!isLoggedIn() || !isAdmin()) {
    redirect($base . '/login.php');
}

// Handle form submission
$error = '';
$success = '';

// Check if questions table exists, if not create it
$tableCheck = $conn->query("SHOW TABLES LIKE 'questions'");
if ($tableCheck->num_rows == 0) {
    $createTable = "
    CREATE TABLE IF NOT EXISTS `questions` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `question_text` text NOT NULL,
        `option_a` varchar(255) NOT NULL,
        `option_b` varchar(255) NOT NULL,
        `option_c` varchar(255) NOT NULL,
        `option_d` varchar(255) NOT NULL,
        `correct_answer` char(1) NOT NULL,
        `explanation` text,
        `coc_number` int(1) NOT NULL DEFAULT 1,
        `category` varchar(50) NOT NULL DEFAULT 'general',
        `is_active` tinyint(1) NOT NULL DEFAULT 1,
        `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ";
    $conn->query($createTable);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $question_text = trim($_POST['question_text'] ?? '');
    $option_a = trim($_POST['option_a'] ?? '');
    $option_b = trim($_POST['option_b'] ?? '');
    $option_c = trim($_POST['option_c'] ?? '');
    $option_d = trim($_POST['option_d'] ?? '');
    $correct_answer = strtoupper(trim($_POST['correct_answer'] ?? ''));
    $explanation = trim($_POST['explanation'] ?? '');
    $coc_number = intval($_POST['coc_number'] ?? 1);
    $category = trim($_POST['category'] ?? 'general');
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    // Validation
    if (empty($question_text)) {
        $error = "Question text is required.";
    } elseif (empty($option_a) || empty($option_b) || empty($option_c) || empty($option_d)) {
        $error = "All four options are required.";
    } elseif (!in_array($correct_answer, ['A', 'B', 'C', 'D'])) {
        $error = "Correct answer must be A, B, C, or D.";
    } elseif ($coc_number < 1 || $coc_number > 4) {
        $error = "COC number must be between 1 and 4.";
    } else {
        $stmt = $conn->prepare("
            INSERT INTO questions (question_text, option_a, option_b, option_c, option_d, correct_answer, explanation, coc_number, category, is_active) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param("sssssssisi", $question_text, $option_a, $option_b, $option_c, $option_d, $correct_answer, $explanation, $coc_number, $category, $is_active);

        if ($stmt->execute()) {
            $success = "Question added successfully!";
            $_POST = array(); // Clear form data
        } else {
            $error = "Error adding question: " . $conn->error;
        }
        $stmt->close();
    }
}

$site_name = defined('SITE_NAME') ? SITE_NAME : 'CSS TESDA Reviewer';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Add Question | <?php echo htmlspecialchars($site_name); ?></title>
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
        /* All colors are defined here, so text is always readable */
        :root {
            color-scheme: dark;
            --bg-primary: #0a0f1e;
            --bg-sidebar: #0b1428;
            --bg-card: rgba(10, 20, 40, 0.85);
            --bg-input: #0d1730;
            --bg-select-option: #0d1730;

            --text-primary: #e8eeff;
            --text-secondary: #a9b8e0;
            --text-muted: #7f8fb5;
            --input-text: #ffffff;
            --text-dark: #050812;

            --border-color: rgba(0, 255, 157, 0.25);
            --border-strong: rgba(0, 255, 157, 0.4);
            --shadow-color: rgba(0, 0, 0, 0.35);

            --cyber-bg1: #0a0f1e;
            --cyber-bg2: #050812;
            --grid-color: rgba(0, 255, 157, 0.03);

            --accent: #00ff9d;
            --accent-blue: #0066ff;
        }

        [data-theme="light"] {
            color-scheme: light;
            --bg-primary: #d5dce6;
            --bg-sidebar: #f0f4f8;
            --bg-card: rgba(255, 255, 255, 0.95);
            --bg-input: #ffffff;
            --bg-select-option: #ffffff;

            --text-primary: #1a2332;
            --text-secondary: #3d4a5e;
            --text-muted: #6b7a8e;
            --input-text: #1a2332;
            --text-dark: #1a2332;

            --border-color: rgba(0, 160, 100, 0.35);
            --border-strong: rgba(0, 160, 100, 0.55);
            --shadow-color: rgba(0, 0, 0, 0.12);

            --cyber-bg1: #d5dce6;
            --cyber-bg2: #c8d0dd;
            --grid-color: rgba(0, 160, 100, 0.06);

            --accent: #00aa77;
            --accent-blue: #0066cc;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Rajdhani', sans-serif;
            background: var(--bg-primary);
            color: var(--text-primary);
            overflow-x: hidden;
            transition: background 0.3s ease, color 0.3s ease;
        }

        body.menu-open { overflow: hidden; }

        .cyber-bg {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            z-index: -2;
            background: linear-gradient(135deg, var(--cyber-bg1) 0%, var(--cyber-bg2) 100%);
        }

        .grid-overlay {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background-image: linear-gradient(var(--grid-color) 1px, transparent 1px),
                              linear-gradient(90deg, var(--grid-color) 1px, transparent 1px);
            background-size: 50px 50px;
            z-index: -1;
            animation: gridMove 20s linear infinite;
            pointer-events: none;
        }

        @keyframes gridMove {
            0% { transform: translate(0, 0); }
            100% { transform: translate(50px, 50px); }
        }

        .dashboard-container { display: flex; min-height: 100vh; }

        /* ---------- Sidebar ---------- */
        .sidebar {
            width: 280px;
            background: var(--bg-sidebar);
            border-right: 1px solid var(--border-color);
            padding: 2rem 1.5rem;
            position: fixed;
            top: 0; left: 0; bottom: 0;
            height: 100%;
            overflow-y: auto;
            -webkit-overflow-scrolling: touch;
            z-index: 1000;
            transition: transform 0.3s ease, background 0.3s ease;
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

        .sidebar-close {
            display: none;
            position: absolute;
            top: 12px; right: 12px;
            width: 38px; height: 38px;
            border: 0;
            background: transparent;
            color: var(--text-secondary);
            font-size: 1.6rem;
            line-height: 1;
            cursor: pointer;
        }

        .theme-toggle-btn {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 15px;
            background: rgba(0, 102, 255, 0.08);
            border: 1px solid rgba(0, 102, 255, 0.25);
            border-radius: 10px;
            margin-bottom: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            user-select: none;
        }
        .theme-toggle-btn:hover { background: rgba(0, 102, 255, 0.15); border-color: var(--accent-blue); }
        .theme-toggle-btn i { color: var(--accent-blue); font-size: 1.2rem; width: 24px; text-align: center; }
        .theme-toggle-btn .theme-text { color: var(--text-secondary); font-size: 0.9rem; flex: 1; }
        .theme-toggle-btn .theme-badge {
            font-family: 'Orbitron', sans-serif;
            font-size: 0.75rem;
            color: var(--accent-blue);
            background: rgba(0, 102, 255, 0.1);
            padding: 2px 10px;
            border-radius: 20px;
        }

        .brightness-box {
            padding: 12px 15px;
            background: rgba(255, 170, 0, 0.08);
            border: 1px solid rgba(255, 170, 0, 0.3);
            border-radius: 10px;
            margin-bottom: 1.5rem;
        }
        .brightness-head { display: flex; align-items: center; gap: 12px; margin-bottom: 10px; }
        .brightness-head i { color: #f59e0b; font-size: 1.2rem; width: 24px; text-align: center; }
        .brightness-head .brightness-text { color: var(--text-secondary); font-size: 0.9rem; flex: 1; }
        .brightness-head .brightness-badge {
            font-family: 'Orbitron', sans-serif;
            font-size: 0.75rem;
            color: #f59e0b;
            background: rgba(255, 170, 0, 0.12);
            padding: 2px 10px;
            border-radius: 20px;
            min-width: 54px;
            text-align: center;
        }
        .brightness-slider {
            -webkit-appearance: none; appearance: none;
            width: 100%; height: 6px; border-radius: 6px;
            background: rgba(255, 170, 0, 0.3);
            outline: none; cursor: pointer;
        }
        .brightness-slider::-webkit-slider-thumb {
            -webkit-appearance: none; appearance: none;
            width: 22px; height: 22px; border-radius: 50%;
            background: #f59e0b; border: 2px solid #fff; cursor: pointer;
        }
        .brightness-slider::-moz-range-thumb {
            width: 22px; height: 22px; border-radius: 50%;
            background: #f59e0b; border: 2px solid #fff; cursor: pointer;
        }
        .brightness-reset {
            margin-top: 8px; width: 100%; padding: 6px;
            border: 1px solid rgba(255, 170, 0, 0.4);
            border-radius: 8px; background: transparent;
            color: #f59e0b; font-family: 'Rajdhani', sans-serif;
            font-weight: 600; cursor: pointer;
        }

        .brightness-dim, .brightness-glow {
            position: fixed; top: 0; right: 0; bottom: 0; left: 0;
            pointer-events: none; opacity: 0;
            z-index: 2147482000;
            transition: opacity 0.15s ease;
        }
        .brightness-dim { background: #000; }
        .brightness-glow { background: #fff; }

        .nav-menu { list-style: none; }
        .nav-item { margin-bottom: 0.5rem; }
        .nav-link {
            display: flex; align-items: center; gap: 12px;
            padding: 12px 16px;
            color: var(--text-secondary);
            text-decoration: none;
            border-radius: 12px;
            transition: all 0.3s;
        }
        .nav-link:hover, .nav-link.active { background: rgba(0, 255, 157, 0.1); color: var(--accent); }
        .nav-link i { width: 24px; }

        /* Mobile top bar + overlay */
        .mobile-topbar {
            display: none;
            position: fixed; top: 0; left: 0; right: 0;
            height: 56px;
            padding: 0 12px;
            padding-top: env(safe-area-inset-top, 0px);
            background: var(--bg-sidebar);
            border-bottom: 1px solid var(--border-color);
            align-items: center; gap: 12px;
            z-index: 900;
        }
        .menu-btn {
            width: 42px; height: 42px;
            border: 1px solid var(--border-strong);
            border-radius: 10px;
            background: rgba(0, 255, 157, 0.08);
            color: var(--accent);
            font-size: 1.2rem; cursor: pointer;
            -webkit-tap-highlight-color: transparent;
        }
        .mobile-title {
            font-family: 'Orbitron', sans-serif;
            font-size: 1rem; font-weight: 800;
            background: linear-gradient(135deg, var(--accent), var(--accent-blue));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .sidebar-overlay {
            display: none;
            position: fixed; top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(0, 0, 0, 0.6);
            z-index: 950;
        }
        .sidebar-overlay.show { display: block; }

        /* ---------- Main ---------- */
        .main-content { flex: 1; margin-left: 280px; padding: 2rem; min-width: 0; }

        .page-header {
            display: flex; justify-content: space-between; align-items: center;
            margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem;
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
            font-size: 0.9rem;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .btn-primary { background: linear-gradient(45deg, var(--accent), var(--accent-blue)); color: var(--text-dark); }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(0, 255, 157, 0.4); }
        .btn-secondary { background: transparent; border: 2px solid var(--accent); color: var(--accent); }
        .btn-secondary:hover { background: var(--accent); color: var(--text-dark); }

        /* Form card */
        .form-card {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 20px;
            padding: 2rem;
            box-shadow: 0 5px 20px var(--shadow-color);
        }
        .form-title {
            font-family: 'Orbitron', sans-serif;
            font-size: 1.3rem;
            color: var(--accent);
            margin-bottom: 1.5rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid var(--border-color);
        }
        .form-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 1.5rem; }
        .form-group { margin-bottom: 1rem; }
        .form-group.full-width { grid-column: span 2; }
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: var(--text-secondary);
            font-weight: 600;
        }
        .form-group label i { color: var(--accent); margin-right: 8px; }

        .form-group input[type="text"],
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px 16px;
            background: var(--bg-input);
            border: 1px solid var(--border-strong);
            border-radius: 12px;
            color: var(--input-text);
            -webkit-text-fill-color: var(--input-text);
            font-family: 'Rajdhani', sans-serif;
            font-size: 1rem;
            transition: all 0.3s;
        }
        .form-group select option { background: var(--bg-select-option); color: var(--input-text); }
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: var(--accent);
            box-shadow: 0 0 10px rgba(0, 255, 157, 0.25);
        }
        .form-group input::placeholder,
        .form-group textarea::placeholder {
            color: var(--text-muted);
            -webkit-text-fill-color: var(--text-muted);
            opacity: 1;
        }
        .form-group textarea { resize: vertical; min-height: 100px; }

        .options-row { display: grid; grid-template-columns: repeat(2, 1fr); gap: 1rem; }
        .option-input { display: flex; align-items: center; gap: 10px; }
        .option-prefix {
            width: 40px; height: 40px;
            background: rgba(0, 255, 157, 0.12);
            border: 1px solid var(--border-strong);
            border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            font-weight: bold; font-size: 1.2rem;
            color: var(--accent);
            flex-shrink: 0;
        }
        .option-input input { flex: 1; min-width: 0; }

        .radio-group { display: flex; gap: 20px; align-items: center; flex-wrap: wrap; }
        .radio-group label {
            display: flex; align-items: center; gap: 8px;
            cursor: pointer; margin-bottom: 0;
            color: var(--text-primary);
            font-size: 1.05rem;
        }
        .radio-group input[type="radio"] {
            width: 20px; height: 20px; margin: 0;
            accent-color: var(--accent);
        }

        .checkbox-group { display: flex; align-items: center; gap: 10px; }
        .checkbox-group input[type="checkbox"] { width: 20px; height: 20px; accent-color: var(--accent); }
        .checkbox-group label { margin-bottom: 0; cursor: pointer; color: var(--text-primary); }

        .form-actions {
            display: flex; gap: 1rem; justify-content: flex-end;
            margin-top: 2rem; padding-top: 1rem;
            border-top: 1px solid var(--border-color);
        }

        .alert {
            padding: 12px 20px; border-radius: 12px; margin-bottom: 1.5rem;
            display: flex; align-items: center; gap: 10px;
        }
        .alert-success { background: rgba(0, 255, 157, 0.12); border: 1px solid var(--accent); color: var(--accent); }
        .alert-danger { background: rgba(220, 38, 38, 0.12); border: 1px solid #dc2626; color: #ef4444; }
        .alert i { font-size: 1.2rem; }

        .tips-box {
            margin-top: 1.5rem;
            padding: 1.2rem 1.5rem;
            background: rgba(0, 255, 157, 0.07);
            border-radius: 12px;
            border-left: 3px solid var(--accent);
            color: var(--text-secondary);
        }
        .tips-box i { color: var(--accent); margin-right: 10px; }
        .tips-box strong { color: var(--accent); }
        .tips-box ul { margin-top: 8px; margin-left: 20px; color: var(--text-secondary); }
        .tips-box ul li { margin-bottom: 4px; }

        /* ---------- Responsive ---------- */
        @media (max-width: 1024px) {
            .form-grid { grid-template-columns: 1fr; }
            .form-group.full-width { grid-column: span 1; }
        }

        @media (max-width: 900px) {
            .mobile-topbar { display: flex; }
            .sidebar {
                width: 270px; max-width: 82vw;
                transform: translateX(-100%);
                padding-top: 3.5rem;
            }
            .sidebar.open { transform: translateX(0); box-shadow: 8px 0 40px rgba(0, 0, 0, 0.5); }
            .sidebar-close { display: block; }
            .main-content {
                margin-left: 0;
                padding: 1rem;
                padding-top: calc(56px + env(safe-area-inset-top, 0px) + 1rem);
            }
            .options-row { grid-template-columns: 1fr; }
            .page-header h1 { font-size: 1.3rem; }
            .form-card { padding: 1rem; }
        }

        @media (max-width: 480px) {
            .form-actions { flex-direction: column; }
            .form-actions .btn { width: 100%; justify-content: center; }
        }
    </style>
</head>
<body>
    <div class="cyber-bg"></div>
    <div class="grid-overlay"></div>
    <div class="brightness-dim" id="brightnessDim"></div>
    <div class="brightness-glow" id="brightnessGlow"></div>

    <!-- Mobile top bar -->
    <div class="mobile-topbar">
        <button type="button" class="menu-btn" id="menuBtn" aria-label="Open menu"><i class="fas fa-bars"></i></button>
        <span class="mobile-title">CSS Admin</span>
    </div>
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <div class="dashboard-container">
        <!-- Sidebar -->
        <aside class="sidebar" id="sidebar">
            <button type="button" class="sidebar-close" id="sidebarClose" aria-label="Close menu">&times;</button>
            <div class="logo">CSS Admin</div>

            <div class="theme-toggle-btn" id="themeToggle" title="Toggle dark/light mode">
                <i class="fas fa-moon" id="themeIcon"></i>
                <span class="theme-text">Theme</span>
                <span class="theme-badge" id="themeBadge">Dark</span>
            </div>

            <div class="brightness-box">
                <div class="brightness-head">
                    <i class="fas fa-sun"></i>
                    <span class="brightness-text">Brightness</span>
                    <span class="brightness-badge" id="brightnessBadge">100%</span>
                </div>
                <input type="range" class="brightness-slider" id="brightnessSlider" min="40" max="120" step="5" value="100" aria-label="Screen brightness">
                <button type="button" class="brightness-reset" id="brightnessReset"><i class="fas fa-undo"></i> Reset</button>
            </div>

            <ul class="nav-menu">
                <li class="nav-item"><a href="/dashboard.php" class="nav-link"><i class="fas fa-tachometer-alt"></i><span>Dashboard</span></a></li>
                <li class="nav-item"><a href="<?php echo $base; ?>/admin/manage_questions.php" class="nav-link"><i class="fas fa-question-circle"></i><span>Manage Questions</span></a></li>
                <li class="nav-item"><a href="<?php echo $base; ?>/admin/add_question.php" class="nav-link active"><i class="fas fa-plus-circle"></i><span>Add Question</span></a></li>
                <li class="nav-item"><a href="<?php echo $base; ?>/admin/manage_users.php" class="nav-link"><i class="fas fa-users"></i><span>Users</span></a></li>
                <li class="nav-item"><a href="<?php echo $base; ?>/admin/view_results.php" class="nav-link"><i class="fas fa-chart-line"></i><span>View Results</span></a></li>
                <li class="nav-item"><a href="<?php echo $base; ?>/logout.php" class="nav-link"><i class="fas fa-sign-out-alt"></i><span>Logout</span></a></li>
            </ul>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <div class="page-header">
                <h1><i class="fas fa-plus-circle"></i> Add New Question</h1>
                <a href="<?php echo $base; ?>/admin/manage_questions.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back to Questions</a>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>

            <!-- Add Question Form -->
            <div class="form-card">
                <div class="form-title">
                    <i class="fas fa-edit"></i> Question Details
                </div>

                <form method="POST" action="" id="questionForm">
                    <div class="form-grid">
                        <!-- Question Text -->
                        <div class="form-group full-width">
                            <label><i class="fas fa-question-circle"></i> Question Text</label>
                            <textarea name="question_text" placeholder="Enter the question here..." required><?php echo htmlspecialchars($_POST['question_text'] ?? ''); ?></textarea>
                        </div>

                        <!-- Options -->
                        <div class="form-group full-width">
                            <label><i class="fas fa-list"></i> Answer Options</label>
                            <div class="options-row">
                                <div class="option-input">
                                    <span class="option-prefix">A</span>
                                    <input type="text" name="option_a" placeholder="Option A" value="<?php echo htmlspecialchars($_POST['option_a'] ?? ''); ?>" required>
                                </div>
                                <div class="option-input">
                                    <span class="option-prefix">B</span>
                                    <input type="text" name="option_b" placeholder="Option B" value="<?php echo htmlspecialchars($_POST['option_b'] ?? ''); ?>" required>
                                </div>
                                <div class="option-input">
                                    <span class="option-prefix">C</span>
                                    <input type="text" name="option_c" placeholder="Option C" value="<?php echo htmlspecialchars($_POST['option_c'] ?? ''); ?>" required>
                                </div>
                                <div class="option-input">
                                    <span class="option-prefix">D</span>
                                    <input type="text" name="option_d" placeholder="Option D" value="<?php echo htmlspecialchars($_POST['option_d'] ?? ''); ?>" required>
                                </div>
                            </div>
                        </div>

                        <!-- Correct Answer -->
                        <div class="form-group">
                            <label><i class="fas fa-check-circle"></i> Correct Answer</label>
                            <div class="radio-group">
                                <label><input type="radio" name="correct_answer" value="A" <?php echo (($_POST['correct_answer'] ?? '') == 'A') ? 'checked' : ''; ?> required> A</label>
                                <label><input type="radio" name="correct_answer" value="B" <?php echo (($_POST['correct_answer'] ?? '') == 'B') ? 'checked' : ''; ?>> B</label>
                                <label><input type="radio" name="correct_answer" value="C" <?php echo (($_POST['correct_answer'] ?? '') == 'C') ? 'checked' : ''; ?>> C</label>
                                <label><input type="radio" name="correct_answer" value="D" <?php echo (($_POST['correct_answer'] ?? '') == 'D') ? 'checked' : ''; ?>> D</label>
                            </div>
                        </div>

                        <!-- COC Number -->
                        <div class="form-group">
                            <label><i class="fas fa-layer-group"></i> COC Number</label>
                            <select name="coc_number" required>
                                <option value="1" <?php echo (($_POST['coc_number'] ?? '') == '1') ? 'selected' : ''; ?>>COC 1: Install & Configure</option>
                                <option value="2" <?php echo (($_POST['coc_number'] ?? '') == '2') ? 'selected' : ''; ?>>COC 2: Set-up Networks</option>
                                <option value="3" <?php echo (($_POST['coc_number'] ?? '') == '3') ? 'selected' : ''; ?>>COC 3: Set-up Servers</option>
                                <option value="4" <?php echo (($_POST['coc_number'] ?? '') == '4') ? 'selected' : ''; ?>>COC 4: Maintain & Repair</option>
                            </select>
                        </div>

                        <!-- Category -->
                        <div class="form-group">
                            <label><i class="fas fa-tag"></i> Category</label>
                            <select name="category" required>
                                <option value="general" <?php echo (($_POST['category'] ?? '') == 'general') ? 'selected' : ''; ?>>General</option>
                                <option value="hardware" <?php echo (($_POST['category'] ?? '') == 'hardware') ? 'selected' : ''; ?>>Hardware</option>
                                <option value="networking" <?php echo (($_POST['category'] ?? '') == 'networking') ? 'selected' : ''; ?>>Networking</option>
                                <option value="software" <?php echo (($_POST['category'] ?? '') == 'software') ? 'selected' : ''; ?>>Software</option>
                                <option value="troubleshooting" <?php echo (($_POST['category'] ?? '') == 'troubleshooting') ? 'selected' : ''; ?>>Troubleshooting</option>
                                <option value="safety" <?php echo (($_POST['category'] ?? '') == 'safety') ? 'selected' : ''; ?>>Safety & Procedures</option>
                            </select>
                        </div>

                        <!-- Active Status -->
                        <div class="form-group">
                            <label><i class="fas fa-power-off"></i> Status</label>
                            <div class="checkbox-group">
                                <input type="checkbox" name="is_active" id="is_active" value="1" checked>
                                <label for="is_active">Active (visible to users)</label>
                            </div>
                        </div>

                        <!-- Explanation -->
                        <div class="form-group full-width">
                            <label><i class="fas fa-info-circle"></i> Explanation (Optional)</label>
                            <textarea name="explanation" placeholder="Add an explanation for why this answer is correct..."><?php echo htmlspecialchars($_POST['explanation'] ?? ''); ?></textarea>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="reset" class="btn btn-secondary"><i class="fas fa-undo"></i> Reset</button>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Question</button>
                    </div>
                </form>
            </div>

            <!-- Quick Tips -->
            <div class="tips-box">
                <i class="fas fa-lightbulb"></i>
                <strong>Quick Tips:</strong>
                <ul>
                    <li>Make sure all options are clear and distinct from each other</li>
                    <li>The correct answer should be exactly one of A, B, C, or D</li>
                    <li>Add explanations to help students learn from their mistakes</li>
                    <li>Inactive questions won't appear in quizzes</li>
                </ul>
            </div>
        </main>
    </div>

    <script>
        // Mobile sidebar (slide-in menu)
        (function() {
            var sidebar = document.getElementById('sidebar');
            var overlay = document.getElementById('sidebarOverlay');
            var menuBtn = document.getElementById('menuBtn');
            var closeBtn = document.getElementById('sidebarClose');

            function openMenu() {
                sidebar.classList.add('open');
                overlay.classList.add('show');
                document.body.classList.add('menu-open');
            }
            function closeMenu() {
                sidebar.classList.remove('open');
                overlay.classList.remove('show');
                document.body.classList.remove('menu-open');
            }
            menuBtn.addEventListener('click', openMenu);
            closeBtn.addEventListener('click', closeMenu);
            overlay.addEventListener('click', closeMenu);
            window.addEventListener('resize', function() { if (window.innerWidth > 900) closeMenu(); });
            document.addEventListener('keydown', function(e) { if (e.key === 'Escape') closeMenu(); });
        })();

        // Brightness control (40% - 120%)
        (function() {
            var slider = document.getElementById('brightnessSlider');
            var badge = document.getElementById('brightnessBadge');
            var resetBtn = document.getElementById('brightnessReset');
            var dim = document.getElementById('brightnessDim');
            var glow = document.getElementById('brightnessGlow');
            var KEY = 'css_tesda_brightness';

            function applyBrightness(value) {
                value = parseInt(value, 10);
                if (isNaN(value)) value = 100;
                value = Math.min(120, Math.max(40, value));
                if (value < 100) {
                    dim.style.opacity = (100 - value) / 100;
                    glow.style.opacity = 0;
                } else {
                    dim.style.opacity = 0;
                    glow.style.opacity = (value - 100) / 100 * 0.5;
                }
                slider.value = value;
                badge.textContent = value + '%';
                try { localStorage.setItem(KEY, value); } catch (e) {}
            }

            var saved = 100;
            try { saved = localStorage.getItem(KEY) || 100; } catch (e) {}
            applyBrightness(saved);
            slider.addEventListener('input', function() { applyBrightness(slider.value); });
            resetBtn.addEventListener('click', function() { applyBrightness(100); });
        })();

        // Theme toggle - Dark / Light mode
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

            applyTheme(localStorage.getItem('theme') || 'dark');
            themeToggle.addEventListener('click', function() {
                var current = localStorage.getItem('theme') || 'dark';
                applyTheme(current === 'dark' ? 'light' : 'dark');
            });
        })();

        // Auto-dismiss alerts after 5 seconds
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.alert').forEach(function(alert) {
                setTimeout(function() {
                    alert.style.transition = 'opacity 0.5s ease';
                    alert.style.opacity = '0';
                    setTimeout(function() { alert.remove(); }, 500);
                }, 5000);
            });
        });

        // Form validation
        document.getElementById('questionForm').addEventListener('submit', function(e) {
            var questionText = document.querySelector('textarea[name="question_text"]').value.trim();
            var optionA = document.querySelector('input[name="option_a"]').value.trim();
            var optionB = document.querySelector('input[name="option_b"]').value.trim();
            var optionC = document.querySelector('input[name="option_c"]').value.trim();
            var optionD = document.querySelector('input[name="option_d"]').value.trim();
            var correctAnswer = document.querySelector('input[name="correct_answer"]:checked');

            if (!questionText) { e.preventDefault(); alert('Please enter the question text.'); return; }
            if (!optionA || !optionB || !optionC || !optionD) { e.preventDefault(); alert('Please fill in all four options.'); return; }
            if (!correctAnswer) { e.preventDefault(); alert('Please select the correct answer.'); return; }
        });
    </script>
</body>
</html>
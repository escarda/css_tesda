<?php
// add_question.php - Dashboard-aligned design
require_once '../config.php';

// Check if admin is logged in using the isAdmin() function
if (!isLoggedIn() || !isAdmin()) {
    redirect('../login.php');
}

// Handle form submission
$error = '';
$success = '';

// Check if questions table exists, if not create it
$tableCheck = $conn->query("SHOW TABLES LIKE 'questions'");
if ($tableCheck->num_rows == 0) {
    // Create questions table
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
        // Insert into database
        $stmt = $conn->prepare("
            INSERT INTO questions (question_text, option_a, option_b, option_c, option_d, correct_answer, explanation, coc_number, category, is_active) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param("sssssssssi", $question_text, $option_a, $option_b, $option_c, $option_d, $correct_answer, $explanation, $coc_number, $category, $is_active);
        
        if ($stmt->execute()) {
            $success = "Question added successfully!";
            // Clear form data
            $_POST = array();
        } else {
            $error = "Error adding question: " . $conn->error;
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Question | <?php echo SITE_NAME; ?></title>
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
            background: var(--bg-primary);
            color: var(--text-primary);
            overflow-x: hidden;
        }

        /* Cyber Background */
        .cyber-bg {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -2;
            background: linear-gradient(135deg, var(--cyber-bg1) 0%, var(--cyber-bg2) 100%);
        }
        .grid-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: linear-gradient(var(--grid-color) 1px, transparent 1px), linear-gradient(90deg, var(--grid-color) 1px, transparent 1px);
            background-size: 50px 50px;
            z-index: -1;
            animation: gridMove 20s linear infinite;
        }
        @keyframes gridMove {
            0% { transform: translate(0, 0); }
            100% { transform: translate(50px, 50px); }
        }

        /* Dashboard Layout */
        .dashboard-container {
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar */
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
        }
        .logo {
            font-family: 'Orbitron', sans-serif;
            font-size: 1.4rem;
            font-weight: 800;
            background: linear-gradient(135deg, #00ff9d, #0066ff);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 2.5rem;
            text-align: center;
        }
        .nav-menu {
            list-style: none;
        }
        .nav-item {
            margin-bottom: 0.5rem;
        }
        .nav-link {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 16px;
            color: var(--text-secondary);
            text-decoration: none;
            border-radius: 12px;
            transition: all 0.3s;
        }
        .nav-link:hover, .nav-link.active {
            background: rgba(0, 255, 157, 0.1);
            color: #00ff9d;
        }
        .nav-link i {
            width: 24px;
        }

        /* Main Content */
        .main-content {
            flex: 1;
            margin-left: 280px;
            padding: 2rem;
            background: var(--bg-primary);
        }

        /* Page Header */
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
            background: linear-gradient(135deg, #00ff9d, #0066ff);
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
            font-size: 0.9rem;
        }
        .btn-primary {
            background: linear-gradient(45deg, #00ff9d, #0066ff);
            color: #050812;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 255, 157, 0.4);
        }
        .btn-secondary {
            background: transparent;
            border: 2px solid #00ff9d;
            color: #00ff9d;
        }
        .btn-secondary:hover {
            background: #00ff9d;
            color: #050812;
        }
        .btn-danger {
            background: transparent;
            border: 2px solid #dc2626;
            color: #dc2626;
        }
        .btn-danger:hover {
            background: #dc2626;
            color: #fff;
        }

        /* Form Card */
        .form-card {
            background: var(--bg-card);
            backdrop-filter: blur(10px);
            border: 1px solid var(--border-color);
            border-radius: 20px;
            padding: 2rem;
            box-shadow: 0 5px 20px var(--shadow-color);
        }
        .form-title {
            font-family: 'Orbitron', sans-serif;
            font-size: 1.3rem;
            color: #00ff9d;
            margin-bottom: 1.5rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid var(--border-color);
        }
        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1.5rem;
        }
        .form-group {
            margin-bottom: 1rem;
        }
        .form-group.full-width {
            grid-column: span 2;
        }
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: var(--text-secondary);
            font-weight: 600;
        }
        .form-group label i {
            color: #00ff9d;
            margin-right: 8px;
        }
        .form-group input, 
        .form-group select, 
        .form-group textarea {
            width: 100%;
            padding: 12px 16px;
            background: var(--bg-input);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            color: var(--input-text);
            font-family: 'Rajdhani', sans-serif;
            font-size: 1rem;
            transition: all 0.3s;
        }
        .form-group input:focus, 
        .form-group select:focus, 
        .form-group textarea:focus {
            outline: none;
            border-color: #00ff9d;
            box-shadow: 0 0 10px rgba(0, 255, 157, 0.2);
        }
        .form-group input::placeholder,
        .form-group textarea::placeholder {
            color: var(--text-muted);
            opacity: 0.7;
        }
        .form-group textarea {
            resize: vertical;
            min-height: 100px;
        }
        .options-row {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
        }
        .option-input {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .option-prefix {
            width: 40px;
            height: 40px;
            background: rgba(0, 255, 157, 0.1);
            border: 1px solid var(--border-color);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 1.2rem;
            color: #00ff9d;
            flex-shrink: 0;
        }
        .option-input input {
            flex: 1;
        }
        .radio-group {
            display: flex;
            gap: 20px;
            align-items: center;
            flex-wrap: wrap;
        }
        .radio-group label {
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            margin-bottom: 0;
        }
        .radio-group input[type="radio"] {
            width: auto;
            margin: 0;
            accent-color: #00ff9d;
        }
        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .checkbox-group input[type="checkbox"] {
            width: auto;
            accent-color: #00ff9d;
        }
        .checkbox-group label {
            margin-bottom: 0;
            cursor: pointer;
        }
        .form-actions {
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
            margin-top: 2rem;
            padding-top: 1rem;
            border-top: 1px solid var(--border-color);
        }

        /* Alert Messages */
        .alert {
            padding: 12px 20px;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .alert-success {
            background: rgba(0, 255, 157, 0.1);
            border: 1px solid #00ff9d;
            color: #00ff9d;
        }
        .alert-danger {
            background: rgba(220, 38, 38, 0.1);
            border: 1px solid #dc2626;
            color: #ef4444;
        }
        .alert i {
            font-size: 1.2rem;
        }

        /* Tips Box */
        .tips-box {
            margin-top: 1.5rem;
            padding: 1.2rem 1.5rem;
            background: rgba(0, 255, 157, 0.05);
            border-radius: 12px;
            border-left: 3px solid #00ff9d;
        }
        .tips-box i {
            color: #00ff9d;
            margin-right: 10px;
        }
        .tips-box strong {
            color: #00ff9d;
        }
        .tips-box ul {
            margin-top: 8px;
            margin-left: 20px;
            color: var(--text-secondary);
        }
        .tips-box ul li {
            margin-bottom: 4px;
        }

        /* Responsive */
        @media (max-width: 1024px) {
            .form-grid {
                grid-template-columns: 1fr;
            }
            .form-group.full-width {
                grid-column: span 1;
            }
        }

        @media (max-width: 768px) {
            .sidebar {
                width: 80px;
                padding: 1rem 0.5rem;
            }
            .sidebar .logo, .sidebar .nav-link span {
                display: none;
            }
            .main-content {
                margin-left: 80px;
                padding: 1rem;
            }
            .nav-link {
                justify-content: center;
            }
            .options-row {
                grid-template-columns: 1fr;
            }
            .radio-group {
                gap: 10px;
            }
            .page-header h1 {
                font-size: 1.3rem;
            }
            .form-card {
                padding: 1rem;
            }
        }

        @media (max-width: 480px) {
            .form-actions {
                flex-direction: column;
            }
            .form-actions .btn {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <div class="cyber-bg"></div>
    <div class="grid-overlay"></div>

    <div class="dashboard-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="logo">CSS Admin</div>
            <ul class="nav-menu">
                <li class="nav-item"><a href="../dashboard.php" class="nav-link"><i class="fas fa-tachometer-alt"></i><span>Dashboard</span></a></li>
                <li class="nav-item"><a href="manage_questions.php" class="nav-link"><i class="fas fa-question-circle"></i><span>Manage Questions</span></a></li>
                <li class="nav-item"><a href="add_question.php" class="nav-link active"><i class="fas fa-plus-circle"></i><span>Add Question</span></a></li>
                <li class="nav-item"><a href="manage_users.php" class="nav-link"><i class="fas fa-users"></i><span>Users</span></a></li>
                <li class="nav-item"><a href="../logout.php" class="nav-link"><i class="fas fa-sign-out-alt"></i><span>Logout</span></a></li>
            </ul>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <div class="page-header">
                <h1><i class="fas fa-plus-circle"></i> Add New Question</h1>
                <a href="manage_questions.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back to Questions</a>
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
                                <label>
                                    <input type="radio" name="correct_answer" value="A" <?php echo (($_POST['correct_answer'] ?? '') == 'A') ? 'checked' : ''; ?> required> A
                                </label>
                                <label>
                                    <input type="radio" name="correct_answer" value="B" <?php echo (($_POST['correct_answer'] ?? '') == 'B') ? 'checked' : ''; ?>> B
                                </label>
                                <label>
                                    <input type="radio" name="correct_answer" value="C" <?php echo (($_POST['correct_answer'] ?? '') == 'C') ? 'checked' : ''; ?>> C
                                </label>
                                <label>
                                    <input type="radio" name="correct_answer" value="D" <?php echo (($_POST['correct_answer'] ?? '') == 'D') ? 'checked' : ''; ?>> D
                                </label>
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
                                <input type="checkbox" name="is_active" id="is_active" <?php echo (isset($_POST['is_active']) && $_POST['is_active'] == '1') ? 'checked' : 'checked'; ?> value="1">
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
        // Auto-dismiss alerts after 5 seconds
        document.addEventListener('DOMContentLoaded', function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                setTimeout(function() {
                    alert.style.transition = 'opacity 0.5s ease';
                    alert.style.opacity = '0';
                    setTimeout(function() {
                        alert.remove();
                    }, 500);
                }, 5000);
            });
        });

        // Form validation
        document.getElementById('questionForm').addEventListener('submit', function(e) {
            const questionText = document.querySelector('textarea[name="question_text"]').value.trim();
            const optionA = document.querySelector('input[name="option_a"]').value.trim();
            const optionB = document.querySelector('input[name="option_b"]').value.trim();
            const optionC = document.querySelector('input[name="option_c"]').value.trim();
            const optionD = document.querySelector('input[name="option_d"]').value.trim();
            const correctAnswer = document.querySelector('input[name="correct_answer"]:checked');

            if (!questionText) {
                e.preventDefault();
                alert('Please enter the question text.');
                return;
            }

            if (!optionA || !optionB || !optionC || !optionD) {
                e.preventDefault();
                alert('Please fill in all four options.');
                return;
            }

            if (!correctAnswer) {
                e.preventDefault();
                alert('Please select the correct answer.');
                return;
            }
        });
    </script>
</body>
</html>
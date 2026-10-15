<?php
// import_questions.php - Bulk import questions with dashboard-aligned design

include "../config.php";

// Check if admin is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../login.php");
    exit();
}

// Function to check if column exists and add if needed
function ensureColumns($conn) {
    $columns = ['coc_number', 'category', 'is_active', 'difficulty'];
    foreach ($columns as $col) {
        $result = $conn->query("SHOW COLUMNS FROM questions LIKE '$col'");
        if ($result->num_rows == 0) {
            if ($col == 'coc_number') {
                $conn->query("ALTER TABLE questions ADD COLUMN coc_number INT DEFAULT 1");
            } elseif ($col == 'category') {
                $conn->query("ALTER TABLE questions ADD COLUMN category VARCHAR(50) DEFAULT 'general'");
            } elseif ($col == 'is_active') {
                $conn->query("ALTER TABLE questions ADD COLUMN is_active TINYINT DEFAULT 1");
            } elseif ($col == 'difficulty') {
                $conn->query("ALTER TABLE questions ADD COLUMN difficulty ENUM('easy','medium','hard') DEFAULT 'medium'");
            }
        }
    }
}
ensureColumns($conn);

$import_result = '';
$import_success_count = 0;
$import_error_count = 0;
$import_errors = [];

// Handle CSV import
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csv_file'])) {
    $file = $_FILES['csv_file'];
    
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $import_result = '<div class="alert alert-danger">Error uploading file.</div>';
    } else {
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        if ($extension !== 'csv') {
            $import_result = '<div class="alert alert-danger">Please upload a CSV file.</div>';
        } else {
            $handle = fopen($file['tmp_name'], 'r');
            if ($handle !== false) {
                // Get header row
                $headers = fgetcsv($handle);
                
                // Expected headers
                $expected_headers = ['question_text', 'option_a', 'option_b', 'option_c', 'option_d', 'correct_answer', 'explanation', 'coc_number', 'category'];
                
                // Map headers to indices
                $header_map = [];
                foreach ($expected_headers as $expected) {
                    $index = array_search($expected, $headers);
                    if ($index !== false) {
                        $header_map[$expected] = $index;
                    }
                }
                
                // Check required fields
                $required = ['question_text', 'option_a', 'option_b', 'option_c', 'option_d', 'correct_answer'];
                $missing = array_diff($required, array_keys($header_map));
                
                if (!empty($missing)) {
                    $import_result = '<div class="alert alert-danger">Missing required columns: ' . implode(', ', $missing) . '</div>';
                } else {
                    $row_num = 1;
                    $stmt = $conn->prepare("
                        INSERT INTO questions (question_text, option_a, option_b, option_c, option_d, correct_answer, explanation, coc_number, category, is_active) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1)
                    ");
                    
                    while (($data = fgetcsv($handle)) !== false) {
                        $row_num++;
                        
                        // Extract data using header map
                        $question_text = trim($data[$header_map['question_text']] ?? '');
                        $option_a = trim($data[$header_map['option_a']] ?? '');
                        $option_b = trim($data[$header_map['option_b']] ?? '');
                        $option_c = trim($data[$header_map['option_c']] ?? '');
                        $option_d = trim($data[$header_map['option_d']] ?? '');
                        $correct_answer = strtoupper(trim($data[$header_map['correct_answer']] ?? ''));
                        $explanation = trim($data[$header_map['explanation']] ?? '');
                        $coc_number = isset($header_map['coc_number']) ? intval($data[$header_map['coc_number']] ?? 1) : 1;
                        $category = isset($header_map['category']) ? trim($data[$header_map['category']] ?? 'general') : 'general';
                        
                        // Validate
                        if (empty($question_text) || empty($option_a) || empty($option_b) || empty($option_c) || empty($option_d)) {
                            $import_error_count++;
                            $import_errors[] = "Row $row_num: Missing required fields";
                            continue;
                        }
                        
                        if (!in_array($correct_answer, ['A', 'B', 'C', 'D'])) {
                            $import_error_count++;
                            $import_errors[] = "Row $row_num: Invalid correct_answer '$correct_answer'. Must be A, B, C, or D";
                            continue;
                        }
                        
                        if ($coc_number < 1 || $coc_number > 4) {
                            $coc_number = 1;
                        }
                        
                        $stmt->bind_param("sssssssis", $question_text, $option_a, $option_b, $option_c, $option_d, $correct_answer, $explanation, $coc_number, $category);
                        
                        if ($stmt->execute()) {
                            $import_success_count++;
                        } else {
                            $import_error_count++;
                            $import_errors[] = "Row $row_num: " . $conn->error;
                        }
                    }
                    $stmt->close();
                    fclose($handle);
                    
                    if ($import_success_count > 0) {
                        $import_result = '<div class="alert alert-success">✅ Import completed! ' . $import_success_count . ' questions added successfully. ' . ($import_error_count > 0 ? $import_error_count . ' failed.' : '') . '</div>';
                    } else {
                        $import_result = '<div class="alert alert-danger">No questions were imported. Please check your CSV format.</div>';
                    }
                }
            } else {
                $import_result = '<div class="alert alert-danger">Could not open the file.</div>';
            }
        }
    }
}

// Handle sample CSV download
if (isset($_GET['download_sample'])) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="sample_questions.csv"');
    
    $output = fopen('php://output', 'w');
    
    // Headers
    fputcsv($output, ['question_text', 'option_a', 'option_b', 'option_c', 'option_d', 'correct_answer', 'explanation', 'coc_number', 'category']);
    
    // Sample data
    $samples = [
        [
            'What does CPU stand for?',
            'Central Processing Unit',
            'Computer Personal Unit',
            'Central Program Utility',
            'Core Processing Utility',
            'A',
            'CPU is the brain of the computer that processes instructions.',
            '1',
            'hardware'
        ],
        [
            'Which of the following is a storage device?',
            'RAM',
            'CPU',
            'Hard Disk Drive',
            'Motherboard',
            'C',
            'Hard Disk Drive (HDD) is a permanent storage device.',
            '1',
            'hardware'
        ],
        [
            'What does IP stand for in networking?',
            'Internet Protocol',
            'Internal Process',
            'Internet Program',
            'Interface Protocol',
            'A',
            'IP (Internet Protocol) is the principal communications protocol for relaying data across network boundaries.',
            '2',
            'networking'
        ],
        [
            'Which cable is commonly used for Ethernet networks?',
            'HDMI',
            'VGA',
            'USB',
            'Cat5e/Cat6',
            'D',
            'Cat5e and Cat6 cables are standard for Ethernet networking.',
            '2',
            'networking'
        ]
    ];
    
    foreach ($samples as $sample) {
        fputcsv($output, $sample);
    }
    
    fclose($output);
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Import Questions | CSS TESDA Admin</title>
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

        /* Dashboard Layout */
        .dashboard-container {
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar */
        .sidebar {
            width: 280px;
            background: rgba(10, 20, 40, 0.9);
            backdrop-filter: blur(10px);
            border-right: 1px solid rgba(0, 255, 157, 0.2);
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
            color: #94a3b8;
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
        .btn-success {
            background: linear-gradient(45deg, #00b894, #00cec9);
            color: #050812;
        }
        .btn-success:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 184, 148, 0.4);
        }

        /* Cards */
        .import-card, .guide-card {
            background: rgba(0, 0, 0, 0.3);
            border: 1px solid rgba(0, 255, 157, 0.2);
            border-radius: 20px;
            padding: 2rem;
            margin-bottom: 2rem;
        }
        .card-title {
            font-family: 'Orbitron', sans-serif;
            font-size: 1.3rem;
            color: #00ff9d;
            margin-bottom: 1.5rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid rgba(0, 255, 157, 0.2);
        }
        .card-title i {
            margin-right: 10px;
        }

        /* Upload Area */
        .upload-area {
            border: 2px dashed rgba(0, 255, 157, 0.3);
            border-radius: 16px;
            padding: 2rem;
            text-align: center;
            transition: all 0.3s;
            cursor: pointer;
        }
        .upload-area:hover {
            border-color: #00ff9d;
            background: rgba(0, 255, 157, 0.05);
        }
        .upload-icon {
            font-size: 3rem;
            color: #00ff9d;
            margin-bottom: 1rem;
        }
        .upload-area input {
            display: none;
        }
        .file-label {
            display: inline-block;
            padding: 10px 20px;
            background: rgba(0, 255, 157, 0.1);
            border: 1px solid #00ff9d;
            border-radius: 50px;
            color: #00ff9d;
            cursor: pointer;
            transition: all 0.3s;
        }
        .file-label:hover {
            background: #00ff9d;
            color: #050812;
        }
        .selected-file {
            margin-top: 1rem;
            color: #8899cc;
        }

        /* Table */
        .table-container {
            overflow-x: auto;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            padding: 12px 16px;
            text-align: left;
            border-bottom: 1px solid rgba(0, 255, 157, 0.1);
        }
        th {
            background: rgba(0, 255, 157, 0.05);
            font-family: 'Orbitron', sans-serif;
            font-weight: 600;
            color: #00ff9d;
        }
        .code-block {
            background: rgba(0, 0, 0, 0.5);
            padding: 15px;
            border-radius: 10px;
            font-family: monospace;
            font-size: 0.8rem;
            overflow-x: auto;
            color: #00ff9d;
        }

        /* Alert Messages */
        .alert {
            padding: 12px 20px;
            border-radius: 12px;
            margin-bottom: 1.5rem;
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
        .alert-warning {
            background: rgba(245, 158, 11, 0.1);
            border: 1px solid #f59e0b;
            color: #f59e0b;
        }

        /* Error List */
        .error-list {
            max-height: 200px;
            overflow-y: auto;
            margin-top: 10px;
        }
        .error-item {
            padding: 5px 10px;
            font-size: 0.8rem;
            color: #ef4444;
            border-bottom: 1px solid rgba(220, 38, 38, 0.2);
        }

        /* Responsive */
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
            }
            .nav-link {
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
                <li class="nav-item"><a href="add_question.php" class="nav-link"><i class="fas fa-plus-circle"></i><span>Add Question</span></a></li>
                <li class="nav-item"><a href="import_questions.php" class="nav-link active"><i class="fas fa-upload"></i><span>Import</span></a></li>
                <li class="nav-item"><a href="../manage_users.php" class="nav-link"><i class="fas fa-users"></i><span>Users</span></a></li>
                <li class="nav-item"><a href="../../logout.php" class="nav-link"><i class="fas fa-sign-out-alt"></i><span>Logout</span></a></li>
            </ul>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <div class="page-header">
                <h1><i class="fas fa-upload"></i> Import Questions</h1>
                <div>
                    <a href="?download_sample=1" class="btn btn-success"><i class="fas fa-download"></i> Download Sample CSV</a>
                    <a href="manage_questions.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back</a>
                </div>
            </div>

            <?php echo $import_result; ?>

            <!-- Import Card -->
            <div class="import-card">
                <div class="card-title">
                    <i class="fas fa-file-csv"></i> Upload CSV File
                </div>
                
                <form method="POST" enctype="multipart/form-data" id="importForm">
                    <div class="upload-area" onclick="document.getElementById('csv_file').click()">
                        <div class="upload-icon">
                            <i class="fas fa-cloud-upload-alt"></i>
                        </div>
                        <h3 style="color: #00ff9d; margin-bottom: 10px;">Click to select CSV file</h3>
                        <p style="color: #8899cc; margin-bottom: 15px;">or drag and drop your file here</p>
                        <div class="file-label">
                            <i class="fas fa-folder-open"></i> Browse Files
                        </div>
                        <input type="file" name="csv_file" id="csv_file" accept=".csv" required onchange="updateFileName(this)">
                        <div class="selected-file" id="selectedFile"></div>
                    </div>
                    
                    <div style="margin-top: 1.5rem; text-align: center;">
                        <button type="submit" class="btn btn-primary" style="padding: 12px 40px;">
                            <i class="fas fa-upload"></i> Import Questions
                        </button>
                    </div>
                </form>
            </div>

            <!-- Guide Card -->
            <div class="guide-card">
                <div class="card-title">
                    <i class="fas fa-info-circle"></i> CSV Format Guide
                </div>
                
                <p style="margin-bottom: 1rem; color: #8899cc;">
                    Your CSV file must include the following columns in the first row:
                </p>
                
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Column Name</th>
                                <th>Required</th>
                                <th>Description</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr><td>question_text</td><td><span style="color: #00ff9d;">✓ Yes</span></td><td>The question text</td></tr>
                            <tr><td>option_a</td><td><span style="color: #00ff9d;">✓ Yes</span></td><td>Option A text</td></tr>
                            <tr><td>option_b</td><td><span style="color: #00ff9d;">✓ Yes</span></td><td>Option B text</td></tr>
                            <tr><td>option_c</td><td><span style="color: #00ff9d;">✓ Yes</span></td><td>Option C text</td></tr>
                            <tr><td>option_d</td><td><span style="color: #00ff9d;">✓ Yes</span></td><td>Option D text</td></tr>
                            <tr><td>correct_answer</td><td><span style="color: #00ff9d;">✓ Yes</span></td><td>Must be A, B, C, or D</td></tr>
                            <tr><td>explanation</td><td><span style="color: #f59e0b;">Optional</span></td><td>Explanation for the correct answer</td></tr>
                            <tr><td>coc_number</td><td><span style="color: #f59e0b;">Optional</span></td><td>1, 2, 3, or 4 (default: 1)</td></tr>
                            <tr><td>category</td><td><span style="color: #f59e0b;">Optional</span></td><td>hardware, networking, software, etc. (default: general)</td></tr>
                        </tbody>
                    </table>
                </div>

                <div style="margin-top: 1.5rem;">
                    <p style="color: #00ff9d; margin-bottom: 0.5rem;"><i class="fas fa-download"></i> Sample CSV content:</p>
                    <div class="code-block">
                        question_text,option_a,option_b,option_c,option_d,correct_answer,explanation,coc_number,category<br>
                        "What does CPU stand for?","Central Processing Unit","Computer Personal Unit","Central Program Utility","Core Processing Utility","A","CPU is the brain of the computer.",1,hardware<br>
                        "Which cable is used for Ethernet?","HDMI","VGA","USB","Cat5e","D","Cat5e/Cat6 are standard Ethernet cables.",2,networking
                    </div>
                </div>

                <div style="margin-top: 1.5rem; padding: 1rem; background: rgba(0, 255, 157, 0.05); border-radius: 12px; border-left: 3px solid #00ff9d;">
                    <i class="fas fa-lightbulb" style="color: #00ff9d; margin-right: 10px;"></i>
                    <strong style="color: #00ff9d;">Tips:</strong>
                    <ul style="margin-top: 8px; margin-left: 20px; color: #8899cc;">
                        <li>Save your file as CSV (Comma Delimited) format</li>
                        <li>Use double quotes around fields that contain commas</li>
                        <li>The first row MUST contain the exact column names shown above</li>
                        <li>Click "Download Sample CSV" to get a template with example questions</li>
                    </ul>
                </div>
            </div>

            <?php if (!empty($import_errors)): ?>
            <div class="alert alert-warning">
                <strong><i class="fas fa-exclamation-triangle"></i> Import Errors (<?php echo count($import_errors); ?>):</strong>
                <div class="error-list">
                    <?php foreach ($import_errors as $err): ?>
                        <div class="error-item"><?php echo htmlspecialchars($err); ?></div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </main>
    </div>

    <script>
        function updateFileName(input) {
            const fileName = input.files[0]?.name;
            const display = document.getElementById('selectedFile');
            if (fileName) {
                display.innerHTML = '<i class="fas fa-file-csv"></i> Selected: ' + fileName;
                display.style.color = '#00ff9d';
            } else {
                display.innerHTML = '';
            }
        }
        
        // Drag and drop functionality
        const uploadArea = document.querySelector('.upload-area');
        if (uploadArea) {
            uploadArea.addEventListener('dragover', (e) => {
                e.preventDefault();
                uploadArea.style.borderColor = '#00ff9d';
                uploadArea.style.background = 'rgba(0, 255, 157, 0.1)';
            });
            
            uploadArea.addEventListener('dragleave', (e) => {
                e.preventDefault();
                uploadArea.style.borderColor = 'rgba(0, 255, 157, 0.3)';
                uploadArea.style.background = 'transparent';
            });
            
            uploadArea.addEventListener('drop', (e) => {
                e.preventDefault();
                uploadArea.style.borderColor = 'rgba(0, 255, 157, 0.3)';
                uploadArea.style.background = 'transparent';
                
                const files = e.dataTransfer.files;
                if (files.length > 0) {
                    const fileInput = document.getElementById('csv_file');
                    fileInput.files = files;
                    updateFileName(fileInput);
                }
            });
        }
    </script>
</body>
</html>
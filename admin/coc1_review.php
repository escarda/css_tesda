<?php
// coc1_review.php - Complete COC 1 Review with ALL topics and controllable zoom
// Session is started by config.php
require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$coc_number = 1;
$page_title = "COC 1: Install & Configure Computer Systems";

// Get UNIQUE review materials for COC 1 - NO DUPLICATES
$sql = "SELECT MIN(id) as id, coc_number, title, MIN(content) as content, MIN(category) as category 
        FROM review_materials 
        WHERE coc_number = ? 
        GROUP BY title 
        ORDER BY id";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $coc_number);
$stmt->execute();
$materials = $stmt->get_result();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <?php if (file_exists(__DIR__ . '/includes/theme_preload.php')) { include __DIR__ . '/includes/theme_preload.php'; } ?>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> | CSS TESDA Reviewer</title>
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&family=Rajdhani:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <?php if (file_exists(__DIR__ . '/includes/theme_vars.php')) { include __DIR__ . '/includes/theme_vars.php'; } ?>
    <style>
        /* Page theme variables: independent from the brightness widget */
        :root {
            --bg-primary: #050812;
            --bg-navbar: rgba(5, 8, 18, 0.92);
            --bg-card: rgba(10, 20, 40, 0.82);
            --bg-panel: rgba(10, 20, 40, 0.95);
            --bg-stat: rgba(0, 255, 157, 0.08);
            --text-primary: #e8fff6;
            --text-secondary: #9db3ad;
            --border-light: rgba(0, 255, 157, 0.18);
            --border-color: rgba(0, 255, 157, 0.35);
            --cyber-bg1: #050812;
            --cyber-bg2: #071a1a;
            --grid-color: rgba(0, 255, 157, 0.035);
        }

        html[data-theme="light"] {
            --bg-primary: #f4f8f7;
            --bg-navbar: rgba(255, 255, 255, 0.94);
            --bg-card: rgba(255, 255, 255, 0.96);
            --bg-panel: rgba(255, 255, 255, 0.98);
            --bg-stat: rgba(0, 150, 100, 0.08);
            --text-primary: #17231f;
            --text-secondary: #53645e;
            --border-light: rgba(0, 130, 90, 0.18);
            --border-color: rgba(0, 130, 90, 0.32);
            --cyber-bg1: #eef8f4;
            --cyber-bg2: #e7f3ff;
            --grid-color: rgba(0, 120, 90, 0.06);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html, body {
            overflow-x: hidden;
        }

        body {
            font-family: 'Rajdhani', sans-serif;
            background: var(--bg-primary);
            color: var(--text-primary);
            min-height: 100vh;
            transition: background 0.3s ease, color 0.3s ease;
        }

        /* Scroll lock - page keeps its full height, so scroll position is preserved */
        html.modal-open {
            overflow: hidden !important;
        }

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

        .navbar {
            background: var(--bg-navbar);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid var(--border-light);
            padding: 1rem 2rem;
            position: sticky;
            top: 0;
            z-index: 100;
            transition: background 0.3s ease;
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
            color: var(--text-secondary);
            text-decoration: none;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .nav-links a:hover, .nav-links a.active {
            color: #00ff9d;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 1rem;
            color: #00ff9d;
        }

        .theme-toggle-nav {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            padding: 8px 13px;
            border: 1px solid rgba(0, 255, 157, 0.45);
            border-radius: 10px;
            background: rgba(0, 255, 157, 0.08);
            color: var(--text-primary);
            font-family: 'Rajdhani', sans-serif;
            font-size: 0.9rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.25s ease;
            white-space: nowrap;
        }

        .theme-toggle-nav:hover {
            color: #00ff9d;
            border-color: #00ff9d;
            background: rgba(0, 255, 157, 0.15);
            transform: translateY(-1px);
        }

        .theme-toggle-nav i {
            font-size: 0.95rem;
        }

        @media (max-width: 768px) {
            .theme-toggle-nav {
                padding: 7px 10px;
            }
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 2rem;
        }

        .page-header {
            text-align: center;
            margin-bottom: 3rem;
        }

        .page-header h1 {
            font-family: 'Orbitron', sans-serif;
            font-size: 2.5rem;
            background: linear-gradient(135deg, #00ff9d, #0066ff);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 1rem;
        }

        .page-header p {
            color: var(--text-secondary);
            font-size: 1.1rem;
        }

        .coc-badge {
            display: inline-block;
            padding: 8px 20px;
            background: rgba(0, 255, 157, 0.1);
            border: 1px solid #00ff9d;
            border-radius: 50px;
            color: #00ff9d;
            margin-bottom: 1rem;
        }

        .materials-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(380px, 1fr));
            gap: 1.5rem;
        }

        .material-card {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 16px;
            overflow: hidden;
            transition: all 0.3s;
            cursor: pointer;
            position: relative;
        }

        .material-card:hover {
            transform: translateY(-8px);
            border-color: #00ff9d;
            box-shadow: 0 15px 40px rgba(0, 255, 157, 0.2);
        }

        .material-card:active {
            transform: translateY(-4px);
        }

        .material-card::after {
            content: '\f054';
            font-family: 'Font Awesome 6 Free';
            font-weight: 900;
            position: absolute;
            bottom: 15px;
            right: 15px;
            color: #00ff9d;
            opacity: 0;
            transition: opacity 0.3s;
        }

        .material-card:hover::after {
            opacity: 1;
        }

        .card-image {
            width: 100%;
            height: 280px;
            overflow: hidden;
            background: linear-gradient(135deg, #1a1f2e, #0a0f1e);
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
        }

        .card-image img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            transition: transform 0.5s ease;
            background: #0a0f1e;
        }

        .material-card:hover .card-image img {
            transform: scale(1.1);
        }

        .card-header {
            padding: 1rem 1.5rem;
            background: rgba(0, 255, 157, 0.05);
            border-bottom: 1px solid rgba(0, 255, 157, 0.1);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .card-header i {
            font-size: 1.5rem;
            color: #00ff9d;
        }

        .card-header h3 {
            font-family: 'Orbitron', sans-serif;
            font-size: 1.1rem;
            color: #00ff9d;
        }

        .card-body {
            padding: 1.5rem;
        }

        .card-body p {
            color: var(--text-secondary);
            line-height: 1.6;
            margin-bottom: 0.5rem;
        }

        .category-tag {
            display: inline-block;
            padding: 4px 12px;
            background: rgba(0, 255, 157, 0.1);
            border-radius: 20px;
            font-size: 0.7rem;
            color: #00ff9d;
            margin-bottom: 1rem;
        }

        .read-more {
            color: #00ff9d;
            font-size: 0.85rem;
            margin-top: 10px;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        html[data-theme="light"] .navbar {
            box-shadow: 0 2px 18px rgba(0, 0, 0, 0.08);
        }

        html[data-theme="light"] .material-card,
        html[data-theme="light"] .quiz-section {
            color: var(--text-primary);
        }

        html[data-theme="light"] .theme-toggle-nav {
            color: #17231f;
            background: rgba(0, 150, 100, 0.08);
            border-color: rgba(0, 130, 90, 0.32);
        }

        /* ============================================
           MODAL - SEMI-TRANSPARENT + FIXED POSITIONING
           ============================================ */
        .modal {
            display: none;
            position: fixed !important;
            top: 0 !important;
            left: 0 !important;
            right: 0 !important;
            bottom: 0 !important;
            width: 100vw !important;
            height: 100vh !important;
            margin: 0 !important;
            padding: 20px !important;
            background: rgba(5, 8, 18, 0.75);
            z-index: 99999 !important;
            overflow-y: auto;
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
            transform: none !important;
        }

        .modal.active {
            display: flex !important;
            justify-content: center !important;
            align-items: center !important;
            animation: fadeIn 0.3s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .modal-content {
            background: rgba(10, 20, 40, 0.95);
            border: 2px solid #00ff9d;
            border-radius: 24px;
            max-width: 900px;
            width: 100%;
            max-height: 85vh;
            overflow-y: auto;
            position: relative;
            animation: slideUp 0.3s ease;
            margin: 0 !important;
            flex-shrink: 0;
        }

        @keyframes slideUp {
            from {
                transform: translateY(50px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .modal-header {
            padding: 1.5rem;
            border-bottom: 1px solid rgba(0, 255, 157, 0.2);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-header h2 {
            font-family: 'Orbitron', sans-serif;
            color: #00ff9d;
            font-size: 1.5rem;
        }

        .close-modal {
            background: none;
            border: none;
            color: #8899cc;
            font-size: 1.8rem;
            cursor: pointer;
            transition: all 0.3s;
        }

        .close-modal:hover {
            color: #ef4444;
            transform: scale(1.1);
        }

        .image-container {
            position: relative;
            background: #0a0f1e;
            padding: 1rem;
        }

        .zoom-controls {
            position: absolute;
            bottom: 15px;
            left: 50%;
            transform: translateX(-50%);
            display: flex;
            gap: 10px;
            z-index: 10;
            background: rgba(0, 0, 0, 0.7);
            padding: 8px 12px;
            border-radius: 40px;
            backdrop-filter: blur(5px);
            -webkit-backdrop-filter: blur(5px);
            border: 1px solid rgba(0, 255, 157, 0.3);
        }

        .zoom-btn {
            background: rgba(0, 255, 157, 0.2);
            border: none;
            color: #00ff9d;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            cursor: pointer;
            font-size: 1.2rem;
            font-weight: bold;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .zoom-btn:hover {
            background: #00ff9d;
            color: #050812;
            transform: scale(1.05);
        }

        .zoom-reset {
            background: rgba(255, 165, 0, 0.2);
            color: #ffaa00;
        }

        .zoom-reset:hover {
            background: #ffaa00;
            color: #050812;
        }

        .zoom-level {
            color: #00ff9d;
            font-size: 0.8rem;
            display: flex;
            align-items: center;
            padding: 0 8px;
            font-family: monospace;
        }

        .modal-image {
            width: 100%;
            height: 400px;
            overflow: auto;
            background: #0a0f1e;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: default;
            position: relative;
        }

        .modal-image:active {
            cursor: grabbing;
        }

        .modal-image img {
            display: block;
            max-width: none;
            max-height: none;
            width: auto;
            height: auto;
            transform-origin: center center;
            transition: transform 0.1s ease;
            pointer-events: none;
            margin: auto;
        }

        .pan-hint {
            position: absolute;
            bottom: 70px;
            left: 50%;
            transform: translateX(-50%);
            background: rgba(0, 0, 0, 0.5);
            padding: 4px 8px;
            border-radius: 20px;
            font-size: 0.7rem;
            color: #8899cc;
            pointer-events: none;
        }

        .modal-body {
            padding: 1.5rem;
        }

        .modal-body p {
            color: #cbd5e1;
            line-height: 1.8;
            margin-bottom: 1rem;
        }

        .modal-category {
            display: inline-block;
            padding: 4px 12px;
            background: rgba(0, 255, 157, 0.1);
            border-radius: 20px;
            font-size: 0.8rem;
            color: #00ff9d;
            margin-bottom: 1rem;
        }

        .quiz-section {
            margin-top: 3rem;
            padding: 2rem;
            background: var(--bg-panel, rgba(0, 0, 0, 0.3));
            border: 1px solid var(--border-color);
            border-radius: 20px;
            text-align: center;
        }

        .quiz-section h2 {
            color: #00ff9d;
            margin-bottom: 1rem;
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

        .quiz-buttons {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
            margin-top: 1.5rem;
        }

        .key-topics {
            margin-top: 2rem;
            padding: 1.5rem;
            background: var(--bg-stat);
            border-radius: 16px;
        }

        .key-topics h3 {
            color: #00ff9d;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .topic-list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 0.8rem;
            list-style: none;
        }

        .topic-list li {
            padding: 0.5rem 1rem;
            background: rgba(0, 255, 157, 0.05);
            border-radius: 8px;
            display: flex;
            align-items: center;
            gap: 10px;
            color: var(--text-primary);
        }

        .topic-list li i {
            color: #00ff9d;
        }

        @media (max-width: 768px) {
            .container { padding: 1rem; }
            .materials-grid { grid-template-columns: 1fr; }
            .page-header h1 { font-size: 1.8rem; }
            .nav-links { width: 100%; justify-content: center; }
            .card-image { height: 220px; }
            .modal-image { height: 300px; }
            .modal { padding: 10px !important; }
            .modal-content { max-height: 90vh; }
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
                <a href="coc1_review.php" class="active"><i class="fas fa-microchip"></i> COC 1</a>
                <a href="coc2_review.php"><i class="fas fa-network-wired"></i> COC 2</a>
                <a href="coc3_review.php"><i class="fas fa-server"></i> COC 3</a>
                <a href="coc4_review.php"><i class="fas fa-tools"></i> COC 4</a>
                
                <div class="user-info">
                    <i class="fas fa-user"></i>
                    <span><?php echo htmlspecialchars($_SESSION['full_name'] ?? $_SESSION['username']); ?></span>
                    <button type="button" class="theme-toggle-nav" id="themeToggleNav" aria-label="Switch theme" title="Switch between dark mode and light mode">
                        <i class="fas fa-sun" id="themeToggleIcon"></i>
                        <span id="themeToggleText">Light Mode</span>
                    </button>
                    <a href="logout.php" style="color: #ef4444;"><i class="fas fa-sign-out-alt"></i></a>
                </div>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="page-header">
            <div class="coc-badge">
                <i class="fas fa-microchip"></i> COC 1: Install & Configure Computer Systems
            </div>
            <h1>Complete Review Materials</h1>
            <p>Click on any card to view detailed information. Use + / - buttons or mouse wheel to zoom images.</p>
        </div>

        <div class="key-topics">
            <h3><i class="fas fa-star-of-life"></i> All Topics Covered </h3>
            <ul class="topic-list">
                <li><i class="fas fa-desktop"></i> Introduction to Computer Systems</li>
                <li><i class="fas fa-microchip"></i> CPU, Motherboard, RAM</li>
                <li><i class="fas fa-sd-card"></i> Storage (SSD vs HDD)</li>
                <li><i class="fas fa-plug"></i> Power Supply Unit (PSU)</li>
                <li><i class="fas fa-tv"></i> Graphics Card (GPU)</li>
                <li><i class="fas fa-keyboard"></i> Input & Output Devices</li>
                <li><i class="fas fa-tools"></i> Assembly & Cable Management</li>
                <li><i class="fas fa-desktop"></i> BIOS/UEFI Configuration</li>
                <li><i class="fas fa-window-maximize"></i> OS Installation (Windows/Linux)</li>
                <li><i class="fas fa-network-wired"></i> Network Setup & Commands</li>
                <li><i class="fas fa-shield-alt"></i> Safety & ESD Protection</li>
                <li><i class="fas fa-search"></i> Troubleshooting (BSOD, No Boot, etc.)</li>
                <li><i class="fas fa-chart-line"></i> Maintenance & Backup Strategies</li>
                <li><i class="fas fa-file-alt"></i> Documentation & Inventory</li>
            </ul>
        </div>

        <div class="materials-grid">
            <?php if ($materials->num_rows > 0): ?>
                <?php 
                // Track displayed titles to prevent PHP-level duplicates
                $displayed_titles = [];
                while ($material = $materials->fetch_assoc()): 
                    $title_lower = strtolower(trim($material['title']));
                    
                    // Skip if already displayed
                    if (in_array($title_lower, $displayed_titles)) {
                        continue;
                    }
                    $displayed_titles[] = $title_lower;
                    
                    $image_url = '';
                    
                    // INTRODUCTION TO COMPUTER SYSTEMS
                    if (strpos($title_lower, 'introduction') !== false || strpos($title_lower, 'computer system') !== false) {
                        $image_url = 'https://i.pinimg.com/736x/12/3a/c2/123ac257b8eabb6d994f4cd0550637fc.jpg';
                    } 
                    // CPU Image
                    elseif (strpos($title_lower, 'cpu') !== false || strpos($title_lower, 'central processing') !== false) {
                        $image_url = 'https://i.pinimg.com/736x/0e/b7/ca/0eb7cafc92d81777fb781044ad47344f.jpg';
                    } 
                    // Motherboard Image
                    elseif (strpos($title_lower, 'motherboard') !== false) {
                        $image_url = 'https://i.pinimg.com/736x/c6/9f/0e/c69f0ec66156d60d9dac1f93adefc027.jpg';
                    } 
                    // RAM Image
                    elseif (strpos($title_lower, 'ram') !== false || strpos($title_lower, 'memory') !== false) {
                        $image_url = 'https://i.pinimg.com/736x/24/ab/b7/24abb73cc7f22175538563921e1046f2.jpg';
                    } 
                    // Storage (SSD/HDD) Image
                    elseif (strpos($title_lower, 'storage') !== false || strpos($title_lower, 'ssd') !== false || strpos($title_lower, 'hdd') !== false) {
                        $image_url = 'https://i.pinimg.com/736x/49/a4/60/49a460902979c293fefbc3f1c2e9e54c.jpg';
                    } 
                    // Power Supply Image
                    elseif (strpos($title_lower, 'power supply') !== false || strpos($title_lower, 'psu') !== false) {
                        $image_url = 'https://i.pinimg.com/736x/a3/46/d4/a346d43616a4031be3d183775284a5be.jpg';
                    } 
                    // Graphics Card (GPU)
                    elseif (strpos($title_lower, 'graphics card') !== false || strpos($title_lower, 'gpu') !== false) {
                        $image_url = 'https://i.pinimg.com/736x/57/20/29/572029b32c23430468db28c8417ec330.jpg';
                    } 
                    // Input Devices
                    elseif (strpos($title_lower, 'input device') !== false || (strpos($title_lower, 'input') !== false && strpos($title_lower, 'device') !== false)) {
                        $image_url = 'https://i.pinimg.com/736x/15/bc/e6/15bce688d582bd0b71748778eab12059.jpg';
                    } 
                    // Output Devices
                    elseif (strpos($title_lower, 'output device') !== false || (strpos($title_lower, 'output') !== false && strpos($title_lower, 'device') !== false)) {
                        $image_url = 'https://i.pinimg.com/736x/b4/7d/a8/b47da8c4448b70e579e32ea8df7ce0c4.jpg';
                    } 
                    // Display Interfaces
                    elseif (strpos($title_lower, 'display interface') !== false || strpos($title_lower, 'hdmi') !== false || strpos($title_lower, 'vga') !== false) {
                        $image_url = 'https://i.pinimg.com/736x/e8/5e/4d/e85e4d1b96c68e3f38f1e59b77a2f104.jpg';
                    } 
                    // Tools for Computer Assembly
                    elseif (strpos($title_lower, 'tools') !== false && strpos($title_lower, 'assembly') !== false) {
                        $image_url = 'https://i.pinimg.com/736x/51/35/f9/5135f901859ebe22b8359830db981360.jpg';
                    } 
                    // Step-by-Step Assembly Guide
                    elseif (strpos($title_lower, 'step-by-step') !== false || (strpos($title_lower, 'assembly') !== false && strpos($title_lower, 'guide') !== false)) {
                        $image_url = 'https://i.pinimg.com/1200x/ab/be/2c/abbe2cc70c004d57a6d1ab51aa368cfe.jpg';
                    } 
                    // Thermal Paste Application
                    elseif (strpos($title_lower, 'thermal paste') !== false) {
                        $image_url = 'https://i.pinimg.com/1200x/c0/90/c9/c090c9f70b0f806d0264d2684188e865.jpg';
                    } 
                    // Cable Management
                    elseif (strpos($title_lower, 'cable management') !== false) {
                        $image_url = 'https://i.pinimg.com/736x/08/d0/1f/08d01f81336f49fc118172da6be6c945.jpg';
                    } 
                    // BIOS vs UEFI
                    elseif (strpos($title_lower, 'bios vs') !== false || strpos($title_lower, 'uefi vs') !== false) {
                        $image_url = 'https://i.pinimg.com/736x/c0/ff/61/c0ff615cec08c1887b69c121ddc48c79.jpg';
                    } 
                    // Accessing BIOS/UEFI
                    elseif (strpos($title_lower, 'accessing bios') !== false || strpos($title_lower, 'enter bios') !== false) {
                        $image_url = 'https://i.pinimg.com/736x/b4/40/5c/b4405c329da3087d27a10803d8389937.jpg';
                    } 
                    // Types of Computers
                    elseif (strpos($title_lower, 'types of computer') !== false) {
                        $image_url = 'https://i.pinimg.com/1200x/4e/e5/5d/4ee55dd9b9fe15cdb7ac62164cfe78b6.jpg';
                    } 
                    // Essential BIOS Settings
                    elseif (strpos($title_lower, 'essential bios') !== false || strpos($title_lower, 'bios settings') !== false) {
                        $image_url = 'https://r.testifier.nl/Acbs8526SDKI/resizing_type:fit/width:3840/height:2560/plain/https://s3-newsifier.ams3.digitaloceanspaces.com/gizchina.com/images/2025-06/essential-bios-settings.jpg@webp';
                    } 
                    // Updating BIOS/UEFI
                    elseif (strpos($title_lower, 'updating bios') !== false || strpos($title_lower, 'update bios') !== false) {
                        $image_url = 'https://benhvientinhoc.com/wp-content/uploads/2025/10/image-469-1024x544.png';
                    } 
                    // OS Installation Media Preparation
                    elseif (strpos($title_lower, 'installation media') !== false || strpos($title_lower, 'bootable usb') !== false) {
                        $image_url = 'https://lh3.googleusercontent.com/vqz-6KM73-u6FXY_BL_dq5BfkOW8BYP7BWI_OQTfJYvDGrFVUoBt5aeXC7hRmO97sTHjViER-2Vh3asnv9Ija9kWmnMdn2bOdJtbE7oPuPCca3ta-n115uFzNORRz2FbLyRcOeZSX9tm0nR7jNpxCi4';
                    } 
                    // Windows Installation Steps
                    elseif (strpos($title_lower, 'windows 10') !== false || strpos($title_lower, 'windows 11') !== false || strpos($title_lower, 'windows installation') !== false) {
                        $image_url = 'https://i.pinimg.com/736x/8c/a0/ae/8ca0aedb02a6981e3e9e5878cbcc10dc.jpg';
                    } 
                    // Linux Installation
                    elseif (strpos($title_lower, 'linux') !== false || strpos($title_lower, 'ubuntu') !== false) {
                        $image_url = 'https://i.pinimg.com/1200x/0b/ad/34/0bad343bbffdb73abf4e308f499b31c6.jpg';
                    } 
                    // Disk Partitioning
                    elseif (strpos($title_lower, 'disk partition') !== false || strpos($title_lower, 'partitioning') !== false) {
                        $image_url = 'https://www.nirmaltv.com/images/Partition-hard-disk-in-windows-11-1536x919.png';
                    } 
                    // Device Drivers
                    elseif (strpos($title_lower, 'device driver') !== false) {
                        $image_url = 'https://www.jengal.com/blog/wp-content/uploads/2022/08/examples-of-device-drivers.jpg';
                    } 
                    // Installing Drivers
                    elseif (strpos($title_lower, 'installing driver') !== false) {
                        $image_url = 'https://images.drivereasy.com/wp-content/uploads/2016/10/img_5805d0cfb5bda.png';
                    } 
                    // Essential Software Installation
                    elseif (strpos($title_lower, 'essential software') !== false) {
                        $image_url = 'https://windows-cdn.softpedia.com/screenshots/Essential-Software-Installer_1.png';
                    } 
                    // Network Connection Setup
                    elseif (strpos($title_lower, 'network connection') !== false || strpos($title_lower, 'wired connection') !== false) {
                        $image_url ='https://www.guidingtech.com/wp-content/uploads/open-network-connections-using-controlpanel4.png';
                    } 
                    // IP Configuration
                    elseif (strpos($title_lower, 'ip configuration') !== false || strpos($title_lower, 'static ip') !== false) {
                        $image_url = 'https://www.sas.upenn.edu/~jasonrw/images/Network_000.jpg';
                    } 
                    // Network Commands
                    elseif (strpos($title_lower, 'network command') !== false || strpos($title_lower, 'ping') !== false) {
                        $image_url = 'https://cdn.mos.cms.futurecdn.net/znE7aCVfgeDVxDz2vNVWub-1024-80.jpg';
                    } 
                    // ESD Protection
                    elseif (strpos($title_lower, 'esd') !== false || strpos($title_lower, 'electrostatic') !== false) {
                        $image_url = 'https://i0.wp.com/blog.minicircuits.com/wp-content/uploads/2019/12/ESD-Saftey.jpg?resize=2048%2C1296&ssl=1';
                    } 
                    // Electrical Safety
                    elseif (strpos($title_lower, 'electrical safety') !== false) {
                        $image_url = 'https://greenwgroup.co.in/wp-content/uploads/2015/03/Electrical-Safety-1-1.jpg';
                    } 
                    // Physical Safety
                    elseif (strpos($title_lower, 'physical safety') !== false) {
                        $image_url = 'https://th.bing.com/th/id/R.858814a868c5a4e6789504937792a6c3?rik=iaEJSMx4N1ykIw&riu=http%3a%2f%2fwww.anatomystuff.co.uk%2fcdn%2fshop%2ffiles%2ftop-tips-for-safe-lifting-poster-product-image.jpg%3fv%3d1741093901&ehk=O4oV3mrkN503tj9tS%2fgcAy6Q63xuGc%2ft%2fto9lRJgJiI%3d&risl=&pid=ImgRaw&r=0';
                    } 
                    // Computer Won't Boot
                    elseif (strpos($title_lower, 'won\'t boot') !== false || strpos($title_lower, 'no power') !== false) {
                        $image_url = 'https://www.stellarinfo.co.in/blog/wp-content/uploads/2023/04/Windows-10-Wont-Boot-on-My-PC-002.jpg';
                    } 
                    // No Display Issue
                    elseif (strpos($title_lower, 'no display') !== false || strpos($title_lower, 'no signal') !== false) {
                        $image_url = 'https://tse1.mm.bing.net/th/id/OIP.pRo0wK7OdA16BxXRd4R1vAHaTR?rs=1&pid=ImgDetMain&o=7&rm=3';
                    } 
                    // Blue Screen of Death
                    elseif (strpos($title_lower, 'blue screen') !== false || strpos($title_lower, 'bsod') !== false) {
                        $image_url = 'https://us.norton.com/content/dam/blogs/images/norton/am/blue-screen-of-death-01.jpg';
                    } 
                    // Overheating Issues
                    elseif (strpos($title_lower, 'overheating') !== false) {
                        $image_url = 'https://t3.ftcdn.net/jpg/06/27/96/94/360_F_627969437_S5KcQIH15f1pk8BtSFxzhydorCKMqtmU.jpg';
                    } 
                    // Slow Performance
                    elseif (strpos($title_lower, 'slow performance') !== false) {
                        $image_url =' https://image.slidesharecdn.com/howtofixlaptopslowperformance-230809081012-a5837647/75/How-To-Fix-Laptop-Slow-Performance-1-2048.jpg';
                    } 
                    // No Internet Connection
                    elseif (strpos($title_lower, 'no internet') !== false) {
                        $image_url = 'https://www.systweak.com/blogs/wp-content/uploads/2024/09/How-to-Fix-No-Internet-Access-while-using-Ethernet-Connection.webp';
                    } 
                    // Cleaning Computer Components
                    elseif (strpos($title_lower, 'cleaning') !== false && strpos($title_lower, 'component') !== false) {
                        $image_url = 'https://thumbs.dreamstime.com/z/computer-repair-cleaning-components-dust-your-221487401.jpg';
                    } 
                    // Software Maintenance
                    elseif (strpos($title_lower, 'software maintenance') !== false) {
                        $image_url = 'https://distantjob.com/wp-content/uploads/2021/11/Ways-to-Improve-Software-Maintenance.jpg';
                    } 
                    // Backup Strategies
                    elseif (strpos($title_lower, 'backup') !== false) {
                        $image_url = 'https://www.collidu.com/media/catalog/product/img/f/e/fee0512a55bb20f7e4c6901e8d070abb46266b0aeaaef20b2a64886e8279a988/data-backup-strategy-slide2.png';
                    } 
                    // Performance Monitoring
                    elseif (strpos($title_lower, 'performance monitoring') !== false) {
                        $image_url ='https://image3.slideserve.com/5405856/performance-monitoring-approaches-l.jpg';
                    } 
                    // Inventory Documentation
                    elseif (strpos($title_lower, 'inventory') !== false && strpos($title_lower, 'documentation') !== false) {
                        $image_url = 'https://www.gep.com/prod/s3fs-public/images/Inventory-Management-Software-Features-Must-Have.jpg';
                    } 
                    // Troubleshooting Documentation
                    elseif (strpos($title_lower, 'troubleshooting documentation') !== false) {
                        $image_url = 'https://document360.com/wp-content/uploads/2023/01/Microsofts_troubleshooting_guide-768x368.png';
                    } 
                    // System Configuration Documentation
                    elseif (strpos($title_lower, 'system configuration') !== false) {
                        $image_url = 'https://image.slideserve.com/1275638/documenting-a-linux-system-configuration-l.jpg';
                    } 
                    // Default Image
                    else {
                        $image_url = 'https://upload.wikimedia.org/wikipedia/commons/thumb/2/2a/Computer_case_side_open.jpg/500px-Computer_case_side_open.jpg';
                    }
                ?>
                    <div class="material-card" onclick='openModal(<?php echo htmlspecialchars(json_encode($material), ENT_QUOTES, "UTF-8"); ?>, <?php echo htmlspecialchars(json_encode($image_url), ENT_QUOTES, "UTF-8"); ?>)'>
                        <div class="card-image">
                            <img src="<?php echo $image_url; ?>" alt="<?php echo htmlspecialchars($material['title']); ?>" loading="lazy" onerror="this.src='https://upload.wikimedia.org/wikipedia/commons/thumb/2/2a/Computer_case_side_open.jpg/500px-Computer_case_side_open.jpg'">
                        </div>
                        <div class="card-header">
                            <i class="fas fa-<?php 
                                echo $material['category'] == 'hardware' ? 'microchip' : 
                                    ($material['category'] == 'safety' ? 'shield-alt' : 
                                    ($material['category'] == 'tools' ? 'wrench' : 
                                    ($material['category'] == 'assembly' ? 'tools' : 
                                    ($material['category'] == 'bios' ? 'desktop' : 
                                    ($material['category'] == 'networking' ? 'network-wired' : 
                                    ($material['category'] == 'software' ? 'code' : 
                                    ($material['category'] == 'documentation' ? 'file-alt' : 'book'))))))); 
                            ?>"></i>
                            <h3><?php echo htmlspecialchars($material['title']); ?></h3>
                        </div>
                        <div class="card-body">
                            <span class="category-tag">
                                <i class="fas fa-tag"></i> <?php echo ucfirst($material['category']); ?>
                            </span>
                            <p><?php echo nl2br(htmlspecialchars(substr($material['content'], 0, 120))); ?>...</p>
                            <div class="read-more">
                                <i class="fas fa-mouse-pointer"></i> Click to read more
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div style="grid-column: 1/-1; text-align: center; padding: 3rem;">
                    <i class="fas fa-book-open" style="font-size: 3rem; color: #8899cc;"></i>
                    <p>Review materials for COC 1 will be available soon!</p>
                </div>
            <?php endif; ?>
        </div>

        <div class="quiz-section">
            <h2><i class="fas fa-question-circle"></i> Test Your Knowledge</h2>
            <p>Ready to assess your understanding? Take the COC 1 quiz to see how well you've mastered all the topics.</p>
            <div class="quiz-buttons">
                <a href="quiz.php?coc=1" class="btn btn-primary"><i class="fas fa-play"></i> Take COC 1 Quiz</a>
                <a href="practice_mode.php?coc=1" class="btn btn-chalkboard-teacher"><i class="fas fa-chalkboard-teacher"></i> Practice Mode</a>
            </div>
        </div>
    </div>

    <div id="detailModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalTitle">Title</h2>
                <button class="close-modal" onclick="closeModal()">&times;</button>
            </div>
            <div class="image-container">
                <div class="zoom-controls">
                    <button class="zoom-btn" id="zoomOutBtn" title="Zoom Out (Mouse Wheel)">−</button>
                    <span class="zoom-level" id="zoomLevel">100%</span>
                    <button class="zoom-btn" id="zoomInBtn" title="Zoom In (Mouse Wheel)">+</button>
                    <button class="zoom-btn zoom-reset" id="zoomResetBtn" title="Reset Zoom">⟳</button>
                </div>
                <div class="modal-image" id="modalImageContainer">
                    <img id="modalImage" src="" alt="">
                </div>
                <div class="pan-hint">
                    <i class="fas fa-hand-paper"></i> Click and drag to pan
                </div>
            </div>
            <div class="modal-body">
                <span class="modal-category" id="modalCategory"></span>
                <p id="modalContent"></p>
            </div>
        </div>
    </div>

    <script>
        let currentModal = null;
        let currentZoom = 1;
        let currentImage = null;
        let isPanning = false;
        let startX = 0;
        let startY = 0;
        let scrollLeft = 0;
        let scrollTop = 0;
        let savedScrollY = 0;   // remembers the page scroll position while the modal is open

        function openModal(material, imageUrl) {
            const modal = document.getElementById('detailModal');
            const modalTitle = document.getElementById('modalTitle');
            const modalImage = document.getElementById('modalImage');
            const modalCategory = document.getElementById('modalCategory');
            const modalContent = document.getElementById('modalContent');
            const container = document.getElementById('modalImageContainer');

            modalTitle.textContent = material.title || '';
            modalCategory.textContent = material.category ? material.category.charAt(0).toUpperCase() + material.category.slice(1) : '';
            modalContent.textContent = material.content || '';

            currentModal = modal;
            currentImage = modalImage;
            currentZoom = 1;
            isPanning = false;
            modalImage.style.transform = 'scale(1)';
            modalImage.alt = material.title || 'Review material image';
            container.scrollLeft = 0;
            container.scrollTop = 0;
            container.style.cursor = 'default';
            document.getElementById('zoomLevel').textContent = '100%';

            // Remember where the page was, then lock scrolling on <html>.
            // The page keeps its full height, so nothing jumps.
            savedScrollY = window.scrollY;
            document.documentElement.classList.add('modal-open');

            modal.classList.add('active');

            modalImage.onload = function () {
                container.scrollLeft = 0;
                container.scrollTop = 0;
            };

            modalImage.onerror = function () {
                modalImage.onerror = null;
                modalImage.src = 'https://upload.wikimedia.org/wikipedia/commons/thumb/2/2a/Computer_case_side_open.jpg/500px-Computer_case_side_open.jpg';
            };

            modalImage.src = imageUrl || 'https://upload.wikimedia.org/wikipedia/commons/thumb/2/2a/Computer_case_side_open.jpg/500px-Computer_case_side_open.jpg';
            setupPanning();
        }

        (function setupZoomControlsOnce() {
            const zoomInBtn = document.getElementById('zoomInBtn');
            const zoomOutBtn = document.getElementById('zoomOutBtn');
            const zoomResetBtn = document.getElementById('zoomResetBtn');
            const container = document.getElementById('modalImageContainer');

            zoomInBtn.addEventListener('click', () => zoomImage(0.1));
            zoomOutBtn.addEventListener('click', () => zoomImage(-0.1));
            zoomResetBtn.addEventListener('click', () => resetZoom());

            container.addEventListener('wheel', function(e) {
                if (!currentModal) return;
                e.preventDefault();
                zoomImage(e.deltaY < 0 ? 0.05 : -0.05);
            }, { passive: false });
        })();

        function setupPanning() {
            const container = document.getElementById('modalImageContainer');
            container.onmousedown = startPanning;
            container.onmouseleave = stopPanning;
            container.onmouseup = stopPanning;
            container.onmousemove = panImage;
        }

        function startPanning(e) {
            if (currentZoom <= 1) return;
            isPanning = true;
            const container = document.getElementById('modalImageContainer');
            startX = e.clientX;
            startY = e.clientY;
            scrollLeft = container.scrollLeft;
            scrollTop = container.scrollTop;
            container.style.cursor = 'grabbing';
        }
        
        function stopPanning() {
            isPanning = false;
            const container = document.getElementById('modalImageContainer');
            container.style.cursor = 'grab';
        }
        
        function panImage(e) {
            if (!isPanning) return;
            e.preventDefault();
            const container = document.getElementById('modalImageContainer');
            const x = e.clientX;
            const y = e.clientY;
            const walkX = (x - startX) * 1.5;
            const walkY = (y - startY) * 1.5;
            container.scrollLeft = scrollLeft - walkX;
            container.scrollTop = scrollTop - walkY;
        }
        
        function zoomImage(delta) {
            if (!currentImage) return;
            let newZoom = currentZoom + delta;
            newZoom = Math.min(Math.max(newZoom, 0.5), 3);
            if (newZoom === currentZoom) return;
            currentZoom = newZoom;
            currentImage.style.transform = `scale(${currentZoom})`;
            document.getElementById('zoomLevel').textContent = Math.round(currentZoom * 100) + '%';
            
            const container = document.getElementById('modalImageContainer');
            if (currentZoom > 1) {
                container.style.cursor = 'grab';
            } else {
                container.style.cursor = 'default';
                container.scrollLeft = 0;
                container.scrollTop = 0;
            }
        }
        
        function resetZoom() {
            currentZoom = 1;
            if (currentImage) {
                currentImage.style.transform = 'scale(1)';
            }
            document.getElementById('zoomLevel').textContent = '100%';
            const container = document.getElementById('modalImageContainer');
            if (container) {
                container.scrollLeft = 0;
                container.scrollTop = 0;
                container.style.cursor = 'default';
            }
        }

        function closeModal() {
            if (!currentModal) return;

            currentModal.classList.remove('active');

            // Unlock scrolling and restore the exact position (safety net)
            document.documentElement.classList.remove('modal-open');
            window.scrollTo(0, savedScrollY);

            currentModal = null;
            currentImage = null;
            resetZoom();
        }

        window.onclick = function(event) {
            if (event.target === currentModal) {
                closeModal();
            }
        }

        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape' && currentModal) {
                closeModal();
            }
        });

        // ============================================
        // DARK / LIGHT MODE TOGGLE
        // ============================================
        (function () {
            const themeButton = document.getElementById('themeToggleNav');
            const themeIcon = document.getElementById('themeToggleIcon');
            const themeText = document.getElementById('themeToggleText');
            const storageKey = 'css_tesda_theme';

            function applyTheme(theme) {
                document.documentElement.setAttribute('data-theme', theme);
                localStorage.setItem(storageKey, theme);

                if (theme === 'light') {
                    themeIcon.className = 'fas fa-moon';
                    themeText.textContent = 'Dark Mode';
                    themeButton.title = 'Switch to dark mode';
                } else {
                    themeIcon.className = 'fas fa-sun';
                    themeText.textContent = 'Light Mode';
                    themeButton.title = 'Switch to light mode';
                }
            }

            const savedTheme = localStorage.getItem(storageKey);
            const initialTheme = savedTheme === 'light' ? 'light' : 'dark';

            if (themeButton) {
                applyTheme(initialTheme);
                themeButton.addEventListener('click', function (event) {
                    event.preventDefault();
                    const current = document.documentElement.getAttribute('data-theme') || 'dark';
                    applyTheme(current === 'dark' ? 'light' : 'dark');
                });
            } else {
                document.documentElement.setAttribute('data-theme', initialTheme);
            }
        })();
    </script>
     <?php if (file_exists(__DIR__ . '/includes/brightness_widget.php')) { $global_brightness = function_exists('getGlobalBrightness') ? getGlobalBrightness() : 100; include __DIR__ . '/includes/brightness_widget.php'; } ?>
</body>
</html>
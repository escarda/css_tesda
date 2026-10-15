<?php
// coc3_review.php - COC 3: Set-up Computer Servers
// Same Android WebView-safe layout/modal as coc1_review.php

// Session is started safely by config.php
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$coc_number = 3;
$page_title = "COC 3: Set-up Computer Servers";

$sql = "
    SELECT rm.id, rm.coc_number, rm.title, rm.content, rm.category
    FROM review_materials rm
    INNER JOIN (
        SELECT MIN(id) AS id
        FROM review_materials
        WHERE coc_number = ?
        GROUP BY LOWER(TRIM(title))
    ) unique_materials ON rm.id = unique_materials.id
    ORDER BY rm.id
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $coc_number);
$stmt->execute();
$materials = $stmt->get_result();
$stmt->close();

$fallback_image = 'https://upload.wikimedia.org/wikipedia/commons/thumb/2/2a/Computer_case_side_open.jpg/500px-Computer_case_side_open.jpg';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <?php if (file_exists(__DIR__ . '/includes/theme_preload.php')) { include __DIR__ . '/includes/theme_preload.php'; } ?>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <title><?php echo htmlspecialchars($page_title, ENT_QUOTES, 'UTF-8'); ?> | CSS TESDA Reviewer</title>
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
        html.modal-open,
        body.modal-open {
            overflow: hidden !important;
            touch-action: none;
        }

        /* Android WebView-safe modal layer */
        body {
            position: relative;
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

        /* ---------- Key topics ---------- */
        .key-topics {
            margin-bottom: 2rem;
            padding: 1.5rem;
            background: var(--bg-card);
            border: 1px solid var(--border-light);
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
            grid-template-columns: repeat(auto-fill, minmax(min(250px, 100%), 1fr));
            gap: 0.8rem;
            list-style: none;
        }

        .topic-list li {
            padding: 0.5rem 1rem;
            background: var(--bg-stat);
            border-radius: 8px;
            display: flex;
            align-items: center;
            gap: 10px;
            color: var(--text-primary);
        }

        .topic-list li i {
            color: #00ff9d;
        }

        /* ---------- Cards ---------- */
        .materials-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(min(380px, 100%), 1fr));
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
            -webkit-tap-highlight-color: transparent;
            touch-action: manipulation;
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

        /* ---------- Quiz section ---------- */
        .quiz-section {
            margin-top: 3rem;
            padding: 2rem;
            background: var(--bg-card);
            border: 1px solid var(--border-light);
            border-radius: 20px;
            text-align: center;
        }

        .quiz-section h2 {
            color: #00ff9d;
            margin-bottom: 1rem;
        }

        .quiz-section p {
            color: var(--text-secondary);
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

        /* ============================================
           MODAL - ANDROID WEBVIEW SAFE
           ============================================ */
        .modal {
            display: none;
            position: fixed !important;
            top: 0 !important;
            right: 0 !important;
            bottom: 0 !important;
            left: 0 !important;
            width: 100% !important;
            height: 100% !important;
            min-height: 0 !important;
            margin: 0 !important;
            padding: 10px !important;
            background: rgba(5, 8, 18, 0.88);
            z-index: 2147483000 !important;
            overflow: hidden !important;
            box-sizing: border-box;
            transform: none !important;
            align-items: center;
            justify-content: center;
        }

        .modal.active {
            display: flex !important;
        }

        .modal-content {
            width: calc(100% - 20px);
            max-width: 780px;
            height: calc(100% - 20px);
            max-height: 900px;
            margin: 0 auto;
            background: var(--bg-panel, #0a1428);
            border: 2px solid #00ff9d;
            border-radius: 18px;
            box-shadow: 0 25px 80px rgba(0, 255, 157, 0.22);
            overflow: hidden;
            display: flex;
            flex-direction: column;
            position: relative;
            box-sizing: border-box;
        }

        .modal-header {
            flex: 0 0 auto;
            min-height: 58px;
            padding: 0.85rem 1rem;
            border-bottom: 1px solid rgba(0, 255, 157, 0.2);
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 10px;
        }

        .modal-header h2 {
            font-family: 'Orbitron', sans-serif;
            color: #00ff9d;
            font-size: 1.25rem;
            line-height: 1.3;
            overflow-wrap: anywhere;
            word-break: break-word;
            min-width: 0;
            flex: 1;
        }

        .close-modal {
            flex: 0 0 auto;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: transparent;
            border: 0;
            color: #8899cc;
            font-size: 1.8rem;
            line-height: 1;
            cursor: pointer;
            -webkit-tap-highlight-color: transparent;
        }

        .close-modal:hover,
        .close-modal:active {
            color: #ef4444;
        }

        .image-container {
            position: relative;
            flex: 0 1 42%;
            min-height: 190px;
            max-height: 46%;
            background: #0a0f1e;
            padding: 0.5rem;
            box-sizing: border-box;
            overflow: hidden;
        }

        .zoom-controls {
            position: absolute;
            bottom: 10px;
            left: 50%;
            transform: translateX(-50%);
            display: flex;
            gap: 6px;
            z-index: 20;
            background: rgba(0, 0, 0, 0.78);
            padding: 5px 7px;
            border-radius: 40px;
            border: 1px solid rgba(0, 255, 157, 0.3);
            white-space: nowrap;
        }

        .zoom-btn {
            background: rgba(0, 255, 157, 0.2);
            border: none;
            color: #00ff9d;
            width: 34px;
            height: 34px;
            border-radius: 50%;
            cursor: pointer;
            font-size: 1.15rem;
            font-weight: bold;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0;
            -webkit-tap-highlight-color: transparent;
        }

        .zoom-reset {
            background: rgba(255, 165, 0, 0.2);
            color: #ffaa00;
        }

        .zoom-level {
            color: #00ff9d;
            font-size: 0.75rem;
            display: flex;
            align-items: center;
            padding: 0 5px;
            font-family: monospace;
        }

        .modal-image {
            width: 100%;
            height: 100%;
            overflow: auto;
            -webkit-overflow-scrolling: touch;
            overscroll-behavior: contain;
            background: #0a0f1e;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: default;
            position: relative;
            box-sizing: border-box;
        }

        .modal-image img {
            display: block;
            width: auto;
            height: auto;
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
            transform-origin: center center;
            transition: transform 0.1s ease;
            pointer-events: none;
            flex: 0 0 auto;
        }

        .pan-hint {
            position: absolute;
            bottom: 62px;
            left: 50%;
            transform: translateX(-50%);
            background: rgba(0, 0, 0, 0.55);
            padding: 4px 8px;
            border-radius: 20px;
            font-size: 0.65rem;
            color: #8899cc;
            pointer-events: none;
            white-space: nowrap;
            z-index: 15;
        }

        .modal-body {
            flex: 1 1 auto;
            min-height: 0;
            padding: 1rem 1.15rem;
            overflow-y: auto;
            -webkit-overflow-scrolling: touch;
            box-sizing: border-box;
        }

        .modal-body p {
            color: #cbd5e1;
            line-height: 1.65;
            margin: 0;
            white-space: pre-line;
            overflow-wrap: anywhere;
            word-break: normal;
        }

        html[data-theme="light"] .modal-body p {
            color: #33433d;
        }

        .modal-category {
            display: inline-block;
            padding: 4px 12px;
            background: rgba(0, 255, 157, 0.1);
            border-radius: 20px;
            font-size: 0.8rem;
            color: #00ff9d;
            margin-bottom: 0.75rem;
        }

        /* ---------- Small screens ---------- */
        @media (max-width: 768px) {
            .navbar {
                padding: 0.8rem 1rem;
            }

            .nav-links {
                width: 100%;
                justify-content: center;
                gap: 1rem;
            }

            .theme-toggle-nav {
                padding: 7px 10px;
            }

            .container {
                padding: 1rem;
            }

            .page-header h1 {
                font-size: 1.8rem;
            }

            .materials-grid {
                grid-template-columns: 1fr;
            }

            .card-image {
                height: 220px;
            }

            .modal {
                padding: 7px !important;
            }

            .modal-content {
                width: calc(100% - 4px);
                height: calc(100% - 4px);
                max-height: none;
                border-radius: 15px;
            }

            .modal-header {
                min-height: 54px;
                padding: 0.65rem 0.75rem;
            }

            .modal-header h2 {
                font-size: 0.92rem;
                line-height: 1.25;
            }

            .close-modal {
                width: 36px;
                height: 36px;
                font-size: 1.55rem;
            }

            .image-container {
                flex: 0 0 38%;
                height: 38%;
                min-height: 175px;
                max-height: 38%;
                padding: 0.35rem;
            }

            .modal-image {
                height: 100%;
            }

            .modal-image img {
                max-width: 100%;
                max-height: 100%;
            }

            .modal-body {
                flex: 1 1 auto;
                padding: 0.8rem 0.9rem 1rem;
            }

            .modal-body p {
                font-size: 0.9rem;
                line-height: 1.5;
            }

            .modal-category {
                font-size: 0.72rem;
                padding: 3px 9px;
                margin-bottom: 0.55rem;
            }

            .zoom-controls {
                bottom: 7px;
            }

            .zoom-btn {
                width: 31px;
                height: 31px;
                font-size: 1rem;
            }

            .zoom-level {
                font-size: 0.68rem;
                padding: 0 3px;
            }

            .pan-hint {
                display: none;
            }
        }

        @media (max-width: 768px) and (orientation: landscape) {
            .modal-content {
                width: calc(100% - 8px);
                height: calc(100% - 8px);
            }

            .modal-header {
                min-height: 46px;
                padding: 0.45rem 0.7rem;
            }

            .modal-header h2 {
                font-size: 0.85rem;
            }

            .image-container {
                flex: 0 0 45%;
                height: 45%;
                max-height: 45%;
                min-height: 130px;
            }

            .modal-body {
                padding: 0.6rem 0.8rem;
            }
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
                <a href="coc3_review.php" class="active"><i class="fas fa-server"></i> COC 3</a>
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
                <i class="fas fa-server"></i> COC 3: Set-up Computer Servers
            </div>
            <h1>Complete Server Administration</h1>
            <p>Click on any card to view detailed information. Use + / - buttons or mouse wheel to zoom images.</p>
        </div>

        <div class="key-topics">
            <h3><i class="fas fa-star-of-life"></i> All Topics Covered</h3>
            <ul class="topic-list">
                <li><i class="fas fa-server"></i> Server Fundamentals & Hardware</li>
                <li><i class="fas fa-microchip"></i> Server CPUs, ECC RAM, RAID</li>
                <li><i class="fab fa-windows"></i> Windows Server & Linux</li>
                <li><i class="fas fa-users"></i> Active Directory (AD DS, DC, GPO)</li>
                <li><i class="fas fa-folder-open"></i> File & Print Services</li>
                <li><i class="fas fa-globe"></i> Web Servers (IIS, Apache, Nginx)</li>
                <li><i class="fas fa-database"></i> Database Servers</li>
                <li><i class="fas fa-cube"></i> Virtualization (Hyper-V, VMware)</li>
                <li><i class="fas fa-shield-alt"></i> Server Security & Hardening</li>
                <li><i class="fas fa-archive"></i> Backup & Disaster Recovery</li>
                <li><i class="fas fa-chart-line"></i> Monitoring & Troubleshooting</li>
                <li><i class="fas fa-network-wired"></i> Network Services (DHCP, DNS, VPN)</li>
                <li><i class="fas fa-sync"></i> High Availability & Clustering</li>
            </ul>
        </div>

        <div class="materials-grid">
            <?php if ($materials->num_rows > 0): ?>
                <?php while ($material = $materials->fetch_assoc()):
                    $title_lower = strtolower(trim($material['title']));
                    $image_url = $fallback_image;

                    if (strpos($title_lower,'introduction to server')!==false || strpos($title_lower,'what is a server')!==false) $image_url='https://i.pinimg.com/736x/29/bf/5d/29bf5d97bf0a70546b8edf365e6d7a37.jpg';
                    elseif (strpos($title_lower,'server vs desktop')!==false || strpos($title_lower,'desktop pc')!==false) $image_url='https://i.pinimg.com/736x/58/7e/f2/587ef2a3c90246f8f4ac93182b51c927.jpg';
                    elseif (strpos($title_lower,'form factor')!==false || strpos($title_lower,'tower server')!==false || strpos($title_lower,'rack server')!==false) $image_url='https://i.pinimg.com/1200x/f7/7d/ec/f77decfad89b3a8e0825955c77dba217.jpg';
                    elseif (strpos($title_lower,'server hardware')!==false || strpos($title_lower,'hardware components')!==false) $image_url='https://i.pinimg.com/736x/47/88/44/478844ca5ebb3732e79a21e653b19a0a.jpg';
                    elseif (strpos($title_lower,'raid')!==false) $image_url='https://i.pinimg.com/1200x/23/d9/ae/23d9ae1e025f5b194cbc63338bf2f751.jpg';
                    elseif (strpos($title_lower,'windows server')!==false) $image_url='https://i.pinimg.com/736x/4b/41/c0/4b41c06aa6e13d73861f6965a3f1ab1d.jpg';
                    elseif (strpos($title_lower,'linux')!==false || strpos($title_lower,'ubuntu')!==false) $image_url='https://i.pinimg.com/736x/8b/8c/fc/8b8cfc5c57dfd820ed3d4e0728fb9295.jpg';
                    elseif (strpos($title_lower,'installation method')!==false || strpos($title_lower,'pxe')!==false) $image_url='https://i.pinimg.com/736x/12/8a/d0/128ad035c8a7fbf4d57b4d877c4a446c.jpg';
                    elseif (strpos($title_lower,'active directory')!==false) $image_url='https://i.pinimg.com/736x/29/26/9c/29269c8df80d708caab74a50074ba6d0.jpg';
                    elseif (strpos($title_lower,'domain controller')!==false) $image_url='https://i.pinimg.com/736x/f6/84/d8/f684d8da77bc1844b6f7bdb08d5f254b.jpg';
                    elseif (strpos($title_lower,'organizational unit')!==false || strpos($title_lower,'gpo')!==false) $image_url='https://i.pinimg.com/1200x/32/00/49/320049d5b5a43fecaa7c52a41ae1adae.jpg';
                    elseif (strpos($title_lower,'file server')!==false) $image_url='https://i.pinimg.com/736x/b4/53/75/b4537539c4add90ae0c3a407627830c3.jpg';
                    elseif (strpos($title_lower,'ntfs')!==false) $image_url='https://i.pinimg.com/1200x/62/9c/10/629c10cc4892bfb3072d3dccddba104b.jpg';
                    elseif (strpos($title_lower,'dfs')!==false) $image_url='https://i.pinimg.com/1200x/e2/ab/f5/e2abf5ade4dc9a3961090fba621e5342.jpg';
                    elseif (strpos($title_lower,'iis')!==false) $image_url='https://i.pinimg.com/736x/ff/30/52/ff30520c3e1959982fc7609667d3bf1b.jpg';
                    elseif (strpos($title_lower,'apache')!==false) $image_url='https://i.pinimg.com/1200x/cd/d1/7f/cdd17fe854fe1f57b1cefd60d156f1bf.jpg';
                    elseif (strpos($title_lower,'nginx')!==false) $image_url='https://i.pinimg.com/1200x/5e/53/74/5e53749d819a8610a64657610f297a1c.jpg';
                    elseif (strpos($title_lower,'database')!==false || strpos($title_lower,'sql')!==false) $image_url='https://i.pinimg.com/1200x/61/cb/5d/61cb5d42a4e0df22578282b8c5384fcb.jpg';
                    elseif (strpos($title_lower,'virtualization')!==false) $image_url='https://i.pinimg.com/1200x/89/69/17/896917cc3a9ad6c7a3aa3fe6c4e474d9.jpg';
                    elseif (strpos($title_lower,'hyper-v')!==false) $image_url='https://i.pinimg.com/1200x/fa/0f/df/fa0fdf375f5862847faa26da752a34f1.jpg';
                    elseif (strpos($title_lower,'vmware')!==false) $image_url='https://i.pinimg.com/736x/dd/1c/00/dd1c00aa8fba4a73f69b595635b8ec91.jpg';
                    elseif (strpos($title_lower,'container')!==false || strpos($title_lower,'docker')!==false) $image_url='https://i.pinimg.com/1200x/e1/bb/71/e1bb7108b403a228081181f58cf9c562.jpg';
                    elseif (strpos($title_lower,'hardening')!==false) $image_url='https://i.pinimg.com/1200x/ca/a8/46/caa8468c12e3ee63041febf9bd2e26b8.jpg';
                    elseif (strpos($title_lower,'firewall')!==false) $image_url='https://i.pinimg.com/736x/8c/5d/a8/8c5da8293f431f9ce3977abf49313cf4.jpg';
                    elseif (strpos($title_lower,'ssl')!==false || strpos($title_lower,'tls')!==false) $image_url='https://i.pinimg.com/1200x/49/5f/5d/495f5dd358812947f076678411a52ce5.jpg';
                    elseif (strpos($title_lower,'backup')!==false) $image_url='https://i.pinimg.com/736x/42/25/50/422550420559210d5db69645a6022443.jpg';
                    elseif (strpos($title_lower,'bare metal')!==false) $image_url='https://i.pinimg.com/736x/96/11/a1/9611a1efbe3949a319ae3b081576a573.jpg';
                    elseif (strpos($title_lower,'performance')!==false) $image_url='https://i.pinimg.com/1200x/b1/e2/62/b1e262a65f2dd9bd049f9ed502ff5593.jpg';
                    elseif (strpos($title_lower,'event viewer')!==false) $image_url='https://i.pinimg.com/736x/68/cf/00/68cf00f43cb1afcd06778c40b82ead7b.jpg';
                    elseif (strpos($title_lower,'powershell')!==false) $image_url='https://i.pinimg.com/1200x/4d/f4/41/4df44103b2cee1cbe3fa99efe48e35a5.jpg';
                    elseif (strpos($title_lower,'troubleshooting')!==false) $image_url='https://i.pinimg.com/736x/78/10/dc/7810dc21bfaffef5b8a1d4e936816f38.jpg';
                    elseif (strpos($title_lower,'common server')!==false || strpos($title_lower,'common issues')!==false) $image_url='https://i.pinimg.com/1200x/a6/54/be/a654be1398c4facecf7d43f18a8c2926.jpg';
                    elseif (strpos($title_lower,'dhcp')!==false) $image_url='https://i.pinimg.com/1200x/f5/2d/f8/f52df8a75ad612c5ee617fffa27fdb9b.jpg';
                    elseif (strpos($title_lower,'dns')!==false) $image_url='https://i.pinimg.com/736x/84/d4/a9/84d4a95385f99480e691316a383958af.jpg';
                    elseif (strpos($title_lower,'vpn')!==false) $image_url='https://i.pinimg.com/736x/d2/ae/91/d2ae91d4ff209b9b317fb88f47774cdb.jpg';
                    elseif (strpos($title_lower,'remote desktop')!==false || strpos($title_lower,'rds')!==false) $image_url='https://i.pinimg.com/736x/24/d6/17/24d6179d0125ff9057ca65e99ee59089.jpg';
                    elseif (strpos($title_lower,'failover')!==false || strpos($title_lower,'cluster')!==false) $image_url='https://i.pinimg.com/736x/83/bc/d7/83bcd7f082c550b65d6cc1876d6c8d27.jpg';

                    // Category icon (Windows is a brand icon, so it needs the "fab" prefix)
                    $icon = 'server';
                    $category = strtolower(trim((string)$material['category']));
                    if ($category === 'web-server') $icon = 'globe';
                    elseif ($category === 'application') $icon = 'database';
                    elseif ($category === 'active-directory') $icon = 'users';
                    elseif ($category === 'virtualization') $icon = 'cube';
                    elseif ($category === 'security') $icon = 'shield-alt';
                    elseif ($category === 'backup') $icon = 'archive';
                    elseif ($category === 'storage') $icon = 'hdd';
                    elseif ($category === 'server-os') $icon = 'windows';
                    elseif ($category === 'file-services') $icon = 'folder-open';
                    elseif ($category === 'monitoring') $icon = 'chart-line';
                    elseif ($category === 'network-services') $icon = 'network-wired';
                    elseif ($category === 'remote-access') $icon = 'desktop';
                    elseif ($category === 'high-availability') $icon = 'sync';
                    $icon_prefix = ($icon === 'windows') ? 'fab' : 'fas';
                ?>
                    <div class="material-card" data-material='<?php echo htmlspecialchars(json_encode($material, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), ENT_QUOTES, "UTF-8"); ?>' data-image='<?php echo htmlspecialchars($image_url, ENT_QUOTES, "UTF-8"); ?>'>
                        <div class="card-image">
                            <img src="<?php echo htmlspecialchars($image_url, ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($material['title'], ENT_QUOTES, 'UTF-8'); ?>" loading="lazy" onerror="this.onerror=null; this.src='<?php echo $fallback_image; ?>'">
                        </div>
                        <div class="card-header">
                            <i class="<?php echo $icon_prefix; ?> fa-<?php echo htmlspecialchars($icon, ENT_QUOTES, 'UTF-8'); ?>"></i>
                            <h3><?php echo htmlspecialchars($material['title'], ENT_QUOTES, 'UTF-8'); ?></h3>
                        </div>
                        <div class="card-body">
                            <span class="category-tag">
                                <i class="fas fa-tag"></i> <?php echo htmlspecialchars(ucfirst(str_replace('-', ' ', $category)), ENT_QUOTES, 'UTF-8'); ?>
                            </span>
                            <p><?php echo nl2br(htmlspecialchars(substr($material['content'], 0, 120), ENT_QUOTES, 'UTF-8')); ?>...</p>
                            <div class="read-more">
                                <i class="fas fa-mouse-pointer"></i> Click to read more
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div style="grid-column: 1/-1; text-align: center; padding: 3rem;">
                    <i class="fas fa-book-open" style="font-size: 3rem; color: #8899cc;"></i>
                    <p>No review materials found for COC 3. Please add topics to the database.</p>
                </div>
            <?php endif; ?>
        </div>

        <div class="quiz-section">
            <h2><i class="fas fa-question-circle"></i> Test Your Knowledge</h2>
            <p>Ready to assess your understanding? Take the COC 3 quiz to see how well you've mastered all the server topics.</p>
            <div class="quiz-buttons">
                <a href="quiz.php?coc=3" class="btn btn-primary"><i class="fas fa-play"></i> Take COC 3 Quiz</a>
                <a href="practice_mode.php?coc=3" class="btn btn-secondary"><i class="fas fa-chalkboard-teacher"></i> Practice Mode</a>
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
                    <img id="modalImage" src="" alt="" referrerpolicy="no-referrer">
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
        const FALLBACK_IMAGE = <?php echo json_encode($fallback_image); ?>;

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

            if (!modal || !modalTitle || !modalImage || !modalCategory || !modalContent || !container) {
                return;
            }

            const category = String((material && material.category) || '').replace(/-/g, ' ');

            modalTitle.textContent = material && material.title ? material.title : '';
            modalCategory.textContent = category
                ? category.charAt(0).toUpperCase() + category.slice(1)
                : '';
            modalContent.textContent = material && material.content ? material.content : '';

            currentModal = modal;
            currentImage = modalImage;
            currentZoom = 1;
            isPanning = false;

            modalImage.style.transform = 'scale(1)';
            modalImage.alt = material && material.title ? material.title : 'Review material image';
            container.scrollLeft = 0;
            container.scrollTop = 0;
            container.style.cursor = 'default';

            const zoomLevel = document.getElementById('zoomLevel');
            if (zoomLevel) zoomLevel.textContent = '100%';

            // Keep the modal attached to the Android WebView viewport.
            // Do NOT move it to the document scroll position; doing so can
            // leave only a thin border/green line visible on some WebViews.
            savedScrollY = window.pageYOffset || document.documentElement.scrollTop || 0;
            modal.style.position = 'fixed';
            modal.style.top = '0';
            modal.style.right = '0';
            modal.style.bottom = '0';
            modal.style.left = '0';
            modal.style.width = '100%';
            modal.style.height = '100%';
            modal.style.minHeight = '0';
            modal.style.display = 'flex';
            modal.classList.add('active');

            // Lock page scrolling after the modal has been placed.
            document.documentElement.classList.add('modal-open');
            document.body.classList.add('modal-open');

            modalImage.onload = function () {
                container.scrollLeft = 0;
                container.scrollTop = 0;
            };

            modalImage.onerror = function () {
                modalImage.onerror = null;
                modalImage.src = FALLBACK_IMAGE;
            };

            modalImage.src = imageUrl || FALLBACK_IMAGE;
            setupPanning();
        }

        // Android WebView: delegated click so card taps are reliable on phones.
        document.addEventListener('click', function(event) {
            const card = event.target.closest ? event.target.closest('.material-card') : null;
            if (!card || event.target.closest('button, a')) return;
            const data = card.getAttribute('data-material');
            const image = card.getAttribute('data-image');
            if (data) {
                try {
                    openModal(JSON.parse(data), image || '');
                } catch (e) {
                    console.error('Could not open material card:', e);
                }
            }
        }, false);

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
            container.style.cursor = currentZoom > 1 ? 'grab' : 'default';
        }

        function panImage(e) {
            if (!isPanning) return;
            e.preventDefault();
            const container = document.getElementById('modalImageContainer');
            const walkX = (e.clientX - startX) * 1.5;
            const walkY = (e.clientY - startY) * 1.5;
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

            const modalToClose = currentModal;
            modalToClose.classList.remove('active');
            modalToClose.style.display = 'none';

            document.documentElement.classList.remove('modal-open');
            document.body.classList.remove('modal-open');

            currentModal = null;
            currentImage = null;
            resetZoom();

            // Restore the exact position after unlocking scroll.
            window.scrollTo(0, savedScrollY);
        }

        window.addEventListener('click', function(event) {
            if (currentModal && event.target === currentModal) {
                closeModal();
            }
        }, false);

        // Android WebView touch support for the modal backdrop.
        window.addEventListener('touchend', function(event) {
            if (currentModal && event.target === currentModal) {
                closeModal();
            }
        }, { passive: true });

        document.addEventListener('keydown', function(event) {
            if (!currentModal) return;

            if (event.key === 'Escape') closeModal();
            if (event.key === '+' || event.key === '=') zoomImage(0.1);
            if (event.key === '-' || event.key === '_') zoomImage(-0.1);
            if (event.key === '0') resetZoom();
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

                if (!themeButton) return;

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
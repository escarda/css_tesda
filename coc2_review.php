<?php
// coc2_review.php - COC 2: Set-up Computer Networks
// Same Android WebView-safe layout/modal as coc1_review.php
// Text content from database, images hardcoded in PHP

require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$coc_number = 2;
$page_title = "COC 2: Set-up Computer Networks";

// Get UNIQUE review materials for COC 2 from database
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

$fallback_image = 'https://upload.wikimedia.org/wikipedia/commons/thumb/3/3b/Network_switch.jpg/640px-Network_switch.jpg';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <?php if (file_exists(__DIR__ . '/includes/theme_preload.php')) { include __DIR__ . '/includes/theme_preload.php'; } ?>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
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
                <a href="coc2_review.php" class="active"><i class="fas fa-network-wired"></i> COC 2</a>
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
                <i class="fas fa-network-wired"></i> COC 2: Set-up Computer Networks
            </div>
            <h1>Complete Network Configuration & Setup</h1>
            <p>Click on any card to view detailed information. Use + / - buttons or mouse wheel to zoom images.</p>
        </div>

        <div class="key-topics">
            <h3><i class="fas fa-star-of-life"></i> All Topics Covered</h3>
            <ul class="topic-list">
                <li><i class="fas fa-sitemap"></i> Network Topologies (Star, Bus, Ring, Mesh, Hybrid)</li>
                <li><i class="fas fa-plug"></i> Network Cabling (Twisted Pair, Fiber Optic, Coaxial)</li>
                <li><i class="fas fa-microchip"></i> Networking Devices (NIC, Hub, Switch, Router, AP)</li>
                <li><i class="fas fa-address-card"></i> IP Addressing (IPv4, IPv6, Classes, Subnetting)</li>
                <li><i class="fas fa-exchange-alt"></i> Network Protocols (TCP/IP, DNS, DHCP, HTTP, FTP)</li>
                <li><i class="fas fa-wifi"></i> Wireless Networking (Wi-Fi Standards, Security)</li>
                <li><i class="fas fa-terminal"></i> Network Commands (ping, ipconfig, tracert, nslookup)</li>
                <li><i class="fas fa-tools"></i> Network Troubleshooting</li>
                <li><i class="fas fa-shield-alt"></i> Network Security (Firewalls, VLANs, VPNs)</li>
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

                    /* =====================================================
                       NETWORKING IMAGES
                       ===================================================== */

                    // Network Fundamentals
                    if (
                        strpos($title_lower, 'introduction to computer networks') !== false ||
                        strpos($title_lower, 'what is a network') !== false
                    ) {
                        $image_url = 'https://i.pinimg.com/1200x/07/1a/17/071a1765ba1ce060a34bb3b359245bab.jpg';

                    } elseif (
                        strpos($title_lower, 'types of networks') !== false ||
                        strpos($title_lower, 'pan') !== false ||
                        strpos($title_lower, 'lan') !== false
                    ) {
                        $image_url = 'https://i.pinimg.com/736x/78/26/a1/7826a13009a293cd8e64da4648790d99.jpg';

                    } elseif (
                        strpos($title_lower, 'client-server') !== false ||
                        strpos($title_lower, 'peer-to-peer') !== false
                    ) {
                        $image_url = 'https://i.pinimg.com/1200x/f9/54/54/f954542bbbd9a911b5c071c859f5e622.jpg';

                    // Network Topologies
                    } elseif (strpos($title_lower, 'star topology') !== false) {
                        $image_url = 'https://i.pinimg.com/1200x/78/48/48/7848481682c55d9f31834e5128fbbd9b.jpg';

                    } elseif (strpos($title_lower, 'bus topology') !== false) {
                        $image_url = 'https://i.pinimg.com/1200x/c6/f0/4b/c6f04b011d839b3d87d536e4d5a6cab6.jpg';

                    } elseif (strpos($title_lower, 'ring topology') !== false) {
                        $image_url = 'https://i.pinimg.com/736x/a0/97/7d/a0977dc4b650b93e0cc9c725cf0cf0b2.jpg';

                    } elseif (strpos($title_lower, 'mesh topology') !== false) {
                        $image_url = 'https://i.pinimg.com/1200x/13/ed/cb/13edcb219d9647913e4eae1552c9d55f.jpg';

                    } elseif (strpos($title_lower, 'hybrid topology') !== false) {
                        $image_url = 'https://i.pinimg.com/1200x/4e/fc/5b/4efc5b25a81649ff90abac4d66792e3b.jpg';

                    // Network Cabling
                    } elseif (strpos($title_lower, 'twisted pair') !== false) {
                        $image_url = 'https://i.pinimg.com/736x/b1/d3/d1/b1d3d157bec85a935ed22b1d8e2e3845.jpg';

                    } elseif (strpos($title_lower, 'fiber optic') !== false) {
                        $image_url = 'https://i.pinimg.com/736x/cc/35/66/cc35665b872dc84cc07aeb7104f05b1b.jpg';

                    } elseif (strpos($title_lower, 'coaxial') !== false) {
                        $image_url = 'https://i.pinimg.com/736x/17/76/c2/1776c2066c80ef09da88d8791657f967.jpg';

                    } elseif (
                        strpos($title_lower, 't568a') !== false ||
                        strpos($title_lower, 't568b') !== false
                    ) {
                        $image_url = 'https://i.pinimg.com/736x/1b/d4/1a/1bd41ab5a8b635b55f31fa01e268ac6f.jpg';

                    } elseif (
                        strpos($title_lower, 'straight-through') !== false ||
                        strpos($title_lower, 'crossover') !== false
                    ) {
                        $image_url = 'https://i.pinimg.com/736x/52/d9/7c/52d97cade557f1201e18a02ae134a064.jpg';

                    // Network Devices
                    } elseif (
                        strpos($title_lower, 'nic') !== false ||
                        strpos($title_lower, 'network interface card') !== false
                    ) {
                        $image_url = 'https://i.pinimg.com/736x/9d/48/e2/9d48e21925cf6324c92a6a39cbc30f05.jpg';

                    } elseif (
                        strpos($title_lower, 'hub vs switch') !== false ||
                        strpos($title_lower, 'router') !== false
                    ) {
                        $image_url = 'https://i.pinimg.com/1200x/8e/a5/c7/8ea5c79e77cf6674b8924bd3207bb21c.jpg';

                    } elseif (strpos($title_lower, 'access point') !== false) {
                        $image_url = 'https://i.pinimg.com/1200x/bd/d0/a0/bdd0a06d5510f352f4e2ecc97aa22f18.jpg';

                    } elseif (strpos($title_lower, 'modem') !== false) {
                        $image_url = 'https://i.pinimg.com/736x/12/4c/38/124c384c8e060e679304d04521649862.jpg';

                    } elseif (strpos($title_lower, 'firewall') !== false) {
                        $image_url = 'https://i.pinimg.com/1200x/e9/94/7f/e9947fcdaef986fcf2b20f1811b98d46.jpg';

                    // IP Addressing
                    } elseif (strpos($title_lower, 'ipv4 address structure') !== false) {
                        $image_url = 'https://i.pinimg.com/1200x/2f/68/d9/2f68d987e3e9349f3cce1db6cbe01dc3.jpg';

                    } elseif (strpos($title_lower, 'ipv4 address classes') !== false) {
                        $image_url = 'https://i.pinimg.com/1200x/20/c5/d8/20c5d84f27f1d71d2148bd4282fe60e3.jpg';

                    } elseif (strpos($title_lower, 'private ip') !== false) {
                        $image_url = 'https://i.pinimg.com/1200x/ec/a0/77/eca0770b4f02ed4c5e4a9effbfc9ab91.jpg';

                    } elseif (strpos($title_lower, 'subnetting') !== false) {
                        $image_url = 'https://i.pinimg.com/1200x/eb/79/95/eb7995886e83a80a6dff04fa016a3c11.jpg';

                    } elseif (strpos($title_lower, 'ipv6') !== false) {
                        $image_url = 'https://i.pinimg.com/1200x/98/91/f9/9891f9071c9e46425e99f6660b516565.jpg';

                    // Network Protocols
                    } elseif (strpos($title_lower, 'tcp/ip') !== false) {
                        $image_url = 'https://i.pinimg.com/1200x/f5/49/3c/f5493c471fd040e197af528d615cd37c.jpg';

                    } elseif (
                        strpos($title_lower, 'http') !== false ||
                        strpos($title_lower, 'https') !== false
                    ) {
                        $image_url = 'https://i.pinimg.com/736x/08/b0/35/08b03526834ca92af5c20fcf8ebe6e92.jpg';

                    } elseif (strpos($title_lower, 'dns') !== false) {
                        $image_url = 'https://i.pinimg.com/1200x/14/a0/18/14a01889d6a5f8ddc3a6bd8a3337554f.jpg';

                    } elseif (strpos($title_lower, 'dhcp') !== false) {
                        $image_url = 'https://i.pinimg.com/1200x/ea/56/b4/ea56b4c5dafbfc70994df56ce215c30b.jpg';

                    } elseif (
                        strpos($title_lower, 'email protocols') !== false ||
                        strpos($title_lower, 'smtp') !== false
                    ) {
                        $image_url = 'https://i.pinimg.com/1200x/18/99/64/189964c51f850369ef3987738e4cf89d.jpg';

                    } elseif (
                        strpos($title_lower, 'ftp') !== false ||
                        strpos($title_lower, 'sftp') !== false
                    ) {
                        $image_url = 'https://i.pinimg.com/1200x/53/cb/30/53cb30827bc2c1722b7c7d4e7f54f7c2.jpg';

                    // Wireless Networking
                    } elseif (
                        strpos($title_lower, 'wi-fi standards') !== false ||
                        strpos($title_lower, '802.11') !== false
                    ) {
                        $image_url = 'https://i.pinimg.com/736x/9a/6b/f6/9a6bf660a11e79de22e3a78ccd25a931.jpg';

                    } elseif (
                        strpos($title_lower, 'wireless security') !== false ||
                        strpos($title_lower, 'wep') !== false ||
                        strpos($title_lower, 'wpa') !== false
                    ) {
                        $image_url = 'https://i.pinimg.com/736x/35/a3/19/35a319211646e1316685d31a328d5b02.jpg';

                    } elseif (
                        strpos($title_lower, 'wi-fi channels') !== false ||
                        strpos($title_lower, 'interference') !== false
                    ) {
                        $image_url = 'https://i.pinimg.com/1200x/ec/7f/00/ec7f000b9868662adb611041f9b9ba10.jpg';

                    // Network Commands
                    } elseif (strpos($title_lower, 'ping') !== false) {
                        $image_url = 'https://i.pinimg.com/1200x/65/31/4a/65314a24f3bf3874a8140a803f63f808.jpg';

                    } elseif (strpos($title_lower, 'ipconfig') !== false) {
                        $image_url = 'https://i.pinimg.com/736x/70/8f/ff/708fff6dc3eabc553268815d221e6a63.jpg';

                    } elseif (
                        strpos($title_lower, 'tracert') !== false ||
                        strpos($title_lower, 'traceroute') !== false
                    ) {
                        $image_url = 'https://www.cloudns.net/blog/wp-content/uploads/2021/03/Traceroute-command-ClouDNS-3.png';

                    } elseif (strpos($title_lower, 'nslookup') !== false) {
                        $image_url = 'https://i.pinimg.com/1200x/16/2a/8f/162a8f3500146d596cf6b5d04202e5a2.jpg';

                    } elseif (strpos($title_lower, 'netstat') !== false) {
                        $image_url = 'https://i.pinimg.com/736x/1e/f6/f1/1ef6f1f8febeedc48fc4a0840e9a54a2.jpg';

                    // Troubleshooting
                    } elseif (strpos($title_lower, 'troubleshooting methodology') !== false) {
                        $image_url = 'https://i.pinimg.com/1200x/ce/7e/d7/ce7ed773b9b38979f5681f36d788f4b2.jpg';

                    } elseif (strpos($title_lower, 'common network issues') !== false) {
                        $image_url = 'https://i.pinimg.com/736x/bc/73/89/bc7389a24cb14637e11fd7e0974293f5.jpg';

                    } elseif (strpos($title_lower, 'osi model') !== false) {
                        $image_url = 'https://i.pinimg.com/1200x/bc/10/41/bc1041347fed2d53c42cfe8915d112e6.jpg';

                    // Security
                    } elseif (strpos($title_lower, 'firewall configuration') !== false) {
                        $image_url = 'https://i.pinimg.com/736x/8c/ae/10/8cae101a8794b022771d208db1c0d218.jpg';

                    } elseif (strpos($title_lower, 'vlan') !== false) {
                        $image_url = 'https://i.pinimg.com/736x/5e/e0/84/5ee084acf9eedc2b69c014ed4deaf478.jpg';

                    } elseif (strpos($title_lower, 'vpn') !== false) {
                        $image_url = 'https://i.pinimg.com/1200x/1d/1c/de/1d1cde52df26a174b03ecc7eb8a43f82.jpg';

                    // Default image
                    } else {
                        $image_url = $fallback_image;
                    }
                ?>
                    <div class="material-card" data-material='<?php echo htmlspecialchars(json_encode($material, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), ENT_QUOTES, "UTF-8"); ?>' data-image='<?php echo htmlspecialchars($image_url, ENT_QUOTES, "UTF-8"); ?>'>
                        <div class="card-image">
                            <img src="<?php echo htmlspecialchars($image_url, ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($material['title']); ?>" loading="lazy" onerror="this.onerror=null; this.src='<?php echo $fallback_image; ?>'">
                        </div>
                        <div class="card-header">
                            <i class="fas fa-network-wired"></i>
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
                    <p>Review materials for COC 2 will be available soon!</p>
                </div>
            <?php endif; ?>
        </div>

        <div class="quiz-section">
            <h2><i class="fas fa-question-circle"></i> Test Your Knowledge</h2>
            <p>Ready to assess your understanding? Take the COC 2 quiz to see how well you've mastered all the network topics.</p>
            <div class="quiz-buttons">
                <a href="quiz.php?coc=2" class="btn btn-primary"><i class="fas fa-play"></i> Take COC 2 Quiz</a>
                <a href="practice_mode.php?coc=2" class="btn btn-secondary"><i class="fas fa-chalkboard-teacher"></i> Practice Mode</a>
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

            modalTitle.textContent = material && material.title ? material.title : '';
            modalCategory.textContent = material && material.category
                ? material.category.charAt(0).toUpperCase() + material.category.slice(1)
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
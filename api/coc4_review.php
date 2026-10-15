<?php
// coc4_review.php - COMPLETE COC 4 Coverage (Maintain & Repair Computer Systems)
require_once 'config.php';
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit(); }
$coc_number = 4;
$page_title = "COC 4: Maintain & Repair Computer Systems";
$sql = "SELECT MIN(id) as id, coc_number, title, MIN(content) as content, MIN(category) as category FROM review_materials WHERE coc_number = ? GROUP BY title ORDER BY id";
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
        :root {
            --bg-primary: #0a0f1e;
            --bg-navbar: rgba(10, 20, 40, 0.9);
            --bg-card: rgba(0, 0, 0, 0.3);
            --bg-panel: rgba(0, 0, 0, 0.3);
            --bg-stat: rgba(0, 0, 0, 0.2);
            --text-primary: #e0e0ff;
            --text-secondary: #94a3b8;
            --text-muted: #8899cc;
            --border-color: rgba(0, 255, 157, 0.2);
            --border-light: rgba(0, 255, 157, 0.2);
            --cyber-bg1: #0a0f1e;
            --cyber-bg2: #050812;
            --grid-color: rgba(0, 255, 157, 0.03);
        }

        body.light-mode {
            --bg-primary: #f4f7fb;
            --bg-navbar: rgba(255, 255, 255, 0.92);
            --bg-card: rgba(255, 255, 255, 0.88);
            --bg-panel: rgba(255, 255, 255, 0.78);
            --bg-stat: rgba(255, 255, 255, 0.65);
            --text-primary: #172033;
            --text-secondary: #526174;
            --text-muted: #66758a;
            --border-color: rgba(0, 150, 105, 0.24);
            --border-light: rgba(0, 150, 105, 0.24);
            --cyber-bg1: #f4f7fb;
            --cyber-bg2: #eaf1f8;
            --grid-color: rgba(0, 150, 105, 0.07);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Rajdhani', sans-serif;
            background: var(--bg-primary);
            color: var(--text-primary);
            min-height: 100vh;
            overflow-x: hidden;
            transition: background 0.3s ease, color 0.3s ease;
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

        .theme-toggle {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 12px;
            border: 1px solid rgba(0, 255, 157, 0.35);
            border-radius: 999px;
            background: rgba(0, 255, 157, 0.08);
            color: #00ff9d;
            font-family: 'Rajdhani', sans-serif;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            white-space: nowrap;
        }

        .theme-toggle:hover {
            background: #00ff9d;
            color: #050812;
            transform: translateY(-1px);
            box-shadow: 0 5px 15px rgba(0, 255, 157, 0.25);
        }

        body.light-mode .theme-toggle {
            border-color: rgba(0, 130, 95, 0.35);
            background: rgba(0, 150, 105, 0.08);
            color: #008a65;
        }

        body.light-mode .theme-toggle:hover {
            background: #00a878;
            color: #ffffff;
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
            content: '054';
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

        /* MODAL WITH CONTROLLABLE ZOOM - kept as a dark lightbox in both
           themes on purpose, same convention as most image viewers/lightboxes,
           so photos keep good contrast regardless of page theme. */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.95);
            z-index: 1000;
            justify-content: center;
            align-items: center;
            backdrop-filter: blur(10px);
        }

        .modal.active {
            display: flex;
            animation: fadeIn 0.3s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .modal-content {
            background: var(--bg-navbar);
            border: 2px solid #00ff9d;
            border-radius: 24px;
            max-width: 900px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
            position: relative;
            animation: slideUp 0.3s ease;
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
            border-bottom: 1px solid var(--border-light);
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
            right: 15px;
            display: flex;
            gap: 10px;
            z-index: 10;
            background: rgba(0, 0, 0, 0.7);
            padding: 8px 12px;
            border-radius: 40px;
            backdrop-filter: blur(5px);
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
            cursor: grab;
            position: relative;
        }

        .modal-image:active {
            cursor: grabbing;
        }

        .modal-image img {
            max-width: none;
            transition: transform 0.1s ease;
            pointer-events: none;
        }

        .pan-hint {
            position: absolute;
            bottom: 70px;
            right: 15px;
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
            color: var(--text-primary);
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
            .user-info { justify-content: center; flex-wrap: wrap; }
            .card-image { height: 220px; }
            .modal-image { height: 300px; }
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
                <a href="coc4_review.php" class="active"><i class="fas fa-tools"></i> COC 4</a>
                
                <div class="user-info">
                    <i class="fas fa-user"></i>
                    <span><?php echo htmlspecialchars($_SESSION['full_name'] ?? $_SESSION['username']); ?></span>
                    <button type="button" class="theme-toggle" id="themeToggle" aria-label="Toggle light and dark mode" title="Toggle light and dark mode">
                        <i class="fas fa-moon" id="themeIcon"></i>
                        <span id="themeText">Dark Mode</span>
                    </button>
                    <a href="logout.php" style="color: #ef4444;"><i class="fas fa-sign-out-alt"></i></a>
                </div>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="page-header">
            <div class="coc-badge"><i class="fas fa-tools"></i> COC 4: Maintain & Repair Computer Systems</div>
            <h1>Complete Computer Maintenance & Repair</h1>
            <p>Click on any card to view detailed information. Use + / - buttons or mouse wheel to zoom images.</p>
        </div>
        <div class="key-topics">
            <h3><i class="fas fa-star-of-life"></i> All Topics Covered (50+ Topics)</h3>
            <ul class="topic-list">
                <li><i class="fas fa-shield-alt"></i> Preventive Maintenance (Cleaning, Thermal Management)</li>
                <li><i class="fas fa-search"></i> Diagnostic Tools & Software (Multimeter, POST Card)</li>
                <li><i class="fas fa-microchip"></i> Hardware Troubleshooting (CPU, RAM, Motherboard, PSU, HDD/SSD, GPU)</li>
                <li><i class="fas fa-tv"></i> Display & Graphics Issues (No Display, Artifacts)</li>
                <li><i class="fas fa-plug"></i> Power & Boot Issues (No Power, POST Errors, Beep Codes)</li>
                <li><i class="fas fa-window-maximize"></i> OS & Software Issues (BSOD, Slow Performance, Drivers)</li>
                <li><i class="fas fa-network-wired"></i> Network & Connectivity Issues (No Internet, Slow Network)</li>
                <li><i class="fas fa-archive"></i> Data Recovery & Backup Strategies</li>
                <li><i class="fas fa-shield-virus"></i> Malware Removal & Security Hardening</li>
                <li><i class="fas fa-file-alt"></i> Documentation & Inventory Management</li>
                <li><i class="fas fa-clipboard-list"></i> Work Order & Service Report Writing</li>
                <li><i class="fas fa-chart-line"></i> Performance Optimization & Upgrades</li>
                <li><i class="fas fa-bolt"></i> Electrical Safety & ESD Protection</li>
            </ul>
        </div>
        <div class="materials-grid">
            <?php if ($materials->num_rows > 0): ?>
                <?php
                $displayed_titles = [];
                while ($material = $materials->fetch_assoc()):
                    $title_lower = strtolower(trim($material['title']));
                    if (in_array($title_lower, $displayed_titles)) continue;
                    $displayed_titles[] = $title_lower;
                    $image_url = 'https://upload.wikimedia.org/wikipedia/commons/thumb/2/2a/Computer_case_side_open.jpg/500px-Computer_case_side_open.jpg';
                    if (strpos($title_lower,'preventive maintenance')!==false) $image_url='https://i.pinimg.com/736x/c2/5b/7e/c25b7e2658c088cb16e30b2c65286958.jpg';
                    elseif (strpos($title_lower,'cleaning')!==false) $image_url='https://thumbs.dreamstime.com/z/computer-repair-cleaning-components-dust-your-221487401.jpg';
                    elseif (strpos($title_lower,'thermal')!==false || strpos($title_lower,'overheating')!==false) $image_url='https://t3.ftcdn.net/jpg/06/27/96/94/360_F_627969437_S5KcQIH15f1pk8BtSFxzhydorCKMqtmU.jpg';
                    elseif (strpos($title_lower,'diagnostic tool')!==false) $image_url='https://i.pinimg.com/736x/51/35/f9/5135f901859ebe22b8359830db981360.jpg';
                    elseif (strpos($title_lower,'multimeter')!==false) $image_url='https://i.pinimg.com/736x/15/7e/fe/157efe47f26499ff09386d94cbbff105.jpg';
                    elseif (strpos($title_lower,'post card')!==false) $image_url='https://i.pinimg.com/1200x/1d/5c/5d/1d5c5d307d4ead935997058b65aa4eff.jpg';
                    elseif (strpos($title_lower,'cpu')!==false) $image_url='https://i.pinimg.com/1200x/e1/60/01/e16001e2fc43b0fd79d0d46c376ca004.jpg';
                    elseif (strpos($title_lower,'ram')!==false || strpos($title_lower,'memory')!==false) $image_url='https://i.pinimg.com/1200x/55/57/28/5557281d6fed7d0dbef82fefaeb04cc9.jpg';
                    elseif (strpos($title_lower,'motherboard')!==false) $image_url='https://i.pinimg.com/1200x/bc/57/e8/bc57e84b6eef3d116a6f99b83af0a4a7.jpg';
                    elseif (strpos($title_lower,'power supply')!==false || strpos($title_lower,'psu')!==false) $image_url='https://i.pinimg.com/1200x/06/65/e8/0665e8206df1945866d9a19a4fdc09ad.jpg';
                    elseif (strpos($title_lower,'hard drive')!==false || strpos($title_lower,'hdd')!==false || strpos($title_lower,'ssd')!==false || strpos($title_lower,'storage')!==false) $image_url='https://i.pinimg.com/1200x/a3/b8/6b/a3b86b27dbdd3f8ef087acc9d55314d2.jpg';
                    elseif (strpos($title_lower,'graphics card')!==false || strpos($title_lower,'gpu')!==false) $image_url='https://i.pinimg.com/1200x/bf/19/85/bf1985b17a5257f3aefc6d18cb401477.jpg';
                    elseif (strpos($title_lower,'no power')!==false || strpos($title_lower,'no post')!==false) $image_url='https://i.pinimg.com/236x/a5/d6/58/a5d65896ca5f49a4b88d3d59a4c2b2dd.jpg';
                    elseif (strpos($title_lower,'beep code')!==false) $image_url='https://i.pinimg.com/736x/2b/86/8d/2b868dae7436cf2051b07b1770903ae4.jpg';
                    elseif (strpos($title_lower,'blue screen')!==false || strpos($title_lower,'bsod')!==false) $image_url='https://us.norton.com/content/dam/blogs/images/norton/am/blue-screen-of-death-01.jpg';
                    elseif (strpos($title_lower,'slow performance')!==false) $image_url='https://image.slidesharecdn.com/howtofixlaptopslowperformance-230809081012-a5837647/75/How-To-Fix-Laptop-Slow-Performance-1-2048.jpg';
                    elseif (strpos($title_lower,'driver')!==false) $image_url='https://www.jengal.com/blog/wp-content/uploads/2022/08/examples-of-device-drivers.jpg';
                    elseif (strpos($title_lower,'network connectivity')!==false || strpos($title_lower,'no internet')!==false) $image_url='https://www.systweak.com/blogs/wp-content/uploads/2024/09/How-to-Fix-No-Internet-Access-while-using-Ethernet-Connection.webp';
                    elseif (strpos($title_lower,'wireless')!==false || strpos($title_lower,'wifi')!==false) $image_url='https://i.pinimg.com/736x/9a/6b/f6/9a6bf660a11e79de22e3a78ccd25a931.jpg';
                    elseif (strpos($title_lower,'data recovery')!==false || strpos($title_lower,'backup')!==false) $image_url='https://www.collidu.com/media/catalog/product/img/f/e/fee0512a55bb20f7e4c6901e8d070abb46266b0aeaaef20b2a64886e8279a988/data-backup-strategy-slide2.png';
                    elseif (strpos($title_lower,'malware')!==false || strpos($title_lower,'virus')!==false || strpos($title_lower,'ransomware')!==false) $image_url='https://i.pinimg.com/1200x/76/f1/c8/76f1c897f07e49a54e659a2dc8e137e1.jpg';
                    elseif (strpos($title_lower,'work order')!==false || strpos($title_lower,'inventory')!==false) $image_url='https://www.gep.com/prod/s3fs-public/images/Inventory-Management-Software-Features-Must-Have.jpg';
                    elseif (strpos($title_lower,'service report')!==false) $image_url='https://document360.com/wp-content/uploads/2023/01/Microsofts_troubleshooting_guide-768x368.png';
                    elseif (strpos($title_lower,'esd')!==false || strpos($title_lower,'electrostatic')!==false) $image_url='https://i0.wp.com/blog.minicircuits.com/wp-content/uploads/2019/12/ESD-Saftey.jpg?resize=2048%2C1296&ssl=1';
                    elseif (strpos($title_lower,'electrical safety')!==false) $image_url='https://greenwgroup.co.in/wp-content/uploads/2015/03/Electrical-Safety-1-1.jpg';
                    elseif (strpos($title_lower,'upgrade')!==false || strpos($title_lower,'optimization')!==false) $image_url='https://i.pinimg.com/736x/0e/b7/ca/0eb7cafc92d81777fb781044ad47344f.jpg';
                    $icon='tools';
                    if ($material['category']==='preventive-maintenance') $icon='shield-alt';
                    elseif ($material['category']==='diagnostic') $icon='stethoscope';
                    elseif ($material['category']==='hardware') $icon='microchip';
                    elseif ($material['category']==='software') $icon='code';
                    elseif ($material['category']==='network') $icon='network-wired';
                    elseif ($material['category']==='security') $icon='shield-virus';
                    elseif ($material['category']==='backup') $icon='archive';
                    elseif ($material['category']==='documentation') $icon='file-alt';
                    elseif ($material['category']==='troubleshooting') $icon='search';
                ?>
                <div class="material-card" onclick='openModal(<?php echo htmlspecialchars(json_encode($material), ENT_QUOTES, "UTF-8"); ?>, <?php echo htmlspecialchars(json_encode($image_url), ENT_QUOTES, "UTF-8"); ?>)'>
                    <div class="card-image"><img src="<?php echo htmlspecialchars($image_url,ENT_QUOTES,'UTF-8'); ?>" alt="<?php echo htmlspecialchars($material['title'],ENT_QUOTES,'UTF-8'); ?>" loading="lazy" onerror="this.src='https://upload.wikimedia.org/wikipedia/commons/thumb/2/2a/Computer_case_side_open.jpg/500px-Computer_case_side_open.jpg'"></div>
                    <div class="card-header"><i class="fas fa-<?php echo $icon; ?>"></i><h3><?php echo htmlspecialchars($material['title']); ?></h3></div>
                    <div class="card-body"><span class="category-tag"><i class="fas fa-tag"></i> <?php echo ucfirst(str_replace('-', ' ', $material['category'])); ?></span><p><?php echo nl2br(htmlspecialchars(substr($material['content'],0,120))); ?>...</p><div class="read-more"><i class="fas fa-mouse-pointer"></i> Click to read more</div></div>
                </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div style="grid-column:1/-1;text-align:center;padding:3rem;"><i class="fas fa-book-open" style="font-size:3rem;color:#8899cc;"></i><p>No review materials found for COC 4. Please add topics to the database.</p></div>
            <?php endif; ?>
        </div>
        <div class="quiz-section">
            <h2><i class="fas fa-question-circle"></i> Test Your Knowledge</h2>
            <p>Ready to assess your understanding? Take the COC 4 quiz to see how well you've mastered all the maintenance and repair topics.</p>
            <div class="quiz-buttons"><a href="quiz.php?coc=4" class="btn btn-primary"><i class="fas fa-play"></i> Take COC 4 Quiz</a><a href="practice_mode.php?coc=4" class="btn btn-secondary"><i class="fas fa-chalkboard-teacher"></i> Practice Mode</a></div>
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

        function openModal(material, imageUrl) {
            const modal = document.getElementById('detailModal');
            const modalTitle = document.getElementById('modalTitle');
            const modalImage = document.getElementById('modalImage');
            const modalCategory = document.getElementById('modalCategory');
            const modalContent = document.getElementById('modalContent');
            
            modalTitle.textContent = material.title;
            modalImage.src = imageUrl;
            modalCategory.innerHTML = '<i class="fas fa-tag"></i> ' + material.category.charAt(0).toUpperCase() + material.category.slice(1);
            modalContent.innerHTML = material.content.replace(/\r\n|\r|\n/g, '<br>');
            
            resetZoom();
            
            modal.classList.add('active');
            currentModal = modal;
            currentImage = modalImage;
            document.body.style.overflow = 'hidden';
            
            setupZoomControls();
            setupPanning();
        }
        
        function setupZoomControls() {
            const zoomInBtn = document.getElementById('zoomInBtn');
            const zoomOutBtn = document.getElementById('zoomOutBtn');
            const zoomResetBtn = document.getElementById('zoomResetBtn');
            
            const newZoomIn = zoomInBtn.cloneNode(true);
            const newZoomOut = zoomOutBtn.cloneNode(true);
            const newZoomReset = zoomResetBtn.cloneNode(true);
            zoomInBtn.parentNode.replaceChild(newZoomIn, zoomInBtn);
            zoomOutBtn.parentNode.replaceChild(newZoomOut, zoomOutBtn);
            zoomResetBtn.parentNode.replaceChild(newZoomReset, zoomResetBtn);
            
            newZoomIn.addEventListener('click', () => zoomImage(0.1));
            newZoomOut.addEventListener('click', () => zoomImage(-0.1));
            newZoomReset.addEventListener('click', () => resetZoom());
            
            const container = document.getElementById('modalImageContainer');
            container.addEventListener('wheel', function(e) {
                e.preventDefault();
                if (e.deltaY < 0) {
                    zoomImage(0.05);
                } else {
                    zoomImage(-0.05);
                }
            }, { passive: false });
        }
        
        function setupPanning() {
            const container = document.getElementById('modalImageContainer');
            
            container.removeEventListener('mousedown', startPanning);
            container.removeEventListener('mouseleave', stopPanning);
            container.removeEventListener('mouseup', stopPanning);
            container.removeEventListener('mousemove', panImage);
            
            container.addEventListener('mousedown', startPanning);
            container.addEventListener('mouseleave', stopPanning);
            container.addEventListener('mouseup', stopPanning);
            container.addEventListener('mousemove', panImage);
        }
        
        function startPanning(e) {
            if (currentZoom <= 1) return;
            isPanning = true;
            const container = document.getElementById('modalImageContainer');
            startX = e.pageX - container.offsetLeft;
            startY = e.pageY - container.offsetTop;
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
            const x = e.pageX - container.offsetLeft;
            const y = e.pageY - container.offsetTop;
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
            if (currentModal) {
                currentModal.classList.remove('active');
                document.body.style.overflow = 'auto';
                currentModal = null;
                currentImage = null;
                resetZoom();
            }
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

        // Light / Dark mode - only the button beside the user name controls the theme.
        (function () {
            const themeToggle = document.getElementById('themeToggle');
            const themeIcon = document.getElementById('themeIcon');
            const themeText = document.getElementById('themeText');

            function applyTheme(theme) {
                const isLight = theme === 'light';
                document.body.classList.toggle('light-mode', isLight);
                if (themeIcon) {
                    themeIcon.className = isLight ? 'fas fa-sun' : 'fas fa-moon';
                }
                if (themeText) {
                    themeText.textContent = isLight ? 'Light Mode' : 'Dark Mode';
                }
            }

            const savedTheme = localStorage.getItem('css_tesda_theme') || 'dark';
            applyTheme(savedTheme);

            if (themeToggle) {
                themeToggle.addEventListener('click', function () {
                    const nextTheme = document.body.classList.contains('light-mode') ? 'dark' : 'light';
                    localStorage.setItem('css_tesda_theme', nextTheme);
                    applyTheme(nextTheme);
                });
            }
        })();
    </script>
     <?php if (file_exists(__DIR__ . '/includes/brightness_widget.php')) { $global_brightness = function_exists('getGlobalBrightness') ? getGlobalBrightness() : 100; include __DIR__ . '/includes/brightness_widget.php'; } ?>
</body>
</html>

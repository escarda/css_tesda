<?php
// coc3_review.php - COC 3: Set-up Computer Servers

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
?>
<!--
IMPORTANT:
Paste the CSS/HTML/image-mapping portion from the COC 3 code you supplied
unchanged below this point, but replace its card onclick and JavaScript with
the fixed versions shown in this file.
-->
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo htmlspecialchars($page_title, ENT_QUOTES, 'UTF-8'); ?> | CSS TESDA Reviewer</title>
<link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&family=Rajdhani:wght@300;400;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --bg-primary: #050812;
            --bg-secondary: #0a1020;
            --bg-card: rgba(10, 20, 40, 0.88);
            --bg-panel: rgba(0, 0, 0, 0.3);
            --bg-stat: rgba(10, 20, 40, 0.75);
            --bg-navbar: rgba(5, 8, 18, 0.92);
            --text-primary: #e5edf8;
            --text-secondary: #aab7cc;
            --border-color: rgba(0, 255, 157, 0.22);
            --border-light: rgba(0, 255, 157, 0.16);
            --cyber-bg1: #050812;
            --cyber-bg2: #0a1020;
            --grid-color: rgba(0, 255, 157, 0.07);
        }

        body.light-mode {
            --bg-primary: #f4f7fb;
            --bg-secondary: #ffffff;
            --bg-card: #ffffff;
            --bg-panel: #ffffff;
            --bg-stat: #ffffff;
            --bg-navbar: rgba(255, 255, 255, 0.92);
            --text-primary: #172033;
            --text-secondary: #4b5870;
            --border-color: #d6deea;
            --border-light: #dce4ef;
            --cyber-bg1: #eef4fb;
            --cyber-bg2: #ffffff;
            --grid-color: rgba(0, 120, 180, 0.08);
        }

        body.dark-mode {
            --bg-primary: #050812;
            --bg-secondary: #0a1020;
            --bg-card: rgba(10, 20, 40, 0.88);
            --bg-panel: rgba(0, 0, 0, 0.3);
            --bg-stat: rgba(10, 20, 40, 0.75);
            --bg-navbar: rgba(5, 8, 18, 0.92);
            --text-primary: #e5edf8;
            --text-secondary: #aab7cc;
            --border-color: rgba(0, 255, 157, 0.22);
            --border-light: rgba(0, 255, 157, 0.16);
            --cyber-bg1: #050812;
            --cyber-bg2: #0a1020;
            --grid-color: rgba(0, 255, 157, 0.07);
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

        .theme-toggle {
            display: inline-flex;
            position: relative;
            z-index: 200;
            align-items: center;
            gap: 7px;
            border: 1px solid var(--border-color);
            background: var(--bg-card);
            color: var(--text-primary);
            border-radius: 999px;
            padding: 7px 12px;
            font-family: 'Rajdhani', sans-serif;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.25s ease;
        }

        .theme-toggle:hover {
            border-color: #00ff9d;
            color: #00ff9d;
            transform: translateY(-1px);
        }

        .theme-toggle i {
            width: 16px;
            text-align: center;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 1rem;
            color: #00ff9d;
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
            background: rgba(10, 20, 40, 0.95);
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
            overflow-anchor: none;
            background: #0a0f1e;
            cursor: default;
            position: relative;
            scrollbar-width: thin;
        }

        .modal-image-stage {
            position: relative;
            display: block;
            min-width: 1px;
            min-height: 1px;
        }

        .modal-image img {
            display: block;
            width: auto;
            height: auto;
            max-width: none;
            max-height: none;
            pointer-events: none;
            user-select: none;
        }

        .modal-image img.image-error {
            width: 100% !important;
            height: 220px !important;
            object-fit: contain;
            opacity: 0.9;
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
            color: #cbd5e1;
            line-height: 1.8;
            margin-bottom: 1rem;
            white-space: pre-line;
            overflow-wrap: anywhere;
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
        }
    </style>
</head>
<body class="dark-mode">
<div class="cyber-bg"></div><div class="grid-overlay"></div>
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
                    <button type="button" class="theme-toggle" id="themeToggle" aria-label="Toggle light and dark mode">
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
<div class="coc-badge"><i class="fas fa-server"></i> COC 3: Set-up Computer Servers</div>
<h1>Complete Server Administration</h1>
<p>Click on any card to view detailed information. Use + / - buttons or mouse wheel to zoom images.</p>
</div>

<div class="key-topics"><h3><i class="fas fa-star-of-life"></i> All Topics Covered</h3><ul class="topic-list">
<li><i class="fas fa-server"></i> Server Fundamentals & Hardware</li>
<li><i class="fas fa-microchip"></i> Server CPUs, ECC RAM, RAID</li>
<li><i class="fas fa-windows"></i> Windows Server & Linux</li>
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
</ul></div>

<div class="materials-grid">
<?php if ($materials->num_rows > 0): ?>
<?php while ($material = $materials->fetch_assoc()):
$title_lower = strtolower(trim($material['title']));
$image_url = 'https://i.pinimg.com/736x/2f/3a/4b/2f3a4b5c6d7e8f9a0b1c2d3e4f5a6b7c.jpg';

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

$icon='server';
$category=strtolower(trim((string)$material['category']));
if ($category==='web-server') $icon='globe';
elseif ($category==='application') $icon='database';
elseif ($category==='active-directory') $icon='users';
elseif ($category==='virtualization') $icon='cube';
elseif ($category==='security') $icon='shield-alt';
elseif ($category==='backup') $icon='archive';
elseif ($category==='storage') $icon='hdd';
elseif ($category==='server-os') $icon='windows';
elseif ($category==='file-services') $icon='folder-open';
elseif ($category==='monitoring') $icon='chart-line';
elseif ($category==='network-services') $icon='network-wired';
elseif ($category==='remote-access') $icon='desktop';
elseif ($category==='high-availability') $icon='sync';

$material_json = htmlspecialchars(
    json_encode($material, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT),
    ENT_QUOTES,
    'UTF-8'
);
$image_json = htmlspecialchars(json_encode($image_url, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT), ENT_QUOTES, 'UTF-8');
?>
<div class="material-card" onclick='openModal(<?php echo $material_json; ?>, <?php echo $image_json; ?>)'>
<div class="card-image"><img src="<?php echo htmlspecialchars($image_url,ENT_QUOTES,'UTF-8'); ?>" alt="<?php echo htmlspecialchars($material['title'],ENT_QUOTES,'UTF-8'); ?>" loading="lazy" onerror="this.src='https://i.pinimg.com/736x/2f/3a/4b/2f3a4b5c6d7e8f9a0b1c2d3e4f5a6b7c.jpg'"></div>
<div class="card-header"><i class="fas fa-<?php echo htmlspecialchars($icon,ENT_QUOTES,'UTF-8'); ?>"></i><h3><?php echo htmlspecialchars($material['title'],ENT_QUOTES,'UTF-8'); ?></h3></div>
<div class="card-body">
<span class="category-tag"><i class="fas fa-tag"></i> <?php echo htmlspecialchars(ucfirst(str_replace('-',' ',$category)),ENT_QUOTES,'UTF-8'); ?></span>
<p><?php echo nl2br(htmlspecialchars(substr($material['content'],0,120),ENT_QUOTES,'UTF-8')); ?>...</p>
<div class="read-more"><i class="fas fa-mouse-pointer"></i> Click to read more</div>
</div></div>
<?php endwhile; else: ?>
<div style="grid-column:1/-1;text-align:center;padding:3rem"><i class="fas fa-book-open" style="font-size:3rem;color:#8899cc"></i><p>No review materials found for COC 3. Please add topics to the database.</p></div>
<?php endif; ?>
</div>

<div class="quiz-section"><h2><i class="fas fa-question-circle"></i> Test Your Knowledge</h2><p>Ready to assess your understanding? Take the COC 3 quiz to see how well you've mastered all the server topics.</p><div class="quiz-buttons">
<a href="quiz.php?coc=3" class="btn btn-primary"><i class="fas fa-play"></i> Take COC 3 Quiz</a>
<a href="practice_mode.php?coc=3" class="btn btn-secondary"><i class="fas fa-chalkboard-teacher"></i> Practice Mode</a>
</div></div></div>

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
                    <div class="modal-image-stage" id="modalImageStage">
                        <img id="modalImage" src="" alt="" referrerpolicy="no-referrer">
                    </div>
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
        let baseWidth = 0;
        let baseHeight = 0;
        let fitScale = 1;

        const modal = document.getElementById('detailModal');
        const modalImage = document.getElementById('modalImage');
        const modalImageStage = document.getElementById('modalImageStage');
        const modalImageContainer = document.getElementById('modalImageContainer');
        const zoomInBtn = document.getElementById('zoomInBtn');
        const zoomOutBtn = document.getElementById('zoomOutBtn');
        const zoomResetBtn = document.getElementById('zoomResetBtn');
        const zoomLevel = document.getElementById('zoomLevel');
        const defaultImageUrl = 'https://upload.wikimedia.org/wikipedia/commons/thumb/2/2a/Computer_case_side_open.jpg/500px-Computer_case_side_open.jpg';

        function openModal(material, imageUrl) {
            document.getElementById('modalTitle').textContent = material.title || '';

            const category = String(material.category || '');
            document.getElementById('modalCategory').innerHTML =
                '<i class="fas fa-tag"></i> ' +
                category.charAt(0).toUpperCase() + category.slice(1);

            document.getElementById('modalContent').textContent = material.content || '';

            currentModal = modal;
            currentImage = modalImage;
            currentZoom = 1;
            baseWidth = 0;
            baseHeight = 0;
            fitScale = 1;
            isPanning = false;

            // Reset the viewer without making the image permanently 1px wide.
            modalImage.classList.remove('image-error');
            modalImage.style.width = 'auto';
            modalImage.style.height = 'auto';
            modalImage.style.objectFit = 'contain';
            modalImage.style.visibility = 'hidden';
            modalImageStage.style.width = '100%';
            modalImageStage.style.height = '1px';
            modalImageContainer.scrollLeft = 0;
            modalImageContainer.scrollTop = 0;
            zoomLevel.textContent = '100%';

            // IMPORTANT: register handlers BEFORE assigning src so cached images
            // and failed external images are handled correctly.
            modalImage.onload = function () {
                modalImage.classList.remove('image-error');
                modalImage.style.visibility = 'visible';
                prepareImage();
            };

            modalImage.onerror = function () {
                if (modalImage.dataset.fallbackUsed !== '1') {
                    modalImage.dataset.fallbackUsed = '1';
                    modalImage.src = defaultImageUrl;
                    return;
                }

                // If both the original image and fallback fail, show a clear
                // message instead of a black/empty viewer.
                modalImage.classList.add('image-error');
                modalImage.style.visibility = 'visible';
                modalImage.style.width = '100%';
                modalImage.style.height = '220px';
                modalImage.style.objectFit = 'contain';
                modalImageStage.style.width = '100%';
                modalImageStage.style.height = '220px';
                modalImage.alt = 'Image could not be loaded';
                zoomLevel.textContent = 'Image unavailable';
            };

            modalImage.dataset.fallbackUsed = '0';
            modalImage.alt = material.title || 'Review material';

            modal.classList.add('active');
            document.body.style.overflow = 'hidden';

            // Force the browser to re-evaluate the image source even when the
            // same URL is opened repeatedly.
            modalImage.src = '';
            requestAnimationFrame(function () {
                modalImage.src = imageUrl || defaultImageUrl;
            });
        }

        function prepareImage() {
            if (!modalImage.naturalWidth || !modalImage.naturalHeight) return;

            const availableWidth = Math.max(modalImageContainer.clientWidth - 32, 100);
            const availableHeight = Math.max(modalImageContainer.clientHeight - 32, 100);

            // Fit the complete image into the viewer at 100% zoom.
            fitScale = Math.min(
                availableWidth / modalImage.naturalWidth,
                availableHeight / modalImage.naturalHeight,
                1
            );

            baseWidth = Math.max(1, Math.round(modalImage.naturalWidth * fitScale));
            baseHeight = Math.max(1, Math.round(modalImage.naturalHeight * fitScale));
            currentZoom = 1;
            applyImageSize(false);
            centerImage();
        }

        function applyImageSize(keepCenter) {
            if (!baseWidth || !baseHeight) return;

            const oldCenterX = modalImageContainer.scrollLeft + modalImageContainer.clientWidth / 2;
            const oldCenterY = modalImageContainer.scrollTop + modalImageContainer.clientHeight / 2;
            const width = Math.max(1, Math.round(baseWidth * currentZoom));
            const height = Math.max(1, Math.round(baseHeight * currentZoom));

            modalImageStage.style.width = width + 'px';
            modalImageStage.style.height = height + 'px';
            modalImage.style.width = width + 'px';
            modalImage.style.height = height + 'px';
            modalImage.style.transform = 'none';
            zoomLevel.textContent = Math.round(currentZoom * 100) + '%';
            modalImageContainer.style.cursor = currentZoom > 1 ? 'grab' : 'default';

            if (keepCenter) {
                requestAnimationFrame(function () {
                    modalImageContainer.scrollLeft = Math.max(0, oldCenterX - modalImageContainer.clientWidth / 2);
                    modalImageContainer.scrollTop = Math.max(0, oldCenterY - modalImageContainer.clientHeight / 2);
                    clampScroll();
                });
            }
        }

        function centerImage() {
            requestAnimationFrame(function () {
                modalImageContainer.scrollLeft = Math.max(0, (modalImageStage.offsetWidth - modalImageContainer.clientWidth) / 2);
                modalImageContainer.scrollTop = Math.max(0, (modalImageStage.offsetHeight - modalImageContainer.clientHeight) / 2);
                clampScroll();
            });
        }

        function clampScroll() {
            modalImageContainer.scrollLeft = Math.max(0, Math.min(
                modalImageContainer.scrollLeft,
                modalImageContainer.scrollWidth - modalImageContainer.clientWidth
            ));
            modalImageContainer.scrollTop = Math.max(0, Math.min(
                modalImageContainer.scrollTop,
                modalImageContainer.scrollHeight - modalImageContainer.clientHeight
            ));
        }

        function zoomImage(delta) {
            if (!currentImage || !baseWidth) return;
            const newZoom = Math.min(Math.max(currentZoom + delta, 0.5), 3);
            if (newZoom === currentZoom) return;
            currentZoom = newZoom;
            applyImageSize(true);
        }

        function resetZoom() {
            currentZoom = 1;
            if (baseWidth) {
                applyImageSize(false);
                centerImage();
            } else {
                zoomLevel.textContent = '100%';
                modalImageContainer.scrollLeft = 0;
                modalImageContainer.scrollTop = 0;
            }
        }

        function closeModal() {
            modal.classList.remove('active');
            document.body.style.overflow = '';
            currentModal = null;
            currentImage = null;
            isPanning = false;
            currentZoom = 1;
            baseWidth = 0;
            baseHeight = 0;
            modalImage.style.width = 'auto';
            modalImage.style.height = 'auto';
            modalImageStage.style.width = '1px';
            modalImageStage.style.height = '1px';
            zoomLevel.textContent = '100%';
            modalImageContainer.scrollLeft = 0;
            modalImageContainer.scrollTop = 0;
        }

        zoomInBtn.addEventListener('click', function (e) {
            e.stopPropagation();
            zoomImage(0.1);
        });

        zoomOutBtn.addEventListener('click', function (e) {
            e.stopPropagation();
            zoomImage(-0.1);
        });

        zoomResetBtn.addEventListener('click', function (e) {
            e.stopPropagation();
            resetZoom();
        });

        modalImageContainer.addEventListener('wheel', function (e) {
            if (!currentModal) return;
            e.preventDefault();
            zoomImage(e.deltaY < 0 ? 0.05 : -0.05);
        }, { passive: false });

        modalImageContainer.addEventListener('mousedown', function (e) {
            if (currentZoom <= 1) return;
            isPanning = true;
            startX = e.pageX - modalImageContainer.offsetLeft;
            startY = e.pageY - modalImageContainer.offsetTop;
            scrollLeft = modalImageContainer.scrollLeft;
            scrollTop = modalImageContainer.scrollTop;
            modalImageContainer.style.cursor = 'grabbing';
        });

        modalImageContainer.addEventListener('mousemove', function (e) {
            if (!isPanning) return;
            e.preventDefault();
            const x = e.pageX - modalImageContainer.offsetLeft;
            const y = e.pageY - modalImageContainer.offsetTop;
            modalImageContainer.scrollLeft = scrollLeft - ((x - startX) * 1.5);
            modalImageContainer.scrollTop = scrollTop - ((y - startY) * 1.5);
            clampScroll();
        });

        function stopPanning() {
            isPanning = false;
            modalImageContainer.style.cursor = currentZoom > 1 ? 'grab' : 'default';
        }

        modalImageContainer.addEventListener('mouseup', stopPanning);
        modalImageContainer.addEventListener('mouseleave', stopPanning);

        modal.addEventListener('click', function (e) {
            if (e.target === modal) closeModal();
        });

        document.addEventListener('keydown', function (e) {
            if (!currentModal) return;
            if (e.key === 'Escape') closeModal();
            else if (e.key === '+' || e.key === '=') zoomImage(0.1);
            else if (e.key === '-') zoomImage(-0.1);
            else if (e.key === '0') resetZoom();
        });

        // ---------------- THEME TOGGLE ----------------
        const themeToggle = document.getElementById('themeToggle');
        const themeIcon = document.getElementById('themeIcon');
        const themeText = document.getElementById('themeText');
        const savedTheme = localStorage.getItem('css_tesda_theme') === 'light' ? 'light' : 'dark';

        function applyTheme(theme) {
            const isLight = theme === 'light';
            document.body.classList.toggle('light-mode', isLight);
            document.body.classList.toggle('dark-mode', !isLight);
            themeIcon.className = isLight ? 'fas fa-sun' : 'fas fa-moon';
            themeText.textContent = isLight ? 'Light Mode' : 'Dark Mode';
            localStorage.setItem('css_tesda_theme', isLight ? 'light' : 'dark');
        }

        applyTheme(savedTheme);

        themeToggle.addEventListener('click', function () {
            const nextTheme = document.body.classList.contains('light-mode') ? 'dark' : 'light';
            applyTheme(nextTheme);
        });

        window.addEventListener('resize', function () {
            if (currentModal && currentImage && currentImage.naturalWidth) {
                prepareImage();
            }
        });
        // Keep the existing project brightness widget. It controls page brightness only.
    </script>
<?php if (file_exists(__DIR__ . '/includes/brightness_widget.php')) { include __DIR__ . '/includes/brightness_widget.php'; } ?>
</body>
</html>

<?php
// index.php - Landing page with admin game shortcuts

// No login required for landing page
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title>CSS TESDA Assessment Reviewer | Master NC II</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;14..32,400;14..32,600;14..32,700;14..32,800&family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --bg-primary: #020617;
            --bg-card: rgba(8, 15, 35, 0.75);
            --bg-feature: rgba(2, 10, 30, 0.7);
            --bg-panel: rgba(0, 30, 40, 0.4);
            --text-primary: #e2e8f0;
            --text-secondary: #94a3b8;
            --text-muted: #9ca3af;
            --border-main: rgba(0, 255, 200, 0.3);
            --border-soft: rgba(0, 255, 200, 0.2);
            --cyber-bg1: #0a0f2a;
            --cyber-bg2: #020617;
            --grid-color: rgba(0, 255, 200, 0.04);
            --accent: #0bffb0;
        }

        body.light-mode {
            --bg-primary: #f4f7fb;
            --bg-card: rgba(255, 255, 255, 0.92);
            --bg-feature: rgba(255, 255, 255, 0.9);
            --bg-panel: rgba(255, 255, 255, 0.9);
            --text-primary: #172033;
            --text-secondary: #526174;
            --text-muted: #5f6b7d;
            --border-main: rgba(0, 150, 105, 0.28);
            --border-soft: rgba(0, 150, 105, 0.2);
            --cyber-bg1: #ffffff;
            --cyber-bg2: #eaf1f7;
            --grid-color: rgba(0, 150, 105, 0.06);
            --accent: #008a65;
        }

        body {
            font-family: 'Inter', 'Space Grotesk', sans-serif;
            background: var(--bg-primary);
            color: var(--text-primary);
            min-height: 100vh;
            line-height: 1.5;
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
            background: radial-gradient(circle at 20% 30%, var(--cyber-bg1), var(--cyber-bg2));
            transition: background 0.3s ease;
        }

        .grid-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image:
                linear-gradient(var(--grid-color) 1px, transparent 1px),
                linear-gradient(90deg, var(--grid-color) 1px, transparent 1px);
            background-size: 55px 55px;
            z-index: -1;
            pointer-events: none;
        }

        .floating-elements {
            position: fixed;
            width: 100%;
            height: 100%;
            z-index: -1;
            overflow: hidden;
            pointer-events: none;
        }

        .floating-element {
            position: absolute;
            color: rgba(0, 255, 200, 0.08);
            font-family: 'Space Grotesk', monospace;
            font-weight: 600;
            font-size: 1rem;
            white-space: nowrap;
            animation: floatSlow 22s linear infinite;
            user-select: none;
            display: flex;
            align-items: center;
            gap: 7px;
        }

        .floating-element i {
            font-size: 0.9em;
        }

        body.light-mode .floating-element {
            color: rgba(0, 130, 95, 0.14);
        }

        @keyframes floatSlow {
            0% { transform: translateY(110vh) rotate(0deg); opacity: 0.3; }
            100% { transform: translateY(-20vh) rotate(360deg); opacity: 0.6; }
        }

        .landing-wrapper {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 1.5rem;
        }

        .landing-container {
            width: 100%;
            max-width: 1300px;
            margin: 0 auto;
        }

        .landing-card {
            background: var(--bg-card);
            backdrop-filter: blur(18px);
            border-radius: 2.5rem;
            border: 1px solid var(--border-main);
            box-shadow: 0 30px 50px rgba(0, 0, 0, 0.5), 0 0 0 1px rgba(0, 255, 200, 0.1) inset;
            padding: 3rem 2.5rem;
            transition: transform 0.2s ease, background 0.3s ease, border-color 0.3s ease;
        }

        body.light-mode .landing-card {
            box-shadow: 0 20px 45px rgba(40, 60, 90, 0.15);
        }

        .landing-header {
            text-align: center;
            margin-bottom: 3rem;
        }

        .logo-icon {
            width: 110px;
            height: 110px;
            margin: 0 auto 1.8rem;
            background: linear-gradient(145deg, #0bffb0, #0a5cff);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3.8rem;
            color: #020617;
            box-shadow: 0 20px 35px rgba(0, 255, 200, 0.3), 0 0 0 3px rgba(0,255,200,0.4);
            transition: all 0.3s;
        }

        .logo-icon i {
            filter: drop-shadow(0 2px 4px rgba(0,0,0,0.2));
        }

        .landing-header h1 {
            font-size: 3.2rem;
            font-weight: 800;
            background: linear-gradient(135deg, #b3ffe0, #3b82f6, #0bffb0);
            background-size: 200% auto;
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
            letter-spacing: -0.02em;
            margin-bottom: 1.2rem;
        }

        body.light-mode .landing-header h1 {
            background: linear-gradient(135deg, #007b61, #2563eb, #00a878);
            -webkit-background-clip: text;
            background-clip: text;
        }

        .tagline {
            font-size: 1.2rem;
            color: var(--text-secondary);
            max-width: 700px;
            margin: 0 auto;
            font-weight: 400;
            border-bottom: 1px dashed rgba(0,255,200,0.3);
            display: inline-block;
            padding-bottom: 0.5rem;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1.8rem;
            margin: 3rem 0 3.2rem;
        }

        .feature-card {
            background: var(--bg-feature);
            border-radius: 1.8rem;
            padding: 1.8rem 1.5rem;
            border: 1px solid var(--border-soft);
            transition: all 0.3s cubic-bezier(0.2, 0.9, 0.4, 1.1);
            backdrop-filter: blur(4px);
            text-align: center;
        }

        .feature-card:hover {
            transform: translateY(-8px);
            border-color: #0bffb0;
            background: rgba(0, 40, 30, 0.6);
            box-shadow: 0 20px 30px -12px rgba(0, 255, 200, 0.25);
        }

        body.light-mode .feature-card:hover {
            background: rgba(0, 150, 105, 0.06);
            border-color: #00a878;
            box-shadow: 0 15px 28px rgba(0, 130, 95, 0.14);
        }

        .feature-icon {
            width: 70px;
            height: 70px;
            margin: 0 auto 1.2rem;
            background: linear-gradient(125deg, rgba(11, 255, 176, 0.15), rgba(59, 130, 246, 0.1));
            border-radius: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.2rem;
            color: #0bffb0;
            border: 1px solid rgba(11, 255, 176, 0.4);
            transition: all 0.2s;
        }

        .feature-card:hover .feature-icon {
            background: #0bffb0;
            color: #020617;
            transform: scale(0.98);
            box-shadow: 0 0 12px #0bffb0;
        }

        .feature-card h3 {
            font-size: 1.4rem;
            font-weight: 700;
            margin-bottom: 0.75rem;
            letter-spacing: -0.3px;
            background: linear-gradient(135deg, #e2e8f0, #a5f3fc);
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        body.light-mode .feature-card h3 {
            background: linear-gradient(135deg, #172033, #007b61);
            -webkit-background-clip: text;
            background-clip: text;
        }

        .feature-card p {
            font-size: 0.9rem;
            color: var(--text-muted);
            line-height: 1.5;
        }

        .admin-play-section {
            margin: 2rem 0;
            padding: 1.5rem;
            background: var(--bg-panel);
            border-radius: 1.8rem;
            border: 1px solid rgba(11, 255, 176, 0.3);
            text-align: center;
        }

        .admin-play-section h3 {
            color: #0bffb0;
            margin-bottom: 0.5rem;
            font-size: 1.2rem;
        }

        body.light-mode .admin-play-section h3 {
            color: #008a65;
        }

        .admin-play-section p {
            color: var(--text-secondary);
            font-size: 0.85rem;
            margin-bottom: 1rem;
        }

        .admin-game-buttons {
            display: flex;
            gap: 0.8rem;
            justify-content: center;
            flex-wrap: wrap;
        }

        .admin-game-btn {
            background: rgba(0, 0, 0, 0.5);
            border: 1px solid rgba(11, 255, 176, 0.4);
            border-radius: 40px;
            padding: 0.5rem 1.2rem;
            font-size: 0.8rem;
            font-weight: 600;
            color: #0bffb0;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s;
        }

        body.light-mode .admin-game-btn {
            background: rgba(255,255,255,0.8);
            color: #008a65;
            border-color: rgba(0,130,95,0.35);
        }

        .admin-game-btn:hover {
            background: #0bffb0;
            color: #020617;
            transform: translateY(-2px);
            box-shadow: 0 5px 12px rgba(11, 255, 176, 0.3);
        }

        .cta-section {
            text-align: center;
            margin: 2rem 0 2.5rem;
        }

        .cta-buttons {
            display: flex;
            gap: 1.5rem;
            justify-content: center;
            flex-wrap: wrap;
        }

        .btn {
            padding: 0.9rem 2.2rem;
            font-size: 1rem;
            font-weight: 600;
            font-family: 'Space Grotesk', sans-serif;
            border-radius: 60px;
            transition: all 0.25s;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
            letter-spacing: 0.3px;
            border: none;
        }

        .btn-primary {
            background: linear-gradient(105deg, #0bffb0, #2b6ef0);
            color: #020617;
            box-shadow: 0 5px 15px rgba(11, 255, 176, 0.3);
        }

        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 25px rgba(11, 255, 176, 0.45);
            background: linear-gradient(105deg, #1effbb, #3d7eff);
        }

        .btn-secondary {
            background: rgba(0, 0, 0, 0.4);
            border: 1.5px solid #0bffb0;
            color: #0bffb0;
            backdrop-filter: blur(4px);
        }

        body.light-mode .btn-secondary {
            background: rgba(255,255,255,0.75);
            color: #008a65;
            border-color: #00a878;
        }

        .btn-secondary:hover {
            background: #0bffb0;
            color: #020617;
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(11, 255, 176, 0.3);
        }

        .stats-bar {
            display: flex;
            justify-content: center;
            gap: 3rem;
            margin-top: 2rem;
            padding: 1.5rem 2rem;
            background: var(--bg-panel);
            border-radius: 80px;
            border: 1px solid rgba(11, 255, 176, 0.2);
            flex-wrap: wrap;
        }

        .stat-item {
            text-align: center;
            min-width: 120px;
        }

        .stat-number {
            font-family: 'Space Grotesk', monospace;
            font-size: 2rem;
            font-weight: 800;
            color: #0bffb0;
            line-height: 1.2;
        }

        body.light-mode .stat-number {
            color: #008a65;
        }

        .stat-label {
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: var(--text-secondary);
            font-weight: 500;
        }

        .tesda-badge {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            margin: 2rem auto 1.5rem;
            background: linear-gradient(95deg, rgba(11, 255, 176, 0.12), rgba(0, 50, 50, 0.3));
            width: fit-content;
            padding: 0.7rem 2rem;
            border-radius: 60px;
            border-left: 3px solid #0bffb0;
            font-weight: 500;
        }

        body.light-mode .tesda-badge {
            background: rgba(0, 150, 105, 0.07);
            border-left-color: #00a878;
        }

        .tesda-badge i {
            color: #0bffb0;
            font-size: 1.3rem;
        }

        body.light-mode .tesda-badge i {
            color: #008a65;
        }

        .coc-tags {
            display: flex;
            justify-content: center;
            gap: 1rem;
            flex-wrap: wrap;
            margin-top: 0.5rem;
        }

        .coc-tag {
            background: rgba(0, 0, 0, 0.45);
            padding: 0.5rem 1.2rem;
            border-radius: 40px;
            font-size: 0.8rem;
            font-weight: 500;
            color: #b9d0f0;
            border: 1px solid rgba(11, 255, 176, 0.25);
            transition: 0.2s;
        }

        body.light-mode .coc-tag {
            background: rgba(255,255,255,0.85);
            color: #526174;
            border-color: rgba(0,130,95,0.25);
        }

        .coc-tag i {
            margin-right: 6px;
            color: #0bffb0;
        }

        body.light-mode .coc-tag i {
            color: #008a65;
        }

        .coc-tag:hover {
            background: #0bffb020;
            border-color: #0bffb0;
            color: #e0f2fe;
            transform: translateY(-2px);
        }

        body.light-mode .coc-tag:hover {
            color: #172033;
            border-color: #00a878;
        }

        .admin-badge {
            display: inline-block;
            margin-left: 10px;
            background: rgba(255, 68, 68, 0.2);
            border: 1px solid #ff4444;
            border-radius: 20px;
            padding: 2px 8px;
            font-size: 0.6rem;
            color: #ff6666;
            vertical-align: middle;
        }

        .loading {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.85);
            backdrop-filter: blur(8px);
            z-index: 9999;
            justify-content: center;
            align-items: center;
            flex-direction: column;
        }

        .loading.active {
            display: flex;
        }

        .loading-spinner {
            width: 60px;
            height: 60px;
            border: 3px solid rgba(11, 255, 176, 0.2);
            border-top: 3px solid #0bffb0;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* COC1-style theme toggle */
        .theme-toggle {
            position: fixed;
            top: 18px;
            right: 18px;
            z-index: 10000;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 12px;
            border: 1px solid rgba(0, 255, 157, 0.35);
            border-radius: 999px;
            background: rgba(0, 255, 157, 0.08);
            color: #00ff9d;
            font-family: 'Space Grotesk', sans-serif;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            white-space: nowrap;
            backdrop-filter: blur(10px);
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
            color: #fff;
        }

        @media (max-width: 1100px) {
            .features-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 1.5rem;
            }
            .landing-card {
                padding: 2rem 1.5rem;
            }
            .landing-header h1 {
                font-size: 2.4rem;
            }
            .admin-play-section {
                margin: 1rem 0;
            }
        }

        @media (max-width: 640px) {
            .features-grid {
                grid-template-columns: 1fr;
            }
            .stats-bar {
                gap: 1.2rem;
                padding: 1rem;
            }
            .stat-number {
                font-size: 1.6rem;
            }
            .btn {
                padding: 0.7rem 1.6rem;
            }
            .landing-header h1 {
                font-size: 1.9rem;
            }
            .logo-icon {
                width: 85px;
                height: 85px;
                font-size: 2.8rem;
            }
            .tesda-badge {
                font-size: 0.75rem;
                padding: 0.5rem 1.2rem;
            }
            .admin-game-buttons {
                gap: 0.5rem;
            }
            .admin-game-btn {
                padding: 0.4rem 0.8rem;
                font-size: 0.7rem;
            }
            .theme-toggle {
                top: 10px;
                right: 10px;
                font-size: 0.75rem;
                padding: 7px 10px;
            }
        }

        html {
            scroll-behavior: smooth;
        }
    </style>
</head>
<body>

<div class="cyber-bg"></div>
<div class="grid-overlay"></div>
<div class="floating-elements" id="floatingElements"></div>

<div class="loading" id="loading">
    <div class="loading-spinner"></div>
    <p style="margin-top: 1rem; color: #0bffb0; font-weight: 500;">Initializing secure portal...</p>
</div>

<button type="button" class="theme-toggle" id="themeToggle" aria-label="Toggle light and dark mode" title="Toggle light and dark mode">
    <i class="fas fa-moon" id="themeIcon"></i>
    <span id="themeText">Dark Mode</span>
</button>

<div class="landing-wrapper">
    <div class="landing-container">
        <div class="landing-card">
            <div class="landing-header">
                <div class="logo-icon">
                    <i class="fas fa-microchip"></i>
                </div>
                <h1>CSS TESDA Assessment Reviewer</h1>
                <div class="tagline">
                    Master Computer Systems Servicing NC II — interactive quizzes, 3D simulations & dynamic games for COC 1-4.
                </div>
            </div>

            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon"><i class="fas fa-question-circle"></i></div>
                    <h3>Interactive Quizzes</h3>
                    <p>Timed assessments with instant feedback, detailed explanations & competency-based drills.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon"><i class="fas fa-cube"></i></div>
                    <h3>3D Simulations</h3>
                    <p>Hands-on hardware installation, network & server configuration, real troubleshooting labs.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon"><i class="fas fa-chart-line"></i></div>
                    <h3>Progress Tracking</h3>
                    <p>Score analytics, performance heatmaps & smart recommendations to boost weak areas.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon"><i class="fas fa-certificate"></i></div>
                    <h3>TESDA Aligned</h3>
                    <p>Based on official CSS NC-II assessment standards, COC competencies, and TR.</p>
                </div>
            </div>

            <?php if (isset($_SESSION['user_id']) && isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
            <div class="admin-play-section">
                <h3><i class="fas fa-crown"></i> Admin Quick Play <span class="admin-badge">ADMIN MODE</span></h3>
                <p><i class="fas fa-info-circle"></i> Your scores will be saved to the database just like regular users!</p>
                <div class="admin-game-buttons">
                    <a href="coc1_game.php" class="admin-game-btn"><i class="fas fa-microchip"></i> COC 1 Game</a>
                    <a href="coc2_game.php" class="admin-game-btn"><i class="fas fa-plug"></i> COC 2 Game</a>
                    <a href="coc3_games.php" class="admin-game-btn"><i class="fas fa-server"></i> COC 3 Games</a>
                    <a href="coc4_game.php" class="admin-game-btn"><i class="fas fa-tools"></i> COC 4 Game</a>
                    <a href="pc_assembly_game.php" class="admin-game-btn"><i class="fas fa-microchip"></i> Installation Sim</a>
                    <a href="os_installation_complete.php" class="admin-game-btn"><i class="fas fa-window-maximize"></i> OS Install</a>
                    <a href="troubleshooting_simulator.php" class="admin-game-btn"><i class="fas fa-tools"></i> Troubleshooting</a>
                    <a href="admin/unified_analytics.php" class="admin-game-btn"><i class="fas fa-chart-line"></i> View Analytics</a>
                </div>
            </div>
            <?php endif; ?>

            <div class="cta-section">
                <div class="cta-buttons">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <a href="dashboard.php" class="btn btn-primary" onclick="showLoading()"><i class="fas fa-tachometer-alt"></i> GO TO DASHBOARD</a>
                        <a href="logout.php" class="btn btn-secondary" onclick="showLoading()"><i class="fas fa-sign-out-alt"></i> LOGOUT</a>
                    <?php else: ?>
                        <a href="login.php" class="btn btn-primary" onclick="showLoading()"><i class="fas fa-sign-in-alt"></i> LOGIN</a>
                        <a href="register.php" class="btn btn-secondary" onclick="showLoading()"><i class="fas fa-user-plus"></i> CREATE ACCOUNT</a>
                    <?php endif; ?>
                </div>
            </div>

            <div class="stats-bar">
                <div class="stat-item"><div class="stat-number">4 COC</div><div class="stat-label">Assessment Areas</div></div>
                <div class="stat-item"><div class="stat-number">450+</div><div class="stat-label">Exam Questions</div></div>
                <div class="stat-item"><div class="stat-number">32</div><div class="stat-label">3D Simulations</div></div>
                <div class="stat-item"><div class="stat-number">⭐ 98%</div><div class="stat-label">Success Pass Rate</div></div>
            </div>

            <div class="tesda-badge">
                <i class="fas fa-certificate"></i>
                <span>TESDA Computer Systems Servicing NC II — Official Digital Reviewer</span>
                <i class="fas fa-shield-alt" style="margin-left: 5px;"></i>
            </div>

            <div class="coc-tags">
                <span class="coc-tag"><i class="fas fa-desktop"></i> COC 1: Install & Configure</span>
                <span class="coc-tag"><i class="fas fa-network-wired"></i> COC 2: Set-up Networks</span>
                <span class="coc-tag"><i class="fas fa-server"></i> COC 3: Set-up Servers</span>
                <span class="coc-tag"><i class="fas fa-tools"></i> COC 4: Maintain & Repair</span>
            </div>
        </div>
    </div>
</div>

<script>
function showLoading() {
    const loadingEl = document.getElementById('loading');
    if (loadingEl) {
        loadingEl.classList.add('active');
        setTimeout(() => {
            if (loadingEl.classList.contains('active')) {
                console.log('Loading still active, check redirection');
            }
        }, 15000);
    }
}

function createFloatingElements() {
    const container = document.getElementById('floatingElements');
    if (!container) return;

    container.innerHTML = '';

    const techItems = [
        ['0x7F', 'fa-microchip'], ['NCII', 'fa-certificate'], ['CSS', 'fa-code'],
        ['BIOS', 'fa-microchip'], ['UEFI', 'fa-desktop'], ['RAM', 'fa-memory'],
        ['CPU', 'fa-microchip'], ['PCIe', 'fa-plug'], ['SATA', 'fa-hard-drive'],
        ['TCP/IP', 'fa-network-wired'], ['OSI', 'fa-layer-group'], ['DHCP', 'fa-server'],
        ['DNS', 'fa-globe'], ['HTTP', 'fa-globe'], ['SSD', 'fa-hard-drive'],
        ['NVMe', 'fa-memory'], ['M.2', 'fa-microchip'], ['JAVA', 'fa-code'],
        ['PYTHON', 'fa-terminal'], ['>_', 'fa-terminal'], ['sudo', 'fa-terminal'],
        ['ping', 'fa-network-wired'], ['netstat', 'fa-network-wired'], ['config.sys', 'fa-file-code'],
        ['010011', 'fa-binary'], ['101010', 'fa-binary'], ['TERMINAL', 'fa-terminal'],
        ['ROUTER', 'fa-router'], ['SWITCH', 'fa-code-branch'], ['FIREWALL', 'fa-shield-halved'],
        ['KVM', 'fa-server'], ['VS Code', 'fa-code'], ['BASH', 'fa-terminal'],
        ['ZSH', 'fa-terminal'], ['KUBERNETES', 'fa-cubes'], ['DOCKER', 'fa-box'],
        ['ANSIBLE', 'fa-gears']
    ];

    const numElements = 48;

    for (let i = 0; i < numElements; i++) {
        const item = techItems[Math.floor(Math.random() * techItems.length)];
        const el = document.createElement('div');
        el.className = 'floating-element';

        const icon = document.createElement('i');
        icon.className = 'fas ' + item[1];

        const label = document.createElement('span');
        let word = item[0];
        if (Math.random() > 0.7) word = `[${word}]`;
        label.textContent = word;

        el.appendChild(icon);
        el.appendChild(label);

        const leftPos = Math.random() * 100;
        const duration = 16 + Math.random() * 22;
        const delay = Math.random() * 18;
        const fontSize = 0.7 + Math.random() * 1.3;

        el.style.left = leftPos + '%';
        el.style.animationDuration = duration + 's';
        el.style.animationDelay = delay + 's';
        el.style.fontSize = fontSize + 'rem';
        el.style.opacity = 0.2 + Math.random() * 0.3;

        container.appendChild(el);
    }
}

function initParallax() {
    const card = document.querySelector('.landing-card');
    if (!card) return;

    document.addEventListener('mousemove', (e) => {
        const mouseX = e.clientX;
        const mouseY = e.clientY;
        const centerX = window.innerWidth / 2;
        const centerY = window.innerHeight / 2;
        const rotateX = (mouseY - centerY) / 70;
        const rotateY = (mouseX - centerX) / 70;
        card.style.transform = `perspective(1200px) rotateX(${rotateX * 0.3}deg) rotateY(${rotateY * 0.3}deg)`;
    });

    document.addEventListener('mouseleave', () => {
        card.style.transform = 'perspective(1200px) rotateX(0deg) rotateY(0deg)';
    });
}

function enhanceButtonEffects() {
    const btns = document.querySelectorAll('.btn');
    btns.forEach(btn => {
        btn.addEventListener('mouseenter', () => {
            btn.style.transform = 'translateY(-2px)';
        });
        btn.addEventListener('mouseleave', () => {
            if (!btn.style.transform.includes('translateY(-3px)')) {
                btn.style.transform = '';
            }
        });
    });
}

function verifyIcons() {
    const progressIcon = document.querySelector('.feature-card:nth-child(3) .feature-icon i');
    if (progressIcon && !progressIcon.classList.contains('fa-chart-line')) {
        progressIcon.className = 'fas fa-chart-line';
    }
}

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

window.addEventListener('load', () => {
    createFloatingElements();
    initParallax();
    enhanceButtonEffects();
    verifyIcons();

    if (window.performance && window.performance.navigation.type === 2) {
        const loadingDiv = document.getElementById('loading');
        if (loadingDiv) loadingDiv.classList.remove('active');
    }
});

window.addEventListener('pageshow', (event) => {
    const loadingDiv = document.getElementById('loading');
    if (loadingDiv && event.persisted) {
        loadingDiv.classList.remove('active');
    }
});

document.querySelectorAll('.btn, .feature-card, .coc-tag, .admin-game-btn').forEach(el => {
    if (el && !el.getAttribute('tabindex')) {
        el.setAttribute('tabindex', '0');
    }
});
</script>
<?php if (file_exists(__DIR__ . '/includes/brightness_widget.php')) { include __DIR__ . '/includes/brightness_widget.php'; } ?>
</body>
</html>

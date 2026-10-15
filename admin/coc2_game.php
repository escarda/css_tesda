<?php
// coc2_game.php - RJ45 Cable Crimping Game with Database Save

require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$coc = 2;
$user_id = $_SESSION['user_id'];

// Get user's high score and games played
$score_query = "SELECT high_score, games_played FROM user_game_scores WHERE user_id = ? AND coc_number = ?";
$stmt = $conn->prepare($score_query);
$stmt->bind_param("ii", $user_id, $coc);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    $user_data = $result->fetch_assoc();
    $high_score = $user_data['high_score'];
    $games_played = $user_data['games_played'];
} else {
    $high_score = 0;
    $games_played = 0;
}
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>COC 2: RJ45 Cable Crimping Game | CSS TESDA Reviewer</title>
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
            min-height: 100vh;
        }

        .cyber-bg {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -2;
            background: radial-gradient(circle at 20% 30%, #0f172a, #020617);
        }

        .grid-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: linear-gradient(rgba(0, 255, 157, 0.05) 1px, transparent 1px), linear-gradient(90deg, rgba(0, 255, 157, 0.05) 1px, transparent 1px);
            background-size: 60px 60px;
            z-index: -1;
            animation: gridMove 20s linear infinite;
        }

        @keyframes gridMove {
            0% { transform: translate(0, 0); }
            100% { transform: translate(60px, 60px); }
        }

        .navbar {
            background: rgba(15, 23, 42, 0.95);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid rgba(0, 255, 157, 0.3);
            padding: 0.8rem 2rem;
            position: sticky;
            top: 0;
            z-index: 100;
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
            font-size: 1.3rem;
            font-weight: 800;
            background: linear-gradient(135deg, #00ff9d, #3b82f6);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            text-decoration: none;
        }

        .nav-links {
            display: flex;
            gap: 1rem;
            align-items: center;
            flex-wrap: wrap;
        }

        .nav-links a {
            color: #94a3b8;
            text-decoration: none;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.9rem;
        }

        .nav-links a:hover, .nav-links a.active {
            color: #00ff9d;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 1rem;
            background: rgba(0, 255, 157, 0.1);
            padding: 0.4rem 1rem;
            border-radius: 40px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 1.5rem;
        }

        .game-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .game-header h1 {
            font-family: 'Orbitron', sans-serif;
            font-size: 2rem;
            background: linear-gradient(135deg, #00ff9d, #3b82f6, #a855f7);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 0.5rem;
        }

        .coc-badge {
            display: inline-block;
            padding: 5px 15px;
            background: rgba(0, 255, 157, 0.15);
            border: 1px solid #00ff9d;
            border-radius: 40px;
            color: #00ff9d;
            font-size: 0.75rem;
            margin-bottom: 1rem;
        }

        /* ========== INSTRUCTIONS PANEL ========== */
        .instructions-panel {
            background: rgba(0, 0, 0, 0.5);
            border: 1px solid #00ff9d;
            border-radius: 20px;
            padding: 1rem;
            margin-bottom: 1.5rem;
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 1.5rem;
        }
        .instruction-item {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 0.8rem;
            color: #a5f3fc;
        }
        .instruction-icon {
            width: 32px;
            height: 32px;
            background: rgba(0, 255, 157, 0.15);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.9rem;
            font-weight: bold;
            color: #00ff9d;
        }
        .instruction-text { color: #cbd5e1; font-size:0.9rem; }
        .instruction-highlight { color: #00ff9d; font-weight: bold; }

        /* Game Mode Selector */
        .mode-selector {
            display: flex;
            justify-content: center;
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .mode-btn {
            padding: 12px 30px;
            background: rgba(15, 23, 42, 0.7);
            border: 2px solid rgba(0, 255, 157, 0.3);
            border-radius: 50px;
            color: #94a3b8;
            font-family: 'Orbitron', sans-serif;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s;
        }

        .mode-btn.active {
            background: linear-gradient(135deg, #00ff9d, #3b82f6);
            border-color: #00ff9d;
            color: #0f172a;
            box-shadow: 0 0 15px rgba(0,255,157,0.3);
        }

        .mode-btn:hover {
            transform: translateY(-2px);
        }

        .info-box {
            background: rgba(168, 85, 247, 0.1);
            border: 1px solid #a855f7;
            border-radius: 12px;
            padding: 10px;
            margin-bottom: 1.5rem;
            text-align: center;
            font-size: 0.85rem;
        }

        .stats-bar {
            display: flex;
            justify-content: center;
            gap: 1.5rem;
            margin-bottom: 2rem;
            flex-wrap: wrap;
        }

        .stat-card {
            background: rgba(15, 23, 42, 0.6);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(0, 255, 157, 0.3);
            border-radius: 20px;
            padding: 0.8rem 1.5rem;
            text-align: center;
        }

        .stat-value {
            font-family: 'Orbitron', sans-serif;
            font-size: 1.8rem;
            font-weight: 700;
            color: #00ff9d;
        }

        .stat-label {
            font-size: 0.65rem;
            color: #94a3b8;
            text-transform: uppercase;
        }

        .timer-card {
            background: linear-gradient(135deg, #1a1a2e, #0f0f23);
            border: 2px solid #ffd700;
            border-radius: 20px;
            padding: 0.8rem 1.5rem;
            text-align: center;
        }

        .timer-value {
            font-family: 'Orbitron', sans-serif;
            font-size: 1.8rem;
            font-weight: 700;
            color: #ffd700;
        }

        /* Main Game Area */
        .game-area {
            background: linear-gradient(145deg, rgba(15,23,42,0.6), rgba(0,0,0,0.4));
            backdrop-filter: blur(10px);
            border-radius: 32px;
            padding: 2rem;
            border: 1px solid rgba(0, 255, 157, 0.3);
        }

        /* Cable Connection Visual */
        .cable-visual {
            background: rgba(0,0,0,0.3);
            border-radius: 20px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            position: relative;
        }

        .cable-title {
            font-family: 'Orbitron', sans-serif;
            font-size: 0.9rem;
            color: #00ff9d;
            text-align: center;
            margin-bottom: 1rem;
        }

        .connectors-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 2rem;
            position: relative;
            min-height: 400px;
        }

        /* Side A - Left Connector */
        .side-connector {
            flex: 1;
            background: linear-gradient(135deg, #1a1a2e, #0f0f23);
            border: 2px solid #00ff9d;
            border-radius: 16px;
            padding: 1rem;
            text-align: center;
            position: relative;
            z-index: 2;
        }

        .side-title {
            font-family: 'Orbitron', sans-serif;
            font-size: 0.8rem;
            color: #a855f7;
            margin-bottom: 0.5rem;
        }

        .side-label {
            font-size: 0.7rem;
            color: #ffd700;
            margin-bottom: 1rem;
        }

        .pin-list {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .pin-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: rgba(0,0,0,0.3);
            border-radius: 8px;
            padding: 6px 12px;
            cursor: pointer;
            transition: all 0.3s;
        }

        .pin-row:hover {
            background: rgba(0,255,157,0.1);
            transform: translateX(5px);
        }

        .pin-row.selected {
            background: rgba(255,215,0,0.2);
            border: 1px solid #ffd700;
        }

        .pin-row.filled {
            background: rgba(0,255,157,0.15);
            border-left: 3px solid #00ff9d;
        }

        .pin-number {
            font-weight: bold;
            color: #64748b;
            width: 30px;
        }

        .pin-wire {
            flex: 1;
            text-align: left;
            padding-left: 10px;
            font-size: 0.75rem;
        }

        .wire-color-dot {
            width: 16px;
            height: 16px;
            border-radius: 50%;
            margin-right: 8px;
            display: inline-block;
        }

        /* Center Cable Lines */
        .cable-lines {
            flex: 2;
            position: relative;
            min-height: 350px;
            background: rgba(0,0,0,0.2);
            border-radius: 20px;
            overflow: visible;
        }

        .lines-canvas {
            position: relative;
            width: 100%;
            height: 100%;
            min-height: 350px;
        }

        .connection-line {
            position: absolute;
            height: 3px;
            background: linear-gradient(90deg, #00ff9d, #a855f7);
            transform-origin: left center;
            border-radius: 3px;
            box-shadow: 0 0 5px #00ff9d;
            transition: all 0.3s ease;
            animation: lineGlow 1s ease-in-out infinite alternate;
        }

        @keyframes lineGlow {
            from { opacity: 0.6; box-shadow: 0 0 2px #00ff9d; }
            to { opacity: 1; box-shadow: 0 0 10px #00ff9d; }
        }

        /* Wire Selection Area */
        .wire-selection {
            margin-top: 2rem;
            padding: 1rem;
            background: rgba(0,0,0,0.2);
            border-radius: 16px;
        }

        .wire-title {
            font-family: 'Orbitron', sans-serif;
            font-size: 0.8rem;
            color: #f59e0b;
            text-align: center;
            margin-bottom: 1rem;
        }

        .wire-grid {
            display: flex;
            justify-content: center;
            gap: 0.8rem;
            flex-wrap: wrap;
        }

        .wire-option {
            padding: 8px 16px;
            background: rgba(0,0,0,0.3);
            border: 2px solid rgba(0,255,157,0.3);
            border-radius: 40px;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .wire-option:hover {
            transform: scale(1.05);
            border-color: #00ff9d;
        }

        .wire-option.selected {
            border-color: #ffd700;
            background: rgba(255,215,0,0.1);
        }

        .wire-dot {
            width: 20px;
            height: 20px;
            border-radius: 50%;
        }

        .wire-name {
            font-size: 0.75rem;
            color: #e0e0ff;
        }

        /* Test Button */
        .test-section {
            margin-top: 2rem;
        }

        .test-btn {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #00ff9d, #3b82f6);
            border: none;
            border-radius: 50px;
            color: #0f172a;
            font-family: 'Orbitron', sans-serif;
            font-weight: bold;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s;
        }

        .test-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,255,157,0.4);
        }

        .reset-btn {
            background: linear-gradient(135deg, #a855f7, #3b82f6);
            margin-top: 0.5rem;
        }

        .result-status {
            margin-top: 1rem;
            padding: 1rem;
            border-radius: 12px;
            text-align: center;
            font-weight: bold;
            display: none;
        }

        .result-status.show {
            display: block;
            animation: fadeIn 0.3s ease;
        }

        .result-correct {
            background: rgba(0,255,157,0.1);
            border: 1px solid #00ff9d;
            color: #00ff9d;
        }

        .result-wrong {
            background: rgba(239,68,68,0.1);
            border: 1px solid #ef4444;
            color: #ef4444;
        }

        .progress-bar {
            margin-top: 1rem;
            height: 8px;
            background: rgba(255,255,255,0.1);
            border-radius: 10px;
            overflow: hidden;
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #00ff9d, #3b82f6, #a855f7);
            border-radius: 10px;
            transition: width 0.5s ease;
            width: 0%;
        }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.95);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }

        .modal.active {
            display: flex;
        }

        .modal-content {
            background: linear-gradient(135deg, rgba(15,23,42,0.98), rgba(0,0,0,0.9));
            border: 2px solid #00ff9d;
            border-radius: 32px;
            max-width: 450px;
            width: 90%;
            padding: 2rem;
            text-align: center;
        }

        .modal-score {
            font-size: 3rem;
            font-weight: bold;
            color: #ffd700;
            margin: 0.5rem 0;
        }

        .modal-buttons {
            display: flex;
            gap: 1rem;
            justify-content: center;
            margin-top: 1.5rem;
            flex-wrap: wrap;
        }

        .btn {
            padding: 10px 24px;
            border: none;
            border-radius: 40px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            font-family: 'Orbitron', sans-serif;
            font-size: 0.8rem;
        }

        .btn-primary {
            background: linear-gradient(135deg, #00ff9d, #3b82f6);
            color: #0f172a;
        }

        .btn-outline {
            background: transparent;
            border: 2px solid #00ff9d;
            color: #00ff9d;
        }

        .btn-save {
            background: linear-gradient(135deg, #ffd700, #f59e0b);
            color: #0f172a;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @media (max-width: 768px) {
            .connectors-container { flex-direction: column; }
            .cable-lines { min-height: 200px; width: 100%; }
            .wire-option { padding: 6px 12px; }
            .instructions-panel { flex-direction: column; gap: 0.8rem; align-items: flex-start; }
        }

        /* ===== COC1-STYLE DARK / LIGHT MODE ===== */
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

        body.light-mode {
            background: #f4f7fb;
            color: #172033;
        }
        body.light-mode .cyber-bg {
            background: radial-gradient(circle at 20% 30%, #ffffff, #eaf1f7);
        }
        body.light-mode .grid-overlay {
            background-image:
                linear-gradient(rgba(0, 150, 105, 0.06) 1px, transparent 1px),
                linear-gradient(90deg, rgba(0, 150, 105, 0.06) 1px, transparent 1px);
        }
        body.light-mode .navbar {
            background: rgba(255, 255, 255, 0.94);
            border-bottom-color: rgba(0, 150, 105, 0.25);
        }
        body.light-mode .nav-links a {
            color: #526174;
        }
        body.light-mode .nav-links a:hover,
        body.light-mode .nav-links a.active {
            color: #008a65;
        }
        body.light-mode .user-info {
            background: rgba(0, 150, 105, 0.08);
            color: #008a65;
        }
        body.light-mode .game-header p,
        body.light-mode .instruction-text {
            color: #526174;
        }
        body.light-mode .instructions-panel,
        body.light-mode .stat-card,
        body.light-mode .game-area {
            background: rgba(255, 255, 255, 0.88);
            color: #172033;
        }
        body.light-mode .instructions-panel,
        body.light-mode .game-area {
            border-color: rgba(0, 150, 105, 0.28);
        }
        body.light-mode .instruction-highlight,
        body.light-mode .cable-title,
        body.light-mode .slots-title {
            color: #008a65;
        }
        body.light-mode .mode-btn {
            background: rgba(255, 255, 255, 0.9);
            border-color: rgba(0, 150, 105, 0.3);
            color: #526174;
        }
        body.light-mode .mode-btn.active {
            background: linear-gradient(135deg, #00a878, #3b82f6);
            border-color: #00a878;
            color: #ffffff;
        }
        body.light-mode .info-box {
            background: rgba(168, 85, 247, 0.08);
        }
        body.light-mode .cable-visual,
        body.light-mode .wire-selection,
        body.light-mode .cable-lines {
            background: rgba(235, 240, 245, 0.9);
        }
        body.light-mode .side-connector {
            background: linear-gradient(135deg, #ffffff, #e7edf2);
            border-color: #00a878;
        }
        body.light-mode .pin-row {
            background: rgba(220, 228, 235, 0.8);
            color: #172033;
        }
        body.light-mode .pin-row:hover {
            background: rgba(0, 150, 105, 0.10);
        }
        body.light-mode .wire-option {
            background: rgba(255, 255, 255, 0.9);
            border-color: rgba(0, 150, 105, 0.3);
        }
        body.light-mode .wire-name {
            color: #172033;
        }
        body.light-mode .progress-bar {
            background: #d7dee6;
        }
        body.light-mode .modal {
            background: rgba(20, 30, 45, 0.72);
        }
        body.light-mode .modal-content {
            background: linear-gradient(135deg, #ffffff, #eef3f8);
            color: #172033;
        }
        body.light-mode .btn-outline {
            border-color: #00a878;
            color: #008a65;
        }
        body.light-mode .btn-outline:hover {
            background: #00a878;
            color: #ffffff;
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
        body.light-mode ::-webkit-scrollbar-track {
            background: #e1e7ed;
        }

    </style>
</head>
<body>
    <div class="cyber-bg"></div>
    <div class="grid-overlay"></div>

    <nav class="navbar">
        <div class="nav-container">
            <a href="dashboard.php" class="logo">🔌 RJ45 CRIMPING SIMULATOR</a>
            <div class="nav-links">
                <a href="dashboard.php"><i class="fas fa-home"></i> Home</a>
                <a href="coc2_review.php"><i class="fas fa-book"></i> COC 2 Review</a>
                <a href="coc2_game.php" class="active"><i class="fas fa-gamepad"></i> Game</a>
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
        <div class="game-header">
            <div class="coc-badge"><i class="fas fa-plug"></i> COC 2: RJ45 CRIMPING SIMULATOR</div>
            <h1>Cat5 Cabling Pin-out</h1>
        </div>

        <div class="instructions-panel">
            <div class="instruction-item">
                <div class="instruction-icon">①</div>
                <div class="instruction-text">Select <span class="instruction-highlight">STRAIGHT-THROUGH</span> or <span class="instruction-highlight">CROSSOVER</span> mode</div>
            </div>
            <div class="instruction-item">
                <div class="instruction-icon">②</div>
                <div class="instruction-text">Click a <span class="instruction-highlight">WIRE COLOR</span> from the bottom panel</div>
            </div>
            <div class="instruction-item">
                <div class="instruction-icon">③</div>
                <div class="instruction-text">Click a <span class="instruction-highlight">PIN NUMBER</span> on Side A or Side B</div>
            </div>
            <div class="instruction-item">
                <div class="instruction-icon">✅</div>
                <div class="instruction-text"><span class="instruction-highlight">CORRECT</span> = +15 points</div>
            </div>
            <div class="instruction-item">
                <div class="instruction-icon">❌</div>
                <div class="instruction-text"><span class="instruction-highlight">WRONG</span> = -5 points</div>
            </div>
            <div class="instruction-item">
                <div class="instruction-icon">🔍</div>
                <div class="instruction-text">Click <span class="instruction-highlight">TEST CABLE</span> to check your work</div>
            </div>
        </div>

        <!-- Game Mode Selection -->
        <div class="mode-selector">
            <button class="mode-btn" id="straightModeBtn" onclick="setMode('straight')">📀 STRAIGHT-THROUGH</button>
            <button class="mode-btn" id="crossoverModeBtn" onclick="setMode('crossover')">🔄 CROSSOVER</button>
        </div>

        <div class="info-box" id="infoBox">
            <i class="fas fa-info-circle"></i> 
            <span id="modeDescription">STRAIGHT-THROUGH: Same standard on both ends (T568B to T568B)</span>
        </div>

        <div class="stats-bar">
            <div class="stat-card"><div class="stat-value" id="scoreDisplay">0</div><div class="stat-label">SCORE</div></div>
            <div class="stat-card"><div class="stat-value" id="highScoreDisplay"><?php echo $high_score; ?></div><div class="stat-label">HIGH SCORE</div></div>
            <div class="stat-card"><div class="stat-value" id="gamesPlayedDisplay"><?php echo $games_played; ?></div><div class="stat-label">GAMES PLAYED</div></div>
            <div class="stat-card"><div class="stat-value" id="wiredCount">0</div><div class="stat-label">CONNECTIONS</div></div>
            <div class="timer-card"><div class="timer-value" id="timerDisplay">05:00</div><div class="stat-label">TIME LEFT</div></div>
        </div>

        <div class="game-area">
            <!-- Cable Connection Visual -->
            <div class="cable-visual">
                <div class="cable-title"><i class="fas fa-chart-line"></i> CABLE CONNECTION MAP</div>
                <div class="connectors-container" id="connectorsContainer">
                    <!-- Side A -->
                    <div class="side-connector" id="sideA">
                        <div class="side-title">SIDE A</div>
                        <div class="side-label" id="sideALabel">T568B</div>
                        <div class="pin-list" id="pinListA"></div>
                    </div>

                    <!-- Cable Lines -->
                    <div class="cable-lines" id="cableLines">
                        <svg id="lineSvg" style="position: absolute; width: 100%; height: 100%; top: 0; left: 0; pointer-events: none;"></svg>
                    </div>

                    <!-- Side B -->
                    <div class="side-connector right" id="sideB">
                        <div class="side-title">SIDE B</div>
                        <div class="side-label" id="sideBLabel">T568B</div>
                        <div class="pin-list" id="pinListB"></div>
                    </div>
                </div>
            </div>

            <!-- Wire Selection -->
            <div class="wire-selection">
                <div class="wire-title"><i class="fas fa-palette"></i> SELECT WIRE COLOR</div>
                <div class="wire-grid" id="wireGrid"></div>
            </div>

            <!-- Test Section -->
            <div class="test-section">
                <button class="test-btn" onclick="testCable()">🔍 TEST CABLE</button>
                <button class="test-btn reset-btn" onclick="resetAll()">🔄 RESET ALL</button>
                <div class="result-status" id="resultStatus"></div>
            </div>

            <div class="progress-bar">
                <div class="progress-fill" id="progressFill"></div>
            </div>
        </div>
    </div>

    <div id="resultModal" class="modal">
        <div class="modal-content">
            <i class="fas fa-trophy" style="font-size: 4rem; color: #ffd700;"></i>
            <h2 id="modalTitle">🏆 PERFECT CABLE! 🏆</h2>
            <p id="modalMessage">You've successfully created the cable!</p>
            <div class="modal-score" id="finalScore">0</div>
            <div id="newHighScoreMsg" style="color: #00ff9d; display: none;">🏆 NEW HIGH SCORE! 🏆</div>
            <div class="modal-buttons">
                <button class="btn btn-save" onclick="saveScoreAndContinue()"><i class="fas fa-save"></i> Save Score</button>
                <button class="btn btn-primary" onclick="restartGame()"><i class="fas fa-redo-alt"></i> Play Again</button>
                <a href="coc2_review.php" class="btn btn-outline">Review</a>
                <a href="dashboard.php" class="btn btn-outline">Dashboard</a>
            </div>
        </div>
    </div>

    <div id="toast" style="position: fixed; bottom: 20px; right: 20px; background: #00ff9d; color: #050812; padding: 12px 24px; border-radius: 50px; display: none; z-index: 200; font-size: 0.9rem; font-weight: bold;"></div>

    <script>
        // T568B Standard
        const t568bOrder = [
            { pin: 1, wire: "White/Orange", color: "#ff8c00", short: "W-O" },
            { pin: 2, wire: "Orange", color: "#ff6600", short: "O" },
            { pin: 3, wire: "White/Green", color: "#90ee90", short: "W-G" },
            { pin: 4, wire: "Blue", color: "#4169e1", short: "B" },
            { pin: 5, wire: "White/Blue", color: "#87ceeb", short: "W-B" },
            { pin: 6, wire: "Green", color: "#00ff00", short: "G" },
            { pin: 7, wire: "White/Brown", color: "#d2b48c", short: "W-BR" },
            { pin: 8, wire: "Brown", color: "#8b4513", short: "BR" }
        ];

        // T568A Standard (For crossover)
        const t568aOrder = [
            { pin: 1, wire: "White/Green", color: "#90ee90", short: "W-G" },
            { pin: 2, wire: "Green", color: "#00ff00", short: "G" },
            { pin: 3, wire: "White/Orange", color: "#ff8c00", short: "W-O" },
            { pin: 4, wire: "Blue", color: "#4169e1", short: "B" },
            { pin: 5, wire: "White/Blue", color: "#87ceeb", short: "W-B" },
            { pin: 6, wire: "Orange", color: "#ff6600", short: "O" },
            { pin: 7, wire: "White/Brown", color: "#d2b48c", short: "W-BR" },
            { pin: 8, wire: "Brown", color: "#8b4513", short: "BR" }
        ];

        const wires = [
            { name: "White/Orange", color: "#ff8c00", short: "W-O" },
            { name: "Orange", color: "#ff6600", short: "O" },
            { name: "White/Green", color: "#90ee90", short: "W-G" },
            { name: "Green", color: "#00ff00", short: "G" },
            { name: "Blue", color: "#4169e1", short: "B" },
            { name: "White/Blue", color: "#87ceeb", short: "W-B" },
            { name: "White/Brown", color: "#d2b48c", short: "W-BR" },
            { name: "Brown", color: "#8b4513", short: "BR" }
        ];

        let currentMode = 'straight';
        let sideAWires = { 1: null, 2: null, 3: null, 4: null, 5: null, 6: null, 7: null, 8: null };
        let sideBWires = { 1: null, 2: null, 3: null, 4: null, 5: null, 6: null, 7: null, 8: null };
        let selectedWire = null;
        let score = 0;
        let timeLeft = 300;
        let timerInterval = null;
        let gameActive = true;
        let gameSaved = false;
        let highScore = <?php echo $high_score; ?>;
        let gamesPlayed = <?php echo $games_played; ?>;

        function showToast(msg, isError = false) {
            const toast = document.getElementById('toast');
            toast.textContent = msg;
            toast.style.background = isError ? '#ef4444' : '#00ff9d';
            toast.style.color = isError ? 'white' : '#050812';
            toast.style.display = 'block';
            setTimeout(() => { toast.style.display = 'none'; }, 2500);
        }

        function startTimer() {
            timerInterval = setInterval(() => {
                if (!gameActive) return;
                if (timeLeft <= 0) {
                    clearInterval(timerInterval);
                    gameActive = false;
                    showToast("TIME'S UP! GAME OVER!", true);
                    endGame(false);
                } else {
                    timeLeft--;
                    let minutes = Math.floor(timeLeft / 60);
                    let seconds = timeLeft % 60;
                    document.getElementById('timerDisplay').textContent = `${minutes.toString().padStart(2,'0')}:${seconds.toString().padStart(2,'0')}`;
                    if (timeLeft <= 60) {
                        document.getElementById('timerDisplay').style.color = '#ef4444';
                    }
                }
            }, 1000);
        }

        function setMode(mode) {
            currentMode = mode;
            
            document.getElementById('straightModeBtn').classList.remove('active');
            document.getElementById('crossoverModeBtn').classList.remove('active');
            document.getElementById(mode + 'ModeBtn').classList.add('active');
            
            let infoBox = document.getElementById('infoBox');
            let modeDesc = document.getElementById('modeDescription');
            let sideALabel = document.getElementById('sideALabel');
            let sideBLabel = document.getElementById('sideBLabel');
            
            if (mode === 'straight') {
                modeDesc.innerHTML = 'STRAIGHT-THROUGH: Same standard on both ends (T568B to T568B)';
                infoBox.style.borderColor = '#00ff9d';
                sideALabel.innerHTML = 'T568B';
                sideBLabel.innerHTML = 'T568B';
            } else {
                modeDesc.innerHTML = 'CROSSOVER: Different standards (T568B to T568A) - Used for PC to PC direct connection';
                infoBox.style.borderColor = '#f59e0b';
                sideALabel.innerHTML = 'T568B';
                sideBLabel.innerHTML = 'T568A';
            }
            
            resetAll();
        }

        function getCorrectWire(side, pin) {
            if (side === 'A') {
                return t568bOrder.find(p => p.pin === pin);
            } else {
                if (currentMode === 'straight') {
                    return t568bOrder.find(p => p.pin === pin);
                } else {
                    return t568aOrder.find(p => p.pin === pin);
                }
            }
        }

        function renderPins() {
            // Render Side A
            let pinListA = document.getElementById('pinListA');
            pinListA.innerHTML = '';
            
            for (let i = 1; i <= 8; i++) {
                let wire = sideAWires[i];
                let correctWire = getCorrectWire('A', i);
                
                let div = document.createElement('div');
                div.className = `pin-row ${wire ? 'filled' : ''}`;
                div.onclick = () => {
                    if (!gameActive) return;
                    if (!selectedWire) {
                        showToast("Please select a wire color first!", true);
                        return;
                    }
                    if (wire) {
                        showToast(`Pin ${i} already has ${wire}. Use RESET to change.`, true);
                        return;
                    }
                    
                    sideAWires[i] = selectedWire.name;
                    
                    if (selectedWire.name === correctWire.wire) {
                        score += 15;
                        showToast(`Side A - Pin ${i} correct! (+15 pts)`, false);
                    } else {
                        score -= 5;
                        showToast(`Side A - Pin ${i} should be ${correctWire.wire} (-5 pts)`, true);
                    }
                    
                    selectedWire = null;
                    updateScore();
                    renderPins();
                    renderWireGrid();
                    drawLines();
                    
                    checkAllFilled();
                };
                div.innerHTML = `
                    <span class="pin-number">${i}</span>
                    <span class="pin-wire">
                        ${wire ? `<span class="wire-color-dot" style="background: ${correctWire.color};"></span>${wire}` : '__________'}
                    </span>
                `;
                pinListA.appendChild(div);
            }
            
            // Render Side B
            let pinListB = document.getElementById('pinListB');
            pinListB.innerHTML = '';
            
            for (let i = 1; i <= 8; i++) {
                let wire = sideBWires[i];
                let correctWire = getCorrectWire('B', i);
                
                let div = document.createElement('div');
                div.className = `pin-row ${wire ? 'filled' : ''}`;
                div.onclick = () => {
                    if (!gameActive) return;
                    if (!selectedWire) {
                        showToast("Please select a wire color first!", true);
                        return;
                    }
                    if (wire) {
                        showToast(`Pin ${i} already has ${wire}. Use RESET to change.`, true);
                        return;
                    }
                    
                    sideBWires[i] = selectedWire.name;
                    
                    if (selectedWire.name === correctWire.wire) {
                        score += 15;
                        showToast(`Side B - Pin ${i} correct! (+15 pts)`, false);
                    } else {
                        score -= 5;
                        showToast(`Side B - Pin ${i} should be ${correctWire.wire} (-5 pts)`, true);
                    }
                    
                    selectedWire = null;
                    updateScore();
                    renderPins();
                    renderWireGrid();
                    drawLines();
                    
                    checkAllFilled();
                };
                div.innerHTML = `
                    <span class="pin-number">${i}</span>
                    <span class="pin-wire">
                        ${wire ? `<span class="wire-color-dot" style="background: ${correctWire.color};"></span>${wire}` : '__________'}
                    </span>
                `;
                pinListB.appendChild(div);
            }
        }

        function renderWireGrid() {
            let container = document.getElementById('wireGrid');
            container.innerHTML = '';
            
            wires.forEach(wire => {
                let div = document.createElement('div');
                div.className = `wire-option ${selectedWire === wire ? 'selected' : ''}`;
                div.onclick = () => {
                    if (!gameActive) return;
                    selectedWire = wire;
                    renderWireGrid();
                    showToast(`Selected: ${wire.name}. Click on any pin to place it.`, false);
                };
                div.innerHTML = `
                    <div class="wire-dot" style="background: ${wire.color}; box-shadow: 0 0 5px ${wire.color};"></div>
                    <span class="wire-name">${wire.name}</span>
                `;
                container.appendChild(div);
            });
        }

        function drawLines() {
            const svg = document.getElementById('lineSvg');
            svg.innerHTML = '';
            
            const sideAElement = document.getElementById('sideA');
            const sideBElement = document.getElementById('sideB');
            const cableLinesElement = document.getElementById('cableLines');
            
            const sideARect = sideAElement.getBoundingClientRect();
            const sideBRect = sideBElement.getBoundingClientRect();
            const containerRect = cableLinesElement.getBoundingClientRect();
            
            const startX = sideARect.right - containerRect.left;
            const endX = sideBRect.left - containerRect.left;
            
            const pinRows = document.querySelectorAll('#pinListA .pin-row');
            const pinRowsB = document.querySelectorAll('#pinListB .pin-row');
            
            for (let i = 0; i < 8; i++) {
                let wireA = sideAWires[i + 1];
                let wireB = sideBWires[i + 1];
                
                if (wireA && wireB) {
                    const rowRect = pinRows[i].getBoundingClientRect();
                    const rowRectB = pinRowsB[i].getBoundingClientRect();
                    
                    const startY = rowRect.top + rowRect.height / 2 - containerRect.top;
                    const endY = rowRectB.top + rowRectB.height / 2 - containerRect.top;
                    
                    const line = document.createElementNS("http://www.w3.org/2000/svg", "line");
                    line.setAttribute("x1", startX);
                    line.setAttribute("y1", startY);
                    line.setAttribute("x2", endX);
                    line.setAttribute("y2", endY);
                    line.setAttribute("stroke", "#00ff9d");
                    line.setAttribute("stroke-width", "3");
                    line.setAttribute("stroke-dasharray", "5,5");
                    line.setAttribute("opacity", "0.8");
                    
                    svg.appendChild(line);
                }
            }
        }

        function testCable() {
            let allFilled = true;
            for (let i = 1; i <= 8; i++) {
                if (!sideAWires[i] || !sideBWires[i]) {
                    allFilled = false;
                    break;
                }
            }
            
            if (!allFilled) {
                showToast("Please fill all pins on both sides before testing!", true);
                return;
            }
            
            let sideACorrect = true;
            let sideBCorrect = true;
            
            for (let i = 1; i <= 8; i++) {
                let correctA = getCorrectWire('A', i);
                let correctB = getCorrectWire('B', i);
                
                if (sideAWires[i] !== correctA.wire) sideACorrect = false;
                if (sideBWires[i] !== correctB.wire) sideBCorrect = false;
            }
            
            if (sideACorrect && sideBCorrect) {
                let bonus = 300;
                score += bonus;
                updateScore();
                showToast(`✅ PERFECT CABLE! +${bonus} BONUS! ✅`, false);
                endGame(true);
            } else if (sideACorrect && !sideBCorrect) {
                showToast("⚠️ Side A (T568B) is correct! Fix Side B ⚠️", true);
            } else if (!sideACorrect && sideBCorrect) {
                showToast("⚠️ Side B is correct! Fix Side A (T568B) ⚠️", true);
            } else {
                if (currentMode === 'straight') {
                    showToast("❌ NOT A STRAIGHT-THROUGH CABLE! Both ends must be T568B ❌", true);
                } else {
                    showToast("❌ NOT A CROSSOVER CABLE! Need T568B → T568A ❌", true);
                }
            }
        }

        function checkAllFilled() {
            let allFilled = true;
            for (let i = 1; i <= 8; i++) {
                if (!sideAWires[i] || !sideBWires[i]) {
                    allFilled = false;
                    break;
                }
            }
            
            if (allFilled) {
                let sideACorrect = true;
                let sideBCorrect = true;
                
                for (let i = 1; i <= 8; i++) {
                    let correctA = getCorrectWire('A', i);
                    let correctB = getCorrectWire('B', i);
                    
                    if (sideAWires[i] !== correctA.wire) sideACorrect = false;
                    if (sideBWires[i] !== correctB.wire) sideBCorrect = false;
                }
                
                if (sideACorrect && sideBCorrect) {
                    endGame(true);
                }
            }
        }

        function resetAll() {
            sideAWires = { 1: null, 2: null, 3: null, 4: null, 5: null, 6: null, 7: null, 8: null };
            sideBWires = { 1: null, 2: null, 3: null, 4: null, 5: null, 6: null, 7: null, 8: null };
            selectedWire = null;
            
            renderPins();
            renderWireGrid();
            drawLines();
            
            showToast("Reset complete! Start crimping your cable.", false);
        }

        function updateScore() {
            document.getElementById('scoreDisplay').textContent = score;
            let wiredCount = 0;
            for (let i = 1; i <= 8; i++) {
                if (sideAWires[i]) wiredCount++;
                if (sideBWires[i]) wiredCount++;
            }
            document.getElementById('wiredCount').textContent = wiredCount;
            let percent = (wiredCount / 16) * 100;
            document.getElementById('progressFill').style.width = percent + '%';
        }

      function saveScoreAndContinue() {
    if (gameSaved) {
        showToast('Score already saved!', false);
        return;
    }
    
    showToast('Saving score...', false);
    
    fetch('save_coc2_score.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'score=' + score
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            gameSaved = true;
            showToast('✅ Score ' + score + ' saved!', false);
            document.getElementById('highScoreDisplay').textContent = data.high_score;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('❌ Network error - score not saved', true);
    });
}
        function endGame(won) {
            gameActive = false;
            clearInterval(timerInterval);
            
            let finalScore = score;
            document.getElementById('finalScore').textContent = finalScore;
            
            let modalTitle = document.getElementById('modalTitle');
            let modalMessage = document.getElementById('modalMessage');
            
            if (currentMode === 'straight') {
                modalTitle.innerHTML = '🏆 STRAIGHT-THROUGH MASTER! 🏆';
                modalMessage.innerHTML = 'You\'ve successfully created a Straight-Through Cable!';
            } else {
                modalTitle.innerHTML = '🏆 CROSSOVER MASTER! 🏆';
                modalMessage.innerHTML = 'You\'ve successfully created a Crossover Cable!';
            }
            
            if (won && finalScore > highScore) {
                document.getElementById('newHighScoreMsg').style.display = 'block';
            }
            
            document.getElementById('resultModal').classList.add('active');
        }

        function restartGame() {
            resetAll();
            score = 0;
            gameActive = true;
            gameSaved = false;
            timeLeft = 300;
            
            clearInterval(timerInterval);
            startTimer();
            updateScore();
            document.getElementById('timerDisplay').style.color = '#ffd700';
            document.getElementById('resultModal').classList.remove('active');
            document.getElementById('newHighScoreMsg').style.display = 'none';
            
            showToast(`Game restarted! Create a ${currentMode === 'straight' ? 'Straight-Through' : 'Crossover'} Cable!`, false);
        }

        // Initialize
        startTimer();
        renderPins();
        renderWireGrid();
        updateScore();
        
        window.addEventListener('resize', () => drawLines());
        setTimeout(() => drawLines(), 100);
    </script>
     <?php $global_brightness = getGlobalBrightness(); include __DIR__ . '/includes/brightness_widget.php'; ?>

    <script>
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
                const nextTheme =
                    document.body.classList.contains('light-mode') ? 'dark' : 'light';

                localStorage.setItem('css_tesda_theme', nextTheme);
                applyTheme(nextTheme);
            });
        }
    })();
    </script>
</body>
</html>
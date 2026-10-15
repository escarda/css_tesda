<?php
// coc4_game.php - COC 4 PC Building Simulator - ULTRA HARDCORE MODE with Database Save (FIXED)

require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$coc = 4;
$user_id = $_SESSION['user_id'];
$page_title = "COC 4: PC Building Simulator - STRESS TEST MODE";
$username = $_SESSION['full_name'] ?? $_SESSION['username'];

// Get user's high score and games played for COC 4
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <title><?php echo $page_title; ?> | CSS TESDA Reviewer</title>
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&family=Rajdhani:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Rajdhani', sans-serif;
            background: #0a0f1e;
            color: #e0e0ff;
            min-height: 100vh;
            overflow-x: hidden;
        }
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
            background-image: linear-gradient(rgba(255, 68, 68, 0.03) 1px, transparent 1px), linear-gradient(90deg, rgba(255, 68, 68, 0.03) 1px, transparent 1px);
            background-size: 50px 50px;
            z-index: -1;
            animation: gridMove 20s linear infinite;
        }
        @keyframes gridMove {
            0% { transform: translate(0, 0); }
            100% { transform: translate(50px, 50px); }
        }
        .navbar {
            background: rgba(10, 20, 40, 0.9);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(255, 68, 68, 0.2);
            padding: 1rem 2rem;
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
            font-size: 1.5rem;
            font-weight: 800;
            background: linear-gradient(135deg, #ff4444, #cc0000);
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
            color: #94a3b8;
            text-decoration: none;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .nav-links a:hover, .nav-links a.active { color: #ff4444; }
        .user-info {
            display: flex;
            align-items: center;
            gap: 1rem;
            color: #ff4444;
        }
        .container {
            max-width: 1600px;
            margin: 0 auto;
            padding: 1.5rem;
        }
        .game-header {
            text-align: center;
            margin-bottom: 1.5rem;
        }
        .game-header h1 {
            font-family: 'Orbitron', sans-serif;
            font-size: 1.8rem;
            background: linear-gradient(135deg, #ff4444, #cc0000);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 0.5rem;
        }

        /* Instructions Panel */
        .instructions-panel {
            background: rgba(0, 0, 0, 0.5);
            border: 1px solid #ff4444;
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
            color: #ffaa66;
        }
        .instruction-icon {
            width: 32px;
            height: 32px;
            background: rgba(255, 68, 68, 0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.9rem;
            font-weight: bold;
            color: #ff4444;
        }
        .instruction-text { color: #cbd5e1; font-size:0.9rem; }
        .instruction-highlight { color: #ff4444; font-weight: bold; }

        /* Stats Bar */
        .stats-bar {
            display: flex;
            justify-content: center;
            gap: 1rem;
            margin-bottom: 1rem;
            flex-wrap: wrap;
        }
        .stat-card {
            background: rgba(0, 0, 0, 0.4);
            border: 1px solid rgba(255, 68, 68, 0.3);
            border-radius: 15px;
            padding: 0.5rem 1rem;
            text-align: center;
        }
        .stat-value {
            font-family: 'Orbitron', sans-serif;
            font-size: 1.5rem;
            font-weight: bold;
            color: #ff4444;
        }
        .stat-label {
            font-size: 0.65rem;
            color: #94a3b8;
        }

        .stress-bar {
            background: rgba(0,0,0,0.5);
            border-radius: 10px;
            padding: 0.8rem;
            margin-bottom: 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }
        .stress-meter {
            flex: 1;
            height: 20px;
            background: rgba(255,0,0,0.2);
            border-radius: 10px;
            overflow: hidden;
        }
        .stress-fill {
            width: 0%;
            height: 100%;
            background: linear-gradient(90deg, #ffaa00, #ff0000);
            transition: width 0.5s;
            border-radius: 10px;
        }
        .timer-display {
            font-family: monospace;
            font-size: 1.5rem;
            font-weight: bold;
            color: #ff4444;
        }
        .damage-warning {
            background: rgba(255,0,0,0.2);
            border-left: 4px solid #ff0000;
            padding: 0.5rem;
            margin: 0.5rem 0;
            font-size: 0.8rem;
            animation: blink 1s infinite;
        }
        @keyframes blink {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
        .simulation-area {
            display: grid;
            grid-template-columns: 1fr 350px;
            gap: 1.5rem;
        }
        .pc-case {
            background: rgba(0, 0, 0, 0.5);
            border: 3px solid #ff4444;
            border-radius: 20px;
            padding: 1.5rem;
            max-height: 70vh;
            overflow-y: auto;
        }
        .pc-case h3 { color: #ff8888; margin-bottom: 1rem; display: flex; align-items: center; gap: 10px; }
        .component-slot {
            background: rgba(0, 0, 0, 0.5);
            border: 2px dashed rgba(255, 68, 68, 0.3);
            border-radius: 12px;
            padding: 0.8rem;
            margin-bottom: 0.8rem;
        }
        .component-slot.damaged { border-color: #ff0000; background: rgba(255,0,0,0.1); }
        .component-slot.installed { border-color: #00ff9d; background: rgba(0, 255, 157, 0.05); }
        .component-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.5rem;
            flex-wrap: wrap;
            gap: 0.5rem;
        }
        .component-name { font-size: 1rem; font-weight: bold; }
        .component-status {
            padding: 2px 8px;
            border-radius: 20px;
            font-size: 0.7rem;
        }
        .status-missing { background: rgba(255, 0, 0, 0.2); color: #ff6666; }
        .status-installed { background: rgba(0, 255, 0, 0.2); color: #00ff9d; }
        .status-damaged { background: rgba(255, 0, 0, 0.4); color: #ff0000; animation: blink 0.5s infinite; }
        .status-progress { background: rgba(255, 170, 0, 0.2); color: #ffaa00; }
        .steps-container { margin-top: 0.5rem; padding-left: 0.5rem; }
        .step-item {
            display: flex;
            align-items: center;
            gap: 8px;
            margin: 0.3rem 0;
            padding: 0.3rem;
            background: rgba(0,0,0,0.3);
            border-radius: 6px;
            font-size: 0.8rem;
        }
        .step-item.completed { opacity: 0.6; background: rgba(0,255,0,0.05); }
        .step-btn {
            background: linear-gradient(45deg, #ff4444, #cc0000);
            border: none;
            padding: 2px 10px;
            border-radius: 15px;
            color: white;
            cursor: pointer;
            font-size: 0.7rem;
            margin-left: auto;
        }
        .step-btn:disabled { opacity: 0.5; cursor: not-allowed; }
        .tools-panel {
            background: rgba(0, 0, 0, 0.5);
            border: 1px solid rgba(255, 68, 68, 0.3);
            border-radius: 20px;
            padding: 1rem;
            max-height: 70vh;
            overflow-y: auto;
        }
        .tools-panel h3 { color: #ff8888; margin-bottom: 1rem; font-size: 1rem; }
        .tool-btn {
            width: 100%;
            background: rgba(0, 0, 0, 0.5);
            border: 1px solid rgba(255, 68, 68, 0.3);
            border-radius: 10px;
            padding: 0.7rem;
            margin-bottom: 0.7rem;
            display: flex;
            align-items: center;
            gap: 10px;
            cursor: pointer;
            font-size: 0.8rem;
        }
        .tool-btn:hover { background: rgba(255, 68, 68, 0.1); }
        .diagnostic-result {
            background: rgba(0, 0, 0, 0.5);
            border-radius: 10px;
            padding: 0.8rem;
            margin-top: 1rem;
            font-size: 0.75rem;
            max-height: 200px;
            overflow-y: auto;
        }
        .power-section {
            background: rgba(0, 0, 0, 0.5);
            border-radius: 12px;
            padding: 0.8rem;
            text-align: center;
            margin-top: 1rem;
        }
        .power-btn {
            background: #ff4444;
            border: none;
            padding: 8px 16px;
            border-radius: 30px;
            color: white;
            font-weight: bold;
            cursor: pointer;
            margin-top: 0.5rem;
        }
        .led-green { display: inline-block; width: 10px; height: 10px; background: #00ff9d; border-radius: 50%; animation: pulse 1s infinite; margin-right: 5px; }
        .led-red { display: inline-block; width: 10px; height: 10px; background: #ff4444; border-radius: 50%; margin-right: 5px; }
        @keyframes pulse { 0%, 100% { opacity: 1; } 50% { opacity: 0.3; } }
        .game-over {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.95);
            z-index: 1000;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
        }
        .success-message {
            background: rgba(0,255,0,0.2);
            padding: 1rem;
            border-radius: 10px;
            text-align: center;
        }
        .toast {
            position: fixed;
            bottom: 20px;
            right: 20px;
            padding: 12px 24px;
            border-radius: 50px;
            display: none;
            z-index: 200;
            font-weight: bold;
        }
        @media (max-width: 900px) { .simulation-area { grid-template-columns: 1fr; } .container { padding: 1rem; } }

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
        body.light-mode .page-header p,
        body.light-mode .instruction-text,
        body.light-mode .game-card > p {
            color: #526174;
        }
        body.light-mode .instructions-panel,
        body.light-mode .stat-card,
        body.light-mode .game-card {
            background: rgba(255, 255, 255, 0.88);
            color: #172033;
            border-color: rgba(0, 150, 105, 0.28);
        }
        body.light-mode .instruction-item {
            color: #526174;
        }
        body.light-mode .instruction-icon {
            background: rgba(0, 150, 105, 0.10);
            color: #008a65;
        }
        body.light-mode .game-tab {
            background: rgba(255, 255, 255, 0.9);
            border-color: rgba(0, 150, 105, 0.3);
            color: #526174;
        }
        body.light-mode .game-tab:hover {
            background: rgba(0, 150, 105, 0.08);
            border-color: #00a878;
            color: #008a65;
        }
        body.light-mode .game-tab.active {
            background: linear-gradient(135deg, #00a878, #3b82f6);
            border-color: transparent;
            color: #ffffff;
        }
        body.light-mode .config-field input,
        body.light-mode .config-field select,
        body.light-mode .flag-input,
        body.light-mode .response-input {
            background: rgba(255, 255, 255, 0.95);
            border-color: rgba(0, 150, 105, 0.3);
            color: #172033;
        }
        body.light-mode .config-field input::placeholder,
        body.light-mode .flag-input::placeholder,
        body.light-mode .response-input::placeholder {
            color: #7b8798;
        }
        body.light-mode .ctf-challenge,
        body.light-mode .roleplay-message {
            background: rgba(235, 240, 245, 0.9);
        }
        body.light-mode .timer-box,
        body.light-mode .score-area {
            color: #172033;
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
            <a href="dashboard.php" class="logo">CSS TESDA Reviewer</a>
            <div class="nav-links">
                <a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                <a href="coc1_review.php"><i class="fas fa-microchip"></i> COC 1</a>
                <a href="coc2_review.php"><i class="fas fa-network-wired"></i> COC 2</a>
                <a href="coc3_review.php"><i class="fas fa-server"></i> COC 3</a>
                <a href="coc4_review.php"><i class="fas fa-tools"></i> COC 4</a>
                <a href="coc4_game.php" class="active"><i class="fas fa-skull-crosswalk"></i> Stress Test</a>
                <div class="user-info">
                    <i class="fas fa-user"></i>
                    <span><?php echo htmlspecialchars($username); ?></span>
                    <button type="button" class="theme-toggle" id="themeToggle" aria-label="Toggle light and dark mode" title="Toggle light and dark mode">
                        <i class="fas fa-moon" id="themeIcon"></i>
                        <span id="themeText">Dark Mode</span>
                    </button>
                    <a href="logout.php" style="color: #8f8181;"><i class="fas fa-sign-out-alt"></i></a>
                </div>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="game-header">
            <h1><i class="fas fa-skull-crosswalk"></i> PC Building Simulator - STRESS TEST MODE</h1>
            <p>⏱️ TIMED INSTALLATION | 💀 PERMANENT DAMAGE | 🔥 OVERHEATING | ⚡ ESD RISK | 📦 WRONG PARTS</p>
        </div>

        <!-- Stats Bar -->
        <div class="stats-bar">
            <div class="stat-card"><div class="stat-value" id="highScoreDisplay"><?php echo $high_score; ?></div><div class="stat-label">HIGH SCORE</div></div>
            <div class="stat-card"><div class="stat-value" id="gamesPlayedDisplay"><?php echo $games_played; ?></div><div class="stat-label">GAMES PLAYED</div></div>
        </div>

        <!-- Instructions Panel -->
        <div class="instructions-panel">
            <div class="instruction-item">
                <div class="instruction-icon">①</div>
                <div class="instruction-text">Click <span class="instruction-highlight">STEP BUTTONS</span> to install each component step-by-step</div>
            </div>
            <div class="instruction-item">
                <div class="instruction-icon">②</div>
                <div class="instruction-text"><span class="instruction-highlight">RISK PERCENTAGES</span> shown - higher risk = more likely to fail</div>
            </div>
            <div class="instruction-item">
                <div class="instruction-icon">③</div>
                <div class="instruction-text">Use <span class="instruction-highlight">EMERGENCY TOOLS</span> to diagnose and fix issues</div>
            </div>
            <div class="instruction-item">
                <div class="instruction-icon">④</div>
                <div class="instruction-text"><span class="instruction-highlight">ESD WRIST STRAP</span> prevents static damage (30% failure chance without)</div>
            </div>
            <div class="instruction-item">
                <div class="instruction-icon">⑤</div>
                <div class="instruction-text"><span class="instruction-highlight">POWER ON</span> only after essential components are installed</div>
            </div>
            <div class="instruction-item">
                <div class="instruction-icon">🏆</div>
                <div class="instruction-text">Install <span class="instruction-highlight">ALL 7 components</span> without dying to WIN!</div>
            </div>
        </div>

        <div class="stress-bar">
            <div><i class="fas fa-heartbeat"></i> <span id="lives">3</span> Lives</div>
            <div class="stress-meter"><div class="stress-fill" id="stressFill"></div></div>
            <div class="timer-display" id="timerDisplay">05:00</div>
            <div><i class="fas fa-fire"></i> <span id="tempDisplay">45°C</span></div>
        </div>

        <div class="simulation-area">
            <div class="pc-case">
                <h3><i class="fas fa-desktop"></i> Computer Case - ATX</h3>
                <div id="pcComponents"></div>
                <div class="power-section">
                    <span id="powerLed" class="led-red"></span> Status: <span id="powerStatus">OFF</span>
                    <br><button class="power-btn" id="powerBtn" onclick="togglePower()"><i class="fas fa-power-off"></i> POWER ON</button>
                </div>
                <div id="bootResult"></div>
            </div>

            <div class="tools-panel">
                <h3><i class="fas fa-toolbox"></i> Emergency Tools</h3>
                <div class="tool-btn" onclick="runDiagnostic()"><div class="tool-icon"><i class="fas fa-stethoscope"></i></div><div>Full Diagnostic</div></div>
                <div class="tool-btn" onclick="checkCompatibility()"><div class="tool-icon"><i class="fas fa-microchip"></i></div><div>Compatibility Check</div></div>
                <div class="tool-btn" onclick="testPSU()"><div class="tool-icon"><i class="fas fa-plug"></i></div><div>PSU Tester</div></div>
                <div class="tool-btn" onclick="emergencyCool()"><div class="tool-icon"><i class="fas fa-snowflake"></i></div><div>Emergency Cooling</div></div>
                <div class="tool-btn" onclick="useESDWriststrap()"><div class="tool-icon"><i class="fas fa-hand-paper"></i></div><div>Use ESD Wrist Strap</div></div>
                <div id="diagnosticResult" class="diagnostic-result"><i class="fas fa-info-circle"></i> Ready...</div>
            </div>
        </div>
    </div>

    <div id="toast" class="toast"></div>

    <script>
        // ============ ULTRA HARDCORE MODE with Database Save ==========
        let lives = 3;
        let stressLevel = 0;
        let timeLeft = 300; // 5 minutes
        let timerInterval;
        let temperature = 45;
        let tempInterval;
        let esdProtected = false;
        let gameActive = true;
        let gameSaved = false;
        let finalScore = 0;
        let highScore = <?php echo $high_score; ?>;
        let gamesPlayed = <?php echo $games_played; ?>;
        
        function showToast(msg, isError = false) {
            const toast = document.getElementById('toast');
            toast.textContent = msg;
            toast.style.background = isError ? '#ef4444' : '#00ff9d';
            toast.style.color = isError ? 'white' : '#050812';
            toast.style.display = 'block';
            setTimeout(() => { toast.style.display = 'none'; }, 3000);
        }
        
        function saveScore() {
            if (gameSaved) {
                showToast('Score already saved!', false);
                return;
            }
            
            showToast('Saving score...', false);
            
            // Use fetch to save score
            fetch('save_game_score.php', {
                method: 'POST',
                headers: { 
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'coc=4&score=' + finalScore + '&games_played=1'
            })
            .then(response => response.json())
            .then(data => {
                console.log('Save response:', data);
                if (data.success) {
                    gameSaved = true;
                    showToast('✅ Score ' + finalScore + ' saved to database!', false);
                    document.getElementById('highScoreDisplay').textContent = data.high_score;
                    document.getElementById('gamesPlayedDisplay').textContent = data.games_played;
                } else {
                    showToast('❌ Error saving score: ' + (data.error || 'Unknown error'), true);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('❌ Network error saving score', true);
            });
        }
        
        // Components with multi-step installation
        let components = {
            motherboard: {
                installed: false, damaged: false, name: "Motherboard", icon: "fa-microchip", part: "ASUS ROG B550-F",
                steps: [
                    { id: 1, text: "Install I/O Shield", completed: false, action: "Install I/O Shield", risk: 15 },
                    { id: 2, text: "Install standoffs (9 positions)", completed: false, action: "Install 9 standoffs", risk: 20 },
                    { id: 3, text: "Place motherboard on standoffs", completed: false, action: "Position board", risk: 10 },
                    { id: 4, text: "Screw in 9 screws (cross pattern)", completed: false, action: "Tighten screws", risk: 25 }
                ], currentStep: 0, temp: 35
            },
            cpu: {
                installed: false, damaged: false, name: "CPU", icon: "fa-microchip", part: "AMD Ryzen 7 5800X",
                steps: [
                    { id: 1, text: "Lift CPU lever", completed: false, action: "Open socket", risk: 10 },
                    { id: 2, text: "Align gold triangle (CORRECT orientation!)", completed: false, action: "Align CPU", risk: 40 },
                    { id: 3, text: "Gently place CPU - NO FORCE!", completed: false, action: "Insert CPU", risk: 50 },
                    { id: 4, text: "Lower and lock lever", completed: false, action: "Lock socket", risk: 15 }
                ], currentStep: 0, temp: 45
            },
            cooler: {
                installed: false, damaged: false, name: "CPU Cooler", icon: "fa-fan", part: "Noctua NH-D15",
                steps: [
                    { id: 1, text: "Apply thermal paste (pea-sized)", completed: false, action: "Apply paste", risk: 30 },
                    { id: 2, text: "Position cooler over CPU", completed: false, action: "Position cooler", risk: 10 },
                    { id: 3, text: "Tighten screws cross-pattern", completed: false, action: "Screw cooler", risk: 35 },
                    { id: 4, text: "Connect CPU fan header", completed: false, action: "Connect fan", risk: 5 }
                ], currentStep: 0, temp: 38
            },
            ram: {
                installed: false, damaged: false, name: "RAM", icon: "fa-memory", part: "32GB DDR4-3600",
                steps: [
                    { id: 1, text: "Open DIMM slot clips", completed: false, action: "Open slots", risk: 5 },
                    { id: 2, text: "Align notch with slot (correct direction!)", completed: false, action: "Align RAM", risk: 30 },
                    { id: 3, text: "Press firmly until clips click", completed: false, action: "Insert RAM", risk: 25 }
                ], currentStep: 0, temp: 30
            },
            gpu: {
                installed: false, damaged: false, name: "GPU", icon: "fa-tv", part: "NVIDIA RTX 4070 Ti",
                steps: [
                    { id: 1, text: "Remove PCIe slot covers", completed: false, action: "Remove covers", risk: 10 },
                    { id: 2, text: "Align GPU with PCIe x16 slot", completed: false, action: "Align GPU", risk: 15 },
                    { id: 3, text: "Press until latch clicks", completed: false, action: "Insert GPU", risk: 20 },
                    { id: 4, text: "Screw GPU bracket", completed: false, action: "Secure GPU", risk: 15 },
                    { id: 5, text: "Connect 8-pin PCIe power cables", completed: false, action: "Connect power", risk: 10 }
                ], currentStep: 0, temp: 55
            },
            storage: {
                installed: false, damaged: false, name: "NVMe SSD", icon: "fa-hdd", part: "Samsung 980 Pro 1TB",
                steps: [
                    { id: 1, text: "Locate M.2 slot", completed: false, action: "Find slot", risk: 5 },
                    { id: 2, text: "Insert at 30-degree angle", completed: false, action: "Insert SSD", risk: 20 },
                    { id: 3, text: "Push down and screw in place", completed: false, action: "Secure SSD", risk: 15 }
                ], currentStep: 0, temp: 35
            },
            psu: {
                installed: false, damaged: false, name: "PSU", icon: "fa-plug", part: "Corsair RM850x",
                steps: [
                    { id: 1, text: "Mount PSU in case (fan facing down)", completed: false, action: "Mount PSU", risk: 10 },
                    { id: 2, text: "Screw 4 mounting screws", completed: false, action: "Screw PSU", risk: 10 },
                    { id: 3, text: "Connect 24-pin to motherboard", completed: false, action: "Connect 24-pin", risk: 15 },
                    { id: 4, text: "Connect 8-pin EPS to CPU", completed: false, action: "Connect CPU power", risk: 15 },
                    { id: 5, text: "Connect PCIe to GPU", completed: false, action: "Connect GPU power", risk: 10 }
                ], currentStep: 0, temp: 40
            }
        };
        
        let isPoweredOn = false;
        
        function calculateScore() {
            let installedCount = 0;
            let totalSteps = 0;
            let completedSteps = 0;
            
            for (let comp in components) {
                const c = components[comp];
                totalSteps += c.steps.length;
                completedSteps += c.steps.filter(s => s.completed).length;
                if (c.installed && !c.damaged) installedCount++;
            }
            
            const timeBonus = Math.floor(timeLeft / 10) * 5;
            const livesBonus = lives * 20;
            const stressPenalty = Math.floor(stressLevel / 5);
            const stepBonus = completedSteps * 10;
            
            finalScore = stepBonus + timeBonus + livesBonus - stressPenalty;
            finalScore = Math.max(0, finalScore);
            return finalScore;
        }
        
        function startTimer() {
            timerInterval = setInterval(() => {
                if (!gameActive) return;
                if (timeLeft <= 0) {
                    gameOver("TIME'S UP! You failed to complete the build in time!");
                }
                timeLeft--;
                const minutes = Math.floor(timeLeft / 60);
                const seconds = timeLeft % 60;
                document.getElementById('timerDisplay').textContent = `${minutes.toString().padStart(2,'0')}:${seconds.toString().padStart(2,'0')}`;
                
                if (timeLeft % 30 === 0 && timeLeft > 0) {
                    increaseStress(5, "Time pressure increasing stress!");
                }
            }, 1000);
        }
        
        function startTemperatureMonitor() {
            tempInterval = setInterval(() => {
                if (!gameActive || !isPoweredOn) return;
                temperature += Math.random() * 3;
                if (!components.cooler.installed) temperature += 5;
                if (temperature > 85) {
                    damageComponent('cpu', "CPU overheated and fried!");
                    temperature = 45;
                }
                if (temperature > 75) {
                    document.getElementById('tempDisplay').style.color = '#ffaa00';
                } else {
                    document.getElementById('tempDisplay').style.color = '#00ff9d';
                }
                document.getElementById('tempDisplay').textContent = Math.floor(temperature) + '°C';
            }, 2000);
        }
        
        function increaseStress(amount, reason) {
            if (!gameActive) return;
            stressLevel = Math.min(100, stressLevel + amount);
            document.getElementById('stressFill').style.width = stressLevel + '%';
            if (stressLevel >= 80) {
                document.getElementById('stressFill').style.background = 'linear-gradient(90deg, #ff0000, #ff0000)';
            }
            if (stressLevel >= 100) {
                gameOver("STRESS OVERLOAD! You made too many mistakes!");
            }
            addDiagnosticMessage(`⚠️ Stress +${amount}: ${reason}`);
        }
        
        function damageComponent(component, reason) {
            if (!gameActive) return;
            if (components[component].damaged) return;
            components[component].damaged = true;
            lives--;
            document.getElementById('lives').textContent = lives;
            addDiagnosticMessage(`💀 ${components[component].name} DAMAGED! ${reason}`);
            
            if (lives <= 0) {
                gameOver("GAME OVER! You destroyed too many components!");
            }
            
            const slotDiv = document.getElementById(`${component}-slot`);
            if (slotDiv) slotDiv.classList.add('damaged');
            const statusSpan = document.querySelector(`#${component}-slot .component-status`);
            if (statusSpan) {
                statusSpan.className = 'component-status status-damaged';
                statusSpan.innerHTML = '<i class="fas fa-skull"></i> DAMAGED';
            }
        }
        
        function gameOver(reason) {
            if (!gameActive) return;
            gameActive = false;
            clearInterval(timerInterval);
            clearInterval(tempInterval);
            calculateScore();
            document.getElementById('bootResult').innerHTML = `
                <div class="game-over">
                    <div style="background:rgba(0,0,0,0.95); padding:2rem; border-radius:20px; text-align:center; border:2px solid #ff0000;">
                        <i class="fas fa-skull" style="font-size:4rem; color:#ff0000;"></i>
                        <h2 style="color:#ff0000;">GAME OVER</h2>
                        <p>${reason}</p>
                        <p>Final Score: ${finalScore} points</p>
                        <button onclick="saveScoreAndReload()" style="background:#ffaa00; border:none; padding:10px 20px; border-radius:10px; color:#050812; margin:0.5rem; cursor:pointer;"><i class="fas fa-save"></i> Save Score</button>
                        <button onclick="location.reload()" style="background:#ff4444; border:none; padding:10px 20px; border-radius:10px; color:white; margin:0.5rem; cursor:pointer;">TRY AGAIN</button>
                    </div>
                </div>
            `;
        }
        
        function saveScoreAndReload() {
            saveScore();
            setTimeout(() => {
                if (gameSaved) {
                    location.reload();
                }
            }, 2000);
        }
        
        function addDiagnosticMessage(msg) {
            const diagDiv = document.getElementById('diagnosticResult');
            diagDiv.innerHTML = `<div style="color:#ffaa66;">${msg}</div>` + diagDiv.innerHTML;
            if (diagDiv.children.length > 10) diagDiv.removeChild(diagDiv.lastChild);
        }
        
        function completeStep(component, stepIndex) {
            if (!gameActive) return;
            if (components[component].installed || components[component].damaged) return;
            
            const step = components[component].steps[stepIndex];
            if (step.completed) return;
            
            const risk = step.risk;
            const random = Math.random() * 100;
            
            if (!esdProtected && Math.random() < 0.3) {
                damageComponent(component, "ESD damage! You forgot to use anti-static wrist strap!");
                addDiagnosticMessage("⚡ ESD STATIC DISCHARGE damaged the component!");
                return;
            }
            
            if (random < risk) {
                damageComponent(component, `Failed at step "${step.text}" - ${risk}% risk triggered!`);
                addDiagnosticMessage(`💥 FAILED: ${step.text} - Component damaged!`);
                return;
            }
            
            step.completed = true;
            components[component].currentStep++;
            addDiagnosticMessage(`✅ Completed: ${step.text}`);
            
            stressLevel = Math.max(0, stressLevel - 5);
            document.getElementById('stressFill').style.width = stressLevel + '%';
            
            const stepDiv = document.getElementById(`${component}-step-${stepIndex}`);
            if (stepDiv) {
                stepDiv.classList.add('completed');
                stepDiv.querySelector('.step-check').innerHTML = '<i class="fas fa-check"></i>';
            }
            
            const allCompleted = components[component].steps.every(s => s.completed);
            if (allCompleted) {
                components[component].installed = true;
                addDiagnosticMessage(`🎉 ${components[component].name} FULLY INSTALLED!`);
                updateComponentUI(component);
                checkAllComponentsComplete();
            }
            
            updateComponentUI(component);
        }
        
        function checkAllComponentsComplete() {
            let allComplete = true;
            for (let comp in components) {
                if (!components[comp].installed && !components[comp].damaged) allComplete = false;
            }
            if (allComplete && gameActive) {
                clearInterval(timerInterval);
                clearInterval(tempInterval);
                calculateScore();
                const timeSpent = 300 - timeLeft;
                const minutes = Math.floor(timeSpent / 60);
                const seconds = timeSpent % 60;
                document.getElementById('bootResult').innerHTML = `
                    <div class="success-message">
                        <i class="fas fa-trophy" style="font-size:3rem; color:#ffcc00;"></i>
                        <h2 style="color:#00ff9d;">🏆 ULTRA HARDCORE COMPLETE! 🏆</h2>
                        <p>Build Time: ${minutes}:${seconds.toString().padStart(2,'0')}</p>
                        <p>Lives Remaining: ${lives} | Final Stress: ${stressLevel}%</p>
                        <p>Final Score: ${finalScore} points</p>
                        <button onclick="saveScoreAndReload()" style="background:#ffaa00; border:none; padding:10px 20px; border-radius:10px; color:#050812; margin:0.5rem; cursor:pointer;"><i class="fas fa-save"></i> Save Score</button>
                        <button onclick="location.reload()" style="background:#00ff9d; border:none; padding:10px 20px; border-radius:10px; color:#050812; margin:0.5rem; cursor:pointer;">NEW CHALLENGE</button>
                    </div>
                `;
                gameActive = false;
            }
        }
        
        function updateComponentUI(component) {
            const comp = components[component];
            const slotDiv = document.getElementById(`${component}-slot`);
            if (!slotDiv) return;
            
            if (comp.damaged) {
                slotDiv.classList.add('damaged');
                const statusSpan = slotDiv.querySelector('.component-status');
                if (statusSpan) {
                    statusSpan.className = 'component-status status-damaged';
                    statusSpan.innerHTML = '<i class="fas fa-skull"></i> DAMAGED';
                }
            } else if (comp.installed) {
                slotDiv.classList.remove('damaged');
                slotDiv.classList.add('installed');
                const statusSpan = slotDiv.querySelector('.component-status');
                if (statusSpan) {
                    statusSpan.className = 'component-status status-installed';
                    statusSpan.innerHTML = '<i class="fas fa-check-circle"></i> Installed';
                }
            } else if (comp.currentStep > 0) {
                const statusSpan = slotDiv.querySelector('.component-status');
                if (statusSpan) {
                    statusSpan.className = 'component-status status-progress';
                    statusSpan.innerHTML = `<i class="fas fa-spinner"></i> ${comp.currentStep}/${comp.steps.length}`;
                }
            }
        }
        
        function renderPCComponents() {
            const container = document.getElementById('pcComponents');
            const componentOrder = ['motherboard', 'cpu', 'cooler', 'ram', 'gpu', 'storage', 'psu'];
            let html = '';
            
            componentOrder.forEach(comp => {
                const c = components[comp];
                let statusClass = 'status-missing';
                let statusText = '<i class="fas fa-times-circle"></i> Missing';
                if (c.damaged) {
                    statusClass = 'status-damaged';
                    statusText = '<i class="fas fa-skull"></i> DAMAGED';
                } else if (c.installed) {
                    statusClass = 'status-installed';
                    statusText = '<i class="fas fa-check-circle"></i> Installed';
                } else if (c.currentStep > 0) {
                    statusClass = 'status-progress';
                    statusText = `<i class="fas fa-spinner"></i> ${c.currentStep}/${c.steps.length}`;
                }
                
                html += `<div class="component-slot" id="${comp}-slot">
                    <div class="component-header">
                        <span class="component-name"><i class="fas ${c.icon}"></i> ${c.name}</span>
                        <span class="component-status ${statusClass}">${statusText}</span>
                    </div>
                    <div class="steps-container">`;
                
                c.steps.forEach((step, idx) => {
                    const completedClass = step.completed ? 'completed' : '';
                    const riskBadge = !step.completed && !c.installed && !c.damaged ? `<span style="font-size:0.6rem; color:#ffaa66;"> (${step.risk}% risk)</span>` : '';
                    html += `<div class="step-item ${completedClass}" id="${comp}-step-${idx}">
                        <div class="step-check">${step.completed ? '<i class="fas fa-check"></i>' : (idx+1)}</div>
                        <div class="step-text">${step.text}${riskBadge}</div>
                        ${!step.completed && !c.installed && !c.damaged && gameActive ? `<button class="step-btn" onclick="completeStep('${comp}', ${idx})"><i class="fas fa-wrench"></i> ${step.action}</button>` : ''}
                    </div>`;
                });
                html += `</div></div>`;
            });
            container.innerHTML = html;
        }
        
        function togglePower() {
            if (!gameActive) return;
            if (!isPoweredOn) {
                const essential = ['motherboard', 'cpu', 'ram', 'psu'];
                let missingEssential = [];
                essential.forEach(comp => { if (!components[comp].installed) missingEssential.push(components[comp].name); });
                if (missingEssential.length > 0) {
                    addDiagnosticMessage(`❌ POST FAILED! Missing: ${missingEssential.join(', ')}`);
                    increaseStress(15, "POST failure!");
                    return;
                }
                isPoweredOn = true;
                document.getElementById('powerStatus').innerHTML = 'ON';
                document.getElementById('powerLed').className = 'led-green';
                document.getElementById('powerBtn').innerHTML = '<i class="fas fa-power-off"></i> POWER OFF';
                addDiagnosticMessage("🔌 System powered ON - Temperature monitoring active!");
                setTimeout(() => {
                    if (isPoweredOn && gameActive) {
                        document.getElementById('bootResult').innerHTML = `<div class="success-message" style="background:rgba(0,255,0,0.2); padding:0.5rem; border-radius:8px;"><i class="fas fa-check-circle"></i> BOOT SUCCESSFUL! BIOS POST Passed.</div>`;
                    }
                }, 1000);
            } else {
                isPoweredOn = false;
                document.getElementById('powerStatus').innerHTML = 'OFF';
                document.getElementById('powerLed').className = 'led-red';
                document.getElementById('powerBtn').innerHTML = '<i class="fas fa-power-off"></i> POWER ON';
                document.getElementById('bootResult').innerHTML = '';
                addDiagnosticMessage("🔌 System powered OFF");
            }
        }
        
        function runDiagnostic() {
            let issues = [];
            let totalSteps = 0;
            let completedSteps = 0;
            for (let comp in components) {
                const c = components[comp];
                totalSteps += c.steps.length;
                completedSteps += c.steps.filter(s => s.completed).length;
                if (c.damaged) issues.push(`💀 ${c.name}: DAMAGED - Needs replacement!`);
                else if (!c.installed && comp !== 'cooler') issues.push(`❌ ${c.name}: Not fully installed (${c.currentStep}/${c.steps.length} steps)`);
            }
            document.getElementById('diagnosticResult').innerHTML = `<div><strong>🔍 DIAGNOSTIC REPORT:</strong><br>Progress: ${completedSteps}/${totalSteps} steps<br>Lives: ${lives} | Stress: ${stressLevel}%<br>${issues.join('<br>')}</div>`;
        }
        
        function checkCompatibility() { document.getElementById('diagnosticResult').innerHTML = "<div>✅ All components compatible! (AMD Ryzen + B550 + DDR4)</div>"; }
        function testPSU() { document.getElementById('diagnosticResult').innerHTML = "<div>🔌 PSU Test: +12V:12.1V ✅ | +5V:5.02V ✅ | +3.3V:3.31V ✅</div>"; }
        function emergencyCool() { 
            temperature = Math.max(30, temperature - 15);
            addDiagnosticMessage("❄️ Emergency cooling activated! Temperature reduced.");
            document.getElementById('diagnosticResult').innerHTML = "<div>❄️ Emergency cooling applied! Temperatures normalized.</div>";
        }
        function useESDWriststrap() { 
            esdProtected = true;
            addDiagnosticMessage("🛡️ ESD wrist strap connected! Static damage risk reduced.");
            document.getElementById('diagnosticResult').innerHTML = "<div>🛡️ ESD protection active! Static damage prevented.</div>";
        }
        
        // Make functions global
        window.completeStep = completeStep;
        window.togglePower = togglePower;
        window.runDiagnostic = runDiagnostic;
        window.checkCompatibility = checkCompatibility;
        window.testPSU = testPSU;
        window.emergencyCool = emergencyCool;
        window.useESDWriststrap = useESDWriststrap;
        window.saveScoreAndReload = saveScoreAndReload;
        
        renderPCComponents();
        startTimer();
        startTemperatureMonitor();
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
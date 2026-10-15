<?php
// coc1_game.php - INSANE MODE PC Assembly Game with AUTO-SAVE
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$coc = 1;
$user_id = $_SESSION['user_id'];

// Get user's stats from game_scores table
$score_query = "SELECT MAX(score) as high_score, COUNT(*) as games_played 
                FROM game_scores 
                WHERE user_id = ? AND coc_number = ?";
$stmt = $conn->prepare($score_query);
$stmt->bind_param("ii", $user_id, $coc);
$stmt->execute();
$result = $stmt->get_result();
$user_data = $result->fetch_assoc();
$high_score = $user_data['high_score'] ?? 0;
$games_played = $user_data['games_played'] ?? 0;
$stmt->close();

// Get global brightness
$global_brightness = function_exists('getGlobalBrightness')
    ? getGlobalBrightness()
    : 100;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">

    <title>INSANE MODE: COC 1 PC Assembly | CSS TESDA Reviewer</title>

    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&family=Rajdhani:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <style>
        /* =========================================================
           RESET
        ========================================================= */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            user-select: none;
        }

        /* =========================================================
           GLOBAL BRIGHTNESS
        ========================================================= */
        html {
            --global-brightness: 100%;
            scrollbar-color: rgba(128, 128, 128, 0.35) transparent;
            scrollbar-width: thin;
        }

        body {
            font-family: 'Rajdhani', sans-serif;
            background: #0a0f1e;
            color: #e0e0ff;
            min-height: 100vh;
            overflow-x: hidden;

            /* GLOBAL BRIGHTNESS */
            filter: brightness(var(--global-brightness, 100%));
        }

        /* =========================================================
           BACKGROUND
        ========================================================= */
        .cyber-bg {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -2;
            background:
                radial-gradient(
                    circle at 20% 30%,
                    #0a0a1a,
                    #010105
                );
        }

        .grid-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image:
                linear-gradient(
                    rgba(255, 68, 68, 0.03) 1px,
                    transparent 1px
                ),
                linear-gradient(
                    90deg,
                    rgba(255, 68, 68, 0.03) 1px,
                    transparent 1px
                );
            background-size: 50px 50px;
            z-index: -1;
            animation: gridMove 20s linear infinite;
        }

        @keyframes gridMove {
            0% {
                transform: translate(0, 0);
            }

            100% {
                transform: translate(50px, 50px);
            }
        }

        /* =========================================================
           NAVBAR
        ========================================================= */
        .navbar {
            background: rgba(5, 10, 25, 0.95);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid rgba(255, 68, 68, 0.3);
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
            background: linear-gradient(
                135deg,
                #ff4444,
                #cc0000
            );
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
            font-size: 0.85rem;
            display: flex;
            align-items: center;
            gap: 5px;
            transition: all 0.3s;
        }

        .nav-links a:hover,
        .nav-links a.active {
            color: #ff4444;
            text-shadow: 0 0 10px rgba(255, 68, 68, 0.5);
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 1rem;
            background: rgba(255, 68, 68, 0.1);
            padding: 0.3rem 1rem;
            border-radius: 40px;
            font-size: 0.85rem;
        }

        /* =========================================================
           MAIN
        ========================================================= */
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
            font-size: 2rem;
            background: linear-gradient(
                135deg,
                #ff4444,
                #ff6666
            );
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 0.5rem;
        }

        .danger-badge {
            display: inline-block;
            padding: 5px 15px;
            background: rgba(255, 0, 0, 0.2);
            border: 1px solid #ff4444;
            border-radius: 40px;
            color: #ff6666;
            font-size: 0.7rem;
            margin-bottom: 1rem;
            animation: pulse 1s infinite;
        }

        @keyframes pulse {
            0%,
            100% {
                opacity: 1;
            }

            50% {
                opacity: 0.5;
            }
        }

        /* =========================================================
           INSTRUCTIONS
        ========================================================= */
        .instructions-panel {
            background: rgba(0, 0, 0, 0.5);
            border: 1px solid #ff4444;
            border-radius: 20px;
            padding: 1rem;
            margin-bottom: 1.5rem;
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 2rem;
        }

        .instruction-item {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 0.85rem;
            color: #ffaa66;
        }

        .instruction-icon {
            width: 40px;
            height: 40px;
            background: rgba(255, 68, 68, 0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.3rem;
            font-weight: bold;
        }

        .instruction-text {
            color: #cbd5e1;
            font-size: 1rem;
        }

        .instruction-highlight {
            color: #ff4444;
            font-weight: bold;
        }

        /* =========================================================
           STATS
        ========================================================= */
        .stats-bar {
            display: flex;
            justify-content: center;
            gap: 1.5rem;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
        }

        .stat-card {
            background: rgba(5, 10, 25, 0.7);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 68, 68, 0.3);
            border-radius: 20px;
            padding: 0.8rem 1.5rem;
            text-align: center;
        }

        .stat-value {
            font-family: 'Orbitron', sans-serif;
            font-size: 1.8rem;
            font-weight: 700;
            color: #ff4444;
        }

        .stat-label {
            font-size: 0.7rem;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .lives-container {
            display: flex;
            gap: 0.5rem;
            justify-content: center;
            margin-top: 0.3rem;
        }

        .heart {
            color: #ff4444;
            font-size: 1.2rem;
        }

        .heart.empty {
            color: #333;
        }

        /* =========================================================
           GAME AREA
        ========================================================= */
        .game-area {
            display: grid;
            grid-template-columns: 1fr 380px;
            gap: 2rem;
        }

        .slots-area {
            background:
                linear-gradient(
                    145deg,
                    rgba(10, 15, 35, 0.6),
                    rgba(0, 0, 0, 0.4)
                );
            border-radius: 30px;
            padding: 2rem;
            border: 1px solid rgba(255, 68, 68, 0.3);
        }

        .slots-title {
            font-family: 'Orbitron', sans-serif;
            font-size: 1rem;
            color: #ff6666;
            margin-bottom: 1.5rem;
            text-align: center;
        }

        .slots-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
        }

        .slot-card {
            background:
                linear-gradient(
                    145deg,
                    #1a1a2e,
                    #0f0f23
                );
            border: 2px solid rgba(255, 68, 68, 0.3);
            border-radius: 20px;
            padding: 20px 12px;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s;
        }

        .slot-card:hover {
            transform: scale(1.02);
            border-color: #ff4444;
        }

        .slot-card.installed {
            border-color: #00ff9d;
            background: rgba(0, 255, 157, 0.05);
        }

        .slot-card.mistake {
            animation: shake 0.3s ease;
            border-color: #ff0000;
            background: rgba(255, 0, 0, 0.2);
        }

        @keyframes shake {
            0%,
            100% {
                transform: translateX(0);
            }

            25% {
                transform: translateX(-5px);
            }

            75% {
                transform: translateX(5px);
            }
        }

        .slot-icon {
            font-size: 3.5rem;
            margin-bottom: 1rem;
        }

        .slot-name {
            font-size: 0.9rem;
            font-weight: bold;
            margin-bottom: 0.3rem;
        }

        .slot-status {
            font-size: 0.7rem;
            margin-top: 0.5rem;
        }

        .slot-status.empty {
            color: #ff6666;
        }

        .slot-status.installed {
            color: #00ff9d;
        }

        /* =========================================================
           COMPONENTS
        ========================================================= */
        .components-area {
            background:
                linear-gradient(
                    145deg,
                    rgba(10, 15, 35, 0.6),
                    rgba(0, 0, 0, 0.4)
                );
            border-radius: 30px;
            padding: 1.5rem;
            border: 1px solid rgba(255, 68, 68, 0.3);
        }

        .components-title {
            font-family: 'Orbitron', sans-serif;
            font-size: 1rem;
            color: #ff6666;
            margin-bottom: 1.5rem;
            text-align: center;
        }

        .components-list {
            display: flex;
            flex-direction: column;
            gap: 15px;
            max-height: 600px;
            overflow-y: auto;
        }

        .comp-card {
            background:
                linear-gradient(
                    135deg,
                    #1a1a2e,
                    #0f0f23
                );
            border: 2px solid rgba(255, 68, 68, 0.3);
            border-radius: 16px;
            padding: 15px;
            display: flex;
            align-items: center;
            gap: 20px;
            cursor: pointer;
            transition: all 0.2s;
        }

        .comp-card:hover {
            transform: translateX(8px);
            border-color: #ff4444;
            background: rgba(255, 68, 68, 0.1);
        }

        .comp-card.selected {
            border-color: #ffd700;
            background: rgba(255, 215, 0, 0.1);
            box-shadow: 0 0 20px rgba(255, 215, 0, 0.3);
        }

        .comp-img {
            width: 100px;
            height: 100px;
            background: rgba(0, 0, 0, 0.3);
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            flex-shrink: 0;
        }

        .comp-img img {
            width: 90px;
            height: 90px;
            object-fit: contain;
        }

        .comp-info {
            flex: 1;
        }

        .comp-name {
            font-size: 1.1rem;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .comp-points {
            font-size: 0.8rem;
            color: #ffd700;
        }

        /* =========================================================
           PROGRESS
        ========================================================= */
        .progress-area {
            margin-top: 2rem;
        }

        .progress-bar {
            width: 100%;
            height: 12px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            overflow: hidden;
        }

        .progress-fill {
            height: 100%;
            background:
                linear-gradient(
                    90deg,
                    #ff4444,
                    #ff6666
                );
            border-radius: 10px;
            transition: width 0.3s;
        }

        .penalty-area {
            margin-top: 1.5rem;
            background: rgba(255, 0, 0, 0.1);
            border-radius: 16px;
            padding: 1rem;
            text-align: center;
            border-left: 4px solid #ff0000;
            font-size: 0.9rem;
        }

        /* =========================================================
           MODAL
        ========================================================= */
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
            backdrop-filter: blur(15px);
        }

        .modal.active {
            display: flex;
        }

        .modal-content {
            background:
                linear-gradient(
                    135deg,
                    #0f0f23,
                    #050510
                );
            border: 2px solid #ff4444;
            border-radius: 30px;
            max-width: 450px;
            width: 90%;
            padding: 2rem;
            text-align: center;
        }

        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 40px;
            font-weight: bold;
            cursor: pointer;
            font-family: 'Orbitron', sans-serif;
            margin: 0.3rem;
            font-size: 0.9rem;
        }

        .btn-primary {
            background:
                linear-gradient(
                    135deg,
                    #ff4444,
                    #cc0000
                );
            color: white;
        }

        .btn-outline {
            background: transparent;
            border: 2px solid #ff4444;
            color: #ff4444;
        }

        /* =========================================================
           SCROLLBAR - NEUTRAL GRAY
        ========================================================= */
        ::-webkit-scrollbar {
            width: 8px;
        }

        ::-webkit-scrollbar-track {
            background: transparent;
            border-radius: 10px;
        }

        ::-webkit-scrollbar-thumb {
            background: rgba(128, 128, 128, 0.35);
            border-radius: 10px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: rgba(128, 128, 128, 0.55);
        }

        /* =========================================================
           THEME TOGGLE
        ========================================================= */
        .theme-toggle {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 12px;
            border: 1px solid rgba(255, 68, 68, 0.35);
            border-radius: 999px;
            background: rgba(255, 68, 68, 0.08);
            color: #ff6666;
            font-family: 'Rajdhani', sans-serif;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            white-space: nowrap;
        }

        .theme-toggle:hover {
            background: #ff4444;
            color: #fff;
            transform: translateY(-1px);
            box-shadow: 0 5px 15px rgba(255, 68, 68, 0.25);
        }

        /* =========================================================
           LIGHT MODE
        ========================================================= */
        body.light-mode {
            background: #f4f7fb;
            color: #172033;
        }

        body.light-mode .cyber-bg {
            background:
                radial-gradient(
                    circle at 20% 30%,
                    #fff,
                    #eaf1f7
                );
        }

        body.light-mode .grid-overlay {
            background-image:
                linear-gradient(
                    rgba(255, 68, 68, 0.06) 1px,
                    transparent 1px
                ),
                linear-gradient(
                    90deg,
                    rgba(255, 68, 68, 0.06) 1px,
                    transparent 1px
                );
        }

        body.light-mode .navbar {
            background: rgba(255, 255, 255, 0.94);
            border-bottom-color: rgba(255, 68, 68, 0.25);
        }

        body.light-mode .nav-links a {
            color: #526174;
        }

        body.light-mode .nav-links a:hover,
        body.light-mode .nav-links a.active {
            color: #cc0000;
            text-shadow: none;
        }

        body.light-mode .user-info {
            background: rgba(255, 68, 68, 0.08);
            color: #cc0000;
        }

        body.light-mode .game-header p {
            color: #526174;
        }

        body.light-mode .instructions-panel {
            background: rgba(255, 255, 255, 0.88);
            border-color: rgba(255, 68, 68, 0.35);
        }

        body.light-mode .instruction-item,
        body.light-mode .instruction-text {
            color: #526174;
        }

        body.light-mode .instruction-icon {
            background: rgba(255, 68, 68, 0.10);
        }

        body.light-mode .stat-card {
            background: rgba(255, 255, 255, 0.88);
            border-color: rgba(255, 68, 68, 0.28);
        }

        body.light-mode .stat-label {
            color: #526174;
        }

        body.light-mode .slots-area,
        body.light-mode .components-area {
            background:
                linear-gradient(
                    145deg,
                    rgba(255, 255, 255, 0.92),
                    rgba(238, 243, 248, 0.92)
                );
            border-color: rgba(255, 68, 68, 0.28);
        }

        body.light-mode .slot-card {
            background:
                linear-gradient(
                    145deg,
                    #fff,
                    #e9eef3
                );
            border-color: rgba(255, 68, 68, 0.28);
            color: #172033;
        }

        body.light-mode .slot-card:hover {
            border-color: #ff4444;
        }

        body.light-mode .comp-card {
            background:
                linear-gradient(
                    135deg,
                    #fff,
                    #e9eef3
                );
            border-color: rgba(255, 68, 68, 0.28);
            color: #172033;
        }

        body.light-mode .comp-card:hover {
            background: rgba(255, 68, 68, 0.08);
            border-color: #ff4444;
        }

        body.light-mode .comp-img {
            background: rgba(0, 0, 0, 0.08);
        }

        body.light-mode .progress-bar {
            background: rgba(20, 35, 55, 0.10);
        }

        body.light-mode .penalty-area {
            background: rgba(255, 0, 0, 0.07);
            color: #172033;
        }

        body.light-mode .modal {
            background: rgba(20, 30, 45, 0.72);
        }

        body.light-mode .modal-content {
            background:
                linear-gradient(
                    135deg,
                    #fff,
                    #eef3f8
                );
            color: #172033;
        }

        body.light-mode .theme-toggle {
            border-color: rgba(180, 0, 0, 0.35);
            background: rgba(255, 68, 68, 0.08);
            color: #cc0000;
        }

        body.light-mode .theme-toggle:hover {
            background: #cc0000;
            color: #fff;
        }

        /* =========================================================
           RESPONSIVE
        ========================================================= */
        @media (max-width: 1000px) {
            .game-area {
                grid-template-columns: 1fr;
            }

            .slots-grid {
                grid-template-columns: repeat(3, 1fr);
            }
        }

        @media (max-width: 600px) {
            .container {
                padding: 1rem;
            }

            .comp-img {
                width: 70px;
                height: 70px;
            }

            .comp-img img {
                width: 60px;
                height: 60px;
            }

            .slot-icon {
                font-size: 2.5rem;
            }

            .instructions-panel {
                flex-direction: column;
                gap: 0.5rem;
            }

            .slots-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .navbar {
                padding: 0.8rem 1rem;
            }

            .nav-links {
                gap: 0.6rem;
            }
        }
    </style>
</head>

<body>

    <div class="cyber-bg"></div>
    <div class="grid-overlay"></div>

    <!-- =========================================================
         NAVBAR
    ========================================================= -->
    <nav class="navbar">
        <div class="nav-container">

            <a href="dashboard.php" class="logo">
                💀 INSANE VR PC ASSEMBLY 💀
            </a>

            <div class="nav-links">

                <a href="dashboard.php">
                    <i class="fas fa-home"></i> Home
                </a>

                <a href="coc1_review.php">
                    <i class="fas fa-microchip"></i> COC 1
                </a>

                <a href="coc2_review.php">
                    <i class="fas fa-network-wired"></i> COC 2
                </a>

                <a href="coc3_review.php">
                    <i class="fas fa-server"></i> COC 3
                </a>

                <a href="coc4_review.php">
                    <i class="fas fa-tools"></i> COC 4
                </a>

                <a href="coc1_game.php" class="active">
                    <i class="fas fa-skull-crossbones"></i> Insane Mode
                </a>

                <div class="user-info">

                    <i class="fas fa-user-astronaut"></i>

                    <span>
                        <?php
                        echo htmlspecialchars(
                            $_SESSION['full_name'] ?? $_SESSION['username']
                        );
                        ?>
                    </span>

                    <button
                        type="button"
                        class="theme-toggle"
                        id="themeToggle"
                        aria-label="Toggle light and dark mode"
                        title="Toggle light and dark mode"
                    >
                        <i class="fas fa-moon" id="themeIcon"></i>
                        <span id="themeText">Dark Mode</span>
                    </button>

                    <a
                        href="logout.php"
                        style="color: #ef4444;"
                    >
                        <i class="fas fa-sign-out-alt"></i>
                    </a>

                </div>

            </div>
        </div>
    </nav>

    <!-- =========================================================
         MAIN CONTAINER
    ========================================================= -->
    <div class="container">

        <div class="game-header">

            <div class="danger-badge">
                <i class="fas fa-skull-crossbones"></i>
                INSANE MODE - MEMORY TEST
                <i class="fas fa-skull-crossbones"></i>
            </div>

            <h1>🔧 VR PC Assembly 🔧</h1>

            <p>
                ⚠️ Components are RANDOMIZED!
                Match each component to the CORRECT slot! ⚠️
            </p>

        </div>

        <!-- =====================================================
             INSTRUCTIONS
        ===================================================== -->
        <div class="instructions-panel">

            <div class="instruction-item">
                <div class="instruction-icon">①</div>
                <div class="instruction-text">
                    <span class="instruction-highlight">CLICK</span>
                    a component from the RIGHT panel
                </div>
            </div>

            <div class="instruction-item">
                <div class="instruction-icon">➡️</div>
                <div class="instruction-text">
                    <span class="instruction-highlight">DRAG / CLICK</span>
                    on the matching slot on the LEFT
                </div>
            </div>

            <div class="instruction-item">
                <div class="instruction-icon">✅</div>
                <div class="instruction-text">
                    <span class="instruction-highlight">CORRECT</span>
                    = +Points, component installed
                </div>
            </div>

            <div class="instruction-item">
                <div class="instruction-icon">❌</div>
                <div class="instruction-text">
                    <span class="instruction-highlight">WRONG</span>
                    = -50pts, -1 Life
                </div>
            </div>

            <div class="instruction-item">
                <div class="instruction-icon">🏆</div>
                <div class="instruction-text">
                    Install
                    <span class="instruction-highlight">ALL 8 components</span>
                    to WIN!
                </div>
            </div>

        </div>

        <!-- =====================================================
             STATS
        ===================================================== -->
        <div class="stats-bar">

            <div class="stat-card">
                <div class="stat-value" id="scoreDisplay">0</div>
                <div class="stat-label">SCORE</div>
            </div>

            <div class="stat-card">
                <div class="stat-value" id="highScoreDisplay">
                    <?php echo (int)$high_score; ?>
                </div>
                <div class="stat-label">BEST SCORE</div>
            </div>

            <div class="stat-card">
                <div class="stat-value" id="installedCount">0</div>
                <div class="stat-label">INSTALLED</div>
            </div>

            <div class="stat-card">
                <div class="stat-value" id="penaltyCount">0</div>
                <div class="stat-label">PENALTIES</div>
            </div>

            <div class="stat-card">
                <div class="lives-container" id="livesContainer"></div>
                <div class="stat-label">LIVES</div>
            </div>

            <div class="stat-card">
                <div class="stat-value" id="gamesPlayed">
                    <?php echo (int)$games_played; ?>
                </div>
                <div class="stat-label">GAMES PLAYED</div>
            </div>

        </div>

        <!-- =====================================================
             GAME AREA
        ===================================================== -->
        <div class="game-area">

            <!-- SLOTS -->
            <div class="slots-area">

                <div class="slots-title">
                    <i class="fas fa-cube"></i>
                    PC COMPONENT SLOTS
                    <i class="fas fa-cube"></i>
                </div>

                <div
                    class="slots-grid"
                    id="slotsGrid"
                ></div>

            </div>

            <!-- COMPONENTS -->
            <div class="components-area">

                <div class="components-title">
                    <i class="fas fa-box-open"></i>
                    COMPONENTS (RANDOMIZED)
                    <i class="fas fa-box-open"></i>
                </div>

                <div
                    class="components-list"
                    id="componentsList"
                ></div>

            </div>

        </div>

        <!-- =====================================================
             PROGRESS
        ===================================================== -->
        <div class="progress-area">

            <div class="progress-bar">
                <div
                    class="progress-fill"
                    id="progressFill"
                ></div>
            </div>

        </div>

        <!-- =====================================================
             PENALTY MESSAGE
        ===================================================== -->
        <div
            class="penalty-area"
            id="penaltyMessage"
        >
            <i class="fas fa-info-circle"></i>
            Match each component to its correct slot.
            Wrong = -50pts + damage!
        </div>

    </div>

    <!-- =========================================================
         GAME MODAL
    ========================================================= -->
    <div
        id="gameModal"
        class="modal"
    >

        <div class="modal-content">

            <i
                class="fas fa-trophy"
                style="font-size: 4rem; color: #ffd700;"
            ></i>

            <h2 id="modalTitle">
                Game Complete!
            </h2>

            <p id="modalMessage">
                You built the PC!
            </p>

            <div
                class="stat-value"
                id="finalScoreDisplay"
                style="font-size: 2rem;"
            >
                0
            </div>

            <div
                id="newRecordMsg"
                style="color: #00ff9d; display: none;"
            >
                🏆 NEW RECORD! 🏆
            </div>

            <div style="margin-top: 1.5rem;">

                <button
                    class="btn btn-primary"
                    onclick="restartGame()"
                >
                    <i class="fas fa-redo-alt"></i>
                    Play Again
                </button>

                <button
                    class="btn btn-outline"
                    onclick="location.href='coc1_review.php'"
                >
                    <i class="fas fa-book"></i>
                    Review
                </button>

            </div>

        </div>

    </div>

    <!-- =========================================================
         TOAST
    ========================================================= -->
    <div
        id="toast"
        style="
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: #ff4444;
            color: white;
            padding: 12px 24px;
            border-radius: 50px;
            display: none;
            z-index: 200;
            font-size: 1rem;
            font-weight: bold;
        "
    ></div>

    <!-- =========================================================
         GAME JAVASCRIPT
    ========================================================= -->
    <script>
        // ========================================================
        // HARDWARE COMPONENTS
        // ========================================================
        const allComponents = [
            {
                id: "cpu",
                name: "CPU",
                points: 150,
                slotMatch: "cpu_slot",
                img: "https://i.pinimg.com/736x/fc/a2/71/fca2710fa4559012738b2ce5b12b9c55.jpg"
            },
            {
                id: "motherboard",
                name: "Motherboard",
                points: 100,
                slotMatch: "mb_slot",
                img: "https://i.pinimg.com/1200x/0f/3f/e5/0f3fe51ffe80fd881b86b6f265a09c21.jpg"
            },
            {
                id: "ram",
                name: "RAM",
                points: 100,
                slotMatch: "ram_slot",
                img: "https://i.pinimg.com/1200x/a5/68/e6/a568e6b3facfe86cd98544665ed60bff.jpg"
            },
            {
                id: "ssd",
                name: "SSD",
                points: 100,
                slotMatch: "ssd_slot",
                img: "https://i.pinimg.com/1200x/67/36/50/6736504cc7bbd24956269f39cb46b8ee.jpg"
            },
            {
                id: "psu",
                name: "Power Supply",
                points: 150,
                slotMatch: "psu_slot",
                img: "https://i.pinimg.com/736x/57/86/84/578684a03801a9571a17e09fe7be064c.jpg"
            },
            {
                id: "gpu",
                name: "GPU",
                points: 150,
                slotMatch: "gpu_slot",
                img: "https://i.pinimg.com/1200x/59/92/3e/59923e6db3f6940dd2f6588515789e09.jpg"
            },
            {
                id: "case",
                name: "PC Case",
                points: 100,
                slotMatch: "case_slot",
                img: "https://i.pinimg.com/1200x/ad/33/f8/ad33f886760cbfae6987d6b20eae51ac.jpg"
            },
            {
                id: "cooler",
                name: "CPU Cooler",
                points: 100,
                slotMatch: "cooler_slot",
                img: "https://i.pinimg.com/736x/a2/92/70/a292707edf7da10daf7d28ecdd99f992.jpg"
            }
        ];

        const slots = [
            {
                id: "cpu_slot",
                name: "CPU Socket",
                icon: "fa-microchip"
            },
            {
                id: "mb_slot",
                name: "Motherboard",
                icon: "fa-border-all"
            },
            {
                id: "ram_slot",
                name: "RAM Slot",
                icon: "fa-memory"
            },
            {
                id: "ssd_slot",
                name: "M.2 SSD Slot",
                icon: "fa-sd-card"
            },
            {
                id: "psu_slot",
                name: "PSU Bay",
                icon: "fa-plug"
            },
            {
                id: "gpu_slot",
                name: "PCIe Slot",
                icon: "fa-tv"
            },
            {
                id: "case_slot",
                name: "Case Mount",
                icon: "fa-desktop"
            },
            {
                id: "cooler_slot",
                name: "CPU Fan Header",
                icon: "fa-fan"
            }
        ];

        // ========================================================
        // SHUFFLE
        // ========================================================
        function shuffleArray(arr) {
            for (let i = arr.length - 1; i > 0; i--) {
                const j = Math.floor(Math.random() * (i + 1));

                [arr[i], arr[j]] = [arr[j], arr[i]];
            }

            return arr;
        }

        // ========================================================
        // GAME VARIABLES
        // ========================================================
        let availableComponents = [];
        let installedMap = {};
        let lives = 5;
        let score = 0;
        let penalties = 0;
        let selectedComponent = null;
        let gameActive = true;
        let gameEnded = false;

        let highScore = <?php echo (int)$high_score; ?>;
        let gamesPlayed = <?php echo (int)$games_played; ?>;

        // ========================================================
        // AUTO SAVE
        // ========================================================
        function autoSaveScore() {

            if (gameEnded === false && score <= 0) {
                // Allow score 0 only on actual game ending.
                // Prevent unnecessary saves during normal gameplay.
            }

            console.log("Auto-saving score:", score);

            const xhr = new XMLHttpRequest();

            xhr.open(
                "POST",
                "save_coc1_score.php",
                true
            );

            xhr.setRequestHeader(
                "Content-Type",
                "application/x-www-form-urlencoded"
            );

            xhr.onreadystatechange = function () {

                if (xhr.readyState !== 4) {
                    return;
                }

                console.log(
                    "Auto-save response:",
                    xhr.responseText
                );

                if (xhr.status === 200) {

                    try {

                        const data =
                            JSON.parse(xhr.responseText);

                        if (data.success) {

                            if (
                                data.high_score !== undefined
                            ) {
                                highScore =
                                    Number(data.high_score);

                                document.getElementById(
                                    "highScoreDisplay"
                                ).textContent =
                                    data.high_score;
                            }

                            if (
                                data.games_played !== undefined
                            ) {
                                gamesPlayed =
                                    Number(data.games_played);

                                document.getElementById(
                                    "gamesPlayed"
                                ).textContent =
                                    data.games_played;
                            }

                        }

                    } catch (error) {

                        console.error(
                            "Auto-save parse error:",
                            error
                        );

                    }

                }

            };

            xhr.send(
                "score=" +
                encodeURIComponent(score)
            );
        }

        // ========================================================
        // INIT GAME
        // ========================================================
        function initGame() {

            availableComponents =
                shuffleArray([...allComponents]);

            installedMap = {};

            lives = 5;
            score = 0;
            penalties = 0;
            selectedComponent = null;

            gameActive = true;
            gameEnded = false;

            updateUI();
            renderSlotsGrid();
            renderComponentsList();
        }

        // ========================================================
        // TOAST
        // ========================================================
        function showToast(
            msg,
            isError = true
        ) {

            const toast =
                document.getElementById("toast");

            toast.textContent = msg;

            toast.style.background =
                isError
                    ? "#ff4444"
                    : "#00ff9d";

            toast.style.color =
                isError
                    ? "white"
                    : "#050812";

            toast.style.display = "block";

            setTimeout(
                function () {
                    toast.style.display = "none";
                },
                2000
            );
        }

        // ========================================================
        // UPDATE UI
        // ========================================================
        function updateUI() {

            document.getElementById(
                "scoreDisplay"
            ).textContent = score;

            document.getElementById(
                "installedCount"
            ).textContent =
                Object.keys(installedMap).length;

            document.getElementById(
                "penaltyCount"
            ).textContent = penalties;

            const percent =
                (
                    Object.keys(installedMap).length /
                    8
                ) * 100;

            document.getElementById(
                "progressFill"
            ).style.width =
                percent + "%";

            const livesContainer =
                document.getElementById(
                    "livesContainer"
                );

            livesContainer.innerHTML = "";

            for (let i = 0; i < lives; i++) {

                livesContainer.innerHTML +=
                    '<i class="fas fa-heart heart"></i>';
            }

            for (let i = lives; i < 5; i++) {

                livesContainer.innerHTML +=
                    '<i class="far fa-heart heart empty"></i>';
            }
        }

        // ========================================================
        // PENALTY
        // ========================================================
        function applyPenalty(reason) {

            if (!gameActive) {
                return;
            }

            penalties++;

            score =
                Math.max(
                    0,
                    score - 50
                );

            lives--;

            updateUI();

            showToast(
                `💀 PENALTY! ${reason} -50pts, 1 life lost!`,
                true
            );

            if (lives <= 0) {

                gameActive = false;
                gameEnded = true;

                autoSaveScore();

                document.getElementById(
                    "modalTitle"
                ).innerHTML =
                    "💀 GAME OVER 💀";

                document.getElementById(
                    "modalMessage"
                ).innerHTML =
                    "Your score has been auto-saved!";

                document.getElementById(
                    "finalScoreDisplay"
                ).textContent = score;

                document.getElementById(
                    "newRecordMsg"
                ).style.display = "none";

                document.getElementById(
                    "gameModal"
                ).classList.add("active");
            }
        }

        // ========================================================
        // SELECT COMPONENT
        // ========================================================
        function selectComponent(comp) {

            if (!gameActive) {
                return;
            }

            selectedComponent = comp;

            document
                .querySelectorAll(".comp-card")
                .forEach(function (card) {
                    card.classList.remove("selected");
                });

            const compCards =
                document.querySelectorAll(
                    ".comp-card"
                );

            for (const card of compCards) {

                if (
                    card.getAttribute("data-id") ===
                    comp.id
                ) {

                    card.classList.add("selected");
                }
            }

            showToast(
                `🔧 Selected: ${comp.name} - Find its correct slot!`,
                false
            );
        }

        // ========================================================
        // TRY PLACE COMPONENT
        // ========================================================
        function tryPlaceComponent(slotId) {

            if (!gameActive) {
                return;
            }

            if (!selectedComponent) {

                showToast(
                    "⚠️ First click a component from the right panel!",
                    true
                );

                return;
            }

            if (installedMap[slotId]) {

                applyPenalty(
                    `Slot already has ${installedMap[slotId].name}!`
                );

                selectedComponent = null;

                renderComponentsList();

                return;
            }

            if (
                selectedComponent.slotMatch !==
                slotId
            ) {

                const slotName =
                    slots.find(
                        s => s.id === slotId
                    )?.name || slotId;

                applyPenalty(
                    `${selectedComponent.name} does NOT belong in ${slotName}!`
                );

                selectedComponent = null;

                renderComponentsList();
                renderSlotsGrid();

                return;
            }

            // CORRECT PLACEMENT
            const component =
                selectedComponent;

            const index =
                availableComponents.findIndex(
                    c => c.id === component.id
                );

            if (index !== -1) {
                availableComponents.splice(
                    index,
                    1
                );
            }

            installedMap[slotId] =
                component;

            score += component.points;

            updateUI();

            selectedComponent = null;

            renderSlotsGrid();
            renderComponentsList();

            showToast(
                `✅ CORRECT! ${component.name} installed! +${component.points} points!`,
                false
            );

            // WIN
            if (
                Object.keys(installedMap).length === 8
            ) {

                gameActive = false;
                gameEnded = true;

                autoSaveScore();

                const finalScore = score;

                document.getElementById(
                    "finalScoreDisplay"
                ).textContent =
                    finalScore;

                if (finalScore > highScore) {

                    document.getElementById(
                        "newRecordMsg"
                    ).style.display =
                        "block";
                } else {

                    document.getElementById(
                        "newRecordMsg"
                    ).style.display =
                        "none";
                }

                document.getElementById(
                    "modalTitle"
                ).innerHTML =
                    "🎉 PC ASSEMBLED! 🎉";

                document.getElementById(
                    "modalMessage"
                ).innerHTML =
                    `Your score ${finalScore} has been auto-saved!`;

                document.getElementById(
                    "gameModal"
                ).classList.add("active");
            }
        }

        // ========================================================
        // RENDER SLOTS
        // ========================================================
        function renderSlotsGrid() {

            const container =
                document.getElementById(
                    "slotsGrid"
                );

            container.innerHTML = "";

            slots.forEach(function (slot) {

                const installed =
                    installedMap[slot.id];

                const div =
                    document.createElement("div");

                div.className =
                    `slot-card ${
                        installed
                            ? "installed"
                            : ""
                    }`;

                div.onclick =
                    function () {
                        tryPlaceComponent(
                            slot.id
                        );
                    };

                div.innerHTML = `
                    <div class="slot-icon">
                        <i class="fas ${slot.icon}"></i>
                    </div>

                    <div class="slot-name">
                        ${slot.name}
                    </div>

                    <div class="slot-status ${
                        installed
                            ? "installed"
                            : "empty"
                    }">

                        ${
                            installed
                                ? `
                                    <i class="fas fa-check-circle"></i>
                                    ${installed.name}
                                  `
                                : `
                                    <i class="fas fa-circle"></i>
                                    ???
                                  `
                        }

                    </div>
                `;

                container.appendChild(div);
            });
        }

        // ========================================================
        // RENDER COMPONENTS
        // ========================================================
        function renderComponentsList() {

            const container =
                document.getElementById(
                    "componentsList"
                );

            container.innerHTML = "";

            availableComponents.forEach(
                function (comp) {

                    const div =
                        document.createElement(
                            "div"
                        );

                    div.className =
                        `comp-card ${
                            selectedComponent &&
                            selectedComponent.id === comp.id
                                ? "selected"
                                : ""
                        }`;

                    div.setAttribute(
                        "data-id",
                        comp.id
                    );

                    div.onclick =
                        function () {
                            selectComponent(
                                comp
                            );
                        };

                    div.innerHTML = `
                        <div class="comp-img">
                            <img
                                src="${comp.img}"
                                alt="${comp.name}"
                                onerror="this.style.display='none';"
                            >
                        </div>

                        <div class="comp-info">

                            <div class="comp-name">
                                ${comp.name}
                            </div>

                            <div class="comp-points">
                                <i class="fas fa-star"></i>
                                ${comp.points} pts
                            </div>

                        </div>

                        <i
                            class="fas fa-arrow-right"
                            style="
                                color:#ff6666;
                                font-size:1.2rem;
                            "
                        ></i>
                    `;

                    container.appendChild(div);
                }
            );
        }

        // ========================================================
        // RESTART
        // ========================================================
        function restartGame() {

            initGame();

            document
                .getElementById("gameModal")
                .classList.remove("active");

            showToast(
                "🔄 Game restarted! Components are SHUFFLED - NO CLUES! 🔄",
                false
            );
        }

        // ========================================================
        // GLOBAL FUNCTIONS
        // ========================================================
        window.restartGame =
            restartGame;

        window.tryPlaceComponent =
            tryPlaceComponent;

        window.selectComponent =
            selectComponent;

        // ========================================================
        // START GAME
        // ========================================================
        initGame();
    </script>

    <!-- =========================================================
         BRIGHTNESS WIDGET
    ========================================================= -->
    <?php
        include __DIR__ . '/includes/brightness_widget.php';
    ?>

    <!-- =========================================================
         THEME + BRIGHTNESS BRIDGE
    ========================================================= -->
    <script>
        (function () {

            /* =====================================================
               THEME
            ===================================================== */

            const themeToggle =
                document.getElementById(
                    "themeToggle"
                );

            const themeIcon =
                document.getElementById(
                    "themeIcon"
                );

            const themeText =
                document.getElementById(
                    "themeText"
                );

            function applyTheme(theme) {

                const isLight =
                    theme === "light";

                document.body.classList.toggle(
                    "light-mode",
                    isLight
                );

                if (themeIcon) {

                    themeIcon.className =
                        isLight
                            ? "fas fa-sun"
                            : "fas fa-moon";
                }

                if (themeText) {

                    themeText.textContent =
                        isLight
                            ? "Light Mode"
                            : "Dark Mode";
                }
            }

            const savedTheme =
                localStorage.getItem(
                    "css_tesda_theme"
                ) || "dark";

            applyTheme(savedTheme);

            if (themeToggle) {

                themeToggle.addEventListener(
                    "click",
                    function () {

                        const nextTheme =
                            document.body.classList.contains(
                                "light-mode"
                            )
                                ? "dark"
                                : "light";

                        localStorage.setItem(
                            "css_tesda_theme",
                            nextTheme
                        );

                        applyTheme(
                            nextTheme
                        );
                    }
                );
            }

            /* =====================================================
               BRIGHTNESS
            ===================================================== */

            function applyBrightness(value) {

                let brightness =
                    parseFloat(value);

                if (!isFinite(brightness)) {
                    brightness = 100;
                }

                /*
                 * Supports:
                 * 0.1 - 2.0
                 * 10 - 200
                 */
                if (
                    brightness > 0 &&
                    brightness <= 2
                ) {
                    brightness *= 100;
                }

                brightness =
                    Math.max(
                        10,
                        Math.min(
                            200,
                            brightness
                        )
                    );

                document.documentElement.style.setProperty(
                    "--global-brightness",
                    brightness + "%"
                );
            }

            function loadBrightness() {

                const saved =
                    localStorage.getItem(
                        "css_tesda_brightness"
                    );

                if (saved !== null) {
                    applyBrightness(saved);
                } else {
                    applyBrightness(
                        <?php echo (float)$global_brightness; ?>
                    );
                }
            }

            loadBrightness();

            /*
             * Brightness widget event
             */
            window.addEventListener(
                "brightnessChanged",
                function (event) {

                    if (
                        event.detail !== undefined
                    ) {
                        applyBrightness(
                            event.detail
                        );
                    } else {
                        loadBrightness();
                    }
                }
            );

            /*
             * Changes from another tab/page
             */
            window.addEventListener(
                "storage",
                function (event) {

                    if (
                        event.key ===
                        "css_tesda_brightness"
                    ) {

                        applyBrightness(
                            event.newValue
                        );
                    }

                    if (
                        event.key ===
                        "css_tesda_theme"
                    ) {

                        applyTheme(
                            event.newValue ||
                            "dark"
                        );
                    }
                }
            );

            /*
             * Find brightness sliders created by
             * brightness_widget.php
             */
            function bindBrightnessInputs() {

                const inputs =
                    document.querySelectorAll(
                        'input[type="range"]'
                    );

                inputs.forEach(
                    function (input) {

                        if (
                            input.dataset
                                .brightnessBound
                        ) {
                            return;
                        }

                        const name =
                            (
                                input.id +
                                " " +
                                input.name +
                                " " +
                                input.className
                            ).toLowerCase();

                        if (
                            !name.includes(
                                "bright"
                            )
                        ) {
                            return;
                        }

                        input.dataset
                            .brightnessBound =
                            "true";

                        input.addEventListener(
                            "input",
                            function () {

                                applyBrightness(
                                    input.value
                                );
                            }
                        );

                        input.addEventListener(
                            "change",
                            function () {

                                applyBrightness(
                                    input.value
                                );
                            }
                        );
                    }
                );
            }

            bindBrightnessInputs();

            /*
             * Widget may load dynamically
             */
            const observer =
                new MutationObserver(
                    function () {

                        bindBrightnessInputs();
                    }
                );

            observer.observe(
                document.body,
                {
                    childList: true,
                    subtree: true
                }
            );

        })();
    </script>

</body>
</html>
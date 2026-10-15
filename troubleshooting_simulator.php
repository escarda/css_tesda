<?php
// troubleshooting_simulator.php - Interactive Troubleshooting Simulator
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$page_title = "Troubleshooting Simulator";

// Get user's previous scores
$score_query = $conn->prepare("
    SELECT score, problems_fixed, attempts
    FROM troubleshooting_scores
    WHERE user_id = ?
    ORDER BY played_at DESC
    LIMIT 1
");

$score_query->bind_param("i", $user_id);
$score_query->execute();
$previous_score = $score_query->get_result()->fetch_assoc();
$score_query->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">

    <title><?php echo $page_title; ?> | CSS TESDA Reviewer</title>

    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&family=Rajdhani:wght@300;400;600&display=swap" rel="stylesheet">

    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

<style>

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    user-select: none;
}

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
    background: radial-gradient(
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

/* =========================
   NAVBAR
   ========================= */

.navbar {
    background: rgba(10, 20, 40, 0.9);
    backdrop-filter: blur(10px);
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

.nav-links a:hover {
    color: #ff4444;
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

/* =========================
   THEME BUTTON
   ========================= */

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
    color: #ffffff;

    transform: translateY(-1px);

    box-shadow:
        0 5px 15px rgba(255, 68, 68, 0.25);
}

/* =========================
   MAIN CONTAINER
   ========================= */

.container {
    max-width: 1400px;
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
}

/* =========================
   GAME
   ========================= */

.game-container {
    display: flex;
    gap: 2rem;
    flex-wrap: wrap;
}

.computer-panel {
    flex: 2;
    min-width: 500px;
}

.computer-screen {
    background: #1a1f2e;

    border: 3px solid #ff4444;

    border-radius: 20px;

    overflow: hidden;

    box-shadow:
        0 0 30px rgba(255, 68, 68, 0.3);
}

.screen-header {
    background: linear-gradient(
        135deg,
        #ff4444,
        #cc0000
    );

    padding: 12px;

    display: flex;
    align-items: center;

    gap: 10px;
}

.screen-header i {
    color: white;
    font-size: 1.2rem;
}

.screen-header span {
    color: white;
    font-weight: bold;
}

.screen-header .status {
    margin-left: auto;

    font-size: 0.8rem;

    background: rgba(0,0,0,0.3);

    padding: 4px 12px;

    border-radius: 20px;
}

.screen-content {
    padding: 20px;

    min-height: 400px;

    background: #050812;

    font-family: monospace;
}

/* =========================
   ERROR SCREEN
   ========================= */

.error-screen {
    text-align: center;
}

.error-code {
    font-size: 4rem;
    font-weight: bold;

    color: #ff4444;

    font-family: monospace;
}

.error-message {
    font-size: 1.2rem;

    margin: 1rem 0;

    color: #ffaa66;
}

.error-description {
    color: #8899cc;

    margin: 1rem 0;
}

/* =========================
   DIAGNOSTIC PANEL
   ========================= */

.diagnostic-panel {
    flex: 1;
    min-width: 280px;
}

.diagnostic-card {
    background: rgba(0, 0, 0, 0.4);

    border: 1px solid rgba(255, 68, 68, 0.3);

    border-radius: 16px;

    margin-bottom: 1rem;

    overflow: hidden;
}

.diagnostic-header {
    background: rgba(255, 68, 68, 0.1);

    padding: 12px 16px;

    font-weight: bold;

    color: #ff6666;
}

.diagnostic-body {
    padding: 16px;
}

.tool-buttons {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.tool-btn {
    background: rgba(255, 68, 68, 0.15);

    border: 1px solid rgba(255, 68, 68, 0.4);

    color: #ff8888;

    padding: 8px 16px;

    border-radius: 8px;

    cursor: pointer;

    transition: all 0.3s;

    font-size: 0.8rem;
}

.tool-btn:hover {
    background: rgba(255, 68, 68, 0.3);

    transform: translateY(-2px);
}

/* =========================
   SOLUTIONS
   ========================= */

.solution-option {
    background: rgba(0, 0, 0, 0.3);

    border: 1px solid rgba(255, 68, 68, 0.2);

    border-radius: 12px;

    padding: 12px;

    margin-bottom: 10px;

    cursor: pointer;

    transition: all 0.3s;
}

.solution-option:hover {
    background: rgba(255, 68, 68, 0.1);

    border-color: #ff4444;

    transform: translateX(5px);
}

.solution-option.correct {
    background: rgba(0, 255, 157, 0.15);
    border-color: #00ff9d;
}

.solution-option.wrong {
    background: rgba(255, 0, 0, 0.15);
    border-color: #ff0000;
}

/* =========================
   SCORE
   ========================= */

.score-panel {
    display: flex;
    justify-content: space-between;

    margin-bottom: 1rem;

    padding: 12px;

    background: rgba(0,0,0,0.3);

    border-radius: 12px;
}

.score-value {
    font-size: 1.5rem;
    font-weight: bold;
    color: #ffaa00;
}

/* =========================
   BUTTONS
   ========================= */

.btn {
    padding: 10px 20px;

    border: none;

    border-radius: 40px;

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
    background: linear-gradient(
        45deg,
        #ff4444,
        #cc0000
    );

    color: white;
}

.btn-secondary {
    background: transparent;

    border: 2px solid #ff4444;

    color: #ff4444;
}

.btn-primary:hover,
.btn-secondary:hover {
    transform: translateY(-2px);
}

.control-buttons {
    display: flex;

    gap: 1rem;

    justify-content: center;

    margin-top: 2rem;
}

/* =========================
   COMPLETION
   ========================= */

.completion-modal {
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
}

.completion-modal.active {
    display: flex;
}

.completion-content {
    background: linear-gradient(
        135deg,
        #0f0f23,
        #050510
    );

    border: 2px solid #ffaa00;

    border-radius: 24px;

    padding: 2rem;

    text-align: center;

    max-width: 500px;
}

@keyframes fall {
    to {
        transform: translateY(100vh) rotate(360deg);
        opacity: 0;
    }
}

/* =========================
   LIGHT MODE
   ========================= */

body.light-mode {
    background: #f4f7fb;
    color: #172033;
}

body.light-mode .cyber-bg {
    background: radial-gradient(
        circle at 20% 30%,
        #ffffff,
        #eaf1f7
    );
}

body.light-mode .grid-overlay {
    background-image:
        linear-gradient(
            rgba(220, 50, 50, 0.07) 1px,
            transparent 1px
        ),
        linear-gradient(
            90deg,
            rgba(220, 50, 50, 0.07) 1px,
            transparent 1px
        );
}

body.light-mode .navbar {
    background: rgba(255, 255, 255, 0.94);

    border-bottom-color:
        rgba(220, 50, 50, 0.25);
}

body.light-mode .nav-links a {
    color: #526174;
}

body.light-mode .nav-links a:hover {
    color: #cc0000;
}

body.light-mode .user-info {
    color: #cc0000;

    background:
        rgba(220, 50, 50, 0.08);
}

body.light-mode .game-header p {
    color: #526174;
}

body.light-mode .computer-screen {
    background: #ffffff;

    border-color: #dd3333;

    box-shadow:
        0 0 30px rgba(220, 50, 50, 0.18);
}

body.light-mode .screen-content {
    background: #ffffff;
    color: #172033;
}

body.light-mode .error-message {
    color: #b45309;
}

body.light-mode .error-description {
    color: #526174;
}

body.light-mode .diagnostic-card {
    background: rgba(255, 255, 255, 0.9);

    border-color:
        rgba(220, 50, 50, 0.25);
}

body.light-mode .diagnostic-header {
    background:
        rgba(220, 50, 50, 0.08);

    color: #cc0000;
}

body.light-mode .diagnostic-body {
    color: #334155;
}

body.light-mode .tool-btn {
    background:
        rgba(220, 50, 50, 0.08);

    border-color:
        rgba(220, 50, 50, 0.3);

    color: #b91c1c;
}

body.light-mode .tool-btn:hover {
    background:
        rgba(220, 50, 50, 0.15);
}

body.light-mode .solution-option {
    background:
        rgba(255, 255, 255, 0.85);

    border-color:
        rgba(220, 50, 50, 0.2);

    color: #334155;
}

body.light-mode .solution-option:hover {
    background:
        rgba(220, 50, 50, 0.08);

    border-color: #dd3333;
}

body.light-mode .score-panel {
    background:
        rgba(255, 255, 255, 0.9);

    color: #334155;
}

body.light-mode .btn-secondary {
    background: transparent;

    border-color: #cc0000;

    color: #cc0000;
}

body.light-mode .completion-modal {
    background:
        rgba(20, 30, 45, 0.72);
}

body.light-mode .completion-content {
    background: linear-gradient(
        135deg,
        #ffffff,
        #f1f5f9
    );

    color: #172033;
}

body.light-mode .theme-toggle {
    border-color:
        rgba(180, 30, 30, 0.35);

    background:
        rgba(220, 50, 50, 0.08);

    color: #b91c1c;
}

body.light-mode .theme-toggle:hover {
    background: #00cc11;
    color: #ffffff;
}

/* Dynamic recommended tools area */
body.light-mode #screenContent [style*="background: rgba(255,68,68,0.1)"] {
    background:
        rgba(220, 50, 50, 0.08) !important;

    color: #334155 !important;
}

/* =========================
   MOBILE
   ========================= */

@media (max-width: 900px) {

    .game-container {
        flex-direction: column;
    }

    .computer-panel {
        min-width: auto;
    }
}

</style>
</head>

<body>

<div class="cyber-bg"></div>
<div class="grid-overlay"></div>

<!-- =========================
     NAVBAR
     ========================= -->

<nav class="navbar">

    <div class="nav-container">

        <a href="dashboard.php" class="logo">
            🔧 CSS TROUBLESHOOTING SIM 🔧
        </a>

        <div class="nav-links">

            <a href="dashboard.php">
                <i class="fas fa-home"></i>
                Home
            </a>

            <a href="coc1_review.php">
                <i class="fas fa-microchip"></i>
                COC 1
            </a>

            <a href="coc4_review.php">
                <i class="fas fa-tools"></i>
                COC 4
            </a>

            <div class="user-info">

                <i class="fas fa-user"></i>

                <span>
                    <?php
                    echo htmlspecialchars(
                        $_SESSION['full_name']
                        ?? $_SESSION['username']
                    );
                    ?>
                </span>

                <!-- THEME BUTTON -->

                <button
                    type="button"
                    class="theme-toggle"
                    id="themeToggle"
                    aria-label="Toggle light and dark mode"
                    title="Toggle light and dark mode"
                >

                    <i
                        class="fas fa-moon"
                        id="themeIcon"
                    ></i>

                    <span id="themeText">
                        Dark Mode
                    </span>

                </button>

                <a
                    href="logout.php"
                    style="color: #a52109;"
                >
                    <i class="fas fa-sign-out-alt"></i>
                </a>

            </div>

        </div>

    </div>

</nav>

<!-- =========================
     MAIN CONTENT
     ========================= -->

<div class="container">

    <div class="game-header">

        <h1>
            <i class="fas fa-tools"></i>
            PC Troubleshooting Simulator
        </h1>

        <p>
            Diagnose and fix computer problems using the correct tools and solutions!
        </p>

    </div>

    <div class="game-container">

        <!-- COMPUTER -->

        <div class="computer-panel">

            <div class="computer-screen">

                <div class="screen-header">

                    <i class="fas fa-desktop"></i>

                    <span>
                        PC DIAGNOSTIC MODE
                    </span>

                    <span
                        class="status status-error"
                        id="problemStatus"
                    >
                        🔴 ISSUE DETECTED
                    </span>

                </div>

                <div
                    class="screen-content"
                    id="screenContent"
                ></div>

            </div>

            <div class="score-panel">

                <div>
                    <i class="fas fa-chart-line"></i>
                    Score:
                    <span
                        id="scoreValue"
                        class="score-value"
                    >0</span>
                </div>

                <div>
                    <i class="fas fa-check-circle"></i>
                    Fixed:
                    <span id="fixedCount">0</span>/
                    <span id="totalProblems">12</span>
                </div>

                <div>
                    <i class="fas fa-tools"></i>
                    Attempts:
                    <span id="attemptCount">0</span>
                </div>

                <div>
                    <i class="fas fa-database"></i>
                    Best:
                    <span id="bestScore">
                        <?php echo $previous_score['score'] ?? 0; ?>
                    </span>
                </div>

            </div>

        </div>

        <!-- DIAGNOSTIC PANEL -->

        <div class="diagnostic-panel">

            <div class="diagnostic-card">

                <div class="diagnostic-header">
                    <i class="fas fa-microscope"></i>
                    Diagnostic Tools
                </div>

                <div class="diagnostic-body">

                    <div
                        class="tool-buttons"
                        id="toolButtons"
                    >

                        <div
                            class="tool-btn"
                            data-tool="multimeter"
                        >
                            📊 Multimeter
                        </div>

                        <div
                            class="tool-btn"
                            data-tool="postcard"
                        >
                            🟢 POST Card
                        </div>

                        <div
                            class="tool-btn"
                            data-tool="memtest"
                        >
                            🧪 MemTest86
                        </div>

                        <div
                            class="tool-btn"
                            data-tool="chkdsk"
                        >
                            💿 CHKDSK
                        </div>

                        <div
                            class="tool-btn"
                            data-tool="sfc"
                        >
                            🔧 SFC /SCANNOW
                        </div>

                        <div
                            class="tool-btn"
                            data-tool="ping"
                        >
                            🌐 PING
                        </div>

                        <div
                            class="tool-btn"
                            data-tool="ipconfig"
                        >
                            📡 IPCONFIG
                        </div>

                        <div
                            class="tool-btn"
                            data-tool="eventviewer"
                        >
                            📋 Event Viewer
                        </div>

                        <div
                            class="tool-btn"
                            data-tool="taskmgr"
                        >
                            ⚡ Task Manager
                        </div>

                        <div
                            class="tool-btn"
                            data-tool="bios"
                        >
                            💻 BIOS Setup
                        </div>

                    </div>

                </div>

            </div>

            <div class="diagnostic-card">

                <div class="diagnostic-header">
                    <i class="fas fa-lightbulb"></i>
                    Possible Solutions
                </div>

                <div
                    class="diagnostic-body"
                    id="solutionsList"
                ></div>

            </div>

        </div>

    </div>

    <div class="control-buttons">

        <button
            class="btn btn-secondary"
            onclick="resetGame()"
        >
            <i class="fas fa-redo"></i>
            Reset
        </button>

    </div>

</div>

<!-- =========================
     COMPLETION MODAL
     ========================= -->

<div
    id="completionModal"
    class="completion-modal"
>

    <div class="completion-content">

        <i
            class="fas fa-trophy"
            style="font-size: 4rem; color: #ffaa00;"
        ></i>

        <h2>
            🏆 Master Troubleshooter! 🏆
        </h2>

        <p>
            You've successfully diagnosed and fixed all computer problems!
        </p>

        <div
            style="font-size: 2rem; margin: 1rem 0;"
            id="finalScoreSpan"
        >
            0
        </div>

        <div id="completionMessage"></div>

        <div style="margin-top: 1.5rem;">

            <button
                class="btn btn-primary"
                onclick="saveAndClose()"
            >
                <i class="fas fa-save"></i>
                Save Score
            </button>

            <button
                class="btn btn-secondary"
                onclick="resetGame(); closeCompletionModal();"
            >
                <i class="fas fa-redo"></i>
                Practice Again
            </button>

        </div>

    </div>

</div>

<!-- TOAST -->

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
        font-weight: bold;
    "
></div>

<script>

/* ==========================================================
   TROUBLESHOOTING PROBLEMS DATABASE
   ========================================================== */

const problemDatabase = [

    {
        id: 1,
        title: "Computer Won't Turn On",
        errorCode: "NO POWER",
        description:
            "The computer shows no signs of life. No lights, no fans, no beeps.",
        symptoms: [
            "No LED lights",
            "No fan movement",
            "No POST beeps"
        ],
        tools: ["multimeter", "postcard"],
        correctSolution:
            "Check power supply connections and test PSU with multimeter",
        points: 100
    },

    {
        id: 2,
        title: "Blue Screen of Death",
        errorCode: "CRITICAL_PROCESS_DIED",
        description:
            "Windows suddenly crashes with a blue screen error.",
        symptoms: [
            "Blue screen appears",
            "System restarts automatically"
        ],
        tools: ["eventviewer", "sfc"],
        correctSolution:
            "Run SFC /SCANNOW and check Event Viewer for error logs",
        points: 100
    },

    {
        id: 3,
        title: "No Display on Monitor",
        errorCode: "NO SIGNAL",
        description:
            "Computer powers on but monitor shows 'No Signal'.",
        symptoms: [
            "Fans spinning",
            "LEDs on",
            "Monitor on but no display"
        ],
        tools: ["postcard", "bios"],
        correctSolution:
            "Reseat GPU and check video cable connections",
        points: 100
    },

    {
        id: 4,
        title: "Slow Computer Performance",
        errorCode: "HIGH RESOURCE USAGE",
        description:
            "Computer runs very slowly, programs take long to open.",
        symptoms: [
            "High CPU usage",
            "RAM almost full",
            "Slow boot time"
        ],
        tools: ["taskmgr", "memtest"],
        correctSolution:
            "Check Task Manager for resource-heavy processes and run disk cleanup",
        points: 100
    },

    {
        id: 5,
        title: "No Internet Connection",
        errorCode: "NETWORK ERROR",
        description:
            "Computer shows 'No Internet' or 'Limited connectivity'.",
        symptoms: [
            "Network icon shows error",
            "Cannot browse websites"
        ],
        tools: ["ping", "ipconfig"],
        correctSolution:
            "Run IPCONFIG /RELEASE and IPCONFIG /RENEW, then PING gateway",
        points: 100
    },

    {
        id: 6,
        title: "Computer Randomly Restarts",
        errorCode: "THERMAL_THROTTLE",
        description:
            "Computer shuts down and restarts randomly.",
        symptoms: [
            "Sudden shutdowns",
            "Worse under load",
            "Hot components"
        ],
        tools: ["bios", "taskmgr"],
        correctSolution:
            "Check CPU temperatures and clean cooling system",
        points: 100
    },

    {
        id: 7,
        title: "USB Devices Not Recognized",
        errorCode: "USB DEVICE ERROR",
        description:
            "Connected USB devices are not detected.",
        symptoms: [
            "USB device not showing",
            "Device Manager has exclamation"
        ],
        tools: ["eventviewer", "taskmgr"],
        correctSolution:
            "Update USB controllers in Device Manager",
        points: 100
    },

    {
        id: 8,
        title: "Hard Drive Not Detected",
        errorCode: "DISK BOOT FAILURE",
        description:
            "BIOS doesn't detect hard drive.",
        symptoms: [
            "BIOS shows no HDD/SSD",
            "Clicking noise from drive"
        ],
        tools: ["chkdsk", "postcard"],
        correctSolution:
            "Check SATA connections and run CHKDSK",
        points: 100
    },

    {
        id: 9,
        title: "Windows Won't Boot",
        errorCode: "BOOTMGR MISSING",
        description:
            "Windows fails to start.",
        symptoms: [
            "Black screen on boot",
            "Boot manager error"
        ],
        tools: ["sfc", "chkdsk"],
        correctSolution:
            "Run Startup Repair or Bootrec commands from recovery media",
        points: 100
    },

    {
        id: 10,
        title: "No Sound from Speakers",
        errorCode: "AUDIO SERVICE ERROR",
        description:
            "No audio output from speakers.",
        symptoms: [
            "Red X on speaker icon",
            "No sound from any app"
        ],
        tools: ["taskmgr", "eventviewer"],
        correctSolution:
            "Restart Windows Audio service and update audio drivers",
        points: 100
    },

    {
        id: 11,
        title: "Printer Not Printing",
        errorCode: "PRINTER OFFLINE",
        description:
            "Printer shows offline status.",
        symptoms: [
            "Printer offline",
            "Documents stuck in queue"
        ],
        tools: ["ping", "eventviewer"],
        correctSolution:
            "Clear print queue and restart Print Spooler service",
        points: 100
    },

    {
        id: 12,
        title: "BIOS Password Forgotten",
        errorCode: "PASSWORD LOCKED",
        description:
            "BIOS is password protected.",
        symptoms: [
            "Password prompt on boot",
            "Cannot enter BIOS"
        ],
        tools: ["bios", "postcard"],
        correctSolution:
            "Clear CMOS by removing battery or using jumper",
        points: 100
    }

];

/* ==========================================================
   GAME VARIABLES
   ========================================================== */

let problems = [];
let currentProblemIndex = 0;
let score = 0;
let attempts = 0;
let problemStatus = [];
let gameSaved = false;

/* ==========================================================
   SHUFFLE
   ========================================================== */

function shuffleArray(arr) {

    for (let i = arr.length - 1; i > 0; i--) {

        const j =
            Math.floor(Math.random() * (i + 1));

        [arr[i], arr[j]] =
            [arr[j], arr[i]];
    }

    return arr;
}

/* ==========================================================
   INITIALIZE GAME
   ========================================================== */

function initGame() {

    problems =
        shuffleArray([...problemDatabase]);

    problemStatus =
        problems.map(() => ({
            fixed: false,
            attempts: 0
        }));

    currentProblemIndex = 0;

    if (!gameSaved) {
        score = 0;
        attempts = 0;
    }

    updateStats();
    loadProblem();
}

/* ==========================================================
   LOAD PROBLEM
   ========================================================== */

function loadProblem() {

    while (
        currentProblemIndex < problems.length &&
        problemStatus[currentProblemIndex].fixed
    ) {
        currentProblemIndex++;
    }

    if (currentProblemIndex >= problems.length) {

        showCompletion();
        return;
    }

    const problem =
        problems[currentProblemIndex];

    document.getElementById(
        'screenContent'
    ).innerHTML = `

        <div class="error-screen">

            <div class="error-code">
                ${problem.errorCode}
            </div>

            <div class="error-message">
                ${problem.title}
            </div>

            <div class="error-description">
                ${problem.description}
            </div>

            <div
                style="
                    margin-top: 1rem;
                    text-align: left;
                "
            >

                <strong>
                    <i class="fas fa-stethoscope"></i>
                    Symptoms:
                </strong>

                <ul
                    style="
                        margin-top: 0.5rem;
                        margin-left: 1.5rem;
                    "
                >

                    ${problem.symptoms
                        .map(s => `<li>${s}</li>`)
                        .join('')}

                </ul>

            </div>

            <div
                style="
                    margin-top: 1rem;
                    padding: 0.5rem;
                    background: rgba(255,68,68,0.1);
                    border-radius: 8px;
                "
            >

                <i class="fas fa-lightbulb"></i>

                <strong>
                    Recommended Tools:
                </strong>

                ${problem.tools.join(', ')}

            </div>

        </div>
    `;

    generateSolutions(problem);

    document.getElementById(
        'problemStatus'
    ).innerHTML =
        `🔴 ${problem.title}`;
}

/* ==========================================================
   GENERATE SOLUTIONS
   ========================================================== */

function generateSolutions(problem) {

    const incorrectSolutions = [

        "Replace the motherboard immediately",

        "Reinstall Windows from scratch",

        "Buy a new computer",

        "Ignore the problem",

        "Spray water to clean components",

        "Remove all RAM sticks",

        "Disconnect all cables",

        "Format the hard drive",

        "Overclock the CPU"

    ];

    const availableIncorrect =
        incorrectSolutions.filter(
            s => s !== problem.correctSolution
        );

    const shuffledIncorrect =
        shuffleArray([...availableIncorrect]);

    const selectedIncorrect =
        shuffledIncorrect.slice(0, 3);

    const allOptions = [
        problem.correctSolution,
        ...selectedIncorrect
    ];

    const shuffledOptions =
        shuffleArray(allOptions);

    document.getElementById(
        'solutionsList'
    ).innerHTML = shuffledOptions.map(opt => `

        <div
            class="solution-option"
            onclick="selectSolution(
                '${escapeHtml(opt)}',
                '${escapeHtml(problem.correctSolution)}'
            )"
        >

            <i class="fas fa-wrench"></i>

            ${escapeHtml(opt)}

        </div>

    `).join('');
}

/* ==========================================================
   ESCAPE HTML
   ========================================================== */

function escapeHtml(text) {

    const div =
        document.createElement('div');

    div.textContent = text;

    return div.innerHTML;
}

/* ==========================================================
   SELECT SOLUTION
   ========================================================== */

function selectSolution(selected, correct) {

    const problem =
        problems[currentProblemIndex];

    const status =
        problemStatus[currentProblemIndex];

    if (status.fixed) return;

    attempts++;
    status.attempts++;

    if (selected === correct) {

        const pointsEarned =
            Math.max(
                50,
                problem.points -
                (status.attempts - 1) * 20
            );

        score += pointsEarned;

        status.fixed = true;

        updateStats();

        showToast(
            `✅ CORRECT! +${pointsEarned} points!`,
            false
        );

        document.getElementById(
            'solutionsList'
        ).innerHTML = `

            <div
                style="
                    background: rgba(0,255,157,0.1);
                    padding: 12px;
                    border-radius: 12px;
                    border-left: 3px solid #00ff9d;
                "
            >

                <i
                    class="fas fa-check-circle"
                    style="color: #00ff9d;"
                ></i>

                <strong>
                    Correct Solution!
                </strong>

                <div
                    style="
                        margin-top: 8px;
                        font-size: 0.85rem;
                    "
                >
                    ${problem.correctSolution}
                </div>

            </div>
        `;

        setTimeout(() => {

            currentProblemIndex++;

            loadProblem();

        }, 2000);

    } else {

        showToast(
            `❌ WRONG! -10 points. Try again!`,
            true
        );

        score =
            Math.max(0, score - 10);

        updateStats();

        const options =
            document.querySelectorAll(
                '.solution-option'
            );

        options.forEach(opt => {

            if (
                opt.innerHTML.includes(
                    escapeHtml(selected)
                )
            ) {

                opt.classList.add('wrong');

                setTimeout(() => {

                    opt.classList.remove('wrong');

                }, 500);
            }

        });
    }
}

/* ==========================================================
   UPDATE STATS
   ========================================================== */

function updateStats() {

    const fixed =
        problemStatus.filter(
            s => s.fixed
        ).length;

    document.getElementById(
        'scoreValue'
    ).textContent = score;

    document.getElementById(
        'fixedCount'
    ).textContent = fixed;

    document.getElementById(
        'totalProblems'
    ).textContent = problems.length;

    document.getElementById(
        'attemptCount'
    ).textContent = attempts;
}

/* ==========================================================
   TOAST
   ========================================================== */

function showToast(msg, isError) {

    const toast =
        document.getElementById('toast');

    toast.textContent = msg;

    toast.style.background =
        isError
            ? '#ff4444'
            : '#00ff9d';

    toast.style.color =
        isError
            ? 'white'
            : '#050812';

    toast.style.display = 'block';

    setTimeout(() => {

        toast.style.display = 'none';

    }, 3000);
}

/* ==========================================================
   COMPLETION
   ========================================================== */

function showCompletion() {

    const fixed =
        problemStatus.filter(
            s => s.fixed
        ).length;

    const total =
        problems.length;

    const percentage =
        (fixed / total) * 100;

    document.getElementById(
        'finalScoreSpan'
    ).textContent = score;

    document.getElementById(
        'completionMessage'
    ).innerHTML =
        percentage === 100

        ? '🏆 PERFECT SCORE! 🏆<br>You diagnosed and fixed all problems correctly!'

        : '🎉 Great job! You\'re becoming a troubleshooting expert! 🎉';

    document.getElementById(
        'completionModal'
    ).classList.add('active');

    createConfetti();
}

/* ==========================================================
   SAVE SCORE
   ========================================================== */

function saveAndClose() {

    if (gameSaved) return;

    fetch(
        'save_troubleshooting_score.php',
        {
            method: 'POST',

            headers: {
                'Content-Type':
                    'application/x-www-form-urlencoded'
            },

            body:
                'score=' + score +
                '&problems_fixed=' +
                problemStatus.filter(
                    s => s.fixed
                ).length +
                '&attempts=' +
                attempts
        }
    )

    .then(response => response.json())

    .then(data => {

        if (data.success) {

            gameSaved = true;

            showToast(
                '✅ Score saved to database!',
                false
            );

            document.getElementById(
                'bestScore'
            ).textContent =
                data.best_score;
        }

    })

    .catch(error =>
        console.error(
            'Error:',
            error
        )
    );

    setTimeout(() => {

        document.getElementById(
            'completionModal'
        ).classList.remove('active');

    }, 1500);
}

/* ==========================================================
   CONFETTI
   ========================================================== */

function createConfetti() {

    for (let i = 0; i < 100; i++) {

        const confetti =
            document.createElement('div');

        confetti.style.position =
            'fixed';

        confetti.style.left =
            Math.random() *
            window.innerWidth +
            'px';

        confetti.style.top =
            '-10px';

        confetti.style.width =
            Math.random() * 10 +
            5 +
            'px';

        confetti.style.height =
            Math.random() * 10 +
            5 +
            'px';

        confetti.style.backgroundColor =
            `hsl(
                ${Math.random() * 360},
                100%,
                50%
            )`;

        confetti.style.borderRadius =
            Math.random() > 0.5
                ? '50%'
                : '0';

        confetti.style.zIndex =
            '2000';

        confetti.style.animation =
            `fall ${
                Math.random() * 2 + 2
            }s linear forwards`;

        document.body.appendChild(
            confetti
        );

        setTimeout(() => {

            confetti.remove();

        }, 3000);
    }
}

/* ==========================================================
   RESET
   ========================================================== */

function resetGame() {

    gameSaved = false;

    initGame();

    showToast(
        '🔄 Game reset! New problems loaded!',
        false
    );
}

/* ==========================================================
   CLOSE COMPLETION MODAL
   ========================================================== */

function closeCompletionModal() {

    document.getElementById(
        'completionModal'
    ).classList.remove('active');
}

/* ==========================================================
   DIAGNOSTIC TOOLS
   ========================================================== */

function initTools() {

    const toolInfo = {

        'multimeter':
            '🔧 Multimeter: Used to test voltage and continuity in power supplies.',

        'postcard':
            '🟢 POST Card: Displays POST error codes to identify hardware failures.',

        'memtest':
            '🧪 MemTest86: Memory testing tool to detect RAM errors.',

        'chkdsk':
            '💿 CHKDSK: Checks and repairs hard drive errors.',

        'sfc':
            '🔧 SFC /SCANNOW: Scans and repairs corrupted Windows system files.',

        'ping':
            '🌐 PING: Tests network connectivity to other devices.',

        'ipconfig':
            '📡 IPCONFIG: Displays IP configuration and can renew DHCP addresses.',

        'eventviewer':
            '📋 Event Viewer: Views system logs and error reports.',

        'taskmgr':
            '⚡ Task Manager: Monitors processes and performance.',

        'bios':
            '💻 BIOS Setup: Low-level hardware configuration interface.'

    };

    document
        .querySelectorAll('.tool-btn')
        .forEach(tool => {

            tool.addEventListener(
                'click',
                () => {

                    const toolName =
                        tool.getAttribute(
                            'data-tool'
                        );

                    showToast(
                        `ℹ️ ${
                            toolInfo[toolName]
                            ||
                            'Learn more about this diagnostic tool.'
                        }`,
                        false
                    );

                }
            );

        });
}

/* ==========================================================
   THEME TOGGLE
   ========================================================== */

(function () {

    const themeToggle =
        document.getElementById(
            'themeToggle'
        );

    const themeIcon =
        document.getElementById(
            'themeIcon'
        );

    const themeText =
        document.getElementById(
            'themeText'
        );

    function applyTheme(theme) {

        const isLight =
            theme === 'light';

        document.body.classList.toggle(
            'light-mode',
            isLight
        );

        if (themeIcon) {

            themeIcon.className =
                isLight
                    ? 'fas fa-sun'
                    : 'fas fa-moon';
        }

        if (themeText) {

            themeText.textContent =
                isLight
                    ? 'Light Mode'
                    : 'Dark Mode';
        }
    }

    const savedTheme =
        localStorage.getItem(
            'css_tesda_theme'
        ) || 'dark';

    applyTheme(savedTheme);

    if (themeToggle) {

        themeToggle.addEventListener(
            'click',
            function () {

                const nextTheme =
                    document.body.classList.contains(
                        'light-mode'
                    )
                        ? 'dark'
                        : 'light';

                localStorage.setItem(
                    'css_tesda_theme',
                    nextTheme
                );

                applyTheme(nextTheme);
            }
        );
    }

})();

/* ==========================================================
   START GAME
   ========================================================== */

document.addEventListener(
    'DOMContentLoaded',
    () => {

        initGame();
        initTools();

    }
);

</script>

<?php
$global_brightness = getGlobalBrightness();
include __DIR__ . '/includes/brightness_widget.php';
?>

</body>
</html>
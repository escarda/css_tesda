<?php
// corrective_maintenance.php - COC 4: Maintain and Repair Computer Systems
// Session is started by config.php
require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$is_admin = isAdmin();
$global_brightness = getGlobalBrightness();

$page_title = "COC 4: Corrective Maintenance";

// Fetch recent results for this specific activity (optional, for context)
$recent_cm_results = $conn->query("
    SELECT score, total_questions, completed_at 
    FROM quizzes 
    WHERE user_id = $user_id AND category_id = 4 
    ORDER BY completed_at DESC 
    LIMIT 3
");
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

        * { margin: 0; padding: 0; box-sizing: border-box; }
        html, body { overflow-x: hidden; }

        body {
            font-family: 'Rajdhani', sans-serif;
            background: var(--bg-primary);
            color: var(--text-primary);
            min-height: 100vh;
            transition: background 0.3s ease, color 0.3s ease;
        }

        .page-wrapper {
            filter: brightness(var(--global-brightness, 100%));
            transition: filter 0.3s ease;
            min-height: 100vh;
        }

        body.modal-open { overflow: hidden !important; }

        .cyber-bg {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            z-index: -2; background: linear-gradient(135deg, var(--cyber-bg1) 0%, var(--cyber-bg2) 100%);
        }

        .grid-overlay {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background-image: linear-gradient(var(--grid-color) 1px, transparent 1px), linear-gradient(90deg, var(--grid-color) 1px, transparent 1px);
            background-size: 50px 50px; z-index: -1;
            animation: gridMove 20s linear infinite;
        }

        @keyframes gridMove { 0% { transform: translate(0, 0); } 100% { transform: translate(50px, 50px); } }

        .navbar {
            background: var(--bg-navbar); backdrop-filter: blur(10px);
            border-bottom: 1px solid var(--border-light);
            padding: 1rem 2rem; position: sticky; top: 0; z-index: 100;
            transition: background 0.3s ease;
        }

        .nav-container {
            max-width: 1400px; margin: 0 auto; display: flex;
            justify-content: space-between; align-items: center;
            flex-wrap: wrap; gap: 1rem;
        }

        .logo {
            font-family: 'Orbitron', sans-serif; font-size: 1.5rem; font-weight: 800;
            background: linear-gradient(135deg, #00ff9d, #0066ff);
            -webkit-background-clip: text; -webkit-text-fill-color: transparent;
            text-decoration: none;
        }

        .nav-links { display: flex; gap: 1.5rem; align-items: center; flex-wrap: wrap; }
        .nav-links a { color: var(--text-secondary); text-decoration: none; transition: all 0.3s; display: flex; align-items: center; gap: 8px; }
        .nav-links a:hover, .nav-links a.active { color: #00ff9d; }
        .user-info { display: flex; align-items: center; gap: 1rem; color: #00ff9d; }

        .theme-toggle-nav {
            display: inline-flex; align-items: center; gap: 7px;
            padding: 8px 13px; border: 1px solid rgba(0, 255, 157, 0.45);
            border-radius: 10px; background: rgba(0, 255, 157, 0.08);
            color: var(--text-primary); font-family: 'Rajdhani', sans-serif;
            font-size: 0.9rem; font-weight: 600; cursor: pointer;
            transition: all 0.25s ease; white-space: nowrap;
        }
        .theme-toggle-nav:hover { color: #00ff9d; border-color: #00ff9d; background: rgba(0, 255, 157, 0.15); transform: translateY(-1px); }

        .container { max-width: 1400px; margin: 0 auto; padding: 2rem; }

        .page-header { text-align: center; margin-bottom: 3rem; }
        .page-header h1 {
            font-family: 'Orbitron', sans-serif; font-size: 2.5rem;
            background: linear-gradient(135deg, #00ff9d, #0066ff);
            -webkit-background-clip: text; -webkit-text-fill-color: transparent;
            margin-bottom: 1rem;
        }
        .page-header p { color: var(--text-secondary); font-size: 1.1rem; }

        .coc-badge {
            display: inline-block; padding: 8px 20px;
            background: rgba(0, 255, 157, 0.1); border: 1px solid #00ff9d;
            border-radius: 50px; color: #00ff9d; margin-bottom: 1rem;
        }

        /* Overview Section */
        .overview-section {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 20px;
            padding: 2rem;
            margin-bottom: 2rem;
        }

        .overview-section h2 {
            font-family: 'Orbitron', sans-serif;
            color: #00ff9d;
            font-size: 1.5rem;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .overview-section p {
            color: var(--text-secondary);
            line-height: 1.8;
            margin-bottom: 1rem;
        }

        .definition-box {
            background: rgba(0, 255, 157, 0.05);
            border-left: 4px solid #00ff9d;
            padding: 1rem 1.5rem;
            border-radius: 0 10px 10px 0;
            margin: 1.5rem 0;
        }

        .definition-box strong {
            color: #00ff9d;
        }

        /* Common Faults Grid */
        .faults-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 1.5rem;
            margin: 2rem 0;
        }

        .fault-card {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 16px;
            padding: 1.5rem;
            transition: all 0.3s;
        }

        .fault-card:hover {
            transform: translateY(-5px);
            border-color: #00ff9d;
            box-shadow: 0 10px 30px rgba(0, 255, 157, 0.15);
        }

        .fault-card .fault-icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-bottom: 1rem;
        }

        .fault-card .fault-icon.power { background: rgba(255, 68, 68, 0.2); color: #ff6666; }
        .fault-card .fault-icon.display { background: rgba(255, 170, 0, 0.2); color: #ffaa00; }
        .fault-card .fault-icon.boot { background: rgba(59, 130, 246, 0.2); color: #60a5fa; }
        .fault-card .fault-icon.network { background: rgba(236, 72, 153, 0.2); color: #ec4899; }
        .fault-card .fault-icon.overheat { background: rgba(255, 100, 50, 0.2); color: #ff6432; }
        .fault-card .fault-icon.slow { background: rgba(168, 85, 247, 0.2); color: #a855f7; }

        .fault-card h3 {
            font-family: 'Orbitron', sans-serif;
            font-size: 1.1rem;
            color: #00ff9d;
            margin-bottom: 0.75rem;
        }

        .fault-card p {
            color: var(--text-secondary);
            font-size: 0.9rem;
            line-height: 1.6;
            margin-bottom: 1rem;
        }

        .fault-card .symptoms {
            font-size: 0.8rem;
            color: var(--text-muted);
        }

        .fault-card .symptoms strong {
            color: #ffaa00;
        }

        /* Quiz Section */
        .quiz-section {
            margin-top: 3rem;
            padding: 2rem;
            background: var(--bg-panel, rgba(0, 0, 0, 0.3));
            border: 1px solid var(--border-color);
            border-radius: 20px;
        }

        .quiz-section h2 {
            font-family: 'Orbitron', sans-serif;
            color: #00ff9d;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .quiz-question {
            background: rgba(0, 0, 0, 0.2);
            border: 1px solid var(--border-light);
            border-radius: 16px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            transition: all 0.3s;
        }

        .quiz-question .q-number {
            font-family: 'Orbitron', sans-serif;
            font-size: 0.8rem;
            color: #00ff9d;
            margin-bottom: 0.75rem;
        }

        .quiz-question .q-text {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 1.25rem;
            line-height: 1.5;
        }

        .quiz-options {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }

        .quiz-option {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 16px;
            background: var(--bg-stat);
            border: 1px solid var(--border-light);
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.2s;
            color: var(--text-primary);
            font-family: 'Rajdhani', sans-serif;
            font-size: 1rem;
            text-align: left;
            width: 100%;
        }

        .quiz-option:hover:not(.disabled) {
            border-color: #00ff9d;
            background: rgba(0, 255, 157, 0.08);
            transform: translateX(5px);
        }

        .quiz-option .opt-letter {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background: rgba(0, 255, 157, 0.1);
            border: 1px solid var(--border-color);
            color: #00ff9d;
            font-weight: bold;
            font-size: 0.85rem;
            flex-shrink: 0;
        }

        .quiz-option.correct {
            border-color: #00ff9d;
            background: rgba(0, 255, 157, 0.15);
        }
        .quiz-option.correct .opt-letter {
            background: #00ff9d;
            color: #050812;
        }

        .quiz-option.wrong {
            border-color: #ff3366;
            background: rgba(255, 51, 102, 0.15);
        }
        .quiz-option.wrong .opt-letter {
            background: #ff3366;
            color: #050812;
        }

        .quiz-option.disabled {
            cursor: default;
        }
        .quiz-option.disabled:hover {
            transform: none;
        }

        .quiz-feedback {
            margin-top: 1rem;
            padding: 0.75rem 1rem;
            border-radius: 10px;
            font-size: 0.9rem;
            display: none;
        }
        .quiz-feedback.show { display: block; }
        .quiz-feedback.correct {
            background: rgba(0, 255, 157, 0.1);
            border: 1px solid rgba(0, 255, 157, 0.3);
            color: #00ff9d;
        }
        .quiz-feedback.wrong {
            background: rgba(255, 51, 102, 0.1);
            border: 1px solid rgba(255, 51, 102, 0.3);
            color: #ff3366;
        }

        .quiz-score-display {
            text-align: center;
            padding: 1.5rem;
            background: rgba(0, 255, 157, 0.08);
            border: 1px solid rgba(0, 255, 157, 0.3);
            border-radius: 16px;
            margin-top: 1.5rem;
            display: none;
        }
        .quiz-score-display.show { display: block; }

        .quiz-score-display h3 {
            font-family: 'Orbitron', sans-serif;
            color: #00ff9d;
            font-size: 1.3rem;
            margin-bottom: 0.5rem;
        }

        .quiz-score-display p {
            color: var(--text-secondary);
            font-size: 1rem;
        }

        .quiz-score-display .score-value {
            font-size: 2.5rem;
            font-weight: bold;
            color: #00ff9d;
            font-family: 'Orbitron', sans-serif;
        }

        .quiz-actions {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
            margin-top: 1.5rem;
        }

        .btn {
            padding: 12px 28px;
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

        @media (max-width: 768px) {
            .container { padding: 1rem; }
            .page-header h1 { font-size: 1.8rem; }
            .nav-links { width: 100%; justify-content: center; }
            .faults-grid { grid-template-columns: 1fr; }
            .quiz-actions { flex-direction: column; }
            .quiz-actions .btn { width: 100%; justify-content: center; }
        }
    </style>
</head>
<body>
    <div class="page-wrapper">
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
                    <a href="corrective_maintenance.php" class="active"><i class="fas fa-wrench"></i> Corrective Maintenance</a>
                    
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
                    <i class="fas fa-wrench"></i> COC 4: Maintain and Repair Computer Systems
                </div>
                <h1>Corrective Maintenance</h1>
                <p>Diagnose faults, perform repairs, and restore computer systems to normal operation.</p>
            </div>

            <!-- Overview Section -->
            <div class="overview-section">
                <h2><i class="fas fa-info-circle"></i> What is Corrective Maintenance?</h2>
                <p>
                    <strong style="color:#00ff9d;">Corrective maintenance</strong> is the process of restoring a computer system or network to its normal operating condition after a failure or fault has occurred [citation:9][citation:32]. Unlike preventive maintenance (which is scheduled), corrective maintenance is <strong style="color:#ffaa00;">reactive</strong> — it happens when something breaks [citation:32].
                </p>
                <div class="definition-box">
                    <strong>📖 TESDA Definition:</strong> "Maintenance carried out to restore (including adjustment and repair) an item which has ceased to meet an acceptable condition." [citation:9]
                </div>
                <p>
                    In the context of <strong>Computer Systems Servicing NC II</strong>, corrective maintenance involves diagnosing and repairing faults in computer systems and networks. This is a core competency under COC 4 — <em>Maintain and Repair Computer Systems and Networks</em> [citation:1].
                </p>
                <p>
                    The key steps in corrective maintenance are [citation:24]:
                </p>
                <ul style="color: var(--text-secondary); line-height: 2; margin-left: 1.5rem;">
                    <li><strong style="color:#00ff9d;">Detection</strong> — identifying that a fault has occurred</li>
                    <li><strong style="color:#00ff9d;">Localization</strong> — finding which component is faulty</li>
                    <li><strong style="color:#00ff9d;">Diagnosis</strong> — determining the root cause</li>
                    <li><strong style="color:#00ff9d;">Repair</strong> — fixing or replacing the faulty component</li>
                    <li><strong style="color:#00ff9d;">Verification</strong> — testing that the system works correctly again</li>
                </ul>
            </div>

            <!-- Common Faults Grid -->
            <h2 style="font-family: 'Orbitron', sans-serif; color: #00ff9d; margin: 2rem 0 1rem; font-size: 1.5rem;">
                <i class="fas fa-exclamation-triangle"></i> Common Faults and Solutions
            </h2>
            <div class="faults-grid">
                <!-- Power Issues -->
                <div class="fault-card">
                    <div class="fault-icon power"><i class="fas fa-plug"></i></div>
                    <h3>No Power / Won't Turn On</h3>
                    <p>System does not respond when power button is pressed. No lights, no fans, no display.</p>
                    <div class="symptoms">
                        <strong>Possible Causes:</strong> Faulty PSU, loose power cable, dead motherboard, faulty power button, tripped circuit breaker.
                    </div>
                </div>

                <!-- Display Issues -->
                <div class="fault-card">
                    <div class="fault-icon display"><i class="fas fa-tv"></i></div>
                    <h3>No Display / No Signal</h3>
                    <p>System powers on (fans spin, lights on) but monitor shows "No Signal" or stays black.</p>
                    <div class="symptoms">
                        <strong>Possible Causes:</strong> Loose video cable, faulty RAM, dead GPU, monitor not powered, wrong input source.
                    </div>
                </div>

                <!-- Boot Issues -->
                <div class="fault-card">
                    <div class="fault-icon boot"><i class="fas fa-hdd"></i></div>
                    <h3>Boot Failure / BSOD</h3>
                    <p>System starts but stops at BIOS screen, shows "Operating System Not Found," or blue screens.</p>
                    <div class="symptoms">
                        <strong>Possible Causes:</strong> Corrupted OS, failing hard drive, bad sectors, faulty RAM, incorrect boot order.
                    </div>
                </div>

                <!-- Network Issues -->
                <div class="fault-card">
                    <div class="fault-icon network"><i class="fas fa-wifi"></i></div>
                    <h3>No Network / No Internet</h3>
                    <p>Cannot connect to network or internet. "No Internet Access" or "Unidentified Network."</p>
                    <div class="symptoms">
                        <strong>Possible Causes:</strong> Faulty NIC, bad cable, wrong IP config, router issue, driver problem.
                    </div>
                </div>

                <!-- Overheating -->
                <div class="fault-card">
                    <div class="fault-icon overheat"><i class="fas fa-thermometer-full"></i></div>
                    <h3>Overheating / Shutdowns</h3>
                    <p>System randomly shuts down during use. Fans running at max speed constantly.</p>
                    <div class="symptoms">
                        <strong>Possible Causes:</strong> Dust buildup, dried thermal paste, faulty fan, blocked vents.
                    </div>
                </div>

                <!-- Slow Performance -->
                <div class="fault-card">
                    <div class="fault-icon slow"><i class="fas fa-hourglass-half"></i></div>
                    <h3>Slow Performance</h3>
                    <p>System takes long to boot, open apps, or respond to commands. Freezing frequently.</p>
                    <div class="symptoms">
                        <strong>Possible Causes:</strong> Insufficient RAM, failing HDD, malware, too many startup programs, fragmented disk.
                    </div>
                </div>
            </div>

            <!-- Quiz Section -->
            <div class="quiz-section">
                <h2><i class="fas fa-question-circle"></i> Corrective Maintenance Quiz</h2>
                <p style="color: var(--text-secondary); margin-bottom: 1.5rem;">
                    Test your knowledge on diagnosing and repairing computer systems. Click the best answer for each question.
                </p>

                <div id="quizContainer">
                    <!-- Q1 -->
                    <div class="quiz-question" data-question="1" data-correct="B">
                        <div class="q-number">Question 1 of 10</div>
                        <div class="q-text">What is the first step in the corrective maintenance process?</div>
                        <div class="quiz-options">
                            <button class="quiz-option" data-value="A"><span class="opt-letter">A</span> Replace the faulty component</button>
                            <button class="quiz-option" data-value="B"><span class="opt-letter">B</span> Detect that a fault has occurred</button>
                            <button class="quiz-option" data-value="C"><span class="opt-letter">C</span> Order replacement parts</button>
                            <button class="quiz-option" data-value="D"><span class="opt-letter">D</span> Document the repair</button>
                        </div>
                        <div class="quiz-feedback"></div>
                    </div>

                    <!-- Q2 -->
                    <div class="quiz-question" data-question="2" data-correct="C">
                        <div class="q-number">Question 2 of 10</div>
                        <div class="q-text">A computer powers on (fans spin, lights on) but the monitor shows "No Signal." What should you check FIRST?</div>
                        <div class="quiz-options">
                            <button class="quiz-option" data-value="A"><span class="opt-letter">A</span> Replace the motherboard</button>
                            <button class="quiz-option" data-value="B"><span class="opt-letter">B</span> Reinstall the operating system</button>
                            <button class="quiz-option" data-value="C"><span class="opt-letter">C</span> Check the video cable connection</button>
                            <button class="quiz-option" data-value="D"><span class="opt-letter">D</span> Replace the power supply</button>
                        </div>
                        <div class="quiz-feedback"></div>
                    </div>

                    <!-- Q3 -->
                    <div class="quiz-question" data-question="3" data-correct="A">
                        <div class="q-number">Question 3 of 10</div>
                        <div class="q-text">Which component is MOST LIKELY faulty if a computer randomly shuts down during heavy use and the fans are very loud?</div>
                        <div class="quiz-options">
                            <button class="quiz-option" data-value="A"><span class="opt-letter">A</span> Cooling system (fan/heatsink)</button>
                            <button class="quiz-option" data-value="B"><span class="opt-letter">B</span> Hard drive</button>
                            <button class="quiz-option" data-value="C"><span class="opt-letter">C</span> RAM</button>
                            <button class="quiz-option" data-value="D"><span class="opt-letter">D</span> Monitor</button>
                        </div>
                        <div class="quiz-feedback"></div>
                    </div>

                    <!-- Q4 -->
                    <div class="quiz-question" data-question="4" data-correct="D">
                        <div class="q-number">Question 4 of 10</div>
                        <div class="q-text">What is the purpose of thermal paste in a computer system?</div>
                        <div class="quiz-options">
                            <button class="quiz-option" data-value="A"><span class="opt-letter">A</span> To clean the CPU</button>
                            <button class="quiz-option" data-value="B"><span class="opt-letter">B</span> To prevent static electricity</button>
                            <button class="quiz-option" data-value="C"><span class="opt-letter">C</span> To lubricate the fan</button>
                            <button class="quiz-option" data-value="D"><span class="opt-letter">D</span> To improve heat transfer between CPU and heatsink</button>
                        </div>
                        <div class="quiz-feedback"></div>
                    </div>

                    <!-- Q5 -->
                    <div class="quiz-question" data-question="5" data-correct="B">
                        <div class="q-number">Question 5 of 10</div>
                        <div class="q-text">A user reports "No Internet Access" but other devices on the same network work fine. What is the MOST LIKELY cause?</div>
                        <div class="quiz-options">
                            <button class="quiz-option" data-value="A"><span class="opt-letter">A</span> The ISP is down</button>
                            <button class="quiz-option" data-value="B"><span class="opt-letter">B</span> The computer's NIC or IP configuration</button>
                            <button class="quiz-option" data-value="C"><span class="opt-letter">C</span> The router is broken</button>
                            <button class="quiz-option" data-value="D"><span class="opt-letter">D</span> The modem needs replacement</button>
                        </div>
                        <div class="quiz-feedback"></div>
                    </div>

                    <!-- Q6 -->
                    <div class="quiz-question" data-question="6" data-correct="C">
                        <div class="q-number">Question 6 of 10</div>
                        <div class="q-text">What should you do BEFORE opening a computer case to perform corrective maintenance?</div>
                        <div class="quiz-options">
                            <button class="quiz-option" data-value="A"><span class="opt-letter">A</span> Turn on the PC to check fans</button>
                            <button class="quiz-option" data-value="B"><span class="opt-letter">B</span> Spray water to cool it down</button>
                            <button class="quiz-option" data-value="C"><span class="opt-letter">C</span> Unplug power and ground yourself</button>
                            <button class="quiz-option" data-value="D"><span class="opt-letter">D</span> Shake the case to loosen dust</button>
                        </div>
                        <div class="quiz-feedback"></div>
                    </div>

                    <!-- Q7 -->
                    <div class="quiz-question" data-question="7" data-correct="A">
                        <div class="q-number">Question 7 of 10</div>
                        <div class="q-text">A computer shows "Operating System Not Found" on boot. What is a POSSIBLE cause?</div>
                        <div class="quiz-options">
                            <button class="quiz-option" data-value="A"><span class="opt-letter">A</span> Failing hard drive or corrupted boot sector</button>
                            <button class="quiz-option" data-value="B"><span class="opt-letter">B</span> Monitor cable is loose</button>
                            <button class="quiz-option" data-value="C"><span class="opt-letter">C</span> Mouse is not connected</button>
                            <button class="quiz-option" data-value="D"><span class="opt-letter">D</span> Keyboard has a stuck key</button>
                        </div>
                        <div class="quiz-feedback"></div>
                    </div>

                    <!-- Q8 -->
                    <div class="quiz-question" data-question="8" data-correct="D">
                        <div class="q-number">Question 8 of 10</div>
                        <div class="q-text">What is the recommended way to clean dust from inside a computer?</div>
                        <div class="quiz-options">
                            <button class="quiz-option" data-value="A"><span class="opt-letter">A</span> Use a wet cloth</button>
                            <button class="quiz-option" data-value="B"><span class="opt-letter">B</span> Use a vacuum with metal nozzle</button>
                            <button class="quiz-option" data-value="C"><span class="opt-letter">C</span> Use a broom</button>
                            <button class="quiz-option" data-value="D"><span class="opt-letter">D</span> Use compressed air</button>
                        </div>
                        <div class="quiz-feedback"></div>
                    </div>

                    <!-- Q9 -->
                    <div class="quiz-question" data-question="9" data-correct="B">
                        <div class="q-number">Question 9 of 10</div>
                        <div class="q-text">Which Windows tool is used to check a hard disk for errors and repair them?</div>
                        <div class="quiz-options">
                            <button class="quiz-option" data-value="A"><span class="opt-letter">A</span> Task Manager</button>
                            <button class="quiz-option" data-value="B"><span class="opt-letter">B</span> chkdsk</button>
                            <button class="quiz-option" data-value="C"><span class="opt-letter">C</span> Disk Defragmenter</button>
                            <button class="quiz-option" data-value="D"><span class="opt-letter">D</span> Device Manager</button>
                        </div>
                        <div class="quiz-feedback"></div>
                    </div>

                    <!-- Q10 -->
                    <div class="quiz-question" data-question="10" data-correct="A">
                        <div class="q-number">Question 10 of 10</div>
                        <div class="q-text">After completing a repair, what is the FINAL step in corrective maintenance?</div>
                        <div class="quiz-options">
                            <button class="quiz-option" data-value="A"><span class="opt-letter">A</span> Verify the system works and document the repair</button>
                            <button class="quiz-option" data-value="B"><span class="opt-letter">B</span> Immediately close the case and leave</button>
                            <button class="quiz-option" data-value="C"><span class="opt-letter">C</span> Order new parts for next time</button>
                            <button class="quiz-option" data-value="D"><span class="opt-letter">D</span> Turn off the computer without testing</button>
                        </div>
                        <div class="quiz-feedback"></div>
                    </div>
                </div>

                <!-- Score Display -->
                <div class="quiz-score-display" id="quizScore">
                    <h3><i class="fas fa-trophy"></i> Quiz Complete!</h3>
                    <p>Your Score:</p>
                    <div class="score-value" id="scoreValue">0 / 10</div>
                    <p id="scoreMessage" style="margin-top: 0.5rem;"></p>
                </div>

                <div class="quiz-actions" id="quizActions">
                    <button class="btn btn-primary" id="submitQuizBtn" disabled>
                        <i class="fas fa-check-circle"></i> Submit Quiz
                    </button>
                    <button class="btn btn-secondary" id="resetQuizBtn">
                        <i class="fas fa-redo"></i> Reset Quiz
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal for future use (kept consistent) -->
    <div id="detailModal" class="modal" style="display:none;">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalTitle">Title</h2>
                <button class="close-modal" onclick="closeModal()">&times;</button>
            </div>
            <div class="modal-body">
                <span class="modal-category" id="modalCategory"></span>
                <p id="modalContent"></p>
            </div>
        </div>
    </div>

    <script>
        // ============================================
        // QUIZ LOGIC
        // ============================================
        const quizQuestions = document.querySelectorAll('.quiz-question');
        let answeredCount = 0;
        let score = 0;
        let quizSubmitted = false;

        quizQuestions.forEach(function(question) {
            const options = question.querySelectorAll('.quiz-option');
            const correctAnswer = question.dataset.correct;
            const feedback = question.querySelector('.quiz-feedback');

            options.forEach(function(option) {
                option.addEventListener('click', function() {
                    if (quizSubmitted || option.classList.contains('disabled')) return;

                    const selected = this.dataset.value;
                    const isCorrect = (selected === correctAnswer);

                    // Disable all options in this question
                    options.forEach(function(opt) {
                        opt.classList.add('disabled');
                        opt.style.pointerEvents = 'none';
                    });

                    // Mark selected and correct/wrong
                    if (isCorrect) {
                        this.classList.add('correct');
                        score++;
                        feedback.textContent = '✅ Correct!';
                        feedback.className = 'quiz-feedback show correct';
                    } else {
                        this.classList.add('wrong');
                        // Highlight the correct answer
                        options.forEach(function(opt) {
                            if (opt.dataset.value === correctAnswer) {
                                opt.classList.add('correct');
                            }
                        });
                        feedback.textContent = '❌ Incorrect. The correct answer is ' + correctAnswer + '.';
                        feedback.className = 'quiz-feedback show wrong';
                    }

                    answeredCount++;
                    if (answeredCount === quizQuestions.length) {
                        document.getElementById('submitQuizBtn').disabled = false;
                    }
                });
            });
        });

        // Submit Quiz
        document.getElementById('submitQuizBtn').addEventListener('click', function() {
            if (quizSubmitted) return;
            quizSubmitted = true;

            const total = quizQuestions.length;
            const scoreDisplay = document.getElementById('quizScore');
            const scoreValue = document.getElementById('scoreValue');
            const scoreMessage = document.getElementById('scoreMessage');

            scoreValue.textContent = score + ' / ' + total;
            scoreDisplay.classList.add('show');

            const percentage = Math.round((score / total) * 100);
            if (percentage >= 75) {
                scoreMessage.textContent = '🎉 Great job! You passed with ' + percentage + '%.';
                scoreMessage.style.color = '#00ff9d';
            } else {
                scoreMessage.textContent = '📚 Keep studying! You scored ' + percentage + '%. Try again.';
                scoreMessage.style.color = '#ffaa00';
            }

            document.getElementById('submitQuizBtn').style.display = 'none';
        });

        // Reset Quiz
        document.getElementById('resetQuizBtn').addEventListener('click', function() {
            location.reload();
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

        // Placeholder for modal (not used in this page but kept for consistency)
        function closeModal() {
            document.getElementById('detailModal').style.display = 'none';
            document.body.classList.remove('modal-open');
        }
    </script>
     <?php if (file_exists(__DIR__ . '/includes/brightness_widget.php')) { $global_brightness = function_exists('getGlobalBrightness') ? getGlobalBrightness() : 100; include __DIR__ . '/includes/brightness_widget.php'; } ?>
</body>
</html>
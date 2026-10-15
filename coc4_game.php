<?php
// coc4_game.php
// COC 4 PC Building Simulator - ULTRA HARDCORE MODE
// Responsive Desktop / Tablet / Mobile Layout
// Database Save + Dark/Light Mode + Brightness

require_once 'config.php';

/* =========================================================
   LOGIN CHECK
   ========================================================= */

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$coc = 4;
$user_id = $_SESSION['user_id'];

$page_title = "COC 4: PC Building Simulator - STRESS TEST MODE";

$username = $_SESSION['full_name']
    ?? $_SESSION['username']
    ?? 'User';

/* =========================================================
   GET HIGH SCORE / GAMES PLAYED
   ========================================================= */

$high_score = 0;
$games_played = 0;

$score_query = "
    SELECT high_score, games_played
    FROM user_game_scores
    WHERE user_id = ?
    AND coc_number = ?
";

$stmt = $conn->prepare($score_query);

if ($stmt) {

    $stmt->bind_param("ii", $user_id, $coc);
    $stmt->execute();

    $result = $stmt->get_result();

    if ($result && $result->num_rows > 0) {

        $user_data = $result->fetch_assoc();

        $high_score = (int) ($user_data['high_score'] ?? 0);
        $games_played = (int) ($user_data['games_played'] ?? 0);
    }

    $stmt->close();
}

/* =========================================================
   GLOBAL BRIGHTNESS
   ========================================================= */

$global_brightness = 100;

if (function_exists('getGlobalBrightness')) {
    $global_brightness = (int) getGlobalBrightness();
}

$global_brightness = max(60, min(150, $global_brightness));

?>
<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no"
    >

    <meta name="theme-color" content="#050812">

    <title>
        <?php echo htmlspecialchars($page_title); ?>
        | CSS TESDA Reviewer
    </title>

    <link
        href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&family=Rajdhani:wght@300;400;600;700&display=swap"
        rel="stylesheet"
    >

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css"
    >

    <?php
    if (file_exists(__DIR__ . '/includes/theme_preload.php')) {
        include __DIR__ . '/includes/theme_preload.php';
    }
    ?>

<style>

/* =========================================================
   GLOBAL RESET
   ========================================================= */

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

html {
    width: 100%;
    min-height: 100%;
    scroll-behavior: smooth;
}

body {
    width: 100%;
    min-height: 100vh;

    font-family: 'Rajdhani', sans-serif;

    background: #0a0f1e;
    color: #e0e0ff;

    overflow-x: hidden;

    -webkit-text-size-adjust: 100%;
}

/* =========================================================
   BACKGROUND
   ========================================================= */

.cyber-bg {
    position: fixed;
    inset: 0;

    width: 100%;
    height: 100%;

    z-index: -2;

    background:
        radial-gradient(
            circle at 20% 20%,
            rgba(255, 68, 68, 0.08),
            transparent 35%
        ),
        radial-gradient(
            circle at 80% 80%,
            rgba(255, 0, 0, 0.06),
            transparent 35%
        ),
        linear-gradient(
            135deg,
            #0a0f1e 0%,
            #050812 100%
        );
}

.grid-overlay {
    position: fixed;
    inset: 0;

    width: 100%;
    height: 100%;

    background-image:
        linear-gradient(
            rgba(255, 68, 68, 0.035) 1px,
            transparent 1px
        ),
        linear-gradient(
            90deg,
            rgba(255, 68, 68, 0.035) 1px,
            transparent 1px
        );

    background-size: 50px 50px;

    z-index: -1;

    animation: gridMove 20s linear infinite;

    pointer-events: none;
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
    position: sticky;
    top: 0;

    width: 100%;

    z-index: 500;

    background: rgba(7, 12, 25, 0.96);

    backdrop-filter: blur(14px);
    -webkit-backdrop-filter: blur(14px);

    border-bottom:
        1px solid
        rgba(255, 68, 68, 0.22);

    padding: 12px 18px;
}

.nav-container {
    width: 100%;
    max-width: 1600px;

    margin: 0 auto;

    display: flex;

    align-items: center;
    justify-content: space-between;

    gap: 15px;
}

.logo {
    flex-shrink: 0;

    font-family: 'Orbitron', sans-serif;

    font-size: 1.2rem;
    font-weight: 900;

    background:
        linear-gradient(
            135deg,
            #ff5555,
            #cc0000
        );

    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;

    text-decoration: none;

    white-space: nowrap;
}

.nav-links {
    flex: 1;

    display: flex;

    justify-content: flex-end;
    align-items: center;

    gap: 12px;

    flex-wrap: wrap;
}

.nav-links a {
    color: #94a3b8;

    text-decoration: none;

    display: inline-flex;

    align-items: center;

    gap: 5px;

    font-size: 0.78rem;

    white-space: nowrap;

    transition: 0.25s ease;
}

.nav-links a:hover,
.nav-links a.active {
    color: #ff4444;
}

.user-info {
    display: flex;

    align-items: center;

    gap: 8px;

    color: #ff5555;

    white-space: nowrap;
}

/* =========================================================
   THEME BUTTON
   ========================================================= */

.theme-toggle {
    display: inline-flex;

    align-items: center;
    justify-content: center;

    gap: 7px;

    min-height: 34px;

    padding: 7px 11px;

    border:
        1px solid
        rgba(0, 255, 157, 0.35);

    border-radius: 999px;

    background:
        rgba(0, 255, 157, 0.08);

    color: #00ff9d;

    font-family: 'Rajdhani', sans-serif;

    font-weight: 600;

    cursor: pointer;

    transition: 0.25s ease;

    white-space: nowrap;

    touch-action: manipulation;
}

.theme-toggle:hover {
    background: #00ff9d;

    color: #050812;

    transform: translateY(-1px);

    box-shadow:
        0 5px 15px
        rgba(0, 255, 157, 0.25);
}

/* =========================================================
   MAIN CONTAINER
   ========================================================= */

.container {
    width: 100%;
    max-width: 1600px;

    margin: 0 auto;

    padding: 20px 20px 45px;
}

/* =========================================================
   GAME HEADER
   ========================================================= */

.game-header {
    width: 100%;

    text-align: center;

    margin-bottom: 16px;
}

.game-header h1 {
    font-family: 'Orbitron', sans-serif;

    font-size: clamp(
        1.2rem,
        2vw,
        1.9rem
    );

    line-height: 1.3;

    background:
        linear-gradient(
            135deg,
            #ff5555,
            #cc0000
        );

    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;

    margin-bottom: 7px;
}

.game-header p {
    color: #94a3b8;

    font-size: 0.76rem;

    line-height: 1.5;
}

/* =========================================================
   INSTRUCTIONS
   ========================================================= */

.instructions-panel {
    width: 100%;

    background:
        rgba(0, 0, 0, 0.45);

    border:
        1px solid
        rgba(255, 68, 68, 0.45);

    border-radius: 16px;

    padding: 14px;

    margin-bottom: 15px;

    display: grid;

    grid-template-columns:
        repeat(
            auto-fit,
            minmax(220px, 1fr)
        );

    gap: 10px;
}

.instruction-item {
    display: flex;

    align-items: center;

    gap: 9px;

    min-width: 0;

    color: #ffaa66;

    font-size: 0.78rem;
}

.instruction-icon {
    flex: 0 0 31px;

    width: 31px;
    height: 31px;

    display: flex;

    align-items: center;
    justify-content: center;

    background:
        rgba(255, 68, 68, 0.14);

    border:
        1px solid
        rgba(255, 68, 68, 0.25);

    border-radius: 50%;

    color: #ff5555;

    font-size: 0.8rem;

    font-weight: 700;
}

.instruction-text {
    min-width: 0;

    color: #cbd5e1;

    font-size: 0.78rem;

    line-height: 1.3;
}

.instruction-highlight {
    color: #ff5555;

    font-weight: 700;
}

/* =========================================================
   STATS
   ========================================================= */

.stats-bar {
    width: 100%;

    display: flex;

    justify-content: center;
    align-items: stretch;

    gap: 12px;

    margin-bottom: 14px;

    flex-wrap: wrap;
}

.stat-card {
    min-width: 145px;

    background:
        rgba(0, 0, 0, 0.45);

    border:
        1px solid
        rgba(255, 68, 68, 0.3);

    border-radius: 12px;

    padding: 8px 18px;

    text-align: center;
}

.stat-value {
    font-family: 'Orbitron', sans-serif;

    font-size: 1.3rem;

    font-weight: 700;

    color: #ff4444;
}

.stat-label {
    color: #94a3b8;

    font-size: 0.62rem;

    letter-spacing: 0.5px;
}

/* =========================================================
   STRESS BAR
   ========================================================= */

.stress-bar {
    width: 100%;

    display: grid;

    grid-template-columns:
        auto
        minmax(180px, 1fr)
        auto
        auto;

    align-items: center;

    gap: 13px;

    padding: 10px 14px;

    margin-bottom: 17px;

    background:
        rgba(0, 0, 0, 0.5);

    border:
        1px solid
        rgba(255, 68, 68, 0.18);

    border-radius: 14px;
}

.stress-meter {
    width: 100%;
    height: 18px;

    background:
        rgba(255, 0, 0, 0.13);

    border-radius: 20px;

    overflow: hidden;
}

.stress-fill {
    width: 0%;
    height: 100%;

    background:
        linear-gradient(
            90deg,
            #ffaa00,
            #ff0000
        );

    border-radius: 20px;

    transition: width 0.5s ease;
}

.timer-display {
    min-width: 75px;

    text-align: center;

    font-family: 'Orbitron', monospace;

    font-size: 1.15rem;

    font-weight: 700;

    color: #ff5555;
}

/* =========================================================
   MAIN SIMULATION
   ========================================================= */

.simulation-area {
    width: 100%;

    display: grid;

    grid-template-columns:
        minmax(0, 1fr)
        320px;

    gap: 18px;

    align-items: start;
}

/* =========================================================
   PC CASE
   ========================================================= */

.pc-case {
    width: 100%;

    min-width: 0;

    background:
        rgba(0, 0, 0, 0.48);

    border:
        2px solid
        rgba(255, 68, 68, 0.7);

    border-radius: 18px;

    padding: 17px;

    overflow: visible;

    box-shadow:
        0 0 25px
        rgba(255, 0, 0, 0.05);
}

.pc-case h3 {
    display: flex;

    align-items: center;

    gap: 9px;

    color: #ff8888;

    font-family: 'Orbitron', sans-serif;

    font-size: 0.9rem;

    margin-bottom: 13px;

    padding-bottom: 10px;

    border-bottom:
        1px solid
        rgba(255, 68, 68, 0.18);
}

/* =========================================================
   COMPONENTS
   ========================================================= */

#pcComponents {
    width: 100%;

    display: flex;

    flex-direction: column;

    gap: 9px;
}

.component-slot {
    width: 100%;

    min-width: 0;

    background:
        rgba(0, 0, 0, 0.42);

    border:
        1px solid
        rgba(255, 68, 68, 0.28);

    border-radius: 12px;

    padding: 10px;

    transition: 0.2s ease;
}

.component-slot:hover {
    border-color:
        rgba(255, 68, 68, 0.55);
}

.component-slot.damaged {
    border-color: #ff0000;

    background:
        rgba(255, 0, 0, 0.1);
}

.component-slot.installed {
    border-color: #00ff9d;

    background:
        rgba(0, 255, 157, 0.045);
}

.component-header {
    width: 100%;

    display: flex;

    align-items: center;

    justify-content: space-between;

    gap: 8px;

    margin-bottom: 7px;

    flex-wrap: wrap;
}

.component-name {
    min-width: 0;

    display: flex;

    align-items: center;

    gap: 8px;

    font-size: 0.9rem;

    font-weight: 700;

    overflow-wrap: anywhere;
}

.component-name i {
    color: #ff5555;

    flex-shrink: 0;
}

.component-status {
    flex-shrink: 0;

    padding: 3px 8px;

    border-radius: 20px;

    font-size: 0.63rem;

    font-weight: 700;

    white-space: nowrap;
}

.status-missing {
    background:
        rgba(255, 0, 0, 0.14);

    color: #ff6666;
}

.status-installed {
    background:
        rgba(0, 255, 157, 0.14);

    color: #00ff9d;
}

.status-damaged {
    background:
        rgba(255, 0, 0, 0.3);

    color: #ff2222;

    animation: blink 0.5s infinite;
}

.status-progress {
    background:
        rgba(255, 170, 0, 0.14);

    color: #ffaa00;
}

/* =========================================================
   STEPS
   ========================================================= */

.steps-container {
    width: 100%;

    display: flex;

    flex-direction: column;

    gap: 5px;

    margin-top: 5px;
}

.step-item {
    width: 100%;

    min-width: 0;

    display: grid;

    grid-template-columns:
        24px
        minmax(0, 1fr)
        auto;

    align-items: center;

    gap: 7px;

    min-height: 35px;

    padding: 5px 7px;

    background:
        rgba(0, 0, 0, 0.28);

    border-radius: 7px;

    font-size: 0.72rem;
}

.step-item.completed {
    opacity: 0.6;

    background:
        rgba(0, 255, 0, 0.04);
}

.step-check {
    width: 22px;
    height: 22px;

    display: flex;

    align-items: center;
    justify-content: center;

    border-radius: 50%;

    background:
        rgba(255, 68, 68, 0.12);

    color: #ff6666;

    font-size: 0.62rem;

    flex-shrink: 0;
}

.step-text {
    min-width: 0;

    line-height: 1.3;

    overflow-wrap: anywhere;
}

.step-btn {
    flex-shrink: 0;

    background:
        linear-gradient(
            45deg,
            #ff4444,
            #cc0000
        );

    border: none;

    padding: 6px 9px;

    min-height: 30px;

    border-radius: 15px;

    color: white;

    cursor: pointer;

    font-family: 'Rajdhani', sans-serif;

    font-size: 0.64rem;

    font-weight: 600;

    white-space: nowrap;

    touch-action: manipulation;

    transition: 0.2s ease;
}

.step-btn:hover {
    transform: translateY(-1px);

    box-shadow:
        0 4px 12px
        rgba(255, 0, 0, 0.22);
}

.step-btn:active {
    transform: scale(0.97);
}

.step-btn:disabled {
    opacity: 0.5;

    cursor: not-allowed;
}

/* =========================================================
   TOOLS
   ========================================================= */

.tools-panel {
    width: 100%;

    background:
        rgba(0, 0, 0, 0.48);

    border:
        1px solid
        rgba(255, 68, 68, 0.32);

    border-radius: 18px;

    padding: 15px;

    position: sticky;

    top: 80px;

    max-height:
        calc(100vh - 100px);

    overflow-y: auto;

    scrollbar-width: thin;
}

.tools-panel h3 {
    display: flex;

    align-items: center;

    gap: 8px;

    color: #ff8888;

    font-family: 'Orbitron', sans-serif;

    font-size: 0.85rem;

    margin-bottom: 12px;

    padding-bottom: 9px;

    border-bottom:
        1px solid
        rgba(255, 68, 68, 0.18);
}

.tool-btn {
    width: 100%;

    min-height: 44px;

    background:
        rgba(0, 0, 0, 0.42);

    border:
        1px solid
        rgba(255, 68, 68, 0.28);

    border-radius: 10px;

    padding: 8px 10px;

    margin-bottom: 8px;

    display: flex;

    align-items: center;

    gap: 10px;

    color: #e0e0ff;

    cursor: pointer;

    font-size: 0.76rem;

    touch-action: manipulation;

    transition: 0.2s ease;
}

.tool-btn:hover {
    background:
        rgba(255, 68, 68, 0.1);

    border-color:
        rgba(255, 68, 68, 0.55);

    transform: translateX(-2px);
}

.tool-btn:active {
    transform: scale(0.98);
}

.tool-icon {
    width: 28px;
    height: 28px;

    flex: 0 0 28px;

    display: flex;

    align-items: center;
    justify-content: center;

    border-radius: 7px;

    background:
        rgba(255, 68, 68, 0.12);

    color: #ff5555;
}

/* =========================================================
   DIAGNOSTIC
   ========================================================= */

.diagnostic-result {
    width: 100%;

    background:
        rgba(0, 0, 0, 0.48);

    border:
        1px solid
        rgba(255, 68, 68, 0.15);

    border-radius: 10px;

    padding: 10px;

    margin-top: 12px;

    font-size: 0.7rem;

    line-height: 1.45;

    max-height: 240px;

    overflow-y: auto;

    overflow-wrap: anywhere;
}

/* =========================================================
   POWER
   ========================================================= */

.power-section {
    width: 100%;

    background:
        rgba(0, 0, 0, 0.45);

    border:
        1px solid
        rgba(255, 68, 68, 0.18);

    border-radius: 12px;

    padding: 13px;

    text-align: center;

    margin-top: 13px;
}

.power-btn {
    background:
        linear-gradient(
            135deg,
            #ff4444,
            #cc0000
        );

    border: none;

    padding: 10px 18px;

    min-height: 40px;

    border-radius: 30px;

    color: white;

    font-family: 'Rajdhani', sans-serif;

    font-weight: 700;

    cursor: pointer;

    margin-top: 7px;

    touch-action: manipulation;

    transition: 0.2s ease;
}

.power-btn:hover {
    transform: translateY(-1px);

    box-shadow:
        0 5px 15px
        rgba(255, 0, 0, 0.25);
}

/* =========================================================
   LED
   ========================================================= */

.led-green {
    display: inline-block;

    width: 10px;
    height: 10px;

    background: #00ff9d;

    border-radius: 50%;

    animation: pulse 1s infinite;

    margin-right: 5px;

    box-shadow:
        0 0 8px #00ff9d;
}

.led-red {
    display: inline-block;

    width: 10px;
    height: 10px;

    background: #ff4444;

    border-radius: 50%;

    margin-right: 5px;

    box-shadow:
        0 0 8px #ff4444;
}

@keyframes pulse {

    0%, 100% {
        opacity: 1;
    }

    50% {
        opacity: 0.3;
    }
}

@keyframes blink {

    0%, 100% {
        opacity: 1;
    }

    50% {
        opacity: 0.5;
    }
}

/* =========================================================
   SUCCESS
   ========================================================= */

.success-message {
    width: 100%;

    background:
        rgba(0, 255, 0, 0.1);

    border:
        1px solid
        rgba(0, 255, 157, 0.25);

    padding: 17px;

    border-radius: 14px;

    text-align: center;

    margin-top: 13px;

    overflow-wrap: anywhere;
}

.success-message h2 {
    font-family: 'Orbitron', sans-serif;

    font-size: 1rem;

    margin: 8px 0;
}

/* =========================================================
   GAME OVER
   ========================================================= */

.game-over {
    position: fixed;

    inset: 0;

    width: 100%;
    height: 100%;

    z-index: 2147482500;

    background:
        rgba(0, 0, 0, 0.94);

    display: flex;

    align-items: center;
    justify-content: center;

    padding: 15px;

    overflow-y: auto;
}

.game-over > div {
    width: min(
        520px,
        100%
    );

    max-height: 90vh;

    overflow-y: auto;

    padding: 28px !important;

    border-radius: 20px !important;

    text-align: center;

    overflow-wrap: anywhere;
}

/* =========================================================
   TOAST
   ========================================================= */

.toast {
    position: fixed;

    left: 50%;
    bottom: 22px;

    transform:
        translateX(-50%);

    width: max-content;

    max-width: 90vw;

    padding: 11px 20px;

    border-radius: 50px;

    display: none;

    z-index: 2147482600;

    font-weight: 700;

    text-align: center;

    overflow-wrap: anywhere;
}

/* =========================================================
   SCROLLBARS
   ========================================================= */

.tools-panel::-webkit-scrollbar,
.diagnostic-result::-webkit-scrollbar,
.game-over > div::-webkit-scrollbar {
    width: 6px;
}

.tools-panel::-webkit-scrollbar-thumb,
.diagnostic-result::-webkit-scrollbar-thumb,
.game-over > div::-webkit-scrollbar-thumb {
    background:
        rgba(255, 68, 68, 0.45);

    border-radius: 10px;
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
            circle at 20% 20%,
            #ffffff,
            transparent 40%
        ),
        linear-gradient(
            135deg,
            #f8fafc,
            #eaf1f7
        );
}

body.light-mode .grid-overlay {
    background-image:
        linear-gradient(
            rgba(0, 150, 105, 0.055) 1px,
            transparent 1px
        ),
        linear-gradient(
            90deg,
            rgba(0, 150, 105, 0.055) 1px,
            transparent 1px
        );
}

body.light-mode .navbar {
    background:
        rgba(255, 255, 255, 0.95);

    border-bottom-color:
        rgba(0, 150, 105, 0.25);
}

body.light-mode .nav-links a {
    color: #526174;
}

body.light-mode .nav-links a:hover,
body.light-mode .nav-links a.active {
    color: #008a65;
}

body.light-mode .user-info {
    color: #008a65;
}

body.light-mode .game-header p {
    color: #526174;
}

body.light-mode .instructions-panel,
body.light-mode .stat-card,
body.light-mode .stress-bar,
body.light-mode .pc-case,
body.light-mode .tools-panel,
body.light-mode .diagnostic-result,
body.light-mode .power-section,
body.light-mode .component-slot,
body.light-mode .step-item,
body.light-mode .tool-btn {
    background:
        rgba(255, 255, 255, 0.88);

    color: #172033;
}

body.light-mode .instructions-panel,
body.light-mode .pc-case,
body.light-mode .tools-panel {
    border-color:
        rgba(0, 150, 105, 0.28);
}

body.light-mode .instruction-text {
    color: #526174;
}

body.light-mode .instruction-highlight,
body.light-mode .game-header h1 {
    color: #008a65;
}

body.light-mode .pc-case h3,
body.light-mode .tools-panel h3 {
    color: #008a65;
}

body.light-mode .stat-card {
    border-color:
        rgba(0, 150, 105, 0.3);
}

body.light-mode .stat-value {
    color: #008a65;
}

body.light-mode .stat-label {
    color: #526174;
}

body.light-mode .component-slot {
    border-color:
        rgba(0, 150, 105, 0.25);
}

body.light-mode .step-item,
body.light-mode .tool-btn {
    border-color:
        rgba(0, 150, 105, 0.18);
}

body.light-mode .tool-btn {
    color: #172033;
}

body.light-mode .tool-btn:hover {
    background:
        rgba(0, 150, 105, 0.08);
}

body.light-mode .diagnostic-result {
    color: #172033;
}

body.light-mode .theme-toggle {
    border-color:
        rgba(0, 130, 95, 0.35);

    background:
        rgba(0, 150, 105, 0.08);

    color: #008a65;
}

body.light-mode .theme-toggle:hover {
    background: #00a878;
    color: #ffffff;
}

/* =========================================================
   TABLET
   ========================================================= */

@media (max-width: 1200px) {

    .nav-links {
        gap: 9px;
    }

    .nav-links a {
        font-size: 0.72rem;
    }

    .simulation-area {
        grid-template-columns:
            minmax(0, 1fr)
            290px;
    }

    .container {
        padding: 18px;
    }
}

/* =========================================================
   SMALL LAPTOP / TABLET
   ========================================================= */

@media (max-width: 1000px) {

    .nav-container {
        flex-direction: column;

        align-items: center;
    }

    .logo {
        text-align: center;
    }

    .nav-links {
        width: 100%;

        justify-content: center;

        gap: 9px;
    }

    .simulation-area {
        grid-template-columns: 1fr;
    }

    .tools-panel {
        position: static;

        max-height: none;

        display: grid;

        grid-template-columns:
            repeat(
                2,
                minmax(0, 1fr)
            );

        gap: 8px;
    }

    .tools-panel h3 {
        grid-column: 1 / -1;

        margin-bottom: 2px;
    }

    .tool-btn {
        margin-bottom: 0;
    }

    .diagnostic-result {
        grid-column: 1 / -1;

        max-height: 220px;
    }
}

/* =========================================================
   MOBILE
   ========================================================= */

@media (max-width: 700px) {

    body {
        overflow-x: hidden;
    }

    /* -----------------------------------------------------
       NAVBAR
       ----------------------------------------------------- */

    .navbar {
        position: relative;

        padding: 10px 9px;
    }

    .nav-container {
        width: 100%;

        flex-direction: column;

        gap: 9px;
    }

    .logo {
        width: 100%;

        font-size: 1rem;

        text-align: center;
    }

    .nav-links {
        width: 100%;

        display: flex;

        justify-content: center;

        align-items: center;

        gap: 6px;

        flex-wrap: wrap;
    }

    .nav-links > a {
        padding: 4px 3px;

        font-size: 0.67rem;
    }

    .user-info {
        width: 100%;

        justify-content: center;

        flex-wrap: wrap;

        gap: 7px;

        font-size: 0.76rem;
    }

    .theme-toggle {
        min-height: 36px;

        padding: 7px 10px;

        font-size: 0.72rem;
    }

    /* -----------------------------------------------------
       MAIN
       ----------------------------------------------------- */

    .container {
        width: 100%;

        padding:
            13px
            9px
            40px;
    }

    /* -----------------------------------------------------
       HEADER
       ----------------------------------------------------- */

    .game-header {
        margin-bottom: 13px;
    }

    .game-header h1 {
        font-size: 1.05rem;

        line-height: 1.4;
    }

    .game-header p {
        font-size: 0.63rem;

        line-height: 1.6;
    }

    /* -----------------------------------------------------
       INSTRUCTIONS
       ----------------------------------------------------- */

    .instructions-panel {
        grid-template-columns: 1fr;

        gap: 8px;

        padding: 11px;

        border-radius: 13px;
    }

    .instruction-item {
        align-items: flex-start;

        font-size: 0.7rem;
    }

    .instruction-icon {
        width: 28px;
        height: 28px;

        flex-basis: 28px;

        font-size: 0.7rem;
    }

    .instruction-text {
        font-size: 0.71rem;

        line-height: 1.35;
    }

    /* -----------------------------------------------------
       STATS
       ----------------------------------------------------- */

    .stats-bar {
        width: 100%;

        display: grid;

        grid-template-columns:
            repeat(
                2,
                minmax(0, 1fr)
            );

        gap: 8px;
    }

    .stat-card {
        min-width: 0;

        padding: 8px 5px;
    }

    .stat-value {
        font-size: 1.05rem;
    }

    .stat-label {
        font-size: 0.56rem;
    }

    /* -----------------------------------------------------
       STRESS
       ----------------------------------------------------- */

    .stress-bar {
        width: 100%;

        display: grid;

        grid-template-columns:
            1fr
            1fr;

        gap: 9px;

        padding: 10px;

        border-radius: 12px;
    }

    .stress-bar > div:first-child {
        font-size: 0.72rem;
    }

    .stress-bar > div:last-child {
        justify-self: end;

        font-size: 0.72rem;
    }

    .stress-meter {
        grid-column: 1 / -1;

        grid-row: 2;

        height: 15px;
    }

    .timer-display {
        min-width: 0;

        justify-self: start;

        font-size: 0.95rem;
    }

    /* -----------------------------------------------------
       SIMULATION
       ----------------------------------------------------- */

    .simulation-area {
        width: 100%;

        display: flex;

        flex-direction: column;

        gap: 12px;
    }

    /* -----------------------------------------------------
       PC CASE
       ----------------------------------------------------- */

    .pc-case {
        width: 100%;

        padding: 11px;

        border-radius: 14px;
    }

    .pc-case h3 {
        font-size: 0.78rem;

        margin-bottom: 10px;
    }

    #pcComponents {
        gap: 7px;
    }

    /* -----------------------------------------------------
       COMPONENT
       ----------------------------------------------------- */

    .component-slot {
        padding: 8px;

        border-radius: 10px;
    }

    .component-header {
        align-items: flex-start;

        gap: 6px;
    }

    .component-name {
        flex: 1;

        font-size: 0.78rem;

        line-height: 1.3;
    }

    .component-status {
        font-size: 0.55rem;

        padding: 3px 6px;
    }

    /* -----------------------------------------------------
       STEP
       ----------------------------------------------------- */

    .step-item {
        display: grid;

        grid-template-columns:
            22px
            minmax(0, 1fr);

        align-items: start;

        gap: 6px;

        padding: 7px;

        min-height: 0;

        font-size: 0.68rem;

        line-height: 1.35;
    }

    .step-check {
        width: 21px;
        height: 21px;
    }

    .step-text {
        padding-top: 2px;
    }

    .step-btn {
        grid-column: 2;

        justify-self: stretch;

        width: 100%;

        min-height: 36px;

        margin-top: 2px;

        padding: 7px 8px;

        font-size: 0.65rem;

        border-radius: 8px;
    }

    /* -----------------------------------------------------
       POWER
       ----------------------------------------------------- */

    .power-section {
        padding: 11px;

        font-size: 0.75rem;
    }

    .power-btn {
        width: 100%;

        min-height: 42px;

        font-size: 0.75rem;
    }

    /* -----------------------------------------------------
       TOOLS
       ----------------------------------------------------- */

    .tools-panel {
        width: 100%;

        display: flex;

        flex-direction: column;

        padding: 11px;

        border-radius: 14px;

        max-height: none;
    }

    .tools-panel h3 {
        width: 100%;

        font-size: 0.78rem;

        margin-bottom: 9px;
    }

    .tool-btn {
        width: 100%;

        min-height: 45px;

        margin-bottom: 7px;

        padding: 8px;

        font-size: 0.73rem;
    }

    .tool-icon {
        width: 30px;
        height: 30px;

        flex-basis: 30px;
    }

    .diagnostic-result {
        width: 100%;

        max-height: 210px;

        margin-top: 5px;

        font-size: 0.67rem;
    }

    /* -----------------------------------------------------
       SUCCESS
       ----------------------------------------------------- */

    .success-message {
        padding: 14px;

        font-size: 0.75rem;
    }

    .success-message h2 {
        font-size: 0.85rem;
    }

    /* -----------------------------------------------------
       GAME OVER
       ----------------------------------------------------- */

    .game-over {
        padding: 10px;
    }

    .game-over > div {
        width: 100%;

        max-width: 430px;

        max-height: 90vh;

        padding: 20px !important;
    }

    /* -----------------------------------------------------
       TOAST
       ----------------------------------------------------- */

    .toast {
        bottom: 15px;

        max-width: 92vw;

        padding: 10px 15px;

        font-size: 0.75rem;
    }
}

/* =========================================================
   SMALL PHONES
   ========================================================= */

@media (max-width: 430px) {

    .nav-links {
        gap: 4px;
    }

    .nav-links > a {
        font-size: 0.61rem;
    }

    .user-info {
        font-size: 0.7rem;
    }

    .theme-toggle {
        font-size: 0.65rem;

        padding: 6px 8px;
    }

    .container {
        padding-left: 7px;
        padding-right: 7px;
    }

    .game-header h1 {
        font-size: 0.95rem;
    }

    .game-header p {
        font-size: 0.58rem;
    }

    .instructions-panel {
        padding: 9px;
    }

    .instruction-item {
        gap: 7px;
    }

    .instruction-icon {
        width: 26px;
        height: 26px;

        flex-basis: 26px;
    }

    .instruction-text {
        font-size: 0.67rem;
    }

    .stat-value {
        font-size: 0.95rem;
    }

    .stat-label {
        font-size: 0.52rem;
    }

    .component-name {
        font-size: 0.73rem;
    }

    .component-status {
        font-size: 0.51rem;
    }

    .step-item {
        font-size: 0.64rem;
    }

    .step-btn {
        font-size: 0.62rem;

        min-height: 35px;
    }
}

/* =========================================================
   EXTRA SMALL PHONES
   ========================================================= */

@media (max-width: 360px) {

    .nav-links > a {
        font-size: 0.56rem;
    }

    .logo {
        font-size: 0.88rem;
    }

    .game-header h1 {
        font-size: 0.88rem;
    }

    .stress-bar {
        padding: 8px;
    }

    .component-slot {
        padding: 7px;
    }

    .step-item {
        padding: 6px;

        font-size: 0.61rem;
    }

    .step-btn {
        font-size: 0.58rem;
    }
}

</style>

</head>

<body>

<div class="cyber-bg"></div>
<div class="grid-overlay"></div>

<!-- =======================================================
     NAVBAR
     ======================================================= -->

<nav class="navbar">

    <div class="nav-container">

        <a href="dashboard.php" class="logo">
            CSS TESDA Reviewer
        </a>

        <div class="nav-links">

            <a href="dashboard.php">
                <i class="fas fa-tachometer-alt"></i>
                Dashboard
            </a>

            <a href="coc1_review.php">
                <i class="fas fa-microchip"></i>
                COC 1
            </a>

            <a href="coc2_review.php">
                <i class="fas fa-network-wired"></i>
                COC 2
            </a>

            <a href="coc3_review.php">
                <i class="fas fa-server"></i>
                COC 3
            </a>

            <a href="coc4_review.php">
                <i class="fas fa-tools"></i>
                COC 4
            </a>

            <a
                href="coc4_game.php"
                class="active"
            >
                <i class="fas fa-skull-crossbones"></i>
                Stress Test
            </a>

            <div class="user-info">

                <i class="fas fa-user"></i>

                <span>
                    <?php echo htmlspecialchars($username); ?>
                </span>

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
                    style="color:#ef4444;"
                    aria-label="Logout"
                    title="Logout"
                >
                    <i class="fas fa-sign-out-alt"></i>
                </a>

            </div>

        </div>

    </div>

</nav>

<!-- =======================================================
     MAIN
     ======================================================= -->

<div class="container">

    <div class="game-header">

        <h1>
            <i class="fas fa-skull-crossbones"></i>
            PC Building Simulator -
            STRESS TEST MODE
        </h1>

        <p>
            ⏱️ TIMED INSTALLATION |
            💀 PERMANENT DAMAGE |
            🔥 OVERHEATING |
            ⚡ ESD RISK |
            📦 WRONG PARTS
        </p>

    </div>

    <!-- ===================================================
         STATS
         =================================================== -->

    <div class="stats-bar">

        <div class="stat-card">

            <div
                class="stat-value"
                id="highScoreDisplay"
            >
                <?php echo $high_score; ?>
            </div>

            <div class="stat-label">
                HIGH SCORE
            </div>

        </div>

        <div class="stat-card">

            <div
                class="stat-value"
                id="gamesPlayedDisplay"
            >
                <?php echo $games_played; ?>
            </div>

            <div class="stat-label">
                GAMES PLAYED
            </div>

        </div>

    </div>

    <!-- ===================================================
         INSTRUCTIONS
         =================================================== -->

    <div class="instructions-panel">

        <div class="instruction-item">

            <div class="instruction-icon">
                ①
            </div>

            <div class="instruction-text">
                Click
                <span class="instruction-highlight">
                    STEP BUTTONS
                </span>
                to install each component step-by-step
            </div>

        </div>

        <div class="instruction-item">

            <div class="instruction-icon">
                ②
            </div>

            <div class="instruction-text">
                <span class="instruction-highlight">
                    RISK PERCENTAGES
                </span>
                shown - higher risk = more likely to fail
            </div>

        </div>

        <div class="instruction-item">

            <div class="instruction-icon">
                ③
            </div>

            <div class="instruction-text">
                Use
                <span class="instruction-highlight">
                    EMERGENCY TOOLS
                </span>
                to diagnose and fix issues
            </div>

        </div>

        <div class="instruction-item">

            <div class="instruction-icon">
                ④
            </div>

            <div class="instruction-text">
                <span class="instruction-highlight">
                    ESD WRIST STRAP
                </span>
                prevents static damage
            </div>

        </div>

        <div class="instruction-item">

            <div class="instruction-icon">
                ⑤
            </div>

            <div class="instruction-text">
                <span class="instruction-highlight">
                    POWER ON
                </span>
                only after essential components are installed
            </div>

        </div>

        <div class="instruction-item">

            <div class="instruction-icon">
                🏆
            </div>

            <div class="instruction-text">
                Install
                <span class="instruction-highlight">
                    ALL 7 components
                </span>
                without dying to WIN!
            </div>

        </div>

    </div>

    <!-- ===================================================
         STRESS / TIMER
         =================================================== -->

    <div class="stress-bar">

        <div>
            <i class="fas fa-heartbeat"></i>

            <span id="lives">
                3
            </span>

            Lives
        </div>

        <div class="stress-meter">

            <div
                class="stress-fill"
                id="stressFill"
            ></div>

        </div>

        <div
            class="timer-display"
            id="timerDisplay"
        >
            05:00
        </div>

        <div>
            <i class="fas fa-fire"></i>

            <span id="tempDisplay">
                45°C
            </span>
        </div>

    </div>

    <!-- ===================================================
         SIMULATION AREA
         =================================================== -->

    <div class="simulation-area">

        <!-- =================================================
             PC CASE
             ================================================= -->

        <div class="pc-case">

            <h3>
                <i class="fas fa-desktop"></i>
                Computer Case - ATX
            </h3>

            <div id="pcComponents"></div>

            <!-- POWER -->

            <div class="power-section">

                <span
                    id="powerLed"
                    class="led-red"
                ></span>

                Status:

                <span id="powerStatus">
                    OFF
                </span>

                <br>

                <button
                    class="power-btn"
                    id="powerBtn"
                    onclick="togglePower()"
                >
                    <i class="fas fa-power-off"></i>
                    POWER ON
                </button>

            </div>

            <div id="bootResult"></div>

        </div>

        <!-- =================================================
             TOOLS
             ================================================= -->

        <div class="tools-panel">

            <h3>
                <i class="fas fa-toolbox"></i>
                Emergency Tools
            </h3>

            <div
                class="tool-btn"
                onclick="runDiagnostic()"
            >

                <div class="tool-icon">
                    <i class="fas fa-stethoscope"></i>
                </div>

                <div>
                    Full Diagnostic
                </div>

            </div>

            <div
                class="tool-btn"
                onclick="checkCompatibility()"
            >

                <div class="tool-icon">
                    <i class="fas fa-microchip"></i>
                </div>

                <div>
                    Compatibility Check
                </div>

            </div>

            <div
                class="tool-btn"
                onclick="testPSU()"
            >

                <div class="tool-icon">
                    <i class="fas fa-plug"></i>
                </div>

                <div>
                    PSU Tester
                </div>

            </div>

            <div
                class="tool-btn"
                onclick="emergencyCool()"
            >

                <div class="tool-icon">
                    <i class="fas fa-snowflake"></i>
                </div>

                <div>
                    Emergency Cooling
                </div>

            </div>

            <div
                class="tool-btn"
                onclick="useESDWriststrap()"
            >

                <div class="tool-icon">
                    <i class="fas fa-hand-paper"></i>
                </div>

                <div>
                    Use ESD Wrist Strap
                </div>

            </div>

            <div
                id="diagnosticResult"
                class="diagnostic-result"
            >
                <i class="fas fa-info-circle"></i>
                Ready...
            </div>

        </div>

    </div>

</div>

<div
    id="toast"
    class="toast"
></div>

<script>

/* =========================================================
   ULTRA HARDCORE GAME
   ========================================================= */

let lives = 3;

let stressLevel = 0;

let timeLeft = 300;

let timerInterval;

let temperature = 45;

let tempInterval;

let esdProtected = false;

let gameActive = true;

let gameSaved = false;

let finalScore = 0;

let highScore =
    <?php echo (int)$high_score; ?>;

let gamesPlayed =
    <?php echo (int)$games_played; ?>;

/* =========================================================
   TOAST
   ========================================================= */

function showToast(
    msg,
    isError = false
) {

    const toast =
        document.getElementById('toast');

    if (!toast) return;

    toast.textContent = msg;

    toast.style.background =
        isError
            ? '#ef4444'
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

/* =========================================================
   SAVE SCORE
   ========================================================= */

function saveScore() {

    if (gameSaved) {

        showToast(
            'Score already saved!',
            false
        );

        return;
    }

    showToast(
        'Saving score...',
        false
    );

    fetch(
        'save_game_score.php',
        {
            method: 'POST',

            headers: {
                'Content-Type':
                    'application/x-www-form-urlencoded'
            },

            body:
                'coc=4' +
                '&score=' +
                encodeURIComponent(finalScore) +
                '&games_played=1'
        }
    )

    .then(response => {

        if (!response.ok) {
            throw new Error(
                'HTTP ' + response.status
            );
        }

        return response.json();
    })

    .then(data => {

        console.log(
            'Save response:',
            data
        );

        if (data.success) {

            gameSaved = true;

            showToast(
                '✅ Score ' +
                finalScore +
                ' saved to database!',
                false
            );

            const highScoreDisplay =
                document.getElementById(
                    'highScoreDisplay'
                );

            const gamesPlayedDisplay =
                document.getElementById(
                    'gamesPlayedDisplay'
                );

            if (highScoreDisplay) {

                highScoreDisplay.textContent =
                    data.high_score;
            }

            if (gamesPlayedDisplay) {

                gamesPlayedDisplay.textContent =
                    data.games_played;
            }

        } else {

            showToast(
                '❌ Error saving score: ' +
                (
                    data.error ||
                    'Unknown error'
                ),
                true
            );
        }

    })

    .catch(error => {

        console.error(
            'Error:',
            error
        );

        showToast(
            '❌ Network error saving score',
            true
        );
    });
}

/* =========================================================
   COMPONENTS
   ========================================================= */

let components = {

    motherboard: {

        installed: false,
        damaged: false,

        name: 'Motherboard',
        icon: 'fa-microchip',
        part: 'ASUS ROG B550-F',

        steps: [

            {
                id: 1,
                text: 'Install I/O Shield',
                completed: false,
                action: 'Install I/O Shield',
                risk: 15
            },

            {
                id: 2,
                text: 'Install standoffs (9 positions)',
                completed: false,
                action: 'Install 9 standoffs',
                risk: 20
            },

            {
                id: 3,
                text: 'Place motherboard on standoffs',
                completed: false,
                action: 'Position board',
                risk: 10
            },

            {
                id: 4,
                text: 'Screw in 9 screws (cross pattern)',
                completed: false,
                action: 'Tighten screws',
                risk: 25
            }

        ],

        currentStep: 0,
        temp: 35
    },

    cpu: {

        installed: false,
        damaged: false,

        name: 'CPU',
        icon: 'fa-microchip',
        part: 'AMD Ryzen 7 5800X',

        steps: [

            {
                id: 1,
                text: 'Lift CPU lever',
                completed: false,
                action: 'Open socket',
                risk: 10
            },

            {
                id: 2,
                text: 'Align gold triangle (CORRECT orientation!)',
                completed: false,
                action: 'Align CPU',
                risk: 40
            },

            {
                id: 3,
                text: 'Gently place CPU - NO FORCE!',
                completed: false,
                action: 'Insert CPU',
                risk: 50
            },

            {
                id: 4,
                text: 'Lower and lock lever',
                completed: false,
                action: 'Lock socket',
                risk: 15
            }

        ],

        currentStep: 0,
        temp: 45
    },

    cooler: {

        installed: false,
        damaged: false,

        name: 'CPU Cooler',
        icon: 'fa-fan',
        part: 'Noctua NH-D15',

        steps: [

            {
                id: 1,
                text: 'Apply thermal paste (pea-sized)',
                completed: false,
                action: 'Apply paste',
                risk: 30
            },

            {
                id: 2,
                text: 'Position cooler over CPU',
                completed: false,
                action: 'Position cooler',
                risk: 10
            },

            {
                id: 3,
                text: 'Tighten screws cross-pattern',
                completed: false,
                action: 'Screw cooler',
                risk: 35
            },

            {
                id: 4,
                text: 'Connect CPU fan header',
                completed: false,
                action: 'Connect fan',
                risk: 5
            }

        ],

        currentStep: 0,
        temp: 38
    },

    ram: {

        installed: false,
        damaged: false,

        name: 'RAM',
        icon: 'fa-memory',
        part: '32GB DDR4-3600',

        steps: [

            {
                id: 1,
                text: 'Open DIMM slot clips',
                completed: false,
                action: 'Open slots',
                risk: 5
            },

            {
                id: 2,
                text: 'Align notch with slot (correct direction!)',
                completed: false,
                action: 'Align RAM',
                risk: 30
            },

            {
                id: 3,
                text: 'Press firmly until clips click',
                completed: false,
                action: 'Insert RAM',
                risk: 25
            }

        ],

        currentStep: 0,
        temp: 30
    },

    gpu: {

        installed: false,
        damaged: false,

        name: 'GPU',
        icon: 'fa-tv',
        part: 'NVIDIA RTX 4070 Ti',

        steps: [

            {
                id: 1,
                text: 'Remove PCIe slot covers',
                completed: false,
                action: 'Remove covers',
                risk: 10
            },

            {
                id: 2,
                text: 'Align GPU with PCIe x16 slot',
                completed: false,
                action: 'Align GPU',
                risk: 15
            },

            {
                id: 3,
                text: 'Press until latch clicks',
                completed: false,
                action: 'Insert GPU',
                risk: 20
            },

            {
                id: 4,
                text: 'Screw GPU bracket',
                completed: false,
                action: 'Secure GPU',
                risk: 15
            },

            {
                id: 5,
                text: 'Connect 8-pin PCIe power cables',
                completed: false,
                action: 'Connect power',
                risk: 10
            }

        ],

        currentStep: 0,
        temp: 55
    },

    storage: {

        installed: false,
        damaged: false,

        name: 'NVMe SSD',
        icon: 'fa-hdd',
        part: 'Samsung 980 Pro 1TB',

        steps: [

            {
                id: 1,
                text: 'Locate M.2 slot',
                completed: false,
                action: 'Find slot',
                risk: 5
            },

            {
                id: 2,
                text: 'Insert at 30-degree angle',
                completed: false,
                action: 'Insert SSD',
                risk: 20
            },

            {
                id: 3,
                text: 'Push down and screw in place',
                completed: false,
                action: 'Secure SSD',
                risk: 15
            }

        ],

        currentStep: 0,
        temp: 35
    },

    psu: {

        installed: false,
        damaged: false,

        name: 'PSU',
        icon: 'fa-plug',
        part: 'Corsair RM850x',

        steps: [

            {
                id: 1,
                text: 'Mount PSU in case (fan facing down)',
                completed: false,
                action: 'Mount PSU',
                risk: 10
            },

            {
                id: 2,
                text: 'Screw 4 mounting screws',
                completed: false,
                action: 'Screw PSU',
                risk: 10
            },

            {
                id: 3,
                text: 'Connect 24-pin to motherboard',
                completed: false,
                action: 'Connect 24-pin',
                risk: 15
            },

            {
                id: 4,
                text: 'Connect 8-pin EPS to CPU',
                completed: false,
                action: 'Connect CPU power',
                risk: 15
            },

            {
                id: 5,
                text: 'Connect PCIe to GPU',
                completed: false,
                action: 'Connect GPU power',
                risk: 10
            }

        ],

        currentStep: 0,
        temp: 40
    }

};

let isPoweredOn = false;

/* =========================================================
   SCORE
   ========================================================= */

function calculateScore() {

    let installedCount = 0;

    let totalSteps = 0;

    let completedSteps = 0;

    for (let comp in components) {

        const c =
            components[comp];

        totalSteps +=
            c.steps.length;

        completedSteps +=
            c.steps.filter(
                s => s.completed
            ).length;

        if (
            c.installed &&
            !c.damaged
        ) {
            installedCount++;
        }
    }

    const timeBonus =
        Math.floor(timeLeft / 10) * 5;

    const livesBonus =
        lives * 20;

    const stressPenalty =
        Math.floor(
            stressLevel / 5
        );

    const stepBonus =
        completedSteps * 10;

    finalScore =
        stepBonus +
        timeBonus +
        livesBonus -
        stressPenalty;

    finalScore =
        Math.max(
            0,
            finalScore
        );

    return finalScore;
}

/* =========================================================
   TIMER
   ========================================================= */

function startTimer() {

    timerInterval =
        setInterval(() => {

            if (!gameActive) {
                return;
            }

            if (timeLeft <= 0) {

                gameOver(
                    "TIME'S UP! You failed to complete the build in time!"
                );

                return;
            }

            timeLeft--;

            const minutes =
                Math.floor(
                    timeLeft / 60
                );

            const seconds =
                timeLeft % 60;

            const timer =
                document.getElementById(
                    'timerDisplay'
                );

            if (timer) {

                timer.textContent =
                    minutes
                        .toString()
                        .padStart(2, '0')
                    + ':' +
                    seconds
                        .toString()
                        .padStart(2, '0');
            }

            if (
                timeLeft % 30 === 0 &&
                timeLeft > 0
            ) {

                increaseStress(
                    5,
                    'Time pressure increasing stress!'
                );
            }

        }, 1000);
}

/* =========================================================
   TEMPERATURE
   ========================================================= */

function startTemperatureMonitor() {

    tempInterval =
        setInterval(() => {

            if (
                !gameActive ||
                !isPoweredOn
            ) {
                return;
            }

            temperature +=
                Math.random() * 3;

            if (
                !components.cooler.installed
            ) {
                temperature += 5;
            }

            if (temperature > 85) {

                damageComponent(
                    'cpu',
                    'CPU overheated and fried!'
                );

                temperature = 45;
            }

            const tempDisplay =
                document.getElementById(
                    'tempDisplay'
                );

            if (!tempDisplay) {
                return;
            }

            if (temperature > 75) {

                tempDisplay.style.color =
                    '#ffaa00';

            } else {

                tempDisplay.style.color =
                    '#00ff9d';
            }

            tempDisplay.textContent =
                Math.floor(
                    temperature
                ) + '°C';

        }, 2000);
}

/* =========================================================
   STRESS
   ========================================================= */

function increaseStress(
    amount,
    reason
) {

    if (!gameActive) {
        return;
    }

    stressLevel =
        Math.min(
            100,
            stressLevel + amount
        );

    const fill =
        document.getElementById(
            'stressFill'
        );

    if (fill) {

        fill.style.width =
            stressLevel + '%';

        if (stressLevel >= 80) {

            fill.style.background =
                'linear-gradient(90deg,#ff0000,#ff0000)';
        }
    }

    if (stressLevel >= 100) {

        gameOver(
            "STRESS OVERLOAD! You made too many mistakes!"
        );

        return;
    }

    addDiagnosticMessage(
        `⚠️ Stress +${amount}: ${reason}`
    );
}

/* =========================================================
   DAMAGE
   ========================================================= */

function damageComponent(
    component,
    reason
) {

    if (!gameActive) {
        return;
    }

    if (
        !components[component] ||
        components[component].damaged
    ) {
        return;
    }

    components[component].damaged =
        true;

    lives--;

    const livesDisplay =
        document.getElementById(
            'lives'
        );

    if (livesDisplay) {
        livesDisplay.textContent =
            lives;
    }

    addDiagnosticMessage(
        `💀 ${components[component].name} DAMAGED! ${reason}`
    );

    const slotDiv =
        document.getElementById(
            `${component}-slot`
        );

    if (slotDiv) {
        slotDiv.classList.add(
            'damaged'
        );
    }

    const statusSpan =
        document.querySelector(
            `#${component}-slot .component-status`
        );

    if (statusSpan) {

        statusSpan.className =
            'component-status status-damaged';

        statusSpan.innerHTML =
            '<i class="fas fa-skull"></i> DAMAGED';
    }

    if (lives <= 0) {

        gameOver(
            'GAME OVER! You destroyed too many components!'
        );
    }
}

/* =========================================================
   GAME OVER
   ========================================================= */

function gameOver(reason) {

    if (!gameActive) {
        return;
    }

    gameActive = false;

    clearInterval(timerInterval);

    clearInterval(tempInterval);

    calculateScore();

    const bootResult =
        document.getElementById(
            'bootResult'
        );

    if (!bootResult) {
        return;
    }

    bootResult.innerHTML = `

        <div class="game-over">

            <div
                style="
                    background:rgba(0,0,0,0.96);
                    border:2px solid #ff0000;
                    box-shadow:0 0 35px rgba(255,0,0,0.15);
                "
            >

                <i
                    class="fas fa-skull"
                    style="
                        font-size:4rem;
                        color:#ff0000;
                    "
                ></i>

                <h2
                    style="
                        color:#ff0000;
                        font-family:Orbitron,sans-serif;
                        margin:10px 0;
                    "
                >
                    GAME OVER
                </h2>

                <p>
                    ${reason}
                </p>

                <p style="margin-top:8px;">
                    Final Score:
                    <strong>
                        ${finalScore}
                    </strong>
                    points
                </p>

                <button
                    onclick="saveScoreAndReload()"
                    style="
                        background:#ffaa00;
                        border:none;
                        padding:11px 20px;
                        border-radius:10px;
                        color:#050812;
                        margin:10px 4px 4px;
                        cursor:pointer;
                        font-family:Rajdhani,sans-serif;
                        font-weight:700;
                    "
                >
                    <i class="fas fa-save"></i>
                    Save Score
                </button>

                <button
                    onclick="location.reload()"
                    style="
                        background:#ff4444;
                        border:none;
                        padding:11px 20px;
                        border-radius:10px;
                        color:white;
                        margin:4px;
                        cursor:pointer;
                        font-family:Rajdhani,sans-serif;
                        font-weight:700;
                    "
                >
                    TRY AGAIN
                </button>

            </div>

        </div>
    `;
}

/* =========================================================
   SAVE + RELOAD
   ========================================================= */

function saveScoreAndReload() {

    saveScore();

    setTimeout(() => {

        if (gameSaved) {

            location.reload();
        }

    }, 2000);
}

/* =========================================================
   DIAGNOSTIC MESSAGE
   ========================================================= */

function addDiagnosticMessage(msg) {

    const diagDiv =
        document.getElementById(
            'diagnosticResult'
        );

    if (!diagDiv) {
        return;
    }

    diagDiv.innerHTML =
        `<div style="color:#ffaa66;margin-bottom:5px;">
            ${msg}
        </div>` +
        diagDiv.innerHTML;

    while (
        diagDiv.children.length > 10
    ) {

        diagDiv.removeChild(
            diagDiv.lastChild
        );
    }
}

/* =========================================================
   COMPLETE STEP
   ========================================================= */

function completeStep(
    component,
    stepIndex
) {

    if (!gameActive) {
        return;
    }

    if (
        !components[component] ||
        components[component].installed ||
        components[component].damaged
    ) {
        return;
    }

    const step =
        components[component]
            .steps[stepIndex];

    if (!step || step.completed) {
        return;
    }

    const risk =
        step.risk;

    const random =
        Math.random() * 100;

    /* ESD */

    if (
        !esdProtected &&
        Math.random() < 0.3
    ) {

        damageComponent(
            component,
            'ESD damage! You forgot to use anti-static wrist strap!'
        );

        addDiagnosticMessage(
            '⚡ ESD STATIC DISCHARGE damaged the component!'
        );

        return;
    }

    /* RISK */

    if (random < risk) {

        damageComponent(
            component,
            `Failed at step "${step.text}" - ${risk}% risk triggered!`
        );

        addDiagnosticMessage(
            `💥 FAILED: ${step.text} - Component damaged!`
        );

        return;
    }

    step.completed = true;

    components[component]
        .currentStep++;

    addDiagnosticMessage(
        `✅ Completed: ${step.text}`
    );

    stressLevel =
        Math.max(
            0,
            stressLevel - 5
        );

    const stressFill =
        document.getElementById(
            'stressFill'
        );

    if (stressFill) {

        stressFill.style.width =
            stressLevel + '%';
    }

    const stepDiv =
        document.getElementById(
            `${component}-step-${stepIndex}`
        );

    if (stepDiv) {

        stepDiv.classList.add(
            'completed'
        );

        const check =
            stepDiv.querySelector(
                '.step-check'
            );

        if (check) {

            check.innerHTML =
                '<i class="fas fa-check"></i>';
        }
    }

    const allCompleted =
        components[component]
            .steps
            .every(
                s => s.completed
            );

    if (allCompleted) {

        components[component]
            .installed = true;

        addDiagnosticMessage(
            `🎉 ${components[component].name} FULLY INSTALLED!`
        );

        updateComponentUI(
            component
        );

        checkAllComponentsComplete();
    }

    updateComponentUI(
        component
    );
}

/* =========================================================
   CHECK ALL COMPONENTS
   ========================================================= */

function checkAllComponentsComplete() {

    let allComplete = true;

    for (let comp in components) {

        if (
            !components[comp].installed &&
            !components[comp].damaged
        ) {

            allComplete = false;
        }
    }

    if (
        allComplete &&
        gameActive
    ) {

        clearInterval(timerInterval);

        clearInterval(tempInterval);

        calculateScore();

        const timeSpent =
            300 - timeLeft;

        const minutes =
            Math.floor(
                timeSpent / 60
            );

        const seconds =
            timeSpent % 60;

        document.getElementById(
            'bootResult'
        ).innerHTML = `

            <div class="success-message">

                <i
                    class="fas fa-trophy"
                    style="
                        font-size:3rem;
                        color:#ffcc00;
                    "
                ></i>

                <h2
                    style="
                        color:#00ff9d;
                    "
                >
                    🏆 ULTRA HARDCORE COMPLETE! 🏆
                </h2>

                <p>
                    Build Time:
                    ${minutes}:${seconds
                        .toString()
                        .padStart(2,'0')}
                </p>

                <p>
                    Lives Remaining:
                    ${lives}
                    |
                    Final Stress:
                    ${stressLevel}%
                </p>

                <p>
                    Final Score:
                    ${finalScore}
                    points
                </p>

                <button
                    onclick="saveScoreAndReload()"
                    style="
                        background:#ffaa00;
                        border:none;
                        padding:10px 18px;
                        border-radius:10px;
                        color:#050812;
                        margin:6px 3px;
                        cursor:pointer;
                        font-family:Rajdhani,sans-serif;
                        font-weight:700;
                    "
                >
                    <i class="fas fa-save"></i>
                    Save Score
                </button>

                <button
                    onclick="location.reload()"
                    style="
                        background:#00ff9d;
                        border:none;
                        padding:10px 18px;
                        border-radius:10px;
                        color:#050812;
                        margin:6px 3px;
                        cursor:pointer;
                        font-family:Rajdhani,sans-serif;
                        font-weight:700;
                    "
                >
                    NEW CHALLENGE
                </button>

            </div>
        `;

        gameActive = false;
    }
}

/* =========================================================
   UPDATE COMPONENT UI
   ========================================================= */

function updateComponentUI(
    component
) {

    const comp =
        components[component];

    const slotDiv =
        document.getElementById(
            `${component}-slot`
        );

    if (!slotDiv) {
        return;
    }

    if (comp.damaged) {

        slotDiv.classList.add(
            'damaged'
        );

        const statusSpan =
            slotDiv.querySelector(
                '.component-status'
            );

        if (statusSpan) {

            statusSpan.className =
                'component-status status-damaged';

            statusSpan.innerHTML =
                '<i class="fas fa-skull"></i> DAMAGED';
        }

    } else if (comp.installed) {

        slotDiv.classList.remove(
            'damaged'
        );

        slotDiv.classList.add(
            'installed'
        );

        const statusSpan =
            slotDiv.querySelector(
                '.component-status'
            );

        if (statusSpan) {

            statusSpan.className =
                'component-status status-installed';

            statusSpan.innerHTML =
                '<i class="fas fa-check-circle"></i> Installed';
        }

    } else if (
        comp.currentStep > 0
    ) {

        const statusSpan =
            slotDiv.querySelector(
                '.component-status'
            );

        if (statusSpan) {

            statusSpan.className =
                'component-status status-progress';

            statusSpan.innerHTML =
                `<i class="fas fa-spinner"></i>
                ${comp.currentStep}/${comp.steps.length}`;
        }
    }
}

/* =========================================================
   RENDER COMPONENTS
   ========================================================= */

function renderPCComponents() {

    const container =
        document.getElementById(
            'pcComponents'
        );

    if (!container) {
        return;
    }

    const componentOrder = [
        'motherboard',
        'cpu',
        'cooler',
        'ram',
        'gpu',
        'storage',
        'psu'
    ];

    let html = '';

    componentOrder.forEach(
        comp => {

            const c =
                components[comp];

            let statusClass =
                'status-missing';

            let statusText =
                '<i class="fas fa-times-circle"></i> Missing';

            if (c.damaged) {

                statusClass =
                    'status-damaged';

                statusText =
                    '<i class="fas fa-skull"></i> DAMAGED';

            } else if (c.installed) {

                statusClass =
                    'status-installed';

                statusText =
                    '<i class="fas fa-check-circle"></i> Installed';

            } else if (
                c.currentStep > 0
            ) {

                statusClass =
                    'status-progress';

                statusText =
                    `<i class="fas fa-spinner"></i>
                    ${c.currentStep}/${c.steps.length}`;
            }

            html += `

                <div
                    class="component-slot"
                    id="${comp}-slot"
                >

                    <div class="component-header">

                        <span class="component-name">

                            <i
                                class="fas ${c.icon}"
                            ></i>

                            ${c.name}

                        </span>

                        <span
                            class="component-status ${statusClass}"
                        >
                            ${statusText}
                        </span>

                    </div>

                    <div class="steps-container">
            `;

            c.steps.forEach(
                (step, idx) => {

                    const completedClass =
                        step.completed
                            ? 'completed'
                            : '';

                    const riskBadge =
                        !step.completed &&
                        !c.installed &&
                        !c.damaged
                            ? `
                                <span
                                    style="
                                        font-size:0.6rem;
                                        color:#ffaa66;
                                    "
                                >
                                    (${step.risk}% risk)
                                </span>
                            `
                            : '';

                    html += `

                        <div
                            class="step-item ${completedClass}"
                            id="${comp}-step-${idx}"
                        >

                            <div class="step-check">

                                ${
                                    step.completed
                                    ? '<i class="fas fa-check"></i>'
                                    : idx + 1
                                }

                            </div>

                            <div class="step-text">

                                ${step.text}

                                ${riskBadge}

                            </div>

                            ${
                                !step.completed &&
                                !c.installed &&
                                !c.damaged &&
                                gameActive
                                    ? `
                                        <button
                                            class="step-btn"
                                            onclick="completeStep('${comp}', ${idx})"
                                        >
                                            <i class="fas fa-wrench"></i>
                                            ${step.action}
                                        </button>
                                      `
                                    : ''
                            }

                        </div>
                    `;
                }
            );

            html += `

                    </div>

                </div>
            `;
        }
    );

    container.innerHTML =
        html;
}

/* =========================================================
   POWER
   ========================================================= */

function togglePower() {

    if (!gameActive) {
        return;
    }

    if (!isPoweredOn) {

        const essential = [
            'motherboard',
            'cpu',
            'ram',
            'psu'
        ];

        let missingEssential = [];

        essential.forEach(
            comp => {

                if (
                    !components[comp]
                        .installed
                ) {

                    missingEssential.push(
                        components[comp].name
                    );
                }
            }
        );

        if (
            missingEssential.length > 0
        ) {

            addDiagnosticMessage(
                `❌ POST FAILED! Missing: ${missingEssential.join(', ')}`
            );

            increaseStress(
                15,
                'POST failure!'
            );

            return;
        }

        isPoweredOn = true;

        document.getElementById(
            'powerStatus'
        ).innerHTML = 'ON';

        document.getElementById(
            'powerLed'
        ).className =
            'led-green';

        document.getElementById(
            'powerBtn'
        ).innerHTML =
            '<i class="fas fa-power-off"></i> POWER OFF';

        addDiagnosticMessage(
            '🔌 System powered ON - Temperature monitoring active!'
        );

        setTimeout(() => {

            if (
                isPoweredOn &&
                gameActive
            ) {

                document.getElementById(
                    'bootResult'
                ).innerHTML = `

                    <div
                        class="success-message"
                        style="
                            background:rgba(0,255,0,0.2);
                            padding:10px;
                            border-radius:8px;
                        "
                    >
                        <i class="fas fa-check-circle"></i>
                        BOOT SUCCESSFUL!
                        BIOS POST Passed.
                    </div>
                `;
            }

        }, 1000);

    } else {

        isPoweredOn = false;

        document.getElementById(
            'powerStatus'
        ).innerHTML = 'OFF';

        document.getElementById(
            'powerLed'
        ).className =
            'led-red';

        document.getElementById(
            'powerBtn'
        ).innerHTML =
            '<i class="fas fa-power-off"></i> POWER ON';

        document.getElementById(
            'bootResult'
        ).innerHTML = '';

        addDiagnosticMessage(
            '🔌 System powered OFF'
        );
    }
}

/* =========================================================
   DIAGNOSTIC
   ========================================================= */

function runDiagnostic() {

    let issues = [];

    let totalSteps = 0;

    let completedSteps = 0;

    for (let comp in components) {

        const c =
            components[comp];

        totalSteps +=
            c.steps.length;

        completedSteps +=
            c.steps.filter(
                s => s.completed
            ).length;

        if (c.damaged) {

            issues.push(
                `💀 ${c.name}: DAMAGED - Needs replacement!`
            );

        } else if (
            !c.installed &&
            comp !== 'cooler'
        ) {

            issues.push(
                `❌ ${c.name}: Not fully installed (${c.currentStep}/${c.steps.length} steps)`
            );
        }
    }

    document.getElementById(
        'diagnosticResult'
    ).innerHTML = `

        <div>

            <strong>
                🔍 DIAGNOSTIC REPORT:
            </strong>

            <br>

            Progress:
            ${completedSteps}/${totalSteps}
            steps

            <br>

            Lives:
            ${lives}

            |

            Stress:
            ${stressLevel}%

            <br>

            ${issues.join('<br>')}

        </div>
    `;
}

/* =========================================================
   TOOLS
   ========================================================= */

function checkCompatibility() {

    document.getElementById(
        'diagnosticResult'
    ).innerHTML =
        '<div>✅ All components compatible! (AMD Ryzen + B550 + DDR4)</div>';
}

function testPSU() {

    document.getElementById(
        'diagnosticResult'
    ).innerHTML =
        '<div>🔌 PSU Test: +12V:12.1V ✅ | +5V:5.02V ✅ | +3.3V:3.31V ✅</div>';
}

function emergencyCool() {

    temperature =
        Math.max(
            30,
            temperature - 15
        );

    addDiagnosticMessage(
        '❄️ Emergency cooling activated! Temperature reduced.'
    );

    document.getElementById(
        'diagnosticResult'
    ).innerHTML =
        '<div>❄️ Emergency cooling applied! Temperatures normalized.</div>';
}

function useESDWriststrap() {

    esdProtected = true;

    addDiagnosticMessage(
        '🛡️ ESD wrist strap connected! Static damage risk reduced.'
    );

    document.getElementById(
        'diagnosticResult'
    ).innerHTML =
        '<div>🛡️ ESD protection active! Static damage prevented.</div>';
}

/* =========================================================
   GLOBAL FUNCTIONS
   ========================================================= */

window.completeStep =
    completeStep;

window.togglePower =
    togglePower;

window.runDiagnostic =
    runDiagnostic;

window.checkCompatibility =
    checkCompatibility;

window.testPSU =
    testPSU;

window.emergencyCool =
    emergencyCool;

window.useESDWriststrap =
    useESDWriststrap;

window.saveScoreAndReload =
    saveScoreAndReload;

/* =========================================================
   START GAME
   ========================================================= */

renderPCComponents();

startTimer();

startTemperatureMonitor();

</script>

<!-- =======================================================
     BRIGHTNESS WIDGET
     ======================================================= -->

<?php

if (
    file_exists(
        __DIR__ .
        '/includes/brightness_widget.php'
    )
) {

    include __DIR__ .
        '/includes/brightness_widget.php';
}

?>

<!-- =======================================================
     THEME
     ======================================================= -->

<script>

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

    applyTheme(
        savedTheme
    );

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

                applyTheme(
                    nextTheme
                );
            }
        );
    }

})();

</script>

</body>
</html>
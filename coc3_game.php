<?php
// coc3_game.php - COC 3: Set-up Computer Servers - Server Administration Simulator

require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$coc = 3;
$user_id = (int)$_SESSION['user_id'];
$page_title = "COC 3: Set-up Computer Servers - Server Administration Simulator";
$username = $_SESSION['full_name'] ?? $_SESSION['username'] ?? 'User';

$high_score = 0;
$games_played = 0;

/*
|--------------------------------------------------------------------------
| LOAD COC 3 GAME STATS
|--------------------------------------------------------------------------
| Uses the same user_game_scores table used by the other COC games.
*/
$score_query = "SELECT high_score, games_played
                FROM user_game_scores
                WHERE user_id = ? AND coc_number = ?
                LIMIT 1";

if ($stmt = $conn->prepare($score_query)) {
    $stmt->bind_param("ii", $user_id, $coc);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $high_score = (int)($row['high_score'] ?? 0);
        $games_played = (int)($row['games_played'] ?? 0);
    }

    $stmt->close();
}

$global_brightness = 100;
if (function_exists('getGlobalBrightness')) {
    $global_brightness = getGlobalBrightness();
}
$global_brightness = is_numeric($global_brightness) ? (float)$global_brightness : 100;
$global_brightness = max(50, min(150, $global_brightness));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
<meta name="theme-color" content="#050812">
<title><?php echo htmlspecialchars($page_title, ENT_QUOTES, 'UTF-8'); ?> | CSS TESDA Reviewer</title>

<link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;600;700;900&family=Rajdhani:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

<style>
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

:root {
    --global-brightness: <?php echo $global_brightness; ?>%;

    --bg-primary: #050812;
    --bg-secondary: #0a1020;
    --bg-card: rgba(10, 20, 40, 0.92);
    --bg-panel: rgba(0, 0, 0, 0.32);
    --bg-navbar: rgba(5, 8, 18, 0.94);

    --text-primary: #e5edf8;
    --text-secondary: #aab7cc;

    --green: #00ff9d;
    --blue: #0066ff;
    --orange: #ffaa00;
    --red: #ff4757;

    --border: rgba(0, 255, 157, 0.23);
    --border-light: rgba(0, 255, 157, 0.14);
    --grid-color: rgba(0, 255, 157, 0.07);

    --cyber-bg1: #050812;
    --cyber-bg2: #0a1020;
}

body.light-mode {
    --bg-primary: #f4f7fb;
    --bg-secondary: #ffffff;
    --bg-card: rgba(255,255,255,.96);
    --bg-panel: rgba(255,255,255,.92);
    --bg-navbar: rgba(255,255,255,.94);
    --text-primary: #172033;
    --text-secondary: #4b5870;
    --border: #d6deea;
    --border-light: #dce4ef;
    --grid-color: rgba(0,120,180,.08);
    --cyber-bg1: #eef4fb;
    --cyber-bg2: #ffffff;
}

body.dark-mode {
    --bg-primary: #050812;
    --bg-secondary: #0a1020;
    --bg-card: rgba(10,20,40,.92);
    --bg-panel: rgba(0,0,0,.32);
    --bg-navbar: rgba(5,8,18,.94);
    --text-primary: #e5edf8;
    --text-secondary: #aab7cc;
    --border: rgba(0,255,157,.23);
    --border-light: rgba(0,255,157,.14);
    --grid-color: rgba(0,255,157,.07);
    --cyber-bg1: #050812;
    --cyber-bg2: #0a1020;
}

html {
    scrollbar-width: thin;
    scrollbar-color: rgba(128,128,128,.45) transparent;
}

::-webkit-scrollbar {
    width: 8px;
    height: 8px;
}

::-webkit-scrollbar-track {
    background: transparent;
}

::-webkit-scrollbar-thumb {
    background: rgba(128,128,128,.45);
    border-radius: 10px;
}

::-webkit-scrollbar-thumb:hover {
    background: rgba(128,128,128,.65);
}

body {
    font-family: 'Rajdhani', sans-serif;
    background: var(--bg-primary);
    color: var(--text-primary);
    min-height: 100vh;
    overflow-x: hidden;
    transition: background .3s ease, color .3s ease;
    -webkit-tap-highlight-color: transparent;
}

#pageContent {
    min-height: 100vh;
    filter: brightness(var(--global-brightness, 100%));
    transition: filter .2s ease;
}

.cyber-bg,
.grid-overlay {
    position: fixed;
    inset: 0;
    pointer-events: none;
}

.cyber-bg {
    z-index: -2;
    background: linear-gradient(135deg, var(--cyber-bg1), var(--cyber-bg2));
}

.grid-overlay {
    z-index: -1;
    background-image:
        linear-gradient(var(--grid-color) 1px, transparent 1px),
        linear-gradient(90deg, var(--grid-color) 1px, transparent 1px);
    background-size: 50px 50px;
    animation: gridMove 20s linear infinite;
}

@keyframes gridMove {
    from { transform: translate(0,0); }
    to { transform: translate(50px,50px); }
}

/* NAVIGATION */
.navbar {
    position: sticky;
    top: 0;
    z-index: 100;
    background: var(--bg-navbar);
    backdrop-filter: blur(10px);
    border-bottom: 1px solid var(--border-light);
    padding: 1rem 2rem;
}

.nav-container {
    max-width: 1450px;
    margin: auto;
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 1rem;
}

.logo {
    color: var(--green);
    font-family: 'Orbitron', sans-serif;
    font-size: 1.35rem;
    font-weight: 900;
    text-decoration: none;
    white-space: nowrap;
    background: linear-gradient(135deg,var(--green),var(--blue));
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
}

.nav-links {
    display: flex;
    align-items: center;
    gap: 1.15rem;
    flex-wrap: wrap;
}

.nav-links a {
    color: var(--text-secondary);
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 7px;
    transition: .25s;
}

.nav-links a:hover,
.nav-links a.active {
    color: var(--green);
}

.user-info {
    display: flex;
    align-items: center;
    gap: .7rem;
    color: var(--green);
}

.theme-toggle {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    border: 1px solid var(--border);
    background: var(--bg-card);
    color: var(--text-primary);
    border-radius: 999px;
    padding: 7px 12px;
    font-family: 'Rajdhani', sans-serif;
    font-weight: 700;
    cursor: pointer;
}

.theme-toggle:hover {
    border-color: var(--green);
    color: var(--green);
}

/* PAGE */
.container {
    width: min(1450px, calc(100% - 32px));
    margin: auto;
    padding: 1.7rem 0 3rem;
}

.game-header {
    text-align: center;
    margin-bottom: 1.4rem;
}

.coc-badge {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 7px 18px;
    border: 1px solid var(--green);
    border-radius: 999px;
    color: var(--green);
    background: rgba(0,255,157,.08);
    font-family: 'Orbitron', sans-serif;
    font-size: .78rem;
    margin-bottom: .8rem;
}

.game-header h1 {
    font-family: 'Orbitron', sans-serif;
    font-size: clamp(1.5rem, 3vw, 2.5rem);
    background: linear-gradient(135deg,var(--green),var(--blue));
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    margin-bottom: .45rem;
}

.game-header p {
    color: var(--text-secondary);
    font-size: 1rem;
}

.instructions-panel {
    background: var(--bg-card);
    border: 1px solid var(--border);
    border-radius: 16px;
    padding: 1rem 1.2rem;
    margin-bottom: 1rem;
}

.instructions-panel h2 {
    color: var(--green);
    font-family: 'Orbitron', sans-serif;
    font-size: .95rem;
    margin-bottom: .5rem;
}

.instructions-panel p {
    color: var(--text-secondary);
    line-height: 1.5;
}

.stats-bar {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: .8rem;
    margin-bottom: 1rem;
}

.stat-card {
    background: var(--bg-card);
    border: 1px solid var(--border);
    border-radius: 13px;
    padding: .75rem;
    text-align: center;
}

.stat-value {
    color: var(--green);
    font-family: 'Orbitron', sans-serif;
    font-size: 1.2rem;
    font-weight: 700;
}

.stat-label {
    color: var(--text-secondary);
    font-size: .72rem;
    margin-top: 2px;
}

/* GAME LAYOUT */
.game-layout {
    display: grid;
    grid-template-columns: minmax(0, 1.75fr) minmax(300px, 1fr);
    gap: 1rem;
    align-items: start;
}

.server-panel,
.control-panel {
    background: var(--bg-card);
    border: 1px solid var(--border);
    border-radius: 18px;
    min-width: 0;
}

.server-panel {
    padding: 1rem;
}

.control-panel {
    padding: 1rem;
    position: sticky;
    top: 88px;
}

.panel-title {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: .7rem;
    margin-bottom: .8rem;
    padding-bottom: .65rem;
    border-bottom: 1px solid var(--border-light);
}

.panel-title h2 {
    color: var(--green);
    font-family: 'Orbitron', sans-serif;
    font-size: .95rem;
}

.status-chip {
    padding: 4px 9px;
    border-radius: 999px;
    font-size: .65rem;
    font-weight: 700;
    color: var(--orange);
    border: 1px solid rgba(255,170,0,.35);
    background: rgba(255,170,0,.08);
}

.status-chip.online {
    color: var(--green);
    border-color: rgba(0,255,157,.35);
    background: rgba(0,255,157,.08);
}

/* SERVER DESKTOP */
.server-screen {
    border: 8px solid #202b3d;
    border-radius: 12px;
    background: #080d16;
    overflow: hidden;
    box-shadow: 0 0 0 1px rgba(0,255,157,.18), 0 15px 35px rgba(0,0,0,.35);
}

.server-topbar {
    background: #111a2b;
    color: #9eb2cf;
    padding: 7px 10px;
    display: flex;
    justify-content: space-between;
    gap: 8px;
    font-family: monospace;
    font-size: .7rem;
}

.server-display {
    min-height: 410px;
    padding: 1rem;
    background:
        radial-gradient(circle at 50% 25%, rgba(0,102,255,.12), transparent 38%),
        #07101b;
    color: #d9e8ff;
    font-family: monospace;
    overflow: auto;
}

.boot-screen {
    min-height: 378px;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    text-align: center;
    gap: .8rem;
}

.boot-logo {
    width: 70px;
    height: 70px;
    border: 2px solid var(--green);
    border-radius: 14px;
    display: grid;
    place-items: center;
    color: var(--green);
    font-size: 2rem;
    box-shadow: 0 0 25px rgba(0,255,157,.16);
}

.boot-screen h3 {
    color: #fff;
    font-family: 'Orbitron', sans-serif;
    font-size: 1rem;
}

.boot-screen p {
    color: #91a4bd;
    max-width: 520px;
}

.boot-btn {
    border: 1px solid var(--green);
    background: rgba(0,255,157,.08);
    color: var(--green);
    padding: 10px 18px;
    border-radius: 8px;
    font-family: 'Orbitron', sans-serif;
    font-weight: 700;
    cursor: pointer;
}

.boot-btn:hover {
    background: var(--green);
    color: #061019;
}

.terminal-line {
    margin-bottom: 5px;
    white-space: pre-wrap;
}

.terminal-green { color: #00ff9d; }
.terminal-blue { color: #62a9ff; }
.terminal-yellow { color: #ffd166; }
.terminal-red { color: #ff6472; }

.server-rack {
    margin-top: 1rem;
    border: 1px solid #33435c;
    border-radius: 10px;
    background: #101827;
    padding: .65rem;
}

.rack-label {
    color: #7285a3;
    font-family: monospace;
    font-size: .65rem;
    margin-bottom: .45rem;
}

.rack-unit {
    height: 58px;
    border: 1px solid #3d4e68;
    border-radius: 7px;
    background: linear-gradient(#1b2638,#0d1522);
    display: flex;
    align-items: center;
    gap: .7rem;
    padding: .55rem .7rem;
}

.rack-leds {
    display: flex;
    gap: 5px;
}

.rack-led {
    width: 7px;
    height: 7px;
    border-radius: 50%;
    background: #34445e;
}

.rack-led.on {
    background: var(--green);
    box-shadow: 0 0 8px var(--green);
}

.rack-name {
    color: #d9e4f5;
    font-family: 'Orbitron', sans-serif;
    font-size: .72rem;
}

.rack-model {
    color: #71819a;
    font-size: .65rem;
    margin-left: auto;
}

/* PROGRESS */
.progress-box {
    margin-bottom: .8rem;
}

.progress-info {
    display: flex;
    justify-content: space-between;
    color: var(--text-secondary);
    font-size: .75rem;
    margin-bottom: 5px;
}

.progress-track {
    height: 9px;
    border-radius: 99px;
    background: rgba(128,128,128,.18);
    overflow: hidden;
}

.progress-fill {
    height: 100%;
    width: 0;
    background: linear-gradient(90deg,var(--green),var(--blue));
    transition: width .35s ease;
}

/* TASKS */
.task-list {
    display: grid;
    gap: .55rem;
}

.task {
    display: grid;
    grid-template-columns: 28px minmax(0,1fr) auto;
    gap: .6rem;
    align-items: center;
    padding: .65rem;
    border: 1px solid var(--border-light);
    border-radius: 10px;
    background: rgba(0,0,0,.12);
    cursor: pointer;
    transition: .2s;
}

.task:hover {
    border-color: var(--green);
}

.task.active {
    border-color: var(--blue);
    box-shadow: 0 0 0 1px rgba(0,102,255,.18);
}

.task.done {
    border-color: rgba(0,255,157,.35);
    background: rgba(0,255,157,.045);
}

.task-number {
    width: 28px;
    height: 28px;
    border-radius: 50%;
    display: grid;
    place-items: center;
    background: rgba(0,102,255,.12);
    border: 1px solid rgba(0,102,255,.35);
    color: #70aaff;
    font-family: 'Orbitron', sans-serif;
    font-size: .65rem;
}

.task.done .task-number {
    color: #061019;
    background: var(--green);
    border-color: var(--green);
}

.task-name {
    font-weight: 700;
    color: var(--text-primary);
    font-size: .78rem;
}

.task-desc {
    color: var(--text-secondary);
    font-size: .65rem;
}

.task-status {
    font-size: .62rem;
    color: #71819a;
    white-space: nowrap;
}

.task.done .task-status {
    color: var(--green);
}

/* CONTROLS */
.timer-box {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: .75rem;
    border: 1px solid var(--border-light);
    border-radius: 10px;
    margin-bottom: .8rem;
}

.timer-label {
    color: var(--text-secondary);
    font-size: .72rem;
}

.timer {
    font-family: 'Orbitron', sans-serif;
    color: var(--orange);
    font-weight: 700;
}

.timer.warning { color: var(--red); }

.action-box {
    padding: .75rem;
    border: 1px solid var(--border-light);
    border-radius: 10px;
    margin-bottom: .8rem;
}

.action-box h3 {
    color: var(--green);
    font-family: 'Orbitron', sans-serif;
    font-size: .75rem;
    margin-bottom: .55rem;
}

.action-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0,1fr));
    gap: .5rem;
}

.action-btn {
    min-height: 58px;
    border: 1px solid rgba(0,255,157,.2);
    border-radius: 9px;
    background: rgba(0,255,157,.045);
    color: var(--text-primary);
    cursor: pointer;
    font-family: 'Rajdhani', sans-serif;
    font-weight: 700;
    font-size: .72rem;
    padding: .45rem;
    transition: .2s;
}

.action-btn i {
    display: block;
    color: var(--green);
    margin-bottom: 4px;
    font-size: .9rem;
}

.action-btn:hover:not(:disabled) {
    border-color: var(--green);
    transform: translateY(-1px);
}

.action-btn:disabled {
    opacity: .38;
    cursor: not-allowed;
}

.current-task {
    border: 1px solid rgba(0,102,255,.35);
    background: rgba(0,102,255,.06);
    border-radius: 10px;
    padding: .75rem;
    margin-bottom: .8rem;
}

.current-task h3 {
    color: #71aaff;
    font-family: 'Orbitron', sans-serif;
    font-size: .75rem;
    margin-bottom: .35rem;
}

.current-task p {
    color: var(--text-secondary);
    font-size: .72rem;
    line-height: 1.4;
}

.control-buttons {
    display: grid;
    gap: .5rem;
}

.btn {
    border: none;
    border-radius: 9px;
    padding: 10px 14px;
    font-family: 'Orbitron', sans-serif;
    font-weight: 700;
    cursor: pointer;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 7px;
    transition: .2s;
}

.btn-primary {
    background: linear-gradient(45deg,var(--green),var(--blue));
    color: #061019;
}

.btn-secondary {
    color: var(--green);
    background: transparent;
    border: 1px solid var(--green);
}

.btn-danger {
    color: #fff;
    background: rgba(255,71,87,.12);
    border: 1px solid rgba(255,71,87,.45);
}

.btn:hover {
    transform: translateY(-1px);
}

.message-box {
    min-height: 42px;
    padding: .65rem;
    border-radius: 9px;
    border: 1px solid var(--border-light);
    color: var(--text-secondary);
    font-size: .72rem;
    line-height: 1.4;
    margin-bottom: .8rem;
}

.message-box.success {
    color: var(--green);
    border-color: rgba(0,255,157,.35);
    background: rgba(0,255,157,.05);
}

.message-box.error {
    color: #ff7c86;
    border-color: rgba(255,71,87,.35);
    background: rgba(255,71,87,.05);
}

/* GAME OVER */
.game-over {
    display: none;
    position: fixed;
    inset: 0;
    z-index: 5000;
    align-items: center;
    justify-content: center;
    padding: 1rem;
    background: rgba(0,0,0,.82);
    backdrop-filter: blur(8px);
}

.game-over.active {
    display: flex;
}

.game-over-card {
    width: min(520px, 100%);
    background: var(--bg-card);
    border: 2px solid var(--green);
    border-radius: 20px;
    padding: 2rem;
    text-align: center;
    box-shadow: 0 20px 70px rgba(0,255,157,.18);
}

.game-over-icon {
    font-size: 3rem;
    color: var(--green);
    margin-bottom: .7rem;
}

.game-over-card h2 {
    font-family: 'Orbitron', sans-serif;
    color: var(--green);
    margin-bottom: .5rem;
}

.final-score {
    font-family: 'Orbitron', sans-serif;
    font-size: 2.4rem;
    color: var(--orange);
    margin: .7rem 0;
}

.game-over-card p {
    color: var(--text-secondary);
    margin-bottom: 1rem;
}

/* TOAST */
.toast {
    position: fixed;
    right: 18px;
    bottom: 18px;
    z-index: 6000;
    max-width: 340px;
    padding: .8rem 1rem;
    background: var(--bg-card);
    border: 1px solid var(--green);
    color: var(--text-primary);
    border-radius: 10px;
    transform: translateY(120px);
    opacity: 0;
    transition: .25s;
    pointer-events: none;
}

.toast.show {
    transform: translateY(0);
    opacity: 1;
}

/* MOBILE - KEEP SERVER AND CONTROL PANELS SIDE-BY-SIDE */
@media (max-width: 900px) {
    .navbar {
        padding: .75rem .8rem;
    }

    .nav-container {
        align-items: flex-start;
    }

    .logo {
        font-size: .95rem;
    }

    .nav-links {
        gap: .55rem;
        justify-content: flex-end;
    }

    .nav-links > a {
        display: none;
    }

    /* Show Dashboard button on Android/mobile view */
    .nav-links > a[href="dashboard.php"] {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        font-size: .65rem;
        padding: 5px 7px;
        white-space: nowrap;
    }

    .user-info {
        font-size: .72rem;
    }

    .container {
        width: calc(100% - 10px);
        padding: .7rem 0 2rem;
    }

    .game-header {
        margin-bottom: .75rem;
    }

    .game-header h1 {
        font-size: 1.25rem;
    }

    .game-header p {
        font-size: .76rem;
    }

    .coc-badge {
        font-size: .62rem;
        padding: 5px 10px;
    }

    .instructions-panel {
        padding: .7rem;
    }

    .instructions-panel h2 {
        font-size: .72rem;
    }

    .instructions-panel p {
        font-size: .65rem;
    }

    .stats-bar {
        grid-template-columns: repeat(4, 1fr);
        gap: .35rem;
    }

    .stat-card {
        padding: .5rem .2rem;
    }

    .stat-value {
        font-size: .78rem;
    }

    .stat-label {
        font-size: .52rem;
    }

    .game-layout {
        grid-template-columns: minmax(0, 1.75fr) minmax(118px, 1fr);
        gap: .5rem;
    }

    .server-panel,
    .control-panel {
        padding: .55rem;
        border-radius: 11px;
    }

    .control-panel {
        position: static;
    }

    .panel-title h2 {
        font-size: .65rem;
    }

    .status-chip {
        font-size: .48rem;
        padding: 3px 5px;
    }

    .server-screen {
        border-width: 4px;
    }

    .server-topbar {
        padding: 4px 5px;
        font-size: .48rem;
    }

    .server-display {
        min-height: 310px;
        padding: .5rem;
        font-size: .53rem;
    }

    .boot-screen {
        min-height: 280px;
    }

    .boot-logo {
        width: 45px;
        height: 45px;
        font-size: 1.2rem;
    }

    .boot-screen h3 {
        font-size: .65rem;
    }

    .boot-screen p {
        font-size: .55rem;
    }

    .boot-btn {
        font-size: .55rem;
        padding: 7px 9px;
    }

    .server-rack {
        margin-top: .5rem;
        padding: .35rem;
    }

    .rack-label {
        font-size: .45rem;
    }

    .rack-unit {
        height: 42px;
        padding: .35rem;
        gap: .3rem;
    }

    .rack-name {
        font-size: .48rem;
    }

    .rack-model {
        font-size: .4rem;
    }

    .rack-led {
        width: 5px;
        height: 5px;
    }

    .timer-box {
        padding: .5rem;
    }

    .timer-label,
    .timer {
        font-size: .55rem;
    }

    .current-task,
    .action-box,
    .message-box {
        padding: .5rem;
    }

    .current-task h3,
    .action-box h3 {
        font-size: .57rem;
    }

    .current-task p,
    .message-box {
        font-size: .53rem;
    }

    .action-grid {
        grid-template-columns: 1fr;
        gap: .3rem;
    }

    .action-btn {
        min-height: 46px;
        font-size: .52rem;
        padding: .3rem;
    }

    .action-btn i {
        font-size: .7rem;
        margin-bottom: 2px;
    }

    .task {
        grid-template-columns: 20px minmax(0,1fr);
        gap: .35rem;
        padding: .4rem;
    }

    .task-number {
        width: 20px;
        height: 20px;
        font-size: .45rem;
    }

    .task-name {
        font-size: .52rem;
    }

    .task-desc {
        display: none;
    }

    .task-status {
        grid-column: 2;
        font-size: .45rem;
        margin-top: -3px;
    }

    .progress-info {
        font-size: .52rem;
    }

    .progress-track {
        height: 6px;
    }

    .btn {
        padding: 7px 8px;
        font-size: .52rem;
    }
}

@media (max-width: 430px) {
    .game-layout {
        grid-template-columns: minmax(0, 1.65fr) minmax(105px, 1fr);
    }

    .user-info span {
        display: none;
    }

    .server-display {
        min-height: 280px;
    }
}
</style>
</head>

<body class="dark-mode">

<div id="pageContent">
    <div class="cyber-bg"></div>
    <div class="grid-overlay"></div>

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
                    <span><?php echo htmlspecialchars($username, ENT_QUOTES, 'UTF-8'); ?></span>

                    <button type="button" class="theme-toggle" id="themeToggle">
                        <i class="fas fa-moon" id="themeIcon"></i>
                        <span id="themeText">Dark Mode</span>
                    </button>

                    <a href="logout.php" style="color:#ef4444;" aria-label="Logout">
                        <i class="fas fa-sign-out-alt"></i>
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <main class="container">

        <section class="game-header">
            <div class="coc-badge">
                <i class="fas fa-server"></i>
                COC 3: SET-UP COMPUTER SERVERS
            </div>
            <h1>SERVER ADMINISTRATION SIMULATOR</h1>
            <p>Configure, secure, and verify a Windows-based computer server.</p>
        </section>

        <section class="instructions-panel">
            <h2><i class="fas fa-terminal"></i> SIMULATION OBJECTIVE</h2>
            <p>
                Complete the server setup in the correct order. Inspect the hardware,
                prepare storage, install the operating system, configure network services,
                create server roles, secure the system, and perform the final verification.
            </p>
        </section>

        <section class="stats-bar">
            <div class="stat-card">
                <div class="stat-value" id="scoreDisplay">0</div>
                <div class="stat-label">CURRENT SCORE</div>
            </div>
            <div class="stat-card">
                <div class="stat-value" id="highScoreDisplay"><?php echo $high_score; ?></div>
                <div class="stat-label">HIGH SCORE</div>
            </div>
            <div class="stat-card">
                <div class="stat-value" id="gamesDisplay"><?php echo $games_played; ?></div>
                <div class="stat-label">GAMES PLAYED</div>
            </div>
            <div class="stat-card">
                <div class="stat-value" id="stepDisplay">0/10</div>
                <div class="stat-label">STEPS COMPLETE</div>
            </div>
        </section>

        <section class="game-layout">

            <!-- SERVER SIDE -->
            <div class="server-panel">
                <div class="panel-title">
                    <h2><i class="fas fa-server"></i> SERVER CONSOLE</h2>
                    <span class="status-chip" id="serverStatus">OFFLINE</span>
                </div>

                <div class="server-screen">
                    <div class="server-topbar">
                        <span>SERVER01</span>
                        <span id="topbarStatus">POWER OFF</span>
                    </div>

                    <div class="server-display" id="serverDisplay">
                        <div class="boot-screen" id="bootScreen">
                            <div class="boot-logo"><i class="fas fa-server"></i></div>
                            <h3>SERVER01 • WINDOWS SERVER</h3>
                            <p>
                                Server is powered off. Start the simulation to begin
                                the COC 3 server setup procedure.
                            </p>
                            <button class="boot-btn" id="startBtn" type="button">
                                <i class="fas fa-power-off"></i> POWER ON SERVER
                            </button>
                        </div>
                    </div>
                </div>

                <div class="server-rack">
                    <div class="rack-label">RACK MOUNT • SERVER01</div>
                    <div class="rack-unit">
                        <div class="rack-leds">
                            <span class="rack-led" id="led1"></span>
                            <span class="rack-led" id="led2"></span>
                            <span class="rack-led" id="led3"></span>
                        </div>
                        <span class="rack-name">SERVER01</span>
                        <span class="rack-model">2U COMPUTE NODE • 32 GB ECC • RAID</span>
                    </div>
                </div>
            </div>

            <!-- CONTROL SIDE -->
            <aside class="control-panel">
                <div class="panel-title">
                    <h2><i class="fas fa-cogs"></i> CONTROL PANEL</h2>
                    <span class="status-chip" id="missionStatus">READY</span>
                </div>

                <div class="timer-box">
                    <span class="timer-label"><i class="fas fa-stopwatch"></i> TIME</span>
                    <span class="timer" id="timer">10:00</span>
                </div>

                <div class="progress-box">
                    <div class="progress-info">
                        <span>SETUP PROGRESS</span>
                        <span id="progressText">0%</span>
                    </div>
                    <div class="progress-track">
                        <div class="progress-fill" id="progressFill"></div>
                    </div>
                </div>

                <div class="current-task">
                    <h3><i class="fas fa-crosshairs"></i> CURRENT TASK</h3>
                    <p id="currentTask">
                        Power on the server to begin the installation procedure.
                    </p>
                </div>

                <div class="action-box">
                    <h3><i class="fas fa-wrench"></i> SERVER TOOLS</h3>

                    <div class="action-grid">
                        <button class="action-btn" data-action="inspect" disabled>
                            <i class="fas fa-search"></i>
                            Inspect Hardware
                        </button>

                        <button class="action-btn" data-action="storage" disabled>
                            <i class="fas fa-hdd"></i>
                            Configure RAID
                        </button>

                        <button class="action-btn" data-action="os" disabled>
                            <i class="fab fa-windows"></i>
                            Install OS
                        </button>

                        <button class="action-btn" data-action="network" disabled>
                            <i class="fas fa-network-wired"></i>
                            Configure Network
                        </button>

                        <button class="action-btn" data-action="dns" disabled>
                            <i class="fas fa-globe"></i>
                            Configure DNS
                        </button>

                        <button class="action-btn" data-action="ad" disabled>
                            <i class="fas fa-users"></i>
                            Install AD DS
                        </button>

                        <button class="action-btn" data-action="share" disabled>
                            <i class="fas fa-folder-open"></i>
                            Create File Share
                        </button>

                        <button class="action-btn" data-action="security" disabled>
                            <i class="fas fa-shield-alt"></i>
                            Harden Server
                        </button>

                        <button class="action-btn" data-action="verify" disabled>
                            <i class="fas fa-check-double"></i>
                            Verify Server
                        </button>
                    </div>
                </div>

                <div class="message-box" id="messageBox">
                    Select the correct tool for the current task.
                </div>

                <div class="control-buttons">
                    <button class="btn btn-danger" id="restartBtn" type="button">
                        <i class="fas fa-redo"></i> Restart Simulation
                    </button>
                    <a class="btn btn-secondary" href="coc3_review.php">
                        <i class="fas fa-book"></i> COC 3 Review
                    </a>
                </div>

                <div style="margin-top:.8rem;">
                    <div class="progress-info">
                        <span><i class="fas fa-list-check"></i> TASK CHECKLIST</span>
                    </div>
                    <div class="task-list" id="taskList"></div>
                </div>
            </aside>

        </section>
    </main>
</div>

<div class="game-over" id="gameOver">
    <div class="game-over-card">
        <div class="game-over-icon" id="gameOverIcon">
            <i class="fas fa-server"></i>
        </div>
        <h2 id="gameOverTitle">SERVER READY</h2>
        <div class="final-score" id="finalScore">0</div>
        <p id="gameOverMessage">Simulation completed.</p>

        <div class="control-buttons">
            <button class="btn btn-primary" id="playAgainBtn" type="button">
                <i class="fas fa-redo"></i> Play Again
            </button>
            <a class="btn btn-secondary" href="coc3_review.php">
                <i class="fas fa-book"></i> Back to COC 3 Review
            </a>
        </div>
    </div>
</div>

<div class="toast" id="toast"></div>

<script>
(function () {
    'use strict';

    const TOTAL_STEPS = 10;
    const GAME_TIME = 600;

    const tasks = [
        {
            id: 1,
            name: 'Inspect Server Hardware',
            desc: 'Check CPU, ECC RAM, storage and network hardware.',
            action: 'inspect',
            points: 100,
            terminal: [
                'POST: PASS',
                'CPU: 8-CORE SERVER PROCESSOR ........ OK',
                'ECC RAM: 32 GB ........................ OK',
                'NIC: 2 x GIGABIT ETHERNET ............ OK',
                'STORAGE: 2 x 1 TB HDD ................ OK'
            ]
        },
        {
            id: 2,
            name: 'Configure RAID Storage',
            desc: 'Initialize the two-disk RAID-1 storage volume.',
            action: 'storage',
            points: 100,
            terminal: [
                'STORAGE CONTROLLER DETECTED',
                'DISK 0: 1 TB ......................... ONLINE',
                'DISK 1: 1 TB ......................... ONLINE',
                'RAID LEVEL: 1 ........................ MIRROR',
                'VOLUME: SERVER_OS .................... READY'
            ]
        },
        {
            id: 3,
            name: 'Install Windows Server',
            desc: 'Install the server operating system on the prepared volume.',
            action: 'os',
            points: 120,
            terminal: [
                'BOOT MEDIA: WINDOWS SERVER',
                'TARGET: SERVER_OS (RAID-1)',
                'COPYING INSTALLATION FILES .......... 100%',
                'INSTALLING SERVER COMPONENTS ......... DONE',
                'OS INSTALLATION ...................... COMPLETE'
            ]
        },
        {
            id: 4,
            name: 'Configure Network',
            desc: 'Assign a static IP address, gateway and hostname.',
            action: 'network',
            points: 110,
            terminal: [
                'HOSTNAME: SERVER01',
                'IP ADDRESS: 192.168.10.10',
                'SUBNET MASK: 255.255.255.0',
                'DEFAULT GATEWAY: 192.168.10.1',
                'NETWORK INTERFACE ................... CONNECTED'
            ]
        },
        {
            id: 5,
            name: 'Configure DNS',
            desc: 'Prepare DNS settings for server and domain services.',
            action: 'dns',
            points: 100,
            terminal: [
                'DNS ROLE: SERVER01',
                'PRIMARY DNS: 192.168.10.10',
                'FORWARD LOOKUPS ..................... READY',
                'REVERSE LOOKUPS ...................... READY',
                'DNS CONFIGURATION ................... VALID'
            ]
        },
        {
            id: 6,
            name: 'Install Active Directory',
            desc: 'Install AD DS and promote SERVER01 as a domain controller.',
            action: 'ad',
            points: 140,
            terminal: [
                'ROLE: ACTIVE DIRECTORY DOMAIN SERVICES',
                'DOMAIN: CSSLOCAL.LOCAL',
                'SERVER01 DOMAIN CONTROLLER .......... ONLINE',
                'GLOBAL CATALOG ....................... ENABLED',
                'AD DS INSTALLATION ................... COMPLETE'
            ]
        },
        {
            id: 7,
            name: 'Create File Share',
            desc: 'Create a shared folder for authorized users.',
            action: 'share',
            points: 90,
            terminal: [
                'FOLDER: C:\\SERVER-DATA',
                'SHARE NAME: SERVER-DATA',
                'NTFS PERMISSIONS .................... CONFIGURED',
                'SHARE PERMISSIONS ................... CONFIGURED',
                'FILE SERVICE ........................ READY'
            ]
        },
        {
            id: 8,
            name: 'Harden Server',
            desc: 'Apply firewall, updates and basic server security.',
            action: 'security',
            points: 110,
            terminal: [
                'WINDOWS FIREWALL .................... ENABLED',
                'SECURITY UPDATES .................... CHECKED',
                'UNUSED SERVICES ..................... REVIEWED',
                'ADMIN ACCOUNT ....................... PROTECTED',
                'SERVER HARDENING .................... COMPLETE'
            ]
        },
        {
            id: 9,
            name: 'Run Server Diagnostics',
            desc: 'Check services, network connectivity and event logs.',
            action: 'diagnostics',
            points: 100,
            terminal: [
                'PING GATEWAY ........................ PASS',
                'PING DNS SERVER ...................... PASS',
                'CRITICAL SERVICES .................... RUNNING',
                'EVENT LOG ............................ CLEAN',
                'DIAGNOSTICS .......................... PASS'
            ]
        },
        {
            id: 10,
            name: 'Final Server Verification',
            desc: 'Verify the complete server installation and configuration.',
            action: 'verify',
            points: 130,
            terminal: [
                'HOSTNAME ............................. SERVER01',
                'NETWORK .............................. PASS',
                'DNS .................................. PASS',
                'ACTIVE DIRECTORY ..................... PASS',
                'FILE SERVICES ......................... PASS',
                'SECURITY ............................. PASS',
                'FINAL SERVER STATUS .................. READY'
            ]
        }
    ];

    let currentStep = 0;
    let score = 0;
    let secondsLeft = GAME_TIME;
    let timerInterval = null;
    let started = false;
    let finished = false;
    let savedGame = false;

    const scoreDisplay = document.getElementById('scoreDisplay');
    const highScoreDisplay = document.getElementById('highScoreDisplay');
    const gamesDisplay = document.getElementById('gamesDisplay');
    const stepDisplay = document.getElementById('stepDisplay');
    const progressFill = document.getElementById('progressFill');
    const progressText = document.getElementById('progressText');
    const currentTask = document.getElementById('currentTask');
    const taskList = document.getElementById('taskList');
    const timer = document.getElementById('timer');
    const messageBox = document.getElementById('messageBox');
    const serverDisplay = document.getElementById('serverDisplay');
    const serverStatus = document.getElementById('serverStatus');
    const missionStatus = document.getElementById('missionStatus');
    const topbarStatus = document.getElementById('topbarStatus');
    const startBtn = document.getElementById('startBtn');
    const restartBtn = document.getElementById('restartBtn');
    const gameOver = document.getElementById('gameOver');
    const finalScore = document.getElementById('finalScore');
    const gameOverTitle = document.getElementById('gameOverTitle');
    const gameOverMessage = document.getElementById('gameOverMessage');
    const gameOverIcon = document.getElementById('gameOverIcon');
    const playAgainBtn = document.getElementById('playAgainBtn');
    const toast = document.getElementById('toast');

    function renderTasks() {
        taskList.innerHTML = tasks.map((task, index) => {
            const done = index < currentStep;
            const active = index === currentStep && started && !finished;

            return `
                <div class="task ${done ? 'done' : ''} ${active ? 'active' : ''}">
                    <div class="task-number">${done ? '<i class="fas fa-check"></i>' : task.id}</div>
                    <div>
                        <div class="task-name">${escapeHtml(task.name)}</div>
                        <div class="task-desc">${escapeHtml(task.desc)}</div>
                    </div>
                    <div class="task-status">${done ? 'COMPLETE' : active ? 'CURRENT' : 'LOCKED'}</div>
                </div>
            `;
        }).join('');
    }

    function updateProgress() {
        const percent = Math.round((currentStep / TOTAL_STEPS) * 100);
        progressFill.style.width = percent + '%';
        progressText.textContent = percent + '%';
        stepDisplay.textContent = currentStep + '/' + TOTAL_STEPS;
        scoreDisplay.textContent = score;
    }

    function updateCurrentTask() {
        if (!started) {
            currentTask.textContent = 'Power on the server to begin the installation procedure.';
            return;
        }

        if (finished) {
            currentTask.textContent = 'Server verification completed. The server is ready for production.';
            return;
        }

        const task = tasks[currentStep];

        if (task) {
            currentTask.textContent = task.desc;
        }
    }

    function setMessage(text, type) {
        messageBox.className = 'message-box' + (type ? ' ' + type : '');
        messageBox.textContent = text;
    }

    function showToast(text) {
        toast.textContent = text;
        toast.classList.add('show');

        clearTimeout(showToast.timeout);
        showToast.timeout = setTimeout(() => {
            toast.classList.remove('show');
        }, 2600);
    }

    function printTerminal(lines, colorClass) {
        serverDisplay.innerHTML = '';

        lines.forEach((line, index) => {
            const row = document.createElement('div');
            row.className = 'terminal-line ' + (colorClass || (index === lines.length - 1 ? 'terminal-green' : ''));
            row.textContent = '> ' + line;
            serverDisplay.appendChild(row);
        });

        const cursor = document.createElement('div');
        cursor.className = 'terminal-line terminal-green';
        cursor.textContent = '> _';
        serverDisplay.appendChild(cursor);

        serverDisplay.scrollTop = serverDisplay.scrollHeight;
    }

    function startGame() {
        if (started && !finished) return;

        started = true;
        finished = false;
        currentStep = 0;
        score = 0;
        secondsLeft = GAME_TIME;
        savedGame = false;

        gameOver.classList.remove('active');

        serverStatus.textContent = 'ONLINE';
        serverStatus.classList.add('online');
        missionStatus.textContent = 'ACTIVE';
        missionStatus.classList.add('online');
        topbarStatus.textContent = 'SYSTEM ONLINE';

        document.querySelectorAll('.action-btn').forEach(button => {
            button.disabled = false;
        });

        printTerminal([
            'POWER ON SELF TEST ................... PASS',
            'SERVER01 BIOS ......................... READY',
            'MEMORY CHECK .......................... 32768 MB',
            'BOOT DEVICE ........................... READY',
            'WAITING FOR ADMINISTRATOR ACTION...'
        ]);

        setMessage('Server is online. Start with "Inspect Hardware".', '');
        updateCurrentTask();
        updateProgress();
        renderTasks();

        startTimer();
        showToast('COC 3 server simulation started.');
    }

    function startTimer() {
        clearInterval(timerInterval);

        timerInterval = setInterval(() => {
            if (!started || finished) return;

            secondsLeft--;
            updateTimer();

            if (secondsLeft <= 0) {
                secondsLeft = 0;
                updateTimer();
                endGame(false, 'TIME EXPIRED');
            }
        }, 1000);
    }

    function updateTimer() {
        const minutes = Math.floor(secondsLeft / 60);
        const seconds = secondsLeft % 60;

        timer.textContent =
            String(minutes).padStart(2, '0') + ':' +
            String(seconds).padStart(2, '0');

        timer.classList.toggle('warning', secondsLeft <= 60);
    }

    function performAction(action) {
        if (!started || finished) return;

        const task = tasks[currentStep];

        if (!task) return;

        /*
         * Diagnostics is intentionally mapped to the final tool button area.
         * It is available after the file share step.
         */
        const expectedAction = task.action;

        if (action !== expectedAction) {
            score = Math.max(0, score - 15);
            setMessage(
                'Incorrect action. Current task: ' + task.name + '. Choose the matching server tool.',
                'error'
            );
            scoreDisplay.textContent = score;
            showToast('-15 points: incorrect server procedure.');
            return;
        }

        score += task.points;

        printTerminal(task.terminal, 'terminal-green');

        currentStep++;

        if (currentStep >= TOTAL_STEPS) {
            updateProgress();
            updateCurrentTask();
            renderTasks();
            endGame(true, 'SERVER CONFIGURATION COMPLETE');
            return;
        }

        updateProgress();
        updateCurrentTask();
        renderTasks();

        const nextTask = tasks[currentStep];

        setMessage(
            'SUCCESS: ' + task.name + ' completed. Next: ' + nextTask.name + '.',
            'success'
        );

        showToast('Step complete: +' + task.points + ' points.');
    }

    function endGame(success, title) {
        if (finished) return;

        finished = true;
        clearInterval(timerInterval);

        document.querySelectorAll('.action-btn').forEach(button => {
            button.disabled = true;
        });

        if (success) {
            serverStatus.textContent = 'READY';
            missionStatus.textContent = 'COMPLETE';
            topbarStatus.textContent = 'SERVER READY';

            document.getElementById('led1').classList.add('on');
            document.getElementById('led2').classList.add('on');
            document.getElementById('led3').classList.add('on');

            gameOverIcon.innerHTML = '<i class="fas fa-check-circle"></i>';
            gameOverTitle.textContent = title;
            gameOverMessage.textContent =
                'Excellent work. SERVER01 passed the COC 3 server setup and verification procedure.';
        } else {
            serverStatus.textContent = 'OFFLINE';
            missionStatus.textContent = 'FAILED';
            missionStatus.classList.remove('online');
            topbarStatus.textContent = 'TIME EXPIRED';

            gameOverIcon.innerHTML = '<i class="fas fa-triangle-exclamation"></i>';
            gameOverTitle.textContent = title;
            gameOverMessage.textContent =
                'The simulation ended before the server setup was completed. Restart and try again.';
        }

        finalScore.textContent = score;
        gameOver.classList.add('active');

        saveGameResult();
    }

    function saveGameResult() {
        if (savedGame) return;
        savedGame = true;

        const payload = new URLSearchParams();
        payload.append('action', 'save_game');
        payload.append('coc_number', '3');
        payload.append('score', String(score));
        payload.append('steps_completed', String(currentStep));
        payload.append('success', finished && currentStep === TOTAL_STEPS ? '1' : '0');

        /*
         * Save through the same page so no additional API endpoint is required.
         * The PHP handler is intentionally embedded below.
         */
        fetch(window.location.href, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: payload.toString(),
            credentials: 'same-origin'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                highScoreDisplay.textContent = data.high_score;
                gamesDisplay.textContent = data.games_played;
            }
        })
        .catch(() => {
            /*
             * Do not interrupt the completed game if the save request fails.
             * The score remains visible locally.
             */
        });
    }

    function restartGame() {
        clearInterval(timerInterval);

        started = false;
        finished = false;
        currentStep = 0;
        score = 0;
        secondsLeft = GAME_TIME;
        savedGame = false;

        gameOver.classList.remove('active');

        serverStatus.textContent = 'OFFLINE';
        serverStatus.classList.remove('online');

        missionStatus.textContent = 'READY';
        missionStatus.classList.remove('online');

        topbarStatus.textContent = 'POWER OFF';

        document.getElementById('led1').classList.remove('on');
        document.getElementById('led2').classList.remove('on');
        document.getElementById('led3').classList.remove('on');

        document.querySelectorAll('.action-btn').forEach(button => {
            button.disabled = true;
        });

        serverDisplay.innerHTML = `
            <div class="boot-screen">
                <div class="boot-logo"><i class="fas fa-server"></i></div>
                <h3>SERVER01 • WINDOWS SERVER</h3>
                <p>
                    Server is powered off. Start the simulation to begin
                    the COC 3 server setup procedure.
                </p>
                <button class="boot-btn" id="startBtn2" type="button">
                    <i class="fas fa-power-off"></i> POWER ON SERVER
                </button>
            </div>
        `;

        document.getElementById('startBtn2').addEventListener('click', startGame);

        timer.classList.remove('warning');
        updateTimer();
        updateProgress();
        updateCurrentTask();
        renderTasks();

        setMessage('Select POWER ON SERVER to start a new simulation.', '');
    }

    function escapeHtml(value) {
        return String(value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    /*
     * TOOL BUTTONS
     */
    document.querySelectorAll('.action-btn').forEach(button => {
        button.addEventListener('click', function () {
            performAction(this.dataset.action);
        });
    });

    startBtn.addEventListener('click', startGame);
    restartBtn.addEventListener('click', restartGame);
    playAgainBtn.addEventListener('click', restartGame);

    /*
     * THEME
     */
    const themeToggle = document.getElementById('themeToggle');
    const themeIcon = document.getElementById('themeIcon');
    const themeText = document.getElementById('themeText');

    const savedTheme =
        localStorage.getItem('css_tesda_theme') === 'light'
            ? 'light'
            : 'dark';

    function applyTheme(theme) {
        const light = theme === 'light';

        document.body.classList.toggle('light-mode', light);
        document.body.classList.toggle('dark-mode', !light);

        themeIcon.className = light ? 'fas fa-sun' : 'fas fa-moon';
        themeText.textContent = light ? 'Light Mode' : 'Dark Mode';

        localStorage.setItem('css_tesda_theme', light ? 'light' : 'dark');
    }

    applyTheme(savedTheme);

    themeToggle.addEventListener('click', function () {
        applyTheme(
            document.body.classList.contains('light-mode')
                ? 'dark'
                : 'light'
        );
    });

    /*
     * SHARED BRIGHTNESS
     * Compatible with the project's brightness_widget.php/localStorage.
     */
    function applyBrightness(value) {
        let brightness = parseFloat(value);

        if (!Number.isFinite(brightness)) {
            brightness = 100;
        }

        brightness = Math.max(50, Math.min(150, brightness));

        document.documentElement.style.setProperty(
            '--global-brightness',
            brightness + '%'
        );
    }

    applyBrightness(<?php echo json_encode($global_brightness); ?>);

    const storedBrightness = localStorage.getItem('css_tesda_brightness');

    if (storedBrightness !== null) {
        applyBrightness(storedBrightness);
    }

    window.addEventListener('storage', function (event) {
        if (event.key === 'css_tesda_brightness') {
            applyBrightness(event.newValue);
        }
    });

    window.addEventListener('css_tesda_brightness', function (event) {
        if (event.detail !== undefined) {
            applyBrightness(event.detail);
        }
    });

    updateTimer();
    updateProgress();
    updateCurrentTask();
    renderTasks();
})();
</script>

<?php
/*
|--------------------------------------------------------------------------
| AJAX SCORE SAVE
|--------------------------------------------------------------------------
| This block runs after the page has been rendered. For normal page loads
| nothing is returned. For AJAX save requests, return JSON and stop.
*/
if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['action']) &&
    $_POST['action'] === 'save_game'
) {
    header('Content-Type: application/json; charset=UTF-8');

    $posted_coc = isset($_POST['coc_number']) ? (int)$_POST['coc_number'] : 0;
    $posted_score = isset($_POST['score']) ? max(0, (int)$_POST['score']) : 0;
    $posted_steps = isset($_POST['steps_completed']) ? max(0, min(10, (int)$_POST['steps_completed'])) : 0;

    if ($posted_coc !== 3) {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid COC number.'
        ]);
        exit();
    }

    $new_high_score = $posted_score;
    $new_games_played = 1;

    $check = $conn->prepare(
        "SELECT high_score, games_played
         FROM user_game_scores
         WHERE user_id = ? AND coc_number = ?
         LIMIT 1"
    );

    if (!$check) {
        echo json_encode([
            'success' => false,
            'message' => 'Unable to prepare score lookup.'
        ]);
        exit();
    }

    $check->bind_param("ii", $user_id, $posted_coc);
    $check->execute();
    $existing = $check->get_result();

    if ($existing && $existing->num_rows > 0) {
        $old = $existing->fetch_assoc();

        $old_high = (int)($old['high_score'] ?? 0);
        $old_games = (int)($old['games_played'] ?? 0);

        $new_high_score = max($old_high, $posted_score);
        $new_games_played = $old_games + 1;

        $check->close();

        $update = $conn->prepare(
            "UPDATE user_game_scores
             SET high_score = ?, games_played = ?
             WHERE user_id = ? AND coc_number = ?"
        );

        if ($update) {
            $update->bind_param(
                "iiii",
                $new_high_score,
                $new_games_played,
                $user_id,
                $posted_coc
            );
            $ok = $update->execute();
            $update->close();

            if (!$ok) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Unable to save score.'
                ]);
                exit();
            }
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Unable to prepare score update.'
            ]);
            exit();
        }
    } else {
        $check->close();

        $insert = $conn->prepare(
            "INSERT INTO user_game_scores
             (user_id, coc_number, high_score, games_played)
             VALUES (?, ?, ?, ?)"
        );

        if ($insert) {
            $insert->bind_param(
                "iiii",
                $user_id,
                $posted_coc,
                $new_high_score,
                $new_games_played
            );
            $ok = $insert->execute();
            $insert->close();

            if (!$ok) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Unable to create score record.'
                ]);
                exit();
            }
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Unable to prepare score insert.'
            ]);
            exit();
        }
    }

    echo json_encode([
        'success' => true,
        'high_score' => $new_high_score,
        'games_played' => $new_games_played,
        'steps_completed' => $posted_steps
    ]);
    exit();
}
?>

<?php
if (file_exists(__DIR__ . '/includes/brightness_widget.php')) {
    include __DIR__ . '/includes/brightness_widget.php';
}
?>

</body>
</html>

$high_score = 0;
$games_played = 0;

/*
|--------------------------------------------------------------------------
| LOAD COC 3 GAME STATS
|--------------------------------------------------------------------------
| Uses the same user_game_scores table used by the other COC games.
*/
$score_query = "SELECT high_score, games_played
                FROM user_game_scores
                WHERE user_id = ? AND coc_number = ?
                LIMIT 1";

if ($stmt = $conn->prepare($score_query)) {
    $stmt->bind_param("ii", $user_id, $coc);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $high_score = (int)($row['high_score'] ?? 0);
        $games_played = (int)($row['games_played'] ?? 0);
    }

    $stmt->close();
}

$global_brightness = 100;
if (function_exists('getGlobalBrightness')) {
    $global_brightness = getGlobalBrightness();
}
$global_brightness = is_numeric($global_brightness) ? (float)$global_brightness : 100;
$global_brightness = max(50, min(150, $global_brightness));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
<meta name="theme-color" content="#050812">
<title><?php echo htmlspecialchars($page_title, ENT_QUOTES, 'UTF-8'); ?> | CSS TESDA Reviewer</title>

<link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;600;700;900&family=Rajdhani:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

<style>
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

:root {
    --global-brightness: <?php echo $global_brightness; ?>%;

    --bg-primary: #050812;
    --bg-secondary: #0a1020;
    --bg-card: rgba(10, 20, 40, 0.92);
    --bg-panel: rgba(0, 0, 0, 0.32);
    --bg-navbar: rgba(5, 8, 18, 0.94);

    --text-primary: #e5edf8;
    --text-secondary: #aab7cc;

    --green: #00ff9d;
    --blue: #0066ff;
    --orange: #ffaa00;
    --red: #ff4757;

    --border: rgba(0, 255, 157, 0.23);
    --border-light: rgba(0, 255, 157, 0.14);
    --grid-color: rgba(0, 255, 157, 0.07);

    --cyber-bg1: #050812;
    --cyber-bg2: #0a1020;
}

body.light-mode {
    --bg-primary: #f4f7fb;
    --bg-secondary: #ffffff;
    --bg-card: rgba(255,255,255,.96);
    --bg-panel: rgba(255,255,255,.92);
    --bg-navbar: rgba(255,255,255,.94);
    --text-primary: #172033;
    --text-secondary: #4b5870;
    --border: #d6deea;
    --border-light: #dce4ef;
    --grid-color: rgba(0,120,180,.08);
    --cyber-bg1: #eef4fb;
    --cyber-bg2: #ffffff;
}

body.dark-mode {
    --bg-primary: #050812;
    --bg-secondary: #0a1020;
    --bg-card: rgba(10,20,40,.92);
    --bg-panel: rgba(0,0,0,.32);
    --bg-navbar: rgba(5,8,18,.94);
    --text-primary: #e5edf8;
    --text-secondary: #aab7cc;
    --border: rgba(0,255,157,.23);
    --border-light: rgba(0,255,157,.14);
    --grid-color: rgba(0,255,157,.07);
    --cyber-bg1: #050812;
    --cyber-bg2: #0a1020;
}

html {
    scrollbar-width: thin;
    scrollbar-color: rgba(128,128,128,.45) transparent;
}

::-webkit-scrollbar {
    width: 8px;
    height: 8px;
}

::-webkit-scrollbar-track {
    background: transparent;
}

::-webkit-scrollbar-thumb {
    background: rgba(128,128,128,.45);
    border-radius: 10px;
}

::-webkit-scrollbar-thumb:hover {
    background: rgba(128,128,128,.65);
}

body {
    font-family: 'Rajdhani', sans-serif;
    background: var(--bg-primary);
    color: var(--text-primary);
    min-height: 100vh;
    overflow-x: hidden;
    transition: background .3s ease, color .3s ease;
    -webkit-tap-highlight-color: transparent;
}

#pageContent {
    min-height: 100vh;
    filter: brightness(var(--global-brightness, 100%));
    transition: filter .2s ease;
}

.cyber-bg,
.grid-overlay {
    position: fixed;
    inset: 0;
    pointer-events: none;
}

.cyber-bg {
    z-index: -2;
    background: linear-gradient(135deg, var(--cyber-bg1), var(--cyber-bg2));
}

.grid-overlay {
    z-index: -1;
    background-image:
        linear-gradient(var(--grid-color) 1px, transparent 1px),
        linear-gradient(90deg, var(--grid-color) 1px, transparent 1px);
    background-size: 50px 50px;
    animation: gridMove 20s linear infinite;
}

@keyframes gridMove {
    from { transform: translate(0,0); }
    to { transform: translate(50px,50px); }
}

/* NAVIGATION */
.navbar {
    position: sticky;
    top: 0;
    z-index: 100;
    background: var(--bg-navbar);
    backdrop-filter: blur(10px);
    border-bottom: 1px solid var(--border-light);
    padding: 1rem 2rem;
}

.nav-container {
    max-width: 1450px;
    margin: auto;
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 1rem;
}

.logo {
    color: var(--green);
    font-family: 'Orbitron', sans-serif;
    font-size: 1.35rem;
    font-weight: 900;
    text-decoration: none;
    white-space: nowrap;
    background: linear-gradient(135deg,var(--green),var(--blue));
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
}

.nav-links {
    display: flex;
    align-items: center;
    gap: 1.15rem;
    flex-wrap: wrap;
}

.nav-links a {
    color: var(--text-secondary);
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 7px;
    transition: .25s;
}

.nav-links a:hover,
.nav-links a.active {
    color: var(--green);
}

.user-info {
    display: flex;
    align-items: center;
    gap: .7rem;
    color: var(--green);
}

.theme-toggle {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    border: 1px solid var(--border);
    background: var(--bg-card);
    color: var(--text-primary);
    border-radius: 999px;
    padding: 7px 12px;
    font-family: 'Rajdhani', sans-serif;
    font-weight: 700;
    cursor: pointer;
}

.theme-toggle:hover {
    border-color: var(--green);
    color: var(--green);
}

/* PAGE */
.container {
    width: min(1450px, calc(100% - 32px));
    margin: auto;
    padding: 1.7rem 0 3rem;
}

.game-header {
    text-align: center;
    margin-bottom: 1.4rem;
}

.coc-badge {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 7px 18px;
    border: 1px solid var(--green);
    border-radius: 999px;
    color: var(--green);
    background: rgba(0,255,157,.08);
    font-family: 'Orbitron', sans-serif;
    font-size: .78rem;
    margin-bottom: .8rem;
}

.game-header h1 {
    font-family: 'Orbitron', sans-serif;
    font-size: clamp(1.5rem, 3vw, 2.5rem);
    background: linear-gradient(135deg,var(--green),var(--blue));
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    margin-bottom: .45rem;
}

.game-header p {
    color: var(--text-secondary);
    font-size: 1rem;
}

.instructions-panel {
    background: var(--bg-card);
    border: 1px solid var(--border);
    border-radius: 16px;
    padding: 1rem 1.2rem;
    margin-bottom: 1rem;
}

.instructions-panel h2 {
    color: var(--green);
    font-family: 'Orbitron', sans-serif;
    font-size: .95rem;
    margin-bottom: .5rem;
}

.instructions-panel p {
    color: var(--text-secondary);
    line-height: 1.5;
}

.stats-bar {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: .8rem;
    margin-bottom: 1rem;
}

.stat-card {
    background: var(--bg-card);
    border: 1px solid var(--border);
    border-radius: 13px;
    padding: .75rem;
    text-align: center;
}

.stat-value {
    color: var(--green);
    font-family: 'Orbitron', sans-serif;
    font-size: 1.2rem;
    font-weight: 700;
}

.stat-label {
    color: var(--text-secondary);
    font-size: .72rem;
    margin-top: 2px;
}

/* GAME LAYOUT */
.game-layout {
    display: grid;
    grid-template-columns: minmax(0, 1.75fr) minmax(300px, 1fr);
    gap: 1rem;
    align-items: start;
}

.server-panel,
.control-panel {
    background: var(--bg-card);
    border: 1px solid var(--border);
    border-radius: 18px;
    min-width: 0;
}

.server-panel {
    padding: 1rem;
}

.control-panel {
    padding: 1rem;
    position: sticky;
    top: 88px;
}

.panel-title {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: .7rem;
    margin-bottom: .8rem;
    padding-bottom: .65rem;
    border-bottom: 1px solid var(--border-light);
}

.panel-title h2 {
    color: var(--green);
    font-family: 'Orbitron', sans-serif;
    font-size: .95rem;
}

.status-chip {
    padding: 4px 9px;
    border-radius: 999px;
    font-size: .65rem;
    font-weight: 700;
    color: var(--orange);
    border: 1px solid rgba(255,170,0,.35);
    background: rgba(255,170,0,.08);
}

.status-chip.online {
    color: var(--green);
    border-color: rgba(0,255,157,.35);
    background: rgba(0,255,157,.08);
}

/* SERVER DESKTOP */
.server-screen {
    border: 8px solid #202b3d;
    border-radius: 12px;
    background: #080d16;
    overflow: hidden;
    box-shadow: 0 0 0 1px rgba(0,255,157,.18), 0 15px 35px rgba(0,0,0,.35);
}

.server-topbar {
    background: #111a2b;
    color: #9eb2cf;
    padding: 7px 10px;
    display: flex;
    justify-content: space-between;
    gap: 8px;
    font-family: monospace;
    font-size: .7rem;
}

.server-display {
    min-height: 410px;
    padding: 1rem;
    background:
        radial-gradient(circle at 50% 25%, rgba(0,102,255,.12), transparent 38%),
        #07101b;
    color: #d9e8ff;
    font-family: monospace;
    overflow: auto;
}

.boot-screen {
    min-height: 378px;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    text-align: center;
    gap: .8rem;
}

.boot-logo {
    width: 70px;
    height: 70px;
    border: 2px solid var(--green);
    border-radius: 14px;
    display: grid;
    place-items: center;
    color: var(--green);
    font-size: 2rem;
    box-shadow: 0 0 25px rgba(0,255,157,.16);
}

.boot-screen h3 {
    color: #fff;
    font-family: 'Orbitron', sans-serif;
    font-size: 1rem;
}

.boot-screen p {
    color: #91a4bd;
    max-width: 520px;
}

.boot-btn {
    border: 1px solid var(--green);
    background: rgba(0,255,157,.08);
    color: var(--green);
    padding: 10px 18px;
    border-radius: 8px;
    font-family: 'Orbitron', sans-serif;
    font-weight: 700;
    cursor: pointer;
}

.boot-btn:hover {
    background: var(--green);
    color: #061019;
}

.terminal-line {
    margin-bottom: 5px;
    white-space: pre-wrap;
}

.terminal-green { color: #00ff9d; }
.terminal-blue { color: #62a9ff; }
.terminal-yellow { color: #ffd166; }
.terminal-red { color: #ff6472; }

.server-rack {
    margin-top: 1rem;
    border: 1px solid #33435c;
    border-radius: 10px;
    background: #101827;
    padding: .65rem;
}

.rack-label {
    color: #7285a3;
    font-family: monospace;
    font-size: .65rem;
    margin-bottom: .45rem;
}

.rack-unit {
    height: 58px;
    border: 1px solid #3d4e68;
    border-radius: 7px;
    background: linear-gradient(#1b2638,#0d1522);
    display: flex;
    align-items: center;
    gap: .7rem;
    padding: .55rem .7rem;
}

.rack-leds {
    display: flex;
    gap: 5px;
}

.rack-led {
    width: 7px;
    height: 7px;
    border-radius: 50%;
    background: #34445e;
}

.rack-led.on {
    background: var(--green);
    box-shadow: 0 0 8px var(--green);
}

.rack-name {
    color: #d9e4f5;
    font-family: 'Orbitron', sans-serif;
    font-size: .72rem;
}

.rack-model {
    color: #71819a;
    font-size: .65rem;
    margin-left: auto;
}

/* PROGRESS */
.progress-box {
    margin-bottom: .8rem;
}

.progress-info {
    display: flex;
    justify-content: space-between;
    color: var(--text-secondary);
    font-size: .75rem;
    margin-bottom: 5px;
}

.progress-track {
    height: 9px;
    border-radius: 99px;
    background: rgba(128,128,128,.18);
    overflow: hidden;
}

.progress-fill {
    height: 100%;
    width: 0;
    background: linear-gradient(90deg,var(--green),var(--blue));
    transition: width .35s ease;
}

/* TASKS */
.task-list {
    display: grid;
    gap: .55rem;
}

.task {
    display: grid;
    grid-template-columns: 28px minmax(0,1fr) auto;
    gap: .6rem;
    align-items: center;
    padding: .65rem;
    border: 1px solid var(--border-light);
    border-radius: 10px;
    background: rgba(0,0,0,.12);
    cursor: pointer;
    transition: .2s;
}

.task:hover {
    border-color: var(--green);
}

.task.active {
    border-color: var(--blue);
    box-shadow: 0 0 0 1px rgba(0,102,255,.18);
}

.task.done {
    border-color: rgba(0,255,157,.35);
    background: rgba(0,255,157,.045);
}

.task-number {
    width: 28px;
    height: 28px;
    border-radius: 50%;
    display: grid;
    place-items: center;
    background: rgba(0,102,255,.12);
    border: 1px solid rgba(0,102,255,.35);
    color: #70aaff;
    font-family: 'Orbitron', sans-serif;
    font-size: .65rem;
}

.task.done .task-number {
    color: #061019;
    background: var(--green);
    border-color: var(--green);
}

.task-name {
    font-weight: 700;
    color: var(--text-primary);
    font-size: .78rem;
}

.task-desc {
    color: var(--text-secondary);
    font-size: .65rem;
}

.task-status {
    font-size: .62rem;
    color: #71819a;
    white-space: nowrap;
}

.task.done .task-status {
    color: var(--green);
}

/* CONTROLS */
.timer-box {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: .75rem;
    border: 1px solid var(--border-light);
    border-radius: 10px;
    margin-bottom: .8rem;
}

.timer-label {
    color: var(--text-secondary);
    font-size: .72rem;
}

.timer {
    font-family: 'Orbitron', sans-serif;
    color: var(--orange);
    font-weight: 700;
}

.timer.warning { color: var(--red); }

.action-box {
    padding: .75rem;
    border: 1px solid var(--border-light);
    border-radius: 10px;
    margin-bottom: .8rem;
}

.action-box h3 {
    color: var(--green);
    font-family: 'Orbitron', sans-serif;
    font-size: .75rem;
    margin-bottom: .55rem;
}

.action-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0,1fr));
    gap: .5rem;
}

.action-btn {
    min-height: 58px;
    border: 1px solid rgba(0,255,157,.2);
    border-radius: 9px;
    background: rgba(0,255,157,.045);
    color: var(--text-primary);
    cursor: pointer;
    font-family: 'Rajdhani', sans-serif;
    font-weight: 700;
    font-size: .72rem;
    padding: .45rem;
    transition: .2s;
}

.action-btn i {
    display: block;
    color: var(--green);
    margin-bottom: 4px;
    font-size: .9rem;
}

.action-btn:hover:not(:disabled) {
    border-color: var(--green);
    transform: translateY(-1px);
}

.action-btn:disabled {
    opacity: .38;
    cursor: not-allowed;
}

.current-task {
    border: 1px solid rgba(0,102,255,.35);
    background: rgba(0,102,255,.06);
    border-radius: 10px;
    padding: .75rem;
    margin-bottom: .8rem;
}

.current-task h3 {
    color: #71aaff;
    font-family: 'Orbitron', sans-serif;
    font-size: .75rem;
    margin-bottom: .35rem;
}

.current-task p {
    color: var(--text-secondary);
    font-size: .72rem;
    line-height: 1.4;
}

.control-buttons {
    display: grid;
    gap: .5rem;
}

.btn {
    border: none;
    border-radius: 9px;
    padding: 10px 14px;
    font-family: 'Orbitron', sans-serif;
    font-weight: 700;
    cursor: pointer;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 7px;
    transition: .2s;
}

.btn-primary {
    background: linear-gradient(45deg,var(--green),var(--blue));
    color: #061019;
}

.btn-secondary {
    color: var(--green);
    background: transparent;
    border: 1px solid var(--green);
}

.btn-danger {
    color: #fff;
    background: rgba(255,71,87,.12);
    border: 1px solid rgba(255,71,87,.45);
}

.btn:hover {
    transform: translateY(-1px);
}

.message-box {
    min-height: 42px;
    padding: .65rem;
    border-radius: 9px;
    border: 1px solid var(--border-light);
    color: var(--text-secondary);
    font-size: .72rem;
    line-height: 1.4;
    margin-bottom: .8rem;
}

.message-box.success {
    color: var(--green);
    border-color: rgba(0,255,157,.35);
    background: rgba(0,255,157,.05);
}

.message-box.error {
    color: #ff7c86;
    border-color: rgba(255,71,87,.35);
    background: rgba(255,71,87,.05);
}

/* GAME OVER */
.game-over {
    display: none;
    position: fixed;
    inset: 0;
    z-index: 5000;
    align-items: center;
    justify-content: center;
    padding: 1rem;
    background: rgba(0,0,0,.82);
    backdrop-filter: blur(8px);
}

.game-over.active {
    display: flex;
}

.game-over-card {
    width: min(520px, 100%);
    background: var(--bg-card);
    border: 2px solid var(--green);
    border-radius: 20px;
    padding: 2rem;
    text-align: center;
    box-shadow: 0 20px 70px rgba(0,255,157,.18);
}

.game-over-icon {
    font-size: 3rem;
    color: var(--green);
    margin-bottom: .7rem;
}

.game-over-card h2 {
    font-family: 'Orbitron', sans-serif;
    color: var(--green);
    margin-bottom: .5rem;
}

.final-score {
    font-family: 'Orbitron', sans-serif;
    font-size: 2.4rem;
    color: var(--orange);
    margin: .7rem 0;
}

.game-over-card p {
    color: var(--text-secondary);
    margin-bottom: 1rem;
}

/* TOAST */
.toast {
    position: fixed;
    right: 18px;
    bottom: 18px;
    z-index: 6000;
    max-width: 340px;
    padding: .8rem 1rem;
    background: var(--bg-card);
    border: 1px solid var(--green);
    color: var(--text-primary);
    border-radius: 10px;
    transform: translateY(120px);
    opacity: 0;
    transition: .25s;
    pointer-events: none;
}

.toast.show {
    transform: translateY(0);
    opacity: 1;
}

/* MOBILE - KEEP SERVER AND CONTROL PANELS SIDE-BY-SIDE */
@media (max-width: 900px) {
    .navbar {
        padding: .75rem .8rem;
    }

    .nav-container {
        align-items: flex-start;
    }

    .logo {
        font-size: .95rem;
    }

    .nav-links {
        gap: .55rem;
        justify-content: flex-end;
    }

    .nav-links > a {
        display: none;
    }

    .user-info {
        font-size: .72rem;
    }

    .container {
        width: calc(100% - 10px);
        padding: .7rem 0 2rem;
    }

    .game-header {
        margin-bottom: .75rem;
    }

    .game-header h1 {
        font-size: 1.25rem;
    }

    .game-header p {
        font-size: .76rem;
    }

    .coc-badge {
        font-size: .62rem;
        padding: 5px 10px;
    }

    .instructions-panel {
        padding: .7rem;
    }

    .instructions-panel h2 {
        font-size: .72rem;
    }

    .instructions-panel p {
        font-size: .65rem;
    }

    .stats-bar {
        grid-template-columns: repeat(4, 1fr);
        gap: .35rem;
    }

    .stat-card {
        padding: .5rem .2rem;
    }

    .stat-value {
        font-size: .78rem;
    }

    .stat-label {
        font-size: .52rem;
    }

    .game-layout {
        grid-template-columns: minmax(0, 1.75fr) minmax(118px, 1fr);
        gap: .5rem;
    }

    .server-panel,
    .control-panel {
        padding: .55rem;
        border-radius: 11px;
    }

    .control-panel {
        position: static;
    }

    .panel-title h2 {
        font-size: .65rem;
    }

    .status-chip {
        font-size: .48rem;
        padding: 3px 5px;
    }

    .server-screen {
        border-width: 4px;
    }

    .server-topbar {
        padding: 4px 5px;
        font-size: .48rem;
    }

    .server-display {
        min-height: 310px;
        padding: .5rem;
        font-size: .53rem;
    }

    .boot-screen {
        min-height: 280px;
    }

    .boot-logo {
        width: 45px;
        height: 45px;
        font-size: 1.2rem;
    }

    .boot-screen h3 {
        font-size: .65rem;
    }

    .boot-screen p {
        font-size: .55rem;
    }

    .boot-btn {
        font-size: .55rem;
        padding: 7px 9px;
    }

    .server-rack {
        margin-top: .5rem;
        padding: .35rem;
    }

    .rack-label {
        font-size: .45rem;
    }

    .rack-unit {
        height: 42px;
        padding: .35rem;
        gap: .3rem;
    }

    .rack-name {
        font-size: .48rem;
    }

    .rack-model {
        font-size: .4rem;
    }

    .rack-led {
        width: 5px;
        height: 5px;
    }

    .timer-box {
        padding: .5rem;
    }

    .timer-label,
    .timer {
        font-size: .55rem;
    }

    .current-task,
    .action-box,
    .message-box {
        padding: .5rem;
    }

    .current-task h3,
    .action-box h3 {
        font-size: .57rem;
    }

    .current-task p,
    .message-box {
        font-size: .53rem;
    }

    .action-grid {
        grid-template-columns: 1fr;
        gap: .3rem;
    }

    .action-btn {
        min-height: 46px;
        font-size: .52rem;
        padding: .3rem;
    }

    .action-btn i {
        font-size: .7rem;
        margin-bottom: 2px;
    }

    .task {
        grid-template-columns: 20px minmax(0,1fr);
        gap: .35rem;
        padding: .4rem;
    }

    .task-number {
        width: 20px;
        height: 20px;
        font-size: .45rem;
    }

    .task-name {
        font-size: .52rem;
    }

    .task-desc {
        display: none;
    }

    .task-status {
        grid-column: 2;
        font-size: .45rem;
        margin-top: -3px;
    }

    .progress-info {
        font-size: .52rem;
    }

    .progress-track {
        height: 6px;
    }

    .btn {
        padding: 7px 8px;
        font-size: .52rem;
    }
}

@media (max-width: 430px) {
    .game-layout {
        grid-template-columns: minmax(0, 1.65fr) minmax(105px, 1fr);
    }

    .user-info span {
        display: none;
    }

    .server-display {
        min-height: 280px;
    }
}
</style>
</head>

<body class="dark-mode">

<div id="pageContent">
    <div class="cyber-bg"></div>
    <div class="grid-overlay"></div>

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
                    <span><?php echo htmlspecialchars($username, ENT_QUOTES, 'UTF-8'); ?></span>

                    <button type="button" class="theme-toggle" id="themeToggle">
                        <i class="fas fa-moon" id="themeIcon"></i>
                        <span id="themeText">Dark Mode</span>
                    </button>

                    <a href="logout.php" style="color:#ef4444;" aria-label="Logout">
                        <i class="fas fa-sign-out-alt"></i>
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <main class="container">

        <section class="game-header">
            <div class="coc-badge">
                <i class="fas fa-server"></i>
                COC 3: SET-UP COMPUTER SERVERS
            </div>
            <h1>SERVER ADMINISTRATION SIMULATOR</h1>
            <p>Configure, secure, and verify a Windows-based computer server.</p>
        </section>

        <section class="instructions-panel">
            <h2><i class="fas fa-terminal"></i> SIMULATION OBJECTIVE</h2>
            <p>
                Complete the server setup in the correct order. Inspect the hardware,
                prepare storage, install the operating system, configure network services,
                create server roles, secure the system, and perform the final verification.
            </p>
        </section>

        <section class="stats-bar">
            <div class="stat-card">
                <div class="stat-value" id="scoreDisplay">0</div>
                <div class="stat-label">CURRENT SCORE</div>
            </div>
            <div class="stat-card">
                <div class="stat-value" id="highScoreDisplay"><?php echo $high_score; ?></div>
                <div class="stat-label">HIGH SCORE</div>
            </div>
            <div class="stat-card">
                <div class="stat-value" id="gamesDisplay"><?php echo $games_played; ?></div>
                <div class="stat-label">GAMES PLAYED</div>
            </div>
            <div class="stat-card">
                <div class="stat-value" id="stepDisplay">0/10</div>
                <div class="stat-label">STEPS COMPLETE</div>
            </div>
        </section>

        <section class="game-layout">

            <!-- SERVER SIDE -->
            <div class="server-panel">
                <div class="panel-title">
                    <h2><i class="fas fa-server"></i> SERVER CONSOLE</h2>
                    <span class="status-chip" id="serverStatus">OFFLINE</span>
                </div>

                <div class="server-screen">
                    <div class="server-topbar">
                        <span>SERVER01</span>
                        <span id="topbarStatus">POWER OFF</span>
                    </div>

                    <div class="server-display" id="serverDisplay">
                        <div class="boot-screen" id="bootScreen">
                            <div class="boot-logo"><i class="fas fa-server"></i></div>
                            <h3>SERVER01 • WINDOWS SERVER</h3>
                            <p>
                                Server is powered off. Start the simulation to begin
                                the COC 3 server setup procedure.
                            </p>
                            <button class="boot-btn" id="startBtn" type="button">
                                <i class="fas fa-power-off"></i> POWER ON SERVER
                            </button>
                        </div>
                    </div>
                </div>

                <div class="server-rack">
                    <div class="rack-label">RACK MOUNT • SERVER01</div>
                    <div class="rack-unit">
                        <div class="rack-leds">
                            <span class="rack-led" id="led1"></span>
                            <span class="rack-led" id="led2"></span>
                            <span class="rack-led" id="led3"></span>
                        </div>
                        <span class="rack-name">SERVER01</span>
                        <span class="rack-model">2U COMPUTE NODE • 32 GB ECC • RAID</span>
                    </div>
                </div>
            </div>

            <!-- CONTROL SIDE -->
            <aside class="control-panel">
                <div class="panel-title">
                    <h2><i class="fas fa-cogs"></i> CONTROL PANEL</h2>
                    <span class="status-chip" id="missionStatus">READY</span>
                </div>

                <div class="timer-box">
                    <span class="timer-label"><i class="fas fa-stopwatch"></i> TIME</span>
                    <span class="timer" id="timer">10:00</span>
                </div>

                <div class="progress-box">
                    <div class="progress-info">
                        <span>SETUP PROGRESS</span>
                        <span id="progressText">0%</span>
                    </div>
                    <div class="progress-track">
                        <div class="progress-fill" id="progressFill"></div>
                    </div>
                </div>

                <div class="current-task">
                    <h3><i class="fas fa-crosshairs"></i> CURRENT TASK</h3>
                    <p id="currentTask">
                        Power on the server to begin the installation procedure.
                    </p>
                </div>

                <div class="action-box">
                    <h3><i class="fas fa-wrench"></i> SERVER TOOLS</h3>

                    <div class="action-grid">
                        <button class="action-btn" data-action="inspect" disabled>
                            <i class="fas fa-search"></i>
                            Inspect Hardware
                        </button>

                        <button class="action-btn" data-action="storage" disabled>
                            <i class="fas fa-hdd"></i>
                            Configure RAID
                        </button>

                        <button class="action-btn" data-action="os" disabled>
                            <i class="fab fa-windows"></i>
                            Install OS
                        </button>

                        <button class="action-btn" data-action="network" disabled>
                            <i class="fas fa-network-wired"></i>
                            Configure Network
                        </button>

                        <button class="action-btn" data-action="dns" disabled>
                            <i class="fas fa-globe"></i>
                            Configure DNS
                        </button>

                        <button class="action-btn" data-action="ad" disabled>
                            <i class="fas fa-users"></i>
                            Install AD DS
                        </button>

                        <button class="action-btn" data-action="share" disabled>
                            <i class="fas fa-folder-open"></i>
                            Create File Share
                        </button>

                        <button class="action-btn" data-action="security" disabled>
                            <i class="fas fa-shield-alt"></i>
                            Harden Server
                        </button>

                        <button class="action-btn" data-action="verify" disabled>
                            <i class="fas fa-check-double"></i>
                            Verify Server
                        </button>
                    </div>
                </div>

                <div class="message-box" id="messageBox">
                    Select the correct tool for the current task.
                </div>

                <div class="control-buttons">
                    <button class="btn btn-danger" id="restartBtn" type="button">
                        <i class="fas fa-redo"></i> Restart Simulation
                    </button>
                    <a class="btn btn-secondary" href="coc3_review.php">
                        <i class="fas fa-book"></i> COC 3 Review
                    </a>
                </div>

                <div style="margin-top:.8rem;">
                    <div class="progress-info">
                        <span><i class="fas fa-list-check"></i> TASK CHECKLIST</span>
                    </div>
                    <div class="task-list" id="taskList"></div>
                </div>
            </aside>

        </section>
    </main>
</div>

<div class="game-over" id="gameOver">
    <div class="game-over-card">
        <div class="game-over-icon" id="gameOverIcon">
            <i class="fas fa-server"></i>
        </div>
        <h2 id="gameOverTitle">SERVER READY</h2>
        <div class="final-score" id="finalScore">0</div>
        <p id="gameOverMessage">Simulation completed.</p>

        <div class="control-buttons">
            <button class="btn btn-primary" id="playAgainBtn" type="button">
                <i class="fas fa-redo"></i> Play Again
            </button>
            <a class="btn btn-secondary" href="coc3_review.php">
                <i class="fas fa-book"></i> Back to COC 3 Review
            </a>
        </div>
    </div>
</div>

<div class="toast" id="toast"></div>

<script>
(function () {
    'use strict';

    const TOTAL_STEPS = 10;
    const GAME_TIME = 600;

    const tasks = [
        {
            id: 1,
            name: 'Inspect Server Hardware',
            desc: 'Check CPU, ECC RAM, storage and network hardware.',
            action: 'inspect',
            points: 100,
            terminal: [
                'POST: PASS',
                'CPU: 8-CORE SERVER PROCESSOR ........ OK',
                'ECC RAM: 32 GB ........................ OK',
                'NIC: 2 x GIGABIT ETHERNET ............ OK',
                'STORAGE: 2 x 1 TB HDD ................ OK'
            ]
        },
        {
            id: 2,
            name: 'Configure RAID Storage',
            desc: 'Initialize the two-disk RAID-1 storage volume.',
            action: 'storage',
            points: 100,
            terminal: [
                'STORAGE CONTROLLER DETECTED',
                'DISK 0: 1 TB ......................... ONLINE',
                'DISK 1: 1 TB ......................... ONLINE',
                'RAID LEVEL: 1 ........................ MIRROR',
                'VOLUME: SERVER_OS .................... READY'
            ]
        },
        {
            id: 3,
            name: 'Install Windows Server',
            desc: 'Install the server operating system on the prepared volume.',
            action: 'os',
            points: 120,
            terminal: [
                'BOOT MEDIA: WINDOWS SERVER',
                'TARGET: SERVER_OS (RAID-1)',
                'COPYING INSTALLATION FILES .......... 100%',
                'INSTALLING SERVER COMPONENTS ......... DONE',
                'OS INSTALLATION ...................... COMPLETE'
            ]
        },
        {
            id: 4,
            name: 'Configure Network',
            desc: 'Assign a static IP address, gateway and hostname.',
            action: 'network',
            points: 110,
            terminal: [
                'HOSTNAME: SERVER01',
                'IP ADDRESS: 192.168.10.10',
                'SUBNET MASK: 255.255.255.0',
                'DEFAULT GATEWAY: 192.168.10.1',
                'NETWORK INTERFACE ................... CONNECTED'
            ]
        },
        {
            id: 5,
            name: 'Configure DNS',
            desc: 'Prepare DNS settings for server and domain services.',
            action: 'dns',
            points: 100,
            terminal: [
                'DNS ROLE: SERVER01',
                'PRIMARY DNS: 192.168.10.10',
                'FORWARD LOOKUPS ..................... READY',
                'REVERSE LOOKUPS ...................... READY',
                'DNS CONFIGURATION ................... VALID'
            ]
        },
        {
            id: 6,
            name: 'Install Active Directory',
            desc: 'Install AD DS and promote SERVER01 as a domain controller.',
            action: 'ad',
            points: 140,
            terminal: [
                'ROLE: ACTIVE DIRECTORY DOMAIN SERVICES',
                'DOMAIN: CSSLOCAL.LOCAL',
                'SERVER01 DOMAIN CONTROLLER .......... ONLINE',
                'GLOBAL CATALOG ....................... ENABLED',
                'AD DS INSTALLATION ................... COMPLETE'
            ]
        },
        {
            id: 7,
            name: 'Create File Share',
            desc: 'Create a shared folder for authorized users.',
            action: 'share',
            points: 90,
            terminal: [
                'FOLDER: C:\\SERVER-DATA',
                'SHARE NAME: SERVER-DATA',
                'NTFS PERMISSIONS .................... CONFIGURED',
                'SHARE PERMISSIONS ................... CONFIGURED',
                'FILE SERVICE ........................ READY'
            ]
        },
        {
            id: 8,
            name: 'Harden Server',
            desc: 'Apply firewall, updates and basic server security.',
            action: 'security',
            points: 110,
            terminal: [
                'WINDOWS FIREWALL .................... ENABLED',
                'SECURITY UPDATES .................... CHECKED',
                'UNUSED SERVICES ..................... REVIEWED',
                'ADMIN ACCOUNT ....................... PROTECTED',
                'SERVER HARDENING .................... COMPLETE'
            ]
        },
        {
            id: 9,
            name: 'Run Server Diagnostics',
            desc: 'Check services, network connectivity and event logs.',
            action: 'diagnostics',
            points: 100,
            terminal: [
                'PING GATEWAY ........................ PASS',
                'PING DNS SERVER ...................... PASS',
                'CRITICAL SERVICES .................... RUNNING',
                'EVENT LOG ............................ CLEAN',
                'DIAGNOSTICS .......................... PASS'
            ]
        },
        {
            id: 10,
            name: 'Final Server Verification',
            desc: 'Verify the complete server installation and configuration.',
            action: 'verify',
            points: 130,
            terminal: [
                'HOSTNAME ............................. SERVER01',
                'NETWORK .............................. PASS',
                'DNS .................................. PASS',
                'ACTIVE DIRECTORY ..................... PASS',
                'FILE SERVICES ......................... PASS',
                'SECURITY ............................. PASS',
                'FINAL SERVER STATUS .................. READY'
            ]
        }
    ];

    let currentStep = 0;
    let score = 0;
    let secondsLeft = GAME_TIME;
    let timerInterval = null;
    let started = false;
    let finished = false;
    let savedGame = false;

    const scoreDisplay = document.getElementById('scoreDisplay');
    const highScoreDisplay = document.getElementById('highScoreDisplay');
    const gamesDisplay = document.getElementById('gamesDisplay');
    const stepDisplay = document.getElementById('stepDisplay');
    const progressFill = document.getElementById('progressFill');
    const progressText = document.getElementById('progressText');
    const currentTask = document.getElementById('currentTask');
    const taskList = document.getElementById('taskList');
    const timer = document.getElementById('timer');
    const messageBox = document.getElementById('messageBox');
    const serverDisplay = document.getElementById('serverDisplay');
    const serverStatus = document.getElementById('serverStatus');
    const missionStatus = document.getElementById('missionStatus');
    const topbarStatus = document.getElementById('topbarStatus');
    const startBtn = document.getElementById('startBtn');
    const restartBtn = document.getElementById('restartBtn');
    const gameOver = document.getElementById('gameOver');
    const finalScore = document.getElementById('finalScore');
    const gameOverTitle = document.getElementById('gameOverTitle');
    const gameOverMessage = document.getElementById('gameOverMessage');
    const gameOverIcon = document.getElementById('gameOverIcon');
    const playAgainBtn = document.getElementById('playAgainBtn');
    const toast = document.getElementById('toast');

    function renderTasks() {
        taskList.innerHTML = tasks.map((task, index) => {
            const done = index < currentStep;
            const active = index === currentStep && started && !finished;

            return `
                <div class="task ${done ? 'done' : ''} ${active ? 'active' : ''}">
                    <div class="task-number">${done ? '<i class="fas fa-check"></i>' : task.id}</div>
                    <div>
                        <div class="task-name">${escapeHtml(task.name)}</div>
                        <div class="task-desc">${escapeHtml(task.desc)}</div>
                    </div>
                    <div class="task-status">${done ? 'COMPLETE' : active ? 'CURRENT' : 'LOCKED'}</div>
                </div>
            `;
        }).join('');
    }

    function updateProgress() {
        const percent = Math.round((currentStep / TOTAL_STEPS) * 100);
        progressFill.style.width = percent + '%';
        progressText.textContent = percent + '%';
        stepDisplay.textContent = currentStep + '/' + TOTAL_STEPS;
        scoreDisplay.textContent = score;
    }

    function updateCurrentTask() {
        if (!started) {
            currentTask.textContent = 'Power on the server to begin the installation procedure.';
            return;
        }

        if (finished) {
            currentTask.textContent = 'Server verification completed. The server is ready for production.';
            return;
        }

        const task = tasks[currentStep];

        if (task) {
            currentTask.textContent = task.desc;
        }
    }

    function setMessage(text, type) {
        messageBox.className = 'message-box' + (type ? ' ' + type : '');
        messageBox.textContent = text;
    }

    function showToast(text) {
        toast.textContent = text;
        toast.classList.add('show');

        clearTimeout(showToast.timeout);
        showToast.timeout = setTimeout(() => {
            toast.classList.remove('show');
        }, 2600);
    }

    function printTerminal(lines, colorClass) {
        serverDisplay.innerHTML = '';

        lines.forEach((line, index) => {
            const row = document.createElement('div');
            row.className = 'terminal-line ' + (colorClass || (index === lines.length - 1 ? 'terminal-green' : ''));
            row.textContent = '> ' + line;
            serverDisplay.appendChild(row);
        });

        const cursor = document.createElement('div');
        cursor.className = 'terminal-line terminal-green';
        cursor.textContent = '> _';
        serverDisplay.appendChild(cursor);

        serverDisplay.scrollTop = serverDisplay.scrollHeight;
    }

    function startGame() {
        if (started && !finished) return;

        started = true;
        finished = false;
        currentStep = 0;
        score = 0;
        secondsLeft = GAME_TIME;
        savedGame = false;

        gameOver.classList.remove('active');

        serverStatus.textContent = 'ONLINE';
        serverStatus.classList.add('online');
        missionStatus.textContent = 'ACTIVE';
        missionStatus.classList.add('online');
        topbarStatus.textContent = 'SYSTEM ONLINE';

        document.querySelectorAll('.action-btn').forEach(button => {
            button.disabled = false;
        });

        printTerminal([
            'POWER ON SELF TEST ................... PASS',
            'SERVER01 BIOS ......................... READY',
            'MEMORY CHECK .......................... 32768 MB',
            'BOOT DEVICE ........................... READY',
            'WAITING FOR ADMINISTRATOR ACTION...'
        ]);

        setMessage('Server is online. Start with "Inspect Hardware".', '');
        updateCurrentTask();
        updateProgress();
        renderTasks();

        startTimer();
        showToast('COC 3 server simulation started.');
    }

    function startTimer() {
        clearInterval(timerInterval);

        timerInterval = setInterval(() => {
            if (!started || finished) return;

            secondsLeft--;
            updateTimer();

            if (secondsLeft <= 0) {
                secondsLeft = 0;
                updateTimer();
                endGame(false, 'TIME EXPIRED');
            }
        }, 1000);
    }

    function updateTimer() {
        const minutes = Math.floor(secondsLeft / 60);
        const seconds = secondsLeft % 60;

        timer.textContent =
            String(minutes).padStart(2, '0') + ':' +
            String(seconds).padStart(2, '0');

        timer.classList.toggle('warning', secondsLeft <= 60);
    }

    function performAction(action) {
        if (!started || finished) return;

        const task = tasks[currentStep];

        if (!task) return;

        /*
         * Diagnostics is intentionally mapped to the final tool button area.
         * It is available after the file share step.
         */
        const expectedAction = task.action;

        if (action !== expectedAction) {
            score = Math.max(0, score - 15);
            setMessage(
                'Incorrect action. Current task: ' + task.name + '. Choose the matching server tool.',
                'error'
            );
            scoreDisplay.textContent = score;
            showToast('-15 points: incorrect server procedure.');
            return;
        }

        score += task.points;

        printTerminal(task.terminal, 'terminal-green');

        currentStep++;

        if (currentStep >= TOTAL_STEPS) {
            updateProgress();
            updateCurrentTask();
            renderTasks();
            endGame(true, 'SERVER CONFIGURATION COMPLETE');
            return;
        }

        updateProgress();
        updateCurrentTask();
        renderTasks();

        const nextTask = tasks[currentStep];

        setMessage(
            'SUCCESS: ' + task.name + ' completed. Next: ' + nextTask.name + '.',
            'success'
        );

        showToast('Step complete: +' + task.points + ' points.');
    }

    function endGame(success, title) {
        if (finished) return;

        finished = true;
        clearInterval(timerInterval);

        document.querySelectorAll('.action-btn').forEach(button => {
            button.disabled = true;
        });

        if (success) {
            serverStatus.textContent = 'READY';
            missionStatus.textContent = 'COMPLETE';
            topbarStatus.textContent = 'SERVER READY';

            document.getElementById('led1').classList.add('on');
            document.getElementById('led2').classList.add('on');
            document.getElementById('led3').classList.add('on');

            gameOverIcon.innerHTML = '<i class="fas fa-check-circle"></i>';
            gameOverTitle.textContent = title;
            gameOverMessage.textContent =
                'Excellent work. SERVER01 passed the COC 3 server setup and verification procedure.';
        } else {
            serverStatus.textContent = 'OFFLINE';
            missionStatus.textContent = 'FAILED';
            missionStatus.classList.remove('online');
            topbarStatus.textContent = 'TIME EXPIRED';

            gameOverIcon.innerHTML = '<i class="fas fa-triangle-exclamation"></i>';
            gameOverTitle.textContent = title;
            gameOverMessage.textContent =
                'The simulation ended before the server setup was completed. Restart and try again.';
        }

        finalScore.textContent = score;
        gameOver.classList.add('active');

        saveGameResult();
    }

    function saveGameResult() {
        if (savedGame) return;
        savedGame = true;

        const payload = new URLSearchParams();
        payload.append('action', 'save_game');
        payload.append('coc_number', '3');
        payload.append('score', String(score));
        payload.append('steps_completed', String(currentStep));
        payload.append('success', finished && currentStep === TOTAL_STEPS ? '1' : '0');

        /*
         * Save through the same page so no additional API endpoint is required.
         * The PHP handler is intentionally embedded below.
         */
        fetch(window.location.href, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: payload.toString(),
            credentials: 'same-origin'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                highScoreDisplay.textContent = data.high_score;
                gamesDisplay.textContent = data.games_played;
            }
        })
        .catch(() => {
            /*
             * Do not interrupt the completed game if the save request fails.
             * The score remains visible locally.
             */
        });
    }

    function restartGame() {
        clearInterval(timerInterval);

        started = false;
        finished = false;
        currentStep = 0;
        score = 0;
        secondsLeft = GAME_TIME;
        savedGame = false;

        gameOver.classList.remove('active');

        serverStatus.textContent = 'OFFLINE';
        serverStatus.classList.remove('online');

        missionStatus.textContent = 'READY';
        missionStatus.classList.remove('online');

        topbarStatus.textContent = 'POWER OFF';

        document.getElementById('led1').classList.remove('on');
        document.getElementById('led2').classList.remove('on');
        document.getElementById('led3').classList.remove('on');

        document.querySelectorAll('.action-btn').forEach(button => {
            button.disabled = true;
        });

        serverDisplay.innerHTML = `
            <div class="boot-screen">
                <div class="boot-logo"><i class="fas fa-server"></i></div>
                <h3>SERVER01 • WINDOWS SERVER</h3>
                <p>
                    Server is powered off. Start the simulation to begin
                    the COC 3 server setup procedure.
                </p>
                <button class="boot-btn" id="startBtn2" type="button">
                    <i class="fas fa-power-off"></i> POWER ON SERVER
                </button>
            </div>
        `;

        document.getElementById('startBtn2').addEventListener('click', startGame);

        timer.classList.remove('warning');
        updateTimer();
        updateProgress();
        updateCurrentTask();
        renderTasks();

        setMessage('Select POWER ON SERVER to start a new simulation.', '');
    }

    function escapeHtml(value) {
        return String(value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    /*
     * TOOL BUTTONS
     */
    document.querySelectorAll('.action-btn').forEach(button => {
        button.addEventListener('click', function () {
            performAction(this.dataset.action);
        });
    });

    startBtn.addEventListener('click', startGame);
    restartBtn.addEventListener('click', restartGame);
    playAgainBtn.addEventListener('click', restartGame);

    /*
     * THEME
     */
    const themeToggle = document.getElementById('themeToggle');
    const themeIcon = document.getElementById('themeIcon');
    const themeText = document.getElementById('themeText');

    const savedTheme =
        localStorage.getItem('css_tesda_theme') === 'light'
            ? 'light'
            : 'dark';

    function applyTheme(theme) {
        const light = theme === 'light';

        document.body.classList.toggle('light-mode', light);
        document.body.classList.toggle('dark-mode', !light);

        themeIcon.className = light ? 'fas fa-sun' : 'fas fa-moon';
        themeText.textContent = light ? 'Light Mode' : 'Dark Mode';

        localStorage.setItem('css_tesda_theme', light ? 'light' : 'dark');
    }

    applyTheme(savedTheme);

    themeToggle.addEventListener('click', function () {
        applyTheme(
            document.body.classList.contains('light-mode')
                ? 'dark'
                : 'light'
        );
    });

    /*
     * SHARED BRIGHTNESS
     * Compatible with the project's brightness_widget.php/localStorage.
     */
    function applyBrightness(value) {
        let brightness = parseFloat(value);

        if (!Number.isFinite(brightness)) {
            brightness = 100;
        }

        brightness = Math.max(50, Math.min(150, brightness));

        document.documentElement.style.setProperty(
            '--global-brightness',
            brightness + '%'
        );
    }

    applyBrightness(<?php echo json_encode($global_brightness); ?>);

    const storedBrightness = localStorage.getItem('css_tesda_brightness');

    if (storedBrightness !== null) {
        applyBrightness(storedBrightness);
    }

    window.addEventListener('storage', function (event) {
        if (event.key === 'css_tesda_brightness') {
            applyBrightness(event.newValue);
        }
    });

    window.addEventListener('css_tesda_brightness', function (event) {
        if (event.detail !== undefined) {
            applyBrightness(event.detail);
        }
    });

    updateTimer();
    updateProgress();
    updateCurrentTask();
    renderTasks();
})();
</script>

<?php
/*
|--------------------------------------------------------------------------
| AJAX SCORE SAVE
|--------------------------------------------------------------------------
| This block runs after the page has been rendered. For normal page loads
| nothing is returned. For AJAX save requests, return JSON and stop.
*/
if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['action']) &&
    $_POST['action'] === 'save_game'
) {
    header('Content-Type: application/json; charset=UTF-8');

    $posted_coc = isset($_POST['coc_number']) ? (int)$_POST['coc_number'] : 0;
    $posted_score = isset($_POST['score']) ? max(0, (int)$_POST['score']) : 0;
    $posted_steps = isset($_POST['steps_completed']) ? max(0, min(10, (int)$_POST['steps_completed'])) : 0;

    if ($posted_coc !== 3) {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid COC number.'
        ]);
        exit();
    }

    $new_high_score = $posted_score;
    $new_games_played = 1;

    $check = $conn->prepare(
        "SELECT high_score, games_played
         FROM user_game_scores
         WHERE user_id = ? AND coc_number = ?
         LIMIT 1"
    );

    if (!$check) {
        echo json_encode([
            'success' => false,
            'message' => 'Unable to prepare score lookup.'
        ]);
        exit();
    }

    $check->bind_param("ii", $user_id, $posted_coc);
    $check->execute();
    $existing = $check->get_result();

    if ($existing && $existing->num_rows > 0) {
        $old = $existing->fetch_assoc();

        $old_high = (int)($old['high_score'] ?? 0);
        $old_games = (int)($old['games_played'] ?? 0);

        $new_high_score = max($old_high, $posted_score);
        $new_games_played = $old_games + 1;

        $check->close();

        $update = $conn->prepare(
            "UPDATE user_game_scores
             SET high_score = ?, games_played = ?
             WHERE user_id = ? AND coc_number = ?"
        );

        if ($update) {
            $update->bind_param(
                "iiii",
                $new_high_score,
                $new_games_played,
                $user_id,
                $posted_coc
            );
            $ok = $update->execute();
            $update->close();

            if (!$ok) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Unable to save score.'
                ]);
                exit();
            }
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Unable to prepare score update.'
            ]);
            exit();
        }
    } else {
        $check->close();

        $insert = $conn->prepare(
            "INSERT INTO user_game_scores
             (user_id, coc_number, high_score, games_played)
             VALUES (?, ?, ?, ?)"
        );

        if ($insert) {
            $insert->bind_param(
                "iiii",
                $user_id,
                $posted_coc,
                $new_high_score,
                $new_games_played
            );
            $ok = $insert->execute();
            $insert->close();

            if (!$ok) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Unable to create score record.'
                ]);
                exit();
            }
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Unable to prepare score insert.'
            ]);
            exit();
        }
    }

    echo json_encode([
        'success' => true,
        'high_score' => $new_high_score,
        'games_played' => $new_games_played,
        'steps_completed' => $posted_steps
    ]);
    exit();
}
?>

<?php
if (file_exists(__DIR__ . '/includes/brightness_widget.php')) {
    include __DIR__ . '/includes/brightness_widget.php';
}
?>

</body>
</html>

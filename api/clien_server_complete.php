<?php
// client_server_complete.php - Complete Client-Server Network Simulator with Database Save
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$page_title = "Client-Server Network Simulator";

// Get user's previous scores
$score_query = $conn->prepare("SELECT score, steps_completed FROM client_server_scores WHERE user_id = ? ORDER BY completed_at DESC LIMIT 1");
$score_query->bind_param("i", $user_id);
$score_query->execute();
$previous_score = $score_query->get_result()->fetch_assoc();
$score_query->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <?php if (file_exists(__DIR__ . '/includes/theme_preload.php')) { include __DIR__ . '/includes/theme_preload.php'; } ?>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <title><?php echo $page_title; ?> | CSS TESDA Reviewer</title>
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&family=Rajdhani:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <?php if (file_exists(__DIR__ . '/includes/theme_vars.php')) { include __DIR__ . '/includes/theme_vars.php'; } ?>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            user-select: none;
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
            transition: background 0.3s ease;
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
            color: #94a3b8;
            text-decoration: none;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .nav-links a:hover {
            color: #00ff9d;
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
            padding: 1.5rem;
        }

        .game-header {
            text-align: center;
            margin-bottom: 1.5rem;
        }

        .game-header h1 {
            font-family: 'Orbitron', sans-serif;
            font-size: 1.8rem;
            background: linear-gradient(135deg, #00ff9d, #0066ff);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .game-header p {
            color: #8899cc;
        }

        /* Workspace Layout */
        .workspace {
            display: flex;
            gap: 2rem;
            flex-wrap: wrap;
            justify-content: center;
        }

        .rack-container {
            flex-shrink: 0;
        }

        /* Machine tabs */
        .machine-tabs {
            display: flex;
            gap: 8px;
            margin-bottom: 10px;
            justify-content: center;
        }

        .machine-tab {
            padding: 6px 18px;
            background: #2a2f3e;
            border: 1px solid rgba(0, 255, 157, 0.3);
            border-radius: 8px 8px 0 0;
            color: #8899cc;
            cursor: pointer;
            font-size: 0.85rem;
            font-weight: 600;
            transition: all 0.2s;
        }

        .machine-tab.active {
            background: #00ff9d;
            color: #000;
        }

        .machine-tab.locked {
            opacity: 0.45;
            cursor: not-allowed;
        }

        /* Monitor (shared by server + client) */
        .monitor {
            width: 750px;
            position: relative;
        }

        .monitor-screen {
            width: 100%;
            height: 450px;
            background: #1a1f2e;
            border-radius: 20px 20px 10px 10px;
            overflow: hidden;
            border: 3px solid #00ff9d;
            box-shadow: 0 0 30px rgba(0, 255, 157, 0.3);
        }

        .monitor-screen.client-mode {
            border-color: #0066ff;
            box-shadow: 0 0 30px rgba(0, 102, 255, 0.35);
        }

        .screen-bezel {
            background: #0a0f1e;
            padding: 10px;
            height: 100%;
            display: flex;
            flex-direction: column;
        }

        .power-led {
            width: 8px;
            height: 8px;
            background: #00ff9d;
            border-radius: 50%;
            position: absolute;
            top: 8px;
            left: 50%;
            transform: translateX(-50%);
        }

        .screen-content {
            background: #050812;
            border-radius: 8px;
            flex: 1;
            padding: 20px;
            overflow-y: auto;
            font-family: monospace;
            color: #00ff9d;
        }

        /* Tower / chassis under the screen */
        .chassis {
            width: 800px;
            height: 110px;
            background: linear-gradient(135deg, #2a2f3e, #1a1f2e);
            margin: -10px auto 0;
            border-radius: 0 0 15px 15px;
            padding: 12px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
        }

        .chassis-label {
            font-family: 'Orbitron', sans-serif;
            font-size: 0.8rem;
            color: #8899cc;
            writing-mode: horizontal-tb;
        }

        .port-bank {
            display: flex;
            gap: 14px;
            align-items: center;
        }

        .port-slot {
            text-align: center;
            font-size: 9px;
            color: #8899cc;
        }

        .rj45 {
            width: 34px;
            height: 26px;
            background: #3d3d4d;
            border: 1px solid #8899cc;
            border-radius: 3px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #8899cc;
            margin-bottom: 4px;
        }

        .rj45.active {
            background: #00ff9d;
            color: #000;
            box-shadow: 0 0 10px #00ff9d;
        }

        .link-led {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #5a2a2a;
            margin: 0 auto;
        }

        .link-led.up {
            background: #00ff9d;
            box-shadow: 0 0 6px #00ff9d;
            animation: blink 1.2s infinite;
        }

        @keyframes blink {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.35; }
        }

        /* Switch drop target */
        .switch-box {
            position: absolute;
            right: 20px;
            bottom: 128px;
            width: 120px;
            padding: 8px;
            background: rgba(0, 0, 0, 0.6);
            border: 2px dashed #00ff9d;
            border-radius: 8px;
            text-align: center;
            font-size: 9px;
            color: #00ff9d;
        }

        .switch-box.active {
            background: rgba(0, 255, 157, 0.35);
            animation: pulse 0.5s infinite;
        }

        .switch-box.connected {
            border-style: solid;
            background: rgba(0, 255, 157, 0.15);
        }

        @keyframes pulse {
            0%, 100% { opacity: 0.6; }
            50% { opacity: 1; }
        }

        .switch-ports {
            display: flex;
            gap: 4px;
            justify-content: center;
            margin-top: 5px;
        }

        .switch-ports span {
            width: 12px;
            height: 10px;
            background: #3d3d4d;
            border-radius: 2px;
        }

        .switch-ports span.lit {
            background: #00ff9d;
        }

        /* Right Panel */
        .right-panel {
            width: 260px;
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        .cable-section {
            background: var(--bg-panel);
            border-radius: 15px;
            padding: 20px;
            text-align: center;
            border: 1px solid var(--border-color);
        }

        .cable-section h4 {
            color: #00ff9d;
            margin-bottom: 15px;
            font-size: 0.9rem;
        }

        .lan-cable {
            width: 150px;
            height: 45px;
            background: linear-gradient(135deg, #2d2d3d, #1a1f2e);
            border: 2px solid #00ff9d;
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            cursor: grab;
            margin: 0 auto;
            position: relative;
            font-size: 0.8rem;
        }

        .lan-cable:active {
            cursor: grabbing;
        }

        .lan-cable.dragging {
            opacity: 0.6;
        }

        .lan-cable.connected {
            background: linear-gradient(135deg, #00ff9d, #0066ff);
            border-color: #fff;
            color: #000;
            cursor: default;
        }

        .lan-cable.connected i {
            color: #000;
        }

        .cable-label {
            font-size: 11px;
            color: #8899cc;
            margin-top: 8px;
        }

        .cmd-section {
            background: var(--bg-panel);
            border-radius: 15px;
            padding: 15px;
            border: 1px solid var(--border-color);
        }

        .cmd-section h4 {
            color: #00ff9d;
            margin-bottom: 12px;
            font-size: 0.85rem;
            text-align: center;
        }

        .cmd-buttons {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 8px;
        }

        .cmd-btn {
            padding: 0 10px;
            height: 34px;
            background: #2a2f3e;
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 11px;
            font-weight: bold;
            color: #00ff9d;
            cursor: pointer;
            border: 1px solid rgba(0, 255, 157, 0.3);
            transition: all 0.2s;
        }

        .cmd-btn:hover {
            background: #00ff9d;
            color: #000;
            transform: scale(1.02);
        }

        .cmd-note {
            font-size: 9px;
            color: #8899cc;
            text-align: center;
            margin-top: 10px;
        }

        .score-display {
            background: var(--bg-panel);
            border-radius: 15px;
            padding: 12px;
            text-align: center;
            border: 1px solid #ffaa00;
        }

        .score-display .score-value {
            font-size: 1.5rem;
            font-weight: bold;
            color: #ffaa00;
        }

        .steps-section {
            background: var(--bg-panel);
            border-radius: 20px;
            padding: 10px;
            border: 1px solid rgba(100, 255, 157, 1);
            margin-top: 0;
        }

        .steps-section h4 {
            color: #00ff9d;
            margin-bottom: 12px;
            font-size: 0.85rem;
            text-align: center;
        }

        .steps-list {
            display: flex;
            flex-direction: column;
            gap: 5px;
            max-height: 400px;
            overflow-y: auto;
        }

        .step-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 6px 8px;
            background: rgba(0, 0, 0, 0.3);
            border-radius: 8px;
            font-size: 0.75rem;
        }

        .step-num {
            width: 24px;
            height: 24px;
            background: rgba(0, 255, 157, 0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 0.7rem;
        }

        .step-name {
            flex: 1;
            color: #cbd5e1;
        }

        .step-status-icon {
            width: 20px;
            text-align: center;
        }

        .step-status-icon.completed {
            color: #00ff9d;
        }

        .step-status-icon.pending {
            color: #ffaa00;
        }

        /* Wizard styles */
        .setup-wizard {
            width: 100%;
            height: 100%;
        }

        .wizard-header {
            background: #00ff9d;
            color: #000;
            padding: 8px;
            font-weight: bold;
        }

        .wizard-header.client {
            background: #0066ff;
            color: #fff;
        }

        .wizard-body {
            padding: 15px;
        }

        .wizard-option {
            padding: 8px;
            margin: 5px 0;
            background: #3d3d3d;
            cursor: pointer;
            border-radius: 5px;
        }

        .wizard-option:hover {
            background: #00ff9d;
            color: #000;
        }

        .option-btn {
            padding: 8px 16px;
            background: #3d3d3d;
            border: none;
            border-radius: 5px;
            color: #fff;
            cursor: pointer;
        }

        .option-btn:hover {
            background: #00ff9d;
            color: #000;
        }

        .progress-bar {
            width: 100%;
            height: 20px;
            background: #3d3d3d;
            border-radius: 10px;
            overflow: hidden;
        }

        .progress-fill {
            width: 0%;
            height: 100%;
            background: linear-gradient(90deg, #00ff9d, #0066ff);
            transition: width 0.3s;
        }

        .console-out {
            background: #000;
            border: 1px solid #00ff9d;
            border-radius: 5px;
            padding: 10px;
            font-size: 11px;
            line-height: 1.5;
            margin-top: 10px;
            white-space: pre-wrap;
        }

        .instructions {
            background: var(--bg-panel);
            border-radius: 12px;
            padding: 12px;
            margin-top: 20px;
            text-align: center;
        }

        .instructions p {
            margin: 4px 0;
            font-size: 0.85rem;
        }

        .drag-hint {
            display: inline-block;
            padding: 3px 12px;
            background: rgba(0, 255, 157, 0.15);
            border-radius: 20px;
            margin: 3px;
            font-size: 0.8rem;
        }

        .step-status {
            display: flex;
            justify-content: center;
            gap: 8px;
            margin-top: 15px;
            flex-wrap: wrap;
        }

        .step-badge {
            padding: 4px 10px;
            background: rgba(0, 0, 0, 0.5);
            border-radius: 20px;
            font-size: 0.7rem;
        }

        .step-badge.completed {
            background: #00ff9d;
            color: #000;
        }

        .control-buttons {
            display: flex;
            gap: 1rem;
            justify-content: center;
            margin: 20px 0;
        }

        .btn {
            padding: 8px 20px;
            border-radius: 50px;
            font-family: 'Orbitron', sans-serif;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 0.85rem;
        }

        .btn-primary {
            background: linear-gradient(45deg, #00ff9d, #0066ff);
            color: #050812;
        }

        .btn-secondary {
            background: transparent;
            border: 2px solid #00ff9d;
            color: #00ff9d;
        }

        .btn-primary:hover, .btn-secondary:hover {
            transform: translateY(-2px);
        }

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
            background: rgba(10, 20, 40, 0.95);
            border: 2px solid #00ff9d;
            border-radius: 20px;
            padding: 1.5rem;
            text-align: center;
            max-width: 450px;
        }

        @keyframes fall {
            to {
                transform: translateY(100vh) rotate(360deg);
                opacity: 0;
            }
        }

        @media (max-width: 1100px) {
            .monitor { transform: scale(0.9); }
            .rack-container { transform-origin: top left; }
        }

        @media (max-width: 900px) {
            .workspace { flex-direction: column; align-items: center; }
            .right-panel { width: 100%; flex-direction: row; flex-wrap: wrap; justify-content: center; }
            .cable-section, .cmd-section, .steps-section { flex: 1; min-width: 200px; }
        }

        input, select {
            background: #3d3d3d;
            border: 1px solid #00ff9d;
            color: #fff;
            padding: 8px;
            border-radius: 5px;
            width: 100%;
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
                <div class="user-info">
                    <i class="fas fa-user"></i>
                    <span><?php echo htmlspecialchars($_SESSION['full_name'] ?? $_SESSION['username']); ?></span>
                    <a href="logout.php" style="color: #ef4444;"><i class="fas fa-sign-out-alt"></i></a>
                </div>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="game-header">
            <h1><i class="fas fa-network-wired"></i> Client-Server Network Simulator</h1>
            <p>Set up a working client-server network - from patch cable to a mapped network drive!</p>
        </div>

        <div class="workspace">
            <!-- Machine -->
            <div class="rack-container">
                <div class="machine-tabs">
                    <div class="machine-tab active" id="tabServer" onclick="switchMachine('server')"><i class="fas fa-server"></i> SERVER01</div>
                    <div class="machine-tab locked" id="tabClient" onclick="switchMachine('client')"><i class="fas fa-desktop"></i> CLIENT-PC</div>
                </div>

                <div class="monitor" style="position: relative;">
                    <div class="monitor-screen" id="monitorScreen">
                        <div class="screen-bezel">
                            <div class="power-led"></div>
                            <div class="screen-content" id="screenContent">
                                <div style="padding: 15px;">
                                    <h3>SERVER01 - Hardware Status</h3>
                                    <hr>
                                    <p>Role: Windows Server (Domain Controller candidate)</p>
                                    <p>NIC: Intel I219-LM Gigabit Ethernet</p>
                                    <p>Link: <span id="linkStatus" style="color:#ffaa00;">&#9888; Media disconnected</span></p>
                                    <p>IPv4: <span id="ipStatus" style="color:#ffaa00;">Not configured</span></p>
                                    <hr>
                                    <div>
                                        <p><strong>Network checklist:</strong></p>
                                        <div class="wizard-option">1. Patch cable to switch <span id="cableStatus">(Unplugged)</span></div>
                                        <div class="wizard-option">2. Static IP on server</div>
                                        <div class="wizard-option">3. Roles: AD DS, DHCP, DNS</div>
                                    </div>
                                    <button id="configBtn" class="option-btn" style="margin-top:15px; display:none; background:#00ff9d; color:#000;" onclick="openIPConfig()">&#128225; Open network settings</button>
                                    <p style="margin-top:15px; font-size:11px; color:#ffaa00;">&#128073; Drag the patch cable from the right panel into the switch (right edge)</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="chassis">
                        <div class="chassis-label" id="chassisLabel">SERVER01 &bull; Tower chassis</div>
                        <div class="port-bank">
                            <div class="port-slot">
                                <div class="rj45" id="rj45Port"><i class="fas fa-ethernet"></i></div>
                                LAN1
                                <div class="link-led" id="linkLed"></div>
                            </div>
                            <div class="port-slot"><div class="rj45"><i class="fas fa-ethernet"></i></div>LAN2<div class="link-led"></div></div>
                            <div class="port-slot"><div class="rj45"><i class="fas fa-usb"></i></div>USB<div class="link-led"></div></div>
                            <div class="port-slot"><div class="rj45"><i class="fas fa-plug"></i></div>PWR<div class="link-led up"></div></div>
                        </div>
                    </div>
                    <div class="switch-box" id="switchBox">
                        <i class="fas fa-sitemap"></i> 8-PORT SWITCH
                        <div class="switch-ports">
                            <span id="swp1"></span><span id="swp2"></span><span></span><span></span>
                        </div>
                        <div style="margin-top:4px;">Drop cable here</div>
                    </div>
                </div>
            </div>

            <!-- Right Panel -->
            <div class="right-panel">
                <div class="cable-section">
                    <h4><i class="fas fa-ethernet"></i> Cabling</h4>
                    <div class="lan-cable" id="lanCable" draggable="true">
                        <i class="fas fa-ethernet"></i> <span>UTP Cat6</span> <i class="fas fa-arrows-alt"></i>
                    </div>
                    <div class="cable-label">&#11013;&#65039; Drag to the switch</div>
                </div>

                <div class="cmd-section">
                    <h4><i class="fas fa-terminal"></i> Command Prompt</h4>
                    <div class="cmd-buttons">
                        <div class="cmd-btn" onclick="runCommand('ipconfig')">ipconfig</div>
                        <div class="cmd-btn" onclick="runCommand('ping')">ping</div>
                        <div class="cmd-btn" onclick="runCommand('nslookup')">nslookup</div>
                        <div class="cmd-btn" onclick="runCommand('gpupdate')">gpupdate</div>
                    </div>
                    <div class="cmd-note">ipconfig: check IP | ping: test link | nslookup: test DNS</div>
                </div>

                <div class="score-display">
                    <i class="fas fa-star"></i> Your Score
                    <div class="score-value" id="currentScore">0</div>
                    <small>Best: <span id="bestScore"><?php echo $previous_score['score'] ?? 0; ?></span></small>
                </div>

                <div class="steps-section">
                    <h4><i class="fas fa-list-ol"></i> Setup Steps (1-15)</h4>
                    <div class="steps-list" id="stepsList"></div>
                </div>
            </div>
        </div>

        <div class="instructions">
            <p><strong>&#128203; Client-Server Setup Guide (15 Steps)</strong></p>
            <p><span class="drag-hint">1&#65039;&#8419; Plug patch cable</span> <span class="drag-hint">2&#65039;&#8419; Confirm link light</span> <span class="drag-hint">3&#65039;&#8419; Set server static IP</span></p>
            <p><span class="drag-hint">4&#65039;&#8419; Subnet, gateway, DNS</span> <span class="drag-hint">5&#65039;&#8419; Install AD DS role</span> <span class="drag-hint">6&#65039;&#8419; Promote to domain controller</span></p>
            <p><span class="drag-hint">7&#65039;&#8419; Create DHCP scope</span> <span class="drag-hint">8&#65039;&#8419; Add DNS zone</span> <span class="drag-hint">9&#65039;&#8419; Create domain users</span></p>
            <p><span class="drag-hint">&#128287; Create shared folder</span> <span class="drag-hint">1&#65039;&#8419;1&#65039;&#8419; Set share permissions</span> <span class="drag-hint">1&#65039;&#8419;2&#65039;&#8419; Client gets IP by DHCP</span></p>
            <p><span class="drag-hint">1&#65039;&#8419;3&#65039;&#8419; Join client to domain</span> <span class="drag-hint">1&#65039;&#8419;4&#65039;&#8419; Map network drive</span> <span class="drag-hint">1&#65039;&#8419;5&#65039;&#8419; Ping test passed!</span></p>
        </div>

        <div class="step-status" id="stepStatus"></div>

        <div class="control-buttons">
            <button class="btn btn-secondary" onclick="resetNetwork()"><i class="fas fa-redo"></i> Reset</button>
        </div>
    </div>

    <div id="completionModal" class="completion-modal">
        <div class="completion-content">
            <i class="fas fa-trophy" style="font-size: 3rem; color: #ffaa00;"></i>
            <h2>Network is live!</h2>
            <p>The client is joined to the domain and the shared drive is reachable.</p>
            <div id="completionMessage"></div>
            <div style="margin-top: 1rem;">
                <button class="btn btn-primary" onclick="saveAndClose()"><i class="fas fa-save"></i> Save Score</button>
                <button class="btn btn-secondary" onclick="resetNetwork(); closeCompletionModal();"><i class="fas fa-redo"></i> Practice Again</button>
            </div>
        </div>
    </div>

    <script>
        // ============================================
        // CLIENT-SERVER NETWORK SIMULATOR
        // 15 Comprehensive Steps with Database Save
        // ============================================

        let cablePlugged = false;
        let roleInterval = null;
        let totalScore = 0;
        let gameSaved = false;
        let currentMachine = 'server';

        const steps = [
            { id: 1,  name: "Connect Patch Cable", desc: "UTP Cat6 from server NIC to switch", completed: false, points: 10 },
            { id: 2,  name: "Verify Link Status", desc: "Link LED up, media connected", completed: false, points: 10 },
            { id: 3,  name: "Server Static IP", desc: "Assign fixed IPv4 address", completed: false, points: 10 },
            { id: 4,  name: "Subnet / Gateway / DNS", desc: "Complete the IPv4 settings", completed: false, points: 10 },
            { id: 5,  name: "Install AD DS Role", desc: "Add Active Directory Domain Services", completed: false, points: 10 },
            { id: 6,  name: "Promote to DC", desc: "Create the domain forest", completed: false, points: 10 },
            { id: 7,  name: "Configure DHCP Scope", desc: "Address pool for clients", completed: false, points: 10 },
            { id: 8,  name: "Configure DNS Zone", desc: "Forward lookup zone", completed: false, points: 10 },
            { id: 9,  name: "Create Domain Users", desc: "Add an account in AD", completed: false, points: 10 },
            { id: 10, name: "Create Shared Folder", desc: "Share a folder on the server", completed: false, points: 10 },
            { id: 11, name: "Set Share Permissions", desc: "Grant access to the group", completed: false, points: 10 },
            { id: 12, name: "Client IP via DHCP", desc: "Obtain address automatically", completed: false, points: 10 },
            { id: 13, name: "Join Client to Domain", desc: "Authenticate with domain admin", completed: false, points: 10 },
            { id: 14, name: "Map Network Drive", desc: "Attach the shared folder", completed: false, points: 10 },
            { id: 15, name: "Test Connectivity", desc: "Ping the server, network ready", completed: false, points: 10 }
        ];

        let networkData = {
            serverIP: null, subnet: null, gateway: null, dns: null,
            domain: null, dhcpScope: null, dnsZone: null,
            domainUser: null, shareName: null, permission: null,
            clientIP: null, driveLetter: null
        };

        let subnetSet = false, gatewaySet = false, dnsSet = false;

        function renderStepsList() {
            const container = document.getElementById('stepsList');
            if (!container) return;
            container.innerHTML = '';
            steps.forEach(step => {
                const stepDiv = document.createElement('div');
                stepDiv.className = 'step-item';
                stepDiv.innerHTML = `
                    <div class="step-num">${step.id}</div>
                    <div class="step-name">${step.name}</div>
                    <div class="step-status-icon ${step.completed ? 'completed' : 'pending'}">
                        ${step.completed ? '<i class="fas fa-check-circle"></i>' : '<i class="fas fa-circle"></i>'}
                    </div>
                `;
                container.appendChild(stepDiv);
            });
        }

        function updateStepStatus() {
            renderStepsList();
            const container = document.getElementById('stepStatus');
            if (container) {
                container.innerHTML = '';
                steps.forEach(step => {
                    const badge = document.createElement('div');
                    badge.className = `step-badge ${step.completed ? 'completed' : ''}`;
                    badge.innerHTML = `${step.id}. ${step.name.substring(0, 12)}`;
                    container.appendChild(badge);
                });
            }

            const completedSteps = steps.filter(s => s.completed).length;
            totalScore = completedSteps * 10;
            document.getElementById('currentScore').textContent = totalScore;
        }

        function showMessage(msg, type) {
            const toast = document.createElement('div');
            toast.style.cssText = `
                position: fixed; bottom: 20px; left: 50%; transform: translateX(-50%);
                background: ${type === 'error' ? '#ef4444' : (type === 'success' ? '#00ff9d' : '#3b82f6')};
                color: ${type === 'error' ? '#fff' : '#000'}; padding: 8px 16px; border-radius: 8px;
                z-index: 1000; font-weight: bold; font-size: 0.8rem;
            `;
            toast.innerHTML = msg;
            document.body.appendChild(toast);
            setTimeout(() => toast.remove(), 2500);
        }

        function initDragDrop() {
            const cable = document.getElementById('lanCable');
            const switchBox = document.getElementById('switchBox');
            if (!cable || !switchBox) return;
            cable.addEventListener('dragstart', e => {
                if (!cablePlugged) e.dataTransfer.setData('text/plain', 'cable');
                else e.preventDefault();
                cable.classList.add('dragging');
            });
            cable.addEventListener('dragend', () => cable.classList.remove('dragging'));
            switchBox.addEventListener('dragover', e => { e.preventDefault(); switchBox.classList.add('active'); });
            switchBox.addEventListener('dragleave', () => switchBox.classList.remove('active'));
            switchBox.addEventListener('drop', e => {
                e.preventDefault();
                switchBox.classList.remove('active');
                if (!cablePlugged) plugCable();
            });
        }

        function plugCable() {
            cablePlugged = true;
            document.getElementById('lanCable').classList.add('connected');
            document.getElementById('rj45Port').classList.add('active');
            document.getElementById('linkLed').classList.add('up');
            document.getElementById('switchBox').classList.add('connected');
            document.getElementById('swp1').classList.add('lit');

            const linkStatus = document.getElementById('linkStatus');
            const cableStatus = document.getElementById('cableStatus');
            const configBtn = document.getElementById('configBtn');
            if (linkStatus) { linkStatus.innerHTML = '&#10003; Connected at 1.0 Gbps'; linkStatus.style.color = '#00ff9d'; }
            if (cableStatus) cableStatus.innerHTML = '(Plugged into port 1)';
            if (configBtn) configBtn.style.display = 'inline-block';

            steps[0].completed = true; steps[1].completed = true;
            updateStepStatus();
            showMessage('Cable plugged in. Open network settings next.', 'success');
        }

        function runCommand(cmd) {
            if (!cablePlugged) { showMessage('No link. Plug the patch cable first.', 'error'); return; }
            if (cmd === 'ipconfig') {
                showMessage(networkData.serverIP ? `IPv4: ${networkData.serverIP}` : 'IPv4: not configured yet', 'info');
            } else if (cmd === 'ping') {
                if (steps[13].completed && !steps[14].completed) { finishPingTest(); }
                else showMessage(networkData.serverIP ? `Reply from ${networkData.serverIP}: time<1ms` : 'Destination host unreachable.', networkData.serverIP ? 'info' : 'error');
            } else if (cmd === 'nslookup') {
                showMessage(networkData.domain ? `Server: ${networkData.domain}` : 'DNS request timed out.', networkData.domain ? 'info' : 'error');
            } else {
                showMessage('Group policy update completed.', 'info');
            }
        }

        function switchMachine(machine) {
            if (machine === 'client' && !steps[11].completed && !clientUnlocked) {
                showMessage('Finish the server setup first.', 'error');
                return;
            }
            currentMachine = machine;
            document.getElementById('tabServer').classList.toggle('active', machine === 'server');
            document.getElementById('tabClient').classList.toggle('active', machine === 'client');
            document.getElementById('monitorScreen').classList.toggle('client-mode', machine === 'client');
            document.getElementById('chassisLabel').textContent = machine === 'client' ? 'CLIENT-PC \u2022 Desktop' : 'SERVER01 \u2022 Tower chassis';
        }

        let clientUnlocked = false;

        function unlockClient() {
            clientUnlocked = true;
            document.getElementById('tabClient').classList.remove('locked');
            document.getElementById('swp2').classList.add('lit');
            switchMachine('client');
        }

        /* ---------- Step 3 & 4: IP configuration ---------- */
        function openIPConfig() {
            const screen = document.getElementById('screenContent');
            if (!screen) return;
            screen.innerHTML = `
                <div class="setup-wizard">
                    <div class="wizard-header"><i class="fas fa-ethernet"></i> IPv4 Properties - SERVER01</div>
                    <div class="wizard-body">
                        <p style="font-size:11px; margin-bottom:8px;">A server must use a static address. Use the 192.168.1.x range.</p>
                        <input type="text" id="ipInput" placeholder="192.168.1.10">
                        <button class="option-btn" onclick="setServerIP()" style="margin-top:10px; background:#00ff9d; color:#000;">Apply address</button>
                        <div id="ipMsg" style="margin-top:10px; color:#ffaa00; font-size:11px;"></div>
                    </div>
                </div>
            `;
        }

        function setServerIP() {
            const val = (document.getElementById('ipInput')?.value || '').trim();
            const valid = /^192\.168\.1\.([2-9]|[1-9]\d|1\d\d|2[0-4]\d|25[0-4])$/.test(val);
            if (!valid) {
                const msg = document.getElementById('ipMsg');
                if (msg) msg.innerHTML = 'Address rejected. Enter an address in 192.168.1.2 - 192.168.1.254.';
                return;
            }
            networkData.serverIP = val;
            steps[2].completed = true;
            updateStepStatus();
            showMessage(`Server address set to ${val}`, 'success');
            showIPDetailsScreen();
        }

        function showIPDetailsScreen() {
            const screen = document.getElementById('screenContent');
            if (!screen) return;
            screen.innerHTML = `
                <div class="setup-wizard">
                    <div class="wizard-header"><i class="fas fa-sliders-h"></i> IPv4 Properties - Details</div>
                    <div class="wizard-body">
                        <div><strong>Subnet mask:</strong>
                            <div class="wizard-option" onclick="setSubnet('255.255.255.0')">255.255.255.0 (/24)</div>
                            <div class="wizard-option" onclick="setSubnet('255.255.0.0')">255.255.0.0 (/16)</div>
                        </div>
                        <div><strong>Default gateway:</strong>
                            <div class="wizard-option" onclick="setGateway('192.168.1.1')">192.168.1.1 (router)</div>
                            <div class="wizard-option" onclick="setGateway('192.168.1.254')">192.168.1.254</div>
                        </div>
                        <div><strong>Preferred DNS:</strong>
                            <div class="wizard-option" onclick="setDNS('127.0.0.1')">127.0.0.1 (this server)</div>
                            <div class="wizard-option" onclick="setDNS('8.8.8.8')">8.8.8.8 (public)</div>
                        </div>
                        <div id="ipDetailStatus" style="margin-top:10px; color:#ffaa00;"></div>
                    </div>
                </div>
            `;
        }

        function setSubnet(v) { networkData.subnet = v; subnetSet = true; updateIPDetailStatus(); }
        function setGateway(v) { networkData.gateway = v; gatewaySet = true; updateIPDetailStatus(); }
        function setDNS(v) { networkData.dns = v; dnsSet = true; updateIPDetailStatus(); }

        function updateIPDetailStatus() {
            const el = document.getElementById('ipDetailStatus');
            if (el) el.innerHTML = `${subnetSet?'\u2713':''} Subnet ${gatewaySet?'\u2713':''} Gateway ${dnsSet?'\u2713':''} DNS`;
            if (subnetSet && gatewaySet && dnsSet) {
                setTimeout(() => {
                    steps[3].completed = true;
                    updateStepStatus();
                    showRoleScreen();
                }, 500);
            }
        }

        /* ---------- Step 5: Server roles ---------- */
        function showRoleScreen() {
            const screen = document.getElementById('screenContent');
            if (!screen) return;
            screen.innerHTML = `
                <div class="setup-wizard">
                    <div class="wizard-header"><i class="fas fa-layer-group"></i> Add Roles and Features</div>
                    <div class="wizard-body">
                        <p style="font-size:11px; margin-bottom:8px;">Pick the role that turns this machine into a domain controller.</p>
                        <div class="wizard-option" onclick="installRole('adds')">&#128737; Active Directory Domain Services</div>
                        <div class="wizard-option" onclick="installRole('print')">&#128438; Print and Document Services</div>
                        <div class="wizard-option" onclick="installRole('web')">&#127760; Web Server (IIS)</div>
                        <div id="roleMsg" style="margin-top:10px; color:#ffaa00; font-size:11px;"></div>
                    </div>
                </div>
            `;
        }

        function installRole(role) {
            if (role !== 'adds') {
                const msg = document.getElementById('roleMsg');
                if (msg) msg.innerHTML = 'That role will not create a domain. Choose Active Directory Domain Services.';
                return;
            }
            const screen = document.getElementById('screenContent');
            screen.innerHTML = `
                <div style="padding:20px; text-align:center;">
                    <h3><i class="fas fa-cogs"></i> Installing AD DS...</h3>
                    <div class="progress-bar"><div class="progress-fill" id="roleFill" style="width:0%"></div></div>
                    <p id="roleText" style="margin-top:10px;">Preparing installation: 0%</p>
                    <p style="margin-top:15px; font-size:11px;">&#9888; Do not restart the server</p>
                </div>
            `;
            let progress = 0;
            if (roleInterval) clearInterval(roleInterval);
            roleInterval = setInterval(() => {
                progress += 4;
                const fill = document.getElementById('roleFill');
                const text = document.getElementById('roleText');
                if (fill) fill.style.width = progress + '%';
                if (text) {
                    if (progress < 35) text.innerHTML = `Copying binaries: ${progress}%`;
                    else if (progress < 70) text.innerHTML = `Configuring components: ${progress}%`;
                    else text.innerHTML = `Finishing installation: ${progress}%`;
                }
                if (progress >= 100) {
                    clearInterval(roleInterval);
                    steps[4].completed = true;
                    updateStepStatus();
                    showPromoteScreen();
                }
            }, 70);
        }

        /* ---------- Step 6: Promote to DC ---------- */
        function showPromoteScreen() {
            const screen = document.getElementById('screenContent');
            if (!screen) return;
            screen.innerHTML = `
                <div class="setup-wizard">
                    <div class="wizard-header"><i class="fas fa-sitemap"></i> Promote to Domain Controller</div>
                    <div class="wizard-body">
                        <p style="font-size:11px; margin-bottom:8px;">Add a new forest and name the root domain.</p>
                        <input type="text" id="domainInput" placeholder="tesda.local">
                        <button class="option-btn" onclick="promoteServer()" style="margin-top:10px; background:#00ff9d; color:#000;">Create forest</button>
                        <div id="domainMsg" style="margin-top:10px; color:#ffaa00; font-size:11px;"></div>
                    </div>
                </div>
            `;
        }

        function promoteServer() {
            const val = (document.getElementById('domainInput')?.value || '').trim().toLowerCase();
            if (!/^[a-z0-9-]+\.[a-z]{2,}$/.test(val)) {
                const msg = document.getElementById('domainMsg');
                if (msg) msg.innerHTML = 'Use a two-part name such as tesda.local.';
                return;
            }
            networkData.domain = val;
            steps[5].completed = true;
            updateStepStatus();
            showMessage(`Forest ${val} created`, 'success');
            showDHCPScreen();
        }

        /* ---------- Step 7: DHCP scope ---------- */
        function showDHCPScreen() {
            const screen = document.getElementById('screenContent');
            if (!screen) return;
            screen.innerHTML = `
                <div class="setup-wizard">
                    <div class="wizard-header"><i class="fas fa-random"></i> New DHCP Scope</div>
                    <div class="wizard-body">
                        <p style="font-size:11px; margin-bottom:8px;">Choose the pool clients will lease addresses from.</p>
                        <div class="wizard-option" onclick="setScope('192.168.1.100 - 192.168.1.200')">192.168.1.100 - 192.168.1.200 (same subnet)</div>
                        <div class="wizard-option" onclick="setScope('10.0.0.50 - 10.0.0.90')">10.0.0.50 - 10.0.0.90 (different subnet)</div>
                        <div id="scopeMsg" style="margin-top:10px; color:#ffaa00; font-size:11px;"></div>
                    </div>
                </div>
            `;
        }

        function setScope(scope) {
            if (scope.indexOf('192.168.1.') !== 0) {
                const msg = document.getElementById('scopeMsg');
                if (msg) msg.innerHTML = 'That pool is outside the server subnet. Clients would not reach the server.';
                return;
            }
            networkData.dhcpScope = scope;
            steps[6].completed = true;
            updateStepStatus();
            showDNSScreen();
        }

        /* ---------- Step 8: DNS zone ---------- */
        function showDNSScreen() {
            const screen = document.getElementById('screenContent');
            if (!screen) return;
            screen.innerHTML = `
                <div class="setup-wizard">
                    <div class="wizard-header"><i class="fas fa-address-book"></i> DNS Manager</div>
                    <div class="wizard-body">
                        <p style="font-size:11px; margin-bottom:8px;">Names resolve to addresses through a forward lookup zone.</p>
                        <div class="wizard-option" onclick="setZone('forward')">&#10145; Forward lookup zone for ${networkData.domain || 'the domain'}</div>
                        <div class="wizard-option" onclick="setZone('reverse')">&#11013; Reverse lookup zone (addresses to names)</div>
                    </div>
                </div>
            `;
        }

        function setZone(type) {
            networkData.dnsZone = type;
            steps[7].completed = true;
            updateStepStatus();
            showMessage(type === 'forward' ? 'Forward lookup zone created' : 'Reverse zone created', 'success');
            showUserScreen();
        }

        /* ---------- Step 9: Domain users ---------- */
        function showUserScreen() {
            const screen = document.getElementById('screenContent');
            if (!screen) return;
            screen.innerHTML = `
                <div class="setup-wizard">
                    <div class="wizard-header"><i class="fas fa-users"></i> Active Directory Users and Computers</div>
                    <div class="wizard-body">
                        <p style="font-size:11px; margin-bottom:8px;">Create the account the client will log in with.</p>
                        <input type="text" id="adUser" placeholder="Username (e.g. jdelacruz)" style="margin-bottom:10px;">
                        <input type="password" id="adPass" placeholder="Password">
                        <button class="option-btn" onclick="createDomainUser()" style="margin-top:12px; background:#00ff9d; color:#000;">Create user</button>
                        <div id="userMsg" style="margin-top:10px; color:#ffaa00; font-size:11px;"></div>
                    </div>
                </div>
            `;
        }

        function createDomainUser() {
            const name = (document.getElementById('adUser')?.value || '').trim();
            const pass = document.getElementById('adPass')?.value || '';
            const msg = document.getElementById('userMsg');
            if (!name) { if (msg) msg.innerHTML = 'Enter a username.'; return; }
            if (pass.length < 6) { if (msg) msg.innerHTML = 'Password must be at least 6 characters.'; return; }
            networkData.domainUser = name;
            steps[8].completed = true;
            updateStepStatus();
            showShareScreen();
        }

        /* ---------- Step 10 & 11: Shared folder ---------- */
        function showShareScreen() {
            const screen = document.getElementById('screenContent');
            if (!screen) return;
            screen.innerHTML = `
                <div class="setup-wizard">
                    <div class="wizard-header"><i class="fas fa-folder-open"></i> Create Shared Folder</div>
                    <div class="wizard-body">
                        <p style="font-size:11px; margin-bottom:8px;">Name the folder clients will see on the network.</p>
                        <input type="text" id="shareInput" placeholder="Share name (e.g. Records)">
                        <button class="option-btn" onclick="createShare()" style="margin-top:10px; background:#00ff9d; color:#000;">Share folder</button>
                        <div id="shareMsg" style="margin-top:10px; color:#ffaa00; font-size:11px;"></div>
                    </div>
                </div>
            `;
        }

        function createShare() {
            const name = (document.getElementById('shareInput')?.value || '').trim();
            if (!name) {
                const msg = document.getElementById('shareMsg');
                if (msg) msg.innerHTML = 'Enter a share name.';
                return;
            }
            networkData.shareName = name;
            steps[9].completed = true;
            updateStepStatus();
            showPermissionScreen();
        }

        function showPermissionScreen() {
            const screen = document.getElementById('screenContent');
            if (!screen) return;
            screen.innerHTML = `
                <div class="setup-wizard">
                    <div class="wizard-header"><i class="fas fa-user-lock"></i> Share Permissions</div>
                    <div class="wizard-body">
                        <p style="font-size:11px; margin-bottom:8px;">Access level for Domain Users on \\\\SERVER01\\${networkData.shareName}</p>
                        <div class="wizard-option" onclick="setPermission('Read')">&#128065; Read - view files only</div>
                        <div class="wizard-option" onclick="setPermission('Change')">&#9999; Change - read, write, delete</div>
                        <div class="wizard-option" onclick="setPermission('Full Control')">&#128273; Full Control - includes permissions</div>
                    </div>
                </div>
            `;
        }

        function setPermission(level) {
            networkData.permission = level;
            steps[10].completed = true;
            updateStepStatus();
            showMessage(`Domain Users granted ${level}. Now switch to CLIENT-PC.`, 'success');
            setTimeout(() => {
                unlockClient();
                showClientNetworkScreen();
            }, 900);
        }

        /* ---------- Step 12: Client IP ---------- */
        function showClientNetworkScreen() {
            const screen = document.getElementById('screenContent');
            if (!screen) return;
            screen.innerHTML = `
                <div class="setup-wizard">
                    <div class="wizard-header client"><i class="fas fa-desktop"></i> CLIENT-PC - IPv4 Properties</div>
                    <div class="wizard-body">
                        <p style="font-size:11px; margin-bottom:8px;">The server hands out addresses, so the client should ask for one.</p>
                        <div class="wizard-option" onclick="setClientIP('dhcp')">&#128260; Obtain an IP address automatically</div>
                        <div class="wizard-option" onclick="setClientIP('static')">&#9999; Use the following IP address</div>
                        <div id="clientIPMsg" style="margin-top:10px; color:#ffaa00; font-size:11px;"></div>
                    </div>
                </div>
            `;
        }

        function setClientIP(mode) {
            if (mode !== 'dhcp') {
                const msg = document.getElementById('clientIPMsg');
                if (msg) msg.innerHTML = 'A manual address risks a conflict with the DHCP pool. Use automatic.';
                return;
            }
            networkData.clientIP = '192.168.1.101';
            steps[11].completed = true;
            updateStepStatus();
            showMessage('Lease received: 192.168.1.101', 'success');
            showJoinDomainScreen();
        }

        /* ---------- Step 13: Join domain ---------- */
        function showJoinDomainScreen() {
            const screen = document.getElementById('screenContent');
            if (!screen) return;
            screen.innerHTML = `
                <div class="setup-wizard">
                    <div class="wizard-header client"><i class="fas fa-link"></i> CLIENT-PC - Join a Domain</div>
                    <div class="wizard-body">
                        <p style="font-size:11px; margin-bottom:8px;">Member of:</p>
                        <input type="text" id="joinDomain" placeholder="Domain name" style="margin-bottom:10px;">
                        <input type="text" id="joinUser" placeholder="Domain account" style="margin-bottom:10px;">
                        <button class="option-btn" onclick="joinDomain()" style="background:#0066ff; color:#fff;">Join domain</button>
                        <div id="joinMsg" style="margin-top:10px; color:#ffaa00; font-size:11px;"></div>
                    </div>
                </div>
            `;
        }

        function joinDomain() {
            const dom = (document.getElementById('joinDomain')?.value || '').trim().toLowerCase();
            const usr = (document.getElementById('joinUser')?.value || '').trim();
            const msg = document.getElementById('joinMsg');
            if (dom !== networkData.domain) {
                if (msg) msg.innerHTML = `Domain not found. The server hosts ${networkData.domain}.`;
                return;
            }
            if (usr.toLowerCase() !== (networkData.domainUser || '').toLowerCase()) {
                if (msg) msg.innerHTML = `Logon failure. Use the account created in AD (${networkData.domainUser}).`;
                return;
            }
            steps[12].completed = true;
            updateStepStatus();
            showMessage(`Welcome to the ${dom} domain`, 'success');
            showMapDriveScreen();
        }

        /* ---------- Step 14: Map drive ---------- */
        function showMapDriveScreen() {
            const screen = document.getElementById('screenContent');
            if (!screen) return;
            screen.innerHTML = `
                <div class="setup-wizard">
                    <div class="wizard-header client"><i class="fas fa-hdd"></i> CLIENT-PC - Map Network Drive</div>
                    <div class="wizard-body">
                        <p style="font-size:11px; margin-bottom:8px;">Folder: \\\\SERVER01\\${networkData.shareName}</p>
                        <div class="wizard-option" onclick="mapDrive('Z:')">Z: - Map as Z drive</div>
                        <div class="wizard-option" onclick="mapDrive('S:')">S: - Map as S drive</div>
                        <div class="wizard-option" onclick="mapDrive('C:')">C: - Map as C drive</div>
                        <div id="mapMsg" style="margin-top:10px; color:#ffaa00; font-size:11px;"></div>
                    </div>
                </div>
            `;
        }

        function mapDrive(letter) {
            if (letter === 'C:') {
                const msg = document.getElementById('mapMsg');
                if (msg) msg.innerHTML = 'C: is the local system drive. Pick a free letter.';
                return;
            }
            networkData.driveLetter = letter;
            steps[13].completed = true;
            updateStepStatus();
            showPingScreen();
        }

        /* ---------- Step 15: Ping test ---------- */
        function showPingScreen() {
            const screen = document.getElementById('screenContent');
            if (!screen) return;
            screen.innerHTML = `
                <div class="setup-wizard">
                    <div class="wizard-header client"><i class="fas fa-terminal"></i> CLIENT-PC - Command Prompt</div>
                    <div class="wizard-body">
                        <p style="font-size:11px;">Confirm the client can reach the server.</p>
                        <button class="option-btn" onclick="finishPingTest()" style="margin-top:10px; background:#0066ff; color:#fff;">Run ping ${networkData.serverIP}</button>
                        <div id="pingOut" class="console-out" style="display:none;"></div>
                    </div>
                </div>
            `;
        }

        function finishPingTest() {
            const out = document.getElementById('pingOut');
            if (out) {
                out.style.display = 'block';
                out.textContent =
                    `C:\\Users\\${networkData.domainUser}> ping ${networkData.serverIP}\n\n` +
                    `Reply from ${networkData.serverIP}: bytes=32 time<1ms TTL=128\n` +
                    `Reply from ${networkData.serverIP}: bytes=32 time<1ms TTL=128\n` +
                    `Reply from ${networkData.serverIP}: bytes=32 time<1ms TTL=128\n\n` +
                    `Packets: Sent = 4, Received = 4, Lost = 0 (0% loss)`;
            }
            if (steps[14].completed) return;
            steps[14].completed = true;
            updateStepStatus();
            setTimeout(() => {
                showCompletion();
                createConfetti();
            }, 1200);
        }

        function showCompletion() {
            const screen = document.getElementById('screenContent');
            if (!screen) return;
            screen.innerHTML = `
                <div style="padding:40px; text-align:center;">
                    <i class="fas fa-check-circle" style="font-size:4rem; color:#00ff9d;"></i>
                    <h2>Network is live</h2>
                    <p>CLIENT-PC is joined to ${networkData.domain || 'the domain'}</p>
                    <p style="margin-top:10px; font-size:12px;">Server: ${networkData.serverIP} &bull; Drive ${networkData.driveLetter} &rarr; \\\\SERVER01\\${networkData.shareName}</p>
                    <p style="margin-top:10px; font-size:14px; color:#ffaa00;">Your Score: ${totalScore} points!</p>
                    <button class="option-btn" onclick="showFinalModal()" style="margin-top:20px; background:#00ff9d; color:#000;">Finish setup &rarr;</button>
                </div>
            `;
        }

        function showFinalModal() {
            document.getElementById('completionModal').classList.add('active');
        }

        function saveAndClose() {
            if (gameSaved) return;

            fetch('save_client_server_score.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'score=' + totalScore + '&steps_completed=' + steps.filter(s => s.completed).length
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    gameSaved = true;
                    showMessage('&#9989; Score saved to database!', 'success');
                    document.getElementById('bestScore').textContent = totalScore;
                }
            })
            .catch(error => console.error('Error:', error));

            setTimeout(() => {
                document.getElementById('completionModal').classList.remove('active');
            }, 1500);
        }

        function createConfetti() {
            for (let i = 0; i < 80; i++) {
                const c = document.createElement('div');
                c.style.cssText = `position:fixed; left:${Math.random()*100}%; top:-10px; width:${Math.random()*8+5}px; height:${Math.random()*8+5}px; background:hsl(${Math.random()*360},100%,50%); border-radius:${Math.random()>0.5?'50%':'0'}; z-index:2000; animation:fall ${Math.random()*2+2}s linear forwards;`;
                document.body.appendChild(c);
                setTimeout(() => c.remove(), 3000);
            }
        }

        function resetNetwork() {
            if (roleInterval) clearInterval(roleInterval);
            cablePlugged = false;
            gameSaved = false;
            clientUnlocked = false;
            subnetSet = gatewaySet = dnsSet = false;
            networkData = {};
            steps.forEach(s => s.completed = false);
            totalScore = 0;

            const cable = document.getElementById('lanCable');
            const port = document.getElementById('rj45Port');
            const led = document.getElementById('linkLed');
            const sw = document.getElementById('switchBox');
            if (cable) cable.classList.remove('connected');
            if (port) port.classList.remove('active');
            if (led) led.classList.remove('up');
            if (sw) sw.classList.remove('connected');
            document.getElementById('swp1').classList.remove('lit');
            document.getElementById('swp2').classList.remove('lit');
            document.getElementById('tabClient').classList.add('locked');
            switchMachine('server');

            const screen = document.getElementById('screenContent');
            if (screen) {
                screen.innerHTML = `
                    <div style="padding:15px;">
                        <h3>SERVER01 - Hardware Status</h3><hr>
                        <p>Role: Windows Server (Domain Controller candidate)</p>
                        <p>NIC: Intel I219-LM Gigabit Ethernet</p>
                        <p>Link: <span id="linkStatus" style="color:#ffaa00;">&#9888; Media disconnected</span></p>
                        <p>IPv4: <span id="ipStatus" style="color:#ffaa00;">Not configured</span></p><hr>
                        <div>
                            <p><strong>Network checklist:</strong></p>
                            <div class="wizard-option">1. Patch cable to switch <span id="cableStatus">(Unplugged)</span></div>
                            <div class="wizard-option">2. Static IP on server</div>
                            <div class="wizard-option">3. Roles: AD DS, DHCP, DNS</div>
                        </div>
                        <button id="configBtn" class="option-btn" style="margin-top:15px; display:none; background:#00ff9d; color:#000;" onclick="openIPConfig()">&#128225; Open network settings</button>
                        <p style="margin-top:15px; font-size:11px; color:#ffaa00;">&#128073; Drag the patch cable into the switch to start over</p>
                    </div>
                `;
            }
            updateStepStatus();
            document.getElementById('currentScore').textContent = '0';
            showMessage('Lab reset. Drag the patch cable to start over.', 'success');
        }

        function closeCompletionModal() {
            const modal = document.getElementById('completionModal');
            if (modal) modal.classList.remove('active');
        }

        document.addEventListener('DOMContentLoaded', () => {
            initDragDrop();
            renderStepsList();
            updateStepStatus();
        });
    </script>

    <?php if (file_exists(__DIR__ . '/includes/brightness_widget.php')) { $global_brightness = function_exists('getGlobalBrightness') ? getGlobalBrightness() : 100; include __DIR__ . '/includes/brightness_widget.php'; } ?>
    <?php if (file_exists(__DIR__ . '/includes/theme_toggle.php')) { include __DIR__ . '/includes/theme_toggle.php'; } ?>
</body>
</html>
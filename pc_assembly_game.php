<?php
// windows_server_2012_r2_install.php - Windows Server 2012 R2 OS Installation Simulator
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$page_title = "Windows Server 2012 R2 Installation Simulator";
$previous_score = ['score' => 0];
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
            background: #0a0f1e;
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
            background: radial-gradient(circle at 20% 30%, #0f172a, #020617);
        }

        .grid-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: linear-gradient(rgba(0, 255, 157, 0.03) 1px, transparent 1px),
                              linear-gradient(90deg, rgba(0, 255, 157, 0.03) 1px, transparent 1px);
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
            align-items: flex-start;
        }

        .rack-container {
            flex-shrink: 0;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        /* Machine tabs */
        .machine-tabs {
            display: flex;
            gap: 8px;
            margin-bottom: 14px;
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

        /* ============================================
           PC SETUP ROW - monitor + tower case + switch
           laid out with real flexbox instead of absolute
           positioning, so nothing drifts or overlaps.
           ============================================ */
        .pc-setup {
            display: flex;
            align-items: flex-end;
            justify-content: center;
            gap: 28px;
            flex-wrap: wrap;
        }

        /* Monitor column: screen + neck + base, like a real display */
        .monitor-stack {
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .monitor {
            width: 620px;
            max-width: 80vw;
        }

        .monitor-screen {
            width: 100%;
            height: 430px;
            background: #1a1f2e;
            border-radius: 18px;
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

        .monitor-stand-neck {
            width: 18px;
            height: 32px;
            background: linear-gradient(#3a3f4e, #20242f);
        }

        .monitor-stand-base {
            width: 170px;
            height: 14px;
            border-radius: 8px;
            background: linear-gradient(#2a2f3e, #1a1f2e);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.4);
        }

        /* Decorative keyboard - purely visual, not interactive */
        .keyboard {
            margin-top: 14px;
            width: 340px;
            max-width: 90%;
            background: linear-gradient(160deg, #2a2f3e, #171b26 80%);
            border: 1px solid rgba(0, 255, 157, 0.25);
            border-radius: 10px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.45);
            padding: 10px 12px 12px;
            pointer-events: none;
        }

        .keyboard-row {
            display: flex;
            gap: 4px;
            margin-bottom: 4px;
            justify-content: center;
        }

        .keyboard-row:last-child {
            margin-bottom: 0;
        }

        .kb-key {
            height: 18px;
            flex: 1;
            min-width: 10px;
            max-width: 22px;
            background: linear-gradient(#3d4356, #262b38);
            border-radius: 3px;
            border-bottom: 2px solid #14161f;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 8px;
            font-weight: 600;
            color: #aab4d4;
            font-family: 'Rajdhani', sans-serif;
        }

        .kb-key.wide {
            max-width: 46px;
            font-size: 6.5px;
        }

        .kb-key.space {
            max-width: 160px;
            flex: 4;
        }

        .keyboard-led {
            width: 5px;
            height: 5px;
            border-radius: 50%;
            background: #00ff9d;
            box-shadow: 0 0 5px #00ff9d;
            margin: 0 auto 6px;
        }

        /* Tower case: an actual PC next to the monitor */
        .tower-case {
            width: 190px;
            min-height: 430px;
            background: linear-gradient(160deg, #2f3546, #171b26 70%);
            border-radius: 14px;
            border: 1px solid rgba(0, 255, 157, 0.25);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5), inset 0 0 20px rgba(0, 0, 0, 0.4);
            display: flex;
            flex-direction: column;
            padding: 14px 12px;
            position: relative;
        }

        .tower-top-vents {
            height: 36px;
            border-radius: 6px;
            margin-bottom: 12px;
            background-image: repeating-linear-gradient(0deg, rgba(0, 0, 0, 0.55) 0 3px, transparent 3px 7px);
        }

        .tower-front {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 14px;
            flex: 1;
        }

        .tower-power-btn {
            width: 34px;
            height: 34px;
            border-radius: 50%;
            background: radial-gradient(circle at 35% 30%, #3d4356, #14161f);
            border: 2px solid rgba(0, 255, 157, 0.5);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .power-ring {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: #00ff9d;
            box-shadow: 0 0 8px #00ff9d;
            animation: blink 2s infinite;
        }

        .tower-drive-bay {
            width: 130px;
            height: 10px;
            background: #0e1118;
            border: 1px solid #3d4356;
            border-radius: 3px;
        }

        .tower-brand {
            font-family: 'Orbitron', sans-serif;
            font-size: 0.65rem;
            letter-spacing: 1px;
            color: #8899cc;
            text-align: center;
        }

        .tower-io-panel {
            margin-top: auto;
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            justify-content: center;
            padding-top: 12px;
            border-top: 1px dashed rgba(136, 153, 204, 0.3);
        }

        .tower-vents-side {
            position: absolute;
            right: 6px;
            top: 60px;
            bottom: 60px;
            width: 10px;
            border-radius: 4px;
            opacity: 0.6;
            background-image: repeating-linear-gradient(0deg, rgba(0, 0, 0, 0.55) 0 2px, transparent 2px 6px);
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

        /* Switch drop target - now a normal flex item, not absolutely positioned */
        .switch-box {
            width: 120px;
            padding: 8px;
            align-self: center;
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
            overflow-y: auto;
            padding: 20px 0;
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

        /* Windows-installed screenshot window shown on completion */
        .screenshot-window {
            margin: 14px auto 0;
            max-width: 360px;
            border-radius: 10px;
            overflow: hidden;
            border: 1px solid rgba(0, 255, 157, 0.4);
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.5);
            background: #0a0f1e;
        }

        .screenshot-titlebar {
            display: flex;
            align-items: center;
            gap: 6px;
            padding: 6px 10px;
            background: #1a1f2e;
        }

        .screenshot-titlebar .dot {
            width: 9px;
            height: 9px;
            border-radius: 50%;
        }

        .screenshot-titlebar .dot.red { background: #ef4444; }
        .screenshot-titlebar .dot.yellow { background: #ffaa00; }
        .screenshot-titlebar .dot.green { background: #00ff9d; }

        .screenshot-title {
            margin-left: 6px;
            font-size: 0.7rem;
            color: #8899cc;
        }

        .screenshot-img {
            width: 100%;
            display: block;
        }

        /* Windows screenshot filling the entire monitor screen (cancels the
           screen-content padding so the image goes edge-to-edge) */
        .onscreen-shot-full {
            position: relative;
            margin: -20px;
            width: calc(100% + 40px);
            height: calc(100% + 40px);
            overflow: hidden;
        }

        .onscreen-shot-full img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        .onscreen-shot-overlay {
            position: absolute;
            left: 0;
            right: 0;
            bottom: 0;
            padding: 16px 18px 18px;
            background: linear-gradient(to top, rgba(0, 0, 0, 0.88), rgba(0, 0, 0, 0.35) 60%, transparent);
            text-align: center;
            color: #fff;
        }

        .onscreen-shot-overlay p {
            font-size: 11px;
            margin: 2px 0;
        }

        .onscreen-shot-overlay .onscreen-caption {
            font-size: 13px;
            color: #00ff9d;
            margin-bottom: 4px;
        }

        .onscreen-shot-overlay .onscreen-score {
            color: #ffaa00;
            font-size: 13px;
            margin-top: 6px;
        }

        .onscreen-shot-overlay .option-btn {
            margin-top: 8px;
            background: #00ff9d;
            color: #000;
        }

        @keyframes fall {
            to {
                transform: translateY(100vh) rotate(360deg);
                opacity: 0;
            }
        }

        @media (max-width: 1100px) {
            .pc-setup { transform: scale(0.9); transform-origin: top center; }
        }

        @media (max-width: 900px) {
            .workspace { flex-direction: column; align-items: center; }
            .right-panel { width: 100%; flex-direction: row; flex-wrap: wrap; justify-content: center; }
            .cable-section, .cmd-section, .steps-section { flex: 1; min-width: 200px; }
            .pc-setup { transform: scale(0.8); }
        }

        input, select {
            background: #3d3d3d;
            border: 1px solid #00ff9d;
            color: #fff;
            padding: 8px;
            border-radius: 5px;
            width: 100%;
        }

        /* Dark / Light Mode Toggle - same behavior as the reference page */
        .theme-toggle {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 14px;
            border: 1px solid rgba(0, 255, 157, 0.45);
            border-radius: 50px;
            background: rgba(10, 20, 40, 0.85);
            color: #00ff9d;
            font-family: 'Rajdhani', sans-serif;
            font-weight: 600;
            font-size: 0.85rem;
            cursor: pointer;
            transition: all 0.3s ease;
            white-space: nowrap;
        }

        .theme-toggle:hover {
            background: #00ff9d;
            color: #050812;
            box-shadow: 0 0 15px rgba(0, 255, 157, 0.35);
            transform: translateY(-1px);
        }

        body.light-mode {
            background: #f1f5f9;
            color: #1e293b;
        }

        body.light-mode .cyber-bg {
            background: linear-gradient(135deg, #e2e8f0 0%, #f8fafc 100%);
        }

        body.light-mode .grid-overlay {
            background-image:
                linear-gradient(rgba(0, 102, 255, 0.08) 1px, transparent 1px),
                linear-gradient(90deg, rgba(0, 102, 255, 0.08) 1px, transparent 1px);
        }

        body.light-mode .navbar {
            background: rgba(255, 255, 255, 0.92);
            border-bottom-color: #cbd5e1;
        }

        body.light-mode .nav-links a {
            color: #475569;
        }

        body.light-mode .nav-links a:hover {
            color: #008f68;
        }

        body.light-mode .user-info {
            color: #008f68;
        }

        body.light-mode .theme-toggle {
            background: #ffffff;
            border-color: #10b981;
            color: #047857;
        }

        body.light-mode .theme-toggle:hover {
            background: #10b981;
            color: #ffffff;
        }

        body.light-mode .game-header p {
            color: #475569;
        }

        body.light-mode .machine-tab {
            background: #e2e8f0;
            color: #475569;
        }

        body.light-mode .machine-tab.active {
            background: #10b981;
            color: #000;
        }

        body.light-mode .monitor-screen {
            background: #e2e8f0;
        }

        body.light-mode .screen-bezel {
            background: #cbd5e1;
        }

        body.light-mode .screen-content {
            background: #f8fafc;
            color: #047857;
        }

        body.light-mode .monitor-stand-neck,
        body.light-mode .monitor-stand-base {
            background: linear-gradient(#cbd5e1, #94a3b8);
        }

        body.light-mode .tower-case {
            background: linear-gradient(160deg, #e2e8f0, #cbd5e1 70%);
            border-color: #94a3b8;
        }

        body.light-mode .tower-brand,
        body.light-mode .port-slot {
            color: #475569;
        }

        body.light-mode .tower-top-vents,
        body.light-mode .tower-vents-side {
            background-image: repeating-linear-gradient(0deg, rgba(100, 116, 139, 0.5) 0 3px, transparent 3px 7px);
        }

        body.light-mode .tower-drive-bay {
            background: #f8fafc;
            border-color: #94a3b8;
        }

        body.light-mode .rj45 {
            background: #cbd5e1;
            border-color: #64748b;
            color: #475569;
        }

        body.light-mode .switch-box {
            background: rgba(255, 255, 255, 0.88);
            color: #047857;
        }

        body.light-mode .switch-ports span {
            background: #94a3b8;
        }

        body.light-mode .cable-section,
        body.light-mode .cmd-section,
        body.light-mode .score-display,
        body.light-mode .steps-section,
        body.light-mode .instructions {
            background: #ffffff;
            border-color: #cbd5e1;
            color: #1e293b;
        }

        body.light-mode .lan-cable {
            background: linear-gradient(135deg, #e2e8f0, #cbd5e1);
        }

        body.light-mode .cmd-btn {
            background: #e2e8f0;
            color: #047857;
        }

        body.light-mode .step-item {
            background: #f1f5f9;
        }

        body.light-mode .step-name {
            color: #334155;
        }

        body.light-mode .step-badge {
            background: #e2e8f0;
            color: #334155;
        }

        body.light-mode .wizard-option,
        body.light-mode .option-btn,
        body.light-mode .progress-bar {
            background: #e2e8f0;
            color: #1e293b;
        }

        body.light-mode .wizard-option:hover,
        body.light-mode .option-btn:hover {
            background: #10b981;
            color: #000;
        }

        body.light-mode input,
        body.light-mode select {
            background: #ffffff;
            color: #1e293b;
            border-color: #10b981;
        }

        body.light-mode input::placeholder {
            color: #64748b;
        }

        body.light-mode .completion-modal {
            background: rgba(15, 23, 42, 0.72);
        }

        body.light-mode .completion-content {
            background: #ffffff;
            color: #1e293b;
        }

        body.light-mode .screenshot-window {
            background: #ffffff;
            border-color: #cbd5e1;
        }

        body.light-mode .screenshot-titlebar {
            background: #e2e8f0;
        }

        @media (max-width: 700px) {
            .theme-toggle {
                font-size: 0.75rem;
                padding: 7px 11px;
            }
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
                <button type="button" class="theme-toggle" id="themeToggle" aria-label="Toggle light and dark mode" title="Toggle light and dark mode">
                    <i class="fas fa-moon" id="themeIcon"></i>
                    <span id="themeText">Dark Mode</span>
                </button>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="game-header">
            <h1><i class="fas fa-server"></i> Windows Server 2012 R2 Installation Simulator</h1>
            <p>Install Windows Server 2012 R2 on a server computer - from boot media to a verified Server Manager desktop!</p>
        </div>

        <div class="workspace">
            <!-- Machine -->
            <div class="rack-container">
                <div class="machine-tabs">
                    <div class="machine-tab active" id="tabServer"><i class="fas fa-server"></i> SERVER01</div>
                    <div class="machine-tab locked" id="tabInstaller"><i class="fas fa-compact-disc"></i> INSTALLER</div>
                </div>

                <div class="pc-setup">
                    <!-- Monitor on a stand -->
                    <div class="monitor-stack">
                        <div class="monitor">
                            <div class="monitor-screen" id="monitorScreen">
                                <div class="screen-bezel">
                                    <div class="power-led"></div>
                                    <div class="screen-content" id="screenContent">
                                        <div style="padding: 15px;">
                                            <h3>SERVER01 - BIOS / UEFI Setup</h3>
                                            <hr>
                                            <p>CPU: Intel Xeon E5 Server CPU</p>
                                            <p>RAM: 16GB Server Memory</p>
                                            <p>Storage: 1TB Server Disk</p>
                                            <p>Boot Media: <span id="mediaStatus" style="color:#ffaa00;">&#9888; Not Detected</span></p>
                                            <hr>
                                            <div>
                                                <p><strong>Installation checklist:</strong></p>
                                                <div class="wizard-option">1. Boot Windows Server 2012 R2 media <span id="bootStatus">(Waiting)</span></div>
                                                <div class="wizard-option">2. Configure language and keyboard</div>
                                                <div class="wizard-option">3. Install and configure Windows Server 2012 R2</div>
                                            </div>
                                            <button id="startBtn" class="option-btn" style="margin-top:15px; display:none; background:#00ff9d; color:#000;" onclick="startInstallation()">&#128640; Start Windows Server Setup</button>
                                            <p style="margin-top:15px; font-size:11px; color:#ffaa00;">&#128073; Drag the Windows Server 2012 R2 installation media from the right panel to the USB port below the PC</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="monitor-stand-neck"></div>
                        <div class="monitor-stand-base"></div>

                        <!-- Decorative keyboard, not interactive -->
                        <div class="keyboard" aria-hidden="true">
                            <div class="keyboard-led"></div>
                            <div class="keyboard-row">
                                <div class="kb-key">1</div><div class="kb-key">2</div><div class="kb-key">3</div><div class="kb-key">4</div><div class="kb-key">5</div><div class="kb-key">6</div><div class="kb-key">7</div><div class="kb-key">8</div><div class="kb-key">9</div><div class="kb-key">0</div><div class="kb-key">-</div><div class="kb-key">=</div>
                            </div>
                            <div class="keyboard-row">
                                <div class="kb-key wide">Tab</div><div class="kb-key">Q</div><div class="kb-key">W</div><div class="kb-key">E</div><div class="kb-key">R</div><div class="kb-key">T</div><div class="kb-key">Y</div><div class="kb-key">U</div><div class="kb-key">I</div><div class="kb-key">O</div><div class="kb-key">P</div>
                            </div>
                            <div class="keyboard-row">
                                <div class="kb-key wide">Caps</div><div class="kb-key">A</div><div class="kb-key">S</div><div class="kb-key">D</div><div class="kb-key">F</div><div class="kb-key">G</div><div class="kb-key">H</div><div class="kb-key">J</div><div class="kb-key">K</div><div class="kb-key">L</div><div class="kb-key wide">Enter</div>
                            </div>
                            <div class="keyboard-row">
                                <div class="kb-key wide">Shift</div><div class="kb-key">Z</div><div class="kb-key">X</div><div class="kb-key">C</div><div class="kb-key">V</div><div class="kb-key">B</div><div class="kb-key">N</div><div class="kb-key">M</div><div class="kb-key wide">Shift</div>
                            </div>
                            <div class="keyboard-row">
                                <div class="kb-key wide">Ctrl</div><div class="kb-key wide">Alt</div><div class="kb-key space">Space</div><div class="kb-key wide">Alt</div><div class="kb-key wide">Ctrl</div>
                            </div>
                        </div>
                    </div>

                    <!-- Real PC tower case -->
                    <div class="tower-case">
                        <div class="tower-top-vents"></div>
                        <div class="tower-front">
                            <div class="tower-power-btn"><span class="power-ring"></span></div>
                            <div class="tower-drive-bay"></div>
                            <div class="tower-brand" id="chassisLabel">SERVER01 &bull; Windows Server</div>
                            <div class="tower-io-panel">
                                <div class="port-slot"><div class="rj45"><i class="fas fa-compact-disc"></i></div>DVD<div class="link-led" id="dvdLed"></div></div>
                                <div class="port-slot"><div class="rj45" id="usbPort"><i class="fas fa-usb"></i></div>USB<div class="link-led" id="usbLed"></div></div>
                                <div class="port-slot"><div class="rj45"><i class="fas fa-ethernet"></i></div>LAN<div class="link-led"></div></div>
                                <div class="port-slot"><div class="rj45"><i class="fas fa-plug"></i></div>PWR<div class="link-led up"></div></div>
                            </div>
                        </div>
                        <div class="tower-vents-side"></div>
                    </div>

                    <!-- Installation media drop target -->
                    <div class="switch-box" id="installTarget">
                        <i class="fas fa-usb"></i> USB INSTALL MEDIA
                        <div class="switch-ports">
                            <span></span><span id="mediaPort"></span><span></span><span></span>
                        </div>
                        <div style="margin-top:4px;">Drop Windows Server media here</div>
                    </div>
                </div>
            </div>

            <!-- Right Panel -->
            <div class="right-panel">
                <div class="cable-section">
                    <h4><i class="fas fa-compact-disc"></i> Installation Media</h4>
                    <div class="lan-cable" id="serverMedia" draggable="true">
                        <i class="fas fa-compact-disc"></i> <span>Server 2012 R2</span> <i class="fas fa-arrows-alt"></i>
                    </div>
                    <div class="cable-label">&#11013;&#65039; Drag media to the USB port</div>
                </div>

                <div class="cmd-section">
                    <h4><i class="fas fa-keyboard"></i> BIOS Shortcuts</h4>
                    <div class="cmd-buttons">
                        <div class="cmd-btn" onclick="pressShortcut('F2')">F2</div>
                        <div class="cmd-btn" onclick="pressShortcut('F12')">F12</div>
                        <div class="cmd-btn" onclick="pressShortcut('DEL')">DEL</div>
                        <div class="cmd-btn" onclick="pressShortcut('ESC')">ESC</div>
                    </div>
                    <div class="cmd-note">F2/DEL: Setup | F12: Boot Menu | ESC: Exit</div>
                </div>

                <div class="score-display">
                    <i class="fas fa-star"></i> Your Score
                    <div class="score-value" id="currentScore">0</div>
                    <small>Best: <span id="bestScore"><?php echo $previous_score['score'] ?? 0; ?></span></small>
                </div>

                <div class="steps-section">
                    <h4><i class="fas fa-list-ol"></i> Installation Steps (1-15)</h4>
                    <div class="steps-list" id="stepsList"></div>
                </div>
            </div>
        </div>

        <div class="instructions">
            <p><strong>&#128203; Windows Server 2012 R2 OS Installation Guide (15 Steps)</strong></p>
            <p><span class="drag-hint">1&#65039;&#8419; Boot installation media</span> <span class="drag-hint">2&#65039;&#8419; Language &amp; keyboard</span> <span class="drag-hint">3&#65039;&#8419; Start Windows Setup</span></p>
            <p><span class="drag-hint">4&#65039;&#8419; Select Server edition</span> <span class="drag-hint">5&#65039;&#8419; Accept license terms</span> <span class="drag-hint">6&#65039;&#8419; Choose Custom install</span></p>
            <p><span class="drag-hint">7&#65039;&#8419; Prepare disk partition</span> <span class="drag-hint">8&#65039;&#8419; Install Windows files</span> <span class="drag-hint">9&#65039;&#8419; Restart &amp; boot</span></p>
            <p><span class="drag-hint">&#128100; Set Administrator password</span> <span class="drag-hint">1&#65039;&#8419;1&#65039;&#8419; First sign-in</span> <span class="drag-hint">1&#65039;&#8419;2&#65039;&#8419; Configure network</span></p>
            <p><span class="drag-hint">1&#65039;&#8419;3&#65039;&#8419; Rename server</span> <span class="drag-hint">1&#65039;&#8419;4&#65039;&#8419; Updates &amp; activation</span> <span class="drag-hint">1&#65039;&#8419;5&#65039;&#8419; Verify installation</span></p>
        </div>

        <div class="step-status" id="stepStatus"></div>

        <div class="control-buttons">
            <button class="btn btn-secondary" onclick="resetInstallation()"><i class="fas fa-redo"></i> Reset</button>
        </div>
    </div>

    <div id="completionModal" class="completion-modal">
        <div class="completion-content">
            <i class="fas fa-server" style="font-size: 3rem; color: #ffaa00;"></i>
            <h2>Windows Server 2012 R2 Installed!</h2>
            <p>The operating system installation is complete and the server is ready to use.</p>
            <div id="completionMessage"></div>
            <div style="margin-top: 1rem;">
                <button class="btn btn-primary" onclick="saveAndClose()"><i class="fas fa-save"></i> Save Score</button>
                <button class="btn btn-secondary" onclick="resetInstallation(); closeCompletionModal();"><i class="fas fa-redo"></i> Practice Again</button>
            </div>
        </div>
    </div>

    <script>
        // ============================================
        // WINDOWS SERVER 2012 R2 OS INSTALLATION SIMULATOR
        // 15 Installation Steps with Database Save
        // ============================================

        let mediaInserted = false;
        let totalScore = 0;
        let gameSaved = false;
        let installationRunning = false;
        let currentStep = 0;
        let installTimer = null;

        const completionImage = 'https://i.pinimg.com/736x/cf/d3/ca/cfd3ca2f7bf320e57424f7fca49eab49.jpg';

        const steps = [
            { id: 1,  name: "Boot Installation Media", desc: "Start the server from Windows Server 2012 R2 DVD/ISO or USB media", completed: false, points: 10 },
            { id: 2,  name: "Language & Keyboard", desc: "Choose language, time/currency format, and keyboard layout", completed: false, points: 10 },
            { id: 3,  name: "Start Windows Setup", desc: "Select Install Now to begin Windows Server setup", completed: false, points: 10 },
            { id: 4,  name: "Select Server Edition", desc: "Choose Windows Server 2012 R2 Standard or Datacenter with GUI", completed: false, points: 10 },
            { id: 5,  name: "Accept License Terms", desc: "Read and accept the Microsoft software license terms", completed: false, points: 10 },
            { id: 6,  name: "Choose Custom Install", desc: "Select Custom installation for a clean server installation", completed: false, points: 10 },
            { id: 7,  name: "Prepare Disk Partition", desc: "Select, create, or format the target server disk", completed: false, points: 10 },
            { id: 8,  name: "Install Windows Files", desc: "Copy files, install features, and apply the Windows image", completed: false, points: 10 },
            { id: 9,  name: "Restart & Boot", desc: "Restart and boot from the newly installed server disk", completed: false, points: 10 },
            { id: 10, name: "Set Administrator Password", desc: "Create and confirm the local Administrator password", completed: false, points: 10 },
            { id: 11, name: "First Sign-In", desc: "Sign in to Windows Server as Administrator", completed: false, points: 10 },
            { id: 12, name: "Configure Network", desc: "Configure IPv4 networking for the server", completed: false, points: 10 },
            { id: 13, name: "Rename the Server", desc: "Set a meaningful computer name such as SERVER01", completed: false, points: 10 },
            { id: 14, name: "Updates & Activation", desc: "Check Windows Update and verify Windows activation", completed: false, points: 10 },
            { id: 15, name: "Verify Installation", desc: "Open Server Manager and confirm Windows Server is ready", completed: false, points: 10 }
        ];

        const installationData = {
            language: 'English (United States)',
            keyboard: 'US',
            edition: 'Windows Server 2012 R2 Standard (Server with a GUI)',
            disk: 'Disk 0 - 100 GB',
            serverName: 'SERVER01'
        };

        function renderStepsList() {
            const list = document.getElementById('stepsList');
            if (!list) return;
            list.innerHTML = steps.map((step, i) => `
                <div class="step-item ${step.completed ? 'completed' : ''} ${i === currentStep && !step.completed ? 'current' : ''}" id="step-${step.id}" onclick="performStep(${step.id})">
                    <div class="step-number">${step.id}</div>
                    <div class="step-info"><strong>${step.name}</strong><small>${step.desc}</small></div>
                    <div class="step-check">${step.completed ? '✓' : '○'}</div>
                </div>
            `).join('');
        }

        function updateStepStatus() {
            const completed = steps.filter(s => s.completed).length;
            document.getElementById('currentScore').textContent = totalScore;
            const status = document.getElementById('stepStatus');
            if (status) status.innerHTML = completed === steps.length
                ? '<strong>Installation complete!</strong> Windows Server 2012 R2 is ready.'
                : `<strong>Progress:</strong> ${completed}/15 installation steps completed.`;
            renderStepsList();
        }

        function showMessage(msg, type='success') {
            const status = document.getElementById('stepStatus');
            if (!status) return;
            status.innerHTML = `<strong>${msg}</strong>`;
            status.className = `step-status ${type}`;
        }

        function initDragDrop() {
            const media = document.getElementById('serverMedia');
            const target = document.getElementById('installTarget');
            const usb = document.getElementById('usbPort');
            if (!media) return;

            media.addEventListener('dragstart', e => {
                e.dataTransfer.setData('text/plain', 'server2012r2');
                media.classList.add('dragging');
            });
            media.addEventListener('dragend', () => media.classList.remove('dragging'));

            [target, usb].forEach(el => {
                if (!el) return;
                el.addEventListener('dragover', e => { e.preventDefault(); el.classList.add('active'); });
                el.addEventListener('dragleave', () => el.classList.remove('active'));
                el.addEventListener('drop', e => {
                    e.preventDefault();
                    el.classList.remove('active');
                    insertInstallationMedia();
                });
            });
        }

        function insertInstallationMedia() {
            if (mediaInserted) return;
            mediaInserted = true;
            document.getElementById('serverMedia')?.classList.add('connected');
            document.getElementById('mediaStatus').innerHTML = '&#10004; Windows Server 2012 R2 media detected';
            document.getElementById('mediaStatus').style.color = '#00ff9d';
            document.getElementById('bootStatus').textContent = '(Media Ready)';
            document.getElementById('startBtn').style.display = 'inline-flex';
            document.getElementById('usbLed')?.classList.add('up');
            showMessage('Installation media detected. Start Windows Server setup.');
            awardStep(1);
        }

        function pressShortcut(key) {
            if (!mediaInserted) {
                showMessage('Insert the Windows Server 2012 R2 installation media first.', 'error');
                return;
            }
            showMessage(`${key} pressed — boot/firmware shortcut acknowledged.`);
            if (key === 'F12' || key === 'F2' || key === 'DEL') {
                document.getElementById('startBtn').style.display = 'inline-flex';
            }
        }

        function startInstallation() {
            if (!mediaInserted || installationRunning) return;
            installationRunning = true;
            currentStep = 1;
            awardStep(2);
            const screen = document.getElementById('screenContent');
            screen.innerHTML = `<div style="padding:15px">
                <h3>Windows Server 2012 R2 Setup</h3><hr>
                <p>Language to install: <strong>English (United States)</strong></p>
                <p>Time and currency format: <strong>English (United States)</strong></p>
                <p>Keyboard/input method: <strong>US</strong></p>
                <button class="option-btn" style="margin-top:15px;background:#00ff9d;color:#000" onclick="nextInstallStep(3)">Continue to Windows Setup</button>
            </div>`;
            showMessage('Windows Setup started. Follow the installation steps in order.');
        }

        function awardStep(id) {
            const step = steps.find(s => s.id === id);
            if (!step || step.completed) return;
            if (id > 1 && !steps[id-2].completed) return;
            step.completed = true;
            totalScore += step.points;
            currentStep = Math.min(id, 15);
            updateStepStatus();
            if (id === 15) setTimeout(showCompletion, 350);
        }

        function nextInstallStep(id) {
            if (id === 3) {
                awardStep(3);
                document.getElementById('screenContent').innerHTML = `<div style="padding:15px">
                    <h3>Select Windows Server 2012 R2 Edition</h3><hr>
                    <div class="wizard-option" onclick="chooseEdition()">Windows Server 2012 R2 Standard (Server with a GUI)</div>
                    <div class="wizard-option" onclick="chooseEdition()">Windows Server 2012 R2 Datacenter (Server with a GUI)</div>
                </div>`;
                return;
            }
        }

        function chooseEdition() {
            awardStep(4);
            document.getElementById('screenContent').innerHTML = `<div style="padding:15px">
                <h3>Applicable License Terms</h3><hr>
                <p>Please review the Microsoft Software License Terms.</p>
                <button class="option-btn" style="margin-top:15px;background:#00ff9d;color:#000" onclick="acceptLicense()">I Accept the License Terms</button>
            </div>`;
        }

        function acceptLicense() {
            awardStep(5);
            document.getElementById('screenContent').innerHTML = `<div style="padding:15px">
                <h3>Which type of installation do you want?</h3><hr>
                <div class="wizard-option" onclick="chooseCustom()"><strong>Custom: Install Windows only (advanced)</strong><br><small>Use this for a clean server installation.</small></div>
            </div>`;
        }

        function chooseCustom() {
            awardStep(6);
            document.getElementById('screenContent').innerHTML = `<div style="padding:15px">
                <h3>Where do you want to install Windows?</h3><hr>
                <div class="wizard-option" onclick="prepareDisk()"><strong>Drive 0 Unallocated Space</strong><br><small>100 GB available for Windows Server 2012 R2.</small></div>
            </div>`;
        }

        function prepareDisk() {
            awardStep(7);
            document.getElementById('screenContent').innerHTML = `<div style="padding:15px">
                <h3>Installing Windows Server 2012 R2</h3><hr>
                <p>Copying Windows files...</p><div style="height:12px;background:#2a2f3e;border-radius:5px;overflow:hidden"><div id="installBar" style="height:100%;width:0;background:#00ff9d"></div></div>
                <p id="installPct" style="margin-top:10px">0%</p>
            </div>`;
            let pct=0;
            clearInterval(installTimer);
            installTimer=setInterval(()=>{
                pct+=5;
                const bar=document.getElementById('installBar'); const label=document.getElementById('installPct');
                if(bar) bar.style.width=pct+'%'; if(label) label.textContent=pct+'%';
                if(pct>=100){ clearInterval(installTimer); awardStep(8); restartServer(); }
            },80);
        }

        function restartServer() {
            document.getElementById('screenContent').innerHTML = `<div style="padding:15px">
                <h3>Restarting SERVER01...</h3><hr><p>Windows Server installation files are ready.</p><p style="color:#ffaa00">The server is rebooting and starting from the installed disk.</p>
            </div>`;
            setTimeout(()=>{
                awardStep(9);
                document.getElementById('screenContent').innerHTML = `<div style="padding:15px">
                    <h3>Set the Administrator Password</h3><hr>
                    <p>Create a strong password for the local Administrator account.</p>
                    <button class="option-btn" style="margin-top:15px;background:#00ff9d;color:#000" onclick="setAdminPassword()">Set Administrator Password</button>
                </div>`;
            },900);
        }

        function setAdminPassword() {
            awardStep(10);
            document.getElementById('screenContent').innerHTML = `<div style="padding:15px">
                <h3>Sign In</h3><hr><p>User: <strong>Administrator</strong></p>
                <button class="option-btn" style="margin-top:15px;background:#00ff9d;color:#000" onclick="firstSignIn()">Sign In to Windows Server</button>
            </div>`;
        }

        function firstSignIn() {
            awardStep(11);
            document.getElementById('screenContent').innerHTML = `<div style="padding:15px">
                <h3>Server Initial Configuration</h3><hr>
                <p>Administrator sign-in successful.</p>
                <button class="option-btn" style="margin-top:15px;background:#00ff9d;color:#000" onclick="configureNetwork()">Configure Network</button>
            </div>`;
        }

        function configureNetwork() {
            awardStep(12);
            document.getElementById('screenContent').innerHTML = `<div style="padding:15px">
                <h3>Network Configuration</h3><hr>
                <p>IPv4: <strong>192.168.1.10</strong></p><p>Subnet: <strong>255.255.255.0</strong></p><p>Gateway: <strong>192.168.1.1</strong></p>
                <button class="option-btn" style="margin-top:15px;background:#00ff9d;color:#000" onclick="renameServer()">Apply Network Settings</button>
            </div>`;
        }

        function renameServer() {
            awardStep(13);
            document.getElementById('screenContent').innerHTML = `<div style="padding:15px">
                <h3>Computer Name</h3><hr><p>Current name: <strong>SERVER01</strong></p>
                <button class="option-btn" style="margin-top:15px;background:#00ff9d;color:#000" onclick="updateAndActivate()">Keep SERVER01 and Continue</button>
            </div>`;
        }

        function updateAndActivate() {
            awardStep(14);
            document.getElementById('screenContent').innerHTML = `<div style="padding:15px">
                <h3>Windows Update &amp; Activation</h3><hr><p>&#10004; Windows Update checked</p><p>&#10004; Activation status verified</p>
                <button class="option-btn" style="margin-top:15px;background:#00ff9d;color:#000" onclick="verifyInstallation()">Open Server Manager</button>
            </div>`;
        }

        function verifyInstallation() {
            awardStep(15);
            showCompletion();
        }

        function showCompletion() {
            const screen = document.getElementById('screenContent');
            if (!screen) return;
            screen.classList.add('installation-complete');
            screen.innerHTML = `<div class="onscreen-shot-full"><img src="${completionImage}" alt="Windows Server 2012 R2 installation completed on SERVER01" draggable="false"></div>`;
            showFinalModal();
        }

        function showFinalModal() {
            document.getElementById('completionMessage').innerHTML = `<p>Score: <strong>${totalScore}/150</strong></p><p>15 of 15 steps completed.</p>`;
            document.getElementById('completionModal').classList.add('active');
        }

        function saveAndClose() {
            if (gameSaved) { closeCompletionModal(); return; }
            fetch('save_os_install_score.php', {
                method:'POST',
                headers:{'Content-Type':'application/x-www-form-urlencoded'},
                body:'score='+encodeURIComponent(totalScore)+'&steps_completed='+steps.filter(s=>s.completed).length
            }).then(r=>r.json()).then(d=>{
                gameSaved=true;
                if (d && d.success) document.getElementById('bestScore').textContent=totalScore;
                closeCompletionModal();
            }).catch(()=>closeCompletionModal());
        }

        function resetInstallation() {
            if (installTimer) clearInterval(installTimer);
            mediaInserted=false; totalScore=0; gameSaved=false; installationRunning=false; currentStep=0;
            steps.forEach(s=>s.completed=false);
            const media=document.getElementById('serverMedia'); if(media) media.classList.remove('connected');
            const status=document.getElementById('mediaStatus'); if(status){status.innerHTML='&#9888; Not Detected';status.style.color='#ffaa00';}
            document.getElementById('bootStatus').textContent='(Waiting)';
            document.getElementById('startBtn').style.display='none';
            document.getElementById('usbLed')?.classList.remove('up');
            const screen=document.getElementById('screenContent');
            screen.classList.remove('installation-complete');
            screen.innerHTML=`<div style="padding:15px">
                <h3>SERVER01 - BIOS / UEFI Setup</h3><hr>
                <p>CPU: Intel Xeon E5 Server CPU</p><p>RAM: 16GB Server Memory</p><p>Storage: 1TB Server Disk</p>
                <p>Boot Media: <span id="mediaStatus" style="color:#ffaa00">&#9888; Not Detected</span></p><hr>
                <div><p><strong>Installation checklist:</strong></p><div class="wizard-option">1. Boot Windows Server 2012 R2 media <span id="bootStatus">(Waiting)</span></div><div class="wizard-option">2. Configure language and keyboard</div><div class="wizard-option">3. Install and configure Windows Server 2012 R2</div></div>
                <button id="startBtn" class="option-btn" style="margin-top:15px;display:none;background:#00ff9d;color:#000" onclick="startInstallation()">&#128640; Start Windows Server Setup</button>
                <p style="margin-top:15px;font-size:11px;color:#ffaa00">&#128073; Drag the Windows Server 2012 R2 installation media from the right panel to the USB port below the PC</p>
            </div>`;
            updateStepStatus();
            showMessage('Lab reset. Drag the Windows Server 2012 R2 media to start.');
        }

        function closeCompletionModal() {
            const modal=document.getElementById('completionModal');
            if(modal) modal.classList.remove('active');
        }

        // ============================================
        // DARK / LIGHT MODE — copied from File 1
        // ============================================
        (function () {
            const themeToggle = document.getElementById('themeToggle');
            const themeIcon = document.getElementById('themeIcon');
            const themeText = document.getElementById('themeText');
            function applyTheme(theme) {
                const isLight = theme === 'light';
                document.body.classList.toggle('light-mode', isLight);
                if (themeIcon) themeIcon.className = isLight ? 'fas fa-sun' : 'fas fa-moon';
                if (themeText) themeText.textContent = isLight ? 'Light Mode' : 'Dark Mode';
            }
            const savedTheme = localStorage.getItem('css_tesda_theme') || 'dark';
            applyTheme(savedTheme);
            if (themeToggle) themeToggle.addEventListener('click', function () {
                const nextTheme = document.body.classList.contains('light-mode') ? 'dark' : 'light';
                localStorage.setItem('css_tesda_theme', nextTheme);
                applyTheme(nextTheme);
            });
        })();

        document.addEventListener('DOMContentLoaded', () => {
            initDragDrop();
            renderStepsList();
            updateStepStatus();
        });
    </script>

    <?php if (file_exists(__DIR__ . '/includes/brightness_widget.php')) { $global_brightness = function_exists('getGlobalBrightness') ? getGlobalBrightness() : 100; include __DIR__ . '/includes/brightness_widget.php'; } ?>
</body>
</html>
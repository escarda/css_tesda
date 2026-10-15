<?php
// windows_server_2012_r2_install.php - Windows Server 2012 R2 Installation Simulator
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$page_title = "Windows Server 2012 R2 Installation Simulator";

// Reuse the existing score table/endpoint so this page can be dropped into the same project
// without requiring a new database table.
$previous_score = null;
if (isset($conn)) {
    $score_query = $conn->prepare("SELECT score, steps_completed FROM client_server_scores WHERE user_id = ? ORDER BY completed_at DESC LIMIT 1");
    if ($score_query) {
        $score_query->bind_param("i", $user_id);
        $score_query->execute();
        $previous_score = $score_query->get_result()->fetch_assoc();
        $score_query->close();
    }
}
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
            position: relative;
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


        /* When installation is finished, the simulated monitor becomes the
           actual Windows Server screen. The image stays INSIDE the PC display. */
        .screen-content.installation-complete {
            padding: 0;
            overflow: hidden;
            border-radius: 8px;
            position: relative;
            background: #07111f;
        }

        .screen-content.installation-complete .onscreen-shot-full {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            margin: 0;
            overflow: hidden;
            background: #07111f;
            z-index: 5;
        }

        .screen-content.installation-complete .onscreen-shot-full img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            object-position: center;
            display: block;
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
            position: relative;
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
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            overflow: hidden;
            background: #07111f;
            z-index: 20;
        }

        .onscreen-shot-full img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            object-position: center;
            display: block;
            background: #07111f;
        }

        .onscreen-shot-full::after {
            content: '';
            position: absolute;
            inset: 0;
            pointer-events: none;
            box-shadow: inset 0 0 45px rgba(0, 255, 157, 0.12);
        }

        .onscreen-shot-overlay {
            display: none !important;
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
            <h1><i class="fab fa-windows"></i> Windows Server 2012 R2 Installation Simulator</h1>
            <p>Complete the Windows Server 2012 R2 installation from boot media through first server verification.</p>
        </div>

        <div class="workspace">
            <div class="rack-container">
                <div class="machine-tabs">
                    <div class="machine-tab active" id="tabServer" onclick="showStep(currentStep)">
                        <i class="fas fa-server"></i> SERVER01
                    </div>
                    <div class="machine-tab locked" id="tabClient">
                        <i class="fas fa-compact-disc"></i> INSTALL MEDIA
                    </div>
                </div>

                <div class="pc-setup">
                    <div class="monitor-stack">
                        <div class="monitor">
                            <div class="monitor-screen" id="monitorScreen">
                                <div class="screen-bezel">
                                    <div class="power-led"></div>
                                    <div class="screen-content" id="screenContent"></div>
                                </div>
                            </div>
                        </div>
                        <div class="monitor-stand-neck"></div>
                        <div class="monitor-stand-base"></div>

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

                    <div class="tower-case">
                        <div class="tower-top-vents"></div>
                        <div class="tower-front">
                            <div class="tower-power-btn"><span class="power-ring"></span></div>
                            <div class="tower-drive-bay"></div>
                            <div class="tower-brand" id="chassisLabel">SERVER01 &bull; Windows Server</div>
                            <div class="tower-io-panel">
                                <div class="port-slot">
                                    <div class="rj45 active"><i class="fas fa-ethernet"></i></div>
                                    LAN1
                                    <div class="link-led up"></div>
                                </div>
                                <div class="port-slot"><div class="rj45"><i class="fas fa-ethernet"></i></div>LAN2<div class="link-led"></div></div>
                                <div class="port-slot"><div class="rj45"><i class="fas fa-usb"></i></div>USB<div class="link-led"></div></div>
                                <div class="port-slot"><div class="rj45"><i class="fas fa-plug"></i></div>PWR<div class="link-led up"></div></div>
                            </div>
                        </div>
                        <div class="tower-vents-side"></div>
                    </div>

                    <div class="switch-box connected" id="switchBox">
                        <i class="fas fa-compact-disc"></i> INSTALL MEDIA
                        <div class="switch-ports">
                            <span class="lit"></span><span class="lit"></span><span></span><span></span>
                        </div>
                        <div style="margin-top:4px;">Windows Server 2012 R2</div>
                    </div>
                </div>
            </div>

            <div class="right-panel">
                <div class="cable-section">
                    <h4><i class="fas fa-compact-disc"></i> Installation Media</h4>
                    <div class="lan-cable connected" id="lanCable" role="button" tabindex="0" onclick="startInstallation()">
                        <i class="fab fa-windows"></i> <span>Server 2012 R2</span> <i class="fas fa-play"></i>
                    </div>
                    <div class="cable-label">Select the installation media to begin Windows Server 2012 R2 Setup.</div>
                </div>

                <div class="cmd-section">
                    <h4><i class="fas fa-tools"></i> Setup Controls</h4>
                    <div class="cmd-buttons">
                        <div class="cmd-btn" onclick="runCommand('diskpart')">diskpart</div>
                        <div class="cmd-btn" onclick="runCommand('ipconfig')">ipconfig</div>
                        <div class="cmd-btn" onclick="runCommand('hostname')">hostname</div>
                        <div class="cmd-btn" onclick="runCommand('winver')">winver</div>
                    </div>
                    <div class="cmd-note">Use these controls after the relevant installation stage.</div>
                </div>

                <div class="score-display">
                    <i class="fas fa-star"></i> Your Score
                    <div class="score-value" id="currentScore">0</div>
                    <small>Best: <span id="bestScore"><?php echo $previous_score['score'] ?? 0; ?></span></small>
                </div>

                <div class="steps-section">
                    <h4><i class="fas fa-list-ol"></i> Installation Steps</h4>
                    <div class="steps-list" id="stepsList"></div>
                </div>
            </div>
        </div>

        <div class="instructions">
            <p><strong>Windows Server 2012 R2 Installation Guide (15 Steps)</strong></p>
            <p><span class="drag-hint">01 Boot from DVD / ISO</span> <span class="drag-hint">02 Language &amp; Keyboard</span> <span class="drag-hint">03 Start Windows Setup</span></p><p><span class="drag-hint">04 Server Edition</span> <span class="drag-hint">05 License Terms</span> <span class="drag-hint">06 Custom Installation</span></p><p><span class="drag-hint">07 Disk Partition</span> <span class="drag-hint">08 Install Windows</span> <span class="drag-hint">09 Restart &amp; Boot</span></p><p><span class="drag-hint">10 Administrator Password</span> <span class="drag-hint">11 First Sign-In</span> <span class="drag-hint">12 Network Configuration</span></p><p><span class="drag-hint">13 Server Name</span> <span class="drag-hint">14 Updates &amp; Activation</span> <span class="drag-hint">15 Verify Installation</span></p>
        </div>

        <div class="step-status" id="stepStatus"></div>

        <div class="control-buttons">
            <button class="btn btn-secondary" onclick="resetInstallation()"><i class="fas fa-redo"></i> Reset</button>
        </div>
    </div>

    <div id="completionModal" class="completion-modal">
        <div class="completion-content">
            <i class="fas fa-trophy" style="font-size: 3rem; color: #ffaa00;"></i>
            <h2>Windows Server 2012 R2 Installation Complete!</h2>
            <p>SERVER01 has completed the Windows Server 2012 R2 installation workflow successfully.</p>
            <div id="completionMessage"></div>
            <div style="margin-top: 1rem;">
                <button class="btn btn-primary" onclick="saveAndClose()"><i class="fas fa-save"></i> Save Score</button>
                <button class="btn btn-secondary" onclick="resetInstallation(); closeCompletionModal();"><i class="fas fa-redo"></i> Practice Again</button>
            </div>
        </div>
    </div>

    <script>
        const steps = [
            { id: 1, name: 'Boot Installation Media', desc: 'Start the server from the Windows Server 2012 R2 DVD/ISO or virtual media.', completed: false, points: 10 },
            { id: 2, name: 'Language & Keyboard', desc: 'Choose the installation language, time/currency format, and keyboard layout.', completed: false, points: 10 },
            { id: 3, name: 'Start Windows Setup', desc: 'Select Install Now to begin the Windows Server 2012 R2 installation.', completed: false, points: 10 },
            { id: 4, name: 'Select Server Edition', desc: 'Choose the required Windows Server 2012 R2 edition and Server with a GUI installation.', completed: false, points: 10 },
            { id: 5, name: 'Accept License Terms', desc: 'Read and accept the Microsoft software license terms to continue.', completed: false, points: 10 },
            { id: 6, name: 'Choose Custom Install', desc: 'Select Custom: Install Windows only (advanced) for a clean server installation.', completed: false, points: 10 },
            { id: 7, name: 'Prepare Disk Partition', desc: 'Select the target disk, create or select the system partition, and format it when required.', completed: false, points: 10 },
            { id: 8, name: 'Install Windows Files', desc: 'Start the installation and allow Setup to copy files, install features, and apply updates.', completed: false, points: 10 },
            { id: 9, name: 'Restart & Boot', desc: 'Allow the server to restart and boot from the installed Windows Server system.', completed: false, points: 10 },
            { id: 10, name: 'Set Administrator Password', desc: 'Create and confirm the built-in local Administrator password.', completed: false, points: 10 },
            { id: 11, name: 'First Sign-In', desc: 'Sign in as Administrator and wait for the Windows Server desktop/Server Manager to load.', completed: false, points: 10 },
            { id: 12, name: 'Configure Network', desc: "Verify the network adapter and configure the server's IPv4 settings as required.", completed: false, points: 10 },
            { id: 13, name: 'Rename the Server', desc: 'Give the server a meaningful computer name, then restart if Windows requests it.', completed: false, points: 10 },
            { id: 14, name: 'Updates & Activation', desc: 'Check Windows Update and verify the Windows activation/license status.', completed: false, points: 10 },
            { id: 15, name: 'Verify Installation', desc: 'Open Server Manager and confirm the server is ready for roles and further configuration.', completed: false, points: 10 }
        ];

        let currentStep = 0;
        let totalScore = 0;
        let gameSaved = false;
        const installData = {
            edition: '',
            disk: '',
            password: '',
            ipMode: '',
            serverName: ''
        };

        function renderStepsList() {
            const list = document.getElementById('stepsList');
            if (!list) return;
            list.innerHTML = steps.map((step, index) => `
                <div class="step-item" onclick="showStep(${index})" style="cursor:pointer;">
                    <div class="step-num">${index + 1}</div>
                    <div class="step-name">${step.name}</div>
                    <div class="step-status-icon ${step.completed ? 'completed' : 'pending'}">
                        <i class="fas ${step.completed ? 'fa-check-circle' : 'fa-clock'}"></i>
                    </div>
                </div>
            `).join('');
        }

        function updateStepStatus() {
            renderStepsList();
            const completed = steps.filter(s => s.completed).length;
            const status = document.getElementById('stepStatus');
            if (status) {
                status.innerHTML = steps.map((s, i) =>
                    `<span class="step-badge ${s.completed ? 'completed' : ''}">${i + 1} ${s.completed ? '✓' : '•'}</span>`
                ).join('');
            }
            const score = document.getElementById('currentScore');
            if (score) score.textContent = totalScore;
            if (completed === steps.length) {
                setTimeout(showCompletion, 500);
            }
        }

        function showMessage(message, type='success') {
            const screen = document.getElementById('screenContent');
            if (!screen) return;
            const color = type === 'error' ? '#ff6677' : '#00ff9d';
            const icon = type === 'error' ? 'fa-triangle-exclamation' : 'fa-circle-check';
            const old = screen.querySelector('.inline-message');
            if (old) old.remove();
            const div = document.createElement('div');
            div.className = 'inline-message';
            div.style.cssText = `margin-top:10px;padding:8px;border:1px solid ${color};border-radius:6px;color:${color};font-size:11px;`;
            div.innerHTML = `<i class="fas ${icon}"></i> ${message}`;
            screen.appendChild(div);
        }

        function completeStep(index, message) {
            if (index < 0 || index >= steps.length) return;
            if (!steps[index].completed) {
                steps[index].completed = true;
                totalScore += steps[index].points;
            }
            currentStep = Math.min(index + 1, steps.length - 1);
            updateStepStatus();
            if (message) showMessage(message, 'success');
            if (index < steps.length - 1) {
                setTimeout(() => showStep(currentStep), 450);
            }
        }

        function showStep(index) {
            if (index < 0 || index >= steps.length) return;
            currentStep = index;
            const screen = document.getElementById('screenContent');
            if (!screen) return;

            const headerIcons = [
                'fa-power-off','fa-language','fa-play-circle','fa-server','fa-file-signature',
                'fa-wrench','fa-hdd','fa-copy','fa-redo','fa-key','fa-user-shield',
                'fa-network-wired','fa-tag','fa-sync-alt','fa-check-double'
            ];
            const headers = [
                'BOOT FROM INSTALLATION MEDIA','WINDOWS SETUP','INSTALL NOW','SELECT SERVER EDITION',
                'LICENSE TERMS','INSTALLATION TYPE','WHERE TO INSTALL WINDOWS','INSTALLING WINDOWS',
                'RESTART & BOOT','ADMINISTRATOR PASSWORD','FIRST SIGN-IN','NETWORK CONFIGURATION',
                'SERVER NAME','UPDATES & ACTIVATION','INSTALLATION VERIFICATION'
            ];

            let content = `<div class="setup-wizard">
                <div class="wizard-header"><i class="fas ${headerIcons[index]}"></i> ${headers[index]}</div>
                <div class="wizard-body">`;

            switch(index) {
                case 0:
                    content += `
                        <p>Power on SERVER01 and boot from the Windows Server 2012 R2 DVD/ISO.</p>
                        <div class="wizard-option" onclick="completeStep(0,'Installation media detected. Boot sequence started.')">▶ Boot from DVD / ISO</div>
                        
                    `;
                    break;
                case 1:
                    content += `
                        <p>Confirm the basic setup options before continuing.</p>
                        <select id="languageSelect" style="margin-bottom:8px;">
                            <option value="English">English</option><option value="Filipino">Filipino</option>
                        </select>
                        <select id="timeSelect" style="margin-bottom:8px;">
                            <option value="English (Philippines)">Time and currency format: English (Philippines)</option>
                            <option value="English (United States)">Time and currency format: English (United States)</option>
                        </select>
                        <select id="keyboardSelect">
                            <option value="US">Keyboard: US</option>
                            <option value="UK">Keyboard: United Kingdom</option>
                        </select>
                        <button class="option-btn" style="margin-top:10px;background:#00ff9d;color:#000;" onclick="completeStep(1,'Language, time, and keyboard settings confirmed.')">Next</button>
                    `;
                    break;
                case 2:
                    content += `
                        <p>Windows Setup is ready to begin the operating-system installation.</p>
                        <div class="wizard-option" onclick="completeStep(2,'Windows Setup started.')"><i class="fas fa-play"></i> Install Now</div>
                    `;
                    break;
                case 3:
                    content += `
                        <p>Select the required Windows Server 2012 R2 edition.</p>
                        <div class="wizard-option" onclick="selectEdition('Windows Server 2012 R2 Standard (Server with a GUI)')">Windows Server 2012 R2 Standard (Server with a GUI)</div>
                        <div class="wizard-option" onclick="selectEdition('Windows Server 2012 R2 Datacenter (Server with a GUI)')">Windows Server 2012 R2 Datacenter (Server with a GUI)</div>
                        <div id="editionMsg" style="margin-top:10px;color:#ffaa00;font-size:11px;"></div>
                    `;
                    break;
                case 4:
                    content += `
                        <p>Review the Microsoft software license terms.</p>
                        <div class="wizard-option" onclick="completeStep(4,'License terms accepted.')">☑ I accept the license terms</div>
                    `;
                    break;
                case 5:
                    content += `
                        <p>Choose the installation type for a clean installation.</p>
                        <div class="wizard-option" onclick="completeStep(5,'Custom installation selected.')">Custom: Install Windows only (advanced)</div>
                        <div class="wizard-option" onclick="showMessage('Upgrade is not the clean-install workflow used by this simulator.','error')">Upgrade</div>
                    `;
                    break;
                case 6:
                    content += `
                        <p>Select the target disk. In a real deployment, confirm backups and disk requirements before formatting.</p>
                        <div class="wizard-option" onclick="selectDisk('Disk 0 - Unallocated Space')">Disk 0 - Unallocated Space</div>
                        <div class="wizard-option" onclick="selectDisk('Disk 0 - Existing Windows Partition')">Disk 0 - Existing Windows Partition</div>
                        <div id="diskMsg" style="margin-top:10px;color:#ffaa00;font-size:11px;"></div>
                    `;
                    break;
                case 7:
                    content += `
                        <p>Begin copying Windows files and installing the operating system.</p>
                        <div class="progress-bar"><div class="progress-fill" id="installProgress"></div></div>
                        <button class="option-btn" style="margin-top:12px;background:#00ff9d;color:#000;" onclick="runInstallation()">Start Installation</button>
                        <div id="installMsg" style="margin-top:10px;color:#ffaa00;font-size:11px;"></div>
                    `;
                    break;
                case 8:
                    content += `
                        <p>The installation has finished its initial file copy. Restart and boot from the installed system.</p>
                        <div class="wizard-option" onclick="completeStep(8,'Server restarted and booted from the installed Windows system.')">↻ Restart Now</div>
                    `;
                    break;
                case 9:
                    content += `
                        <p>Create the local Administrator password. Use a strong password that meets the server's password policy.</p>
                        <input type="password" id="adminPass" placeholder="Administrator password" style="margin-bottom:8px;">
                        <input type="password" id="adminPass2" placeholder="Confirm password">
                        <button class="option-btn" style="margin-top:10px;background:#00ff9d;color:#000;" onclick="setAdminPassword()">Set Administrator Password</button>
                        <div id="passwordMsg" style="margin-top:10px;color:#ffaa00;font-size:11px;"></div>
                    `;
                    break;
                case 10:
                    content += `
                        <p>Sign in to the new Windows Server installation.</p>
                        <div class="wizard-option" onclick="completeStep(10,'Administrator sign-in completed.')"><i class="fas fa-sign-in-alt"></i> Sign in as Administrator</div>
                    `;
                    break;
                case 11:
                    content += `
                        <p>Choose the initial network configuration method. A server may later be assigned a static IPv4 address according to the network plan.</p>
                        <div class="wizard-option" onclick="setNetworkMode('static')">Configure a static IPv4 address</div>
                        <div class="wizard-option" onclick="setNetworkMode('dhcp')">Use DHCP temporarily</div>
                        <div id="networkMsg" style="margin-top:10px;color:#ffaa00;font-size:11px;"></div>
                    `;
                    break;
                case 12:
                    content += `
                        <p>Set a clear computer name for the server.</p>
                        <input type="text" id="serverName" value="SERVER01" placeholder="Computer name">
                        <button class="option-btn" style="margin-top:10px;background:#00ff9d;color:#000;" onclick="setServerName()">Apply Computer Name</button>
                        <div id="serverNameMsg" style="margin-top:10px;color:#ffaa00;font-size:11px;"></div>
                    `;
                    break;
                case 13:
                    content += `
                        <p>Review update and activation status before placing the server into service.</p>
                        <div class="wizard-option" onclick="completeStep(13,'Update check completed and activation status reviewed.')">Check Windows Update &amp; Activation</div>
                        <p style="font-size:10px;color:#8899cc;margin-top:8px;">Internet access, licensing method, and update availability depend on the actual deployment environment.</p>
                    `;
                    break;
                case 14:
                    content += `
                        <p>Confirm the installation is ready for server configuration.</p>
                        <div class="wizard-option" onclick="finishVerification()"><i class="fas fa-server"></i> Open Server Manager &amp; Verify</div>
                        <div class="console-out" style="margin-top:10px;">SERVER01
Windows Server 2012 R2
Server Manager: Ready
Installation workflow: Complete</div>
                    `;
                    break;
            }

            content += `</div></div>`;
            screen.innerHTML = content;
        }

        function selectEdition(edition) {
            installData.edition = edition;
            const msg = document.getElementById('editionMsg');
            if (msg) msg.textContent = `Selected: ${edition}`;
            completeStep(3, 'Server edition selected.');
        }

        function selectDisk(disk) {
            installData.disk = disk;
            const msg = document.getElementById('diskMsg');
            if (msg) msg.textContent = `Target selected: ${disk}`;
            completeStep(6, 'Installation target selected.');
        }

        function runInstallation() {
            const bar = document.getElementById('installProgress');
            const msg = document.getElementById('installMsg');
            let value = 0;
            if (bar) bar.style.width = '0%';
            const timer = setInterval(() => {
                value += 10;
                if (bar) bar.style.width = value + '%';
                if (value >= 100) {
                    clearInterval(timer);
                    if (msg) msg.textContent = 'Windows files installed. Restart is required.';
                    completeStep(7, 'Windows installation completed.');
                }
            }, 120);
        }

        function setAdminPassword() {
            const p1 = document.getElementById('adminPass')?.value || '';
            const p2 = document.getElementById('adminPass2')?.value || '';
            const msg = document.getElementById('passwordMsg');
            if (p1.length < 8) {
                if (msg) msg.textContent = 'Use a stronger password of at least 8 characters.';
                return;
            }
            if (p1 !== p2) {
                if (msg) msg.textContent = 'The passwords do not match.';
                return;
            }
            installData.password = p1;
            completeStep(9, 'Administrator password created.');
        }

        function setNetworkMode(mode) {
            installData.ipMode = mode;
            const msg = document.getElementById('networkMsg');
            if (msg) msg.textContent = mode === 'static'
                ? 'Static IPv4 selected for server configuration.'
                : 'DHCP selected for temporary network configuration.';
            completeStep(11, 'Initial network configuration selected.');
        }

        function setServerName() {
            const name = (document.getElementById('serverName')?.value || '').trim();
            const msg = document.getElementById('serverNameMsg');
            if (!/^[A-Za-z0-9-]{1,15}$/.test(name)) {
                if (msg) msg.textContent = 'Use 1-15 letters, numbers, or hyphens for the computer name.';
                return;
            }
            installData.serverName = name.toUpperCase();
            document.getElementById('chassisLabel').innerHTML = installData.serverName + ' &bull; Windows Server';
            completeStep(12, `Server name set to ${installData.serverName}.`);
        }

        function finishVerification() {
            completeStep(14, 'Server Manager opened. Installation verified.');
            setTimeout(() => {
                showCompletion();
                createConfetti();
            }, 600);
        }

        function startInstallation() {
            showStep(0);
        }

        function runCommand(command) {
            const outputs = {
                diskpart: 'DISKPART> list disk\n\n  Disk 0    Online    60 GB',
                ipconfig: 'Windows IP Configuration\n\nEthernet adapter Ethernet:\n  IPv4 Address: 192.168.1.10',
                hostname: `${installData.serverName || 'SERVER01'}`,
                winver: 'Microsoft Windows Server 2012 R2'
            };
            const screen = document.getElementById('screenContent');
            if (!screen) return;
            const div = document.createElement('div');
            div.className = 'console-out';
            div.textContent = outputs[command] || 'Command completed.';
            screen.appendChild(div);
        }

        function showCompletion() {
            const screen = document.getElementById('screenContent');
            if (!screen) return;

            const serverName = installData.serverName || 'SERVER01';

            // The completed Windows Server screen is embedded directly in this
            // PHP file, so it appears inside the simulated PC monitor even
            // without an internet connection or Pinterest access.
            const completionImage = 'data:image/jpeg;base64,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';

            screen.classList.add('installation-complete');
            screen.innerHTML = `
                <div class="onscreen-shot-full" id="completedServerScreen"
                     aria-label="Windows Server 2012 R2 installation completed on ${serverName}">
                    <img src="${completionImage}"
                         alt="Windows Server 2012 R2 installation completed on ${serverName}"
                         draggable="false">
                </div>
            `;
        }

        function showFinalModal() {
            document.getElementById('completionMessage').innerHTML =
                `<p><strong>Score: ${totalScore} / 150</strong></p><p>All 15 installation steps are complete.</p>`;
            document.getElementById('completionModal').classList.add('active');
        }

        function saveAndClose() {
            if (gameSaved) return;
            fetch('save_client_server_score.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'score=' + encodeURIComponent(totalScore) + '&steps_completed=' + steps.filter(s => s.completed).length
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    gameSaved = true;
                    showMessage('Score saved to database!', 'success');
                    const best = document.getElementById('bestScore');
                    if (best) best.textContent = totalScore;
                }
            })
            .catch(error => console.error('Error saving score:', error));

            setTimeout(() => {
                document.getElementById('completionModal').classList.remove('active');
            }, 1500);
        }

        function createConfetti() {
            for (let i = 0; i < 70; i++) {
                const c = document.createElement('div');
                c.style.cssText = `position:fixed;left:${Math.random()*100}%;top:-10px;width:${Math.random()*8+5}px;height:${Math.random()*8+5}px;background:hsl(${Math.random()*360},100%,50%);border-radius:${Math.random()>0.5?'50%':'0'};z-index:2000;animation:fall ${Math.random()*2+2}s linear forwards;`;
                document.body.appendChild(c);
                setTimeout(() => c.remove(), 3000);
            }
        }

        function resetInstallation() {
            steps.forEach(s => s.completed = false);
            currentStep = 0;
            totalScore = 0;
            gameSaved = false;
            installData.edition = '';
            installData.disk = '';
            installData.password = '';
            installData.ipMode = '';
            installData.serverName = '';
            document.getElementById('chassisLabel').innerHTML = 'SERVER01 &bull; Windows Server';
            updateStepStatus();
            showStep(0);
        }

        function closeCompletionModal() {
            const modal = document.getElementById('completionModal');
            if (modal) modal.classList.remove('active');
        }

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

            if (themeToggle) {
                themeToggle.addEventListener('click', function () {
                    const nextTheme = document.body.classList.contains('light-mode') ? 'dark' : 'light';
                    localStorage.setItem('css_tesda_theme', nextTheme);
                    applyTheme(nextTheme);
                });
            }
        })();

        document.addEventListener('DOMContentLoaded', () => {
            renderStepsList();
            updateStepStatus();
            showStep(0);
        });
    </script>

    <?php if (file_exists(__DIR__ . '/includes/brightness_widget.php')) {
        $global_brightness = function_exists('getGlobalBrightness') ? getGlobalBrightness() : 100;
        include __DIR__ . '/includes/brightness_widget.php';
    } ?>
</body>
</html>
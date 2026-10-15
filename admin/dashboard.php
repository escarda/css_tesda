<?php
require_once 'config.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

$user_id = $_SESSION['user_id'];
$is_admin = isAdmin();

// Get global brightness value
$global_brightness = getGlobalBrightness();

// Get user stats
$stats = $conn->query("
    SELECT 
        COUNT(DISTINCT category_id) as categories_attempted,
        COUNT(*) as total_quizzes,
        COALESCE(AVG(score/total_questions*100), 0) as avg_score
    FROM quizzes 
    WHERE user_id = $user_id
")->fetch_assoc();

// Get recent ALL RESULTS (Quizzes + Games + Simulations)
$recent_results = $conn->query("
    (SELECT 
        'quiz' as result_type,
        q.id,
        q.user_id,
        q.category_id,
        q.score as result_score,
        q.total_questions as max_score,
        q.completed_at as result_date,
        c.name as category_name,
        CONCAT('Quiz: ', c.name) as activity_name,
        CONCAT(q.score, '/', q.total_questions) as score_display,
        ROUND((q.score / q.total_questions) * 100, 1) as percentage
    FROM quizzes q
    JOIN categories c ON q.category_id = c.id
    WHERE q.user_id = $user_id)
    
    UNION ALL
    
    (SELECT 
        'coc1_game' as result_type,
        gs.id,
        gs.user_id,
        gs.coc_number as category_id,
        gs.high_score as result_score,
        100 as max_score,
        gs.played_at as result_date,
        'COC 1 - PC Assembly' as category_name,
        'PC Assembly Simulator' as activity_name,
        CONCAT(gs.high_score, ' points') as score_display,
        LEAST(gs.high_score, 100) as percentage
    FROM user_game_scores gs
    WHERE gs.user_id = $user_id AND gs.coc_number = 1)
    
    UNION ALL
    
    (SELECT 
        'coc2_game' as result_type,
        g.id,
        g.user_id,
        g.coc_number as category_id,
        g.score as result_score,
        100 as max_score,
        g.played_at as result_date,
        'COC 2 - RJ45 Crimping' as category_name,
        'COC 2 Game' as activity_name,
        CONCAT(g.score, ' points') as score_display,
        LEAST(g.score, 100) as percentage
    FROM game_scores g
    WHERE g.user_id = $user_id AND g.coc_number = 2)
    
    UNION ALL
    
    (SELECT 
        'coc3_game' as result_type,
        g.id,
        g.user_id,
        g.coc_number as category_id,
        g.score as result_score,
        100 as max_score,
        g.played_at as result_date,
        'COC 3 - Server Admin' as category_name,
        'COC 3 Game' as activity_name,
        CONCAT(g.score, ' points') as score_display,
        LEAST(g.score, 100) as percentage
    FROM game_scores g
    WHERE g.user_id = $user_id AND g.coc_number = 3)
    
    UNION ALL
    
    (SELECT 
        'coc4_game' as result_type,
        gs.id,
        gs.user_id,
        gs.coc_number as category_id,
        gs.high_score as result_score,
        100 as max_score,
        gs.played_at as result_date,
        'COC 4 - Stress Test' as category_name,
        'COC 4 Game' as activity_name,
        CONCAT(gs.high_score, ' points') as score_display,
        LEAST(gs.high_score, 100) as percentage
    FROM user_game_scores gs
    WHERE gs.user_id = $user_id AND gs.coc_number = 4)
    
    UNION ALL
    
    (SELECT 
        'os_install_sim' as result_type,
        os.id,
        os.user_id,
        NULL as category_id,
        os.score as result_score,
        100 as max_score,
        os.completed_at as result_date,
        'OS Installation' as category_name,
        'OS Installation Simulator' as activity_name,
        CONCAT(os.score, ' points') as score_display,
        LEAST(os.score, 100) as percentage
    FROM os_installation_scores os
    WHERE os.user_id = $user_id)
    
    UNION ALL
    
    (SELECT 
        'troubleshooting_sim' as result_type,
        ts.id,
        ts.user_id,
        NULL as category_id,
        ts.score as result_score,
        100 as max_score,
        ts.played_at as result_date,
        'Troubleshooting' as category_name,
        'Troubleshooting Simulator' as activity_name,
        CONCAT(ts.score, ' points') as score_display,
        LEAST(ts.score, 100) as percentage
    FROM troubleshooting_scores ts
    WHERE ts.user_id = $user_id)
    
    UNION ALL
    
    (SELECT 
        'client_server_sim' as result_type,
        cs.id,
        cs.user_id,
        NULL as category_id,
        cs.score as result_score,
        150 as max_score,
        cs.completed_at as result_date,
        'Client-Server Network' as category_name,
        'Client-Server Simulator' as activity_name,
        CONCAT(cs.score, ' points') as score_display,
        ROUND((cs.score / 150) * 100, 1) as percentage
    FROM client_server_scores cs
    WHERE cs.user_id = $user_id)
    
    UNION ALL
    
    (SELECT 
        'windows_server_sim' as result_type,
        ws.id,
        ws.user_id,
        NULL as category_id,
        ws.score as result_score,
        150 as max_score,
        ws.completed_at as result_date,
        'Windows Server 2012 R2' as category_name,
        'Windows Server Install Simulator' as activity_name,
        CONCAT(ws.score, ' points') as score_display,
        ROUND((ws.score / 150) * 100, 1) as percentage
    FROM windows_server_install_scores ws
    WHERE ws.user_id = $user_id)
    
    ORDER BY result_date DESC
    LIMIT 10
");

// Get game scores for stats
$game_scores = $conn->query("
    SELECT * FROM game_scores 
    WHERE user_id = $user_id 
    ORDER BY played_at DESC 
    LIMIT 5
");

// Get categories
$categories = $conn->query("SELECT * FROM categories ORDER BY id");

// ============================================
// COC COMPLETION CHECKLIST
// ============================================
function getCOCProgress($conn, $user_id) {
    $coc_progress = [];
    
    for ($coc = 1; $coc <= 4; $coc++) {
        $quiz_query = $conn->query("
            SELECT COUNT(*) as count, AVG(score/total_questions*100) as avg_score
            FROM quizzes 
            WHERE user_id = $user_id AND category_id = $coc
        ");
        $quiz_data = $quiz_query->fetch_assoc();
        $quiz_completed = $quiz_data['count'] > 0;
        $quiz_score = $quiz_data['avg_score'] ?? 0;
        
        $game_query = $conn->query("
            SELECT COUNT(*) as count, MAX(score) as best_score
            FROM game_scores 
            WHERE user_id = $user_id AND coc_number = $coc
        ");
        $game_data = $game_query->fetch_assoc();
        $game_completed = $game_data['count'] > 0;
        $game_score = $game_data['best_score'] ?? 0;
        
        $install_completed = false;
        $install_score = 0;
        if ($coc == 1) {
            $install_query = $conn->query("
                SELECT COUNT(*) as count, MAX(high_score) as best_score
                FROM user_game_scores 
                WHERE user_id = $user_id AND coc_number = 1
            ");
            $install_data = $install_query->fetch_assoc();
            $install_completed = $install_data['count'] > 0;
            $install_score = $install_data['best_score'] ?? 0;
        }
        
        $troubleshoot_completed = false;
        $troubleshoot_score = 0;
        if ($coc == 4) {
            $troubleshoot_query = $conn->query("
                SELECT COUNT(*) as count, MAX(score) as best_score
                FROM troubleshooting_scores 
                WHERE user_id = $user_id
            ");
            $troubleshoot_data = $troubleshoot_query->fetch_assoc();
            $troubleshoot_completed = $troubleshoot_data['count'] > 0;
            $troubleshoot_score = $troubleshoot_data['best_score'] ?? 0;
        }
        
        $clientserver_completed = false;
        $clientserver_score = 0;
        if ($coc == 3) {
            $cs_query = $conn->query("
                SELECT COUNT(*) as count, MAX(score) as best_score
                FROM client_server_scores 
                WHERE user_id = $user_id
            ");
            $cs_data = $cs_query->fetch_assoc();
            $clientserver_completed = $cs_data['count'] > 0;
            $clientserver_score = $cs_data['best_score'] ?? 0;
        }
        
        $windowsserver_completed = false;
        $windowsserver_score = 0;
        if ($coc == 3) {
            $ws_query = $conn->query("
                SELECT COUNT(*) as count, MAX(score) as best_score
                FROM windows_server_install_scores 
                WHERE user_id = $user_id
            ");
            $ws_data = $ws_query->fetch_assoc();
            $windowsserver_completed = $ws_data['count'] > 0;
            $windowsserver_score = $ws_data['best_score'] ?? 0;
        }
        
        $is_completed = ($quiz_completed && $quiz_score >= 75);
        
        $coc_progress[$coc] = [
            'quiz_completed' => $quiz_completed,
            'quiz_score' => round($quiz_score, 1),
            'game_completed' => $game_completed,
            'game_score' => round($game_score, 1),
            'install_completed' => $install_completed,
            'install_score' => round($install_score, 1),
            'troubleshoot_completed' => $troubleshoot_completed,
            'troubleshoot_score' => round($troubleshoot_score, 1),
            'clientserver_completed' => $clientserver_completed,
            'clientserver_score' => round($clientserver_score, 1),
            'windowsserver_completed' => $windowsserver_completed,
            'windowsserver_score' => round($windowsserver_score, 1),
            'is_completed' => $is_completed
        ];
    }
    
    return $coc_progress;
}

$coc_progress = getCOCProgress($conn, $user_id);

$completed_cocs = 0;
foreach ($coc_progress as $coc => $data) {
    if ($data['is_completed']) {
        $completed_cocs++;
    }
}
$overall_progress = round(($completed_cocs / 4) * 100);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <?php if (file_exists(__DIR__ . '/includes/theme_preload.php')) { include __DIR__ . '/includes/theme_preload.php'; } ?>
    <!-- FIX 1: viewport-fit=cover lets the page draw under the camera cutout -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Dashboard - <?php echo SITE_NAME; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&family=Rajdhani:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
      
    <style>
        :root {
            --bg-primary: #0a0f1e;
            --bg-secondary: #050812;
            --bg-card: rgba(10, 20, 40, 0.7);
            --bg-card-hover: rgba(10, 20, 40, 0.9);
            --bg-sidebar: rgba(5, 8, 18, 0.95);
            --bg-table: rgba(10, 20, 40, 0.7);
            --bg-table-hover: rgba(0, 255, 157, 0.05);
            --bg-input: rgba(0, 0, 0, 0.5);
            --bg-stat: rgba(0, 0, 0, 0.3);
            --bg-welcome: rgba(10, 20, 40, 0.7);
            
            --text-primary: #e0e0ff;
            --text-secondary: #8899cc;
            --text-muted: #94a3b8;
            --text-white: #ffffff;
            --text-dark: #050812;
            
            --border-color: rgba(0, 255, 157, 0.3);
            --border-light: rgba(0, 255, 157, 0.1);
            --border-dark: rgba(0, 255, 157, 0.5);
            
            --shadow-color: rgba(0, 255, 157, 0.2);
            --shadow-glow: rgba(0, 255, 157, 0.4);
            
            --cyber-bg1: #0a0f1e;
            --cyber-bg2: #050812;
            --grid-color: rgba(0, 255, 157, 0.03);
            
            --card-bg: rgba(10, 20, 40, 0.7);
            --card-border: rgba(0, 255, 157, 0.3);
            
            --welcome-border: 2px solid #00ff9d;
            
            --scrollbar-track: #1a1a2e;
            --scrollbar-thumb: #00ff9d;
            
            --global-brightness: <?php echo $global_brightness; ?>%;
            --bright: <?php echo ((float)$global_brightness) / 100; ?>;
        }

        [data-theme="light"] {
            --bg-primary: #d5dce6;
            --bg-secondary: #c8d0dd;
            --bg-card: rgba(255, 255, 255, 0.92);
            --bg-card-hover: rgba(255, 255, 255, 0.98);
            --bg-sidebar: rgba(240, 244, 248, 0.95);
            --bg-table: rgba(255, 255, 255, 0.92);
            --bg-table-hover: rgba(0, 160, 100, 0.05);
            --bg-input: rgba(255, 255, 255, 0.95);
            --bg-stat: rgba(0, 0, 0, 0.04);
            --bg-welcome: rgba(255, 255, 255, 0.92);

            --text-primary: #1a2332;
            --text-secondary: #3d4a5e;
            --text-muted: #5a6b7e;
            --text-white: #1a2332;
            --text-dark: #1a2332;

            --border-color: rgba(0, 160, 100, 0.4);
            --border-light: rgba(0, 160, 100, 0.2);
            --border-dark: rgba(0, 160, 100, 0.5);

            --shadow-color: rgba(0, 160, 100, 0.15);
            --shadow-glow: rgba(0, 160, 100, 0.3);

            --cyber-bg1: #d5dce6;
            --cyber-bg2: #c8d0dd;
            --grid-color: rgba(0, 160, 100, 0.06);

            --card-bg: rgba(255, 255, 255, 0.92);
            --card-border: rgba(0, 160, 100, 0.35);

            --welcome-border: 2px solid #00aa77;

            --scrollbar-track: #c8d0dd;
            --scrollbar-thumb: #00aa77;
        }

        /* Apply brightness to page wrapper — NOT body */
        .page-wrapper {
            filter: brightness(var(--global-brightness));
            transition: filter 0.3s ease;
            min-height: 100vh;
            min-height: 100dvh; /* FIX 4 */
        }

        /* Keep brightness controls unaffected */
        .brightness-controls-wrapper,
        .brightness-control-btn,
        .brightness-panel,
        .brightness-indicator,
        .brightness-toggle-btn,
        .brightness-sidebar-control {
            filter: brightness(1) !important;
        }

        .brightness-sidebar-control {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 15px;
            background: rgba(255, 170, 0, 0.08);
            border: 1px solid rgba(255, 170, 0, 0.25);
            border-radius: 10px;
            margin: 10px 15px;
            cursor: pointer !important;
            transition: all 0.3s ease;
            user-select: none;
        }
        
        .brightness-sidebar-control:hover {
            background: rgba(255, 170, 0, 0.15);
            border-color: #ffaa00;
            transform: scale(1.02);
        }
        
        .brightness-sidebar-control:active { transform: scale(0.97); }
        
        .brightness-sidebar-control i {
            color: #ffaa00;
            font-size: 1.2rem;
            width: 24px;
            text-align: center;
        }
        
        .brightness-sidebar-control .brightness-text {
            color: var(--text-secondary);
            font-size: 0.9rem;
            flex: 1;
        }
        
        .brightness-sidebar-control .brightness-value-display {
            font-family: 'Orbitron', sans-serif;
            font-size: 0.8rem;
            color: #ffaa00;
            background: rgba(255, 170, 0, 0.1);
            padding: 2px 10px;
            border-radius: 20px;
        }
        
        .brightness-sidebar-control .brightness-arrow {
            color: var(--text-muted);
            font-size: 0.7rem;
            transition: transform 0.3s ease;
        }
        
        .brightness-sidebar-control.active .brightness-arrow {
            transform: rotate(180deg);
        }

        .brightness-panel-sidebar {
            display: none;
            padding: 15px;
            margin: 0 15px 10px 15px;
            background: rgba(0, 0, 0, 0.3);
            border: 1px solid var(--border-color);
            border-radius: 10px;
            animation: slideDown 0.3s ease;
        }
        
        .brightness-panel-sidebar.active { display: block; }
        
        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .brightness-panel-sidebar label {
            font-size: 0.75rem;
            color: var(--text-secondary);
            font-weight: 600;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 8px;
        }
        
        .brightness-panel-sidebar .brightness-slider {
            width: 100%;
            height: 6px;
            -webkit-appearance: none;
            appearance: none;
            background: linear-gradient(90deg, #3d4a5e, #ffaa00);
            border-radius: 3px;
            outline: none;
            cursor: pointer !important;
            margin: 8px 0;
        }
        
        .brightness-panel-sidebar .brightness-slider::-webkit-slider-thumb {
            -webkit-appearance: none;
            appearance: none;
            width: 18px;
            height: 18px;
            border-radius: 50%;
            background: #ffaa00;
            cursor: pointer !important;
            border: 2px solid var(--bg-card);
            box-shadow: 0 2px 10px rgba(255, 170, 0, 0.4);
        }
        
        .brightness-panel-sidebar .brightness-presets {
            display: flex;
            gap: 6px;
            justify-content: center;
            flex-wrap: wrap;
            margin-top: 10px;
        }
        
        .brightness-panel-sidebar .brightness-preset {
            padding: 3px 12px;
            border-radius: 20px;
            background: var(--bg-stat);
            border: 1px solid var(--border-light);
            color: var(--text-secondary);
            font-size: 0.65rem;
            cursor: pointer !important;
            transition: all 0.2s;
            font-family: 'Rajdhani', sans-serif;
        }
        
        .brightness-panel-sidebar .brightness-preset:hover,
        .brightness-panel-sidebar .brightness-preset.active {
            border-color: #ffaa00;
            color: #ffaa00;
            background: rgba(255, 170, 0, 0.1);
        }
        
        .brightness-panel-sidebar .reset-btn {
            padding: 4px 12px;
            border-radius: 20px;
            background: rgba(255, 68, 68, 0.1);
            border: 1px solid rgba(255, 68, 68, 0.3);
            color: #ff6666;
            cursor: pointer !important;
            font-size: 0.65rem;
            transition: all 0.2s;
            font-family: 'Rajdhani', sans-serif;
            margin-top: 8px;
            width: 100%;
        }
        
        .brightness-panel-sidebar .reset-btn:hover {
            background: rgba(255, 68, 68, 0.2);
        }

        /* ============================================
           THEME TOGGLE BUTTON
           ============================================ */
        .theme-toggle-sidebar {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 15px;
            background: rgba(0, 102, 255, 0.08);
            border: 1px solid rgba(0, 102, 255, 0.25);
            border-radius: 10px;
            margin: 10px 15px;
            cursor: pointer;
            transition: all 0.3s ease;
            user-select: none;
        }

        .theme-toggle-sidebar:hover {
            background: rgba(0, 102, 255, 0.15);
            border-color: #0066ff;
            transform: scale(1.02);
        }

        .theme-toggle-sidebar:active { transform: scale(0.97); }

        .theme-toggle-sidebar i {
            color: #0066ff;
            font-size: 1.2rem;
            width: 24px;
            text-align: center;
        }

        .theme-toggle-sidebar .theme-text {
            color: var(--text-secondary);
            font-size: 0.9rem;
            flex: 1;
        }

        .theme-toggle-sidebar .theme-value-display {
            font-family: 'Orbitron', sans-serif;
            font-size: 0.8rem;
            color: #0066ff;
            background: rgba(0, 102, 255, 0.1);
            padding: 2px 10px;
            border-radius: 20px;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        /* FIX 1b: html background fills the camera-cutout area (follows dark/light theme) */
        html {
            -webkit-text-size-adjust: 100%;
            background: var(--bg-primary);
        }

        body {
            font-family: 'Rajdhani', sans-serif;
            background: var(--bg-primary);
            color: var(--text-primary);
            min-height: 100vh;
            min-height: 100dvh; /* FIX 4 */
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
            transition: background 0.3s ease;
        }

        .grid-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: 
                linear-gradient(var(--grid-color) 1px, transparent 1px),
                linear-gradient(90deg, var(--grid-color) 1px, transparent 1px);
            background-size: 50px 50px;
            z-index: -1;
            animation: gridMove 20s linear infinite;
        }

        @keyframes gridMove {
            0% { transform: translate(0, 0); }
            100% { transform: translate(50px, 50px); }
        }

        .dashboard {
            display: grid;
            grid-template-columns: 280px 1fr;
            min-height: 100vh;
            min-height: 100dvh; /* FIX 4 */
        }

        .sidebar {
            background: var(--bg-sidebar);
            backdrop-filter: blur(10px);
            border-right: 2px solid #00ff9d;
            padding: 30px 0;
            position: relative;
            box-shadow: 10px 0 30px var(--shadow-color);
            transition: background 0.3s ease, box-shadow 0.3s ease;
            overflow-y: auto;
        }

        .sidebar::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 2px;
            height: 100%;
            background: linear-gradient(to bottom, transparent, #00ff9d, transparent);
        }

        .sidebar-header {
            text-align: center;
            padding: 0 20px 30px;
            border-bottom: 1px solid var(--border-color);
        }

        .sidebar-header i {
            font-size: 3rem;
            color: #00ff9d;
            margin-bottom: 15px;
            animation: pulse 2s infinite;
        }

        .sidebar-header h2 {
            font-family: 'Orbitron', sans-serif;
            color: #00ff9d;
            font-size: 1.3rem;
            margin-bottom: 5px;
        }

        .welcome {
            color: var(--text-secondary);
            font-size: 0.9rem;
        }

        .admin-badge {
            color: #00ff9d;
            font-size: 0.8rem;
            margin-top: 5px;
        }

        .sidebar-nav { padding: 10px 20px 20px; }
        .sidebar-nav ul { list-style: none; }
        .sidebar-nav li { margin-bottom: 5px; }

        .sidebar-nav a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 15px;
            color: var(--text-secondary);
            text-decoration: none;
            border-radius: 8px;
            transition: all 0.3s;
            position: relative;
            overflow: hidden;
        }

        .sidebar-nav a::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            height: 100%;
            width: 3px;
            background: #00ff9d;
            transform: scaleY(0);
            transition: transform 0.3s;
        }

        .sidebar-nav a:hover,
        .sidebar-nav a.active {
            background: rgba(0, 255, 157, 0.1);
            color: #00ff9d;
        }

        .sidebar-nav a:hover::before,
        .sidebar-nav a.active::before {
            transform: scaleY(1);
        }

        .sidebar-nav i { width: 20px; text-align: center; }

        .sidebar-footer {
            position: absolute;
            bottom: 0;
            width: 100%;
            padding: 20px;
            border-top: 1px solid var(--border-color);
        }

        .user-stats {
            display: flex;
            justify-content: space-around;
            text-align: center;
        }

        .stat-item { display: flex; flex-direction: column; }
        .stat-label { font-size: 0.8rem; color: var(--text-secondary); }
        .stat-value { font-size: 1.2rem; color: #00ff9d; font-weight: bold; }

        .main-content {
            padding: 40px;
            overflow-y: auto;
            max-height: 100vh;
            max-height: 100dvh; /* FIX 4 */
            background: var(--bg-primary);
            transition: background 0.3s ease;
        }

        .main-content h1 {
            font-family: 'Orbitron', sans-serif;
            font-size: 2.5rem;
            margin-bottom: 30px;
            background: linear-gradient(135deg, #00ff9d, #0066ff);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .welcome-section {
            background: var(--bg-welcome);
            backdrop-filter: blur(10px);
            border: var(--welcome-border);
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 40px;
            box-shadow: 0 0 30px var(--shadow-color);
            transition: background 0.3s ease, border 0.3s ease, box-shadow 0.3s ease;
        }

        .welcome-section h2 {
            font-size: 1.8rem;
            margin-bottom: 10px;
            color: #00ff9d;
        }

        .welcome-section p {
            color: var(--text-secondary);
            font-size: 1.1rem;
        }

        .dashboard-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 25px;
            margin-bottom: 40px;
        }

        .card {
            background: var(--card-bg);
            backdrop-filter: blur(10px);
            border: 1px solid var(--card-border);
            border-radius: 20px;
            padding: 25px;
            transition: all 0.3s;
            position: relative;
            overflow: hidden;
        }

        .card::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(0, 255, 157, 0.1) 0%, transparent 70%);
            opacity: 0;
            transition: opacity 0.3s;
        }

        .card:hover {
            transform: translateY(-5px);
            border-color: #00ff9d;
            box-shadow: 0 10px 30px var(--shadow-color);
        }

        .card:hover::before { opacity: 1; }

        .card-icon {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #00ff9d, #0066ff);
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 20px;
        }

        .card-icon i { font-size: 2rem; color: #050812; }

        .card h3 {
            font-family: 'Orbitron', sans-serif;
            font-size: 1.3rem;
            margin-bottom: 10px;
            color: #00ff9d;
        }

        .card p {
            color: var(--text-secondary);
            margin-bottom: 20px;
        }

        .btn-small {
            display: inline-block;
            padding: 10px 20px;
            background: transparent;
            border: 1px solid #00ff9d;
            color: #00ff9d;
            border-radius: 8px;
            text-decoration: none;
            transition: all 0.3s;
            font-size: 0.9rem;
        }

        .btn-small:hover {
            background: #00ff9d;
            color: #050812;
        }

        .card-stats {
            display: flex;
            justify-content: space-between;
            color: #00ff9d;
            font-weight: 600;
            margin-top: 15px;
        }

        .checklist-section { margin: 40px 0; }

        .checklist-section h2 {
            font-family: 'Orbitron', sans-serif;
            color: #00ff9d;
            margin-bottom: 10px;
            font-size: 1.8rem;
        }

        .checklist-section .subtitle {
            color: var(--text-secondary);
            margin-bottom: 30px;
            font-size: 1.1rem;
        }

        .overall-progress {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 16px;
            padding: 25px;
            margin-bottom: 30px;
        }

        .overall-progress .progress-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .overall-progress .progress-header h3 {
            font-family: 'Orbitron', sans-serif;
            font-size: 1.1rem;
            color: var(--text-primary);
        }

        .overall-progress .progress-header span {
            font-size: 1.5rem;
            font-weight: bold;
            color: #00ff9d;
        }

        .progress-bar-container {
            width: 100%;
            height: 12px;
            background: var(--bg-stat);
            border-radius: 10px;
            overflow: hidden;
        }

        .progress-bar-fill {
            height: 100%;
            background: linear-gradient(90deg, #00ff9d, #0066ff);
            border-radius: 10px;
            transition: width 0.8s ease;
        }

        .progress-stats {
            display: flex;
            gap: 20px;
            margin-top: 10px;
            flex-wrap: wrap;
        }

        .progress-stats span { color: var(--text-secondary); font-size: 0.9rem; }
        .progress-stats .completed { color: #00ff9d; }

        .checklist-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
        }

        .checklist-item {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 16px;
            padding: 20px;
            transition: all 0.3s;
            position: relative;
        }

        .checklist-item:hover {
            border-color: #00ff9d;
            transform: translateY(-3px);
            box-shadow: 0 5px 20px var(--shadow-color);
        }

        .checklist-item.completed {
            border-color: #00ff9d;
            background: rgba(0, 255, 157, 0.05);
        }

        .checklist-item.needs-retake {
            border-color: #ffaa00;
            background: rgba(255, 170, 0, 0.05);
        }

        .checklist-item .item-header {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 15px;
        }

        .checklist-item .item-icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            flex-shrink: 0;
        }

        .checklist-item .item-icon.coc1 { background: rgba(255, 68, 68, 0.2); color: #ff6666; }
        .checklist-item .item-icon.coc2 { background: rgba(255, 170, 0, 0.2); color: #ffaa00; }
        .checklist-item .item-icon.coc3 { background: rgba(59, 130, 246, 0.2); color: #60a5fa; }
        .checklist-item .item-icon.coc4 { background: rgba(236, 72, 153, 0.2); color: #ec4899; }

        .checklist-item .item-title { flex: 1; }

        .checklist-item .item-title h4 {
            font-family: 'Orbitron', sans-serif;
            font-size: 1rem;
            color: var(--text-primary);
        }

        .checklist-item .item-title .coc-label {
            font-size: 0.75rem;
            color: var(--text-secondary);
        }

        .checklist-item .status-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.7rem;
            font-weight: bold;
            white-space: nowrap;
        }

        .checklist-item .status-badge.completed {
            background: rgba(0, 255, 157, 0.2);
            color: #00ff9d;
        }

        .checklist-item .status-badge.pending {
            background: rgba(255, 170, 0, 0.2);
            color: #ffaa00;
        }

        .checklist-item .status-badge.not-started {
            background: rgba(136, 153, 204, 0.2);
            color: var(--text-secondary);
        }

        .checklist-item .item-details {
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid var(--border-light);
        }

        .checklist-item .item-details .detail-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 8px;
            padding: 4px 0;
            font-size: 0.85rem;
            color: var(--text-secondary);
        }

        .checklist-item .item-details .detail-row .label {
            color: var(--text-muted);
            display: flex;
            align-items: center;
            gap: 6px;
            flex-wrap: wrap;
        }

        .checklist-item .item-details .detail-row .value { color: var(--text-primary); text-align: right; }
        .checklist-item .item-details .detail-row .value.pass { color: #00ff9d; }
        .checklist-item .item-details .detail-row .value.fail { color: #ff4444; }

        .tag-required {
            font-size: 0.6rem;
            padding: 1px 7px;
            border-radius: 10px;
            background: rgba(0, 255, 157, 0.15);
            color: #00ff9d;
            font-weight: 700;
            letter-spacing: 0.5px;
        }

        .tag-optional {
            font-size: 0.6rem;
            padding: 1px 7px;
            border-radius: 10px;
            background: rgba(136, 153, 204, 0.15);
            color: var(--text-muted);
            font-weight: 700;
            letter-spacing: 0.5px;
        }

        .next-step {
            margin-top: 15px;
            padding: 10px 12px;
            border-radius: 10px;
            background: rgba(0, 102, 255, 0.08);
            border: 1px solid rgba(0, 102, 255, 0.25);
            font-size: 0.8rem;
            color: var(--text-secondary);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .next-step.next-step-retake {
            background: rgba(255, 170, 0, 0.08);
            border-color: rgba(255, 170, 0, 0.3);
        }

        .next-step.next-step-done {
            background: rgba(0, 255, 157, 0.08);
            border-color: rgba(0, 255, 157, 0.3);
        }

        .checklist-item .item-actions {
            margin-top: 15px;
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .checklist-item .item-actions .btn-check {
            padding: 6px 14px;
            border-radius: 8px;
            border: 1px solid var(--border-color);
            background: transparent;
            color: var(--text-secondary);
            text-decoration: none;
            font-size: 0.75rem;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .checklist-item .item-actions .btn-check:hover {
            border-color: #00ff9d;
            color: #00ff9d;
            background: rgba(0, 255, 157, 0.05);
        }

        .checklist-item .item-actions .btn-check.btn-check-primary {
            border-color: #00ff9d;
            background: rgba(0, 255, 157, 0.15);
            color: #00ff9d;
            font-weight: 700;
        }

        .checklist-item .item-actions .btn-check.btn-check-primary:hover {
            background: #00ff9d;
            color: #050812;
        }

        .checklist-item .check-mark {
            position: absolute;
            top: 15px;
            right: 15px;
            font-size: 1.5rem;
            color: #00ff9d;
        }

        .coc-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 30px;
            margin: 30px 0;
        }

        .coc-card {
            background: var(--card-bg);
            backdrop-filter: blur(10px);
            border: 2px solid var(--card-border);
            border-radius: 20px;
            overflow: hidden;
            transition: all 0.3s;
        }

        .coc-card:hover {
            transform: translateY(-10px) scale(1.02);
            border-color: #00ff9d;
            box-shadow: 0 20px 40px var(--shadow-color);
        }

        .coc-header {
            background: linear-gradient(135deg, rgba(0,255,157,0.1), rgba(0,102,255,0.1));
            padding: 20px;
            display: flex;
            align-items: center;
            gap: 15px;
            border-bottom: 1px solid var(--border-color);
        }

        .coc-header i { font-size: 2rem; color: #00ff9d; }

        .coc-header h3 {
            font-family: 'Orbitron', sans-serif;
            font-size: 1.2rem;
            color: #00ff9d;
            flex: 1;
        }

        .coc-body { padding: 20px; }

        .coc-body p {
            color: var(--text-secondary);
            margin-bottom: 20px;
            line-height: 1.6;
        }

        .coc-actions {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
            margin-top: 20px;
        }

        .btn-coc {
            padding: 12px;
            background: transparent;
            border: 1px solid #00ff9d;
            color: #00ff9d;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            text-decoration: none;
            font-size: 0.9rem;
        }

        .btn-coc:hover {
            background: #00ff9d;
            color: #050812;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 255, 157, 0.3);
        }

        .recent-results { margin-top: 40px; }

        .recent-results h2 {
            font-family: 'Orbitron', sans-serif;
            color: #00ff9d;
            margin-bottom: 20px;
        }

        .result-badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 0.65rem;
            font-weight: bold;
            margin-left: 8px;
        }
        .result-quiz { background: rgba(0, 255, 157, 0.2); color: #00ff9d; }
        .result-coc1_game { background: rgba(255, 68, 68, 0.2); color: #ff6666; }
        .result-coc2_game { background: rgba(255, 170, 0, 0.2); color: #ffaa00; }
        .result-coc3_game { background: rgba(59, 130, 246, 0.2); color: #60a5fa; }
        .result-coc4_game { background: rgba(236, 72, 153, 0.2); color: #ec4899; }
        .result-installation_sim { background: rgba(34, 197, 94, 0.2); color: #22c55e; }
        .result-os_install_sim { background: rgba(0, 255, 157, 0.2); color: #00ff9d; }
        .result-troubleshooting_sim { background: rgba(168, 85, 247, 0.2); color: #a855f7; }
        .result-client_server_sim { background: rgba(0, 102, 255, 0.2); color: #60a5fa; }
        .result-windows_server_sim { background: rgba(147, 51, 234, 0.2); color: #a78bfa; }

        .data-table {
            width: 100%;
            background: var(--bg-table);
            border: 1px solid var(--border-color);
            border-radius: 10px;
            overflow: hidden;
            transition: background 0.3s ease, border 0.3s ease;
        }

        .data-table th {
            background: linear-gradient(135deg, #00ff9d, #0066ff);
            color: #050812;
            font-family: 'Orbitron', sans-serif;
            padding: 15px;
            text-align: left;
        }

        .data-table td {
            padding: 12px 15px;
            border-bottom: 1px solid var(--border-light);
            color: var(--text-secondary);
        }

        .data-table tr:hover { background: var(--bg-table-hover); }

        .no-data {
            text-align: center;
            color: var(--text-secondary);
            padding: 40px;
            background: var(--bg-card);
            border-radius: 10px;
            border: 1px dashed var(--border-color);
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }

        ::-webkit-scrollbar { width: 10px; }
        ::-webkit-scrollbar-track {
            background: var(--scrollbar-track);
            border-radius: 10px;
        }
        ::-webkit-scrollbar-thumb {
            background: var(--scrollbar-thumb);
            border-radius: 10px;
        }

        /* ============================================
           MOBILE MENU (hamburger) — hidden on desktop
           ============================================ */
        .menu-btn {
            display: none;
            position: fixed;
            top: 12px;
            left: 12px;
            z-index: 1100;
            width: 44px;
            height: 44px;
            align-items: center;
            justify-content: center;
            border-radius: 12px;
            border: 1px solid #00ff9d;
            background: var(--bg-sidebar);
            color: #00ff9d;
            font-size: 1.2rem;
            cursor: pointer;
        }

        .menu-btn.open {
            left: auto;
            right: 12px;
        }

        .sidebar-backdrop {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.6);
            z-index: 1000;
        }

        .sidebar-backdrop.show { display: block; }

        /* ============================================
           RESPONSIVE (hamburger menu ONLY on portrait phones)
           Landscape phones keep the normal sidebar (see compact rules below)
           ============================================ */
        @media (max-width: 768px) {
            html, body { overflow-x: hidden; }

            /* A CSS filter on an ancestor breaks position:fixed children,
               so on mobile the brightness is applied to the parts directly. */
            .page-wrapper { filter: none; }
            .main-content,
            .sidebar,
            .cyber-bg,
            .grid-overlay {
                filter: brightness(var(--global-brightness));
            }

            .dashboard { grid-template-columns: 1fr; }
            .menu-btn { display: flex; }

            .sidebar {
                position: fixed;
                top: 0;
                left: 0;
                bottom: 0;
                width: 280px;
                max-width: 85vw;
                z-index: 1050;
                transform: translateX(-100%);
                transition: transform 0.3s ease;
                padding-bottom: 20px;
            }
            .sidebar.open { transform: translateX(0); }
            .sidebar-footer {
                position: static;
                margin-top: 10px;
            }

            .main-content {
                padding: 70px 14px 24px;
                max-height: none;
                overflow-y: visible;
            }
            .main-content h1 { font-size: 1.7rem; margin-bottom: 18px; }
            .welcome-section { padding: 20px; margin-bottom: 24px; }
            .welcome-section h2 { font-size: 1.4rem; }
            .welcome-section p { font-size: 1rem; }

            .dashboard-cards,
            .coc-grid,
            .checklist-grid { grid-template-columns: 1fr; gap: 16px; }
            .coc-actions { grid-template-columns: 1fr; }
            .coc-card:hover { transform: none; }
            .card { padding: 20px; }
            .checklist-section h2 { font-size: 1.3rem; }
            .checklist-section .subtitle { font-size: 0.95rem; margin-bottom: 20px; }
            .overall-progress { padding: 18px; }
            .overall-progress .progress-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 5px;
            }
            .overall-progress .progress-header span { font-size: 1.15rem; }
            .checklist-item .item-header { flex-wrap: wrap; }

            .data-table {
                display: block;
                overflow-x: auto;
                white-space: nowrap;
            }
            .data-table th, .data-table td { padding: 10px 12px; }
        }

        /* ============================================
           Portrait phones (hamburger layout): stay clear of camera cutout
           (must stay AFTER the block above)
           ============================================ */
        @media (max-width: 768px) {
            .main-content {
                padding-left: calc(14px + env(safe-area-inset-left, 0px));
                padding-right: calc(14px + env(safe-area-inset-right, 0px));
            }
            .menu-btn { left: calc(12px + env(safe-area-inset-left, 0px)); }
            .menu-btn.open { left: auto; right: calc(12px + env(safe-area-inset-right, 0px)); }
            .sidebar { padding-left: env(safe-area-inset-left, 0px); }
        }

        @media (min-width: 600px) and (max-width: 768px) {
            .dashboard-cards,
            .checklist-grid,
            .coc-grid { grid-template-columns: repeat(2, 1fr); }
        }

        /* ============================================
           Landscape phones / small tablets: KEEP the sidebar visible,
           just make it compact so the dashboard has room
           ============================================ */
        @media (min-width: 769px) and (max-width: 1100px),
               (min-width: 769px) and (max-height: 500px) {
            .dashboard {
                grid-template-columns: calc(220px + env(safe-area-inset-left, 0px)) 1fr;
            }
            .sidebar {
                padding: 12px 0;
                padding-left: env(safe-area-inset-left, 0px);
            }
            .sidebar-header { padding: 0 12px 12px; }
            .sidebar-header i { font-size: 2rem; margin-bottom: 6px; }
            .sidebar-header h2 { font-size: 1.05rem; }
            .welcome { font-size: 0.8rem; }
            .sidebar-footer { position: static; margin-top: 10px; padding: 14px; }
            .theme-toggle-sidebar,
            .brightness-sidebar-control { margin: 8px 10px; padding: 9px 10px; gap: 8px; }
            .brightness-panel-sidebar { margin: 0 10px 8px; padding: 10px; }
            .sidebar-nav { padding: 6px 10px 10px; }
            .sidebar-nav a { padding: 9px 10px; font-size: 0.9rem; gap: 8px; }

            .main-content {
                padding: 20px 16px;
                padding-right: calc(16px + env(safe-area-inset-right, 0px));
            }
            .main-content h1 { font-size: 1.8rem; margin-bottom: 16px; }
            .welcome-section { padding: 20px; margin-bottom: 24px; }
            .dashboard-cards,
            .checklist-grid,
            .coc-grid {
                grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
                gap: 16px;
            }
            .coc-card:hover { transform: none; }
        }

        /* ============================================
           APP-SHELL LAYOUT (Android WebView / phones)
           Sidebar and dashboard are two screen-sized scroll areas.
           No filter on tall elements, no backdrop-filter, no animated
           grid: these made the WebView leave the dashboard area blank.
           (must stay LAST in the stylesheet)
           ============================================ */
        @media (max-width: 768px),
               (min-width: 769px) and (max-width: 1100px),
               (min-width: 769px) and (max-height: 500px) {
            /* NO vh / dvh anywhere: the Android WebView reports a tiny
               viewport height, which collapsed the dashboard. The page now
               just grows with its content and scrolls normally. */
            html, body { height: auto; min-height: 0; overflow-x: hidden; }
            body { position: relative; }

            .page-wrapper {
                filter: none;
                height: auto;
                min-height: 0;
                overflow: visible;
                position: relative;
            }
            .dashboard { height: auto; min-height: 0; overflow: visible; }
            .main-content {
                height: auto;
                max-height: none;
                overflow: visible;
                filter: none;
            }

            /* backgrounds follow the page instead of the (wrong) viewport */
            .cyber-bg,
            .grid-overlay {
                position: absolute;
                filter: none;
            }
            .grid-overlay { animation: none; }

            .sidebar,
            .card,
            .welcome-section,
            .coc-card {
                backdrop-filter: none;
                -webkit-backdrop-filter: none;
            }

            /* Brightness without filter: dark overlay (dim) / light overlay (bright) */
            body::after,
            body::before {
                content: '';
                position: absolute;
                top: 0; left: 0; right: 0; bottom: 0;
                pointer-events: none;
                z-index: 2000;
            }
            body::after {
                background: #000;
                opacity: calc(1 - var(--bright, 1));
            }
            body::before {
                background: #fff;
                mix-blend-mode: soft-light;
                opacity: calc((var(--bright, 1) - 1) * 1.2);
            }
        }

        /* Landscape phones: sidebar stays fixed on the left and has its OWN scroll,
           the dashboard on the right scrolls separately */
        @media (min-width: 769px) and (max-width: 1100px),
               (min-width: 769px) and (max-height: 500px) {
            .dashboard { display: block; }

            .sidebar {
                position: fixed;
                top: 0;
                bottom: 0;
                left: 0;
                width: calc(220px + env(safe-area-inset-left, 0px));
                height: auto;
                overflow-y: auto;
                overflow-x: hidden;
                overscroll-behavior: contain;
                -webkit-overflow-scrolling: touch;
                filter: none;
                z-index: 50;
            }

            .main-content {
                margin-left: calc(220px + env(safe-area-inset-left, 0px));
            }

            /* Safety net: if the WebView reports a broken height, the JS adds
               .sidebar-flow and the sidebar goes back to normal flow */
            .dashboard.sidebar-flow { display: grid; }
            .dashboard.sidebar-flow .sidebar {
                position: relative;
                width: auto;
                overflow: visible;
            }
            .dashboard.sidebar-flow .main-content { margin-left: 0; }
        }

        /* Portrait phones: drawer sidebar keeps its own scrolling */
        @media (max-width: 768px) {
            .sidebar {
                overflow-y: auto;
                filter: none;
            }
        }
    </style>
</head>
<body>
    <!-- Mobile menu button + backdrop (kept OUTSIDE .page-wrapper on purpose) -->
    <button type="button" class="menu-btn" id="menuBtn" aria-label="Open menu"><i class="fas fa-bars" id="menuIcon"></i></button>
    <div class="sidebar-backdrop" id="sidebarBackdrop"></div>

    <div class="page-wrapper">
        <div class="cyber-bg"></div>
        <div class="grid-overlay"></div>

        <div class="dashboard">
            <!-- Sidebar -->
            <div class="sidebar" id="sidebar">
                <div class="sidebar-header">
                    <i class="fas fa-microchip"></i>
                    <h2>CSS REVIEWER</h2>
                    <p class="welcome">Welcome, <?php echo htmlspecialchars($_SESSION['full_name']); ?>!</p>
                    <?php if ($is_admin): ?>
                        <p class="admin-badge"><i class="fas fa-crown"></i> Administrator</p>
                    <?php endif; ?>
                </div>
                
                <!-- Theme Toggle Button -->
                <div class="theme-toggle-sidebar" id="themeToggleSidebar">
                    <i class="fas fa-moon" id="themeIcon"></i>
                    <span class="theme-text">Theme</span>
                    <span class="theme-value-display" id="themeBadge">Dark</span>
                </div>
                
                <div class="brightness-sidebar-control" id="brightnessToggleSidebar">
                    <i class="fas fa-sun"></i>
                    <span class="brightness-text">Brightness</span>
                    <span class="brightness-value-display" id="brightnessBadge"><?php echo $global_brightness; ?>%</span>
                    <span class="brightness-arrow"><i class="fas fa-chevron-down"></i></span>
                </div>
                
                <div class="brightness-panel-sidebar" id="brightnessPanelSidebar">
                    <label>
                        <span>Adjust Brightness</span>
                        <span id="brightnessValueSidebar"><?php echo $global_brightness; ?>%</span>
                    </label>
                    <input type="range" class="brightness-slider" id="brightnessSliderSidebar" min="50" max="150" value="<?php echo $global_brightness; ?>">
                    
                    <div class="brightness-presets">
                        <button class="brightness-preset" data-value="60">Dim</button>
                        <button class="brightness-preset <?php echo $global_brightness == 100 ? 'active' : ''; ?>" data-value="100">Normal</button>
                        <button class="brightness-preset" data-value="130">Bright</button>
                        <button class="brightness-preset" data-value="150">Extra</button>
                    </div>
                    
                    <button class="reset-btn" id="resetBrightnessSidebar"><i class="fas fa-undo"></i> Reset to 100%</button>
                </div>
                
                <nav class="sidebar-nav">
                    <ul>
                        <li><a href="dashboard.php" class="active"><i class="fas fa-home"></i> Dashboard</a></li>
                        
                        <?php if ($is_admin): ?>
                        <li><a href="admin/manage_questions.php"><i class="fas fa-question-circle"></i> Manage Questions</a></li>
                        
                        <li style="border-top: 1px solid var(--border-color); margin: 10px 0; padding-top: 10px;"></li>
                        <?php endif; ?>
                        
                        <li><a href="coc1_review.php"><i class="fas fa-book-open"></i> COC 1 Review</a></li>
                        <li><a href="quiz.php?category=1"><i class="fas fa-pencil-alt"></i> COC 1 Quiz</a></li>
                        <li><a href="game.php?coc=1"><i class="fas fa-gamepad"></i> COC 1 simulation</a></li>
                        <li><a href="pc_assembly_game.php" style="color: #ffaa00;"><i class="fas fa-microchip"></i> Installation Simulation</a></li>
                        
                        <li><a href="coc2_review.php"><i class="fas fa-book-open"></i> COC 2 Review</a></li>
                        <li><a href="quiz.php?category=2"><i class="fas fa-pencil-alt"></i> COC 2 Quiz</a></li>
                        <li><a href="game.php?coc=2"><i class="fas fa-gamepad"></i> COC 2 simulation</a></li>
                        
                        <li><a href="coc3_review.php"><i class="fas fa-book-open"></i> COC 3 Review</a></li>
                        <li><a href="quiz.php?category=3"><i class="fas fa-pencil-alt"></i> COC 3 Quiz</a></li>
                        <li><a href="game.php?coc=3"><i class="fas fa-gamepad"></i> COC 3 simulation</a></li>
                        <li><a href="client_server_complete.php" style="color: #0342a2;"><i class="fas fa-network-wired"></i> Client-Server Simulator</a></li>
                        <li><a href="windows_server_install.php" style="color: #280397;"><i class="fas fa-server"></i> Windows Server Install Simulator</a></li>
                        
                        <li><a href="coc4_review.php"><i class="fas fa-book-open"></i> COC 4 Review</a></li>
                        <li><a href="quiz.php?category=4"><i class="fas fa-pencil-alt"></i> COC 4 Quiz</a></li>
                        <li><a href="game.php?coc=4"><i class="fas fa-gamepad"></i> COC 4 simulation</a></li>
                        <li><a href="troubleshooting_simulator.php" style="color: #ff4444;"><i class="fas fa-tools"></i> Troubleshooting Simulator</a></li>
                        <li><a href="corrective_maintenance.php" style="color: #ff3366;"><i class="fas fa-wrench"></i> Corrective Maintenance</a></li>
                        
                        <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                    </ul>
                </nav>
                
                <div class="sidebar-footer">
                    <div class="user-stats">
                        <div class="stat-item">
                            <span class="stat-label">Quizzes</span>
                            <span class="stat-value"><?php echo $stats['total_quizzes'] ?? 0; ?></span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-label">Avg. Score</span>
                            <span class="stat-value"><?php echo round($stats['avg_score'] ?? 0, 1); ?>%</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main Content -->
            <main class="main-content">
                <h1>Dashboard</h1>
                
                <div class="welcome-section">
                    <h2>Ready to Learn, <?php echo explode(' ', $_SESSION['full_name'])[0]; ?>!</h2>
                    <p>Master Computer Systems Servicing with our comprehensive review materials, interactive quizzes, and engaging simulations for all COCs.</p>
                </div>

                <!-- Quick Action Cards -->
                <div class="dashboard-cards">
                    <div class="card">
                        <div class="card-icon"><i class="fas fa-book-open"></i></div>
                        <h3>COC Review</h3>
                        <p>Study comprehensive guides for each COC</p>
                        <div style="display: flex; flex-wrap: wrap; gap: 8px; margin-top: 10px;">
                            <a href="coc1_review.php" class="btn-small" style="padding: 6px 12px;">COC 1</a>
                            <a href="coc2_review.php" class="btn-small" style="padding: 6px 12px;">COC 2</a>
                            <a href="coc3_review.php" class="btn-small" style="padding: 6px 12px;">COC 3</a>
                            <a href="coc4_review.php" class="btn-small" style="padding: 6px 12px;">COC 4</a>
                        </div>
                    </div>
                    
                    <div class="card">
                        <div class="card-icon"><i class="fas fa-pencil-alt"></i></div>
                        <h3>Take a Quiz and simulation</h3>
                        <p>Test your knowledge with timed assessments</p>
                        <div style="display: flex; flex-wrap: wrap; gap: 8px; margin-top: 10px;">
                            <a href="quiz.php?category=1" class="btn-small" style="padding: 6px 12px;">COC 1</a>
                            <a href="quiz.php?category=2" class="btn-small" style="padding: 6px 12px;">COC 2</a>
                            <a href="quiz.php?category=3" class="btn-small" style="padding: 6px 12px;">COC 3</a>
                            <a href="quiz.php?category=4" class="btn-small" style="padding: 6px 12px;">COC 4</a>
                        </div>
                    </div>
                    
                    <div class="card" style="border-color: #ffaa00;">
                        <div class="card-icon" style="background: linear-gradient(135deg, #ffaa00, #ff6600);">
                            <i class="fas fa-microchip"></i>
                        </div>
                        <h3>Installation Simulation</h3>
                        <p>Build a computer by installing components in correct order!</p>
                        <a href="pc_assembly_game.php" class="btn-small" style="border-color: #ffaa00; color: #ffaa00;">
                            <i class="fas fa-gamepad"></i> Play Now!
                        </a>
                    </div>

                    <div class="card" style="border-color: #0066ff;">
                        <div class="card-icon" style="background: linear-gradient(135deg, #0066ff, #003d99);">
                            <i class="fas fa-network-wired"></i>
                        </div>
                        <h3>Client-Server Setup</h3>
                        <p>Build a domain network in 15 steps, from patch cable to mapped drive.</p>
                        <a href="client_server_complete.php" class="btn-small" style="border-color: #0066ff; color: #60a5fa;">
                            <i class="fas fa-play"></i> Start Setup
                        </a>
                    </div>

                    <div class="card" style="border-color: #9333ea;">
                        <div class="card-icon" style="background: linear-gradient(135deg, #9333ea, #6b21a8);">
                            <i class="fas fa-server"></i>
                        </div>
                        <h3>Windows Server Install</h3>
                        <p>Install Windows Server 2012 R2 in 15 steps, from boot media to Server Manager.</p>
                        <a href="windows_server_install.php" class="btn-small" style="border-color: #9333ea; color: #a78bfa;">
                            <i class="fas fa-play"></i> Start Install
                        </a>
                    </div>

                    <div class="card" style="border-color: #ff4444;">
                        <div class="card-icon" style="background: linear-gradient(135deg, #ff4444, #cc0000);">
                            <i class="fas fa-tools"></i>
                        </div>
                        <h3>Troubleshooting</h3>
                        <p>Diagnose and fix 12 common computer problems!</p>
                        <a href="troubleshooting_simulator.php" class="btn-small" style="border-color: #ff4444; color: #ff4444;">
                            <i class="fas fa-stethoscope"></i> Start Diagnosis
                        </a>
                    </div>
                    
                    <div class="card">
                        <div class="card-icon"><i class="fas fa-chart-line"></i></div>
                        <h3>Your Progress</h3>
                        <p>Track your improvement</p>
                        <div class="card-stats">
                            <span>Quizzes: <?php echo $stats['total_quizzes'] ?? 0; ?></span>
                            <span>simulations: <?php echo $game_scores->num_rows; ?></span>
                        </div>
                    </div>
                </div>

                <!-- COC COMPLETION CHECKLIST -->
                <div class="checklist-section">
                    <h2>
                        <i class="fas fa-clipboard-check"></i>
                        COC Completion Checklist
                    </h2>

                    <p class="subtitle">
                        To complete a COC, all required activities must be completed.
                        The COC Quiz must have a score of 75% or higher, and all required
                        simulations/practice activities must also be completed.
                    </p>

                    <?php
                    /*
                    | REQUIRED ACTIVITY RULES
                    | COC 1: Quiz (>=75%), Simulation, Installation Sim
                    | COC 2: Quiz (>=75%), Simulation
                    | COC 3: Quiz (>=75%), Simulation, Client-Server Sim, Windows Server Install
                    | COC 4: Quiz (>=75%), Simulation, Troubleshooting
                    */

                    $completed_cocs = 0;

                    for ($coc = 1; $coc <= 4; $coc++) {

                        if (!isset($coc_progress[$coc])) {
                            $coc_progress[$coc] = [
                                'quiz_completed' => false,
                                'quiz_score' => 0,
                                'game_completed' => false,
                                'game_score' => 0,
                                'install_completed' => false,
                                'install_score' => 0,
                                'clientserver_completed' => false,
                                'clientserver_score' => 0,
                                'windowsserver_completed' => false,
                                'windowsserver_score' => 0,
                                'troubleshoot_completed' => false,
                                'troubleshoot_score' => 0
                            ];
                        }

                        $progress = $coc_progress[$coc];

                        // Quiz
                        $quiz_completed = !empty($progress['quiz_completed']);
                        $quiz_score = isset($progress['quiz_score']) ? (float)$progress['quiz_score'] : 0;
                        $quiz_passed = $quiz_completed && $quiz_score >= 75;

                        // Main simulation
                        $game_completed = !empty($progress['game_completed']);

                        // COC-specific requirements
                        $install_required = ($coc == 1);
                        $clientserver_required = ($coc == 3);
                        $windowsserver_required = ($coc == 3);
                        $troubleshoot_required = ($coc == 4);

                        $install_completed = $install_required ? !empty($progress['install_completed']) : true;
                        $clientserver_completed = $clientserver_required ? !empty($progress['clientserver_completed']) : true;
                        $windowsserver_completed = $windowsserver_required ? !empty($progress['windowsserver_completed']) : true;
                        $troubleshoot_completed = $troubleshoot_required ? !empty($progress['troubleshoot_completed']) : true;

                        // ALL required activities must be completed
                        $is_completed =
                            $quiz_passed &&
                            $game_completed &&
                            (!$install_required || $install_completed) &&
                            (!$clientserver_required || $clientserver_completed) &&
                            (!$windowsserver_required || $windowsserver_completed) &&
                            (!$troubleshoot_required || $troubleshoot_completed);

                        // Status
                        if ($is_completed) {
                            $status = 'completed';
                            $completed_cocs++;
                        } elseif ($quiz_completed || $game_completed ||
                                  ($install_required && $install_completed) ||
                                  ($clientserver_required && $clientserver_completed) ||
                                  ($windowsserver_required && $windowsserver_completed) ||
                                  ($troubleshoot_required && $troubleshoot_completed)) {
                            $status = 'in_progress';
                        } else {
                            $status = 'not_started';
                        }

                        $coc_progress[$coc]['is_completed'] = $is_completed;
                        $coc_progress[$coc]['status'] = $status;
                    }

                    $overall_progress = round(($completed_cocs / 4) * 100);
                    ?>

                    <!-- OVERALL PROGRESS -->
                    <div class="overall-progress">
                        <div class="progress-header">
                            <h3><i class="fas fa-chart-simple"></i> Overall Progress</h3>
                            <span>
                                <?php echo $completed_cocs; ?>/4 COCs Completed
                                (<?php echo $overall_progress; ?>%)
                            </span>
                        </div>

                        <div class="progress-bar-container">
                            <div class="progress-bar-fill" style="width: <?php echo $overall_progress; ?>%;"></div>
                        </div>

                        <div class="progress-stats">
                            <span class="completed"><i class="fas fa-check-circle"></i> Completed: <?php echo $completed_cocs; ?></span>
                            <span><i class="fas fa-hourglass-half"></i> Remaining: <?php echo 4 - $completed_cocs; ?></span>
                            <span><i class="fas fa-flag-checkered"></i> Target: 4/4</span>
                        </div>
                    </div>

                    <!-- COC CARDS -->
                    <div class="checklist-grid">

                        <?php
                        $coc_names = [
                            1 => 'Install & Configure Computer Systems',
                            2 => 'Set-up Computer Networks',
                            3 => 'Set-up Computer Servers',
                            4 => 'Maintain & Repair Computer Systems'
                        ];
                        $coc_icons = [1 => 'fa-cog', 2 => 'fa-network-wired', 3 => 'fa-server', 4 => 'fa-tools'];
                        $coc_colors = ['coc1', 'coc2', 'coc3', 'coc4'];
                        ?>

                        <?php for ($coc = 1; $coc <= 4; $coc++): ?>

                            <?php
                            $progress = $coc_progress[$coc];
                            $is_completed = !empty($progress['is_completed']);
                            $status = $progress['status'] ?? 'not_started';

                            // Re-read THIS COC's own values (previously these still held COC 4's values)
                            $quiz_completed = !empty($progress['quiz_completed']);
                            $quiz_score = (float)($progress['quiz_score'] ?? 0);
                            $quiz_passed = $quiz_completed && $quiz_score >= 75;

                            $game_completed = !empty($progress['game_completed']);
                            $game_score = (float)($progress['game_score'] ?? 0);

                            $install_completed = !empty($progress['install_completed']);
                            $install_score = (float)($progress['install_score'] ?? 0);

                            $clientserver_completed = !empty($progress['clientserver_completed']);
                            $clientserver_score = (float)($progress['clientserver_score'] ?? 0);

                            $windowsserver_completed = !empty($progress['windowsserver_completed']);
                            $windowsserver_score = (float)($progress['windowsserver_score'] ?? 0);

                            $troubleshoot_completed = !empty($progress['troubleshoot_completed']);
                            $troubleshoot_score = (float)($progress['troubleshoot_score'] ?? 0);
                            ?>

                            <div class="checklist-item <?php echo $is_completed ? 'completed' : ''; ?> <?php echo $status === 'in_progress' ? 'needs-retake' : ''; ?>">

                                <?php if ($is_completed): ?>
                                    <div class="check-mark"><i class="fas fa-check-circle"></i></div>
                                <?php endif; ?>

                                <!-- HEADER -->
                                <div class="item-header">
                                    <div class="item-icon <?php echo $coc_colors[$coc - 1]; ?>">
                                        <i class="fas <?php echo $coc_icons[$coc]; ?>"></i>
                                    </div>

                                    <div class="item-title">
                                        <h4>COC <?php echo $coc; ?></h4>
                                        <span class="coc-label"><?php echo $coc_names[$coc]; ?></span>
                                    </div>

                                    <?php if ($status === 'completed'): ?>
                                        <span class="status-badge completed">✅ Completed</span>
                                    <?php elseif ($status === 'in_progress'): ?>
                                        <span class="status-badge pending">🔄 Requirements Incomplete</span>
                                    <?php else: ?>
                                        <span class="status-badge not-started">⚪ Not Started</span>
                                    <?php endif; ?>
                                </div>

                                <!-- DETAILS -->
                                <div class="item-details">

                                    <!-- QUIZ -->
                                    <div class="detail-row">
                                        <span class="label">
                                            <i class="fas fa-pencil-alt"></i> Quiz
                                            <span class="tag-required">Required</span>
                                        </span>
                                        <span class="value <?php echo $quiz_passed ? 'pass' : ($quiz_completed ? 'fail' : ''); ?>">
                                            <?php if ($quiz_completed): ?>
                                                <?php echo $quiz_score; ?>% <?php echo $quiz_passed ? '✅' : '❌'; ?>
                                            <?php else: ?>
                                                Not taken
                                            <?php endif; ?>
                                        </span>
                                    </div>

                                    <!-- MAIN SIMULATION -->
                                    <div class="detail-row">
                                        <span class="label">
                                            <i class="fas fa-gamepad"></i> Simulation
                                            <span class="tag-required">Required</span>
                                        </span>
                                        <span class="value <?php echo $game_completed ? 'pass' : ''; ?>">
                                            <?php if ($game_completed): ?>
                                                <?php echo $game_score; ?> pts ✅
                                            <?php else: ?>
                                                Not completed ❌
                                            <?php endif; ?>
                                        </span>
                                    </div>

                                    <!-- COC 1 INSTALLATION -->
                                    <?php if ($coc == 1): ?>
                                        <div class="detail-row">
                                            <span class="label">
                                                <i class="fas fa-microchip"></i> Installation Sim
                                                <span class="tag-required">Required</span>
                                            </span>
                                            <span class="value <?php echo $install_completed ? 'pass' : ''; ?>">
                                                <?php if ($install_completed): ?>
                                                    <?php echo $install_score; ?> pts ✅
                                                <?php else: ?>
                                                    Not completed ❌
                                                <?php endif; ?>
                                            </span>
                                        </div>
                                    <?php endif; ?>

                                    <!-- COC 3 CLIENT SERVER + WINDOWS SERVER -->
                                    <?php if ($coc == 3): ?>
                                        <div class="detail-row">
                                            <span class="label">
                                                <i class="fas fa-network-wired"></i> Client-Server Sim
                                                <span class="tag-required">Required</span>
                                            </span>
                                            <span class="value <?php echo $clientserver_completed ? 'pass' : ''; ?>">
                                                <?php if ($clientserver_completed): ?>
                                                    <?php echo $clientserver_score; ?> pts ✅
                                                <?php else: ?>
                                                    Not completed ❌
                                                <?php endif; ?>
                                            </span>
                                        </div>

                                        <div class="detail-row">
                                            <span class="label">
                                                <i class="fas fa-server"></i> Windows Server Install
                                                <span class="tag-required">Required</span>
                                            </span>
                                            <span class="value <?php echo $windowsserver_completed ? 'pass' : ''; ?>">
                                                <?php if ($windowsserver_completed): ?>
                                                    <?php echo $windowsserver_score; ?> pts ✅
                                                <?php else: ?>
                                                    Not completed ❌
                                                <?php endif; ?>
                                            </span>
                                        </div>
                                    <?php endif; ?>

                                    <!-- COC 4 TROUBLESHOOTING -->
                                    <?php if ($coc == 4): ?>
                                        <div class="detail-row">
                                            <span class="label">
                                                <i class="fas fa-tools"></i> Troubleshooting
                                                <span class="tag-required">Required</span>
                                            </span>
                                            <span class="value <?php echo $troubleshoot_completed ? 'pass' : ''; ?>">
                                                <?php if ($troubleshoot_completed): ?>
                                                    <?php echo $troubleshoot_score; ?> pts ✅
                                                <?php else: ?>
                                                    Not completed ❌
                                                <?php endif; ?>
                                            </span>
                                        </div>
                                    <?php endif; ?>

                                </div>

                                <!-- NEXT STEP -->
                                <?php if ($status === 'completed'): ?>
                                    <div class="next-step next-step-done">
                                        <i class="fas fa-star"></i>
                                        COC <?php echo $coc; ?> complete! All required activities have been completed.
                                    </div>
                                <?php elseif (!$quiz_passed && $quiz_completed): ?>
                                    <div class="next-step next-step-retake">
                                        <i class="fas fa-redo"></i>
                                        Quiz score: <?php echo $quiz_score; ?>%. You need at least 75% to pass.
                                    </div>
                                <?php elseif (!$quiz_completed): ?>
                                    <div class="next-step">
                                        <i class="fas fa-arrow-right"></i>
                                        Next step: Take the COC <?php echo $coc; ?> Quiz. Minimum score: 75%.
                                    </div>
                                <?php else: ?>
                                    <div class="next-step">
                                        <i class="fas fa-exclamation-circle"></i>
                                        Complete all remaining required activities to finish COC <?php echo $coc; ?>.
                                    </div>
                                <?php endif; ?>

                                <!-- ACTIONS -->
                                <div class="item-actions">

                                    <?php if (!$quiz_passed): ?>
                                        <a href="quiz.php?category=<?php echo $coc; ?>" class="btn-check btn-check-primary">
                                            <i class="fas fa-pencil-alt"></i>
                                            <?php echo $quiz_completed ? 'Retake Quiz' : 'Take Quiz'; ?>
                                        </a>
                                    <?php endif; ?>

                                    <a href="coc<?php echo $coc; ?>_review.php" class="btn-check">
                                        <i class="fas fa-book-open"></i> Review
                                    </a>

                                    <?php if (!$game_completed): ?>
                                        <a href="game.php?coc=<?php echo $coc; ?>" class="btn-check">
                                            <i class="fas fa-gamepad"></i> Complete Simulation
                                        </a>
                                    <?php endif; ?>

                                    <?php if ($coc == 1 && !$install_completed): ?>
                                        <a href="pc_assembly_game.php" class="btn-check" style="border-color:#ffaa00;color:#ffaa00;">
                                            <i class="fas fa-microchip"></i> Complete Installation
                                        </a>
                                    <?php endif; ?>

                                    <?php if ($coc == 3): ?>
                                        <?php if (!$clientserver_completed): ?>
                                            <a href="client_server_complete.php" class="btn-check" style="border-color:#0066ff;color:#60a5fa;">
                                                <i class="fas fa-network-wired"></i> Complete Client-Server
                                            </a>
                                        <?php endif; ?>

                                        <?php if (!$windowsserver_completed): ?>
                                            <a href="windows_server_install.php" class="btn-check" style="border-color:#9333ea;color:#a78bfa;">
                                                <i class="fas fa-server"></i> Complete Server Install
                                            </a>
                                        <?php endif; ?>
                                    <?php endif; ?>

                                    <?php if ($coc == 4 && !$troubleshoot_completed): ?>
                                        <a href="troubleshooting_simulator.php" class="btn-check" style="border-color:#ff4444;color:#ff4444;">
                                            <i class="fas fa-tools"></i> Complete Troubleshooting
                                        </a>
                                    <?php endif; ?>

                                </div>

                            </div>

                        <?php endfor; ?>

                    </div>

                </div>
                
                <!-- COC Assessment Areas -->
                <h2 style="font-family: 'Orbitron', sans-serif; color: #00ff9d; margin: 30px 0 20px;">COC Assessment Areas</h2>
                <div class="coc-grid">
                    <div class="coc-card" id="coc1">
                        <div class="coc-header">
                            <i class="fas fa-cog"></i>
                            <h3>COC 1: Install & Configure Computer Systems</h3>
                        </div>
                        <div class="coc-body">
                            <p>Learn to install and configure computer hardware components including motherboards, CPUs, RAM, and storage devices.</p>
                            <div class="coc-actions">
                                <a href="coc1_review.php" class="btn-coc"><i class="fas fa-book-open"></i> Review</a>
                                <a href="quiz.php?category=1" class="btn-coc"><i class="fas fa-pencil-alt"></i> Quiz</a>
                                <a href="coc1_game.php" class="btn-coc" style="border-color: #1dee4d; color: #10ea79;"><i class="fas fa-gamepad"></i> Simulate</a>
                            </div>
                            <div style="margin-top: 12px; padding-top: 12px; border-top: 1px dashed var(--border-light);">
                                <a href="pc_assembly_game.php" class="btn-coc" style="border-color: #ffaa00; color: #ffaa00; width: 100%;">
                                    <i class="fas fa-microchip"></i> Installation Simulator
                                </a>
                            </div>
                        </div>
                    </div>

                    <div class="coc-card">
                        <div class="coc-header">
                            <i class="fas fa-network-wired"></i>
                            <h3>COC 2: Set-up Computer Networks</h3>
                        </div>
                        <div class="coc-body">
                            <p>Master network setup with guides on cabling, IP configuration, and network device connections. Learn about routers, switches, and protocols.</p>
                            <div class="coc-actions">
                                <a href="coc2_review.php" class="btn-coc"><i class="fas fa-book-open"></i> Review</a>
                                <a href="quiz.php?category=2" class="btn-coc"><i class="fas fa-pencil-alt"></i> Quiz</a>
                                <a href="coc2_game.php" class="btn-coc"><i class="fas fa-gamepad"></i> Simulate</a>
                            </div>
                        </div>
                    </div>

                    <div class="coc-card">
                        <div class="coc-header">
                            <i class="fas fa-server"></i>
                            <h3>COC 3: Set-up Computer Servers</h3>
                        </div>
                        <div class="coc-body">
                            <p>Configure servers with guides on RAID arrays, DNS setup, and server role installation. Learn about Active Directory, DHCP, and file services.</p>
                            <div class="coc-actions">
                                <a href="coc3_review.php" class="btn-coc"><i class="fas fa-book-open"></i> Review</a>
                                <a href="quiz.php?category=3" class="btn-coc"><i class="fas fa-pencil-alt"></i> Quiz</a>
                                <a href="coc3_games.php" class="btn-coc"><i class="fas fa-gamepad"></i> Simulate</a>
                            </div>
                            <div style="margin-top: 12px; padding-top: 12px; border-top: 1px dashed var(--border-light);">
                                <a href="client_server_complete.php" class="btn-coc" style="border-color: #0066ff; color: #60a5fa; width: 100%;">
                                    <i class="fas fa-network-wired"></i> Client-Server Simulator
                                </a>
                            </div>
                            <div style="margin-top: 8px;">
                                <a href="windows_server_install.php" class="btn-coc" style="border-color: #9333ea; color: #a78bfa; width: 100%;">
                                    <i class="fas fa-server"></i> Windows Server Install Simulator
                                </a>
                            </div>
                        </div>
                    </div>

                    <div class="coc-card">
                        <div class="coc-header">
                            <i class="fas fa-tools"></i>
                            <h3>COC 4: Maintain & Repair Computer Systems</h3>
                        </div>
                        <div class="coc-body">
                            <p>Learn troubleshooting techniques with guides on diagnostic tools and repair procedures. Master ESD safety, multimeter use, and problem-solving.</p>
                            <div class="coc-actions">
                                <a href="coc4_review.php" class="btn-coc"><i class="fas fa-book-open"></i> Review</a>
                                <a href="quiz.php?category=4" class="btn-coc"><i class="fas fa-pencil-alt"></i> Quiz</a>
                                <a href="coc4_game.php" class="btn-coc"><i class="fas fa-tools"></i> Simulate</a>
                            </div>
                            <div style="margin-top: 12px; padding-top: 12px; border-top: 1px dashed var(--border-light);">
                                <a href="troubleshooting_simulator.php" class="btn-coc" style="border-color: #ff4444; color: #ff4444; width: 100%;">
                                    <i class="fas fa-stethoscope"></i> Troubleshooting Simulator
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Results -->
                <div class="recent-results">
                    <h2><i class="fas fa-history"></i> Recent Results</h2>
                    <?php if ($recent_results && $recent_results->num_rows > 0): ?>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Activity</th>
                                <th>Category</th>
                                <th>Score</th>
                                <th>Result</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $recent_results->fetch_assoc()): 
                                $percentage = round($row['percentage']);
                                $badge_class = '';
                                switch($row['result_type']) {
                                    case 'quiz': $badge_class = 'result-quiz'; break;
                                    case 'coc1_game': $badge_class = 'result-coc1_game'; break;
                                    case 'coc2_game': $badge_class = 'result-coc2_game'; break;
                                    case 'coc3_game': $badge_class = 'result-coc3_game'; break;
                                    case 'coc4_game': $badge_class = 'result-coc4_game'; break;
                                    case 'installation_sim': $badge_class = 'result-installation_sim'; break;
                                    case 'os_install_sim': $badge_class = 'result-os_install_sim'; break;
                                    case 'troubleshooting_sim': $badge_class = 'result-troubleshooting_sim'; break;
                                    case 'client_server_sim': $badge_class = 'result-client_server_sim'; break;
                                    case 'windows_server_sim': $badge_class = 'result-windows_server_sim'; break;
                                }
                            ?>
                            <tr>
                                <td><?php echo date('M d, Y', strtotime($row['result_date'])); ?></td>
                                <td>
                                    <?php echo htmlspecialchars($row['activity_name']); ?>
                                    <span class="result-badge <?php echo $badge_class; ?>"><?php echo str_replace('_', ' ', $row['result_type']); ?></span>
                                </td>
                                <td><?php echo htmlspecialchars($row['category_name']); ?></td>
                                <td><?php echo htmlspecialchars($row['score_display']); ?></td>
                                <td style="color: <?php echo $percentage >= 75 ? '#00ff9d' : '#ff3366'; ?>">
                                    <?php echo $percentage; ?>%
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                    <?php else: ?>
                    <p class="no-data">No results yet. Start a quiz or play a game!</p>
                    <?php endif; ?>
                </div>
            </main>
        </div>
    </div>

    <script>
        // ============================================
        // THEME TOGGLE (Dark / Light)
        // ============================================
        (function() {
            const themeToggle = document.getElementById('themeToggleSidebar');
            const themeIcon = document.getElementById('themeIcon');
            const themeBadge = document.getElementById('themeBadge');

            function applyTheme(theme) {
                if (theme === 'light') {
                    document.documentElement.setAttribute('data-theme', 'light');
                    themeIcon.className = 'fas fa-sun';
                    themeBadge.textContent = 'Light';
                } else {
                    document.documentElement.removeAttribute('data-theme');
                    themeIcon.className = 'fas fa-moon';
                    themeBadge.textContent = 'Dark';
                }
                localStorage.setItem('theme', theme);
            }

            // Load saved theme (default: dark)
            const savedTheme = localStorage.getItem('theme') || 'dark';
            applyTheme(savedTheme);

            // Toggle on click
            themeToggle.addEventListener('click', function() {
                const current = localStorage.getItem('theme') || 'dark';
                applyTheme(current === 'dark' ? 'light' : 'dark');
            });
        })();

        // ============================================
        // BRIGHTNESS CONTROL
        // ============================================
        const brightnessToggle = document.getElementById('brightnessToggleSidebar');
        const brightnessPanel = document.getElementById('brightnessPanelSidebar');
        const brightnessSlider = document.getElementById('brightnessSliderSidebar');
        const brightnessValueDisplay = document.getElementById('brightnessValueSidebar');
        const brightnessBadge = document.getElementById('brightnessBadge');
        const resetBtn = document.getElementById('resetBrightnessSidebar');
        const presetBtns = document.querySelectorAll('.brightness-preset');

        brightnessToggle.addEventListener('click', function(e) {
            e.stopPropagation();
            brightnessPanel.classList.toggle('active');
            this.classList.toggle('active');
        });

        function updateBrightness(value) {
            document.documentElement.style.setProperty('--global-brightness', value + '%');
            document.documentElement.style.setProperty('--bright', (parseInt(value) / 100));
            brightnessValueDisplay.textContent = value + '%';
            brightnessBadge.textContent = value + '%';
            brightnessSlider.value = value;
            presetBtns.forEach(btn => {
                btn.classList.toggle('active', parseInt(btn.dataset.value) === parseInt(value));
            });
            localStorage.setItem('brightness', value);
            fetch('brightness_control.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'action=update_brightness&value=' + value
            }).catch(err => console.log('Brightness save error:', err));
        }

        const savedBrightness = localStorage.getItem('brightness') || '<?php echo $global_brightness; ?>';
        updateBrightness(parseInt(savedBrightness));

        brightnessSlider.addEventListener('input', function() {
            updateBrightness(this.value);
        });

        presetBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                updateBrightness(this.dataset.value);
            });
        });

        resetBtn.addEventListener('click', function() {
            updateBrightness(100);
        });

        document.addEventListener('click', function(e) {
            if (brightnessPanel.classList.contains('active')) {
                if (!brightnessPanel.contains(e.target) && 
                    e.target !== brightnessToggle && 
                    !brightnessToggle.contains(e.target)) {
                    brightnessPanel.classList.remove('active');
                    brightnessToggle.classList.remove('active');
                }
            }
        });

        document.addEventListener('DOMContentLoaded', function() {
            const progressBars = document.querySelectorAll('.progress-bar-fill');
            progressBars.forEach(bar => {
                const width = bar.style.width;
                bar.style.width = '0%';
                setTimeout(() => {
                    bar.style.width = width;
                }, 300);
            });
        });

        // ============================================
        // MOBILE MENU (hamburger)
        // ============================================
        (function () {
            const menuBtn = document.getElementById('menuBtn');
            const menuIcon = document.getElementById('menuIcon');
            const sidebar = document.getElementById('sidebar');
            const backdrop = document.getElementById('sidebarBackdrop');
            const mobileQuery = window.matchMedia('(max-width: 768px)');

            function setMenu(open) {
                sidebar.classList.toggle('open', open);
                backdrop.classList.toggle('show', open);
                menuBtn.classList.toggle('open', open);
                menuIcon.className = open ? 'fas fa-times' : 'fas fa-bars';
            }

            menuBtn.addEventListener('click', function () {
                setMenu(!sidebar.classList.contains('open'));
            });

            backdrop.addEventListener('click', function () {
                setMenu(false);
            });

            sidebar.querySelectorAll('.sidebar-nav a').forEach(function (a) {
                a.addEventListener('click', function () { setMenu(false); });
            });

            // FIX 5: close the menu only when we leave the mobile layout
            window.addEventListener('resize', function () {
                if (!mobileQuery.matches) setMenu(false);
            });
        })();

        // ============================================
        // SIDEBAR SAFETY NET (fixed sidebar needs a sane viewport height)
        // ============================================
        (function () {
            const dash = document.querySelector('.dashboard');
            const sb = document.getElementById('sidebar');

            function checkSidebar() {
                dash.classList.remove('sidebar-flow');
                const fixed = getComputedStyle(sb).position === 'fixed';
                const isLandscapeLayout = window.matchMedia(
                    '(min-width: 769px) and (max-width: 1100px), (min-width: 769px) and (max-height: 500px)'
                ).matches;
                if (isLandscapeLayout && fixed && sb.getBoundingClientRect().height < 200) {
                    dash.classList.add('sidebar-flow');
                }
            }

            window.addEventListener('load', checkSidebar);
            window.addEventListener('resize', checkSidebar);
            window.addEventListener('orientationchange', function () { setTimeout(checkSidebar, 300); });
        })();
    </script>
</body>
</html>
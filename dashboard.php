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


/* ==========================================================
   ADMIN: STUDENT REGISTRATION CODE GENERATOR (inside dashboard)
========================================================== */
$registration_code_message = '';
$registration_code_error = '';
if ($is_admin) {
    // Create the table safely if it has not been created in phpMyAdmin yet.
    // This prevents dashboard.php from crashing when registration_codes is missing.
    $conn->query("CREATE TABLE IF NOT EXISTS registration_codes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        code VARCHAR(50) NOT NULL UNIQUE,
        is_used TINYINT(1) NOT NULL DEFAULT 0,
        created_by INT NULL,
        used_by INT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        expires_at DATETIME NULL,
        used_at DATETIME NULL,
        INDEX idx_registration_code_used (is_used),
        INDEX idx_registration_code_expiry (expires_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    if (empty($_SESSION['dashboard_registration_csrf'])) {
        $_SESSION['dashboard_registration_csrf'] = bin2hex(random_bytes(32));
    }
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['dashboard_action'] ?? '') === 'generate_registration_codes') {
        $postedToken = (string)($_POST['registration_csrf'] ?? '');
        if (!hash_equals($_SESSION['dashboard_registration_csrf'], $postedToken)) {
            $_SESSION['dashboard_registration_error'] = 'Security check failed. Refresh the dashboard and try again.';
        } else {
            $quantity = max(1, min(25, (int)($_POST['registration_quantity'] ?? 1)));
            $expiryDays = (int)($_POST['registration_expiry_days'] ?? 30);
            if (!in_array($expiryDays, [0, 7, 14, 30, 90], true)) $expiryDays = 30;
            $stmt = $conn->prepare("INSERT INTO registration_codes (code, is_used, created_by, expires_at, created_at) VALUES (?, 0, ?, ?, NOW())");
            if (!$stmt) {
                $_SESSION['dashboard_registration_error'] = 'The registration_codes table is missing. Create the table in phpMyAdmin first.';
            } else {
                $createdCodes = [];
                $adminId = (int)$_SESSION['user_id'];
                try {
                    for ($i = 0; $i < $quantity; $i++) {
                        $code = 'CSS-' . strtoupper(bin2hex(random_bytes(4)));
                        $expiresAt = $expiryDays === 0 ? null : date('Y-m-d H:i:s', time() + ($expiryDays * 86400));
                        $stmt->bind_param('sis', $code, $adminId, $expiresAt);
                        if (!$stmt->execute()) throw new Exception('Unable to save a registration code. Please try again.');
                        $createdCodes[] = $code;
                    }
                    $_SESSION['dashboard_new_registration_codes'] = $createdCodes;
                    $_SESSION['dashboard_registration_message'] = 'Registration codes generated successfully. Give one code to each student.';
                    $_SESSION['dashboard_registration_csrf'] = bin2hex(random_bytes(32));
                } catch (Throwable $e) {
                    $_SESSION['dashboard_registration_error'] = $e->getMessage();
                }
                $stmt->close();
            }
        }
        header('Location: dashboard.php#registration-codes');
        exit;
    }
    $registration_code_message = (string)($_SESSION['dashboard_registration_message'] ?? '');
    $registration_code_error = (string)($_SESSION['dashboard_registration_error'] ?? '');
    $dashboard_new_registration_codes = $_SESSION['dashboard_new_registration_codes'] ?? [];
    unset($_SESSION['dashboard_registration_message'], $_SESSION['dashboard_registration_error'], $_SESSION['dashboard_new_registration_codes']);
    $dashboard_registration_codes = [];
    $registrationCodesResult = $conn->query("SELECT rc.code, rc.is_used, rc.created_at, rc.expires_at, rc.used_at, u.full_name AS used_by_name FROM registration_codes rc LEFT JOIN users u ON u.id = rc.used_by ORDER BY rc.created_at DESC LIMIT 15");
    if ($registrationCodesResult) {
        while ($registrationCodeRow = $registrationCodesResult->fetch_assoc()) $dashboard_registration_codes[] = $registrationCodeRow;
        $registrationCodesResult->free();
    }
}

/* ==========================================================
   ADMIN: CSS STUDENT ASSESSMENT READINESS
   Uses the existing users and quizzes tables.
   A student is marked READY only after attempting all 4 COCs
   and reaching at least 75% average in each COC.
========================================================== */
$readiness_students = [];
$readiness_counts = [
    'total' => 0,
    'ready' => 0,
    'almost' => 0,
    'practice' => 0,
    'not_started' => 0
];

if ($is_admin) {
    $readiness_sql = "
        SELECT
            u.id AS student_id,
            u.username,
            u.full_name,
            u.email,
            COALESCE(qs.quiz_attempts, 0) AS quiz_attempts,
            COALESCE(qs.cocs_attempted, 0) AS cocs_attempted,
            COALESCE(qs.cocs_passed, 0) AS cocs_passed,
            COALESCE(qs.overall_average, 0) AS overall_average,
            COALESCE(qs.latest_quiz, '') AS latest_quiz
        FROM users u
        LEFT JOIN (
            SELECT
                user_id,
                SUM(attempts) AS quiz_attempts,
                COUNT(*) AS cocs_attempted,
                SUM(CASE WHEN coc_average >= 75 THEN 1 ELSE 0 END) AS cocs_passed,
                AVG(coc_average) AS overall_average,
                MAX(latest_quiz) AS latest_quiz
            FROM (
                SELECT
                    user_id,
                    category_id,
                    COUNT(*) AS attempts,
                    AVG((score / NULLIF(total_questions, 0)) * 100) AS coc_average,
                    MAX(completed_at) AS latest_quiz
                FROM quizzes
                WHERE category_id BETWEEN 1 AND 4
                GROUP BY user_id, category_id
            ) AS per_coc
            GROUP BY user_id
        ) AS qs ON qs.user_id = u.id
        WHERE (u.role IS NULL OR u.role <> 'admin')
        ORDER BY u.full_name ASC, u.username ASC
    ";

    $readiness_result = $conn->query($readiness_sql);
    if ($readiness_result) {
        while ($student = $readiness_result->fetch_assoc()) {
            $student['student_id'] = (int)$student['student_id'];
            $student['quiz_attempts'] = (int)$student['quiz_attempts'];
            $student['cocs_attempted'] = (int)$student['cocs_attempted'];
            $student['cocs_passed'] = (int)$student['cocs_passed'];
            $student['overall_average'] = round((float)$student['overall_average'], 1);

            $student['coc_scores'] = [1 => null, 2 => null, 3 => null, 4 => null];
            $score_stmt = $conn->prepare("SELECT category_id, AVG((score / NULLIF(total_questions, 0)) * 100) AS avg_score, COUNT(*) AS attempts FROM quizzes WHERE user_id = ? AND category_id BETWEEN 1 AND 4 GROUP BY category_id");
            if ($score_stmt) {
                $score_stmt->bind_param('i', $student['student_id']);
                $score_stmt->execute();
                $score_result = $score_stmt->get_result();
                while ($score_row = $score_result->fetch_assoc()) {
                    $cat_id = (int)$score_row['category_id'];
                    $student['coc_scores'][$cat_id] = [
                        'average' => round((float)$score_row['avg_score'], 1),
                        'attempts' => (int)$score_row['attempts']
                    ];
                }
                $score_stmt->close();
            }

            if ($student['cocs_attempted'] === 4 && $student['cocs_passed'] === 4) {
                $student['readiness'] = 'READY';
                $student['readiness_class'] = 'ready';
                $readiness_counts['ready']++;
            } elseif ($student['cocs_attempted'] >= 3 && $student['cocs_passed'] >= 2) {
                $student['readiness'] = 'ALMOST READY';
                $student['readiness_class'] = 'almost';
                $readiness_counts['almost']++;
            } elseif ($student['quiz_attempts'] === 0) {
                $student['readiness'] = 'NOT STARTED';
                $student['readiness_class'] = 'not-started';
                $readiness_counts['not_started']++;
            } else {
                $student['readiness'] = 'NEEDS PRACTICE';
                $student['readiness_class'] = 'practice';
                $readiness_counts['practice']++;
            }

            $readiness_students[] = $student;
        }
        $readiness_result->free();
    }
    $readiness_counts['total'] = count($readiness_students);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <?php if (file_exists(__DIR__ . '/includes/theme_preload.php')) { include __DIR__ . '/includes/theme_preload.php'; } ?>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
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

        body {
            font-family: 'Rajdhani', sans-serif;
            background: var(--bg-primary);
            color: var(--text-primary);
            min-height: 100vh;
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
            padding: 4px 0;
            font-size: 0.85rem;
            color: var(--text-secondary);
        }

        .checklist-item .item-details .detail-row .label {
            color: var(--text-muted);
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .checklist-item .item-details .detail-row .value { color: var(--text-primary); }
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

        /* Android / WebView scrolling stability */
        html { scroll-behavior: smooth; -webkit-text-size-adjust: 100%; }
        body { overflow-x: hidden; overscroll-behavior-y: contain; }
        .page-wrapper { min-height: 100dvh; }
        .main-content { -webkit-overflow-scrolling: touch; touch-action: pan-y; }
        .dashboard-cards, .coc-grid, .checklist-grid { align-content: start; }
        .mobile-bottom-nav, .mobile-app-header { -webkit-backdrop-filter: none; backdrop-filter: none; }
        .sidebar-overlay { display:none; }
        .mobile-menu-button { flex:0 0 auto; display:inline-flex; align-items:center; justify-content:center; width:42px; height:42px; border:1px solid var(--border-color); border-radius:11px; background:var(--bg-card); color:#00ff9d; font-size:1.2rem; cursor:pointer; touch-action:manipulation; }
        .sidebar-close-button { display:none; }

        /* Responsive layout for phones / Android WebView */
        .mobile-app-header, .mobile-bottom-nav { display:none; }
        img, video, canvas, iframe { max-width:100%; }
        .main-content, .registration-panel, .readiness-panel, .card, .coc-card { min-width:0; }
        .main-content { overflow-wrap:anywhere; }
        @media (max-width: 900px) {
            .dashboard { grid-template-columns: 1fr; position:relative; z-index:auto; }
            /* Keep drawer and overlay above dashboard content in Android WebView */
            .sidebar-overlay { position:fixed !important; inset:0 !important; z-index:9998 !important; }
            .sidebar {
                display:block !important; position:fixed !important; z-index:9999 !important; top:0; bottom:0; left:0;
                width:min( min(300px, 86vw), 300px); height:100dvh; max-height:100dvh;
                overflow-y:auto; overflow-x:hidden; overscroll-behavior:contain;
                padding:20px 0 28px; transform:translateX(-105%); visibility:hidden;
                transition:transform .24s ease, visibility .24s ease;
                -webkit-overflow-scrolling:touch; box-shadow:12px 0 30px rgba(0,0,0,.35);
            }
            body.sidebar-open { overflow:hidden; }
            body.sidebar-open .sidebar { transform:translateX(0) !important; visibility:visible !important; }
            body.sidebar-open .sidebar-overlay { opacity:1 !important; visibility:visible !important; pointer-events:auto !important; }
            .sidebar-close-button { display:inline-flex; align-items:center; justify-content:center; position:absolute; top:12px; right:12px; width:38px; height:38px; border:1px solid var(--border-color); border-radius:10px; background:var(--bg-card); color:#00ff9d; font-size:1.1rem; z-index:2; }
            .sidebar-overlay { display:block; position:fixed; inset:0; z-index:1250; background:rgba(0,0,0,.58); opacity:0; visibility:hidden; transition:opacity .2s ease,visibility .2s ease; touch-action:manipulation; }
            body.sidebar-open .sidebar-overlay { opacity:1; visibility:visible; }
            .mobile-app-header {
                display:flex; position:sticky; top:0; z-index:1000;
                align-items:center; justify-content:space-between; gap:12px;
                padding:12px 16px; background:var(--bg-sidebar);
                border-bottom:1px solid var(--border-color); backdrop-filter:blur(14px);
            }
            .mobile-header-left { display:flex; align-items:center; gap:10px; min-width:0; }
            .mobile-brand { display:flex; align-items:center; gap:10px; min-width:0; }
            .mobile-brand i { color:#00ff9d; font-size:1.35rem; }
            .mobile-brand strong { font:700 .9rem 'Orbitron',sans-serif; color:#00ff9d; }
            .mobile-brand small { display:block; color:var(--text-secondary); font-size:.78rem; }
            .mobile-theme-button { flex:0 0 auto; border:1px solid var(--border-color); border-radius:10px; padding:9px 11px; background:var(--bg-card); color:var(--text-primary); }
            .main-content { padding:20px 16px 100px; max-height:none; overflow:visible; }
            .main-content h1 { font-size:clamp(1.55rem,5vw,2rem); margin-bottom:20px; }
            .dashboard-cards { grid-template-columns:repeat(2,minmax(0,1fr)); gap:14px; }
            .card { padding:17px; border-radius:16px; }
            .card h3 { font-size:1rem; overflow-wrap:anywhere; }
            .card-icon { width:48px; height:48px; margin-bottom:14px; }
            .card-icon i { font-size:1.5rem; }
            .coc-grid { grid-template-columns:1fr; gap:16px; }
            .checklist-grid { grid-template-columns:1fr; }
            .coc-actions { grid-template-columns:repeat(2,minmax(0,1fr)); }
            .overall-progress .progress-header { flex-direction:row; align-items:center; gap:8px; }
            .mobile-bottom-nav {
                display:grid; grid-template-columns:repeat(4,minmax(0,1fr));
                position:fixed; left:0; right:0; bottom:0; z-index:1100;
                padding:8px 6px calc(8px + env(safe-area-inset-bottom));
                background:var(--bg-sidebar); border-top:1px solid var(--border-color);
                box-shadow:0 -8px 24px rgba(0,0,0,.22);
            }
            .mobile-bottom-nav a { display:flex; flex-direction:column; align-items:center; justify-content:center; gap:4px; min-width:0; padding:5px 2px; text-decoration:none; color:var(--text-secondary); font-size:.7rem; font-weight:600; }
            .mobile-bottom-nav a i { font-size:1.1rem; }
            .mobile-bottom-nav a:active, .mobile-bottom-nav a:hover { color:#00ff9d; }
            .data-table-wrap, .registration-table-wrap, .readiness-table-wrap { max-width:100%; overflow-x:auto; }
            .data-table { min-width:560px; }
            .registration-form { grid-template-columns:repeat(2,minmax(0,1fr)); }
            .readiness-counts { grid-template-columns:repeat(3,minmax(0,1fr)); }
            .detail-grid { grid-template-columns:repeat(2,minmax(0,1fr)); }
        }
        @media (max-width: 560px) {
            .main-content { padding:16px 12px 100px; }
            .welcome-section, .registration-panel, .readiness-panel, .overall-progress { padding:15px; border-radius:14px; }
            .dashboard-cards { grid-template-columns:1fr; }
            .card p { margin-bottom:14px; }
            .coc-actions { grid-template-columns:1fr; }
            .registration-form { grid-template-columns:1fr; }
            .registration-form button { width:100%; }
            .readiness-counts { grid-template-columns:repeat(2,minmax(0,1fr)); }
            .readiness-heading h2 { font-size:.95rem; }
            .detail-grid { grid-template-columns:1fr 1fr; }
            .progress-stats { flex-wrap:wrap; gap:10px; }
            .data-table th, .data-table td { padding:9px; }
        }


        /* Dashboard registration-code generator */
        .registration-panel { margin:22px 0 28px; padding:22px; border:1px solid var(--border-color); border-radius:16px; background:var(--bg-card); box-shadow:0 0 24px rgba(0,255,157,.07); }
        .registration-panel h2 { color:var(--text-primary); font-family:'Orbitron',sans-serif; font-size:1.08rem; margin:0 0 8px; }
        .registration-panel p { color:var(--text-secondary); }
        .registration-form { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:12px; align-items:end; margin:16px 0; }
        .registration-form label { display:block; color:var(--text-secondary); margin-bottom:6px; font-weight:600; }
        .registration-form select { width:100%; min-height:44px; padding:10px; border-radius:9px; border:1px solid var(--border-color); background:var(--bg-input); color:var(--text-primary); font:600 1rem 'Rajdhani',sans-serif; }
        .registration-form button,.registration-copy { min-height:44px; border:0; border-radius:9px; padding:11px 15px; background:linear-gradient(100deg,#00ff9d,#54aaff); color:#050812; font:700 .82rem 'Orbitron',sans-serif; cursor:pointer; }
        .registration-notice { padding:11px 13px; border-radius:9px; margin:12px 0; overflow-wrap:anywhere; }
        .registration-ok { color:#00ff9d; border:1px solid rgba(0,255,157,.4); background:rgba(0,255,157,.07); }
        .registration-error { color:#ff9aa8; border:1px solid rgba(255,99,119,.45); background:rgba(255,99,119,.07); }
        .registration-code-list { display:flex; gap:8px; flex-wrap:wrap; margin:12px 0; }
        .registration-code { display:inline-block; padding:9px 11px; border:1px dashed rgba(0,255,157,.5); border-radius:8px; color:#00ff9d; background:rgba(0,255,157,.06); font:700 .98rem monospace; user-select:all; }
        .registration-table-wrap { overflow-x:auto; margin-top:14px; }
        .registration-table { width:100%; border-collapse:collapse; min-width:600px; }
        .registration-table th,.registration-table td { padding:10px; text-align:left; border-bottom:1px solid var(--border-light); }
        .registration-table th { color:#00ff9d; font-size:.8rem; }
        .registration-status { display:inline-block; border-radius:99px; padding:4px 8px; font-size:.78rem; font-weight:700; }
        .registration-status.available { color:#00ff9d; background:rgba(0,255,157,.1); }
        .registration-status.used { color:#ffbd69; background:rgba(255,189,105,.1); }
        .registration-status.expired { color:#ff9aa8; background:rgba(255,99,119,.1); }
        @media(max-width:560px) { .registration-panel { padding:14px; } .registration-form { grid-template-columns:1fr; } .registration-form button { width:100%; } }

        /* Admin student readiness panel */
        .readiness-panel { margin: 22px 0 28px; padding: 22px; border: 1px solid var(--border-color); border-radius: 16px; background: var(--bg-card); box-shadow: 0 0 24px rgba(0,255,157,.07); }
        .readiness-heading { display:flex; justify-content:space-between; align-items:flex-start; gap:14px; flex-wrap:wrap; margin-bottom:18px; }
        .readiness-heading h2 { color:var(--text-primary); font-family:'Orbitron',sans-serif; font-size:1.15rem; margin:0 0 7px; }
        .readiness-heading p { color:var(--text-secondary); margin:0; }
        .readiness-counts { display:grid; grid-template-columns:repeat(5,minmax(0,1fr)); gap:10px; margin-bottom:18px; }
        .readiness-count { border:1px solid var(--border-light); border-radius:12px; padding:13px; background:var(--bg-stat); min-width:0; }
        .readiness-count span { display:block; color:var(--text-secondary); font-size:.83rem; }
        .readiness-count strong { display:block; margin-top:5px; color:var(--text-primary); font:700 1.55rem 'Orbitron',sans-serif; }
        .readiness-controls { display:flex; gap:10px; flex-wrap:wrap; margin-bottom:14px; }
        .readiness-controls input,.readiness-controls select { min-height:42px; padding:9px 12px; border:1px solid var(--border-color); border-radius:9px; background:var(--bg-input); color:var(--text-primary); font:600 1rem 'Rajdhani',sans-serif; }
        .readiness-controls input { flex:1; min-width:190px; }
        .readiness-table-wrap { width:100%; overflow-x:auto; border-radius:10px; }
        .readiness-table { width:100%; min-width:760px; border-collapse:collapse; }
        .readiness-table th { text-align:left; padding:12px; background:linear-gradient(135deg,#00ff9d,#0066ff); color:#050812; font:700 .75rem 'Orbitron',sans-serif; }
        .readiness-table td { padding:12px; border-bottom:1px solid var(--border-light); color:var(--text-secondary); vertical-align:middle; }
        .readiness-table tr:hover td { background:var(--bg-table-hover); }
        .student-name { color:var(--text-primary); font-weight:700; }
        .student-details { margin-top:5px; }
        .student-details summary { cursor:pointer; color:#00ff9d; font-size:.88rem; font-weight:700; }
        .detail-grid { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:8px; padding:12px 0 4px; }
        .detail-coc { border:1px solid var(--border-light); border-radius:8px; padding:10px; background:var(--bg-stat); }
        .detail-coc b { display:block; color:var(--text-primary); }
        .detail-coc span { display:block; color:var(--text-secondary); font-size:.85rem; margin-top:4px; }
        .readiness-badge { display:inline-block; padding:5px 9px; border-radius:20px; font-size:.75rem; font-weight:700; white-space:nowrap; }
        .readiness-badge.ready { color:#00ff9d; background:rgba(0,255,157,.12); border:1px solid rgba(0,255,157,.4); }
        .readiness-badge.almost { color:#ffd166; background:rgba(255,209,102,.12); border:1px solid rgba(255,209,102,.4); }
        .readiness-badge.practice { color:#ff9b71; background:rgba(255,155,113,.12); border:1px solid rgba(255,155,113,.4); }
        .readiness-badge.not-started { color:#94a3b8; background:rgba(148,163,184,.12); border:1px solid rgba(148,163,184,.35); }
        .readiness-note { margin-top:14px; color:var(--text-secondary); font-size:.9rem; }
        .readiness-empty { padding:24px; text-align:center; color:var(--text-secondary); }
        @media(max-width:900px) { .readiness-counts { grid-template-columns:repeat(3,minmax(0,1fr)); } .detail-grid { grid-template-columns:repeat(2,minmax(0,1fr)); } }
        @media(max-width:560px) { .readiness-panel { padding:14px; } .readiness-counts { grid-template-columns:repeat(2,minmax(0,1fr)); } .readiness-heading h2 { font-size:.95rem; } .detail-grid { grid-template-columns:1fr 1fr; } }

        /* Desktop/web browser: show styled scrollbars. Android WebView hides them below. */
        html, body, * { scrollbar-width: thin; scrollbar-color: var(--scrollbar-thumb) var(--scrollbar-track); }
        *::-webkit-scrollbar { width: 10px; height: 10px; }
        *::-webkit-scrollbar-track { background: var(--scrollbar-track); border-radius: 10px; }
        *::-webkit-scrollbar-thumb { background: var(--scrollbar-thumb); border-radius: 10px; }

        /* Only the Android app/WebView hides scrollbar visuals; scrolling still works. */
        html.android-webview, html.android-webview body,
        html.android-webview *, html.android-webview *::before, html.android-webview *::after {
            scrollbar-width: none !important;
            -ms-overflow-style: none !important;
        }
        html.android-webview::-webkit-scrollbar,
        html.android-webview body::-webkit-scrollbar,
        html.android-webview *::-webkit-scrollbar {
            width: 0 !important;
            height: 0 !important;
            display: none !important;
            background: transparent !important;
        }

        /* Android drawer fix: force sidebar above page content and header */
        @media (max-width: 900px) {
            html, body { min-height:100%; }
            body { position:relative; }
            .page-wrapper { position:relative; isolation:isolate; }
            .dashboard { position:static !important; display:block !important; z-index:auto !important; }
            .mobile-app-header { position:sticky; top:0; z-index:1000 !important; }
            .sidebar-overlay {
                display:block !important; position:fixed !important; inset:0 !important;
                z-index:2147483000 !important; background:rgba(0,0,0,.62) !important;
                opacity:0; visibility:hidden; pointer-events:none;
            }
            body.sidebar-open .sidebar-overlay {
                opacity:1 !important; visibility:visible !important; pointer-events:auto !important;
            }
            .dashboard .sidebar, #dashboardSidebar {
                display:block !important; position:fixed !important;
                top:0 !important; bottom:0 !important; left:0 !important;
                width:min(300px, 86vw) !important; height:100vh !important; height:100dvh !important;
                max-height:100vh !important; max-height:100dvh !important;
                overflow-y:auto !important; overflow-x:hidden !important;
                z-index:2147483001 !important; background:var(--bg-sidebar) !important;
                visibility:hidden; transform:translate3d(-110%,0,0);
                transition:transform .24s ease, visibility .24s ease;
                -webkit-overflow-scrolling:touch; overscroll-behavior-y:contain;
                touch-action:pan-y; padding-top:calc(64px + env(safe-area-inset-top, 0px)) !important;
                padding-bottom:calc(18px + env(safe-area-inset-bottom, 0px)) !important;
                box-shadow:12px 0 32px rgba(0,0,0,.55);
            }
            body.sidebar-open .dashboard .sidebar,
            body.sidebar-open #dashboardSidebar {
                visibility:visible !important; transform:translate3d(0,0,0) !important;
            }
            .sidebar-close-button { display:inline-flex !important; position:fixed !important; top:10px !important; left:min(250px, calc(86vw - 48px)) !important; right:auto !important; z-index:2147483002 !important; }
            body.sidebar-open { overflow:hidden !important; }
            .mobile-menu-button { position:relative; z-index:1001 !important; touch-action:manipulation; }
        }

        /* Final Android/WebView drawer overrides: keep the menu button tappable,
           and prevent the overlay from intercepting taps while the drawer is closed. */
        @media (max-width: 900px) {
            .page-wrapper { isolation: auto !important; }
            .mobile-app-header {
                position: sticky !important;
                top: 0 !important;
                z-index: 12000 !important;
                pointer-events: auto !important;
            }
            .mobile-header-left,
            .mobile-menu-button,
            .mobile-theme-button {
                position: relative !important;
                z-index: 12002 !important;
                pointer-events: auto !important;
                touch-action: manipulation !important;
            }
            .mobile-menu-button {
                min-width: 44px !important;
                min-height: 44px !important;
                -webkit-tap-highlight-color: transparent;
            }
            .sidebar-overlay {
                display: block !important;
                position: fixed !important;
                inset: 0 !important;
                z-index: 12010 !important;
                opacity: 0 !important;
                visibility: hidden !important;
                pointer-events: none !important;
                touch-action: manipulation !important;
            }
            body.sidebar-open .sidebar-overlay {
                opacity: 1 !important;
                visibility: visible !important;
                pointer-events: auto !important;
            }
            .dashboard .sidebar,
            #dashboardSidebar {
                position: fixed !important;
                top: 0 !important;
                bottom: 0 !important;
                left: 0 !important;
                width: min(300px, 86vw) !important;
                height: 100vh !important;
                height: 100dvh !important;
                max-height: 100vh !important;
                max-height: 100dvh !important;
                z-index: 12011 !important;
                transform: translate3d(-110%, 0, 0) !important;
                visibility: hidden !important;
                pointer-events: none !important;
                overflow-y: auto !important;
                -webkit-overflow-scrolling: touch !important;
                touch-action: pan-y !important;
                padding-top: calc(64px + env(safe-area-inset-top, 0px)) !important;
            }
            body.sidebar-open .dashboard .sidebar,
            body.sidebar-open #dashboardSidebar {
                transform: translate3d(0, 0, 0) !important;
                visibility: visible !important;
                pointer-events: auto !important;
            }
            .sidebar-close-button {
                position: fixed !important;
                top: calc(10px + env(safe-area-inset-top, 0px)) !important;
                left: auto !important;
                right: auto !important;
                margin-left: min(246px, calc(86vw - 48px)) !important;
                z-index: 12013 !important;
                min-width: 40px !important;
                min-height: 40px !important;
                pointer-events: auto !important;
                touch-action: manipulation !important;
            }
            body.sidebar-open { overflow: hidden !important; }
        }
    </style>
</head>
<script>
(function () {
    // Web browser keeps visible scrollbars; Android app/WebView hides their visuals.
    var ua = navigator.userAgent || '';
    var isAndroid = /Android/i.test(ua);
    var isWebView = /; wv\)|\bwv\b|Version\/4\.0/i.test(ua) || (isAndroid && /WebView/i.test(ua));
    if (isAndroid && (isWebView || /Chrome/i.test(ua))) {
        document.documentElement.classList.add('android-webview');
    }
})();
</script>
<body>
    <div class="page-wrapper">
        <div class="cyber-bg"></div>
        <div class="grid-overlay"></div>

        <header class="mobile-app-header">
            <div class="mobile-header-left">
                <button type="button" class="mobile-menu-button" id="mobileMenuToggle" aria-label="Open sidebar" aria-controls="dashboardSidebar" aria-expanded="false"><i class="fas fa-bars"></i></button>
                <div class="mobile-brand">
                    <i class="fas fa-microchip"></i>
                    <div><strong>CSS REVIEWER</strong><small><?php echo htmlspecialchars($_SESSION['full_name'] ?? 'Student', ENT_QUOTES, 'UTF-8'); ?><?php echo $is_admin ? ' · Admin' : ''; ?></small></div>
                </div>
            </div>
            <button type="button" class="mobile-theme-button" id="mobileThemeToggle" aria-label="Toggle dark and light mode"><i class="fas fa-circle-half-stroke"></i></button>
        </header>
        <div class="sidebar-overlay" id="sidebarOverlay" aria-hidden="true"></div>

        <div class="dashboard">
            <!-- Sidebar -->
            <div class="sidebar" id="dashboardSidebar" aria-label="Main sidebar">
                <button type="button" class="sidebar-close-button" id="sidebarCloseButton" aria-label="Close sidebar"><i class="fas fa-times"></i></button>
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


                <?php if ($is_admin): ?>
                <section class="registration-panel" id="registration-codes">
                    <h2><i class="fas fa-key"></i> STUDENT REGISTRATION CODES</h2>
                    <p>Generate one-time access codes for CSS students directly from this dashboard. Each code can be used for one successful registration only.</p>
                    <?php if ($registration_code_message): ?><div class="registration-notice registration-ok"><i class="fas fa-circle-check"></i> <?php echo htmlspecialchars($registration_code_message, ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>
                    <?php if ($registration_code_error): ?><div class="registration-notice registration-error"><i class="fas fa-circle-exclamation"></i> <?php echo htmlspecialchars($registration_code_error, ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>
                    <?php if (!empty($dashboard_new_registration_codes)): ?>
                        <strong>Newly generated codes — copy and share privately:</strong>
                        <div class="registration-code-list" id="dashboardNewRegistrationCodes"><?php foreach ($dashboard_new_registration_codes as $newCode): ?><span class="registration-code"><?php echo htmlspecialchars($newCode, ENT_QUOTES, 'UTF-8'); ?></span><?php endforeach; ?></div>
                        <button type="button" class="registration-copy" onclick="copyDashboardRegistrationCodes()"><i class="fas fa-copy"></i> Copy All New Codes</button>
                    <?php endif; ?>
                    <form method="post" class="registration-form">
                        <input type="hidden" name="dashboard_action" value="generate_registration_codes">
                        <input type="hidden" name="registration_csrf" value="<?php echo htmlspecialchars($_SESSION['dashboard_registration_csrf'], ENT_QUOTES, 'UTF-8'); ?>">
                        <div><label for="registration_quantity">Number of codes</label><select id="registration_quantity" name="registration_quantity"><?php for ($i=1; $i<=25; $i++): ?><option value="<?php echo $i; ?>" <?php echo $i===1?'selected':''; ?>><?php echo $i; ?> code<?php echo $i===1?'':'s'; ?></option><?php endfor; ?></select></div>
                        <div><label for="registration_expiry_days">Code expiry</label><select id="registration_expiry_days" name="registration_expiry_days"><option value="7">7 days</option><option value="14">14 days</option><option value="30" selected>30 days</option><option value="90">90 days</option><option value="0">Never expires</option></select></div>
                        <button type="submit"><i class="fas fa-wand-magic-sparkles"></i> Generate Codes</button>
                    </form>
                    <div class="registration-table-wrap"><table class="registration-table"><thead><tr><th>Code</th><th>Status</th><th>Expires</th><th>Used by</th></tr></thead><tbody>
                    <?php if (empty($dashboard_registration_codes)): ?><tr><td colspan="4">No registration codes yet. Generate the first code above.</td></tr>
                    <?php else: foreach ($dashboard_registration_codes as $registrationRow): $registrationExpired = empty($registrationRow['is_used']) && !empty($registrationRow['expires_at']) && strtotime($registrationRow['expires_at']) < time(); $registrationStatus = !empty($registrationRow['is_used']) ? 'USED' : ($registrationExpired ? 'EXPIRED' : 'AVAILABLE'); $registrationStatusClass = !empty($registrationRow['is_used']) ? 'used' : ($registrationExpired ? 'expired' : 'available'); ?>
                        <tr><td><strong><?php echo htmlspecialchars($registrationRow['code'], ENT_QUOTES, 'UTF-8'); ?></strong></td><td><span class="registration-status <?php echo $registrationStatusClass; ?>"><?php echo $registrationStatus; ?></span></td><td><?php echo !empty($registrationRow['expires_at']) ? htmlspecialchars($registrationRow['expires_at'], ENT_QUOTES, 'UTF-8') : 'Never'; ?></td><td><?php echo htmlspecialchars($registrationRow['used_by_name'] ?: '—', ENT_QUOTES, 'UTF-8'); ?></td></tr>
                    <?php endforeach; endif; ?>
                    </tbody></table></div>
                    <p><i class="fas fa-shield-halved"></i> Keep codes private. Students must enter a valid, unused code in register.php before creating an account.</p>
                </section>
                <?php endif; ?>

                <?php if ($is_admin): ?>
                <!-- Admin-only: individual CSS assessment readiness -->
                <section class="readiness-panel" id="student-readiness">
                    <div class="readiness-heading">
                        <div>
                            <h2><i class="fas fa-user-graduate"></i> CSS STUDENT ASSESSMENT READINESS</h2>
                            <p>Check each student's COC quiz averages and open their details.</p>
                        </div>
                        <a href="manage_users.php" class="btn-small"><i class="fas fa-users-cog"></i> Manage Users</a>
                    </div>

                    <div class="readiness-counts">
                        <div class="readiness-count"><span>Total Students</span><strong><?php echo (int)$readiness_counts['total']; ?></strong></div>
                        <div class="readiness-count"><span>Ready</span><strong><?php echo (int)$readiness_counts['ready']; ?></strong></div>
                        <div class="readiness-count"><span>Almost Ready</span><strong><?php echo (int)$readiness_counts['almost']; ?></strong></div>
                        <div class="readiness-count"><span>Needs Practice</span><strong><?php echo (int)$readiness_counts['practice']; ?></strong></div>
                        <div class="readiness-count"><span>Not Started</span><strong><?php echo (int)$readiness_counts['not_started']; ?></strong></div>
                    </div>

                    <div class="readiness-controls">
                        <input type="search" id="readinessSearch" placeholder="Search student name, username, or email..." aria-label="Search students">
                        <select id="readinessFilter" aria-label="Filter readiness status">
                            <option value="all">All statuses</option>
                            <option value="READY">Ready</option>
                            <option value="ALMOST READY">Almost Ready</option>
                            <option value="NEEDS PRACTICE">Needs Practice</option>
                            <option value="NOT STARTED">Not Started</option>
                        </select>
                    </div>

                    <div class="readiness-table-wrap">
                        <table class="readiness-table" id="readinessTable">
                            <thead><tr><th>Student</th><th>COCs Passed</th><th>Quiz Attempts</th><th>Average</th><th>Readiness</th></tr></thead>
                            <tbody>
                            <?php if (empty($readiness_students)): ?>
                                <tr><td colspan="5" class="readiness-empty">No student accounts found yet.</td></tr>
                            <?php else: foreach ($readiness_students as $student): ?>
                                <tr class="readiness-row" data-status="<?php echo htmlspecialchars($student['readiness'], ENT_QUOTES, 'UTF-8'); ?>" data-search="<?php echo htmlspecialchars(strtolower(($student['full_name'] ?? '') . ' ' . ($student['username'] ?? '') . ' ' . ($student['email'] ?? '')), ENT_QUOTES, 'UTF-8'); ?>">
                                    <td>
                                        <div class="student-name"><?php echo htmlspecialchars($student['full_name'] ?: $student['username'], ENT_QUOTES, 'UTF-8'); ?></div>
                                        <div style="font-size:.84rem;">@<?php echo htmlspecialchars($student['username'], ENT_QUOTES, 'UTF-8'); ?><?php if (!empty($student['email'])): ?> · <?php echo htmlspecialchars($student['email'], ENT_QUOTES, 'UTF-8'); ?><?php endif; ?></div>
                                        <details class="student-details">
                                            <summary>View individual details</summary>
                                            <div class="detail-grid">
                                                <?php for ($coc_id = 1; $coc_id <= 4; $coc_id++): $coc_detail = $student['coc_scores'][$coc_id]; ?>
                                                    <div class="detail-coc">
                                                        <b>COC <?php echo $coc_id; ?></b>
                                                        <?php if ($coc_detail !== null): ?>
                                                            <span>Average: <?php echo number_format($coc_detail['average'], 1); ?>%</span>
                                                            <span>Attempts: <?php echo (int)$coc_detail['attempts']; ?></span>
                                                            <span><?php echo $coc_detail['average'] >= 75 ? '✓ Benchmark met' : 'Needs practice'; ?></span>
                                                        <?php else: ?>
                                                            <span>No quiz attempts</span>
                                                        <?php endif; ?>
                                                    </div>
                                                <?php endfor; ?>
                                            </div>
                                            <div class="readiness-note">Latest quiz activity: <?php echo !empty($student['latest_quiz']) ? htmlspecialchars($student['latest_quiz'], ENT_QUOTES, 'UTF-8') : 'No quiz activity'; ?></div>
                                        </details>
                                    </td>
                                    <td><?php echo (int)$student['cocs_passed']; ?> / 4</td>
                                    <td><?php echo (int)$student['quiz_attempts']; ?></td>
                                    <td><?php echo number_format($student['overall_average'], 1); ?>%</td>
                                    <td><span class="readiness-badge <?php echo htmlspecialchars($student['readiness_class'], ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($student['readiness'], ENT_QUOTES, 'UTF-8'); ?></span></td>
                                </tr>
                            <?php endforeach; endif; ?>
                            </tbody>
                        </table>
                    </div>
                    <p class="readiness-note"><i class="fas fa-info-circle"></i> Readiness is a practice indicator based on quiz records only, not an official TESDA assessment result. “READY” means the student has attempted all four COCs and has at least a 75% average in each.</p>
                </section>
                <?php endif; ?>
                
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
    |--------------------------------------------------------------------------
    | REQUIRED ACTIVITY RULES
    |--------------------------------------------------------------------------
    |
    | COC 1:
    |   - Quiz: Required, minimum 75%
    |   - Simulation: Required
    |   - Installation Sim: Required
    |
    | COC 2:
    |   - Quiz: Required, minimum 75%
    |   - Simulation: Required
    |
    | COC 3:
    |   - Quiz: Required, minimum 75%
    |   - Simulation: Required
    |   - Client-Server Sim: Required
    |   - Windows Server Install: Required
    |
    | COC 4:
    |   - Quiz: Required, minimum 75%
    |   - Simulation: Required
    |   - Troubleshooting: Required
    |
    |--------------------------------------------------------------------------
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

        /*
        |--------------------------------------------------------------------------
        | QUIZ REQUIREMENT
        |--------------------------------------------------------------------------
        */
        $quiz_required = true;

        $quiz_completed = !empty($progress['quiz_completed']);
        $quiz_score = isset($progress['quiz_score'])
            ? (float)$progress['quiz_score']
            : 0;

        $quiz_passed = $quiz_completed && $quiz_score >= 75;


        /*
        |--------------------------------------------------------------------------
        | GAME / SIMULATION REQUIREMENT
        |--------------------------------------------------------------------------
        */
        $game_required = true;

        $game_completed = !empty($progress['game_completed']);
        $game_score = isset($progress['game_score'])
            ? (float)$progress['game_score']
            : 0;


        /*
        |--------------------------------------------------------------------------
        | COC-SPECIFIC REQUIREMENTS
        |--------------------------------------------------------------------------
        */

        $install_required = false;
        $install_completed = true;

        $clientserver_required = false;
        $clientserver_completed = true;

        $windowsserver_required = false;
        $windowsserver_completed = true;

        $troubleshoot_required = false;
        $troubleshoot_completed = true;


        /*
        |--------------------------------------------------------------------------
        | COC 1
        |--------------------------------------------------------------------------
        */
        if ($coc == 1) {

            $install_required = true;

            $install_completed = !empty($progress['install_completed']);

            $install_score = isset($progress['install_score'])
                ? (float)$progress['install_score']
                : 0;
        }


        /*
        |--------------------------------------------------------------------------
        | COC 3
        |--------------------------------------------------------------------------
        */
        if ($coc == 3) {

            $clientserver_required = true;
            $windowsserver_required = true;

            $clientserver_completed =
                !empty($progress['clientserver_completed']);

            $windowsserver_completed =
                !empty($progress['windowsserver_completed']);

            $clientserver_score = isset($progress['clientserver_score'])
                ? (float)$progress['clientserver_score']
                : 0;

            $windowsserver_score = isset($progress['windowsserver_score'])
                ? (float)$progress['windowsserver_score']
                : 0;
        }


        /*
        |--------------------------------------------------------------------------
        | COC 4
        |--------------------------------------------------------------------------
        */
        if ($coc == 4) {

            $troubleshoot_required = true;

            $troubleshoot_completed =
                !empty($progress['troubleshoot_completed']);

            $troubleshoot_score = isset($progress['troubleshoot_score'])
                ? (float)$progress['troubleshoot_score']
                : 0;
        }


        /*
        |--------------------------------------------------------------------------
        | FINAL COC COMPLETION CHECK
        |--------------------------------------------------------------------------
        |
        | ALL required activities must be completed.
        |
        */
        $is_completed =
            $quiz_passed &&
            $game_completed &&
            (!$install_required || $install_completed) &&
            (!$clientserver_required || $clientserver_completed) &&
            (!$windowsserver_required || $windowsserver_completed) &&
            (!$troubleshoot_required || $troubleshoot_completed);


        /*
        |--------------------------------------------------------------------------
        | STATUS
        |--------------------------------------------------------------------------
        */

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


        /*
        |--------------------------------------------------------------------------
        | STORE CALCULATED STATUS
        |--------------------------------------------------------------------------
        */

        $coc_progress[$coc]['is_completed'] = $is_completed;
        $coc_progress[$coc]['status'] = $status;
    }

    /*
    |--------------------------------------------------------------------------
    | OVERALL PROGRESS
    |--------------------------------------------------------------------------
    */

    $overall_progress = round(($completed_cocs / 4) * 100);
    ?>

    <!-- OVERALL PROGRESS -->
    <div class="overall-progress">

        <div class="progress-header">

            <h3>
                <i class="fas fa-chart-simple"></i>
                Overall Progress
            </h3>

            <span>
                <?php echo $completed_cocs; ?>/4 COCs Completed
                (<?php echo $overall_progress; ?>%)
            </span>

        </div>

        <div class="progress-bar-container">

            <div
                class="progress-bar-fill"
                style="width: <?php echo $overall_progress; ?>%;">
            </div>

        </div>

        <div class="progress-stats">

            <span class="completed">
                <i class="fas fa-check-circle"></i>
                Completed: <?php echo $completed_cocs; ?>
            </span>

            <span>
                <i class="fas fa-hourglass-half"></i>
                Remaining: <?php echo 4 - $completed_cocs; ?>
            </span>

            <span>
                <i class="fas fa-flag-checkered"></i>
                Target: 4/4
            </span>

        </div>

    </div>


    <!-- COC CARDS -->
    <div class="checklist-grid">

        <?php for ($coc = 1; $coc <= 4; $coc++): ?>

            <?php

            $progress = $coc_progress[$coc];

            $is_completed = !empty($progress['is_completed']);

            $status = $progress['status'] ?? 'not_started';

            /*
            |--------------------------------------------------------------------------
            | COC NAMES
            |--------------------------------------------------------------------------
            */

            $coc_names = [
                1 => 'Install & Configure Computer Systems',
                2 => 'Set-up Computer Networks',
                3 => 'Set-up Computer Servers',
                4 => 'Maintain & Repair Computer Systems'
            ];

            /*
            |--------------------------------------------------------------------------
            | ICONS
            |--------------------------------------------------------------------------
            */

            $coc_icons = [
                1 => 'fa-cog',
                2 => 'fa-network-wired',
                3 => 'fa-server',
                4 => 'fa-tools'
            ];

            /*
            |--------------------------------------------------------------------------
            | COLORS
            |--------------------------------------------------------------------------
            */

            $coc_colors = [
                'coc1',
                'coc2',
                'coc3',
                'coc4'
            ];

            ?>

            <div
                class="checklist-item
                <?php echo $is_completed ? 'completed' : ''; ?>
                <?php echo $status === 'in_progress' ? 'needs-retake' : ''; ?>"
            >

                <?php if ($is_completed): ?>

                    <div class="check-mark">
                        <i class="fas fa-check-circle"></i>
                    </div>

                <?php endif; ?>


                <!-- HEADER -->
                <div class="item-header">

                    <div class="item-icon <?php echo $coc_colors[$coc - 1]; ?>">

                        <i class="fas <?php echo $coc_icons[$coc]; ?>"></i>

                    </div>


                    <div class="item-title">

                        <h4>
                            COC <?php echo $coc; ?>
                        </h4>

                        <span class="coc-label">
                            <?php echo $coc_names[$coc]; ?>
                        </span>

                    </div>


                    <?php if ($status === 'completed'): ?>

                        <span class="status-badge completed">
                            ✅ Completed
                        </span>

                    <?php elseif ($status === 'in_progress'): ?>

                        <span class="status-badge pending">
                            🔄 Requirements Incomplete
                        </span>

                    <?php else: ?>

                        <span class="status-badge not-started">
                            ⚪ Not Started
                        </span>

                    <?php endif; ?>

                </div>


                <!-- DETAILS -->
                <div class="item-details">


                    <!-- QUIZ -->
                    <div class="detail-row">

                        <span class="label">

                            <i class="fas fa-pencil-alt"></i>

                            Quiz

                            <span class="tag-required">
                                Required
                            </span>

                        </span>


                        <span
                            class="value
                            <?php echo $quiz_passed ? 'pass' : ($quiz_completed ? 'fail' : ''); ?>"
                        >

                            <?php if ($quiz_completed): ?>

                                <?php echo $quiz_score; ?>%

                                <?php if ($quiz_passed): ?>

                                    ✅

                                <?php else: ?>

                                    ❌

                                <?php endif; ?>

                            <?php else: ?>

                                Not taken

                            <?php endif; ?>

                        </span>

                    </div>


                    <!-- MAIN SIMULATION -->
                    <div class="detail-row">

                        <span class="label">

                            <i class="fas fa-gamepad"></i>

                            Simulation

                            <span class="tag-required">
                                Required
                            </span>

                        </span>


                        <span
                            class="value
                            <?php echo $game_completed ? 'pass' : ''; ?>"
                        >

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

                                <i class="fas fa-microchip"></i>

                                Installation Sim

                                <span class="tag-required">
                                    Required
                                </span>

                            </span>


                            <span
                                class="value
                                <?php echo $install_completed ? 'pass' : ''; ?>"
                            >

                                <?php if ($install_completed): ?>

                                    <?php echo $install_score; ?> pts ✅

                                <?php else: ?>

                                    Not completed ❌

                                <?php endif; ?>

                            </span>

                        </div>

                    <?php endif; ?>


                    <!-- COC 3 CLIENT SERVER -->
                    <?php if ($coc == 3): ?>

                        <div class="detail-row">

                            <span class="label">

                                <i class="fas fa-network-wired"></i>

                                Client-Server Sim

                                <span class="tag-required">
                                    Required
                                </span>

                            </span>


                            <span
                                class="value
                                <?php echo $clientserver_completed ? 'pass' : ''; ?>"
                            >

                                <?php if ($clientserver_completed): ?>

                                    <?php echo $clientserver_score; ?> pts ✅

                                <?php else: ?>

                                    Not completed ❌

                                <?php endif; ?>

                            </span>

                        </div>


                        <!-- WINDOWS SERVER -->
                        <div class="detail-row">

                            <span class="label">

                                <i class="fas fa-server"></i>

                                Windows Server Install

                                <span class="tag-required">
                                    Required
                                </span>

                            </span>


                            <span
                                class="value
                                <?php echo $windowsserver_completed ? 'pass' : ''; ?>"
                            >

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

                                <i class="fas fa-tools"></i>

                                Troubleshooting

                                <span class="tag-required">
                                    Required
                                </span>

                            </span>


                            <span
                                class="value
                                <?php echo $troubleshoot_completed ? 'pass' : ''; ?>"
                            >

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

                        COC <?php echo $coc; ?> complete!
                        All required activities have been completed.

                    </div>


                <?php elseif (!$quiz_passed && $quiz_completed): ?>

                    <div class="next-step next-step-retake">

                        <i class="fas fa-redo"></i>

                        Quiz score:
                        <?php echo $quiz_score; ?>%.

                        You need at least 75% to pass.

                    </div>


                <?php elseif (!$quiz_completed): ?>

                    <div class="next-step">

                        <i class="fas fa-arrow-right"></i>

                        Next step:
                        Take the COC <?php echo $coc; ?> Quiz.
                        Minimum score: 75%.

                    </div>


                <?php else: ?>

                    <div class="next-step">

                        <i class="fas fa-exclamation-circle"></i>

                        Complete all remaining required activities
                        to finish COC <?php echo $coc; ?>.

                    </div>

                <?php endif; ?>


                <!-- ACTIONS -->
                <div class="item-actions">


                    <?php if (!$quiz_passed): ?>

                        <a
                            href="quiz.php?category=<?php echo $coc; ?>"
                            class="btn-check btn-check-primary"
                        >

                            <i class="fas fa-pencil-alt"></i>

                            <?php echo $quiz_completed ? 'Retake Quiz' : 'Take Quiz'; ?>

                        </a>

                    <?php endif; ?>


                    <a
                        href="coc<?php echo $coc; ?>_review.php"
                        class="btn-check"
                    >

                        <i class="fas fa-book-open"></i>

                        Review

                    </a>


                    <!-- MAIN SIMULATION -->
                    <?php if (!$game_completed): ?>

                        <a
                            href="game.php?coc=<?php echo $coc; ?>"
                            class="btn-check"
                        >

                            <i class="fas fa-gamepad"></i>

                            Complete Simulation

                        </a>

                    <?php endif; ?>


                    <!-- COC 1 INSTALL -->
                    <?php if ($coc == 1 && !$install_completed): ?>

                        <a
                            href="pc_assembly_game.php"
                            class="btn-check"
                            style="border-color:#ffaa00;color:#ffaa00;"
                        >

                            <i class="fas fa-microchip"></i>

                            Complete Installation

                        </a>

                    <?php endif; ?>


                    <!-- COC 3 -->
                    <?php if ($coc == 3): ?>

                        <?php if (!$clientserver_completed): ?>

                            <a
                                href="client_server_complete.php"
                                class="btn-check"
                                style="border-color:#0066ff;color:#60a5fa;"
                            >

                                <i class="fas fa-network-wired"></i>

                                Complete Client-Server

                            </a>

                        <?php endif; ?>


                        <?php if (!$windowsserver_completed): ?>

                            <a
                                href="windows_server_install.php"
                                class="btn-check"
                                style="border-color:#9333ea;color:#a78bfa;"
                            >

                                <i class="fas fa-server"></i>

                                Complete Server Install

                            </a>

                        <?php endif; ?>

                    <?php endif; ?>


                    <!-- COC 4 -->
                    <?php if ($coc == 4 && !$troubleshoot_completed): ?>

                        <a
                            href="troubleshooting_simulator.php"
                            class="btn-check"
                            style="border-color:#ff4444;color:#ff4444;"
                        >

                            <i class="fas fa-tools"></i>

                            Complete Troubleshooting

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
        <nav class="mobile-bottom-nav" aria-label="Mobile navigation">
            <a href="dashboard.php"><i class="fas fa-house"></i><span>Home</span></a>
            <a href="coc1_review.php"><i class="fas fa-book-open"></i><span>Review</span></a>
            <a href="quiz.php?category=1"><i class="fas fa-circle-question"></i><span>Quiz</span></a>
            <a href="game.php?coc=1"><i class="fas fa-gamepad"></i><span>Practice</span></a>
        </nav>
    </div>

    <script>
        // Mobile hamburger sidebar: Android WebView-safe, single source of truth.
        (function() {
            const menuButton = document.getElementById('mobileMenuToggle');
            const closeButton = document.getElementById('sidebarCloseButton');
            const overlay = document.getElementById('sidebarOverlay');
            const sidebar = document.getElementById('dashboardSidebar');
            if (!menuButton || !sidebar || !overlay) return;

            function syncSidebar(open) {
                document.body.classList.toggle('sidebar-open', open);
                menuButton.setAttribute('aria-expanded', open ? 'true' : 'false');
                menuButton.setAttribute('aria-label', open ? 'Close sidebar' : 'Open sidebar');
                overlay.setAttribute('aria-hidden', open ? 'false' : 'true');
                const icon = menuButton.querySelector('i');
                if (icon) icon.className = open ? 'fas fa-times' : 'fas fa-bars';
            }
            function closeSidebar() { syncSidebar(false); }
            function openSidebar() { syncSidebar(true); }

            // Use click plus touchend fallback for older Android WebViews.
            let lastToggleAt = 0;
            function toggleSidebar(event) {
                if (event) {
                    if (event.cancelable) event.preventDefault();
                    event.stopPropagation();
                }
                const now = Date.now();
                if (now - lastToggleAt < 350) return;
                lastToggleAt = now;
                syncSidebar(!document.body.classList.contains('sidebar-open'));
            }
            menuButton.addEventListener('click', toggleSidebar, false);
            menuButton.addEventListener('touchend', toggleSidebar, { passive: false });
            if (closeButton) {
                closeButton.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    closeSidebar();
                });
                closeButton.addEventListener('touchend', function(e) {
                    if (e.cancelable) e.preventDefault();
                    e.stopPropagation();
                    closeSidebar();
                }, { passive: false });
            }
            overlay.addEventListener('click', closeSidebar);
            overlay.addEventListener('touchend', function(e) {
                if (e.cancelable) e.preventDefault();
                closeSidebar();
            }, { passive: false });

            sidebar.querySelectorAll('a').forEach(function(link) {
                link.addEventListener('click', function() {
                    if (window.matchMedia('(max-width: 900px)').matches) closeSidebar();
                });
            });
            document.addEventListener('keydown', function(event) {
                if (event.key === 'Escape') closeSidebar();
            });
            window.addEventListener('resize', function() {
                if (window.matchMedia('(min-width: 901px)').matches) closeSidebar();
            }, { passive: true });
            syncSidebar(false);
        })();

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
            const mobileThemeToggle = document.getElementById('mobileThemeToggle');
            if (mobileThemeToggle) {
                mobileThemeToggle.addEventListener('click', function() {
                    const current = localStorage.getItem('theme') || 'dark';
                    applyTheme(current === 'dark' ? 'light' : 'dark');
                });
            }
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
    </script>

<script>
(function () {
    const search = document.getElementById('readinessSearch');
    const filter = document.getElementById('readinessFilter');
    const rows = document.querySelectorAll('#readinessTable .readiness-row');
    if (!search || !filter || !rows.length) return;
    function applyReadinessFilters() {
        const term = search.value.trim().toLowerCase();
        const status = filter.value;
        rows.forEach(function (row) {
            const matchesSearch = (row.dataset.search || '').includes(term);
            const matchesStatus = status === 'all' || row.dataset.status === status;
            row.style.display = matchesSearch && matchesStatus ? '' : 'none';
        });
    }
    search.addEventListener('input', applyReadinessFilters);
    filter.addEventListener('change', applyReadinessFilters);
})();
</script>


<script>
function copyDashboardRegistrationCodes(){
  const nodes=[...document.querySelectorAll('#dashboardNewRegistrationCodes .registration-code')];
  const text=nodes.map(node=>node.textContent.trim()).join('\n');
  if(!text)return;
  if(navigator.clipboard&&window.isSecureContext){navigator.clipboard.writeText(text).then(()=>alert('Registration codes copied. Give one code to each student only.')).catch(()=>fallbackDashboardCopy(text));}
  else fallbackDashboardCopy(text);
}
function fallbackDashboardCopy(value){const area=document.createElement('textarea');area.value=value;document.body.appendChild(area);area.select();document.execCommand('copy');area.remove();alert('Registration codes copied. Give one code to each student only.');}
</script>

</body>
</html> ari ang dashboard.php
<?php
// view_results.php - Admin view all results (Quizzes + Games + Simulations)
require_once 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

$search = isset($_GET['search']) ? $_GET['search'] : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$category_filter = isset($_GET['category']) ? $_GET['category'] : '';
$user_filter = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
$activity_type = isset($_GET['activity_type']) ? $_GET['activity_type'] : 'all';

$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

$all_queries = [];

$all_queries[] = "
    SELECT q.id, q.user_id, q.category_id, q.score, q.total_questions, q.time_taken, q.completed_at,
           u.username, u.full_name, u.email, c.name AS category_name,
           'quiz' AS result_type, '📝 Quiz' AS type_icon, CONCAT('Quiz: ', c.name) AS activity_name,
           ROUND((q.score / NULLIF(q.total_questions,0)) * 100, 1) AS percentage,
           q.score AS raw_score, q.total_questions AS max_score, q.completed_at AS sort_date
    FROM quizzes q
    JOIN users u ON q.user_id = u.id
    JOIN categories c ON q.category_id = c.id
";

$all_queries[] = "
    SELECT gs.id, gs.user_id, gs.coc_number AS category_id, gs.high_score AS score,
           NULL AS total_questions, NULL AS time_taken, gs.played_at AS completed_at,
           u.username, u.full_name, u.email, 'COC 1 - PC Assembly' AS category_name,
           'coc1_game' AS result_type, '🎮 COC 1' AS type_icon,
           'COC 1 - PC Assembly Game' AS activity_name,
           LEAST(gs.high_score,100) AS percentage, gs.high_score AS raw_score, 100 AS max_score,
           gs.played_at AS sort_date
    FROM user_game_scores gs JOIN users u ON gs.user_id = u.id WHERE gs.coc_number = 1
";

$all_queries[] = "
    SELECT g.id, g.user_id, g.coc_number AS category_id, g.score,
           NULL AS total_questions, NULL AS time_taken, g.played_at AS completed_at,
           u.username, u.full_name, u.email, 'COC 2 - RJ45 Crimping' AS category_name,
           'coc2_game' AS result_type, '🔌 COC 2' AS type_icon,
           'COC 2 - RJ45 Cable Crimping' AS activity_name,
           LEAST(g.score,100) AS percentage, g.score AS raw_score, 100 AS max_score,
           g.played_at AS sort_date
    FROM game_scores g JOIN users u ON g.user_id = u.id WHERE g.coc_number = 2
";

$all_queries[] = "
    SELECT g.id, g.user_id, g.coc_number AS category_id, g.score,
           NULL AS total_questions, NULL AS time_taken, g.played_at AS completed_at,
           u.username, u.full_name, u.email, 'COC 3 - Server Admin' AS category_name,
           'coc3_game' AS result_type, '🎲 COC 3' AS type_icon,
           'COC 3 - Server Admin Games' AS activity_name,
           LEAST(g.score,100) AS percentage, g.score AS raw_score, 100 AS max_score,
           g.played_at AS sort_date
    FROM game_scores g JOIN users u ON g.user_id = u.id WHERE g.coc_number = 3
";

$all_queries[] = "
    SELECT gs.id, gs.user_id, gs.coc_number AS category_id, gs.high_score AS score,
           NULL AS total_questions, NULL AS time_taken, gs.played_at AS completed_at,
           u.username, u.full_name, u.email, 'COC 4 - Stress Test' AS category_name,
           'coc4_game' AS result_type, '💀 COC 4' AS type_icon,
           'COC 4 - Stress Test PC Building' AS activity_name,
           LEAST(gs.high_score,100) AS percentage, gs.high_score AS raw_score, 100 AS max_score,
           gs.played_at AS sort_date
    FROM user_game_scores gs JOIN users u ON gs.user_id = u.id WHERE gs.coc_number = 4
";

$all_queries[] = "
    SELECT gs.id, gs.user_id, gs.coc_number AS category_id, gs.high_score AS score,
           NULL AS total_questions, NULL AS time_taken, gs.played_at AS completed_at,
           u.username, u.full_name, u.email, 'Installation Simulation' AS category_name,
           'installation_sim' AS result_type, '🔧 Installation' AS type_icon,
           'PC Assembly Simulator' AS activity_name,
           LEAST(gs.high_score,100) AS percentage, gs.high_score AS raw_score, 100 AS max_score,
           gs.played_at AS sort_date
    FROM user_game_scores gs JOIN users u ON gs.user_id = u.id WHERE gs.coc_number = 1
";

$os_exists = $conn->query("SHOW TABLES LIKE 'os_installation_scores'");
if ($os_exists && $os_exists->num_rows > 0) {
    $all_queries[] = "
        SELECT os.id, os.user_id, NULL AS category_id, os.score,
               NULL AS total_questions, NULL AS time_taken, os.completed_at,
               u.username, u.full_name, u.email, 'OS Installation' AS category_name,
               'os_install_sim' AS result_type, '💿 OS Install' AS type_icon,
               'OS Installation Simulator' AS activity_name,
               LEAST(os.score,100) AS percentage, os.score AS raw_score, 100 AS max_score,
               os.completed_at AS sort_date
        FROM os_installation_scores os JOIN users u ON os.user_id = u.id
    ";
}

$ts_exists = $conn->query("SHOW TABLES LIKE 'troubleshooting_scores'");
if ($ts_exists && $ts_exists->num_rows > 0) {
    $all_queries[] = "
        SELECT ts.id, ts.user_id, NULL AS category_id, ts.score,
               NULL AS total_questions, NULL AS time_taken, ts.played_at AS completed_at,
               u.username, u.full_name, u.email, 'Troubleshooting' AS category_name,
               'troubleshooting_sim' AS result_type, '🛠️ Troubleshoot' AS type_icon,
               'Troubleshooting Simulator' AS activity_name,
               LEAST(ts.score,100) AS percentage, ts.score AS raw_score, 100 AS max_score,
               ts.played_at AS sort_date
        FROM troubleshooting_scores ts JOIN users u ON ts.user_id = u.id
    ";
}

$combined_sql = implode(" UNION ALL ", $all_queries) . " ORDER BY sort_date DESC";
$all_results_query = $conn->query($combined_sql);
$results = [];
if ($all_results_query) {
    while ($row = $all_results_query->fetch_assoc()) $results[] = $row;
}

$filtered_results = [];
foreach ($results as $row) {
    if ($search !== '') {
        $needle = strtolower($search);
        $haystack = strtolower(($row['full_name'] ?? '') . ' ' . ($row['username'] ?? '') . ' ' . ($row['email'] ?? '') . ' ' . ($row['category_name'] ?? '') . ' ' . ($row['activity_name'] ?? ''));
        if (strpos($haystack, $needle) === false) continue;
    }
    $percentage = (float)$row['percentage'];
    if ($status_filter === 'excellent' && $percentage < 90) continue;
    if ($status_filter === 'passed' && $percentage < 75) continue;
    if ($status_filter === 'failed' && $percentage >= 75) continue;
    if ($category_filter !== '' && (string)$row['category_id'] !== (string)$category_filter) continue;
    if ($user_filter && (int)$row['user_id'] !== $user_filter) continue;
    if ($activity_type !== 'all' && $row['result_type'] !== $activity_type) continue;
    $filtered_results[] = $row;
}

$total_results = count($filtered_results);
$total_pages = max(1, (int)ceil($total_results / $limit));
if ($page > $total_pages) $page = $total_pages;
$offset = ($page - 1) * $limit;
$paginated_results = array_slice($filtered_results, $offset, $limit);

$stats = ['total'=>$total_results, 'passed'=>0, 'failed'=>0, 'excellent'=>0];
$total_percentage = 0;
$unique_users = [];
foreach ($filtered_results as $row) {
    $pct = (float)$row['percentage'];
    $total_percentage += $pct;
    if ($pct >= 90) $stats['excellent']++;
    elseif ($pct >= 75) $stats['passed']++;
    else $stats['failed']++;
    $unique_users[(int)$row['user_id']] = true;
}
$stats['average'] = $stats['total'] ? round($total_percentage / $stats['total'], 1) : 0;
$stats['unique_users'] = count($unique_users);

$users_list = [];
$users_result = $conn->query("SELECT id, username, full_name FROM users ORDER BY username");
if ($users_result) while ($row = $users_result->fetch_assoc()) $users_list[] = $row;

$categories_list = [];
$categories_result = $conn->query("SELECT id, name FROM categories ORDER BY name");
if ($categories_result) while ($row = $categories_result->fetch_assoc()) $categories_list[] = $row;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>View Results | CSS TESDA Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&family=Rajdhani:wght@300;400;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<style>
*{margin:0;padding:0;box-sizing:border-box}
:root{--bg:#0a0f1e;--bg2:#050812;--panel:rgba(0,0,0,.3);--nav:rgba(10,20,40,.9);--text:#e0e0ff;--muted:#94a3b8;--border:rgba(0,255,157,.2);--green:#00ff9d;--blue:#0066ff;--input:rgba(0,0,0,.4);--brightness:100%}
body{font-family:'Rajdhani',sans-serif;background:var(--bg);color:var(--text);overflow-x:hidden;filter:brightness(var(--brightness));transition:background .3s,color .3s}
.cyber-bg{position:fixed;top:0;left:0;width:100%;height:100%;z-index:-2;background:linear-gradient(135deg,var(--bg) 0%,var(--bg2) 100%);transition:background .3s}
.grid-overlay{position:fixed;top:0;left:0;width:100%;height:100%;background-image:linear-gradient(rgba(0,255,157,.03) 1px,transparent 1px),linear-gradient(90deg,rgba(0,255,157,.03) 1px,transparent 1px);background-size:50px 50px;z-index:-1;animation:gridMove 20s linear infinite}
@keyframes gridMove{0%{transform:translate(0,0)}100%{transform:translate(50px,50px)}}
.dashboard-container{display:flex;min-height:100vh}.sidebar{width:280px;background:var(--nav);backdrop-filter:blur(10px);border-right:1px solid var(--border);padding:2rem 1.5rem;position:fixed;height:100vh;overflow-y:auto;z-index:10;transition:background .3s,border .3s}
.logo{font-family:'Orbitron',sans-serif;font-size:1.4rem;font-weight:800;background:linear-gradient(135deg,var(--green),var(--blue));-webkit-background-clip:text;-webkit-text-fill-color:transparent;margin-bottom:2.5rem;text-align:center}
.nav-menu{list-style:none}.nav-item{margin-bottom:.5rem}.nav-link{display:flex;align-items:center;gap:12px;padding:12px 16px;color:var(--muted);text-decoration:none;border-radius:12px;transition:all .3s}.nav-link:hover,.nav-link.active{background:rgba(0,255,157,.1);color:var(--green)}.nav-link i{width:24px}
.main-content{flex:1;margin-left:280px;padding:2rem}.page-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:2rem;flex-wrap:wrap;gap:1rem}.page-header h1{font-family:'Orbitron',sans-serif;font-size:1.8rem;background:linear-gradient(135deg,var(--green),var(--blue));-webkit-background-clip:text;-webkit-text-fill-color:transparent}
.btn{padding:10px 24px;border:none;border-radius:50px;font-family:'Orbitron',sans-serif;font-weight:600;cursor:pointer;transition:all .3s;text-decoration:none;display:inline-flex;align-items:center;gap:8px}.btn-primary{background:linear-gradient(45deg,var(--green),var(--blue));color:#050812}.btn-primary:hover{transform:translateY(-2px);box-shadow:0 5px 15px rgba(0,255,157,.4)}.btn-secondary{background:transparent;border:2px solid var(--green);color:var(--green)}.btn-secondary:hover{background:var(--green);color:#050812}.btn-sm{padding:5px 12px;font-size:.75rem}.btn-warning{background:#f59e0b;color:#050812}
.stats-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:1rem;margin-bottom:2rem}.stat-card{background:var(--panel);border:1px solid var(--border);border-radius:15px;padding:1rem;text-align:center}.stat-number{font-family:'Orbitron',sans-serif;font-size:2rem;color:var(--green)}.stat-label{color:#ced4e7;font-size:.8rem}
.filter-card{background:var(--panel);border:1px solid var(--border);border-radius:20px;padding:1.5rem;margin-bottom:2rem}.filter-form{display:flex;flex-wrap:wrap;gap:1rem;align-items:flex-end}.filter-group{flex:1;min-width:150px}.filter-group label{display:block;margin-bottom:5px;font-size:.8rem;color:#dfe2eb}.filter-group input,.filter-group select{width:100%;padding:8px 12px;background:var(--input);border:1px solid rgba(0,255,157,.3);border-radius:10px;color:var(--text);font-family:'Rajdhani',sans-serif}
.table-card{background:var(--panel);border:1px solid var(--border);border-radius:20px;overflow:hidden}.table-container{overflow-x:auto}table{width:100%;border-collapse:collapse}th,td{padding:14px 16px;text-align:left;border-bottom:1px solid rgba(0,255,157,.1)}th{background:rgba(0,255,157,.05);font-family:'Orbitron',sans-serif;font-weight:600;color:var(--green)}tr:hover{background:rgba(0,255,157,.02)}
.badge{padding:4px 12px;border-radius:50px;font-size:.7rem;font-weight:600}.badge-excellent{background:rgba(0,255,157,.2);color:var(--green)}.badge-passed{background:rgba(0,102,255,.2);color:#60a5fa}.badge-failed{background:rgba(220,38,38,.2);color:#ef4444}.type-badge{display:inline-block;padding:2px 8px;border-radius:12px;font-size:.65rem;font-weight:bold;margin-left:8px}.type-quiz{background:rgba(0,255,157,.2);color:var(--green)}.type-coc1_game{background:rgba(255,68,68,.2);color:#ff6666}.type-coc2_game{background:rgba(255,170,0,.2);color:#ffaa00}.type-coc3_game{background:rgba(59,130,246,.2);color:#60a5fa}.type-coc4_game{background:rgba(236,72,153,.2);color:#ec4899}.type-installation_sim{background:rgba(34,197,94,.2);color:#22c55e}.type-os_install_sim{background:rgba(0,255,157,.2);color:var(--green)}.type-troubleshooting_sim{background:rgba(168,85,247,.2);color:#a855f7}
.progress-bar{width:100px;height:6px;background:rgba(217,204,204,.1);border-radius:3px;overflow:hidden}.progress-fill{height:100%;border-radius:3px}.progress-fill.high{background:var(--green)}.progress-fill.medium{background:#f59e0b}.progress-fill.low{background:#ef4444}.action-buttons{display:flex;gap:8px}.pagination{display:flex;justify-content:center;gap:8px;margin-top:1.5rem}.pagination a,.pagination span{padding:8px 14px;background:rgba(0,0,0,.3);border-radius:10px;text-decoration:none;color:var(--green);transition:all .3s}.pagination a:hover,.pagination .active{background:var(--green);color:#050812}
.theme-brightness{display:flex;flex-direction:column;gap:10px;margin-top:2rem;padding-top:1.5rem;border-top:1px solid rgba(0,255,157,.12)}.theme-toggle,.brightness-control{width:100%;display:flex;align-items:center;gap:10px;padding:11px 14px;border:1px solid rgba(0,255,157,.25);border-radius:12px;background:rgba(0,255,157,.04);color:var(--green);font-family:'Rajdhani',sans-serif;font-weight:600;cursor:pointer;transition:.3s}.theme-toggle:hover,.brightness-control:hover{background:rgba(0,255,157,.12)}.theme-toggle span:first-of-type,.brightness-control span:first-of-type{flex:1;text-align:left}.brightness-panel{display:none;background:rgba(0,0,0,.25);border:1px solid rgba(0,255,157,.18);border-radius:12px;padding:12px}.brightness-panel.active{display:block}.brightness-label{display:flex;justify-content:space-between;color:var(--muted);font-size:.8rem;margin-bottom:8px}.brightness-slider{width:100%;accent-color:var(--green)}.brightness-presets{display:grid;grid-template-columns:repeat(4,1fr);gap:5px;margin-top:10px}.brightness-preset{border:1px solid rgba(0,255,157,.2);background:transparent;color:var(--muted);border-radius:7px;padding:5px 2px;font-family:'Rajdhani';cursor:pointer;font-size:.7rem}.brightness-preset.active,.brightness-preset:hover{background:var(--green);color:#050812}.brightness-reset{width:100%;margin-top:8px;padding:7px;border:0;border-radius:7px;background:#f59e0b;color:#050812;cursor:pointer;font-family:'Rajdhani';font-weight:600}
[data-theme="light"]{--bg:#eef3f8;--bg2:#dfe8f2;--panel:rgba(255,255,255,.78);--nav:rgba(255,255,255,.92);--text:#172033;--muted:#526174;--border:rgba(0,130,95,.25);--input:rgba(255,255,255,.9)}[data-theme="light"] .grid-overlay{background-image:linear-gradient(rgba(0,100,80,.06) 1px,transparent 1px),linear-gradient(90deg,rgba(0,100,80,.06) 1px,transparent 1px)}[data-theme="light"] .stat-label,[data-theme="light"] .filter-group label{color:#475569}[data-theme="light"] th{background:rgba(0,130,95,.07)}[data-theme="light"] .nav-link{color:#526174}[data-theme="light"] .nav-link:hover,[data-theme="light"] .nav-link.active{color:#007f5f;background:rgba(0,160,120,.1)}[data-theme="light"] .filter-group input,[data-theme="light"] .filter-group select{color:#172033}
@media(max-width:768px){.sidebar{width:80px;padding:1rem .5rem}.sidebar .logo,.sidebar .nav-link span,.theme-toggle span,.brightness-control span{display:none}.main-content{margin-left:80px}.nav-link{justify-content:center}.theme-toggle,.brightness-control{justify-content:center}.brightness-panel{width:180px}.brightness-panel .brightness-label{display:flex}}
@media print{.sidebar,.filter-card,.page-header .btn,.action-buttons,.pagination,.theme-brightness{display:none!important}.main-content{margin-left:0}.table-card{border:1px solid #aaa}.cyber-bg,.grid-overlay{display:none}body{filter:none;background:#fff;color:#000}}
</style>
</head>
<body>
<div class="cyber-bg"></div><div class="grid-overlay"></div>
<div class="dashboard-container">
<aside class="sidebar">
    <div class="logo">CSS Admin</div>
    <ul class="nav-menu">
        <li class="nav-item"><a href="dashboard.php" class="nav-link"><i class="fas fa-tachometer-alt"></i><span>Dashboard</span></a></li>
        <li class="nav-item"><a href="view_results.php" class="nav-link active"><i class="fas fa-chart-line"></i><span>Results</span></a></li>
        <li class="nav-item"><a href="../logout.php" class="nav-link"><i class="fas fa-sign-out-alt"></i><span>Logout</span></a></li>
    </ul>
    <div class="theme-brightness">
        <button type="button" class="theme-toggle" id="themeToggle"><i class="fas fa-moon" id="themeIcon"></i><span id="themeText">Dark Mode</span></button>
        <button type="button" class="brightness-control" id="brightnessToggle"><i class="fas fa-sun"></i><span>Brightness</span><strong id="brightnessBadge">100%</strong><i class="fas fa-chevron-down"></i></button>
        <div class="brightness-panel" id="brightnessPanel">
            <div class="brightness-label"><span>Adjust Brightness</span><strong id="brightnessValue">100%</strong></div>
            <input type="range" class="brightness-slider" id="brightnessSlider" min="50" max="150" value="100">
            <div class="brightness-presets">
                <button type="button" class="brightness-preset" data-value="60">Dim</button>
                <button type="button" class="brightness-preset active" data-value="100">Normal</button>
                <button type="button" class="brightness-preset" data-value="130">Bright</button>
                <button type="button" class="brightness-preset" data-value="150">Extra</button>
            </div>
            <button type="button" class="brightness-reset" id="resetBrightness"><i class="fas fa-undo"></i> Reset to 100%</button>
        </div>
    </div>
</aside>
<main class="main-content">
<div class="page-header"><h1><i class="fas fa-chart-line"></i> All Results (Quizzes + Games + Simulations)</h1><button onclick="window.print()" class="btn btn-secondary"><i class="fas fa-print"></i> Print Report</button></div>
<div class="stats-grid">
<?php foreach ([['total','Total Activities'],['excellent','Excellent (90%+)'],['passed','Passed (75%+)'],['failed','Failed (<75%)'],['average','Average Score'],['unique_users','Active Users']] as $s): ?><div class="stat-card"><div class="stat-number"><?php echo $stats[$s[0]] . ($s[0]==='average'?'%':''); ?></div><div class="stat-label"><?php echo $s[1]; ?></div></div><?php endforeach; ?>
</div>
<div class="filter-card"><form method="GET" class="filter-form">
<div class="filter-group"><label><i class="fas fa-search"></i> Search</label><input type="text" name="search" placeholder="User or activity..." value="<?php echo htmlspecialchars($search); ?>"></div>
<div class="filter-group"><label><i class="fas fa-filter"></i> Status</label><select name="status"><option value="">All</option><option value="excellent" <?php echo $status_filter==='excellent'?'selected':''; ?>>Excellent (90%+)</option><option value="passed" <?php echo $status_filter==='passed'?'selected':''; ?>>Passed (75%+)</option><option value="failed" <?php echo $status_filter==='failed'?'selected':''; ?>>Failed (&lt;75%)</option></select></div>
<div class="filter-group"><label><i class="fas fa-layer-group"></i> Category</label><select name="category"><option value="">All Categories</option><?php foreach($categories_list as $cat): ?><option value="<?php echo $cat['id']; ?>" <?php echo $category_filter==$cat['id']?'selected':''; ?>><?php echo htmlspecialchars($cat['name']); ?></option><?php endforeach; ?></select></div>
<div class="filter-group"><label><i class="fas fa-user"></i> User</label><select name="user_id"><option value="0">All Users</option><?php foreach($users_list as $user): ?><option value="<?php echo $user['id']; ?>" <?php echo $user_filter==$user['id']?'selected':''; ?>><?php echo htmlspecialchars($user['full_name']); ?></option><?php endforeach; ?></select></div>
<div class="filter-group"><label><i class="fas fa-tag"></i> Activity Type</label><select name="activity_type"><option value="all">All Activities</option><option value="quiz">📝 Quizzes</option><option value="coc1_game">🎮 COC 1 Game</option><option value="coc2_game">🔌 COC 2 Game</option><option value="coc3_game">🎲 COC 3 Game</option><option value="coc4_game">💀 COC 4 Game</option><option value="installation_sim">🔧 Installation Sim</option><option value="os_install_sim">💿 OS Installation</option><option value="troubleshooting_sim">🛠️ Troubleshooting</option></select></div>
<div class="filter-group"><label>&nbsp;</label><button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-filter"></i> Apply</button> <a href="view_results.php" class="btn btn-warning btn-sm"><i class="fas fa-undo"></i> Reset</a></div>
</form></div>
<div class="table-card"><div class="table-container"><table><thead><tr><th>ID</th><th>User</th><th>Activity</th><th>Category</th><th>Score</th><th>Percentage</th><th>Status</th><th>Completed</th><th>Actions</th></tr></thead><tbody>
<?php if($paginated_results): foreach($paginated_results as $row): $percentage=round((float)$row['percentage']); $progress_class=$percentage>=75?'high':($percentage>=50?'medium':'low'); $status_class=$percentage>=90?'excellent':($percentage>=75?'passed':'failed'); $status_text=ucfirst($status_class); $type_class='type-'.$row['result_type']; ?>
<tr><td><?php echo $row['id']; ?></td><td><strong><?php echo htmlspecialchars($row['full_name']); ?></strong><br><small><?php echo htmlspecialchars($row['username']); ?></small></td><td><?php echo $row['type_icon'].' '.htmlspecialchars($row['activity_name']); ?> <span class="type-badge <?php echo $type_class; ?>"><?php echo str_replace('_',' ',$row['result_type']); ?></span></td><td><?php echo htmlspecialchars($row['category_name']); ?></td><td><?php echo $row['total_questions'] ? $row['raw_score'].'/'.$row['max_score'] : $row['raw_score'].' pts'; ?></td><td><div style="display:flex;align-items:center;gap:8px"><span><?php echo $percentage; ?>%</span><div class="progress-bar"><div class="progress-fill <?php echo $progress_class; ?>" style="width:<?php echo max(0,min(100,$percentage)); ?>%"></div></div></div></td><td><span class="badge badge-<?php echo $status_class; ?>"><?php echo $status_text; ?></span></td><td><?php echo date('M d, Y h:i A',strtotime($row['completed_at'])); ?></td><td class="action-buttons"><a href="view_result_details.php?id=<?php echo $row['id']; ?>&type=<?php echo urlencode($row['result_type']); ?>" class="btn btn-primary btn-sm" title="View Details"><i class="fas fa-eye"></i></a></td></tr>
<?php endforeach; else: ?><tr><td colspan="9" style="text-align:center;padding:40px"><i class="fas fa-inbox" style="font-size:48px;opacity:.5"></i><p>No results found. Play some games or take quizzes!</p></td></tr><?php endif; ?>
</tbody></table></div></div>
<?php if($total_pages>1): ?><div class="pagination"><?php if($page>1): ?><a href="?page=<?php echo $page-1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>&category=<?php echo urlencode($category_filter); ?>&user_id=<?php echo $user_filter; ?>&activity_type=<?php echo urlencode($activity_type); ?>">« Prev</a><?php endif; ?><?php for($i=max(1,$page-2);$i<=min($total_pages,$page+2);$i++): ?><a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>&category=<?php echo urlencode($category_filter); ?>&user_id=<?php echo $user_filter; ?>&activity_type=<?php echo urlencode($activity_type); ?>" class="<?php echo $i==$page?'active':''; ?>"><?php echo $i; ?></a><?php endfor; ?><?php if($page<$total_pages): ?><a href="?page=<?php echo $page+1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>&category=<?php echo urlencode($category_filter); ?>&user_id=<?php echo $user_filter; ?>&activity_type=<?php echo urlencode($activity_type); ?>">Next »</a><?php endif; ?></div><?php endif; ?>
<div style="margin-top:1rem;text-align:right;color:var(--muted)"><small>Showing <?php echo count($paginated_results); ?> of <?php echo $total_results; ?> results</small></div>
</main></div>
<script>
(function(){
 const root=document.documentElement, themeToggle=document.getElementById('themeToggle'), themeIcon=document.getElementById('themeIcon'), themeText=document.getElementById('themeText');
 function applyTheme(theme){const light=theme==='light';root.setAttribute('data-theme',light?'light':'dark');themeIcon.className=light?'fas fa-sun':'fas fa-moon';themeText.textContent=light?'Light Mode':'Dark Mode';localStorage.setItem('css_tesda_theme',theme)}
 applyTheme(localStorage.getItem('css_tesda_theme')||'dark'); themeToggle.addEventListener('click',()=>applyTheme((localStorage.getItem('css_tesda_theme')||'dark')==='dark'?'light':'dark'));
 const bt=document.getElementById('brightnessToggle'),bp=document.getElementById('brightnessPanel'),bs=document.getElementById('brightnessSlider'),bv=document.getElementById('brightnessValue'),bb=document.getElementById('brightnessBadge'),reset=document.getElementById('resetBrightness'),presets=document.querySelectorAll('.brightness-preset');
 function applyBrightness(v){v=parseInt(v);root.style.setProperty('--brightness',(v/100));bs.value=v;bv.textContent=v+'%';bb.textContent=v+'%';presets.forEach(p=>p.classList.toggle('active',parseInt(p.dataset.value)===v));localStorage.setItem('css_tesda_brightness',v);if(window.fetch){fetch('brightness_control.php',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:'action=update_brightness&value='+v}).catch(()=>{});}}
 applyBrightness(parseInt(localStorage.getItem('css_tesda_brightness')||'100'));bt.addEventListener('click',e=>{e.stopPropagation();bp.classList.toggle('active')});bs.addEventListener('input',e=>applyBrightness(e.target.value));presets.forEach(p=>p.addEventListener('click',()=>applyBrightness(p.dataset.value)));reset.addEventListener('click',()=>applyBrightness(100));document.addEventListener('click',e=>{if(bp.classList.contains('active')&&!bp.contains(e.target)&&!bt.contains(e.target))bp.classList.remove('active')});
})();
</script>
</body></html>

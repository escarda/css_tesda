<?php
// view_results.php - Admin view all results
// Location: /admin/view_results.php

require_once __DIR__ . '/../config.php';

/*
|--------------------------------------------------------------------------
| BASE PATH
|--------------------------------------------------------------------------
*/
$base = rtrim(
    str_replace(
        '\\',
        '/',
        dirname(dirname($_SERVER['SCRIPT_NAME']))
    ),
    '/'
);

if ($base === '.' || $base === '/') {
    $base = '';
}

/*
|--------------------------------------------------------------------------
| ADMIN ACCESS
|--------------------------------------------------------------------------
*/
if (
    !isset($_SESSION['user_id']) ||
    ($_SESSION['role'] ?? '') !== 'admin'
) {
    header("Location: " . $base . "/login.php");
    exit();
}

/*
|--------------------------------------------------------------------------
| FILTERS
|--------------------------------------------------------------------------
*/
$search = isset($_GET['search'])
    ? trim($_GET['search'])
    : '';

$status_filter = isset($_GET['status'])
    ? $_GET['status']
    : '';

$category_filter = isset($_GET['category'])
    ? $_GET['category']
    : '';

$user_filter = isset($_GET['user_id'])
    ? intval($_GET['user_id'])
    : 0;

$activity_type = isset($_GET['activity_type'])
    ? $_GET['activity_type']
    : 'all';

$page = isset($_GET['page'])
    ? max(1, intval($_GET['page']))
    : 1;

$limit = 20;

/*
|--------------------------------------------------------------------------
| BUILD RESULT QUERIES
|--------------------------------------------------------------------------
*/
$all_queries = [];

/*
|--------------------------------------------------------------------------
| QUIZZES
|--------------------------------------------------------------------------
*/
$all_queries[] = "
    SELECT
        q.id,
        q.user_id,
        q.category_id,
        q.score,
        q.total_questions,
        q.time_taken,
        q.completed_at,
        u.username,
        u.full_name,
        u.email,
        c.name AS category_name,
        'quiz' AS result_type,
        '📝 Quiz' AS type_icon,
        CONCAT('Quiz: ', c.name) AS activity_name,
        ROUND(
            (q.score / NULLIF(q.total_questions, 0)) * 100,
            1
        ) AS percentage,
        q.score AS raw_score,
        q.total_questions AS max_score,
        q.completed_at AS sort_date
    FROM quizzes q
    JOIN users u
        ON q.user_id = u.id
    JOIN categories c
        ON q.category_id = c.id
";

/*
|--------------------------------------------------------------------------
| COC 1 GAME
|--------------------------------------------------------------------------
*/
$all_queries[] = "
    SELECT
        gs.id,
        gs.user_id,
        gs.coc_number AS category_id,
        gs.high_score AS score,
        NULL AS total_questions,
        NULL AS time_taken,
        gs.played_at AS completed_at,
        u.username,
        u.full_name,
        u.email,
        'COC 1 - PC Assembly' AS category_name,
        'coc1_game' AS result_type,
        '🎮 COC 1' AS type_icon,
        'COC 1 - PC Assembly Game' AS activity_name,
        LEAST(gs.high_score, 100) AS percentage,
        gs.high_score AS raw_score,
        100 AS max_score,
        gs.played_at AS sort_date
    FROM user_game_scores gs
    JOIN users u
        ON gs.user_id = u.id
    WHERE gs.coc_number = 1
";

/*
|--------------------------------------------------------------------------
| COC 2 GAME
|--------------------------------------------------------------------------
*/
$all_queries[] = "
    SELECT
        g.id,
        g.user_id,
        g.coc_number AS category_id,
        g.score,
        NULL AS total_questions,
        NULL AS time_taken,
        g.played_at AS completed_at,
        u.username,
        u.full_name,
        u.email,
        'COC 2 - RJ45 Crimping' AS category_name,
        'coc2_game' AS result_type,
        '🔌 COC 2' AS type_icon,
        'COC 2 - RJ45 Cable Crimping' AS activity_name,
        LEAST(g.score, 100) AS percentage,
        g.score AS raw_score,
        100 AS max_score,
        g.played_at AS sort_date
    FROM game_scores g
    JOIN users u
        ON g.user_id = u.id
    WHERE g.coc_number = 2
";

/*
|--------------------------------------------------------------------------
| COC 3 GAME
|--------------------------------------------------------------------------
*/
$all_queries[] = "
    SELECT
        g.id,
        g.user_id,
        g.coc_number AS category_id,
        g.score,
        NULL AS total_questions,
        NULL AS time_taken,
        g.played_at AS completed_at,
        u.username,
        u.full_name,
        u.email,
        'COC 3 - Server Admin' AS category_name,
        'coc3_game' AS result_type,
        '🎲 COC 3' AS type_icon,
        'COC 3 - Server Admin Games' AS activity_name,
        LEAST(g.score, 100) AS percentage,
        g.score AS raw_score,
        100 AS max_score,
        g.played_at AS sort_date
    FROM game_scores g
    JOIN users u
        ON g.user_id = u.id
    WHERE g.coc_number = 3
";

/*
|--------------------------------------------------------------------------
| COC 4 GAME
|--------------------------------------------------------------------------
*/
$all_queries[] = "
    SELECT
        gs.id,
        gs.user_id,
        gs.coc_number AS category_id,
        gs.high_score AS score,
        NULL AS total_questions,
        NULL AS time_taken,
        gs.played_at AS completed_at,
        u.username,
        u.full_name,
        u.email,
        'COC 4 - Stress Test' AS category_name,
        'coc4_game' AS result_type,
        '💀 COC 4' AS type_icon,
        'COC 4 - Stress Test PC Building' AS activity_name,
        LEAST(gs.high_score, 100) AS percentage,
        gs.high_score AS raw_score,
        100 AS max_score,
        gs.played_at AS sort_date
    FROM user_game_scores gs
    JOIN users u
        ON gs.user_id = u.id
    WHERE gs.coc_number = 4
";

/*
|--------------------------------------------------------------------------
| INSTALLATION SIMULATOR
|--------------------------------------------------------------------------
*/
$all_queries[] = "
    SELECT
        gs.id,
        gs.user_id,
        gs.coc_number AS category_id,
        gs.high_score AS score,
        NULL AS total_questions,
        NULL AS time_taken,
        gs.played_at AS completed_at,
        u.username,
        u.full_name,
        u.email,
        'Installation Simulation' AS category_name,
        'installation_sim' AS result_type,
        '🔧 Installation' AS type_icon,
        'PC Assembly Simulator' AS activity_name,
        LEAST(gs.high_score, 100) AS percentage,
        gs.high_score AS raw_score,
        100 AS max_score,
        gs.played_at AS sort_date
    FROM user_game_scores gs
    JOIN users u
        ON gs.user_id = u.id
    WHERE gs.coc_number = 1
";

/*
|--------------------------------------------------------------------------
| OS INSTALLATION SIMULATOR
|--------------------------------------------------------------------------
*/
$os_exists = $conn->query(
    "SHOW TABLES LIKE 'os_installation_scores'"
);

if ($os_exists && $os_exists->num_rows > 0) {

    $all_queries[] = "
        SELECT
            os.id,
            os.user_id,
            NULL AS category_id,
            os.score,
            NULL AS total_questions,
            NULL AS time_taken,
            os.completed_at,
            u.username,
            u.full_name,
            u.email,
            'OS Installation' AS category_name,
            'os_install_sim' AS result_type,
            '💿 OS Install' AS type_icon,
            'OS Installation Simulator' AS activity_name,
            LEAST(os.score, 100) AS percentage,
            os.score AS raw_score,
            100 AS max_score,
            os.completed_at AS sort_date
        FROM os_installation_scores os
        JOIN users u
            ON os.user_id = u.id
    ";
}

/*
|--------------------------------------------------------------------------
| TROUBLESHOOTING SIMULATOR
|--------------------------------------------------------------------------
*/
$ts_exists = $conn->query(
    "SHOW TABLES LIKE 'troubleshooting_scores'"
);

if ($ts_exists && $ts_exists->num_rows > 0) {

    $all_queries[] = "
        SELECT
            ts.id,
            ts.user_id,
            NULL AS category_id,
            ts.score,
            NULL AS total_questions,
            NULL AS time_taken,
            ts.played_at AS completed_at,
            u.username,
            u.full_name,
            u.email,
            'Troubleshooting' AS category_name,
            'troubleshooting_sim' AS result_type,
            '🛠️ Troubleshoot' AS type_icon,
            'Troubleshooting Simulator' AS activity_name,
            LEAST(ts.score, 100) AS percentage,
            ts.score AS raw_score,
            100 AS max_score,
            ts.played_at AS sort_date
        FROM troubleshooting_scores ts
        JOIN users u
            ON ts.user_id = u.id
    ";
}

/*
|--------------------------------------------------------------------------
| COMBINED QUERY
|--------------------------------------------------------------------------
*/
$combined_sql =
    implode(" UNION ALL ", $all_queries) .
    " ORDER BY sort_date DESC";

$all_results_query = $conn->query($combined_sql);

$results = [];

if ($all_results_query) {

    while ($row = $all_results_query->fetch_assoc()) {
        $results[] = $row;
    }
}

/*
|--------------------------------------------------------------------------
| FILTER RESULTS
|--------------------------------------------------------------------------
*/
$filtered_results = [];

foreach ($results as $row) {

    /*
    | Search
    */
    if ($search !== '') {

        $needle = strtolower($search);

        $haystack = strtolower(
            ($row['full_name'] ?? '') . ' ' .
            ($row['username'] ?? '') . ' ' .
            ($row['email'] ?? '') . ' ' .
            ($row['category_name'] ?? '') . ' ' .
            ($row['activity_name'] ?? '')
        );

        if (strpos($haystack, $needle) === false) {
            continue;
        }
    }

    /*
    | Percentage
    */
    $percentage = (float)$row['percentage'];

    /*
    | Status
    */
    if (
        $status_filter === 'excellent' &&
        $percentage < 90
    ) {
        continue;
    }

    if (
        $status_filter === 'passed' &&
        $percentage < 75
    ) {
        continue;
    }

    if (
        $status_filter === 'failed' &&
        $percentage >= 75
    ) {
        continue;
    }

    /*
    | Category
    */
    if (
        $category_filter !== '' &&
        (string)$row['category_id'] !==
        (string)$category_filter
    ) {
        continue;
    }

    /*
    | User
    */
    if (
        $user_filter &&
        (int)$row['user_id'] !== $user_filter
    ) {
        continue;
    }

    /*
    | Activity
    */
    if (
        $activity_type !== 'all' &&
        $row['result_type'] !== $activity_type
    ) {
        continue;
    }

    $filtered_results[] = $row;
}

/*
|--------------------------------------------------------------------------
| PAGINATION
|--------------------------------------------------------------------------
*/
$total_results = count($filtered_results);

$total_pages = max(
    1,
    (int)ceil($total_results / $limit)
);

if ($page > $total_pages) {
    $page = $total_pages;
}

$offset = ($page - 1) * $limit;

$paginated_results = array_slice(
    $filtered_results,
    $offset,
    $limit
);

/*
|--------------------------------------------------------------------------
| STATISTICS
|--------------------------------------------------------------------------
*/
$stats = [
    'total'        => $total_results,
    'passed'       => 0,
    'failed'       => 0,
    'excellent'    => 0,
    'average'      => 0,
    'unique_users' => 0
];

$total_percentage = 0;
$unique_users = [];

foreach ($filtered_results as $row) {

    $pct = (float)$row['percentage'];

    $total_percentage += $pct;

    if ($pct >= 90) {
        $stats['excellent']++;
    } elseif ($pct >= 75) {
        $stats['passed']++;
    } else {
        $stats['failed']++;
    }

    $unique_users[(int)$row['user_id']] = true;
}

$stats['average'] =
    $stats['total']
        ? round(
            $total_percentage /
            $stats['total'],
            1
        )
        : 0;

$stats['unique_users'] =
    count($unique_users);

/*
|--------------------------------------------------------------------------
| USERS
|--------------------------------------------------------------------------
*/
$users_list = [];

$users_result = $conn->query(
    "SELECT id, username, full_name
     FROM users
     ORDER BY username"
);

if ($users_result) {

    while ($row = $users_result->fetch_assoc()) {
        $users_list[] = $row;
    }
}

/*
|--------------------------------------------------------------------------
| CATEGORIES
|--------------------------------------------------------------------------
*/
$categories_list = [];

$categories_result = $conn->query(
    "SELECT id, name
     FROM categories
     ORDER BY name"
);

if ($categories_result) {

    while ($row = $categories_result->fetch_assoc()) {
        $categories_list[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0, viewport-fit=cover"
>

<meta
    name="theme-color"
    content="#0a0f1e"
>

<title>View Results | CSS TESDA Admin</title>

<link
    href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&family=Rajdhani:wght@300;400;600&display=swap"
    rel="stylesheet"
>

<link
    rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css"
>

<script>
(function () {

    try {

        var savedTheme =
            localStorage.getItem('theme') || 'dark';

        if (savedTheme === 'light') {

            document.documentElement.setAttribute(
                'data-theme',
                'light'
            );
        }

    } catch (e) {}

})();
</script>

<style>
:root{
 --bg-primary:#0a0f1e;--bg-secondary:#050812;--bg-sidebar:#0b1428;--bg-card:rgba(0,0,0,.3);--bg-card-hover:rgba(0,0,0,.4);--bg-input:rgba(0,0,0,.4);--bg-pagination:rgba(0,0,0,.3);
 --text-primary:#e0e0ff;--text-secondary:#8899cc;--text-muted:#94a3b8;--text-dark:#050812;
 --border-color:rgba(0,255,157,.2);--border-light:rgba(0,255,157,.1);--border-strong:rgba(0,255,157,.3);
 --cyber-bg1:#0a0f1e;--cyber-bg2:#050812;--grid-color:rgba(0,255,157,.03);--accent:#00ff9d;--accent-blue:#0066ff;
 --scrollbar-track:#1a1a2e;--scrollbar-thumb:#00ff9d;
}
[data-theme="light"]{
 --bg-primary:#d5dce6;--bg-secondary:#c8d0dd;--bg-sidebar:#f0f4f8;--bg-card:rgba(255,255,255,.92);--bg-card-hover:rgba(255,255,255,.98);--bg-input:rgba(255,255,255,.95);--bg-pagination:rgba(255,255,255,.9);
 --text-primary:#1a2332;--text-secondary:#3d4a5e;--text-muted:#5a6b7e;--text-dark:#1a2332;
 --border-color:rgba(0,160,100,.35);--border-light:rgba(0,160,100,.2);--border-strong:rgba(0,160,100,.5);
 --cyber-bg1:#d5dce6;--cyber-bg2:#c8d0dd;--grid-color:rgba(0,160,100,.06);--accent:#00aa77;--accent-blue:#0066cc;
 --scrollbar-track:#c8d0dd;--scrollbar-thumb:#00aa77;
}
*{margin:0;padding:0;box-sizing:border-box}
html,body{width:100%;min-height:100%;overflow-x:hidden;-webkit-overflow-scrolling:touch}
body{font-family:'Rajdhani',sans-serif;background:var(--bg-primary);color:var(--text-primary);transition:background .3s ease,color .3s ease}
body.menu-open{overflow:hidden}
.cyber-bg{position:fixed;inset:0;z-index:-2;background:linear-gradient(135deg,var(--cyber-bg1),var(--cyber-bg2));pointer-events:none}
.grid-overlay{position:fixed;inset:0;background-image:linear-gradient(var(--grid-color) 1px,transparent 1px),linear-gradient(90deg,var(--grid-color) 1px,transparent 1px);background-size:50px 50px;z-index:-1;animation:gridMove 20s linear infinite;pointer-events:none}
@keyframes gridMove{0%{transform:translate(0,0)}100%{transform:translate(50px,50px)}}
.dashboard-container{display:flex;min-height:100vh}
.sidebar{width:280px;background:var(--bg-sidebar);border-right:1px solid var(--border-color);padding:2rem 1.5rem;position:fixed;top:0;left:0;bottom:0;height:100%;overflow-y:auto;-webkit-overflow-scrolling:touch;z-index:1000;transition:transform .3s ease,background .3s ease,border .3s ease}
.logo{font-family:'Orbitron',sans-serif;font-size:1.4rem;font-weight:800;background:linear-gradient(135deg,var(--accent),var(--accent-blue));-webkit-background-clip:text;-webkit-text-fill-color:transparent;margin-bottom:2rem;text-align:center}
.sidebar-close{display:none;position:absolute;top:12px;right:12px;width:38px;height:38px;border:0;background:transparent;color:var(--text-secondary);font-size:1.6rem;line-height:1;cursor:pointer;z-index:1002}
.nav-menu{list-style:none}.nav-item{margin-bottom:.5rem}
.nav-link{position:relative;z-index:1001;display:flex;align-items:center;gap:12px;padding:12px 16px;color:var(--text-muted);text-decoration:none;border-radius:12px;transition:all .3s;cursor:pointer;touch-action:manipulation;-webkit-tap-highlight-color:transparent}
.nav-link:hover,.nav-link.active{background:rgba(0,255,157,.1);color:var(--accent)}.nav-link i{width:24px;text-align:center}
.theme-toggle-btn{display:flex;align-items:center;gap:12px;padding:12px 15px;background:rgba(0,102,255,.08);border:1px solid rgba(0,102,255,.25);border-radius:10px;margin-bottom:1.5rem;cursor:pointer;transition:all .3s;user-select:none;touch-action:manipulation}
.theme-toggle-btn:hover{background:rgba(0,102,255,.15);border-color:var(--accent-blue);transform:scale(1.02)}.theme-toggle-btn:active{transform:scale(.97)}
.theme-toggle-btn i{color:var(--accent-blue);font-size:1.2rem;width:24px;text-align:center}.theme-toggle-btn .theme-text{color:var(--text-secondary);font-size:.9rem;flex:1}.theme-toggle-btn .theme-badge{font-family:'Orbitron',sans-serif;font-size:.75rem;color:var(--accent-blue);background:rgba(0,102,255,.1);padding:2px 10px;border-radius:20px}
.brightness-box{padding:12px 15px;background:rgba(255,170,0,.08);border:1px solid rgba(255,170,0,.3);border-radius:10px;margin-bottom:1.5rem}.brightness-head{display:flex;align-items:center;gap:12px;margin-bottom:10px}.brightness-head i{color:#f59e0b;font-size:1.2rem;width:24px;text-align:center}.brightness-head .brightness-text{color:var(--text-secondary);font-size:.9rem;flex:1}.brightness-head .brightness-badge{font-family:'Orbitron',sans-serif;font-size:.75rem;color:#f59e0b;background:rgba(255,170,0,.12);padding:2px 10px;border-radius:20px;min-width:54px;text-align:center}
.brightness-slider{-webkit-appearance:none;appearance:none;width:100%;height:6px;border-radius:6px;background:rgba(255,170,0,.3);outline:none;cursor:pointer;touch-action:pan-x}.brightness-slider::-webkit-slider-thumb{-webkit-appearance:none;appearance:none;width:22px;height:22px;border-radius:50%;background:#f59e0b;border:2px solid #fff;cursor:pointer}.brightness-slider::-moz-range-thumb{width:22px;height:22px;border-radius:50%;background:#f59e0b;border:2px solid #fff;cursor:pointer}.brightness-reset{margin-top:8px;width:100%;padding:6px;border:1px solid rgba(255,170,0,.4);border-radius:8px;background:transparent;color:#f59e0b;font-family:'Rajdhani',sans-serif;font-weight:600;cursor:pointer;touch-action:manipulation}
.mobile-topbar{display:none;position:fixed;top:0;left:0;right:0;min-height:56px;padding:0 12px;padding-top:env(safe-area-inset-top,0px);background:var(--bg-sidebar);border-bottom:1px solid var(--border-color);align-items:center;gap:12px;z-index:900}
.menu-btn{width:42px;height:42px;border:1px solid var(--border-strong);border-radius:10px;background:rgba(0,255,157,.08);color:var(--accent);font-size:1.2rem;cursor:pointer;touch-action:manipulation;-webkit-tap-highlight-color:transparent}
.mobile-title{font-family:'Orbitron',sans-serif;font-size:1rem;font-weight:800;background:linear-gradient(135deg,var(--accent),var(--accent-blue));-webkit-background-clip:text;-webkit-text-fill-color:transparent}
.sidebar-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.6);z-index:950;pointer-events:auto}.sidebar-overlay.show{display:block}
.main-content{flex:1;margin-left:280px;padding:2rem;min-width:0}
.page-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:2rem;flex-wrap:wrap;gap:1rem}.page-header h1{font-family:'Orbitron',sans-serif;font-size:1.8rem;background:linear-gradient(135deg,var(--accent),var(--accent-blue));-webkit-background-clip:text;-webkit-text-fill-color:transparent}
.btn{padding:10px 24px;border:none;border-radius:50px;font-family:'Orbitron',sans-serif;font-weight:600;cursor:pointer;transition:all .3s;text-decoration:none;display:inline-flex;align-items:center;gap:8px;touch-action:manipulation}.btn-primary{background:linear-gradient(45deg,var(--accent),var(--accent-blue));color:var(--text-dark)}.btn-primary:hover{transform:translateY(-2px);box-shadow:0 5px 15px rgba(0,255,157,.4)}.btn-secondary{background:transparent;color:var(--accent);border:1px solid var(--accent)}.btn-secondary:hover{background:rgba(0,255,157,.1)}.btn-sm{padding:5px 12px;font-size:.75rem}
.stats-grid{display:grid;grid-template-columns:repeat(6,1fr);gap:1rem;margin-bottom:2rem}.stat-card{background:var(--bg-card);border:1px solid var(--border-color);border-radius:16px;padding:1rem;text-align:center;transition:all .3s}.stat-card:hover{background:var(--bg-card-hover);transform:translateY(-2px)}.stat-number{font-family:'Orbitron',sans-serif;font-size:1.6rem;color:var(--accent);margin-bottom:5px}.stat-label{font-size:.75rem;color:var(--text-secondary);line-height:1.2}
.filter-card,.table-card{background:var(--bg-card);border:1px solid var(--border-color);border-radius:20px;transition:background .3s ease,border .3s ease}.filter-card{padding:1.5rem;margin-bottom:2rem}.filter-form{display:flex;flex-wrap:wrap;gap:1rem;align-items:flex-end}.filter-group{flex:1;min-width:160px}.filter-group label{display:block;margin-bottom:5px;font-size:.8rem;color:var(--text-secondary)}.filter-group input,.filter-group select{width:100%;padding:8px 12px;background:var(--bg-input);border:1px solid var(--border-strong);border-radius:10px;color:var(--text-primary);font-family:'Rajdhani',sans-serif;min-height:38px}.filter-group select option{background:var(--bg-sidebar);color:var(--text-primary)}
.table-card{overflow:hidden}.table-container{overflow-x:auto;-webkit-overflow-scrolling:touch;touch-action:pan-x pan-y}table{width:100%;border-collapse:collapse;min-width:950px}th,td{padding:14px 16px;text-align:left;border-bottom:1px solid var(--border-light)}th{background:rgba(0,255,157,.05);font-family:'Orbitron',sans-serif;font-weight:600;color:var(--accent);white-space:nowrap}tr:hover{background:rgba(0,255,157,.02)}
.badge{padding:4px 12px;border-radius:50px;font-size:.7rem;font-weight:600;white-space:nowrap}.badge-active{background:rgba(0,255,157,.2);color:var(--accent)}.badge-inactive{background:rgba(220,38,38,.2);color:#ef4444}.badge-coc{background:rgba(59,130,246,.2);color:#60a5fa}.badge-excellent{background:rgba(0,255,157,.2);color:var(--accent)}.badge-passed{background:rgba(59,130,246,.2);color:#60a5fa}.badge-failed{background:rgba(220,38,38,.2);color:#ef4444}
.progress-bar{width:90px;height:7px;background:rgba(128,128,128,.2);border-radius:10px;overflow:hidden}.progress-fill{height:100%;border-radius:10px;background:var(--accent)}.progress-fill.passed{background:#60a5fa}.progress-fill.failed{background:#ef4444}.progress-fill.excellent{background:var(--accent)}.action-buttons{display:flex;gap:8px}
.pagination{display:flex;justify-content:center;flex-wrap:wrap;gap:8px;margin-top:1.5rem;padding-bottom:.5rem}.pagination a,.pagination span{padding:8px 14px;background:var(--bg-pagination);border-radius:10px;text-decoration:none;color:var(--text-primary);transition:all .3s}.pagination a:hover,.pagination .active{background:var(--accent);color:var(--text-dark)}
::-webkit-scrollbar{width:10px;height:10px}::-webkit-scrollbar-track{background:var(--scrollbar-track);border-radius:10px}::-webkit-scrollbar-thumb{background:var(--scrollbar-thumb);border-radius:10px}
@media print{.sidebar,.mobile-topbar,.sidebar-overlay,.filter-card,.page-header button,.pagination{display:none!important}.main-content{margin-left:0;padding:0}.table-card{border:0}.cyber-bg,.grid-overlay{display:none}}
@media(max-width:1100px){.stats-grid{grid-template-columns:repeat(3,1fr)}}
@media(max-width:900px){.mobile-topbar{display:flex}.sidebar{width:270px;max-width:82vw;transform:translateX(-100%);box-shadow:none;padding-top:calc(3.5rem + env(safe-area-inset-top,0px));will-change:transform}.sidebar.open{transform:translateX(0);box-shadow:8px 0 40px rgba(0,0,0,.5)}.sidebar-close{display:block}.main-content{margin-left:0;padding:1rem;padding-top:calc(56px + env(safe-area-inset-top,0px) + 1rem);padding-bottom:5rem}.page-header h1{font-size:1.3rem}.filter-group{min-width:100%;width:100%}.filter-form{flex-direction:column;align-items:stretch}.filter-group .btn{width:100%;margin-bottom:6px}.stats-grid{grid-template-columns:repeat(3,1fr);gap:.6rem}.stat-number{font-size:1.4rem}.stat-label{font-size:.7rem}.table-card{border-radius:14px}th,td{padding:12px}}
@media(max-width:480px){.main-content{padding-left:10px;padding-right:10px}.stats-grid{grid-template-columns:repeat(2,1fr)}.page-header{align-items:stretch}.page-header .btn{width:100%;justify-content:center}.stat-number{font-size:1.25rem}}
</style>

</head>

<body>

<div class="cyber-bg"></div>

<div class="grid-overlay"></div>


<!-- =========================================================
     MOBILE TOP BAR
========================================================= -->

<div class="mobile-topbar">

    <button
        type="button"
        class="menu-btn"
        id="menuBtn"
        aria-label="Open menu"
    >
        <i class="fas fa-bars"></i>
    </button>

    <span class="mobile-title">
        CSS Admin
    </span>

</div>


<div
    class="sidebar-overlay"
    id="sidebarOverlay"
></div>


<div class="dashboard-container">


<!-- =========================================================
     SIDEBAR
========================================================= -->

<aside
    class="sidebar"
    id="sidebar"
>

    <button
        type="button"
        class="sidebar-close"
        id="sidebarClose"
        aria-label="Close menu"
    >
        &times;
    </button>

    <div class="logo">
        CSS Admin
    </div>

    <!-- Theme Toggle Button -->
    <div class="theme-toggle-btn" id="themeToggle" title="Toggle dark/light mode">
        <i class="fas fa-moon" id="themeIcon"></i>
        <span class="theme-text">Theme</span>
        <span class="theme-badge" id="themeBadge">Dark</span>
    </div>

    <!-- Brightness Control -->
    <div class="brightness-box">
        <div class="brightness-head">
            <i class="fas fa-sun"></i>
            <span class="brightness-text">Brightness</span>
            <span class="brightness-badge" id="brightnessBadge">100%</span>
        </div>
        <input type="range" class="brightness-slider" id="brightnessSlider" min="40" max="120" step="5" value="100" aria-label="Screen brightness">
        <button type="button" class="brightness-reset" id="brightnessReset"><i class="fas fa-undo"></i> Reset</button>
    </div>

    <ul class="nav-menu">

        <li class="nav-item"><a href="/dashboard.php" class="nav-link"><i class="fas fa-tachometer-alt"></i><span>Dashboard</span></a></li>
        <li class="nav-item"><a href="<?php echo $base; ?>/admin/manage_questions.php" class="nav-link"><i class="fas fa-question-circle"></i><span>Questions</span></a></li>
        <li class="nav-item"><a href="<?php echo $base; ?>/admin/manage_users.php" class="nav-link"><i class="fas fa-users"></i><span>Users</span></a></li>
        <li class="nav-item"><a href="<?php echo $base; ?>/admin/view_results.php" class="nav-link active"><i class="fas fa-chart-line"></i><span>View Results</span></a></li>
        <li class="nav-item"><a href="<?php echo $base; ?>/logout.php" class="nav-link"><i class="fas fa-sign-out-alt"></i><span>Logout</span></a></li>

    </ul>

</aside>


<!-- =========================================================
     MAIN
========================================================= -->

<main class="main-content">


    <!-- HEADER -->

    <div class="page-header">

        <h1>

            <i class="fas fa-chart-line"></i>

            All Results

        </h1>


        <button
            type="button"
            onclick="window.print()"
            class="btn btn-secondary"
        >

            <i class="fas fa-print"></i>

            Print Report

        </button>

    </div>


    <!-- =====================================================
         STATISTICS
    ====================================================== -->

    <div class="stats-grid">

        <div class="stat-card">

            <div class="stat-number">
                <?php echo $stats['total']; ?>
            </div>

            <div class="stat-label">
                Total Activities
            </div>

        </div>


        <div class="stat-card">

            <div class="stat-number">
                <?php echo $stats['excellent']; ?>
            </div>

            <div class="stat-label">
                Excellent (90%+)
            </div>

        </div>


        <div class="stat-card">

            <div class="stat-number">
                <?php echo $stats['passed']; ?>
            </div>

            <div class="stat-label">
                Passed (75%+)
            </div>

        </div>


        <div class="stat-card">

            <div class="stat-number">
                <?php echo $stats['failed']; ?>
            </div>

            <div class="stat-label">
                Failed (&lt;75%)
            </div>

        </div>


        <div class="stat-card">

            <div class="stat-number">
                <?php echo $stats['average']; ?>%
            </div>

            <div class="stat-label">
                Average Score
            </div>

        </div>


        <div class="stat-card">

            <div class="stat-number">
                <?php echo $stats['unique_users']; ?>
            </div>

            <div class="stat-label">
                Active Users
            </div>

        </div>

    </div>


    <!-- =====================================================
         FILTERS
    ====================================================== -->

    <div class="filter-card">

        <form
            method="GET"
            class="filter-form"
        >


            <div class="filter-group">

                <label>
                    <i class="fas fa-search"></i>
                    Search
                </label>

                <input
                    type="text"
                    name="search"
                    placeholder="Username, name, email..."
                    value="<?php echo htmlspecialchars(
                        $search,
                        ENT_QUOTES,
                        'UTF-8'
                    ); ?>"
                >

            </div>


            <div class="filter-group">

                <label>
                    <i class="fas fa-filter"></i>
                    Status
                </label>

                <select name="status">

                    <option value="">
                        All
                    </option>

                    <option
                        value="excellent"
                        <?php echo
                            $status_filter === 'excellent'
                                ? 'selected'
                                : '';
                        ?>
                    >
                        Excellent (90%+)
                    </option>

                    <option
                        value="passed"
                        <?php echo
                            $status_filter === 'passed'
                                ? 'selected'
                                : '';
                        ?>
                    >
                        Passed (75%+)
                    </option>

                    <option
                        value="failed"
                        <?php echo
                            $status_filter === 'failed'
                                ? 'selected'
                                : '';
                        ?>
                    >
                        Failed (&lt;75%)
                    </option>

                </select>

            </div>


            <div class="filter-group">

                <label>
                    <i class="fas fa-layer-group"></i>
                    Category
                </label>

                <select name="category">

                    <option value="">
                        All Categories
                    </option>

                    <?php foreach (
                        $categories_list as $cat
                    ): ?>

                        <option
                            value="<?php echo (int)$cat['id']; ?>"
                            <?php echo
                                $category_filter ==
                                $cat['id']
                                    ? 'selected'
                                    : '';
                            ?>
                        >
                            <?php echo htmlspecialchars(
                                $cat['name'],
                                ENT_QUOTES,
                                'UTF-8'
                            ); ?>
                        </option>

                    <?php endforeach; ?>

                </select>

            </div>


            <div class="filter-group">

                <label>
                    <i class="fas fa-user"></i>
                    User
                </label>

                <select name="user_id">

                    <option value="0">
                        All Users
                    </option>

                    <?php foreach (
                        $users_list as $user
                    ): ?>

                        <option
                            value="<?php echo (int)$user['id']; ?>"
                            <?php echo
                                $user_filter ==
                                $user['id']
                                    ? 'selected'
                                    : '';
                            ?>
                        >
                            <?php echo htmlspecialchars(
                                $user['full_name'],
                                ENT_QUOTES,
                                'UTF-8'
                            ); ?>
                        </option>

                    <?php endforeach; ?>

                </select>

            </div>


            <div class="filter-group">

                <label>
                    <i class="fas fa-tag"></i>
                    Activity Type
                </label>

                <select name="activity_type">

                    <option value="all">
                        All Activities
                    </option>

                    <option
                        value="quiz"
                        <?php echo
                            $activity_type === 'quiz'
                                ? 'selected'
                                : '';
                        ?>
                    >
                        📝 Quizzes
                    </option>

                    <option
                        value="coc1_game"
                        <?php echo
                            $activity_type === 'coc1_game'
                                ? 'selected'
                                : '';
                        ?>
                    >
                        🎮 COC 1 Game
                    </option>

                    <option
                        value="coc2_game"
                        <?php echo
                            $activity_type === 'coc2_game'
                                ? 'selected'
                                : '';
                        ?>
                    >
                        🔌 COC 2 Game
                    </option>

                    <option
                        value="coc3_game"
                        <?php echo
                            $activity_type === 'coc3_game'
                                ? 'selected'
                                : '';
                        ?>
                    >
                        🎲 COC 3 Game
                    </option>

                    <option
                        value="coc4_game"
                        <?php echo
                            $activity_type === 'coc4_game'
                                ? 'selected'
                                : '';
                        ?>
                    >
                        💀 COC 4 Game
                    </option>

                    <option
                        value="installation_sim"
                        <?php echo
                            $activity_type === 'installation_sim'
                                ? 'selected'
                                : '';
                        ?>
                    >
                        🔧 Installation Sim
                    </option>

                    <option
                        value="os_install_sim"
                        <?php echo
                            $activity_type === 'os_install_sim'
                                ? 'selected'
                                : '';
                        ?>
                    >
                        💿 OS Installation
                    </option>

                    <option
                        value="troubleshooting_sim"
                        <?php echo
                            $activity_type ===
                            'troubleshooting_sim'
                                ? 'selected'
                                : '';
                        ?>
                    >
                        🛠️ Troubleshooting
                    </option>

                </select>

            </div>


            <div class="filter-group">

                <label>
                    &nbsp;
                </label>

                <button
                    type="submit"
                    class="btn btn-primary btn-sm"
                >

                    <i class="fas fa-filter"></i>

                    Filter

                </button>


                <a
                    href="<?php echo $base; ?>/admin/view_results.php"
                    class="btn btn-warning btn-sm"
                >

                    <i class="fas fa-undo"></i>

                    Reset

                </a>

            </div>

        </form>

    </div>


    <!-- =====================================================
         RESULTS TABLE
    ====================================================== -->

    <div class="table-card">

        <div class="table-container">

            <table>

                <thead>

                    <tr>

                        <th>ID</th>

                        <th>User</th>

                        <th>Activity</th>

                        <th>Category</th>

                        <th>Score</th>

                        <th>Percentage</th>

                        <th>Status</th>

                        <th>Completed</th>

                        <th>Actions</th>

                    </tr>

                </thead>


                <tbody>


                <?php if (
                    !empty($paginated_results)
                ): ?>


                    <?php foreach (
                        $paginated_results as $row
                    ): ?>

                        <?php

                        $percentage = round(
                            (float)$row['percentage']
                        );

                        $progress_class =
                            $percentage >= 75
                                ? 'high'
                                : (
                                    $percentage >= 50
                                        ? 'medium'
                                        : 'low'
                                );

                        $status_class =
                            $percentage >= 90
                                ? 'excellent'
                                : (
                                    $percentage >= 75
                                        ? 'passed'
                                        : 'failed'
                                );

                        $status_text =
                            ucfirst($status_class);

                        $type_class =
                            'type-' .
                            $row['result_type'];

                        ?>


                        <tr>

                            <!-- ID -->

                            <td>
                                <?php echo (int)$row['id']; ?>
                            </td>


                            <!-- USER -->

                            <td>

                                <strong>
                                    <?php echo htmlspecialchars(
                                        $row['full_name'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ); ?>
                                </strong>

                                <br>

                                <small>
                                    <?php echo htmlspecialchars(
                                        $row['username'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ); ?>
                                </small>

                            </td>


                            <!-- ACTIVITY -->

                            <td>

                                <?php echo
                                    $row['type_icon'] .
                                    ' ' .
                                    htmlspecialchars(
                                        $row['activity_name'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    );
                                ?>

                                <span
                                    class="type-badge <?php echo
                                        htmlspecialchars(
                                            $type_class,
                                            ENT_QUOTES,
                                            'UTF-8'
                                        );
                                    ?>"
                                >

                                    <?php echo htmlspecialchars(
                                        str_replace(
                                            '_',
                                            ' ',
                                            $row['result_type']
                                        ),
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ); ?>

                                </span>

                            </td>


                            <!-- CATEGORY -->

                            <td>
                                <?php echo htmlspecialchars(
                                    $row['category_name'],
                                    ENT_QUOTES,
                                    'UTF-8'
                                ); ?>
                            </td>


                            <!-- SCORE -->

                            <td>

                                <?php

                                if (
                                    $row['total_questions']
                                    !== null &&
                                    $row['total_questions']
                                    !== ''
                                ) {

                                    echo
                                        htmlspecialchars(
                                            $row['raw_score'],
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) .
                                        '/' .
                                        htmlspecialchars(
                                            $row['max_score'],
                                            ENT_QUOTES,
                                            'UTF-8'
                                        );

                                } else {

                                    echo
                                        htmlspecialchars(
                                            $row['raw_score'],
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) .
                                        ' pts';
                                }

                                ?>

                            </td>


                            <!-- PERCENTAGE -->

                            <td>

                                <div
                                    style="
                                        display:flex;
                                        align-items:center;
                                        gap:8px;
                                    "
                                >

                                    <span>
                                        <?php echo
                                            $percentage;
                                        ?>%
                                    </span>

                                    <div
                                        class="progress-bar"
                                    >

                                        <div
                                            class="progress-fill <?php echo
                                                $progress_class;
                                            ?>"
                                            style="width:<?php echo
                                                max(
                                                    0,
                                                    min(
                                                        100,
                                                        $percentage
                                                    )
                                                );
                                            ?>%;"
                                        ></div>

                                    </div>

                                </div>

                            </td>


                            <!-- STATUS -->

                            <td>

                                <span
                                    class="badge badge-<?php echo
                                        $status_class;
                                    ?>"
                                >
                                    <?php echo
                                        $status_text;
                                    ?>
                                </span>

                            </td>


                            <!-- DATE -->

                            <td>

                                <?php

                                echo !empty(
                                    $row['completed_at']
                                )
                                    ? date(
                                        'M d, Y h:i A',
                                        strtotime(
                                            $row['completed_at']
                                        )
                                    )
                                    : 'N/A';

                                ?>

                            </td>


                            <!-- ACTION -->

                            <td class="action-buttons">

                                <a
                                    href="<?php echo $base; ?>/admin/view_result_details.php?id=<?php echo (int)$row['id']; ?>&type=<?php echo urlencode($row['result_type']); ?>"
                                    class="btn btn-primary btn-sm"
                                    title="View Details"
                                    aria-label="View Details"
                                >

                                    <i
                                        class="fas fa-eye"
                                    ></i>

                                </a>

                            </td>

                        </tr>

                    <?php endforeach; ?>


                <?php else: ?>


                    <tr>

                        <td
                            colspan="9"
                            style="
                                text-align:center;
                                padding:40px;
                            "
                        >

                            <i
                                class="fas fa-inbox"
                                style="
                                    font-size:48px;
                                    opacity:.5;
                                    display:block;
                                    margin-bottom:12px;
                                "
                            ></i>

                            <p>
                                No results found.
                                Play some games or
                                take quizzes!
                            </p>

                        </td>

                    </tr>


                <?php endif; ?>


                </tbody>

            </table>

        </div>

    </div>


    <!-- =====================================================
         PAGINATION
    ====================================================== -->

    <?php if ($total_pages > 1): ?>

        <div class="pagination">

            <?php if ($page > 1): ?>

                <a
                    href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>&category=<?php echo urlencode($category_filter); ?>&user_id=<?php echo $user_filter; ?>&activity_type=<?php echo urlencode($activity_type); ?>"
                >
                    &laquo; Prev
                </a>

            <?php endif; ?>


            <?php

            $start_page =
                max(1, $page - 2);

            $end_page =
                min(
                    $total_pages,
                    $page + 2
                );

            ?>


            <?php for (
                $i = $start_page;
                $i <= $end_page;
                $i++
            ): ?>

                <a
                    href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>&category=<?php echo urlencode($category_filter); ?>&user_id=<?php echo $user_filter; ?>&activity_type=<?php echo urlencode($activity_type); ?>"
                    class="<?php echo
                        $i == $page
                            ? 'active'
                            : '';
                    ?>"
                >
                    <?php echo $i; ?>
                </a>

            <?php endfor; ?>


            <?php if (
                $page < $total_pages
            ): ?>

                <a
                    href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>&category=<?php echo urlencode($category_filter); ?>&user_id=<?php echo $user_filter; ?>&activity_type=<?php echo urlencode($activity_type); ?>"
                >
                    Next &raquo;
                </a>

            <?php endif; ?>

        </div>

    <?php endif; ?>


    <!-- RESULTS COUNT -->

    <div
        style="
            margin-top:1rem;
            margin-bottom:2rem;
            padding-bottom:2rem;
            text-align:right;
            color:var(--text-muted);
        "
    >

        <small>

            Showing
            <?php echo count(
                $paginated_results
            ); ?>

            of

            <?php echo $total_results; ?>

            results

        </small>

    </div>


</main>

</div>


<!-- =========================================================
     JAVASCRIPT
========================================================= -->

<script>

/* =========================================================
   MOBILE SIDEBAR
   ========================================================= */

(function () {
    'use strict';

    var sidebar = document.getElementById('sidebar');
    var overlay = document.getElementById('sidebarOverlay');
    var menuBtn = document.getElementById('menuBtn');
    var closeBtn = document.getElementById('sidebarClose');

    if (!sidebar || !overlay) return;

    function openMenu() {
        sidebar.classList.add('open');
        overlay.classList.add('show');
        document.body.classList.add('menu-open');

        if (menuBtn) {
            menuBtn.setAttribute('aria-expanded', 'true');
        }
    }

    function closeMenu() {
        sidebar.classList.remove('open');
        overlay.classList.remove('show');
        document.body.classList.remove('menu-open');

        if (menuBtn) {
            menuBtn.setAttribute('aria-expanded', 'false');
        }
    }

    if (menuBtn) {
        menuBtn.setAttribute('aria-expanded', 'false');
        menuBtn.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();

            if (sidebar.classList.contains('open')) {
                closeMenu();
            } else {
                openMenu();
            }
        });
    }

    if (closeBtn) {
        closeBtn.addEventListener('click', function (e) {
            e.preventDefault();
            closeMenu();
        });
    }

    overlay.addEventListener('click', closeMenu);

    sidebar.querySelectorAll('.nav-link').forEach(function (link) {
        link.addEventListener('click', function () {
            closeMenu();
        });
    });

    window.addEventListener('resize', function () {
        if (window.innerWidth > 900) {
            closeMenu();
        }
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            closeMenu();

            var panel = document.getElementById('brightnessPanel');
            if (panel) {
                panel.classList.remove('active');
            }
        }
    });
})();


/* =========================================================
   DARK / LIGHT MODE
========================================================= */
(function(){
    var toggle=document.getElementById('themeToggle');
    var icon=document.getElementById('themeIcon');
    var badge=document.getElementById('themeBadge');
    function applyTheme(theme){
        if(theme==='light'){
            document.documentElement.setAttribute('data-theme','light');
            icon.className='fas fa-sun'; badge.textContent='Light';
        }else{
            document.documentElement.removeAttribute('data-theme');
            icon.className='fas fa-moon'; badge.textContent='Dark';
        }
        try{localStorage.setItem('theme',theme);}catch(e){}
    }
    var saved='dark'; try{saved=localStorage.getItem('theme')||'dark';}catch(e){}
    applyTheme(saved);
    toggle.addEventListener('click',function(){
        var current='dark'; try{current=localStorage.getItem('theme')||'dark';}catch(e){}
        applyTheme(current==='dark'?'light':'dark');
    });
})();

/* =========================================================
   BRIGHTNESS - WEBVIEW SAFE OVERLAY
========================================================= */
(function(){
    var slider=document.getElementById('brightnessSlider');
    var badge=document.getElementById('brightnessBadge');
    var reset=document.getElementById('brightnessReset');
    var dim=document.createElement('div');
    var glow=document.createElement('div');
    dim.style.cssText='position:fixed;inset:0;background:#000;pointer-events:none;z-index:2147482000;opacity:0;transition:opacity .15s ease';
    glow.style.cssText='position:fixed;inset:0;background:#fff;pointer-events:none;z-index:2147482000;opacity:0;transition:opacity .15s ease';
    document.body.appendChild(dim); document.body.appendChild(glow);
    function apply(value){
        value=parseInt(value,10); if(isNaN(value)) value=100; value=Math.min(120,Math.max(40,value));
        if(value<100){dim.style.opacity=(100-value)/100;glow.style.opacity=0}else{dim.style.opacity=0;glow.style.opacity=((value-100)/100)*.5}
        slider.value=value; badge.textContent=value+'%';
        try{localStorage.setItem('css_tesda_brightness',value)}catch(e){}
    }
    var saved=100; try{saved=parseInt(localStorage.getItem('css_tesda_brightness')||'100',10)}catch(e){}
    apply(saved);
    slider.addEventListener('input',function(){apply(slider.value)});
    reset.addEventListener('click',function(){apply(100)});
})();

/* =========================================================
   ANDROID / MOBILE SCROLL PROTECTION
========================================================= */

(function () {

    /*
    | Make sure page is scrollable after load.
    */
    function enablePageScroll() {

        document.documentElement.style
            .overflowY = 'auto';

        document.body.style
            .overflowY = 'auto';

    }


    if (
        document.readyState ===
        'loading'
    ) {

        document.addEventListener(
            'DOMContentLoaded',
            enablePageScroll
        );

    } else {

        enablePageScroll();
    }


    /*
    | Restore scrolling when returning
    | from another page/app state.
    */
    window.addEventListener(
        'pageshow',
        enablePageScroll
    );


    window.addEventListener(
        'orientationchange',
        function () {

            setTimeout(
                enablePageScroll,
                150
            );

        }
    );

})();

</script>

</body>
</html>

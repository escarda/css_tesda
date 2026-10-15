<?php
// manage_users.php - root directory (same folder as config.php and dashboard.php)

require_once __DIR__ . '/config.php';

// Base path is detected automatically (domain root => '', /proj folder => '/proj')
$base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
if ($base === '.' || $base === '/') { $base = ''; }

// Check if admin is logged in
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: " . $base . "/login.php");
    exit();
}

// Handle user deletion
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);

    // Prevent admin from deleting themselves
    if ($id == $_SESSION['user_id']) {
        $_SESSION['message'] = "You cannot delete your own account!";
        $_SESSION['message_type'] = "danger";
    } else {
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            $_SESSION['message'] = "User deleted successfully!";
            $_SESSION['message_type'] = "success";
        } else {
            $_SESSION['message'] = "Error deleting user: " . $conn->error;
            $_SESSION['message_type'] = "danger";
        }
        $stmt->close();
    }
    header("Location: " . $base . "/manage_users.php");
    exit();
}

// Handle role change
if (isset($_GET['change_role']) && isset($_GET['role'])) {
    $id = intval($_GET['change_role']);
    $new_role = $_GET['role'] == 'admin' ? 'admin' : 'user';

    // Prevent admin from changing their own role away from admin
    if ($id == $_SESSION['user_id'] && $new_role != 'admin') {
        $_SESSION['message'] = "You cannot change your own admin role!";
        $_SESSION['message_type'] = "danger";
    } else {
        $stmt = $conn->prepare("UPDATE users SET role = ? WHERE id = ?");
        $stmt->bind_param("si", $new_role, $id);
        if ($stmt->execute()) {
            $_SESSION['message'] = "User role updated!";
            $_SESSION['message_type'] = "success";
        }
        $stmt->close();
    }
    header("Location: " . $base . "/manage_users.php");
    exit();
}

// Handle add/edit user form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
    $username = trim($_POST['username'] ?? '');
    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $role = ($_POST['role'] ?? 'user') === 'admin' ? 'admin' : 'user';
    $password = $_POST['password'] ?? '';

    if ($user_id > 0) {
        // Update existing user
        if (!empty($password)) {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET username = ?, full_name = ?, email = ?, role = ?, password = ? WHERE id = ?");
            $stmt->bind_param("sssssi", $username, $full_name, $email, $role, $hashed_password, $user_id);
        } else {
            $stmt = $conn->prepare("UPDATE users SET username = ?, full_name = ?, email = ?, role = ? WHERE id = ?");
            $stmt->bind_param("ssssi", $username, $full_name, $email, $role, $user_id);
        }

        if ($stmt->execute()) {
            $_SESSION['message'] = "User updated successfully!";
            $_SESSION['message_type'] = "success";
        } else {
            $_SESSION['message'] = "Error updating user: " . $conn->error;
            $_SESSION['message_type'] = "danger";
        }
        $stmt->close();
    } else {
        // Add new user
        if (empty($password)) {
            $_SESSION['message'] = "Password is required for new users.";
            $_SESSION['message_type'] = "danger";
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users (username, full_name, email, role, password) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sssss", $username, $full_name, $email, $role, $hashed_password);

            if ($stmt->execute()) {
                $_SESSION['message'] = "User added successfully!";
                $_SESSION['message_type'] = "success";
            } else {
                $_SESSION['message'] = "Error adding user: " . $conn->error;
                $_SESSION['message_type'] = "danger";
            }
            $stmt->close();
        }
    }
    header("Location: " . $base . "/manage_users.php");
    exit();
}

// Pagination and filtering
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = 15;
$offset = ($page - 1) * $limit;

$search = isset($_GET['search']) ? $_GET['search'] : '';
$role_filter = isset($_GET['role_filter']) ? $_GET['role_filter'] : '';

$where_clauses = [];
$params = [];
$types = "";

if (!empty($search)) {
    $where_clauses[] = "(username LIKE ? OR full_name LIKE ? OR email LIKE ?)";
    $search_param = "%$search%";
    for ($i = 0; $i < 3; $i++) {
        $params[] = $search_param;
        $types .= "s";
    }
}
if (!empty($role_filter)) {
    $where_clauses[] = "role = ?";
    $params[] = $role_filter;
    $types .= "s";
}

$where_sql = !empty($where_clauses) ? "WHERE " . implode(" AND ", $where_clauses) : "";

// Get total count
$count_sql = "SELECT COUNT(*) as total FROM users $where_sql";
$count_stmt = $conn->prepare($count_sql);
if (!empty($params)) {
    $count_stmt->bind_param($types, ...$params);
}
$count_stmt->execute();
$total_users = $count_stmt->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_users / $limit);
$count_stmt->close();

// Get users
$sql = "SELECT id, username, full_name, email, role, created_at FROM users $where_sql ORDER BY id DESC LIMIT ? OFFSET ?";
$params[] = $limit;
$params[] = $offset;
$types .= "ii";
$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$users = $stmt->get_result();
$stmt->close();

// Get statistics
$stats = [];
$stats_result = $conn->query("SELECT COUNT(*) as total FROM users");
$stats['total'] = $stats_result->fetch_assoc()['total'];
$stats_result = $conn->query("SELECT COUNT(*) as total FROM users WHERE role = 'admin'");
$stats['admins'] = $stats_result->fetch_assoc()['total'];
$stats_result = $conn->query("SELECT COUNT(*) as total FROM users WHERE role = 'user'");
$stats['users'] = $stats_result->fetch_assoc()['total'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Manage Users | CSS TESDA Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&family=Rajdhani:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <script>
        // Apply saved theme IMMEDIATELY (before render) to avoid flash
        (function() {
            var savedTheme = localStorage.getItem('theme') || 'dark';
            if (savedTheme === 'light') {
                document.documentElement.setAttribute('data-theme', 'light');
            }
        })();
    </script>

    <style>
        :root {
            color-scheme: dark;
            --bg-primary: #0a0f1e;
            --bg-sidebar: #0b1428;
            --bg-card: rgba(10, 20, 40, 0.85);
            --bg-input: #0d1730;
            --bg-modal: #0b1428;
            --bg-pagination: rgba(0, 0, 0, 0.3);

            --text-primary: #e8eeff;
            --text-secondary: #a9b8e0;
            --text-muted: #7f8fb5;
            --text-dark: #050812;

            --border-color: rgba(0, 255, 157, 0.25);
            --border-light: rgba(0, 255, 157, 0.12);
            --border-strong: rgba(0, 255, 157, 0.4);

            --cyber-bg1: #0a0f1e;
            --cyber-bg2: #050812;
            --grid-color: rgba(0, 255, 157, 0.03);

            --accent: #00ff9d;
            --accent-blue: #0066ff;
        }

        [data-theme="light"] {
            color-scheme: light;
            --bg-primary: #d5dce6;
            --bg-sidebar: #f0f4f8;
            --bg-card: rgba(255, 255, 255, 0.95);
            --bg-input: #ffffff;
            --bg-modal: #ffffff;
            --bg-pagination: rgba(255, 255, 255, 0.9);

            --text-primary: #1a2332;
            --text-secondary: #3d4a5e;
            --text-muted: #6b7a8e;
            --text-dark: #1a2332;

            --border-color: rgba(0, 160, 100, 0.35);
            --border-light: rgba(0, 160, 100, 0.2);
            --border-strong: rgba(0, 160, 100, 0.55);

            --cyber-bg1: #d5dce6;
            --cyber-bg2: #c8d0dd;
            --grid-color: rgba(0, 160, 100, 0.06);

            --accent: #00aa77;
            --accent-blue: #0066cc;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Rajdhani', sans-serif;
            background: var(--bg-primary);
            color: var(--text-primary);
            overflow-x: hidden;
            transition: background 0.3s ease, color 0.3s ease;
        }

        body.menu-open, body.modal-open { overflow: hidden; }

        .cyber-bg {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            z-index: -2;
            background: linear-gradient(135deg, var(--cyber-bg1) 0%, var(--cyber-bg2) 100%);
        }

        .grid-overlay {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background-image: linear-gradient(var(--grid-color) 1px, transparent 1px),
                              linear-gradient(90deg, var(--grid-color) 1px, transparent 1px);
            background-size: 50px 50px;
            z-index: -1;
            animation: gridMove 20s linear infinite;
            pointer-events: none;
        }

        @keyframes gridMove {
            0% { transform: translate(0, 0); }
            100% { transform: translate(50px, 50px); }
        }

        .dashboard-container { display: flex; min-height: 100vh; }

        /* ---------- Sidebar ---------- */
        .sidebar {
            width: 280px;
            background: var(--bg-sidebar);
            border-right: 1px solid var(--border-color);
            padding: 2rem 1.5rem;
            position: fixed;
            top: 0; left: 0; bottom: 0;
            height: 100%;
            overflow-y: auto;
            -webkit-overflow-scrolling: touch;
            z-index: 1000;
            transition: transform 0.3s ease, background 0.3s ease;
        }

        .logo {
            font-family: 'Orbitron', sans-serif;
            font-size: 1.4rem;
            font-weight: 800;
            background: linear-gradient(135deg, var(--accent), var(--accent-blue));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 2rem;
            text-align: center;
        }

        .sidebar-close {
            display: none;
            position: absolute;
            top: 12px; right: 12px;
            width: 38px; height: 38px;
            border: 0;
            background: transparent;
            color: var(--text-secondary);
            font-size: 1.6rem;
            line-height: 1;
            cursor: pointer;
        }

        .theme-toggle-btn {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 15px;
            background: rgba(0, 102, 255, 0.08);
            border: 1px solid rgba(0, 102, 255, 0.25);
            border-radius: 10px;
            margin-bottom: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            user-select: none;
        }
        .theme-toggle-btn:hover { background: rgba(0, 102, 255, 0.15); border-color: var(--accent-blue); }
        .theme-toggle-btn i { color: var(--accent-blue); font-size: 1.2rem; width: 24px; text-align: center; }
        .theme-toggle-btn .theme-text { color: var(--text-secondary); font-size: 0.9rem; flex: 1; }
        .theme-toggle-btn .theme-badge {
            font-family: 'Orbitron', sans-serif;
            font-size: 0.75rem;
            color: var(--accent-blue);
            background: rgba(0, 102, 255, 0.1);
            padding: 2px 10px;
            border-radius: 20px;
        }

        .brightness-box {
            padding: 12px 15px;
            background: rgba(255, 170, 0, 0.08);
            border: 1px solid rgba(255, 170, 0, 0.3);
            border-radius: 10px;
            margin-bottom: 1.5rem;
        }
        .brightness-head { display: flex; align-items: center; gap: 12px; margin-bottom: 10px; }
        .brightness-head i { color: #f59e0b; font-size: 1.2rem; width: 24px; text-align: center; }
        .brightness-head .brightness-text { color: var(--text-secondary); font-size: 0.9rem; flex: 1; }
        .brightness-head .brightness-badge {
            font-family: 'Orbitron', sans-serif;
            font-size: 0.75rem;
            color: #f59e0b;
            background: rgba(255, 170, 0, 0.12);
            padding: 2px 10px;
            border-radius: 20px;
            min-width: 54px;
            text-align: center;
        }
        .brightness-slider {
            -webkit-appearance: none; appearance: none;
            width: 100%; height: 6px; border-radius: 6px;
            background: rgba(255, 170, 0, 0.3);
            outline: none; cursor: pointer;
        }
        .brightness-slider::-webkit-slider-thumb {
            -webkit-appearance: none; appearance: none;
            width: 22px; height: 22px; border-radius: 50%;
            background: #f59e0b; border: 2px solid #fff; cursor: pointer;
        }
        .brightness-slider::-moz-range-thumb {
            width: 22px; height: 22px; border-radius: 50%;
            background: #f59e0b; border: 2px solid #fff; cursor: pointer;
        }
        .brightness-reset {
            margin-top: 8px; width: 100%; padding: 6px;
            border: 1px solid rgba(255, 170, 0, 0.4);
            border-radius: 8px; background: transparent;
            color: #f59e0b; font-family: 'Rajdhani', sans-serif;
            font-weight: 600; cursor: pointer;
        }

        .brightness-dim, .brightness-glow {
            position: fixed; top: 0; right: 0; bottom: 0; left: 0;
            pointer-events: none; opacity: 0;
            z-index: 2147482000;
            transition: opacity 0.15s ease;
        }
        .brightness-dim { background: #000; }
        .brightness-glow { background: #fff; }

        .nav-menu { list-style: none; }
        .nav-item { margin-bottom: 0.5rem; }
        .nav-link {
            display: flex; align-items: center; gap: 12px;
            padding: 12px 16px;
            color: var(--text-secondary);
            text-decoration: none;
            border-radius: 12px;
            transition: all 0.3s;
        }
        .nav-link:hover, .nav-link.active { background: rgba(0, 255, 157, 0.1); color: var(--accent); }
        .nav-link i { width: 24px; }

        /* Mobile top bar + overlay */
        .mobile-topbar {
            display: none;
            position: fixed; top: 0; left: 0; right: 0;
            height: 56px;
            padding: 0 12px;
            padding-top: env(safe-area-inset-top, 0px);
            background: var(--bg-sidebar);
            border-bottom: 1px solid var(--border-color);
            align-items: center; gap: 12px;
            z-index: 900;
        }
        .menu-btn {
            width: 42px; height: 42px;
            border: 1px solid var(--border-strong);
            border-radius: 10px;
            background: rgba(0, 255, 157, 0.08);
            color: var(--accent);
            font-size: 1.2rem; cursor: pointer;
            -webkit-tap-highlight-color: transparent;
        }
        .mobile-title {
            font-family: 'Orbitron', sans-serif;
            font-size: 1rem; font-weight: 800;
            background: linear-gradient(135deg, var(--accent), var(--accent-blue));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .sidebar-overlay {
            display: none;
            position: fixed; top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(0, 0, 0, 0.6);
            z-index: 950;
        }
        .sidebar-overlay.show { display: block; }

        /* ---------- Main ---------- */
        .main-content { flex: 1; margin-left: 280px; padding: 2rem; min-width: 0; }

        .page-header {
            display: flex; justify-content: space-between; align-items: center;
            margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem;
        }
        .page-header h1 {
            font-family: 'Orbitron', sans-serif;
            font-size: 1.8rem;
            background: linear-gradient(135deg, var(--accent), var(--accent-blue));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .btn {
            padding: 10px 24px;
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
        .btn-primary { background: linear-gradient(45deg, var(--accent), var(--accent-blue)); color: var(--text-dark); }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(0, 255, 157, 0.4); }
        .btn-secondary { background: transparent; border: 2px solid var(--accent); color: var(--accent); }
        .btn-secondary:hover { background: var(--accent); color: var(--text-dark); }
        .btn-danger { background: #dc2626; color: white; }
        .btn-danger:hover { background: #b91c1c; }
        .btn-warning { background: #f59e0b; color: var(--text-dark); }
        .btn-success { background: linear-gradient(45deg, #00b894, #00cec9); color: #050812; }
        .btn-sm { padding: 6px 12px; font-size: 0.75rem; }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        .stat-card {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 15px;
            padding: 1rem;
            text-align: center;
        }
        .stat-number { font-family: 'Orbitron', sans-serif; font-size: 2rem; color: var(--accent); }
        .stat-label { color: var(--text-secondary); font-size: 0.85rem; }

        .filter-card {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 20px;
            padding: 1.5rem;
            margin-bottom: 2rem;
        }
        .filter-form { display: flex; flex-wrap: wrap; gap: 1rem; align-items: flex-end; }
        .filter-group { flex: 1; min-width: 160px; }
        .filter-group label { display: block; margin-bottom: 5px; font-size: 0.85rem; color: var(--text-secondary); }
        .filter-group input, .filter-group select {
            width: 100%;
            padding: 9px 12px;
            background: var(--bg-input);
            border: 1px solid var(--border-strong);
            border-radius: 10px;
            color: var(--text-primary);
            -webkit-text-fill-color: var(--text-primary);
            font-family: 'Rajdhani', sans-serif;
            font-size: 1rem;
        }
        .filter-group input::placeholder { color: var(--text-muted); -webkit-text-fill-color: var(--text-muted); opacity: 1; }
        select option { background: var(--bg-input); color: var(--text-primary); }

        .table-card {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 20px;
            overflow: hidden;
        }
        .table-container { overflow-x: auto; -webkit-overflow-scrolling: touch; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 14px 16px; text-align: left; border-bottom: 1px solid var(--border-light); white-space: nowrap; }
        th {
            background: rgba(0, 255, 157, 0.05);
            font-family: 'Orbitron', sans-serif;
            font-weight: 600;
            color: var(--accent);
        }
        tr:hover { background: rgba(0, 255, 157, 0.03); }

        .badge { padding: 4px 12px; border-radius: 50px; font-size: 0.7rem; font-weight: 600; }
        .badge-admin { background: linear-gradient(135deg, #ff6b6b, #ee5a24); color: white; }
        .badge-user { background: rgba(0, 255, 157, 0.2); color: var(--accent); }

        .action-buttons { display: flex; gap: 8px; }

        .pagination {
            display: flex; justify-content: center; flex-wrap: wrap;
            gap: 8px; margin-top: 1.5rem;
        }
        .pagination a, .pagination span {
            padding: 8px 14px;
            background: var(--bg-pagination);
            border-radius: 10px;
            text-decoration: none;
            color: var(--text-primary);
            transition: all 0.3s;
        }
        .pagination a:hover, .pagination .active { background: var(--accent); color: var(--text-dark); }

        .alert { padding: 12px 20px; border-radius: 12px; margin-bottom: 1.5rem; }
        .alert-success { background: rgba(0, 255, 157, 0.12); border: 1px solid var(--accent); color: var(--accent); }
        .alert-danger { background: rgba(220, 38, 38, 0.12); border: 1px solid #dc2626; color: #ef4444; }

        /* ---------- Modal ---------- */
        .modal {
            display: none;
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(0, 0, 0, 0.75);
            z-index: 2000;
            justify-content: center;
            align-items: center;
            padding: 1rem;
            overflow-y: auto;
        }
        .modal.active { display: flex; }
        .modal-content {
            background: var(--bg-modal);
            border: 2px solid var(--accent);
            border-radius: 20px;
            padding: 1.75rem;
            max-width: 500px;
            width: 100%;
            max-height: 100%;
            overflow-y: auto;
        }
        .modal-content h3 { font-family: 'Orbitron', sans-serif; color: var(--accent); margin-bottom: 1.5rem; }
        .modal-content label { display: block; margin-bottom: 4px; font-size: 0.85rem; color: var(--text-secondary); }
        .modal-content input, .modal-content select {
            width: 100%;
            padding: 11px 12px;
            margin-bottom: 1rem;
            background: var(--bg-input);
            border: 1px solid var(--border-strong);
            border-radius: 10px;
            color: var(--text-primary);
            -webkit-text-fill-color: var(--text-primary);
            font-family: 'Rajdhani', sans-serif;
            font-size: 1rem;
        }
        .modal-content input::placeholder { color: var(--text-muted); -webkit-text-fill-color: var(--text-muted); opacity: 1; }
        .modal-content input:focus, .modal-content select:focus,
        .filter-group input:focus, .filter-group select:focus {
            outline: none;
            border-color: var(--accent);
            box-shadow: 0 0 10px rgba(0, 255, 157, 0.25);
        }
        .modal-actions { display: flex; gap: 1rem; justify-content: flex-end; margin-top: 0.5rem; }

        ::-webkit-scrollbar { width: 10px; height: 10px; }
        ::-webkit-scrollbar-track { background: var(--bg-pagination); border-radius: 10px; }
        ::-webkit-scrollbar-thumb { background: var(--accent); border-radius: 10px; }

        /* ---------- Phones / small tablets ---------- */
        @media (max-width: 900px) {
            .mobile-topbar { display: flex; }
            .sidebar {
                width: 270px; max-width: 82vw;
                transform: translateX(-100%);
                padding-top: 3.5rem;
            }
            .sidebar.open { transform: translateX(0); box-shadow: 8px 0 40px rgba(0, 0, 0, 0.5); }
            .sidebar-close { display: block; }
            .main-content {
                margin-left: 0;
                padding: 1rem;
                padding-top: calc(56px + env(safe-area-inset-top, 0px) + 1rem);
            }
            .page-header h1 { font-size: 1.3rem; }
            .filter-group { min-width: 100%; }
            .stats-grid { grid-template-columns: repeat(3, 1fr); gap: 0.6rem; }
            .stat-number { font-size: 1.4rem; }
            .stat-label { font-size: 0.7rem; }
            th, td { padding: 12px; }
        }

        @media (max-width: 480px) {
            .modal-actions { flex-direction: column-reverse; }
            .modal-actions .btn { width: 100%; justify-content: center; }
        }
    </style>
</head>
<body>
    <div class="cyber-bg"></div>
    <div class="grid-overlay"></div>
    <div class="brightness-dim" id="brightnessDim"></div>
    <div class="brightness-glow" id="brightnessGlow"></div>

    <!-- Mobile top bar -->
    <div class="mobile-topbar">
        <button type="button" class="menu-btn" id="menuBtn" aria-label="Open menu"><i class="fas fa-bars"></i></button>
        <span class="mobile-title">CSS Admin</span>
    </div>
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <div class="dashboard-container">
        <aside class="sidebar" id="sidebar">
            <button type="button" class="sidebar-close" id="sidebarClose" aria-label="Close menu">&times;</button>
            <div class="logo">CSS Admin</div>

            <div class="theme-toggle-btn" id="themeToggle" title="Toggle dark/light mode">
                <i class="fas fa-moon" id="themeIcon"></i>
                <span class="theme-text">Theme</span>
                <span class="theme-badge" id="themeBadge">Dark</span>
            </div>

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
                <li class="nav-item"><a href="<?php echo $base; ?>/manage_users.php" class="nav-link active"><i class="fas fa-users"></i><span>Users</span></a></li>
                <li class="nav-item"><a href="<?php echo $base; ?>/admin/view_results.php" class="nav-link"><i class="fas fa-chart-line"></i><span>View Results</span></a></li>
                <li class="nav-item"><a href="<?php echo $base; ?>/logout.php" class="nav-link"><i class="fas fa-sign-out-alt"></i><span>Logout</span></a></li>
            </ul>
        </aside>

        <main class="main-content">
            <div class="page-header">
                <h1><i class="fas fa-users"></i> Manage Users</h1>
                <button class="btn btn-primary" onclick="openAddModal()"><i class="fas fa-user-plus"></i> Add User</button>
            </div>

            <?php if (isset($_SESSION['message'])): ?>
                <div class="alert alert-<?php echo htmlspecialchars($_SESSION['message_type']); ?>">
                    <?php echo htmlspecialchars($_SESSION['message']); unset($_SESSION['message']); unset($_SESSION['message_type']); ?>
                </div>
            <?php endif; ?>

            <div class="stats-grid">
                <div class="stat-card"><div class="stat-number"><?php echo $stats['total']; ?></div><div class="stat-label">Total Users</div></div>
                <div class="stat-card"><div class="stat-number"><?php echo $stats['admins']; ?></div><div class="stat-label">Admins</div></div>
                <div class="stat-card"><div class="stat-number"><?php echo $stats['users']; ?></div><div class="stat-label">Regular Users</div></div>
            </div>

            <div class="filter-card">
                <form method="GET" class="filter-form">
                    <div class="filter-group"><label>Search</label><input type="text" name="search" placeholder="Username, name, email..." value="<?php echo htmlspecialchars($search); ?>"></div>
                    <div class="filter-group"><label>Role</label><select name="role_filter"><option value="">All</option><option value="admin" <?php echo $role_filter=='admin'?'selected':''; ?>>Admin</option><option value="user" <?php echo $role_filter=='user'?'selected':''; ?>>User</option></select></div>
                    <div class="filter-group"><label>&nbsp;</label><button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-filter"></i> Filter</button> <a href="<?php echo $base; ?>/manage_users.php" class="btn btn-warning btn-sm"><i class="fas fa-undo"></i> Reset</a></div>
                </form>
            </div>

            <div class="table-card">
                <div class="table-container">
                    <table>
                        <thead>
                            <tr><th>ID</th><th>Username</th><th>Full Name</th><th>Email</th><th>Role</th><th>Joined</th><th>Actions</th></tr>
                        </thead>
                        <tbody>
                            <?php if ($users->num_rows > 0): ?>
                                <?php while ($row = $users->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo $row['id']; ?></td>
                                        <td><?php echo htmlspecialchars($row['username']); ?></td>
                                        <td><?php echo htmlspecialchars($row['full_name']); ?></td>
                                        <td><?php echo htmlspecialchars($row['email']); ?></td>
                                        <td><span class="badge <?php echo $row['role'] == 'admin' ? 'badge-admin' : 'badge-user'; ?>"><?php echo ucfirst($row['role']); ?></span></td>
                                        <td><?php echo date('M d, Y', strtotime($row['created_at'])); ?></td>
                                        <td class="action-buttons">
                                            <button type="button" class="btn btn-primary btn-sm edit-user-btn" data-user='<?php echo htmlspecialchars(json_encode([
                                                'id' => (int)$row['id'],
                                                'username' => $row['username'],
                                                'full_name' => $row['full_name'],
                                                'email' => $row['email'],
                                                'role' => $row['role']
                                            ]), ENT_QUOTES, 'UTF-8'); ?>'><i class="fas fa-edit"></i></button>
                                            <?php if ($row['id'] != $_SESSION['user_id']): ?>
                                                <a href="?change_role=<?php echo $row['id']; ?>&role=<?php echo $row['role'] == 'admin' ? 'user' : 'admin'; ?>" class="btn btn-success btn-sm" onclick="return confirm('Change role?')"><i class="fas fa-exchange-alt"></i></a>
                                                <a href="?delete=<?php echo $row['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Delete this user?')"><i class="fas fa-trash"></i></a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="7" style="text-align:center;padding:40px;">No users found</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <?php if ($total_pages > 1): ?>
                <div class="pagination">
                    <?php if ($page > 1): ?><a href="?page=<?php echo $page-1; ?>&search=<?php echo urlencode($search); ?>&role_filter=<?php echo urlencode($role_filter); ?>">&laquo; Prev</a><?php endif; ?>
                    <?php for ($i = max(1, $page-2); $i <= min($total_pages, $page+2); $i++): ?><a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&role_filter=<?php echo urlencode($role_filter); ?>" class="<?php echo $i == $page ? 'active' : ''; ?>"><?php echo $i; ?></a><?php endfor; ?>
                    <?php if ($page < $total_pages): ?><a href="?page=<?php echo $page+1; ?>&search=<?php echo urlencode($search); ?>&role_filter=<?php echo urlencode($role_filter); ?>">Next &raquo;</a><?php endif; ?>
                </div>
            <?php endif; ?>
        </main>
    </div>

    <div id="userModal" class="modal">
        <div class="modal-content">
            <h3 id="modalTitle">Add New User</h3>
            <form method="POST" action="">
                <input type="hidden" name="user_id" id="user_id" value="0">
                <label for="username">Username</label>
                <input type="text" name="username" id="username" placeholder="Username" required>
                <label for="full_name">Full Name</label>
                <input type="text" name="full_name" id="full_name" placeholder="Full Name" required>
                <label for="email">Email</label>
                <input type="email" name="email" id="email" placeholder="Email" required>
                <label for="role">Role</label>
                <select name="role" id="role">
                    <option value="user">User</option>
                    <option value="admin">Admin</option>
                </select>
                <label for="password">Password</label>
                <input type="password" name="password" id="password" placeholder="Password (required for new user)">
                <div class="modal-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // ---------- Add / Edit user modal ----------
        function showModal() {
            document.getElementById('userModal').classList.add('active');
            document.body.classList.add('modal-open');
        }

        function openAddModal() {
            document.getElementById('modalTitle').innerText = 'Add New User';
            document.getElementById('user_id').value = '0';
            document.getElementById('username').value = '';
            document.getElementById('full_name').value = '';
            document.getElementById('email').value = '';
            document.getElementById('role').value = 'user';
            document.getElementById('password').value = '';
            document.getElementById('password').placeholder = 'Password (required for new user)';
            showModal();
        }

        function openEditModal(u) {
            document.getElementById('modalTitle').innerText = 'Edit User';
            document.getElementById('user_id').value = u.id;
            document.getElementById('username').value = u.username;
            document.getElementById('full_name').value = u.full_name;
            document.getElementById('email').value = u.email;
            document.getElementById('role').value = u.role;
            document.getElementById('password').value = '';
            document.getElementById('password').placeholder = 'Password (leave blank to keep current)';
            showModal();
        }

        function closeModal() {
            document.getElementById('userModal').classList.remove('active');
            document.body.classList.remove('modal-open');
        }

        document.querySelectorAll('.edit-user-btn').forEach(function(btn) {
            btn.addEventListener('click', function() {
                try { openEditModal(JSON.parse(btn.getAttribute('data-user'))); } catch (e) { console.error(e); }
            });
        });

        document.getElementById('userModal').addEventListener('click', function(e) {
            if (e.target === this) closeModal();
        });

        // ---------- Mobile sidebar ----------
        (function() {
            var sidebar = document.getElementById('sidebar');
            var overlay = document.getElementById('sidebarOverlay');
            var menuBtn = document.getElementById('menuBtn');
            var closeBtn = document.getElementById('sidebarClose');

            function openMenu() {
                sidebar.classList.add('open');
                overlay.classList.add('show');
                document.body.classList.add('menu-open');
            }
            function closeMenu() {
                sidebar.classList.remove('open');
                overlay.classList.remove('show');
                document.body.classList.remove('menu-open');
            }
            menuBtn.addEventListener('click', openMenu);
            closeBtn.addEventListener('click', closeMenu);
            overlay.addEventListener('click', closeMenu);
            window.addEventListener('resize', function() { if (window.innerWidth > 900) closeMenu(); });
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') { closeMenu(); closeModal(); }
            });
        })();

        // ---------- Brightness control (40% - 120%) ----------
        (function() {
            var slider = document.getElementById('brightnessSlider');
            var badge = document.getElementById('brightnessBadge');
            var resetBtn = document.getElementById('brightnessReset');
            var dim = document.getElementById('brightnessDim');
            var glow = document.getElementById('brightnessGlow');
            var KEY = 'css_tesda_brightness';

            function applyBrightness(value) {
                value = parseInt(value, 10);
                if (isNaN(value)) value = 100;
                value = Math.min(120, Math.max(40, value));
                if (value < 100) {
                    dim.style.opacity = (100 - value) / 100;
                    glow.style.opacity = 0;
                } else {
                    dim.style.opacity = 0;
                    glow.style.opacity = (value - 100) / 100 * 0.5;
                }
                slider.value = value;
                badge.textContent = value + '%';
                try { localStorage.setItem(KEY, value); } catch (e) {}
            }

            var saved = 100;
            try { saved = localStorage.getItem(KEY) || 100; } catch (e) {}
            applyBrightness(saved);
            slider.addEventListener('input', function() { applyBrightness(slider.value); });
            resetBtn.addEventListener('click', function() { applyBrightness(100); });
        })();

        // ---------- Theme toggle: Dark / Light ----------
        (function() {
            var themeToggle = document.getElementById('themeToggle');
            var themeIcon = document.getElementById('themeIcon');
            var themeBadge = document.getElementById('themeBadge');

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

            applyTheme(localStorage.getItem('theme') || 'dark');
            themeToggle.addEventListener('click', function() {
                var current = localStorage.getItem('theme') || 'dark';
                applyTheme(current === 'dark' ? 'light' : 'dark');
            });
        })();
    </script>
</body>
</html>
<?php
// manage_users.php - For root directory (C:\xampp\htdocs\proj\manage_users.php)

require_once __DIR__ . '/config.php';  // config.php is in the same directory

// Check if admin is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
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
    header("Location: manage_users.php");
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
    header("Location: manage_users.php");
    exit();
}

// Handle add/edit user form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
    $username = trim($_POST['username'] ?? '');
    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $role = $_POST['role'] ?? 'user';
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
    header("Location: manage_users.php");
    exit();
}

// Pagination and filtering
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users | CSS TESDA Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&family=Rajdhani:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Rajdhani', sans-serif;
            background: #0a0f1e;
            color: #e0e0ff;
            overflow-x: hidden;
        }
        .cyber-bg {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -2;
            background: linear-gradient(135deg, #0a0f1e 0%, #050812 100%);
        }
        .grid-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: linear-gradient(rgba(0, 255, 157, 0.03) 1px, transparent 1px), linear-gradient(90deg, rgba(0, 255, 157, 0.03) 1px, transparent 1px);
            background-size: 50px 50px;
            z-index: -1;
            animation: gridMove 20s linear infinite;
        }
        @keyframes gridMove {
            0% { transform: translate(0, 0); }
            100% { transform: translate(50px, 50px); }
        }
        .dashboard-container { display: flex; min-height: 100vh; }
        .sidebar {
            width: 280px;
            background: rgba(10, 20, 40, 0.9);
            backdrop-filter: blur(10px);
            border-right: 1px solid rgba(0, 255, 157, 0.2);
            padding: 2rem 1.5rem;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            z-index: 10;
        }
        .logo {
            font-family: 'Orbitron', sans-serif;
            font-size: 1.4rem;
            font-weight: 800;
            background: linear-gradient(135deg, #00ff9d, #0066ff);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 2.5rem;
            text-align: center;
        }
        .nav-menu { list-style: none; }
        .nav-item { margin-bottom: 0.5rem; }
        .nav-link {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 16px;
            color: #94a3b8;
            text-decoration: none;
            border-radius: 12px;
            transition: all 0.3s;
        }
        .nav-link:hover, .nav-link.active {
            background: rgba(0, 255, 157, 0.1);
            color: #00ff9d;
        }
        .nav-link i { width: 24px; }
        .main-content {
            flex: 1;
            margin-left: 280px;
            padding: 2rem;
        }
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            flex-wrap: wrap;
            gap: 1rem;
        }
        .page-header h1 {
            font-family: 'Orbitron', sans-serif;
            font-size: 1.8rem;
            background: linear-gradient(135deg, #00ff9d, #0066ff);
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
        .btn-primary {
            background: linear-gradient(45deg, #00ff9d, #0066ff);
            color: #050812;
        }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(0, 255, 157, 0.4); }
        .btn-danger { background: #dc2626; color: white; }
        .btn-danger:hover { background: #b91c1c; }
        .btn-warning { background: #f59e0b; color: #050812; }
        .btn-sm { padding: 5px 12px; font-size: 0.75rem; }
        .btn-success { background: linear-gradient(45deg, #00b894, #00cec9); color: #050812; }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        .stat-card {
            background: rgba(0, 0, 0, 0.3);
            border: 1px solid rgba(0, 255, 157, 0.2);
            border-radius: 15px;
            padding: 1rem;
            text-align: center;
        }
        .stat-number { font-family: 'Orbitron', sans-serif; font-size: 2rem; color: #00ff9d; }
        .stat-label { color: #8899cc; font-size: 0.8rem; }
        .filter-card {
            background: rgba(0, 0, 0, 0.3);
            border: 1px solid rgba(0, 255, 157, 0.2);
            border-radius: 20px;
            padding: 1.5rem;
            margin-bottom: 2rem;
        }
        .filter-form {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            align-items: flex-end;
        }
        .filter-group { flex: 1; min-width: 150px; }
        .filter-group label { display: block; margin-bottom: 5px; font-size: 0.8rem; color: #8899cc; }
        .filter-group input, .filter-group select {
            width: 100%;
            padding: 8px 12px;
            background: rgba(0, 0, 0, 0.4);
            border: 1px solid rgba(0, 255, 157, 0.3);
            border-radius: 10px;
            color: #e0e0ff;
            font-family: 'Rajdhani', sans-serif;
        }
        .table-card {
            background: rgba(0, 0, 0, 0.3);
            border: 1px solid rgba(0, 255, 157, 0.2);
            border-radius: 20px;
            overflow: hidden;
        }
        .table-container { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 14px 16px; text-align: left; border-bottom: 1px solid rgba(0, 255, 157, 0.1); }
        th {
            background: rgba(0, 255, 157, 0.05);
            font-family: 'Orbitron', sans-serif;
            font-weight: 600;
            color: #00ff9d;
        }
        tr:hover { background: rgba(0, 255, 157, 0.02); }
        .badge {
            padding: 4px 12px;
            border-radius: 50px;
            font-size: 0.7rem;
            font-weight: 600;
        }
        .badge-admin { background: linear-gradient(135deg, #ff6b6b, #ee5a24); color: white; }
        .badge-user { background: rgba(0, 255, 157, 0.2); color: #00ff9d; }
        .action-buttons { display: flex; gap: 8px; flex-wrap: wrap; }
        .pagination {
            display: flex;
            justify-content: center;
            gap: 8px;
            margin-top: 1.5rem;
        }
        .pagination a, .pagination span {
            padding: 8px 14px;
            background: rgba(0, 0, 0, 0.3);
            border-radius: 10px;
            text-decoration: none;
            color: #e0e0ff;
            transition: all 0.3s;
        }
        .pagination a:hover, .pagination .active {
            background: #00ff9d;
            color: #050812;
        }
        .alert {
            padding: 12px 20px;
            border-radius: 12px;
            margin-bottom: 1.5rem;
        }
        .alert-success {
            background: rgba(0, 255, 157, 0.1);
            border: 1px solid #00ff9d;
            color: #00ff9d;
        }
        .alert-danger {
            background: rgba(220, 38, 38, 0.1);
            border: 1px solid #dc2626;
            color: #ef4444;
        }
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }
        .modal.active { display: flex; }
        .modal-content {
            background: rgba(10, 20, 40, 0.95);
            backdrop-filter: blur(20px);
            border: 2px solid #00ff9d;
            border-radius: 20px;
            padding: 2rem;
            max-width: 500px;
            width: 90%;
        }
        .modal-content h3 { font-family: 'Orbitron', sans-serif; color: #00ff9d; margin-bottom: 1.5rem; }
        .modal-content input, .modal-content select {
            width: 100%;
            padding: 10px;
            margin-bottom: 1rem;
            background: rgba(0, 0, 0, 0.4);
            border: 1px solid rgba(0, 255, 157, 0.3);
            border-radius: 10px;
            color: #e0e0ff;
        }
        .modal-actions { display: flex; gap: 1rem; justify-content: flex-end; margin-top: 1rem; }
        @media (max-width: 768px) {
            .sidebar { width: 80px; padding: 1rem 0.5rem; }
            .sidebar .logo, .sidebar .nav-link span { display: none; }
            .main-content { margin-left: 80px; }
            .nav-link { justify-content: center; }
        }
    </style>
</head>
<body>
    <div class="cyber-bg"></div>
    <div class="grid-overlay"></div>

    <div class="dashboard-container">
        <aside class="sidebar">
            <div class="logo">CSS Admin</div>
            <ul class="nav-menu">
                <li class="nav-item"><a href="dashboard.php" class="nav-link"><i class="fas fa-tachometer-alt"></i><span>Dashboard</span></a></li>
      
           
                <li class="nav-item"><a href="manage_users.php" class="nav-link active"><i class="fas fa-users"></i><span>Users</span></a></li>
                <li class="nav-item"><a href="logout.php" class="nav-link"><i class="fas fa-sign-out-alt"></i><span>Logout</span></a></li>
            </ul>
        </aside>

        <main class="main-content">
            <div class="page-header">
                <h1><i class="fas fa-users"></i> Manage Users</h1>
                <button class="btn btn-primary" onclick="openAddModal()"><i class="fas fa-user-plus"></i> Add User</button>
            </div>

            <?php if (isset($_SESSION['message'])): ?>
                <div class="alert alert-<?php echo $_SESSION['message_type']; ?>">
                    <?php echo $_SESSION['message']; unset($_SESSION['message']); unset($_SESSION['message_type']); ?>
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
                    <div class="filter-group"><label>&nbsp;</label><button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-filter"></i> Filter</button><a href="manage_users.php" class="btn btn-warning btn-sm"><i class="fas fa-undo"></i> Reset</a></div>
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
                                            <button class="btn btn-primary btn-sm" onclick="openEditModal(<?php echo $row['id']; ?>, '<?php echo htmlspecialchars($row['username']); ?>', '<?php echo htmlspecialchars($row['full_name']); ?>', '<?php echo htmlspecialchars($row['email']); ?>', '<?php echo $row['role']; ?>')"><i class="fas fa-edit"></i></button>
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
                    <?php if ($page > 1): ?><a href="?page=<?php echo $page-1; ?>&search=<?php echo urlencode($search); ?>&role_filter=<?php echo $role_filter; ?>">« Prev</a><?php endif; ?>
                    <?php for ($i = max(1, $page-2); $i <= min($total_pages, $page+2); $i++): ?><a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&role_filter=<?php echo $role_filter; ?>" class="<?php echo $i == $page ? 'active' : ''; ?>"><?php echo $i; ?></a><?php endfor; ?>
                    <?php if ($page < $total_pages): ?><a href="?page=<?php echo $page+1; ?>&search=<?php echo urlencode($search); ?>&role_filter=<?php echo $role_filter; ?>">Next »</a><?php endif; ?>
                </div>
            <?php endif; ?>
        </main>
    </div>

    <div id="userModal" class="modal">
        <div class="modal-content">
            <h3 id="modalTitle">Add New User</h3>
            <form method="POST" action="">
                <input type="hidden" name="user_id" id="user_id" value="0">
                <input type="text" name="username" id="username" placeholder="Username" required>
                <input type="text" name="full_name" id="full_name" placeholder="Full Name" required>
                <input type="email" name="email" id="email" placeholder="Email" required>
                <select name="role" id="role">
                    <option value="user">User</option>
                    <option value="admin">Admin</option>
                </select>
                <input type="password" name="password" id="password" placeholder="Password (required for new user)">
                <div class="modal-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openAddModal() {
            document.getElementById('modalTitle').innerText = 'Add New User';
            document.getElementById('user_id').value = '0';
            document.getElementById('username').value = '';
            document.getElementById('full_name').value = '';
            document.getElementById('email').value = '';
            document.getElementById('role').value = 'user';
            document.getElementById('password').value = '';
            document.getElementById('password').placeholder = 'Password (required for new user)';
            document.getElementById('userModal').classList.add('active');
        }

        function openEditModal(id, username, full_name, email, role) {
            document.getElementById('modalTitle').innerText = 'Edit User';
            document.getElementById('user_id').value = id;
            document.getElementById('username').value = username;
            document.getElementById('full_name').value = full_name;
            document.getElementById('email').value = email;
            document.getElementById('role').value = role;
            document.getElementById('password').value = '';
            document.getElementById('password').placeholder = 'Password (leave blank to keep current)';
            document.getElementById('userModal').classList.add('active');
        }

        function closeModal() {
            document.getElementById('userModal').classList.remove('active');
        }

        window.onclick = function(event) {
            const modal = document.getElementById('userModal');
            if (event.target == modal) closeModal();
        }
    </script>
     <?php $global_brightness = getGlobalBrightness(); include __DIR__ . '/includes/brightness_widget.php'; ?>
     <?php include __DIR__ . '/includes/theme_toggle.php'; ?>
</body>
</html>
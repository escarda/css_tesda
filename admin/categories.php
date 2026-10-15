<?php
require_once '../config.php';

if (!isAdmin()) {
    redirect('login.php');
}

// Handle CRUD operations
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add'])) {
        $name = $conn->real_escape_string($_POST['name']);
        $description = $conn->real_escape_string($_POST['description']);
        $icon = $conn->real_escape_string($_POST['icon']);
        
        $sql = "INSERT INTO categories (name, description, icon) VALUES ('$name', '$description', '$icon')";
        
        if ($conn->query($sql)) {
            $message = "Category added successfully!";
            $message_type = "success";
        } else {
            $message = "Error: " . $conn->error;
            $message_type = "error";
        }
    }
    elseif (isset($_POST['edit'])) {
        $id = (int)$_POST['id'];
        $name = $conn->real_escape_string($_POST['name']);
        $description = $conn->real_escape_string($_POST['description']);
        $icon = $conn->real_escape_string($_POST['icon']);
        
        $sql = "UPDATE categories SET name = '$name', description = '$description', icon = '$icon' WHERE id = $id";
        
        if ($conn->query($sql)) {
            $message = "Category updated successfully!";
            $message_type = "success";
        } else {
            $message = "Error: " . $conn->error;
            $message_type = "error";
        }
    }
    elseif (isset($_POST['delete'])) {
        $id = (int)$_POST['id'];
        
        // Check if category has questions
        $check = $conn->query("SELECT COUNT(*) as count FROM questions WHERE category_id = $id");
        $count = $check->fetch_assoc()['count'];
        
        if ($count > 0) {
            $message = "Cannot delete category with $count questions. Move or delete questions first.";
            $message_type = "error";
        } else {
            $sql = "DELETE FROM categories WHERE id = $id";
            if ($conn->query($sql)) {
                $message = "Category deleted successfully!";
                $message_type = "success";
            } else {
                $message = "Error: " . $conn->error;
                $message_type = "error";
            }
        }
    }
}

// Get all categories with statistics
$categories_query = "
    SELECT c.*, 
           COUNT(q.id) as question_count
    FROM categories c
    LEFT JOIN questions q ON c.id = q.category_id
    GROUP BY c.id
    ORDER BY c.id
";
$categories_result = $conn->query($categories_query);

// Get question counts by difficulty for each category
$difficulty_stats = [];
$difficulty_query = "
    SELECT category_id, 
           SUM(CASE WHEN difficulty = 'easy' THEN 1 ELSE 0 END) as easy_count,
           SUM(CASE WHEN difficulty = 'medium' THEN 1 ELSE 0 END) as medium_count,
           SUM(CASE WHEN difficulty = 'hard' THEN 1 ELSE 0 END) as hard_count,
           COUNT(*) as total
    FROM questions 
    GROUP BY category_id
";
$difficulty_result = $conn->query($difficulty_query);
if ($difficulty_result) {
    while ($row = $difficulty_result->fetch_assoc()) {
        $difficulty_stats[$row['category_id']] = $row;
    }
}

// Available icons for selection
$icons = [
    'cog', 'network-wired', 'server', 'tools', 'microchip', 'desktop', 
    'laptop', 'memory', 'hdd', 'ssd', 'plug', 'wifi', 'globe', 
    'database', 'cloud', 'shield-alt', 'lock', 'key', 'terminal'
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Categories - Admin Panel</title>
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&family=Rajdhani:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Rajdhani', sans-serif;
            background: #0a0f1e;
            color: #e0e0ff;
            min-height: 100vh;
        }

        /* Animated Background */
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
            background-image: 
                linear-gradient(rgba(0, 255, 157, 0.03) 1px, transparent 1px),
                linear-gradient(90deg, rgba(0, 255, 157, 0.03) 1px, transparent 1px);
            background-size: 50px 50px;
            z-index: -1;
            animation: gridMove 20s linear infinite;
        }

        @keyframes gridMove {
            0% { transform: translate(0, 0); }
            100% { transform: translate(50px, 50px); }
        }

        /* Dashboard Layout */
        .dashboard {
            display: grid;
            grid-template-columns: 280px 1fr;
            min-height: 100vh;
        }

        /* Sidebar */
        .sidebar {
            background: rgba(5, 8, 18, 0.95);
            backdrop-filter: blur(10px);
            border-right: 2px solid #00ff9d;
            padding: 30px 0;
            position: relative;
            box-shadow: 10px 0 30px rgba(0, 255, 157, 0.1);
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
            border-bottom: 1px solid rgba(0, 255, 157, 0.2);
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
            color: #8899cc;
            font-size: 0.9rem;
        }

        .admin-badge {
            color: #00ff9d;
            font-size: 0.8rem;
            margin-top: 5px;
        }

        .sidebar-nav {
            padding: 20px;
        }

        .sidebar-nav ul {
            list-style: none;
        }

        .sidebar-nav li {
            margin-bottom: 5px;
        }

        .sidebar-nav a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 15px;
            color: #8899cc;
            text-decoration: none;
            border-radius: 8px;
            transition: all 0.3s;
        }

        .sidebar-nav a:hover,
        .sidebar-nav a.active {
            background: rgba(0, 255, 157, 0.1);
            color: #00ff9d;
        }

        .sidebar-nav i {
            width: 20px;
        }

        .sidebar-footer {
            position: absolute;
            bottom: 0;
            width: 100%;
            padding: 20px;
            border-top: 1px solid rgba(0, 255, 157, 0.2);
        }

        /* Main Content */
        .main-content {
            padding: 40px;
            overflow-y: auto;
            max-height: 100vh;
        }

        .main-content h1 {
            font-family: 'Orbitron', sans-serif;
            font-size: 2.5rem;
            margin-bottom: 10px;
            background: linear-gradient(135deg, #00ff9d, #0066ff);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .subtitle {
            color: #8899cc;
            margin-bottom: 30px;
            font-size: 1.1rem;
        }

        /* Action Bar */
        .action-bar {
            background: rgba(10, 20, 40, 0.7);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(0, 255, 157, 0.3);
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 30px;
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            align-items: center;
        }

        .btn {
            padding: 12px 25px;
            border: none;
            border-radius: 8px;
            font-family: 'Orbitron', sans-serif;
            font-size: 0.9rem;
            cursor: pointer;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
        }

        .btn-primary {
            background: linear-gradient(45deg, #00ff9d, #0066ff);
            color: #050812;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(0, 255, 157, 0.4);
        }

        .btn-secondary {
            background: transparent;
            border: 1px solid #00ff9d;
            color: #00ff9d;
        }

        .btn-secondary:hover {
            background: rgba(0, 255, 157, 0.1);
            transform: translateY(-2px);
        }

        .btn-danger {
            background: transparent;
            border: 1px solid #ff3366;
            color: #ff3366;
        }

        .btn-danger:hover {
            background: rgba(255, 51, 102, 0.1);
        }

        /* Categories Grid */
        .categories-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 25px;
        }

        .category-card {
            background: rgba(10, 20, 40, 0.7);
            backdrop-filter: blur(10px);
            border: 2px solid rgba(0, 255, 157, 0.3);
            border-radius: 20px;
            overflow: hidden;
            transition: all 0.3s;
        }

        .category-card:hover {
            transform: translateY(-5px);
            border-color: #00ff9d;
            box-shadow: 0 10px 30px rgba(0, 255, 157, 0.2);
        }

        .card-header {
            background: linear-gradient(135deg, rgba(0,255,157,0.1), rgba(0,102,255,0.1));
            padding: 25px;
            display: flex;
            align-items: center;
            gap: 15px;
            border-bottom: 1px solid rgba(0, 255, 157, 0.2);
        }

        .card-header i {
            font-size: 2.5rem;
            color: #00ff9d;
        }

        .card-header h3 {
            font-family: 'Orbitron', sans-serif;
            color: #00ff9d;
            font-size: 1.3rem;
        }

        .card-body {
            padding: 25px;
        }

        .description {
            color: #8899cc;
            margin-bottom: 20px;
            line-height: 1.6;
        }

        .stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
            margin-bottom: 20px;
        }

        .stat-item {
            text-align: center;
            padding: 10px;
            background: rgba(0, 0, 0, 0.3);
            border-radius: 10px;
            border: 1px solid rgba(0, 255, 157, 0.2);
        }

        .stat-value {
            font-size: 1.5rem;
            color: #00ff9d;
            font-weight: bold;
        }

        .stat-label {
            font-size: 0.8rem;
            color: #8899cc;
        }

        .difficulty-bars {
            margin-bottom: 20px;
        }

        .difficulty-bar-item {
            margin: 10px 0;
        }

        .difficulty-label {
            display: flex;
            justify-content: space-between;
            color: #8899cc;
            margin-bottom: 5px;
        }

        .bar-container {
            width: 100%;
            height: 8px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 4px;
            overflow: hidden;
        }

        .bar-fill {
            height: 100%;
            background: linear-gradient(90deg, #00ff9d, #0066ff);
            border-radius: 4px;
            transition: width 0.3s;
        }

        .card-actions {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }

        .card-btn {
            flex: 1;
            padding: 10px;
            border: 1px solid #00ff9d;
            background: transparent;
            color: #00ff9d;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .card-btn:hover {
            background: #00ff9d;
            color: #050812;
        }

        .card-btn.delete:hover {
            border-color: #ff3366;
            background: #ff3366;
        }

        /* Modal */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.8);
            backdrop-filter: blur(5px);
        }

        .modal-content {
            background: #0a1a2f;
            margin: 10% auto;
            padding: 30px;
            border: 2px solid #00ff9d;
            border-radius: 20px;
            width: 90%;
            max-width: 600px;
            position: relative;
            animation: slideIn 0.3s;
        }

        @keyframes slideIn {
            from {
                transform: translateY(-50px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            border-bottom: 1px solid rgba(0, 255, 157, 0.3);
            padding-bottom: 10px;
        }

        .modal-header h2 {
            color: #00ff9d;
            font-family: 'Orbitron', sans-serif;
        }

        .close {
            color: #00ff9d;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }

        .close:hover {
            color: #ff3366;
        }

        /* Form Styles */
        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #00ff9d;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.85rem;
            letter-spacing: 1px;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px 15px;
            background: rgba(0, 0, 0, 0.3);
            border: 1px solid rgba(0, 255, 157, 0.3);
            border-radius: 8px;
            color: #e0e0ff;
            font-family: 'Rajdhani', sans-serif;
            font-size: 1rem;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #00ff9d;
            box-shadow: 0 0 10px rgba(0, 255, 157, 0.3);
        }

        .icon-grid {
            display: grid;
            grid-template-columns: repeat(6, 1fr);
            gap: 10px;
            max-height: 200px;
            overflow-y: auto;
            padding: 10px;
            background: rgba(0, 0, 0, 0.3);
            border-radius: 8px;
        }

        .icon-option {
            text-align: center;
            padding: 10px;
            border: 1px solid rgba(0, 255, 157, 0.3);
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s;
        }

        .icon-option:hover {
            border-color: #00ff9d;
            background: rgba(0, 255, 157, 0.1);
        }

        .icon-option.selected {
            border-color: #00ff9d;
            background: rgba(0, 255, 157, 0.2);
        }

        .icon-option i {
            font-size: 1.5rem;
            color: #00ff9d;
        }

        /* Alert Messages */
        .alert {
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            animation: slideIn 0.3s;
        }

        .alert-success {
            background: rgba(0, 255, 157, 0.1);
            border: 1px solid #00ff9d;
            color: #00ff9d;
        }

        .alert-error {
            background: rgba(255, 51, 102, 0.1);
            border: 1px solid #ff3366;
            color: #ff3366;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }

        /* Responsive */
        @media (max-width: 768px) {
            .dashboard {
                grid-template-columns: 1fr;
            }
            
            .sidebar {
                display: none;
            }
            
            .main-content {
                padding: 20px;
            }
            
            .icon-grid {
                grid-template-columns: repeat(3, 1fr);
            }
        }
    </style>
</head>
<body>
    <div class="cyber-bg"></div>
    <div class="grid-overlay"></div>

    <div class="dashboard">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="sidebar-header">
                <i class="fas fa-crown"></i>
                <h2>ADMIN PANEL</h2>
                <p class="welcome">Welcome, <?php echo htmlspecialchars($_SESSION['full_name']); ?>!</p>
                <p class="admin-badge"><i class="fas fa-shield-alt"></i> Administrator</p>
            </div>
            
            <nav class="sidebar-nav">
                <ul>
                    <li><a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                    <li><a href="users.php"><i class="fas fa-users"></i> Users</a></li>
                    <li><a href="manage_questions.php"><i class="fas fa-question-circle"></i> Questions</a></li>
                    <li><a href="categories.php" class="active"><i class="fas fa-tags"></i> Categories</a></li>
                    <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                </ul>
            </nav>
        </div>

        <!-- Main Content -->
        <main class="main-content">
            <h1><i class="fas fa-tags"></i> Manage Categories</h1>
            <p class="subtitle">Organize questions by COC (Certificate of Competency)</p>

            <!-- Alert Messages -->
            <?php if ($message): ?>
            <div class="alert alert-<?php echo $message_type; ?>">
                <i class="fas fa-<?php echo $message_type == 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                <?php echo $message; ?>
            </div>
            <?php endif; ?>

            <!-- Action Bar -->
            <div class="action-bar">
                <button class="btn btn-primary" onclick="showAddModal()">
                    <i class="fas fa-plus"></i> Add New Category
                </button>
            </div>

            <!-- Categories Grid -->
            <div class="categories-grid">
                <?php if ($categories_result && $categories_result->num_rows > 0): ?>
                    <?php while ($cat = $categories_result->fetch_assoc()): 
                        $stats = $difficulty_stats[$cat['id']] ?? ['easy_count' => 0, 'medium_count' => 0, 'hard_count' => 0, 'total' => 0];
                        $total = $stats['total'] ?: 1; // Prevent division by zero
                    ?>
                    <div class="category-card">
                        <div class="card-header">
                            <i class="fas fa-<?php echo $cat['icon']; ?>"></i>
                            <h3><?php echo htmlspecialchars($cat['name']); ?></h3>
                        </div>
                        <div class="card-body">
                            <p class="description"><?php echo htmlspecialchars($cat['description']); ?></p>
                            
                            <div class="stats">
                                <div class="stat-item">
                                    <div class="stat-value"><?php echo $cat['question_count']; ?></div>
                                    <div class="stat-label">Questions</div>
                                </div>
                                <div class="stat-item">
                                    <div class="stat-value"><?php echo $stats['easy_count']; ?></div>
                                    <div class="stat-label">Easy</div>
                                </div>
                                <div class="stat-item">
                                    <div class="stat-value"><?php echo $stats['medium_count']; ?></div>
                                    <div class="stat-label">Medium</div>
                                </div>
                            </div>

                            <div class="difficulty-bars">
                                <div class="difficulty-bar-item">
                                    <div class="difficulty-label">
                                        <span>Easy</span>
                                        <span><?php echo round(($stats['easy_count'] / $total) * 100); ?>%</span>
                                    </div>
                                    <div class="bar-container">
                                        <div class="bar-fill" style="width: <?php echo ($stats['easy_count'] / $total) * 100; ?>%"></div>
                                    </div>
                                </div>
                                <div class="difficulty-bar-item">
                                    <div class="difficulty-label">
                                        <span>Medium</span>
                                        <span><?php echo round(($stats['medium_count'] / $total) * 100); ?>%</span>
                                    </div>
                                    <div class="bar-container">
                                        <div class="bar-fill" style="width: <?php echo ($stats['medium_count'] / $total) * 100; ?>%"></div>
                                    </div>
                                </div>
                                <div class="difficulty-bar-item">
                                    <div class="difficulty-label">
                                        <span>Hard</span>
                                        <span><?php echo round(($stats['hard_count'] / $total) * 100); ?>%</span>
                                    </div>
                                    <div class="bar-container">
                                        <div class="bar-fill" style="width: <?php echo ($stats['hard_count'] / $total) * 100; ?>%"></div>
                                    </div>
                                </div>
                            </div>

                            <div class="card-actions">
                                <button class="card-btn" onclick='editCategory(<?php echo json_encode($cat); ?>)'>
                                    <i class="fas fa-edit"></i> Edit
                                </button>
                                <button class="card-btn delete" onclick="deleteCategory(<?php echo $cat['id']; ?>)">
                                    <i class="fas fa-trash"></i> Delete
                                </button>
                            </div>
                        </div>
                    </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div style="grid-column: 1/-1; text-align: center; padding: 60px; background: rgba(10,20,40,0.7); border-radius: 20px;">
                        <i class="fas fa-tags" style="font-size: 4rem; color: #8899cc; margin-bottom: 20px;"></i>
                        <h3 style="color: #00ff9d; margin-bottom: 10px;">No Categories Found</h3>
                        <p style="color: #8899cc;">Click the "Add New Category" button to create your first category.</p>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <!-- Add/Edit Category Modal -->
    <div id="categoryModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalTitle">Add New Category</h2>
                <span class="close" onclick="closeModal()">&times;</span>
            </div>
            
            <form method="POST" action="" id="categoryForm">
                <input type="hidden" name="id" id="categoryId">
                
                <div class="form-group">
                    <label><i class="fas fa-tag"></i> Category Name</label>
                    <input type="text" name="name" id="categoryName" required placeholder="e.g., COC 1: Install and Configure Computer Systems">
                </div>
                
                <div class="form-group">
                    <label><i class="fas fa-align-left"></i> Description</label>
                    <textarea name="description" id="categoryDescription" rows="3" required placeholder="Enter category description..."></textarea>
                </div>
                
                <div class="form-group">
                    <label><i class="fas fa-icons"></i> Icon</label>
                    <input type="hidden" name="icon" id="categoryIcon">
                    <div class="icon-grid" id="iconGrid">
                        <?php foreach ($icons as $icon): ?>
                        <div class="icon-option" onclick="selectIcon('<?php echo $icon; ?>')">
                            <i class="fas fa-<?php echo $icon; ?>"></i>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <div style="display: flex; gap: 10px; justify-content: flex-end;">
                    <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
                    <button type="submit" name="add" id="submitBtn" class="btn btn-primary">Add Category</button>
                    <button type="submit" name="edit" id="updateBtn" class="btn btn-primary" style="display: none;">Update Category</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        let selectedIcon = '';

        function showAddModal() {
            document.getElementById('modalTitle').textContent = 'Add New Category';
            document.getElementById('categoryForm').reset();
            document.getElementById('categoryId').value = '';
            document.getElementById('submitBtn').style.display = 'block';
            document.getElementById('updateBtn').style.display = 'none';
            
            // Reset icon selection
            document.querySelectorAll('.icon-option').forEach(opt => {
                opt.classList.remove('selected');
            });
            selectedIcon = '';
            
            document.getElementById('categoryModal').style.display = 'block';
        }

        function editCategory(category) {
            document.getElementById('modalTitle').textContent = 'Edit Category';
            document.getElementById('categoryId').value = category.id;
            document.getElementById('categoryName').value = category.name;
            document.getElementById('categoryDescription').value = category.description;
            
            // Set icon
            selectedIcon = category.icon;
            document.getElementById('categoryIcon').value = category.icon;
            
            document.querySelectorAll('.icon-option').forEach(opt => {
                const icon = opt.querySelector('i').className.split('-')[1];
                if (icon === category.icon) {
                    opt.classList.add('selected');
                } else {
                    opt.classList.remove('selected');
                }
            });
            
            document.getElementById('submitBtn').style.display = 'none';
            document.getElementById('updateBtn').style.display = 'block';
            document.getElementById('categoryModal').style.display = 'block';
        }

        function deleteCategory(id) {
            if (confirm('Are you sure you want to delete this category? Categories with questions cannot be deleted.')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `<input type="hidden" name="id" value="${id}"><input type="hidden" name="delete" value="1">`;
                document.body.appendChild(form);
                form.submit();
            }
        }

        function selectIcon(icon) {
            selectedIcon = icon;
            document.getElementById('categoryIcon').value = icon;
            
            document.querySelectorAll('.icon-option').forEach(opt => {
                const optIcon = opt.querySelector('i').className.split('-')[1];
                if (optIcon === icon) {
                    opt.classList.add('selected');
                } else {
                    opt.classList.remove('selected');
                }
            });
        }

        function closeModal() {
            document.getElementById('categoryModal').style.display = 'none';
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('categoryModal');
            if (event.target == modal) {
                modal.style.display = 'none';
            }
        }

        // Form validation
        document.getElementById('categoryForm').addEventListener('submit', function(e) {
            if (!selectedIcon && !document.getElementById('categoryId').value) {
                e.preventDefault();
                alert('Please select an icon for the category.');
            }
        });
    </script>
</body>
</html>
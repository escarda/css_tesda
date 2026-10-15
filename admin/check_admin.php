<?php
require_once 'config.php';

echo "<h1>Database Check</h1>";

// Check if users table exists
$tables = $conn->query("SHOW TABLES LIKE 'users'");
if ($tables->num_rows == 0) {
    echo "<p style='color: red;'>✗ Users table doesn't exist! You need to import database.sql</p>";
    exit;
}

// Check all users
$users = $conn->query("SELECT id, username, password, role FROM users");
echo "<h2>Users in database:</h2>";
echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
echo "<tr><th>ID</th><th>Username</th><th>Password Hash</th><th>Role</th><th>Valid?</th></tr>";

while ($user = $users->fetch_assoc()) {
    $valid = (strlen($user['password']) > 20 && !strpos($user['password'], 'YourHashedPassword')) ? '✓' : '✗';
    echo "<tr>";
    echo "<td>" . $user['id'] . "</td>";
    echo "<td>" . $user['username'] . "</td>";
    echo "<td>" . substr($user['password'], 0, 30) . "...</td>";
    echo "<td>" . $user['role'] . "</td>";
    echo "<td>" . $valid . "</td>";
    echo "</tr>";
}
echo "</table>";

// Test password verification
echo "<h2>Test password verification:</h2>";
$admin = $conn->query("SELECT * FROM users WHERE username = 'admin'");
if ($admin->num_rows > 0) {
    $admin_data = $admin->fetch_assoc();
    $test_password = 'admin123';
    
    echo "<p>Testing password 'admin123' for admin user:</p>";
    
    if (password_verify($test_password, $admin_data['password'])) {
        echo "<p style='color: green;'>✓ Password is correct!</p>";
    } else {
        echo "<p style='color: red;'>✗ Password is incorrect!</p>";
        echo "<p>Current hash: " . $admin_data['password'] . "</p>";
        echo "<p>This hash should start with '$2y$10$' and be about 60 characters long</p>";
    }
}

echo "<h2>Quick Fix:</h2>";
echo "<p>Run the fix_admin.php script to automatically fix this issue:</p>";
echo "<p><a href='fix_admin.php' style='padding: 10px; background: #00ff9d; color: black; text-decoration: none; border-radius: 5px;'>Run Fix Admin Script</a></p>";
?>
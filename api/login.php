<?php
// api/login.php
// CSS TESDA Reviewer - Native Android Login API

require_once '../config.php';

header('Content-Type: application/json; charset=utf-8');

// Only POST is allowed
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);

    echo json_encode([
        'success' => false,
        'message' => 'Only POST requests are allowed.',
        'data' => []
    ]);
    exit;
}

// Read JSON body
$raw = file_get_contents('php://input');
$input = json_decode($raw, true);

if (!is_array($input)) {
    http_response_code(400);

    echo json_encode([
        'success' => false,
        'message' => 'Invalid JSON request.',
        'data' => []
    ]);
    exit;
}

// Accept username OR email
$identifier = trim($input['username'] ?? $input['email'] ?? '');
$password = (string)($input['password'] ?? '');

if ($identifier === '' || $password === '') {
    http_response_code(400);

    echo json_encode([
        'success' => false,
        'message' => 'Username/email and password are required.',
        'data' => []
    ]);
    exit;
}

try {

    // Find user
    $user = getUserByUsername($identifier);

    /*
     * If username was not found, also try email.
     * This supports the Android login field accepting
     * either username or email.
     */
    if (!$user) {
        $stmt = $conn->prepare("
            SELECT
                id,
                username,
                full_name,
                email,
                password,
                role
            FROM users
            WHERE email = ?
            LIMIT 1
        ");

        if (!$stmt) {
            throw new Exception($conn->error);
        }

        $stmt->bind_param('s', $identifier);
        $stmt->execute();

        $result = $stmt->get_result();
        $user = $result->fetch_assoc();

        $stmt->close();
    }

    // User not found
    if (!$user) {
        http_response_code(401);

        echo json_encode([
            'success' => false,
            'message' => 'Invalid username/email or password.',
            'data' => []
        ]);
        exit;
    }

    // Verify password
    if (!password_verify($password, $user['password'])) {
        http_response_code(401);

        echo json_encode([
            'success' => false,
            'message' => 'Invalid username/email or password.',
            'data' => []
        ]);
        exit;
    }

    // Create secure session
    session_regenerate_id(true);

    $_SESSION['user_id'] = (int)$user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['full_name'] = $user['full_name'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['role'] = $user['role'];

    // Return user information without password
    $user_data = [
        'id' => (int)$user['id'],
        'username' => $user['username'],
        'full_name' => $user['full_name'],
        'email' => $user['email'],
        'role' => $user['role']
    ];

    echo json_encode([
        'success' => true,
        'message' => 'Login successful.',
        'data' => [
            'user' => $user_data
        ]
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {

    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' => 'Server error.',
        'data' => [],
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}

<?php

error_reporting(E_ALL);
ini_set('display_errors', '1');

// ================================
// SESSION CONFIGURATION
// ================================
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.use_only_cookies', '1');
    ini_set('session.use_strict_mode', '1');
    ini_set('session.cookie_httponly', '1');

    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => true,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);

    session_start();
}

// ================================
// CACHE CONTROL
// ================================
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");

// ================================
// HOSTINGER MYSQL DATABASE
// ================================
$host = 'srv1982.hstgr.io';
$username = 'u911076865_tesda';

// ENTER YOUR HOSTINGER MYSQL PASSWORD HERE
$password = 'Chrisdion12';

$database = 'u911076865_css';
$port = 3306;

// Create database connection
$conn = new mysqli(
    $host,
    $username,
    $password,
    $database,
    $port
);

// Check connection
if ($conn->connect_error) {
    die('Database connection failed. Please check your Hostinger MySQL credentials.');
}

// Set UTF-8
$conn->set_charset('utf8mb4');

// ================================
// SITE CONFIGURATION
// ================================
define('SITE_NAME', 'CSS TESDA Reviewer');
define('SITE_BASE_PATH', '/');
define('SESSION_TIMEOUT', 3600);

// ================================
// AUTHENTICATION FUNCTIONS
// ================================
function isLoggedIn() {
    if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
        return false;
    }

    if (
        isset($_SESSION['last_activity']) &&
        time() - $_SESSION['last_activity'] > SESSION_TIMEOUT
    ) {
        destroySession();
        $_SESSION = [];
        return false;
    }

    $_SESSION['last_activity'] = time();

    // User-Agent fingerprint
    $fingerprint = hash(
        'sha256',
        $_SERVER['HTTP_USER_AGENT'] ?? ''
    );

    if (!isset($_SESSION['fingerprint'])) {
        $_SESSION['fingerprint'] = $fingerprint;
    } elseif (
        !hash_equals(
            $_SESSION['fingerprint'],
            $fingerprint
        )
    ) {
        destroySession();
        $_SESSION = [];
        return false;
    }

    return true;
}

function isAdmin() {
    return isLoggedIn()
        && isset($_SESSION['role'])
        && $_SESSION['role'] === 'admin';
}

// ================================
// REDIRECT FUNCTION
// ================================
function redirect($url) {

    if (preg_match('#^https?://#i', $url)) {
        header('Location: ' . $url);
        exit();
    }

    if (strpos($url, '/') === 0) {
        header('Location: ' . $url);
        exit();
    }

    header(
        'Location: ' .
        SITE_BASE_PATH .
        ltrim($url, '/')
    );

    exit();
}

// ================================
// LOGIN SESSION
// ================================
function regenerateSessionOnLogin(
    $user_id,
    $username,
    $full_name,
    $role
) {
    session_regenerate_id(true);

    $_SESSION['user_id'] = $user_id;
    $_SESSION['username'] = $username;
    $_SESSION['full_name'] = $full_name;
    $_SESSION['role'] = $role;
    $_SESSION['last_activity'] = time();

    $_SESSION['fingerprint'] = hash(
        'sha256',
        $_SERVER['HTTP_USER_AGENT'] ?? ''
    );

    $_SESSION['logged_in_at'] = time();
}

// ================================
// DESTROY SESSION
// ================================
function destroySession() {

    $_SESSION = [];

    if (ini_get('session.use_cookies')) {

        $params = session_get_cookie_params();

        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httponly']
        );
    }

    session_destroy();
}

// ================================
// THEME
// ================================
function loadTheme() {
    return '';
}

// ================================
// BRIGHTNESS
// ================================
function getGlobalBrightness() {

    global $conn;

    $result = $conn->query(
        "SELECT setting_value
         FROM settings
         WHERE setting_key = 'brightness'
         LIMIT 1"
    );

    if ($result && $result->num_rows > 0) {
        return intval(
            $result->fetch_assoc()['setting_value']
        );
    }

    return 100;
}

function updateGlobalBrightness($value) {

    global $conn;

    $value = max(
        50,
        min(150, intval($value))
    );

    $stmt = $conn->prepare(
        "UPDATE settings
         SET setting_value = ?
         WHERE setting_key = 'brightness'"
    );

    if (!$stmt) {
        return false;
    }

    $value = (string)$value;

    $stmt->bind_param('s', $value);

    $ok = $stmt->execute();

    $stmt->close();

    return $ok;
}

function getBrightnessStyle() {

    return '--global-brightness: ' .
        getGlobalBrightness() .
        '%;';
}

// ================================
// SANITIZE
// ================================
function sanitize($input) {

    global $conn;

    return $conn->real_escape_string(
        trim($input)
    );
}

// ================================
// CSRF TOKEN
// ================================
function generateCSRFToken() {

    if (!isset($_SESSION['csrf_token'])) {

        $_SESSION['csrf_token'] =
            bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function verifyCSRFToken($token) {

    return isset($_SESSION['csrf_token'])
        && is_string($token)
        && hash_equals(
            $_SESSION['csrf_token'],
            $token
        );
}

// ================================
// USER FUNCTIONS
// ================================
function getUserById($user_id) {

    global $conn;

    $stmt = $conn->prepare(
        "SELECT id, username, full_name, email, role, created_at
         FROM users
         WHERE id = ?"
    );

    if (!$stmt) {
        return null;
    }

    $stmt->bind_param('i', $user_id);

    $stmt->execute();

    $user = $stmt
        ->get_result()
        ->fetch_assoc();

    $stmt->close();

    return $user;
}

function getUserByUsername($username) {

    global $conn;

    $stmt = $conn->prepare(
        "SELECT id, username, full_name, email, password, role
         FROM users
         WHERE username = ?
         OR email = ?
         LIMIT 1"
    );

    if (!$stmt) {
        return null;
    }

    $stmt->bind_param(
        'ss',
        $username,
        $username
    );

    $stmt->execute();

    $user = $stmt
        ->get_result()
        ->fetch_assoc();

    $stmt->close();

    return $user;
}

// ================================
// CATEGORY FUNCTIONS
// ================================
function getCategories() {

    global $conn;

    $result = $conn->query(
        "SELECT *
         FROM categories
         ORDER BY id"
    );

    $categories = [];

    if ($result) {

        while ($row = $result->fetch_assoc()) {
            $categories[] = $row;
        }
    }

    return $categories;
}

// ================================
// QUESTION FUNCTIONS
// ================================
function getQuestionsByCategory($category_id) {

    global $conn;

    $stmt = $conn->prepare(
        "SELECT id,
                question_text,
                option_a,
                option_b,
                option_c,
                option_d,
                correct_answer,
                explanation
         FROM questions
         WHERE category_id = ?
         AND is_active = 1
         ORDER BY RAND()"
    );

    if (!$stmt) {
        return [];
    }

    $stmt->bind_param(
        'i',
        $category_id
    );

    $stmt->execute();

    $result = $stmt->get_result();

    $questions = [];

    while ($row = $result->fetch_assoc()) {
        $questions[] = $row;
    }

    $stmt->close();

    return $questions;
}

// ================================
// QUIZ RESULTS
// ================================
function getUserQuizResults(
    $user_id,
    $category_id = null
) {

    global $conn;

    $sql =
        "SELECT *
         FROM quizzes
         WHERE user_id = ?";

    if ($category_id !== null) {
        $sql .= " AND category_id = ?";
    }

    $sql .= " ORDER BY completed_at DESC";

    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        return [];
    }

    if ($category_id !== null) {

        $stmt->bind_param(
            'ii',
            $user_id,
            $category_id
        );

    } else {

        $stmt->bind_param(
            'i',
            $user_id
        );
    }

    $stmt->execute();

    $result = $stmt->get_result();

    $results = [];

    while ($row = $result->fetch_assoc()) {
        $results[] = $row;
    }

    $stmt->close();

    return $results;
}

// ================================
// USER PROGRESS
// ================================
function getUserProgress($user_id) {

    global $conn;

    $coc_progress = [];

    for ($coc = 1; $coc <= 4; $coc++) {

        $stmt = $conn->prepare(
            "SELECT COUNT(*) AS count,
                    MAX(
                        score /
                        NULLIF(total_questions, 0)
                        * 100
                    ) AS best_score
             FROM quizzes
             WHERE user_id = ?
             AND category_id = ?"
        );

        if (!$stmt) {

            $coc_progress[$coc] = [
                'attempted' => false,
                'best_score' => 0,
                'passed' => false
            ];

            continue;
        }

        $stmt->bind_param(
            'ii',
            $user_id,
            $coc
        );

        $stmt->execute();

        $data = $stmt
            ->get_result()
            ->fetch_assoc();

        $stmt->close();

        $best_score =
            floatval(
                $data['best_score'] ?? 0
            );

        $coc_progress[$coc] = [

            'attempted' =>
                intval($data['count'] ?? 0) > 0,

            'best_score' =>
                round($best_score, 1),

            'passed' =>
                $best_score >= 75
        ];
    }

    return $coc_progress;
}

// ================================
// MAINTENANCE MODE
// ================================
function isMaintenanceMode() {

    global $conn;

    $result = $conn->query(
        "SELECT is_enabled
         FROM system_maintenance
         WHERE id = 1
         LIMIT 1"
    );

    if ($result && $result->num_rows > 0) {

        return intval(
            $result->fetch_assoc()['is_enabled']
        ) === 1;
    }

    return false;
}

function getMaintenanceMessage() {

    global $conn;

    $result = $conn->query(
        "SELECT message
         FROM system_maintenance
         WHERE id = 1
         LIMIT 1"
    );

    if ($result && $result->num_rows > 0) {

        return $result
            ->fetch_assoc()['message']
            ?? 'System is currently under maintenance.';
    }

    return 'System is currently under maintenance.';
}

// ================================
// DEBUG
// ================================
function debug($data) {

    echo '<pre>';

    print_r($data);

    echo '</pre>';
}

// ================================
// CURRENT USER
// ================================
function getCurrentUserId() {

    return $_SESSION['user_id'] ?? null;
}

function getCurrentUserRole() {

    return $_SESSION['role'] ?? 'user';
}

// ================================
// TIMEZONE
// ================================
date_default_timezone_set('Asia/Manila');


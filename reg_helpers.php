<?php
// includes/reg_helpers.php - helpers for the registration-code system.
// Load config.php first ($conn = mysqli, session started).
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT); // make DB errors throw so transactions roll back cleanly

if (!defined('REG_PASSWORD_COLUMN')) define('REG_PASSWORD_COLUMN', 'password'); // column in users that stores the hash
if (!defined('REG_STUDENT_ROLE'))    define('REG_STUDENT_ROLE', 'student');
if (!defined('REG_ADMIN_ROLE'))      define('REG_ADMIN_ROLE', 'admin');
if (!defined('REG_CODE_PEPPER'))     { http_response_code(500); exit('Setup error: define REG_CODE_PEPPER in config.php.'); }

const REG_MAX_FAILED_ATTEMPTS = 10;   // failed code checks allowed per IP...
const REG_ATTEMPT_WINDOW_MIN  = 15;   // ...within this many minutes

function reg_h($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

// Uses the CSRF helpers already defined in config.php
function reg_csrf_token() { return generateCSRFToken(); }
function reg_csrf_ok()    { return verifyCSRFToken($_POST['csrf'] ?? ''); }

function reg_ip() { return substr($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0', 0, 45); }

// CSS-XXXX-XXXX-XXXX, 12 random chars from a 31-char alphabet (~59 bits), no modulo bias.
function reg_generate_code() {
    $alphabet = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';
    $out = '';
    while (strlen($out) < 12) {
        $bytes = random_bytes(16);
        for ($i = 0; $i < 16 && strlen($out) < 12; $i++) {
            $b = ord($bytes[$i]);
            if ($b >= 248) continue;              // 248 = 31 * 8
            $out .= $alphabet[$b % 31];
        }
    }
    return 'CSS-' . substr($out, 0, 4) . '-' . substr($out, 4, 4) . '-' . substr($out, 8, 4);
}

// Accepts "css 7k9p x4dm q2rt", "CSS7K9PX4DMQ2RT", etc. Returns canonical form or null.
function reg_normalize_code($raw) {
    $c = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (string)$raw));
    if (strlen($c) === 15 && substr($c, 0, 3) === 'CSS') $c = substr($c, 3);
    if (strlen($c) !== 12) return null;
    return 'CSS-' . substr($c, 0, 4) . '-' . substr($c, 4, 4) . '-' . substr($c, 8, 4);
}

function reg_hash_code($canonical) { return hash_hmac('sha256', $canonical, REG_CODE_PEPPER); }

function reg_audit($conn, $userId, $action, $details = '') {
    $ip = reg_ip();
    $details = substr($details, 0, 500);
    $stmt = $conn->prepare('INSERT INTO admin_audit_log (user_id, action, details, ip_address) VALUES (?,?,?,?)');
    $stmt->bind_param('isss', $userId, $action, $details, $ip);
    $stmt->execute();
    $stmt->close();
}

function reg_rate_limited($conn) {
    $ip = reg_ip(); $win = REG_ATTEMPT_WINDOW_MIN;
    $stmt = $conn->prepare('SELECT COUNT(*) FROM registration_attempts WHERE ip_address=? AND created_at > (NOW() - INTERVAL ? MINUTE)');
    $stmt->bind_param('si', $ip, $win);
    $stmt->execute();
    $stmt->bind_result($n);
    $stmt->fetch();
    $stmt->close();
    return $n >= REG_MAX_FAILED_ATTEMPTS;
}

function reg_record_failed_attempt($conn) {
    $ip = reg_ip();
    $stmt = $conn->prepare('INSERT INTO registration_attempts (ip_address) VALUES (?)');
    $stmt->bind_param('s', $ip);
    $stmt->execute();
    $stmt->close();
    $conn->query('DELETE FROM registration_attempts WHERE created_at < (NOW() - INTERVAL 1 DAY)');
}

// Server-side admin gate: checks the role in the database on every request.
function reg_require_admin($conn) {
    if (!isLoggedIn()) { redirect('login.php'); }
    $uid = (int)$_SESSION['user_id'];
    $stmt = $conn->prepare('SELECT role, is_active FROM users WHERE id=? LIMIT 1');
    $stmt->bind_param('i', $uid);
    $stmt->execute();
    $stmt->bind_result($role, $active);
    $found = $stmt->fetch();
    $stmt->close();
    if (!$found || strtolower((string)$role) !== REG_ADMIN_ROLE || (int)$active !== 1) {
        http_response_code(403);
        exit('Access denied.');
    }
    return $uid;
}
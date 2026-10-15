<?php
// register.php - CSS TESDA Reviewer registration with admin-issued one-time code
require_once __DIR__ . '/config.php';

if (isLoggedIn()) {
    redirect('dashboard.php');
}

$error = '';
$success = '';
$full_name = '';
$email = '';
$username = '';
$registration_code = '';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
if (empty($_SESSION['register_csrf'])) {
    $_SESSION['register_csrf'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $registration_code = strtoupper(trim($_POST['registration_code'] ?? ''));
    $password = (string)($_POST['password'] ?? '');
    $confirm_password = (string)($_POST['confirm_password'] ?? '');
    $terms = isset($_POST['terms']);
    $csrf = (string)($_POST['csrf_token'] ?? '');

    if (!hash_equals($_SESSION['register_csrf'], $csrf)) {
        $error = 'Security check failed. Refresh the page and try again.';
    } elseif ($full_name === '' || $email === '' || $username === '' ||
              $registration_code === '' || $password === '' || $confirm_password === '') {
        $error = 'Please complete all required fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($username) < 3 || strlen($username) > 50) {
        $error = 'Username must be between 3 and 50 characters.';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters long.';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } elseif (!$terms) {
        $error = 'You must agree to the Terms of Service and Privacy Policy.';
    } else {
        // This table is created using the SQL provided with this file.
        $checkCode = $conn->prepare(
            "SELECT id, is_used, expires_at FROM registration_codes WHERE code = ? LIMIT 1"
        );

        if (!$checkCode) {
            $error = 'Registration code system is not set up yet. Ask the administrator to install the registration_codes table.';
        } else {
            $checkCode->bind_param('s', $registration_code);
            $checkCode->execute();
            $codeResult = $checkCode->get_result();
            $codeRow = $codeResult ? $codeResult->fetch_assoc() : null;
            $checkCode->close();

            if (!$codeRow) {
                $error = 'Invalid registration code. Please ask the administrator for a valid code.';
            } elseif ((int)$codeRow['is_used'] === 1) {
                $error = 'This registration code has already been used.';
            } elseif (!empty($codeRow['expires_at']) && strtotime($codeRow['expires_at']) < time()) {
                $error = 'This registration code has expired. Ask the administrator for a new one.';
            } else {
                $duplicate = $conn->prepare(
                    "SELECT id FROM users WHERE username = ? OR email = ? LIMIT 1"
                );
                if (!$duplicate) {
                    $error = 'Unable to validate your details. Please try again.';
                } else {
                    $duplicate->bind_param('ss', $username, $email);
                    $duplicate->execute();
                    $duplicateResult = $duplicate->get_result();
                    $alreadyExists = $duplicateResult && $duplicateResult->num_rows > 0;
                    $duplicate->close();

                    if ($alreadyExists) {
                        $error = 'Username or email is already registered.';
                    } else {
                        // Transaction + row lock prevents the same code being used twice.
                        $conn->begin_transaction();
                        try {
                            $lock = $conn->prepare(
                                "SELECT id, is_used, expires_at FROM registration_codes WHERE id = ? FOR UPDATE"
                            );
                            if (!$lock) {
                                throw new Exception('Could not verify registration code.');
                            }
                            $codeId = (int)$codeRow['id'];
                            $lock->bind_param('i', $codeId);
                            $lock->execute();
                            $lockedResult = $lock->get_result();
                            $lockedCode = $lockedResult ? $lockedResult->fetch_assoc() : null;
                            $lock->close();

                            if (!$lockedCode || (int)$lockedCode['is_used'] === 1 ||
                                (!empty($lockedCode['expires_at']) && strtotime($lockedCode['expires_at']) < time())) {
                                throw new Exception('This registration code is no longer valid. Please request a new code.');
                            }

                            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                            $role = 'user'; // Never allow public registration to create an admin.
                            $insert = $conn->prepare(
                                "INSERT INTO users (full_name, email, username, password, role) VALUES (?, ?, ?, ?, ?)"
                            );
                            if (!$insert) {
                                throw new Exception('Unable to create your account. Please try again.');
                            }
                            $insert->bind_param('sssss', $full_name, $email, $username, $hashedPassword, $role);
                            if (!$insert->execute()) {
                                $insert->close();
                                throw new Exception('Registration failed. Please check your details and try again.');
                            }
                            $newUserId = (int)$insert->insert_id;
                            $insert->close();

                            $useCode = $conn->prepare(
                                "UPDATE registration_codes SET is_used = 1, used_by = ?, used_at = NOW() WHERE id = ? AND is_used = 0"
                            );
                            if (!$useCode) {
                                throw new Exception('Could not mark registration code as used.');
                            }
                            $useCode->bind_param('ii', $newUserId, $codeId);
                            if (!$useCode->execute() || $useCode->affected_rows !== 1) {
                                $useCode->close();
                                throw new Exception('This registration code has already been used. Please request a new code.');
                            }
                            $useCode->close();

                            $conn->commit();
                            $_SESSION['register_csrf'] = bin2hex(random_bytes(32));
                            $success = 'Registration successful! You can now log in using your username and password.';
                            $full_name = $email = $username = $registration_code = '';
                        } catch (Throwable $e) {
                            $conn->rollback();
                            $error = $e->getMessage();
                            if ($error === '') {
                                $error = 'Registration failed. Please try again.';
                            }
                        }
                    }
                }
            }
        }
    }
}

$global_brightness = function_exists('getGlobalBrightness') ? getGlobalBrightness() : 100;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
<title>Student Registration | CSS TESDA Reviewer</title>
<link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&family=Rajdhani:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<style>
:root{color-scheme:dark;--bg:#070b16;--card:rgba(12,21,39,.94);--green:#00ff9d;--blue:#1684ff;--text:#e7f4ff;--muted:#8ea2bd;--line:rgba(0,255,157,.27);--danger:#ff6377}
*{box-sizing:border-box}
html{min-height:100%;background:var(--bg)}
body{margin:0;min-height:100vh;min-height:100dvh;font-family:'Rajdhani',sans-serif;color:var(--text);background:radial-gradient(ellipse at 15% 10%,#10233a 0%,transparent 42%),radial-gradient(ellipse at 85% 90%,#101b38 0%,transparent 42%),var(--bg);filter:brightness(<?php echo max(50,min(120,(int)$global_brightness)); ?>%);overflow-x:hidden}
body:before{content:"";position:fixed;inset:0;z-index:0;pointer-events:none;background-image:linear-gradient(rgba(0,255,157,.035) 1px,transparent 1px),linear-gradient(90deg,rgba(0,255,157,.035) 1px,transparent 1px);background-size:50px 50px}
.wrap{position:relative;z-index:1;min-height:100vh;min-height:100dvh;display:flex;align-items:center;justify-content:center;padding:76px 16px 24px}
.card{width:100%;max-width:520px;padding:clamp(22px,5vw,38px);background:var(--card);border:1px solid var(--line);border-radius:24px;box-shadow:0 22px 65px rgba(0,0,0,.48),0 0 28px rgba(0,255,157,.07);backdrop-filter:blur(16px)}
.logo{text-align:center;margin-bottom:25px}.logo-icon{width:68px;height:68px;margin:0 auto 14px;border-radius:20px;display:grid;place-items:center;background:rgba(0,255,157,.09);border:1px solid var(--line);color:var(--green);font-size:30px;box-shadow:0 0 28px rgba(0,255,157,.1)}
h1{font-family:Orbitron,sans-serif;font-size:clamp(1.15rem,4vw,1.55rem);line-height:1.35;margin:0 0 8px;background:linear-gradient(100deg,var(--green),#66b9ff);background-clip:text;-webkit-background-clip:text;color:transparent}
.logo p{margin:0;color:var(--muted);font-size:1rem}
.notice{padding:12px 14px;border-radius:12px;margin-bottom:17px;font-size:.98rem;line-height:1.4}.error{background:rgba(255,99,119,.1);border:1px solid rgba(255,99,119,.5);color:#ff9baa}.success{background:rgba(0,255,157,.08);border:1px solid var(--green);color:var(--green)}
.group{margin:0 0 15px}.group label{display:block;margin-bottom:6px;color:#b8c9df;font-weight:700;font-size:.96rem;letter-spacing:.02em}.field{position:relative}.field>i{position:absolute;left:14px;top:50%;transform:translateY(-50%);color:var(--green);pointer-events:none}.field input{width:100%;min-height:48px;border:1px solid rgba(123,157,191,.28);border-radius:11px;background:rgba(2,7,16,.72);padding:12px 44px 12px 42px;color:var(--text);font:600 1rem 'Rajdhani',sans-serif;outline:none;transition:.2s}.field input:focus{border-color:var(--green);box-shadow:0 0 0 3px rgba(0,255,157,.09)}.field input::placeholder{color:#60738b}.showpass{position:absolute;right:9px;top:50%;transform:translateY(-50%);border:0;background:transparent;color:var(--muted);padding:8px;cursor:pointer}
.code-hint{font-size:.85rem;color:var(--muted);margin-top:5px}.terms{display:flex;align-items:flex-start;gap:9px;color:var(--muted);font-size:.92rem;line-height:1.45;margin:18px 0}.terms input{margin-top:4px;accent-color:var(--green);width:17px;height:17px;flex-shrink:0}.terms a,.login a{color:var(--green);text-decoration:none}.terms a:hover,.login a:hover{text-decoration:underline}
.submit{width:100%;min-height:51px;border:0;border-radius:12px;background:linear-gradient(110deg,var(--green),#4ba7ff);color:#06111a;font:900 .92rem Orbitron,sans-serif;cursor:pointer;box-shadow:0 8px 22px rgba(0,255,157,.12);transition:transform .2s,box-shadow .2s}.submit:hover{transform:translateY(-2px);box-shadow:0 12px 28px rgba(0,255,157,.2)}.login{text-align:center;color:var(--muted);margin:18px 0 0;font-size:1rem}.top-actions{position:absolute;top:17px;right:17px;z-index:2}.top-actions a{color:var(--green);border:1px solid var(--line);background:rgba(0,255,157,.06);padding:9px 13px;border-radius:999px;text-decoration:none;font-weight:700}
@media(max-width:480px){.wrap{padding:70px 12px 18px}.card{border-radius:18px}.logo{margin-bottom:20px}.group{margin-bottom:13px}.field input{font-size:16px}.top-actions{top:12px;right:12px}.top-actions a{font-size:.85rem;padding:8px 11px}}
</style>
</head>
<body>
<div class="top-actions"><a href="login.php"><i class="fas fa-right-to-bracket"></i> Login</a></div>
<main class="wrap">
<section class="card">
  <header class="logo">
    <div class="logo-icon"><i class="fas fa-user-shield"></i></div>
    <h1>STUDENT REGISTRATION</h1>
    <p>CSS TESDA Assessment Reviewer</p>
  </header>

  <?php if ($error !== ''): ?><div class="notice error" role="alert"><i class="fas fa-circle-exclamation"></i> <?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>
  <?php if ($success !== ''): ?><div class="notice success" role="status"><i class="fas fa-circle-check"></i> <?php echo htmlspecialchars($success, ENT_QUOTES, 'UTF-8'); ?> <a href="login.php" style="color:inherit;font-weight:700">Proceed to Login <i class="fas fa-arrow-right"></i></a></div><?php endif; ?>

  <form method="POST" action="register.php" autocomplete="on">
    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['register_csrf'], ENT_QUOTES, 'UTF-8'); ?>">

    <div class="group">
      <label for="full_name">Full Name</label>
      <div class="field"><i class="fas fa-id-card"></i><input id="full_name" name="full_name" type="text" placeholder="Enter your complete name" value="<?php echo htmlspecialchars($full_name, ENT_QUOTES, 'UTF-8'); ?>" maxlength="150" autocomplete="name" required></div>
    </div>
    <div class="group">
      <label for="email">Email Address</label>
      <div class="field"><i class="fas fa-envelope"></i><input id="email" name="email" type="email" placeholder="student@example.com" value="<?php echo htmlspecialchars($email, ENT_QUOTES, 'UTF-8'); ?>" maxlength="190" autocomplete="email" required></div>
    </div>
    <div class="group">
      <label for="username">Username</label>
      <div class="field"><i class="fas fa-user"></i><input id="username" name="username" type="text" placeholder="Create a username" value="<?php echo htmlspecialchars($username, ENT_QUOTES, 'UTF-8'); ?>" minlength="3" maxlength="50" autocomplete="username" required></div>
    </div>
    <div class="group">
      <label for="registration_code">Admin Registration Code</label>
      <div class="field"><i class="fas fa-key"></i><input id="registration_code" name="registration_code" type="text" placeholder="Enter the code given by admin" value="<?php echo htmlspecialchars($registration_code, ENT_QUOTES, 'UTF-8'); ?>" maxlength="50" autocapitalize="characters" autocomplete="off" required></div>
      <div class="code-hint"><i class="fas fa-lock"></i> Only students with a valid code issued by the administrator can register.</div>
    </div>
    <div class="group">
      <label for="password">Password</label>
      <div class="field"><i class="fas fa-lock"></i><input id="password" name="password" type="password" placeholder="At least 8 characters" minlength="8" autocomplete="new-password" required><button class="showpass" type="button" data-target="password" aria-label="Show password"><i class="fas fa-eye"></i></button></div>
    </div>
    <div class="group">
      <label for="confirm_password">Confirm Password</label>
      <div class="field"><i class="fas fa-shield-halved"></i><input id="confirm_password" name="confirm_password" type="password" placeholder="Re-enter your password" minlength="8" autocomplete="new-password" required><button class="showpass" type="button" data-target="confirm_password" aria-label="Show confirm password"><i class="fas fa-eye"></i></button></div>
    </div>

    <label class="terms"><input type="checkbox" name="terms" value="1" required><span>I agree to the <a href="terms.php" target="_blank" rel="noopener">Terms of Service</a> and <a href="privacy.php" target="_blank" rel="noopener">Privacy Policy</a>. If those pages are not available on your site, update these links to your existing policy page.</span></label>
    <button class="submit" type="submit"><i class="fas fa-user-plus"></i> CREATE STUDENT ACCOUNT</button>
  </form>
  <p class="login">Already registered? <a href="login.php">Log in here</a></p>
</section>
</main>
<script>
document.querySelectorAll('.showpass').forEach(function(button){
  button.addEventListener('click', function(){
    const input = document.getElementById(button.dataset.target);
    if (!input) return;
    const showing = input.type === 'text';
    input.type = showing ? 'password' : 'text';
    const icon = button.querySelector('i');
    if (icon) icon.className = showing ? 'fas fa-eye' : 'fas fa-eye-slash';
    button.setAttribute('aria-label', showing ? 'Show password' : 'Hide password');
  });
});
</script>
</body>
</html>

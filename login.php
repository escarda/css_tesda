<?php

error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once 'config.php';

// If already logged in, redirect to dashboard
if (isLoggedIn()) {
    redirect('dashboard.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get and sanitize input manually
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    // Validate input
    if (empty($username) || empty($password)) {
        $error = 'Please enter both username and password';
    } else {
        // Prepare statement to prevent SQL injection
        $stmt = $conn->prepare("SELECT id, username, full_name, password, role FROM users WHERE username = ? OR email = ?");
        $stmt->bind_param("ss", $username, $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();

            // Verify password
           if (password_verify($password, $user['password'])) {
    regenerateSessionOnLogin(
        $user['id'],
        $user['username'],
        $user['full_name'],
        $user['role']
    );
    redirect('dashboard.php');
}else {
                $error = 'Invalid username or password';
            }
        } else {
            $error = 'Invalid username or password';
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Login - CSS TESDA Reviewer</title>
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&family=Rajdhani:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html, body {
            width: 100%;
            overflow-x: hidden;
        }

        body {
            font-family: 'Rajdhani', sans-serif;
            background: #0a0f1e;
            min-height: 100vh;
            position: relative;
            filter: brightness(var(--global-brightness, 100%));
        }

        .login-wrapper {
            width: 100%;
            min-height: 100vh;
            min-height: 100dvh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 4.5rem 1rem 1.5rem;
        }

        .cyber-bg {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -2;
            background: radial-gradient(circle at 20% 30%, #0f172a, #020617);
        }

        .grid-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: linear-gradient(rgba(0, 255, 157, 0.03) 1px, transparent 1px), 
                              linear-gradient(90deg, rgba(0, 255, 157, 0.03) 1px, transparent 1px);
            background-size: 50px 50px;
            z-index: -1;
            animation: gridMove 20s linear infinite;
        }

        @keyframes gridMove {
            0% { transform: translate(0, 0); }
            100% { transform: translate(50px, 50px); }
        }

        .particles {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
            pointer-events: none;
        }

        .particle {
            position: absolute;
            width: 4px;
            height: 4px;
            background: #00ff9d;
            border-radius: 50%;
            opacity: 0.5;
            animation: floatParticle 15s linear infinite;
        }

        @keyframes floatParticle {
            0% { transform: translateY(100vh) translateX(0); opacity: 0; }
            10% { opacity: 0.5; }
            90% { opacity: 0.5; }
            100% { transform: translateY(-20vh) translateX(100px); opacity: 0; }
        }

        .login-container {
            width: 100%;
            max-width: 450px;
            margin: 5rem auto 0;
        }

        .login-card {
            background: rgba(15, 23, 42, 0.8);
            backdrop-filter: blur(20px);
            border: 2px solid rgba(0, 255, 157, 0.3);
            border-radius: 32px;
            padding: 2.5rem;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.5);
            animation: fadeInUp 0.6s ease;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .logo {
            text-align: center;
            margin-bottom: 2rem;
        }

        .logo i {
            font-size: 3.5rem;
            color: #00ff9d;
            margin-bottom: 1rem;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }

        .logo h1 {
            font-family: 'Orbitron', sans-serif;
            font-size: 1.8rem;
            background: linear-gradient(135deg, #00ff9d, #3b82f6, #a855f7);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 0.5rem;
        }

        .logo p {
            color: #94a3b8;
            font-size: 0.9rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: #00ff9d;
            font-weight: 600;
            font-size: 0.9rem;
        }

        .input-group {
            position: relative;
            display: flex;
            align-items: center;
        }

        .input-group i {
            position: absolute;
            left: 15px;
            color: #00ff9d;
            font-size: 1rem;
        }

        .input-group input {
            width: 100%;
            padding: 12px 15px 12px 45px;
            background: rgba(0, 0, 0, 0.5);
            border: 1px solid rgba(0, 255, 157, 0.3);
            border-radius: 12px;
            color: #e0e0ff;
            font-family: 'Rajdhani', sans-serif;
            font-size: 1rem;
            transition: all 0.3s;
        }

        .input-group input:focus {
            outline: none;
            border-color: #00ff9d;
            box-shadow: 0 0 10px rgba(0, 255, 157, 0.3);
        }

        .input-group input::placeholder {
            color: #64748b;
        }

        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 1.5rem;
        }

        .checkbox-group input {
            width: 18px;
            height: 18px;
            accent-color: #00ff9d;
        }

        .checkbox-group label {
            margin-bottom: 0;
            font-size: 0.85rem;
            color: #94a3b8;
        }

        .btn-login {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #00ff9d, #3b82f6);
            border: none;
            border-radius: 12px;
            color: #0f172a;
            font-family: 'Orbitron', sans-serif;
            font-size: 1rem;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s;
            margin-bottom: 1rem;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(0, 255, 157, 0.4);
        }

        .btn-login:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none !important;
        }

        .error-message {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid #ef4444;
            border-radius: 12px;
            padding: 12px;
            margin-bottom: 1.5rem;
            color: #ef4444;
            font-size: 0.85rem;
            text-align: center;
        }

        .register-link {
            text-align: center;
            color: #94a3b8;
            font-size: 0.85rem;
        }

        .register-link a {
            color: #00ff9d;
            text-decoration: none;
            font-weight: bold;
        }

        .register-link a:hover {
            text-decoration: underline;
        }

        .back-button {
            position: fixed;
            top: 20px;
            left: 20px;
            z-index: 10000;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 14px;
            border: 1px solid rgba(0, 255, 157, 0.35);
            border-radius: 999px;
            background: rgba(0, 255, 157, 0.08);
            color: #00ff9d;
            font-family: 'Rajdhani', sans-serif;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            white-space: nowrap;
            text-decoration: none;
        }

        .back-button:hover {
            background: #00ff9d;
            color: #050812;
            transform: translateY(-1px);
            box-shadow: 0 5px 15px rgba(0, 255, 157, 0.25);
        }

        body.light-mode .back-button {
            border-color: rgba(0, 130, 95, 0.35);
            background: rgba(0, 150, 105, 0.08);
            color: #008a65;
        }

        body.light-mode .back-button:hover {
            background: #00a878;
            color: #ffffff;
        }

        .theme-toggle {
            position: fixed;
            top: 20px;
            right: 20px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 12px;
            border: 1px solid rgba(0, 255, 157, 0.35);
            border-radius: 999px;
            background: rgba(0, 255, 157, 0.08);
            color: #00ff9d;
            font-family: 'Rajdhani', sans-serif;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            white-space: nowrap;
            z-index: 1000;
        }

        .theme-toggle:hover {
            background: #00ff9d;
            color: #050812;
            transform: translateY(-1px);
            box-shadow: 0 5px 15px rgba(0, 255, 157, 0.25);
        }

        body.light-mode {
            background: #f4f7fb;
            color: #172033;
        }

        body.light-mode .cyber-bg {
            background: radial-gradient(circle at 20% 30%, #ffffff, #eaf1f7);
        }

        body.light-mode .grid-overlay {
            background-image:
                linear-gradient(rgba(0, 150, 105, 0.04) 1px, transparent 1px),
                linear-gradient(90deg, rgba(0, 150, 105, 0.04) 1px, transparent 1px);
        }

        body.light-mode .particle {
            background: #00a878;
        }

        body.light-mode .login-card {
            background: rgba(255, 255, 255, 0.90);
            border-color: rgba(0, 150, 105, 0.28);
            box-shadow: 0 25px 50px rgba(50, 70, 90, 0.18);
        }

        body.light-mode .logo p,
        body.light-mode .checkbox-group label,
        body.light-mode .register-link {
            color: #526174;
        }

        body.light-mode .input-group input {
            background: rgba(235, 240, 245, 0.9);
            border-color: rgba(0, 150, 105, 0.3);
            color: #172033;
        }

        body.light-mode .input-group input::placeholder {
            color: #718096;
        }

        body.light-mode .theme-toggle {
            border-color: rgba(0, 130, 95, 0.35);
            background: rgba(0, 150, 105, 0.08);
            color: #008a65;
        }

        body.light-mode .theme-toggle:hover {
            background: #00a878;
            color: #ffffff;
        }

        @media (max-width: 768px) {
            .login-card {
                padding: 1.5rem;
            }

            .logo h1 {
                font-size: 1.5rem;
            }
        }

        @media (max-width: 480px) {
            .login-wrapper {
                padding: 4rem 0.75rem 1rem;
            }

            .login-container {
                margin-top: 3rem;
            }

            .login-card {
                padding: 1.2rem;
                border-radius: 20px;
            }

            .theme-toggle {
                top: 12px;
                right: 12px;
            }

            .back-button {
                top: 12px;
                left: 12px;
            }
        }
    </style>
</head>
<body>
    <div class="cyber-bg"></div>
    <div class="grid-overlay"></div>
    <div class="particles" id="particles"></div>

    <a href="index.php" class="back-button" aria-label="Back to index" title="Back">
        <i class="fas fa-arrow-left"></i>
        <span>Back</span>
    </a>

    <button type="button" class="theme-toggle" id="themeToggle"
            aria-label="Toggle light and dark mode"
            title="Toggle light and dark mode">
        <i class="fas fa-moon" id="themeIcon"></i>
        <span id="themeText">Dark Mode</span>
    </button>

    <div class="login-wrapper">
        <div class="login-container">
            <div class="login-card">
                <div class="logo">
                    <i class="fas fa-microchip"></i>
                    <h1>CSS REVIEWER</h1>
                    <p>Computer Systems Servicing</p>
                </div>

                <?php if ($error): ?>
                    <div class="error-message">
                        <i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="">
                    <div class="form-group">
                        <label><i class="fas fa-user"></i> Username or Email</label>
                        <div class="input-group">
                            <i class="fas fa-envelope"></i>
                            <input type="text" name="username" placeholder="Enter your username or email" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label><i class="fas fa-lock"></i> Password</label>
                        <div class="input-group">
                            <i class="fas fa-key"></i>
                            <input type="password" name="password" placeholder="Enter your password" required>
                        </div>
                    </div>

                    <div class="checkbox-group">
                        <input type="checkbox" name="remember" id="remember">
                        <label for="remember">Remember me</label>
                    </div>

                    <button type="submit" class="btn-login">
                        <i class="fas fa-sign-in-alt"></i> Login
                    </button>
                </form>

                <div class="register-link">
                    Don't have an account? <a href="register.php">Register here</a>
                </div>
            </div>
        </div>
    </div>

    <script>
        function createParticles() {
            const container = document.getElementById('particles');
            for (let i = 0; i < 50; i++) {
                const particle = document.createElement('div');
                particle.className = 'particle';
                particle.style.left = Math.random() * 100 + '%';
                particle.style.width = Math.random() * 5 + 2 + 'px';
                particle.style.height = particle.style.width;
                particle.style.animationDelay = Math.random() * 15 + 's';
                particle.style.animationDuration = Math.random() * 10 + 10 + 's';
                container.appendChild(particle);
            }
        }

        createParticles();

        document.querySelector('form')?.addEventListener('submit', function(e) {
            const btn = this.querySelector('.btn-login');
            if (btn) {
                btn.disabled = true;
                btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Logging in...';
            }
        });
    </script>
    <script>
        (function () {
            const themeToggle = document.getElementById('themeToggle');
            const themeIcon = document.getElementById('themeIcon');
            const themeText = document.getElementById('themeText');

            function applyTheme(theme) {
                const isLight = theme === 'light';
                document.body.classList.toggle('light-mode', isLight);

                if (themeIcon) {
                    themeIcon.className = isLight ? 'fas fa-sun' : 'fas fa-moon';
                }

                if (themeText) {
                    themeText.textContent = isLight ? 'Light Mode' : 'Dark Mode';
                }
            }

            const savedTheme = localStorage.getItem('css_tesda_theme') || 'dark';
            applyTheme(savedTheme);

            if (themeToggle) {
                themeToggle.addEventListener('click', function () {
                    const nextTheme =
                        document.body.classList.contains('light-mode') ? 'dark' : 'light';

                    localStorage.setItem('css_tesda_theme', nextTheme);
                    applyTheme(nextTheme);
                });
            }
        })();
    </script>
    <style>
        #login-brightness-widget {
            position: fixed;
            right: 20px;
            bottom: 20px;
            z-index: 2147483647;
        }
    </style>
    <div id="login-brightness-widget">
        <?php if (file_exists(__DIR__ . '/includes/brightness_widget.php')) { $global_brightness = function_exists('getGlobalBrightness') ? getGlobalBrightness() : 100; include __DIR__ . '/includes/brightness_widget.php'; } ?>
    </div>

    <script>
    (function () {
        'use strict';

        function applyBrightness(value) {
            var n = parseFloat(value);

            if (!isFinite(n)) {
                return;
            }

            if (n > 0 && n <= 1) {
                n *= 100;
            }

            n = Math.max(10, Math.min(200, n));

            document.documentElement.style.setProperty(
                '--global-brightness',
                n + '%'
            );

            try {
                localStorage.setItem('css_tesda_brightness', String(n));
            } catch (e) {}
        }

        function connectBrightness() {
            var controls = document.querySelectorAll(
                '#login-brightness-widget input[type="range"],' +
                'input[type="range"][id*="bright" i],' +
                'input[type="range"][name*="bright" i],' +
                'input[type="range"][class*="bright" i]'
            );

            controls.forEach(function (control) {
                if (control.dataset.loginBrightnessBound === '1') {
                    return;
                }

                control.dataset.loginBrightnessBound = '1';

                control.addEventListener('input', function () {
                    applyBrightness(this.value);
                });

                control.addEventListener('change', function () {
                    applyBrightness(this.value);
                });
            });
        }

        try {
            var saved = localStorage.getItem('css_tesda_brightness');

            if (saved !== null) {
                applyBrightness(saved);
            } else {
                applyBrightness(100);
            }
        } catch (e) {
            applyBrightness(100);
        }

        connectBrightness();

        new MutationObserver(connectBrightness).observe(document.body, {
            childList: true,
            subtree: true
        });

        window.addEventListener('brightnessChanged', function (event) {
            if (event.detail !== undefined) {
                applyBrightness(event.detail);
            }
        });

        window.addEventListener('storage', function (event) {
            if (event.key === 'css_tesda_brightness' && event.newValue !== null) {
                applyBrightness(event.newValue);
            }
        });
    })();
    </script>

</body>
</html>
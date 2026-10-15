<?php
require_once 'config.php';

// If already logged in, redirect to dashboard
if (isLoggedIn()) {
    redirect('dashboard.php');
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['register'])) {
        $username = sanitize($_POST['username']);
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];
        $full_name = sanitize($_POST['full_name']);
        $email = sanitize($_POST['email']);
        
        // Validation
        if ($password !== $confirm_password) {
            $error = "Passwords do not match!";
        } elseif (strlen($password) < 6) {
            $error = "Password must be at least 6 characters long!";
        } else {
            // Check if username or email exists
            $check_sql = "SELECT id FROM users WHERE username = '$username' OR email = '$email'";
            $check_result = $conn->query($check_sql);
            
            if ($check_result->num_rows > 0) {
                $error = "Username or email already exists!";
            } else {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $sql = "INSERT INTO users (username, password, full_name, email) 
                        VALUES ('$username', '$hashed_password', '$full_name', '$email')";
                
                if ($conn->query($sql)) {
                    $success = "Registration successful! You can now login.";
                    // Clear form
                    $_POST = array();
                } else {
                    $error = "Registration failed: " . $conn->error;
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
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
            overflow-x: hidden;
            position: relative;
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

        .floating-elements {
            position: fixed;
            width: 100%;
            height: 100%;
            z-index: -1;
        }

        .floating-element {
            position: absolute;
            color: rgba(0, 255, 157, 0.03);
            font-family: 'Orbitron', monospace;
            font-size: 1.2rem;
            white-space: nowrap;
            animation: float 20s linear infinite;
        }

        @keyframes float {
            from { transform: translateY(100vh) rotate(0deg); }
            to { transform: translateY(-100vh) rotate(360deg); }
        }

        /* Main Container */
        .auth-wrapper {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            position: relative;
        }

        .auth-container {
            width: 100%;
            max-width: 520px;
            perspective: 1000px;
        }

        .auth-card {
            background: rgba(10, 20, 40, 0.7);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(0, 255, 157, 0.3);
            border-radius: 30px;
            padding: 50px 40px;
            box-shadow: 
                0 0 30px rgba(0, 255, 157, 0.2),
                inset 0 0 30px rgba(0, 255, 157, 0.1);
            transform-style: preserve-3d;
            animation: cardAppear 1s ease-out;
            position: relative;
            overflow: hidden;
        }

        .auth-card::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(0, 255, 157, 0.1) 0%, transparent 70%);
            animation: rotate 20s linear infinite;
        }

        @keyframes cardAppear {
            from {
                opacity: 0;
                transform: translateY(50px) rotateX(20deg);
            }
            to {
                opacity: 1;
                transform: translateY(0) rotateX(0);
            }
        }

        @keyframes rotate {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        /* Header */
        .auth-header {
            text-align: center;
            margin-bottom: 30px;
            position: relative;
        }

        .logo-icon {
            width: 80px;
            height: 80px;
            margin: 0 auto 15px;
            background: linear-gradient(135deg, #00ff9d, #0066ff);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            color: #050812;
            box-shadow: 0 0 30px rgba(0, 255, 157, 0.5);
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% { box-shadow: 0 0 30px rgba(0, 255, 157, 0.5); }
            50% { box-shadow: 0 0 50px rgba(0, 255, 157, 0.8); }
        }

        .auth-header h1 {
            font-family: 'Orbitron', sans-serif;
            font-size: 2rem;
            background: linear-gradient(135deg, #00ff9d, #0066ff);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 10px;
            letter-spacing: 2px;
        }

        .auth-header p {
            color: #8899cc;
            font-size: 0.95rem;
            letter-spacing: 1px;
        }

        /* Form */
        .auth-form {
            position: relative;
            z-index: 1;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }

        .form-group {
            margin-bottom: 20px;
            position: relative;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            color: #00ff9d;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.8rem;
            letter-spacing: 1px;
        }

        .form-group label i {
            margin-right: 5px;
            color: #0066ff;
        }

        .input-wrapper {
            position: relative;
        }

        .input-wrapper i {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #00ff9d;
            font-size: 1rem;
            transition: all 0.3s;
        }

        .form-group input {
            width: 100%;
            padding: 12px 12px 12px 40px;
            background: rgba(0, 0, 0, 0.4);
            border: 2px solid rgba(0, 255, 157, 0.3);
            border-radius: 10px;
            color: #e0e0ff;
            font-family: 'Rajdhani', sans-serif;
            font-size: 0.95rem;
            transition: all 0.3s;
        }

        .form-group input:focus {
            outline: none;
            border-color: #00ff9d;
            box-shadow: 0 0 15px rgba(0, 255, 157, 0.3);
            background: rgba(0, 0, 0, 0.6);
        }

        .form-group input:focus + i {
            color: #0066ff;
            transform: translateY(-50%) scale(1.1);
        }

        /* Password Strength Meter */
        .password-strength {
            margin-top: 8px;
            height: 4px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 2px;
            overflow: hidden;
        }

        .strength-bar {
            height: 100%;
            width: 0;
            transition: all 0.3s;
        }

        .strength-text {
            font-size: 0.75rem;
            margin-top: 5px;
            text-align: right;
            color: #8899cc;
        }

        /* Terms */
        .terms-check {
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 20px 0;
            font-size: 0.9rem;
        }

        .terms-check input[type="checkbox"] {
            width: 18px;
            height: 18px;
            cursor: pointer;
            accent-color: #00ff9d;
        }

        .terms-check a {
            color: #00ff9d;
            text-decoration: none;
        }

        .terms-check a:hover {
            text-decoration: underline;
        }

        /* Button */
        .btn-register {
            width: 100%;
            padding: 14px;
            background: linear-gradient(45deg, #00ff9d, #0066ff);
            border: none;
            border-radius: 10px;
            color: #050812;
            font-family: 'Orbitron', sans-serif;
            font-size: 1rem;
            font-weight: bold;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            transition: all 0.3s;
            position: relative;
            overflow: hidden;
        }

        .btn-register::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
            transition: left 0.5s;
        }

        .btn-register:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 30px rgba(0, 255, 157, 0.4);
        }

        .btn-register:hover::before {
            left: 100%;
        }

        /* Login Link */
        .login-link {
            text-align: center;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid rgba(0, 255, 157, 0.2);
        }

        .login-link p {
            color: #8899cc;
            margin-bottom: 8px;
            font-size: 0.9rem;
        }

        .login-link a {
            color: #00ff9d;
            text-decoration: none;
            font-weight: bold;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 0.95rem;
        }

        .login-link a:hover {
            color: #0066ff;
            transform: translateX(5px);
        }

        /* Alerts */
        .alert {
            padding: 12px 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            animation: slideIn 0.5s ease;
            font-size: 0.9rem;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateX(-20px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        .alert-error {
            background: rgba(255, 0, 102, 0.1);
            border: 1px solid #ff0066;
            color: #ff6699;
        }

        .alert-success {
            background: rgba(0, 255, 157, 0.1);
            border: 1px solid #00ff9d;
            color: #00ff9d;
        }

        /* TESDA Badge */
        .tesda-badge {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            margin-top: 20px;
            padding: 8px;
            color: #8899cc;
            font-size: 0.8rem;
        }

        .tesda-badge i {
            color: #00ff9d;
        }

        /* Responsive */
        @media (max-width: 520px) {
            .auth-card {
                padding: 30px 20px;
            }

            .form-row {
                grid-template-columns: 1fr;
                gap: 0;
            }

            .auth-header h1 {
                font-size: 1.6rem;
            }
        }

        /* Loading Animation */
        .loading {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            z-index: 9999;
            justify-content: center;
            align-items: center;
        }

        .loading.active {
            display: flex;
        }

        .loading-spinner {
            width: 50px;
            height: 50px;
            border: 3px solid rgba(0, 255, 157, 0.3);
            border-top-color: #00ff9d;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="cyber-bg"></div>
    <div class="grid-overlay"></div>
    
    <!-- Floating Binary Elements -->
    <div class="floating-elements" id="floatingElements"></div>

    <!-- Loading Animation -->
    <div class="loading" id="loading">
        <div class="loading-spinner"></div>
    </div>

    <div class="auth-wrapper">
        <div class="auth-container">
            <div class="auth-card">
                <div class="auth-header">
                    <div class="logo-icon">
                        <i class="fas fa-user-plus"></i>
                    </div>
                    <h1>CREATE ACCOUNT</h1>
                    <p>Join the TESDA CSS NCII Review Community</p>
                </div>

                <?php if ($error): ?>
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-circle"></i>
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i>
                        <?php echo $success; ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="" class="auth-form" id="registerForm">
                    <div class="form-row">
                        <div class="form-group">
                            <label><i class="fas fa-user"></i> FULL NAME</label>
                            <div class="input-wrapper">
                                <i class="fas fa-user"></i>
                                <input type="text" name="full_name" required placeholder="John Doe" value="<?php echo isset($_POST['full_name']) ? htmlspecialchars($_POST['full_name']) : ''; ?>">
                            </div>
                        </div>

                        <div class="form-group">
                            <label><i class="fas fa-envelope"></i> EMAIL</label>
                            <div class="input-wrapper">
                                <i class="fas fa-envelope"></i>
                                <input type="email" name="email" required placeholder="john@example.com" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label><i class="fas fa-user-circle"></i> USERNAME</label>
                        <div class="input-wrapper">
                            <i class="fas fa-user-circle"></i>
                            <input type="text" name="username" required placeholder="Choose a username" value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label><i class="fas fa-lock"></i> PASSWORD</label>
                            <div class="input-wrapper">
                                <i class="fas fa-lock"></i>
                                <input type="password" name="password" required placeholder="Min. 6 characters" id="password">
                            </div>
                            <div class="password-strength" id="strengthMeter">
                                <div class="strength-bar" id="strengthBar"></div>
                            </div>
                            <div class="strength-text" id="strengthText"></div>
                        </div>

                        <div class="form-group">
                            <label><i class="fas fa-check-circle"></i> CONFIRM</label>
                            <div class="input-wrapper">
                                <i class="fas fa-check-circle"></i>
                                <input type="password" name="confirm_password" required placeholder="Confirm password" id="confirm_password">
                            </div>
                        </div>
                    </div>

                    <div class="terms-check">
                        <input type="checkbox" name="terms" id="terms" required>
                        <label for="terms">I agree to the <a href="#">Terms of Service</a> and <a href="#">Privacy Policy</a></label>
                    </div>

                    <button type="submit" name="register" class="btn-register" onclick="showLoading()">
                        <span>REGISTER</span>
                        <i class="fas fa-arrow-right"></i>
                    </button>

                    <div class="login-link">
                        <p>Already have an account?</p>
                        <a href="login.php">
                            <span>LOGIN HERE</span>
                            <i class="fas fa-sign-in-alt"></i>
                        </a>
                    </div>
                </form>

                <div class="tesda-badge">
                    <i class="fas fa-certificate"></i>
                    <span>TESDA Computer Systems Servicing NC II</span>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Show loading animation
        function showLoading() {
            document.getElementById('loading').classList.add('active');
        }

        // Floating binary numbers
        function createFloatingElements() {
            const container = document.getElementById('floatingElements');
            const binary = ['0', '1', '101', '010', 'CSS', 'NCII', 'TESDA', 'CPU', 'RAM', 'HDD', 'PC', 'BIOS', 'SATA', 'USB'];
            
            for (let i = 0; i < 30; i++) {
                const element = document.createElement('div');
                element.className = 'floating-element';
                element.textContent = binary[Math.floor(Math.random() * binary.length)];
                element.style.left = Math.random() * 100 + '%';
                element.style.animationDuration = (Math.random() * 20 + 10) + 's';
                element.style.animationDelay = Math.random() * 10 + 's';
                element.style.fontSize = (Math.random() * 1 + 0.5) + 'rem';
                container.appendChild(element);
            }
        }

        // Password strength checker
        const password = document.getElementById('password');
        const confirm = document.getElementById('confirm_password');
        const strengthBar = document.getElementById('strengthBar');
        const strengthText = document.getElementById('strengthText');
        const strengthMeter = document.getElementById('strengthMeter');

        password.addEventListener('input', function() {
            const val = this.value;
            let strength = 0;
            
            if (val.length >= 6) strength += 25;
            if (val.match(/[a-z]+/)) strength += 25;
            if (val.match(/[A-Z]+/)) strength += 25;
            if (val.match(/[0-9]+/)) strength += 25;
            
            strengthBar.style.width = strength + '%';
            
            if (strength <= 25) {
                strengthBar.style.background = '#ff3366';
                strengthText.textContent = 'Weak';
            } else if (strength <= 50) {
                strengthBar.style.background = '#ffaa00';
                strengthText.textContent = 'Fair';
            } else if (strength <= 75) {
                strengthBar.style.background = '#00ff9d';
                strengthText.textContent = 'Good';
            } else {
                strengthBar.style.background = '#0066ff';
                strengthText.textContent = 'Strong';
            }
            
            checkPasswordMatch();
        });

        confirm.addEventListener('input', checkPasswordMatch);

        function checkPasswordMatch() {
            if (confirm.value && password.value !== confirm.value) {
                confirm.style.borderColor = '#ff3366';
                confirm.style.boxShadow = '0 0 10px rgba(255, 51, 102, 0.3)';
            } else if (confirm.value && password.value === confirm.value) {
                confirm.style.borderColor = '#00ff9d';
                confirm.style.boxShadow = '0 0 10px rgba(0, 255, 157, 0.3)';
            } else {
                confirm.style.borderColor = 'rgba(0, 255, 157, 0.3)';
                confirm.style.boxShadow = 'none';
            }
        }

        // Form validation
        document.getElementById('registerForm').addEventListener('submit', function(e) {
            const password = document.querySelector('input[name="password"]').value;
            const confirm = document.querySelector('input[name="confirm_password"]').value;
            const terms = document.getElementById('terms').checked;
            
            if (password !== confirm) {
                e.preventDefault();
                alert('Passwords do not match!');
            }
            
            if (!terms) {
                e.preventDefault();
                alert('You must agree to the Terms of Service');
            }
        });

        // Initialize floating elements
        createFloatingElements();

        // Add focus effects
        document.querySelectorAll('.input-wrapper input').forEach(input => {
            input.addEventListener('focus', function() {
                this.parentElement.style.transform = 'scale(1.02)';
            });
            
            input.addEventListener('blur', function() {
                this.parentElement.style.transform = 'scale(1)';
            });
        });
    </script>
    
    <?php $global_brightness = getGlobalBrightness(); include __DIR__ . '/includes/brightness_widget.php'; ?>
    <?php include __DIR__ . '/includes/theme_toggle.php'; ?>
     
</body>
</html>
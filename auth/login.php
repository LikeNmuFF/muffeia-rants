<?php
include '../includes/db.php';
session_start();
$error = '';
$is_register_mode = false;

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] == 'login') {
        $stmt = $conn->prepare("SELECT id, username, password_hash FROM users WHERE email = ?");
        $stmt->bind_param("s", $_POST['email']);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            if (password_verify($_POST['password'], $user['password_hash'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                header("Location: ../index.php");
                exit();
            } else {
                $error = 'Incorrect password.';
            }
        } else {
            $error = 'No account found with that email.';
        }
        $stmt->close();
    }

    if ($_POST['action'] == 'register') {
        $is_register_mode = true;
        $username = $_POST['username'];
        $email = $_POST['email'];
        $password = $_POST['password'];

        // Server-side password strength validation
        $password_errors = [];
        if (strlen($password) < 8) {
            $password_errors[] = 'at least 8 characters';
        }
        if (!preg_match('/[A-Z]/', $password)) {
            $password_errors[] = 'an uppercase letter';
        }
        if (!preg_match('/[a-z]/', $password)) {
            $password_errors[] = 'a lowercase letter';
        }
        if (!preg_match('/[0-9]/', $password)) {
            $password_errors[] = 'a number';
        }
        if (!preg_match('/[^A-Za-z0-9]/', $password)) {
            $password_errors[] = 'a special character';
        }

        if (!empty($password_errors)) {
            $error = 'Password must contain ' . implode(', ', $password_errors) . '.';
        } else {
            // Validate username
            if (strlen($username) < 3) {
                $error = 'Username must be at least 3 characters long.';
            } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
                $error = 'Username can only contain letters, numbers, and underscores.';
            } else {
                // Check for existing user
                $stmt_check = $conn->prepare("SELECT id FROM users WHERE email = ? OR username = ?");
                $stmt_check->bind_param("ss", $email, $username);
                $stmt_check->execute();
                $result_check = $stmt_check->get_result();

                if ($result_check->num_rows > 0) {
                    $error = 'Email or Username already exists.';
                } else {
                    // Insert new user
                    $password_hash = password_hash($password, PASSWORD_DEFAULT);
                    $stmt_insert = $conn->prepare("INSERT INTO users (username, email, password_hash) VALUES (?, ?, ?)");
                    $stmt_insert->bind_param("sss", $username, $email, $password_hash);
                    
                    if ($stmt_insert->execute()) {
                        header("Location: login.php?registered=success");
                        exit();
                    } else {
                        $error = "Error: " . $conn->error;
                    }
                    $stmt_insert->close();
                }
                $stmt_check->close();
            }
        }
    }
}

if (isset($_GET['registered']) && $_GET['registered'] == 'success') {
    $error = 'Registration successful! Please log in.';
    $is_register_mode = false;
}

$initial_class = $is_register_mode ? 'register-mode' : '';
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MUFFEIA - Login</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #7c3aed;
            --primary-dark: #6d28d9;
            --primary-light: #8b5cf6;
            --dark: #1e293b;
            --light: #f8fafc;
            --gray: #64748b;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            --shadow-lg: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
        }

        body {
            background: linear-gradient(135deg, #1e293b 0%, #334155 100%);
            /* UPDATED: Changed min-height to height and added overflow */
            height: 100vh;
            overflow: hidden; 
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            position: relative;
        }

        body::before,
        body::after {
            content: '';
            position: absolute;
            border-radius: 50%;
            opacity: 0.1;
            pointer-events: none;
        }

        body::before {
            width: 500px;
            height: 500px;
            background: var(--primary);
            top: -250px;
            right: -250px;
        }

        body::after {
            width: 400px;
            height: 400px;
            background: var(--primary-light);
            bottom: -200px;
            left: -200px;
        }

        .mobile-container {
            width: 100%;
            max-width: 440px;
            background: var(--light);
            border-radius: 20px;
            overflow: hidden;
            box-shadow: var(--shadow-lg);
            position: relative;
            z-index: 1;
            
            /* UPDATED: Make container a flex column and limit its height */
            display: flex;
            flex-direction: column;
            max-height: calc(100vh - 40px); /* 100vh minus body padding (20px * 2) */
        }

        .tabs {
            display: flex;
            background: #f1f5f9;
            padding: 6px;
            gap: 4px;
            /* UPDATED: Prevent tabs from shrinking */
            flex-shrink: 0;
        }

        .tab {
            flex: 1;
            padding: 14px 16px;
            text-align: center;
            font-weight: 600;
            cursor: pointer;
            border-radius: 12px;
            transition: all 0.3s ease;
            color: var(--gray);
            font-size: 0.95rem;
        }

        .tab.active {
            background: white;
            color: var(--primary);
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .content {
            padding: 32px 28px;
            /* UPDATED: Allow content to scroll if it overflows */
            overflow-y: auto;
        }

        .brand {
            text-align: center;
            margin-bottom: 28px;
        }

        /* ... [Rest of your CSS is unchanged] ... */

        .brand-logo {
            font-size: 2.5rem;
            font-weight: 800;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            margin-bottom: 10px;
            letter-spacing: -1px;
        }

        .title {
            font-size: 1.6rem;
            font-weight: 700;
            margin-bottom: 6px;
            color: var(--dark);
            text-align: center;
        }

        .subtitle {
            color: var(--gray);
            text-align: center;
            margin-bottom: 24px;
            font-size: 0.95rem;
            line-height: 1.5;
        }

        .form {
            display: flex;
            flex-direction: column;
            gap: 16px;
            margin-bottom: 24px;
        }

        .input-group {
            width: 100%;
            position: relative;
        }

        .input-group input {
            width: 100%;
            padding: 14px 16px;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            font-size: 0.95rem;
            background: white;
            transition: all 0.3s ease;
        }

        .input-group input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(124, 58, 237, 0.1);
        }

        .input-group input::placeholder {
            color: #94a3b8;
        }

        .input-group.error input {
            border-color: var(--danger);
        }

        .input-group.success input {
            border-color: var(--success);
        }

        .password-toggle {
            position: absolute;
            right: 16px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: var(--gray);
            font-size: 1rem;
            transition: color 0.3s ease;
        }

        .password-toggle:hover {
            color: var(--primary);
        }

        .password-strength {
            margin-top: 8px;
            padding: 12px;
            border-radius: 8px;
            background: #f1f5f9;
            display: none;
        }

        .password-strength.show {
            display: block;
        }

        .strength-bar {
            height: 4px;
            background: #e2e8f0;
            border-radius: 2px;
            margin-bottom: 8px;
            overflow: hidden;
        }

        .strength-bar-fill {
            height: 100%;
            transition: all 0.3s ease;
            border-radius: 2px;
        }

        .strength-bar-fill.weak {
            width: 33%;
            background: var(--danger);
        }

        .strength-bar-fill.medium {
            width: 66%;
            background: var(--warning);
        }

        .strength-bar-fill.strong {
            width: 100%;
            background: var(--success);
        }

        .strength-text {
            font-size: 0.85rem;
            font-weight: 600;
            margin-bottom: 6px;
        }

        .strength-text.weak {
            color: var(--danger);
        }

        .strength-text.medium {
            color: var(--warning);
        }

        .strength-text.strong {
            color: var(--success);
        }

        .strength-requirements {
            font-size: 0.8rem;
            color: var(--gray);
            list-style: none;
        }

        .strength-requirements li {
            padding: 3px 0;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .strength-requirements li i {
            font-size: 0.75rem;
            width: 14px;
        }

        .strength-requirements li.met {
            color: var(--success);
        }

        .strength-requirements li.met i {
            color: var(--success);
        }

        .strength-requirements li.unmet {
            color: var(--gray);
        }

        .strength-requirements li.unmet i {
            color: var(--gray);
        }

        .submit-btn {
            width: 100%;
            padding: 14px;
            border: none;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 600;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
            color: white;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 8px;
        }

        .submit-btn:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(124, 58, 237, 0.4);
        }

        .submit-btn:active:not(:disabled) {
            transform: translateY(0);
        }

        .submit-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .security {
            background: rgba(124, 58, 237, 0.05);
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
        }

        .security-title {
            font-weight: 600;
            margin-bottom: 14px;
            color: var(--dark);
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.95rem;
        }

        .security-list {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .security-item {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 0.88rem;
            color: var(--gray);
        }

        .security-item i {
            color: var(--success);
            font-size: 0.9rem;
        }

        .nav-link {
            text-align: center;
            color: var(--gray);
            font-size: 0.9rem;
        }

        .nav-link a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
        }

        .nav-link a:hover {
            text-decoration: underline;
        }

        .error-message {
            background: #fef2f2;
            color: #dc2626;
            padding: 12px 14px;
            border-radius: 10px;
            margin-bottom: 16px;
            font-size: 0.88rem;
            border-left: 4px solid #dc2626;
            line-height: 1.4;
        }

        .success-message {
            background: #f0fdf4;
            color: #16a34a;
            padding: 12px 14px;
            border-radius: 10px;
            margin-bottom: 16px;
            font-size: 0.88rem;
            border-left: 4px solid #16a34a;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: scale(0.95);
            }
            to {
                opacity: 1;
                transform: scale(1);
            }
        }

        .mobile-container {
            animation: slideIn 0.4s ease-out;
        }

        /* Tablet styles */
        @media (min-width: 768px) {
            body {
                padding: 40px;
            }

            .mobile-container {
                max-width: 600px;
                /* UPDATED: Adjust max-height for new body padding */
                max-height: calc(100vh - 80px); /* 100vh - (40px * 2) */
            }

            .content {
                padding: 40px 36px;
            }

            .brand-logo {
                font-size: 2.8rem;
            }

            .title {
                font-size: 1.8rem;
            }

            .subtitle {
                font-size: 1rem;
            }

            .tab {
                padding: 15px 18px;
                font-size: 1rem;
            }

            .input-group input {
                padding: 15px 18px;
                font-size: 1rem;
            }

            .submit-btn {
                padding: 15px;
                font-size: 1.05rem;
            }
        }

        /* Desktop styles */
        @media (min-width: 1024px) {
            body {
                padding: 60px;
            }

            .mobile-container {
                max-width: 640px;
                 /* UPDATED: Adjust max-height for new body padding */
                max-height: calc(100vh - 120px); /* 100vh - (60px * 2) */
            }

            .content {
                padding: 44px 40px;
            }

            .brand-logo {
                font-size: 3rem;
            }

            .form {
                gap: 18px;
            }

            .security {
                padding: 24px;
            }

            .submit-btn:hover:not(:disabled) {
                transform: translateY(-3px);
                box-shadow: 0 8px 25px rgba(124, 58, 237, 0.5);
            }
        }

        /* Mobile adjustments */
        @media (max-width: 380px) {
            .content {
                padding: 24px 20px;
            }
            
            .brand-logo {
                font-size: 2.2rem;
            }
            
            .title {
                font-size: 1.4rem;
            }
            
            .subtitle {
                font-size: 0.88rem;
            }

            .tab {
                padding: 12px 14px;
                font-size: 0.88rem;
            }

            .input-group input {
                padding: 12px 14px;
                font-size: 0.9rem;
            }

            .submit-btn {
                padding: 13px;
                font-size: 0.95rem;
            }
        }

        /* Short screens */
        @media (max-height: 700px) {
            .content {
                padding: 24px 28px;
            }
            
            .brand {
                margin-bottom: 20px;
            }
            
            .subtitle {
                margin-bottom: 20px;
            }

            .security {
                padding: 16px;
            }

            .form {
                gap: 14px;
                margin-bottom: 20px;
            }
        }

        /* Very short screens */
        @media (max-height: 600px) {
            body {
                padding: 10px;
            }

            .mobile-container {
                /* UPDATED: Adjust max-height for new body padding */
                max-height: calc(100vh - 20px); /* 100vh - (10px * 2) */
            }

            .content {
                padding: 20px 24px;
            }

            .brand-logo {
                font-size: 2rem;
                margin-bottom: 8px;
            }

            .title {
                font-size: 1.3rem;
            }

            .brand {
                margin-bottom: 16px;
            }

            .subtitle {
                font-size: 0.85rem;
                margin-bottom: 16px;
            }
        }

        /* Landscape mobile */
        @media (max-height: 500px) and (orientation: landscape) {
            body {
                padding: 10px;
                /* UPDATED: Removed align-items: flex-start and overflow-y: auto */
                /* The body's default flex settings will now center the constrained box */
            }

            .mobile-container {
                /* UPDATED: Removed margin override, let body center it */
                /* UPDATED: Ensure max-height is set for this view */
                max-height: calc(100vh - 20px);
            }

            .content {
                padding: 16px 24px;
            }

            .brand {
                margin-bottom: 12px;
            }

            .brand-logo {
                font-size: 1.8rem;
                margin-bottom: 4px;
            }

            .title {
                font-size: 1.2rem;
                margin-bottom: 4px;
            }

            .subtitle {
                font-size: 0.82rem;
                margin-bottom: 12px;
            }

            .form {
                gap: 10px;
                margin-bottom: 12px;
            }

            .input-group input {
                padding: 10px 12px;
                font-size: 0.9rem;
            }

            .submit-btn {
                padding: 11px;
                font-size: 0.95rem;
            }

            .security {
                padding: 12px;
                margin-bottom: 12px;
            }

            .security-title {
                font-size: 0.85rem;
                margin-bottom: 8px;
            }

            .security-item {
                font-size: 0.8rem;
                gap: 8px;
            }

            .password-strength {
                padding: 10px;
            }

            .strength-requirements li {
                padding: 2px 0;
            }
        }
    </style>
</head>
<body>
    
    <div class="mobile-container">
        <div class="tabs">
            <div class="tab active" data-tab="login">Sign In</div>
            <div class="tab" data-tab="register">Register</div>
        </div>
        
        <div class="content">
            <div class="brand">
                <div class="brand-logo">MUFFEIA</div>
                <h1 class="title">Welcome Back</h1>
                <p class="subtitle">Access your account and continue your journey</p>
            </div>

            <?php if (!empty($error)): ?>
                <div class="<?php echo (strpos($error, 'successful') !== false) ? 'success-message' : 'error-message'; ?>">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <form class="form" method="POST" action="" id="authForm">
                <input type="hidden" name="action" value="login">
                
                <div class="input-group">
                    <input type="email" name="email" placeholder="Email address" required>
                </div>
                
                <div class="input-group">
                    <input type="password" name="password" id="loginPassword" placeholder="Password" required>
                    <i class="fas fa-eye password-toggle" data-target="loginPassword"></i>
                </div>
                
                <button type="submit" class="submit-btn">
                    Sign In
                </button>
            </form>
            
            <div class="security">
                <div class="security-title">
                    <i class="fas fa-shield-alt"></i>
                    Secure Login
                </div>
                <div class="security-list">
                    <div classs="security-item">
                        <i class="fas fa-check-circle"></i>
                        <span>End-to-end encrypted</span>
                    </div>
                    <div class="security-item">
                        <i class="fas fa-check-circle"></i>
                        <span>Privacy first approach</span>
                    </div>
                </div>
            </div>
            
            <p class="nav-link">
                Don't have an account? <a href="#" class="switch-tab" data-target="register">Join now</a>
            </p>
        </div>
    </div>

    <script>
        let currentPasswordStrength = 'weak';

        // Tab switching functionality
        document.querySelectorAll('.tab').forEach(tab => {
            tab.addEventListener('click', function() {
                switchTab(this.dataset.tab);
            });
        });

        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('switch-tab')) {
                e.preventDefault();
                switchTab(e.target.dataset.target);
            }
        });

        // Password toggle functionality
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('password-toggle')) {
                const targetId = e.target.dataset.target;
                const input = document.getElementById(targetId);
                
                if (input.type === 'password') {
                    input.type = 'text';
                    e.target.classList.remove('fa-eye');
                    e.target.classList.add('fa-eye-slash');
                } else {
                    input.type = 'password';
                    e.target.classList.remove('fa-eye-slash');
                    e.target.classList.add('fa-eye');
                }
            }
        });

        function checkPasswordStrength(password) {
            let strength = 0;
            const requirements = {
                length: password.length >= 8,
                uppercase: /[A-Z]/.test(password),
                lowercase: /[a-z]/.test(password),
                number: /[0-9]/.test(password),
                special: /[^A-Za-z0-9]/.test(password)
            };

            // Count met requirements
            Object.values(requirements).forEach(met => {
                if (met) strength++;
            });

            let level = 'weak';
            if (strength >= 5) {
                level = 'strong';
            } else if (strength >= 3) {
                level = 'medium';
            }

            return { level, requirements, strength };
        }

        function updatePasswordStrength(password, strengthElement) {
            const result = checkPasswordStrength(password);
            currentPasswordStrength = result.level;
            
            if (password.length === 0) {
                strengthElement.classList.remove('show');
                return;
            }

            strengthElement.classList.add('show');
            
            const strengthBar = strengthElement.querySelector('.strength-bar-fill');
            const strengthText = strengthElement.querySelector('.strength-text');
            const requirements = strengthElement.querySelectorAll('.strength-requirements li');

            // Update bar
            strengthBar.className = 'strength-bar-fill ' + result.level;
            
            // Update text
            strengthText.className = 'strength-text ' + result.level;
            strengthText.textContent = result.level.charAt(0).toUpperCase() + result.level.slice(1) + ' Password';

            // Update requirements
            const reqArray = Object.entries(result.requirements);
            requirements.forEach((li, index) => {
                if (reqArray[index][1]) {
                    li.className = 'met';
                    li.querySelector('i').className = 'fas fa-check-circle';
                } else {
                    li.className = 'unmet';
                    li.querySelector('i').className = 'far fa-circle';
                }
            });

            // Enable/disable submit button
            const submitBtn = document.querySelector('.submit-btn');
            if (result.level === 'weak') {
                submitBtn.disabled = true;
            } else {
                submitBtn.disabled = false;
            }
        }

        function switchTab(tabName) {
            // Update active tab
            document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
            document.querySelector(`.tab[data-tab="${tabName}"]`).classList.add('active');
            
            const form = document.querySelector('.form');
            const title = document.querySelector('.title');
            const subtitle = document.querySelector('.subtitle');
            const navLink = document.querySelector('.nav-link');
            
            if (tabName === 'register') {
                // Switch to register form
                title.textContent = 'Create Account';
                subtitle.textContent = 'Join us and start your journey today';
                
                form.innerHTML = `
                    <input type="hidden" name="action" value="register">
                    <div class="input-group">
                        <input type="text" name="username" placeholder="Username" required minlength="3" pattern="[a-zA-Z0-9_]+">
                    </div>
                    <div class="input-group">
                        <input type="email" name="email" placeholder="Email address" required>
                    </div>
                    <div class="input-group">
                        <input type="password" name="password" id="registerPassword" placeholder="Password" required>
                        <i class="fas fa-eye password-toggle" data-target="registerPassword"></i>
                    </div>
                    <div class="password-strength">
                        <div class="strength-bar">
                            <div class="strength-bar-fill weak"></div>
                        </div>
                        <div class="strength-text weak">Weak Password</div>
                        <ul class="strength-requirements">
                            <li class="unmet"><i class="far fa-circle"></i> At least 8 characters</li>
                            <li class="unmet"><i class="far fa-circle"></i> One uppercase letter</li>
                            <li class="unmet"><i class="far fa-circle"></i> One lowercase letter</li>
                            <li class="unmet"><i class="far fa-circle"></i> One number</li>
                            <li class="unmet"><i class="far fa-circle"></i> One special character</li>
                        </ul>
                    </div>
                    <button type="submit" class="submit-btn" disabled>Create Account</button>
                `;
                
                navLink.innerHTML = 'Already have an account? <a href="#" class="switch-tab" data-target="login">Sign in</a>';

                // Add password strength checker
                const passwordInput = document.getElementById('registerPassword');
                const strengthElement = document.querySelector('.password-strength');
                
                passwordInput.addEventListener('input', function() {
                    updatePasswordStrength(this.value, strengthElement);
                });
            } else {
                // Switch to login form
                title.textContent = 'Welcome Back';
                subtitle.textContent = 'Access your account and continue your journey';
                
                form.innerHTML = `
                    <input type="hidden" name="action" value="login">
                    <div class="input-group">
                        <input type="email" name="email" placeholder="Email address" required>
                    </div>
                    <div class="input-group">
                        <input type="password" name="password" id="loginPassword" placeholder="Password" required>
                        <i class="fas fa-eye password-toggle" data-target="loginPassword"></i>
                    </div>
                    <button type="submit" class="submit-btn">Sign In</button>
                `;
                
                navLink.innerHTML = 'Don\'t have an account? <a href="#" class="switch-tab" data-target="register">Join now</a>';
            }
        }

        // Set initial tab based on PHP
        <?php if ($is_register_mode): ?>
        switchTab('register');
        <?php endif; ?>

        // Form validation before submit
        document.addEventListener('submit', function(e) {
            if (e.target.id === 'authForm') {
                const action = e.target.querySelector('input[name="action"]').value;
                
                if (action === 'register') {
                    if (currentPasswordStrength === 'weak') {
                        e.preventDefault();
                        alert('Please create a stronger password before registering.');
                        return false;
                    }
                }
            }
        });
    </script>
</body>
</html>
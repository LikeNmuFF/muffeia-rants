<?php
include '../includes/db.php';
session_start();
$error = '';
$is_register_mode = false; // Flag to control the initial view state

// Check if the login form was submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] == 'login') {
        // IMPORTANT: Use prepared statements to prevent SQL Injection
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
                // Stay on login page on error
            }
        } else {
            $error = 'No account found with that email.';
            // Stay on login page on error
        }
        $stmt->close();
    }

    // Check if the registration form was submitted
    if ($_POST['action'] == 'register') {
        $is_register_mode = true; // Keep the registration form visible on error
        $username = $_POST['username'];
        $email = $_POST['email'];
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

        // Check for existing user
        $stmt_check = $conn->prepare("SELECT id FROM users WHERE email = ? OR username = ?");
        $stmt_check->bind_param("ss", $email, $username);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();

        if ($result_check->num_rows > 0) {
            $error = 'Email or Username already exists.';
        } else {
            // Insert new user
            $stmt_insert = $conn->prepare("INSERT INTO users (username, email, password_hash) VALUES (?, ?, ?)");
            $stmt_insert->bind_param("sss", $username, $email, $password);
            
            if ($stmt_insert->execute()) {
                // Redirect to login page after successful registration, adding a flag
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

// Check for the success message flag after redirection
if (isset($_GET['registered']) && $_GET['registered'] == 'success') {
    $error = 'Registration successful! Please log in.'; // This is the success message
    $is_register_mode = false;
}

// Set initial mode based on PHP error or previous action
$initial_class = $is_register_mode ? 'register-mode' : '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to Muffeia</title>
    <link rel="icon" type="image/png" href="../logo/muffeia.png" />
    <link href="../css/css2.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/all.min.css">
    <link rel="stylesheet" href="../css/logins.css">

</head>
<body class="<?= $initial_class ?>">

<div class="form-container-wrapper <?= $initial_class ?>">
    
    <div class="form-container login-container">
        <form method="POST" action="login.php">
            <div class="brand-logo">MUFFEIA</div>
            <h1>Welcome Back</h1>
            <p class="subtitle">Access your account and continue your journey</p>
            
            <?php if ($error && !$is_register_mode): 
                // IMPROVEMENT: Check if this is the success message to add a different class
                $is_success = (strpos($error, 'successful') !== false);
                $error_class = $is_success ? 'error-message success' : 'error-message';
            ?>
                <div class="<?= $error_class ?>" role="alert">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <div class="input-group" style="--delay: 1">
                <input type="email" name="email" placeholder="Email address" required aria-label="Email address" value="<?= isset($_POST['email']) && !$is_register_mode ? htmlspecialchars($_POST['email']) : '' ?>">
            </div>
            <div class="input-group" style="--delay: 2">
                <input type="password" name="password" placeholder="Password" required aria-label="Password">
            </div>
            
            <div class="social-login" style="--delay: 3">
                <button type="button" class="social-btn" aria-label="Login with Google"><i class="fab fa-google"></i></button>
                <button type="button" class="social-btn" aria-label="Login with Facebook"><i class="fab fa-facebook-f"></i></button>
                <button type="button" class="social-btn" aria-label="Login with Apple"><i class="fab fa-apple"></i></button>
            </div>

            <div class="input-group" style="--delay: 4">
                <button type="submit" name="action" value="login">
                    <span>Sign In</span>
                </button>
            </div>
            <p class="form-nav">Don't have an account? <a href="#" id="goToRegister">Join now</a></p>
        </form>
    </div>

    <div class="form-container register-container">
        <form method="POST" action="login.php">
            <div class="brand-logo">MUFFEIA</div>
            <h1>Join Us Today</h1>
            <p class="subtitle">Create your account and unlock amazing features</p>
            
            <?php if ($error && $is_register_mode): // Show error only if in registration mode ?>
                <div class="error-message" role="alert">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <div class="input-group" style="--delay: 1">
                <input type="text" name="username" placeholder="Username" required aria-label="Username" value="<?= isset($_POST['username']) && $is_register_mode ? htmlspecialchars($_POST['username']) : '' ?>">
            </div>
            <div class="input-group" style="--delay: 2">
                <input type="email" name="email" placeholder="Email address" required aria-label="Email address" value="<?= isset($_POST['email']) && $is_register_mode ? htmlspecialchars($_POST['email']) : '' ?>">
            </div>
            <div class="input-group" style="--delay: 3">
                <input type="password" name="password" placeholder="Password" required aria-label="Password">
            </div>
            <div class="input-group" style="--delay: 4">
                <button type="submit" name="action" value="register">
                    <span>Create Account</span>
                </button>
            </div>
            <p class="form-nav">Already have an account? <a href="#" id="goToLogin">Sign in</a></p>
        </form>
    </div>
        
    <div class="overlay">
        <div class="overlay-content overlay-left">
            <div class="icon-rocket">🚀</div>
            <h1>Start Your Journey</h1>
            <p>Join our growing community and discover powerful collaboration tools designed for modern teams</p>
            <div class="features">
                <span class="feature-badge">🎯 Smart Solutions</span>
                <span class="feature-badge">⚡ Lightning Fast</span>
                <span class="feature-badge">🔒 Secure</span>
            </div>
            <button class="overlay-btn" id="registerTrigger"><span>Register Now</span></button>
        </div>
        <div class="overlay-content overlay-right">
            <div class="icon-rocket">👋</div>
            <h1>Hello Again!</h1>
            <p>Welcome back! We're excited to see you again. Sign in to access your workspace and continue collaborating</p>
            <div class="features">
                <span class="feature-badge">💼 Your Projects</span>
                <span class="feature-badge">👥 Team Ready</span>
                <span class="feature-badge">📊 Analytics</span>
            </div>
            <button class="overlay-btn" id="loginTrigger"><span>Sign In</span></button>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const wrapper = document.querySelector('.form-container-wrapper');
    const loginBtn = document.querySelector('#loginTrigger'); // Desktop overlay button
    const registerBtn = document.querySelector('#registerTrigger'); // Desktop overlay button
    const goToRegister = document.querySelector('#goToRegister'); // Mobile/form link
    const goToLogin = document.querySelector('#goToLogin'); // Mobile/form link

    function registerMode() {
        wrapper.classList.add('register-mode');
        clearErrors();
    }

    function loginMode() {
        wrapper.classList.remove('register-mode');
        clearErrors();
    }

    function clearErrors() {
        // Clear any *client-side* error messages when toggling
        const errorMessages = document.querySelectorAll('.error-message');
        errorMessages.forEach(msg => {
            // Don't remove the success message, just hide others
            if (!msg.classList.contains('success')) {
                msg.style.display = 'none';
            }
        });
    }



    // Desktop/Overlay event listeners
    if (registerBtn) registerBtn.addEventListener('click', registerMode);
    if (loginBtn) loginBtn.addEventListener('click', loginMode);
    
    // Mobile/Form Link event listeners
    if (goToRegister) goToRegister.addEventListener('click', (e) => { 
        e.preventDefault();
        registerMode(); 
    });
    if (goToLogin) goToLogin.addEventListener('click', (e) => { 
        e.preventDefault();
        loginMode(); 
    });
});
</script>
</body>
</html>
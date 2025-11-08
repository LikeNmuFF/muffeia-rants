<?php
include '../includes/db.php';
session_start();
$success = '';
$error = '';
$is_register_mode = false;

// Generate CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = 'Security token validation failed. Please try again.';
    } else {
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
}

if (isset($_GET['registered']) && $_GET['registered'] == 'success') {
    $success = 'Registration successful! Please log in.';
    $error = ''; // Clear any errors
    $is_register_mode = false;
}

$initial_class = $is_register_mode ? 'register-mode' : '';

// Preserve form data after submission
$preserved_email = isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '';
$preserved_username = isset($_POST['username']) ? htmlspecialchars($_POST['username']) : '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Muffeia</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/auth.css">
    <link rel='stylesheet' href='../css/responsive.css'>
    <link rel="icon" href="../logo/m-blues.png" type="image/png">
</head>
<body>
    <!-- Floating background elements -->
    <div class="floating-element"></div>
    <div class="floating-element"></div>
    <div class="floating-element"></div>
    <div class="floating-element"></div>

    <div class="container">
        <header>
            <div class="logo">
                <i class="fas fa-comment-dots logo-icon"></i>
                Muffeia
            </div>
            <nav>
                <ul>
                    <li><a href="../index.php">Home</a></li>
                    <li><a href="../community/about.php" class="active">About</a></li>
                    <li><a href="../community/guidelines.php">Community</a></li>
                    <li><a href="../community/resources.php">Resources</a></li>
                    <li><a href="../community/contact.php">Contact</a></li>
                </ul>
            </nav>
            <div class="auth-buttons">
                <button class="btn btn-secondary" id="loginBtn">Login</button>
                <button class="btn btn-primary" id="registerBtn">Join Now</button>
            </div>
            <button class="mobile-menu-btn">
                <i class="fas fa-bars"></i>
            </button>
        </header>

        <section class="hero">
            <div class="hero-content">
                <h1>Express Yourself Safely. Find Support. Heal Together.</h1>
                <p>Muffeia is a secure, anonymous platform where you can share your thoughts, frustrations, and experiences without judgment. Connect with a supportive community that understands and cares.</p>
                <div class="cta-buttons">
                    <button class="btn btn-primary" id="heroRegisterBtn">
                        <i class="fas fa-user-plus"></i> Join Our Community
                    </button>
                    <button class="btn btn-secondary">
                        <i class="fas fa-play-circle"></i> How It Works
                    </button>
                </div>
            </div>
            <div class="hero-image">
                <div class="illustration">
                    <div class="illustration-content">
                        <i class="fas fa-heart"></i>
                        <h3>Safe Emotional Space</h3>
                        <p>Share your thoughts anonymously and receive supportive responses from our caring community.</p>
                    </div>
                </div>
            </div>
        </section>

        <section class="features">
            <h2 class="section-title">Why Choose Muffeia?</h2>
            <p class="section-subtitle">Our platform is designed with your emotional well-being and security in mind, providing a safe space for expression and connection.</p>
            
            <div class="features-grid">
                <div class="feature-card animate-on-scroll">
                    <div class="feature-icon">
                        <i class="fas fa-user-secret"></i>
                    </div>
                    <h3>Anonymous Posting</h3>
                    <p>Share your thoughts and feelings without revealing your identity. Your privacy is our top priority with end-to-end encryption.</p>
                </div>
                
                <div class="feature-card animate-on-scroll">
                    <div class="feature-icon">
                        <i class="fas fa-hands-helping"></i>
                    </div>
                    <h3>Supportive Community</h3>
                    <p>Connect with empathetic peers who provide comforting responses and genuine understanding without judgment.</p>
                </div>
                
                <div class="feature-card animate-on-scroll">
                    <div class="feature-icon">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <h3>Advanced Security</h3>
                    <p>Protected against SQL injection, XSS, CSRF, and other vulnerabilities with industry-standard security practices.</p>
                </div>
                
                <div class="feature-card animate-on-scroll">
                    <div class="feature-icon">
                        <i class="fas fa-brain"></i>
                    </div>
                    <h3>Mental Wellness</h3>
                    <p>Access resources, tools, and a supportive community to help you on your emotional and mental health journey.</p>
                </div>
                
                <div class="feature-card animate-on-scroll">
                    <div class="feature-icon">
                        <i class="fas fa-comments"></i>
                    </div>
                    <h3>Peer Responses</h3>
                    <p>Receive thoughtful, comforting replies from others who understand what you're going through.</p>
                </div>
                
                <div class="feature-card animate-on-scroll">
                    <div class="feature-icon">
                        <i class="fas fa-mobile-alt"></i>
                    </div>
                    <h3>Always Accessible</h3>
                    <p>Use our platform on any device, anytime you need emotional support or want to help others.</p>
                </div>
            </div>
        </section>

        <section class="security-section">
            <h2 class="section-title">Your Security Is Our Priority</h2>
            <p class="section-subtitle">We implement multiple layers of security to protect your data and ensure a safe environment for everyone.</p>
            
            <div class="security-grid">
                <div class="security-card animate-on-scroll">
                    <div class="security-icon">
                        <i class="fas fa-database"></i>
                    </div>
                    <h3>SQL Injection Protection</h3>
                    <p>Prepared statements and parameterized queries prevent database attacks.</p>
                </div>
                
                <div class="security-card animate-on-scroll">
                    <div class="security-icon">
                        <i class="fas fa-code"></i>
                    </div>
                    <h3>XSS Prevention</h3>
                    <p>Content sanitization and output escaping protect against cross-site scripting.</p>
                </div>
                
                <div class="security-card animate-on-scroll">
                    <div class="security-icon">
                        <i class="fas fa-shield-virus"></i>
                    </div>
                    <h3>CSRF Tokens</h3>
                    <p>Anti-CSRF tokens validate legitimate requests and prevent cross-site request forgery.</p>
                </div>
                
                <div class="security-card animate-on-scroll">
                    <div class="security-icon">
                        <i class="fas fa-lock"></i>
                    </div>
                    <h3>Data Encryption</h3>
                    <p>End-to-end encryption ensures your private data remains confidential.</p>
                </div>
            </div>
        </section>

        <section class="testimonials">
            <h2 class="section-title">Evaluation Process via IT professionals</h2>
            <p class="section-subtitle">Hear from evaluators who have found comfort and connection through Muffeia.</p>
            
            <div class="testimonial-slider">
                <div class="testimonial-track">
                    <div class="testimonial-slide">
                        <div class="testimonial-avatar">
                            <i class="fas fa-user"></i>
                        </div>
                        <p class="testimonial-text">"Comments from evaluator's will be printed here soon."</p>
                        <p class="testimonial-author">Nan</p>
                    </div>
                    
                    <div class="testimonial-slide">
                        <div class="testimonial-avatar">
                            <i class="fas fa-user"></i>
                        </div>
                        <p class="testimonial-text">Nan</p>
                        <p class="testimonial-author">Nan</p>
                    </div>
                    
                    <div class="testimonial-slide">
                        <div class="testimonial-avatar">
                            <i class="fas fa-user"></i>
                        </div>
                        <p class="testimonial-text">NaN</p>
                        <p class="testimonial-author">NaN</p>
                    </div>
                </div>
                
                <div class="slider-nav">
                    <div class="slider-dot active" data-slide="0"></div>
                    <div class="slider-dot" data-slide="1"></div>
                    <div class="slider-dot" data-slide="2"></div>
                </div>
            </div>
        </section>

        <footer>
            <div class="footer-logo">Muffeia</div>
            <p>Creating a safer, more compassionate online community for emotional expression and support.</p>
            
            <div class="footer-links">
                <a href="#">About Us</a>
                <a href="#">Privacy Policy</a>
                <a href="#">Community Guidelines</a>
                <a href="#">Mental Health Resources</a>
                <a href="#">Contact</a>
            </div>
            
            <p class="copyright">© 2025 Muffeia. All rights reserved. Built with security and compassion in mind.</p>
        </footer>
    </div>

    <!-- Login Modal -->
    <div class="modal" id="loginModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Welcome Back</h2>
                <button class="close-modal">&times;</button>
            </div>
            <div class="modal-body">
                <?php if ($success): ?>
                    <div class="success-message"><?php echo $success; ?></div>
                <?php elseif ($error && !$is_register_mode): ?>
                    <div class="error-message"><?php echo $error; ?></div>
                <?php endif; ?>
                <form id="loginForm" method="POST" action="">
                    <input type="hidden" name="action" value="login">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    <div class="form-group">
                        <label class="form-label" for="loginEmail">Email</label>
                        <input type="email" id="loginEmail" name="email" class="form-input" placeholder="Enter your email" value="<?php echo $preserved_email; ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="loginPassword">Password</label>
                        <div style="position: relative;">
                            <input type="password" id="loginPassword" name="password" class="form-input" placeholder="Enter your password" required>
                            <i class="fas fa-eye password-toggle" data-target="loginPassword"></i>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 20px;">Sign In</button>
                </form>
                <div class="form-footer">
                    <p>Don't have an account? <a href="#" id="switchToRegister">Sign up here</a></p>
                    <p><a href="forgot_password">Forgot your password?</a></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Register Modal -->
    <div class="modal" id="registerModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Join Muffeia</h2>
                <button class="close-modal">&times;</button>
            </div>
            <div class="modal-body">
                <?php if ($error && $is_register_mode): ?>
                    <div class="error-message"><?php echo $error; ?></div>
                <?php endif; ?>
                <form id="registerForm" method="POST" action="">
                    <input type="hidden" name="action" value="register">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    <div class="form-group">
                        <label class="form-label" for="registerUsername">Username (2 words allowed)</label>
                        <input type="text" id="registerUsername" name="username" class="form-input" placeholder="e.g., Happy User" pattern="[a-zA-Z0-9_ ]{3,30}" title="Username can contain letters, numbers, underscores, and spaces (3-30 characters)" value="<?php echo $preserved_username; ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="registerEmail">Email</label>
                        <input type="email" id="registerEmail" name="email" class="form-input" placeholder="Enter your email" value="<?php echo $preserved_email; ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="registerPassword">Password</label>
                        <div style="position: relative;">
                            <input type="password" id="registerPassword" name="password" class="form-input" placeholder="Create a strong password" required>
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
                    </div>
                    <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 20px;" disabled>Create Account</button>
                </form>
                <div class="form-footer">
                    <p>Already have an account? <a href="#" id="switchToLogin">Sign in here</a></p>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Modal functionality
        const loginModal = document.getElementById('loginModal');
        const registerModal = document.getElementById('registerModal');
        const loginBtn = document.getElementById('loginBtn');
        const registerBtn = document.getElementById('registerBtn');
        const heroRegisterBtn = document.getElementById('heroRegisterBtn');
        const closeModalBtns = document.querySelectorAll('.close-modal');
        const switchToRegister = document.getElementById('switchToRegister');
        const switchToLogin = document.getElementById('switchToLogin');

        function openModal(modal) {
            modal.style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }

        function closeModal(modal) {
            modal.style.display = 'none';
            document.body.style.overflow = 'auto';
        }

        loginBtn.addEventListener('click', () => openModal(loginModal));
        registerBtn.addEventListener('click', () => openModal(registerModal));
        heroRegisterBtn.addEventListener('click', () => openModal(registerModal));

        closeModalBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                closeModal(loginModal);
                closeModal(registerModal);
            });
        });

        switchToRegister.addEventListener('click', (e) => {
            e.preventDefault();
            closeModal(loginModal);
            openModal(registerModal);
        });

        switchToLogin.addEventListener('click', (e) => {
            e.preventDefault();
            closeModal(registerModal);
            openModal(loginModal);
        });

        // Close modal when clicking outside
        window.addEventListener('click', (e) => {
            if (e.target === loginModal) closeModal(loginModal);
            if (e.target === registerModal) closeModal(registerModal);
        });

        // Testimonial slider functionality
        const track = document.querySelector('.testimonial-track');
        const slides = document.querySelectorAll('.testimonial-slide');
        const dots = document.querySelectorAll('.slider-dot');
        let currentSlide = 0;
        
        function goToSlide(index) {
            track.style.transform = `translateX(-${index * 100}%)`;
            currentSlide = index;
            
            // Update active dot
            dots.forEach((dot, i) => {
                dot.classList.toggle('active', i === index);
            });
        }
        
        dots.forEach(dot => {
            dot.addEventListener('click', () => {
                goToSlide(parseInt(dot.dataset.slide));
            });
        });
        
        // Auto-advance slides
        setInterval(() => {
            currentSlide = (currentSlide + 1) % slides.length;
            goToSlide(currentSlide);
        }, 5000);
        
        // Animation on scroll
        const animateOnScroll = () => {
            const elements = document.querySelectorAll('.animate-on-scroll');
            
            elements.forEach(element => {
                const elementTop = element.getBoundingClientRect().top;
                const windowHeight = window.innerHeight;
                
                if (elementTop < windowHeight - 100) {
                    element.classList.add('animated');
                }
            });
        };
        
        // Initial check
        animateOnScroll();
        
        // Check on scroll
        window.addEventListener('scroll', animateOnScroll);
        
        // Mobile menu toggle
        const mobileMenuBtn = document.querySelector('.mobile-menu-btn');
        const nav = document.querySelector('nav ul');
        
        mobileMenuBtn.addEventListener('click', () => {
            nav.style.display = nav.style.display === 'flex' ? 'none' : 'flex';
        });
        
        // Adjust nav for window resize
        window.addEventListener('resize', () => {
            if (window.innerWidth > 768) {
                nav.style.display = 'flex';
            } else {
                nav.style.display = 'none';
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

        // Password strength checker
        let currentPasswordStrength = 'weak';

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
            const submitBtn = document.querySelector('#registerForm .btn');
            if (result.level === 'weak') {
                submitBtn.disabled = true;
            } else {
                submitBtn.disabled = false;
            }
        }

        // Add password strength checker to register form
        const registerPasswordInput = document.getElementById('registerPassword');
        const strengthElement = document.querySelector('.password-strength');
        
        if (registerPasswordInput && strengthElement) {
            registerPasswordInput.addEventListener('input', function() {
                updatePasswordStrength(this.value, strengthElement);
            });
        }

        // Form validation
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            // Form will be submitted to PHP backend
        });

        document.getElementById('registerForm').addEventListener('submit', function(e) {
            const username = document.getElementById('registerUsername').value;
            
            // Validate two-word username
            if (!isValidTwoWordUsername(username)) {
                e.preventDefault();
                alert('Username must be 1-2 words with letters, numbers, and underscores only.');
                return;
            }
            
            // Form will be submitted to PHP backend
        });

        function isValidTwoWordUsername(username) {
            // Allow 1-2 words with letters, numbers, underscores, and spaces
            const words = username.trim().split(/\s+/);
            if (words.length > 2) return false;
            
            // Each word should match the pattern
            const pattern = /^[a-zA-Z0-9_]+$/;
            return words.every(word => pattern.test(word) && word.length >= 1);
        }

        // Auto-open modal if there's an error
        <?php if ($error && $is_register_mode): ?>
            document.addEventListener('DOMContentLoaded', function() {
                openModal(registerModal);
            });
        <?php elseif ($error && !$is_register_mode): ?>
            document.addEventListener('DOMContentLoaded', function() {
                openModal(loginModal);
            });
        <?php endif; ?>
    </script>
</body>
</html>
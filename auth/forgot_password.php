<?php
include '../includes/db.php';
session_start();

// Set timezone to UTC for consistency
date_default_timezone_set('UTC');
// Set MySQL session timezone to UTC
$conn->query("SET time_zone = '+00:00'");

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);
    
    if (empty($email)) {
        $error = 'Please enter your email address.';
    } else {
        // Check if email exists
        $sql = "SELECT id, username FROM users WHERE email = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            
            // Generate unique token
            $token = bin2hex(random_bytes(50));
            $expires = date('Y-m-d H:i:s', time() + 3600); // 1 hour from now
            
            // Delete any existing tokens for this email
            $delete_sql = "DELETE FROM password_resets WHERE email = ?";
            $delete_stmt = $conn->prepare($delete_sql);
            $delete_stmt->bind_param("s", $email);
            $delete_stmt->execute();
            
            // Insert new token
            $insert_sql = "INSERT INTO password_resets (email, token, expires_at) VALUES (?, ?, ?)";
            $insert_stmt = $conn->prepare($insert_sql);
            $insert_stmt->bind_param("sss", $email, $token, $expires);
            
            if ($insert_stmt->execute()) {
                // Send email with reset link
                $reset_link = "http://localhost:8080/auth/reset_password.php?token=" . $token;
                
                // Email content
                $to = $email;
                $subject = "MUFFEIA - Password Reset Request";
                $message = "
                <html>
                <head>
                    <style>
                        body { font-family: Arial, sans-serif; }
                        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                        .header { background: #7c3aed; color: white; padding: 20px; text-align: center; border-radius: 10px 10px 0 0; }
                        .content { padding: 20px; background: #f8fafc; border-radius: 0 0 10px 10px; }
                        .button { background: #7c3aed; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; display: inline-block; }
                        .footer { padding: 20px; text-align: center; color: #64748b; font-size: 14px; }
                    </style>
                </head>
                <body>
                    <div class='container'>
                        <div class='header'>
                            <h1>MUFFEIA</h1>
                            <p>Password Reset Request</p>
                        </div>
                        <div class='content'>
                            <h3>Hello {$user['username']},</h3>
                            <p>You requested to reset your password for your MUFFEIA account.</p>
                            <p>Click the button below to reset your password:</p>
                            <p style='text-align: center;'>
                                <a href='{$reset_link}' class='button'>Reset Password</a>
                            </p>
                            <p>Or copy and paste this link in your browser:<br>
                            <small>{$reset_link}</small></p>
                            <p><strong>This link will expire in 1 hour.</strong></p>
                            <p>If you didn't request this reset, please ignore this email.</p>
                        </div>
                        <div class='footer'>
                            <p>&copy; " . date("Y") . " MUFFEIA. All rights reserved.</p>
                        </div>
                    </div>
                </body>
                </html>
                ";
                
                // Email headers
                $headers = "MIME-Version: 1.0" . "\r\n";
                $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
                $headers .= "From: MUFFEIA <noreply@muffeia.com>" . "\r\n";
                $headers .= "Reply-To: noreply@muffeia.com" . "\r\n";
                
                // Send email
                if (mail($to, $subject, $message, $headers)) {
                    $success = "Password reset link has been sent to your email! Check your inbox (and spam folder).";
                } else {
                    $error = "Failed to send email. Please try again or contact support.";
                }
            } else {
                $error = 'Error generating reset token. Please try again.';
            }
        } else {
            $error = 'No account found with that email address.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MUFFEIA - Forgot Password</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="icon" href="../logo/m-blues.png" type="image/png">
    <style>
        /* Import the auth.css styles */
        :root {
            --primary: #7c3aed;
            --primary-dark: #6d28d9;
            --primary-light: #8b5cf6;
            --secondary: #f59e0b;
            --secondary-light: #fbbf24;
            --accent: #10b981;
            --dark: #1e293b;
            --darker: #0f172a;
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
            background: linear-gradient(135deg, var(--darker) 0%, var(--dark) 100%);
            min-height: 100vh;
            color: var(--light);
            overflow-x: hidden;
            position: relative;
        }

        /* Floating animation for background elements */
        @keyframes float {
            0%, 100% { transform: translateY(0) rotate(0deg); }
            50% { transform: translateY(-20px) rotate(5deg); }
        }

        @keyframes float-reverse {
            0%, 100% { transform: translateY(0) rotate(0deg); }
            50% { transform: translateY(20px) rotate(-5deg); }
        }

        .floating-element {
            position: absolute;
            border-radius: 50%;
            opacity: 0.05;
            pointer-events: none;
            z-index: 0;
            animation-duration: 8s;
            animation-iteration-count: infinite;
            animation-timing-function: ease-in-out;
        }

        .floating-element:nth-child(odd) {
            animation-name: float;
        }

        .floating-element:nth-child(even) {
            animation-name: float-reverse;
        }

        .floating-element:nth-child(1) {
            width: 500px;
            height: 500px;
            background: var(--primary);
            top: -250px;
            right: -250px;
            animation-delay: 0s;
        }

        .floating-element:nth-child(2) {
            width: 400px;
            height: 400px;
            background: var(--primary-light);
            bottom: -200px;
            left: -200px;
            animation-delay: 1s;
        }

        .floating-element:nth-child(3) {
            width: 300px;
            height: 300px;
            background: var(--secondary);
            top: 20%;
            left: 10%;
            animation-delay: 2s;
        }

        .floating-element:nth-child(4) {
            width: 200px;
            height: 200px;
            background: var(--accent);
            bottom: 30%;
            right: 15%;
            animation-delay: 3s;
        }

        /* Forgot Password Specific Styles */
        .auth-page {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 20px;
            position: relative;
            z-index: 1;
        }

        .auth-container {
            width: 100%;
            max-width: 440px;
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 40px 32px;
            box-shadow: var(--shadow-lg);
            border: 1px solid rgba(255, 255, 255, 0.1);
            animation: slideInUp 0.8s ease-out;
        }

        @keyframes slideInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .brand {
            text-align: center;
            margin-bottom: 32px;
        }

        .brand-logo {
            font-size: 2.5rem;
            font-weight: 800;
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            margin-bottom: 10px;
            letter-spacing: -1px;
        }

        .title {
            font-size: 1.6rem;
            font-weight: 700;
            margin-bottom: 8px;
            color: var(--light);
            text-align: center;
        }

        .subtitle {
            color: var(--gray);
            text-align: center;
            margin-bottom: 32px;
            font-size: 0.95rem;
            line-height: 1.5;
        }

        .form-group {
            margin-bottom: 24px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--light);
        }

        .form-group input {
            width: 100%;
            padding: 14px 16px;
            border: 2px solid rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            font-size: 0.95rem;
            background: rgba(255, 255, 255, 0.05);
            color: var(--light);
            transition: all 0.3s ease;
        }

        .form-group input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(124, 58, 237, 0.1);
        }

        .form-group input::placeholder {
            color: var(--gray);
        }

        .btn-submit {
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
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            box-shadow: 0 4px 15px rgba(124, 58, 237, 0.4);
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(124, 58, 237, 0.6);
        }

        .back-link {
            text-align: center;
        }

        .back-link a {
            color: var(--primary-light);
            text-decoration: none;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: color 0.3s ease;
        }

        .back-link a:hover {
            color: var(--primary);
        }

        .alert {
            padding: 12px 16px;
            border-radius: 10px;
            margin-bottom: 24px;
            font-size: 0.9rem;
            line-height: 1.4;
            display: flex;
            align-items: center;
            gap: 8px;
            animation: slideInLeft 0.5s ease-out;
        }

        @keyframes slideInLeft {
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
            background: rgba(239, 68, 68, 0.1);
            color: var(--danger);
            border-left: 4px solid var(--danger);
        }

        .alert-success {
            background: rgba(16, 185, 129, 0.1);
            color: var(--success);
            border-left: 4px solid var(--success);
        }

        /* Responsive Styles */
        @media (max-width: 576px) {
            .auth-container {
                padding: 30px 20px;
            }
            
            .brand-logo {
                font-size: 2rem;
            }
            
            .title {
                font-size: 1.4rem;
            }
        }
    </style>
</head>
<body>
    <!-- Background floating elements -->
    <div class="floating-element"></div>
    <div class="floating-element"></div>
    <div class="floating-element"></div>
    <div class="floating-element"></div>
    
    <div class="auth-page">
        <div class="auth-container">
            <div class="brand">
                <div class="brand-logo">MUFFEIA</div>
                <h1 class="title">Reset Password</h1>
                <p class="subtitle">Enter your email to receive a password reset link</p>
            </div>

            <!-- PHP error/success messages would go here -->
            <!-- Example error message -->
            <!-- <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                No account found with that email address.
            </div> -->
            
            <!-- Example success message -->
            <!-- <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                Password reset link has been sent to your email! Check your inbox (and spam folder).
            </div> -->

            <form method="POST" action="forgot_password.php">
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" required 
                           placeholder="Enter your email address">
                </div>
                
                <button type="submit" class="btn-submit">
                    <i class="fas fa-paper-plane"></i> Send Reset Link
                </button>
            </form>
            
            <div class="back-link">
                <a href="login.php">
                    <i class="fas fa-arrow-left"></i> Back to Login
                </a>
            </div>
        </div>
    </div>
</body>
</html>
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
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
        }

        body {
            background: linear-gradient(135deg, #1e293b 0%, #334155 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .auth-container {
            width: 100%;
            max-width: 440px;
            background: var(--light);
            border-radius: 20px;
            padding: 40px 32px;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }

        .brand {
            text-align: center;
            margin-bottom: 32px;
        }

        .brand-logo {
            font-size: 2.5rem;
            font-weight: 800;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            margin-bottom: 10px;
        }

        .title {
            font-size: 1.6rem;
            font-weight: 700;
            margin-bottom: 8px;
            color: var(--dark);
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
            color: var(--dark);
        }

        .form-group input {
            width: 100%;
            padding: 14px 16px;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            font-size: 0.95rem;
            background: white;
            transition: all 0.3s ease;
        }

        .form-group input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(124, 58, 237, 0.1);
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
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(124, 58, 237, 0.4);
        }

        .back-link {
            text-align: center;
        }

        .back-link a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
        }

        .back-link a:hover {
            text-decoration: underline;
        }

        .alert {
            padding: 12px 16px;
            border-radius: 10px;
            margin-bottom: 24px;
            font-size: 0.9rem;
            line-height: 1.4;
        }

        .alert-error {
            background: #fef2f2;
            color: #dc2626;
            border-left: 4px solid #dc2626;
        }

        .alert-success {
            background: #f0fdf4;
            color: #16a34a;
            border-left: 4px solid #16a34a;
        }
    </style>
</head>
<body>
    <div class="auth-container">
        <div class="brand">
            <div class="brand-logo">MUFFEIA</div>
            <h1 class="title">Reset Password</h1>
            <p class="subtitle">Enter your email to receive a password reset link</p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($success)): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <?php echo $success; ?>
            </div>
        <?php endif; ?>

        <?php if (empty($success)): ?>
            <form method="POST" action="forgot_password.php">
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" required 
                           placeholder="Enter your email address" 
                           value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                </div>
                
                <button type="submit" class="btn-submit">
                    <i class="fas fa-paper-plane"></i> Send Reset Link
                </button>
            </form>
        <?php endif; ?>
        
        <div class="back-link">
            <a href="login.php">
                <i class="fas fa-arrow-left"></i> Back to Login
            </a>
        </div>
    </div>
</body>
</html>
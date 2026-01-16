<?php
/**
 * CLIENT PORTAL LOGIN
 * Login page for clients to access their portal
 */

session_start();

// If already logged in, redirect to dashboard
if (isset($_SESSION['client_id'])) {
    header("Location: dashboard.php");
    exit();
}

require_once '../config/database.php';

$error = "";

// Process login form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error = "Please fill in all fields";
    } else {
        try {
            $stmt = $conn->prepare("SELECT ca.*, c.ClientId, c.FirstName, c.LastName, c.Email 
                                    FROM CLIENT_AUTH ca 
                                    JOIN CLIENT c ON ca.ClientId = c.ClientId 
                                    WHERE ca.Username = ? AND ca.IsActive = TRUE");
            $stmt->execute([$username]);
            $client_auth = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($client_auth && password_verify($password, $client_auth['Password'])) {
                // Update last login
                $stmt = $conn->prepare("UPDATE CLIENT_AUTH SET LastLogin = NOW() WHERE AuthId = ?");
                $stmt->execute([$client_auth['AuthId']]);
                
                // Set session variables
                $_SESSION['client_id'] = $client_auth['ClientId'];
                $_SESSION['client_name'] = $client_auth['FirstName'] . ' ' . $client_auth['LastName'];
                $_SESSION['client_email'] = $client_auth['Email'];
                
                header("Location: dashboard.php");
                exit();
            } else {
                $error = "Invalid username or password";
            }
        } catch(PDOException $e) {
            $error = "Login error: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Client Portal Login - Law Firm Management System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        html, body {
            height: 100%;
            margin: 0;
            padding: 0;
            overflow-x: hidden;
        }
        
        .login-page {
            min-height: 100vh;
            position: relative;
            display: block;
            overflow-x: hidden;
            overflow-y: auto;
            background: linear-gradient(135deg, #8b5cf6 0%, #6366f1 50%, #ec4899 100%);
            background-size: 400% 400%;
            animation: gradientShift 15s ease infinite;
        }
        
        @keyframes gradientShift {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
        
        .login-container {
            padding: 20px;
            position: relative;
            z-index: 2;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: calc(100vh - 150px);
            margin: 0 auto;
        }
        
        /* Floating animated elements */
        .floating-shapes {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: 0;
            overflow: hidden;
        }
        
        /* Footer styles */
        footer {
            text-align: center !important;
        }
        
        footer div {
            text-align: center !important;
        }
        
        footer p {
            text-align: center !important;
            width: 100%;
        }
        
        footer a {
            text-align: center !important;
        }
        
        .shape {
            position: absolute;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.08);
            backdrop-filter: blur(20px);
            animation: float 25s infinite ease-in-out;
        }
        
        .shape:nth-child(1) {
            width: 400px;
            height: 400px;
            top: -10%;
            left: -5%;
            animation-delay: 0s;
            background: rgba(139, 92, 246, 0.15);
        }
        
        .shape:nth-child(2) {
            width: 300px;
            height: 300px;
            bottom: -5%;
            right: -5%;
            animation-delay: 3s;
            background: rgba(99, 102, 241, 0.15);
        }
        
        .shape:nth-child(3) {
            width: 200px;
            height: 200px;
            top: 50%;
            left: 5%;
            animation-delay: 6s;
            background: rgba(236, 72, 153, 0.12);
        }
        
        .shape:nth-child(4) {
            width: 250px;
            height: 250px;
            top: 20%;
            right: 10%;
            animation-delay: 9s;
            background: rgba(139, 92, 246, 0.1);
        }
        
        /* Add subtle speckles */
        .login-page::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: radial-gradient(circle, rgba(255, 255, 255, 0.1) 1px, transparent 1px);
            background-size: 30px 30px;
            pointer-events: none;
            z-index: 0;
        }
        
        @keyframes float {
            0%, 100% {
                transform: translate(0, 0) rotate(0deg);
            }
            25% {
                transform: translate(50px, -50px) rotate(90deg);
            }
            50% {
                transform: translate(-30px, 30px) rotate(180deg);
            }
            75% {
                transform: translate(30px, 50px) rotate(270deg);
            }
        }
        
        /* Enhanced login box */
        .login-box {
            animation: slideInUp 0.8s ease-out;
            position: relative;
            z-index: 3;
            max-width: 450px;
            width: 100%;
            margin: 0 auto;
            transform: translateY(0);
        }
        
        .glass-effect {
            background: rgba(255, 255, 255, 0.25) !important;
            backdrop-filter: blur(30px) saturate(180%) !important;
            -webkit-backdrop-filter: blur(30px) saturate(180%) !important;
            border: 2px solid rgba(255, 255, 255, 0.4) !important;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3) !important;
        }
        
        @keyframes slideInUp {
            from {
                opacity: 0;
                transform: translateY(50px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        /* Enhanced icon wrapper */
        .icon-wrapper {
            animation: bounceIn 1s ease-out;
            background: rgba(255, 255, 255, 0.3) !important;
            border: 2px solid rgba(255, 255, 255, 0.5) !important;
        }
        
        @keyframes bounceIn {
            0% {
                opacity: 0;
                transform: scale(0.3);
            }
            50% {
                opacity: 1;
                transform: scale(1.1);
            }
            100% {
                transform: scale(1);
            }
        }
        
        .login-header h1 {
            color: #ffffff !important;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.3) !important;
            font-size: 36px !important;
            font-weight: 700 !important;
        }
        
        .login-header h2 {
            color: rgba(255, 255, 255, 0.95) !important;
            text-shadow: 0 1px 5px rgba(0, 0, 0, 0.2) !important;
            font-size: 16px !important;
            font-weight: 400 !important;
        }
        
        /* Form inputs with glow effect */
        .login-form .form-group input:focus,
        .login-form .form-group select:focus {
            box-shadow: 0 0 20px rgba(255, 255, 255, 0.5), 0 0 40px rgba(255, 255, 255, 0.3);
            border-color: rgba(255, 255, 255, 0.7);
            background: rgba(255, 255, 255, 0.35);
        }
        
        /* Ensure labels are visible */
        .login-form .form-group label {
            color: #ffffff !important;
            text-shadow: 0 1px 3px rgba(0, 0, 0, 0.3) !important;
            font-weight: 600 !important;
        }
        
        /* Input placeholder visibility */
        .login-form input::placeholder,
        .login-form input::-webkit-input-placeholder,
        .login-form input::-moz-placeholder,
        .login-form input:-ms-input-placeholder {
            color: rgba(255, 255, 255, 0.85) !important;
            opacity: 1 !important;
        }
        
        /* Better contrast for form elements */
        .login-form input,
        .login-form select {
            color: rgba(255, 255, 255, 0.95) !important;
        }
        
        /* Button hover effects */
        .btn-primary {
            position: relative;
            overflow: hidden;
        }
        
        .btn-primary::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.3);
            transform: translate(-50%, -50%);
            transition: width 0.6s, height 0.6s;
        }
        
        .btn-primary:hover::before {
            width: 300px;
            height: 300px;
        }
        
        /* Pulse animation for icon */
        .icon-wrapper i {
            animation: pulse 2s ease-in-out infinite;
        }
        
        @keyframes pulse {
            0%, 100% {
                transform: scale(1);
            }
            50% {
                transform: scale(1.1);
            }
        }
    </style>
</head>
<body class="login-page">
    <!-- Floating animated shapes -->
    <div class="floating-shapes">
        <div class="shape"></div>
        <div class="shape"></div>
        <div class="shape"></div>
        <div class="shape"></div>
    </div>
    
    <div class="login-container">
        <div class="login-box glass-effect">
            <div class="login-header">
                <div class="icon-wrapper">
                    <i class="fas fa-user-circle"></i>
                </div>
                <h1>Client Portal</h1>
                <h2>Access Your Cases & Documents</h2>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="" class="login-form">
                <div class="form-group">
                    <label for="username">
                        <i class="fas fa-user"></i> Username
                    </label>
                    <div class="input-wrapper">
                        <input type="text" id="username" name="username" required autofocus placeholder="Enter your username">
                        <i class="fas fa-user input-icon"></i>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="password">
                        <i class="fas fa-lock"></i> Password
                    </label>
                    <div class="input-wrapper">
                        <input type="password" id="password" name="password" required placeholder="Enter your password">
                        <i class="fas fa-lock input-icon"></i>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary btn-login">
                    <span>Sign In</span>
                    <i class="fas fa-arrow-right"></i>
                </button>
            </form>
            
            <div style="margin-top: 24px; text-align: center; padding-top: 24px; border-top: 1px solid rgba(255, 255, 255, 0.2);">
                <p style="color: rgba(255, 255, 255, 0.9); margin-bottom: 16px; font-size: 14px;">
                    Don't have an account?
                </p>
                <a href="register.php" class="btn btn-secondary" style="width: 100%; background: rgba(255, 255, 255, 0.2); border: 2px solid rgba(255, 255, 255, 0.3);">
                    <i class="fas fa-user-plus"></i> Create Account
                </a>
            </div>
            
            <div style="margin-top: 20px; text-align: center;">
                <a href="../index.php" class="btn btn-secondary" style="width: 100%; background: transparent; border: 1px solid rgba(255, 255, 255, 0.3);">
                    <i class="fas fa-arrow-left"></i> Back to Home
                </a>
            </div>
        </div>
    </div>
    
    <!-- Footer -->
    <footer style="background: rgba(15, 23, 42, 0.9); color: #ffffff; padding: 25px 20px; position: relative; z-index: 1; width: 100%; box-sizing: border-box; margin-top: 60px;">
        <div style="max-width: 1200px; margin: 0 auto; text-align: center !important; width: 100%;">
            <p style="color: #cbd5e1; font-size: 14px; margin-bottom: 10px; text-align: center !important; width: 100%;">
                <a href="../database/check_users.php" style="color: #a78bfa; text-decoration: none; transition: color 0.3s; display: inline-block; text-align: center;"><i class="fas fa-question-circle"></i> Having login issues? Check Users</a>
            </p>
            <p style="color: #94a3b8; font-size: 12px; text-align: center !important; margin: 0; width: 100%;">&copy; <?php echo date('Y'); ?> Law Firm Management System. All rights reserved.</p>
        </div>
    </footer>
</body>
</html>

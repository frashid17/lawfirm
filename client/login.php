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
        
        .login-box {
            animation: slideInUp 0.8s ease-out;
            position: relative;
            z-index: 3;
            max-width: 450px;
            width: 100%;
            margin: 0 auto;
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
        
        footer {
            background: rgba(15, 23, 42, 0.9);
            color: #ffffff;
            padding: 25px 20px;
            position: relative;
            z-index: 1;
            width: 100%;
            box-sizing: border-box;
            margin-top: 60px;
            text-align: center;
        }
    </style>
</head>
<body class="login-page">
    <!-- Floating animated shapes -->
    <div class="floating-shapes">
        <div class="shape"></div>
        <div class="shape"></div>
    </div>
    
    <div class="login-container">
        <div class="login-box glass-effect">
            <div class="login-header">
                <div class="icon-wrapper">
                    <i class="fas fa-user"></i>
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
    <footer>
        <div style="max-width: 1200px; margin: 0 auto; text-align: center !important; width: 100%;">
            <p style="color: #94a3b8; font-size: 12px; text-align: center !important; margin: 0; width: 100%;">&copy; <?php echo date('Y'); ?> Law Firm Management System. All rights reserved.</p>
        </div>
    </footer>
</body>
</html>

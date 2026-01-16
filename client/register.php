<?php
/**
 * CLIENT REGISTRATION
 * Registration page for new clients to create an account
 */

session_start();

// If already logged in, redirect to dashboard
if (isset($_SESSION['client_id'])) {
    header("Location: dashboard.php");
    exit();
}

require_once '../config/database.php';

$error = "";
$success = "";

// Process registration form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $firstName = trim($_POST['firstName'] ?? '');
    $lastName = trim($_POST['lastName'] ?? '');
    $phoneNo = trim($_POST['phoneNo'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirmPassword'] ?? '';
    
    // Validation
    if (empty($firstName) || empty($lastName) || empty($phoneNo) || empty($username) || empty($password)) {
        $error = "Please fill in all required fields";
    } elseif ($password !== $confirmPassword) {
        $error = "Passwords do not match";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters long";
    } else {
        try {
            // Check if username already exists
            $stmt = $conn->prepare("SELECT AuthId FROM CLIENT_AUTH WHERE Username = ?");
            $stmt->execute([$username]);
            if ($stmt->fetch()) {
                $error = "Username already exists. Please choose a different username.";
            } else {
                // Check if phone number already exists
                $stmt = $conn->prepare("SELECT ClientId FROM CLIENT WHERE PhoneNo = ?");
                $stmt->execute([$phoneNo]);
                if ($stmt->fetch()) {
                    $error = "A client with this phone number already exists. Please contact support if this is an error.";
                } else {
                    // Check if email already exists (if provided)
                    if (!empty($email)) {
                        $stmt = $conn->prepare("SELECT ClientId FROM CLIENT WHERE Email = ?");
                        $stmt->execute([$email]);
                        if ($stmt->fetch()) {
                            $error = "A client with this email already exists. Please use a different email or contact support.";
                        }
                    }
                    
                    if (empty($error)) {
                        // Start transaction
                        $conn->beginTransaction();
                        
                        try {
                            // Insert into CLIENT table
                            $stmt = $conn->prepare("INSERT INTO CLIENT (FirstName, LastName, PhoneNo, Email, Address) VALUES (?, ?, ?, ?, ?)");
                            $stmt->execute([$firstName, $lastName, $phoneNo, $email ?: null, $address ?: null]);
                            $clientId = $conn->lastInsertId();
                            
                            // Hash password
                            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                            
                            // Insert into CLIENT_AUTH table
                            $stmt = $conn->prepare("INSERT INTO CLIENT_AUTH (ClientId, Username, Password, IsActive) VALUES (?, ?, ?, 1)");
                            $stmt->execute([$clientId, $username, $hashedPassword]);
                            
                            // Commit transaction
                            $conn->commit();
                            
                            $success = "Account created successfully! You can now log in.";
                            
                            // Clear form data
                            $firstName = $lastName = $phoneNo = $email = $address = $username = '';
                            
                        } catch(PDOException $e) {
                            $conn->rollBack();
                            $error = "Registration failed: " . $e->getMessage();
                        }
                    }
                }
            }
        } catch(PDOException $e) {
            $error = "Registration error: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Client Registration - Law Firm Management System</title>
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
            max-width: 500px;
            width: 100%;
            margin: 0 auto;
            transform: translateY(0);
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
                    <i class="fas fa-user-plus"></i>
                </div>
                <h1>Create Account</h1>
                <h2>Register as a Client</h2>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="" class="login-form">
                <div class="form-group">
                    <label for="firstName">
                        <i class="fas fa-user"></i> First Name <span style="color: rgba(255, 255, 255, 0.7);">*</span>
                    </label>
                    <div class="input-wrapper">
                        <input type="text" id="firstName" name="firstName" required autofocus placeholder="Enter your first name" value="<?php echo htmlspecialchars($firstName ?? ''); ?>">
                        <i class="fas fa-user input-icon"></i>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="lastName">
                        <i class="fas fa-user"></i> Last Name <span style="color: rgba(255, 255, 255, 0.7);">*</span>
                    </label>
                    <div class="input-wrapper">
                        <input type="text" id="lastName" name="lastName" required placeholder="Enter your last name" value="<?php echo htmlspecialchars($lastName ?? ''); ?>">
                        <i class="fas fa-user input-icon"></i>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="phoneNo">
                        <i class="fas fa-phone"></i> Phone Number <span style="color: rgba(255, 255, 255, 0.7);">*</span>
                    </label>
                    <div class="input-wrapper">
                        <input type="tel" id="phoneNo" name="phoneNo" required placeholder="e.g., 254712345678" value="<?php echo htmlspecialchars($phoneNo ?? ''); ?>">
                        <i class="fas fa-phone input-icon"></i>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="email">
                        <i class="fas fa-envelope"></i> Email Address
                    </label>
                    <div class="input-wrapper">
                        <input type="email" id="email" name="email" placeholder="Enter your email (optional)" value="<?php echo htmlspecialchars($email ?? ''); ?>">
                        <i class="fas fa-envelope input-icon"></i>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="address">
                        <i class="fas fa-map-marker-alt"></i> Address
                    </label>
                    <div class="input-wrapper">
                        <input type="text" id="address" name="address" placeholder="Enter your address (optional)" value="<?php echo htmlspecialchars($address ?? ''); ?>">
                        <i class="fas fa-map-marker-alt input-icon"></i>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="username">
                        <i class="fas fa-user-circle"></i> Username <span style="color: rgba(255, 255, 255, 0.7);">*</span>
                    </label>
                    <div class="input-wrapper">
                        <input type="text" id="username" name="username" required placeholder="Choose a username" value="<?php echo htmlspecialchars($username ?? ''); ?>">
                        <i class="fas fa-user-circle input-icon"></i>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="password">
                        <i class="fas fa-lock"></i> Password <span style="color: rgba(255, 255, 255, 0.7);">*</span>
                    </label>
                    <div class="input-wrapper">
                        <input type="password" id="password" name="password" required placeholder="Enter password (min. 6 characters)">
                        <i class="fas fa-lock input-icon"></i>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="confirmPassword">
                        <i class="fas fa-lock"></i> Confirm Password <span style="color: rgba(255, 255, 255, 0.7);">*</span>
                    </label>
                    <div class="input-wrapper">
                        <input type="password" id="confirmPassword" name="confirmPassword" required placeholder="Confirm your password">
                        <i class="fas fa-lock input-icon"></i>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary btn-login">
                    <span>Create Account</span>
                    <i class="fas fa-user-plus"></i>
                </button>
            </form>
            
            <div style="margin-top: 24px; text-align: center; padding-top: 24px; border-top: 1px solid rgba(255, 255, 255, 0.2);">
                <p style="color: rgba(255, 255, 255, 0.9); margin-bottom: 16px; font-size: 14px;">
                    Already have an account?
                </p>
                <a href="login.php" class="btn btn-secondary" style="width: 100%; background: rgba(255, 255, 255, 0.2); border: 2px solid rgba(255, 255, 255, 0.3);">
                    <i class="fas fa-sign-in-alt"></i> Sign In
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

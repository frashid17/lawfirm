<?php
/**
 * LOGIN PAGE
 * Sign in page for the Law Firm Management System
 */

session_start();

// If already logged in, redirect to dashboard
if (isset($_SESSION['user_id']) && isset($_SESSION['user_role'])) {
    $role = $_SESSION['user_role'];
    if ($role === 'admin') {
        header("Location: admin/dashboard.php");
    } elseif ($role === 'advocate') {
        header("Location: advocate/dashboard.php");
    } elseif ($role === 'receptionist') {
        header("Location: receptionist/dashboard.php");
    }
    exit();
}

// Check if client is already logged in
if (isset($_SESSION['client_id'])) {
    header("Location: client/dashboard.php");
    exit();
}

// Include database connection
require_once 'config/database.php';

$error = "";
$show_security_question = false;
$security_question = "";
$login_attempt = $_SESSION['login_attempt'] ?? null;

// Handle security question answer
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['security_answer'])) {
    $security_answer = trim($_POST['security_answer'] ?? '');
    $username = $_SESSION['login_attempt']['username'] ?? '';
    $role = $_SESSION['login_attempt']['role'] ?? '';
    $user_id = $_SESSION['login_attempt']['user_id'] ?? null;
    $table_name = $_SESSION['login_attempt']['table_name'] ?? '';
    $id_field = $_SESSION['login_attempt']['id_field'] ?? '';
    
    if (empty($security_answer)) {
        $error = "Please enter your security answer";
        $show_security_question = true;
        $security_question = $_SESSION['login_attempt']['security_question'] ?? '';
    } else {
        try {
            // Get user's security answer
            $stmt = $conn->prepare("SELECT SecurityAnswer, FailedAttempts, IsLocked FROM {$table_name} WHERE {$id_field} = ?");
            $stmt->execute([$user_id]);
            $user_data = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$user_data) {
                $error = "User not found";
                unset($_SESSION['login_attempt']);
            } elseif ($user_data['IsLocked']) {
                $error = "Your account is locked. Please contact the administrator to unlock it.";
                unset($_SESSION['login_attempt']);
            } else {
                // Check if security answer matches (case-insensitive)
                if (strtolower(trim($user_data['SecurityAnswer'])) === strtolower(trim($security_answer))) {
                    // Correct answer - reset failed attempts and complete login
                    $stmt = $conn->prepare("UPDATE {$table_name} SET FailedAttempts = 0 WHERE {$id_field} = ?");
                    $stmt->execute([$user_id]);
                    
                    // Set session variables and redirect
                    if ($role === 'client') {
                        $_SESSION['client_id'] = $_SESSION['login_attempt']['client_id'];
                        $_SESSION['client_name'] = $_SESSION['login_attempt']['user_name'];
                        $_SESSION['client_email'] = $_SESSION['login_attempt']['email'] ?? '';
                        unset($_SESSION['login_attempt']);
                        header("Location: client/dashboard.php");
                    } else {
                        $_SESSION['user_id'] = $user_id;
                        $_SESSION['user_role'] = $role;
                        $_SESSION['user_name'] = $_SESSION['login_attempt']['user_name'];
                        $_SESSION['username'] = $username;
                        unset($_SESSION['login_attempt']);
                        
                        if ($role === 'admin') {
                            header("Location: admin/dashboard.php");
                        } elseif ($role === 'advocate') {
                            header("Location: advocate/dashboard.php");
                        } elseif ($role === 'receptionist') {
                            header("Location: receptionist/dashboard.php");
                        }
                    }
                    exit();
                } else {
                    // Wrong answer - increment failed attempts
                    $failed_attempts = ($user_data['FailedAttempts'] ?? 0) + 1;
                    $is_locked = false;
                    
                    // Lock account after 3 failed attempts
                    if ($failed_attempts >= 3) {
                        $is_locked = true;
                        $stmt = $conn->prepare("UPDATE {$table_name} SET FailedAttempts = ?, IsLocked = TRUE, LockedAt = NOW() WHERE {$id_field} = ?");
                        $stmt->execute([$failed_attempts, $user_id]);
                        $error = "Your account has been locked after 3 failed attempts. Please contact the administrator to unlock it.";
                        unset($_SESSION['login_attempt']);
                    } else {
                        $stmt = $conn->prepare("UPDATE {$table_name} SET FailedAttempts = ? WHERE {$id_field} = ?");
                        $stmt->execute([$failed_attempts, $user_id]);
                        $remaining = 5 - $failed_attempts;
                        $error = "Incorrect security answer. You have {$remaining} attempt(s) remaining.";
                        $show_security_question = true;
                        $security_question = $_SESSION['login_attempt']['security_question'] ?? '';
                    }
                }
            }
        } catch(PDOException $e) {
            $error = "Login error: " . $e->getMessage();
            unset($_SESSION['login_attempt']);
        }
    }
}

// Process initial login form (username/password)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['security_answer'])) {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? '';
    
    if (empty($username) || empty($password) || empty($role)) {
        $error = "Please fill in all fields";
    } else {
        // Check login based on role
        try {
            $user = null;
            $user_id = null;
            $user_name = null;
            $table_name = '';
            $id_field = '';
            $security_question_field = '';
            
            if ($role === 'admin') {
                $stmt = $conn->prepare("SELECT AdminId, FirstName, LastName, Password FROM ADMIN WHERE Username = ?");
                $stmt->execute([$username]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($user) {
                    $user_id = $user['AdminId'];
                    $user_name = $user['FirstName'] . ' ' . $user['LastName'];
                }
            } elseif ($role === 'advocate') {
                $stmt = $conn->prepare("SELECT AdvtId, FirstName, LastName, Password, IsLocked, SecurityQuestion FROM ADVOCATE WHERE Username = ? AND Status = 'Active'");
                $stmt->execute([$username]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($user) {
                    $user_id = $user['AdvtId'];
                    $user_name = $user['FirstName'] . ' ' . $user['LastName'];
                    $table_name = 'ADVOCATE';
                    $id_field = 'AdvtId';
                    $security_question_field = $user['SecurityQuestion'] ?? '';
                }
            } elseif ($role === 'receptionist') {
                $stmt = $conn->prepare("SELECT RecId, FirstName, LastName, Password, IsLocked, SecurityQuestion FROM RECEPTIONIST WHERE Username = ?");
                $stmt->execute([$username]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($user) {
                    $user_id = $user['RecId'];
                    $user_name = $user['FirstName'] . ' ' . $user['LastName'];
                    $table_name = 'RECEPTIONIST';
                    $id_field = 'RecId';
                    $security_question_field = $user['SecurityQuestion'] ?? '';
                }
            } elseif ($role === 'client') {
                $stmt = $conn->prepare("SELECT ca.*, c.ClientId, c.FirstName, c.LastName, c.Email 
                                        FROM CLIENT_AUTH ca 
                                        JOIN CLIENT c ON ca.ClientId = c.ClientId 
                                        WHERE ca.Username = ? AND ca.IsActive = TRUE");
                $stmt->execute([$username]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($user) {
                    $user_id = $user['AuthId']; // Use AuthId for CLIENT_AUTH table
                    $user_name = $user['FirstName'] . ' ' . $user['LastName'];
                    $table_name = 'CLIENT_AUTH';
                    $id_field = 'AuthId';
                    $security_question_field = $user['SecurityQuestion'] ?? '';
                }
            }
            
            // Verify password
            if ($user) {
                if (password_verify($password, $user['Password'])) {
                    // Check if account is locked (for non-admin users)
                    if ($role !== 'admin' && isset($user['IsLocked']) && $user['IsLocked']) {
                        $error = "Your account is locked. Please contact the administrator to unlock it.";
                    } elseif ($role === 'admin') {
                        // Admin doesn't need security question - login directly
                        $_SESSION['user_id'] = $user_id;
                        $_SESSION['user_role'] = $role;
                        $_SESSION['user_name'] = $user_name;
                        $_SESSION['username'] = $username;
                        header("Location: admin/dashboard.php");
                        exit();
                    } elseif (empty($security_question_field)) {
                        // No security question set - allow login but warn user
                        if ($role === 'client') {
                            $_SESSION['client_id'] = $user_id;
                            $_SESSION['client_name'] = $user_name;
                            $_SESSION['client_email'] = $user['Email'] ?? '';
                            header("Location: client/dashboard.php");
                        } else {
                            $_SESSION['user_id'] = $user_id;
                            $_SESSION['user_role'] = $role;
                            $_SESSION['user_name'] = $user_name;
                            $_SESSION['username'] = $username;
                            
                            if ($role === 'advocate') {
                                header("Location: advocate/dashboard.php");
                            } elseif ($role === 'receptionist') {
                                header("Location: receptionist/dashboard.php");
                            }
                        }
                        exit();
                    } else {
                        // Password correct, show security question
                        $_SESSION['login_attempt'] = [
                            'username' => $username,
                            'role' => $role,
                            'user_id' => $user_id,
                            'user_name' => $user_name,
                            'table_name' => $table_name,
                            'id_field' => $id_field,
                            'security_question' => $security_question_field,
                            'email' => $user['Email'] ?? '',
                            'client_id' => ($role === 'client' ? $user['ClientId'] : null)
                        ];
                        $show_security_question = true;
                        $security_question = $security_question_field;
                    }
                } else {
                    if ($role === 'client') {
                        $error = "Incorrect password. Please contact the administrator if you forgot your password.";
                    } else {
                        $error = "Incorrect password. Please try again or run setup_users.php to reset passwords.";
                    }
                }
            } else {
                if ($role === 'client') {
                    $error = "Client account not found. Please contact the administrator to create your portal account.";
                } else {
                    $error = "User not found. Please check your username and role, or run setup_users.php to create users.";
                }
            }
        } catch(PDOException $e) {
            $error = "Login error: " . $e->getMessage();
        }
    }
}

// Check if we should show security question from session
if (isset($_SESSION['login_attempt']) && !$show_security_question) {
    $show_security_question = true;
    $security_question = $_SESSION['login_attempt']['security_question'] ?? '';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In - Law Firm Management System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
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
        
        /* Select dropdown styling */
        .login-form select {
            color: rgba(255, 255, 255, 0.95) !important;
            cursor: pointer;
        }
        
        .login-form select option {
            background: #1e293b;
            color: #ffffff;
            padding: 10px;
        }
        
        /* Improve select visibility */
        .login-form .input-wrapper select {
            appearance: none;
            -webkit-appearance: none;
            -moz-appearance: none;
            background-repeat: no-repeat !important;
            background-position: right 16px center !important;
            background-size: 12px !important;
        }
        
        .login-form .input-wrapper select::-ms-expand {
            display: none;
        }
        
        /* Make select text more visible */
        .login-form select {
            color: rgba(255, 255, 255, 0.95) !important;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%23ffffff' d='M6 9L1 4h10z'/%3E%3C/svg%3E") !important;
            background-repeat: no-repeat !important;
            background-position: right 16px center !important;
            background-size: 12px !important;
        }
        
        .login-form select:focus {
            color: rgba(255, 255, 255, 1) !important;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%23ffffff' d='M6 9L1 4h10z'/%3E%3C/svg%3E") !important;
            background-repeat: no-repeat !important;
        }
        
        .login-form select option {
            background: rgba(30, 41, 59, 0.95) !important;
            color: #ffffff !important;
            padding: 12px !important;
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
                    <i class="fas fa-gavel"></i>
                </div>
                <h1>Sign In</h1>
                <h2>Law Firm Management System</h2>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($show_security_question): ?>
                <form method="POST" action="" class="login-form">
                    <div class="form-group">
                        <label style="color: #ffffff; font-weight: 600; margin-bottom: 10px; display: block;">
                            <i class="fas fa-question-circle"></i> Security Question
                        </label>
                        <div style="background: rgba(255, 255, 255, 0.2); padding: 15px; border-radius: 8px; margin-bottom: 20px; color: #ffffff; font-size: 14px;">
                            <?php echo htmlspecialchars($security_question); ?>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="security_answer">
                            <i class="fas fa-key"></i> Your Answer
                        </label>
                        <div class="input-wrapper">
                            <input type="text" id="security_answer" name="security_answer" required autofocus placeholder="Enter your security answer">
                            <i class="fas fa-key input-icon"></i>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary btn-login">
                        <span>Verify Answer</span>
                        <i class="fas fa-arrow-right"></i>
                    </button>
                    
                    <div style="margin-top: 15px; text-align: center;">
                        <a href="login.php" class="btn btn-secondary btn-sm" style="width: 100%;">
                            <i class="fas fa-arrow-left"></i> Back to Login
                        </a>
                    </div>
                </form>
            <?php else: ?>
                <form method="POST" action="" class="login-form">
                    <div class="form-group">
                        <label for="role">
                            <i class="fas fa-user-tag"></i> Select Role
                        </label>
                        <div class="input-wrapper">
                            <select name="role" id="role" required>
                                <option value="">-- Select Role --</option>
                                <option value="admin">Administrator</option>
                                <option value="advocate">Advocate</option>
                                <option value="receptionist">Receptionist</option>
                                <option value="client">Client</option>
                            </select>
                            <i class="fas fa-chevron-down input-icon"></i>
                        </div>
                    </div>
                    
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
            <?php endif; ?>
            
            <div style="margin-top: 32px; text-align: center;">
                <a href="index.php" class="btn btn-secondary" style="width: 100%;">
                    <i class="fas fa-arrow-left"></i> Back to Home
                </a>
            </div>
        </div>
    </div>
    
    <!-- Footer -->
    <footer style="background: rgba(15, 23, 42, 0.9); color: #ffffff; padding: 25px 20px; position: relative; z-index: 1; width: 100%; box-sizing: border-box; margin-top: 60px;">
        <div style="max-width: 1200px; margin: 0 auto; text-align: center !important; width: 100%;">
            <p style="color: #cbd5e1; font-size: 14px; margin-bottom: 10px; text-align: center !important; width: 100%;">
                <a href="database/check_users.php" style="color: #a78bfa; text-decoration: none; transition: color 0.3s; display: inline-block; text-align: center;"><i class="fas fa-question-circle"></i> Having login issues? Check Users</a>
            </p>
            <p style="color: #94a3b8; font-size: 12px; text-align: center !important; margin: 0; width: 100%;">&copy; <?php echo date('Y'); ?> Law Firm Management System. All rights reserved.</p>
        </div>
    </footer>
</body>
</html>

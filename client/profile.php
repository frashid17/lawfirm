<?php
/**
 * CLIENT PROFILE
 * Client can change password and set security question
 */

session_start();
require_once '../config/database.php';

if (!isset($_SESSION['client_id'])) {
    header("Location: login.php");
    exit();
}

$client_id = $_SESSION['client_id'];
$message = "";
$error = "";

// Predefined security questions
$security_questions = [
    "What was the name of your first pet?",
    "What city were you born in?",
    "What was your mother's maiden name?",
    "What was the name of your elementary school?",
    "What was your childhood nickname?",
    "What street did you grow up on?",
    "What was the make of your first car?",
    "What is your favorite movie?",
    "What was the name of your first teacher?",
    "What is your favorite food?",
    "What was your favorite sport in high school?",
    "What is the name of your best friend from childhood?",
    "What was your favorite book as a child?",
    "What is the name of the hospital where you were born?",
    "What was your favorite vacation destination?"
];

// Password validation function
function validatePassword($password) {
    $errors = [];
    if (strlen($password) < 8) {
        $errors[] = "Password must be at least 8 characters long";
    }
    if (!preg_match('/[A-Z]/', $password)) {
        $errors[] = "Password must contain at least one uppercase letter";
    }
    if (!preg_match('/[a-z]/', $password)) {
        $errors[] = "Password must contain at least one lowercase letter";
    }
    if (!preg_match('/[0-9]/', $password)) {
        $errors[] = "Password must contain at least one number";
    }
    if (!preg_match('/[^A-Za-z0-9]/', $password)) {
        $errors[] = "Password must contain at least one special character";
    }
    return $errors;
}

// Get client data
try {
    $stmt = $conn->prepare("SELECT c.*, ca.AuthId, ca.Username, ca.SecurityQuestion, ca.SecurityAnswer 
                            FROM CLIENT c 
                            JOIN CLIENT_AUTH ca ON c.ClientId = ca.ClientId 
                            WHERE c.ClientId = ?");
    $stmt->execute([$client_id]);
    $client = $stmt->fetch(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $error = "Error loading profile: " . $e->getMessage();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'change_password') {
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
            $error = "Please fill in all password fields";
        } elseif ($new_password !== $confirm_password) {
            $error = "New passwords do not match";
        } else {
            // Validate password strength
            $password_errors = validatePassword($new_password);
            if (!empty($password_errors)) {
                $error = implode(". ", $password_errors);
            } else {
                try {
                    // Get current password
                    $stmt = $conn->prepare("SELECT Password FROM CLIENT_AUTH WHERE ClientId = ?");
                    $stmt->execute([$client_id]);
                    $auth_data = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($auth_data && password_verify($current_password, $auth_data['Password'])) {
                        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                        $stmt = $conn->prepare("UPDATE CLIENT_AUTH SET Password = ? WHERE ClientId = ?");
                        $stmt->execute([$hashed_password, $client_id]);
                        $message = "Password changed successfully";
                    } else {
                        $error = "Current password is incorrect";
                    }
                } catch(PDOException $e) {
                    $error = "Error changing password: " . $e->getMessage();
                }
            }
        }
    } elseif ($action === 'set_security_question') {
        $security_question = trim($_POST['security_question'] ?? '');
        $security_answer = trim($_POST['security_answer'] ?? '');
        
        if (empty($security_question)) {
            $error = "Please enter a security question";
        } elseif (empty($security_answer) && empty($client['SecurityAnswer'])) {
            $error = "Please enter a security answer";
        } else {
            try {
                if (!empty($security_answer)) {
                    $stmt = $conn->prepare("UPDATE CLIENT_AUTH SET SecurityQuestion = ?, SecurityAnswer = ? WHERE ClientId = ?");
                    $stmt->execute([$security_question, strtolower(trim($security_answer)), $client_id]);
                } else {
                    $stmt = $conn->prepare("UPDATE CLIENT_AUTH SET SecurityQuestion = ? WHERE ClientId = ?");
                    $stmt->execute([$security_question, $client_id]);
                }
                $message = "Security question updated successfully";
                // Reload client data
                $stmt = $conn->prepare("SELECT c.*, ca.AuthId, ca.Username, ca.SecurityQuestion, ca.SecurityAnswer 
                                        FROM CLIENT c 
                                        JOIN CLIENT_AUTH ca ON c.ClientId = ca.ClientId 
                                        WHERE c.ClientId = ?");
                $stmt->execute([$client_id]);
                $client = $stmt->fetch(PDO::FETCH_ASSOC);
            } catch(PDOException $e) {
                $error = "Error setting security question: " . $e->getMessage();
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
    <title>My Profile - Client Portal</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="header">
        <div class="header-content">
            <h1>My Profile - Client Portal</h1>
            <div class="header-user">
                <a href="dashboard.php" class="btn btn-secondary btn-sm"><i class="fas fa-arrow-left"></i> Back</a>
                <a href="logout.php" class="btn btn-secondary btn-sm"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>
    </div>
    
    <div class="nav">
        <div class="nav-content">
            <ul class="nav-menu">
                <li><a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a></li>
                <li><a href="my_cases.php"><i class="fas fa-folder-open"></i> My Cases</a></li>
                <li><a href="invoices.php"><i class="fas fa-file-invoice-dollar"></i> Invoices</a></li>
                <li><a href="payment_history.php"><i class="fas fa-history"></i> Payment History</a></li>
                <li><a href="documents.php"><i class="fas fa-file"></i> Documents</a></li>
                <li><a href="messages.php"><i class="fas fa-comments"></i> Messages</a></li>
                <li><a href="events.php"><i class="fas fa-calendar-alt"></i> Events</a></li>
                <li><a href="profile.php" class="active"><i class="fas fa-user"></i> Profile</a></li>
            </ul>
        </div>
    </div>
    
    <div class="container">
        <h2>My Profile</h2>

        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <div class="form-container">
            <h3>Profile Information</h3>
            <div class="form-group">
                <label>Name</label>
                <input type="text" value="<?php echo htmlspecialchars($client['FirstName'] . ' ' . $client['LastName']); ?>" disabled>
            </div>
            
            <div class="form-group">
                <label>Email</label>
                <input type="email" value="<?php echo htmlspecialchars($client['Email'] ?? ''); ?>" disabled>
            </div>
            
            <div class="form-group">
                <label>Phone</label>
                <input type="text" value="<?php echo htmlspecialchars($client['PhoneNo'] ?? ''); ?>" disabled>
            </div>
            
            <div class="form-group">
                <label>Username</label>
                <input type="text" value="<?php echo htmlspecialchars($client['Username'] ?? ''); ?>" disabled>
                <small style="color: var(--gray); font-size: 12px;">Username cannot be changed</small>
            </div>
        </div>

        <div class="form-container">
            <h3>Change Password</h3>
            <form method="POST" action="">
                <input type="hidden" name="action" value="change_password">
                
                <div class="form-group">
                    <label for="current_password">Current Password *</label>
                    <input type="password" id="current_password" name="current_password" required>
                </div>
                
                <div class="form-group">
                    <label for="new_password">New Password *</label>
                    <input type="password" id="new_password" name="new_password" required minlength="8">
                    <small style="color: var(--gray); font-size: 12px; display: block; margin-top: 5px;">
                        Password must be at least 8 characters and contain: uppercase, lowercase, number, and special character
                    </small>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Confirm New Password *</label>
                    <input type="password" id="confirm_password" name="confirm_password" required minlength="8">
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Change Password</button>
                </div>
            </form>
        </div>

        <div class="form-container">
            <h3>Security Question</h3>
            <p style="color: var(--gray); margin-bottom: 20px; font-size: 14px;">
                Set a security question and answer. You will be asked this question when logging in.
            </p>
            <form method="POST" action="">
                <input type="hidden" name="action" value="set_security_question">
                
                <div class="form-group">
                    <label for="security_question">Security Question *</label>
                    <select id="security_question" name="security_question" required>
                        <option value="">-- Select a Security Question --</option>
                        <?php foreach ($security_questions as $question): ?>
                            <option value="<?php echo htmlspecialchars($question); ?>" 
                                    <?php echo (isset($client['SecurityQuestion']) && $client['SecurityQuestion'] === $question) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($question); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="security_answer">Security Answer <?php echo empty($client['SecurityAnswer']) ? '*' : ''; ?></label>
                    <input type="text" id="security_answer" name="security_answer" 
                           value="" 
                           <?php echo empty($client['SecurityAnswer']) ? 'required' : ''; ?> 
                           placeholder="Enter your answer">
                    <?php if (!empty($client['SecurityAnswer'])): ?>
                        <small style="color: var(--gray); font-size: 12px; display: block; margin-top: 5px;">
                            Leave blank to keep current answer, or enter new answer to update
                        </small>
                    <?php endif; ?>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Save Security Question</button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>

<?php
/**
 * ADVOCATE PROFILE
 * Advocate can change password, name, email, address, and delete account
 */

require_once '../config/database.php';
require_once '../config/session.php';
requireRole('advocate');

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

// Create uploads directory if it doesn't exist
$upload_dir = '../uploads/profiles/';
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

// Get current advocate data
$advocate_id = $_SESSION['user_id'];
try {
    $stmt = $conn->prepare("SELECT * FROM ADVOCATE WHERE AdvtId = ?");
    $stmt->execute([$advocate_id]);
    $advocate = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Get profile picture
    $stmt = $conn->prepare("SELECT ProfilePicture FROM USER_PROFILE WHERE UserId = ? AND UserRole = 'advocate'");
    $stmt->execute([$advocate_id]);
    $profile_data = $stmt->fetch(PDO::FETCH_ASSOC);
    $profile_picture = $profile_data ? $profile_data['ProfilePicture'] : null;
} catch(PDOException $e) {
    $error = "Error loading profile: " . $e->getMessage();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'upload_picture') {
        if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['profile_picture'];
            $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
            
            if (!in_array($file_ext, $allowed_extensions)) {
                $error = "Invalid file type. Allowed: JPG, PNG, GIF";
            } elseif ($file['size'] > 2097152) {
                $error = "File size exceeds 2MB limit";
            } else {
                $unique_name = 'advocate_' . $advocate_id . '_' . time() . '.' . $file_ext;
                $file_path = $upload_dir . $unique_name;
                
                if (move_uploaded_file($file['tmp_name'], $file_path)) {
                    try {
                        if ($profile_picture && file_exists($profile_picture)) {
                            unlink($profile_picture);
                        }
                        $stmt = $conn->prepare("INSERT INTO USER_PROFILE (UserId, UserRole, ProfilePicture) VALUES (?, 'advocate', ?) 
                                                ON DUPLICATE KEY UPDATE ProfilePicture = ?");
                        $stmt->execute([$advocate_id, $file_path, $file_path]);
                        $message = "Profile picture updated successfully";
                        $profile_picture = $file_path;
                    } catch(PDOException $e) {
                        unlink($file_path);
                        $error = "Error updating profile picture: " . $e->getMessage();
                    }
                } else {
                    $error = "Error uploading file";
                }
            }
        } else {
            $error = "Please select a valid image file";
        }
    } elseif ($action === 'update_profile') {
        $firstname = trim($_POST['firstname'] ?? '');
        $lastname = trim($_POST['lastname'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $address = trim($_POST['address'] ?? '');
        
        if (empty($firstname) || empty($lastname) || empty($phone) || empty($email)) {
            $error = "Please fill in all required fields";
        } else {
            try {
                $stmt = $conn->prepare("UPDATE ADVOCATE SET FirstName = ?, LastName = ?, PhoneNo = ?, Email = ?, Address = ? WHERE AdvtId = ?");
                $stmt->execute([$firstname, $lastname, $phone, $email, $address, $advocate_id]);
                $message = "Profile updated successfully";
                // Update session
                $_SESSION['user_name'] = $firstname . ' ' . $lastname;
                // Reload advocate data
                $stmt = $conn->prepare("SELECT * FROM ADVOCATE WHERE AdvtId = ?");
                $stmt->execute([$advocate_id]);
                $advocate = $stmt->fetch(PDO::FETCH_ASSOC);
            } catch(PDOException $e) {
                $error = "Error updating profile: " . $e->getMessage();
            }
        }
    } elseif ($action === 'change_password') {
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
                    // Verify current password
                    if (password_verify($current_password, $advocate['Password'])) {
                        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                        $stmt = $conn->prepare("UPDATE ADVOCATE SET Password = ? WHERE AdvtId = ?");
                        $stmt->execute([$hashed_password, $advocate_id]);
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
        } elseif (empty($security_answer) && empty($advocate['SecurityAnswer'])) {
            $error = "Please enter a security answer";
        } else {
            try {
                if (!empty($security_answer)) {
                    // Update both question and answer
                    $stmt = $conn->prepare("UPDATE ADVOCATE SET SecurityQuestion = ?, SecurityAnswer = ? WHERE AdvtId = ?");
                    $stmt->execute([$security_question, strtolower(trim($security_answer)), $advocate_id]);
                } else {
                    // Only update question, keep existing answer
                    $stmt = $conn->prepare("UPDATE ADVOCATE SET SecurityQuestion = ? WHERE AdvtId = ?");
                    $stmt->execute([$security_question, $advocate_id]);
                }
                $message = "Security question updated successfully";
                // Reload advocate data
                $stmt = $conn->prepare("SELECT * FROM ADVOCATE WHERE AdvtId = ?");
                $stmt->execute([$advocate_id]);
                $advocate = $stmt->fetch(PDO::FETCH_ASSOC);
            } catch(PDOException $e) {
                $error = "Error setting security question: " . $e->getMessage();
            }
        }
    } elseif ($action === 'delete_account') {
        $confirm_delete = $_POST['confirm_delete'] ?? '';
        if ($confirm_delete === 'DELETE') {
            try {
                $stmt = $conn->prepare("DELETE FROM ADVOCATE WHERE AdvtId = ?");
                $stmt->execute([$advocate_id]);
                session_destroy();
                header("Location: ../login.php?deleted=1");
                exit();
            } catch(PDOException $e) {
                $error = "Error deleting account: " . $e->getMessage();
            }
        } else {
            $error = "Please type DELETE to confirm account deletion";
        }
    }
}

include 'header.php';
?>

<h2>My Profile</h2>

<?php if ($message): ?>
    <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>

<div class="form-container">
    <h3>Profile Picture</h3>
    <div style="text-align: center; margin-bottom: 30px;">
        <?php if ($profile_picture && file_exists($profile_picture)): ?>
            <img src="<?php echo htmlspecialchars($profile_picture); ?>" 
                 alt="Profile Picture" 
                 style="width: 150px; height: 150px; border-radius: 50%; object-fit: cover; border: 4px solid var(--primary-color);">
        <?php else: ?>
            <div style="width: 150px; height: 150px; border-radius: 50%; background: var(--primary-color); margin: 0 auto; display: flex; align-items: center; justify-content: center; color: white; font-size: 48px;">
                <i class="fas fa-user"></i>
            </div>
        <?php endif; ?>
    </div>
    <form method="POST" action="" enctype="multipart/form-data">
        <input type="hidden" name="action" value="upload_picture">
        <div class="form-group">
            <label for="profile_picture">Upload Profile Picture (Max 2MB, JPG/PNG/GIF)</label>
            <input type="file" id="profile_picture" name="profile_picture" accept="image/jpeg,image/png,image/gif">
        </div>
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Upload Picture</button>
        </div>
    </form>
</div>

<div class="form-container">
    <h3>Update Profile Information</h3>
    <form method="POST" action="">
        <input type="hidden" name="action" value="update_profile">
        
        <div class="form-group">
            <label for="firstname">First Name *</label>
            <input type="text" id="firstname" name="firstname" 
                   value="<?php echo htmlspecialchars($advocate['FirstName'] ?? ''); ?>" required>
        </div>
        
        <div class="form-group">
            <label for="lastname">Last Name *</label>
            <input type="text" id="lastname" name="lastname" 
                   value="<?php echo htmlspecialchars($advocate['LastName'] ?? ''); ?>" required>
        </div>
        
        <div class="form-group">
            <label for="phone">Phone Number *</label>
            <input type="text" id="phone" name="phone" 
                   value="<?php echo htmlspecialchars($advocate['PhoneNo'] ?? ''); ?>" required>
        </div>
        
        <div class="form-group">
            <label for="email">Email *</label>
            <input type="email" id="email" name="email" 
                   value="<?php echo htmlspecialchars($advocate['Email'] ?? ''); ?>" required>
        </div>
        
        <div class="form-group">
            <label for="address">Address</label>
            <input type="text" id="address" name="address" 
                   value="<?php echo htmlspecialchars($advocate['Address'] ?? ''); ?>">
        </div>
        
        <div class="form-group">
            <label>Username</label>
            <input type="text" value="<?php echo htmlspecialchars($advocate['Username'] ?? ''); ?>" disabled>
            <small style="color: var(--gray); font-size: 12px;">Username cannot be changed</small>
        </div>
        
        <div class="form-group">
            <label>Status</label>
            <input type="text" value="<?php echo htmlspecialchars($advocate['Status'] ?? ''); ?>" disabled>
            <small style="color: var(--gray); font-size: 12px;">Status can only be changed by admin</small>
        </div>
        
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Update Profile</button>
        </div>
    </form>
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
                            <?php echo (isset($advocate['SecurityQuestion']) && $advocate['SecurityQuestion'] === $question) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($question); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="form-group">
            <label for="security_answer">Security Answer <?php echo empty($advocate['SecurityAnswer']) ? '*' : ''; ?></label>
            <input type="text" id="security_answer" name="security_answer" 
                   value="" 
                   <?php echo empty($advocate['SecurityAnswer']) ? 'required' : ''; ?> 
                   placeholder="Enter your answer">
            <?php if (!empty($advocate['SecurityAnswer'])): ?>
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

<div class="form-container" style="border: 2px solid var(--danger-color);">
    <h3 style="color: var(--danger-color);">Delete Account</h3>
    <p style="color: var(--gray); margin-bottom: 20px;">Warning: This action cannot be undone. All your data will be permanently deleted.</p>
    <form method="POST" action="" onsubmit="return confirm('Are you absolutely sure you want to delete your account? This cannot be undone!');">
        <input type="hidden" name="action" value="delete_account">
        
        <div class="form-group">
            <label for="confirm_delete">Type DELETE to confirm *</label>
            <input type="text" id="confirm_delete" name="confirm_delete" required 
                   placeholder="Type DELETE here" style="border-color: var(--danger-color);">
        </div>
        
        <div class="form-actions">
            <button type="submit" class="btn btn-danger">Delete My Account</button>
        </div>
    </form>
</div>

<?php include 'footer.php'; ?>

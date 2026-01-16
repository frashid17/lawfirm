<?php
/**
 * ADMIN PROFILE
 * Admin can change password, name, email, and delete account
 */

require_once '../config/database.php';
require_once '../config/session.php';
requireRole('admin');

$message = "";
$error = "";

// Create uploads directory if it doesn't exist
$upload_dir = '../uploads/profiles/';
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

// Get current admin data
$admin_id = $_SESSION['user_id'];
try {
    $stmt = $conn->prepare("SELECT * FROM ADMIN WHERE AdminId = ?");
    $stmt->execute([$admin_id]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Get profile picture
    $stmt = $conn->prepare("SELECT ProfilePicture FROM USER_PROFILE WHERE UserId = ? AND UserRole = 'admin'");
    $stmt->execute([$admin_id]);
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
            } elseif ($file['size'] > 2097152) { // 2MB
                $error = "File size exceeds 2MB limit";
            } else {
                $unique_name = 'admin_' . $admin_id . '_' . time() . '.' . $file_ext;
                $file_path = $upload_dir . $unique_name;
                
                if (move_uploaded_file($file['tmp_name'], $file_path)) {
                    try {
                        // Delete old picture
                        if ($profile_picture && file_exists($profile_picture)) {
                            unlink($profile_picture);
                        }
                        
                        // Update or insert profile
                        $stmt = $conn->prepare("INSERT INTO USER_PROFILE (UserId, UserRole, ProfilePicture) VALUES (?, 'admin', ?) 
                                                ON DUPLICATE KEY UPDATE ProfilePicture = ?");
                        $stmt->execute([$admin_id, $file_path, $file_path]);
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
        
        if (empty($firstname) || empty($lastname) || empty($phone) || empty($email)) {
            $error = "Please fill in all required fields";
        } else {
            try {
                $stmt = $conn->prepare("UPDATE ADMIN SET FirstName = ?, LastName = ?, PhoneNo = ?, Email = ? WHERE AdminId = ?");
                $stmt->execute([$firstname, $lastname, $phone, $email, $admin_id]);
                $message = "Profile updated successfully";
                // Update session
                $_SESSION['user_name'] = $firstname . ' ' . $lastname;
                // Reload admin data
                $stmt = $conn->prepare("SELECT * FROM ADMIN WHERE AdminId = ?");
                $stmt->execute([$admin_id]);
                $admin = $stmt->fetch(PDO::FETCH_ASSOC);
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
            try {
                // Verify current password
                if (password_verify($current_password, $admin['Password'])) {
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                    $stmt = $conn->prepare("UPDATE ADMIN SET Password = ? WHERE AdminId = ?");
                    $stmt->execute([$hashed_password, $admin_id]);
                    $message = "Password changed successfully";
                } else {
                    $error = "Current password is incorrect";
                }
            } catch(PDOException $e) {
                $error = "Error changing password: " . $e->getMessage();
            }
        }
    } elseif ($action === 'delete_account') {
        $confirm_delete = $_POST['confirm_delete'] ?? '';
        if ($confirm_delete === 'DELETE') {
            try {
                $stmt = $conn->prepare("DELETE FROM ADMIN WHERE AdminId = ?");
                $stmt->execute([$admin_id]);
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
                   value="<?php echo htmlspecialchars($admin['FirstName'] ?? ''); ?>" required>
        </div>
        
        <div class="form-group">
            <label for="lastname">Last Name *</label>
            <input type="text" id="lastname" name="lastname" 
                   value="<?php echo htmlspecialchars($admin['LastName'] ?? ''); ?>" required>
        </div>
        
        <div class="form-group">
            <label for="phone">Phone Number *</label>
            <input type="text" id="phone" name="phone" 
                   value="<?php echo htmlspecialchars($admin['PhoneNo'] ?? ''); ?>" required>
        </div>
        
        <div class="form-group">
            <label for="email">Email *</label>
            <input type="email" id="email" name="email" 
                   value="<?php echo htmlspecialchars($admin['Email'] ?? ''); ?>" required>
        </div>
        
        <div class="form-group">
            <label>Username</label>
            <input type="text" value="<?php echo htmlspecialchars($admin['Username'] ?? ''); ?>" disabled>
            <small style="color: var(--gray); font-size: 12px;">Username cannot be changed</small>
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
            <input type="password" id="new_password" name="new_password" required minlength="6">
        </div>
        
        <div class="form-group">
            <label for="confirm_password">Confirm New Password *</label>
            <input type="password" id="confirm_password" name="confirm_password" required minlength="6">
        </div>
        
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Change Password</button>
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

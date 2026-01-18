<?php
/**
 * ADMIN - MANAGE USERS (ADMIN & RECEPTIONIST)
 * View, add, edit, and delete system users
 */

require_once '../config/database.php';
require_once '../config/session.php';
requireRole('admin');

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

// Handle delete
if (isset($_GET['delete']) && isset($_GET['type'])) {
    $type = $_GET['type'];
    $id = $_GET['delete'];
    
    try {
        if ($type === 'admin') {
            // Don't allow deleting the current admin
            if ($id == $_SESSION['user_id']) {
                $error = "You cannot delete your own account";
            } else {
                $stmt = $conn->prepare("DELETE FROM ADMIN WHERE AdminId = ?");
                $stmt->execute([$id]);
                $message = "Admin deleted successfully";
            }
        } elseif ($type === 'receptionist') {
            $stmt = $conn->prepare("DELETE FROM RECEPTIONIST WHERE RecId = ?");
            $stmt->execute([$id]);
            $message = "Receptionist deleted successfully";
        } elseif ($type === 'advocate') {
            $stmt = $conn->prepare("DELETE FROM ADVOCATE WHERE AdvtId = ?");
            $stmt->execute([$id]);
            $message = "Advocate deleted successfully";
        }
    } catch(PDOException $e) {
        $error = "Error deleting user: " . $e->getMessage();
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_type = $_POST['user_type'] ?? '';
    $user_id = $_POST['user_id'] ?? null;
    $firstname = trim($_POST['firstname'] ?? '');
    $lastname = trim($_POST['lastname'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $security_question = trim($_POST['security_question'] ?? '');
    $security_answer = trim($_POST['security_answer'] ?? '');
    
    if (empty($firstname) || empty($lastname) || empty($phone) || empty($email) || empty($username)) {
        $error = "Please fill in all required fields";
    } elseif (!$user_id && $user_type !== 'admin' && (empty($security_question) || empty($security_answer))) {
        // Security question required for new users (not admins)
        $error = "Please fill in security question and answer";
    } else {
        // Validate password if provided
        if (!empty($password)) {
            $password_errors = validatePassword($password);
            if (!empty($password_errors)) {
                $error = implode(". ", $password_errors);
            }
        }
    }
    
    if (empty($error)) {
        try {
            if ($user_type === 'admin') {
                if ($user_id) {
                    // Update existing admin
                    if (!empty($password)) {
                        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                        $stmt = $conn->prepare("UPDATE ADMIN SET FirstName = ?, LastName = ?, PhoneNo = ?, Email = ?, Username = ?, Password = ? WHERE AdminId = ?");
                        $stmt->execute([$firstname, $lastname, $phone, $email, $username, $hashed_password, $user_id]);
                    } else {
                        $stmt = $conn->prepare("UPDATE ADMIN SET FirstName = ?, LastName = ?, PhoneNo = ?, Email = ?, Username = ? WHERE AdminId = ?");
                        $stmt->execute([$firstname, $lastname, $phone, $email, $username, $user_id]);
                    }
                    $message = "Admin updated successfully";
                } else {
                    // Insert new admin
                    if (empty($password)) {
                        $error = "Password is required for new admin";
                    } else {
                        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                        $stmt = $conn->prepare("INSERT INTO ADMIN (FirstName, LastName, PhoneNo, Email, Username, Password) VALUES (?, ?, ?, ?, ?, ?)");
                        $stmt->execute([$firstname, $lastname, $phone, $email, $username, $hashed_password]);
                        $message = "Admin added successfully";
                    }
                }
            } elseif ($user_type === 'receptionist') {
                if ($user_id) {
                    // Update existing receptionist
                    if (!empty($password)) {
                        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                        $stmt = $conn->prepare("UPDATE RECEPTIONIST SET FirstName = ?, LastName = ?, PhoneNo = ?, Email = ?, Username = ?, Password = ? WHERE RecId = ?");
                        $stmt->execute([$firstname, $lastname, $phone, $email, $username, $hashed_password, $user_id]);
                    } else {
                        $stmt = $conn->prepare("UPDATE RECEPTIONIST SET FirstName = ?, LastName = ?, PhoneNo = ?, Email = ?, Username = ? WHERE RecId = ?");
                        $stmt->execute([$firstname, $lastname, $phone, $email, $username, $user_id]);
                    }
                    // Update security question if provided
                    // If both question and answer are provided, update both
                    // If only question is provided but answer is empty, check if user already has a question
                    if (!empty($security_question)) {
                        if (!empty($security_answer)) {
                            // Both question and answer provided - update both
                            $stmt = $conn->prepare("UPDATE RECEPTIONIST SET SecurityQuestion = ?, SecurityAnswer = ? WHERE RecId = ?");
                            $stmt->execute([$security_question, strtolower(trim($security_answer)), $user_id]);
                        } else {
                            // Only question provided - check if user already has an answer
                            $stmt = $conn->prepare("SELECT SecurityAnswer FROM RECEPTIONIST WHERE RecId = ?");
                            $stmt->execute([$user_id]);
                            $existing = $stmt->fetch(PDO::FETCH_ASSOC);
                            if (!empty($existing['SecurityAnswer'])) {
                                // User has existing answer, update only question
                                $stmt = $conn->prepare("UPDATE RECEPTIONIST SET SecurityQuestion = ? WHERE RecId = ?");
                                $stmt->execute([$security_question, $user_id]);
                            } else {
                                // No existing answer - require answer
                                $error = "Security answer is required when setting a new security question.";
                            }
                        }
                    } elseif (!empty($security_answer)) {
                        // Only answer provided - update answer only if question exists
                        $stmt = $conn->prepare("SELECT SecurityQuestion FROM RECEPTIONIST WHERE RecId = ?");
                        $stmt->execute([$user_id]);
                        $existing = $stmt->fetch(PDO::FETCH_ASSOC);
                        if (!empty($existing['SecurityQuestion'])) {
                            $stmt = $conn->prepare("UPDATE RECEPTIONIST SET SecurityAnswer = ? WHERE RecId = ?");
                            $stmt->execute([strtolower(trim($security_answer)), $user_id]);
                        } else {
                            $error = "Security question must be selected before setting an answer.";
                        }
                    }
                    if (empty($error)) {
                        $message = "Receptionist updated successfully";
                    }
                } else {
                    // Insert new receptionist
                    if (empty($password)) {
                        $error = "Password is required for new receptionist";
                    } else {
                        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                        $stmt = $conn->prepare("INSERT INTO RECEPTIONIST (FirstName, LastName, PhoneNo, Email, Username, Password, SecurityQuestion, SecurityAnswer) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                        $stmt->execute([$firstname, $lastname, $phone, $email, $username, $hashed_password, $security_question, strtolower(trim($security_answer))]);
                        $message = "Receptionist added successfully";
                    }
                }
            } elseif ($user_type === 'advocate') {
                $address = trim($_POST['address'] ?? '');
                $status = $_POST['status'] ?? 'Active';
                
                if ($user_id) {
                    // Update existing advocate
                    if (!empty($password)) {
                        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                        $stmt = $conn->prepare("UPDATE ADVOCATE SET FirstName = ?, LastName = ?, PhoneNo = ?, Email = ?, Address = ?, Username = ?, Password = ?, Status = ? WHERE AdvtId = ?");
                        $stmt->execute([$firstname, $lastname, $phone, $email, $address, $username, $hashed_password, $status, $user_id]);
                    } else {
                        $stmt = $conn->prepare("UPDATE ADVOCATE SET FirstName = ?, LastName = ?, PhoneNo = ?, Email = ?, Address = ?, Username = ?, Status = ? WHERE AdvtId = ?");
                        $stmt->execute([$firstname, $lastname, $phone, $email, $address, $username, $status, $user_id]);
                    }
                    // Update security question if provided
                    // If both question and answer are provided, update both
                    // If only question is provided but answer is empty, check if user already has a question
                    if (!empty($security_question)) {
                        if (!empty($security_answer)) {
                            // Both question and answer provided - update both
                            $stmt = $conn->prepare("UPDATE ADVOCATE SET SecurityQuestion = ?, SecurityAnswer = ? WHERE AdvtId = ?");
                            $stmt->execute([$security_question, strtolower(trim($security_answer)), $user_id]);
                        } else {
                            // Only question provided - check if user already has an answer
                            $stmt = $conn->prepare("SELECT SecurityAnswer FROM ADVOCATE WHERE AdvtId = ?");
                            $stmt->execute([$user_id]);
                            $existing = $stmt->fetch(PDO::FETCH_ASSOC);
                            if (!empty($existing['SecurityAnswer'])) {
                                // User has existing answer, update only question
                                $stmt = $conn->prepare("UPDATE ADVOCATE SET SecurityQuestion = ? WHERE AdvtId = ?");
                                $stmt->execute([$security_question, $user_id]);
                            } else {
                                // No existing answer - require answer
                                $error = "Security answer is required when setting a new security question.";
                            }
                        }
                    } elseif (!empty($security_answer)) {
                        // Only answer provided - update answer only if question exists
                        $stmt = $conn->prepare("SELECT SecurityQuestion FROM ADVOCATE WHERE AdvtId = ?");
                        $stmt->execute([$user_id]);
                        $existing = $stmt->fetch(PDO::FETCH_ASSOC);
                        if (!empty($existing['SecurityQuestion'])) {
                            $stmt = $conn->prepare("UPDATE ADVOCATE SET SecurityAnswer = ? WHERE AdvtId = ?");
                            $stmt->execute([strtolower(trim($security_answer)), $user_id]);
                        } else {
                            $error = "Security question must be selected before setting an answer.";
                        }
                    }
                    if (empty($error)) {
                        $message = "Advocate updated successfully";
                    }
                } else {
                    // Insert new advocate
                    if (empty($password)) {
                        $error = "Password is required for new advocate";
                    } else {
                        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                        $stmt = $conn->prepare("INSERT INTO ADVOCATE (FirstName, LastName, PhoneNo, Email, Address, Username, Password, Status, SecurityQuestion, SecurityAnswer) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                        $stmt->execute([$firstname, $lastname, $phone, $email, $address, $username, $hashed_password, $status, $security_question, strtolower(trim($security_answer))]);
                        $message = "Advocate added successfully";
                    }
                }
            }
        } catch(PDOException $e) {
            if ($e->getCode() == 23000) {
                $error = "Username or email already exists";
            } else {
                $error = "Error saving user: " . $e->getMessage();
            }
        }
    }
}

// Get user for editing
$edit_user = null;
$edit_type = null;
if (isset($_GET['edit']) && isset($_GET['type'])) {
    $edit_type = $_GET['type'];
    $id = $_GET['edit'];
    
    try {
        if ($edit_type === 'admin') {
            $stmt = $conn->prepare("SELECT * FROM ADMIN WHERE AdminId = ?");
            $stmt->execute([$id]);
            $edit_user = $stmt->fetch(PDO::FETCH_ASSOC);
        } elseif ($edit_type === 'receptionist') {
            $stmt = $conn->prepare("SELECT * FROM RECEPTIONIST WHERE RecId = ?");
            $stmt->execute([$id]);
            $edit_user = $stmt->fetch(PDO::FETCH_ASSOC);
        } elseif ($edit_type === 'advocate') {
            $stmt = $conn->prepare("SELECT * FROM ADVOCATE WHERE AdvtId = ?");
            $stmt->execute([$id]);
            $edit_user = $stmt->fetch(PDO::FETCH_ASSOC);
        }
        
        // Load security question for editing
        if ($edit_user && $edit_type !== 'admin') {
            // Security question is already in the user data
        }
    } catch(PDOException $e) {
        $error = "Error loading user: " . $e->getMessage();
    }
}

// Get all admins
try {
    $stmt = $conn->query("SELECT * FROM ADMIN ORDER BY LastName, FirstName");
    $admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $error = "Error loading admins: " . $e->getMessage();
}

// Get all receptionists
try {
    $stmt = $conn->query("SELECT * FROM RECEPTIONIST ORDER BY LastName, FirstName");
    $receptionists = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $error = "Error loading receptionists: " . $e->getMessage();
}

// Get all advocates
try {
    $stmt = $conn->query("SELECT * FROM ADVOCATE ORDER BY LastName, FirstName");
    $advocates = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $error = "Error loading advocates: " . $e->getMessage();
}

include 'header.php';
?>

<h2>Manage Users</h2>

<?php if ($message): ?>
    <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>

<div class="form-container">
    <h3><?php echo $edit_user ? 'Edit User' : 'Add New User'; ?></h3>
    <form method="POST" action="">
        <?php if ($edit_user): ?>
            <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($edit_user[$edit_type === 'admin' ? 'AdminId' : ($edit_type === 'advocate' ? 'AdvtId' : 'RecId')]); ?>">
            <input type="hidden" name="user_type" value="<?php echo htmlspecialchars($edit_type); ?>">
        <?php else: ?>
            <div class="form-group">
                <label for="user_type">User Type *</label>
                <select id="user_type" name="user_type" required>
                    <option value="">-- Select Type --</option>
                    <option value="admin">Administrator</option>
                    <option value="advocate">Advocate</option>
                    <option value="receptionist">Receptionist</option>
                </select>
            </div>
        <?php endif; ?>
        
        <div class="form-group">
            <label for="firstname">First Name *</label>
            <input type="text" id="firstname" name="firstname" 
                   value="<?php echo htmlspecialchars($edit_user['FirstName'] ?? ''); ?>" required>
        </div>
        
        <div class="form-group">
            <label for="lastname">Last Name *</label>
            <input type="text" id="lastname" name="lastname" 
                   value="<?php echo htmlspecialchars($edit_user['LastName'] ?? ''); ?>" required>
        </div>
        
        <div class="form-group">
            <label for="phone">Phone Number *</label>
            <input type="text" id="phone" name="phone" 
                   value="<?php echo htmlspecialchars($edit_user['PhoneNo'] ?? ''); ?>" required>
        </div>
        
        <div class="form-group">
            <label for="email">Email *</label>
            <input type="email" id="email" name="email" 
                   value="<?php echo htmlspecialchars($edit_user['Email'] ?? ''); ?>" required>
        </div>
        
        <div class="form-group">
            <label for="username">Username *</label>
            <input type="text" id="username" name="username" 
                   value="<?php echo htmlspecialchars($edit_user['Username'] ?? ''); ?>" required>
        </div>
        
        <?php if ($edit_user && $edit_type === 'advocate'): ?>
            <div class="form-group">
                <label for="address">Address</label>
                <input type="text" id="address" name="address" 
                       value="<?php echo htmlspecialchars($edit_user['Address'] ?? ''); ?>">
            </div>
            
            <div class="form-group">
                <label for="status">Status *</label>
                <select id="status" name="status" required>
                    <option value="Active" <?php echo ($edit_user['Status'] ?? 'Active') === 'Active' ? 'selected' : ''; ?>>Active</option>
                    <option value="Inactive" <?php echo ($edit_user['Status'] ?? '') === 'Inactive' ? 'selected' : ''; ?>>Inactive</option>
                </select>
            </div>
        <?php elseif (!$edit_user): ?>
            <div class="form-group" id="address-group" style="display: none;">
                <label for="address">Address</label>
                <input type="text" id="address" name="address">
            </div>
            
            <div class="form-group" id="status-group" style="display: none;">
                <label for="status">Status *</label>
                <select id="status" name="status">
                    <option value="Active" selected>Active</option>
                    <option value="Inactive">Inactive</option>
                </select>
            </div>
        <?php endif; ?>
        
        <div class="form-group">
            <label for="password">Password <?php echo $edit_user ? '(leave blank to keep current)' : '*'; ?></label>
            <input type="password" id="password" name="password" 
                   <?php echo $edit_user ? '' : 'required'; ?> minlength="8">
            <?php if (!$edit_user): ?>
                <small style="color: var(--gray); font-size: 12px; display: block; margin-top: 5px;">
                    Password must be at least 8 characters and contain: uppercase, lowercase, number, and special character
                </small>
            <?php endif; ?>
        </div>
        
        <?php if ($edit_user && ($edit_type === 'advocate' || $edit_type === 'receptionist')): ?>
            <div class="form-group">
                <label for="security_question">Security Question</label>
                <select id="security_question" name="security_question">
                    <option value="">-- <?php echo empty($edit_user['SecurityQuestion']) ? 'Select Security Question' : 'Keep Current or Select New'; ?> --</option>
                    <?php foreach ($security_questions as $question): ?>
                        <option value="<?php echo htmlspecialchars($question); ?>" 
                                <?php echo (isset($edit_user['SecurityQuestion']) && $edit_user['SecurityQuestion'] === $question) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($question); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if (empty($edit_user['SecurityQuestion'])): ?>
                    <small style="color: var(--gray); font-size: 12px; display: block; margin-top: 5px;">
                        This user does not have a security question set. Please select one and provide an answer.
                    </small>
                <?php else: ?>
                    <small style="color: var(--gray); font-size: 12px; display: block; margin-top: 5px;">
                        Current: <?php echo htmlspecialchars($edit_user['SecurityQuestion']); ?>. Leave blank to keep current, or select a new question and provide a new answer.
                    </small>
                <?php endif; ?>
            </div>
            
            <div class="form-group">
                <label for="security_answer">Security Answer</label>
                <input type="text" id="security_answer" name="security_answer" 
                       placeholder="<?php echo empty($edit_user['SecurityQuestion']) ? 'Enter security answer (required if setting new question)' : 'Enter new answer to update (leave blank to keep current)'; ?>">
                <?php if (empty($edit_user['SecurityQuestion'])): ?>
                    <small style="color: var(--gray); font-size: 12px; display: block; margin-top: 5px;">
                        Required when setting a new security question.
                    </small>
                <?php endif; ?>
            </div>
        <?php elseif (!$edit_user): ?>
            <div class="form-group" id="security-question-group" style="display: none;">
                <label for="security_question">Security Question *</label>
                <select id="security_question" name="security_question">
                    <option value="">-- Select a Security Question --</option>
                    <?php foreach ($security_questions as $question): ?>
                        <option value="<?php echo htmlspecialchars($question); ?>"><?php echo htmlspecialchars($question); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group" id="security-answer-group" style="display: none;">
                <label for="security_answer">Security Answer *</label>
                <input type="text" id="security_answer" name="security_answer" 
                       placeholder="Enter security answer">
            </div>
        <?php endif; ?>
        
        <div class="form-actions">
            <button type="submit" class="btn btn-primary"><?php echo $edit_user ? 'Update User' : 'Add User'; ?></button>
            <?php if ($edit_user): ?>
                <a href="users.php" class="btn btn-secondary">Cancel</a>
            <?php endif; ?>
        </div>
    </form>
</div>

<div class="card mt-20">
    <h3>Administrators</h3>
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Phone</th>
                    <th>Email</th>
                    <th>Username</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($admins) > 0): ?>
                    <?php foreach ($admins as $admin): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($admin['AdminId']); ?></td>
                            <td><?php echo htmlspecialchars($admin['FirstName'] . ' ' . $admin['LastName']); ?></td>
                            <td><?php echo htmlspecialchars($admin['PhoneNo']); ?></td>
                            <td><?php echo htmlspecialchars($admin['Email']); ?></td>
                            <td><?php echo htmlspecialchars($admin['Username']); ?></td>
                            <td>
                                <a href="?edit=<?php echo $admin['AdminId']; ?>&type=admin" class="btn btn-sm btn-success">Edit</a>
                                <?php if ($admin['AdminId'] != $_SESSION['user_id']): ?>
                                    <a href="?delete=<?php echo $admin['AdminId']; ?>&type=admin" 
                                       class="btn btn-sm btn-danger" 
                                       onclick="return confirm('Are you sure you want to delete this admin?');">Delete</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" class="empty-state">No administrators found</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="card mt-20">
    <h3>Receptionists</h3>
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Phone</th>
                    <th>Email</th>
                    <th>Username</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($receptionists) > 0): ?>
                    <?php foreach ($receptionists as $receptionist): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($receptionist['RecId']); ?></td>
                            <td><?php echo htmlspecialchars($receptionist['FirstName'] . ' ' . $receptionist['LastName']); ?></td>
                            <td><?php echo htmlspecialchars($receptionist['PhoneNo']); ?></td>
                            <td><?php echo htmlspecialchars($receptionist['Email']); ?></td>
                            <td><?php echo htmlspecialchars($receptionist['Username']); ?></td>
                            <td>
                                <a href="?edit=<?php echo $receptionist['RecId']; ?>&type=receptionist" class="btn btn-sm btn-success">Edit</a>
                                <a href="?delete=<?php echo $receptionist['RecId']; ?>&type=receptionist" 
                                   class="btn btn-sm btn-danger" 
                                   onclick="return confirm('Are you sure you want to delete this receptionist?');">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" class="empty-state">No receptionists found</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="card mt-20">
    <h3>Advocates</h3>
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Phone</th>
                    <th>Email</th>
                    <th>Address</th>
                    <th>Username</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (isset($advocates) && count($advocates) > 0): ?>
                    <?php foreach ($advocates as $advocate): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($advocate['AdvtId']); ?></td>
                            <td><?php echo htmlspecialchars($advocate['FirstName'] . ' ' . $advocate['LastName']); ?></td>
                            <td><?php echo htmlspecialchars($advocate['PhoneNo']); ?></td>
                            <td><?php echo htmlspecialchars($advocate['Email']); ?></td>
                            <td><?php echo htmlspecialchars($advocate['Address'] ?? '-'); ?></td>
                            <td><?php echo htmlspecialchars($advocate['Username']); ?></td>
                            <td><?php echo htmlspecialchars($advocate['Status']); ?></td>
                            <td>
                                <a href="?edit=<?php echo $advocate['AdvtId']; ?>&type=advocate" class="btn btn-sm btn-success">Edit</a>
                                <a href="?delete=<?php echo $advocate['AdvtId']; ?>&type=advocate" 
                                   class="btn btn-sm btn-danger" 
                                   onclick="return confirm('Are you sure you want to delete this advocate?');">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="8" class="empty-state">No advocates found</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
// Show/hide address, status, and security question fields based on user type
document.getElementById('user_type')?.addEventListener('change', function() {
    const userType = this.value;
    const addressGroup = document.getElementById('address-group');
    const statusGroup = document.getElementById('status-group');
    const securityQuestionGroup = document.getElementById('security-question-group');
    const securityAnswerGroup = document.getElementById('security-answer-group');
    const securityQuestionSelect = securityQuestionGroup ? securityQuestionGroup.querySelector('select') : null;
    const securityAnswerInput = securityAnswerGroup ? securityAnswerGroup.querySelector('input') : null;
    
    if (userType === 'advocate') {
        if (addressGroup) addressGroup.style.display = 'block';
        if (statusGroup) statusGroup.style.display = 'block';
        if (securityQuestionGroup) securityQuestionGroup.style.display = 'block';
        if (securityAnswerGroup) securityAnswerGroup.style.display = 'block';
        if (securityQuestionSelect) securityQuestionSelect.required = true;
        if (securityAnswerInput) securityAnswerInput.required = true;
    } else if (userType === 'receptionist') {
        if (addressGroup) addressGroup.style.display = 'none';
        if (statusGroup) statusGroup.style.display = 'none';
        if (securityQuestionGroup) securityQuestionGroup.style.display = 'block';
        if (securityAnswerGroup) securityAnswerGroup.style.display = 'block';
        if (securityQuestionSelect) securityQuestionSelect.required = true;
        if (securityAnswerInput) securityAnswerInput.required = true;
    } else {
        if (addressGroup) addressGroup.style.display = 'none';
        if (statusGroup) statusGroup.style.display = 'none';
        if (securityQuestionGroup) securityQuestionGroup.style.display = 'none';
        if (securityAnswerGroup) securityAnswerGroup.style.display = 'none';
        if (securityQuestionSelect) securityQuestionSelect.required = false;
        if (securityAnswerInput) securityAnswerInput.required = false;
    }
});
</script>

<?php include 'footer.php'; ?>

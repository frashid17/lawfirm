<?php
/**
 * ADMIN - MANAGE ADVOCATES
 * View, add, edit, and delete advocates
 */

require_once '../config/database.php';
require_once '../config/session.php';
requireRole('admin');

$message = "";
$error = "";

// Handle delete
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    try {
        $stmt = $conn->prepare("UPDATE ADVOCATE SET Status = 'Inactive' WHERE AdvtId = ?");
        $stmt->execute([$_GET['delete']]);
        $message = "Advocate deactivated successfully";
    } catch(PDOException $e) {
        $error = "Error deactivating advocate: " . $e->getMessage();
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $advt_id = $_POST['advt_id'] ?? null;
    $firstname = trim($_POST['firstname'] ?? '');
    $lastname = trim($_POST['lastname'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $status = trim($_POST['status'] ?? 'Active');
    
    if (empty($firstname) || empty($lastname) || empty($phone) || empty($email) || empty($username)) {
        $error = "Please fill in all required fields";
    } else {
        try {
            if ($advt_id) {
                // Update existing advocate
                if (!empty($password)) {
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $conn->prepare("UPDATE ADVOCATE SET FirstName = ?, LastName = ?, PhoneNo = ?, Email = ?, Address = ?, Username = ?, Password = ?, Status = ? WHERE AdvtId = ?");
                    $stmt->execute([$firstname, $lastname, $phone, $email, $address, $username, $hashed_password, $status, $advt_id]);
                } else {
                    $stmt = $conn->prepare("UPDATE ADVOCATE SET FirstName = ?, LastName = ?, PhoneNo = ?, Email = ?, Address = ?, Username = ?, Status = ? WHERE AdvtId = ?");
                    $stmt->execute([$firstname, $lastname, $phone, $email, $address, $username, $status, $advt_id]);
                }
                $message = "Advocate updated successfully";
            } else {
                // Insert new advocate
                if (empty($password)) {
                    $error = "Password is required for new advocate";
                } else {
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $conn->prepare("INSERT INTO ADVOCATE (FirstName, LastName, PhoneNo, Email, Address, Username, Password, Status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$firstname, $lastname, $phone, $email, $address, $username, $hashed_password, $status]);
                    $message = "Advocate added successfully";
                }
            }
        } catch(PDOException $e) {
            if ($e->getCode() == 23000) {
                $error = "Username or email already exists";
            } else {
                $error = "Error saving advocate: " . $e->getMessage();
            }
        }
    }
}

// Get advocate for editing
$edit_advocate = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    try {
        $stmt = $conn->prepare("SELECT * FROM ADVOCATE WHERE AdvtId = ?");
        $stmt->execute([$_GET['edit']]);
        $edit_advocate = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        $error = "Error loading advocate: " . $e->getMessage();
    }
}

// Get all advocates
try {
    $stmt = $conn->query("SELECT * FROM ADVOCATE ORDER BY Status DESC, LastName, FirstName");
    $advocates = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $error = "Error loading advocates: " . $e->getMessage();
}

include 'header.php';
?>

<h2>Manage Advocates</h2>

<?php if ($message): ?>
    <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>

<div class="form-container">
    <h3><?php echo $edit_advocate ? 'Edit Advocate' : 'Add New Advocate'; ?></h3>
    <form method="POST" action="">
        <?php if ($edit_advocate): ?>
            <input type="hidden" name="advt_id" value="<?php echo htmlspecialchars($edit_advocate['AdvtId']); ?>">
        <?php endif; ?>
        
        <div class="form-group">
            <label for="firstname">First Name *</label>
            <input type="text" id="firstname" name="firstname" 
                   value="<?php echo htmlspecialchars($edit_advocate['FirstName'] ?? ''); ?>" required>
        </div>
        
        <div class="form-group">
            <label for="lastname">Last Name *</label>
            <input type="text" id="lastname" name="lastname" 
                   value="<?php echo htmlspecialchars($edit_advocate['LastName'] ?? ''); ?>" required>
        </div>
        
        <div class="form-group">
            <label for="phone">Phone Number *</label>
            <input type="text" id="phone" name="phone" 
                   value="<?php echo htmlspecialchars($edit_advocate['PhoneNo'] ?? ''); ?>" required>
        </div>
        
        <div class="form-group">
            <label for="email">Email *</label>
            <input type="email" id="email" name="email" 
                   value="<?php echo htmlspecialchars($edit_advocate['Email'] ?? ''); ?>" required>
        </div>
        
        <div class="form-group">
            <label for="address">Address</label>
            <textarea id="address" name="address"><?php echo htmlspecialchars($edit_advocate['Address'] ?? ''); ?></textarea>
        </div>
        
        <div class="form-group">
            <label for="username">Username *</label>
            <input type="text" id="username" name="username" 
                   value="<?php echo htmlspecialchars($edit_advocate['Username'] ?? ''); ?>" required>
        </div>
        
        <div class="form-group">
            <label for="password">Password <?php echo $edit_advocate ? '(leave blank to keep current)' : '*'; ?></label>
            <input type="password" id="password" name="password" 
                   <?php echo $edit_advocate ? '' : 'required'; ?>>
        </div>
        
        <?php if ($edit_advocate): ?>
        <div class="form-group">
            <label for="status">Status</label>
            <select id="status" name="status">
                <option value="Active" <?php echo ($edit_advocate['Status'] == 'Active') ? 'selected' : ''; ?>>Active</option>
                <option value="Inactive" <?php echo ($edit_advocate['Status'] == 'Inactive') ? 'selected' : ''; ?>>Inactive</option>
            </select>
        </div>
        <?php endif; ?>
        
        <div class="form-actions">
            <button type="submit" class="btn btn-primary"><?php echo $edit_advocate ? 'Update Advocate' : 'Add Advocate'; ?></button>
            <?php if ($edit_advocate): ?>
                <a href="advocates.php" class="btn btn-secondary">Cancel</a>
            <?php endif; ?>
        </div>
    </form>
</div>

<div class="card mt-20">
    <h3>All Advocates</h3>
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Phone</th>
                    <th>Email</th>
                    <th>Username</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($advocates) > 0): ?>
                    <?php foreach ($advocates as $advocate): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($advocate['AdvtId']); ?></td>
                            <td><?php echo htmlspecialchars($advocate['FirstName'] . ' ' . $advocate['LastName']); ?></td>
                            <td><?php echo htmlspecialchars($advocate['PhoneNo']); ?></td>
                            <td><?php echo htmlspecialchars($advocate['Email']); ?></td>
                            <td><?php echo htmlspecialchars($advocate['Username']); ?></td>
                            <td><?php echo htmlspecialchars($advocate['Status']); ?></td>
                            <td>
                                <a href="?edit=<?php echo $advocate['AdvtId']; ?>" class="btn btn-sm btn-success">Edit</a>
                                <?php if ($advocate['Status'] == 'Active'): ?>
                                    <a href="?delete=<?php echo $advocate['AdvtId']; ?>" 
                                       class="btn btn-sm btn-danger" 
                                       onclick="return confirm('Are you sure you want to deactivate this advocate?');">Deactivate</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" class="empty-state">No advocates found</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'footer.php'; ?>

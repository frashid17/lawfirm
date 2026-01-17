<?php
/**
 * RECEPTIONIST - MANAGE CLIENTS
 * Register and manage clients
 */

require_once '../config/database.php';
require_once '../config/session.php';
requireRole('receptionist');

$message = "";
$error = "";

// Handle delete client
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    try {
        // Check if client has active cases
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM `CASE` WHERE ClientId = ? AND Status = 'Active'");
        $stmt->execute([$_GET['delete']]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result['count'] > 0) {
            $error = "Cannot delete client with active cases. Please close or reassign cases first.";
        } else {
            $stmt = $conn->prepare("DELETE FROM CLIENT WHERE ClientId = ?");
            $stmt->execute([$_GET['delete']]);
            $message = "Client deleted successfully";
        }
    } catch(PDOException $e) {
        $error = "Error deleting client: " . $e->getMessage();
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $client_id = $_POST['client_id'] ?? null;
    $firstname = trim($_POST['firstname'] ?? '');
    $lastname = trim($_POST['lastname'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $address = trim($_POST['address'] ?? '');
    
    if (empty($firstname) || empty($lastname) || empty($phone)) {
        $error = "Please fill in all required fields";
    } else {
        try {
            if ($client_id) {
                // Update existing client
                $stmt = $conn->prepare("UPDATE CLIENT SET FirstName = ?, LastName = ?, PhoneNo = ?, Email = ?, Address = ? WHERE ClientId = ?");
                $stmt->execute([$firstname, $lastname, $phone, $email, $address, $client_id]);
                $message = "Client updated successfully";
            } else {
                // Insert new client
                $stmt = $conn->prepare("INSERT INTO CLIENT (FirstName, LastName, PhoneNo, Email, Address) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$firstname, $lastname, $phone, $email, $address]);
                $message = "Client registered successfully";
            }
        } catch(PDOException $e) {
            $error = "Error saving client: " . $e->getMessage();
        }
    }
}

// Get client for editing
$edit_client = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    try {
        $stmt = $conn->prepare("SELECT * FROM CLIENT WHERE ClientId = ?");
        $stmt->execute([$_GET['edit']]);
        $edit_client = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        $error = "Error loading client: " . $e->getMessage();
    }
}

// Get all clients
try {
    $stmt = $conn->query("SELECT * FROM CLIENT ORDER BY CreatedAt DESC");
    $clients = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $error = "Error loading clients: " . $e->getMessage();
}

include 'header.php';
?>

<h2>Manage Clients</h2>

<?php if ($message): ?>
    <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>

<div class="card">
    <h3>All Clients</h3>
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Phone</th>
                    <th>Email</th>
                    <th>Address</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($clients) > 0): ?>
                    <?php foreach ($clients as $client): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($client['ClientId']); ?></td>
                            <td><?php echo htmlspecialchars($client['FirstName'] . ' ' . $client['LastName']); ?></td>
                            <td><?php echo htmlspecialchars($client['PhoneNo']); ?></td>
                            <td><?php echo htmlspecialchars($client['Email'] ?? '-'); ?></td>
                            <td><?php echo htmlspecialchars($client['Address'] ?? '-'); ?></td>
                            <td>
                                <a href="?edit=<?php echo $client['ClientId']; ?>" class="btn btn-sm btn-success">Edit</a>
                                <a href="?delete=<?php echo $client['ClientId']; ?>" 
                                   class="btn btn-sm btn-danger" 
                                   onclick="return confirm('Are you sure you want to delete this client? This action cannot be undone.');">
                                    <i class="fas fa-trash"></i> Delete
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" class="empty-state">No clients found</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="card mt-20">
    <h3><?php echo $edit_client ? 'Edit Client' : 'Register New Client'; ?></h3>
    <form method="POST" action="">
        <?php if ($edit_client): ?>
            <input type="hidden" name="client_id" value="<?php echo htmlspecialchars($edit_client['ClientId']); ?>">
        <?php endif; ?>
        
        <div class="form-group">
            <label for="firstname">First Name *</label>
            <input type="text" id="firstname" name="firstname" 
                   value="<?php echo htmlspecialchars($edit_client['FirstName'] ?? ''); ?>" required>
        </div>
        
        <div class="form-group">
            <label for="lastname">Last Name *</label>
            <input type="text" id="lastname" name="lastname" 
                   value="<?php echo htmlspecialchars($edit_client['LastName'] ?? ''); ?>" required>
        </div>
        
        <div class="form-group">
            <label for="phone">Phone Number *</label>
            <input type="text" id="phone" name="phone" 
                   value="<?php echo htmlspecialchars($edit_client['PhoneNo'] ?? ''); ?>" required>
        </div>
        
        <div class="form-group">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" 
                   value="<?php echo htmlspecialchars($edit_client['Email'] ?? ''); ?>">
        </div>
        
        <div class="form-group">
            <label for="address">Address</label>
            <textarea id="address" name="address"><?php echo htmlspecialchars($edit_client['Address'] ?? ''); ?></textarea>
        </div>
        
        <div class="form-actions">
            <button type="submit" class="btn btn-primary"><?php echo $edit_client ? 'Update Client' : 'Register Client'; ?></button>
            <?php if ($edit_client): ?>
                <a href="clients.php" class="btn btn-secondary">Cancel</a>
            <?php endif; ?>
        </div>
    </form>
</div>

<?php include 'footer.php'; ?>

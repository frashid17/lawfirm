<?php
/**
 * ADMIN - CREATE CLIENT PORTAL ACCOUNT
 * Allow admin to create login credentials for clients
 */

require_once '../config/database.php';
require_once '../config/session.php';
requireRole('admin');

$message = "";
$error = "";

// Handle account creation
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $client_id = $_POST['client_id'] ?? null;
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($client_id) || empty($username) || empty($password)) {
        $error = "Please fill in all fields";
    } else {
        try {
            // Check if username already exists
            $stmt = $conn->prepare("SELECT AuthId FROM CLIENT_AUTH WHERE Username = ?");
            $stmt->execute([$username]);
            if ($stmt->fetch()) {
                $error = "Username already exists";
            } else {
                // Check if client already has account
                $stmt = $conn->prepare("SELECT AuthId FROM CLIENT_AUTH WHERE ClientId = ?");
                $stmt->execute([$client_id]);
                if ($stmt->fetch()) {
                    // Update existing account
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $conn->prepare("UPDATE CLIENT_AUTH SET Username = ?, Password = ?, IsActive = TRUE WHERE ClientId = ?");
                    $stmt->execute([$username, $hashed_password, $client_id]);
                    $message = "Client account updated successfully";
                } else {
                    // Create new account
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $conn->prepare("INSERT INTO CLIENT_AUTH (ClientId, Username, Password) VALUES (?, ?, ?)");
                    $stmt->execute([$client_id, $username, $hashed_password]);
                    $message = "Client portal account created successfully";
                }
            }
        } catch(PDOException $e) {
            $error = "Error creating account: " . $e->getMessage();
        }
    }
}

// Get all clients
try {
    $stmt = $conn->query("SELECT c.*, ca.Username, ca.IsActive as HasAccount 
                          FROM CLIENT c 
                          LEFT JOIN CLIENT_AUTH ca ON c.ClientId = ca.ClientId 
                          ORDER BY c.LastName, c.FirstName");
    $clients = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $error = "Error loading clients: " . $e->getMessage();
}

include 'header.php';
?>

<h2>Create Client Portal Accounts</h2>

<?php if ($message): ?>
    <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>

<div class="form-container">
    <h3>Create/Update Client Account</h3>
    <form method="POST" action="">
        <div class="form-group">
            <label for="client_id">Client *</label>
            <select id="client_id" name="client_id" required>
                <option value="">-- Select Client --</option>
                <?php foreach ($clients as $client): ?>
                    <option value="<?php echo htmlspecialchars($client['ClientId']); ?>">
                        <?php echo htmlspecialchars($client['FirstName'] . ' ' . $client['LastName']); ?>
                        <?php if ($client['HasAccount']): ?>
                            (Has Account: <?php echo htmlspecialchars($client['Username']); ?>)
                        <?php endif; ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="form-group">
            <label for="username">Username *</label>
            <input type="text" id="username" name="username" required 
                   placeholder="Enter username for client portal">
        </div>
        
        <div class="form-group">
            <label for="password">Password *</label>
            <input type="password" id="password" name="password" required 
                   placeholder="Enter password">
        </div>
        
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Create/Update Account</button>
        </div>
    </form>
</div>

<div class="card mt-20">
    <h3>Client Portal Accounts</h3>
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Client Name</th>
                    <th>Email</th>
                    <th>Username</th>
                    <th>Status</th>
                    <th>Last Login</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($clients) > 0): ?>
                    <?php foreach ($clients as $client): ?>
                        <?php if ($client['HasAccount']): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($client['FirstName'] . ' ' . $client['LastName']); ?></td>
                                <td><?php echo htmlspecialchars($client['Email'] ?? '-'); ?></td>
                                <td><?php echo htmlspecialchars($client['Username']); ?></td>
                                <td>
                                    <span style="color: var(--success-color);">Active</span>
                                </td>
                                <td>
                                    <?php
                                    try {
                                        $stmt = $conn->prepare("SELECT LastLogin FROM CLIENT_AUTH WHERE ClientId = ?");
                                        $stmt->execute([$client['ClientId']]);
                                        $auth = $stmt->fetch(PDO::FETCH_ASSOC);
                                        echo $auth && $auth['LastLogin'] ? date('Y-m-d H:i', strtotime($auth['LastLogin'])) : 'Never';
                                    } catch(PDOException $e) {
                                        echo '-';
                                    }
                                    ?>
                                </td>
                            </tr>
                        <?php endif; ?>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" class="empty-state">No client accounts created yet</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'footer.php'; ?>

<?php
/**
 * ADMIN - LOCKED ACCOUNTS
 * View and unlock locked user accounts
 */

require_once '../config/database.php';
require_once '../config/session.php';
requireRole('admin');

$message = "";
$error = "";

// Handle unlock action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['unlock_account'])) {
    $account_type = $_POST['account_type'] ?? '';
    $account_id = $_POST['account_id'] ?? null;
    
    if (empty($account_type) || empty($account_id)) {
        $error = "Invalid account information";
    } else {
        try {
            if ($account_type === 'advocate') {
                $stmt = $conn->prepare("UPDATE ADVOCATE SET IsLocked = FALSE, FailedAttempts = 0, LockedAt = NULL WHERE AdvtId = ?");
                $stmt->execute([$account_id]);
                $message = "Advocate account unlocked successfully. User can now attempt login 5 times.";
            } elseif ($account_type === 'receptionist') {
                $stmt = $conn->prepare("UPDATE RECEPTIONIST SET IsLocked = FALSE, FailedAttempts = 0, LockedAt = NULL WHERE RecId = ?");
                $stmt->execute([$account_id]);
                $message = "Receptionist account unlocked successfully. User can now attempt login 5 times.";
            } elseif ($account_type === 'client') {
                $stmt = $conn->prepare("UPDATE CLIENT_AUTH SET IsLocked = FALSE, FailedAttempts = 0, LockedAt = NULL WHERE AuthId = ?");
                $stmt->execute([$account_id]);
                $message = "Client account unlocked successfully. User can now attempt login 5 times.";
            }
            
            // Redirect to prevent resubmission
            header("Location: locked_accounts.php?success=1");
            exit();
        } catch(PDOException $e) {
            $error = "Error unlocking account: " . $e->getMessage();
        }
    }
}

// Get success message from URL
if (isset($_GET['success'])) {
    $message = "Account unlocked successfully. User can now attempt login 5 times.";
}

// Fetch all locked accounts
$locked_accounts = [];

try {
    // Locked advocates
    $stmt = $conn->prepare("SELECT AdvtId, FirstName, LastName, Username, Email, LockedAt, FailedAttempts 
                            FROM ADVOCATE 
                            WHERE IsLocked = TRUE 
                            ORDER BY LockedAt DESC");
    $stmt->execute();
    $advocates = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($advocates as $adv) {
        $locked_accounts[] = [
            'type' => 'advocate',
            'id' => $adv['AdvtId'],
            'name' => $adv['FirstName'] . ' ' . $adv['LastName'],
            'username' => $adv['Username'],
            'email' => $adv['Email'],
            'locked_at' => $adv['LockedAt'],
            'failed_attempts' => $adv['FailedAttempts']
        ];
    }
    
    // Locked receptionists
    $stmt = $conn->prepare("SELECT RecId, FirstName, LastName, Username, Email, LockedAt, FailedAttempts 
                            FROM RECEPTIONIST 
                            WHERE IsLocked = TRUE 
                            ORDER BY LockedAt DESC");
    $stmt->execute();
    $receptionists = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($receptionists as $rec) {
        $locked_accounts[] = [
            'type' => 'receptionist',
            'id' => $rec['RecId'],
            'name' => $rec['FirstName'] . ' ' . $rec['LastName'],
            'username' => $rec['Username'],
            'email' => $rec['Email'],
            'locked_at' => $rec['LockedAt'],
            'failed_attempts' => $rec['FailedAttempts']
        ];
    }
    
    // Locked clients
    $stmt = $conn->prepare("SELECT ca.AuthId, c.FirstName, c.LastName, ca.Username, c.Email, ca.LockedAt, ca.FailedAttempts 
                            FROM CLIENT_AUTH ca 
                            JOIN CLIENT c ON ca.ClientId = c.ClientId 
                            WHERE ca.IsLocked = TRUE 
                            ORDER BY ca.LockedAt DESC");
    $stmt->execute();
    $clients = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($clients as $client) {
        $locked_accounts[] = [
            'type' => 'client',
            'id' => $client['AuthId'],
            'name' => $client['FirstName'] . ' ' . $client['LastName'],
            'username' => $client['Username'],
            'email' => $client['Email'],
            'locked_at' => $client['LockedAt'],
            'failed_attempts' => $client['FailedAttempts']
        ];
    }
} catch(PDOException $e) {
    $error = "Error loading locked accounts: " . $e->getMessage();
}

include 'header.php';
?>

<h2>Locked Accounts</h2>

<?php if ($message): ?>
    <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>

<div class="card">
    <?php if (empty($locked_accounts)): ?>
        <p style="text-align: center; color: var(--gray); padding: 40px;">
            <i class="fas fa-check-circle" style="font-size: 48px; color: #10b981; margin-bottom: 20px; display: block;"></i>
            No locked accounts at this time.
        </p>
    <?php else: ?>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Account Type</th>
                    <th>Name</th>
                    <th>Username</th>
                    <th>Email</th>
                    <th>Failed Attempts</th>
                    <th>Locked At</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($locked_accounts as $account): ?>
                    <tr>
                        <td>
                            <span class="badge badge-<?php 
                                echo $account['type'] === 'advocate' ? 'primary' : 
                                    ($account['type'] === 'receptionist' ? 'info' : 'secondary'); 
                            ?>">
                                <?php echo ucfirst($account['type']); ?>
                            </span>
                        </td>
                        <td><?php echo htmlspecialchars($account['name']); ?></td>
                        <td><?php echo htmlspecialchars($account['username']); ?></td>
                        <td><?php echo htmlspecialchars($account['email'] ?? 'N/A'); ?></td>
                        <td><?php echo $account['failed_attempts']; ?></td>
                        <td><?php echo $account['locked_at'] ? date('M d, Y H:i', strtotime($account['locked_at'])) : 'N/A'; ?></td>
                        <td>
                            <form method="POST" action="" style="display: inline;" onsubmit="return confirm('Are you sure you want to unlock this account?');">
                                <input type="hidden" name="account_type" value="<?php echo htmlspecialchars($account['type']); ?>">
                                <input type="hidden" name="account_id" value="<?php echo $account['id']; ?>">
                                <button type="submit" name="unlock_account" class="btn btn-primary btn-sm">
                                    <i class="fas fa-unlock"></i> Unlock
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php include 'footer.php'; ?>

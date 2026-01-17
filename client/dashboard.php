<?php
/**
 * CLIENT PORTAL DASHBOARD
 * Main dashboard for clients
 */

session_start();
require_once '../config/database.php';

// Check if client is logged in
if (!isset($_SESSION['client_id'])) {
    header("Location: login.php");
    exit();
}

$client_id = $_SESSION['client_id'];

// Get client's cases
try {
    $stmt = $conn->prepare("SELECT c.*, cl.FirstName, cl.LastName 
                            FROM `CASE` c 
                            JOIN CLIENT cl ON c.ClientId = cl.ClientId 
                            WHERE c.ClientId = ? 
                            ORDER BY c.CreatedAt DESC");
    $stmt->execute([$client_id]);
    $cases = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $error = "Error loading cases: " . $e->getMessage();
}

// Get upcoming events
try {
    $stmt = $conn->prepare("SELECT e.*, c.CaseName 
                            FROM EVENT e 
                            JOIN `CASE` c ON e.CaseNo = c.CaseNo 
                            WHERE c.ClientId = ? AND e.Date >= NOW() 
                            ORDER BY e.Date ASC 
                            LIMIT 5");
    $stmt->execute([$client_id]);
    $upcoming_events = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $upcoming_events = [];
}

// Get invoices
try {
    $stmt = $conn->prepare("SELECT b.*, c.CaseName 
                            FROM BILLING b 
                            JOIN `CASE` c ON b.CaseNo = c.CaseNo 
                            WHERE c.ClientId = ? 
                            ORDER BY b.Date DESC 
                            LIMIT 5");
    $stmt->execute([$client_id]);
    $invoices = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $invoices = [];
}

// Get unread messages count
try {
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM MESSAGE WHERE ClientId = ? AND IsRead = FALSE AND SenderRole = 'advocate'");
    $stmt->execute([$client_id]);
    $unread_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
} catch(PDOException $e) {
    $unread_count = 0;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Client Portal - Law Firm Management System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="header">
        <div class="header-content">
            <h1>Client Portal - <?php echo htmlspecialchars($_SESSION['client_name']); ?></h1>
            <div class="header-user">
                <a href="messages.php" class="btn btn-secondary btn-sm" style="position: relative;">
                    <i class="fas fa-envelope"></i> Messages
                    <?php if ($unread_count > 0): ?>
                        <span style="position: absolute; top: -8px; right: -8px; background: #ef4444; color: white; border-radius: 50%; width: 20px; height: 20px; display: flex; align-items: center; justify-content: center; font-size: 11px; font-weight: bold;">
                            <?php echo $unread_count; ?>
                        </span>
                    <?php endif; ?>
                </a>
                <a href="logout.php" class="btn btn-secondary btn-sm"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>
    </div>
    
    <div class="nav">
        <div class="nav-content">
            <ul class="nav-menu">
                <li><a href="dashboard.php" class="active"><i class="fas fa-home"></i> Dashboard</a></li>
                <li><a href="my_cases.php"><i class="fas fa-folder-open"></i> My Cases</a></li>
                <li><a href="invoices.php"><i class="fas fa-file-invoice-dollar"></i> Invoices</a></li>
                <li><a href="payment_history.php"><i class="fas fa-history"></i> Payment History</a></li>
                <li><a href="documents.php"><i class="fas fa-file"></i> Documents</a></li>
                <li><a href="messages.php"><i class="fas fa-comments"></i> Messages</a></li>
                <li><a href="events.php"><i class="fas fa-calendar-alt"></i> Events</a></li>
            </ul>
        </div>
    </div>
    
    <div class="container">
        <div class="dashboard">
            <h2>My Dashboard</h2>
            
            <div class="stats-grid">
                <div class="stat-card">
                    <h3>My Cases</h3>
                    <div class="number"><?php echo count($cases); ?></div>
                </div>
                
                <div class="stat-card">
                    <h3>Upcoming Events</h3>
                    <div class="number"><?php echo count($upcoming_events); ?></div>
                </div>
                
                <div class="stat-card">
                    <h3>Pending Invoices</h3>
                    <div class="number">
                        <?php 
                        $pending = 0;
                        foreach ($invoices as $inv) {
                            if ($inv['Status'] == 'Pending') $pending++;
                        }
                        echo $pending;
                        ?>
                    </div>
                </div>
            </div>
            
            <div class="card mt-20">
                <h3>My Cases</h3>
                <?php if (count($cases) > 0): ?>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Case Name</th>
                                    <th>Type</th>
                                    <th>Status</th>
                                    <th>Created</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($cases as $case): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($case['CaseName']); ?></td>
                                        <td><?php echo htmlspecialchars($case['CaseType']); ?></td>
                                        <td><?php echo htmlspecialchars($case['Status']); ?></td>
                                        <td><?php echo date('Y-m-d', strtotime($case['CreatedAt'])); ?></td>
                                        <td>
                                            <a href="case_details.php?id=<?php echo $case['CaseNo']; ?>" class="btn btn-sm btn-success">View</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="empty-state">No cases found</p>
                <?php endif; ?>
            </div>
            
            <div class="card mt-20">
                <h3>Upcoming Events</h3>
                <?php if (count($upcoming_events) > 0): ?>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Event</th>
                                    <th>Case</th>
                                    <th>Date & Time</th>
                                    <th>Location</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($upcoming_events as $event): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($event['EventName']); ?></td>
                                        <td><?php echo htmlspecialchars($event['CaseName']); ?></td>
                                        <td><?php echo date('Y-m-d H:i', strtotime($event['Date'])); ?></td>
                                        <td><?php echo htmlspecialchars($event['Location'] ?? '-'); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="empty-state">No upcoming events</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>

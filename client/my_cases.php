<?php
/**
 * CLIENT MY CASES
 * View all cases for the client
 */

session_start();
require_once '../config/database.php';

if (!isset($_SESSION['client_id'])) {
    header("Location: login.php");
    exit();
}

$client_id = $_SESSION['client_id'];

try {
    $stmt = $conn->prepare("SELECT c.*, a.FirstName as AdvocateFirstName, a.LastName as AdvocateLastName 
                            FROM `CASE` c 
                            LEFT JOIN CASE_ASSIGNMENT ca ON c.CaseNo = ca.CaseNo AND ca.Status = 'Active'
                            LEFT JOIN ADVOCATE a ON ca.AdvtId = a.AdvtId
                            WHERE c.ClientId = ? 
                            ORDER BY c.CreatedAt DESC");
    $stmt->execute([$client_id]);
    $cases = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $error = "Error loading cases: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Cases - Client Portal</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="header">
        <div class="header-content">
            <h1>My Cases - Client Portal</h1>
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
                <li><a href="my_cases.php" class="active"><i class="fas fa-folder-open"></i> My Cases</a></li>
                <li><a href="invoices.php"><i class="fas fa-file-invoice-dollar"></i> Invoices</a></li>
                <li><a href="payment_history.php"><i class="fas fa-history"></i> Payment History</a></li>
                <li><a href="documents.php"><i class="fas fa-file"></i> Documents</a></li>
                <li><a href="messages.php"><i class="fas fa-comments"></i> Messages</a></li>
                <li><a href="events.php"><i class="fas fa-calendar-alt"></i> Events</a></li>
            </ul>
        </div>
    </div>
    
    <div class="container">
        <div class="card">
            <h3>My Cases</h3>
            <?php if (isset($cases) && count($cases) > 0): ?>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Case Name</th>
                                <th>Type</th>
                                <th>Court</th>
                                <th>Assigned Advocate</th>
                                <th>Status</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($cases as $case): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($case['CaseName']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($case['CaseType']); ?></td>
                                    <td><?php echo htmlspecialchars($case['Court'] ?? '-'); ?></td>
                                    <td>
                                        <?php if ($case['AdvocateFirstName']): ?>
                                            <?php echo htmlspecialchars($case['AdvocateFirstName'] . ' ' . $case['AdvocateLastName']); ?>
                                        <?php else: ?>
                                            <span style="color: var(--gray);">Not assigned</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($case['Status']); ?></td>
                                    <td><?php echo date('Y-m-d', strtotime($case['CreatedAt'])); ?></td>
                                    <td>
                                        <a href="case_details.php?id=<?php echo $case['CaseNo']; ?>" class="btn btn-sm btn-success">View Details</a>
                                        <a href="messages.php?case=<?php echo $case['CaseNo']; ?>" class="btn btn-sm btn-primary">Message</a>
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
    </div>
</body>
</html>

<?php
/**
 * CLIENT CASE DETAILS
 * View detailed information about a case
 */

session_start();
require_once '../config/database.php';

if (!isset($_SESSION['client_id'])) {
    header("Location: login.php");
    exit();
}

$client_id = $_SESSION['client_id'];
$case_no = $_GET['id'] ?? null;

if (!$case_no) {
    header("Location: my_cases.php");
    exit();
}

try {
    $stmt = $conn->prepare("SELECT c.*, a.FirstName as AdvocateFirstName, a.LastName as AdvocateLastName 
                            FROM `CASE` c 
                            LEFT JOIN CASE_ASSIGNMENT ca ON c.CaseNo = ca.CaseNo AND ca.Status = 'Active'
                            LEFT JOIN ADVOCATE a ON ca.AdvtId = a.AdvtId
                            WHERE c.CaseNo = ? AND c.ClientId = ?");
    $stmt->execute([$case_no, $client_id]);
    $case = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$case) {
        header("Location: my_cases.php");
        exit();
    }
} catch(PDOException $e) {
    $error = "Error loading case: " . $e->getMessage();
}

// Get events for this case
try {
    $stmt = $conn->prepare("SELECT * FROM EVENT WHERE CaseNo = ? ORDER BY Date ASC");
    $stmt->execute([$case_no]);
    $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $events = [];
}

// Get documents for this case
try {
    $stmt = $conn->prepare("SELECT * FROM DOCUMENT WHERE CaseNo = ? AND IsCurrentVersion = TRUE ORDER BY UploadedAt DESC");
    $stmt->execute([$case_no]);
    $documents = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $documents = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Case Details - Client Portal</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="header">
        <div class="header-content">
            <h1>Case Details - Client Portal</h1>
            <div class="header-user">
                <a href="my_cases.php" class="btn btn-secondary btn-sm"><i class="fas fa-arrow-left"></i> Back</a>
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
                <li><a href="documents.php"><i class="fas fa-file"></i> Documents</a></li>
                <li><a href="messages.php"><i class="fas fa-comments"></i> Messages</a></li>
            </ul>
        </div>
    </div>
    
    <div class="container">
        <div class="card">
            <h3><?php echo htmlspecialchars($case['CaseName']); ?></h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-top: 20px;">
                <div>
                    <strong>Case Number:</strong><br>
                    <?php echo htmlspecialchars($case['CaseNo']); ?>
                </div>
                <div>
                    <strong>Case Type:</strong><br>
                    <?php echo htmlspecialchars($case['CaseType']); ?>
                </div>
                <div>
                    <strong>Court:</strong><br>
                    <?php echo htmlspecialchars($case['Court'] ?? 'Not specified'); ?>
                </div>
                <div>
                    <strong>Status:</strong><br>
                    <span style="padding: 4px 8px; border-radius: 4px; background: var(--primary-color); color: white; font-size: 12px; font-weight: 600;">
                        <?php echo htmlspecialchars($case['Status']); ?>
                    </span>
                </div>
                <div>
                    <strong>Assigned Advocate:</strong><br>
                    <?php if ($case['AdvocateFirstName']): ?>
                        <?php echo htmlspecialchars($case['AdvocateFirstName'] . ' ' . $case['AdvocateLastName']); ?>
                    <?php else: ?>
                        <span style="color: var(--gray);">Not assigned</span>
                    <?php endif; ?>
                </div>
                <div>
                    <strong>Created:</strong><br>
                    <?php echo date('Y-m-d', strtotime($case['CreatedAt'])); ?>
                </div>
            </div>
            
            <?php if ($case['Description']): ?>
                <div style="margin-top: 20px; padding-top: 20px; border-top: 1px solid var(--border);">
                    <strong>Description:</strong><br>
                    <p style="margin-top: 10px; color: var(--gray);"><?php echo nl2br(htmlspecialchars($case['Description'])); ?></p>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="card mt-20">
            <h3>Upcoming Events</h3>
            <?php if (count($events) > 0): ?>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Event Name</th>
                                <th>Type</th>
                                <th>Date & Time</th>
                                <th>Location</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($events as $event): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($event['EventName']); ?></td>
                                    <td><?php echo htmlspecialchars($event['EventType']); ?></td>
                                    <td><?php echo date('Y-m-d H:i', strtotime($event['Date'])); ?></td>
                                    <td><?php echo htmlspecialchars($event['Location'] ?? '-'); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="empty-state">No events scheduled</p>
            <?php endif; ?>
        </div>
        
        <div class="card mt-20">
            <h3>Case Documents</h3>
            <?php if (count($documents) > 0): ?>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Document Name</th>
                                <th>Category</th>
                                <th>Uploaded</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($documents as $doc): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($doc['DocumentName']); ?></td>
                                    <td><?php echo htmlspecialchars($doc['DocumentCategory']); ?></td>
                                    <td><?php echo date('Y-m-d', strtotime($doc['UploadedAt'])); ?></td>
                                    <td>
                                        <a href="document_download.php?id=<?php echo $doc['DocumentId']; ?>" 
                                           class="btn btn-sm btn-success">
                                            <i class="fas fa-download"></i> Download
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="empty-state">No documents uploaded</p>
            <?php endif; ?>
        </div>
        
        <div style="margin-top: 20px;">
            <a href="messages.php?case=<?php echo $case_no; ?>" class="btn btn-primary">
                <i class="fas fa-comments"></i> Message Advocate
            </a>
        </div>
    </div>
</body>
</html>

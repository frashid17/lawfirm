<?php
/**
 * ADVOCATE DASHBOARD
 * Main page for advocates showing their assigned cases
 */

require_once '../config/database.php';
require_once '../config/session.php';
requireRole('advocate');

$advocate_id = $_SESSION['user_id'];

// Initialize variables
$total_cases = 0;
$upcoming_events = 0;
$error = "";

// Get statistics
try {
    // Total assigned cases
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM CASE_ASSIGNMENT WHERE AdvtId = ? AND Status = 'Active'");
    $stmt->execute([$advocate_id]);
    $total_cases = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Upcoming events
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM EVENT e 
                             JOIN CASE_ASSIGNMENT ca ON e.CaseNo = ca.CaseNo 
                             WHERE ca.AdvtId = ? AND e.Date >= NOW()");
    $stmt->execute([$advocate_id]);
    $upcoming_events = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
} catch(PDOException $e) {
    $error = "Error loading statistics: " . $e->getMessage();
}

include 'header.php';
?>

<div class="dashboard">
    <h2>Advocate Dashboard</h2>
    
    <?php if (isset($error)): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    
    <div class="stats-grid">
        <div class="stat-card">
            <h3>My Assigned Cases</h3>
            <div class="number"><?php echo $total_cases; ?></div>
        </div>
        
        <div class="stat-card">
            <h3>Upcoming Events</h3>
            <div class="number"><?php echo $upcoming_events; ?></div>
        </div>
    </div>
    
    <div class="card">
        <h3>My Assigned Cases</h3>
        <?php
        try {
            $stmt = $conn->prepare("SELECT c.*, cl.FirstName, cl.LastName, cl.PhoneNo as ClientPhone 
                                     FROM `CASE` c 
                                     JOIN CLIENT cl ON c.ClientId = cl.ClientId 
                                     JOIN CASE_ASSIGNMENT ca ON c.CaseNo = ca.CaseNo 
                                     WHERE ca.AdvtId = ? AND ca.Status = 'Active' 
                                     ORDER BY c.CreatedAt DESC");
            $stmt->execute([$advocate_id]);
            $my_cases = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (count($my_cases) > 0):
        ?>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Case No</th>
                            <th>Case Name</th>
                            <th>Client</th>
                            <th>Type</th>
                            <th>Court</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($my_cases as $case): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($case['CaseNo']); ?></td>
                                <td><?php echo htmlspecialchars($case['CaseName']); ?></td>
                                <td><?php echo htmlspecialchars($case['FirstName'] . ' ' . $case['LastName']); ?></td>
                                <td><?php echo htmlspecialchars($case['CaseType']); ?></td>
                                <td><?php echo htmlspecialchars($case['Court'] ?? '-'); ?></td>
                                <td><?php echo htmlspecialchars($case['Status']); ?></td>
                                <td>
                                    <a href="case_details.php?id=<?php echo $case['CaseNo']; ?>" class="btn btn-sm btn-success">View Details</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p class="empty-state">No cases assigned to you</p>
        <?php endif; ?>
        <?php } catch(PDOException $e) { ?>
            <div class="alert alert-error">Error: <?php echo htmlspecialchars($e->getMessage()); ?></div>
        <?php } ?>
    </div>
    
    <div class="card mt-20">
        <h3>Upcoming Events</h3>
        <?php
        try {
            $stmt = $conn->prepare("SELECT e.*, c.CaseName 
                                     FROM EVENT e 
                                     JOIN `CASE` c ON e.CaseNo = c.CaseNo 
                                     JOIN CASE_ASSIGNMENT ca ON e.CaseNo = ca.CaseNo 
                                     WHERE ca.AdvtId = ? AND e.Date >= NOW() 
                                     ORDER BY e.Date ASC 
                                     LIMIT 10");
            $stmt->execute([$advocate_id]);
            $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (count($events) > 0):
        ?>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Event Name</th>
                            <th>Type</th>
                            <th>Date & Time</th>
                            <th>Case</th>
                            <th>Location</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($events as $event): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($event['EventName']); ?></td>
                                <td><?php echo htmlspecialchars($event['EventType']); ?></td>
                                <td><?php echo date('Y-m-d H:i', strtotime($event['Date'])); ?></td>
                                <td><?php echo htmlspecialchars($event['CaseName']); ?></td>
                                <td><?php echo htmlspecialchars($event['Location'] ?? '-'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p class="empty-state">No upcoming events</p>
        <?php endif; ?>
        <?php } catch(PDOException $e) { ?>
            <div class="alert alert-error">Error: <?php echo htmlspecialchars($e->getMessage()); ?></div>
        <?php } ?>
    </div>
</div>

<?php include 'footer.php'; ?>

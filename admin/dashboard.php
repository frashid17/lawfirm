<?php
/**
 * ADMIN DASHBOARD
 * Main page for administrators showing system overview
 */

require_once '../config/database.php';
require_once '../config/session.php';
requireRole('admin');

// Initialize variables
$total_clients = 0;
$total_cases = 0;
$active_cases = 0;
$total_advocates = 0;
$pending_bills = 0;
$upcoming_events = 0;
$error = "";

// Get statistics
try {
    // Total clients
    $stmt = $conn->query("SELECT COUNT(*) as total FROM CLIENT");
    $total_clients = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Total cases
    $stmt = $conn->query("SELECT COUNT(*) as total FROM `CASE`");
    $total_cases = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Active cases
    $stmt = $conn->query("SELECT COUNT(*) as total FROM `CASE` WHERE Status = 'Active'");
    $active_cases = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Total advocates
    $stmt = $conn->query("SELECT COUNT(*) as total FROM ADVOCATE WHERE Status = 'Active'");
    $total_advocates = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Pending bills
    $stmt = $conn->query("SELECT COUNT(*) as total FROM BILLING WHERE Status = 'Pending'");
    $pending_bills = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Upcoming events
    $stmt = $conn->query("SELECT COUNT(*) as total FROM EVENT WHERE Date >= NOW()");
    $upcoming_events = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
} catch(PDOException $e) {
    $error = "Error loading statistics: " . $e->getMessage();
}

include 'header.php';
?>

<div class="dashboard">
    <h2>Admin Dashboard</h2>
    
    <?php if (isset($error)): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    
    <div class="stats-grid">
        <div class="stat-card">
            <h3>Total Clients</h3>
            <div class="number"><?php echo $total_clients; ?></div>
        </div>
        
        <div class="stat-card">
            <h3>Total Cases</h3>
            <div class="number"><?php echo $total_cases; ?></div>
        </div>
        
        <div class="stat-card">
            <h3>Active Cases</h3>
            <div class="number"><?php echo $active_cases; ?></div>
        </div>
        
        <div class="stat-card">
            <h3>Active Advocates</h3>
            <div class="number"><?php echo $total_advocates; ?></div>
        </div>
        
        <div class="stat-card">
            <h3>Pending Bills</h3>
            <div class="number"><?php echo $pending_bills; ?></div>
        </div>
        
        <div class="stat-card">
            <h3>Upcoming Events</h3>
            <div class="number"><?php echo $upcoming_events; ?></div>
        </div>
    </div>
    
    <div class="card">
        <h3>Recent Cases</h3>
        <?php
        try {
            $stmt = $conn->query("SELECT c.*, cl.FirstName, cl.LastName 
                                  FROM `CASE` c 
                                  JOIN CLIENT cl ON c.ClientId = cl.ClientId 
                                  ORDER BY c.CreatedAt DESC 
                                  LIMIT 5");
            $recent_cases = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (count($recent_cases) > 0):
        ?>
            <table>
                <thead>
                    <tr>
                        <th>Case No</th>
                        <th>Case Name</th>
                        <th>Client</th>
                        <th>Type</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recent_cases as $case): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($case['CaseNo']); ?></td>
                            <td><?php echo htmlspecialchars($case['CaseName']); ?></td>
                            <td><?php echo htmlspecialchars($case['FirstName'] . ' ' . $case['LastName']); ?></td>
                            <td><?php echo htmlspecialchars($case['CaseType']); ?></td>
                            <td><?php echo htmlspecialchars($case['Status']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p class="empty-state">No cases found</p>
        <?php endif; ?>
        <?php } catch(PDOException $e) { ?>
            <div class="alert alert-error">Error: <?php echo htmlspecialchars($e->getMessage()); ?></div>
        <?php } ?>
    </div>
</div>

<?php include 'footer.php'; ?>

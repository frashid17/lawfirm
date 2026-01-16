<?php
/**
 * ADMIN - REPORTS
 * Generate various reports
 */

require_once '../config/database.php';
require_once '../config/session.php';
requireRole('admin');

include 'header.php';
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
    <h2>Reports</h2>
    <button onclick="window.print()" class="btn btn-primary" style="width: auto; padding: 10px 20px;">
        <i class="fas fa-print"></i> Print Reports
    </button>
</div>

<style>
    @media print {
        .header, .nav, .btn, footer, button {
            display: none !important;
        }
        .container {
            max-width: 100% !important;
            padding: 0 !important;
        }
        .card {
            page-break-inside: avoid;
            margin-bottom: 20px;
        }
        body {
            background: white !important;
        }
    }
</style>

<div class="stats-grid">
    <div class="card">
        <h3>Cases by Status</h3>
        <?php
        try {
            $stmt = $conn->query("SELECT Status, COUNT(*) as count FROM `CASE` GROUP BY Status");
            $case_status = $stmt->fetchAll(PDO::FETCH_ASSOC);
        ?>
            <table>
                <thead>
                    <tr>
                        <th>Status</th>
                        <th>Count</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($case_status as $status): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($status['Status']); ?></td>
                            <td><?php echo htmlspecialchars($status['count']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php } catch(PDOException $e) { ?>
            <div class="alert alert-error">Error: <?php echo htmlspecialchars($e->getMessage()); ?></div>
        <?php } ?>
    </div>
    
    <div class="card">
        <h3>Billing Summary</h3>
        <?php
        try {
            $stmt = $conn->query("SELECT Status, COUNT(*) as count, SUM(Amount) as total FROM BILLING GROUP BY Status");
            $billing_summary = $stmt->fetchAll(PDO::FETCH_ASSOC);
        ?>
            <table>
                <thead>
                    <tr>
                        <th>Status</th>
                        <th>Count</th>
                        <th>Total Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($billing_summary as $bill): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($bill['Status']); ?></td>
                            <td><?php echo htmlspecialchars($bill['count']); ?></td>
                            <td><?php echo number_format($bill['total'] ?? 0, 2); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php } catch(PDOException $e) { ?>
            <div class="alert alert-error">Error: <?php echo htmlspecialchars($e->getMessage()); ?></div>
        <?php } ?>
    </div>
    
    <div class="card">
        <h3>Cases by Type</h3>
        <?php
        try {
            $stmt = $conn->query("SELECT CaseType, COUNT(*) as count FROM `CASE` GROUP BY CaseType ORDER BY count DESC");
            $case_types = $stmt->fetchAll(PDO::FETCH_ASSOC);
        ?>
            <table>
                <thead>
                    <tr>
                        <th>Case Type</th>
                        <th>Count</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($case_types as $type): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($type['CaseType']); ?></td>
                            <td><?php echo htmlspecialchars($type['count']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php } catch(PDOException $e) { ?>
            <div class="alert alert-error">Error: <?php echo htmlspecialchars($e->getMessage()); ?></div>
        <?php } ?>
    </div>
    
    <div class="card">
        <h3>Upcoming Events (Next 7 Days)</h3>
        <?php
        try {
            $stmt = $conn->prepare("SELECT e.*, c.CaseName 
                                    FROM EVENT e 
                                    JOIN `CASE` c ON e.CaseNo = c.CaseNo 
                                    WHERE e.Date >= NOW() AND e.Date <= DATE_ADD(NOW(), INTERVAL 7 DAY) 
                                    ORDER BY e.Date ASC");
            $stmt->execute();
            $upcoming_events = $stmt->fetchAll(PDO::FETCH_ASSOC);
        ?>
            <?php if (count($upcoming_events) > 0): ?>
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
            <?php else: ?>
                <p class="empty-state">No upcoming events</p>
            <?php endif; ?>
        <?php } catch(PDOException $e) { ?>
            <div class="alert alert-error">Error: <?php echo htmlspecialchars($e->getMessage()); ?></div>
        <?php } ?>
    </div>
</div>

<?php include 'footer.php'; ?>

<?php
/**
 * ADVOCATE - REPORTS
 * Generate reports for assigned cases only
 */

require_once '../config/database.php';
require_once '../config/session.php';
requireRole('advocate');

$advocate_id = $_SESSION['user_id'];

// Fetch detailed data for modals and print
$detailed_cases_by_status = [];
$detailed_billing_summary = [];
$detailed_cases_by_type = [];
$detailed_upcoming_events = [];
$case_status = [];
$billing_summary = [];
$case_types = [];

try {
    // Cases by Status - detailed (only assigned cases)
    $stmt = $conn->prepare("SELECT c.*, cl.FirstName, cl.LastName 
                           FROM `CASE` c 
                           JOIN CLIENT cl ON c.ClientId = cl.ClientId 
                           JOIN CASE_ASSIGNMENT ca ON c.CaseNo = ca.CaseNo 
                           WHERE ca.AdvtId = ? AND ca.Status = 'Active' 
                           ORDER BY c.Status, c.CaseName");
    $stmt->execute([$advocate_id]);
    $all_cases = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($all_cases as $case) {
        $detailed_cases_by_status[$case['Status']][] = $case;
    }
    
    // Billing Summary - detailed (only assigned cases)
    $stmt = $conn->prepare("SELECT b.*, c.CaseName, cl.FirstName, cl.LastName 
                           FROM BILLING b 
                           JOIN `CASE` c ON b.CaseNo = c.CaseNo 
                           JOIN CLIENT cl ON b.ClientId = cl.ClientId 
                           JOIN CASE_ASSIGNMENT ca ON c.CaseNo = ca.CaseNo 
                           WHERE ca.AdvtId = ? AND ca.Status = 'Active' 
                           ORDER BY b.Status, b.Date DESC");
    $stmt->execute([$advocate_id]);
    $all_bills = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($all_bills as $bill) {
        $detailed_billing_summary[$bill['Status']][] = $bill;
    }
    
    // Cases by Type - detailed (only assigned cases)
    $stmt = $conn->prepare("SELECT c.*, cl.FirstName, cl.LastName 
                           FROM `CASE` c 
                           JOIN CLIENT cl ON c.ClientId = cl.ClientId 
                           JOIN CASE_ASSIGNMENT ca ON c.CaseNo = ca.CaseNo 
                           WHERE ca.AdvtId = ? AND ca.Status = 'Active' 
                           ORDER BY c.CaseType, c.CaseName");
    $stmt->execute([$advocate_id]);
    $all_cases_type = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($all_cases_type as $case) {
        $detailed_cases_by_type[$case['CaseType']][] = $case;
    }
    
    // Upcoming Events - already detailed (only assigned cases)
    $stmt = $conn->prepare("SELECT e.*, c.CaseName, c.ClientId, cl.FirstName, cl.LastName 
                           FROM EVENT e 
                           JOIN `CASE` c ON e.CaseNo = c.CaseNo 
                           JOIN CLIENT cl ON c.ClientId = cl.ClientId 
                           JOIN CASE_ASSIGNMENT ca ON c.CaseNo = ca.CaseNo 
                           WHERE ca.AdvtId = ? AND ca.Status = 'Active' 
                           AND e.Date >= NOW() AND e.Date <= DATE_ADD(NOW(), INTERVAL 7 DAY) 
                           ORDER BY e.Date ASC");
    $stmt->execute([$advocate_id]);
    $detailed_upcoming_events = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Fetch summary data for cards and print (only assigned cases)
    $stmt = $conn->prepare("SELECT c.Status, COUNT(*) as count 
                           FROM `CASE` c 
                           JOIN CASE_ASSIGNMENT ca ON c.CaseNo = ca.CaseNo 
                           WHERE ca.AdvtId = ? AND ca.Status = 'Active' 
                           GROUP BY c.Status");
    $stmt->execute([$advocate_id]);
    $case_status = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $stmt = $conn->prepare("SELECT b.Status, COUNT(*) as count, SUM(b.Amount) as total 
                           FROM BILLING b 
                           JOIN `CASE` c ON b.CaseNo = c.CaseNo 
                           JOIN CASE_ASSIGNMENT ca ON c.CaseNo = ca.CaseNo 
                           WHERE ca.AdvtId = ? AND ca.Status = 'Active' 
                           GROUP BY b.Status");
    $stmt->execute([$advocate_id]);
    $billing_summary = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $stmt = $conn->prepare("SELECT c.CaseType, COUNT(*) as count 
                           FROM `CASE` c 
                           JOIN CASE_ASSIGNMENT ca ON c.CaseNo = ca.CaseNo 
                           WHERE ca.AdvtId = ? AND ca.Status = 'Active' 
                           GROUP BY c.CaseType 
                           ORDER BY count DESC");
    $stmt->execute([$advocate_id]);
    $case_types = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $error = "Error loading detailed data: " . $e->getMessage();
}

include 'header.php';
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
    <h2>My Reports Dashboard</h2>
    <button onclick="window.print()" class="btn btn-primary" style="width: auto; padding: 10px 20px;">
        <i class="fas fa-print"></i> Print Reports
    </button>
</div>

<style>
    .reports-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 25px;
        margin-bottom: 30px;
    }
    
    .report-card {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border-radius: 16px;
        padding: 25px;
        color: white;
        cursor: pointer;
        transition: all 0.3s ease;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        position: relative;
        overflow: hidden;
    }
    
    .report-card::before {
        content: '';
        position: absolute;
        top: -50%;
        right: -50%;
        width: 200%;
        height: 200%;
        background: rgba(255, 255, 255, 0.1);
        border-radius: 50%;
        transition: all 0.5s ease;
    }
    
    .report-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.2);
    }
    
    .report-card:hover::before {
        top: -30%;
        right: -30%;
    }
    
    .report-card:nth-child(1) {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    }
    
    .report-card:nth-child(2) {
        background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    }
    
    .report-card:nth-child(3) {
        background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
    }
    
    .report-card:nth-child(4) {
        background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
    }
    
    .report-card-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 15px;
        position: relative;
        z-index: 1;
    }
    
    .report-card-title {
        font-size: 18px;
        font-weight: 600;
        margin: 0;
    }
    
    .report-card-icon {
        font-size: 32px;
        opacity: 0.9;
    }
    
    .report-card-content {
        position: relative;
        z-index: 1;
    }
    
    .report-card-summary {
        font-size: 36px;
        font-weight: 700;
        margin: 10px 0;
    }
    
    .report-card-subtitle {
        font-size: 14px;
        opacity: 0.9;
        margin: 0;
    }
    
    .report-card-click {
        font-size: 12px;
        opacity: 0.8;
        margin-top: 10px;
        display: flex;
        align-items: center;
        gap: 5px;
    }
    
    /* Modal Styles */
    .report-modal {
        display: none;
        position: fixed;
        z-index: 1000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.6);
        backdrop-filter: blur(5px);
        animation: fadeIn 0.3s ease;
    }
    
    @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }
    
    .modal-content {
        background-color: white;
        margin: 3% auto;
        padding: 0;
        border-radius: 16px;
        width: 90%;
        max-width: 900px;
        max-height: 85vh;
        overflow: hidden;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        animation: slideUp 0.3s ease;
        display: flex;
        flex-direction: column;
    }
    
    @keyframes slideUp {
        from {
            transform: translateY(50px);
            opacity: 0;
        }
        to {
            transform: translateY(0);
            opacity: 1;
        }
    }
    
    .modal-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 20px 30px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .modal-header h3 {
        margin: 0;
        font-size: 24px;
        font-weight: 600;
    }
    
    .modal-close {
        background: rgba(255, 255, 255, 0.2);
        border: none;
        color: white;
        font-size: 24px;
        width: 40px;
        height: 40px;
        border-radius: 50%;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.3s ease;
    }
    
    .modal-close:hover {
        background: rgba(255, 255, 255, 0.3);
        transform: rotate(90deg);
    }
    
    .modal-body {
        padding: 30px;
        overflow-y: auto;
        flex: 1;
    }
    
    .modal-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 20px;
    }
    
    .modal-table thead {
        background: #f8f9fa;
        position: sticky;
        top: 0;
    }
    
    .modal-table th {
        padding: 12px 15px;
        text-align: left;
        font-weight: 600;
        color: #495057;
        border-bottom: 2px solid #dee2e6;
    }
    
    .modal-table td {
        padding: 12px 15px;
        border-bottom: 1px solid #e9ecef;
    }
    
    .modal-table tbody tr:hover {
        background-color: #f8f9fa;
    }
    
    .badge {
        display: inline-block;
        padding: 4px 12px;
        border-radius: 12px;
        font-size: 12px;
        font-weight: 600;
    }
    
    .badge-active { background: #d4edda; color: #155724; }
    .badge-pending { background: #fff3cd; color: #856404; }
    .badge-paid { background: #d1ecf1; color: #0c5460; }
    .badge-partially { background: #f8d7da; color: #721c24; }
    
    /* Print Styles */
    @media print {
        .header, .nav, .btn, footer, button, .report-modal, .report-card-click {
            display: none !important;
        }
        
        @page {
            size: A4;
            margin: 1.5cm;
        }
        
        body {
            background: white !important;
            font-size: 11pt;
            line-height: 1.4;
        }
        
        .container {
            max-width: 100% !important;
            padding: 0 !important;
        }
        
        .print-header {
            display: block !important;
            border-bottom: 3px solid #667eea;
            padding-bottom: 15px;
            margin-bottom: 25px;
            page-break-after: avoid;
        }
        
        .print-header h1 {
            color: #667eea !important;
            font-size: 24pt;
            margin: 0 0 5px 0;
            font-weight: 700;
        }
        
        .print-header .company-name {
            font-size: 14pt;
            color: #666;
            margin: 0 0 10px 0;
        }
        
        .print-header .report-info {
            display: flex;
            justify-content: space-between;
            font-size: 10pt;
            color: #666;
            margin-top: 10px;
        }
        
        .print-section {
            page-break-inside: avoid;
            margin-bottom: 30px;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 20px;
            background: #f9f9f9;
        }
        
        .print-section h2 {
            color: #667eea;
            font-size: 16pt;
            margin: 0 0 15px 0;
            padding-bottom: 10px;
            border-bottom: 2px solid #667eea;
        }
        
        .print-section h3 {
            color: #495057;
            font-size: 13pt;
            margin: 20px 0 10px 0;
            font-weight: 600;
        }
        
        .print-table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
            font-size: 10pt;
        }
        
        .print-table th {
            background: #667eea;
            color: white;
            padding: 10px 8px;
            text-align: left;
            font-weight: 600;
            border: 1px solid #5568d3;
        }
        
        .print-table td {
            padding: 8px;
            border: 1px solid #ddd;
            background: white;
        }
        
        .print-table tbody tr:nth-child(even) {
            background: #f8f9fa;
        }
        
        .print-summary {
            background: white;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 15px;
            border-left: 4px solid #667eea;
        }
        
        .print-summary strong {
            color: #667eea;
            font-size: 12pt;
        }
        
        .print-footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            text-align: center;
            font-size: 9pt;
            color: #666;
            padding: 10px;
            border-top: 1px solid #ddd;
        }
        
        .report-card {
            display: none !important;
        }
        
        .print-content {
            display: block !important;
        }
    }
    
    .print-content {
        display: none;
    }
    
    .print-header {
        display: none;
    }
</style>

<div class="reports-grid">
    <?php
    // Cases by Status
    $total_cases = array_sum(array_column($case_status, 'count'));
    if ($total_cases > 0):
    ?>
        <div class="report-card" onclick="openModal('cases-status')">
            <div class="report-card-header">
                <h3 class="report-card-title">My Cases by Status</h3>
                <i class="fas fa-folder-open report-card-icon"></i>
            </div>
            <div class="report-card-content">
                <div class="report-card-summary"><?php echo $total_cases; ?></div>
                <p class="report-card-subtitle">Total Assigned Cases</p>
                <div class="report-card-click">
                    <i class="fas fa-mouse-pointer"></i> Click to view details
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="report-card">
            <div class="report-card-header">
                <h3 class="report-card-title">My Cases by Status</h3>
                <i class="fas fa-folder-open report-card-icon"></i>
            </div>
            <div class="report-card-content">
                <div class="report-card-summary">0</div>
                <p class="report-card-subtitle">Total Assigned Cases</p>
            </div>
        </div>
    <?php endif; ?>
    
    <?php
    // Billing Summary
    $total_bills = array_sum(array_column($billing_summary, 'count'));
    $total_amount = array_sum(array_column($billing_summary, 'total'));
    if ($total_bills > 0 || $total_amount > 0):
    ?>
        <div class="report-card" onclick="openModal('billing-summary')">
            <div class="report-card-header">
                <h3 class="report-card-title">My Billing Summary</h3>
                <i class="fas fa-dollar-sign report-card-icon"></i>
            </div>
            <div class="report-card-content">
                <div class="report-card-summary">KES <?php echo number_format($total_amount ?? 0, 0); ?></div>
                <p class="report-card-subtitle"><?php echo $total_bills; ?> Total Bills</p>
                <div class="report-card-click">
                    <i class="fas fa-mouse-pointer"></i> Click to view details
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="report-card">
            <div class="report-card-header">
                <h3 class="report-card-title">My Billing Summary</h3>
                <i class="fas fa-dollar-sign report-card-icon"></i>
            </div>
            <div class="report-card-content">
                <div class="report-card-summary">KES 0</div>
                <p class="report-card-subtitle">0 Total Bills</p>
            </div>
        </div>
    <?php endif; ?>
    
    <?php
    // Cases by Type
    $total_types = count($case_types);
    if ($total_types > 0):
    ?>
        <div class="report-card" onclick="openModal('cases-type')">
            <div class="report-card-header">
                <h3 class="report-card-title">My Cases by Type</h3>
                <i class="fas fa-briefcase report-card-icon"></i>
            </div>
            <div class="report-card-content">
                <div class="report-card-summary"><?php echo $total_types; ?></div>
                <p class="report-card-subtitle">Case Types</p>
                <div class="report-card-click">
                    <i class="fas fa-mouse-pointer"></i> Click to view details
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="report-card">
            <div class="report-card-header">
                <h3 class="report-card-title">My Cases by Type</h3>
                <i class="fas fa-briefcase report-card-icon"></i>
            </div>
            <div class="report-card-content">
                <div class="report-card-summary">0</div>
                <p class="report-card-subtitle">Case Types</p>
            </div>
        </div>
    <?php endif; ?>
    
    <?php
    // Upcoming Events
    $events_count = count($detailed_upcoming_events);
    if ($events_count > 0):
    ?>
        <div class="report-card" onclick="openModal('upcoming-events')">
            <div class="report-card-header">
                <h3 class="report-card-title">My Upcoming Events</h3>
                <i class="fas fa-calendar-alt report-card-icon"></i>
            </div>
            <div class="report-card-content">
                <div class="report-card-summary"><?php echo $events_count; ?></div>
                <p class="report-card-subtitle">Next 7 Days</p>
                <div class="report-card-click">
                    <i class="fas fa-mouse-pointer"></i> Click to view details
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="report-card">
            <div class="report-card-header">
                <h3 class="report-card-title">My Upcoming Events</h3>
                <i class="fas fa-calendar-alt report-card-icon"></i>
            </div>
            <div class="report-card-content">
                <div class="report-card-summary">0</div>
                <p class="report-card-subtitle">Next 7 Days</p>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Cases by Status Modal -->
<div id="modal-cases-status" class="report-modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fas fa-folder-open"></i> My Cases by Status - Detailed Report</h3>
            <button class="modal-close" onclick="closeModal('cases-status')">&times;</button>
        </div>
        <div class="modal-body">
            <?php if (count($case_status) > 0): ?>
                <?php foreach ($case_status as $status): ?>
                    <h4 style="margin-top: 20px; margin-bottom: 10px; color: #495057;">
                        <?php echo htmlspecialchars($status['Status']); ?> (<?php echo $status['count']; ?> cases)
                    </h4>
                    <table class="modal-table">
                        <thead>
                            <tr>
                                <th>Case Name</th>
                                <th>Client</th>
                                <th>Case Type</th>
                                <th>Court</th>
                                <th>Created</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (isset($detailed_cases_by_status[$status['Status']])): ?>
                                <?php foreach ($detailed_cases_by_status[$status['Status']] as $case): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($case['CaseName']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($case['FirstName'] . ' ' . $case['LastName']); ?></td>
                                        <td><?php echo htmlspecialchars($case['CaseType']); ?></td>
                                        <td><?php echo htmlspecialchars($case['Court'] ?? '-'); ?></td>
                                        <td><?php echo date('M d, Y', strtotime($case['CreatedAt'])); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="5" style="text-align: center; color: #999;">No cases found</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                <?php endforeach; ?>
            <?php else: ?>
                <p style="text-align: center; color: #999; padding: 40px;">No cases assigned</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Billing Summary Modal -->
<div id="modal-billing-summary" class="report-modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fas fa-dollar-sign"></i> My Billing Summary - Detailed Report</h3>
            <button class="modal-close" onclick="closeModal('billing-summary')">&times;</button>
        </div>
        <div class="modal-body">
            <?php if (count($billing_summary) > 0): ?>
                <?php foreach ($billing_summary as $bill): ?>
                    <h4 style="margin-top: 20px; margin-bottom: 10px; color: #495057;">
                        <?php echo htmlspecialchars($bill['Status']); ?> - 
                        <?php echo $bill['count']; ?> bills, 
                        Total: KES <?php echo number_format($bill['total'] ?? 0, 2); ?>
                    </h4>
                    <table class="modal-table">
                        <thead>
                            <tr>
                                <th>Invoice #</th>
                                <th>Date</th>
                                <th>Client</th>
                                <th>Case</th>
                                <th>Amount</th>
                                <th>Paid</th>
                                <th>Balance</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (isset($detailed_billing_summary[$bill['Status']])): ?>
                                <?php foreach ($detailed_billing_summary[$bill['Status']] as $invoice): ?>
                                    <tr>
                                        <td><strong>#<?php echo $invoice['BillId']; ?></strong></td>
                                        <td><?php echo date('M d, Y', strtotime($invoice['Date'])); ?></td>
                                        <td><?php echo htmlspecialchars(($invoice['FirstName'] ?? '') . ' ' . ($invoice['LastName'] ?? '')); ?></td>
                                        <td><?php echo htmlspecialchars($invoice['CaseName'] ?? '-'); ?></td>
                                        <td><strong>KES <?php echo number_format($invoice['Amount'], 2); ?></strong></td>
                                        <td>KES <?php echo number_format($invoice['Deposit'] ?? 0, 2); ?></td>
                                        <td>KES <?php echo number_format($invoice['Amount'] - ($invoice['Deposit'] ?? 0), 2); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="7" style="text-align: center; color: #999;">No bills found</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                <?php endforeach; ?>
            <?php else: ?>
                <p style="text-align: center; color: #999; padding: 40px;">No billing records</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Cases by Type Modal -->
<div id="modal-cases-type" class="report-modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fas fa-briefcase"></i> My Cases by Type - Detailed Report</h3>
            <button class="modal-close" onclick="closeModal('cases-type')">&times;</button>
        </div>
        <div class="modal-body">
            <?php if (count($case_types) > 0): ?>
                <?php foreach ($case_types as $type): ?>
                    <h4 style="margin-top: 20px; margin-bottom: 10px; color: #495057;">
                        <?php echo htmlspecialchars($type['CaseType']); ?> (<?php echo $type['count']; ?> cases)
                    </h4>
                    <table class="modal-table">
                        <thead>
                            <tr>
                                <th>Case Name</th>
                                <th>Client</th>
                                <th>Court</th>
                                <th>Status</th>
                                <th>Created</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (isset($detailed_cases_by_type[$type['CaseType']])): ?>
                                <?php foreach ($detailed_cases_by_type[$type['CaseType']] as $case): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($case['CaseName']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($case['FirstName'] . ' ' . $case['LastName']); ?></td>
                                        <td><?php echo htmlspecialchars($case['Court'] ?? '-'); ?></td>
                                        <td><span class="badge badge-<?php echo strtolower($case['Status']); ?>"><?php echo htmlspecialchars($case['Status']); ?></span></td>
                                        <td><?php echo date('M d, Y', strtotime($case['CreatedAt'])); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="5" style="text-align: center; color: #999;">No cases found</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                <?php endforeach; ?>
            <?php else: ?>
                <p style="text-align: center; color: #999; padding: 40px;">No cases assigned</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Upcoming Events Modal -->
<div id="modal-upcoming-events" class="report-modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fas fa-calendar-alt"></i> My Upcoming Events - Next 7 Days</h3>
            <button class="modal-close" onclick="closeModal('upcoming-events')">&times;</button>
        </div>
        <div class="modal-body">
            <?php if (count($detailed_upcoming_events) > 0): ?>
                <table class="modal-table">
                    <thead>
                        <tr>
                            <th>Event Name</th>
                            <th>Case</th>
                            <th>Client</th>
                            <th>Date & Time</th>
                            <th>Location</th>
                            <th>Type</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($detailed_upcoming_events as $event): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($event['EventName']); ?></strong></td>
                                <td><?php echo htmlspecialchars($event['CaseName']); ?></td>
                                <td><?php echo htmlspecialchars($event['FirstName'] . ' ' . $event['LastName']); ?></td>
                                <td>
                                    <strong><?php echo date('M d, Y', strtotime($event['Date'])); ?></strong><br>
                                    <small style="color: #666;"><?php echo date('h:i A', strtotime($event['Date'])); ?></small>
                                </td>
                                <td><?php echo htmlspecialchars($event['Location'] ?? '-'); ?></td>
                                <td><?php echo htmlspecialchars($event['EventType']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p style="text-align: center; color: #999; padding: 40px;">No upcoming events in the next 7 days</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function openModal(modalId) {
    document.getElementById('modal-' + modalId).style.display = 'block';
    document.body.style.overflow = 'hidden';
}

function closeModal(modalId) {
    document.getElementById('modal-' + modalId).style.display = 'none';
    document.body.style.overflow = 'auto';
}

// Close modal when clicking outside
window.onclick = function(event) {
    if (event.target.classList.contains('report-modal')) {
        event.target.style.display = 'none';
        document.body.style.overflow = 'auto';
    }
}

// Close modal with Escape key
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        document.querySelectorAll('.report-modal').forEach(modal => {
            modal.style.display = 'none';
            document.body.style.overflow = 'auto';
        });
    }
});
</script>

<!-- Print Content (Hidden on screen, visible when printing) -->
<div class="print-content">
    <div class="print-header">
        <h1>LAW FIRM MANAGEMENT SYSTEM</h1>
        <p class="company-name">Munyoki Maheli and Company Advocates</p>
        <div class="report-info">
            <div>
                <strong>Report Type:</strong> My Assigned Cases Reports<br>
                <strong>Generated By:</strong> <?php echo htmlspecialchars($_SESSION['user_name']); ?> (Advocate)
            </div>
            <div style="text-align: right;">
                <strong>Generated On:</strong> <?php echo date('F d, Y'); ?><br>
                <strong>Time:</strong> <?php echo date('h:i A'); ?>
            </div>
        </div>
    </div>
    
    <!-- Cases by Status Report -->
    <div class="print-section">
        <h2><i class="fas fa-folder-open"></i> My Cases by Status Report</h2>
        <?php
        $total_all_cases = count($all_cases);
        ?>
        <div class="print-summary">
            <strong>Total Assigned Cases:</strong> <?php echo $total_all_cases; ?> | 
            <?php if (count($case_status) > 0): ?>
                <?php foreach ($case_status as $stat): ?>
                    <?php echo htmlspecialchars($stat['Status']); ?>: <?php echo $stat['count']; ?><?php echo ($stat !== end($case_status)) ? ' | ' : ''; ?>
                <?php endforeach; ?>
            <?php else: ?>
                No cases assigned
            <?php endif; ?>
        </div>
        
        <?php if (count($case_status) > 0): ?>
            <?php foreach ($case_status as $status): ?>
                <h3><?php echo htmlspecialchars($status['Status']); ?> Cases (<?php echo $status['count']; ?>)</h3>
                <table class="print-table">
                    <thead>
                        <tr>
                            <th>Case #</th>
                            <th>Case Name</th>
                            <th>Client Name</th>
                            <th>Case Type</th>
                            <th>Court</th>
                            <th>Created Date</th>
                            <th>Description</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (isset($detailed_cases_by_status[$status['Status']])): ?>
                            <?php foreach ($detailed_cases_by_status[$status['Status']] as $case): ?>
                                <tr>
                                    <td>#<?php echo $case['CaseNo']; ?></td>
                                    <td><strong><?php echo htmlspecialchars($case['CaseName']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($case['FirstName'] . ' ' . $case['LastName']); ?></td>
                                    <td><?php echo htmlspecialchars($case['CaseType']); ?></td>
                                    <td><?php echo htmlspecialchars($case['Court'] ?? 'N/A'); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($case['CreatedAt'])); ?></td>
                                    <td><?php echo htmlspecialchars(substr($case['Description'] ?? 'N/A', 0, 50)) . (strlen($case['Description'] ?? '') > 50 ? '...' : ''); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="7" style="text-align: center;">No cases found</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            <?php endforeach; ?>
        <?php else: ?>
            <p style="text-align: center; padding: 20px;">No cases assigned</p>
        <?php endif; ?>
    </div>
    
    <!-- Billing Summary Report -->
    <div class="print-section">
        <h2><i class="fas fa-dollar-sign"></i> My Billing Summary Report</h2>
        <?php
        $total_all_bills = count($all_bills);
        $grand_total = array_sum(array_column($billing_summary, 'total'));
        $total_deposits = 0;
        foreach ($all_bills as $bill) {
            $total_deposits += ($bill['Deposit'] ?? 0);
        }
        $total_balance = $grand_total - $total_deposits;
        ?>
        <div class="print-summary">
            <strong>Total Invoices:</strong> <?php echo $total_all_bills; ?> | 
            <strong>Total Amount:</strong> KES <?php echo number_format($grand_total, 2); ?> | 
            <strong>Total Paid:</strong> KES <?php echo number_format($total_deposits, 2); ?> | 
            <strong>Outstanding Balance:</strong> KES <?php echo number_format($total_balance, 2); ?>
        </div>
        
        <?php if (count($billing_summary) > 0): ?>
            <?php foreach ($billing_summary as $bill): ?>
                <h3><?php echo htmlspecialchars($bill['Status']); ?> Invoices (<?php echo $bill['count']; ?>) - Total: KES <?php echo number_format($bill['total'] ?? 0, 2); ?></h3>
                <table class="print-table">
                    <thead>
                        <tr>
                            <th>Invoice #</th>
                            <th>Date</th>
                            <th>Client Name</th>
                            <th>Case</th>
                            <th>Amount (KES)</th>
                            <th>Paid (KES)</th>
                            <th>Balance (KES)</th>
                            <th>Description</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (isset($detailed_billing_summary[$bill['Status']])): ?>
                            <?php foreach ($detailed_billing_summary[$bill['Status']] as $invoice): ?>
                                <tr>
                                    <td>#<?php echo $invoice['BillId']; ?></td>
                                    <td><?php echo date('M d, Y', strtotime($invoice['Date'])); ?></td>
                                    <td><?php echo htmlspecialchars(($invoice['FirstName'] ?? '') . ' ' . ($invoice['LastName'] ?? '')); ?></td>
                                    <td><?php echo htmlspecialchars($invoice['CaseName'] ?? 'N/A'); ?></td>
                                    <td><strong><?php echo number_format($invoice['Amount'], 2); ?></strong></td>
                                    <td><?php echo number_format($invoice['Deposit'] ?? 0, 2); ?></td>
                                    <td><?php echo number_format($invoice['Amount'] - ($invoice['Deposit'] ?? 0), 2); ?></td>
                                    <td><?php echo htmlspecialchars(substr($invoice['Description'] ?? 'N/A', 0, 40)) . (strlen($invoice['Description'] ?? '') > 40 ? '...' : ''); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="8" style="text-align: center;">No bills found</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            <?php endforeach; ?>
        <?php else: ?>
            <p style="text-align: center; padding: 20px;">No billing records</p>
        <?php endif; ?>
    </div>
    
    <!-- Cases by Type Report -->
    <div class="print-section">
        <h2><i class="fas fa-briefcase"></i> My Cases by Type Report</h2>
        <div class="print-summary">
            <strong>Total Case Types:</strong> <?php echo count($case_types); ?> | 
            <strong>Total Cases:</strong> <?php echo $total_all_cases; ?>
        </div>
        
        <?php if (count($case_types) > 0): ?>
            <?php foreach ($case_types as $type): ?>
                <h3><?php echo htmlspecialchars($type['CaseType']); ?> Cases (<?php echo $type['count']; ?>)</h3>
                <table class="print-table">
                    <thead>
                        <tr>
                            <th>Case #</th>
                            <th>Case Name</th>
                            <th>Client Name</th>
                            <th>Court</th>
                            <th>Status</th>
                            <th>Created Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (isset($detailed_cases_by_type[$type['CaseType']])): ?>
                            <?php foreach ($detailed_cases_by_type[$type['CaseType']] as $case): ?>
                                <tr>
                                    <td>#<?php echo $case['CaseNo']; ?></td>
                                    <td><strong><?php echo htmlspecialchars($case['CaseName']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($case['FirstName'] . ' ' . $case['LastName']); ?></td>
                                    <td><?php echo htmlspecialchars($case['Court'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($case['Status']); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($case['CreatedAt'])); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="6" style="text-align: center;">No cases found</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            <?php endforeach; ?>
        <?php else: ?>
            <p style="text-align: center; padding: 20px;">No cases assigned</p>
        <?php endif; ?>
    </div>
    
    <!-- Upcoming Events Report -->
    <div class="print-section">
        <h2><i class="fas fa-calendar-alt"></i> My Upcoming Events Report (Next 7 Days)</h2>
        <div class="print-summary">
            <strong>Total Events:</strong> <?php echo count($detailed_upcoming_events); ?> | 
            <strong>Report Period:</strong> <?php echo date('M d, Y'); ?> to <?php echo date('M d, Y', strtotime('+7 days')); ?>
        </div>
        
        <?php if (count($detailed_upcoming_events) > 0): ?>
            <table class="print-table">
                <thead>
                    <tr>
                        <th>Event Name</th>
                        <th>Event Type</th>
                        <th>Case</th>
                        <th>Client Name</th>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Location</th>
                        <th>Description</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($detailed_upcoming_events as $event): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($event['EventName']); ?></strong></td>
                            <td><?php echo htmlspecialchars($event['EventType']); ?></td>
                            <td><?php echo htmlspecialchars($event['CaseName']); ?></td>
                            <td><?php echo htmlspecialchars($event['FirstName'] . ' ' . $event['LastName']); ?></td>
                            <td><?php echo date('M d, Y', strtotime($event['Date'])); ?></td>
                            <td><?php echo date('h:i A', strtotime($event['Date'])); ?></td>
                            <td><?php echo htmlspecialchars($event['Location'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars(substr($event['Description'] ?? 'N/A', 0, 40)) . (strlen($event['Description'] ?? '') > 40 ? '...' : ''); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p style="text-align: center; padding: 20px;">No upcoming events in the next 7 days</p>
        <?php endif; ?>
    </div>
    
    <div class="print-footer">
        <p>This report was generated on <?php echo date('F d, Y \a\t h:i A'); ?> by <?php echo htmlspecialchars($_SESSION['user_name']); ?> | Page <span class="page-number"></span></p>
    </div>
</div>

<script>
// Add page numbers for print
window.onbeforeprint = function() {
    var pageNum = 1;
    document.querySelectorAll('.print-section').forEach(function(section) {
        var pageBreak = document.createElement('div');
        pageBreak.style.pageBreakBefore = 'always';
        section.insertBefore(pageBreak, section.firstChild);
    });
};
</script>

<?php include 'footer.php'; ?>

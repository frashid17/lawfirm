<?php
/**
 * RECEPTIONIST - MANAGE BILLING
 * Record and manage billing information
 */

require_once '../config/database.php';
require_once '../config/session.php';
requireRole('receptionist');

$message = "";
$error = "";

// Handle delete
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    try {
        $stmt = $conn->prepare("DELETE FROM BILLING WHERE BillId = ?");
        $stmt->execute([$_GET['delete']]);
        $message = "Bill deleted successfully";
    } catch(PDOException $e) {
        $error = "Error deleting bill: " . $e->getMessage();
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $bill_id = $_POST['bill_id'] ?? null;
    $client_id = $_POST['client_id'] ?? null;
    $case_no = $_POST['case_no'] ?? null;
    $date = $_POST['date'] ?? '';
    $amount = $_POST['amount'] ?? 0;
    $deposit = $_POST['deposit'] ?? 0;
    $installments = $_POST['installments'] ?? 0;
    $status = trim($_POST['status'] ?? 'Pending');
    $description = trim($_POST['description'] ?? '');
    
    if (empty($client_id) || empty($date)) {
        $error = "Please fill in all required fields";
    } else {
        try {
            if ($bill_id) {
                // For updates, preserve the current deposit from database to prevent overwriting client payments
                $stmt = $conn->prepare("SELECT Deposit FROM BILLING WHERE BillId = ?");
                $stmt->execute([$bill_id]);
                $current_bill = $stmt->fetch(PDO::FETCH_ASSOC);
                $deposit = $current_bill['Deposit'] ?? 0;
                
                // Update existing bill
                $stmt = $conn->prepare("UPDATE BILLING SET ClientId = ?, CaseNo = ?, Date = ?, Amount = ?, Deposit = ?, Installments = ?, Status = ?, Description = ? WHERE BillId = ?");
                $stmt->execute([$client_id, $case_no, $date, $amount, $deposit, $installments, $status, $description, $bill_id]);
                $message = "Bill updated successfully";
            } else {
                // Insert new bill
                $stmt = $conn->prepare("INSERT INTO BILLING (ClientId, CaseNo, Date, Amount, Deposit, Installments, Status, Description) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$client_id, $case_no, $date, $amount, $deposit, $installments, $status, $description]);
                $message = "Bill recorded successfully";
            }
        } catch(PDOException $e) {
            $error = "Error saving bill: " . $e->getMessage();
        }
    }
}

// Get bill for editing
$edit_bill = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    try {
        $stmt = $conn->prepare("SELECT * FROM BILLING WHERE BillId = ?");
        $stmt->execute([$_GET['edit']]);
        $edit_bill = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        $error = "Error loading bill: " . $e->getMessage();
    }
}

// Get all clients for dropdown
try {
    $stmt = $conn->query("SELECT ClientId, FirstName, LastName FROM CLIENT ORDER BY LastName, FirstName");
    $clients = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $error = "Error loading clients: " . $e->getMessage();
}

// Get all cases for dropdown
try {
    $stmt = $conn->query("SELECT CaseNo, CaseName FROM `CASE` ORDER BY CaseName");
    $cases = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $error = "Error loading cases: " . $e->getMessage();
}

// Get all bills with advocate information
try {
    $stmt = $conn->query("SELECT b.*, c.FirstName, c.LastName, cs.CaseName,
                          a.FirstName as AdvocateFirstName, a.LastName as AdvocateLastName
                          FROM BILLING b 
                          JOIN CLIENT c ON b.ClientId = c.ClientId 
                          LEFT JOIN `CASE` cs ON b.CaseNo = cs.CaseNo 
                          LEFT JOIN CASE_ASSIGNMENT ca ON cs.CaseNo = ca.CaseNo AND ca.Status = 'Active'
                          LEFT JOIN ADVOCATE a ON ca.AdvtId = a.AdvtId
                          ORDER BY b.Date DESC");
    $bills = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get payment history for each bill
    foreach ($bills as &$bill) {
        $stmt = $conn->prepare("SELECT ph.*, a.FirstName as AdvocateFirstName, a.LastName as AdvocateLastName
                               FROM PAYMENT_HISTORY ph
                               LEFT JOIN BILLING b ON ph.BillId = b.BillId
                               LEFT JOIN `CASE` c ON b.CaseNo = c.CaseNo
                               LEFT JOIN CASE_ASSIGNMENT ca ON c.CaseNo = ca.CaseNo AND ca.Status = 'Active'
                               LEFT JOIN ADVOCATE a ON ca.AdvtId = a.AdvtId
                               WHERE ph.BillId = ? 
                               ORDER BY ph.PaymentDate DESC");
        $stmt->execute([$bill['BillId']]);
        $bill['payment_history'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    unset($bill);
} catch(PDOException $e) {
    $error = "Error loading bills: " . $e->getMessage();
}

include 'header.php';
?>

<h2>Manage Billing</h2>

<?php if ($message): ?>
    <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>

<div class="card">
    <h3>All Bills</h3>
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Invoice #</th>
                    <th>Date</th>
                    <th>Client</th>
                    <th>Case</th>
                    <th>Advocate</th>
                    <th>Amount</th>
                    <th>Paid</th>
                    <th>Balance</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($bills) > 0): ?>
                    <?php foreach ($bills as $bill): ?>
                        <tr>
                            <td><strong>#<?php echo $bill['BillId']; ?></strong></td>
                            <td><?php echo date('M d, Y', strtotime($bill['Date'])); ?></td>
                            <td><?php echo htmlspecialchars($bill['FirstName'] . ' ' . $bill['LastName']); ?></td>
                            <td><?php echo htmlspecialchars($bill['CaseName'] ?? '-'); ?></td>
                            <td>
                                <?php if ($bill['AdvocateFirstName']): ?>
                                    <?php echo htmlspecialchars($bill['AdvocateFirstName'] . ' ' . $bill['AdvocateLastName']); ?>
                                <?php else: ?>
                                    <span style="color: var(--gray);">-</span>
                                <?php endif; ?>
                            </td>
                            <td>KES <?php echo number_format($bill['Amount'], 2); ?></td>
                            <td>KES <?php echo number_format($bill['Deposit'], 2); ?></td>
                            <td>KES <?php echo number_format($bill['Amount'] - $bill['Deposit'], 2); ?></td>
                            <td>
                                <span style="padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: 600;
                                    <?php
                                    if ($bill['Status'] == 'Paid' || $bill['Status'] == 'Settled') {
                                        echo 'background: #10b981; color: white;';
                                    } elseif ($bill['Status'] == 'Partially Paid') {
                                        echo 'background: #3b82f6; color: white;';
                                    } elseif ($bill['Status'] == 'Pending') {
                                        echo 'background: #f59e0b; color: white;';
                                    } else {
                                        echo 'background: #ef4444; color: white;';
                                    }
                                    ?>">
                                    <?php echo htmlspecialchars($bill['Status']); ?>
                                </span>
                            </td>
                            <td>
                                <button onclick="showPaymentHistory(<?php echo htmlspecialchars(json_encode($bill, JSON_HEX_APOS | JSON_HEX_QUOT)); ?>)" 
                                        class="btn btn-sm btn-info">
                                    <i class="fas fa-history"></i> View History
                                </button>
                                <a href="?edit=<?php echo $bill['BillId']; ?>" class="btn btn-sm btn-success">
                                    <i class="fas fa-edit"></i> Edit
                                </a>
                                <a href="?delete=<?php echo $bill['BillId']; ?>" 
                                   class="btn btn-sm btn-danger" 
                                   onclick="return confirm('Are you sure you want to delete this bill?');">
                                    <i class="fas fa-trash"></i> Delete
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="10" class="empty-state">No bills found</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="card mt-20">
    <h3><?php echo $edit_bill ? 'Edit Bill' : 'Record New Bill'; ?></h3>
    <form method="POST" action="">
        <?php if ($edit_bill): ?>
            <input type="hidden" name="bill_id" value="<?php echo htmlspecialchars($edit_bill['BillId']); ?>">
        <?php endif; ?>
        
        <div class="form-group">
            <label for="client_id">Client *</label>
            <select id="client_id" name="client_id" required>
                <option value="">-- Select Client --</option>
                <?php foreach ($clients as $client): ?>
                    <option value="<?php echo $client['ClientId']; ?>" 
                            <?php echo ($edit_bill && $edit_bill['ClientId'] == $client['ClientId']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($client['FirstName'] . ' ' . $client['LastName']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="form-group">
            <label for="case_no">Case (Optional)</label>
            <select id="case_no" name="case_no">
                <option value="">-- Select Case --</option>
                <?php foreach ($cases as $case): ?>
                    <option value="<?php echo $case['CaseNo']; ?>" 
                            <?php echo ($edit_bill && $edit_bill['CaseNo'] == $case['CaseNo']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($case['CaseName']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="form-group">
            <label for="date">Date *</label>
            <input type="date" id="date" name="date" 
                   value="<?php echo $edit_bill ? $edit_bill['Date'] : date('Y-m-d'); ?>" required>
        </div>
        
        <div class="form-group">
            <label for="amount">Total Amount</label>
            <input type="number" id="amount" name="amount" step="0.01" min="0"
                   value="<?php echo htmlspecialchars($edit_bill['Amount'] ?? '0'); ?>">
        </div>
        
        <div class="form-group">
            <label for="deposit">Deposit</label>
            <input type="number" id="deposit" name="deposit" step="0.01" min="0"
                   value="<?php echo htmlspecialchars($edit_bill['Deposit'] ?? '0'); ?>" readonly
                   style="background-color: #f5f5f5; cursor: not-allowed;">
            <small style="color: var(--gray); font-size: 12px; margin-top: 4px; display: block;">
                <i class="fas fa-info-circle"></i> This field is automatically updated when clients make payments.
            </small>
        </div>
        
        <div class="form-group">
            <label for="installments">Installments</label>
            <input type="number" id="installments" name="installments" step="0.01" min="0"
                   value="<?php echo htmlspecialchars($edit_bill['Installments'] ?? '0'); ?>">
        </div>
        
        <div class="form-group">
            <label for="status">Status</label>
            <select id="status" name="status">
                <option value="Pending" <?php echo ($edit_bill && $edit_bill['Status'] == 'Pending') ? 'selected' : ''; ?>>Pending</option>
                <option value="Partially Paid" <?php echo ($edit_bill && $edit_bill['Status'] == 'Partially Paid') ? 'selected' : ''; ?>>Partially Paid</option>
                <option value="Paid" <?php echo ($edit_bill && $edit_bill['Status'] == 'Paid') ? 'selected' : ''; ?>>Paid</option>
                <option value="Settled" <?php echo ($edit_bill && $edit_bill['Status'] == 'Settled') ? 'selected' : ''; ?>>Settled</option>
                <option value="Overdue" <?php echo ($edit_bill && $edit_bill['Status'] == 'Overdue') ? 'selected' : ''; ?>>Overdue</option>
            </select>
        </div>
        
        <div class="form-group">
            <label for="description">Description</label>
            <textarea id="description" name="description"><?php echo htmlspecialchars($edit_bill['Description'] ?? ''); ?></textarea>
        </div>
        
        <div class="form-actions">
            <button type="submit" class="btn btn-primary"><?php echo $edit_bill ? 'Update Bill' : 'Record Bill'; ?></button>
            <?php if ($edit_bill): ?>
                <a href="billing.php" class="btn btn-secondary">Cancel</a>
            <?php endif; ?>
        </div>
    </form>
</div>

<!-- Payment History Modal -->
<div id="paymentHistoryModal" class="modal" style="display: none;">
    <div class="modal-content" style="max-width: 900px;">
        <div class="modal-header">
            <h3 id="paymentHistoryTitle">Payment History</h3>
            <span class="close-modal" onclick="closePaymentHistoryModal()">&times;</span>
        </div>
        <div id="paymentHistoryContent" style="padding: 20px; max-height: 600px; overflow-y: auto;">
            <!-- Payment history will be loaded here -->
        </div>
    </div>
</div>

<style>
    .modal {
        position: fixed;
        z-index: 1000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        overflow: auto;
        background-color: rgba(0,0,0,0.5);
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .modal-content {
        background-color: white;
        margin: auto;
        padding: 0;
        border-radius: 8px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.3);
        animation: modalSlideIn 0.3s ease;
    }
    @keyframes modalSlideIn {
        from {
            transform: translateY(-50px);
            opacity: 0;
        }
        to {
            transform: translateY(0);
            opacity: 1;
        }
    }
    .modal-header {
        padding: 20px;
        border-bottom: 1px solid var(--border);
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .modal-header h3 {
        margin: 0;
        color: var(--primary-color);
    }
    .close-modal {
        font-size: 28px;
        font-weight: bold;
        color: var(--gray);
        cursor: pointer;
        transition: color 0.3s;
    }
    .close-modal:hover {
        color: var(--dark-text);
    }
</style>

<script>
    function showPaymentHistory(bill) {
        const modal = document.getElementById('paymentHistoryModal');
        const modalTitle = document.getElementById('paymentHistoryTitle');
        const modalContent = document.getElementById('paymentHistoryContent');
        
        modalTitle.textContent = `Payment History - Invoice #${bill.BillId}`;
        
        let html = `
            <div style="margin-bottom: 20px; padding: 15px; background: #f8f9fa; border-radius: 8px;">
                <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px; margin-bottom: 15px;">
                    <div>
                        <strong>Client:</strong><br>
                        ${escapeHtml(bill.FirstName + ' ' + bill.LastName)}
                    </div>
                    <div>
                        <strong>Case:</strong><br>
                        ${escapeHtml(bill.CaseName || '-')}
                    </div>
                    <div>
                        <strong>Advocate:</strong><br>
                        ${bill.AdvocateFirstName ? escapeHtml(bill.AdvocateFirstName + ' ' + bill.AdvocateLastName) : '<span style="color: var(--gray);">Not Assigned</span>'}
                    </div>
                    <div>
                        <strong>Invoice Amount:</strong><br>
                        KES ${parseFloat(bill.Amount).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}
                    </div>
                    <div>
                        <strong>Total Paid:</strong><br>
                        KES ${parseFloat(bill.Deposit).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}
                    </div>
                    <div>
                        <strong>Balance:</strong><br>
                        KES ${(parseFloat(bill.Amount) - parseFloat(bill.Deposit)).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}
                    </div>
                </div>
            </div>
        `;
        
        if (bill.payment_history && bill.payment_history.length > 0) {
            html += '<table style="width: 100%; border-collapse: collapse;">';
            html += '<thead><tr style="background: var(--primary-color); color: white;">';
            html += '<th style="padding: 12px; text-align: left;">Date & Time</th>';
            html += '<th style="padding: 12px; text-align: right;">Amount</th>';
            html += '<th style="padding: 12px; text-align: left;">Advocate</th>';
            html += '<th style="padding: 12px; text-align: left;">Notes</th>';
            html += '</tr></thead>';
            html += '<tbody>';
            
            bill.payment_history.forEach(payment => {
                const paymentDate = new Date(payment.PaymentDate);
                const dateStr = paymentDate.toLocaleDateString('en-US', { 
                    year: 'numeric', 
                    month: 'long', 
                    day: 'numeric' 
                });
                const timeStr = paymentDate.toLocaleTimeString('en-US', { 
                    hour: '2-digit', 
                    minute: '2-digit',
                    hour12: true 
                });
                
                html += `
                    <tr style="border-bottom: 1px solid #e9ecef;">
                        <td style="padding: 12px;">
                            <i class="fas fa-calendar"></i> ${dateStr}<br>
                            <small style="color: var(--gray);"><i class="fas fa-clock"></i> ${timeStr}</small>
                        </td>
                        <td style="padding: 12px; text-align: right; font-weight: 600; color: var(--primary-color);">
                            KES ${parseFloat(payment.Amount).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}
                        </td>
                        <td style="padding: 12px;">
                            ${payment.AdvocateFirstName ? escapeHtml(payment.AdvocateFirstName + ' ' + payment.AdvocateLastName) : '<span style="color: var(--gray);">-</span>'}
                        </td>
                        <td style="padding: 12px; color: var(--gray);">
                            ${payment.Notes ? escapeHtml(payment.Notes) : '-'}
                        </td>
                    </tr>
                `;
            });
            
            html += '</tbody></table>';
        } else {
            html += '<div style="text-align: center; padding: 40px; color: var(--gray);">';
            html += '<i class="fas fa-history" style="font-size: 48px; margin-bottom: 15px; opacity: 0.3;"></i>';
            html += '<p>No payment history found for this invoice.</p>';
            html += '</div>';
        }
        
        modalContent.innerHTML = html;
        modal.style.display = 'flex';
    }
    
    function closePaymentHistoryModal() {
        document.getElementById('paymentHistoryModal').style.display = 'none';
    }
    
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    // Close modal when clicking outside
    window.onclick = function(event) {
        const modal = document.getElementById('paymentHistoryModal');
        if (event.target == modal) {
            closePaymentHistoryModal();
        }
    }
    
    // Close modal on Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closePaymentHistoryModal();
        }
    });
</script>

<?php include 'footer.php'; ?>

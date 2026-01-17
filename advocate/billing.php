<?php
/**
 * ADVOCATE - BILLING/INVOICES
 * Create and manage invoices for assigned cases
 */

require_once '../config/database.php';
require_once '../config/session.php';
requireRole('advocate');

$advocate_id = $_SESSION['user_id'];
$message = "";
$error = "";

// Handle status update (mark as settled/not settled)
if (isset($_GET['toggle_status']) && is_numeric($_GET['toggle_status'])) {
    try {
        // Verify invoice belongs to advocate's assigned case
        $stmt = $conn->prepare("SELECT b.BillId, b.Status FROM BILLING b 
                               JOIN `CASE` c ON b.CaseNo = c.CaseNo 
                               JOIN CASE_ASSIGNMENT ca ON c.CaseNo = ca.CaseNo 
                               WHERE b.BillId = ? AND ca.AdvtId = ? AND ca.Status = 'Active'");
        $stmt->execute([$_GET['toggle_status'], $advocate_id]);
        $invoice = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($invoice) {
            // Get current deposit and amount to check if fully paid
            $stmt = $conn->prepare("SELECT Amount, Deposit FROM BILLING WHERE BillId = ?");
            $stmt->execute([$_GET['toggle_status']]);
            $invoice_details = $stmt->fetch(PDO::FETCH_ASSOC);
            $is_fully_paid = ($invoice_details['Deposit'] >= $invoice_details['Amount']);
            
            if ($invoice['Status'] == 'Paid' || $invoice['Status'] == 'Settled') {
                $new_status = 'Pending';
            } elseif ($is_fully_paid) {
                $new_status = 'Settled';
            } else {
                $new_status = 'Partially Paid';
            }
            
            $stmt = $conn->prepare("UPDATE BILLING SET Status = ? WHERE BillId = ?");
            $stmt->execute([$new_status, $_GET['toggle_status']]);
            $message = "Invoice status updated successfully";
        } else {
            $error = "You don't have permission to modify this invoice";
        }
    } catch(PDOException $e) {
        $error = "Error updating invoice: " . $e->getMessage();
    }
}

// Handle delete
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    try {
        // Verify invoice belongs to advocate's assigned case
        $stmt = $conn->prepare("SELECT b.BillId FROM BILLING b 
                               JOIN `CASE` c ON b.CaseNo = c.CaseNo 
                               JOIN CASE_ASSIGNMENT ca ON c.CaseNo = ca.CaseNo 
                               WHERE b.BillId = ? AND ca.AdvtId = ? AND ca.Status = 'Active'");
        $stmt->execute([$_GET['delete'], $advocate_id]);
        if ($stmt->fetch()) {
            $stmt = $conn->prepare("DELETE FROM BILLING WHERE BillId = ?");
            $stmt->execute([$_GET['delete']]);
            $message = "Invoice deleted successfully";
        } else {
            $error = "You don't have permission to delete this invoice";
        }
    } catch(PDOException $e) {
        $error = "Error deleting invoice: " . $e->getMessage();
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $bill_id = $_POST['bill_id'] ?? null;
    $case_no = $_POST['case_no'] ?? null;
    $date = $_POST['date'] ?? date('Y-m-d');
    $amount = floatval($_POST['amount'] ?? 0);
    $deposit = floatval($_POST['deposit'] ?? 0);
    $description = trim($_POST['description'] ?? '');
    
    // Handle status - preserve existing status if editing and not explicitly changed
    $status = trim($_POST['status'] ?? '');
    if ($bill_id && empty($status)) {
        // When editing, get current status from database
        try {
            $stmt = $conn->prepare("SELECT Status, Amount, Deposit FROM BILLING WHERE BillId = ?");
            $stmt->execute([$bill_id]);
            $current_invoice = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($current_invoice) {
                $status = $current_invoice['Status'];
                // Auto-update status based on payment if deposit changed
                if ($deposit != $current_invoice['Deposit']) {
                    if ($deposit >= $amount) {
                        $status = 'Paid';
                    } elseif ($deposit > 0) {
                        $status = 'Partially Paid';
                    } else {
                        $status = 'Pending';
                    }
                }
            } else {
                $status = 'Pending';
            }
        } catch(PDOException $e) {
            $status = 'Pending';
        }
    } elseif (empty($status)) {
        // For new invoices, determine status based on deposit
        if ($deposit >= $amount) {
            $status = 'Paid';
        } elseif ($deposit > 0) {
            $status = 'Partially Paid';
        } else {
            $status = 'Pending';
        }
    }
    
    if (empty($case_no) || empty($date) || $amount <= 0) {
        $error = "Please fill in all required fields (Case, Date, and Amount must be greater than 0)";
    } else {
        try {
            // Verify case is assigned to advocate
            $stmt = $conn->prepare("SELECT c.CaseNo, c.ClientId FROM `CASE` c 
                                   JOIN CASE_ASSIGNMENT ca ON c.CaseNo = ca.CaseNo 
                                   WHERE c.CaseNo = ? AND ca.AdvtId = ? AND ca.Status = 'Active'");
            $stmt->execute([$case_no, $advocate_id]);
            $case_info = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$case_info) {
                $error = "You are not assigned to this case";
            } else {
                $client_id = $case_info['ClientId'];
                
                if ($bill_id) {
                    // Verify invoice belongs to advocate's assigned case
                    $stmt = $conn->prepare("SELECT b.BillId, b.Status, b.Amount, b.Deposit FROM BILLING b 
                                          JOIN `CASE` c ON b.CaseNo = c.CaseNo 
                                          JOIN CASE_ASSIGNMENT ca ON c.CaseNo = ca.CaseNo 
                                          WHERE b.BillId = ? AND ca.AdvtId = ? AND ca.Status = 'Active'");
                    $stmt->execute([$bill_id, $advocate_id]);
                    $existing_invoice = $stmt->fetch(PDO::FETCH_ASSOC);
                    if ($existing_invoice) {
                        // CRITICAL: Always preserve the deposit from database - client payments update this field
                        // Do NOT overwrite client payments with form values
                        $current_deposit = floatval($existing_invoice['Deposit']);
                        
                        // Use the current deposit from database (client payments are stored here)
                        // Only allow advocate to manually adjust if they explicitly set a different value
                        // But since the field is now read-only, we always use database value
                        $deposit = $current_deposit;
                        
                        // Auto-determine status based on payment
                        $is_fully_paid = ($deposit >= $amount);
                        if (empty($status) || $status == 'Pending') {
                            if ($is_fully_paid) {
                                $status = 'Paid';
                            } elseif ($deposit > 0) {
                                $status = 'Partially Paid';
                            } else {
                                $status = 'Pending';
                            }
                        }
                        
                        // Update existing invoice - preserve deposit from client payments
                        $stmt = $conn->prepare("UPDATE BILLING SET CaseNo = ?, Date = ?, Amount = ?, Deposit = ?, Status = ?, Description = ? WHERE BillId = ?");
                        $stmt->execute([$case_no, $date, $amount, $deposit, $status, $description, $bill_id]);
                        
                        // Redirect to prevent duplicate submissions on refresh
                        header("Location: billing.php?updated=1&bill_id=" . $bill_id);
                        exit();
                    } else {
                        $error = "You don't have permission to edit this invoice";
                    }
                } else {
                    // Insert new invoice
                    $stmt = $conn->prepare("INSERT INTO BILLING (ClientId, CaseNo, Date, Amount, Deposit, Status, Description) VALUES (?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$client_id, $case_no, $date, $amount, $deposit, $status, $description]);
                    $new_bill_id = $conn->lastInsertId();
                    
                    // Redirect to prevent duplicate submissions on refresh
                    header("Location: billing.php?created=1&bill_id=" . $new_bill_id);
                    exit();
                }
            }
        } catch(PDOException $e) {
            $error = "Error saving invoice: " . $e->getMessage();
        }
    }
}

// Get invoice for editing
$edit_invoice = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    try {
        $stmt = $conn->prepare("SELECT b.* FROM BILLING b 
                               JOIN `CASE` c ON b.CaseNo = c.CaseNo 
                               JOIN CASE_ASSIGNMENT ca ON c.CaseNo = ca.CaseNo 
                               WHERE b.BillId = ? AND ca.AdvtId = ? AND ca.Status = 'Active'");
        $stmt->execute([$_GET['edit'], $advocate_id]);
        $edit_invoice = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$edit_invoice) {
            $error = "Invoice not found or you don't have permission to edit it";
        }
    } catch(PDOException $e) {
        $error = "Error loading invoice: " . $e->getMessage();
    }
}

// Get assigned cases for dropdown
try {
    $stmt = $conn->prepare("SELECT DISTINCT c.CaseNo, c.CaseName, cl.FirstName, cl.LastName 
                           FROM `CASE` c 
                           JOIN CLIENT cl ON c.ClientId = cl.ClientId
                           JOIN CASE_ASSIGNMENT ca ON c.CaseNo = ca.CaseNo 
                           WHERE ca.AdvtId = ? AND ca.Status = 'Active' 
                           ORDER BY c.CaseName");
    $stmt->execute([$advocate_id]);
    $cases = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $error = "Error loading cases: " . $e->getMessage();
}

// Get all invoices for assigned cases
try {
    $stmt = $conn->prepare("SELECT b.*, c.CaseName, cl.FirstName, cl.LastName 
                           FROM BILLING b 
                           JOIN `CASE` c ON b.CaseNo = c.CaseNo 
                           JOIN CLIENT cl ON b.ClientId = cl.ClientId
                           JOIN CASE_ASSIGNMENT ca ON b.CaseNo = ca.CaseNo 
                           WHERE ca.AdvtId = ? AND ca.Status = 'Active' 
                           ORDER BY b.Date DESC");
    $stmt->execute([$advocate_id]);
    $invoices = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get payment history for each invoice
    foreach ($invoices as &$invoice) {
        $stmt = $conn->prepare("SELECT * FROM PAYMENT_HISTORY WHERE BillId = ? ORDER BY PaymentDate DESC");
        $stmt->execute([$invoice['BillId']]);
        $invoice['payment_history'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    unset($invoice);
} catch(PDOException $e) {
    $error = "Error loading invoices: " . $e->getMessage();
}

// Handle success messages from redirect
if (isset($_GET['created']) && $_GET['created'] == '1') {
    $message = "Invoice created successfully";
}
if (isset($_GET['updated']) && $_GET['updated'] == '1') {
    $message = "Invoice updated successfully";
}

include 'header.php';
?>

<h2>Manage Invoices</h2>

<?php if ($message): ?>
    <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>

<div class="card">
    <h3>All Invoices</h3>
    <?php if (count($invoices) > 0): ?>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Invoice #</th>
                        <th>Date</th>
                        <th>Client</th>
                        <th>Case</th>
                        <th>Amount</th>
                        <th>Paid</th>
                        <th>Balance</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($invoices as $invoice): ?>
                        <tr>
                            <td>#<?php echo $invoice['BillId']; ?></td>
                            <td><?php echo date('M d, Y', strtotime($invoice['Date'])); ?></td>
                            <td><?php echo htmlspecialchars($invoice['FirstName'] . ' ' . $invoice['LastName']); ?></td>
                            <td><?php echo htmlspecialchars($invoice['CaseName']); ?></td>
                            <td>KES <?php echo number_format($invoice['Amount'], 2); ?></td>
                            <td>KES <?php echo number_format($invoice['Deposit'], 2); ?></td>
                            <td>KES <?php echo number_format($invoice['Amount'] - $invoice['Deposit'], 2); ?></td>
                            <td>
                                <span style="padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: 600;
                                    <?php
                                    if ($invoice['Status'] == 'Paid' || $invoice['Status'] == 'Settled') {
                                        echo 'background: #10b981; color: white;';
                                    } elseif ($invoice['Status'] == 'Partially Paid') {
                                        echo 'background: #3b82f6; color: white;';
                                    } elseif ($invoice['Status'] == 'Pending') {
                                        echo 'background: #f59e0b; color: white;';
                                    } else {
                                        echo 'background: #ef4444; color: white;';
                                    }
                                    ?>">
                                    <?php echo htmlspecialchars($invoice['Status']); ?>
                                </span>
                            </td>
                            <td>
                                <?php 
                                $balance = $invoice['Amount'] - $invoice['Deposit'];
                                $is_fully_paid = ($balance <= 0);
                                ?>
                                <?php if ($is_fully_paid && ($invoice['Status'] != 'Paid' && $invoice['Status'] != 'Settled')): ?>
                                    <a href="?toggle_status=<?php echo $invoice['BillId']; ?>" 
                                       class="btn btn-sm btn-success"
                                       title="Mark as Settled - Full payment received">
                                        <i class="fas fa-check"></i> Mark as Settled
                                    </a>
                                <?php elseif (!$is_fully_paid): ?>
                                    <span style="color: var(--gray); font-size: 12px;">
                                        <i class="fas fa-info-circle"></i> Partial: KES <?php echo number_format($invoice['Deposit'], 2); ?> paid
                                    </span>
                                <?php else: ?>
                                    <a href="?toggle_status=<?php echo $invoice['BillId']; ?>" 
                                       class="btn btn-sm btn-warning"
                                       title="Mark as Not Settled">
                                        <i class="fas fa-undo"></i> Unsettle
                                    </a>
                                <?php endif; ?>
                                <a href="?edit=<?php echo $invoice['BillId']; ?>" class="btn btn-sm btn-success" style="margin-left: 5px;">
                                    <i class="fas fa-edit"></i> Edit
                                </a>
                                <a href="?delete=<?php echo $invoice['BillId']; ?>" 
                                   class="btn btn-sm btn-danger" 
                                   onclick="return confirm('Are you sure you want to delete this invoice?');">
                                    <i class="fas fa-trash"></i> Delete
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <p class="empty-state">No invoices found. Create your first invoice below.</p>
    <?php endif; ?>
</div>

<div class="card mt-20">
    <h3><?php echo $edit_invoice ? 'Edit Invoice' : 'Create New Invoice'; ?></h3>
    <form method="POST" action="">
        <input type="hidden" name="bill_id" value="<?php echo $edit_invoice['BillId'] ?? ''; ?>">
        
        <div class="form-group">
            <label for="case_no">Case <span class="required">*</span></label>
            <select id="case_no" name="case_no" required>
                <option value="">Select Case</option>
                <?php foreach ($cases as $case): ?>
                    <option value="<?php echo $case['CaseNo']; ?>" 
                            <?php echo ($edit_invoice['CaseNo'] ?? '') == $case['CaseNo'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($case['CaseName'] . ' - ' . $case['FirstName'] . ' ' . $case['LastName']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="form-group">
            <label for="date">Invoice Date <span class="required">*</span></label>
            <input type="date" id="date" name="date" 
                   value="<?php echo $edit_invoice ? date('Y-m-d', strtotime($edit_invoice['Date'])) : date('Y-m-d'); ?>" 
                   required>
        </div>
        
        <div class="form-group">
            <label for="amount">Amount (KES) <span class="required">*</span></label>
            <input type="number" id="amount" name="amount" 
                   value="<?php echo $edit_invoice['Amount'] ?? ''; ?>" 
                   step="0.01" min="0.01" required>
        </div>
        
        <div class="form-group">
            <label for="deposit">Deposit/Paid Amount (KES)</label>
            <input type="number" id="deposit" name="deposit" 
                   value="<?php echo isset($edit_invoice['Deposit']) ? number_format($edit_invoice['Deposit'], 2, '.', '') : '0'; ?>" 
                   step="0.01" min="0" readonly
                   style="background-color: #f5f5f5; cursor: not-allowed;">
            <small style="color: var(--gray); font-size: 12px; margin-top: 4px; display: block;">
                <i class="fas fa-info-circle"></i> This field is automatically updated when clients make payments. Do not edit manually.
            </small>
        </div>
        
        <div class="form-group">
            <label for="status">Status</label>
            <select id="status" name="status">
                <option value="Pending" <?php echo ($edit_invoice['Status'] ?? 'Pending') == 'Pending' ? 'selected' : ''; ?>>Pending</option>
                <option value="Partially Paid" <?php echo ($edit_invoice['Status'] ?? '') == 'Partially Paid' ? 'selected' : ''; ?>>Partially Paid</option>
                <option value="Paid" <?php echo ($edit_invoice['Status'] ?? '') == 'Paid' ? 'selected' : ''; ?>>Paid</option>
                <option value="Settled" <?php echo ($edit_invoice['Status'] ?? '') == 'Settled' ? 'selected' : ''; ?>>Settled</option>
                <option value="Overdue" <?php echo ($edit_invoice['Status'] ?? '') == 'Overdue' ? 'selected' : ''; ?>>Overdue</option>
            </select>
        </div>
        
        <div class="form-group">
            <label for="description">Description/Service Details</label>
            <textarea id="description" name="description" rows="4" 
                      placeholder="Describe the services provided..."><?php echo htmlspecialchars($edit_invoice['Description'] ?? ''); ?></textarea>
        </div>
        
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save"></i> <?php echo $edit_invoice ? 'Update' : 'Create'; ?> Invoice
            </button>
            <?php if ($edit_invoice): ?>
                <a href="billing.php" class="btn btn-secondary">Cancel</a>
            <?php endif; ?>
        </div>
    </form>
</div>

<!-- Payment History Modal -->
<div id="paymentHistoryModal" class="modal" style="display: none;">
    <div class="modal-content" style="max-width: 800px;">
        <div class="modal-header">
            <h3 id="paymentHistoryTitle">Payment History</h3>
            <span class="close-modal" onclick="closePaymentHistoryModal()">&times;</span>
        </div>
        <div id="paymentHistoryContent" style="padding: 20px; max-height: 500px; overflow-y: auto;">
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
    function showPaymentHistory(invoice) {
        const modal = document.getElementById('paymentHistoryModal');
        const modalTitle = document.getElementById('paymentHistoryTitle');
        const modalContent = document.getElementById('paymentHistoryContent');
        
        modalTitle.textContent = `Payment History - Invoice #${invoice.BillId}`;
        
        let html = `
            <div style="margin-bottom: 20px; padding: 15px; background: #f8f9fa; border-radius: 8px;">
                <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                    <div>
                        <strong>Case:</strong> ${escapeHtml(invoice.CaseName)}<br>
                        <strong>Client:</strong> ${escapeHtml(invoice.FirstName + ' ' + invoice.LastName)}
                    </div>
                    <div>
                        <strong>Invoice Amount:</strong> KES ${parseFloat(invoice.Amount).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}
                    </div>
                </div>
                <div style="display: flex; justify-content: space-between;">
                    <div>
                        <strong>Total Paid:</strong> KES ${parseFloat(invoice.Deposit).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}
                    </div>
                    <div>
                        <strong>Balance:</strong> KES ${(parseFloat(invoice.Amount) - parseFloat(invoice.Deposit)).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}
                    </div>
                </div>
            </div>
        `;
        
        if (invoice.payment_history && invoice.payment_history.length > 0) {
            html += '<table style="width: 100%; border-collapse: collapse;">';
            html += '<thead><tr style="background: var(--primary-color); color: white;"><th style="padding: 12px; text-align: left;">Date & Time</th><th style="padding: 12px; text-align: right;">Amount</th><th style="padding: 12px; text-align: left;">Notes</th></tr></thead>';
            html += '<tbody>';
            
            invoice.payment_history.forEach(payment => {
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

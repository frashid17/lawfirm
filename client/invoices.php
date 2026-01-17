<?php
/**
 * CLIENT INVOICES
 * View invoices for client's cases
 */

session_start();
require_once '../config/database.php';

if (!isset($_SESSION['client_id'])) {
    header("Location: login.php");
    exit();
}

$client_id = $_SESSION['client_id'];
$message = "";
$error = "";

// Handle payment submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['pay_invoice'])) {
    $bill_id = intval($_POST['bill_id'] ?? 0);
    $payment_amount = floatval($_POST['payment_amount'] ?? 0);
    
    if ($bill_id <= 0 || $payment_amount <= 0) {
        $error = "Invalid payment amount. Please enter a valid amount.";
    } else {
        try {
            // Verify invoice belongs to client
            $stmt = $conn->prepare("SELECT b.*, c.ClientId 
                                   FROM BILLING b 
                                   JOIN `CASE` c ON b.CaseNo = c.CaseNo 
                                   WHERE b.BillId = ? AND c.ClientId = ?");
            $stmt->execute([$bill_id, $client_id]);
            $invoice = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$invoice) {
                $error = "Invoice not found or you don't have permission to pay this invoice";
            } else {
                $current_deposit = floatval($invoice['Deposit']);
                $invoice_amount = floatval($invoice['Amount']);
                
                // Check if invoice is already fully paid
                if ($current_deposit >= $invoice_amount) {
                    $error = "This invoice is already fully paid.";
                } else {
                    $new_deposit = $current_deposit + $payment_amount;
                    
                    // Prevent overpayment
                    if ($new_deposit > $invoice_amount) {
                        $error = "Payment amount exceeds the invoice balance. Maximum payment: KES " . number_format($invoice_amount - $current_deposit, 2);
                    } else {
                        // Use a transaction to ensure atomicity and prevent race conditions
                        $conn->beginTransaction();
                        try {
                            // Re-fetch the invoice with a lock to prevent concurrent updates
                            $stmt = $conn->prepare("SELECT Deposit FROM BILLING WHERE BillId = ? FOR UPDATE");
                            $stmt->execute([$bill_id]);
                            $locked_invoice = $stmt->fetch(PDO::FETCH_ASSOC);
                            
                            if ($locked_invoice) {
                                $locked_deposit = floatval($locked_invoice['Deposit']);
                                
                                // Double-check to prevent duplicate payment
                                if ($locked_deposit != $current_deposit) {
                                    $conn->rollBack();
                                    $error = "Invoice balance has changed. Please refresh and try again.";
                                } else {
                                    // Update deposit
                                    $stmt = $conn->prepare("UPDATE BILLING SET Deposit = ? WHERE BillId = ?");
                                    $stmt->execute([$new_deposit, $bill_id]);
                                    
                                    // Record payment in history
                                    $stmt = $conn->prepare("INSERT INTO PAYMENT_HISTORY (BillId, Amount, PaymentDate, Notes) VALUES (?, ?, NOW(), ?)");
                                    $payment_note = "Payment of KES " . number_format($payment_amount, 2) . " recorded by client";
                                    $stmt->execute([$bill_id, $payment_amount, $payment_note]);
                                    
                                    $conn->commit();
                    
                                    // Auto-update status if fully paid
                                    if ($new_deposit >= $invoice_amount) {
                                        $stmt = $conn->prepare("UPDATE BILLING SET Status = 'Paid' WHERE BillId = ?");
                                        $stmt->execute([$bill_id]);
                                        $success_message = "Payment of KES " . number_format($payment_amount, 2) . " recorded successfully. Invoice is now fully paid!";
                                    } else {
                                        $stmt = $conn->prepare("UPDATE BILLING SET Status = 'Partially Paid' WHERE BillId = ?");
                                        $stmt->execute([$bill_id]);
                                        $success_message = "Payment of KES " . number_format($payment_amount, 2) . " recorded successfully. Balance remaining: KES " . number_format($invoice_amount - $new_deposit, 2);
                                    }
                                    
                                    // Redirect to prevent duplicate submissions on refresh
                                    header("Location: invoices.php?payment_success=1&amount=" . urlencode($payment_amount) . "&balance=" . urlencode($invoice_amount - $new_deposit) . "&paid=" . ($new_deposit >= $invoice_amount ? '1' : '0'));
                                    exit();
                                }
                            } else {
                                $conn->rollBack();
                                $error = "Invoice not found.";
                            }
                        } catch(PDOException $e) {
                            $conn->rollBack();
                            throw $e;
                        }
                    }
                }
            }
        } catch(PDOException $e) {
            $error = "Error processing payment: " . $e->getMessage();
        }
    }
}

// Handle success message from redirect
if (isset($_GET['payment_success']) && $_GET['payment_success'] == '1') {
    $payment_amount = floatval($_GET['amount'] ?? 0);
    $balance = floatval($_GET['balance'] ?? 0);
    $is_fully_paid = isset($_GET['paid']) && $_GET['paid'] == '1';
    
    if ($is_fully_paid) {
        $message = "Payment of KES " . number_format($payment_amount, 2) . " recorded successfully. Invoice is now fully paid!";
    } else {
        $message = "Payment of KES " . number_format($payment_amount, 2) . " recorded successfully. Balance remaining: KES " . number_format($balance, 2);
    }
}

try {
    $stmt = $conn->prepare("SELECT b.*, c.CaseName 
                            FROM BILLING b 
                            JOIN `CASE` c ON b.CaseNo = c.CaseNo 
                            WHERE c.ClientId = ? 
                            ORDER BY b.Date DESC");
    $stmt->execute([$client_id]);
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoices - Client Portal</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="header">
        <div class="header-content">
            <h1>My Invoices - Client Portal</h1>
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
                <li><a href="my_cases.php"><i class="fas fa-folder-open"></i> My Cases</a></li>
                <li><a href="invoices.php" class="active"><i class="fas fa-file-invoice-dollar"></i> Invoices</a></li>
                <li><a href="payment_history.php"><i class="fas fa-history"></i> Payment History</a></li>
                <li><a href="documents.php"><i class="fas fa-file"></i> Documents</a></li>
                <li><a href="messages.php"><i class="fas fa-comments"></i> Messages</a></li>
                <li><a href="events.php"><i class="fas fa-calendar-alt"></i> Events</a></li>
            </ul>
        </div>
    </div>
    
    <div class="container">
        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <div class="card">
            <h3>My Invoices</h3>
            <?php if (isset($invoices) && count($invoices) > 0): ?>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Invoice #</th>
                                <th>Date</th>
                                <th>Case</th>
                                <th>Description</th>
                                <th>Amount (KES)</th>
                                <th>Paid (KES)</th>
                                <th>Balance (KES)</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($invoices as $invoice): ?>
                                <tr>
                                    <td><strong>#<?php echo $invoice['BillId']; ?></strong></td>
                                    <td><?php echo date('M d, Y', strtotime($invoice['Date'])); ?></td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($invoice['CaseName']); ?></strong>
                                    </td>
                                    <td>
                                        <?php echo htmlspecialchars($invoice['Description'] ? substr($invoice['Description'], 0, 50) . (strlen($invoice['Description']) > 50 ? '...' : '') : '-'); ?>
                                    </td>
                                    <td><strong>KES <?php echo number_format($invoice['Amount'], 2); ?></strong></td>
                                    <td>KES <?php echo number_format($invoice['Deposit'], 2); ?></td>
                                    <td><strong>KES <?php echo number_format($invoice['Amount'] - $invoice['Deposit'], 2); ?></strong></td>
                                    <td>
                                        <span style="padding: 6px 12px; border-radius: 6px; font-size: 12px; font-weight: 600;
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
                                            <i class="fas fa-<?php 
                                                if ($invoice['Status'] == 'Paid' || $invoice['Status'] == 'Settled') echo 'check-circle';
                                                elseif ($invoice['Status'] == 'Partially Paid') echo 'clock';
                                                elseif ($invoice['Status'] == 'Pending') echo 'clock';
                                                else echo 'exclamation-circle';
                                            ?>"></i> <?php echo htmlspecialchars($invoice['Status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php 
                                        $balance = $invoice['Amount'] - $invoice['Deposit'];
                                        if ($balance > 0): 
                                        ?>
                                            <button onclick="openPaymentModal(<?php echo $invoice['BillId']; ?>, <?php echo $invoice['Amount']; ?>, <?php echo $invoice['Deposit']; ?>, <?php echo $balance; ?>)" 
                                                    class="btn btn-sm btn-primary">
                                                <i class="fas fa-money-bill-wave"></i> Pay
                                            </button>
                                        <?php else: ?>
                                            <span style="color: #10b981; font-weight: 600;">
                                                <i class="fas fa-check-circle"></i> Fully Paid
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="empty-state">No invoices found</p>
            <?php endif; ?>
        </div>
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
    
    <!-- Payment Modal -->
    <div id="paymentModal" class="modal" style="display: none;">
        <div class="modal-content" style="max-width: 500px;">
            <div class="modal-header">
                <h3>Make Payment</h3>
                <span class="close-modal" onclick="closePaymentModal()">&times;</span>
            </div>
            <form method="POST" action="" id="paymentForm">
                <input type="hidden" name="pay_invoice" value="1">
                <input type="hidden" name="bill_id" id="payment_bill_id">
                
                <div class="form-group">
                    <label>Invoice Amount:</label>
                    <div style="font-size: 18px; font-weight: 600; color: var(--primary-color);">
                        KES <span id="payment_invoice_amount">0.00</span>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Amount Already Paid:</label>
                    <div style="font-size: 16px; color: var(--gray);">
                        KES <span id="payment_already_paid">0.00</span>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Balance Remaining:</label>
                    <div style="font-size: 16px; font-weight: 600; color: var(--secondary-color);">
                        KES <span id="payment_balance">0.00</span>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="payment_amount">Payment Amount (KES) <span class="required">*</span></label>
                    <input type="number" id="payment_amount" name="payment_amount" 
                           step="0.01" min="0.01" required
                           placeholder="Enter amount to pay">
                    <small style="color: var(--gray); font-size: 12px; margin-top: 4px; display: block;">
                        <i class="fas fa-info-circle"></i> Maximum: <span id="payment_max_amount">0.00</span> KES
                    </small>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-check"></i> Confirm Payment
                    </button>
                    <button type="button" class="btn btn-secondary" onclick="closePaymentModal()">
                        Cancel
                    </button>
                </div>
            </form>
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
        #paymentForm {
            padding: 20px;
        }
    </style>
    
    <script>
        function openPaymentModal(billId, invoiceAmount, alreadyPaid, balance) {
            document.getElementById('payment_bill_id').value = billId;
            document.getElementById('payment_invoice_amount').textContent = parseFloat(invoiceAmount).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
            document.getElementById('payment_already_paid').textContent = parseFloat(alreadyPaid).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
            document.getElementById('payment_balance').textContent = parseFloat(balance).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
            document.getElementById('payment_max_amount').textContent = parseFloat(balance).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
            document.getElementById('payment_amount').max = balance;
            document.getElementById('payment_amount').value = '';
            document.getElementById('paymentModal').style.display = 'flex';
        }
        
        function closePaymentModal() {
            document.getElementById('paymentModal').style.display = 'none';
        }
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('paymentModal');
            if (event.target == modal) {
                closePaymentModal();
            }
        }
        
        // Validate payment amount
        document.getElementById('payment_amount').addEventListener('input', function() {
            const maxAmount = parseFloat(this.max);
            const enteredAmount = parseFloat(this.value);
            if (enteredAmount > maxAmount) {
                this.setCustomValidity('Payment amount cannot exceed the balance of KES ' + maxAmount.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
            } else {
                this.setCustomValidity('');
            }
        });
        
        function showPaymentHistory(invoice) {
            const modal = document.getElementById('paymentHistoryModal');
            const modalTitle = document.getElementById('paymentHistoryTitle');
            const modalContent = document.getElementById('paymentHistoryContent');
            
            modalTitle.textContent = `Payment History - Invoice #${invoice.BillId}`;
            
            let html = `
                <div style="margin-bottom: 20px; padding: 15px; background: #f8f9fa; border-radius: 8px;">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                        <div>
                            <strong>Case:</strong> ${escapeHtml(invoice.CaseName)}
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
        
        // Close payment history modal when clicking outside
        window.addEventListener('click', function(event) {
            const paymentHistoryModal = document.getElementById('paymentHistoryModal');
            if (event.target == paymentHistoryModal) {
                closePaymentHistoryModal();
            }
        });
        
        // Close modal on Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closePaymentModal();
                closePaymentHistoryModal();
            }
        });
    </script>
</body>
</html>

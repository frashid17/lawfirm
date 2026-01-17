<?php
/**
 * ADVOCATE PAYMENT HISTORY
 * View payment history for all invoices
 */

require_once '../config/database.php';
require_once '../config/session.php';
requireRole('advocate');

$advocate_id = $_SESSION['user_id'];
$error = "";

try {
    // Get all invoices for assigned cases with payment history
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
    $all_payments = [];
    foreach ($invoices as $invoice) {
        $stmt = $conn->prepare("SELECT ph.*, b.BillId, b.Amount as InvoiceAmount, c.CaseName, cl.FirstName, cl.LastName 
                               FROM PAYMENT_HISTORY ph 
                               JOIN BILLING b ON ph.BillId = b.BillId
                               JOIN `CASE` c ON b.CaseNo = c.CaseNo
                               JOIN CLIENT cl ON b.ClientId = cl.ClientId
                               WHERE ph.BillId = ? 
                               ORDER BY ph.PaymentDate DESC");
        $stmt->execute([$invoice['BillId']]);
        $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($payments as $payment) {
            $all_payments[] = $payment;
        }
    }
    
    // Sort all payments by date (newest first)
    usort($all_payments, function($a, $b) {
        return strtotime($b['PaymentDate']) - strtotime($a['PaymentDate']);
    });
    
} catch(PDOException $e) {
    $error = "Error loading payment history: " . $e->getMessage();
}

include 'header.php';
?>

<h2>Payment History</h2>

<?php if ($error): ?>
    <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>

<div class="card">
    <h3>All Payment Transactions</h3>
    <?php if (count($all_payments) > 0): ?>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Date & Time</th>
                        <th>Invoice #</th>
                        <th>Client</th>
                        <th>Case</th>
                        <th>Amount Paid</th>
                        <th>Invoice Amount</th>
                        <th>Notes</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($all_payments as $payment): ?>
                        <tr>
                            <td>
                                <i class="fas fa-calendar"></i> <?php echo date('M d, Y', strtotime($payment['PaymentDate'])); ?><br>
                                <small style="color: var(--gray);"><i class="fas fa-clock"></i> <?php echo date('h:i A', strtotime($payment['PaymentDate'])); ?></small>
                            </td>
                            <td><strong>#<?php echo $payment['BillId']; ?></strong></td>
                            <td><?php echo htmlspecialchars($payment['FirstName'] . ' ' . $payment['LastName']); ?></td>
                            <td><?php echo htmlspecialchars($payment['CaseName']); ?></td>
                            <td style="font-weight: 600; color: var(--primary-color);">
                                KES <?php echo number_format($payment['Amount'], 2); ?>
                            </td>
                            <td>KES <?php echo number_format($payment['InvoiceAmount'], 2); ?></td>
                            <td style="color: var(--gray);">
                                <?php echo htmlspecialchars($payment['Notes'] ?? '-'); ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <p class="empty-state">
            <i class="fas fa-history" style="font-size: 48px; margin-bottom: 15px; opacity: 0.3;"></i><br>
            No payment history found.
        </p>
    <?php endif; ?>
</div>

<!-- Summary Card -->
<?php if (count($all_payments) > 0): 
    $total_paid = array_sum(array_column($all_payments, 'Amount'));
    $unique_invoices = count(array_unique(array_column($all_payments, 'BillId')));
?>
<div class="card mt-20">
    <h3>Payment Summary</h3>
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px;">
        <div style="padding: 20px; background: #f8f9fa; border-radius: 8px; text-align: center;">
            <div style="font-size: 32px; font-weight: 600; color: var(--primary-color);">
                <?php echo count($all_payments); ?>
            </div>
            <div style="color: var(--gray); margin-top: 5px;">Total Transactions</div>
        </div>
        <div style="padding: 20px; background: #f8f9fa; border-radius: 8px; text-align: center;">
            <div style="font-size: 32px; font-weight: 600; color: var(--primary-color);">
                KES <?php echo number_format($total_paid, 2); ?>
            </div>
            <div style="color: var(--gray); margin-top: 5px;">Total Amount Received</div>
        </div>
        <div style="padding: 20px; background: #f8f9fa; border-radius: 8px; text-align: center;">
            <div style="font-size: 32px; font-weight: 600; color: var(--primary-color);">
                <?php echo $unique_invoices; ?>
            </div>
            <div style="color: var(--gray); margin-top: 5px;">Invoices with Payments</div>
        </div>
    </div>
</div>
<?php endif; ?>

<?php include 'footer.php'; ?>

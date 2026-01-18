<?php
/**
 * CLIENT PAYMENT HISTORY
 * View payment history for all invoices
 */

session_start();
require_once '../config/database.php';

if (!isset($_SESSION['client_id'])) {
    header("Location: login.php");
    exit();
}

$client_id = $_SESSION['client_id'];
$error = "";

try {
    // Get all invoices with payment history
    $stmt = $conn->prepare("SELECT b.*, c.CaseName 
                            FROM BILLING b 
                            JOIN `CASE` c ON b.CaseNo = c.CaseNo 
                            WHERE c.ClientId = ? 
                            ORDER BY b.Date DESC");
    $stmt->execute([$client_id]);
    $invoices = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get payment history for each invoice
    $all_payments = [];
    foreach ($invoices as $invoice) {
        $stmt = $conn->prepare("SELECT ph.*, b.BillId, b.Amount as InvoiceAmount, c.CaseName 
                               FROM PAYMENT_HISTORY ph 
                               JOIN BILLING b ON ph.BillId = b.BillId
                               JOIN `CASE` c ON b.CaseNo = c.CaseNo
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment History - Client Portal</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="header">
        <div class="header-content">
            <h1>Payment History - Client Portal</h1>
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
                <li><a href="invoices.php"><i class="fas fa-file-invoice-dollar"></i> Invoices</a></li>
                <li><a href="payment_history.php" class="active"><i class="fas fa-history"></i> Payment History</a></li>
                <li><a href="documents.php"><i class="fas fa-file"></i> Documents</a></li>
                <li><a href="messages.php"><i class="fas fa-comments"></i> Messages</a></li>
                <li><a href="events.php"><i class="fas fa-calendar-alt"></i> Events</a></li>
                <li><a href="profile.php"><i class="fas fa-user"></i> Profile</a></li>
            </ul>
        </div>
    </div>
    <div class="container">
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
                    <div style="color: var(--gray); margin-top: 5px;">Total Amount Paid</div>
                </div>
                <div style="padding: 20px; background: #f8f9fa; border-radius: 8px; text-align: center;">
                    <div style="font-size: 32px; font-weight: 600; color: var(--primary-color);">
                        <?php echo $unique_invoices; ?>
                    </div>
                    <div style="color: var(--gray); margin-top: 5px;">Invoices Paid</div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>

<?php
/**
 * ADMIN - CASE ASSIGNMENTS
 * Assign cases to advocates
 */

require_once '../config/database.php';
require_once '../config/session.php';
requireRole('admin');

$message = "";
$error = "";

// Handle delete
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    try {
        $stmt = $conn->prepare("DELETE FROM CASE_ASSIGNMENT WHERE AssId = ?");
        $stmt->execute([$_GET['delete']]);
        $message = "Assignment removed successfully";
    } catch(PDOException $e) {
        $error = "Error removing assignment: " . $e->getMessage();
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ass_id = $_POST['ass_id'] ?? null;
    $case_no = $_POST['case_no'] ?? null;
    $advt_id = $_POST['advt_id'] ?? null;
    $status = trim($_POST['status'] ?? 'Active');
    $notes = trim($_POST['notes'] ?? '');
    
    if (empty($case_no) || empty($advt_id)) {
        $error = "Please select both case and advocate";
    } else {
        try {
            if ($ass_id) {
                // Update existing assignment
                $stmt = $conn->prepare("UPDATE CASE_ASSIGNMENT SET CaseNo = ?, AdvtId = ?, Status = ?, Notes = ? WHERE AssId = ?");
                $stmt->execute([$case_no, $advt_id, $status, $notes, $ass_id]);
                $message = "Assignment updated successfully";
            } else {
                // Insert new assignment
                $stmt = $conn->prepare("INSERT INTO CASE_ASSIGNMENT (CaseNo, AdvtId, AssignedDate, Status, Notes) VALUES (?, ?, CURDATE(), ?, ?)");
                $stmt->execute([$case_no, $advt_id, $status, $notes]);
                $message = "Case assigned successfully";
            }
        } catch(PDOException $e) {
            if ($e->getCode() == 23000) {
                $error = "This case is already assigned to this advocate";
            } else {
                $error = "Error saving assignment: " . $e->getMessage();
            }
        }
    }
}

// Get assignment for editing
$edit_assignment = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    try {
        $stmt = $conn->prepare("SELECT * FROM CASE_ASSIGNMENT WHERE AssId = ?");
        $stmt->execute([$_GET['edit']]);
        $edit_assignment = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        $error = "Error loading assignment: " . $e->getMessage();
    }
}

// Get all cases for dropdown
try {
    $stmt = $conn->query("SELECT CaseNo, CaseName FROM `CASE` ORDER BY CaseName");
    $cases = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $error = "Error loading cases: " . $e->getMessage();
}

// Get all active advocates for dropdown
try {
    $stmt = $conn->query("SELECT AdvtId, FirstName, LastName FROM ADVOCATE WHERE Status = 'Active' ORDER BY LastName, FirstName");
    $advocates = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $error = "Error loading advocates: " . $e->getMessage();
}

// Get all assignments
try {
    $stmt = $conn->query("SELECT ca.*, c.CaseName, a.FirstName as AdvFirstName, a.LastName as AdvLastName 
                          FROM CASE_ASSIGNMENT ca 
                          JOIN `CASE` c ON ca.CaseNo = c.CaseNo 
                          JOIN ADVOCATE a ON ca.AdvtId = a.AdvtId 
                          ORDER BY ca.AssignedDate DESC");
    $assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $error = "Error loading assignments: " . $e->getMessage();
}

include 'header.php';
?>

<h2>Case Assignments</h2>

<?php if ($message): ?>
    <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>

<div class="form-container">
    <h3><?php echo $edit_assignment ? 'Edit Assignment' : 'Assign Case to Advocate'; ?></h3>
    <form method="POST" action="">
        <?php if ($edit_assignment): ?>
            <input type="hidden" name="ass_id" value="<?php echo htmlspecialchars($edit_assignment['AssId']); ?>">
        <?php endif; ?>
        
        <div class="form-group">
            <label for="case_no">Case *</label>
            <select id="case_no" name="case_no" required>
                <option value="">-- Select Case --</option>
                <?php foreach ($cases as $case): ?>
                    <option value="<?php echo $case['CaseNo']; ?>" 
                            <?php echo ($edit_assignment && $edit_assignment['CaseNo'] == $case['CaseNo']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($case['CaseName']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="form-group">
            <label for="advt_id">Advocate *</label>
            <select id="advt_id" name="advt_id" required>
                <option value="">-- Select Advocate --</option>
                <?php foreach ($advocates as $advocate): ?>
                    <option value="<?php echo $advocate['AdvtId']; ?>" 
                            <?php echo ($edit_assignment && $edit_assignment['AdvtId'] == $advocate['AdvtId']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($advocate['FirstName'] . ' ' . $advocate['LastName']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="form-group">
            <label for="status">Status</label>
            <select id="status" name="status">
                <option value="Active" <?php echo ($edit_assignment && $edit_assignment['Status'] == 'Active') ? 'selected' : ''; ?>>Active</option>
                <option value="Completed" <?php echo ($edit_assignment && $edit_assignment['Status'] == 'Completed') ? 'selected' : ''; ?>>Completed</option>
            </select>
        </div>
        
        <div class="form-group">
            <label for="notes">Notes</label>
            <textarea id="notes" name="notes"><?php echo htmlspecialchars($edit_assignment['Notes'] ?? ''); ?></textarea>
        </div>
        
        <div class="form-actions">
            <button type="submit" class="btn btn-primary"><?php echo $edit_assignment ? 'Update Assignment' : 'Assign Case'; ?></button>
            <?php if ($edit_assignment): ?>
                <a href="assignments.php" class="btn btn-secondary">Cancel</a>
            <?php endif; ?>
        </div>
    </form>
</div>

<div class="card mt-20">
    <h3>All Case Assignments</h3>
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Assignment ID</th>
                    <th>Case</th>
                    <th>Advocate</th>
                    <th>Assigned Date</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($assignments) > 0): ?>
                    <?php foreach ($assignments as $assignment): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($assignment['AssId']); ?></td>
                            <td><?php echo htmlspecialchars($assignment['CaseName']); ?></td>
                            <td><?php echo htmlspecialchars($assignment['AdvFirstName'] . ' ' . $assignment['AdvLastName']); ?></td>
                            <td><?php echo htmlspecialchars($assignment['AssignedDate']); ?></td>
                            <td><?php echo htmlspecialchars($assignment['Status']); ?></td>
                            <td>
                                <a href="?edit=<?php echo $assignment['AssId']; ?>" class="btn btn-sm btn-success">Edit</a>
                                <a href="?delete=<?php echo $assignment['AssId']; ?>" 
                                   class="btn btn-sm btn-danger" 
                                   onclick="return confirm('Are you sure you want to remove this assignment?');">Remove</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" class="empty-state">No assignments found</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'footer.php'; ?>

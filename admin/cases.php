<?php
/**
 * ADMIN - MANAGE CASES
 * View, add, edit, and delete cases
 */

require_once '../config/database.php';
require_once '../config/session.php';
requireRole('admin');

$message = "";
$error = "";

// Handle delete
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    try {
        $stmt = $conn->prepare("DELETE FROM `CASE` WHERE CaseNo = ?");
        $stmt->execute([$_GET['delete']]);
        $message = "Case deleted successfully";
    } catch(PDOException $e) {
        $error = "Error deleting case: " . $e->getMessage();
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $case_no = $_POST['case_no'] ?? null;
    $case_name = trim($_POST['case_name'] ?? '');
    $case_type = trim($_POST['case_type'] ?? '');
    $court = trim($_POST['court'] ?? '');
    $client_id = $_POST['client_id'] ?? null;
    $status = trim($_POST['status'] ?? 'Active');
    $description = trim($_POST['description'] ?? '');
    
    if (empty($case_name) || empty($case_type) || empty($client_id)) {
        $error = "Please fill in all required fields";
    } else {
        try {
            if ($case_no) {
                // Update existing case
                $stmt = $conn->prepare("UPDATE `CASE` SET CaseName = ?, CaseType = ?, Court = ?, ClientId = ?, Status = ?, Description = ? WHERE CaseNo = ?");
                $stmt->execute([$case_name, $case_type, $court, $client_id, $status, $description, $case_no]);
                $message = "Case updated successfully";
            } else {
                // Insert new case
                $stmt = $conn->prepare("INSERT INTO `CASE` (CaseName, CaseType, Court, ClientId, Status, Description) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$case_name, $case_type, $court, $client_id, $status, $description]);
                $message = "Case added successfully";
            }
        } catch(PDOException $e) {
            $error = "Error saving case: " . $e->getMessage();
        }
    }
}

// Get case for editing
$edit_case = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    try {
        $stmt = $conn->prepare("SELECT * FROM `CASE` WHERE CaseNo = ?");
        $stmt->execute([$_GET['edit']]);
        $edit_case = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        $error = "Error loading case: " . $e->getMessage();
    }
}

// Get all clients for dropdown
try {
    $stmt = $conn->query("SELECT ClientId, FirstName, LastName FROM CLIENT ORDER BY LastName, FirstName");
    $clients = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $error = "Error loading clients: " . $e->getMessage();
}

// Get all advocates for assignment dropdown
try {
    $stmt = $conn->query("SELECT AdvtId, FirstName, LastName FROM ADVOCATE WHERE Status = 'Active' ORDER BY LastName, FirstName");
    $advocates = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $error = "Error loading advocates: " . $e->getMessage();
}

// Get current assignments for the case being edited
$current_assignments = [];
if ($edit_case) {
    try {
        $stmt = $conn->prepare("SELECT ca.*, a.FirstName, a.LastName FROM CASE_ASSIGNMENT ca JOIN ADVOCATE a ON ca.AdvtId = a.AdvtId WHERE ca.CaseNo = ?");
        $stmt->execute([$edit_case['CaseNo']]);
        $current_assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        // Silent fail
    }
}

// Handle assignment addition
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_assignment'])) {
    $case_no = $_POST['case_no'] ?? null;
    $advt_id = $_POST['advt_id'] ?? null;
    
    if (empty($case_no) || empty($advt_id)) {
        $error = "Please select an advocate";
    } else {
        try {
            // Check if assignment already exists
            $stmt = $conn->prepare("SELECT AssId FROM CASE_ASSIGNMENT WHERE CaseNo = ? AND AdvtId = ?");
            $stmt->execute([$case_no, $advt_id]);
            if ($stmt->fetch()) {
                $error = "This case is already assigned to this advocate";
            } else {
                $stmt = $conn->prepare("INSERT INTO CASE_ASSIGNMENT (CaseNo, AdvtId, AssignedDate, Status) VALUES (?, ?, CURDATE(), 'Active')");
                $stmt->execute([$case_no, $advt_id]);
                $message = "Advocate assigned successfully";
                // Reload assignments
                $stmt = $conn->prepare("SELECT ca.*, a.FirstName, a.LastName FROM CASE_ASSIGNMENT ca JOIN ADVOCATE a ON ca.AdvtId = a.AdvtId WHERE ca.CaseNo = ?");
                $stmt->execute([$case_no]);
                $current_assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
        } catch(PDOException $e) {
            $error = "Error assigning advocate: " . $e->getMessage();
        }
    }
}

// Handle assignment removal
if (isset($_GET['remove_assignment']) && is_numeric($_GET['remove_assignment'])) {
    try {
        $stmt = $conn->prepare("DELETE FROM CASE_ASSIGNMENT WHERE AssId = ?");
        $stmt->execute([$_GET['remove_assignment']]);
        $message = "Assignment removed successfully";
        // Reload assignments if editing a case
        if ($edit_case) {
            $stmt = $conn->prepare("SELECT ca.*, a.FirstName, a.LastName FROM CASE_ASSIGNMENT ca JOIN ADVOCATE a ON ca.AdvtId = a.AdvtId WHERE ca.CaseNo = ?");
            $stmt->execute([$edit_case['CaseNo']]);
            $current_assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    } catch(PDOException $e) {
        $error = "Error removing assignment: " . $e->getMessage();
    }
}

// Get all cases with search
try {
    $search = $_GET['search'] ?? '';
    if (!empty($search)) {
        $search_term = "%{$search}%";
        $stmt = $conn->prepare("SELECT c.*, cl.FirstName, cl.LastName 
                                FROM `CASE` c 
                                JOIN CLIENT cl ON c.ClientId = cl.ClientId 
                                WHERE c.CaseName LIKE ? 
                                OR c.CaseType LIKE ? 
                                OR cl.FirstName LIKE ? 
                                OR cl.LastName LIKE ?
                                ORDER BY c.CreatedAt DESC");
        $stmt->execute([$search_term, $search_term, $search_term, $search_term]);
        $cases = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $stmt = $conn->query("SELECT c.*, cl.FirstName, cl.LastName 
                              FROM `CASE` c 
                              JOIN CLIENT cl ON c.ClientId = cl.ClientId 
                              ORDER BY c.CreatedAt DESC");
        $cases = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch(PDOException $e) {
    $error = "Error loading cases: " . $e->getMessage();
}

include 'header.php';
?>

<h2>Manage Cases</h2>

<!-- Search Form -->
<div class="card" style="margin-bottom: 20px;">
    <h3><i class="fas fa-search"></i> Search Cases</h3>
    <form method="GET" action="" style="display: flex; gap: 10px; align-items: end;">
        <div class="form-group" style="flex: 1; margin-bottom: 0;">
            <label for="search">Search by Case Name, Type, or Client</label>
            <input type="text" id="search" name="search" 
                   value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>" 
                   placeholder="Enter search term...">
        </div>
        <button type="submit" class="btn btn-primary" style="width: auto; padding: 14px 24px;">
            <i class="fas fa-search"></i> Search
        </button>
        <?php if (isset($_GET['search'])): ?>
            <a href="cases.php" class="btn btn-secondary" style="width: auto; padding: 14px 24px;">
                <i class="fas fa-times"></i> Clear
            </a>
        <?php endif; ?>
    </form>
</div>

<?php if ($message): ?>
    <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>

<div class="form-container">
    <h3><?php echo $edit_case ? 'Edit Case' : 'Add New Case'; ?></h3>
    <form method="POST" action="">
        <?php if ($edit_case): ?>
            <input type="hidden" name="case_no" value="<?php echo htmlspecialchars($edit_case['CaseNo']); ?>">
        <?php endif; ?>
        
        <div class="form-group">
            <label for="case_name">Case Name *</label>
            <input type="text" id="case_name" name="case_name" 
                   value="<?php echo htmlspecialchars($edit_case['CaseName'] ?? ''); ?>" required>
        </div>
        
        <div class="form-group">
            <label for="case_type">Case Type *</label>
            <input type="text" id="case_type" name="case_type" 
                   value="<?php echo htmlspecialchars($edit_case['CaseType'] ?? ''); ?>" 
                   placeholder="e.g., Civil, Criminal, Corporate" required>
        </div>
        
        <div class="form-group">
            <label for="court">Court</label>
            <input type="text" id="court" name="court" 
                   value="<?php echo htmlspecialchars($edit_case['Court'] ?? ''); ?>">
        </div>
        
        <div class="form-group">
            <label for="client_id">Client *</label>
            <select id="client_id" name="client_id" required>
                <option value="">-- Select Client --</option>
                <?php foreach ($clients as $client): ?>
                    <option value="<?php echo $client['ClientId']; ?>" 
                            <?php echo ($edit_case && $edit_case['ClientId'] == $client['ClientId']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($client['FirstName'] . ' ' . $client['LastName']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="form-group">
            <label for="status">Status</label>
            <select id="status" name="status">
                <option value="Active" <?php echo ($edit_case && $edit_case['Status'] == 'Active') ? 'selected' : ''; ?>>Active</option>
                <option value="Closed" <?php echo ($edit_case && $edit_case['Status'] == 'Closed') ? 'selected' : ''; ?>>Closed</option>
                <option value="Pending" <?php echo ($edit_case && $edit_case['Status'] == 'Pending') ? 'selected' : ''; ?>>Pending</option>
            </select>
        </div>
        
        <div class="form-group">
            <label for="description">Description</label>
            <textarea id="description" name="description"><?php echo htmlspecialchars($edit_case['Description'] ?? ''); ?></textarea>
        </div>
        
        <div class="form-actions">
            <button type="submit" class="btn btn-primary"><?php echo $edit_case ? 'Update Case' : 'Add Case'; ?></button>
            <?php if ($edit_case): ?>
                <a href="cases.php" class="btn btn-secondary">Cancel</a>
            <?php endif; ?>
        </div>
    </form>
    
    <?php if ($edit_case): ?>
        <!-- Advocate Assignment Section -->
        <div class="card mt-20" style="margin-top: 30px;">
            <h3><i class="fas fa-user-tie"></i> Assign Advocates</h3>
            <form method="POST" action="" style="margin-bottom: 20px;">
                <input type="hidden" name="case_no" value="<?php echo htmlspecialchars($edit_case['CaseNo']); ?>">
                <div style="display: flex; gap: 10px; align-items: end;">
                    <div class="form-group" style="flex: 1; margin-bottom: 0;">
                        <label for="advt_id">Select Advocate</label>
                        <select id="advt_id" name="advt_id" required>
                            <option value="">-- Select Advocate --</option>
                            <?php foreach ($advocates as $advocate): ?>
                                <?php
                                // Check if already assigned
                                $is_assigned = false;
                                foreach ($current_assignments as $ass) {
                                    if ($ass['AdvtId'] == $advocate['AdvtId']) {
                                        $is_assigned = true;
                                        break;
                                    }
                                }
                                ?>
                                <?php if (!$is_assigned): ?>
                                    <option value="<?php echo $advocate['AdvtId']; ?>">
                                        <?php echo htmlspecialchars($advocate['FirstName'] . ' ' . $advocate['LastName']); ?>
                                    </option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" name="add_assignment" class="btn btn-primary" style="width: auto; padding: 14px 24px;">
                        <i class="fas fa-plus"></i> Assign
                    </button>
                </div>
            </form>
            
            <?php if (count($current_assignments) > 0): ?>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Advocate</th>
                                <th>Assigned Date</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($current_assignments as $ass): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($ass['FirstName'] . ' ' . $ass['LastName']); ?></td>
                                    <td><?php echo htmlspecialchars($ass['AssignedDate']); ?></td>
                                    <td><?php echo htmlspecialchars($ass['Status']); ?></td>
                                    <td>
                                        <a href="?edit=<?php echo $edit_case['CaseNo']; ?>&remove_assignment=<?php echo $ass['AssId']; ?>" 
                                           class="btn btn-sm btn-danger" 
                                           onclick="return confirm('Are you sure you want to remove this assignment?');">
                                            <i class="fas fa-times"></i> Remove
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p style="color: var(--gray); text-align: center; padding: 20px;">No advocates assigned to this case yet.</p>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<div class="card mt-20">
    <h3>All Cases</h3>
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Case No</th>
                    <th>Case Name</th>
                    <th>Type</th>
                    <th>Client</th>
                    <th>Court</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($cases) > 0): ?>
                    <?php foreach ($cases as $case): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($case['CaseNo']); ?></td>
                            <td><?php echo htmlspecialchars($case['CaseName']); ?></td>
                            <td><?php echo htmlspecialchars($case['CaseType']); ?></td>
                            <td><?php echo htmlspecialchars($case['FirstName'] . ' ' . $case['LastName']); ?></td>
                            <td><?php echo htmlspecialchars($case['Court'] ?? '-'); ?></td>
                            <td><?php echo htmlspecialchars($case['Status']); ?></td>
                            <td>
                                <a href="?edit=<?php echo $case['CaseNo']; ?>" class="btn btn-sm btn-success">Edit</a>
                                <a href="?delete=<?php echo $case['CaseNo']; ?>" 
                                   class="btn btn-sm btn-danger" 
                                   onclick="return confirm('Are you sure you want to delete this case?');">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" class="empty-state">No cases found</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'footer.php'; ?>

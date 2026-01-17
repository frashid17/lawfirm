<?php
/**
 * ADVOCATE - EVENTS
 * Manage events/appointments for assigned cases
 */

require_once '../config/database.php';
require_once '../config/session.php';
requireRole('advocate');

$advocate_id = $_SESSION['user_id'];
$message = "";
$error = "";

// Handle delete
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    try {
        // Verify event belongs to advocate's assigned case
        $stmt = $conn->prepare("SELECT e.EventId FROM EVENT e 
                               JOIN CASE_ASSIGNMENT ca ON e.CaseNo = ca.CaseNo 
                               WHERE e.EventId = ? AND ca.AdvtId = ? AND ca.Status = 'Active'");
        $stmt->execute([$_GET['delete'], $advocate_id]);
        if ($stmt->fetch()) {
            $stmt = $conn->prepare("DELETE FROM EVENT WHERE EventId = ?");
            $stmt->execute([$_GET['delete']]);
            $message = "Event deleted successfully";
        } else {
            $error = "You don't have permission to delete this event";
        }
    } catch(PDOException $e) {
        $error = "Error deleting event: " . $e->getMessage();
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $event_id = $_POST['event_id'] ?? null;
    $event_name = trim($_POST['event_name'] ?? '');
    $event_type = trim($_POST['event_type'] ?? '');
    $date = $_POST['date'] ?? '';
    $case_no = $_POST['case_no'] ?? null;
    $description = trim($_POST['description'] ?? '');
    $location = trim($_POST['location'] ?? '');
    
    if (empty($event_name) || empty($event_type) || empty($date) || empty($case_no)) {
        $error = "Please fill in all required fields";
    } else {
        // Validate date is in the future
        $event_datetime = strtotime($date);
        $current_datetime = time();
        if ($event_datetime <= $current_datetime) {
            $error = "Event date and time must be in the future. You cannot schedule events for past dates or times.";
        } else {
            try {
                // Verify case is assigned to advocate
                $stmt = $conn->prepare("SELECT CaseNo FROM CASE_ASSIGNMENT WHERE CaseNo = ? AND AdvtId = ? AND Status = 'Active'");
                $stmt->execute([$case_no, $advocate_id]);
                if (!$stmt->fetch()) {
                    $error = "You are not assigned to this case";
                } else {
                    if ($event_id) {
                        // Verify event belongs to advocate's assigned case
                        $stmt = $conn->prepare("SELECT e.EventId FROM EVENT e 
                                               JOIN CASE_ASSIGNMENT ca ON e.CaseNo = ca.CaseNo 
                                               WHERE e.EventId = ? AND ca.AdvtId = ? AND ca.Status = 'Active'");
                        $stmt->execute([$event_id, $advocate_id]);
                        if ($stmt->fetch()) {
                            // Update existing event
                            $stmt = $conn->prepare("UPDATE EVENT SET EventName = ?, EventType = ?, Date = ?, CaseNo = ?, Description = ?, Location = ? WHERE EventId = ?");
                            $stmt->execute([$event_name, $event_type, $date, $case_no, $description, $location, $event_id]);
                            $message = "Event updated successfully";
                        } else {
                            $error = "You don't have permission to edit this event";
                        }
                    } else {
                        // Insert new event
                        $stmt = $conn->prepare("INSERT INTO EVENT (EventName, EventType, Date, CaseNo, Description, Location) VALUES (?, ?, ?, ?, ?, ?)");
                        $stmt->execute([$event_name, $event_type, $date, $case_no, $description, $location]);
                        $message = "Event created successfully";
                    }
                }
            } catch(PDOException $e) {
                $error = "Error saving event: " . $e->getMessage();
            }
        }
    }
}

// Get event for editing
$edit_event = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    try {
        $stmt = $conn->prepare("SELECT e.* FROM EVENT e 
                               JOIN CASE_ASSIGNMENT ca ON e.CaseNo = ca.CaseNo 
                               WHERE e.EventId = ? AND ca.AdvtId = ? AND ca.Status = 'Active'");
        $stmt->execute([$_GET['edit'], $advocate_id]);
        $edit_event = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$edit_event) {
            $error = "Event not found or you don't have permission to edit it";
        }
    } catch(PDOException $e) {
        $error = "Error loading event: " . $e->getMessage();
    }
}

// Get assigned cases for dropdown
try {
    $stmt = $conn->prepare("SELECT DISTINCT c.CaseNo, c.CaseName 
                            FROM `CASE` c 
                            JOIN CASE_ASSIGNMENT ca ON c.CaseNo = ca.CaseNo 
                            WHERE ca.AdvtId = ? AND ca.Status = 'Active' 
                            ORDER BY c.CaseName");
    $stmt->execute([$advocate_id]);
    $cases = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $error = "Error loading cases: " . $e->getMessage();
}

// Get all events for assigned cases
try {
    $stmt = $conn->prepare("SELECT e.*, c.CaseName 
                           FROM EVENT e 
                           JOIN `CASE` c ON e.CaseNo = c.CaseNo 
                           JOIN CASE_ASSIGNMENT ca ON e.CaseNo = ca.CaseNo 
                           WHERE ca.AdvtId = ? AND ca.Status = 'Active' 
                           ORDER BY e.Date ASC");
    $stmt->execute([$advocate_id]);
    $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $error = "Error loading events: " . $e->getMessage();
}

include 'header.php';
?>

<h2>Manage Events & Appointments</h2>

<?php if ($message): ?>
    <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>

<div class="card">
    <h3>My Events & Appointments</h3>
    <?php if (count($events) > 0): ?>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Event Name</th>
                        <th>Type</th>
                        <th>Date & Time</th>
                        <th>Case</th>
                        <th>Location</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($events as $event): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($event['EventName']); ?></td>
                            <td><?php echo htmlspecialchars($event['EventType']); ?></td>
                            <td><?php echo date('M d, Y H:i', strtotime($event['Date'])); ?></td>
                            <td><?php echo htmlspecialchars($event['CaseName']); ?></td>
                            <td><?php echo htmlspecialchars($event['Location'] ?? '-'); ?></td>
                            <td>
                                <a href="?edit=<?php echo $event['EventId']; ?>" class="btn btn-sm btn-success">
                                    <i class="fas fa-edit"></i> Edit
                                </a>
                                <a href="?delete=<?php echo $event['EventId']; ?>" 
                                   class="btn btn-sm btn-danger" 
                                   onclick="return confirm('Are you sure you want to delete this event?');">
                                    <i class="fas fa-trash"></i> Delete
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <p class="empty-state">No events found. Create your first event below.</p>
    <?php endif; ?>
</div>

<div class="card mt-20">
    <h3><?php echo $edit_event ? 'Edit Event' : 'Create New Event'; ?></h3>
    <form method="POST" action="">
        <input type="hidden" name="event_id" value="<?php echo $edit_event['EventId'] ?? ''; ?>">
        
        <div class="form-group">
            <label for="event_name">Event Name <span class="required">*</span></label>
            <input type="text" id="event_name" name="event_name" 
                   value="<?php echo htmlspecialchars($edit_event['EventName'] ?? ''); ?>" 
                   required>
        </div>
        
        <div class="form-group">
            <label for="event_type">Event Type <span class="required">*</span></label>
            <select id="event_type" name="event_type" required>
                <option value="">Select Type</option>
                <option value="Meeting" <?php echo ($edit_event['EventType'] ?? '') == 'Meeting' ? 'selected' : ''; ?>>Meeting</option>
                <option value="Court Hearing" <?php echo ($edit_event['EventType'] ?? '') == 'Court Hearing' ? 'selected' : ''; ?>>Court Hearing</option>
                <option value="Consultation" <?php echo ($edit_event['EventType'] ?? '') == 'Consultation' ? 'selected' : ''; ?>>Consultation</option>
                <option value="Document Review" <?php echo ($edit_event['EventType'] ?? '') == 'Document Review' ? 'selected' : ''; ?>>Document Review</option>
                <option value="Other" <?php echo ($edit_event['EventType'] ?? '') == 'Other' ? 'selected' : ''; ?>>Other</option>
            </select>
        </div>
        
        <div class="form-group">
            <label for="case_no">Case <span class="required">*</span></label>
            <select id="case_no" name="case_no" required>
                <option value="">Select Case</option>
                <?php foreach ($cases as $case): ?>
                    <option value="<?php echo $case['CaseNo']; ?>" 
                            <?php echo ($edit_event['CaseNo'] ?? '') == $case['CaseNo'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($case['CaseName']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="form-group">
            <label for="date">Date & Time <span class="required">*</span></label>
            <input type="datetime-local" id="date" name="date" 
                   value="<?php echo $edit_event ? date('Y-m-d\TH:i', strtotime($edit_event['Date'])) : ''; ?>" 
                   min="<?php echo date('Y-m-d\TH:i'); ?>"
                   required>
            <small style="color: var(--gray); font-size: 12px; margin-top: 4px; display: block;">
                <i class="fas fa-info-circle"></i> Events can only be scheduled for future dates and times.
            </small>
        </div>
        
        <div class="form-group">
            <label for="location">Location</label>
            <input type="text" id="location" name="location" 
                   value="<?php echo htmlspecialchars($edit_event['Location'] ?? ''); ?>" 
                   placeholder="e.g., Office, Court Room 5, Online">
        </div>
        
        <div class="form-group">
            <label for="description">Description</label>
            <textarea id="description" name="description" rows="4" 
                      placeholder="Additional details about the event..."><?php echo htmlspecialchars($edit_event['Description'] ?? ''); ?></textarea>
        </div>
        
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save"></i> <?php echo $edit_event ? 'Update' : 'Create'; ?> Event
            </button>
            <?php if ($edit_event): ?>
                <a href="events.php" class="btn btn-secondary">Cancel</a>
            <?php endif; ?>
        </div>
    </form>
</div>

<?php include 'footer.php'; ?>

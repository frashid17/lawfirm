<?php
/**
 * ADMIN - MANAGE EVENTS
 * View, add, edit, and delete events/appointments
 */

require_once '../config/database.php';
require_once '../config/session.php';
requireRole('admin');

$message = "";
$error = "";

// Handle delete
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    try {
        $stmt = $conn->prepare("DELETE FROM EVENT WHERE EventId = ?");
        $stmt->execute([$_GET['delete']]);
        $message = "Event deleted successfully";
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
        try {
            if ($event_id) {
                // Update existing event
                $stmt = $conn->prepare("UPDATE EVENT SET EventName = ?, EventType = ?, Date = ?, CaseNo = ?, Description = ?, Location = ? WHERE EventId = ?");
                $stmt->execute([$event_name, $event_type, $date, $case_no, $description, $location, $event_id]);
                $message = "Event updated successfully";
            } else {
                // Insert new event
                $stmt = $conn->prepare("INSERT INTO EVENT (EventName, EventType, Date, CaseNo, Description, Location) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$event_name, $event_type, $date, $case_no, $description, $location]);
                $message = "Event added successfully";
            }
        } catch(PDOException $e) {
            $error = "Error saving event: " . $e->getMessage();
        }
    }
}

// Get event for editing
$edit_event = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    try {
        $stmt = $conn->prepare("SELECT * FROM EVENT WHERE EventId = ?");
        $stmt->execute([$_GET['edit']]);
        $edit_event = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        $error = "Error loading event: " . $e->getMessage();
    }
}

// Get all cases for dropdown
try {
    $stmt = $conn->query("SELECT CaseNo, CaseName FROM `CASE` ORDER BY CaseName");
    $cases = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $error = "Error loading cases: " . $e->getMessage();
}

// Get all events
try {
    $stmt = $conn->query("SELECT e.*, c.CaseName 
                          FROM EVENT e 
                          JOIN `CASE` c ON e.CaseNo = c.CaseNo 
                          ORDER BY e.Date DESC");
    $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $error = "Error loading events: " . $e->getMessage();
}

include 'header.php';
?>

<h2>Manage Events</h2>

<?php if ($message): ?>
    <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>

<div class="form-container">
    <h3><?php echo $edit_event ? 'Edit Event' : 'Add New Event'; ?></h3>
    <form method="POST" action="">
        <?php if ($edit_event): ?>
            <input type="hidden" name="event_id" value="<?php echo htmlspecialchars($edit_event['EventId']); ?>">
        <?php endif; ?>
        
        <div class="form-group">
            <label for="event_name">Event Name *</label>
            <input type="text" id="event_name" name="event_name" 
                   value="<?php echo htmlspecialchars($edit_event['EventName'] ?? ''); ?>" required>
        </div>
        
        <div class="form-group">
            <label for="event_type">Event Type *</label>
            <input type="text" id="event_type" name="event_type" 
                   value="<?php echo htmlspecialchars($edit_event['EventType'] ?? ''); ?>" 
                   placeholder="e.g., Court Hearing, Meeting, Consultation" required>
        </div>
        
        <div class="form-group">
            <label for="date">Date & Time *</label>
            <input type="datetime-local" id="date" name="date" 
                   value="<?php echo $edit_event ? date('Y-m-d\TH:i', strtotime($edit_event['Date'])) : ''; ?>" required>
        </div>
        
        <div class="form-group">
            <label for="case_no">Case *</label>
            <select id="case_no" name="case_no" required>
                <option value="">-- Select Case --</option>
                <?php foreach ($cases as $case): ?>
                    <option value="<?php echo $case['CaseNo']; ?>" 
                            <?php echo ($edit_event && $edit_event['CaseNo'] == $case['CaseNo']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($case['CaseName']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="form-group">
            <label for="location">Location</label>
            <input type="text" id="location" name="location" 
                   value="<?php echo htmlspecialchars($edit_event['Location'] ?? ''); ?>">
        </div>
        
        <div class="form-group">
            <label for="description">Description</label>
            <textarea id="description" name="description"><?php echo htmlspecialchars($edit_event['Description'] ?? ''); ?></textarea>
        </div>
        
        <div class="form-actions">
            <button type="submit" class="btn btn-primary"><?php echo $edit_event ? 'Update Event' : 'Add Event'; ?></button>
            <?php if ($edit_event): ?>
                <a href="events.php" class="btn btn-secondary">Cancel</a>
            <?php endif; ?>
        </div>
    </form>
</div>

<div class="card mt-20">
    <h3>All Events</h3>
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Event ID</th>
                    <th>Event Name</th>
                    <th>Type</th>
                    <th>Date & Time</th>
                    <th>Case</th>
                    <th>Location</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($events) > 0): ?>
                    <?php foreach ($events as $event): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($event['EventId']); ?></td>
                            <td><?php echo htmlspecialchars($event['EventName']); ?></td>
                            <td><?php echo htmlspecialchars($event['EventType']); ?></td>
                            <td><?php echo date('Y-m-d H:i', strtotime($event['Date'])); ?></td>
                            <td><?php echo htmlspecialchars($event['CaseName']); ?></td>
                            <td><?php echo htmlspecialchars($event['Location'] ?? '-'); ?></td>
                            <td>
                                <a href="?edit=<?php echo $event['EventId']; ?>" class="btn btn-sm btn-success">Edit</a>
                                <a href="?delete=<?php echo $event['EventId']; ?>" 
                                   class="btn btn-sm btn-danger" 
                                   onclick="return confirm('Are you sure you want to delete this event?');">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" class="empty-state">No events found</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'footer.php'; ?>

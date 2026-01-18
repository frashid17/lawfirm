<?php
/**
 * CLIENT DOCUMENTS
 * View and upload documents for client's cases
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

// Create uploads directory if it doesn't exist
$upload_dir = '../uploads/documents/';
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

// Handle document upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload'])) {
    $case_no = $_POST['case_no'] ?? null;
    $document_name = trim($_POST['document_name'] ?? '');
    $category = trim($_POST['category'] ?? '');
    
    // Verify case belongs to client
    $stmt = $conn->prepare("SELECT CaseNo FROM `CASE` WHERE CaseNo = ? AND ClientId = ?");
    $stmt->execute([$case_no, $client_id]);
    if (!$stmt->fetch()) {
        $error = "Invalid case selected";
    } elseif (empty($case_no) || empty($document_name) || empty($category)) {
        $error = "Please fill in all required fields";
    } elseif (!isset($_FILES['document_file']) || $_FILES['document_file']['error'] !== UPLOAD_ERR_OK) {
        $error = "Please select a file to upload";
    } else {
        $file = $_FILES['document_file'];
        $file_name = $file['name'];
        $file_size = $file['size'];
        $file_tmp = $file['tmp_name'];
        $file_type = $file['type'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        $allowed_extensions = ['pdf', 'doc', 'docx', 'txt', 'jpg', 'jpeg', 'png', 'xls', 'xlsx'];
        
        if (!in_array($file_ext, $allowed_extensions)) {
            $error = "File type not allowed";
        } elseif ($file_size > 10485760) {
            $error = "File size exceeds 10MB limit";
        } else {
            $unique_name = uniqid() . '_' . time() . '.' . $file_ext;
            $file_path = $upload_dir . $unique_name;
            
            if (move_uploaded_file($file_tmp, $file_path)) {
                try {
                    // Get advocate for case
                    $stmt = $conn->prepare("SELECT AdvtId FROM CASE_ASSIGNMENT WHERE CaseNo = ? AND Status = 'Active' LIMIT 1");
                    $stmt->execute([$case_no]);
                    $assignment = $stmt->fetch(PDO::FETCH_ASSOC);
                    $advocate_id = $assignment ? $assignment['AdvtId'] : null;
                    
                    $stmt = $conn->prepare("SELECT MAX(Version) as max_version FROM DOCUMENT WHERE CaseNo = ? AND DocumentName = ?");
                    $stmt->execute([$case_no, $document_name]);
                    $result = $stmt->fetch(PDO::FETCH_ASSOC);
                    $new_version = ($result['max_version'] ?? 0) + 1;
                    
                    $stmt = $conn->prepare("UPDATE DOCUMENT SET IsCurrentVersion = FALSE WHERE CaseNo = ? AND DocumentName = ?");
                    $stmt->execute([$case_no, $document_name]);
                    
                    $stmt = $conn->prepare("INSERT INTO DOCUMENT (CaseNo, DocumentName, DocumentCategory, FilePath, FileSize, FileType, UploadedBy, UploadedByRole, Version, IsCurrentVersion) VALUES (?, ?, ?, ?, ?, ?, ?, 'client', ?, TRUE)");
                    $stmt->execute([$case_no, $document_name, $category, $file_path, $file_size, $file_type, $client_id, $new_version]);
                    $message = "Document uploaded successfully";
                } catch(PDOException $e) {
                    unlink($file_path);
                    $error = "Error uploading document: " . $e->getMessage();
                }
            } else {
                $error = "Error moving uploaded file";
            }
        }
    }
}

// Get client's cases
try {
    $stmt = $conn->prepare("SELECT CaseNo, CaseName FROM `CASE` WHERE ClientId = ? ORDER BY CaseName");
    $stmt->execute([$client_id]);
    $cases = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $error = "Error loading cases: " . $e->getMessage();
}

// Get documents for client's cases
try {
    $stmt = $conn->prepare("SELECT d.*, c.CaseName 
                            FROM DOCUMENT d 
                            JOIN `CASE` c ON d.CaseNo = c.CaseNo 
                            WHERE c.ClientId = ? AND d.IsCurrentVersion = TRUE
                            ORDER BY d.UploadedAt DESC");
    $stmt->execute([$client_id]);
    $documents = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $error = "Error loading documents: " . $e->getMessage();
}

$categories = ['Contract', 'Evidence', 'Court Filing', 'Correspondence', 'Report', 'Other'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Documents - Client Portal</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="header">
        <div class="header-content">
            <h1>My Documents - Client Portal</h1>
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
                <li><a href="payment_history.php"><i class="fas fa-history"></i> Payment History</a></li>
                <li><a href="documents.php" class="active"><i class="fas fa-file"></i> Documents</a></li>
                <li><a href="messages.php"><i class="fas fa-comments"></i> Messages</a></li>
                <li><a href="events.php"><i class="fas fa-calendar-alt"></i> Events</a></li>
                <li><a href="profile.php"><i class="fas fa-user"></i> Profile</a></li>
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
        
        <div class="form-container">
            <h3>Upload Document</h3>
            <form method="POST" action="" enctype="multipart/form-data">
                <input type="hidden" name="upload" value="1">
                
                <div class="form-group">
                    <label for="case_no">Case *</label>
                    <select id="case_no" name="case_no" required>
                        <option value="">-- Select Case --</option>
                        <?php foreach ($cases as $case): ?>
                            <option value="<?php echo htmlspecialchars($case['CaseNo']); ?>">
                                <?php echo htmlspecialchars($case['CaseName']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="document_name">Document Name *</label>
                    <input type="text" id="document_name" name="document_name" required>
                </div>
                
                <div class="form-group">
                    <label for="category">Category *</label>
                    <select id="category" name="category" required>
                        <option value="">-- Select Category --</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo htmlspecialchars($cat); ?>"><?php echo htmlspecialchars($cat); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="document_file">File * (Max 10MB)</label>
                    <input type="file" id="document_file" name="document_file" required 
                           accept=".pdf,.doc,.docx,.txt,.jpg,.jpeg,.png,.xls,.xlsx">
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Upload Document</button>
                </div>
            </form>
        </div>
        
        <div class="card mt-20">
            <h3>My Documents</h3>
            <?php if (isset($documents) && count($documents) > 0): ?>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Document Name</th>
                                <th>Case</th>
                                <th>Category</th>
                                <th>Uploaded By</th>
                                <th>Uploaded At</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($documents as $doc): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($doc['DocumentName']); ?></td>
                                    <td><?php echo htmlspecialchars($doc['CaseName']); ?></td>
                                    <td><?php echo htmlspecialchars($doc['DocumentCategory']); ?></td>
                                    <td><?php echo htmlspecialchars($doc['UploadedByRole']); ?></td>
                                    <td><?php echo date('Y-m-d H:i', strtotime($doc['UploadedAt'])); ?></td>
                                    <td>
                                        <a href="document_download.php?id=<?php echo $doc['DocumentId']; ?>" 
                                           class="btn btn-sm btn-success">
                                            <i class="fas fa-download"></i> Download
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="empty-state">No documents found</p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>

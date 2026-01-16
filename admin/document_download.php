<?php
/**
 * DOCUMENT DOWNLOAD
 * Secure file download for documents
 */

require_once '../config/database.php';
require_once '../config/session.php';
requireRole('admin');

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Invalid document ID");
}

try {
    $stmt = $conn->prepare("SELECT * FROM DOCUMENT WHERE DocumentId = ?");
    $stmt->execute([$_GET['id']]);
    $document = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$document) {
        die("Document not found");
    }
    
    $file_path = $document['FilePath'];
    
    if (!file_exists($file_path)) {
        die("File not found on server");
    }
    
    // Set headers for download
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . basename($document['DocumentName'] . '_v' . $document['Version'] . '.' . pathinfo($file_path, PATHINFO_EXTENSION)) . '"');
    header('Content-Length: ' . filesize($file_path));
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    
    // Output file
    readfile($file_path);
    exit();
    
} catch(PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>

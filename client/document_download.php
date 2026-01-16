<?php
/**
 * CLIENT DOCUMENT DOWNLOAD
 */

session_start();
require_once '../config/database.php';

if (!isset($_SESSION['client_id'])) {
    header("Location: login.php");
    exit();
}

$client_id = $_SESSION['client_id'];

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Invalid document ID");
}

try {
    $stmt = $conn->prepare("SELECT d.* FROM DOCUMENT d 
                            JOIN `CASE` c ON d.CaseNo = c.CaseNo 
                            WHERE d.DocumentId = ? AND c.ClientId = ?");
    $stmt->execute([$_GET['id'], $client_id]);
    $document = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$document) {
        die("Document not found or access denied");
    }
    
    $file_path = $document['FilePath'];
    
    if (!file_exists($file_path)) {
        die("File not found on server");
    }
    
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . basename($document['DocumentName'] . '_v' . $document['Version'] . '.' . pathinfo($file_path, PATHINFO_EXTENSION)) . '"');
    header('Content-Length: ' . filesize($file_path));
    readfile($file_path);
    exit();
    
} catch(PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>

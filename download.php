<?php
require_once 'config/config.php';
require_once 'config/database.php';
require_once 'includes/functions.php';
requireLogin();

$userId = $_SESSION['user_id'];
$docId  = isset($_GET['id']) ? (int) $_GET['id'] : 0;

// Admin can download any document; normal user only their own
if (isAdmin()) {
    $stmt = $conn->prepare("SELECT * FROM documents WHERE document_id = ? AND status = 'active'");
    $stmt->bind_param("i", $docId);
} else {
    $stmt = $conn->prepare("SELECT * FROM documents WHERE document_id = ? AND user_id = ? AND status = 'active'");
    $stmt->bind_param("ii", $docId, $userId);
}
$stmt->execute();
$document = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$document) {
    setMessage('danger', 'Document not found or access denied.');
    redirect(BASE_URL . 'documents.php');
}

$filePath = __DIR__ . '/' . $document['file_path'];

if (!file_exists($filePath)) {
    setMessage('danger', 'File missing from server.');
    redirect(BASE_URL . 'documents.php');
}

logActivity($conn, $userId, "Downloaded document: " . $document['document_name'], "Document");

// ----- Stream file for download -----
header('Content-Description: File Transfer');
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . basename($document['document_name'] . '.' . $document['file_type']) . '"');
header('Content-Length: ' . filesize($filePath));
header('Pragma: public');
header('Cache-Control: must-revalidate');
readfile($filePath);
exit();
?>

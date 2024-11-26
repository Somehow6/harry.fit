<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('HTTP/1.1 403 Forbidden');
    exit('Not logged in');
}

$fileId = $_GET['file_id'] ?? null;

if (!$fileId) {
    header('HTTP/1.1 400 Bad Request');
    exit('No file ID provided');
}

try {
    $stmt = $pdo->prepare("SELECT * FROM files WHERE id = ? AND user_id = ?");
    $stmt->execute([$fileId, $_SESSION['user_id']]);
    $file = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($file) {
        $filePath = '../uploads/' . $_SESSION['user_id'] . '/' . $file['filename'];
        
        if (file_exists($filePath)) {
            header('Content-Type: ' . $file['file_type']);
            header('Content-Disposition: attachment; filename="' . $file['original_name'] . '"');
            header('Content-Length: ' . filesize($filePath));
            readfile($filePath);
            exit;
        }
    }
    
    header('HTTP/1.1 404 Not Found');
    exit('File not found');
} catch (PDOException $e) {
    header('HTTP/1.1 500 Internal Server Error');
    exit($e->getMessage());
}
<?php
session_start();
header('Content-Type: application/json');
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$fileId = $data['file_id'] ?? null;

if (!$fileId) {
    echo json_encode(['success' => false, 'error' => 'No file ID provided']);
    exit;
}

try {
    // 获取文件信息
    $stmt = $pdo->prepare("SELECT * FROM files WHERE id = ? AND user_id = ?");
    $stmt->execute([$fileId, $_SESSION['user_id']]);
    $file = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($file) {
        // 删除物理文件
        $filePath = '../uploads/' . $_SESSION['user_id'] . '/' . $file['filename'];
        if (file_exists($filePath)) {
            unlink($filePath);
        }
        
        // 从数据库中删除记录
        $stmt = $pdo->prepare("DELETE FROM files WHERE id = ?");
        $stmt->execute([$fileId]);
        
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'File not found']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
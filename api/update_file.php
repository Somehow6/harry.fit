<?php
session_start();
header('Content-Type: application/json');
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit;
}

if (!isset($_FILES['file'])) {
    echo json_encode(['success' => false, 'error' => 'No file uploaded']);
    exit;
}

$file = $_FILES['file'];
$fileName = $file['name'];
$tmpName = $file['tmp_name'];
$fileSize = $file['size'];
$fileType = $file['type'];

// 创建上传目录
$uploadDir = '../uploads/' . $_SESSION['user_id'] . '/';
if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

// 生成唯一文件名
$uniqueName = uniqid() . '_' . $fileName;
$uploadPath = $uploadDir . $uniqueName;

try {
    // 保存文件
    if (move_uploaded_file($tmpName, $uploadPath)) {
        // 在数据库中记录文件信息
        $stmt = $pdo->prepare("INSERT INTO files (user_id, filename, original_name, file_type, file_size) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$_SESSION['user_id'], $uniqueName, $fileName, $fileType, $fileSize]);
        
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'File upload failed']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
<?php
session_start();
header('Content-Type: application/json');
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(handleError('未登录'));
    exit;
}

if (!isset($_FILES['file'])) {
    echo json_encode(handleError('没有上传文件'));
    exit;
}

$file = $_FILES['file'];
$fileName = filter_var($file['name'], FILTER_SANITIZE_STRING);
$tmpName = $file['tmp_name'];
$fileSize = $file['size'];
$fileType = $file['type'];

// 文件类型白名单
$allowedTypes = [
    'image/jpeg',
    'image/png',
    'image/gif',
    'application/pdf',
    'application/msword',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'text/plain'
];

// 文件大小限制 (10MB)
$maxFileSize = 10 * 1024 * 1024;

// 验证文件类型
if (!in_array($fileType, $allowedTypes)) {
    echo json_encode(handleError('不支持的文件类型'));
    exit;
}

// 验证文件大小
if ($fileSize > $maxFileSize) {
    echo json_encode(handleError('文件大小超过限制（最大10MB）'));
    exit;
}

// 创建上传目录
$uploadDir = '../uploads/' . $_SESSION['user_id'] . '/';
if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

// 生成安全的文件名
$fileExtension = pathinfo($fileName, PATHINFO_EXTENSION);
$uniqueName = uniqid('file_', true) . '_' . time() . '.' . $fileExtension;
$uploadPath = $uploadDir . $uniqueName;

try {
    // 开始事务
    $pdo->beginTransaction();

    // 检查用户存储配额
    $stmt = $pdo->prepare("SELECT SUM(file_size) as total_size FROM files WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $totalSize = $stmt->fetch()['total_size'] ?? 0;

    // 设置用户存储限制 (100MB)
    $storageLimit = 100 * 1024 * 1024;
    if (($totalSize + $fileSize) > $storageLimit) {
        echo json_encode(handleError('存储空间不足'));
        exit;
    }

    // 保存文件
    if (move_uploaded_file($tmpName, $uploadPath)) {
        // 验证上传的文件
        if (!file_exists($uploadPath) || filesize($uploadPath) !== $fileSize) {
            throw new Exception('文件上传验证失败');
        }

        // 在数据库中记录文件信息
        $stmt = $pdo->prepare("
            INSERT INTO files (user_id, filename, original_name, file_type, file_size, created_at) 
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([
            $_SESSION['user_id'],
            $uniqueName,
            $fileName,
            $fileType,
            $fileSize
        ]);

        // 提交事务
        $pdo->commit();
        
        echo json_encode([
            'success' => true,
            'file' => [
                'id' => $pdo->lastInsertId(),
                'name' => $fileName,
                'size' => $fileSize
            ]
        ]);
    } else {
        throw new Exception('文件上传失败');
    }
} catch (Exception $e) {
    // 回滚事务
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    // 如果文件已上传，删除它
    if (file_exists($uploadPath)) {
        unlink($uploadPath);
    }
    
    echo json_encode(handleError('文件上传失败', $e->getMessage()));
}
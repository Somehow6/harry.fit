<?php
session_start();
header('Content-Type: application/json');
require_once 'config.php';

// 调试日志
error_log("Upload attempt - User ID: " . ($_SESSION['user_id'] ?? 'not set'));

if (!isset($_SESSION['user_id'])) {
    error_log("Upload failed - User not logged in");
    echo json_encode(handleError('未登录'));
    exit;
}

if (!isset($_FILES['file'])) {
    error_log("Upload failed - No file uploaded");
    echo json_encode(handleError('没有上传文件'));
    exit;
}

$file = $_FILES['file'];
$fileName = filter_var($file['name'], FILTER_SANITIZE_STRING);
$tmpName = $file['tmp_name'];
$fileSize = $file['size'];
$fileType = $file['type'];

// 调试日志
error_log("File upload details - Name: $fileName, Size: $fileSize, Type: $fileType");

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
    error_log("Upload failed - Invalid file type: $fileType");
    echo json_encode(handleError('不支持的文件类型'));
    exit;
}

// 验证文件大小
if ($fileSize > $maxFileSize) {
    error_log("Upload failed - File too large: $fileSize bytes");
    echo json_encode(handleError('文件大小超过限制（最大10MB）'));
    exit;
}

// 创建上传目录
$baseUploadDir = dirname(__DIR__) . '/uploads';
$userUploadDir = $baseUploadDir . '/' . $_SESSION['user_id'];

// 确保基本上传目录存在
if (!file_exists($baseUploadDir)) {
    if (!@mkdir($baseUploadDir, 0755, true)) {
        error_log("Upload failed - Cannot create base upload directory: $baseUploadDir");
        echo json_encode(handleError('创建上传目录失败'));
        exit;
    }
    chmod($baseUploadDir, 0755);
}

// 创建用户上传目录
if (!file_exists($userUploadDir)) {
    if (!@mkdir($userUploadDir, 0755, true)) {
        error_log("Upload failed - Cannot create user upload directory: $userUploadDir");
        echo json_encode(handleError('创建用户上传目录失败'));
        exit;
    }
    chmod($userUploadDir, 0755);
}

// 检查目录权限
if (!is_writable($userUploadDir)) {
    error_log("Upload failed - Directory not writable: $userUploadDir");
    echo json_encode(handleError('上传目录没有写入权限'));
    exit;
}

// 生成安全的文件名
$fileExtension = pathinfo($fileName, PATHINFO_EXTENSION);
$uniqueName = uniqid('file_', true) . '_' . time() . '.' . $fileExtension;
$uploadPath = $userUploadDir . '/' . $uniqueName;

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
        error_log("Upload failed - Storage quota exceeded");
        echo json_encode(handleError('存储空间不足'));
        exit;
    }

    // 保存文件
    if (@move_uploaded_file($tmpName, $uploadPath)) {
        // 设置文件权限
        chmod($uploadPath, 0644);
        
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
        
        error_log("Upload successful - File: $fileName");
        echo json_encode([
            'success' => true,
            'file' => [
                'id' => $pdo->lastInsertId(),
                'name' => $fileName,
                'size' => $fileSize
            ]
        ]);
    } else {
        $uploadError = error_get_last();
        error_log("Upload failed - move_uploaded_file failed: " . ($uploadError['message'] ?? 'Unknown error'));
        throw new Exception('文件上传失败');
    }
} catch (Exception $e) {
    // 回滚事务
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    // 如果文件已上传，删除它
    if (file_exists($uploadPath)) {
        @unlink($uploadPath);
    }
    
    error_log("Upload failed - Exception: " . $e->getMessage());
    echo json_encode(handleError('文件上传失败', $e->getMessage()));
}
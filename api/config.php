<?php
// Session 配置
ini_set('session.cookie_lifetime', 86400);  // Cookie 生命周期为24小时
ini_set('session.gc_maxlifetime', 86400);   // Session 最大生命周期为24小时
ini_set('session.cookie_path', '/');         // Cookie 路径设为根目录
ini_set('session.cookie_httponly', 1);       // 防止XSS攻击
ini_set('session.use_only_cookies', 1);      // 只使用cookie保存session id
session_start();                             // 启动session

// 数据库配置
$db_host = 'localhost';
$db_name = 'tetris_db';
$db_user = 'tetris_user';
$db_pass = 'Password123#';

try {
    $dsn = "mysql:host={$db_host};dbname={$db_name};charset=utf8mb4";
    $pdo = new PDO($dsn, $db_user, $db_pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false
    ]);
} catch(PDOException $e) {
    error_log("Database connection error: " . $e->getMessage());
    header('HTTP/1.1 500 Internal Server Error');
    echo json_encode(['error' => 'Database connection failed']);
    exit;
}

// 全局错误处理函数
function handleError($error, $logMessage = '') {
    if ($logMessage) {
        error_log($logMessage);
    }
    return ['success' => false, 'message' => $error];
}

// 设置错误处理
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    error_log("Error [$errno] $errstr on line $errline in file $errfile");
    return true;
});
?>
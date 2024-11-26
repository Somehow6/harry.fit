<?php
// Session 配置
ini_set('session.cookie_secure', 1);  // 只在 HTTPS 下发送 cookie
ini_set('session.cookie_httponly', 1);  // 防止 XSS 攻击
ini_set('session.cookie_samesite', 'Strict');  // 防止 CSRF 攻击
ini_set('session.gc_maxlifetime', 86400);  // session 过期时间设为 24 小时
ini_set('session.use_strict_mode', 1);  // 严格模式
ini_set('session.use_only_cookies', 1);  // 只使用 cookie 保存 session id
ini_set('session.cookie_path', '/');  // cookie 路径设为根目录

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
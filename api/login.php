<?php
session_start([
    'cookie_httponly' => true,
    'cookie_secure' => true,
    'cookie_samesite' => 'Strict'
]);

header('Content-Type: application/json');
require_once 'config.php';

// 获取并验证输入
$data = json_decode(file_get_contents('php://input'), true);
$username = filter_var($data['username'] ?? '', FILTER_SANITIZE_STRING);
$password = $data['password'] ?? '';

// 调试日志
error_log("Login attempt - Username: " . $username);

// 输入验证
if (empty($username) || empty($password)) {
    echo json_encode(handleError('用户名和密码不能为空'));
    exit;
}

// 防止暴力破解
$attempts = $_SESSION['login_attempts'] ?? 0;
if ($attempts > 5) {
    $lastAttempt = $_SESSION['last_attempt'] ?? 0;
    if (time() - $lastAttempt < 300) { // 5分钟内禁止登录
        echo json_encode(handleError('登录尝试次数过多，请5分钟后再试'));
        exit;
    }
    $_SESSION['login_attempts'] = 0;
}

try {
    // 查询用户信息
    $stmt = $pdo->prepare("SELECT id, password FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    // 调试日志
    error_log("User query result: " . print_r($user, true));

    if ($user && password_verify($password, $user['password'])) {
        // 调试日志
        error_log("Password verification successful");

        // 重置登录尝试次数
        $_SESSION['login_attempts'] = 0;
        
        // 设置会话
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $username;
        $_SESSION['last_activity'] = time();
        
        // 调试日志
        error_log("Login successful - Session data: " . print_r($_SESSION, true));
        
        echo json_encode([
            'success' => true,
            'user' => [
                'id' => $user['id'],
                'username' => $username
            ]
        ]);
    } else {
        // 调试日志
        error_log("Login failed - Invalid credentials");
        
        // 增加失败次数
        $_SESSION['login_attempts'] = $attempts + 1;
        $_SESSION['last_attempt'] = time();
        
        echo json_encode(handleError('用户名或密码错误'));
    }
} catch(PDOException $e) {
    error_log("Login error: " . $e->getMessage());
    echo json_encode(handleError('登录失败，请稍后重试', $e->getMessage()));
}
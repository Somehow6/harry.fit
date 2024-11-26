<?php
header('Content-Type: application/json');
require_once 'config.php';

// 获取并验证输入
$data = json_decode(file_get_contents('php://input'), true);
$username = filter_var($data['username'] ?? '', FILTER_SANITIZE_STRING);
$password = $data['password'] ?? '';

// 输入验证
if (empty($username) || empty($password)) {
    echo json_encode(handleError('用户名和密码不能为空'));
    exit;
}

// 验证用户名格式
if (!preg_match('/^[a-zA-Z0-9_]{4,20}$/', $username)) {
    echo json_encode(handleError('用户名只能包含字母、数字和下划线，长度4-20位'));
    exit;
}

// 验证密码强度
if (strlen($password) < 6) {
    echo json_encode(handleError('密码长度至少6位'));
    exit;
}

try {
    // 开始事务
    $pdo->beginTransaction();
    
    // 检查用户名是否已存在
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->execute([$username]);
    if ($stmt->fetch()) {
        $pdo->rollBack();
        echo json_encode(handleError('用户名已存在'));
        exit;
    }

    // 创建新用户
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO users (username, password, status) VALUES (?, ?, 'active')");
    $stmt->execute([$username, $hash]);
    
    // 获取新用户ID
    $userId = $pdo->lastInsertId();
    
    // 记录注册日志
    $stmt = $pdo->prepare("INSERT INTO login_logs (user_id, ip_address) VALUES (?, ?)");
    $stmt->execute([$userId, $_SERVER['REMOTE_ADDR']]);
    
    // 提交事务
    $pdo->commit();
    
    // 自动登录
    session_start([
        'cookie_httponly' => true,
        'cookie_secure' => true,
        'cookie_samesite' => 'Strict'
    ]);
    
    $_SESSION['user_id'] = $userId;
    $_SESSION['username'] = $username;
    $_SESSION['last_activity'] = time();
    
    echo json_encode([
        'success' => true,
        'user' => [
            'id' => $userId,
            'username' => $username
        ]
    ]);
    
} catch(PDOException $e) {
    // 回滚事务
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Registration error: " . $e->getMessage());
    echo json_encode(handleError('注册失败，请稍后重试'));
}
?>
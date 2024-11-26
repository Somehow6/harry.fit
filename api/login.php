<?php
session_start();
header('Content-Type: application/json');
require_once 'config.php';

$data = json_decode(file_get_contents('php://input'), true);
$username = $data['username'] ?? '';
$password = $data['password'] ?? '';

if (empty($username) || empty($password)) {
    echo json_encode(['error' => '用户名和密码不能为空']);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT id, password FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        // 设置session
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $username;
        
        // 设置session过期时间为24小时
        ini_set('session.gc_maxlifetime', 86400);
        session_set_cookie_params(86400);
        
        echo json_encode([
            'success' => true,
            'user_id' => $user['id']
        ]);
        
        // 调试信息
        error_log('Login successful. Session data: ' . print_r($_SESSION, true));
    } else {
        echo json_encode(['error' => '用户名或密码错误']);
    }
} catch(PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
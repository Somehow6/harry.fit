<?php
session_start();
header('Content-Type: application/json');

// 调试信息
error_log('Session data: ' . print_r($_SESSION, true));

if (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])) {
    echo json_encode([
        'success' => true,
        'user' => [
            'id' => $_SESSION['user_id'],
            'username' => $_SESSION['username']
        ]
    ]);
} else {
    echo json_encode([
        'success' => false,
        'error' => 'Not logged in'
    ]);
}
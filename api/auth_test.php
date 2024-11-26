<?php
header('Content-Type: application/json');
require_once 'config.php';

try {
    // 测试数据库连接
    echo "Testing database connection...\n";
    $pdo->query("SELECT 1");
    echo "Database connection successful\n\n";
    
    // 检查users表是否存在
    echo "Checking users table...\n";
    $tables = $pdo->query("SHOW TABLES LIKE 'users'")->fetchAll();
    if (empty($tables)) {
        echo "Users table does not exist!\n";
        exit;
    }
    echo "Users table exists\n\n";
    
    // 检查用户表结构
    echo "Checking users table structure...\n";
    $columns = $pdo->query("SHOW COLUMNS FROM users")->fetchAll(PDO::FETCH_COLUMN);
    echo "Table columns: " . implode(", ", $columns) . "\n\n";
    
    // 检查是否有用户数据
    echo "Checking user records...\n";
    $users = $pdo->query("SELECT id, username, status FROM users")->fetchAll();
    echo "Total users: " . count($users) . "\n";
    foreach ($users as $user) {
        echo "User ID: {$user['id']}, Username: {$user['username']}, Status: {$user['status']}\n";
    }
    
    // 检查会话配置
    echo "\nChecking session configuration...\n";
    echo "Session save path: " . session_save_path() . "\n";
    echo "Session cookie params: " . print_r(session_get_cookie_params(), true) . "\n";
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Error code: " . $e->getCode() . "\n";
} 
<?php
header('Content-Type: text/plain');
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Testing database connection...\n\n";

try {
    $db_host = 'localhost';
    $db_name = 'tetris_db';
    $db_user = 'tetris_user';
    $db_pass = 'Password123#';

    echo "Connection parameters:\n";
    echo "Host: $db_host\n";
    echo "Database: $db_name\n";
    echo "User: $db_user\n\n";

    // 尝试连接
    echo "Attempting connection...\n";
    $dsn = "mysql:host={$db_host};dbname={$db_name};charset=utf8mb4";
    $pdo = new PDO($dsn, $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Connection successful!\n\n";

    // 测试查询
    echo "Testing query...\n";
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "Total users in database: " . $result['count'] . "\n\n";

    // 显示MySQL版本
    $version = $pdo->query('SELECT VERSION() as version')->fetch();
    echo "MySQL Version: " . $version['version'] . "\n";

    // 显示字符集
    $charset = $pdo->query('SHOW VARIABLES LIKE "character_set_database"')->fetch();
    echo "Database Character Set: " . $charset['Value'] . "\n";

} catch (PDOException $e) {
    echo "Connection failed!\n";
    echo "Error: " . $e->getMessage() . "\n";
    echo "Error Code: " . $e->getCode() . "\n";
} 
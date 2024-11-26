<?php
require_once 'config.php';
try {
    // 尝试查询测试表
    $query = $pdo->query('SELECT * FROM test_table');
    $result = $query->fetch(PDO::FETCH_ASSOC);
    echo json_encode([
        'status' => 'success', 
        'data' => $result,
        'connection' => 'Database connection successful'
    ]);
} catch(PDOException $e) {
    echo json_encode([
        'status' => 'error', 
        'message' => $e->getMessage(),
        'trace' => $e->getTrace()
    ]);
}
?>
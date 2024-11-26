<?php
header('Content-Type: application/json');
require_once 'config.php';

try {
    $stmt = $pdo->query("
        SELECT u.username, s.score, s.level
        FROM scores s
        JOIN users u ON s.user_id = u.id
        ORDER BY s.score DESC
        LIMIT 10
    ");
    
    echo json_encode([
        'success' => true,
        'leaderboard' => $stmt->fetchAll(PDO::FETCH_ASSOC)
    ]);
} catch(PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>
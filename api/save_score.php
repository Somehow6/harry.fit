<?php
header('Content-Type: application/json');
require_once 'config.php';

$data = json_decode(file_get_contents('php://input'), true);
$user_id = $data['user_id'] ?? 0;
$score = $data['score'] ?? 0;
$level = $data['level'] ?? 1;

try {
    $stmt = $pdo->prepare("INSERT INTO scores (user_id, score, level) VALUES (?, ?, ?)");
    $stmt->execute([$user_id, $score, $level]);
    
    echo json_encode(['success' => true]);
} catch(PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>
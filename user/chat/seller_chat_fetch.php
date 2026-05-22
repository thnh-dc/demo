<?php
session_start();
require_once '../../config/database.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Bạn cần đăng nhập để xem tin nhắn.',
        'messages' => []
    ]);
    exit;
}
$user_id = $_SESSION['user_id'];

try {
    $stmt = $pdo->prepare("
        SELECT id
        FROM chat_conversations
        WHERE user_id = ? AND status = 'open'
        ORDER BY id DESC
        LIMIT 1
    ");
    $stmt->execute([$user_id]);
    $conversation = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$conversation) {
        echo json_encode([
            'success' => true,
            'messages' => []
        ]);
        exit;
    }

    $conversation_id = $conversation['id'];

    $stmt = $pdo->prepare("
        UPDATE chat_messages
        SET is_read = 1
        WHERE conversation_id = ?
        AND sender_type = 'admin'
    ");
    $stmt->execute([$conversation_id]);

    $stmt = $pdo->prepare("
        SELECT 
            sender_type,
            message,
            created_at
        FROM chat_messages
        WHERE conversation_id = ?
        ORDER BY created_at ASC
    ");
    $stmt->execute([$conversation_id]);
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'messages' => $messages
    ]);
    exit;

} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Lỗi hệ thống, không thể tải tin nhắn.',
        'messages' => []
    ]);
    exit;
}
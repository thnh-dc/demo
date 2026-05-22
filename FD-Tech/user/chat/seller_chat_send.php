<?php
session_start();
require_once '../../config/database.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Bạn cần đăng nhập để nhắn với người bán.'
    ]);
    exit;
}

$user_id = $_SESSION['user_id'];
$message = trim($_POST['message'] ?? '');

if ($message === '') {
    echo json_encode([
        'success' => false,
        'message' => 'Nội dung tin nhắn không được để trống.'
    ]);
    exit;
}

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

    if ($conversation) {
        $conversation_id = $conversation['id'];
    } else {
        $stmt = $pdo->prepare("
            INSERT INTO chat_conversations(user_id, status)
            VALUES(?, 'open')
        ");
        $stmt->execute([$user_id]);
        $conversation_id = $pdo->lastInsertId();
    }

    $stmt = $pdo->prepare("
        INSERT INTO chat_messages(conversation_id, sender_type, sender_id, message, is_read)
        VALUES(?, 'user', ?, ?, 0)
    ");
    $stmt->execute([$conversation_id, $user_id, $message]);

    $stmt = $pdo->prepare("
        UPDATE chat_conversations
        SET updated_at = NOW()
        WHERE id = ?
    ");
    $stmt->execute([$conversation_id]);

    echo json_encode([
        'success' => true,
        'message' => 'Đã gửi tin nhắn.',
        'conversation_id' => $conversation_id
    ]);
    exit;

} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Lỗi hệ thống, không thể gửi tin nhắn.'
    ]);
    exit;
}
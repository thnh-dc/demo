<?php
session_start();
require_once '../../config/database.php';
require_once __DIR__ . '/../check_admin.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../chat_list.php");
    exit();
}

$conversation_id = isset($_POST['conversation_id']) ? (int) $_POST['conversation_id'] : 0;
$message = trim($_POST['message'] ?? '');

if ($conversation_id <= 0 || $message === '') {
    $_SESSION['flash_msg'] = 'Dữ liệu tin nhắn không hợp lệ!';
    header("Location: ../chat_list.php");
    exit();
}

$stmt = $pdo->prepare("
    SELECT id, status 
    FROM chat_conversations
    WHERE id = ?
    LIMIT 1
");
$stmt->execute([$conversation_id]);
$conversation = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$conversation) {
    $_SESSION['flash_msg'] = 'Không tìm thấy cuộc trò chuyện!';
    header("Location: ../chat_list.php");
    exit();
}

if ($conversation['status'] !== 'open') {
    $_SESSION['flash_msg'] = 'Cuộc trò chuyện đã đóng, không thể gửi tin nhắn!';
    header("Location: ../chat_detail.php?id=" . $conversation_id);
    exit();
}

$admin_id = $_SESSION['user_id'] ?? 0;

$stmt = $pdo->prepare("
    INSERT INTO chat_messages(conversation_id, sender_type, sender_id, message, is_read)
    VALUES(?, 'admin', ?, ?, 0)
");
$stmt->execute([$conversation_id, $admin_id, $message]);

$stmtUpdate = $pdo->prepare("
    UPDATE chat_conversations
    SET admin_id = ?, updated_at = NOW()
    WHERE id = ?
");
$stmtUpdate->execute([$admin_id, $conversation_id]);

header("Location: ../chat_detail.php?id=" . $conversation_id);
exit();
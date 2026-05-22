<?php
    session_start();
    require_once '../config/database.php';
    require_once __DIR__ . '/check_admin.php';

    $conversation_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

    $stmt = $pdo->prepare("
        SELECT 
            cc.id,
            cc.user_id,
            cc.status,
            u.username,
            u.full_name,
            u.email,
            u.phone
        FROM chat_conversations cc
        INNER JOIN users u ON cc.user_id = u.id
        WHERE cc.id = ?
        LIMIT 1
    ");
    $stmt->execute([$conversation_id]);
    $conversation = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$conversation) {
        die("Không tìm thấy cuộc trò chuyện.");
    }

    $markRead = $pdo->prepare("
        UPDATE chat_messages 
        SET is_read = 1
        WHERE conversation_id = ?
        AND sender_type = 'user'
    ");
    $markRead->execute([$conversation_id]);

    $stmtMessages = $pdo->prepare("
        SELECT 
            sender_type,
            sender_id,
            message,
            created_at
        FROM chat_messages
        WHERE conversation_id = ?
        ORDER BY created_at ASC
    ");
    $stmtMessages->execute([$conversation_id]);
    $messages = $stmtMessages->fetchAll(PDO::FETCH_ASSOC);

    $page_title = 'Chi tiết tin nhắn';
    $page_icon = 'fa-solid fa-comment-dots';
    $custom_css = '<link rel="stylesheet" href="../assets/css/style_admin_chat.css">';

    include 'includes/header.php';
?>

    <div class="dashboard-container">

        <div class="chat-detail-card">

            <div class="chat-detail-header">
                <div>
                    <h2>
                        <?= htmlspecialchars($conversation['full_name'] ?: $conversation['username']) ?>
                    </h2>

                    <p>
                        Email: <?= htmlspecialchars($conversation['email']) ?>
                        <?php if (!empty($conversation['phone'])): ?>
                            | SĐT: <?= htmlspecialchars($conversation['phone']) ?>
                        <?php endif; ?>
                    </p>
                </div>

                <a href="chat_list.php" class="btn btn-secondary">
                    <i class="fa-solid fa-arrow-left"></i>
                    Quay lại
                </a>
            </div>

            <div class="chat-message-box" id="chatMessageBox">

                <?php if (!empty($messages)): ?>
                    <?php foreach ($messages as $msg): ?>
                        <div class="message-row <?= $msg['sender_type'] === 'admin' ? 'admin-message' : 'user-message' ?>">
                            <div class="message-bubble">
                                <p><?= nl2br(htmlspecialchars($msg['message'])) ?></p>
                                <span><?= date('d/m/Y H:i', strtotime($msg['created_at'])) ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-chat">
                        Chưa có tin nhắn nào trong cuộc trò chuyện này.
                    </div>
                <?php endif; ?>

            </div>

            <?php if ($conversation['status'] === 'open'): ?>
                <form action="action_chat/send_admin_message.php" method="POST" class="chat-reply-form">
                    <input type="hidden" name="conversation_id" value="<?= $conversation['id'] ?>">

                    <textarea 
                        name="message" 
                        class="form-control" 
                        rows="3" 
                        placeholder="Nhập phản hồi cho khách hàng..."
                        required
                    ></textarea>

                    <button type="submit" class="btn btn-primary">
                        <i class="fa-solid fa-paper-plane"></i>
                        Gửi
                    </button>
                </form>
            <?php else: ?>
                <div class="closed-message">
                    Cuộc trò chuyện này đã được đóng.
                </div>
            <?php endif; ?>

        </div>

    </div>

    <script>
        const chatBox = document.getElementById('chatMessageBox');
        if (chatBox) {
            chatBox.scrollTop = chatBox.scrollHeight;
        }
</script>

    <script src="../assets/js/script_dashboard.js"></script>
</body>
</html>
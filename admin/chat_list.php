<?php
    session_start();
    require_once '../config/database.php';
    require_once __DIR__ . '/check_admin.php';

    $stmt = $pdo->prepare("
        SELECT 
            cc.id,
            cc.user_id,
            cc.status,
            cc.created_at,
            cc.updated_at,
            u.username,
            u.full_name,
            u.email,
            (
                SELECT message 
                FROM chat_messages 
                WHERE conversation_id = cc.id 
                ORDER BY created_at DESC 
                LIMIT 1
            ) AS last_message,
            (
                SELECT created_at 
                FROM chat_messages 
                WHERE conversation_id = cc.id 
                ORDER BY created_at DESC 
                LIMIT 1
            ) AS last_message_time,
            (
                SELECT COUNT(*) 
                FROM chat_messages 
                WHERE conversation_id = cc.id 
                AND sender_type = 'user'
                AND is_read = 0
            ) AS unread_count
        FROM chat_conversations cc
        INNER JOIN users u ON cc.user_id = u.id
        ORDER BY cc.updated_at DESC
    ");

    $stmt->execute();
    $conversations = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $page_title = 'Tin nhắn khách hàng';
    $page_icon = 'fa-solid fa-comments';
    $custom_css = '<link rel="stylesheet" href="../assets/css/style_admin_chat.css">';

    include 'includes/header.php';
?>

    <div class="dashboard-container">

        <div class="chat-list-card">

            <?php if (!empty($conversations)): ?>
                <div class="chat-list">

                    <?php foreach ($conversations as $chat): ?>
                        <a href="chat_detail.php?id=<?= $chat['id'] ?>" class="chat-item">

                            <div class="chat-avatar">
                                <i class="fa-solid fa-user"></i>
                            </div>

                            <div class="chat-info">
                                <div class="chat-row">
                                    <h3>
                                        <?= htmlspecialchars($chat['full_name'] ?: $chat['username']) ?>
                                    </h3>

                                    <span class="chat-time">
                                        <?php if (!empty($chat['last_message_time'])): ?>
                                            <?= date('d/m/Y H:i', strtotime($chat['last_message_time'])) ?>
                                        <?php else: ?>
                                            <?= date('d/m/Y H:i', strtotime($chat['created_at'])) ?>
                                        <?php endif; ?>
                                    </span>
                                </div>

                                <p class="chat-email">
                                    <?= htmlspecialchars($chat['email']) ?>
                                </p>

                                <p class="chat-last-message">
                                    <?= htmlspecialchars($chat['last_message'] ?? 'Chưa có tin nhắn') ?>
                                </p>
                            </div>

                            <div class="chat-status-area">
                                <?php if ($chat['unread_count'] > 0): ?>
                                    <span class="unread-badge">
                                        <?= $chat['unread_count'] ?>
                                    </span>
                                <?php endif; ?>
                            </div>

                        </a>
                    <?php endforeach; ?>

                </div>
            <?php else: ?>
                <div class="empty-box">
                    <i class="fa-solid fa-comments"></i>
                    <h3>Chưa có cuộc trò chuyện nào</h3>
                </div>
            <?php endif; ?>

        </div>

    </div>

    <script src="../assets/js/script_dashboard.js"></script>
</body>
</html>
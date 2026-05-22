<?php
$is_logged_in = isset($_SESSION['user_id']);
?>

<div class="ai-widget-wrapper">
    <div class="ai-welcome-popup" id="aiWelcomePopup">
        <div class="ai-welcome-header">
            <strong>FD Bot</strong>
        </div>
        <div class="ai-welcome-body">
            <?php if (isset($_SESSION['user_id'])): ?>
                Xin chào <?= htmlspecialchars($_SESSION['username'] ?? 'bạn') ?>!
                Mình là FD Bot, hỏi mình nếu bạn cần hỗ trợ hoặc liên hệ với người bán tại đây nhé!
            <?php else: ?>
                Xin chào bạn! Có vẻ bạn chưa đăng nhập.
                <div class="ai-auth-links">
                    <a href="../auth/login.php">Đăng nhập</a> để được hỗ trợ tốt hơn nhé!
                </div>
            <?php endif; ?>
        </div>
    </div>
    <button type="button" class="ai-floating-btn" id="aiFloatingBtn">
        <img src="/FD-Tech/assets/images/ai-bot.png" alt="FD Bot">
    </button>
</div>
<div class="ai-chat-box" id="aiChatBox">
    <div class="ai-chat-header">
        <div>
            <strong id="chatBoxTitle">FD Bot</strong>
            <p id="chatBoxSubTitle">Trợ lí hỗ trợ mua hàng</p>
        </div>
        <button type="button" id="aiChatClose">&times;</button>
    </div>
    <div class="ai-chat-tabs">
        <button type="button" class="ai-tab-btn active" data-mode="ai">
            Trợ lí FD Bot
        </button>
        <button type="button" class="ai-tab-btn" data-mode="seller">
            Nhắn với người bán
        </button>
    </div>
    <div class="ai-chat-messages" id="aiChatMessages">
        <div class="ai-message ai-bot">
            Xin chào! Bạn cần mình hỗ trợ gì?
            <br>
            Mình có thể giúp bạn :tra cứu đơn hàng, sản phẩm, đổi mật khẩu,... Và bạn cũng có thể chat với người bán.
        </div>

        <?php if (!$is_logged_in): ?>
            <div class="ai-message ai-bot">
                Bạn có thể <a href="../auth/login.php">đăng nhập</a> để tra cứu đơn hàng và nhắn với người bán.
            </div>
        <?php endif; ?>
    </div>
    <form class="ai-chat-form" id="aiChatForm">
        <input 
            type="text" 
            id="aiChatInput" 
            placeholder="Nhập câu hỏi..." 
            autocomplete="off"
        >

        <button type="submit">
            <i class="fa-solid fa-paper-plane"></i>
        </button>
    </form>

</div>
<script>
    window.FD_AI_CONFIG = {
        endpoint: '/FD-Tech/user/ai_assistant.php',
        sellerSendEndpoint: '/FD-Tech/user/chat/seller_chat_send.php',
        sellerFetchEndpoint: '/FD-Tech/user/chat/seller_chat_fetch.php',
        isLoggedIn: <?= $is_logged_in ? 'true' : 'false' ?>
    };
</script>
<script src="/FD-Tech/assets/js/ai_assistant.js"></script>
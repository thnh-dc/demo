document.addEventListener('DOMContentLoaded', function () {
    const floatingBtn = document.getElementById('aiFloatingBtn');
    const chatBox = document.getElementById('aiChatBox');
    const chatClose = document.getElementById('aiChatClose');
    const chatForm = document.getElementById('aiChatForm');
    const chatInput = document.getElementById('aiChatInput');
    const chatMessages = document.getElementById('aiChatMessages');
    const tabButtons = document.querySelectorAll('.ai-tab-btn');
    const chatTitle = document.getElementById('chatBoxTitle');
    const chatSubTitle = document.getElementById('chatBoxSubTitle');
    let chatMode = 'ai';
    let sellerPolling = null;
    if (floatingBtn) {
        floatingBtn.addEventListener('click', function () {
            chatBox.classList.add('active');
            chatInput.focus();
        });
    }
    if (chatClose) {
        chatClose.addEventListener('click', function () {
            chatBox.classList.remove('active');
        });
    }
    tabButtons.forEach(function (button) {
        button.addEventListener('click', function () {
            tabButtons.forEach(function (btn) {
                btn.classList.remove('active');
            });
            this.classList.add('active');
            chatMode = this.dataset.mode;
            chatMessages.innerHTML = '';
            if (chatMode === 'ai') {
                clearInterval(sellerPolling);
                sellerPolling = null;
                chatTitle.innerText = 'FD Bot';
                chatSubTitle.innerText = 'Trợ lí AI hỗ trợ mua hàng';
                chatInput.placeholder = 'Nhập câu hỏi...';
                appendMessage('Xin chào! Mình là FD Bot, bạn cần hỗ trợ gì?', 'bot');
                return;
            }
            if (chatMode === 'seller') {
                chatTitle.innerText = 'Nhắn với người bán';
                chatSubTitle.innerText = 'Trao đổi trực tiếp';
                chatInput.placeholder = 'Nhập tin nhắn cho người bán...';
                if (!window.FD_AI_CONFIG.isLoggedIn) {
                    appendMessage('Bạn cần đăng nhập để nhắn với người bán.', 'bot');
                    return;
                }
                loadSellerMessages();
                clearInterval(sellerPolling);
                sellerPolling = setInterval(loadSellerMessages, 3000);
            }
        });
    });
    if (chatForm) {
        chatForm.addEventListener('submit', function (e) {
            e.preventDefault();
            const message = chatInput.value.trim();
            if (message === '') {
                return;
            }
            appendMessage(message, 'user');
            chatInput.value = '';
            if (chatMode === 'ai') {
                sendToAI(message);
                return;
            }
            sendToSeller(message);
        });
    }
    function sendToAI(message) {
        appendMessage('Đang suy nghĩ...', 'bot');
        fetch(window.FD_AI_CONFIG.endpoint, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: 'message=' + encodeURIComponent(message)
        })
            .then(function (response) {
                return response.text();
            })
            .then(function (text) {
                let data;
                try {
                    data = JSON.parse(text);
                } catch (error) {
                    throw new Error('PHP không trả về JSON hợp lệ.');
                }
                removeLoadingMessage();
                appendMessage(data.reply, 'bot');
            })
            .catch(function () {
                removeLoadingMessage();
                appendMessage('Xin lỗi, hiện tại mình chưa thể phản hồi. Bạn thử lại sau nhé.', 'bot');
            });
    }
    function sendToSeller(message) {
        if (!window.FD_AI_CONFIG.isLoggedIn) {
            appendMessage('Bạn cần đăng nhập để nhắn với người bán.', 'bot');
            return;
        }
        fetch(window.FD_AI_CONFIG.sellerSendEndpoint, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: 'message=' + encodeURIComponent(message)
        })
            .then(function (response) {
                return response.json();
            })
            .then(function (data) {
                if (!data.success) {
                    appendMessage(data.message, 'bot');
                    return;
                }
                loadSellerMessages();
            })
            .catch(function () {
                appendMessage('Không thể gửi tin nhắn cho người bán lúc này.', 'bot');
            });
    }
    function loadSellerMessages() {
        if (chatMode !== 'seller' || !window.FD_AI_CONFIG.isLoggedIn) {
            return;
        }
        fetch(window.FD_AI_CONFIG.sellerFetchEndpoint)
            .then(function (response) {
                return response.json();
            })
            .then(function (data) {
                if (!data.success) {
                    return;
                }
                chatMessages.innerHTML = '';
                if (data.messages.length === 0) {
                    appendMessage('Bạn có thể gửi tin nhắn cho người bán tại đây.', 'bot');
                    return;
                }
                data.messages.forEach(function (msg) {
                    const sender = msg.sender_type === 'user' ? 'user' : 'bot';
                    appendMessage(msg.message, sender, false);
                });
            });
    }
    function appendMessage(text, sender, allowHtml = true) {
        const messageDiv = document.createElement('div');
        messageDiv.className = 'ai-message ai-' + sender;

        if (allowHtml) {
            messageDiv.innerHTML = text;
        } else {
            messageDiv.textContent = text;
        }

        chatMessages.appendChild(messageDiv);
        chatMessages.scrollTop = chatMessages.scrollHeight;
    }
    function removeLoadingMessage() {
        const botMessages = chatMessages.querySelectorAll('.ai-bot');
        const lastBotMessage = botMessages[botMessages.length - 1];

        if (lastBotMessage && lastBotMessage.innerText.trim() === 'Đang suy nghĩ...') {
            lastBotMessage.remove();
        }
    }
});
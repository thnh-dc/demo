<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (isset($_SESSION['noti_message']) && isset($_SESSION['noti_type'])): 
    $message = $_SESSION['noti_message'];
    $type = $_SESSION['noti_type'];

    unset($_SESSION['noti_message']);
    unset($_SESSION['noti_type']);
?>
    <link rel="stylesheet" href="../assets/css/style_notification.css">

    <div id="noti-container" class="noti-container">
        <div class="noti-box <?= htmlspecialchars($type) ?>">
            <div class="noti-icon">
                <?php if ($type == 'success'): ?>
                    ✓
                <?php elseif ($type == 'error'): ?>
                    ✕
                <?php else: ?>
                    i
                <?php endif; ?>
            </div>
            <div class="noti-content">
                <?= htmlspecialchars($message) ?>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var noti = document.getElementById('noti-container');
            if (noti) {
                setTimeout(function() {
                    noti.classList.add('noti-fade-out');
                    setTimeout(function() {
                        noti.remove();
                    }, 500);
                }, 4000);
            }
        });
    </script>
<?php endif; ?>
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user_id'])) {
    $_SESSION['noti_message'] = 'Oppss, bạn chưa đăng nhập rồi!';
    $_SESSION['noti_type'] = 'error';

    header("Location: /FD-Tech/auth/login.php");
    exit();
}
?>
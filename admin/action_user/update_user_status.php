<?php
session_start();
require_once '../../config/database.php';
require_once __DIR__ . '/../check_admin.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['noti_message'] = 'Phương thức không hợp lệ!';
    $_SESSION['noti_type'] = 'error';
    header("Location: ../list_users.php");
    exit();
}
$user_id = isset($_POST['user_id']) ? (int) $_POST['user_id'] : 0;
$status = $_POST['status'] ?? '';

$allowed_status = ['active', 'blocked'];
if ($user_id <= 0 || !in_array($status, $allowed_status)) {
    $_SESSION['noti_message'] = 'Dữ liệu không hợp lệ!';
    $_SESSION['noti_type'] = 'error';

    header("Location: ../list_users.php");
    exit();
}
try {
    $stmt = $pdo->prepare("
        UPDATE users 
        SET status = ?
        WHERE id = ? 
        AND role = 'user'
    ");
    $stmt->execute([$status, $user_id]);
    if ($stmt->rowCount() > 0) {
        $_SESSION['noti_message'] = $status === 'blocked'
            ? 'Đã khóa tài khoản người dùng!'
            : 'Đã mở khóa tài khoản người dùng!';
        $_SESSION['noti_type'] = 'success';
    } else {
        $_SESSION['noti_message'] = 'Không tìm thấy người dùng hoặc trạng thái không thay đổi!';
        $_SESSION['noti_type'] = 'error';
    }

} catch (PDOException $e) {
    $_SESSION['noti_message'] = 'Lỗi hệ thống khi cập nhật trạng thái người dùng!';
    $_SESSION['noti_type'] = 'error';
}
$redirect = $_POST['redirect'] ?? '../list_users.php';
header("Location: " . $redirect);
exit();
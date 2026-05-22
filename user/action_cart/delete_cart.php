<?php
session_start();
require_once '../../config/database.php';
header('Content-Type: application/json; charset=utf-8');
$user_id = $_SESSION['user_id'] ?? 0;
$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
if ($user_id <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Bạn cần đăng nhập để thực hiện thao tác này.'
    ]);
    exit;
}
if ($id <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Sản phẩm không hợp lệ.'
    ]);
    exit;
}
try {
    $stmt = $pdo->prepare("
        DELETE FROM cart_items 
        WHERE id = ? 
        AND user_id = ?
    ");
    $stmt->execute([$id, $user_id]);
    if ($stmt->rowCount() > 0) {
        echo json_encode([
            'success' => true,
            'message' => 'Đã xóa sản phẩm khỏi giỏ hàng.'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Không tìm thấy sản phẩm trong giỏ hàng.'
        ]);
    }
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Lỗi hệ thống, không thể xóa sản phẩm.'
    ]);
}
?>
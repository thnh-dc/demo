<?php
session_start();
require_once '../../config/database.php';
header('Content-Type: application/json; charset=utf-8');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Phương thức không hợp lệ.'
    ]);
    exit;
}
if (!isset($_SESSION['user_id'])) {
    $_SESSION['noti_message'] = 'Oppss, bạn chưa đăng nhập rồi!';
    $_SESSION['noti_type'] = 'error';
    echo json_encode([
        'success' => false,
        'message' => 'Oppss, bạn chưa đăng nhập rồi!',
        'redirect' => '/auth/login.php'
    ]);
    exit;
}
$user_id = (int) $_SESSION['user_id'];
$product_id = isset($_POST['product_id']) ? (int) $_POST['product_id'] : 0;
$rating = isset($_POST['rating']) ? (int) $_POST['rating'] : 5;
$comment = isset($_POST['comment']) ? trim($_POST['comment']) : '';
if ($product_id <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Sản phẩm không hợp lệ.'
    ]);
    exit;
}
if ($rating < 1 || $rating > 5) {
    echo json_encode([
        'success' => false,
        'message' => 'Mức đánh giá không hợp lệ.'
    ]);
    exit;
}
if ($comment === '') {
    echo json_encode([
        'success' => false,
        'message' => 'Vui lòng nhập nội dung đánh giá.'
    ]);
    exit;
}
try {
    $stmtProduct = $pdo->prepare("
        SELECT id FROM products WHERE id = ? LIMIT 1
    ");
    $stmtProduct->execute([$product_id]);
    $product = $stmtProduct->fetch(PDO::FETCH_ASSOC);
    if (!$product) {
        echo json_encode([
            'success' => false,
            'message' => 'Sản phẩm không tồn tại.'
        ]);
        exit;
    }
    $stmt = $pdo->prepare("
        INSERT INTO product_reviews (
            product_id, user_id, rating, comment
        ) 
        VALUES (
            :product_id, :user_id, :rating, :comment
        )
    ");
    $stmt->execute([
        'product_id' => $product_id,
        'user_id' => $user_id,
        'rating' => $rating,
        'comment' => $comment
    ]);
    echo json_encode([
        'success' => true,
        'message' => 'Cảm ơn bạn đã gửi đánh giá!'
    ]);
    exit;
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Lỗi lưu trữ dữ liệu.'
    ]);
    exit;
}
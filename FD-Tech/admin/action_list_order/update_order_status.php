<?php
session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../check_admin.php';

header('Content-Type: application/json; charset=utf-8');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Phương thức không hợp lệ.'
    ]);
    exit;
}
$order_id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
$new_status = $_POST['status'] ?? '';

$allowed = ['pending', 'processing', 'shipped', 'completed', 'cancelled'];
$statusText = [
    'pending' => 'Chờ xác nhận',
    'processing' => 'Đang xử lý',
    'shipped' => 'Đang giao hàng',
    'completed' => 'Hoàn thành',
    'cancelled' => 'Đã hủy'
];
if ($order_id <= 0 || !in_array($new_status, $allowed)) {
    echo json_encode([
        'success' => false,
        'message' => 'Dữ liệu cập nhật không hợp lệ.'
    ]);
    exit;
}
try {
    $pdo->beginTransaction();
    $stmtOrder = $pdo->prepare("
        SELECT id, status
        FROM orders
        WHERE id = ?
        LIMIT 1
    ");
    $stmtOrder->execute([$order_id]);
    $order = $stmtOrder->fetch(PDO::FETCH_ASSOC);
    if (!$order) {
        throw new Exception('Không tìm thấy đơn hàng.');
    }
    $old_status = $order['status'];
    if ($old_status === $new_status) {
        throw new Exception('Trạng thái đơn hàng không thay đổi.');
    }

    if ($old_status === 'cancelled' && $new_status !== 'cancelled') {
        throw new Exception('Đơn hàng đã hủy không thể chuyển sang trạng thái khác.');
    }
    if ($old_status === 'completed' && $new_status === 'cancelled') {
        throw new Exception('Đơn hàng đã hoàn thành không thể hủy.');
    }
    if ($new_status === 'cancelled' && $old_status !== 'cancelled') {
        $stmtItems = $pdo->prepare("
            SELECT product_id, quantity
            FROM order_items
            WHERE order_id = ?
        ");
        $stmtItems->execute([$order_id]);
        $items = $stmtItems->fetchAll(PDO::FETCH_ASSOC);
        if (empty($items)) {
            throw new Exception('Không tìm thấy chi tiết sản phẩm của đơn hàng.');
        }
        $stmtReturnStock = $pdo->prepare("
            UPDATE products
            SET stock_quantity = stock_quantity + ?
            WHERE id = ?
        ");
        foreach ($items as $item) {
            $stmtReturnStock->execute([
                (int) $item['quantity'],
                (int) $item['product_id']
            ]);
        }
    }
    // Cập nhật trạng thái đơn hàng
    $stmtUpdate = $pdo->prepare("
        UPDATE orders
        SET status = ?, updated_at = NOW()
        WHERE id = ?
    ");
    $stmtUpdate->execute([$new_status, $order_id]);
    $pdo->commit();
    echo json_encode([
        'success' => true,
        'message' => 'Đã cập nhật đơn hàng sang trạng thái: ' . $statusText[$new_status],
        'status' => $new_status,
        'status_text' => $statusText[$new_status]
    ]);
    exit;
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
    exit;
}?>
<?php
require_once '../config/database.php';

header('Content-Type: application/json; charset=utf-8');

$rawData = file_get_contents('php://input');
$data = json_decode($rawData, true);

file_put_contents(
    __DIR__ . '/sepay_log.txt',
    date('Y-m-d H:i:s') . " - " . $rawData . PHP_EOL,
    FILE_APPEND
);

if (!$data) {
    http_response_code(400);

    echo json_encode([
        'success' => false,
        'message' => 'Dữ liệu webhook không hợp lệ.'
    ]);
    exit;
}

$amount = $data['transferAmount']
    ?? $data['amount']
    ?? $data['money']
    ?? 0;

$content = $data['content']
    ?? $data['description']
    ?? $data['transactionContent']
    ?? '';

$amount = (float) $amount;
$content = strtoupper(trim($content));

if ($amount <= 0 || $content === '') {
    http_response_code(400);

    echo json_encode([
        'success' => false,
        'message' => 'Thiếu số tiền hoặc nội dung chuyển khoản.'
    ]);
    exit;
}
preg_match('/FDTECH(\d+)/', $content, $matches);

if (empty($matches[1])) {
    echo json_encode([
        'success' => true,
        'message' => 'Không tìm thấy mã đơn hàng FDTECH trong nội dung chuyển khoản.'
    ]);
    exit;
}

$order_id = (int) $matches[1];

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("
        SELECT 
            id,
            total_amount,
            status,
            payment_method,
            payment_status,
            payment_code
        FROM orders
        WHERE id = ?
        LIMIT 1
    ");

    $stmt->execute([$order_id]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        throw new Exception('Không tìm thấy đơn hàng.');
    }

    if ($order['payment_method'] !== 'bank') {
        throw new Exception('Đơn hàng này không phải đơn chuyển khoản.');
    }

    if ($order['payment_status'] === 'paid') {
        $pdo->commit();

        echo json_encode([
            'success' => true,
            'message' => 'Đơn hàng đã được xác nhận thanh toán trước đó.'
        ]);
        exit;
    }

    $paymentCode = strtoupper($order['payment_code'] ?? '');

    if ($paymentCode === '' || strpos($content, $paymentCode) === false) {
        throw new Exception('Nội dung chuyển khoản không khớp mã đơn hàng.');
    }

    if ($amount < (float) $order['total_amount']) {
        throw new Exception('Số tiền chuyển khoản chưa đủ.');
    }

    $stmtUpdate = $pdo->prepare("
        UPDATE orders
        SET 
            payment_status = 'paid',
            status = 'processing',
            paid_at = NOW(),
            updated_at = NOW()
        WHERE id = ?
    ");

    $stmtUpdate->execute([$order_id]);

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Xác nhận thanh toán thành công.'
    ]);
    exit;

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    http_response_code(400);

    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
    exit;
}
?>
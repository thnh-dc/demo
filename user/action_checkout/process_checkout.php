<?php
session_start();
require_once '../../config/database.php';
$user_id = $_SESSION['user_id'] ?? 0;

if ($user_id <= 0) {
    header("Location: ../../auth/login.php");
    exit();
}
$address = trim($_POST['address'] ?? '');
$selectedItems = $_POST['selected_items'] ?? '';
$payment_method = $_POST['payment_method'] ?? 'cod';
if (empty($selectedItems)) {
    header("Location: ../cart.php?error=no_items");
    exit();
}
$selectedArray = array_filter(explode(',', $selectedItems));

if (empty($selectedArray)) {
    header("Location: ../cart.php?error=no_items");
    exit();
}
if (!in_array($payment_method, ['cod', 'bank'])) {
    $payment_method = 'cod';
}
if ($payment_method === 'bank') {
    $payment_note = 'Chuyển khoản ngân hàng';
    $order_status = 'pending';
    $payment_status = 'unpaid';
} else {
    $payment_note = 'Thanh toán khi nhận hàng';
    $order_status = 'processing';
    $payment_status = 'unpaid';
}
$placeholders = implode(',', array_fill(0, count($selectedArray), '?'));

$stmt = $pdo->prepare("
    SELECT 
        c.product_id,
        c.quantity,
        c.id,
        p.price,
        p.discount_price,
        p.stock_quantity,
        COALESCE(NULLIF(p.discount_price, 0), p.price) AS display_price,
        p.name AS product_name,
        p.image_url AS product_image
    FROM cart_items c
    JOIN products p ON c.product_id = p.id
    WHERE c.user_id = ?
    AND c.id IN ($placeholders)
");
$params = array_merge([$user_id], $selectedArray);
$stmt->execute($params);
$cartItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
if (empty($cartItems)) {
    header("Location: ../cart.php?error=cart_empty");
    exit();
}
$total = 0;
foreach ($cartItems as $item) {
    if ((int)$item['quantity'] > (int)$item['stock_quantity']) {
        $_SESSION['noti_message'] = 'Sản phẩm ' . $item['product_name'] . ' không đủ tồn kho.';
        $_SESSION['noti_type'] = 'error';

        header("Location: ../cart.php");
        exit();
    }
    $total += $item['display_price'] * $item['quantity'];
}
$pdo->beginTransaction();
try {
    $stmtOrder = $pdo->prepare("
        INSERT INTO orders(
            user_id,
            total_amount,
            status,
            shipping_address,
            note,
            payment_method,
            payment_status
        )
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $stmtOrder->execute([
        $user_id,
        $total,
        $order_status,
        $address,
        $payment_note,
        $payment_method,
        $payment_status
    ]);
    $order_id = $pdo->lastInsertId();
    $payment_code = null;
    if ($payment_method === 'bank') {
        $payment_code = 'FDTECH' . $order_id;
        $stmtPaymentCode = $pdo->prepare("
            UPDATE orders
            SET 
                payment_code = ?,
                note = ?
            WHERE id = ?
        ");
        $stmtPaymentCode->execute([
            $payment_code,
            'Chuyển khoản ngân hàng - Nội dung: ' . $payment_code,
            $order_id
        ]);
    }
    $stmtItem = $pdo->prepare("
        INSERT INTO order_items(
            order_id,
            product_id,
            quantity,
            price,
            product_name,
            product_image
        )
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmtUpdateStock = $pdo->prepare("
        UPDATE products
        SET stock_quantity = stock_quantity - ?
        WHERE id = ?
        AND stock_quantity >= ?
    ");
    foreach ($cartItems as $item) {
        $product_id = (int)$item['product_id'];
        $quantity = (int)$item['quantity'];
        $stmtUpdateStock->execute([
            $quantity,
            $product_id,
            $quantity
        ]);
        if ($stmtUpdateStock->rowCount() <= 0) {
            throw new Exception("Sản phẩm " . $item['product_name'] . " không đủ tồn kho.");
        }
        $stmtItem->execute([
            $order_id,
            $product_id,
            $quantity,
            $item['display_price'],
            $item['product_name'],
            $item['product_image']
        ]);
    }
    $deletePlaceholders = implode(',', array_fill(0, count($selectedArray), '?'));
    $stmtDelete = $pdo->prepare("
        DELETE FROM cart_items
        WHERE user_id = ?
        AND id IN ($deletePlaceholders)
    ");
    $deleteParams = array_merge([$user_id], $selectedArray);
    $stmtDelete->execute($deleteParams);
    $pdo->commit();
    if ($payment_method === 'bank') {
        header("Location: bank_payment.php?order_id=" . $order_id);
        exit();
    }
    header("Location: ../checkout.php?status=success&order_id=" . $order_id);
    exit();
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $_SESSION['noti_message'] = $e->getMessage();
    $_SESSION['noti_type'] = 'error';
    header("Location: ../cart.php");
    exit();
}
?>
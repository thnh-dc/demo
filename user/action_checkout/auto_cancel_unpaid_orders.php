<?php
require_once __DIR__ . '/../../config/database.php';

function autoCancelUnpaidBankOrders($pdo, $expireMinutes = 15)
{
    try {
        $stmtOrders = $pdo->prepare("
            SELECT id
            FROM orders
            WHERE payment_method = 'bank'
            AND payment_status = 'unpaid'
            AND status = 'pending'
            AND created_at <= DATE_SUB(NOW(), INTERVAL ? MINUTE)
        ");

        $stmtOrders->execute([$expireMinutes]);
        $orders = $stmtOrders->fetchAll(PDO::FETCH_ASSOC);

        if (empty($orders)) {
            return 0;
        }

        $cancelledCount = 0;

        foreach ($orders as $order) {
            $order_id = (int) $order['id'];

            $pdo->beginTransaction();

            try {
                $stmtCheck = $pdo->prepare("
                    SELECT id, status, payment_status
                    FROM orders
                    WHERE id = ?
                    LIMIT 1
                ");
                $stmtCheck->execute([$order_id]);
                $currentOrder = $stmtCheck->fetch(PDO::FETCH_ASSOC);

                if (
                    !$currentOrder ||
                    $currentOrder['status'] !== 'pending' ||
                    $currentOrder['payment_status'] !== 'unpaid'
                ) {
                    $pdo->rollBack();
                    continue;
                }

                $stmtItems = $pdo->prepare("
                    SELECT product_id, quantity
                    FROM order_items
                    WHERE order_id = ?
                ");
                $stmtItems->execute([$order_id]);
                $items = $stmtItems->fetchAll(PDO::FETCH_ASSOC);

                if (empty($items)) {
                    $pdo->rollBack();
                    continue;
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

                $stmtCancel = $pdo->prepare("
                    UPDATE orders
                    SET 
                        status = 'cancelled',
                        note = CONCAT(IFNULL(note, ''), ' | Tự động hủy do quá thời gian thanh toán'),
                        updated_at = NOW()
                    WHERE id = ?
                    AND status = 'pending'
                    AND payment_status = 'unpaid'
                ");

                $stmtCancel->execute([$order_id]);

                $pdo->commit();
                $cancelledCount++;

            } catch (Exception $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
            }
        }
        return $cancelledCount;

    } catch (PDOException $e) {
        return 0;
    }
}
?>
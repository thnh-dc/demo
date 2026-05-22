<?php
    require_once __DIR__ . '/../../config/database.php';

    $order_id = $_GET['order_id'] ?? 0;

    $stmt = $pdo->prepare("
        SELECT 
            p.name, 
            p.image_url, 
            oi.quantity, 
            oi.price,
            o.shipping_address,
            o.note,
            u.full_name,
            u.phone
        FROM order_items oi
        JOIN products p ON oi.product_id = p.id
        JOIN orders o ON oi.order_id = o.id
        JOIN users u ON o.user_id = u.id
        WHERE oi.order_id = ?
    ");
    $stmt->execute([$order_id]);

    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!$items) {
        echo "Không có sản phẩm";
        exit;
    }
    if ($items) {
        $info = $items[0];

        echo '
        <div class="order-customer">
            <p><b>Người nhận:</b> '.$info['full_name'].'</p>
            <p><b>Số điện thoại:</b> '.$info['phone'].'</p>
            <p><b>Địa chỉ nhận:</b> '.$info['shipping_address'].'</p>
            <p><b> Thông tin thanh toán:</b> '.$info['note'].'</p>
        </div>
        ';
    }
        foreach ($items as $item) {
            echo '
                <div class="order-item">
                    <div class="item-left">
                        <img src="'.$item['image_url'].'" class="item-img">
                        <span class="item-name">'.$item['name'].'</span>
                    </div>

                    <div class="item-right">
                        <span class="item-qty">x'.$item['quantity'].'</span>
                        <span class="item-price">'.number_format($item['price']).'₫</span>
                    </div>
                </div>
                ';
        }
?>
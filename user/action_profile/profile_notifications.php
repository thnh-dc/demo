<?php
try {
    $stmt_noti = $pdo->prepare("SELECT id, status, updated_at FROM orders WHERE user_id = ? ORDER BY updated_at DESC LIMIT 10");
    $stmt_noti->execute([$user_id]);
    $recent_orders = $stmt_noti->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $recent_orders = [];
}

function getNotificationText($order) {
    $order_id = str_pad($order['id'], '0', STR_PAD_LEFT);
    $time = date('H:i - d/m/Y', strtotime($order['updated_at'])); 
    
    switch ($order['status']) {
        case 'pending':
            return "Đơn hàng <strong>#FD{$order_id}</strong> đã được ghi nhận và đang chờ xác nhận. <em>($time)</em>";
        case 'processing':
            return "Đơn hàng <strong>#FD{$order_id}</strong> đang được được xử lý. <em>($time)</em>";
        case 'shipped':
        case 'shipping':
            return "Đơn hàng <strong>#FD{$order_id}</strong> đang trên đường giao đến bạn. Chú ý điện thoại nhé! <em>($time)</em>";
        case 'completed':
            return "Đơn hàng <strong>#FD{$order_id}</strong> đã giao thành công. Cảm ơn bạn! <em>($time)</em>";
        case 'cancelled':
            return "Đơn hàng <strong>#FD{$order_id}</strong> đã bị hủy. <em>($time)</em>";
        default:
            return "Đơn hàng <strong>#FD{$order_id}</strong> vừa được cập nhật trạng thái. <em>($time)</em>";
    }
}
?>

<div class="profile-header">
    <h2>Thông báo của tôi</h2>
    <p>Cập nhật mới nhất về đơn hàng của bạn</p>
</div>

<?php if (empty($recent_orders)): ?>
    <p style="color: #888;">Bạn chưa có thông báo nào.</p>
<?php else: ?>
    <ul style="list-style-type: none; padding: 0;">
        <?php foreach ($recent_orders as $order): ?>
            <li style="padding: 10px 0; border-bottom: 1px dashed #eee; color: #444;">
                <i class="fas fa-angle-right" style="color: #1a9bb8; margin-right: 5px;"></i> 
                <?php echo getNotificationText($order); ?>
            </li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>
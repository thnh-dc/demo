<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$user_id = $user_id ?? ($_SESSION['user_id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    // Xử lý Hủy đơn và TỰ ĐỘNG HOÀN SỐ LƯỢNG VÀO KHO
    if ($_POST['action'] == 'cancel_order') {
        $order_id = $_POST['order_id'];
        try {
            // 1. Cập nhật trạng thái đơn hàng thành đã hủy
            $stmt = $pdo->prepare("UPDATE orders SET status = 'cancelled' WHERE id = ? AND user_id = ? AND status IN ('pending', 'processing')");
            
            if ($stmt->execute([$order_id, $user_id])) {
                if ($stmt->rowCount() > 0) {
                    // 2. Lấy danh sách sản phẩm và số lượng từ order_items
                    $st_get_items = $pdo->prepare("SELECT product_id, quantity FROM order_items WHERE order_id = ?");
                    $st_get_items->execute([$order_id]);
                    $cancelled_items = $st_get_items->fetchAll(PDO::FETCH_ASSOC);

                    // 3. Cộng trả lại số lượng vào kho (bảng products, cột stock_quantity)
                    $st_update_stock = $pdo->prepare("UPDATE products SET stock_quantity = stock_quantity + ? WHERE id = ?");
                    foreach ($cancelled_items as $item) {
                        $st_update_stock->execute([$item['quantity'], $item['product_id']]);
                    }
                }
                
                $_SESSION['noti_message'] = 'Đã hủy đơn hàng thành công!';
                $_SESSION['noti_type'] = 'success';
            }
        } catch (PDOException $e) {
            $_SESSION['noti_message'] = 'Lỗi khi hủy đơn hàng!';
            $_SESSION['noti_type'] = 'error';
        }
        header("Location: profile.php?action=orders");
        exit();
    } 
    // Xử lý Xác nhận đã nhận hàng
    elseif ($_POST['action'] == 'confirm_received') {
        try {
            $stmt = $pdo->prepare("UPDATE orders SET status = 'completed' WHERE id = ? AND user_id = ? AND status IN ('shipped', 'shipping')");
            if ($stmt->execute([$_POST['order_id'], $user_id])) {
                $_SESSION['noti_message'] = 'Xác nhận đã nhận hàng thành công!';
                $_SESSION['noti_type'] = 'success';
            }
        } catch (PDOException $e) {
            $_SESSION['noti_message'] = 'Lỗi khi xác nhận đơn hàng!';
            $_SESSION['noti_type'] = 'error';
        }
        header("Location: profile.php?action=orders");
        exit();
    }
}

$current_status = $_GET['status'] ?? 'all';

try {
    if ($current_status == 'all') {
        $stmt = $pdo->prepare("
            SELECT o.*, u.phone AS user_phone 
            FROM orders o 
            LEFT JOIN users u ON o.user_id = u.id 
            WHERE o.user_id = ? 
            ORDER BY o.created_at DESC
        ");
        $stmt->execute([$user_id]);
    } else {
        $stmt = $pdo->prepare("
            SELECT o.*, u.phone AS user_phone 
            FROM orders o 
            LEFT JOIN users u ON o.user_id = u.id 
            WHERE o.user_id = ? AND o.status = ? 
            ORDER BY o.created_at DESC
        ");
        $stmt->execute([$user_id, $current_status]);
    }
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $orders = [];
}

if (!function_exists('translateOrderStatus')) {
    function translateOrderStatus($status)
    {
        $labels = [
            'pending' => 'Chờ xác nhận',
            'processing' => 'Đang xử lý',
            'shipped' => 'Đang giao hàng',
            'shipping' => 'Đang giao hàng',
            'completed' => 'Đã giao',
            'cancelled' => 'Đã hủy'
        ];
        $text = $labels[$status] ?? $status;
        return '<span style="color: #26aa99; font-weight: 500;">' . htmlspecialchars($text) . '</span>';
    }
}
?>

<link rel="stylesheet" href="../assets/css/style_profile_order.css">

<style>
    .order-customer-info {
        background-color: #f9f9f9;
        padding: 12px 15px;
        margin-bottom: 15px;
        margin-left: -15px; 
        margin-right: -15px;  
        text-align: left;
        font-size: 14px;      
        color: #444;
        line-height: 1.6;
        border-top: 1px solid #f0f0f0;
        border-bottom: 1px solid #f0f0f0;
    }
    .order-customer-info div {
        margin-bottom: 6px;
    }
    .order-customer-info div:last-child {
        margin-bottom: 0;
    }
    .order-customer-info strong {
        color: #222;
        font-weight: 500;
    }

    .order-payment-method-box {
        text-align: left;
        font-size: 14px; 
        color: #444;
        padding: 12px 0;
        margin-top: 15px;
        border-top: 1px dashed #eee; 
        
        display: flex;
        flex-direction: column;
        gap: 5px;            
        word-break: break-word;
        overflow-wrap: break-word;
    }
    .order-payment-method-box strong {
        color: #222;
        font-weight: 500;
    }
    .order-payment-method-box span {
        color: #666;
        line-height: 1.5;
        background: #f5f5f5;
        padding: 8px 12px;
        border-radius: 4px;
        display: block;
    }
</style>

<div class="profile-orders-header">
    <h2>Đơn hàng của tôi</h2>
    <p>Theo dõi tình trạng các đơn hàng đã đặt</p>
</div>

<div class="order-filter-tabs">
    <a href="profile.php?action=orders&status=all" class="<?= $current_status == 'all' ? 'active' : '' ?>">Tất cả</a>
    <a href="profile.php?action=orders&status=pending" class="<?= $current_status == 'pending' ? 'active' : '' ?>">Chờ xác nhận</a>
    <a href="profile.php?action=orders&status=processing" class="<?= $current_status == 'processing' ? 'active' : '' ?>">Đang xử lý</a>
    <a href="profile.php?action=orders&status=shipped" class="<?= $current_status == 'shipped' ? 'active' : '' ?>">Đang giao</a>
    <a href="profile.php?action=orders&status=completed" class="<?= $current_status == 'completed' ? 'active' : '' ?>">Đã giao</a>
    <a href="profile.php?action=orders&status=cancelled" class="<?= $current_status == 'cancelled' ? 'active' : '' ?>">Đã hủy</a>
</div>

<?php if (empty($orders)): ?>
    <div class="order-empty-state">
        <p>Bạn chưa có đơn hàng nào.</p>
    </div>
<?php else: ?>
    <div>
        <?php foreach ($orders as $order):
            $st_items = $pdo->prepare("SELECT * FROM order_items WHERE order_id = ?");
            $st_items->execute([$order['id']]);
            $items = $st_items->fetchAll(PDO::FETCH_ASSOC);

            $first_name = !empty($items) ? $items[0]['product_name'] : 'Đơn hàng #' . $order['id'];
            ?>
            <div class="order-card">
                
                <div class="order-summary">
                    <div class="order-summary-left">
                        <span class="order-summary-title"><?= htmlspecialchars($first_name) ?></span>
                        <div class="order-summary-status"><?= translateOrderStatus($order['status']) ?></div>
                    </div>
                    
                    <div class="order-summary-price">
                        <span>Giá tiền: </span>
                        <strong><?= number_format($order['total_amount'], 0, ',', '.') ?>đ</strong>
                    </div>

                    <button type="button" class="btn-toggle-detail" onclick="toggleOrder(<?= $order['id'] ?>, this)">
                        Xem chi tiết
                    </button>
                </div>

                <div id="detail-<?= $order['id'] ?>" class="order-detail-box">
                    
                    <div class="order-info-row">
                        <span>Mã đơn: <strong>#<?= $order['id'] ?></strong></span>
                        <span>Ngày đặt: <strong><?= date('d/m/Y H:i', strtotime($order['created_at'])) ?></strong></span>
                    </div>

                    <?php 
                        $display_phone = !empty($order['user_phone']) ? $order['user_phone'] : 'Chưa cập nhật SĐT'; 
                        $display_address = !empty($order['shipping_address']) ? $order['shipping_address'] : 'Chưa cập nhật địa chỉ';
                        $display_payment = !empty($order['note']) ? $order['note'] : 'Thanh toán khi nhận hàng';
                    ?>

                    <div class="order-customer-info">
                        <div>
                            <strong>Số điện thoại:</strong> <span><?= htmlspecialchars($display_phone) ?></span>
                        </div>
                        <div>
                            <strong>Địa chỉ giao hàng:</strong> <span><?= htmlspecialchars($display_address) ?></span>
                        </div>
                    </div>

                    <?php foreach ($items as $it):
                        $img_link = !empty($it['product_image']) ? $it['product_image'] : '';
                        $p_name = !empty($it['product_name']) ? $it['product_name'] : 'Sản phẩm không xác định';
                        ?>
                        <div class="order-item">
                            <div class="order-item-img">
                                <img src="<?= htmlspecialchars($img_link) ?>" alt="Product" onerror="this.src='https://via.placeholder.com/70?text=No+Image'">
                            </div>
                            <div class="order-item-info">
                                <div class="order-item-name"><?= htmlspecialchars($p_name) ?></div>
                                <div class="order-item-meta">
                                    Số lượng: <?= $it['quantity'] ?> | Giá: <span class="order-item-price"><?= number_format($it['price'], 0, ',', '.') ?>đ</span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>

                    <div class="order-payment-method-box">
                        <strong>Phương thức thanh toán:</strong> <span><?= htmlspecialchars($display_payment) ?></span>
                    </div>

                    <div class="order-footer">
                        <div class="order-total-text">
                            <span>Tổng thanh toán: </span>
                            <span class="order-total-amount"><?= number_format($order['total_amount'], 0, ',', '.') ?>đ</span>
                        </div>

                        <?php if ($order['status'] == 'pending' || $order['status'] == 'processing'): ?>
                            <form action="" method="POST" style="margin: 0;" onsubmit="return confirm('Bạn có chắc chắn muốn hủy đơn hàng #<?= $order['id'] ?> này không?');">
                                <input type="hidden" name="action" value="cancel_order">
                                <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                                <button type="submit" class="btn-cancel-order">Hủy đơn hàng</button>
                            </form>
                        <?php elseif ($order['status'] == 'shipped' || $order['status'] == 'shipping'): ?>
                            <form action="" method="POST" style="margin: 0;" onsubmit="return confirm('Bạn xác nhận đã nhận được đơn hàng #<?= $order['id'] ?> này thành công?');">
                                <input type="hidden" name="action" value="confirm_received">
                                <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                                <button type="submit" class="btn-confirm-received" style="background-color: #26aa99; color: #fff; border: none; padding: 8px 15px; border-radius: 4px; cursor: pointer; font-size: 14px; margin-left: 10px;">Đã nhận được hàng</button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<script>
    function toggleOrder(id, btn) {
        var box = document.getElementById('detail-' + id);
        if (box.style.display === "none" || box.style.display === "") {
            box.style.display = "block";
            btn.innerText = "Đóng";
        } else {
            box.style.display = "none";
            btn.innerText = "Xem chi tiết";
        }
    }
</script>

<?php include '../includes/notification.php'; ?>
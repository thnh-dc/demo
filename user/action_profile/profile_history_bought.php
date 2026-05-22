<?php
// Đảm bảo file được nhúng hợp lệ và có biến $user_id từ profile.php
if (!isset($user_id)) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $user_id = $_SESSION['user_id'] ?? 0;
}

// 1. TRUY VẤN DỮ LIỆU THỐNG KÊ & LỊCH SỬ (CHỈ LẤY ĐƠN HÀNG THÀNH CÔNG)
try {
    // Chỉ lấy đơn hàng thành công
    $stmt_orders = $pdo->prepare("SELECT * FROM orders WHERE user_id = ? AND status = 'completed' ORDER BY created_at DESC");
    $stmt_orders->execute([$user_id]);
    $orders = $stmt_orders->fetchAll(PDO::FETCH_ASSOC);

    // Tính toán số liệu tổng quan để hiển thị
    $total_orders = count($orders);
    $total_spent = 0;

    foreach ($orders as $order) {
        $total_spent += $order['total_amount'] ?? 0;
    }

    // LOGIC TÍNH TOÁN DOANH THU 6 THÁNG GẦN NHẤT
    $months_data = [];

    for ($i = 5; $i >= 0; $i--) {
        $month_target = date('Y-m', strtotime("-$i months"));
        $month_label = "Thương mại " . date('m/y', strtotime("-$i months"));
        $month_label = "Tháng " . date('m/Y', strtotime("-$i months"));

        $months_data[$month_target] = [
            'label' => $month_label,
            'amount' => 0
        ];
    }

    // Duyệt qua các đơn hàng để gom tiền vào từng tháng tương ứng
    foreach ($orders as $order) {
        $order_month = date('Y-m', strtotime($order['created_at']));
        if (isset($months_data[$order_month])) {
            $months_data[$order_month]['amount'] += $order['total_amount'];
        }
    }

} catch (PDOException $e) {
    $orders = [];
    $total_orders = 0;
    $total_spent = 0;
    $months_data = [];
}
?>

<div class="profile-header">
    <h2>Thống kê & Lịch sử mua hàng</h2>
    <p>Xem lại các sản phẩm bạn đã sở hữu và biểu đồ chi tiêu</p>
</div>

<div class="profile-body-split">

    <div class="profile-form-area" style="max-width: 100%; flex: 1;">
        <div class="profile-form" style="max-width: 100%;">
            <h3
                style="margin-bottom: 15px; color: #333; font-size: 16px; border-bottom: 1px solid #eee; padding-bottom: 8px;">
                Danh sách đơn hàng đã mua
            </h3>

            <?php if (empty($orders)): ?>
                <div style="text-align: center; padding: 40px 10px; color: #888;">
                    <i class="fas fa-shopping-bag" style="font-size: 40px; margin-bottom: 10px; color: #ccc;"></i>
                    <p>Bạn chưa có đơn hàng thành công nào trong lịch sử.</p>
                </div>
            <?php else: ?>
                <div style="overflow-x: auto;">
                    <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 14px;">
                        <thead>
                            <tr style="background: #f8f9fa; border-bottom: 2px solid #eee;">
                                <th style="padding: 12px 8px; color: #555;">Mã Đơn</th>
                                <th style="padding: 12px 8px; color: #555;">Ngày mua</th>
                                <th style="padding: 12px 8px; color: #555;">Tổng tiền</th>
                                <th style="padding: 12px 8px; color: #555; text-align: center;">Trạng thái</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($orders as $order): ?>
                                <tr style="border-bottom: 1px solid #eee; transition: background 0.2s;">
                                    <td style="padding: 12px 8px; font-weight: bold; color: #1a9bb8;">
                                        #<?php echo $order['order_code'] ?? $order['id']; ?>
                                    </td>
                                    <td style="padding: 12px 8px; color: #666;">
                                        <?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?>
                                    </td>
                                    <td style="padding: 12px 8px; font-weight: bold; color: #db4437;">
                                        <?php echo number_format($order['total_amount'], 0, ',', '.'); ?>đ
                                    </td>
                                    <td style="padding: 12px 8px; text-align: center;">
                                        <span
                                            style="display: inline-block; padding: 4px 10px; border-radius: 20px; font-size: 12px; font-weight: 500; background-color: #d4edda; color: #155724;">
                                            Thành công
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="profile-avatar-area" style="flex-shrink: 0; width: 300px;">
        <div class="avatar-preview-box"
            style="width: 100%; height: auto; border-radius: 8px; padding: 20px; background: #fff; box-shadow: 0 2px 5px rgba(0,0,0,0.05); border: 1px solid #eee; flex-direction: column; display: flex; gap: 15px;">

            <h3
                style="font-size: 15px; color: #1a9bb8; margin: 0 0 5px 0; font-weight: bold; border-bottom: 1px solid #f1f1f1; padding-bottom: 8px; width: 100%; text-align: center;">
                TỔNG QUAN CHI TIÊU
            </h3>

            <div style="text-align: center; width: 100%;">
                <p style="font-size: 12px; color: #777; margin: 0 0 5px 0;">Tổng tích lũy mua sắm</p>
                <p style="font-size: 22px; font-weight: bold; color: #db4437; margin: 0;">
                    <?php echo number_format($total_spent, 0, ',', '.'); ?> <span style="font-size: 14px;">đ</span>
                </p>
            </div>

            <hr style="border: 0; border-top: 1px dashed #eee; width: 100%; margin: 5px 0;">

            <div
                style="display: flex; justify-content: space-between; width: 100%; font-size: 13px; color: #555; padding: 0 5px;">
                <span>Tổng đơn hàng đã mua:</span>
                <strong><?php echo $total_orders; ?> đơn</strong>
            </div>

            <?php if (!empty($orders)): ?>
                <hr style="border: 0; border-top: 1px solid #eee; width: 100%; margin: 10px 0;">

                <h4 style="font-size: 13px; color: #333; margin: 0 0 5px 0; font-weight: bold; width: 100%;">
                    Chi tiêu 6 tháng gần nhất
                </h4>

                <div style="display: flex; flex-direction: column; gap: 12px; width: 100%;">
                    <?php foreach ($months_data as $month): ?>
                        <div
                            style="display: flex; justify-content: space-between; font-size: 13px; color: #555; padding: 0 5px;">
                            <span><?php echo $month['label']; ?>:</span>
                            <strong style="color: #db4437;">
                                <?php echo number_format($month['amount'], 0, ',', '.'); ?>đ
                            </strong>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

        </div>

        <button type="button" class="btn-upload"
            style="margin-top: 15px; width: 100%; background-color: #1a9bb8; border-color: #1a9bb8; color: #fff;"
            onclick="window.location.href='../user/index.php'">
            <i class="fas fa-shopping-cart" style="margin-right: 5px;"></i> Tiếp tục mua sắm
        </button>

    </div>

</div>
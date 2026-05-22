<?php
session_start();
require_once '../config/database.php';
require_once __DIR__ . '/check_admin.php';
require_once '../user/action_checkout/auto_cancel_unpaid_orders.php';

autoCancelUnpaidBankOrders($pdo, 15);
try {
    $user_filter = $_GET['user_id'] ?? '';

    if ($user_filter != '') {
        $sql = "SELECT o.*, u.username
                FROM orders o
                JOIN users u ON o.user_id = u.id
                WHERE o.user_id = ?
                ORDER BY o.created_at DESC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([$user_filter]);
    } else {
        $sql = "SELECT o.*, u.username
                FROM orders o
                JOIN users u ON o.user_id = u.id
                ORDER BY o.created_at DESC";

        $stmt = $pdo->query($sql);
    }

    $orders = $stmt->fetchAll();

} catch (PDOException $e) {
    die("Lỗi truy vấn: " . $e->getMessage());
}
?>

<?php
$page_title = 'Quản lí đơn hàng';
$page_icon = 'fa-solid fa-cart-shopping';
$custom_css = '
    <link rel="stylesheet" href="/assets/css/style_list_oder.css">
    <link rel="stylesheet" href="/assets/css/style_notification.css">
';

include 'includes/header.php';
?>

            <div class="container dashboard-container">
                <section class="section-block">
                    <div class="card shadow-card" style="background: var(--bg-main); padding: var(--space-lg); border-radius: var(--radius-md);">
                        <table class="data-table">
                            <form method="GET" class="filter-form">
                                <input type="number" name="user_id" placeholder="Nhập User ID..."
                                    value="<?= $_GET['user_id'] ?? '' ?>">

                                <button type="submit" class="btn btn-primary">Lọc</button>
                            </form>
                            <thead>
                                <tr>
                                    <th>Mã Đơn</th>
                                    <th>User ID</th>
                                    <th>User Name</th>
                                    <th>Tổng Tiền</th>
                                    <th>Trạng Thái</th>
                                    <th>Ngày Đặt</th>
                                    <th>Thao Tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($orders) > 0): ?>
                                    <?php foreach ($orders as $row): ?>
                                        <tr>
                                            <td>#FD-<?= $row['id'] ?></td>
                                            <td class="user"><?= htmlspecialchars($row['user_id']) ?></td>
                                            <td class="user"><?= htmlspecialchars($row['username']) ?></td>
                                            <td class="price-highlight">
                                                <?= number_format($row['total_amount'], 0, ',', '.') ?>₫
                                            </td>
                                            <td>
                                                <?php 
                                                    // Xử lý Badge dựa trên status từ database
                                                    $status = $row['status'];
                                                 $badge_class = 'badge-info';
                                                    $status_vi = $status;

                                                    if ($status == 'pending') { $badge_class = 'badge-warning'; $status_vi = 'Chờ thanh toán'; }
                                                    elseif ($status == 'processing') { $badge_class = 'badge-warning'; $status_vi = 'Đang xử lí'; }
                                                    elseif ($status == 'shipped') { $badge_class = 'badge-depending'; $status_vi = 'Đang vận chuyển'; }
                                                    elseif ($status == 'completed') { $badge_class = 'badge-success'; $status_vi = 'Hoàn thành'; }
                                                    elseif ($status == 'cancelled') { $badge_class = 'badge-danger'; $status_vi = 'Đã hủy'; }
                                                ?>
                                                <span class="badge <?= $badge_class ?>"><?= $status_vi ?></span>
                                            </td>
                                            <td><?= date('d/m/Y', strtotime($row['created_at'])) ?></td>
                                            <td style="position: relative;">
                                                <div class="action-buttons">
                                                    <button class="btn btn-primary btn-toggle" data-id="<?= $row['id'] ?>">
                                                        Xem chi tiết
                                                    </button>
                                                    <button class="btn btn-primary btn-action" data-id="<?= $row['id'] ?>">
                                                        Cập nhật
                                                    </button>

                                                    <div class="action-menu">
                                                        <button data-status="processing">Đang xử lý</button>
                                                        <button data-status="shipped">Đang giao</button>
                                                        <button data-status="completed">Hoàn thành</button>
                                                        <button data-status="cancelled">Hủy</button>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                        <tr class="order-detail-row" id="detail-<?= $row['id'] ?>" style="display:none;">
                                            <td colspan="7">
                                                <div class="order-detail-content">
                                                    Đang tải...
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" style="text-align: center; padding: 20px;">Chưa có đơn hàng nào được ghi nhận.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>
        </main>
    </div>

    <script src="../assets/js/script_dashboard.js"></script>
</body>
</html>
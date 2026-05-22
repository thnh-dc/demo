<?php
session_start();
require_once '../../auth/check_login.php';
require_once '../../auth/user_only.php';
require_once '../../config/database.php';
require_once __DIR__ . '/auto_cancel_unpaid_orders.php';
autoCancelUnpaidBankOrders($pdo, 15);

$user_id = $_SESSION['user_id'] ?? 0;
$order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;

if ($order_id <= 0) {
    header("Location: ../cart.php");
    exit();
}
$stmtOrder = $pdo->prepare("
    SELECT 
        id,
        user_id,
        total_amount,
        status,
        shipping_address,
        note,
        payment_method,
        payment_status,
        payment_code,
        paid_at,
        created_at
    FROM orders
    WHERE id = ?
    AND user_id = ?
    AND payment_method = 'bank'
    LIMIT 1
");
$stmtOrder->execute([$order_id, $user_id]);
$order = $stmtOrder->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    header("Location: ../cart.php");
    exit();
}

$stmtItems = $pdo->prepare("
    SELECT 
        product_id,
        product_name,
        product_image,
        quantity,
        price
    FROM order_items
    WHERE order_id = ?
");

$stmtItems->execute([$order_id]);
$items = $stmtItems->fetchAll(PDO::FETCH_ASSOC);
if (
    isset($_GET['check_payment']) &&
    $_GET['check_payment'] == '1' &&
    $order['payment_status'] !== 'paid'
) {
    $_SESSION['noti_message'] = 'Chưa nhận được thanh toán. Vui lòng kiểm tra lại sau ít phút hoặc đảm bảo đã chuyển đúng số tiền và nội dung chuyển khoản.';
    $_SESSION['noti_type'] = 'error';
}
$custom_css = '
    <link rel="stylesheet" href="/assets/css/style_checkout.css">
    <link rel="stylesheet" href="/assets/css/style_notification.css">
';
include '../../includes/header.php';
include '../../includes/notification.php';
?>
<div class="container">
    <div class="checkout-layout">
        <?php if ($order['payment_status'] === 'paid'): ?>
            <div class="success-page-wrapper">
                <div class="success-card">
                    <h1 class="success-title">Đặt hàng & thanh toán thành công!</h1>
                    <p class="success-message">
                        Đơn hàng của bạn đã được ghi nhận.
                    </p>
                    <?php if (!empty($order['paid_at'])): ?>
                        <p class="success-message">
                            Thời gian thanh toán:
                            <b><?= date('d/m/Y H:i', strtotime($order['paid_at'])) ?></b>
                        </p>
                    <?php endif; ?>
                    <button onclick="window.location.href='../index.php'" class="btn btn-primary">
                        Tiếp tục mua sắm
                    </button>
                </div>
            </div>
        <?php elseif ($order['status'] === 'cancelled'): ?>
            <div class="success-page-wrapper">
                <div class="success-card">
                    <h1 class="success-title" style="color: #dc2626;">Đơn hàng đã bị hủy!</h1>
                    <p class="success-message">
                        Đơn hàng 
                        <b>#FD-<?= htmlspecialchars($order['id']) ?></b>
                        đã bị hủy do quá thời gian thanh toán hoặc do người dùng/admin hủy.
                    </p>
                    <button onclick="window.location.href='../index.php'" class="btn btn-primary">
                        Tiếp tục mua sắm
                    </button>
                </div>
            </div>
        <?php else: ?>
            <div class="checkout-section">
                <h3>💳 Thanh toán chuyển khoản</h3>
                <p>
                    Vui lòng chuyển khoản đúng thông tin bên dưới. 
                    Sau khi hệ thống nhận được giao dịch, đơn hàng sẽ tự động chuyển sang trạng thái 
                    <b>Đang xử lý</b>.
                </p>
                <div class="bank-payment-box">
                    <div class="bank-qr-box">
                        <img 
                            src="/assets/images/qr-payment.jpg" 
                            alt="QR thanh toán" 
                            class="bank-qr-img"
                        >
                    </div>
                    <div class="bank-info-box">
                        <p><b>Mã đơn hàng:</b> #FD-<?= htmlspecialchars($order['id']) ?></p>
                        <p><b>Ngân hàng:</b> Ngân hàng BIDV</p>
                        <p><b>Chủ tài khoản:</b> FD TECH</p>
                        <p><b>Số tài khoản:</b> 96247FD2026</p>
                        <p>
                            <b>Số tiền:</b> 
                            <?= number_format($order['total_amount'], 0, ',', '.') ?>₫
                        </p>
                        <p>
                            <b>Nội dung chuyển khoản:</b> 
                            <span class="payment-code">
                                <?= htmlspecialchars($order['payment_code']) ?>
                            </span>
                        </p>
                        <p>
                            <b>Lưu ý: Vui lòng nhập đúng số tiền và đúng nội dung chuyển khoản để hệ thống tự động xác nhận.</b>
                        </p>
                    </div>
                </div>
            </div>
            <div class="checkout-section">
                <h3>📦 Sản phẩm thanh toán</h3>
                <?php foreach ($items as $item): ?>
                    <?php
                        $img = $item['product_image'] ?? '';
                        if (empty($img)) {
                            $img_src = "/assets/images/logo-fd.jpg";
                        } elseif (filter_var($img, FILTER_VALIDATE_URL)) {
                            $img_src = $img;
                        } elseif (strpos($img, 'upload/product_image/') === 0) {
                            $img_src = "/" . $img;
                        } else {
                            $img_src = "/upload/product_image/" . $img;
                        }
                    ?>
                    <div class="checkout-item">
                        <img 
                            src="<?= htmlspecialchars($img_src) ?>" 
                            class="checkout-img"
                            alt="<?= htmlspecialchars($item['product_name']) ?>"
                            onerror="this.src='/assets/images/logo-fd.jpg'"
                        >
                        <div class="checkout-info">
                            <p class="checkout-name">
                                <?= htmlspecialchars($item['product_name']) ?>
                            </p>
                            <p class="checkout-price">
                                <?= number_format($item['price'], 0, ',', '.') ?>₫
                                x <?= htmlspecialchars($item['quantity']) ?>
                            </p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="checkout-section">
                <p>
                    Tổng tiền:
                    <b><?= number_format($order['total_amount'], 0, ',', '.') ?>₫</b>
                </p>
                <p>
                    Trạng thái thanh toán:
                    <b>Chưa thanh toán</b>
                </p>
                <button 
                    type="button" 
                    onclick="window.location.href='bank_payment.php?order_id=<?= $order['id'] ?>&check_payment=1'" 
                    class="btn btn-primary"
                >
                    Kiểm tra thanh toán
                </button>
                <button 
                    type="button" 
                    onclick="window.location.href='../index.php'" 
                    class="btn btn-secondary"
                >
                    Tiếp tục mua sắm
                </button>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php include '../../includes/footer.php'; ?>
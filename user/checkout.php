<?php
session_start();
require_once '../auth/check_login.php';
require_once '../auth/user_only.php';
require_once '../config/database.php';

$custom_css = '<link rel="stylesheet" href="/assets/css/style_checkout.css">';
include '../includes/header.php';

$selectedItems = $_POST['selected_items'] ?? '';
$selectedArray = array_filter(explode(',', $selectedItems));

if (empty($selectedArray)) {
    $cartItems = [];
} else {
    $placeholders = implode(',', array_fill(0, count($selectedArray), '?'));

    $stmt = $pdo->prepare("
        SELECT 
            c.id, c.quantity, p.name, p.price, p.discount_price,
            COALESCE(NULLIF(p.discount_price, 0), p.price) AS display_price, p.image_url
        FROM cart_items c
        JOIN products p ON c.product_id = p.id
        WHERE c.user_id = ?
        AND c.id IN ($placeholders)
    ");
    $id = $_SESSION['user_id'] ?? 0;
    $params = array_merge([$id], $selectedArray);
    $stmt->execute($params);
    $cartItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$id = $_SESSION['user_id'] ?? 0;
$stmtUser = $pdo->prepare("SELECT full_name, phone, address FROM users WHERE id = ?");
$stmtUser->execute([$id]);
$user = $stmtUser->fetch(PDO::FETCH_ASSOC);
$total = 0;
foreach ($cartItems as $row) {
    $total += $row['display_price'] * $row['quantity'];
}
?>
<div class="container">
<?php if (isset($_GET['status']) && $_GET['status'] == 'success'): ?>
    <div class="success-page-wrapper">
        <div class="success-card">
            <h1 class="success-title">Đặt hàng thành công!</h1>
            <p class="success-message">
                Đơn hàng của bạn đã được ghi nhận.
            </p>
            <button onclick="window.location.href='index.php'" class="btn btn-primary">
                Tiếp tục mua sắm
            </button>
        </div>
    </div>
<?php elseif (count($cartItems) > 0): ?>
    <div class="checkout-layout">
        <form action="../user/action_checkout/process_checkout.php" method="POST">
            <input type="hidden" name="selected_items" value="<?= htmlspecialchars($selectedItems) ?>">
            <div class="checkout-section">
                <h3>📍 Thông tin nhận hàng</h3>
                <input 
                    type="text" 
                    name="fullname" 
                    value="<?= htmlspecialchars($user['full_name'] ?? '') ?>" 
                    required
                >
                <input 
                    type="text" 
                    name="phone" 
                    value="<?= htmlspecialchars($user['phone'] ?? '') ?>" 
                    required
                >
                <textarea name="address" required><?= htmlspecialchars($user['address'] ?? '') ?></textarea>
            </div>
            <div class="checkout-section">
                <h3>📦 Sản phẩm</h3>
                <?php foreach ($cartItems as $item): ?>
                    <?php
                        $img = $item['image_url'] ?? '';

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
                            alt="<?= htmlspecialchars($item['name']) ?>"
                            onerror="this.src='/assets/images/logo-fd.jpg'"
                        >
                        <div class="checkout-info">
                            <p class="checkout-name">
                                <?= htmlspecialchars($item['name']) ?>
                            </p>
                            <p class="checkout-price">
                                <?php if (!empty($item['discount_price']) && $item['discount_price'] > 0): ?>
                                    <span class="old-price">
                                        <?= number_format($item['price'], 0, ',', '.') ?>₫
                                    </span>
                                    <br>
                                    <span class="discount-price">
                                        <?= number_format($item['discount_price'], 0, ',', '.') ?>₫
                                    </span>
                                <?php else: ?>
                                    <?= number_format($item['price'], 0, ',', '.') ?>₫
                                <?php endif; ?>

                                x <?= $item['quantity'] ?>
                            </p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="checkout-section">
                <h3>💳 Thanh toán</h3>
                <label>
                    <input type="radio" name="payment_method" value="cod" checked>
                    Thanh toán khi nhận hàng
                </label>
                <label>
                    <input type="radio" name="payment_method" value="bank">
                    Chuyển khoản qua ngân hàng
                </label>
            </div>
            <div class="checkout-section">
                <p>
                    Tổng tiền:
                    <b><?= number_format($total, 0, ',', '.') ?>₫</b>
                </p>
                <button type="submit" class="btn btn-primary">
                    Xác nhận đặt hàng
                </button>
            </div>
        </form>
    </div>
<?php else: ?>
    <div class="empty-cart-container">
        <h2>Oppss, bạn chưa có sản phẩm để thanh toán.</h2>
        <button onclick="window.location.href='cart.php'" class="btn btn-primary">
            Quay lại giỏ hàng của bạn
        </button>
    </div>
<?php endif; ?>
</div>
<?php include '../includes/footer.php'; ?>
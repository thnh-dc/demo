<?php 
    session_start();
    require_once '../auth/check_login.php';
    require_once '../auth/user_only.php';
    require_once '../config/database.php';

    $custom_css = '
    <link rel="stylesheet" href="/FD-Tech/assets/css/style_cart.css">
    <link rel="stylesheet" href="/FD-Tech/assets/css/style_notification.css">';
    include '../includes/header.php';

    $user_id = $_SESSION['user_id'] ?? 0;
    $stmt = $pdo->prepare("
        SELECT 
            c.id, c.quantity, p.name, p.price, p.discount_price, p.stock_quantity,
            COALESCE(NULLIF(p.discount_price, 0), p.price) AS display_price, p.image_url
        FROM cart_items c
        INNER JOIN products p ON c.product_id = p.id
        WHERE c.user_id = ?
    ");

    $stmt->execute([$user_id]);
    $cartItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $total = 0;
    foreach ($cartItems as $item) {
        $total += $item['display_price'] * $item['quantity'];
    }
?>
<style>
    .data-table {
        width: 100%;
        border-collapse: collapse;
    }
    .data-table th,
    .data-table td {
        display: table-cell !important;
        padding: 12px 20px;
        text-align: left;
    }
    .data-table th {
        font-weight: 600;
        background: #f5f5f5;
    }
    .old-price {
        color: #999;
        text-decoration: line-through;
        font-size: 13px;
    }
</style>
<div class="container">
    <section class="section-block">
        <h1 class="page-title">Giỏ Hàng Của Bạn</h1>
        <div class="card shadow-card">
            <?php if (!empty($cartItems)): ?>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>
                                <input type="checkbox" id="check-all">
                            </th>
                            <th>Sản phẩm</th>
                            <th>Đơn giá</th>
                            <th>Số lượng</th>
                            <th>Thành tiền</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($cartItems as $item): ?>
                            <?php
                                $img = $item['image_url'] ?? '';
                                if (empty($img)) {
                                    $img_src = "../assets/images/logo-fd.jpg";
                                } elseif (filter_var($img, FILTER_VALIDATE_URL)) {
                                    $img_src = $img;
                                } elseif (strpos($img, 'upload/product_image/') === 0) {
                                    $img_src = "../" . $img;
                                } else {
                                    $img_src = "../upload/product_image/" . $img;
                                }
                            ?>
                            <tr class="cart-item">
                                <td>
                                    <input type="checkbox" class="item-check" value="<?= $item['id'] ?>">
                                </td>
                                <td class="product-info">
                                    <div class="product-box">
                                        <img 
                                            src="<?= htmlspecialchars($img_src) ?>" 
                                            class="product-img"
                                            alt="<?= htmlspecialchars($item['name']) ?>"
                                            onerror="this.src='../assets/images/logo-fd.jpg'"
                                        >
                                        <span class="product-name">
                                            <?= htmlspecialchars($item['name']) ?>
                                        </span>
                                    </div>
                                </td>
                                <td class="item-price" data-price="<?= $item['display_price'] ?>">
                                    <?php if (!empty($item['discount_price']) && $item['discount_price'] > 0): ?>
                                        <span class="old-price">
                                            <?= number_format($item['price'], 0, ',', '.') ?> vn₫
                                        </span>
                                        <br>
                                        <span>
                                            <?= number_format($item['discount_price'], 0, ',', '.') ?> vn₫
                                        </span>
                                    <?php else: ?>
                                        <?= number_format($item['price'], 0, ',', '.') ?> vn₫
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="quantity-box">
                                        <button class="btn-minus" data-id="<?= $item['id'] ?>">-</button>
                                        <input 
                                            type="text" 
                                            value="<?= $item['quantity'] ?>" 
                                            readonly 
                                            class="qty-input" 
                                            id="qty-<?= $item['id'] ?>"
                                            data-stock="<?= $item['stock_quantity'] ?>">
                                        <button class="btn-plus" data-id="<?= $item['id'] ?>" <?= $item['quantity'] >= $item['stock_quantity'] ? 'disabled' : '' ?>>
                                            +
                                        </button>
                                    </div>
                                </td>
                                <td class="item-subtotal">
                                    <?= number_format($item['display_price'] * $item['quantity'], 0, ',', '.') ?> vn₫
                                </td>
                                <td>
                                    <button class="btn btn-danger btn-delete" data-id="<?= $item['id'] ?>">
                                        Xóa
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <div class="cart-summary">
                    <p class="summary-text">
                        <br>
                        <b>Tổng thanh toán: </b>
                        <span class="price-highlight">
                            <?= number_format($total, 0, ',', '.') ?> vn₫
                        </span>
                    </p>
                    <br>
                    <form id="checkout-form" method="POST" action="checkout.php">
                        <input type="hidden" name="selected_items" id="selected-items">
                        <button type="submit" class="btn btn-primary btn-large">
                            Tiến Hành Đặt Hàng
                        </button>
                    </form>
                </div>
            <?php else: ?>
                <div class="empty-cart-container">
                    <svg class="empty-cart-icon" viewBox="0 0 24 24" fill="none" stroke="var(--text-muted)" stroke-width="1.5">
                        <circle cx="9" cy="21" r="1"></circle>
                        <circle cx="20" cy="21" r="1"></circle>
                        <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
                    </svg>
                    <h2 class="empty-cart-title">Giỏ hàng trống</h2>
                    <p class="empty-cart-desc">Không có sản phẩm nào trong giỏ hàng</p>
                    <a href="index.php">
                        <button class="btn btn-primary btn-large">
                            Về trang chủ
                        </button>
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </section>
</div>
<script src="/FD-Tech/assets/js/script_cart.js"></script>

<?php include '../includes/ai_assistant_widget.php'; ?>
<?php include '../includes/footer.php'; ?>
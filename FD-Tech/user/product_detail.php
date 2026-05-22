<?php
session_start();
require_once '../auth/user_only.php';
require_once '../config/database.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 1;
$stmt = $pdo->prepare("
    SELECT 
        id, name, price, discount_price,
        COALESCE(NULLIF(discount_price, 0), price) AS display_price,
        stock_quantity, image_url, description 
    FROM products 
    WHERE id = :id 
    LIMIT 1
");
$stmt->execute(['id' => $id]);
$sp = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$sp) {
    die("<h2 class='text-center'>Sản phẩm không tồn tại!</h2>");
}
$stmtImages = $pdo->prepare("SELECT image_url FROM product_images WHERE product_id = :id");
$stmtImages->execute(['id' => $id]);
$extraImages = $stmtImages->fetchAll(PDO::FETCH_COLUMN);
$stmtSpecs = $pdo->prepare("SELECT spec_name, spec_value FROM product_specs WHERE product_id = :id");
$stmtSpecs->execute(['id' => $id]);
$specs = $stmtSpecs->fetchAll(PDO::FETCH_ASSOC);
try {
    $stmtReviews = $pdo->prepare("
        SELECT r.*, u.username AS user_name 
        FROM product_reviews r 
        LEFT JOIN users u ON r.user_id = u.id 
        WHERE r.product_id = :id 
        AND r.status = 'show' 
        ORDER BY r.id DESC
    ");
    $stmtReviews->execute(['id' => $id]);
    $reviews = $stmtReviews->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    try {
        $stmtReviews = $pdo->prepare("
            SELECT r.*, u.name AS user_name 
            FROM product_reviews r 
            LEFT JOIN users u ON r.user_id = u.id 
            WHERE r.product_id = :id 
            AND r.status = 'show' 
            ORDER BY r.id DESC
        ");
        $stmtReviews->execute(['id' => $id]);
        $reviews = $stmtReviews->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e2) {
        $stmtReviews = $pdo->prepare("
            SELECT * 
            FROM product_reviews 
            WHERE product_id = :id 
            AND status = 'show' 
            ORDER BY id DESC
        ");
        $stmtReviews->execute(['id' => $id]);
        $reviews = $stmtReviews->fetchAll(PDO::FETCH_ASSOC);
    }
}
$custom_css = 
    '<link rel="stylesheet" href="../assets/css/style_product_detail.css?v=' . time() . '">' . "\n" .
    '<link rel="stylesheet" href="../assets/css/style_notification.css?v=' . time() . '">';
include '../includes/header.php';
include '../includes/notification.php';
$img = $sp['image_url'] ?? '';
if (empty($img)) {
    $img_src = "../assets/images/logo-fd.jpg";
} elseif (filter_var($img, FILTER_VALIDATE_URL)) {
    $img_src = $img;
} elseif (strpos($img, 'upload/product_image/') === 0) {
    $img_src = "../" . $img;
} else {
    $img_src = "../upload/product_image/" . $img;
}
$image_gallery = [$img_src];
foreach ($extraImages as $eImg) {
    if (filter_var($eImg, FILTER_VALIDATE_URL)) {
        $image_gallery[] = $eImg;
    } elseif (strpos($eImg, 'upload/product_gallery/') === 0) {
        $image_gallery[] = "../" . $eImg;
    } else {
        $image_gallery[] = "../upload/product_gallery/" . $eImg;
    }
}
$has_discount = !empty($sp['discount_price']) && $sp['discount_price'] > 0;
$display_price = $has_discount ? $sp['discount_price'] : $sp['price'];
?>
<main class="container product-detail-container">
    <nav class="breadcrumb">
        <a href="index.php">Trang chủ</a> > 
        <a href="product_list.php">Sản phẩm</a> > 
        <span><?= htmlspecialchars($sp['name']); ?></span>
    </nav>
    <div class="product-layout">
        <div class="product-gallery">
            <div class="main-image-container" style="position: relative;">
                <button type="button" id="prev-img-btn" class="nav-arrow left-arrow">
                    <i class="fas fa-chevron-left"></i>
                </button>
                <img 
                    id="main-product-image" 
                    src="<?= htmlspecialchars($img_src); ?>" 
                    alt="<?= htmlspecialchars($sp['name']); ?>" 
                    onerror="this.src='../assets/images/logo-fd.jpg'" 
                    style="transition: opacity 0.2s ease;"
                >
                <button type="button" id="next-img-btn" class="nav-arrow right-arrow">
                    <i class="fas fa-chevron-right"></i>
                </button>
            </div>
            <div class="thumbnail-list">
                <?php foreach($image_gallery as $index => $thumb): ?>
                    <img 
                        src="<?= htmlspecialchars($thumb) ?>" 
                        class="thumb-item <?= $index === 0 ? 'active' : '' ?>" 
                        data-index="<?= $index ?>" 
                        alt="Thumbnail" 
                        onerror="this.src='../assets/images/logo-fd.jpg'"
                    >
                <?php endforeach; ?>
            </div>
        </div>
        <div class="product-info-section">
            <h1 class="product-title">
                <?= htmlspecialchars($sp['name'] ?? 'Đang cập nhật'); ?>
            </h1>
            <div class="product-price">
                <?php if ($has_discount): ?>
                    <span class="old-price-detail">
                        <?= number_format($sp['price'], 0, ',', '.'); ?> VNĐ
                    </span>
                    <span class="discount-price-detail">
                        <?= number_format($sp['discount_price'], 0, ',', '.'); ?> VNĐ
                    </span>
                <?php else: ?>
                    <?= number_format($sp['price'] ?? 0, 0, ',', '.'); ?> VNĐ
                <?php endif; ?>
            </div>
            <div class="product-status">
                Trạng thái: 
                <span class="<?= $sp['stock_quantity'] > 0 ? 'text-success' : 'text-danger' ?>">
                    <?= $sp['stock_quantity'] > 0 ? 'Còn hàng (' . $sp['stock_quantity'] . ')' : 'Hết hàng' ?>
                </span>
            </div>
            <form action="../user/action_product_detail/action_product.php" method="POST" class="product-form" id="addToCartForm">
                <input type="hidden" name="product_id" value="<?= $id; ?>">
                <div class="quantity-group">
                    <label>Số lượng:</label>
                    <div class="qty-control">
                        <button type="button" class="qty-btn minus">-</button>
                        <input 
                            type="number" 
                            name="quantity" 
                            id="qty-input" 
                            value="1" 
                            min="1" 
                            max="<?= $sp['stock_quantity'] ?>" 
                            class="quantity-input" 
                            readonly
                        >
                        <button type="button" class="qty-btn plus">+</button>
                    </div>
                </div>
                <div class="product-actions">
                    <button type="button" name="action_type" value="add_to_cart" class="btn btn-outline" id="btnAddToCart">
                        <i class="fas fa-shopping-cart"></i> THÊM VÀO GIỎ HÀNG
                    </button>
                    <button type="submit" name="action_type" value="buy_now" class="btn btn-primary btn-buy">
                        MUA NGAY
                    </button>
                </div>
            </form>
        </div>
    </div>
    <div class="product-tabs-section">
        <div class="tabs-header">
            <button class="tab-btn active" data-target="tab-desc">Mô tả sản phẩm</button>
            <button class="tab-btn" data-target="tab-specs">Thông số kỹ thuật</button>
            <button class="tab-btn" data-target="tab-reviews">Đánh giá</button>
        </div>
        <div class="tabs-content">
            <div class="tab-pane active" id="tab-desc">
                <div class="content-formatted">
                    <?= nl2br(htmlspecialchars($sp['description'] ?? 'Đang cập nhật mô tả...')) ?>
                </div>
            </div>
            <div class="tab-pane" id="tab-specs">
                <table class="specs-table">
                    <?php if (!empty($specs)): ?>
                        <?php foreach ($specs as $s): ?>
                            <tr>
                                <th><?= htmlspecialchars($s['spec_name'] ?? $s['name'] ?? 'Thông số') ?></th>
                                <td><?= htmlspecialchars($s['spec_value'] ?? $s['value'] ?? '') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="2">Chưa có thông số kỹ thuật.</td>
                        </tr>
                    <?php endif; ?>
                </table>
            </div>
            <div class="tab-pane" id="tab-reviews">
                <div class="review-form-container">
                    <h3 class="review-form-title">Viết đánh giá của bạn</h3>
                    <form id="submitReviewForm" class="review-form">
                        <input type="hidden" name="product_id" value="<?= $id; ?>">
                        <div class="form-group">
                            <label for="rating">Mức độ đánh giá:</label>
                            <select name="rating" id="rating" class="form-control" required>
                                <option value="5">⭐⭐⭐⭐⭐ (5 Sao - Tuyệt vời)</option>
                                <option value="4">⭐⭐⭐⭐ (4 Sao - Tốt)</option>
                                <option value="3">⭐⭐⭐ (3 Sao - Bình thường)</option>
                                <option value="2">⭐⭐ (2 Sao - Kém)</option>
                                <option value="1">⭐ (1 Sao - Tệ)</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="comment">Nội dung đánh giá:</label>
                            <textarea 
                                name="comment" 
                                id="comment" 
                                rows="4" 
                                class="form-control" 
                                required 
                                placeholder="Chia sẻ cảm nhận của bạn về sản phẩm..."
                            ></textarea>
                        </div>
                        <button type="button" id="btnSubmitReview" class="btn btn-primary btn-submit-review">
                            Gửi đánh giá
                        </button>
                    </form>
                </div>
                <hr style="margin: 30px 0; border: 0; border-top: 1px solid #eee;">
                <div class="review-list">
                    <?php if (!empty($reviews)): ?>
                        <?php foreach ($reviews as $r): ?>
                            <div class="review-box">
                                <div class="review-header">
                                    <strong>
                                        <?= htmlspecialchars($r['user_name'] ?? 'Khách hàng (ID: ' . ($r['user_id'] ?? 'Ẩn') . ')') ?>
                                    </strong> 
                                    <span class="stars">
                                        <?= str_repeat('⭐', $r['rating'] ?? 5) ?>
                                    </span>
                                </div>
                                <p><?= nl2br(htmlspecialchars($r['comment'] ?? '')) ?></p>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p>Chưa có đánh giá nào cho sản phẩm này.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</main>
<script>
    const productImages = <?= json_encode($image_gallery); ?>;
    const isLoggedIn = <?= isset($_SESSION['user_id']) ? 'true' : 'false' ?>;
    const loginUrl = '/FD-Tech/auth/login.php';
    document.addEventListener('DOMContentLoaded', function() {
        let currentIndex = 0;
        const mainImg = document.getElementById('main-product-image');
        const prevBtn = document.getElementById('prev-img-btn');
        const nextBtn = document.getElementById('next-img-btn');
        const thumbs = document.querySelectorAll('.thumb-item');
        function changeImage(index) {
            if (index < 0) {
                index = productImages.length - 1;
            }
            if (index >= productImages.length) {
                index = 0;
            }
            currentIndex = index;
            mainImg.style.opacity = '0.4';
            setTimeout(() => {
                mainImg.src = productImages[currentIndex];
                mainImg.style.opacity = '1';
            }, 100);
            thumbs.forEach((t, i) => {
                if (i === currentIndex) {
                    t.classList.add('active');
                } else {
                    t.classList.remove('active');
                }
            });
        }
        if (prevBtn) {
            prevBtn.addEventListener('click', () => changeImage(currentIndex - 1));
        }
        if (nextBtn) {
            nextBtn.addEventListener('click', () => changeImage(currentIndex + 1));
        }
        thumbs.forEach(thumb => {
            thumb.addEventListener('click', function() {
                const idx = parseInt(this.getAttribute('data-index'));
                changeImage(idx);
            });
        });
    });
</script>
<script src="../assets/js/product_detail.js?v=<?= time() ?>"></script>
<?php include '../includes/ai_assistant_widget.php'; ?>
<?php include '../includes/footer.php'; ?>
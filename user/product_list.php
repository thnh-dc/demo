<?php
session_start();
require_once '../auth/user_only.php';
require_once '../config/database.php';

$cat = isset($_GET['cat']) ? (int)$_GET['cat'] : 0;
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'featured';

$products = [];
$category_name = '';

$menu_categories = [
    1 => 'LAPTOP',
    2 => 'LINH KIỆN',
    3 => 'MÀN HÌNH MÁY TÍNH',
    4 => 'TAI NGHE',
    5 => 'LOA',
    6 => 'BÀN PHÍM',
    7 => 'CHUỘT',
    8 => 'PHỤ KIỆN KHÁC'
];

$orderBy = "id DESC";

switch ($sort) {
    case 'price_asc':
        $orderBy = "display_price ASC";
        break;

    case 'price_desc':
        $orderBy = "display_price DESC";
        break;

    case 'newest':
        $orderBy = "id DESC";
        break;

    case 'bestseller':
        $orderBy = "id ASC";
        break;

    case 'discount':
        $orderBy = "discount_price DESC";
        break;

    case 'featured':
    default:
        $orderBy = "id DESC";
        break;
}

try {
    if ($cat > 0) {
        if (isset($menu_categories[$cat])) {
            $category_name = $menu_categories[$cat];
        } else {
            $stmt_cat = $pdo->prepare("SELECT name FROM categories WHERE id = :cat");
            $stmt_cat->execute(['cat' => $cat]);
            $cat_data = $stmt_cat->fetch(PDO::FETCH_ASSOC);

            if ($cat_data && !empty($cat_data['name'])) {
                $category_name = $cat_data['name'];
            }
        }

        $stmt = $pdo->prepare("
            SELECT 
                id,
                name,
                price,
                discount_price,
                COALESCE(NULLIF(discount_price, 0), price) AS display_price,
                image_url,
                description
            FROM products
            WHERE category_id = :cat
            ORDER BY $orderBy
        ");

        $stmt->execute(['cat' => $cat]);
    } else {
        $stmt = $pdo->prepare("
            SELECT 
                id, name, price, discount_price, COALESCE(NULLIF(discount_price, 0), price) AS display_price, image_url, description
            FROM products
            ORDER BY $orderBy
        ");

        $stmt->execute();
    }
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("<h3 style='color:red; text-align:center;'>Lỗi truy vấn SQL: " . $e->getMessage() . "</h3>");
}

$custom_css = '<link rel="stylesheet" href="../assets/css/style_product_list.css?v=' . time() . '">';
include '../includes/header.php';
?>
<main class="container">
    <div class="page-header">
        <h2 class="section-title">
            <?= $category_name !== '' ? htmlspecialchars(mb_strtoupper($category_name, 'UTF-8')) : 'TẤT CẢ SẢN PHẨM' ?>
        </h2>
    </div>
    <div class="sort-bar">
        <span class="sort-label">Sắp xếp theo:</span>
        <a href="?cat=<?= $cat ?>&sort=featured" class="sort-item <?= $sort == 'featured' ? 'active' : '' ?>">
            Nổi bật
        </a>
        <span class="separator">•</span>
        <a href="?cat=<?= $cat ?>&sort=bestseller" class="sort-item <?= $sort == 'bestseller' ? 'active' : '' ?>">
            Bán chạy
        </a>
        <span class="separator">•</span>
        <a href="?cat=<?= $cat ?>&sort=discount" class="sort-item <?= $sort == 'discount' ? 'active' : '' ?>">
            Giảm giá
        </a>
        <span class="separator">•</span>
        <a href="?cat=<?= $cat ?>&sort=newest" class="sort-item <?= $sort == 'newest' ? 'active' : '' ?>">
            Mới
        </a>
        <span class="separator">•</span>
        <div class="sort-dropdown">
            <span class="sort-item <?= strpos($sort, 'price') !== false ? 'active' : '' ?>" style="cursor: pointer;">
                Giá <?= strpos($sort, 'price') !== false ? ($sort == 'price_asc' ? ' (Thấp - Cao)' : ' (Cao - Thấp)') : '' ?>
                <i class="fas fa-chevron-down" style="font-size: 12px; margin-left: 3px;"></i>
            </span>
            <div class="dropdown-content">
                <a href="?cat=<?= $cat ?>&sort=price_asc" class="<?= $sort == 'price_asc' ? 'active' : '' ?>">
                    Giá: Thấp đến Cao
                </a>
                <a href="?cat=<?= $cat ?>&sort=price_desc" class="<?= $sort == 'price_desc' ? 'active' : '' ?>">
                    Giá: Cao đến Thấp
                </a>
            </div>
        </div>
    </div>
    <div class="product-grid">
        <?php if (!empty($products)): ?>
            <?php foreach ($products as $row): ?>
                <?php
                    $img = $row['image_url'] ?? '';
                    if (empty($img)) {
                        $src = "../assets/images/logo-fd.jpg";
                    } elseif (filter_var($img, FILTER_VALIDATE_URL)) {
                        $src = $img;
                    } elseif (strpos($img, 'upload/product_image/') === 0) {
                        $src = "../" . $img;
                    } else {
                        $src = "../upload/product_image/" . $img;
                    }
                    $has_discount = !empty($row['discount_price']) && $row['discount_price'] > 0;
                ?>
                <div class="product-card">
                    <a href="product_detail.php?id=<?= $row['id'] ?>" class="card-link">
                        <div class="img-wrapper">
                            <?php if ($has_discount): ?>
                                <span class="discount-badge">SALE</span>
                            <?php endif; ?>
                            <img 
                                src="<?= htmlspecialchars($src) ?>" 
                                alt="<?= htmlspecialchars($row['name']) ?>" 
                                onerror="this.src='../assets/images/logo-fd.jpg'"
                            >
                        </div>
                        <div class="card-body">
                            <h3><?= htmlspecialchars($row['name']) ?></h3>
                            <p class="price">
                                <?php if ($has_discount): ?>
                                    <span class="old-price">
                                        <?= number_format($row['price'], 0, ',', '.') ?> VNĐ
                                    </span>
                                    <span class="discount-price">
                                        <?= number_format($row['discount_price'], 0, ',', '.') ?> VNĐ
                                    </span>
                                <?php else: ?>
                                    <?= number_format($row['price'], 0, ',', '.') ?> VNĐ
                                <?php endif; ?>
                            </p>
                        </div>
                    </a>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="product-empty">
                <p>Hiện tại chưa có sản phẩm nào trong danh mục này.</p>
            </div>
        <?php endif; ?>
    </div>
</main>
<?php include '../includes/ai_assistant_widget.php'; ?>
<?php include '../includes/footer.php'; ?>
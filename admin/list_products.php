<?php
session_start();
include '../config/database.php';
require_once __DIR__ . '/check_admin.php';

$search = $_GET['search'] ?? '';
$category_id = $_GET['category_id'] ?? '';

try {
    $categories = $pdo->query("SELECT * FROM categories")->fetchAll(PDO::FETCH_ASSOC);

    $sql = "SELECT 
                p.id,
                p.name,
                p.price,
                p.stock_quantity,
                p.image_url,
                p.description,
                c.name AS cat_name
            FROM products p
            LEFT JOIN categories c ON p.category_id = c.id
            WHERE p.name LIKE ?";

    $params = ["%$search%"];

    if (!empty($category_id)) {
        $sql .= " AND p.category_id = ?";
        $params[] = $category_id;
    }

    $sql .= " ORDER BY p.id DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Lỗi query: " . $e->getMessage());
}

function getProductImage($image_url)
{
    if (empty($image_url)) {
        return "../assets/images/logo-fd.jpg";
    }

    if (filter_var($image_url, FILTER_VALIDATE_URL)) {
        return $image_url;
    }

    return "../upload/product_image/" . $image_url;
}

function getStockClass($stock)
{
    if ($stock <= 0) {
        return "out-stock";
    }

    if ($stock < 10) {
        return "low-stock";
    }

    return "in-stock";
}
?>

<?php
$page_title = 'Quản lí sản phẩm';
$page_icon = 'fa-solid fa-box-open';
$custom_css = '<link rel="stylesheet" href="/assets/css/style_list_product.css">';

include 'includes/header.php';
?>

        <div class="product-wrapper">

            <div class="product-card">

                <div class="product-header">

                    <form method="GET" class="search-form">

                        <input
                            type="text"
                            name="search"
                            class="form-control"
                            placeholder="Tìm sản phẩm..."
                            value="<?= htmlspecialchars($search) ?>"
                        >

                        <select name="category_id" class="form-control category-select" onchange="this.form.submit()">
                            <option value="">Tất cả danh mục</option>

                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat['id'] ?>" <?= ($category_id == $cat['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($cat['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>

                        <button class="btn btn-primary">
                            <i class="fa-solid fa-search"></i>
                        </button>

                    </form>

                    <a href="add.php" class="btn btn-primary">
                        <i class="fa-solid fa-plus"></i>
                        Thêm sản phẩm
                    </a>

                </div>

                <table class="admin-table">

                    <thead>
                        <tr>
                            <th>Hình ảnh</th>
                            <th>Tên sản phẩm</th>
                            <th>Danh mục</th>
                            <th>Giá</th>
                            <th>Tồn kho</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>

                    <tbody>

                    <?php if (count($products) > 0): ?>

                        <?php foreach ($products as $p): ?>

                            <?php
                                $stock = (int)$p['stock_quantity'];
                                $stock_class = getStockClass($stock);
                                $image_src = getProductImage($p['image_url']);
                            ?>

                            <tr>

                                <td>
                                    <img
                                        src="<?= htmlspecialchars($image_src) ?>"
                                        class="product-image"
                                        alt="<?= htmlspecialchars($p['name']) ?>"
                                        onerror="this.src='../assets/images/logo-fd.jpg'"
                                    >
                                </td>

                                <td>
                                    <strong><?= htmlspecialchars($p['name']) ?></strong>
                                </td>

                                <td>
                                    <?= htmlspecialchars($p['cat_name'] ?? 'Chưa có') ?>
                                </td>

                                <td class="product-price">
                                    <?= number_format($p['price'], 0, ',', '.') ?>₫
                                </td>

                                <td>
                                    <span class="stock-badge <?= $stock_class ?>">
                                        <?= $stock ?> sản phẩm
                                    </span>
                                </td>

                                <td>
                                    <div class="action-group">

                                        <a
                                            href="edit.php?id=<?= $p['id'] ?>"
                                            class="btn-action btn-edit"
                                        >
                                            <i class="fa-solid fa-pen"></i>
                                        </a>

                                        <a
                                            href="delete.php?id=<?= $p['id'] ?>"
                                            class="btn-action btn-delete"
                                            onclick="return confirm('Bạn có chắc muốn xóa?')"
                                        >
                                            <i class="fa-solid fa-trash"></i>
                                        </a>

                                    </div>
                                </td>

                            </tr>

                        <?php endforeach; ?>

                    <?php else: ?>

                        <tr>
                            <td colspan="6">
                                <div class="empty-box">
                                    <i class="fa-solid fa-box-open"></i>
                                    <h3>Không có sản phẩm</h3>
                                </div>
                            </td>
                        </tr>

                    <?php endif; ?>

                    </tbody>

                </table>

            </div>

        </div>

    </main>

</div>
<script src="../assets/js/script_dashboard.js"></script>
</body>
</html>
<?php
session_start();
include '../config/database.php';
require_once __DIR__ . '/check_admin.php';

$id = $_GET['id'] ?? $_POST['id'] ?? 0;

$stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
$stmt->execute([$id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    header("Location: list_products.php");
    exit;
}

// --- XỬ LÝ XÓA ẢNH PHỤ KHI BẤM NÚT XÓA ---
if (isset($_POST['delete_gallery_image_id'])) {
    $del_img_id = (int)$_POST['delete_gallery_image_id'];
    
    $stmt_find = $pdo->prepare("SELECT image_url FROM product_images WHERE id = ? AND product_id = ?");
    $stmt_find->execute([$del_img_id, $id]);
    $file_to_delete = $stmt_find->fetchColumn();
    
    if ($file_to_delete) {
        $file_path = "../upload/product_gallery/" . $file_to_delete;
        if (file_exists($file_path)) {
            @unlink($file_path);
        }
        $pdo->prepare("DELETE FROM product_images WHERE id = ?")->execute([$del_img_id]);
    }
    header("Location: edit.php?id=" . $id . "&msg=Đã xóa ảnh phụ");
    exit;
}

$stmt_tags = $pdo->prepare("SELECT tag_id FROM product_tags WHERE product_id = ?");
$stmt_tags->execute([$id]);
$current_tags = $stmt_tags->fetchAll(PDO::FETCH_COLUMN);

// Lấy toàn bộ album ảnh phụ hiện có
$stmt_gallery = $pdo->prepare("SELECT * FROM product_images WHERE product_id = ?");
$stmt_gallery->execute([$id]);
$gallery_images = $stmt_gallery->fetchAll(PDO::FETCH_ASSOC);

// LẤY TOÀN BỘ THÔNG SỐ KỸ THUẬT HIỆN TẠI CỦA SẢN PHẨM
$stmt_get_specs = $pdo->prepare("SELECT * FROM product_specs WHERE product_id = ? ORDER BY sort_order ASC");
$stmt_get_specs->execute([$id]);
$current_specs = $stmt_get_specs->fetchAll(PDO::FETCH_ASSOC);

$categories = $pdo->query("SELECT * FROM categories")->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['delete_gallery_image_id'])) {

    $image_url = trim($_POST['image_url'] ?? '');

    if ($image_url === '') {
        $image_url = $product['image_url'];
    }

    if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] == 0) {
        $target_dir = "../upload/product_image/";

        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true);
        }

        $allowed_types = ['image/jpeg', 'image/png', 'image/jpg'];

        if (!in_array($_FILES['product_image']['type'], $allowed_types)) {
            die("Chỉ cho phép JPG, PNG!");
        }

        $ext = pathinfo($_FILES["product_image"]["name"], PATHINFO_EXTENSION);
        $file_name = time() . "_" . basename($_FILES["product_image"]["name"]);
        $target_file = $target_dir . $file_name;

        if (move_uploaded_file($_FILES["product_image"]["tmp_name"], $target_file)) {
            $image_url = $file_name;
        }
    }

    // --- UPLOAD THÊM ẢNH PHỤ MỚI ---
    if (isset($_FILES['product_gallery']) && !empty($_FILES['product_gallery']['name'][0])) {
        $gallery_dir = "../upload/product_gallery/";
        if (!is_dir($gallery_dir)) {
            mkdir($gallery_dir, 0777, true);
        }

        $stmt_gallery_ins = $pdo->prepare("INSERT INTO product_images (product_id, image_url) VALUES (?, ?)");
        foreach ($_FILES['product_gallery']['name'] as $key => $name) {
            if ($_FILES['product_gallery']['error'][$key] == 0) {
                $g_ext = pathinfo($name, PATHINFO_EXTENSION);
                $g_file_name = time() . "_gal_" . $key . "." . $g_ext;
                if (move_uploaded_file($_FILES['product_gallery']['tmp_name'][$key], $gallery_dir . $g_file_name)) {
                    $stmt_gallery_ins->execute([$id, $g_file_name]);
                }
            }
        }
    }

    $tags = $_POST['tags'] ?? [];
    $discount_price = (in_array('2', $tags) && !empty($_POST['discount_price'])) ? $_POST['discount_price'] : null;

    $stmt_update = $pdo->prepare("
        UPDATE products 
        SET name=?, price=?, discount_price=?, stock_quantity=?, category_id=?, description=?, image_url=? 
        WHERE id=?
    ");

    $stmt_update->execute([
        $_POST['name'],
        $_POST['price'],
        $discount_price,
        $_POST['stock'],
        $_POST['category_id'],
        $_POST['description'],
        $image_url,
        $id
    ]);

    // --- XỬ LÝ ĐÈ LẠI THÔNG SỐ KỸ THUẬT MỚI KHI EDIT ---
    $pdo->prepare("DELETE FROM product_specs WHERE product_id = ?")->execute([$id]);
    if (!empty($_POST['spec_names']) && !empty($_POST['spec_values'])) {
        $spec_names = $_POST['spec_names'];
        $spec_values = $_POST['spec_values'];
        
        $stmt_spec_ins = $pdo->prepare("INSERT INTO product_specs (product_id, spec_name, spec_value, sort_order) VALUES (?, ?, ?, ?)");
        $sort_order = 1;
        
        foreach ($spec_names as $index => $name) {
            $name = trim($name);
            $value = trim($spec_values[$index] ?? '');
            if (!empty($name)) {
                $stmt_spec_ins->execute([$id, $name, $value, $sort_order]);
                $sort_order++;
            }
        }
    }

    $pdo->prepare("DELETE FROM product_tags WHERE product_id = ?")->execute([$id]);

    if (!empty($_POST['tags'])) {
        $stmt_ins_tag = $pdo->prepare("INSERT INTO product_tags (product_id, tag_id) VALUES (?, ?)");
        foreach ($_POST['tags'] as $tag_id) {
            $stmt_ins_tag->execute([$id, $tag_id]);
        }
    }

    header("Location: list_products.php?msg=Sửa thành công");
    exit;
}

$img = $product['image_url'];
$src = (filter_var($img, FILTER_VALIDATE_URL)) ? $img : "../upload/product_image/" . $img;

if (empty($img)) {
    $src = "../assets/images/logo-fd.jpg";
}
?>

<?php
$page_title = 'Sửa sản phẩm';
$page_icon = 'fa-solid fa-pen-to-square';
$custom_css = '<link rel="stylesheet" href="/assets/css/style_add_product.css">';

include 'includes/header.php';
?>

        <div class="dashboard-container">
            <div class="card">
                <h3>
                    <i class="fa-solid fa-pen-to-square"></i>
                    Chỉnh sửa sản phẩm
                </h3>

                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="id" value="<?= $product['id'] ?>">

                    <div class="form-group">
                        <label class="form-label">Tên sản phẩm</label>
                        <input
                            name="name"
                            value="<?= htmlspecialchars($product['name']) ?>"
                            class="form-control"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label class="form-label tag-label">🏷️ Gắn nhãn sản phẩm</label>

                        <div class="tag-box">
                            <label class="tag-option">
                                <input
                                    type="checkbox"
                                    name="tags[]"
                                    value="1"
                                    <?= in_array(1, $current_tags) ? 'checked' : '' ?>
                                >
                                <span class="tag-badge tag-featured">
                                    <i class="fa-solid fa-star"></i>
                                    Sản phẩm nổi bật
                                </span>
                            </label>

                            <label class="tag-option">
                                <input
                                    type="checkbox"
                                    name="tags[]"
                                    value="2"
                                    id="flash-sale-checkbox"
                                    <?= in_array(2, $current_tags) ? 'checked' : '' ?>
                                >
                                <span class="tag-badge tag-sale">
                                    <i class="fa-solid fa-bolt"></i>
                                    Flash sale
                                </span>
                            </label>
                        </div>
                    </div>

                    <div class="form-group" id="flash-sale-price-group" style="display: <?= in_array(2, $current_tags) ? 'block' : 'none' ?>; background: #fff5f5; padding: 12px; border-radius: 6px; border: 1px solid #fee2e2;">
                        <label class="form-label" style="color: #dc2626; font-weight: bold;">Giá Flash Sale (₫)</label>
                        <input 
                            name="discount_price" 
                            type="number" 
                            step="any" 
                            min="0" 
                            value="<?= htmlspecialchars($product['discount_price'] ?? '') ?>" 
                            class="form-control" 
                            placeholder="Nhập giá bán riêng cho Flash Sale..."
                        >
                    </div>

                    <div class="form-group">
                        <label class="form-label">Ảnh hiện tại</label>
                        <div class="current-image-box">
                            <img
                                src="<?= htmlspecialchars($src) ?>"
                                class="current-product-image"
                                alt="<?= htmlspecialchars($product['name']) ?>"
                                onerror="this.src='../assets/images/logo-fd.jpg'"
                                style="max-width: 150px; border-radius: 4px;"
                            >
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Link ảnh online</label>
                        <input
                            type="url"
                            name="image_url"
                            class="form-control"
                            placeholder="https://i.ibb.co/..."
                            value="<?= filter_var($product['image_url'], FILTER_VALIDATE_URL) ? htmlspecialchars($product['image_url']) : '' ?>"
                        >
                    </div>

                    <div class="form-group">
                        <label class="form-label">Upload ảnh local</label>
                        <input type="file" name="product_image" class="form-control" accept="image/*">
                    </div>

                    <div class="form-group" style="background: #f8fafc; padding: 15px; border-radius: 6px; border: 1px solid #e2e8f0; margin-top: 20px;">
                        <label class="form-label" style="color: #10b981; font-weight: bold;">📸 Album ảnh phụ hiện tại</label>
                        
                        <?php if (empty($gallery_images)): ?>
                            <p style="color: #94a3b8; font-style: italic; font-size: 0.9rem;">Sản phẩm chưa có ảnh phụ.</p>
                        <?php else: ?>
                            <div style="display: flex; flex-wrap: wrap; gap: 12px; margin-bottom: 15px;">
                                <?php foreach ($gallery_images as $g_img): ?>
                                    <div style="position: relative; width: 80px; height: 80px; border: 1px solid #cbd5e1; border-radius: 4px; padding: 2px; background: #fff;">
                                        <img src="../upload/product_gallery/<?= $g_img['image_url'] ?>" style="width: 100%; height: 100%; object-fit: cover; border-radius: 2px;">
                                        <button type="submit" name="delete_gallery_image_id" value="<?= $g_img['id'] ?>" onclick="return confirm('Bạn có chắc muốn xóa ảnh phụ này?')" style="position: absolute; top: -6px; right: -6px; background: #ef4444; color: #fff; border: none; border-radius: 50%; width: 18px; height: 18px; font-size: 10px; cursor: pointer; display: flex; align-items: center; justify-content: center;">✕</button>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <label class="form-label" style="color: #10b981; font-weight: bold; margin-top: 10px; display: block;">Upload thêm ảnh phụ mới</label>
                        <input type="file" name="product_gallery[]" class="form-control" accept="image/*" multiple>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Giá (₫)</label>
                        <input
                            name="price"
                            type="number"
                            step="any"
                            min="0"
                            value="<?= $product['price'] ?>"
                            class="form-control"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label class="form-label">Tồn kho</label>
                        <input
                            name="stock"
                            type="number"
                            value="<?= $product['stock_quantity'] ?>"
                            class="form-control"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label class="form-label">Danh mục</label>
                        <select name="category_id" class="form-control">
                            <?php foreach ($categories as $c): ?>
                                <option value="<?= $c['id'] ?>" <?= $product['category_id'] == $c['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($c['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Mô tả</label>
                        <textarea name="description" class="form-control" rows="4"><?= htmlspecialchars($product['description']) ?></textarea>
                    </div>

                    <div class="form-group" style="background: #f8fafc; padding: 15px; border-radius: 6px; border: 1px solid #cbd5e1; margin-bottom: 20px;">
                        <label class="form-label" style="font-weight: bold; color: #1e293b; display: block; margin-bottom: 10px;">⚙️ Thông số kỹ thuật sản phẩm</label>
                        
                        <div id="specs-wrapper">
                            <?php if (!empty($current_specs)): ?>
                                <?php foreach ($current_specs as $spec): ?>
                                    <div class="spec-item" style="display: flex; gap: 10px; margin-bottom: 10px;">
                                        <input type="text" name="spec_names[]" value="<?= htmlspecialchars($spec['spec_name']) ?>" class="form-control" placeholder="Tên thông số" style="flex: 1;">
                                        <input type="text" name="spec_values[]" value="<?= htmlspecialchars($spec['spec_value']) ?>" class="form-control" placeholder="Giá trị" style="flex: 2;">
                                        <button type="button" class="btn btn-danger remove-spec-btn" style="background: #ef4444; color: #fff; border: none; padding: 0 15px; border-radius: 4px; cursor: pointer;">Xóa</button>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="spec-item" style="display: flex; gap: 10px; margin-bottom: 10px;">
                                    <input type="text" name="spec_names[]" class="form-control" placeholder="Tên thông số (VD: Kết nối)" style="flex: 1;">
                                    <input type="text" name="spec_values[]" class="form-control" placeholder="Giá trị (VD: Không dây)" style="flex: 2;">
                                    <button type="button" class="btn btn-danger remove-spec-btn" style="background: #ef4444; color: #fff; border: none; padding: 0 15px; border-radius: 4px; cursor: pointer;">Xóa</button>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <button type="button" id="add-spec-btn" class="btn" style="background: #2563eb; color: #fff; padding: 6px 12px; border: none; border-radius: 4px; cursor: pointer; font-size: 0.9rem; margin-top: 5px;">
                            <i class="fa-solid fa-plus"></i> Thêm dòng thông số
                        </button>
                    </div>

                    <button class="btn btn-primary">
                        <i class="fa-solid fa-save"></i>
                        Cập nhật sản phẩm
                    </button>

                    <a href="list_products.php" class="btn btn-cancel">Hủy</a>
                </form>
            </div>
        </div>

    </main>

</div>
<script src="../assets/js/script_dashboard.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Xử lý ẩn hiện Flash Sale
    const flashSaleCheckbox = document.getElementById('flash-sale-checkbox');
    const flashSalePriceGroup = document.getElementById('flash-sale-price-group');
    
    if (flashSaleCheckbox && flashSalePriceGroup) {
        flashSaleCheckbox.addEventListener('change', function() {
            if (this.checked) {
                flashSalePriceGroup.style.display = 'block';
            } else {
                flashSalePriceGroup.style.display = 'none';
                flashSalePriceGroup.querySelector('input').value = '';
            }
        });
    }

    // Xử lý thêm/xóa dòng thông số kỹ thuật động
    const specsWrapper = document.getElementById('specs-wrapper');
    const addSpecBtn = document.getElementById('add-spec-btn');

    addSpecBtn.addEventListener('click', function() {
        const div = document.createElement('div');
        div.className = 'spec-item';
        div.style = 'display: flex; gap: 10px; margin-bottom: 10px;';
        div.innerHTML = `
            <input type="text" name="spec_names[]" class="form-control" placeholder="Tên thông số" style="flex: 1;">
            <input type="text" name="spec_values[]" class="form-control" placeholder="Giá trị" style="flex: 2;">
            <button type="button" class="btn btn-danger remove-spec-btn" style="background: #ef4444; color: #fff; border: none; padding: 0 15px; border-radius: 4px; cursor: pointer;">Xóa</button>
        `;
        specsWrapper.appendChild(div);
    });

    specsWrapper.addEventListener('click', function(e) {
        if (e.target.classList.contains('remove-spec-btn')) {
            e.target.parentElement.remove();
        }
    });
});
</script>
</body>
</html>
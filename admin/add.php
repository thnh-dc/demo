<?php
session_start();
include '../config/database.php';
require_once __DIR__ . '/check_admin.php';

$categories = $pdo->query("SELECT * FROM categories")->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $target_dir = "../upload/product_image/";

    if (!is_dir($target_dir)) {
        mkdir($target_dir, 0777, true);
    }

    $image_url = trim($_POST['image_url'] ?? '');

    if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] == 0) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/jpg'];

        if (!in_array($_FILES['product_image']['type'], $allowed_types)) {
            die("Chỉ cho phép JPG, PNG!");
        }

        $ext = pathinfo($_FILES["product_image"]["name"], PATHINFO_EXTENSION);
        $file_name = time() . "." . $ext;

        $target_file = $target_dir . $file_name;

        if (move_uploaded_file($_FILES["product_image"]["tmp_name"], $target_file)) {
            $image_url = $file_name;
        }
    }

    // --- XỬ LÝ GIÁ FLASH SALE ---
    $tags = $_POST['tags'] ?? [];
    $discount_price = (in_array('2', $tags) && !empty($_POST['discount_price'])) ? $_POST['discount_price'] : null;

    $stmt = $pdo->prepare("
        INSERT INTO products(name, price, discount_price, stock_quantity, category_id, description, image_url)
        VALUES(?,?,?,?,?,?,?)
    ");

    $stmt->execute([
        $_POST['name'],
        $_POST['price'],
        $discount_price,
        $_POST['stock'],
        $_POST['category_id'],
        $_POST['description'],
        $image_url
    ]);

    $product_id = $pdo->lastInsertId();

    // --- XỬ LÝ LƯU THÔNG SỐ KỸ THUẬT ĐỘNG ---
    if (!empty($_POST['spec_names']) && !empty($_POST['spec_values'])) {
        $spec_names = $_POST['spec_names'];
        $spec_values = $_POST['spec_values'];
        
        $stmt_spec = $pdo->prepare("INSERT INTO product_specs (product_id, spec_name, spec_value, sort_order) VALUES (?, ?, ?, ?)");
        $sort_order = 1;

        foreach ($spec_names as $index => $name) {
            $name = trim($name);
            $value = trim($spec_values[$index] ?? '');

            if (!empty($name)) {
                $stmt_spec->execute([$product_id, $name, $value, $sort_order]);
                $sort_order++;
            }
        }
    }

    // --- XỬ LÝ UPLOAD NHIỀU ẢNH PHỤ (ALBUM) ---
    if (isset($_FILES['product_gallery']) && !empty($_FILES['product_gallery']['name'][0])) {
        $gallery_dir = "../upload/product_gallery/";
        if (!is_dir($gallery_dir)) {
            mkdir($gallery_dir, 0777, true);
        }

        $stmt_gallery = $pdo->prepare("INSERT INTO product_images (product_id, image_url) VALUES (?, ?)");

        foreach ($_FILES['product_gallery']['name'] as $key => $name) {
            if ($_FILES['product_gallery']['error'][$key] == 0) {
                $g_ext = pathinfo($name, PATHINFO_EXTENSION);
                $g_file_name = time() . "_gal_" . $key . "." . $g_ext;
                $g_target_file = $gallery_dir . $g_file_name;

                if (move_uploaded_file($_FILES['product_gallery']['tmp_name'][$key], $g_target_file)) {
                    $stmt_gallery->execute([$product_id, $g_file_name]);
                }
            }
        }
    }

    if (!empty($_POST['tags'])) {
        $stmt_tags = $pdo->prepare("INSERT INTO product_tags (product_id, tag_id) VALUES (?, ?)");
        foreach ($_POST['tags'] as $tag_id) {
            $stmt_tags->execute([$product_id, $tag_id]);
        }
    }

    header("Location: list_products.php?msg=Thêm thành công");
    exit;
}
?>

<?php
$page_title = 'Thêm sản phẩm';
$page_icon = 'fa-solid fa-plus';
$custom_css = '<link rel="stylesheet" href="/FD-Tech/assets/css/style_add_product.css">';

include 'includes/header.php';
?>

        <div class="dashboard-container">
            <div class="card">
                <h3>
                    <i class="fa-solid fa-plus"></i>
                    Thêm sản phẩm mới
                </h3>

                <form method="POST" enctype="multipart/form-data">

                    <div class="form-group">
                        <label class="form-label">Tên sản phẩm</label>
                        <input name="name" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label tag-label">🏷️ Gắn nhãn sản phẩm</label>

                        <div class="tag-box">
                            <label class="tag-option">
                                <input type="checkbox" name="tags[]" value="1">
                                <span class="tag-badge tag-featured">
                                    <i class="fa-solid fa-star"></i>
                                    Sản phẩm nổi bật
                                </span>
                            </label>

                            <label class="tag-option">
                                <input type="checkbox" name="tags[]" value="2" id="flash-sale-checkbox">
                                <span class="tag-badge tag-sale">
                                    <i class="fa-solid fa-bolt"></i>
                                    Flash sale
                                </span>
                            </label>
                        </div>
                    </div>

                    <div class="form-group" id="flash-sale-price-group" style="display: none; background: #fff5f5; padding: 12px; border-radius: 6px; border: 1px solid #fee2e2;">
                        <label class="form-label" style="color: #dc2626; font-weight: bold;">Giá Flash Sale (₫)</label>
                        <input name="discount_price" type="number" step="any" min="0" class="form-control" placeholder="Nhập giá bán riêng cho Flash Sale...">
                    </div>

                    <div class="form-group" style="border-left: 3px solid #3b82f6; padding-left: 10px;">
                        <label class="form-label" style="font-weight: bold; color: #3b82f6;">🖼️ Ảnh Đại Diện (Ảnh chính)</label>
                        <input
                            type="url"
                            name="image_url"
                            class="form-control"
                            placeholder="https://i.ibb.co/..."
                        >
                        <input type="file" name="product_image" class="form-control" accept="image/*" style="margin-top: 8px;">
                    </div>

                    <div class="form-group" style="border-left: 3px solid #10b981; padding-left: 10px; background: #f0fdf4; padding: 10px; border-radius: 4px;">
                        <label class="form-label" style="font-weight: bold; color: #10b981;">📸 Thêm Album Ảnh Phụ (Chọn nhiều ảnh)</label>
                        <input type="file" name="product_gallery[]" class="form-control" accept="image/*" multiple>
                        <small style="color: #666;">Nhấn giữ nút <b>Ctrl</b> (hoặc Command trên Mac) để chọn nhiều ảnh cùng lúc.</small>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Giá (₫)</label>
                        <input name="price" type="number" step="any" min="0" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Tồn kho</label>
                        <input name="stock" type="number" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Danh mục</label>
                        <select name="category_id" class="form-control">
                            <?php foreach ($categories as $c): ?>
                                <option value="<?= $c['id'] ?>">
                                    <?= htmlspecialchars($c['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Mô tả</label>
                        <textarea name="description" class="form-control" rows="4"></textarea>
                    </div>

                    <div class="form-group" style="background: #f8fafc; padding: 15px; border-radius: 6px; border: 1px solid #cbd5e1; margin-bottom: 20px;">
                        <label class="form-label" style="font-weight: bold; color: #1e293b; display: block; margin-bottom: 10px;">⚙️ Thông số kỹ thuật sản phẩm</label>
                        
                        <div id="specs-wrapper">
                            <div class="spec-item" style="display: flex; gap: 10px; margin-bottom: 10px;">
                                <input type="text" name="spec_names[]" class="form-control" placeholder="Tên thông số (VD: Kết nối)" style="flex: 1;">
                                <input type="text" name="spec_values[]" class="form-control" placeholder="Giá trị (VD: Không dây 2.4Ghz)" style="flex: 2;">
                                <button type="button" class="btn btn-danger remove-spec-btn" style="background: #ef4444; color: #fff; border: none; padding: 0 15px; border-radius: 4px; cursor: pointer;">Xóa</button>
                            </div>
                        </div>
                        
                        <button type="button" id="add-spec-btn" class="btn" style="background: #2563eb; color: #fff; padding: 6px 12px; border: none; border-radius: 4px; cursor: pointer; font-size: 0.9rem; margin-top: 5px;">
                            <i class="fa-solid fa-plus"></i> Thêm dòng thông số
                        </button>
                    </div>

                    <button class="btn btn-primary">
                        <i class="fa-solid fa-save"></i>
                        Lưu sản phẩm
                    </button>

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
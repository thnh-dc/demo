<?php 
    include '../includes/header.php'; 
    require_once '../config/database.php';

    // Lấy từ khóa tìm kiếm
    $search = isset($_GET['query']) ? trim($_GET['query']) : '';
?>

<div class="container" style="margin-top: 30px;">
    <h2 class="section-title">Kết quả tìm kiếm cho: "<?php echo htmlspecialchars($search); ?>"</h2>
    
    <div class="product-grid" style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px;">
        <?php
        if ($search !== '') {
            try {
                // Sử dụng Prepared Statement của PDO để chống SQL Injection
                $sql = "SELECT * FROM products WHERE name LIKE :search"; 
                $stmt = $pdo->prepare($sql);
                $stmt->execute(['search' => "%$search%"]);
                $products = $stmt->fetchAll();

                if (count($products) > 0) {
                    foreach ($products as $row) {
                        echo '<div class="product-card">';
                        echo '<a href="product_detail.php?id='.$row['id'].'" style="text-decoration: none; color: #333;">';
                        
                        // Xử lý hiển thị ảnh Base64
                        if (!empty($row['image_data'])) {
                            echo '<img src="data:image/jpeg;base64,'.base64_encode($row['image_data']).'" style="width: 100%; height: 150px; object-fit: contain; margin-bottom: 10px; border-radius: 8px;">';
                        } else {
echo '<img src="'.$row['image_url'].'" style="width: 100%; height: 150px; object-fit: contain; margin-bottom: 10px; border-radius: 8px;">';                        }

                        echo '<h3 style="font-size: 14px; margin-bottom: 8px;">'.htmlspecialchars($row['name']).'</h3>';
                        echo '<p style="color: #ee4d2d; font-weight: bold;">'.number_format($row['price']).' ₫</p>';
                        echo '</a></div>';
                    }
                } else {
                    echo '<p>Không tìm thấy sản phẩm nào phù hợp.</p>';
                }
            } catch (PDOException $e) {
                echo "Lỗi truy vấn: " . $e->getMessage();
            }
        }
        ?>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
<?php
    session_start();
    require_once '../auth/user_only.php';
    require_once '../config/database.php';
    $custom_css='
        <link rel="stylesheet" href="../assets/css/index.css">';
    include '../includes/header.php'; 
    
?>

<div class="banner-container">
    <div class="banner-track" id="bannerTrack">
        <div class="banner-slide"><img src="../assets/images/banner1.jpg"></div>
        <div class="banner-slide"><img src="../assets/images/banner.jpg"></div>
    </div>
</div>
<div class="container"> 
    
    <h2 id="san-pham-noi-bat" class="section-title">⭐ Sản phẩm nổi bật</h2>
        <div class="product-grid">
            <?php
            $tag_id_noi_bat = 1; // ID của tag "Nổi bật" trong bảng tags của bạn

            $sql = "SELECT p.* FROM products p 
                    INNER JOIN product_tags pt ON p.id = pt.product_id 
                    WHERE pt.tag_id = ? 
                    LIMIT 8";
                    
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$tag_id_noi_bat]);

            // Chạy vòng lặp từ kết quả đã prepare ở trên
            $base_url = "/FD-Tech/";

            while($row = $stmt->fetch()) {
            echo '<div class="product-card">';
            echo '<span class="badge-hot">HOT</span>';
            echo '<a href="product_detail.php?id='.$row['id'].'" style="text-decoration:none; color:#333;">';
            echo '<img src="'.(strpos($row['image_url'], 'http') === 0 
                ? $row['image_url'] 
                : 'http://localhost/FD-Tech/upload/product_image/'.$row['image_url']).'">';
            echo '<h3>'.$row['name'].'</h3>';
            if (isset($row['discount_price']) && $row['discount_price'] > 0) {
                echo '<div class="price-container">';
                echo '<p class="price">'.number_format($row['discount_price']).' ₫</p>';
                echo '<p class="old-price">'.number_format($row['price']).' ₫</p>';
                echo '</div>';
            } else {
                echo '<div class="price-container">';
                echo '<p class="price">'.number_format($row['price']).' ₫</p>';
                echo '</div>';
    }
    echo '</a></div>';
            }
            ?>
        </div>  

    <h2 id="khuyen-mai" class="section-title">🔥 Flash Sale - Giá Sốc</h2>
        <div class="product-grid">
            <?php
            $tag_id_flash_sale = 2; // ID của tag "Flash Sale" trong database

            $sql_sale = "SELECT p.* FROM products p 
                        INNER JOIN product_tags pt ON p.id = pt.product_id 
                        WHERE pt.tag_id = ? 
                        LIMIT 8";
                    
            $stmt_sale = $pdo->prepare($sql_sale);
            $stmt_sale->execute([$tag_id_flash_sale]);
            $base_url = "/FD-Tech/";

            while($row = $stmt_sale->fetch()) { 
                echo '<div class="product-card">';
                echo '<span class="badge-sale">SALE</span>'; 
                echo '<a href="product_detail.php?id='.$row['id'].'" style="text-decoration:none; color:#333;">';
                echo '<img src="'.(strpos($row['image_url'], 'http') === 0 
                    ? $row['image_url'] 
                    : 'http://localhost/FD-Tech/upload/product_image/'.$row['image_url']).'">';
                    echo '<h3>'.$row['name'].'</h3>';
                    if (isset($row['discount_price']) && $row['discount_price'] > 0) {
                        echo '<div class="price-container">';
                        echo '<p class="price">'.number_format($row['discount_price']).' ₫</p>';
                        echo '<p class="old-price">'.number_format($row['price']).' ₫</p>';
                        echo '</div>';
                    } else {
                        echo '<div class="price-container">';
                        echo '<p class="price">'.number_format($row['price']).' ₫</p>';
                        echo '</div>';
            }
    echo '</a></div>';
            }
            ?>
            </div>
            <h2 id="san-pham-dang-ban" class="section-title">🛒 Sản phẩm đang bán</h2>
        <div class="product-grid">
            <?php
            $sql_random = "SELECT * FROM products 
                           ORDER BY RAND() 
                           LIMIT 8";
                    
            $stmt_random = $pdo->query($sql_random);

            while($row = $stmt_random->fetch()) { 
                echo '<div class="product-card">';
                echo '<a href="product_detail.php?id='.$row['id'].'" style="text-decoration:none; color:#333;">';
                echo '<img src="'.(strpos($row['image_url'], 'http') === 0 
                    ? $row['image_url'] 
                    : 'http://localhost/FD-Tech/upload/product_image/'.$row['image_url']).'">';
                    echo '<h3>'.$row['name'].'</h3>';
                    if (isset($row['discount_price']) && $row['discount_price'] > 0) {
                        echo '<div class="price-container">';
                        echo '<p class="price">'.number_format($row['discount_price']).' ₫</p>';
                        echo '<p class="old-price">'.number_format($row['price']).' ₫</p>';
                        echo '</div>';
                    } else {
                        echo '<div class="price-container">';
                        echo '<p class="price">'.number_format($row['price']).' ₫</p>';
                        echo '</div>';
                    }
                    echo '</a></div>';
            }
            ?>
        </div>
</div>
<?php include '../includes/ai_assistant_widget.php'; ?>
<?php include '../includes/footer.php'; ?>
<link rel="stylesheet" href="../assets/css/style_sidebar.css">

<aside class="sidebar">
    <div class="sidebar-header">
        <h2>FD TECH</h2>
    </div>

    <ul class="sidebar-menu">
        <li class="menu-item <?= (basename($_SERVER['PHP_SELF']) == 'admin_dashboard.php') ? 'active' : '' ?>">
            <a href="admin_dashboard.php"><i class="fa-solid fa-chart-pie"></i> Thống kê</a>
        </li>

        <li class="menu-item <?= (basename($_SERVER['PHP_SELF']) == 'list_order.php') ? 'active' : '' ?>">
            <a href="list_order.php"><i class="fa-solid fa-cart-shopping"></i> Quản lí đơn hàng</a>
        </li>
        <li class="menu-item <?= (basename($_SERVER['PHP_SELF']) == 'chat_list.php' || basename($_SERVER['PHP_SELF']) == 'chat_detail.php') ? 'active' : '' ?>">
            <a href="chat_list.php"><i class="fa-solid fa-comments"></i> Tin nhắn khách hàng</a>
        </li>
        <?php 
            $product_pages = ['add.php', 'edit.php', 'list_products.php'];
            $is_product_active = in_array(basename($_SERVER['PHP_SELF']), $product_pages);
        ?>
        <li class="menu-item has-submenu">
            <a href="#" class="submenu-toggle">
                <i class="fa-solid fa-box-open"></i> Danh mục sản phẩm
                <i class="fa-solid fa-chevron-down arrow-icon"></i>
            </a>
            <ul class="submenu <?= $is_product_active ? 'show' : '' ?>">
                <li><a href="add.php" class="<?= (basename($_SERVER['PHP_SELF']) == 'add.php') ? 'active-sub' : '' ?>"><i class="fa-solid fa-plus"></i> Thêm sản phẩm</a></li>
                <li><a href="list_products.php" class="<?= (basename($_SERVER['PHP_SELF']) == 'list_products.php') ? 'active-sub' : '' ?>"><i class="fa-solid fa-trash"></i> Quản lí sản phẩm</a></li>
            </ul>
        </li>
        <li class="menu-item <?= (basename($_SERVER['PHP_SELF']) == 'list_users.php' || basename($_SERVER['PHP_SELF']) == 'user_detail.php') ? 'active' : '' ?>">
            <a href="list_users.php"><i class="fa-solid fa-users"></i> Quản lí người dùng</a>
        </li>
    </ul>
    <div class="sidebar-footer">
        <a href="/FD-Tech/auth/logout.php" class="btn btn-danger logout-btn">
            <i class="fa-solid fa-right-from-bracket"></i> Đăng xuất
        </a>
    </div>
</aside>
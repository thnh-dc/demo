<?php
session_start();
include '../config/database.php';
require_once __DIR__ . '/check_admin.php';

try {
    // 1. Tính tổng doanh thu từ các đơn hàng đã hoàn thành
    $stmtRevenue = $pdo->prepare("
        SELECT SUM(total_amount) AS total_revenue 
        FROM orders 
        WHERE status = 'completed'
    ");
    $stmtRevenue->execute();
    $revenueRow = $stmtRevenue->fetch(PDO::FETCH_ASSOC);
    $totalRevenue = $revenueRow['total_revenue'] ?? 0;

    // 2. Đếm số lượng đơn hàng mới đang chờ xác nhận
    $stmtOrders = $pdo->prepare("
        SELECT COUNT(id) AS new_orders 
        FROM orders 
        WHERE status = 'pending'
    ");
    $stmtOrders->execute();
    $ordersRow = $stmtOrders->fetch(PDO::FETCH_ASSOC);
    $newOrdersCount = $ordersRow['new_orders'] ?? 0;

    // 3. Đếm tổng số sản phẩm
    $stmtProducts = $pdo->prepare("
        SELECT COUNT(id) AS total_products 
        FROM products
    ");
    $stmtProducts->execute();
    $productsRow = $stmtProducts->fetch(PDO::FETCH_ASSOC);
    $totalProductsCount = $productsRow['total_products'] ?? 0;

    // 4. Lấy dữ liệu doanh thu theo tháng để vẽ biểu đồ
    $stmtChart = $pdo->prepare("
        SELECT 
            DATE_FORMAT(created_at, '%Y-%m') AS month,
            SUM(total_amount) AS revenue
        FROM orders
        WHERE status = 'completed' 
        AND created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
        GROUP BY month
        ORDER BY month
    ");
    $stmtChart->execute();
    $chartData = $stmtChart->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    echo "Lỗi truy vấn: " . $e->getMessage();
}

$labels = [];
$data = [];

foreach ($chartData as $row) {
    $labels[] = $row['month'];
    $data[] = $row['revenue'];
}
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - FD Tech</title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <link rel="stylesheet" href="../assets/css/style_chung.css">
    <link rel="stylesheet" href="../assets/css/style_dashboard.css">
</head>

<body>

<div class="dashboard-layout">

    <?php include 'includes/sidebar.php'; ?>

    <main class="main-content">

        <div class="top-navbar">
            <h1 class="page-title">Tổng quan Thống kê</h1>

            <div class="admin-profile">
                <span class="text-muted">Xin chào, <b>Admin</b></span>
                <img src="../assets/images/logo-fd.jpg" alt="Avatar">
            </div>
        </div>

        <div class="container dashboard-container">

            <div class="stats-grid">

                <div class="stat-card">
                    <div class="stat-icon stat-icon-revenue">
                        <i class="fa-solid fa-sack-dollar"></i>
                    </div>

                    <div class="stat-info">
                        <span class="text-muted">Tổng doanh thu</span>
                        <h3><?= number_format($totalRevenue, 0, ',', '.') ?>₫</h3>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon stat-icon-orders">
                        <i class="fa-solid fa-file-invoice"></i>
                    </div>

                    <div class="stat-info">
                        <span class="text-muted">Đơn hàng mới</span>
                        <h3><?= $newOrdersCount ?> đơn hàng</h3>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon stat-icon-products">
                        <i class="fa-solid fa-cubes"></i>
                    </div>

                    <div class="stat-info">
                        <span class="text-muted">Sản phẩm đang bán</span>
                        <h3><?= $totalProductsCount ?> sản phẩm</h3>
                    </div>
                </div>

            </div>

            <section class="section-block dashboard-chart-section">
                <div class="card stat-chart-placeholder">
                    <h3 class="chart-title">Biểu đồ doanh thu</h3>
                    <canvas id="revenueChart"></canvas>
                </div>
            </section>

        </div>

    </main>

</div>

<script>
    const revenueLabels = <?= json_encode($labels) ?>;
    const revenueData = <?= json_encode($data) ?>;
</script>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="../assets/js/script_dashboard.js"></script>

</body>
</html>
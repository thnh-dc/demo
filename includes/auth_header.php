<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title : 'FD Tech'; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style_auth.css">
    <link rel="stylesheet" href="../assets/css/style_chung.css">
    <link rel="stylesheet" href="../assets/css/footer.css">
</head>
<body>

    <header class="auth-header">
        <div class="auth-header-container">
            <a href="/FD-Tech/user/index.php" class="auth-logo">
                <img src="../assets/images/logo-fd.jpg" alt="FD Tech Logo" onerror="this.style.display='none'">
                <span class="auth-brand">FD<span>TECH</span></span>
            </a>
        </div>
    </header>

    <div class="login-wrapper">
        <div class="login-container">
            <div class="login-branding">
                <img src="../assets/images/logo-fd.jpg" alt="FD Tech Logo" onerror="this.style.display='none'">
                
                <?php if (isset($is_admin) && $is_admin == true): ?>
                    <h1>FD TECH ADMIN</h1>
                    <p>Khu vực xác thực bảo mật 2 lớp<br>dành riêng cho Quản trị viên</p>
                <?php else: ?>
                    <h1>FD TECH</h1>
                    <p>Nền tảng mua sắm đồ chơi công nghệ<br>và phụ kiện chơi game hàng đầu</p>
                <?php endif; ?>
            </div>

            <div class="login-form-box">
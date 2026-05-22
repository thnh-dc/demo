<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
    ?>
    <!DOCTYPE html>
    <html lang="vi">
    <head>
        <meta charset="UTF-8">
        <title>Không thể truy cập trang người dùng</title>
        <style>
            * {
                margin: 0;
                padding: 0;
                box-sizing: border-box;
                font-family: Arial, sans-serif;
            }
            body {
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
                background: linear-gradient(135deg, #dcfce7, #22c55e);
            }
            .admin-warning-box {
                width: 90%;
                max-width: 520px;
                background: #ffffff;
                border-radius: 18px;
                padding: 35px 30px;
                text-align: center;
                box-shadow: 0 15px 35px rgba(0, 0, 0, 0.18);
                border-top: 8px solid #16a34a;
            }
            .warning-icon {
                width: 78px;
                height: 78px;
                margin: 0 auto 18px;
                border-radius: 50%;
                background: #dcfce7;
                color: #16a34a;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 40px;
                font-weight: bold;
            }
            .admin-warning-box p {
                color: #374151;
                font-size: 15px;
                line-height: 1.6;
                margin-bottom: 25px;
            }
            .btn-logout {
                display: inline-block;
                padding: 12px 24px;
                background: #16a34a;
                color: #ffffff;
                text-decoration: none;
                border-radius: 10px;
                font-weight: bold;
                transition: 0.25s ease;
            }
        </style>
    </head>
    <body>
        <div class="admin-warning-box">
            <div class="warning-icon">!</div>
            <h2>Bạn đang đăng nhập bằng tài khoản admin</h2>
            <p>
                Tài khoản người quản trị không thể truy cập trực tiếp vào trang người dùng.
                Vui lòng đăng xuất tài khoản quản trị trước khi sử dụng chức năng dành cho người dùng.
            </p>
            <a href="/FD-Tech/auth/logout.php" class="btn-logout">
                Đăng xuất
            </a>
        </div>
    </body>
    </html>
    <?php
    exit();
}
?>
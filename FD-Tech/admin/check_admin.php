<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user_id'])) {
    header("Location: /FD-Tech/auth/login.php");
    exit();
}
if (($_SESSION['role'] ?? '') !== 'admin') {
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Không có quyền truy cập</title>
    <style>
        body {
            margin: 0;
            height: 100vh;
            background: #b00020;
            color: white;
            font-family: Arial, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            text-align: center;
        }
        .error-box {
            background: rgba(0, 0, 0, 0.25);
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.3);
        }
        .error-box h1 {
            font-size: 48px;
            margin-bottom: 16px;
        }
        .error-box p {
            font-size: 20px;
            margin-bottom: 24px;
        }
        .error-box a {
            display: inline-block;
            padding: 12px 20px;
            background: white;
            color: #b00020;
            text-decoration: none;
            border-radius: 8px;
            font-weight: bold;
        }
        .error-box a:hover {
            opacity: 0.85;
        }.warning-icon {
                width: 78px;
                height: 78px;
                margin: 0 auto 18px;
                border-radius: 50%;
                background: #dcfce7;
                color: #a31616;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 40px;
                font-weight: bold;
            }
    </style>
</head>
<body>
    <div class="error-box">
        <div class="warning-icon">!</div>
        <h1>CẢNH BÁO</h1>
        <p>Bạn không phải Quản trị viên</p>
        <p>Bạn không có quyền truy cập khu vực này !</p>
        <a href="/FD-Tech/user/index.php">Quay về trang người dùng</a>
    </div>
</body>
</html>
<?php
    exit();
}
?>
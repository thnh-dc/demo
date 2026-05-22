<?php
// Bắt đầu session để có thể thao tác với dữ liệu đăng nhập
session_start();

// Xóa tất cả các biến session (xóa thông tin người dùng đang đăng nhập)
session_unset();

// Hủy toàn bộ session
session_destroy();

// Chuyển hướng người dùng về trang chủ
header("Location: ../user/index.php");
exit();
?>
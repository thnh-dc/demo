<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['pending_admin_login'])) {
    header("Location: login.php");
    exit();
}

$step = $_SESSION['admin_step'] ?? 1;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    if (isset($_POST['verify_step_1'])) {
        $username = trim($_POST['username']);
        $password = $_POST['password'];
        $email = trim($_POST['email']);
        $phone = trim($_POST['phone']);

        try {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? AND role = 'admin' AND status = 'active' LIMIT 1");
            $stmt->execute([$username]);
            $admin = $stmt->fetch(PDO::FETCH_ASSOC);

            if (
                $admin &&
                password_verify($password, $admin['password']) &&
                $admin['email'] === $email &&
                $admin['phone'] === $phone
            ) {
                $_SESSION['admin_step'] = 2;
                $_SESSION['auth_admin_id'] = $admin['id'];
                header("Location: admin_verify.php");
                exit();
            } else {
                $_SESSION['noti_message'] = 'Thông tin xác minh sai!';
                $_SESSION['noti_type'] = 'error';
            }
        } catch (PDOException $e) {
            $_SESSION['noti_message'] = 'Lỗi kết nối cơ sở dữ liệu!';
            $_SESSION['noti_type'] = 'error';
        }
    }

    if (isset($_POST['verify_step_2'])) {
        $code = $_POST['code'];
        $admin_id = $_SESSION['auth_admin_id'] ?? 0;

        try {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? AND role = 'admin' LIMIT 1");
            $stmt->execute([$admin_id]);
            $admin = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($admin && !empty($admin['admin_pin']) && password_verify($code, $admin['admin_pin'])) {
                $_SESSION['user_id'] = $admin['id'];
                $_SESSION['role'] = 'admin';
                $_SESSION['username'] = $admin['username'];

                $stmt_update = $pdo->prepare("UPDATE users SET last_login = CURRENT_TIMESTAMP WHERE id = ?");
                $stmt_update->execute([$admin['id']]);

                unset($_SESSION['pending_admin_login'], $_SESSION['admin_step'], $_SESSION['auth_admin_id']);
                header("Location: ../admin/admin_dashboard.php");
                exit();
            } else {
                $_SESSION['noti_message'] = 'Mã PIN bảo mật không chính xác!';
                $_SESSION['noti_type'] = 'error';
            }
        } catch (PDOException $e) {
            $_SESSION['noti_message'] = 'Lỗi hệ thống khi xác thực mã PIN!';
            $_SESSION['noti_type'] = 'error';
        }
    }
}
?>

<?php
$page_title = 'Xác minh Admin - FD Tech';
$is_admin = true;
include '../includes/auth_header.php';
?>

<div class="form-header">
    <h2 class="form-title"><?php echo ($step == 1) ? 'Xác thực thông tin' : 'Mã PIN bảo mật'; ?></h2>
</div>

<?php if ($step == 1): ?>
    <form action="" method="POST">
        <input type="hidden" name="verify_step_1" value="1">
        <div class="input-group">
            <input type="text" name="username" placeholder="Tên đăng nhập" required>
        </div>
        <div class="input-group">
            <input type="password" name="password" placeholder="Mật khẩu" required>
        </div>
        <div class="input-group">
            <input type="email" name="email" placeholder="Email" required>
        </div>
        <div class="input-group">
            <input type="password" name="phone" placeholder="Mã xác thực" required>
        </div>
        <button type="submit" class="btn-login">Xác nhận</button>
    </form>
<?php else: ?>
    <form action="" method="POST">
        <input type="hidden" name="verify_step_2" value="1">
        <div class="input-group">
            <input type="password" name="code" placeholder="Nhập mã PIN" maxlength="6" required>
        </div>
        <button type="submit" class="btn-login" style="background-color: #1a9bb8;">Vào trang quản trị</button>
    </form>
<?php endif; ?>

<div class="register-link" style="margin-top: 25px;">
    <a href="login.php" style="color: #555;">Hủy xác minh</a>
</div>

</div>
</div>
</div>

<?php include '../includes/footer.php'; ?>
<?php include '../includes/notification.php'; ?>

</body>

</html>
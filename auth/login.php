<?php
session_start();
require_once '/config/database.php';
require_once '/libs/PHPMailer/verification_code.php';

$login_step = $_SESSION['user_login_step'] ?? 1;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    if (isset($_POST['btn_step_1'])) {
        $login_input = trim($_POST['username']);
        $password = trim($_POST['password']);

        try {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? OR email = ? LIMIT 1");
            $stmt->execute([$login_input, $login_input]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($password, $user['password'])) {

                // 1. Kiểm tra nếu tài khoản bị khóa
                if (isset($user['status']) && $user['status'] === 'blocked') {
                    $_SESSION['noti_message'] = 'Tài khoản bạn bị khoá vui lòng dùng tài khoản khác!';
                    $_SESSION['noti_type'] = 'error';
                    header("Location: login.php");
                    exit();
                }

                // 2. Nếu là quyền ADMIN -> Đẩy ngay sang file riêng của Admin
                elseif (isset($user['role']) && $user['role'] === 'admin') {
                    $_SESSION['pending_admin_login'] = true;
                    $_SESSION['admin_step'] = 1;
                    $_SESSION['auth_admin_id'] = $user['id'];

                    header("Location: admin_verify.php");
                    exit();
                }

                // 3. Nếu là USER THƯỜNG -> Tiến hành gửi OTP và xử lý
                else {
                    $otp_code = rand(100000, 999999);

                    // Gọi hàm gửi email với hành động 'login'
                    if (send_system_email($user['email'], $otp_code, 'login')) {
                        // Lưu thông tin tạm thời để chuẩn bị đối chiếu ở bước 2
                        $_SESSION['temp_user_data'] = [
                            'id' => $user['id'],
                            'username' => $user['username'],
                            'role' => $user['role'],
                            'avatar' => $user['avatar']
                        ];
                        $_SESSION['temp_user_otp'] = $otp_code;
                        $_SESSION['user_login_step'] = 2; // Chuyển sang bước 2

                        $_SESSION['noti_message'] = 'Mã OTP xác thực đăng nhập đã được gửi vào Email của bạn!';
                        $_SESSION['noti_type'] = 'success';
                    } else {
                        $_SESSION['noti_message'] = 'Hệ thống không thể gửi Email OTP, vui lòng thử lại!';
                        $_SESSION['noti_type'] = 'error';
                    }
                }

            } else {
                $_SESSION['noti_message'] = 'Sai tên đăng nhập hoặc mật khẩu!';
                $_SESSION['noti_type'] = 'error';
            }
        } catch (PDOException $e) {
            $_SESSION['noti_message'] = 'Lỗi hệ thống: Không thể đăng nhập lúc này.';
            $_SESSION['noti_type'] = 'error';
        }
        header("Location: login.php");
        exit();
    }

    // BƯỚC 2: XỬ LÝ XÁC THỰC MÃ OTP (CHỈ DÀNH CHO USER)
    if (isset($_POST['btn_step_2'])) {
        $user_otp = trim($_POST['otp_input']);
        $system_otp = $_SESSION['temp_user_otp'] ?? '';
        $temp_user = $_SESSION['temp_user_data'] ?? null;

        if (!empty($system_otp) && $user_otp == $system_otp && $temp_user) {
            // Đăng nhập chính thức thành công
            $_SESSION['user_id'] = $temp_user['id'];
            $_SESSION['username'] = $temp_user['username'];
            $_SESSION['role'] = $temp_user['role'];
            $_SESSION['avatar'] = $temp_user['avatar'];

            // Xóa dọn dẹp các session rác của bước xác thực
            unset($_SESSION['temp_user_data'], $_SESSION['temp_user_otp'], $_SESSION['user_login_step']);
            header("Location: ../user/index.php");
            exit();
        } else {
            $_SESSION['noti_message'] = 'Mã xác thực OTP không chính xác!';
            $_SESSION['noti_type'] = 'error';
            header("Location: login.php");
            exit();
        }
    }
}

if (isset($_GET['action']) && $_GET['action'] == 'cancel_otp') {
    unset($_SESSION['temp_user_data'], $_SESSION['temp_user_otp'], $_SESSION['user_login_step']);
    header("Location: login.php");
    exit();
}
?>

<?php
$page_title = 'Đăng nhập - FD Tech';
include '../includes/auth_header.php';
?>

<div class="form-header">
    <h2 class="form-title">
        <?php echo ($login_step == 1) ? 'Đăng nhập' : 'Xác thực OTP'; ?>
    </h2>
</div>

<?php if ($login_step == 1): ?>
    <form action="" method="POST">
        <input type="hidden" name="btn_step_1" value="1">
        <div class="input-group">
            <input type="text" name="username" placeholder="Email hoặc Tên đăng nhập" required
                value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
        </div>

        <div class="input-group">
            <input type="password" name="password" placeholder="Mật khẩu" required>
        </div>

        <button type="submit" class="btn-login">Đăng nhập</button>

        <a href="forgot_password.php" class="forgot-pw">Quên mật khẩu</a>

        <div class="register-link" style="margin-top: 25px;">
            Bạn mới biết đến FD Tech? <a href="register.php">Đăng ký</a>
        </div>
    </form>

<?php else: ?>
    <form action="" method="POST">
        <input type="hidden" name="btn_step_2" value="1">
        <p style="font-size: 13px; color: #555; margin-bottom: 15px; text-align: center; line-height: 1.5;">
            Để bảo mật tài khoản, vui lòng điền mã OTP đã được gửi về Email của bạn.
        </p>
        <div class="input-group">
            <input type="text" name="otp_input" placeholder="Mã xác thực" maxlength="6" required autocomplete="off"
                style="text-align: center; font-size: 18px; letter-spacing: 5px; font-weight: bold;">
        </div>

        <button type="submit" class="btn-login" style="background-color: #1a9bb8; border-color: #1a9bb8;">Xác minh đăng
            nhập</button>

        <div class="register-link" style="margin-top: 25px;">
            <a href="?action=cancel_otp" style="color: #db4437;"><i class="fas fa-arrow-left"></i> Quay lại</a>
        </div>
    </form>
<?php endif; ?>

</div>
</div>
</div>
<?php include '../includes/footer.php'; ?>
<?php include '../includes/notification.php'; ?>

</body>

</html>
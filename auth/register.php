<?php
session_start();
require_once '../config/database.php';
require_once '../libs/PHPMailer/verification_code.php';

// Xác định bước hiện tại (Mặc định bước 1: Nhập thông tin đăng ký)
$register_step = $_SESSION['user_register_step'] ?? 1;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // BƯỚC 1: KIỂM TRA THÔNG TIN & GỬI OTP
    if (isset($_POST['btn_register_step_1'])) {
        $username = trim($_POST['username']);
        $email = trim($_POST['email']);
        $password = trim($_POST['password']);
        $confirm_password = trim($_POST['confirm_password']);

        // 1. Kiểm tra định dạng dữ liệu phía Server
        if (!preg_match('/^[a-zA-Z][a-zA-Z0-9]{2,19}$/', $username)) {
            $_SESSION['noti_message'] = 'Tên đăng nhập phải từ 3-20 ký tự, không chứa ký tự đặc biệt và phải bắt đầu bằng chữ cái!';
            $_SESSION['noti_type'] = 'error';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['noti_message'] = 'Định dạng Email không hợp lệ!';
            $_SESSION['noti_type'] = 'error';
        } elseif (strlen($password) < 6) {
            $_SESSION['noti_message'] = 'Mật khẩu phải có ít nhất 6 ký tự!';
            $_SESSION['noti_type'] = 'error';
        } elseif ($password !== $confirm_password) {
            $_SESSION['noti_message'] = 'Mật khẩu xác nhận không khớp! Vui lòng nhập lại.';
            $_SESSION['noti_type'] = 'error';
        } else {
            // Mã hóa mật khẩu trước khi lưu tạm
            $hashed_password = password_hash($password, PASSWORD_BCRYPT);

            try {
                // 2. Kiểm tra trùng lặp tài khoản trong Database
                $stmt = $pdo->prepare("SELECT username, email FROM users WHERE username = ? OR email = ? LIMIT 1");
                $stmt->execute([$username, $email]);
                $existing_user = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($existing_user) {
                    if ($existing_user['username'] === $username) {
                        $_SESSION['noti_message'] = 'Tên đăng nhập đã tồn tại!';
                    } else {
                        $_SESSION['noti_message'] = 'Email này đã tồn tại!';
                    }
                    $_SESSION['noti_type'] = 'error';
                } else {
                    // 3. Thông tin sạch -> Tạo OTP gửi qua Email
                    $otp_code = rand(100000, 999999);

                    if (send_system_email($email, $otp_code, 'register')) {
                        // Lưu thông tin đăng ký tạm vào Session
                        $_SESSION['temp_register_data'] = [
                            'username' => $username,
                            'email' => $email,
                            'password' => $hashed_password
                        ];
                        $_SESSION['temp_register_otp'] = $otp_code;
                        $_SESSION['user_register_step'] = 2; // Đẩy sang bước nhập mã

                        $_SESSION['noti_message'] = 'Mã OTP xác thực đăng ký đã gửi đến Email của bạn!';
                        $_SESSION['noti_type'] = 'success';
                    } else {
                        $_SESSION['noti_message'] = 'Không thể gửi Email xác thực, vui lòng kiểm tra kết nối!';
                        $_SESSION['noti_type'] = 'error';
                    }
                }
            } catch (PDOException $e) {
                $_SESSION['noti_message'] = 'Lỗi hệ thống: Không thể xử lý lúc này.';
                $_SESSION['noti_type'] = 'error';
            }
        }
        header("Location: register.php");
        exit();
    }

    // BƯỚC 2: KIỂM TRA MÃ OTP & LƯU CHÍNH THỨC
    if (isset($_POST['btn_register_step_2'])) {
        $user_otp = trim($_POST['otp_input']);
        $system_otp = $_SESSION['temp_register_otp'] ?? '';
        $reg_data = $_SESSION['temp_register_data'] ?? null;

        if (!empty($system_otp) && $user_otp == $system_otp && $reg_data) {
            try {
                // Thỏa mãn OTP -> Lưu chính thức vào database
                $stmt = $pdo->prepare("INSERT INTO users (username, password, email, role, status) VALUES (?, ?, ?, 'user', 'active')");
                $stmt->execute([$reg_data['username'], $reg_data['password'], $reg_data['email']]);

                unset($_SESSION['temp_register_data'], $_SESSION['temp_register_otp'], $_SESSION['user_register_step']);

                $_SESSION['noti_message'] = 'Đăng ký tài khoản thành công! Vui lòng đăng nhập.';
                $_SESSION['noti_type'] = 'success';
                
                header("Location: login.php");
                exit();
            } catch (PDOException $e) {
                $_SESSION['noti_message'] = 'Lỗi hệ thống khi lưu tài khoản mới!';
                $_SESSION['noti_type'] = 'error';
            }
        } else {
            $_SESSION['noti_message'] = 'Mã xác thực OTP không chính xác, vui lòng thử lại!';
            $_SESSION['noti_type'] = 'error';
            header("Location: register.php");
            exit();
        }
    }
}

if (isset($_GET['action']) && $_GET['action'] == 'cancel_reg') {
    unset($_SESSION['temp_register_data'], $_SESSION['temp_register_otp'], $_SESSION['user_register_step']);
    header("Location: register.php");
    exit();
}
?>

<?php
$page_title = 'Đăng Ký - FD Tech';
include '../includes/auth_header.php';
?>

<div class="form-header">
    <h2 class="form-title">
        <?php echo ($register_step == 1) ? 'Đăng ký' : 'Xác thực Đăng ký'; ?>
    </h2>
</div>

<?php if ($register_step == 1): ?>
    <form action="" method="POST">
        <input type="hidden" name="btn_register_step_1" value="1">
        <div class="input-group">
            <input type="text" name="username" placeholder="Tên đăng nhập (Bắt đầu bằng chữ cái)" required
                pattern="^[a-zA-Z][a-zA-Z0-9]{2,19}$"
                title="Tên đăng nhập phải từ 3-20 ký tự, bắt đầu bằng chữ cái, không chứa khoảng trắng"
                value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
        </div>

        <div class="input-group">
            <input type="email" name="email" placeholder="Email" required
                value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
        </div>

        <div class="input-group">
            <input type="password" name="password" placeholder="Mật khẩu (Tối thiểu 6 ký tự)" required minlength="6">
        </div>

        <div class="input-group">
            <input type="password" name="confirm_password" placeholder="Xác nhận mật khẩu" required minlength="6">
        </div>

        <button type="submit" class="btn-login">ĐĂNG KÝ</button>

        <div class="register-link" style="margin-top: 25px;">
            Bạn đã có tài khoản? <a href="login.php">Đăng nhập</a>
        </div>
    </form>

<?php else: ?>
    <form action="" method="POST">
        <input type="hidden" name="btn_register_step_2" value="1">
        <p style="font-size: 13px; color: #555; margin-bottom: 15px; text-align: center; line-height: 1.5;">
            Chào mừng bạn! Hệ thống đã gửi một mã OTP xác nhận tài khoản mới tới hòm thư Email bạn vừa điền.
        </p>
        <div class="input-group">
            <input type="text" name="otp_input" placeholder="Nhập mã kích hoạt 6 số" maxlength="6" required autocomplete="off" 
                   style="text-align: center; font-size: 18px; letter-spacing: 5px; font-weight: bold;">
        </div>

        <button type="submit" class="btn-login" style="background-color: #1a9bb8; border-color: #1a9bb8;">Xác nhận kích hoạt</button>

        <div class="register-link" style="margin-top: 25px;">
            <a href="?action=cancel_reg" style="color: #db4437;"><i class="fas fa-arrow-left"></i> Thay đổi thông tin</a>
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
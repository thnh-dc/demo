<?php
// FILE: forgot_password.php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../config/database.php';

// NẠP FILE HÀM GỬI EMAIL DÙNG CHUNG VỪA TẠO
require_once '../libs/PHPMailer/verification_code.php';

$referer = $_SERVER['HTTP_REFERER'] ?? '';

if (!empty($referer) && strpos($referer, 'forgot_password.php') === false) {
    unset($_SESSION['reset_step'], $_SESSION['reset_user_id'], $_SESSION['system_otp']);
}

$step = $_SESSION['reset_step'] ?? 1;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // --- BƯỚC 1: KIỂM TRA TÀI KHOẢN & GỬI EMAIL ---
    if (isset($_POST['step_1'])) {
        $user_input = trim($_POST['user_input']);
        
        $stmt = $pdo->prepare("SELECT id, email FROM users WHERE (email = ? OR phone = ?) AND role != 'admin'");
        $stmt->execute([$user_input, $user_input]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            $real_otp = rand(100000, 999999); 
            
            // GỌI HÀM DÙNG CHUNG: Truyền email, mã OTP và hành động 'forgot_password'
            if (send_system_email($user['email'], $real_otp, 'forgot_password')) {
                $_SESSION['reset_step'] = 2;
                $_SESSION['reset_user_id'] = $user['id'];
                $_SESSION['system_otp'] = $real_otp;

                $_SESSION['noti_message'] = 'Mã OTP đã được gửi thành công vào Email của bạn!';
                $_SESSION['noti_type'] = 'success';
            } else {
                $_SESSION['noti_message'] = 'Hệ thống không thể gửi Email, vui lòng kiểm tra kết nối mạng!';
                $_SESSION['noti_type'] = 'error';
            }
        } else {
            $_SESSION['noti_message'] = 'Email/SĐT không tồn tại!';
            $_SESSION['noti_type'] = 'error';
        }
        header("Location: forgot_password.php");
        exit();
    }

    // --- BƯỚC 2: KIỂM TRA MÃ OTP ---
    if (isset($_POST['step_2'])) {
        $user_otp = trim($_POST['otp_input']);
        $system_otp = $_SESSION['system_otp'] ?? '';

        if (!empty($system_otp) && $user_otp == $system_otp) {
            $_SESSION['reset_step'] = 3; 
            $_SESSION['noti_message'] = 'Xác thực OTP thành công! Vui lòng đặt mật khẩu mới.';
            $_SESSION['noti_type'] = 'success';
        } else {
            $_SESSION['noti_message'] = 'Mã OTP không chính xác, vui lòng kiểm tra lại hòm thư!';
            $_SESSION['noti_type'] = 'error';
        }
        header("Location: forgot_password.php");
        exit();
    }

    // --- BƯỚC 3: CẬP NHẬT MẬT KHẨU MỚI ---
    if (isset($_POST['step_3'])) {
        $new_pass = $_POST['new_password'];
        $conf_pass = $_POST['confirm_password'];

        if (strlen($new_pass) < 6) {
            $_SESSION['noti_message'] = 'Mật khẩu mới phải từ 6 ký tự trở lên!';
            $_SESSION['noti_type'] = 'error';
        } elseif ($new_pass === $conf_pass) {
            $hashed_password = password_hash($new_pass, PASSWORD_BCRYPT);
            $user_id = $_SESSION['reset_user_id'] ?? 0;
            
            try {
                if ($user_id > 0) {
                    $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ? AND role != 'admin'");
                    $stmt->execute([$hashed_password, $user_id]);

                    unset($_SESSION['reset_step'], $_SESSION['reset_user_id'], $_SESSION['system_otp']);

                    $_SESSION['noti_message'] = 'Đổi mật khẩu thành công! Vui lòng đăng nhập lại.';
                    $_SESSION['noti_type'] = 'success';
                    header("Location: login.php");
                    exit();
                }
            } catch (PDOException $e) {
                $_SESSION['noti_message'] = 'Lỗi kết nối cơ sở dữ liệu khi đổi mật khẩu!';
                $_SESSION['noti_type'] = 'error';
            }
        } else {
            $_SESSION['noti_message'] = 'Mật khẩu nhập lại không trùng khớp!';
            $_SESSION['noti_type'] = 'error';
        }
        header("Location: forgot_password.php");
        exit();
    }
}

if (isset($_GET['action']) && $_GET['action'] == 'cancel') {
    unset($_SESSION['reset_step'], $_SESSION['reset_user_id'], $_SESSION['system_otp']);
    header("Location: login.php");
    exit();
}
?>

<?php
$page_title = 'Quên mật khẩu - FD Tech';
include '../includes/auth_header.php';
?>

<div class="form-header">
    <h2 class="form-title">
        <?php 
            if ($step == 1) echo 'Lấy lại mật khẩu';
            elseif ($step == 2) echo 'Nhập mã xác thực';
            else echo 'Mật khẩu mới';
        ?>
    </h2>
</div>

<?php if ($step == 1): ?>
    <form action="" method="POST">
        <input type="hidden" name="step_1" value="1">
        <div class="input-group">
            <input type="text" name="user_input" placeholder="Nhập Email hoặc Số điện thoại của tài khoản" required autocomplete="off">
        </div>
        <button type="submit" class="btn-login">Gửi mã OTP qua Email</button>
    </form>

<?php elseif ($step == 2): ?>
    <form action="" method="POST">
        <input type="hidden" name="step_2" value="1">
        <p style="font-size: 13px; color: #555; margin-bottom: 15px; text-align: center; line-height: 1.5;">
            Hệ thống đã gửi một thư chứa mã OTP đến Email của bạn.<br>Vui lòng kiểm tra.
        </p>
        <div class="input-group">
            <input type="text" name="otp_input" placeholder="Nhập mã xác thực gồm 6 số" maxlength="6" required autocomplete="off" style="text-align: center; font-size: 18px; letter-spacing: 5px;">
        </div>
        <button type="submit" class="btn-login" style="background-color: #1a9bb8; border-color: #1a9bb8;">Xác minh mã OTP</button>
    </form>

<?php else: ?>
    <form action="" method="POST">
        <input type="hidden" name="step_3" value="1">
        <div class="input-group">
            <input type="password" name="new_password" placeholder="Mật khẩu mới (Tối thiểu 6 ký tự)" required minlength="6">
        </div>
        <div class="input-group">
            <input type="password" name="confirm_password" placeholder="Nhập lại mật khẩu mới" required minlength="6">
        </div>
        <button type="submit" class="btn-login" style="background-color: #1a9bb8; border-color: #1a9bb8;">Xác nhận đổi mật khẩu</button>
    </form>
<?php endif; ?>

<div class="register-link" style="margin-top: 25px;">
    <a href="?action=cancel"><i class="fas fa-arrow-left"></i>Hủy bỏ</a>
</div>

</div>
</div>
</div> 

<?php include '../includes/footer.php'; ?>
<?php include '../includes/notification.php'; ?>
</body>
</html>
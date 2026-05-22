<?php
// FILE: ../libs/mail_helper.php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Nạp các file lõi của thư viện PHPMailer (Đảm bảo đúng đường dẫn từ file này đến thư mục PHPMailer)
require_once __DIR__ . '/Exception.php';
require_once __DIR__ . '/PHPMailer.php';
require_once __DIR__ . '/SMTP.php';

/**
 * Hàm gửi mã xác thực hệ thống tự động nhận diện chức năng
 * @param string $to_email : Địa chỉ email người nhận
 * @param string $otp_code : Mã số xác thực (OTP / PIN)
 * @param string $action   : Chức năng gửi ('forgot_password', 'login', 'register', 'admin_verify')
 * @return bool            : True nếu gửi thành công, False nếu thất bại
 */
function send_system_email($to_email, $otp_code, $action) {
    $mail = new PHPMailer(true);
    try {
        // --- 1. CẤU HÌNH MÁY CHỦ SMTP GMAIL ---
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'tinhnguyenbook@gmail.com'; 
        $mail->Password   = 'bocz nffa ydsd ritk'; 
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        $mail->CharSet    = 'UTF-8';

        // --- 2. CẤU HÌNH THÔNG TIN GỬI ---
        $mail->setFrom('tinhnguyenbook@gmail.com', 'FD-Tech Shop');
        $mail->addAddress($to_email); 
        $mail->isHTML(true);

        // --- 3. ĐỊNH NGHĨA TIÊU ĐỀ & NỘI DUNG THEO TỪNG CHỨC NĂNG ---
        $subject = '[FD TECH] Mã xác thực hệ thống';
        $title_display = 'MÃ XÁC THỰC BẢO MẬT';
        $body_display = 'Hệ thống nhận được yêu cầu xác minh thông tin từ tài khoản của bạn.';
        $footer_display = 'Mã này có hiệu lực trong 5 phút. Vui lòng không chia sẻ mã này cho bất kỳ ai để bảo mật tài khoản.';

        switch ($action) {
            case 'forgot_password':
                $subject = '[FD TECH] Mã OTP lấy lại mật khẩu';
                $title_display = 'YÊU CẦU ĐỔI MẬT KHẨU';
                $body_display = 'Chào bạn, hệ thống nhận được yêu cầu lấy lại mật khẩu từ tài khoản của bạn. Sử dụng mã OTP bên dưới để tiếp tục:';
                break;

            case 'login':
                $subject = '[FD TECH] Mã OTP xác thực đăng nhập cấp 2';
                $title_display = 'XÁC THỰC ĐĂNG NHẬP';
                $body_display = 'Hệ thống phát hiện lượt đăng nhập mới yêu cầu mã OTP bảo mật. Vui lòng nhập chuỗi số sau:';
                break;

            case 'register':
                $subject = '[FD TECH] Mã xác nhận đăng ký tài khoản mới';
                $title_display = 'XÁC NHẬN ĐĂNG KÝ';
                $body_display = 'Chào mừng bạn đến với FD-Tech Shop! Hãy nhập mã số bên dưới để kích hoạt tài khoản của bạn:';
                $footer_display = 'Mã này dùng để hoàn tất quy trình đăng ký thành viên mới.';
                break;
        }

        //4. RÁP DỮ LIỆU VÀO GIAO DIỆN EMAIL HTML CHUNG
        $mail->Subject = $subject;
        $mail->Body    = "
            <div style='font-family: Arial, sans-serif; padding: 20px; border: 1px solid #ddd; max-width: 500px; margin: 0 auto; border-radius: 8px; box-shadow: 0 4px 10px rgba(0,0,0,0.05);'>
                <h2 style='color: #1a9bb8; border-bottom: 2px solid #1a9bb8; padding-bottom: 10px; margin-top: 0; font-size: 20px; text-align: center;'>{$title_display}</h2>
                <p style='color: #333; font-size: 14px; line-height: 1.6;'>{$body_display}</p>
                
                <div style='font-size: 32px; font-weight: bold; color: #db4437; background-color: #f9f9f9; padding: 15px; text-align: center; border-radius: 5px; letter-spacing: 5px; margin: 25px 0; border: 1px dashed #db4437;'>
                    {$otp_code}
                </div>
                
                <p style='color: #777; font-size: 12px; border-top: 1px solid #eee; padding-top: 10px; line-height: 1.5;'>{$footer_display}</p>
            </div>
        ";

        $mail->send();
        return true; 
    } catch (Exception $e) {
        return false; 
    }
}
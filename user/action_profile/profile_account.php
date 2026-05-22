<?php
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['form_action']) && $_POST['form_action'] == 'update_profile') {
    $full_name = trim($_POST['fullname']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $gender = $_POST['gender'];
    $dob = !empty($_POST['dob']) ? $_POST['dob'] : null;
    $address = trim($_POST['address']);

    $avatar_name = $user['avatar'] ?? '';
    $upload_error = '';

    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png'];
        $filename = $_FILES['avatar']['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        if (!in_array($ext, $allowed)) {
            $upload_error = 'Chỉ chấp nhận JPG, JPEG hoặc PNG!';
        } elseif ($_FILES['avatar']['size'] > 10048576) {
            $upload_error = 'Dung lượng ảnh quá 10MB!';
        } else {
            $new_filename = $user_id . "_" . time() . "." . $ext;
            $upload_dir = "../upload/avatar_user/";
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

            if (move_uploaded_file($_FILES['avatar']['tmp_name'], $upload_dir . $new_filename)) {
                $avatar_name = $new_filename;
                if (!empty($user['avatar']) && file_exists($upload_dir . $user['avatar'])) {
                    unlink($upload_dir . $user['avatar']);
                }
            } else {
                $upload_error = 'Lỗi lưu file ảnh.';
            }
        }
    }

    if ($upload_error) {
        $_SESSION['noti_message'] = $upload_error;
        $_SESSION['noti_type'] = 'error';
    } else {
        try {
            $stmt = $pdo->prepare("UPDATE users SET full_name = ?, email = ?, phone = ?, gender = ?, date_of_birth = ?, address = ?, avatar = ? WHERE id = ?");
            $stmt->execute([$full_name, $email, $phone, $gender, $dob, $address, $avatar_name, $user_id]);
            $_SESSION['noti_message'] = 'Cập nhật hồ sơ thành công!';
            $_SESSION['noti_type'] = 'success';
        } catch (PDOException $e) {
            $_SESSION['noti_message'] = 'Lỗi cập nhật Database!';
            $_SESSION['noti_type'] = 'error';
        }
    }
    header("Location: profile.php?action=account");
    exit();
}
?>

<div class="profile-header">
    <h2>Hồ sơ của tôi</h2>
    <p>Quản lý thông tin hồ sơ để bảo mật tài khoản</p>
</div>
<form action="" method="POST" enctype="multipart/form-data" class="profile-body-split">
    <input type="hidden" name="form_action" value="update_profile">
    <div class="profile-form-area">
        <div class="profile-form">
            <div class="form-group"><label>Tên đăng nhập</label><input type="text" value="<?php echo htmlspecialchars($user['username']); ?>" readonly style="background: #f9f9f9;"></div>
            <div class="form-group"><label>Họ và Tên</label><input type="text" name="fullname" value="<?php echo htmlspecialchars($user['full_name'] ?? ''); ?>"></div>
            <div class="form-group"><label>Email</label><input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required></div>
            <div class="form-group"><label>Số điện thoại</label><input type="text" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>"></div>
            <div class="form-group">
                <label>Giới tính</label>
                <select name="gender">
                    <option value="male" <?php echo ($user['gender'] == 'male') ? 'selected' : ''; ?>>Nam</option>
                    <option value="female" <?php echo ($user['gender'] == 'female') ? 'selected' : ''; ?>>Nữ</option>
                    <option value="other" <?php echo ($user['gender'] == 'other') ? 'selected' : ''; ?>>Khác</option>
                </select>
            </div>
            <div class="form-group"><label>Ngày sinh</label><input type="date" name="dob" value="<?php echo !empty($user['date_of_birth']) ? $user['date_of_birth'] : ''; ?>"></div>
            <div class="form-group"><label>Địa chỉ</label><input type="text" name="address" value="<?php echo htmlspecialchars($user['address'] ?? ''); ?>"></div>
            <div class="form-group"><button type="submit" class="btn-save">Lưu Thay Đổi</button></div>
        </div>
    </div>
    <div class="profile-avatar-area">
        <div class="avatar-preview-box"><img src="<?php echo $avatar_url; ?>" id="image-preview" alt="Avatar"></div>
        <input type="file" id="file-upload" name="avatar" accept=".jpg, .jpeg, .png" style="display: none;" onchange="previewImage(event)">
        <button type="button" class="btn-upload" onclick="document.getElementById('file-upload').click()">Chọn Ảnh</button>
    </div>
</form>

<?php include '../includes/notification.php'; ?>
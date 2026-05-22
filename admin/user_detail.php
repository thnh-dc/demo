<?php
session_start();
require_once '../config/database.php';
require_once __DIR__ . '/check_admin.php';

$user_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$stmt = $pdo->prepare("
    SELECT 
        id, username, full_name, gender, date_of_birth, email, phone, address, avatar, role, status, created_at, last_login
    FROM users
    WHERE id = ? 
    AND role = 'user'
    LIMIT 1
");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$user) {
    die("Không tìm thấy người dùng.");
}
function getAvatarSrc($avatar)
{
    if (empty($avatar)) {
        return "../assets/images/logo-fd.jpg";
    }
    if (filter_var($avatar, FILTER_VALIDATE_URL)) {
        return $avatar;
    }
    if (strpos($avatar, 'upload/avatar_user/') === 0) {
        return "../" . $avatar;
    }
    return "../upload/avatar_user/" . $avatar;
}
$avatarSrc = getAvatarSrc($user['avatar'] ?? '');
?>
<?php
$page_title = 'Chi tiết người dùng';
$page_icon = 'fa-solid fa-users';
$custom_css = '
    <link rel="stylesheet" href="/FD-Tech/assets/css/style_user_management.css">
    <link rel="stylesheet" href="/FD-Tech/assets/css/style_notification.css">
';
include 'includes/header.php';
include '../includes/notification.php';
?>
<div class="dashboard-container">
    <div class="user-detail-card">
        <div class="user-detail-header">
            <img src="<?= htmlspecialchars($avatarSrc) ?>" class="user-detail-avatar" alt="Avatar" onerror="this.src='../assets/images/logo-fd.jpg'">
            <div>
                <h2><?= htmlspecialchars($user['username']) ?></h2>
                <?php if ($user['status'] === 'active'): ?>
                    <span class="status-badge status-active">Đang hoạt động</span>
                <?php else: ?>
                    <span class="status-badge status-blocked">Đã khóa</span>
                <?php endif; ?>
            </div>
        </div>
        <div class="user-detail-grid">
            <div class="detail-item">
                <span>Họ và tên</span>
                <strong><?= htmlspecialchars($user['full_name'] ?? 'Chưa cập nhật') ?></strong>
            </div>
            <div class="detail-item">
                <span>Email</span>
                <strong><?= htmlspecialchars($user['email']) ?></strong>
            </div>
            <div class="detail-item">
                <span>Số điện thoại</span>
                <strong>
                    <?= !empty($user['phone']) ? htmlspecialchars($user['phone']) : 'Chưa cập nhật' ?>
                </strong>
            </div>
            <div class="detail-item">
                <span>Giới tính</span>
                <strong>
                    <?php
                        if ($user['gender'] === 'male') {
                            echo 'Nam';
                        } elseif ($user['gender'] === 'female') {
                            echo 'Nữ';
                        } else {
                            echo 'Khác/Chưa cập nhật';
                        }
                    ?>
                </strong>
            </div>
            <div class="detail-item">
                <span>Ngày sinh</span>
                <strong>
                    <?= !empty($user['date_of_birth']) ? date('d/m/Y', strtotime($user['date_of_birth'])) : 'Chưa cập nhật' ?>
                </strong>
            </div>
            <div class="detail-item">
                <span>Vai trò</span>
                <strong>Người dùng</strong>
            </div>
            <div class="detail-item full-width">
                <span>Địa chỉ</span>
                <strong>
                    <?= !empty($user['address']) ? htmlspecialchars($user['address']) : 'Chưa cập nhật' ?>
                </strong>
            </div>
            <div class="detail-item">
                <span>Ngày tạo tài khoản</span>
                <strong><?= date('d/m/Y H:i', strtotime($user['created_at'])) ?></strong>
            </div>
            <div class="detail-item">
                <span>Lần đăng nhập cuối</span>
                <strong>
                    <?= !empty($user['last_login']) ? date('d/m/Y H:i', strtotime($user['last_login'])) : 'Chưa có' ?>
                </strong>
            </div>
        </div>
        <div class="detail-actions">
            <a href="list_users.php" class="btn btn-secondary">
                <i class="fa-solid fa-arrow-left"></i>
                Quay lại
            </a>
            <form method="POST" action="action_user/update_user_status.php">
                <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                <input type="hidden" name="status" value="<?= $user['status'] === 'active' ? 'blocked' : 'active' ?>">
                <input type="hidden" name="redirect" value="../user_detail.php?id=<?= $user['id'] ?>">
                <?php if ($user['status'] === 'active'): ?>
                    <button type="submit" class="btn btn-danger">
                        <i class="fa-solid fa-lock"></i>
                        Khóa tài khoản
                    </button>
                <?php else: ?>
                    <button type="submit" class="btn btn-primary">
                        <i class="fa-solid fa-lock-open"></i>
                        Mở khóa tài khoản
                    </button>
                <?php endif; ?>
            </form>
        </div>
    </div>
</div>
</main>
</div>
<script src="../assets/js/script_dashboard.js"></script>
</body>
</html>
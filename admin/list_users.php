<?php
session_start();
require_once '../config/database.php';
require_once __DIR__ . '/check_admin.php';

$search = trim($_GET['search'] ?? '');
$status = $_GET['status'] ?? '';

$sql = "
    SELECT 
        id, username, full_name, email, phone, address, avatar, role, status, created_at, last_login
    FROM users
    WHERE role = 'user'
";
$params = [];
if ($search !== '') {
    $sql .= " AND (
        username LIKE ? 
        OR full_name LIKE ? 
        OR email LIKE ? 
        OR phone LIKE ?
    )";
    $keyword = "%$search%";
    $params[] = $keyword;
    $params[] = $keyword;
    $params[] = $keyword;
    $params[] = $keyword;
}
if ($status !== '') {
    $sql .= " AND status = ?";
    $params[] = $status;
}

$sql .= " ORDER BY id DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
?>
<?php
$page_title = 'Quản lí người dùng';
$page_icon = 'fa-solid fa-users';
$custom_css = '
    <link rel="stylesheet" href="/FD-Tech/assets/css/style_user_management.css">
    <link rel="stylesheet" href="/FD-Tech/assets/css/style_notification.css">
';
include 'includes/header.php';
include '../includes/notification.php';
?>
<div class="dashboard-container">
    <div class="user-card">
        <div class="user-card-header">
            <form method="GET" class="user-filter-form">
                <input type="text" name="search" class="form-control" placeholder="Tìm tên, email, số điện thoại..." value="<?= htmlspecialchars($search) ?>">
                <select name="status" class="form-control">
                    <option value="">Tất cả trạng thái</option>
                    <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>
                        Đang hoạt động
                    </option>
                    <option value="blocked" <?= $status === 'blocked' ? 'selected' : '' ?>>
                        Đã khóa
                    </option>
                </select>
                <button type="submit" class="btn btn-primary">
                    <i class="fa-solid fa-search"></i>
                    Tìm kiếm
                </button>
            </form>
        </div>
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Avatar</th>
                    <th>Tài khoản</th>
                    <th>Email</th>
                    <th>Số điện thoại</th>
                    <th>Trạng thái</th>
                    <th>Ngày tạo</th>
                    <th>Thao tác</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($users)): ?>
                    <?php foreach ($users as $user): ?>
                        <?php $avatarSrc = getAvatarSrc($user['avatar'] ?? ''); ?>
                        <tr>
                            <td>
                                <img 
                                    src="<?= htmlspecialchars($avatarSrc) ?>" 
                                    class="user-avatar"
                                    alt="Avatar"
                                    onerror="this.src='../assets/images/logo-fd.jpg'"
                                >
                            </td>
                            <td>
                                <strong><?= htmlspecialchars($user['username']) ?></strong>
                                <br>
                                <span class="text-muted">
                                    <?= htmlspecialchars($user['full_name'] ?? 'Chưa cập nhật') ?>
                                </span>
                            </td>
                            <td><?= htmlspecialchars($user['email']) ?></td>
                            <td>
                                <?= !empty($user['phone']) ? htmlspecialchars($user['phone']) : 'Chưa cập nhật' ?>
                            </td>
                            <td>
                                <?php if ($user['status'] === 'active'): ?>
                                    <span class="status-badge status-active">Đang hoạt động</span>
                                <?php else: ?>
                                    <span class="status-badge status-blocked">Đã khóa</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?= date('d/m/Y H:i', strtotime($user['created_at'])) ?>
                            </td>
                            <td>
                                <div class="action-group">
                                    <a href="user_detail.php?id=<?= $user['id'] ?>" class="btn-action btn-view" title="Xem chi tiết">
                                        <i class="fa-solid fa-eye"></i>
                                    </a>
                                    <form method="POST" action="action_user/update_user_status.php">
                                        <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                        <input type="hidden" name="status" value="<?= $user['status'] === 'active' ? 'blocked' : 'active' ?>" >
                                        <input type="hidden" name="redirect" value="../list_users.php">
                                        <?php if ($user['status'] === 'active'): ?>
                                            <button type="submit" class="btn-action btn-lock" title="Khóa tài khoản">
                                                <i class="fa-solid fa-lock"></i>
                                            </button>
                                        <?php else: ?>
                                            <button type="submit" class="btn-action btn-unlock" title="Mở khóa tài khoản">
                                                <i class="fa-solid fa-lock-open"></i>
                                            </button>
                                        <?php endif; ?>
                                    </form>

                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7">
                            <div class="empty-box">
                                <i class="fa-solid fa-users"></i>
                                <h3>Không có người dùng phù hợp</h3>
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
</main>
</div>
<script src="../assets/js/script_dashboard.js"></script>
</body>
</html>
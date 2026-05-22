<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}
$user_id = $_SESSION['user_id'];

try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Lỗi truy xuất dữ liệu người dùng!");
}

$has_custom_avatar = !empty($user['avatar']) && file_exists("../upload/avatar_user/" . $user['avatar']);
if ($has_custom_avatar) {
    $avatar_url = "../upload/avatar_user/" . $user['avatar'];
} else {
    $initials = mb_strtoupper(mb_substr($user['username'], 0, 2, 'UTF-8'), 'UTF-8');
    $avatar_url = "https://ui-avatars.com/api/?name=" . urlencode($initials) . "&background=random&color=fff&size=128";
}

$allowed_actions = ['account', 'password', 'orders', 'notifications', 'history_bought'];
$action = isset($_GET['action']) && in_array($_GET['action'], $allowed_actions) ? $_GET['action'] : 'account';

$file_path = "action_profile/profile_{$action}.php";

$action_content = "";

if (file_exists($file_path)) {
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        include $file_path;
    }
}

?>

<?php include '../includes/header.php'; ?>
<link rel="stylesheet" href="../assets/css/style_profile.css">

<div class="profile-wrapper">
    <div class="profile-container">

        <div class="profile-sidebar">
            <div class="user-brief">
                <img src="<?php echo $avatar_url; ?>" id="sidebar-avatar" alt="Avatar">
                <div>
                    <h3><?php echo htmlspecialchars($user['username']); ?></h3>
                    <p style="font-size: 12px; color: #777;"><i class="fas fa-pencil-alt"></i> Sửa hồ sơ</p>
                </div>
            </div>
            <ul class="profile-menu">
                <li><a href="?action=account" class="menu-link <?php echo $action == 'account' ? 'active' : ''; ?>"><i
                            class="far fa-user"></i> Tài khoản của tôi</a></li>
                <li><a href="?action=password" class="menu-link <?php echo $action == 'password' ? 'active' : ''; ?>"><i
                            class="fas fa-lock"></i> Đổi mật khẩu</a></li>
                <li><a href="?action=orders" class="menu-link <?php echo $action == 'orders' ? 'active' : ''; ?>"><i
                            class="fas fa-clipboard-list"></i> Đơn mua</a></li>
                <li><a href="?action=notifications"
                        class="menu-link <?php echo $action == 'notifications' ? 'active' : ''; ?>"><i
                            class="fas fa-bell"></i> Thông báo</a></li>
                <li><a href="?action=history_bought" class="menu-link <?php echo $action == 'history_bought' ? 'active' : ''; ?>"><i
                            class="fas fa-chart-bar"></i> Thống kê</a></li>

                <li style="margin-top: 20px; border-top: 1px solid #eee; padding-top: 10px;">
                    <a href="../auth/logout.php" style="color: #DB4437;"><i class="fas fa-sign-out-alt"></i> Đăng
                        xuất</a>
                </li>
            </ul>
        </div>

        <div class="profile-content">
            <?php
            if (file_exists($file_path)) {
                if ($_SERVER['REQUEST_METHOD'] != 'POST') {
                    include $file_path;
                }
            } else {
                echo "<div class='profile-header'><h2>Tính năng này đang được phát triển...</h2></div>";
            }
            ?>
        </div>

    </div>
</div>

<script>
    <?php if (isset($_SESSION['flash_msg'])): ?>
        alert('<?php echo $_SESSION['flash_msg']; ?>');
        <?php unset($_SESSION['flash_msg']); ?>
    <?php endif; ?>

    function previewImage(event) {
        var reader = new FileReader();
        reader.onload = function () {
            if (document.getElementById('image-preview')) document.getElementById('image-preview').src = reader.result;
            if (document.getElementById('sidebar-avatar')) document.getElementById('sidebar-avatar').src = reader.result;
        };
        if (event.target.files[0]) reader.readAsDataURL(event.target.files[0]);
    }
</script>
<?php include '../includes/ai_assistant_widget.php'; ?>
<?php include '../includes/footer.php'; ?>
<?php
session_start();
require_once '../../config/database.php';
function isAjaxRequest()
{
    return isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}
function requireLoginForProductAction()
{
    if (!isset($_SESSION['user_id'])) {
        $_SESSION['noti_message'] = 'Oppss, bạn chưa đăng nhập rồi!';
        $_SESSION['noti_type'] = 'error';
        if (isAjaxRequest()) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => false,
                'message' => 'Oppss, bạn chưa đăng nhập rồi!',
                'redirect' => '/auth/login.php'
            ]);
            exit;
        }
        header("Location:/auth/login.php");
        exit;
    }
}
requireLoginForProductAction();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../index.php");
    exit;}
$user_id = $_SESSION['user_id'];
$product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
$quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;
$action_type = $_POST['action_type'] ?? 'add_to_cart';
if ($product_id <= 0) {
    if (isAjaxRequest()) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => false,
            'message' => 'Sản phẩm không hợp lệ.'
        ]);
        exit;
    }
    $_SESSION['noti_message'] = 'Sản phẩm không hợp lệ.';
    $_SESSION['noti_type'] = 'error';
    header("Location: ../index.php");
    exit;
}
if ($quantity < 1) {
    $quantity = 1;}
$stmtProduct = $pdo->prepare("
    SELECT id, stock_quantity 
    FROM products 
    WHERE id = ?
    LIMIT 1
");
$stmtProduct->execute([$product_id]);
$product = $stmtProduct->fetch(PDO::FETCH_ASSOC);
if (!$product) {
    if (isAjaxRequest()) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => false,
            'message' => 'Sản phẩm không tồn tại.'
        ]);
        exit;
    }
    $_SESSION['noti_message'] = 'Sản phẩm không tồn tại.';
    $_SESSION['noti_type'] = 'error';
    header("Location: ../index.php");
    exit;}
if ($product['stock_quantity'] <= 0) {
    if (isAjaxRequest()) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => false,
            'message' => 'Sản phẩm đã hết hàng.']);
        exit;
    }
    $_SESSION['noti_message'] = 'Sản phẩm đã hết hàng.';
    $_SESSION['noti_type'] = 'error';
    header("Location: ../product_detail.php?id=" . $product_id);
    exit;}
if ($quantity > $product['stock_quantity']) {
    $quantity = $product['stock_quantity'];}
$stmt = $pdo->prepare("
    SELECT id, quantity 
    FROM cart_items 
    WHERE user_id = ? 
    AND product_id = ?
");
$stmt->execute([$user_id, $product_id]);
$item = $stmt->fetch(PDO::FETCH_ASSOC);
$cart_item_id = 0;
if ($item) {
    $new_qty = $item['quantity'] + $quantity;
    if ($new_qty > $product['stock_quantity']) {
        $new_qty = $product['stock_quantity'];
    }
    $update = $pdo->prepare("
        UPDATE cart_items 
        SET quantity = ? 
        WHERE id = ? 
        AND user_id = ?
    ");
    $update->execute([$new_qty, $item['id'], $user_id]);
    $cart_item_id = $item['id'];} 
    else {
    $insert = $pdo->prepare("
        INSERT INTO cart_items(user_id, product_id, quantity) 
        VALUES (?, ?, ?)
    ");
    $insert->execute([$user_id, $product_id, $quantity]);
    $cart_item_id = $pdo->lastInsertId();}
if ($action_type === 'add_to_cart') {
    if (isAjaxRequest()) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => true,
            'message' => 'Đã thêm sản phẩm vào giỏ hàng thành công!'
        ]);
        exit;
    }
    $_SESSION['noti_message'] = 'Đã thêm sản phẩm vào giỏ hàng thành công!';
    $_SESSION['noti_type'] = 'success';

    header("Location: ../cart.php");
    exit;}
if ($action_type === 'buy_now') {
    echo "
        <form id='redirectForm' action='../checkout.php' method='POST'>
            <input type='hidden' name='selected_items' value='{$cart_item_id}'>
        </form>
        <script>
            document.getElementById('redirectForm').submit();
        </script>
    ";
    exit;}
header("Location: ../product_detail.php?id=" . $product_id);
exit;
?>
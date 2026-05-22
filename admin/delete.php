<?php
include '../config/database.php';
require_once __DIR__ . '/check_admin.php';

$id = $_GET['id'] ?? 0;

if($id){

    $stmt = $pdo->prepare("DELETE FROM products WHERE id=?");

    $stmt->execute([$id]);
}

header("Location: list_products.php?msg=Đã xóa thành công");
exit;
?>
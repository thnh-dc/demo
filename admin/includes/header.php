<?php
$page_title = $page_title ?? 'Admin';
$page_icon = $page_icon ?? 'fa-solid fa-gauge';
$custom_css = $custom_css ?? '';
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($page_title) ?> - FD Tech</title>

    <link rel="stylesheet" href="../assets/css/style_chung.css">
    <link rel="stylesheet" href="../assets/css/style_dashboard.css">
    <?= $custom_css ?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body>
<div class="dashboard-layout">
    <?php include __DIR__ . '/sidebar.php'; ?>
    <main class="main-content">
        <div class="top-navbar">
            <h1 class="page-title">
                <i class="<?= htmlspecialchars($page_icon) ?>"></i>
                <?= htmlspecialchars($page_title) ?>
            </h1>
            <div class="admin-profile">
                <span class="text-muted">Xin chào, <b>Admin</b></span>
                <img src="../assets/images/logo-fd.jpg" alt="Admin">
            </div>
        </div>
<?php
$adminTitle = isset($title) && is_string($title)
    ? $title
    : 'Quản trị Flora';

$adminName = (string) (
    $_SESSION['full_name']
    ?? $_SESSION['user']['full_name']
    ?? ''
);
?>
<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title><?= e($adminTitle) ?></title>

    <link rel="stylesheet" href="/flora/assets/css/common.css">
    <link rel="stylesheet" href="/flora/assets/css/admin.css">
</head>

<body class="flora-admin">
    <header class="admin-topbar">
        <a class="admin-brand" href="/flora/index.php?route=admin_dashboard">
            FLORA <span>QUẢN TRỊ</span>
        </a>

        <div class="admin-topbar-actions">
            <?php if ($adminName !== ''): ?>
                <span><?= e($adminName) ?></span>
            <?php endif; ?>

            <a href="/flora/index.php?route=home">
                Xem cửa hàng
            </a>
        </div>
    </header>

    <div class="admin-shell">
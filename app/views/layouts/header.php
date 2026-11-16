<?php
$headerIsAdmin = (Auth::user()['role'] ?? '') === 'admin';
$headerSearch = isset($search) && is_string($search)
    ? $search
    : '';

$headerTitle = isset($title) && is_string($title)
    ? $title
    : (
        isset($detail['name'])
            ? $detail['name'] . ' | Flora'
            : 'Flora | Thời trang nam nữ'
    );

$headerUserId = (int) (
    $_SESSION['user_id']
    ?? $_SESSION['user']['id']
    ?? 0
);

$headerFullName = (string) (
    $_SESSION['full_name']
    ?? $_SESSION['user']['full_name']
    ?? ''
);
?>
<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title><?= flora_e($headerTitle) ?></title>
    <link rel="stylesheet" href="/flora/assets/css/common.css">
    <link rel="stylesheet" href="/flora/assets/css/customer.css">
    <link rel="stylesheet" href="/flora/assets/css/storefront.css">
    <link rel="stylesheet" href="/flora/assets/css/auth-footer.css">
</head>
<body>
    <div class="topbar"><div class="c">
        <span>Giao hàng toàn quốc</span><span>Hỗ trợ khách hàng</span>
        <?php if ($headerUserId > 0 && $headerFullName !== ''): ?>
            <span>Xin chào, <?= flora_e($headerFullName) ?></span>
        <?php endif; ?>
    </div></div>
    <header>
        <div class="c">
            <a class="logo flora-logo" href="/flora/index.php?route=home">
                <span class="flora-logo-image">
                    <img
                        src="/flora/assets/images/flora-logo.png"
                        alt="Logo Flora"
                        width="70"
                        height="70"
                    >
                </span>

                <span class="flora-brand-name">FLORA</span>
            </a>
            <nav aria-label="Điều hướng chính">
                <a href="index.php?route=home#home">Trang chủ</a>
                <a href="index.php?route=products#products">Tất cả</a>
                <a href="index.php?route=products&amp;category=nam#products">Nam</a>
                <a href="index.php?route=products&amp;category=nu#products">Nữ</a>
            </nav>
            <div class="sp">
                <form class="search-form" action="index.php" method="get" role="search">
                    <input type="hidden" name="route" value="products">
                    <input class="fc" name="q" value="<?= flora_e($headerSearch) ?>" placeholder="Tìm sản phẩm" aria-label="Tìm sản phẩm">
                    <button class="search-submit" type="submit" aria-label="Tìm kiếm">
                        <svg aria-hidden="true" focusable="false" viewBox="0 0 24 24">
                            <circle cx="10.8" cy="10.8" r="6.8"></circle>
                            <path d="m16 16 4.5 4.5"></path>
                        </svg>
                    </button>
                </form>
                <?php if (!$headerIsAdmin): ?>
                <a class="cart-link" href="index.php?route=cart" aria-label="Giỏ hàng">
                    <svg aria-hidden="true" focusable="false" viewBox="0 0 24 24">
                        <path d="M3 4h2l2.1 10.1a2 2 0 0 0 2 1.6h8.7a2 2 0 0 0 1.9-1.5L21 8H6"></path>
                        <circle cx="10" cy="19" r="1"></circle>
                        <circle cx="18" cy="19" r="1"></circle>
                    </svg>
                    <span>Giỏ</span>

                    <?php if (isset($cartCount)): ?>
                        <span class="cnt" id="cart-count">
                            <?= max(0, (int) $cartCount) ?>
                        </span>
                    <?php endif; ?>
                </a>

                <?php endif; ?>
                <a
                    class="flora-account-icon"
                    href="/flora/index.php?route=login"
                    aria-label="Tài khoản"
                    title="Tài khoản"
                >
                    <svg aria-hidden="true" viewBox="0 0 24 24">
                        <circle cx="12" cy="8" r="3.5"></circle>
                        <path d="M5 20a7 7 0 0 1 14 0"></path>
                    </svg>
                </a>
            </div>
        </div>
    </header>

    <main>
        <?php require APP_ROOT . '/app/views/layouts/messages.php'; ?>
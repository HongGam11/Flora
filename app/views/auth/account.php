<?php
$accountIsAdmin = $accountUser['role'] === 'admin';
$accountRoles = [
    'customer' => 'Khách hàng',
    'staff' => 'Nhân viên',
    'admin' => 'Quản trị viên',
];

$accountRoleLabel = $accountRoles[$accountUser['role']]
    ?? 'Tài khoản';
?>

<section class="flora-account-page">
    <div class="flora-account-grid">
        <div class="flora-account-intro">
            <p class="flora-account-eyebrow">
                Không gian cá nhân
            </p>

            <h1>
                Xin chào,
                <span><?= e($accountUser['full_name']) ?>.</span>
            </h1>

            <a
                class="flora-account-shopping"
                href="/flora/index.php?route=<?= $accountIsAdmin ? 'admin_dashboard' : 'products' ?>"
            >
                <?= $accountIsAdmin ? 'Vào trang quản trị →' : 'Tiếp tục mua sắm →' ?>
            </a>
        </div>

        <div class="flora-account-details">
            <h2>Thông tin tài khoản</h2>

            <dl>
                <div class="flora-account-row">
                    <dt>Họ và tên</dt>
                    <dd><?= e($accountUser['full_name']) ?></dd>
                </div>

                <div class="flora-account-row">
                    <dt>Email</dt>
                    <dd><?= e($accountUser['email']) ?></dd>
                </div>

                <div class="flora-account-row">
                    <dt>Vai trò</dt>
                    <dd><?= e($accountRoleLabel) ?></dd>
                </div>
            </dl>

            <?php if (!$accountIsAdmin): ?>
                <p><a class="btn" href="/flora/index.php?route=profile">Sửa hồ sơ</a></p>
                <p><a class="btn" href="/flora/index.php?route=orders">Đơn hàng của tôi</a></p>
            <?php endif; ?>
            <p><a class="btn" href="/flora/index.php?route=change_password">Đổi mật khẩu</a></p>
<form
                action="/flora/index.php?route=logout"
                method="post"
            >
                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= e(Csrf::token()) ?>"
                >

                <button class="flora-account-logout" type="submit">
                    Đăng xuất
                </button>
            </form>
        </div>
    </div>
</section>
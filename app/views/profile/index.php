<section class="tv1-profile">
    <p class="tv1-eyebrow">TÀI KHOẢN FLORA</p>
    <h1>Hồ sơ cá nhân</h1>
    <nav class="tv1-links" aria-label="Tài khoản"><a href="/flora/index.php?route=login">Tổng quan</a><a href="/flora/index.php?route=orders">Đơn hàng của tôi</a><a href="/flora/index.php?route=change_password">Đổi mật khẩu</a></nav>
    <p>Cập nhật thông tin để thuận tiện cho lần mua tiếp theo. Địa chỉ trên đơn đã đặt được giữ nguyên.</p>
    <?php if ($error !== ''): ?><p role="alert" class="tv1-error"><?= e($error) ?></p><?php endif; ?>
    <form method="post" action="/flora/index.php?route=profile" class="tv1-form">
        <input type="hidden" name="csrf_token" value="<?= e(Csrf::token()) ?>">
        <label>Họ và tên <input name="full_name" value="<?= e($profile['full_name']) ?>" maxlength="100" required autocomplete="name"></label>
        <label>Email đăng nhập <input type="email" value="<?= e($profile['email']) ?>" readonly><small>Email đăng nhập được giữ cố định.</small></label>
        <label>Số điện thoại <input type="tel" name="phone" value="<?= e($profile['phone'] ?? '') ?>" maxlength="10" pattern="0[0-9]{9}" autocomplete="tel" placeholder="Ví dụ: 0912345678"></label>
        <label>Địa chỉ mặc định <textarea name="address" maxlength="500" rows="3" autocomplete="street-address"><?= e($profile['address'] ?? '') ?></textarea></label>
        <button type="submit" class="btn">Lưu thay đổi</button>
    </form>
</section>

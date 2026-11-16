<section class="tv1-profile">
    <p class="tv1-eyebrow">BẢO MẬT TÀI KHOẢN</p><h1>Đổi mật khẩu</h1>
    <nav class="tv1-links"><a href="/flora/index.php?route=profile">← Quay lại hồ sơ</a></nav>
    <p>Dùng mật khẩu ít nhất 8 ký tự, tối đa 72 byte. Sau khi đổi thành công, bạn cần đăng nhập lại trên các thiết bị.</p>
    <?php if ($error !== ''): ?><p role="alert" class="tv1-error"><?= e($error) ?></p><?php endif; ?>
    <form method="post" action="/flora/index.php?route=change_password" class="tv1-form">
        <input type="hidden" name="csrf_token" value="<?= e(Csrf::token()) ?>">
        <label>Mật khẩu hiện tại <input type="password" name="current_password" required maxlength="1024" autocomplete="current-password"></label>
        <label>Mật khẩu mới <input type="password" name="new_password" required minlength="8" maxlength="72" autocomplete="new-password"></label>
        <label>Nhập lại mật khẩu mới <input type="password" name="password_confirmation" required minlength="8" maxlength="72" autocomplete="new-password"></label>
        <button type="submit" class="btn">Đổi mật khẩu</button>
    </form>
</section>

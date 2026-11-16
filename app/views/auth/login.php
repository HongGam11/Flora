<?php
$errors = isset($errors) && is_array($errors) ? $errors : [];
$old = isset($old) && is_array($old) ? $old : [];
?>
<section class="flora-auth-page flora-auth-compact">
    <div class="flora-auth-panel">
        <p class="flora-auth-eyebrow">Chào mừng trở lại</p>
        <h1>Đăng nhập.</h1>
        <p class="flora-auth-description">Đăng nhập để tiếp tục mua sắm cùng Flora.</p>
        <?php if (!empty($errors['general'])): ?>
            <p class="flora-auth-error flora-auth-general" role="alert"><?= e($errors['general']) ?></p>
        <?php endif; ?>
        <form action="/flora/index.php?route=login" method="post">
            <input type="hidden" name="csrf_token" value="<?= e(Csrf::token()) ?>">
            <div class="flora-auth-row">
                <div class="flora-floating-field">
                    <input placeholder=" " id="auth-email" name="email" type="email" autocomplete="email" maxlength="255" value="<?= e($old['email'] ?? '') ?>" required
                    aria-invalid="<?= isset($errors['email']) ? 'true' : 'false' ?>" aria-describedby="auth-email-error">
                    <label for="auth-email">Email</label>
                </div>
                <span id="auth-email-error" class="flora-auth-error"><?php if (isset($errors['email'])): ?><?= e($errors['email']) ?><?php endif; ?></span>
            </div>
            <div class="flora-auth-row">
                <div class="flora-floating-field">
                    <input placeholder=" " id="auth-password" name="password" type="password" autocomplete="current-password" required
                    aria-invalid="<?= isset($errors['password']) ? 'true' : 'false' ?>" aria-describedby="auth-password-error">
                    <label for="auth-password">Mật khẩu</label>
                </div>
                <span id="auth-password-error" class="flora-auth-error"><?php if (isset($errors['password'])): ?><?= e($errors['password']) ?><?php endif; ?></span>
            </div>
            <button class="flora-auth-button" type="submit">Đăng nhập</button>
        </form>
        <p class="flora-auth-switch">Chưa có tài khoản? <a href="/flora/index.php?route=register">Đăng ký</a></p>
    </div>
</section>

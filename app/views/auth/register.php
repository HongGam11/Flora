<?php
$errors = isset($errors) && is_array($errors) ? $errors : [];
$old = isset($old) && is_array($old) ? $old : [];
?>
<section class="flora-auth-page flora-auth-compact">
    <div class="flora-auth-panel">
        <p class="flora-auth-eyebrow">Tài khoản khách hàng</p>
        <h1>Đăng ký.</h1>
        <p class="flora-auth-description">Tạo tài khoản để mua sắm cùng Flora. Mật khẩu cần tối thiểu 8 ký tự.</p>
        <?php if (!empty($errors['general'])): ?>
            <p class="flora-auth-error flora-auth-general" role="alert"><?= e($errors['general']) ?></p>
        <?php endif; ?>
        <form action="/flora/index.php?route=register" method="post">
            <input type="hidden" name="csrf_token" value="<?= e(Csrf::token()) ?>">
            <div class="flora-auth-row">
                <div class="flora-floating-field">
                    <input placeholder=" " id="auth-full_name" name="full_name" type="text" autocomplete="name" maxlength="100" value="<?= e($old['full_name'] ?? '') ?>" required
                    aria-invalid="<?= isset($errors['full_name']) ? 'true' : 'false' ?>" aria-describedby="auth-full_name-error">
                    <label for="auth-full_name">Họ và tên</label>
                </div>
                <span id="auth-full_name-error" class="flora-auth-error"><?php if (isset($errors['full_name'])): ?><?= e($errors['full_name']) ?><?php endif; ?></span>
            </div>
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
                    <input placeholder=" " id="auth-password" name="password" type="password" autocomplete="new-password" minlength="8" required
                    aria-invalid="<?= isset($errors['password']) ? 'true' : 'false' ?>" aria-describedby="auth-password-error">
                    <label for="auth-password">Mật khẩu</label>
                </div>
                <span id="auth-password-error" class="flora-auth-error"><?php if (isset($errors['password'])): ?><?= e($errors['password']) ?><?php endif; ?></span>
            </div>
            <div class="flora-auth-row">
                <div class="flora-floating-field">
                    <input placeholder=" " id="auth-password_confirm" name="password_confirm" type="password" autocomplete="new-password" minlength="8" required
                    aria-invalid="<?= isset($errors['password_confirm']) ? 'true' : 'false' ?>" aria-describedby="auth-password_confirm-error">
                    <label for="auth-password_confirm">Xác nhận mật khẩu</label>
                </div>
                <span id="auth-password_confirm-error" class="flora-auth-error"><?php if (isset($errors['password_confirm'])): ?><?= e($errors['password_confirm']) ?><?php endif; ?></span>
            </div>
            <button class="flora-auth-button" type="submit">Tạo tài khoản</button>
        </form>
        <p class="flora-auth-switch">Đã có tài khoản? <a href="/flora/index.php?route=login">Đăng nhập</a></p>
    </div>
</section>

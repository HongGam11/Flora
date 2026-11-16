<details class="tv1-staff" <?= $error!==''?'open':'' ?>><summary>Tạo tài khoản nhân viên</summary>
<p>Tài khoản mới có vai trò nhân viên. Bạn có thể thay đổi vai trò trong danh sách sau khi tạo.</p>
<form method="post" action="/flora/index.php?route=admin_users" class="tv1-form">
<input type="hidden" name="csrf_token" value="<?= e(Csrf::token()) ?>"><input type="hidden" name="action" value="create_staff">
<label>Họ tên <input name="full_name" value="<?= e($old['full_name']) ?>" maxlength="100" required autocomplete="off"></label>
<label>Email <input type="email" name="email" value="<?= e($old['email']) ?>" maxlength="255" required autocomplete="off"></label>
<label>Số điện thoại <input type="tel" name="phone" value="<?= e($old['phone']) ?>" maxlength="10" pattern="0[0-9]{9}"></label>
<label>Mật khẩu ban đầu <input type="password" name="password" required minlength="8" maxlength="72" autocomplete="new-password"></label>
<label>Nhập lại mật khẩu <input type="password" name="password_confirmation" required minlength="8" maxlength="72" autocomplete="new-password"></label>
<button type="submit" class="btn">Tạo nhân viên</button>
</form></details>

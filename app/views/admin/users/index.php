<section class="tv1-users">
<h1>Quản lý tài khoản</h1>
<p>Khóa tài khoản sẽ chặn đăng nhập và giữ nguyên lịch sử đơn hàng. Luôn giữ ít nhất một quản trị viên đang hoạt động.</p>
<?php if ($error !== ''): ?><p class="tv1-error" role="alert"><?= e($error) ?></p><?php endif; ?>
<form method="get" action="/flora/index.php" class="tv1-filters">
<input type="hidden" name="route" value="admin_users">
<label>Tìm kiếm <input name="q" value="<?= e($q) ?>" maxlength="255" placeholder="Họ tên, email, số điện thoại"></label>
<label>Vai trò <select name="role"><option value="">Tất cả</option><?php foreach ($labels as $value=>$label): ?><option value="<?= e($value) ?>" <?= $role===$value?'selected':'' ?>><?= e($label) ?></option><?php endforeach; ?></select></label>
<label>Trạng thái <select name="status"><option value="">Tất cả</option><option value="active" <?= $status==='active'?'selected':'' ?>>Hoạt động</option><option value="disabled" <?= $status==='disabled'?'selected':'' ?>>Đã khóa</option></select></label>
<button class="btn" type="submit">Tìm tài khoản</button><a href="/flora/index.php?route=admin_users">Xóa bộ lọc</a>
</form>
<p>Có <?= (int)$result['count'] ?> tài khoản — Trang <?= (int)$result['page'] ?>/<?= (int)$result['pages'] ?>.</p>
<div class="tv1-table-wrap"><table><thead><tr><th>ID</th><th>Tài khoản</th><th>Vai trò</th><th>Trạng thái</th><th>Ngày tạo</th></tr></thead><tbody>
<?php foreach ($result['rows'] as $row): ?>
<tr><td><?= (int)$row['id'] ?><?= (int)$row['id']===(int)Auth::user()['id']?' (bạn)':'' ?></td>
<td><strong><?= e($row['full_name']) ?></strong><br><?= e($row['email']) ?><br><?= e($row['phone'] ?? '') ?></td>
<td><form method="post" action="/flora/index.php?route=admin_users" class="tv1-inline">
<input type="hidden" name="csrf_token" value="<?= e(Csrf::token()) ?>"><input type="hidden" name="action" value="role"><input type="hidden" name="id" value="<?= (int)$row['id'] ?>">
<select name="value" aria-label="Vai trò của <?= e($row['email']) ?>"><?php foreach ($labels as $value=>$label): ?><option value="<?= e($value) ?>" <?= $row['role']===$value?'selected':'' ?>><?= e($label) ?></option><?php endforeach; ?></select><button type="submit">Lưu vai trò</button></form></td>
<td><span class="tv1-status <?= $row['status']==='active'?'is-active':'is-disabled' ?>"><?= $row['status']==='active'?'Hoạt động':'Đã khóa' ?></span>
<form method="post" action="/flora/index.php?route=admin_users" class="tv1-inline"><input type="hidden" name="csrf_token" value="<?= e(Csrf::token()) ?>"><input type="hidden" name="action" value="status"><input type="hidden" name="id" value="<?= (int)$row['id'] ?>"><input type="hidden" name="value" value="<?= $row['status']==='active'?'disabled':'active' ?>"><button type="submit"><?= $row['status']==='active'?'Khóa tài khoản':'Mở khóa' ?></button></form></td>
<td><?= e($row['created_at']) ?></td></tr>
<?php endforeach; ?>
<?php if (!$result['rows']): ?><tr><td colspan="5">Không tìm thấy tài khoản phù hợp.</td></tr><?php endif; ?>
</tbody></table></div>
<nav class="tv1-links" aria-label="Phân trang">
<?php foreach ([-1=>'← Trang trước',1=>'Trang sau →'] as $delta=>$label): $next=$result['page']+$delta; if ($next<1 || $next>$result['pages']) continue; ?>
<a href="/flora/index.php?<?= e(http_build_query(['route'=>'admin_users','q'=>$q,'role'=>$role,'status'=>$status,'page'=>$next])) ?>"><?= e($label) ?></a>
<?php endforeach; ?></nav>
<?php require APP_ROOT . '/app/views/admin/users/form.php'; ?>
</section>

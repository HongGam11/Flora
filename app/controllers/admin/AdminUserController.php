<?php

declare(strict_types=1);

final class AdminUserController
{
    public function index(): void
    {
        Auth::requireRole(['admin']);
        require APP_ROOT . '/database.php';
        $users = new UserModel($conn);
        $error = '';
        $old = ['full_name'=>'','email'=>'','phone'=>''];
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            flora_catalog_post();
            try {
                $action = flora_catalog_text('action');
                $data = [];
                if ($action === 'create_staff') {
                    foreach ($old as $key => $_) $data[$key] = $old[$key] = flora_catalog_text($key);
                    $data['email'] = strtolower($data['email']);
                    if (!is_string($_POST['password'] ?? null) || !is_string($_POST['password_confirmation'] ?? null)) throw new DomainException('Dữ liệu mật khẩu không hợp lệ.');
                    $data['password'] = $_POST['password'];
                    if ($data['password'] !== $_POST['password_confirmation']) throw new DomainException('Xác nhận mật khẩu chưa khớp.');
                } else {
                    $data = ['id' => flora_catalog_id('id'), 'value' => flora_catalog_text('value')];
                }
                $users->administer((int) Auth::user()['id'], $action, $data);
                if ($action !== 'create_staff' && (int) $data['id'] === (int) Auth::user()['id']) {
                    flora_catalog_success('Đã cập nhật tài khoản của bạn.');
                    flora_catalog_redirect('login');
                }
                flora_catalog_success('Đã cập nhật tài khoản.');
                flora_catalog_redirect('admin_users');
            } catch (DomainException $e) { $error = $e->getMessage(); }
            catch (mysqli_sql_exception $e) {
                error_log('Flora users SQL: ' . $e->getCode());
                $error = $e->getCode() === 1062 ? 'Email đã được sử dụng.' : 'Chưa thể lưu tài khoản. Vui lòng thử lại.';
            }
        }
        $q = is_string($_GET['q'] ?? null) ? trim($_GET['q']) : '';
        $q = preg_match('/\A.{0,255}/us', $q, $match) === 1 ? $match[0] : '';
        $role = in_array($_GET['role'] ?? '', ['customer','staff','admin'], true) ? $_GET['role'] : '';
        $status = in_array($_GET['status'] ?? '', ['active','disabled'], true) ? $_GET['status'] : '';
        $page = filter_var($_GET['page'] ?? 1, FILTER_VALIDATE_INT, ['options'=>['min_range'=>1,'max_range'=>1000000]]) ?: 1;
        $result = $users->search($q, $role, $status, $page);
        $labels = ['customer'=>'Khách hàng','staff'=>'Nhân viên','admin'=>'Quản trị viên'];
        $title = 'Quản lý tài khoản | Flora';
        require APP_ROOT . '/app/views/layouts/admin_header.php';
        require APP_ROOT . '/app/views/layouts/admin_sidebar.php';
        require APP_ROOT . '/app/views/admin/users/index.php';
        require APP_ROOT . '/app/views/layouts/admin_footer.php';
    }
}

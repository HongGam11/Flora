<?php

declare(strict_types=1);

final class ProfileController
{
    public function index(): void
    {
        Auth::requireLogin();
        require APP_ROOT . '/database.php';
        $users = new UserModel($conn);
        $id = (int) Auth::user()['id'];
        $profile = $users->findById($id);
        $error = '';
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            flora_catalog_post();
            try {
                $profile['full_name'] = flora_catalog_text('full_name');
                $profile['phone'] = flora_catalog_text('phone');
                $profile['address'] = flora_catalog_text('address');
                $users->updateProfile($id, $profile['full_name'], $profile['phone'], $profile['address']);
                $_SESSION['full_name'] = $profile['full_name'];
                $_SESSION['user']['full_name'] = $profile['full_name'];
                flora_catalog_success('Đã cập nhật hồ sơ. Thông tin của các đơn đã đặt được giữ nguyên.');
                flora_catalog_redirect('profile');
            } catch (DomainException $e) { $error = $e->getMessage(); }
            catch (mysqli_sql_exception $e) { error_log('Flora profile SQL: ' . $e->getCode()); $error = 'Chưa thể lưu hồ sơ. Vui lòng thử lại.'; }
        }
        $title = 'Hồ sơ cá nhân | Flora';
        require APP_ROOT . '/app/views/layouts/header.php';
        require APP_ROOT . '/app/views/profile/index.php';
        require APP_ROOT . '/app/views/layouts/footer.php';
    }

    public function changePassword(): void
    {
        Auth::requireLogin();
        $error = '';
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            flora_catalog_post();
            try {
                foreach (['current_password','new_password','password_confirmation'] as $key) {
                    if (!is_string($_POST[$key] ?? null) || strlen($_POST[$key]) > 1024) throw new DomainException('Dữ liệu mật khẩu không hợp lệ.');
                }
                if ($_POST['new_password'] !== $_POST['password_confirmation']) throw new DomainException('Xác nhận mật khẩu mới chưa khớp.');
                require APP_ROOT . '/database.php';
                (new UserModel($conn))->changePassword((int) Auth::user()['id'], $_POST['current_password'], $_POST['new_password']);
                $_SESSION = [];
                session_regenerate_id(true);
                Csrf::token();
                flora_catalog_success('Đã đổi mật khẩu. Vui lòng đăng nhập lại bằng mật khẩu mới.');
                flora_catalog_redirect('login');
            } catch (DomainException $e) { $error = $e->getMessage(); }
            catch (mysqli_sql_exception $e) { error_log('Flora password SQL: ' . $e->getCode()); $error = 'Chưa thể đổi mật khẩu. Vui lòng thử lại.'; }
        }
        $title = 'Đổi mật khẩu | Flora';
        require APP_ROOT . '/app/views/layouts/header.php';
        require APP_ROOT . '/app/views/profile/change_password.php';
        require APP_ROOT . '/app/views/layouts/footer.php';
    }
}

<?php

declare(strict_types=1);
require_once APP_ROOT . '/app/models/UserModel.php';

final class AuthController
{
    private UserModel $users;

    public function __construct()
    {
        require APP_ROOT . '/database.php';
        $this->users = new UserModel($conn);
    }

    public function register(): void
    {
        $this->allowMethods(['GET', 'POST']);
        $errors = [];
        $old = ['full_name' => '', 'email' => ''];
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            $this->checkCsrf();
            $name = trim($this->input('full_name'));
            $email = strtolower(trim($this->input('email')));
            $password = $this->input('password');
            $confirm = $this->input('password_confirm');
            $old = ['full_name' => $name, 'email' => $email];
            if (!preg_match('/\A.{2,100}\z/us', $name)) {
                $errors['full_name'] = 'Họ tên cần từ 2 đến 100 ký tự.';
            }
            if (strlen($email) > 255 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors['email'] = 'Vui lòng nhập email hợp lệ.';
            }
            if (!preg_match('/\A.{8,}\z/us', $password) || strlen($password) > 72 || str_contains($password, "\0")) {
                $errors['password'] = 'Mật khẩu cần ít nhất 8 ký tự và tối đa 72 byte; ký tự có dấu chiếm nhiều byte.';
            }
            if ($confirm !== $password) {
                $errors['password_confirm'] = 'Mật khẩu nhập lại chưa khớp.';
            }
            if ($errors === []) {
                try {
                    if ($this->users->findByEmail($email)) {
                        $errors['email'] = 'Email này đã được đăng ký.';
                    } else {
            $newUserId = $this->users->createCustomer(
                $name,
                $email,
                password_hash($password, PASSWORD_BCRYPT)
            );

            session_regenerate_id(true);
            $_SESSION = [];

            $_SESSION['user_id'] = $newUserId;
            $registeredUser = $this->users->findByEmail($email);
            $_SESSION['auth_password_stamp'] = hash('sha256', $registeredUser['password_hash']);
            $_SESSION['full_name'] = $name;
            $_SESSION['role'] = 'customer';

            $_SESSION['user'] = [
                'id' => $newUserId,
                'full_name' => $name,
                'email' => $email,
                'role' => 'customer',
                'status' => 'active',
            ];

            Csrf::token();

            $this->flash(
                'success',
                'Đăng ký thành công. Chào mừng bạn đến với Flora!'
            );

            $this->redirect('login');
                    }
                } catch (mysqli_sql_exception $exception) {
                    if ((int) $exception->getCode() === 1062) {
                        $errors['email'] = 'Email này đã được đăng ký.';
                    } else {
                        error_log('Flora register DB error: ' . $exception->getCode());
                        $errors['general'] = 'Chưa thể đăng ký. Vui lòng thử lại sau.';
                    }
                }
            }
        }
        $this->render('register', 'Đăng ký | Flora', $errors, $old);
    }

    public function login(): void
    {
        $this->allowMethods(['GET', 'POST']);
        $currentUserId = (int) (Auth::user()['id'] ?? 0);

    if ($currentUserId > 0) {
        $accountUser = $this->users->findById($currentUserId);

        if ($accountUser && $accountUser['status'] === 'active') {
            $this->render(
                'account',
                'Tài khoản của bạn | Flora',
                [],
                [],
                $accountUser
            );

            return;
        }

        // Tài khoản đã bị khóa hoặc không còn tồn tại.
        $_SESSION = [];
        session_regenerate_id(true);
        Csrf::token();

        $this->flash(
            'warning',
            'Phiên đăng nhập không còn hợp lệ. Vui lòng đăng nhập lại.'
        );

        $this->redirect('login');
    }
        $errors = [];
        $old = ['email' => ''];
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            $this->checkCsrf();
            $email = strtolower(trim($this->input('email')));
            $password = $this->input('password');
            $old['email'] = $email;
            if (strlen($email) > 255 || !filter_var($email, FILTER_VALIDATE_EMAIL) || $password === '' || strlen($password) > 1024) {
                $errors['general'] = 'Vui lòng nhập email và mật khẩu hợp lệ.';
            } else {
                try {
                    $user = $this->users->findByEmail($email);
                    // Hash giả để vẫn thực hiện phép kiểm tra khi email không tồn tại.
                    $hash = $user['password_hash'] ?? '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi';
                    $passwordOk = password_verify($password, $hash);
                    if (!$user || !$passwordOk) {
                        $errors['general'] = 'Email hoặc mật khẩu không đúng.';
                    } elseif ($user['status'] !== 'active') {
                        $errors['general'] = 'Tài khoản đang bị khóa. Vui lòng liên hệ quản trị viên.';
                    } else {
                    
                        session_regenerate_id(true);
                        // Xóa trạng thái giỏ/mã giảm của phiên cũ; giỏ thật nằm trong database.
                        $_SESSION = [];
                        $_SESSION['user_id'] = (int) $user['id'];
                        $_SESSION['auth_password_stamp'] = hash('sha256', $user['password_hash']);
                        $_SESSION['full_name'] = $user['full_name'];
                        $_SESSION['role'] = $user['role'];
                        $_SESSION['user'] = [
                            'id' => (int) $user['id'],
                            'full_name' => $user['full_name'],
                            'email' => $user['email'],
                            'role' => $user['role'],
                            'status' => $user['status'],
                        ];
                        Csrf::token();
                        $this->flash('success', 'Đăng nhập thành công. Chào mừng bạn đến với Flora!');
                    $this->redirect('login');
                    }
                } catch (mysqli_sql_exception $exception) {
                    error_log('Flora login DB error: ' . $exception->getCode());
                    $errors['general'] = 'Chưa thể đăng nhập. Vui lòng thử lại sau.';
                }
            }
        }
        $this->render('login', 'Đăng nhập | Flora', $errors, $old);
    }

    public function logout(): void
    {
        $this->allowMethods(['POST']);
        $this->checkCsrf();
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', [
                'expires' => time() - 42000,
                'path' => $params['path'], 'domain' => $params['domain'],
                'secure' => $params['secure'], 'httponly' => $params['httponly'],
                'samesite' => $params['samesite'] ?? 'Lax',
            ]);
        }
        session_destroy();
        $this->redirect('home');
    }

        private function render(
            string $view,
            string $title,
            array $errors,
            array $old,
            ?array $accountUser = null
        ): void
    {
        // Tương thích header A4 có messages.php, tránh tiêu thụ thông báo hai lần.
        $flash = $_SESSION['flash_message'] ?? null;
        unset($_SESSION['flash_message']);
        require APP_ROOT . '/app/views/layouts/header.php';
        require APP_ROOT . '/app/views/auth/' . $view . '.php';
        require APP_ROOT . '/app/views/layouts/footer.php';
    }

    private function input(string $key): string
    {
        $value = $_POST[$key] ?? '';
        return is_string($value) ? $value : '';
    }

    private function checkCsrf(): void
    {
        $token = $_POST['csrf_token'] ?? null;
        if (!Csrf::verify(is_string($token) ? $token : null)) {
            http_response_code(403);
            exit('Yêu cầu không hợp lệ. Vui lòng tải lại trang và thử lại.');
        }
    }

    private function allowMethods(array $allowed): void
    {
        if (!in_array($_SERVER['REQUEST_METHOD'] ?? 'GET', $allowed, true)) {
            http_response_code(405);
            header('Allow: ' . implode(', ', $allowed));
            exit('Phương thức không được phép.');
        }
    }

    private function safeReturn(mixed $url): string
    {
        if (!is_string($url) || strlen($url) > 2048 || preg_match('/[\x00-\x20\x7f\\\\]/', $url)) {
            return 'index.php?route=products';
        }
        $parts = parse_url($url);
        if ($parts === false || isset($parts['scheme']) || isset($parts['host']) || ($parts['path'] ?? '') !== 'index.php' || isset($parts['fragment'])) {
            return 'index.php?route=products';
        }
        parse_str($parts['query'] ?? '', $query);
        if (!isset($query['route']) || !is_string($query['route']) || !in_array($query['route'], ['home', 'products', 'product-detail', 'cart', 'checkout', 'orders', 'order_detail'], true)) {
            return 'index.php?route=products';
        }
        return $url;
    }

    private function flash(string $type, string $text): void
    {
        $_SESSION['flash_message'] = ['type' => $type, 'text' => $text];
    }

    private function redirect(string $route): never
    {
        header('Location: /flora/index.php?route=' . $route);
        exit;
    }
}

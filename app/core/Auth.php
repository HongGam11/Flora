<?php

final class Auth
{
    private static bool $checked = false;
    private static ?array $currentUser = null;

    public static function user(): ?array
    {
        if (self::$checked) {
            return self::$currentUser;
        }

        self::$checked = true;

        $userId = (int) (
            $_SESSION['user_id']
            ?? $_SESSION['user']['id']
            ?? 0
        );

        if ($userId <= 0) {
            return null;
        }

        require APP_ROOT . '/database.php';

        $stmt = $conn->prepare(
            'SELECT id, full_name, email, password_hash, role, status
             FROM users
             WHERE id = ?
             LIMIT 1'
        );

        $stmt->bind_param('i', $userId);
        $stmt->execute();

        $user = $stmt->get_result()->fetch_assoc();

        $stmt->close();
        $conn->close();

        if (!$user || $user['status'] !== 'active'
            || !isset($_SESSION['auth_password_stamp'])
            || !hash_equals((string) $_SESSION['auth_password_stamp'], hash('sha256', $user['password_hash']))) {
            // Xóa quyền đăng nhập nếu tài khoản bị khóa hoặc bị xóa.
            $_SESSION = [];
            session_regenerate_id(true);
            Csrf::token();

            $_SESSION['flash_message'] = [
                'type' => 'warning',
                'text' => 'Phiên đăng nhập đã hết hiệu lực. Vui lòng đăng nhập lại.',
            ];

            return null;
        }

        // Đồng bộ thông tin phiên với dữ liệu hiện tại trong database.
        $_SESSION['user_id'] = (int) $user['id'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['role'] = $user['role'];

        $_SESSION['user'] = [
            'id' => (int) $user['id'],
            'full_name' => $user['full_name'],
            'email' => $user['email'],
            'role' => $user['role'],
            'status' => $user['status'],
        ];

        unset($user['password_hash']);
        self::$currentUser = $user;

        return self::$currentUser;
    }

    public static function requireLogin(): void
    {
        if (self::user() !== null) {
            return;
        }

        if (!isset($_SESSION['flash_message'])) {
            $_SESSION['flash_message'] = [
                'type' => 'warning',
                'text' => 'Vui lòng đăng nhập để sử dụng chức năng này.',
            ];
        }

        header('Location: /flora/index.php?route=login');
        exit;
    }

    public static function requireRole(array $allowedRoles): void
    {
        self::requireLogin();

        $user = self::user();

        if (in_array($user['role'], $allowedRoles, true)) {
            return;
        }

        http_response_code(403);

        require APP_ROOT . '/app/views/errors/403.php';
        exit;
    }
}
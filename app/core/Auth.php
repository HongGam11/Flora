<?php
/** TV1 bổ sung xác thực, kiểm tra tài khoản active và quyền ở server. */
final class Auth
{
    public static function requireLogin(): void
    {
        throw new LogicException('Chưa triển khai kiểm tra đăng nhập.');
    }
    public static function requireRole(array $roles): void
    {
        throw new LogicException('Chưa triển khai kiểm tra vai trò.');
    }
}

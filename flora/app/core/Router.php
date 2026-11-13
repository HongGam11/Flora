<?php
final class Router
{
    public function dispatch(string $route): void
    {
        // Chỉ các route trong danh sách được phép mới được gọi.
        if ($route === 'home') {
            (new HomeController())->index();
            return;
        }
        http_response_code(404);
        require APP_ROOT . '/app/views/errors/404.php';
    }
}

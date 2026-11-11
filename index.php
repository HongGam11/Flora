<?php
require_once __DIR__ . '/app/bootstrap.php';
$route = $_GET['route'] ?? 'home';
if (!is_string($route)) {
    http_response_code(400);
    exit('Đường dẫn không hợp lệ.');
}
(new Router())->dispatch($route);

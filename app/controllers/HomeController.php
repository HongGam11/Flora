<?php
final class HomeController
{
    public function index(): void
    {
        $title = 'Flora bộ khung dự án';
        require APP_ROOT . '/app/views/layouts/header.php';
        require APP_ROOT . '/app/views/home/index.php';
        require APP_ROOT . '/app/views/layouts/footer.php';
    }
}

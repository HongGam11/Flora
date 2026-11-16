<?php

final class AdminDashboardController
{
    public function index(): void
    {
        Auth::requireRole(['admin']);

        $title = 'Tổng quan quản trị | Flora';

        require APP_ROOT . '/app/views/layouts/admin_header.php';
        require APP_ROOT . '/app/views/layouts/admin_sidebar.php';

        echo '<section class="admin-card">';
        echo '<h1>Quản trị viên</h1>';
        echo '</section>';

        require APP_ROOT . '/app/views/layouts/admin_footer.php';
    }
}
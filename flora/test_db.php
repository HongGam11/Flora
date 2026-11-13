<?php
// Chỉ dùng khi phát triển trên máy cá nhân; bỏ file này khỏi bản triển khai công khai.
header('Content-Type: text/html; charset=utf-8');
try {
    require __DIR__ . '/database.php';
    $row = $conn->query('SELECT COUNT(*) AS total FROM products')->fetch_assoc();
    echo 'Kết nối database Flora thành công!<br>Số sản phẩm: ' . (int) $row['total'];
} catch (Throwable $error) {
    http_response_code(500);
    error_log((string) $error);
    echo 'Chưa kết nối được database. Kiểm tra database.local.php và log PHP của XAMPP.';
}

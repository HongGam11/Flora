<?php
require_once __DIR__ . '/database.php';

$result = $conn->query(
    'SELECT COUNT(*) AS total FROM products'
);

$row = $result->fetch_assoc();

echo 'Kết nối database Flora thành công!<br>';
echo 'Số sản phẩm: ' . (int) $row['total'];
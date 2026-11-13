<?php
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$configPath = __DIR__ . '/database.local.php';
if (!is_file($configPath)) {
    throw new RuntimeException('Hãy sao chép database.example.php thành database.local.php và điền cấu hình.');
}
$config = require $configPath;
$conn = new mysqli($config['host'], $config['username'], $config['password'],
    $config['database'], (int) ($config['port'] ?? 3306));
$conn->set_charset('utf8mb4');
$conn->query("SET time_zone = '+00:00'");

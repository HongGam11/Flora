<?php
// TV6 triển khai tác vụ CLI sau khi OrderService::cancelExpired hoạt động.
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Tác vụ này chạy bằng PHP CLI.');
}
fwrite(STDERR, "TODO TV6: chưa triển khai hủy đơn hết hạn.
");
exit(1);

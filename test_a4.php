<?php

require_once __DIR__ . '/app/bootstrap.php';

$testText = '<b>Flora</b> & "Quần áo"';
$testResult = null;
$testPassed = false;

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $submittedToken = $_POST['csrf_token'] ?? null;

    $testPassed = Csrf::verify(
        is_string($submittedToken) ? $submittedToken : null
    );

    if ($testPassed) {
        $testResult = 'CHẤP NHẬN: token hợp lệ.';
    } else {
        http_response_code(403);
        $testResult = 'TỪ CHỐI: token sai hoặc bị thiếu.';
    }
}

$testToken = Csrf::token();
?>
<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Kiểm tra A4</title>
</head>
<body>
    <h1>Kiểm tra escape và CSRF</h1>

    <h2>1. Escape HTML</h2>

    <p>Nội dung bên dưới phải hiện nguyên dấu ngoặc và thẻ:</p>

    <pre><?= e($testText) ?></pre>

    <h2>2. CSRF</h2>

    <?php if ($testResult !== null): ?>
        <p role="status">
            <strong><?= e($testResult) ?></strong>
        </p>
    <?php endif; ?>

    <form method="post" action="/flora/test_a4.php">
        <input
            type="hidden"
            name="csrf_token"
            value="<?= e($testToken) ?>"
        >
        <button type="submit">
            Thử token đúng
        </button>
    </form>

    <br>

    <form method="post" action="/flora/test_a4.php">
        <input
            type="hidden"
            name="csrf_token"
            value="token-co-tinh-nhap-sai"
        >
        <button type="submit">
            Thử token sai
        </button>
    </form>

    <br>

    <form method="post" action="/flora/test_a4.php">
        <button type="submit">
            Thử thiếu token
        </button>
    </form>
</body>
</html>
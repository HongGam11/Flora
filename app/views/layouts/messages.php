<?php
$layoutFlash = null;

if (isset($flash) && is_array($flash)) {
    $layoutFlash = $flash;
} elseif (
    isset($_SESSION['flash_message'])
    && is_array($_SESSION['flash_message'])
) {
    $layoutFlash = $_SESSION['flash_message'];
    unset($_SESSION['flash_message']);
}

$layoutFlashTypes = [
    'success' => 'Thành công',
    'error'   => 'Có lỗi',
    'warning' => 'Lưu ý',
    'info'    => 'Thông tin',
];
?>

<?php if (
    is_array($layoutFlash)
    && isset($layoutFlash['text'])
    && is_string($layoutFlash['text'])
    && $layoutFlash['text'] !== ''
): ?>
    <?php
    $layoutFlashType = $layoutFlash['type'] ?? 'info';

    if (
        !is_string($layoutFlashType)
        || !isset($layoutFlashTypes[$layoutFlashType])
    ) {
        $layoutFlashType = 'info';
    }
    ?>

    <div class="c">
        <div
            class="flora-alert flora-alert--<?= e($layoutFlashType) ?>"
            role="<?= $layoutFlashType === 'error' ? 'alert' : 'status' ?>"
        >
            <strong>
                <?= e($layoutFlashTypes[$layoutFlashType]) ?>:
            </strong>

            <span><?= e($layoutFlash['text']) ?></span>
        </div>
    </div>
<?php endif; ?>
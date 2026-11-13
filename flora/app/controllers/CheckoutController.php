<?php
declare(strict_types=1);

require_once APP_ROOT . '/app/models/OrderModel.php';
require_once APP_ROOT . '/app/services/CheckoutService.php';

/**
 * Phụ trách: TV5.
 * Điều phối màn hình checkout, xử lý tạo đơn và màn hình đặt hàng thành công.
 */
final class CheckoutController
{
    private mysqli $conn;
    private CheckoutService $checkoutService;
    private OrderModel $orderModel;

    public function __construct(mysqli $conn)
    {
        $this->conn = $conn;
        $this->checkoutService = new CheckoutService($conn);
        $this->orderModel = new OrderModel($conn);
    }

    /**
     * GET index.php?route=checkout
     */
    public function index(): void
    {
        $userId = $this->requireCustomerUserId();

        $couponCode = isset($_GET['coupon']) ? trim((string) $_GET['coupon']) : null;
        if ($couponCode === '') {
            $couponCode = null;
        }

        // Cấp checkout_token 64 ký tự hex gắn với người dùng hiện tại
        if (empty($_SESSION['checkout_tokens'][$userId])) {
            $_SESSION['checkout_tokens'][$userId] = bin2hex(random_bytes(32));
        }
        $checkoutToken = $_SESSION['checkout_tokens'][$userId];

        $preview = $this->checkoutService->buildCheckoutPreview($userId, $couponCode);

        $title = 'Xác nhận đặt hàng - Flora';
        $csrfToken = Csrf::token();
        $flashError = $_SESSION['flash_error'] ?? null;
        $flashWarning = $_SESSION['flash_warning'] ?? null;
        $oldInput = $_SESSION['checkout_old_input'] ?? [];
        unset($_SESSION['flash_error'], $_SESSION['flash_warning'], $_SESSION['checkout_old_input']);

        require APP_ROOT . '/app/views/layouts/header.php';
        require APP_ROOT . '/app/views/checkout/index.php';
        require APP_ROOT . '/app/views/layouts/footer.php';
    }

    /**
     * POST index.php?route=checkout/submit
     */
    public function submit(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            header('Location: index.php?route=checkout');
            exit;
        }

        $userId = $this->requireCustomerUserId();

        if (!Csrf::verify($_POST['csrf_token'] ?? null)) {
            $_SESSION['flash_error'] = 'Phiên biểu mẫu không hợp lệ (CSRF). Vui lòng tải lại trang và thử lại.';
            header('Location: index.php?route=checkout');
            exit;
        }

        $submittedToken = trim((string) ($_POST['checkout_token'] ?? ''));
        $sessionToken = $_SESSION['checkout_tokens'][$userId] ?? '';

        // Kiểm tra checkout_token thuộc đúng phiên người dùng hiện tại (hoặc đã tạo đơn trước đó)
        $existingOrder = preg_match('/^[a-f0-9]{64}$/', $submittedToken)
            ? $this->orderModel->findByCheckoutTokenAndUser($submittedToken, $userId)
            : null;

        if ($existingOrder === null && ($sessionToken === '' || !hash_equals($sessionToken, $submittedToken))) {
            $_SESSION['flash_error'] = 'Mã xác nhận đơn hàng không khớp với phiên của bạn. Vui lòng kiểm tra lại.';
            header('Location: index.php?route=checkout');
            exit;
        }

        $_SESSION['checkout_old_input'] = [
            'recipient_name' => $_POST['recipient_name'] ?? '',
            'recipient_phone' => $_POST['recipient_phone'] ?? '',
            'shipping_address' => $_POST['shipping_address'] ?? '',
            'note' => $_POST['note'] ?? '',
            'payment_method' => $_POST['payment_method'] ?? 'cod',
            'coupon_code' => $_POST['coupon_code'] ?? '',
        ];

        try {
            $result = $this->checkoutService->placeOrder($userId, $_POST);
            $order = $result['order'];

            // Xóa token đã dùng và dữ liệu tạm sau khi tạo đơn thành công
            unset($_SESSION['checkout_tokens'][$userId], $_SESSION['checkout_old_input']);

            if ($result['idempotent']) {
                $_SESSION['flash_info'] = 'Đơn hàng này đã được ghi nhận trước đó.';
            }

            // Nếu đơn online đang chờ thanh toán và total_amount > 0 -> chuyển sang màn hình thanh toán mô phỏng của TV6
            if (
                $order['status'] === 'pending'
                && $order['payment_method'] !== 'cod'
                && (int) $order['total_amount'] > 0
                && $order['payment_status'] === 'unpaid'
            ) {
                header('Location: index.php?route=payment/simulate&order_id=' . (int) $order['id']);
                exit;
            }

            header('Location: index.php?route=checkout/success&order_code=' . urlencode($order['order_code']));
            exit;
        } catch (DomainException $e) {
            // Giá hoặc tổng tiền thay đổi trước xác nhận cuối (Task E11)
            $_SESSION['flash_warning'] = $e->getMessage();
            $couponRedirect = trim((string) ($_POST['coupon_code'] ?? ''));
            $q = $couponRedirect !== '' ? '&coupon=' . urlencode($couponRedirect) : '';
            header('Location: index.php?route=checkout' . $q);
            exit;
        } catch (InvalidArgumentException | RuntimeException $e) {
            $_SESSION['flash_error'] = $e->getMessage();
            $couponRedirect = trim((string) ($_POST['coupon_code'] ?? ''));
            $q = $couponRedirect !== '' ? '&coupon=' . urlencode($couponRedirect) : '';
            header('Location: index.php?route=checkout' . $q);
            exit;
        } catch (Throwable $e) {
            error_log('[Flora Checkout Error] ' . $e->getMessage());
            $_SESSION['flash_error'] = 'Có lỗi xảy ra trong quá trình tạo đơn hàng. Vui lòng thử lại.';
            header('Location: index.php?route=checkout');
            exit;
        }
    }

    /**
     * GET index.php?route=checkout/success&order_code=...
     */
    public function success(): void
    {
        $userId = $this->requireCustomerUserId();
        $orderCode = trim((string) ($_GET['order_code'] ?? ''));

        if ($orderCode === '') {
            http_response_code(404);
            require APP_ROOT . '/app/views/errors/404.php';
            return;
        }

        $order = $this->orderModel->findByCodeAndUser($orderCode, $userId);
        if (!$order) {
            http_response_code(404);
            require APP_ROOT . '/app/views/errors/404.php';
            return;
        }

        $items = $this->orderModel->getOrderItems((int) $order['id']);
        $title = 'Đặt hàng thành công #' . $order['order_code'] . ' - Flora';
        $flashInfo = $_SESSION['flash_info'] ?? null;
        unset($_SESSION['flash_info']);

        require APP_ROOT . '/app/views/layouts/header.php';
        require APP_ROOT . '/app/views/checkout/success.php';
        require APP_ROOT . '/app/views/layouts/footer.php';
    }

    /**
     * Lấy user_id đã đăng nhập (tương thích với Auth của TV1 hoặc session chuẩn).
     */
    private function requireCustomerUserId(): int
    {
        if (class_exists('Auth')) {
            try {
                Auth::requireLogin();
            } catch (LogicException $e) {
                // Auth guard của TV1 đang ở trạng thái khung TODO -> dùng fallback session bên dưới
            }
        }

        $userId = $_SESSION['user_id'] ?? ($_SESSION['user']['id'] ?? null);
        if (!$userId || (int) $userId <= 0) {
            $_SESSION['flash_error'] = 'Vui lòng đăng nhập để tiếp tục đặt hàng.';
            header('Location: index.php?route=login');
            exit;
        }

        return (int) $userId;
    }
}
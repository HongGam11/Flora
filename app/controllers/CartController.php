<?php
declare(strict_types=1);

require_once APP_ROOT . '/app/models/CartModel.php';
require_once APP_ROOT . '/app/models/CouponModel.php';
require_once APP_ROOT . '/app/services/PricingService.php';
if (is_file(APP_ROOT . '/app/models/UserEventModel.php')) {
    require_once APP_ROOT . '/app/models/UserEventModel.php';
}

/**
 * Phụ trách: TV4 (Hoàng Huy).
 * Controller xử lý giỏ hàng và áp/gỡ mã giảm giá trên giỏ.
 */
final class CartController
{
    private mysqli $conn;
    private CartModel $cartModel;
    private CouponModel $couponModel;

    public function __construct(?mysqli $conn = null)
    {
        if ($conn instanceof mysqli) {
            $this->conn = $conn;
        } else {
            require APP_ROOT . '/database.php';
            /** @var mysqli $conn */
            $this->conn = $conn;
        }
        $this->cartModel   = new CartModel($this->conn);
        $this->couponModel = new CouponModel($this->conn);
    }

    public function index(): void
    {
        $userId = $this->requireAuthenticatedActiveUser();

        $items       = $this->cartModel->getCartItemsByUserId($userId, false);
        $baseSummary = PricingService::calculateSummary($items, null);
        $subtotal    = $baseSummary['subtotal'];

        $appliedCoupon = null;
        $couponCode    = (string) ($_SESSION['applied_coupon_code'] ?? '');

        if ($couponCode !== '') {
            if ($subtotal <= 0) {
                unset($_SESSION['applied_coupon_code']);
            } else {
                try {
                    $validated     = $this->couponModel->validateAndLockForOrder($couponCode, $userId, $subtotal, false);
                    $appliedCoupon = $validated['coupon'];
                } catch (Throwable $e) {
                    unset($_SESSION['applied_coupon_code']);
                    $this->setFlash('warning', 'Mã giảm giá ' . e($couponCode) . ' đã được gỡ: ' . $e->getMessage());
                }
            }
        }

        $summary          = PricingService::calculateSummary($items, $appliedCoupon);
        $availableCoupons = $subtotal > 0
            ? $this->couponModel->getAvailableCouponsForUser($userId, $subtotal)
            : [];

        $hasInvalid = false;
        foreach ($summary['items'] as $it) {
            if (empty($it['is_purchasable'])) {
                $hasInvalid = true;
                break;
            }
        }

        $title     = 'Giỏ hàng của bạn — Flora';
        $csrfToken = Csrf::token();
        $flash     = $this->consumeFlash();

        require APP_ROOT . '/app/views/layouts/header.php';
        require APP_ROOT . '/app/views/cart/index.php';
        require APP_ROOT . '/app/views/layouts/footer.php';
    }

    public function add(): void
    {
        $this->requirePostAndCsrf();
        $userId    = $this->requireAuthenticatedActiveUser();
        $variantId = (int) ($_POST['variant_id'] ?? 0);
        $quantity  = (int) ($_POST['quantity'] ?? 1);

        try {
            $added = $this->cartModel->addItem($userId, $variantId, $quantity);
            $this->recordAddToCartEvent($userId, (int) $added['product_id']);

            if ($this->isAjaxRequest()) {
                $totalQty = $this->cartModel->countTotalQuantity($userId);
                $this->jsonResponse(200, [
                    'ok'         => true,
                    'message'    => 'Đã thêm sản phẩm vào giỏ hàng.',
                    'cart_count' => $totalQty,
                ]);
            }

            $this->setFlash('success', 'Đã thêm sản phẩm vào giỏ hàng.');
            $this->redirect('index.php?route=cart');
        } catch (Throwable $e) {
            if ($this->isAjaxRequest()) {
                $this->jsonResponse(400, ['ok' => false, 'message' => $e->getMessage()]);
            }
            $this->setFlash('error', $e->getMessage());
            $backUrl = $this->safeReturnUrl($_POST['return_url'] ?? null, 'index.php?route=products');
            $this->redirect($backUrl);
        }
    }

    public function update(): void
    {
        $this->requirePostAndCsrf();
        $userId     = $this->requireAuthenticatedActiveUser();
        $cartItemId = (int) ($_POST['cart_item_id'] ?? 0);
        $quantity   = (int) ($_POST['quantity'] ?? 0);

        try {
            $this->cartModel->updateItemQuantity($userId, $cartItemId, $quantity);
            $this->setFlash('success', 'Đã cập nhật số lượng sản phẩm.');
        } catch (Throwable $e) {
            $this->setFlash('error', $e->getMessage());
        }

        $this->redirect('index.php?route=cart');
    }

    public function remove(): void
    {
        $this->requirePostAndCsrf();
        $userId     = $this->requireAuthenticatedActiveUser();
        $cartItemId = (int) ($_POST['cart_item_id'] ?? 0);

        try {
            $this->cartModel->removeItem($userId, $cartItemId);
            $this->setFlash('success', 'Đã xóa sản phẩm khỏi giỏ hàng.');
        } catch (Throwable $e) {
            $this->setFlash('error', $e->getMessage());
        }

        $this->redirect('index.php?route=cart');
    }

    public function applyCoupon(): void
    {
        $this->requirePostAndCsrf();
        $userId = $this->requireAuthenticatedActiveUser();
        $code   = CouponModel::normalizeCode((string) ($_POST['coupon_code'] ?? ''));

        try {
            $items    = $this->cartModel->getCartItemsByUserId($userId, false);
            $summary  = PricingService::calculateSummary($items, null);
            $subtotal = $summary['subtotal'];

            if (empty($items)) {
                throw new RuntimeException('Giỏ hàng đang trống, không thể áp dụng mã giảm giá.');
            }

            $validated = $this->couponModel->validateAndLockForOrder($code, $userId, $subtotal, false);
            $_SESSION['applied_coupon_code'] = (string) $validated['coupon']['code'];

            $savedText = PricingService::formatVnd((int) $validated['discount_amount']);
            $this->setFlash('success', "Đã áp dụng mã {$validated['coupon']['code']} (giảm {$savedText}).");
        } catch (Throwable $e) {
            $this->setFlash('error', $e->getMessage());
        }

        $redirectTo = $this->safeReturnUrl($_POST['redirect_to'] ?? null, 'index.php?route=cart');
        $this->redirect($redirectTo);
    }

    public function removeCoupon(): void
    {
        $this->requirePostAndCsrf();
        $this->requireAuthenticatedActiveUser();

        unset($_SESSION['applied_coupon_code']);
        $this->setFlash('success', 'Đã gỡ mã giảm giá.');

        $redirectTo = $this->safeReturnUrl($_POST['redirect_to'] ?? null, 'index.php?route=cart');
        $this->redirect($redirectTo);
    }

    private function recordAddToCartEvent(int $userId, int $productId): void
    {
        if ($userId <= 0 || $productId <= 0) {
            return;
        }

        if (empty($_SESSION['tracking_session_key'])) {
            $_SESSION['tracking_session_key'] = bin2hex(random_bytes(16));
        }
        $sessionKey = (string) $_SESSION['tracking_session_key'];

        try {
           if (class_exists('UserEventModel')) {
                $eventModel = new UserEventModel($this->conn);
                $callable   = [$eventModel, 'logAddToCart'];
                if (is_callable($callable)) {
                    $callable($userId, $sessionKey, $productId);
                    return;
                }
            }

            $stmt = $this->conn->prepare("
                INSERT INTO user_events (user_id, session_key, event_type, product_id, search_keyword)
                VALUES (?, ?, 'add_to_cart', ?, NULL)
            ");
            $stmt->bind_param('isi', $userId, $sessionKey, $productId);
            $stmt->execute();
            $stmt->close();
        } catch (Throwable) {
        }
    }

    private function requireAuthenticatedActiveUser(): int
    {
        $userId = (int) ($_SESSION['user_id'] ?? ($_SESSION['user']['id'] ?? 0));
        if ($userId <= 0) {
            $returnUrl = $this->safeReturnUrl($_POST['return_url'] ?? ($_SERVER['HTTP_REFERER'] ?? null), 'index.php?route=products');
            $_SESSION['redirect_after_login'] = $returnUrl;

            if ($this->isAjaxRequest()) {
                $this->jsonResponse(401, [
                    'ok'           => false,
                    'requireLogin' => true,
                    'message'      => 'Vui lòng đăng nhập để sử dụng giỏ hàng.',
                    'loginUrl'     => 'index.php?route=login',
                ]);
            }

            $this->setFlash('warning', 'Vui lòng đăng nhập để thêm hoặc xem giỏ hàng.');
            $this->redirect('index.php?route=login');
        }

        $stmt = $this->conn->prepare('SELECT id, status FROM users WHERE id = ? LIMIT 1');
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$user || $user['status'] !== 'active') {
            session_unset();
            if ($this->isAjaxRequest()) {
                $this->jsonResponse(403, ['ok' => false, 'message' => 'Tài khoản của bạn đã bị khóa hoặc không hợp lệ.']);
            }
            $this->setFlash('error', 'Tài khoản không hoạt động. Vui lòng đăng nhập lại.');
            $this->redirect('index.php?route=login');
        }

        return $userId;
    }

    private function requirePostAndCsrf(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            http_response_code(405);
            exit('Phương thức không được phép.');
        }

        $token = $_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null);
        if (!Csrf::verify(is_string($token) ? $token : null)) {
            if ($this->isAjaxRequest()) {
                $this->jsonResponse(403, ['ok' => false, 'message' => 'Phiên làm việc hết hạn (CSRF). Vui lòng tải lại trang.']);
            }
            http_response_code(403);
            exit('Yêu cầu không hợp lệ (CSRF token sai).');
        }
    }

    private function safeReturnUrl(?string $url, string $fallback): string
    {
        if (!is_string($url) || $url === '') {
            return $fallback;
        }
        if (str_starts_with($url, 'index.php')) {
            return $url;
        }
        $parsed = parse_url($url);
        if (!empty($parsed['path']) && str_ends_with($parsed['path'], 'index.php')) {
            return 'index.php' . (!empty($parsed['query']) ? '?' . $parsed['query'] : '');
        }
        return $fallback;
    }

    private function isAjaxRequest(): bool
    {
        $xhr    = strtolower((string) ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? ''));
        $accept = strtolower((string) ($_SERVER['HTTP_ACCEPT'] ?? ''));
        return $xhr === 'xmlhttprequest' || str_contains($accept, 'application/json');
    }

    private function jsonResponse(int $statusCode, array $payload): never
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE);
        exit;
    }

    private function setFlash(string $type, string $message): void
    {
        $_SESSION['flash_message'] = ['type' => $type, 'text' => $message];
    }

    private function consumeFlash(): ?array
    {
        $flash = $_SESSION['flash_message'] ?? null;
        unset($_SESSION['flash_message']);
        return is_array($flash) ? $flash : null;
    }

    private function redirect(string $location): never
    {
        header('Location: ' . $location);
        exit;
    }
}
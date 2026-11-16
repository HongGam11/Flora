<?php
require_once APP_ROOT . '/app/models/OrderModel.php';
require_once APP_ROOT . '/app/services/CheckoutService.php';
final class CheckoutController
{
    private mysqli $conn;
    private CheckoutService $checkoutService;
    private OrderModel $orderModel;

    public function __construct()
    {
        Auth::requireLogin();
        require APP_ROOT . '/database.php';
        $this->conn = $conn;
        $this->checkoutService = new CheckoutService($conn);
        $this->orderModel = new OrderModel($conn);
    }
    public function index(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') { $this->submit(); return; }
        $userId = (int) Auth::user()['id'];
        // Mỗi lần xem xác nhận có token/snapshot riêng, tránh tab mới ghi đè giá của tab cũ.
        $checkoutToken = bin2hex(random_bytes(32));
        $_SESSION['checkout_tokens'][$userId] = $checkoutToken;
        $previews = $_SESSION['checkout_previews'][$userId] ?? [];
        foreach ($previews as $key => $value) if (!is_array($value) || ($value['issued_at'] ?? 0) < time()-1800) unset($previews[$key]);
        if (count($previews) >= 20) $previews = array_slice($previews, -19, null, true);
        $_SESSION['checkout_previews'][$userId] = $previews;
        $couponCode = $_SESSION['applied_coupon_code'] ?? null;
        $preview = $this->checkoutService->buildCheckoutPreview($userId, is_string($couponCode) ? $couponCode : null);
        if ($preview['pricing']['coupon_error']) $preview['issues'][] = 'Mã giảm giá không còn hợp lệ. Quay lại giỏ để gỡ hoặc đổi mã.';
        $lines = [];
        foreach ($preview['items'] as $item) $lines[(int)$item['variant_id']] = [(int)$item['quantity'], (int)$item['original_unit_price'], (int)$item['unit_price'], (int)$item['product_id'], $item['product_name'], $item['size_name'], $item['color_name']];
        ksort($lines);
        $_SESSION['checkout_previews'][$userId][$checkoutToken] = [
            'issued_at'=>time(), 'token'=>$checkoutToken, 'lines'=>$lines, 'subtotal'=>$preview['pricing']['subtotal'],
            'total'=>$preview['pricing']['total_amount'], 'coupon'=>$couponCode, 'issues'=>$preview['issues'],
        ];
        $oldInput = $_SESSION['checkout_old_input'] ?? [];
        unset($_SESSION['checkout_old_input']);
        $csrfToken = Csrf::token();
        $title = 'Xác nhận đơn COD | Flora';
        $cartCount = ProductModel::cartCount($this->conn, $userId);
        require APP_ROOT . '/app/views/layouts/header.php';
        require APP_ROOT . '/app/views/checkout/index.php';
        require APP_ROOT . '/app/views/layouts/footer.php';
    }
    public function submit(): void
    {
        flora_catalog_post();
        $userId = (int) Auth::user()['id'];
        $token = $_POST['checkout_token'] ?? '';
        if (!is_string($token) || !preg_match('/^[a-f0-9]{64}$/D', $token)) {
            http_response_code(403); exit('Mã xác nhận đơn không hợp lệ.');
        }
        $existing = $this->orderModel->findByCheckoutTokenAndUser($token, $userId);
        $preview = $_SESSION['checkout_previews'][$userId][$token] ?? null;
        if (!$existing && (!is_array($preview) || ($preview['issued_at'] ?? 0)<time()-1800 || !hash_equals($preview['token'], $token))) {
            http_response_code(403); exit('Mã xác nhận không thuộc phiên đặt hàng của bạn.');
        }
        $input = [];
        foreach (['recipient_name','recipient_phone','shipping_address','note','payment_method'] as $key) {
            $value = $_POST[$key] ?? '';
            if (!is_string($value)) { http_response_code(400); exit('Dữ liệu biểu mẫu không hợp lệ.'); }
            $input[$key] = trim($value);
        }
        $input['checkout_token'] = $token;
        $input['coupon_code'] = is_array($preview) && is_string($preview['coupon']) ? $preview['coupon'] : '';
        $input['expected_subtotal'] = $preview['subtotal'] ?? null;
        $input['expected_total'] = $preview['total'] ?? null;
        $input['_expected_lines'] = $preview['lines'] ?? null;
        $_SESSION['checkout_old_input'] = $input;
        try {
            if (!$existing && !empty($preview['issues'])) throw new RuntimeException('Giỏ hàng có vấn đề. Vui lòng kiểm tra lại trước khi đặt.');
            $result = $this->checkoutService->placeOrder($userId, $input);
            $order = $result['order'];
            if (!$result['idempotent']) {
                unset($_SESSION['checkout_previews'][$userId][$token], $_SESSION['checkout_old_input'], $_SESSION['applied_coupon_code']);
                if (($_SESSION['checkout_tokens'][$userId] ?? '') === $token) unset($_SESSION['checkout_tokens'][$userId]);
            } else unset($_SESSION['checkout_old_input']);
            $_SESSION['flash_message'] = ['type'=>'success', 'text'=>$result['idempotent'] ? 'Đơn này đã được ghi nhận trước đó.' : 'Đã tạo đơn COD thành công.'];
            flora_catalog_redirect('checkout_success', ['order_code'=>$order['order_code']]);
        } catch (DomainException | InvalidArgumentException $error) {
            $_SESSION['flash_message'] = ['type'=>'warning','text'=>$error->getMessage()];
        } catch (mysqli_sql_exception $error) {
            error_log('Flora checkout SQL code '.$error->getCode());
            $_SESSION['flash_message'] = ['type'=>'error','text'=>'Chưa tạo được đơn. Vui lòng thử lại.'];
        } catch (RuntimeException $error) {
            $_SESSION['flash_message'] = ['type'=>'error','text'=>$error->getMessage()];
        }
        flora_catalog_redirect('checkout');
    }
    public function success(): void
    {
        $userId = (int) Auth::user()['id'];
        $code = $_GET['order_code'] ?? '';
        if (!is_string($code) || strlen($code)>40) flora_catalog_notFound();
        $order = $this->orderModel->findByCodeAndUser($code, $userId);
        if (!$order) flora_catalog_notFound();
        $items = $this->orderModel->getOrderItems((int)$order['id']);
        $title = 'Đã ghi nhận đơn | Flora';
        $cartCount = ProductModel::cartCount($this->conn, $userId);
        require APP_ROOT . '/app/views/layouts/header.php';
        require APP_ROOT . '/app/views/checkout/success.php';
        require APP_ROOT . '/app/views/layouts/footer.php';
    }
}

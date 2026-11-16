<?php
require_once APP_ROOT . '/app/models/OrderModel.php';
require_once APP_ROOT . '/app/services/OrderService.php';
final class OrderController
{
    private mysqli $conn;
    private OrderModel $orders;
    public function __construct()
    {
        Auth::requireLogin();
        require APP_ROOT . '/database.php';
        $this->conn = $conn;
        $this->orders = new OrderModel($conn);
    }
    public function index(): void
    {
        $userId = (int)Auth::user()['id'];
        $status = $_GET['status'] ?? '';
        $status = is_string($status) && in_array($status,['pending','confirmed','shipping','completed','cancelled'],true) ? $status : '';
        $orders = $this->orders->getOrdersByUser($userId, $status !== '' ? $status : null);
        $title = 'Đơn hàng của tôi | Flora';
        $cartCount = ProductModel::cartCount($this->conn, $userId);
        require APP_ROOT . '/app/views/layouts/header.php';
        require APP_ROOT . '/app/views/orders/index.php';
        require APP_ROOT . '/app/views/layouts/footer.php';
    }
    public function detail(): void
    {
        $userId = (int)Auth::user()['id'];
        $id = $_GET['id'] ?? null;
        if (!is_string($id) || !ctype_digit($id) || (int)$id<1) flora_catalog_notFound();
        $order = $this->orders->findByIdAndUser((int)$id, $userId);
        if (!$order) flora_catalog_notFound();
        $items = $this->orders->getOrderItems((int)$order['id']);
        $history = $this->orders->getOrderStatusHistory((int)$order['id']);
        $cancelOldInput = $_SESSION['cancel_old_input'][(int)$order['id']] ?? [];
        unset($_SESSION['cancel_old_input'][(int)$order['id']]);
        $recipientOldInput = $_SESSION['recipient_old_input'][(int)$order['id']] ?? null;
        unset($_SESSION['recipient_old_input'][(int)$order['id']]);
        $recipientUpdated = false;
        foreach ($history as $event) if (($event['note']??'')===OrderService::RECIPIENT_UPDATE_NOTE) $recipientUpdated=true;
        $cancelReasonOptions = self::cancellationReasons();
        $title = 'Đơn '.$order['order_code'].' | Flora';
        $cartCount = ProductModel::cartCount($this->conn, $userId);
        require APP_ROOT . '/app/views/layouts/header.php';
        require APP_ROOT . '/app/views/orders/detail.php';
        require APP_ROOT . '/app/views/layouts/footer.php';
    }
    public function cancel(): void
    {
        flora_catalog_post();
        try { $id=flora_catalog_id('order_id'); } catch (DomainException) { http_response_code(400); exit('Mã đơn không hợp lệ.'); } $actor=Auth::user();
        // Che mã đơn người khác ở đường dẫn phía khách.
        if (!$this->orders->findByIdAndUser($id,(int)$actor['id'])) flora_catalog_notFound();
        flora_catalog_handle(function () use ($id,$actor) {
            $choice = flora_catalog_text('cancel_reason');
            $other = flora_catalog_text('cancel_reason_other');
            $_SESSION['cancel_old_input'][$id] = ['choice'=>$choice, 'other'=>$other];
            $labels = self::cancellationReasons();
            if ($choice === 'other') {
                if ($other === '' || preg_match('//u', $other)!==1 || preg_match_all('/./us', $other)>500) {
                    throw new DomainException('Vui lòng nhập lý do khác từ 1 đến 500 ký tự.');
                }
                $reason = $other;
            } elseif (isset($labels[$choice])) {
                $reason = $labels[$choice];
            } else {
                throw new DomainException('Vui lòng chọn một lý do hủy đơn hợp lệ.');
            }
            $result=(new OrderService($this->conn))->cancelOrder($id,(int)$actor['id'],$actor['role'],$reason);
            unset($_SESSION['cancel_old_input'][$id]);
            flora_catalog_success($result['message']);
            flora_catalog_redirect('order_detail',['id'=>$id]);
        },'order_detail',['id'=>$id]);
    }
    public static function cancellationReasons(): array
    {
        return [
            'address'=>'Tôi muốn cập nhật địa chỉ/số điện thoại nhận hàng',
            'coupon'=>'Tôi muốn thêm/thay đổi mã giảm giá',
            'size'=>'Tôi muốn thay đổi sản phẩm (kích thước, màu sắc, số lượng...)',
            'payment'=>'Thủ tục thanh toán rắc rối',
            'better'=>'Tôi tìm thấy chỗ mua khác tốt hơn',
            'no_need'=>'Tôi không có nhu cầu mua nữa',
            'other'=>'Tôi không tìm thấy lý do hủy phù hợp',
        ];
    }
    public function updateRecipient(): void
    {
        flora_catalog_post();
        try { $id=flora_catalog_id('order_id'); } catch (DomainException) { http_response_code(400); exit('Mã đơn không hợp lệ.'); }
        $actor=Auth::user();
        if (!$this->orders->findByIdAndUser($id,(int)$actor['id'])) flora_catalog_notFound();
        flora_catalog_handle(function () use ($id,$actor) {
            $name=flora_catalog_text('recipient_name'); $phone=flora_catalog_text('recipient_phone'); $address=flora_catalog_text('shipping_address');
            $_SESSION['recipient_old_input'][$id]=['recipient_name'=>$name,'recipient_phone'=>$phone,'shipping_address'=>$address];
            $result=(new OrderService($this->conn))->updateRecipient($id,(int)$actor['id'],$name,$phone,$address);
            unset($_SESSION['recipient_old_input'][$id]);
            flora_catalog_success($result['message']);
            flora_catalog_redirect('order_detail',['id'=>$id]);
        },'order_detail',['id'=>$id]);
    }
}

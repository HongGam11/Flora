<?php
require_once __DIR__ . '/../models/OrderStatusHistoryModel.php';
require_once __DIR__ . '/../models/PaymentTransactionModel.php';
final class OrderService
{
    public function __construct(private mysqli $db) {}

    public const RECIPIENT_UPDATE_NOTE = 'Khách cập nhật thông tin nhận hàng (1 lần).';

    public function updateRecipient(int $orderId, int $actorId, string $name, string $phone, string $address): array
    {
        $name=trim($name); $phone=trim($phone); $address=trim($address);
        if ($name==='' || preg_match('//u',$name)!==1 || preg_match_all('/./us',$name)>100) throw new DomainException('Họ tên người nhận phải từ 1 đến 100 ký tự.');
        $digits=preg_replace('/\D+/','',$phone);
        if (!preg_match('/^[0-9+\s().-]{8,20}$/D',$phone) || strlen($digits)<8 || strlen($digits)>15) throw new DomainException('Số điện thoại nhận hàng không hợp lệ.');
        if ($address==='' || preg_match('//u',$address)!==1 || preg_match_all('/./us',$address)>500) throw new DomainException('Địa chỉ nhận hàng phải từ 1 đến 500 ký tự.');
        return $this->transaction(function () use ($orderId,$actorId,$name,$phone,$address): array {
            $this->actor($actorId); $order=$this->order($orderId);
            if ((int)$order['user_id']!==$actorId) throw new DomainException('Bạn không có quyền cập nhật đơn này.');
            if ($order['status']!=='pending' || $order['payment_method']!=='cod') throw new DomainException('Chỉ cập nhật thông tin khi đơn COD đang chờ xác nhận.');
            $same=$order['recipient_name']===$name && $order['recipient_phone']===$phone && $order['shipping_address']===$address;
            if ($same) return ['message'=>'Thông tin nhận hàng đã được ghi nhận.','idempotent'=>true];
            $note=self::RECIPIENT_UPDATE_NOTE;
            $stmt=$this->db->prepare("SELECT id FROM order_status_history WHERE order_id=? AND note=? AND old_status='pending' AND new_status='pending' LIMIT 1");
            $stmt->bind_param('is',$orderId,$note); $stmt->execute();
            if ($stmt->get_result()->fetch_assoc()) throw new DomainException('Thông tin nhận hàng của đơn này đã được cập nhật một lần.');
            $stmt=$this->db->prepare('UPDATE orders SET recipient_name=?, recipient_phone=?, shipping_address=? WHERE id=?');
            $stmt->bind_param('sssi',$name,$phone,$address,$orderId); $stmt->execute();
            (new OrderStatusHistoryModel($this->db))->addHistory($orderId,'pending',$note,$actorId,'pending');
            return ['message'=>'Đã cập nhật thông tin nhận hàng. Đơn hàng vẫn được giữ nguyên.','idempotent'=>false];
        });
    }

    /** Vai trò truyền vào không cấp quyền: luôn kiểm tra lại người thao tác trong DB. */
    public function cancelOrder(int $orderId, int $userId, string $userRole, string $reason): array
    {
        $reason = trim($reason);
        if ($reason === '' || preg_match('//u', $reason)!==1 || preg_match_all('/./us', $reason)>500) {
            throw new DomainException('Vui lòng nhập lý do hủy từ 1 đến 500 ký tự.');
        }
        return $this->transaction(function () use ($orderId, $userId, $reason): array {
            $actor = $this->actor($userId);
            $order = $this->order($orderId);
            $staff = in_array($actor['role'], ['admin','staff'], true);
            if (!$staff && ($actor['role']!=='customer' || (int)$order['user_id']!==$userId)) {
                throw new DomainException('Bạn không có quyền hủy đơn này.');
            }
            if ($order['status']==='cancelled') return ['success'=>true,'idempotent'=>true,'message'=>'Đơn đã được hủy trước đó.'];
            $allowed = $staff ? ['pending','confirmed'] : ['pending'];
            if (!in_array($order['status'], $allowed, true)) throw new DomainException('Trạng thái hiện tại không cho phép hủy đơn.');
            if ($order['payment_method']!=='cod' || !in_array($order['payment_status'],['unpaid','not_required'],true)) {
                throw new DomainException('Bản đợt 1 chỉ xử lý hủy đơn COD chưa thanh toán.');
            }
            $stmt=$this->db->prepare('SELECT variant_id, quantity FROM order_items WHERE order_id=? ORDER BY variant_id');
            $stmt->bind_param('i',$orderId); $stmt->execute(); $items=$stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            foreach ($items as $item) {
                $variantId=(int)$item['variant_id']; $qty=(int)$item['quantity'];
                $stmt=$this->db->prepare('SELECT stock FROM product_variants WHERE id=? FOR UPDATE');
                $stmt->bind_param('i',$variantId); $stmt->execute(); $variant=$stmt->get_result()->fetch_assoc();
                if (!$variant || $qty<1 || (int)$variant['stock']>4294967295-$qty) throw new DomainException('Không thể hoàn kho biến thể của đơn.');
                $stmt=$this->db->prepare('UPDATE product_variants SET stock=stock+? WHERE id=?');
                $stmt->bind_param('ii',$qty,$variantId); $stmt->execute();
            }
            // Lượt mã đếm từ orders chưa cancelled, không có cột used_count.
            if ($order['coupon_id']!==null) {
                $coupon=(int)$order['coupon_id'];
                $stmt=$this->db->prepare('SELECT id FROM coupons WHERE id=? FOR UPDATE');
                $stmt->bind_param('i',$coupon); $stmt->execute(); $stmt->get_result()->fetch_assoc();
            }
            $stmt=$this->db->prepare("UPDATE orders SET status='cancelled', cancel_reason=?, cancelled_at=UTC_TIMESTAMP() WHERE id=?");
            $stmt->bind_param('si',$reason,$orderId); $stmt->execute();
            (new OrderStatusHistoryModel($this->db))->addHistory($orderId,'cancelled',$reason,$userId,$order['status']);
            return ['success'=>true,'idempotent'=>false,'message'=>'Đã hủy đơn và hoàn kho.'];
        });
    }

    public function changeStatus(int $orderId, int $actorId, string $newStatus, string $expectedStatus): array
    {
        if (!in_array($newStatus,['confirmed','shipping','completed'],true)) throw new DomainException('Trạng thái yêu cầu không hợp lệ.');
        return $this->transaction(function () use ($orderId,$actorId,$newStatus,$expectedStatus): array {
            $actor=$this->actor($actorId);
            if (!in_array($actor['role'],['admin','staff'],true)) throw new DomainException('Chỉ nhân viên hoặc quản trị viên được xử lý đơn.');
            $order=$this->order($orderId);
            if ($order['payment_method']!=='cod') throw new DomainException('Đợt 1 chỉ xử lý đơn COD.');
            if ($order['status']===$newStatus) return ['success'=>true,'idempotent'=>true,'message'=>'Thao tác đã được ghi nhận trước đó.'];
            $next=['pending'=>'confirmed','confirmed'=>'shipping','shipping'=>'completed'];
            if ($order['status']!==$expectedStatus || ($next[$order['status']]??null)!==$newStatus) {
                throw new DomainException('Đơn đã thay đổi hoặc bước chuyển trạng thái không hợp lệ. Vui lòng tải lại.');
            }
            if (!(((int)$order['total_amount']>0 && $order['payment_status']==='unpaid') || ((int)$order['total_amount']===0 && $order['payment_status']==='not_required'))) {
                throw new DomainException('Trạng thái thanh toán của đơn COD không hợp lệ.');
            }
            if ($newStatus==='completed') {
                if ((int)$order['total_amount']>0) (new PaymentTransactionModel($this->db))->recordCodReceipt($orderId,(int)$order['total_amount'],$actorId);
                $stmt=$this->db->prepare("UPDATE orders SET status='completed', payment_status=IF(total_amount=0,'not_required','paid'), completed_at=UTC_TIMESTAMP() WHERE id=?");
                $stmt->bind_param('i',$orderId);
            } else {
                $stmt=$this->db->prepare('UPDATE orders SET status=? WHERE id=?');
                $stmt->bind_param('si',$newStatus,$orderId);
            }
            $stmt->execute();
            $notes=['confirmed'=>'Nhân viên xác nhận đơn COD.','shipping'=>'Nhân viên bắt đầu giao hàng.','completed'=>'Đã giao hàng và thu đủ tiền COD (mô phỏng).'];
            (new OrderStatusHistoryModel($this->db))->addHistory($orderId,$newStatus,$notes[$newStatus],$actorId,$order['status']);
            return ['success'=>true,'idempotent'=>false,'message'=>'Đã cập nhật trạng thái đơn.'];
        });
    }
    private function actor(int $actorId): array
    {
        $stmt=$this->db->prepare('SELECT id, role, status FROM users WHERE id=? FOR UPDATE');
        $stmt->bind_param('i',$actorId); $stmt->execute(); $actor=$stmt->get_result()->fetch_assoc();
        if (!$actor || $actor['status']!=='active') throw new DomainException('Tài khoản không còn hoạt động.');
        return $actor;
    }
    private function order(int $orderId): array
    {
        $stmt=$this->db->prepare('SELECT * FROM orders WHERE id=? FOR UPDATE');
        $stmt->bind_param('i',$orderId); $stmt->execute(); $order=$stmt->get_result()->fetch_assoc();
        if (!$order) throw new DomainException('Không tìm thấy đơn hàng.');
        return $order;
    }
    private function transaction(callable $action): array
    {
        for ($attempt=1; $attempt<=3; $attempt++) {
            try {
                $this->db->query('SET TRANSACTION ISOLATION LEVEL READ COMMITTED');
                $this->db->begin_transaction();
                $result=$action(); $this->db->commit(); return $result;
            } catch (Throwable $error) {
                $this->db->rollback();
                if ($error instanceof mysqli_sql_exception && in_array((int)$error->getCode(),[1205,1213],true) && $attempt<3) { usleep(50000*$attempt); continue; }
                throw $error;
            }
        }
        throw new RuntimeException('Không thể xử lý đơn.');
    }
}

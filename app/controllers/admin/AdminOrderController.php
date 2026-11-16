<?php
require_once __DIR__ . '/../../services/OrderService.php';
require_once __DIR__ . '/../../models/OrderModel.php';
final class AdminOrderController
{
    private mysqli $db;
    public function __construct()
    {
        Auth::requireRole(['admin','staff']);
        require APP_ROOT . '/database.php'; $this->db=$conn;
    }
    public function index(): void
    {
        $status=$_GET['status']??''; $q=$_GET['q']??'';
        $status=is_string($status) && in_array($status,['pending','confirmed','shipping','completed','cancelled'],true) ? $status : '';
        $q=is_string($q) ? substr(trim($q),0,100) : '';
        $like='%'.$q.'%';
        $stmt=$this->db->prepare("SELECT o.*, u.full_name FROM orders o JOIN users u ON u.id=o.user_id
            WHERE (?='' OR o.status=?) AND (?='' OR o.order_code LIKE ? OR o.recipient_name LIKE ? OR o.recipient_phone LIKE ?)
            ORDER BY o.created_at DESC, o.id DESC LIMIT 200");
        $stmt->bind_param('ssssss',$status,$status,$q,$like,$like,$like); $stmt->execute();
        $orders=$stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        flora_catalog_page('orders/index',compact('orders','status','q'));
    }
    public function detail(): void
    {
        $id=flora_catalog_queryId('id');
        $stmt=$this->db->prepare('SELECT o.*, u.full_name, u.email FROM orders o JOIN users u ON u.id=o.user_id WHERE o.id=?');
        $stmt->bind_param('i',$id); $stmt->execute(); $order=$stmt->get_result()->fetch_assoc();
        if (!$order) flora_catalog_notFound();
        $items=(new OrderModel($this->db))->getOrderItems($id);
        $histories=(new OrderStatusHistoryModel($this->db))->getHistoryByOrder($id);
        $transactions=(new PaymentTransactionModel($this->db))->getTransactionsByOrder($id);
        flora_catalog_page('orders/detail',compact('order','items','histories','transactions'));
    }
    public function updateStatus(): void
    {
        flora_catalog_post();
        try { $id=flora_catalog_id('order_id'); } catch (DomainException) { http_response_code(400); exit('Mã đơn không hợp lệ.'); }
        flora_catalog_handle(function () use ($id) {
            $actor=Auth::user(); $service=new OrderService($this->db);
            $status=flora_catalog_text('status');
            $result=$status==='cancelled'
                ? $service->cancelOrder($id,(int)$actor['id'],$actor['role'],flora_catalog_text('reason'))
                : $service->changeStatus($id,(int)$actor['id'],$status,flora_catalog_text('expected_status'));
            flora_catalog_success($result['message']);
            flora_catalog_redirect('admin_orders_detail',['id'=>$id]);
        },'admin_orders_detail',['id'=>$id]);
    }
}

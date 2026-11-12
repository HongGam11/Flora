<?php
declare(strict_types=1);

require_once APP_ROOT . '/app/models/CouponModel.php';
require_once APP_ROOT . '/app/services/PricingService.php';

/**
 * Phụ trách: TV4 (Hoàng Huy).
 * Controller quản trị mã giảm giá (chỉ role = admin).
 */
final class AdminCouponController
{
    private mysqli $conn;
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
        $this->couponModel = new CouponModel($this->conn);
    }

    public function index(): void
    {
        $this->requireAdminRole();

        $keyword = trim((string) ($_GET['q'] ?? ''));
        $status  = trim((string) ($_GET['status'] ?? ''));
        $coupons = $this->couponModel->findAll($keyword, $status);

        $title     = 'Quản lý mã giảm giá — Quản trị Flora';
        $csrfToken = Csrf::token();
        $flash     = $this->consumeFlash();

        $this->renderAdminView(APP_ROOT . '/app/views/admin/coupons/index.php', [
            'title'     => $title,
            'coupons'   => $coupons,
            'keyword'   => $keyword,
            'status'    => $status,
            'csrfToken' => $csrfToken,
            'flash'     => $flash,
        ]);
    }

    public function create(): void
    {
        $this->requireAdminRole();

        $nowVn   = new DateTimeImmutable('now', new DateTimeZone('Asia/Ho_Chi_Minh'));
        $laterVn = $nowVn->modify('+30 days');

        $formData = [
            'id'                  => null,
            'code'                => '',
            'description'         => '',
            'discount_type'       => 'percent',
            'discount_value'      => '',
            'max_discount_amount' => '',
            'min_order_amount'    => '0',
            'start_at_vn'         => $nowVn->format('Y-m-d\TH:i'),
            'end_at_vn'           => $laterVn->format('Y-m-d\TH:i'),
            'usage_limit'         => '',
            'per_user_limit'      => '',
            'status'              => 'active',
        ];

        $this->renderAdminView(APP_ROOT . '/app/views/admin/coupons/form.php', [
            'title'     => 'Thêm mã giảm giá — Quản trị Flora',
            'isEdit'    => false,
            'formData'  => $formData,
            'errors'    => [],
            'csrfToken' => Csrf::token(),
            'flash'     => $this->consumeFlash(),
        ]);
    }

    public function store(): void
    {
        $this->requireAdminRole();
        $this->requirePostAndCsrf();

        [$errors, $cleanData, $formData] = $this->validateFormInput($_POST, null);

        if (!empty($errors)) {
            $this->renderAdminView(APP_ROOT . '/app/views/admin/coupons/form.php', [
                'title'     => 'Thêm mã giảm giá — Quản trị Flora',
                'isEdit'    => false,
                'formData'  => $formData,
                'errors'    => $errors,
                'csrfToken' => Csrf::token(),
                'flash'     => null,
            ]);
            return;
        }

        $this->couponModel->create($cleanData);
        $this->setFlash('success', 'Đã tạo mã giảm giá ' . $cleanData['code'] . ' thành công.');
        $this->redirect('index.php?route=admin/coupons');
    }

    public function edit(): void
    {
        $this->requireAdminRole();
        $id     = (int) ($_GET['id'] ?? 0);
        $coupon = $this->couponModel->findById($id);

        if (!$coupon) {
            $this->setFlash('error', 'Không tìm thấy mã giảm giá cần sửa.');
            $this->redirect('index.php?route=admin/coupons');
        }

        $formData = [
            'id'                  => (int) $coupon['id'],
            'code'                => (string) $coupon['code'],
            'description'         => (string) ($coupon['description'] ?? ''),
            'discount_type'       => (string) $coupon['discount_type'],
            'discount_value'      => rtrim(rtrim((string) $coupon['discount_value'], '0'), '.'),
            'max_discount_amount' => $coupon['max_discount_amount'] !== null ? (string) $coupon['max_discount_amount'] : '',
            'min_order_amount'    => (string) $coupon['min_order_amount'],
            'start_at_vn'         => self::utcToVnInput((string) $coupon['start_at']),
            'end_at_vn'           => self::utcToVnInput((string) $coupon['end_at']),
            'usage_limit'         => $coupon['usage_limit'] !== null ? (string) $coupon['usage_limit'] : '',
            'per_user_limit'      => $coupon['per_user_limit'] !== null ? (string) $coupon['per_user_limit'] : '',
            'status'              => (string) $coupon['status'],
        ];

        $this->renderAdminView(APP_ROOT . '/app/views/admin/coupons/form.php', [
            'title'     => 'Sửa mã giảm giá ' . $coupon['code'] . ' — Quản trị Flora',
            'isEdit'    => true,
            'formData'  => $formData,
            'errors'    => [],
            'csrfToken' => Csrf::token(),
            'flash'     => $this->consumeFlash(),
        ]);
    }

    public function update(): void
    {
        $this->requireAdminRole();
        $this->requirePostAndCsrf();

        $id     = (int) ($_POST['id'] ?? 0);
        $coupon = $this->couponModel->findById($id);
        if (!$coupon) {
            $this->setFlash('error', 'Không tìm thấy mã giảm giá.');
            $this->redirect('index.php?route=admin/coupons');
        }

        [$errors, $cleanData, $formData] = $this->validateFormInput($_POST, $id);
        $formData['id'] = $id;

        if (!empty($errors)) {
            $this->renderAdminView(APP_ROOT . '/app/views/admin/coupons/form.php', [
                'title'     => 'Sửa mã giảm giá — Quản trị Flora',
                'isEdit'    => true,
                'formData'  => $formData,
                'errors'    => $errors,
                'csrfToken' => Csrf::token(),
                'flash'     => null,
            ]);
            return;
        }

        $this->couponModel->update($id, $cleanData);
        $this->setFlash('success', 'Đã cập nhật mã giảm giá ' . $cleanData['code'] . '.');
        $this->redirect('index.php?route=admin/coupons');
    }

    public function toggleStatus(): void
    {
        $this->requireAdminRole();
        $this->requirePostAndCsrf();

        $id = (int) ($_POST['id'] ?? 0);
        try {
            $newStatus = $this->couponModel->toggleStatus($id);
            $label     = $newStatus === 'active' ? 'kích hoạt' : 'tạm ngưng';
            $this->setFlash('success', "Đã chuyển trạng thái mã giảm giá sang {$label}.");
        } catch (Throwable $e) {
            $this->setFlash('error', $e->getMessage());
        }

        $this->redirect('index.php?route=admin/coupons');
    }

    public function delete(): void
    {
        $this->requireAdminRole();
        $this->requirePostAndCsrf();

        $id = (int) ($_POST['id'] ?? 0);
        try {
            $this->couponModel->deleteIfUnused($id);
            $this->setFlash('success', 'Đã xóa mã giảm giá.');
        } catch (Throwable $e) {
            $this->setFlash('error', $e->getMessage());
        }

        $this->redirect('index.php?route=admin/coupons');
    }

    private function validateFormInput(array $input, ?int $excludeId): array
    {
        $errors = [];

        $code               = CouponModel::normalizeCode((string) ($input['code'] ?? ''));
        $description        = trim((string) ($input['description'] ?? ''));
        $discountType       = trim((string) ($input['discount_type'] ?? ''));
        $discountValueRaw   = trim((string) ($input['discount_value'] ?? ''));
        $maxDiscountRaw     = trim((string) ($input['max_discount_amount'] ?? ''));
        $minOrderRaw        = trim((string) ($input['min_order_amount'] ?? '0'));
        $startAtVn          = trim((string) ($input['start_at'] ?? ''));
        $endAtVn            = trim((string) ($input['end_at'] ?? ''));
        $usageLimitRaw      = trim((string) ($input['usage_limit'] ?? ''));
        $perUserLimitRaw    = trim((string) ($input['per_user_limit'] ?? ''));
        $status             = trim((string) ($input['status'] ?? 'active'));

        $formData = [
            'id'                  => $excludeId,
            'code'                => $code,
            'description'         => $description,
            'discount_type'       => $discountType,
            'discount_value'      => $discountValueRaw,
            'max_discount_amount' => $maxDiscountRaw,
            'min_order_amount'    => $minOrderRaw,
            'start_at_vn'         => $startAtVn,
            'end_at_vn'           => $endAtVn,
            'usage_limit'         => $usageLimitRaw,
            'per_user_limit'      => $perUserLimitRaw,
            'status'              => $status,
        ];

        if ($code === '') {
            $errors[] = 'Vui lòng nhập mã giảm giá.';
        } elseif (mb_strlen($code, 'UTF-8') > 50 || !preg_match('/^[A-Z0-9_-]+$/', $code)) {
            $errors[] = 'Mã giảm giá chỉ gồm chữ in hoa không dấu, số, dấu gạch ngang (-) hoặc gạch dưới (_), tối đa 50 ký tự.';
        } elseif ($this->couponModel->existsCode($code, $excludeId)) {
            $errors[] = "Mã giảm giá '{$code}' đã tồn tại trong hệ thống.";
        }

        if (mb_strlen($description, 'UTF-8') > 255) {
            $errors[] = 'Mô tả tối đa 255 ký tự.';
        }

        if (!in_array($discountType, ['percent', 'fixed'], true)) {
            $errors[] = 'Loại giảm giá phải là phần trăm (percent) hoặc số tiền cố định (fixed).';
        }

        $discountValue     = is_numeric($discountValueRaw) ? (float) $discountValueRaw : 0.0;
        $maxDiscountAmount = null;

        if ($discountValue <= 0) {
            $errors[] = 'Giá trị giảm phải lớn hơn 0.';
        } elseif ($discountType === 'percent') {
            if ($discountValue > 100) {
                $errors[] = 'Mã giảm theo phần trăm không được vượt quá 100%.';
            }
            if ($maxDiscountRaw === '' || !ctype_digit($maxDiscountRaw) || (int) $maxDiscountRaw <= 0) {
                $errors[] = 'Mã giảm theo phần trăm bắt buộc nhập mức giảm tối đa lớn hơn 0đ.';
            } else {
                $maxDiscountAmount = (int) $maxDiscountRaw;
            }
        } elseif ($discountType === 'fixed') {
            if (floor($discountValue) !== $discountValue) {
                $errors[] = 'Mã giảm số tiền cố định phải là số nguyên đồng.';
            }
            $maxDiscountAmount = null;
        }

        if ($minOrderRaw === '' || !ctype_digit($minOrderRaw)) {
            $errors[] = 'Giá trị đơn tối thiểu phải là số nguyên không âm (>= 0).';
            $minOrderAmount = 0;
        } else {
            $minOrderAmount = (int) $minOrderRaw;
        }

        $startAtUtc = self::vnInputToUtc($startAtVn);
        $endAtUtc   = self::vnInputToUtc($endAtVn);
        if ($startAtUtc === null || $endAtUtc === null) {
            $errors[] = 'Thời gian bắt đầu và kết thúc không hợp lệ.';
        } elseif ($endAtUtc <= $startAtUtc) {
            $errors[] = 'Thời gian kết thúc phải sau thời gian bắt đầu.';
        }

        $usageLimit = null;
        if ($usageLimitRaw !== '') {
            if (!ctype_digit($usageLimitRaw) || (int) $usageLimitRaw <= 0) {
                $errors[] = 'Tổng lượt sử dụng phải để trống (không giới hạn) hoặc là số nguyên > 0.';
            } else {
                $usageLimit = (int) $usageLimitRaw;
            }
        }

        $perUserLimit = null;
        if ($perUserLimitRaw !== '') {
            if (!ctype_digit($perUserLimitRaw) || (int) $perUserLimitRaw <= 0) {
                $errors[] = 'Giới hạn lượt mỗi khách phải để trống hoặc là số nguyên > 0.';
            } else {
                $perUserLimit = (int) $perUserLimitRaw;
            }
        }

        if (!in_array($status, ['active', 'inactive'], true)) {
            $errors[] = 'Trạng thái không hợp lệ.';
        }

        $cleanData = [
            'code'                => $code,
            'description'         => $description !== '' ? $description : null,
            'discount_type'       => $discountType,
            'discount_value'      => $discountValue,
            'max_discount_amount' => $maxDiscountAmount,
            'min_order_amount'    => $minOrderAmount,
            'start_at'            => $startAtUtc ?? '',
            'end_at'              => $endAtUtc ?? '',
            'usage_limit'         => $usageLimit,
            'per_user_limit'      => $perUserLimit,
            'status'              => $status,
        ];

        return [$errors, $cleanData, $formData];
    }

    public static function vnInputToUtc(string $vnDateTime): ?string
    {
        if ($vnDateTime === '') {
            return null;
        }
        try {
            $dt = new DateTimeImmutable($vnDateTime, new DateTimeZone('Asia/Ho_Chi_Minh'));
            return $dt->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s');
        } catch (Throwable) {
            return null;
        }
    }

    public static function utcToVnInput(string $utcDateTime): string
    {
        try {
            $dt = new DateTimeImmutable($utcDateTime, new DateTimeZone('UTC'));
            return $dt->setTimezone(new DateTimeZone('Asia/Ho_Chi_Minh'))->format('Y-m-d\TH:i');
        } catch (Throwable) {
            return '';
        }
    }

    public static function formatUtcToVnDisplay(string $utcDateTime): string
    {
        try {
            $dt = new DateTimeImmutable($utcDateTime, new DateTimeZone('UTC'));
            return $dt->setTimezone(new DateTimeZone('Asia/Ho_Chi_Minh'))->format('d/m/Y H:i');
        } catch (Throwable) {
            return $utcDateTime;
        }
    }

    private function requireAdminRole(): int
    {
        $userId = (int) ($_SESSION['user_id'] ?? ($_SESSION['user']['id'] ?? 0));
        if ($userId <= 0) {
            $this->setFlash('warning', 'Vui lòng đăng nhập bằng tài khoản quản trị.');
            $this->redirect('index.php?route=login');
        }

        $stmt = $this->conn->prepare('SELECT id, role, status FROM users WHERE id = ? LIMIT 1');
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$user || $user['status'] !== 'active') {
            session_unset();
            $this->redirect('index.php?route=login');
        }

        if ($user['role'] !== 'admin') {
            http_response_code(403);
            require APP_ROOT . '/app/views/errors/403.php';
            exit;
        }

        return $userId;
    }

    private function requirePostAndCsrf(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            http_response_code(405);
            exit('Phương thức không được phép.');
        }

        $token = $_POST['csrf_token'] ?? null;
        if (!Csrf::verify(is_string($token) ? $token : null)) {
            http_response_code(403);
            exit('Yêu cầu không hợp lệ (CSRF token sai).');
        }
    }

    private function renderAdminView(string $viewFile, array $data): void
    {
        extract($data, EXTR_SKIP);
        $adminHeader = APP_ROOT . '/app/views/layouts/admin_header.php';
        $adminFooter = APP_ROOT . '/app/views/layouts/admin_footer.php';

        if (is_file($adminHeader) && filesize($adminHeader) > 80) {
            require $adminHeader;
            if (is_file(APP_ROOT . '/app/views/layouts/admin_sidebar.php')) {
                require APP_ROOT . '/app/views/layouts/admin_sidebar.php';
            }
            require $viewFile;
            require $adminFooter;
        } else {
            require APP_ROOT . '/app/views/layouts/header.php';
            require $viewFile;
            require APP_ROOT . '/app/views/layouts/footer.php';
        }
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
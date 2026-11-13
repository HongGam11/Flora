<?php
declare(strict_types=1);

/**
 * Phụ trách: TV5.
 * Nghiệp vụ xem trước checkout và tạo đơn hàng trong transaction chuẩn mục 7.1 & 7.2.
 */
final class CheckoutService
{
    public const FREE_SHIP_THRESHOLD = 500000;
    public const STANDARD_SHIPPING_FEE = 30000;
    public const ONLINE_PAYMENT_TTL_MINUTES = 30;
    private const MAX_DEADLOCK_RETRIES = 3;

    private mysqli $conn;
    private OrderModel $orderModel;

    public function __construct(mysqli $conn)
    {
        $this->conn = $conn;
        $this->orderModel = new OrderModel($conn);
    }

    /**
     * Chuẩn bị dữ liệu hiển thị màn hình Checkout (đọc mới nhất từ DB, không khóa).
     */
    public function buildCheckoutPreview(int $userId, ?string $couponCode = null): array
    {
        $userStmt = $this->conn->prepare('SELECT id, full_name, email, phone, address, status FROM users WHERE id = ? LIMIT 1');
        $userStmt->bind_param('i', $userId);
        $userStmt->execute();
        $user = $userStmt->get_result()->fetch_assoc();

        if (!$user || $user['status'] !== 'active') {
            throw new RuntimeException('Tài khoản không tồn tại hoặc đã bị khóa.');
        }

        // Ưu tiên ảnh theo màu; nếu chưa có thì ưu tiên ảnh chung (color_id IS NULL)
        $sql = "SELECT ci.id AS cart_item_id, ci.variant_id, ci.quantity,
                       pv.original_price, pv.stock, pv.status AS variant_status,
                       p.id AS product_id, p.name AS product_name, p.status AS product_status,
                       p.discount_percent, p.discount_start_at, p.discount_end_at,
                       c.id AS category_id, c.parent_id AS category_parent_id, c.status AS category_status,
                       sz.name AS size_name, cl.name AS color_name,
                       COALESCE(
                           (SELECT pi.image_path FROM product_images pi
                            WHERE pi.product_id = p.id AND pi.color_id = pv.color_id
                            ORDER BY pi.is_main DESC, pi.sort_order ASC, pi.id ASC LIMIT 1),
                           (SELECT pi2.image_path FROM product_images pi2
                            WHERE pi2.product_id = p.id
                            ORDER BY (pi2.color_id IS NULL) DESC, pi2.is_main DESC, pi2.sort_order ASC, pi2.id ASC LIMIT 1)
                       ) AS image_path
                FROM carts ct
                JOIN cart_items ci ON ci.cart_id = ct.id
                JOIN product_variants pv ON pv.id = ci.variant_id
                JOIN products p ON p.id = pv.product_id
                JOIN categories c ON c.id = p.category_id
                JOIN sizes sz ON sz.id = pv.size_id
                JOIN colors cl ON cl.id = pv.color_id
                WHERE ct.user_id = ?
                ORDER BY ci.id ASC";

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        if (empty($rows)) {
            return [
                'user' => $user,
                'items' => [],
                'issues' => ['Giỏ hàng của bạn đang trống.'],
                'pricing' => [
                    'subtotal' => 0,
                    'coupon' => null,
                    'coupon_error' => null,
                    'coupon_discount' => 0,
                    'shipping_fee' => 0,
                    'total_amount' => 0,
                    'amount_to_free_ship' => self::FREE_SHIP_THRESHOLD,
                ],
            ];
        }

        $nowUtc = new DateTimeImmutable('now', new DateTimeZone('UTC'));
        $items = [];
        $issues = [];
        $subtotal = 0;

        foreach ($rows as $row) {
            $qty = (int) $row['quantity'];
            $stock = (int) $row['stock'];
            $originalPrice = (int) $row['original_price'];
            $unitPrice = $this->calculateUnitPrice(
                $originalPrice,
                (float) $row['discount_percent'],
                $row['discount_start_at'],
                $row['discount_end_at'],
                $nowUtc
            );
            $lineTotal = $qty * $unitPrice;

            $isAvailable = (
                $row['category_parent_id'] !== null
                && $row['category_status'] === 'active'
                && $row['product_status'] === 'active'
                && $row['variant_status'] === 'active'
                && $qty > 0
                && $stock >= $qty
            );

            if (!$isAvailable) {
                if ($row['category_status'] !== 'active' || $row['product_status'] !== 'active' || $row['variant_status'] !== 'active') {
                    $issues[] = sprintf('Sản phẩm "%s" (%s / %s) hiện đã ngừng kinh doanh.', $row['product_name'], $row['size_name'], $row['color_name']);
                } elseif ($stock < $qty) {
                    $issues[] = sprintf('Sản phẩm "%s" (%s / %s) chỉ còn %d sản phẩm trong kho.', $row['product_name'], $row['size_name'], $row['color_name'], $stock);
                }
            }

            $subtotal += $lineTotal;
            $items[] = [
                'cart_item_id' => (int) $row['cart_item_id'],
                'variant_id' => (int) $row['variant_id'],
                'product_id' => (int) $row['product_id'],
                'product_name' => $row['product_name'],
                'size_name' => $row['size_name'],
                'color_name' => $row['color_name'],
                'image_path' => $row['image_path'] ?: 'assets/images/no-image.svg',
                'quantity' => $qty,
                'stock' => $stock,
                'original_unit_price' => $originalPrice,
                'unit_price' => $unitPrice,
                'line_total' => $lineTotal,
                'is_available' => $isAvailable,
            ];
        }

        $couponResult = $this->evaluateCouponPreview($couponCode, $subtotal, $userId, $nowUtc);
        $couponDiscount = $couponResult['discount'];
        $shippingFee = ($subtotal >= self::FREE_SHIP_THRESHOLD) ? 0 : self::STANDARD_SHIPPING_FEE;
        $totalAmount = $subtotal - $couponDiscount + $shippingFee;
        $amountToFreeShip = max(0, self::FREE_SHIP_THRESHOLD - $subtotal);

        return [
            'user' => $user,
            'items' => $items,
            'issues' => $issues,
            'pricing' => [
                'subtotal' => $subtotal,
                'coupon' => $couponResult['coupon'],
                'coupon_error' => $couponResult['error'],
                'coupon_discount' => $couponDiscount,
                'shipping_fee' => $shippingFee,
                'total_amount' => $totalAmount,
                'amount_to_free_ship' => $amountToFreeShip,
            ],
        ];
    }

    /**
     * Thực thi đặt hàng trong Transaction (mục 7.1 & 7.2).
     * Trả về ['order' => array, 'idempotent' => bool].
     */
    public function placeOrder(int $userId, array $input): array
    {
        $checkoutToken = trim((string) ($input['checkout_token'] ?? ''));
        if (!preg_match('/^[a-f0-9]{64}$/', $checkoutToken)) {
            throw new InvalidArgumentException('Mã phiên đặt hàng (checkout_token) không hợp lệ.');
        }

        // Bước 1: Kiểm tra idempotency trước khi mở transaction
        $existingOrder = $this->orderModel->findByCheckoutTokenAndUser($checkoutToken, $userId);
        if ($existingOrder !== null) {
            return ['order' => $existingOrder, 'idempotent' => true];
        }

        // Validate dữ liệu người nhận
        $recipientName = trim((string) ($input['recipient_name'] ?? ''));
        $recipientPhone = trim((string) ($input['recipient_phone'] ?? ''));
        $shippingAddress = trim((string) ($input['shipping_address'] ?? ''));
        $note = trim((string) ($input['note'] ?? ''));
        $noteValue = ($note === '') ? null : mb_substr($note, 0, 1000);
        $paymentMethod = trim((string) ($input['payment_method'] ?? ''));
        $rawCouponCode = strtoupper(trim((string) ($input['coupon_code'] ?? '')));
        $couponCodeInput = ($rawCouponCode === '') ? null : $rawCouponCode;

        $expectedSubtotal = isset($input['expected_subtotal']) && $input['expected_subtotal'] !== ''
            ? (int) $input['expected_subtotal'] : null;
        $expectedTotal = isset($input['expected_total']) && $input['expected_total'] !== ''
            ? (int) $input['expected_total'] : null;

        if ($recipientName === '' || mb_strlen($recipientName) > 100) {
            throw new InvalidArgumentException('Vui lòng nhập họ tên người nhận hợp lệ (tối đa 100 ký tự).');
        }
        $phoneDigits = preg_replace('/\D+/', '', $recipientPhone);
        if (!preg_match('/^[0-9+\s().-]{8,20}$/', $recipientPhone) || strlen($phoneDigits) < 8 || strlen($phoneDigits) > 15) {
            throw new InvalidArgumentException('Số điện thoại người nhận không hợp lệ (cần từ 8 đến 15 chữ số).');
        }
        if ($shippingAddress === '' || mb_strlen($shippingAddress) > 500) {
            throw new InvalidArgumentException('Vui lòng nhập địa chỉ giao hàng hợp lệ (tối đa 500 ký tự).');
        }
        if (!in_array($paymentMethod, ['cod', 'ewallet', 'paylater'], true)) {
            throw new InvalidArgumentException('Phương thức thanh toán không hợp lệ.');
        }

        $attempt = 0;
        while (true) {
            $attempt++;
            try {
                return $this->executePlaceOrderTransaction(
                    $userId,
                    $checkoutToken,
                    $recipientName,
                    $recipientPhone,
                    $shippingAddress,
                    $noteValue,
                    $paymentMethod,
                    $couponCodeInput,
                    $expectedSubtotal,
                    $expectedTotal
                );
            } catch (mysqli_sql_exception $e) {
                if ($this->conn->errno || $this->inTransactionSafe()) {
                    try {
                        $this->conn->rollback();
                    } catch (Throwable $ignored) {
                    }
                }

                // Duplicate entry (1062): kiểm tra nếu do trùng checkout_token
                if ((int) $e->getCode() === 1062) {
                    $dupOrder = $this->orderModel->findByCheckoutTokenAndUser($checkoutToken, $userId);
                    if ($dupOrder !== null) {
                        return ['order' => $dupOrder, 'idempotent' => true];
                    }
                    throw new RuntimeException('Yêu cầu đặt hàng bị trùng hoặc xung đột dữ liệu. Vui lòng thử lại.');
                }

                // Deadlock (1213) hoặc Lock wait timeout (1205): thử lại tối đa 3 lần
                if (in_array((int) $e->getCode(), [1213, 1205], true) && $attempt < self::MAX_DEADLOCK_RETRIES) {
                    usleep(50000 * $attempt);
                    continue;
                }

                throw $e;
            } catch (Throwable $e) {
                try {
                    $this->conn->rollback();
                } catch (Throwable $ignored) {
                }
                throw $e;
            }
        }
    }

    /**
     * Thực thi 1 lần transaction đặt hàng theo đúng thứ tự khóa mục 7.1 & 7.2.
     */
    private function executePlaceOrderTransaction(
        int $userId,
        string $checkoutToken,
        string $recipientName,
        string $recipientPhone,
        string $shippingAddress,
        ?string $noteValue,
        string $paymentMethod,
        ?string $couponCodeInput,
        ?int $expectedSubtotal,
        ?int $expectedTotal
    ): array {
        $this->conn->query('SET TRANSACTION ISOLATION LEVEL READ COMMITTED');
        $this->conn->begin_transaction();

        // 1. Khóa users theo id
        $userStmt = $this->conn->prepare('SELECT id, status FROM users WHERE id = ? FOR UPDATE');
        $userStmt->bind_param('i', $userId);
        $userStmt->execute();
        $userRow = $userStmt->get_result()->fetch_assoc();
        if (!$userRow || $userRow['status'] !== 'active') {
            throw new RuntimeException('Tài khoản của bạn không hoạt động hoặc đã bị khóa.');
        }

        // 2. Khóa carts và cart_items
        $cartStmt = $this->conn->prepare('SELECT id FROM carts WHERE user_id = ? FOR UPDATE');
        $cartStmt->bind_param('i', $userId);
        $cartStmt->execute();
        $cartRow = $cartStmt->get_result()->fetch_assoc();
        if (!$cartRow) {
            throw new RuntimeException('Giỏ hàng của bạn đang trống.');
        }
        $cartId = (int) $cartRow['id'];

        $ciStmt = $this->conn->prepare('SELECT id, variant_id, quantity FROM cart_items WHERE cart_id = ? ORDER BY variant_id ASC FOR UPDATE');
        $ciStmt->bind_param('i', $cartId);
        $ciStmt->execute();
        $cartItems = $ciStmt->get_result()->fetch_all(MYSQLI_ASSOC);
        if (empty($cartItems)) {
            throw new RuntimeException('Giỏ hàng của bạn đang trống.');
        }

        $qtyByVariant = [];
        $cartItemIds = [];
        foreach ($cartItems as $ci) {
            $vid = (int) $ci['variant_id'];
            $qty = (int) $ci['quantity'];
            if ($qty <= 0) {
                throw new RuntimeException('Số lượng sản phẩm trong giỏ không hợp lệ.');
            }
            $qtyByVariant[$vid] = $qty;
            $cartItemIds[] = (int) $ci['id'];
        }

        // 3. Khóa coupons nếu có mã (chưa kiểm tra min_order_amount ở bước này)
        $lockedCoupon = null;
        if ($couponCodeInput !== null) {
            $cpStmt = $this->conn->prepare('SELECT * FROM coupons WHERE code = ? FOR UPDATE');
            $cpStmt->bind_param('s', $couponCodeInput);
            $cpStmt->execute();
            $lockedCoupon = $cpStmt->get_result()->fetch_assoc();
            if (!$lockedCoupon) {
                throw new RuntimeException('Mã giảm giá không tồn tại.');
            }
        }

        // 4. Khóa categories -> products -> product_variants theo ID tăng dần
        $variantIds = array_keys($qtyByVariant);
        sort($variantIds, SORT_NUMERIC);
        $placeholders = implode(',', array_fill(0, count($variantIds), '?'));
        $types = str_repeat('i', count($variantIds));

        // Đọc ID danh mục và sản phẩm liên quan để khóa đúng phân cấp
        $metaSql = "SELECT pv.id AS variant_id, p.id AS product_id, p.category_id
                    FROM product_variants pv
                    JOIN products p ON p.id = pv.product_id
                    WHERE pv.id IN ($placeholders)";
        $metaStmt = $this->conn->prepare($metaSql);
        $metaStmt->bind_param($types, ...$variantIds);
        $metaStmt->execute();
        $metaRows = $metaStmt->get_result()->fetch_all(MYSQLI_ASSOC);

        if (count($metaRows) !== count($variantIds)) {
            throw new RuntimeException('Một số sản phẩm trong giỏ không còn tồn tại.');
        }

        $categoryIds = array_values(array_unique(array_map(static fn($r) => (int) $r['category_id'], $metaRows)));
        $productIds = array_values(array_unique(array_map(static fn($r) => (int) $r['product_id'], $metaRows)));
        sort($categoryIds, SORT_NUMERIC);
        sort($productIds, SORT_NUMERIC);

        // 4a. Khóa categories
        $catPh = implode(',', array_fill(0, count($categoryIds), '?'));
        $catStmt = $this->conn->prepare("SELECT id, parent_id, status FROM categories WHERE id IN ($catPh) ORDER BY id ASC FOR UPDATE");
        $catStmt->bind_param(str_repeat('i', count($categoryIds)), ...$categoryIds);
        $catStmt->execute();
        $lockedCats = [];
        foreach ($catStmt->get_result()->fetch_all(MYSQLI_ASSOC) as $c) {
            $lockedCats[(int) $c['id']] = $c;
        }

        // 4b. Khóa products
        $prodPh = implode(',', array_fill(0, count($productIds), '?'));
        $prodStmt = $this->conn->prepare("SELECT id, category_id, name, discount_percent, discount_start_at, discount_end_at, status FROM products WHERE id IN ($prodPh) ORDER BY id ASC FOR UPDATE");
        $prodStmt->bind_param(str_repeat('i', count($productIds)), ...$productIds);
        $prodStmt->execute();
        $lockedProds = [];
        foreach ($prodStmt->get_result()->fetch_all(MYSQLI_ASSOC) as $p) {
            $lockedProds[(int) $p['id']] = $p;
        }

        // 4c. Khóa product_variants trực tiếp (không đặt FOR UPDATE lên bảng lookup sizes/colors)
        $varSql = "SELECT id, product_id, size_id, color_id, original_price, stock, status
                   FROM product_variants
                   WHERE id IN ($placeholders)
                   ORDER BY id ASC
                   FOR UPDATE";
        $varStmt = $this->conn->prepare($varSql);
        $varStmt->bind_param($types, ...$variantIds);
        $varStmt->execute();
        $lockedVariants = $varStmt->get_result()->fetch_all(MYSQLI_ASSOC);

        if (count($lockedVariants) !== count($variantIds)) {
            throw new RuntimeException('Dữ liệu biến thể sản phẩm không khớp.');
        }

        // Đọc tên size và color để chụp snapshot nhất quán
        $sizeIds = array_values(array_unique(array_map(static fn($v) => (int) $v['size_id'], $lockedVariants)));
        $colorIds = array_values(array_unique(array_map(static fn($v) => (int) $v['color_id'], $lockedVariants)));

        $szPh = implode(',', array_fill(0, count($sizeIds), '?'));
        $szStmt = $this->conn->prepare("SELECT id, name FROM sizes WHERE id IN ($szPh)");
        $szStmt->bind_param(str_repeat('i', count($sizeIds)), ...$sizeIds);
        $szStmt->execute();
        $sizeNames = [];
        foreach ($szStmt->get_result()->fetch_all(MYSQLI_ASSOC) as $s) {
            $sizeNames[(int) $s['id']] = $s['name'];
        }

        $clPh = implode(',', array_fill(0, count($colorIds), '?'));
        $clStmt = $this->conn->prepare("SELECT id, name FROM colors WHERE id IN ($clPh)");
        $clStmt->bind_param(str_repeat('i', count($colorIds)), ...$colorIds);
        $clStmt->execute();
        $colorNames = [];
        foreach ($clStmt->get_result()->fetch_all(MYSQLI_ASSOC) as $cl) {
            $colorNames[(int) $cl['id']] = $cl['name'];
        }

        // 5. Kiểm tra điều kiện bán & tính unit_price, subtotal tại thời điểm đặt (UTC)
        $nowUtc = new DateTimeImmutable('now', new DateTimeZone('UTC'));
        $nowUtcStr = $nowUtc->format('Y-m-d H:i:s');
        $subtotal = 0;
        $orderSnapshots = [];

        foreach ($lockedVariants as $v) {
            $vid = (int) $v['id'];
            $pid = (int) $v['product_id'];
            $sizeName = $sizeNames[(int) $v['size_id']] ?? '';
            $colorName = $colorNames[(int) $v['color_id']] ?? '';
            $qty = $qtyByVariant[$vid];
            $prod = $lockedProds[$pid] ?? null;
            $cat = $prod ? ($lockedCats[(int) $prod['category_id']] ?? null) : null;

            if (!$prod || !$cat || $cat['parent_id'] === null || $cat['status'] !== 'active' || $prod['status'] !== 'active' || $v['status'] !== 'active') {
                $pName = $prod['name'] ?? ('#' . $vid);
                throw new RuntimeException("Sản phẩm \"{$pName}\" ({$sizeName} / {$colorName}) hiện đã ngừng kinh doanh.");
            }

            if ((int) $v['stock'] < $qty) {
                throw new RuntimeException("Sản phẩm \"{$prod['name']}\" ({$sizeName} / {$colorName}) không đủ tồn kho (còn {$v['stock']}).");
            }

            $originalPrice = (int) $v['original_price'];
            $unitPrice = $this->calculateUnitPrice(
                $originalPrice,
                (float) $prod['discount_percent'],
                $prod['discount_start_at'],
                $prod['discount_end_at'],
                $nowUtc
            );

            $subtotal += ($qty * $unitPrice);
            $orderSnapshots[] = [
                'variant_id' => $vid,
                'product_name' => $prod['name'],
                'size_name' => $sizeName,
                'color_name' => $colorName,
                'quantity' => $qty,
                'original_unit_price' => $originalPrice,
                'unit_price' => $unitPrice,
            ];
        }

        // 6. Kiểm tra hiệu lực và giới hạn lượt mã giảm giá (đang giữ khóa coupon)
        $couponId = null;
        $couponCode = null;
        $couponDiscount = 0;

        if ($lockedCoupon !== null) {
            $this->validateLockedCoupon($lockedCoupon, $subtotal, $userId, $nowUtc);
            $couponId = (int) $lockedCoupon['id'];
            $couponCode = $lockedCoupon['code'];
            $couponDiscount = $this->calculateCouponDiscount($lockedCoupon, $subtotal);
        }

        $shippingFee = ($subtotal >= self::FREE_SHIP_THRESHOLD) ? 0 : self::STANDARD_SHIPPING_FEE;
        $totalAmount = $subtotal - $couponDiscount + $shippingFee;

        // 7. Phát hiện giá/tổng tiền thay đổi trước xác nhận cuối (Task E11 & T12)
        if ($expectedSubtotal !== null && $expectedSubtotal !== $subtotal) {
            throw new DomainException('Giá sản phẩm vừa được cập nhật. Vui lòng kiểm tra lại giỏ hàng và tổng thanh toán mới trước khi đặt.');
        }
        if ($expectedTotal !== null && $expectedTotal !== $totalAmount) {
            throw new DomainException('Tổng tiền thanh toán đã thay đổi so với màn hình xác nhận trước đó. Vui lòng kiểm tra lại và xác nhận đặt hàng.');
        }

        // 8. Chốt trạng thái thanh toán & hạn thanh toán theo đúng CHECK constraint SQL
        if ($totalAmount === 0) {
            $paymentStatus = 'not_required';
            $paymentExpiresAt = null;
        } else {
            $paymentStatus = 'unpaid';
            $paymentExpiresAt = ($paymentMethod !== 'cod')
                ? $nowUtc->modify('+' . self::ONLINE_PAYMENT_TTL_MINUTES . ' minutes')->format('Y-m-d H:i:s')
                : null;
        }

        $orderCode = $this->generateUniqueOrderCode();
        $orderId = $this->orderModel->createOrder([
            'order_code' => $orderCode,
            'checkout_token' => $checkoutToken,
            'user_id' => $userId,
            'recipient_name' => $recipientName,
            'recipient_phone' => $recipientPhone,
            'shipping_address' => $shippingAddress,
            'note' => $noteValue,
            'subtotal' => $subtotal,
            'coupon_id' => $couponId,
            'coupon_code' => $couponCode,
            'coupon_discount' => $couponDiscount,
            'shipping_fee' => $shippingFee,
            'total_amount' => $totalAmount,
            'payment_method' => $paymentMethod,
            'payment_status' => $paymentStatus,
            'status' => 'pending',
            'payment_expires_at' => $paymentExpiresAt,
            'created_at' => $nowUtcStr,
            'updated_at' => $nowUtcStr,
        ]);

        // Tạo order_items và trừ tồn kho có điều kiện
        $deductStmt = $this->conn->prepare('UPDATE product_variants SET stock = stock - ? WHERE id = ? AND stock >= ?');
        foreach ($orderSnapshots as $snap) {
            $snap['order_id'] = $orderId;
            $this->orderModel->createOrderItem($snap);

            $qty = $snap['quantity'];
            $vid = $snap['variant_id'];
            $deductStmt->bind_param('iii', $qty, $vid, $qty);
            $deductStmt->execute();
            if ($deductStmt->affected_rows !== 1) {
                throw new RuntimeException("Sản phẩm \"{$snap['product_name']}\" vừa hết hàng. Vui lòng thử lại.");
            }
        }

        // Ghi lịch sử trạng thái khởi tạo (old_status = NULL -> new_status = 'pending')
        $histNote = ($totalAmount === 0)
            ? 'Khách hàng tạo đơn hàng (đơn 0đ không cần thanh toán).'
            : 'Khách hàng tạo đơn hàng mới.';
        $histStmt = $this->conn->prepare(
            'INSERT INTO order_status_history (order_id, changed_by, old_status, new_status, note, created_at)
             VALUES (?, ?, NULL, ?, ?, ?)'
        );
        $newStatus = 'pending';
        $histStmt->bind_param('iisss', $orderId, $userId, $newStatus, $histNote, $nowUtcStr);
        $histStmt->execute();

        // Xóa các dòng giỏ hàng đã đặt trong cùng transaction
        $ciPh = implode(',', array_fill(0, count($cartItemIds), '?'));
        $delCiStmt = $this->conn->prepare("DELETE FROM cart_items WHERE id IN ($ciPh) AND cart_id = ?");
        $delParams = array_merge($cartItemIds, [$cartId]);
        $delCiStmt->bind_param(str_repeat('i', count($delParams)), ...$delParams);
        $delCiStmt->execute();

        // 9. Commit toàn bộ transaction
        $this->conn->commit();

        $createdOrder = $this->orderModel->findByIdAndUser($orderId, $userId);
        return ['order' => $createdOrder, 'idempotent' => false];
    }

    /**
     * Tính đơn giá bán hiện hành (làm tròn 0,5 lên).
     */
    public function calculateUnitPrice(
        int $originalPrice,
        float $discountPercent,
        ?string $startAt,
        ?string $endAt,
        DateTimeImmutable $nowUtc
    ): int {
        if ($discountPercent <= 0 || $startAt === null || $endAt === null) {
            return $originalPrice;
        }

        $utc = new DateTimeZone('UTC');
        $start = new DateTimeImmutable($startAt, $utc);
        $end = new DateTimeImmutable($endAt, $utc);

        if ($start <= $nowUtc && $nowUtc < $end) {
            return (int) round($originalPrice * (1 - $discountPercent / 100), 0, PHP_ROUND_HALF_UP);
        }

        return $originalPrice;
    }

    /**
     * Đánh giá mã giảm giá ở bước preview (không khóa).
     */
    private function evaluateCouponPreview(?string $couponCode, int $subtotal, int $userId, DateTimeImmutable $nowUtc): array
    {
        if ($couponCode === null || trim($couponCode) === '') {
            return ['coupon' => null, 'discount' => 0, 'error' => null];
        }

        $code = strtoupper(trim($couponCode));
        $stmt = $this->conn->prepare('SELECT * FROM coupons WHERE code = ? LIMIT 1');
        $stmt->bind_param('s', $code);
        $stmt->execute();
        $coupon = $stmt->get_result()->fetch_assoc();

        if (!$coupon) {
            return ['coupon' => null, 'discount' => 0, 'error' => 'Mã giảm giá không tồn tại.'];
        }

        try {
            $this->validateLockedCoupon($coupon, $subtotal, $userId, $nowUtc);
            $discount = $this->calculateCouponDiscount($coupon, $subtotal);
            return ['coupon' => $coupon, 'discount' => $discount, 'error' => null];
        } catch (RuntimeException $e) {
            return ['coupon' => $coupon, 'discount' => 0, 'error' => $e->getMessage()];
        }
    }

    /**
     * Kiểm tra điều kiện hợp lệ của coupon (dùng cả khi preview và khi đang giữ khóa).
     */
    private function validateLockedCoupon(array $coupon, int $subtotal, int $userId, DateTimeImmutable $nowUtc): void
    {
        if ($coupon['status'] !== 'active') {
            throw new RuntimeException('Mã giảm giá hiện đang tạm ngưng áp dụng.');
        }

        $utc = new DateTimeZone('UTC');
        $startAt = new DateTimeImmutable($coupon['start_at'], $utc);
        $endAt = new DateTimeImmutable($coupon['end_at'], $utc);

        if ($nowUtc < $startAt) {
            throw new RuntimeException('Mã giảm giá chưa đến thời gian áp dụng.');
        }
        if ($nowUtc >= $endAt) {
            throw new RuntimeException('Mã giảm giá đã hết hạn.');
        }

        $minOrder = (int) $coupon['min_order_amount'];
        if ($subtotal < $minOrder) {
            throw new RuntimeException('Đơn hàng cần đạt tối thiểu ' . number_format($minOrder, 0, ',', '.') . 'đ tiền hàng để dùng mã này.');
        }

        $couponId = (int) $coupon['id'];
        if ($coupon['usage_limit'] !== null) {
            $totalUsed = $this->orderModel->countCouponUsage($couponId);
            if ($totalUsed >= (int) $coupon['usage_limit']) {
                throw new RuntimeException('Mã giảm giá đã hết lượt sử dụng.');
            }
        }

        if ($coupon['per_user_limit'] !== null) {
            $userUsed = $this->orderModel->countCouponUsageByUser($couponId, $userId);
            if ($userUsed >= (int) $coupon['per_user_limit']) {
                throw new RuntimeException('Bạn đã dùng hết số lượt cho phép của mã giảm giá này.');
            }
        }
    }

    /**
     * Tính mức giảm thực tế của coupon (tối đa bằng subtotal, không giảm ship).
     */
    public function calculateCouponDiscount(array $coupon, int $subtotal): int
    {
        if ($subtotal <= 0) {
            return 0;
        }

        if ($coupon['discount_type'] === 'fixed') {
            return min((int) $coupon['discount_value'], $subtotal);
        }

        $percentDiscount = (int) round($subtotal * ((float) $coupon['discount_value'] / 100), 0, PHP_ROUND_HALF_UP);
        $maxDiscount = (int) $coupon['max_discount_amount'];
        return min($percentDiscount, $maxDiscount, $subtotal);
    }

    /**
     * Sinh mã đơn hàng duy nhất dạng FLORA + YYMMDD + 6 ký tự hex hoa.
     */
    private function generateUniqueOrderCode(): string
    {
        for ($i = 0; $i < 5; $i++) {
            $code = 'FLORA' . gmdate('ymd') . strtoupper(bin2hex(random_bytes(3)));
            $stmt = $this->conn->prepare('SELECT id FROM orders WHERE order_code = ? LIMIT 1');
            $stmt->bind_param('s', $code);
            $stmt->execute();
            if (!$stmt->get_result()->fetch_assoc()) {
                return $code;
            }
        }
        return 'FLORA' . gmdate('ymdHis') . strtoupper(bin2hex(random_bytes(2)));
    }

    private function inTransactionSafe(): bool
    {
        $res = $this->conn->query('SELECT @@in_transaction AS in_tx');
        $row = $res ? $res->fetch_assoc() : null;
        return isset($row['in_tx']) && (int) $row['in_tx'] === 1;
    }
}
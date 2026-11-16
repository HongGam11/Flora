<?php
declare(strict_types=1);

/**
 * Phụ trách: TV4 phối hợp TV5.
 * Model quản lý giỏ hàng (carts và cart_items).
 */
final class CartModel
{
    private mysqli $conn;

    public function __construct(mysqli $conn)
    {
        $this->conn = $conn;
    }

    public function getOrCreateCartId(int $userId, bool $forUpdate = false): int
    {
        $sql  = 'SELECT id FROM carts WHERE user_id = ?' . ($forUpdate ? ' FOR UPDATE' : '');
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($row) {
            return (int) $row['id'];
        }

        $insert = $this->conn->prepare('INSERT INTO carts (user_id) VALUES (?)');
        $insert->bind_param('i', $userId);
        $insert->execute();
        $cartId = (int) $insert->insert_id;
        $insert->close();

        return $cartId;
    }

    public function getCartItemsByUserId(int $userId, bool $forUpdate = false): array
    {
        if ($forUpdate) {
            $this->lockCartResourcesInOrder($userId);
        }

        $sql = "
            SELECT
                ci.id                  AS cart_item_id,
                ci.cart_id             AS cart_id,
                ci.variant_id          AS variant_id,
                ci.quantity            AS quantity,
                ci.created_at          AS added_at,
                pv.product_id          AS product_id,
                pv.size_id             AS size_id,
                pv.color_id            AS color_id,
                pv.original_price      AS original_price,
                pv.stock               AS stock,
                pv.status              AS variant_status,
                p.name                 AS product_name,
                p.discount_percent     AS discount_percent,
                p.discount_start_at    AS discount_start_at,
                p.discount_end_at      AS discount_end_at,
                p.status               AS product_status,
                cat.id                 AS category_id,
                cat.name               AS category_name,
                cat.status             AS category_status,
                root_cat.id            AS root_category_id,
                root_cat.status        AS root_category_status,
                sz.name                AS size_name,
                col.name               AS color_name,
                col.hex_code           AS color_hex,
                COALESCE(
                    (SELECT pi1.image_path
                     FROM product_images pi1
                     WHERE pi1.product_id = p.id AND pi1.color_id = pv.color_id
                     ORDER BY pi1.is_main DESC, pi1.sort_order ASC, pi1.id ASC
                     LIMIT 1),
                    (SELECT pi2.image_path
                     FROM product_images pi2
                     WHERE pi2.product_id = p.id AND pi2.color_id IS NULL
                     ORDER BY pi2.is_main DESC, pi2.sort_order ASC, pi2.id ASC
                     LIMIT 1),
                    'assets/images/no-image.svg'
                ) AS image_path
            FROM carts c
            JOIN cart_items ci            ON ci.cart_id = c.id
            JOIN product_variants pv      ON pv.id = ci.variant_id
            JOIN products p               ON p.id = pv.product_id
            JOIN categories cat           ON cat.id = p.category_id
            LEFT JOIN categories root_cat ON root_cat.id = cat.parent_id
            JOIN sizes sz                 ON sz.id = pv.size_id
            JOIN colors col               ON col.id = pv.color_id
            WHERE c.user_id = ?
            ORDER BY ci.id ASC
        ";

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $result = $stmt->get_result();

        $items = [];
        while ($row = $result->fetch_assoc()) {
            $qty   = (int) $row['quantity'];
            $stock = (int) $row['stock'];

            $isCategoryActive = ($row['root_category_id'] !== null)
                && ($row['root_category_status'] === 'active')
                && ($row['category_status'] === 'active');
            $isProductActive  = ($row['product_status'] === 'active');
            $isVariantActive  = ($row['variant_status'] === 'active');

            $unavailableReason = null;
            if (!$isCategoryActive || !$isProductActive || !$isVariantActive) {
                $unavailableReason = 'Sản phẩm hoặc phân loại này hiện đã ngừng bán.';
            } elseif ($stock <= 0) {
                $unavailableReason = 'Sản phẩm tạm hết hàng.';
            } elseif ($qty > $stock) {
                $unavailableReason = "Số lượng vượt tồn kho (hiện còn {$stock}).";
            }

            $row['cart_item_id']       = (int) $row['cart_item_id'];
            $row['cart_id']            = (int) $row['cart_id'];
            $row['variant_id']         = (int) $row['variant_id'];
            $row['quantity']           = $qty;
            $row['product_id']         = (int) $row['product_id'];
            $row['size_id']            = (int) $row['size_id'];
            $row['color_id']           = (int) $row['color_id'];
            $row['original_price']     = (int) $row['original_price'];
            $row['stock']              = $stock;
            $row['is_purchasable']     = ($unavailableReason === null);
            $row['unavailable_reason'] = $unavailableReason;

            $items[] = $row;
        }
        $stmt->close();

        return $items;
    }

    public function addItem(int $userId, int $variantId, int $quantity): array
    {
        if ($variantId <= 0 || $quantity <= 0) {
            throw new InvalidArgumentException('Biến thể hoặc số lượng không hợp lệ.');
        }

        return $this->runWithDeadlockRetry(function () use ($userId, $variantId, $quantity): array {
            $this->conn->begin_transaction();
            try {
                $this->lockAndVerifyActiveUser($userId);

                $cartId = $this->getOrCreateCartId($userId, true);
                $stmtCi = $this->conn->prepare('SELECT id, variant_id, quantity FROM cart_items WHERE cart_id = ? ORDER BY id ASC FOR UPDATE');
                $stmtCi->bind_param('i', $cartId);
                $stmtCi->execute();
                $existingItem = null;
                $resCi = $stmtCi->get_result();
                while ($r = $resCi->fetch_assoc()) {
                    if ((int) $r['variant_id'] === $variantId) {
                        $existingItem = $r;
                    }
                }
                $stmtCi->close();

                $metaStmt = $this->conn->prepare('
                    SELECT pv.product_id, p.category_id, c.parent_id
                    FROM product_variants pv
                    JOIN products p ON p.id = pv.product_id
                    JOIN categories c ON c.id = p.category_id
                    WHERE pv.id = ?
                ');
                $metaStmt->bind_param('i', $variantId);
                $metaStmt->execute();
                $meta = $metaStmt->get_result()->fetch_assoc();
                $metaStmt->close();

                if (!$meta || $meta['parent_id'] === null) {
                    throw new RuntimeException('Biến thể sản phẩm không tồn tại hoặc danh mục không hợp lệ.');
                }

                $catIds = array_unique([(int) $meta['parent_id'], (int) $meta['category_id']]);
                sort($catIds, SORT_NUMERIC);
                $this->lockRowsByIds('categories', $catIds);
                $this->lockRowsByIds('products', [(int) $meta['product_id']]);
                $this->lockRowsByIds('product_variants', [$variantId]);

                $checkStmt = $this->conn->prepare('
                    SELECT pv.id, pv.product_id, pv.stock, pv.status AS variant_status,
                           p.status AS product_status,
                           c.status AS category_status,
                           rc.status AS root_status
                    FROM product_variants pv
                    JOIN products p ON p.id = pv.product_id
                    JOIN categories c ON c.id = p.category_id
                    JOIN categories rc ON rc.id = c.parent_id
                    WHERE pv.id = ?
                ');
                $checkStmt->bind_param('i', $variantId);
                $checkStmt->execute();
                $v = $checkStmt->get_result()->fetch_assoc();
                $checkStmt->close();

                if (
                    !$v
                    || $v['root_status'] !== 'active'
                    || $v['category_status'] !== 'active'
                    || $v['product_status'] !== 'active'
                    || $v['variant_status'] !== 'active'
                ) {
                    throw new RuntimeException('Sản phẩm hoặc phân loại này hiện không thể mua.');
                }

                $stock    = (int) $v['stock'];
                $oldQty   = $existingItem ? (int) $existingItem['quantity'] : 0;
                $finalQty = $oldQty + $quantity;

                if ($stock <= 0) {
                    throw new RuntimeException('Sản phẩm đã hết hàng.');
                }
                if ($finalQty > $stock) {
                    throw new RuntimeException("Số lượng trong giỏ vượt quá tồn kho hiện tại (tối đa {$stock} sản phẩm).");
                }

                if ($existingItem) {
                    $itemId = (int) $existingItem['id'];
                    $upd = $this->conn->prepare('UPDATE cart_items SET quantity = ? WHERE id = ?');
                    $upd->bind_param('ii', $finalQty, $itemId);
                    $upd->execute();
                    $upd->close();
                } else {
                    $ins = $this->conn->prepare('INSERT INTO cart_items (cart_id, variant_id, quantity) VALUES (?, ?, ?)');
                    $ins->bind_param('iii', $cartId, $variantId, $finalQty);
                    $ins->execute();
                    $ins->close();
                }

                $touch = $this->conn->prepare('UPDATE carts SET updated_at = UTC_TIMESTAMP() WHERE id = ?');
                $touch->bind_param('i', $cartId);
                $touch->execute();
                $touch->close();

                $this->conn->commit();

                return [
                    'product_id' => (int) $v['product_id'],
                    'variant_id' => $variantId,
                    'quantity'   => $finalQty,
                ];
            } catch (Throwable $e) {
                $this->conn->rollback();
                throw $e;
            }
        });
    }

    public function updateItemQuantity(int $userId, int $cartItemId, int $quantity): void
    {
        if ($cartItemId <= 0 || $quantity <= 0) {
            throw new InvalidArgumentException('Số lượng cập nhật phải lớn hơn 0.');
        }

        $this->runWithDeadlockRetry(function () use ($userId, $cartItemId, $quantity): void {
            $this->conn->begin_transaction();
            try {
                $this->lockAndVerifyActiveUser($userId);

                $stmtCart = $this->conn->prepare('SELECT id FROM carts WHERE user_id = ? FOR UPDATE');
                $stmtCart->bind_param('i', $userId);
                $stmtCart->execute();
                $cart = $stmtCart->get_result()->fetch_assoc();
                $stmtCart->close();

                if (!$cart) {
                    throw new RuntimeException('Không tìm thấy giỏ hàng.');
                }
                $cartId = (int) $cart['id'];

                $stmtItems = $this->conn->prepare('SELECT id, variant_id FROM cart_items WHERE cart_id = ? ORDER BY id ASC FOR UPDATE');
                $stmtItems->bind_param('i', $cartId);
                $stmtItems->execute();
                $variantId = null;
                $res = $stmtItems->get_result();
                while ($r = $res->fetch_assoc()) {
                    if ((int) $r['id'] === $cartItemId) {
                        $variantId = (int) $r['variant_id'];
                    }
                }
                $stmtItems->close();

                if ($variantId === null) {
                    throw new RuntimeException('Mặt hàng không tồn tại hoặc không thuộc giỏ hàng của bạn.');
                }

                $metaStmt = $this->conn->prepare('
                    SELECT pv.product_id, p.category_id, c.parent_id
                    FROM product_variants pv
                    JOIN products p ON p.id = pv.product_id
                    JOIN categories c ON c.id = p.category_id
                    WHERE pv.id = ?
                ');
                $metaStmt->bind_param('i', $variantId);
                $metaStmt->execute();
                $meta = $metaStmt->get_result()->fetch_assoc();
                $metaStmt->close();

                if (!$meta || $meta['parent_id'] === null) {
                    throw new RuntimeException('Biến thể sản phẩm không hợp lệ.');
                }

                $catIds = array_unique([(int) $meta['parent_id'], (int) $meta['category_id']]);
                sort($catIds, SORT_NUMERIC);
                $this->lockRowsByIds('categories', $catIds);
                $this->lockRowsByIds('products', [(int) $meta['product_id']]);
                $this->lockRowsByIds('product_variants', [$variantId]);

                $checkStmt = $this->conn->prepare('
                    SELECT pv.stock, pv.status AS variant_status,
                           p.status AS product_status,
                           c.status AS category_status,
                           rc.status AS root_status
                    FROM product_variants pv
                    JOIN products p ON p.id = pv.product_id
                    JOIN categories c ON c.id = p.category_id
                    JOIN categories rc ON rc.id = c.parent_id
                    WHERE pv.id = ?
                ');
                $checkStmt->bind_param('i', $variantId);
                $checkStmt->execute();
                $v = $checkStmt->get_result()->fetch_assoc();
                $checkStmt->close();

                if (
                    !$v
                    || $v['root_status'] !== 'active'
                    || $v['category_status'] !== 'active'
                    || $v['product_status'] !== 'active'
                    || $v['variant_status'] !== 'active'
                ) {
                    throw new RuntimeException('Sản phẩm này hiện đã ngừng bán, vui lòng xóa khỏi giỏ.');
                }

                $stock = (int) $v['stock'];
                if ($quantity > $stock) {
                    throw new RuntimeException("Số lượng yêu cầu vượt tồn kho hiện tại (còn {$stock} sản phẩm).");
                }

                $upd = $this->conn->prepare('UPDATE cart_items SET quantity = ? WHERE id = ? AND cart_id = ?');
                $upd->bind_param('iii', $quantity, $cartItemId, $cartId);
                $upd->execute();
                $upd->close();

                $this->conn->commit();
            } catch (Throwable $e) {
                $this->conn->rollback();
                throw $e;
            }
        });
    }

    public function removeItem(int $userId, int $cartItemId): void
    {
        if ($cartItemId <= 0) {
            throw new InvalidArgumentException('Dòng giỏ hàng không hợp lệ.');
        }

        $this->runWithDeadlockRetry(function () use ($userId, $cartItemId): void {
            $this->conn->begin_transaction();
            try {
                $this->lockAndVerifyActiveUser($userId);

                $stmtCart = $this->conn->prepare('SELECT id FROM carts WHERE user_id = ? FOR UPDATE');
                $stmtCart->bind_param('i', $userId);
                $stmtCart->execute();
                $cart = $stmtCart->get_result()->fetch_assoc();
                $stmtCart->close();

                if (!$cart) {
                    throw new RuntimeException('Không tìm thấy giỏ hàng.');
                }
                $cartId = (int) $cart['id'];

                $del = $this->conn->prepare('DELETE FROM cart_items WHERE id = ? AND cart_id = ?');
                $del->bind_param('ii', $cartItemId, $cartId);
                $del->execute();
                $affected = $del->affected_rows;
                $del->close();

                if ($affected <= 0) {
                    throw new RuntimeException('Mặt hàng không tồn tại hoặc không thuộc giỏ hàng của bạn.');
                }

                $this->conn->commit();
            } catch (Throwable $e) {
                $this->conn->rollback();
                throw $e;
            }
        });
    }

    public function removePurchasedVariants(int $userId, array $variantIds): void
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $variantIds), static fn(int $v): bool => $v > 0)));
        if (empty($ids)) {
            return;
        }

        $stmt = $this->conn->prepare('SELECT id FROM carts WHERE user_id = ?');
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $cart = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$cart) {
            return;
        }

        $cartId       = (int) $cart['id'];
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $types        = 'i' . str_repeat('i', count($ids));
        $params       = array_merge([$cartId], $ids);

        $del = $this->conn->prepare("DELETE FROM cart_items WHERE cart_id = ? AND variant_id IN ({$placeholders})");
        $del->bind_param($types, ...$params);
        $del->execute();
        $del->close();
    }

    public function countTotalQuantity(int $userId): int
    {
        $stmt = $this->conn->prepare('
            SELECT COALESCE(SUM(ci.quantity), 0) AS total_qty
            FROM carts c
            JOIN cart_items ci ON ci.cart_id = c.id
            WHERE c.user_id = ?
        ');
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return (int) ($row['total_qty'] ?? 0);
    }

    private function lockCartResourcesInOrder(int $userId): void
    {
        $stmtCart = $this->conn->prepare('SELECT id FROM carts WHERE user_id = ? FOR UPDATE');
        $stmtCart->bind_param('i', $userId);
        $stmtCart->execute();
        $cart = $stmtCart->get_result()->fetch_assoc();
        $stmtCart->close();

        if (!$cart) {
            return;
        }
        $cartId = (int) $cart['id'];

        $stmtCi = $this->conn->prepare('SELECT id, variant_id FROM cart_items WHERE cart_id = ? ORDER BY id ASC FOR UPDATE');
        $stmtCi->bind_param('i', $cartId);
        $stmtCi->execute();
        $variantIds = [];
        $resCi = $stmtCi->get_result();
        while ($r = $resCi->fetch_assoc()) {
            $variantIds[] = (int) $r['variant_id'];
        }
        $stmtCi->close();

        if (empty($variantIds)) {
            return;
        }

        $variantIds   = array_values(array_unique($variantIds));
        sort($variantIds, SORT_NUMERIC);
        $placeholders = implode(',', array_fill(0, count($variantIds), '?'));
        $types        = str_repeat('i', count($variantIds));

        $metaStmt = $this->conn->prepare("
            SELECT pv.product_id, p.category_id, c.parent_id
            FROM product_variants pv
            JOIN products p ON p.id = pv.product_id
            JOIN categories c ON c.id = p.category_id
            WHERE pv.id IN ({$placeholders})
        ");
        $metaStmt->bind_param($types, ...$variantIds);
        $metaStmt->execute();
        $resMeta = $metaStmt->get_result();

        $categoryIds = [];
        $productIds  = [];
        while ($m = $resMeta->fetch_assoc()) {
            if ($m['parent_id'] !== null) {
                $categoryIds[] = (int) $m['parent_id'];
            }
            $categoryIds[] = (int) $m['category_id'];
            $productIds[]  = (int) $m['product_id'];
        }
        $metaStmt->close();

        $categoryIds = array_values(array_unique($categoryIds));
        sort($categoryIds, SORT_NUMERIC);
        $productIds = array_values(array_unique($productIds));
        sort($productIds, SORT_NUMERIC);

        $this->lockRowsByIds('categories', $categoryIds);
        $this->lockRowsByIds('products', $productIds);
        $this->lockRowsByIds('product_variants', $variantIds);
    }

    private function lockAndVerifyActiveUser(int $userId): void
    {
        $stmt = $this->conn->prepare('SELECT id, status FROM users WHERE id = ? FOR UPDATE');
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$user || $user['status'] !== 'active') {
            throw new RuntimeException('Tài khoản không tồn tại hoặc đã bị khóa.');
        }
    }

    private function lockRowsByIds(string $table, array $ids): void
    {
        $allowedTables = ['categories', 'products', 'product_variants'];
        if (!in_array($table, $allowedTables, true) || empty($ids)) {
            return;
        }
        $ids = array_values(array_unique(array_map('intval', $ids)));
        sort($ids, SORT_NUMERIC);

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $types        = str_repeat('i', count($ids));
        $stmt         = $this->conn->prepare("SELECT id FROM {$table} WHERE id IN ({$placeholders}) ORDER BY id ASC FOR UPDATE");
        $stmt->bind_param($types, ...$ids);
        $stmt->execute();
        $stmt->get_result()->free();
        $stmt->close();
    }

    private function runWithDeadlockRetry(callable $callback): mixed
    {
        $attempts = 0;
        while (true) {
            try {
                return $callback();
            } catch (mysqli_sql_exception $e) {
                $attempts++;
                if ($e->getCode() === 1213 && $attempts < 3) {
                    usleep(50000 * $attempts);
                    continue;
                }
                throw $e;
            }
        }
    }
}
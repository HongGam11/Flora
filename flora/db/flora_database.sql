-- =====================================================================
-- FLORA - Website bán quần áo nam và nữ
-- Triển khai SQL theo Flora_Database.md (15 bảng, MariaDB 10.4.32 / XAMPP)
--
-- Quy ước (mục 2 của tài liệu):
--   * Mỗi bảng: id INT UNSIGNED AUTO_INCREMENT; FK cùng kiểu với PK.
--   * Tiền DECIMAL(12,0) VNĐ; tỷ lệ DECIMAL(5,2); InnoDB; utf8mb4.
--   * Thời gian lưu UTC  -> script đặt time_zone = '+00:00'.
--     PHP/kết nối DB của ứng dụng cũng phải đặt cùng múi giờ.
--   * Trạng thái là VARCHAR(20) + CHECK giới hạn tập giá trị.
--
-- Chạy một lần để tạo database mới `flora`; không xóa database cũ.
-- Nếu `flora` đã tồn tại, dừng khi gặp lỗi và kiểm tra trước khi chạy tiếp.
-- Database `flora_shop` hiện có không bị thay đổi.
-- Mật khẩu của 3 tài khoản demo: Flora@123
-- =====================================================================

SET NAMES utf8mb4;
SET time_zone = '+00:00';

CREATE DATABASE flora
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;
USE flora;

-- ---------------------------------------------------------------------
-- 3.1 users - Tài khoản
-- ---------------------------------------------------------------------
CREATE TABLE users (
  id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  full_name     VARCHAR(100) NOT NULL,
  email         VARCHAR(255) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  phone         VARCHAR(20)  NULL,
  address       VARCHAR(500) NULL,
  role          VARCHAR(20)  NOT NULL DEFAULT 'customer',
  status        VARCHAR(20)  NOT NULL DEFAULT 'active',
  created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_users_email (email),
  CONSTRAINT chk_users_role   CHECK (role   IN ('admin', 'staff', 'customer')),
  CONSTRAINT chk_users_status CHECK (status IN ('active', 'disabled'))
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 3.2 categories - Danh mục (nhóm gốc + danh mục con)
-- ---------------------------------------------------------------------
CREATE TABLE categories (
  id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  parent_id  INT UNSIGNED NULL,                     -- NULL với nhóm gốc
  name       VARCHAR(100) NOT NULL,
  status     VARCHAR(20)  NOT NULL DEFAULT 'active',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  -- Lưu ý: parent_id NULL thì UNIQUE không chặn trùng tên nhóm gốc.
  -- Hai nhóm gốc cố định được tạo bằng seed; PHP không cho thêm/xóa nhóm gốc.
  UNIQUE KEY uq_categories_parent_name (parent_id, name),
  CONSTRAINT fk_categories_parent FOREIGN KEY (parent_id)
    REFERENCES categories (id) ON DELETE RESTRICT,
  CONSTRAINT chk_categories_status CHECK (status IN ('active', 'inactive'))
  -- Không tự tham chiếu / không tạo cấp ba: PHP kiểm tra (MySQL không cho CHECK
  -- dùng cột AUTO_INCREMENT nên không thể viết parent_id <> id).
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 3.5 sizes / 3.6 colors  (tạo trước products/variants)
-- ---------------------------------------------------------------------
CREATE TABLE sizes (
  id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name       VARCHAR(20) NOT NULL,                  -- S, M, L, XL, 28, 29...
  sort_order INT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (id),
  UNIQUE KEY uq_sizes_name (name)
) ENGINE=InnoDB;

CREATE TABLE colors (
  id       INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name     VARCHAR(50) NOT NULL,
  hex_code CHAR(7) NULL,                            -- ví dụ #FFFFFF
  PRIMARY KEY (id),
  UNIQUE KEY uq_colors_name (name)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 3.3 products - Sản phẩm (không lưu giá và tồn kho)
-- ---------------------------------------------------------------------
CREATE TABLE products (
  id                INT UNSIGNED NOT NULL AUTO_INCREMENT,
  category_id       INT UNSIGNED NOT NULL,          -- phải là danh mục con (PHP kiểm tra)
  name              VARCHAR(200) NOT NULL,
  description       TEXT NULL,
  discount_percent  DECIMAL(5,2) NOT NULL DEFAULT 0,
  discount_start_at DATETIME NULL,
  discount_end_at   DATETIME NULL,
  is_featured       BOOLEAN NOT NULL DEFAULT 0,
  status            VARCHAR(20) NOT NULL DEFAULT 'inactive',
  published_at      DATETIME NULL,                  -- ngày mở bán lần đầu
  created_at        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_products_category_status (category_id, status),
  KEY idx_products_status_featured (status, is_featured),
  CONSTRAINT fk_products_category FOREIGN KEY (category_id)
    REFERENCES categories (id) ON DELETE RESTRICT,
  CONSTRAINT chk_products_status   CHECK (status IN ('active', 'inactive')),
  CONSTRAINT chk_products_discount CHECK (discount_percent BETWEEN 0 AND 100),
  -- Có giảm giá thì phải có khoảng thời gian hợp lệ: bắt đầu < kết thúc
  CONSTRAINT chk_products_discount_time CHECK (
    discount_percent = 0
    OR (discount_start_at IS NOT NULL AND discount_end_at IS NOT NULL
        AND discount_start_at < discount_end_at)
  )
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 3.4 product_images - Ảnh sản phẩm
-- ---------------------------------------------------------------------
CREATE TABLE product_images (
  id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  product_id INT UNSIGNED NOT NULL,
  color_id   INT UNSIGNED NULL,                     -- NULL với ảnh chung
  image_path VARCHAR(500) NOT NULL,
  is_main    BOOLEAN NOT NULL DEFAULT 0,
  sort_order INT UNSIGNED NOT NULL DEFAULT 0,
  -- Cột sinh: bằng product_id chỉ ở ảnh chính, NULL ở ảnh khác.
  -- UNIQUE bên dưới => tối đa một ảnh chính mỗi sản phẩm.
  main_product_id INT UNSIGNED
    GENERATED ALWAYS AS (IF(is_main = 1, product_id, NULL)) VIRTUAL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_product_images_one_main (main_product_id),
  KEY idx_product_images_product_sort (product_id, sort_order),
  KEY idx_product_images_color (color_id),
  CONSTRAINT fk_product_images_product FOREIGN KEY (product_id)
    REFERENCES products (id) ON DELETE CASCADE,
  CONSTRAINT fk_product_images_color FOREIGN KEY (color_id)
    REFERENCES colors (id) ON DELETE RESTRICT
  -- color_id phải là màu của ít nhất một biến thể cùng product_id:
  -- FK không đủ bảo đảm, PHP kiểm tra trong transaction khóa sản phẩm.
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 3.7 product_variants - Biến thể, giá và tồn kho
-- ---------------------------------------------------------------------
CREATE TABLE product_variants (
  id             INT UNSIGNED NOT NULL AUTO_INCREMENT,
  product_id     INT UNSIGNED NOT NULL,
  size_id        INT UNSIGNED NOT NULL,
  color_id       INT UNSIGNED NOT NULL,
  original_price DECIMAL(12,0) NOT NULL,            -- giá bán trước giảm
  stock          INT UNSIGNED NOT NULL DEFAULT 0,
  status         VARCHAR(20) NOT NULL DEFAULT 'active',
  created_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_variants_product_size_color (product_id, size_id, color_id),
  KEY idx_variants_product_status_price (product_id, status, original_price),
  KEY idx_variants_size (size_id),
  KEY idx_variants_color (color_id),
  CONSTRAINT fk_variants_product FOREIGN KEY (product_id)
    REFERENCES products (id) ON DELETE RESTRICT,
  CONSTRAINT fk_variants_size FOREIGN KEY (size_id)
    REFERENCES sizes (id) ON DELETE RESTRICT,
  CONSTRAINT fk_variants_color FOREIGN KEY (color_id)
    REFERENCES colors (id) ON DELETE RESTRICT,
  CONSTRAINT chk_variants_price  CHECK (original_price > 0),
  CONSTRAINT chk_variants_status CHECK (status IN ('active', 'inactive'))
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 3.8 carts / 3.9 cart_items - Giỏ hàng
-- ---------------------------------------------------------------------
CREATE TABLE carts (
  id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id    INT UNSIGNED NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_carts_user (user_id),               -- mỗi tài khoản tối đa một giỏ
  CONSTRAINT fk_carts_user FOREIGN KEY (user_id)
    REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE cart_items (
  id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  cart_id    INT UNSIGNED NOT NULL,
  variant_id INT UNSIGNED NOT NULL,
  quantity   INT UNSIGNED NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_cart_items_cart_variant (cart_id, variant_id),
  KEY idx_cart_items_variant (variant_id),
  CONSTRAINT fk_cart_items_cart FOREIGN KEY (cart_id)
    REFERENCES carts (id) ON DELETE CASCADE,
  CONSTRAINT fk_cart_items_variant FOREIGN KEY (variant_id)
    REFERENCES product_variants (id) ON DELETE CASCADE,
  CONSTRAINT chk_cart_items_qty CHECK (quantity > 0)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 3.10 coupons - Mã giảm giá
-- ---------------------------------------------------------------------
CREATE TABLE coupons (
  id                  INT UNSIGNED NOT NULL AUTO_INCREMENT,
  code                VARCHAR(50) NOT NULL,         -- chuẩn hóa viết hoa ở PHP
  description         VARCHAR(255) NULL,
  discount_type       VARCHAR(20) NOT NULL,         -- percent / fixed
  discount_value      DECIMAL(12,2) NOT NULL,
  max_discount_amount DECIMAL(12,0) NULL,           -- bắt buộc với percent, NULL với fixed
  min_order_amount    DECIMAL(12,0) NOT NULL DEFAULT 0,
  start_at            DATETIME NOT NULL,
  end_at              DATETIME NOT NULL,
  usage_limit         INT UNSIGNED NULL,
  per_user_limit      INT UNSIGNED NULL,
  status              VARCHAR(20) NOT NULL DEFAULT 'active',
  created_at          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_coupons_code (code),
  CONSTRAINT chk_coupons_type   CHECK (discount_type IN ('percent', 'fixed')),
  CONSTRAINT chk_coupons_status CHECK (status IN ('active', 'inactive')),
  CONSTRAINT chk_coupons_value  CHECK (discount_value > 0),
  CONSTRAINT chk_coupons_percent CHECK (discount_type <> 'percent' OR discount_value <= 100),
  CONSTRAINT chk_coupons_fixed_int CHECK (discount_type <> 'fixed' OR discount_value = FLOOR(discount_value)),
  CONSTRAINT chk_coupons_max CHECK (
    (discount_type = 'percent' AND max_discount_amount IS NOT NULL AND max_discount_amount > 0)
    OR (discount_type = 'fixed' AND max_discount_amount IS NULL)
  ),
  CONSTRAINT chk_coupons_min   CHECK (min_order_amount >= 0),
  CONSTRAINT chk_coupons_time  CHECK (end_at > start_at),
  CONSTRAINT chk_coupons_usage CHECK (usage_limit IS NULL OR usage_limit > 0),
  CONSTRAINT chk_coupons_per_user CHECK (per_user_limit IS NULL OR per_user_limit > 0)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 3.11 orders - Đơn hàng (chứa snapshot tại lúc đặt)
-- ---------------------------------------------------------------------
CREATE TABLE orders (
  id                 INT UNSIGNED NOT NULL AUTO_INCREMENT,
  order_code         VARCHAR(40) NOT NULL,          -- tiền tố Flora + chuỗi ngẫu nhiên
  checkout_token     CHAR(64)    NOT NULL,          -- chống tạo đơn trùng
  user_id            INT UNSIGNED NOT NULL,
  recipient_name     VARCHAR(100) NOT NULL,
  recipient_phone    VARCHAR(20)  NOT NULL,
  shipping_address   VARCHAR(500) NOT NULL,
  note               TEXT NULL,
  subtotal           DECIMAL(12,0) NOT NULL,        -- tiền hàng sau giảm sản phẩm
  coupon_id          INT UNSIGNED NULL,
  coupon_code        VARCHAR(50) NULL,
  coupon_discount    DECIMAL(12,0) NOT NULL DEFAULT 0,
  shipping_fee       DECIMAL(12,0) NOT NULL,
  total_amount       DECIMAL(12,0) NOT NULL,
  payment_method     VARCHAR(20) NOT NULL,
  payment_status     VARCHAR(20) NOT NULL DEFAULT 'unpaid',
  status             VARCHAR(20) NOT NULL DEFAULT 'pending',
  payment_expires_at DATETIME NULL,
  cancel_reason      VARCHAR(500) NULL,
  created_at         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  completed_at       DATETIME NULL,
  cancelled_at       DATETIME NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_orders_code  (order_code),
  UNIQUE KEY uq_orders_token (checkout_token),
  KEY idx_orders_user_created       (user_id, created_at, id),
  KEY idx_orders_status_created     (status, created_at, id),
  KEY idx_orders_status_pay_done    (status, payment_status, completed_at),
  KEY idx_orders_status_pay_expires (status, payment_status, payment_expires_at),
  KEY idx_orders_coupon_status_user (coupon_id, status, user_id),
  CONSTRAINT fk_orders_user FOREIGN KEY (user_id)
    REFERENCES users (id) ON DELETE RESTRICT,
  CONSTRAINT fk_orders_coupon FOREIGN KEY (coupon_id)
    REFERENCES coupons (id) ON DELETE RESTRICT,
  CONSTRAINT chk_orders_amounts CHECK (
    subtotal >= 0 AND shipping_fee >= 0 AND total_amount >= 0
    AND coupon_discount >= 0 AND coupon_discount <= subtotal
  ),
  -- total = subtotal - coupon_discount + shipping_fee
  CONSTRAINT chk_orders_total CHECK (total_amount = subtotal - coupon_discount + shipping_fee),
  -- Không dùng mã: coupon_id/coupon_code NULL và coupon_discount = 0
  CONSTRAINT chk_orders_coupon CHECK (
    (coupon_id IS NULL AND coupon_code IS NULL AND coupon_discount = 0)
    OR (coupon_id IS NOT NULL AND coupon_code IS NOT NULL)
  ),
  CONSTRAINT chk_orders_pay_method CHECK (payment_method IN ('cod', 'ewallet', 'paylater')),
  CONSTRAINT chk_orders_pay_status CHECK (payment_status IN ('unpaid', 'paid', 'refunded', 'not_required')),
  CONSTRAINT chk_orders_status CHECK (status IN ('pending', 'confirmed', 'shipping', 'completed', 'cancelled')),
  -- Đơn 0đ <=> not_required (mục 5.1)
  CONSTRAINT chk_orders_zero_total CHECK (
    (total_amount = 0 AND payment_status = 'not_required')
    OR (total_amount > 0 AND payment_status <> 'not_required')
  ),
  -- Hạn thanh toán chỉ có với online và tổng > 0
  CONSTRAINT chk_orders_expires CHECK (
    (payment_expires_at IS NOT NULL) = (payment_method <> 'cod' AND total_amount > 0)
  ),
  CONSTRAINT chk_orders_cancel CHECK (
    (status = 'cancelled') = (cancelled_at IS NOT NULL)
    AND (cancel_reason IS NULL OR status = 'cancelled')
  ),
  CONSTRAINT chk_orders_completed CHECK ((status = 'completed') = (completed_at IS NOT NULL))
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 3.12 order_items - Chi tiết đơn (snapshot)
-- ---------------------------------------------------------------------
CREATE TABLE order_items (
  id                  INT UNSIGNED NOT NULL AUTO_INCREMENT,
  order_id            INT UNSIGNED NOT NULL,
  variant_id          INT UNSIGNED NOT NULL,
  product_name        VARCHAR(200) NOT NULL,
  size_name           VARCHAR(20)  NOT NULL,
  color_name          VARCHAR(50)  NOT NULL,
  quantity            INT UNSIGNED NOT NULL,
  original_unit_price DECIMAL(12,0) NOT NULL,
  unit_price          DECIMAL(12,0) NOT NULL,       -- giá sau giảm sản phẩm, trước mã
  PRIMARY KEY (id),
  UNIQUE KEY uq_order_items_order_variant (order_id, variant_id),
  KEY idx_order_items_variant (variant_id),
  CONSTRAINT fk_order_items_order FOREIGN KEY (order_id)
    REFERENCES orders (id) ON DELETE RESTRICT,
  CONSTRAINT fk_order_items_variant FOREIGN KEY (variant_id)
    REFERENCES product_variants (id) ON DELETE RESTRICT,
  CONSTRAINT chk_order_items_qty   CHECK (quantity > 0),
  CONSTRAINT chk_order_items_price CHECK (
    original_unit_price > 0 AND unit_price >= 0 AND unit_price <= original_unit_price
  )
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 3.13 payment_transactions - Thanh toán và hoàn tiền
-- ---------------------------------------------------------------------
CREATE TABLE payment_transactions (
  id                      INT UNSIGNED NOT NULL AUTO_INCREMENT,
  order_id                INT UNSIGNED NOT NULL,
  transaction_code        VARCHAR(64)  NOT NULL,
  transaction_type        VARCHAR(20)  NOT NULL,    -- payment / refund
  original_transaction_id INT UNSIGNED NULL,        -- NULL với payment
  payment_method          VARCHAR(20)  NOT NULL,
  amount                  DECIMAL(12,0) NOT NULL,
  status                  VARCHAR(20)  NOT NULL,    -- pending / success / failed
  is_simulated            BOOLEAN NOT NULL DEFAULT 1,
  created_at              DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  processed_at            DATETIME NULL,
  processed_by            INT UNSIGNED NULL,        -- NULL khi hệ thống xử lý
  result_message          VARCHAR(500) NULL,
  -- Cột sinh: loại giao dịch chỉ khi success, ngược lại NULL.
  -- UNIQUE (order_id, success_type) => tối đa 1 payment success và
  -- 1 refund success mỗi đơn (mục 3.13).
  success_type            VARCHAR(20)
    GENERATED ALWAYS AS (IF(status = 'success', transaction_type, NULL)) VIRTUAL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_payment_code (transaction_code),
  UNIQUE KEY uq_payment_one_success (order_id, success_type),
  KEY idx_payment_order_created (order_id, created_at, id),
  KEY idx_payment_original (original_transaction_id),
  KEY idx_payment_processed_by (processed_by),
  CONSTRAINT fk_payment_order FOREIGN KEY (order_id)
    REFERENCES orders (id) ON DELETE RESTRICT,
  CONSTRAINT fk_payment_original FOREIGN KEY (original_transaction_id)
    REFERENCES payment_transactions (id) ON DELETE RESTRICT,
  CONSTRAINT fk_payment_processed_by FOREIGN KEY (processed_by)
    REFERENCES users (id) ON DELETE RESTRICT,
  CONSTRAINT chk_payment_type   CHECK (transaction_type IN ('payment', 'refund')),
  CONSTRAINT chk_payment_method CHECK (payment_method IN ('cod', 'ewallet', 'paylater')),
  CONSTRAINT chk_payment_status CHECK (status IN ('pending', 'success', 'failed')),
  CONSTRAINT chk_payment_amount CHECK (amount > 0),
  -- payment không có giao dịch gốc; refund phải có
  CONSTRAINT chk_payment_original CHECK (
    (transaction_type = 'payment' AND original_transaction_id IS NULL)
    OR (transaction_type = 'refund' AND original_transaction_id IS NOT NULL)
  ),
  -- pending <=> processed_at NULL; success/failed phải có processed_at
  CONSTRAINT chk_payment_processed CHECK ((status = 'pending') = (processed_at IS NULL))
  -- Refund phải trỏ đúng payment success cùng đơn, cùng phương thức và không
  -- tự tham chiếu: FK/CHECK không kiểm tra được, PHP kiểm tra (mục 11).
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 3.14 order_status_history - Lịch sử đơn (chỉ thêm, không sửa/xóa)
-- ---------------------------------------------------------------------
CREATE TABLE order_status_history (
  id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  order_id   INT UNSIGNED NOT NULL,
  changed_by INT UNSIGNED NULL,                     -- NULL nếu hệ thống
  old_status VARCHAR(20) NULL,                      -- NULL ở lần tạo
  new_status VARCHAR(20) NOT NULL,
  note       VARCHAR(500) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_history_order_created (order_id, created_at, id),
  KEY idx_history_changed_by (changed_by),
  CONSTRAINT fk_history_order FOREIGN KEY (order_id)
    REFERENCES orders (id) ON DELETE RESTRICT,
  CONSTRAINT fk_history_changed_by FOREIGN KEY (changed_by)
    REFERENCES users (id) ON DELETE RESTRICT,
  CONSTRAINT chk_history_new CHECK (new_status IN ('pending', 'confirmed', 'shipping', 'completed', 'cancelled')),
  CONSTRAINT chk_history_old CHECK (
    old_status IS NULL OR old_status IN ('pending', 'confirmed', 'shipping', 'completed', 'cancelled')
  )
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 3.15 user_events - Hành vi người dùng
-- ---------------------------------------------------------------------
CREATE TABLE user_events (
  id             INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id        INT UNSIGNED NULL,                 -- NULL nếu chưa đăng nhập
  session_key    VARCHAR(64) NOT NULL,              -- mã phiên theo dõi, không phải token xác thực
  event_type     VARCHAR(20) NOT NULL,
  product_id     INT UNSIGNED NULL,
  search_keyword VARCHAR(255) NULL,
  created_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_events_user (user_id),
  KEY idx_events_type_created (event_type, created_at),
  KEY idx_events_product_type_created (product_id, event_type, created_at),
  CONSTRAINT fk_events_user FOREIGN KEY (user_id)
    REFERENCES users (id) ON DELETE SET NULL,
  CONSTRAINT fk_events_product FOREIGN KEY (product_id)
    REFERENCES products (id) ON DELETE RESTRICT,
  CONSTRAINT chk_events_type CHECK (event_type IN ('view_product', 'search', 'add_to_cart')),
  -- view_product / add_to_cart: có product_id, không có từ khóa.
  -- search: có từ khóa, không có product_id.
  CONSTRAINT chk_events_shape CHECK (
    (event_type IN ('view_product', 'add_to_cart') AND product_id IS NOT NULL AND search_keyword IS NULL)
    OR (event_type = 'search' AND search_keyword IS NOT NULL AND product_id IS NULL)
  )
  -- Quy tắc "add_to_cart bắt buộc có user_id" do PHP kiểm tra: MySQL không cho
  -- CHECK trên cột user_id vì cột này có FK ON DELETE SET NULL.
) ENGINE=InnoDB;

-- =====================================================================
-- SEED DỮ LIỆU (mục 13, bước 4)
-- =====================================================================

-- Tài khoản demo (mật khẩu: Flora@123)
INSERT INTO users (full_name, email, password_hash, phone, address, role) VALUES
  ('Quản trị Flora', 'admin@flora.local',    '$2y$10$G71dAn4JjpWe0Xo/wEFmj.hgdMk2PLex97yY.GiH0KK53ouD8kIO6', '0900000001', 'TP. Hồ Chí Minh', 'admin'),
  ('Nhân viên Flora','staff@flora.local',    '$2y$10$G71dAn4JjpWe0Xo/wEFmj.hgdMk2PLex97yY.GiH0KK53ouD8kIO6', '0900000002', 'TP. Hồ Chí Minh', 'staff'),
  ('Khách hàng Demo','customer@flora.local', '$2y$10$G71dAn4JjpWe0Xo/wEFmj.hgdMk2PLex97yY.GiH0KK53ouD8kIO6', '0900000003', '1 Nguyễn Huệ, Quận 1, TP. Hồ Chí Minh', 'customer');

-- Hai nhóm gốc cố định
INSERT INTO categories (parent_id, name) VALUES
  (NULL, 'Thời trang nam'),
  (NULL, 'Thời trang nữ');

-- Danh mục con ban đầu theo tài liệu
INSERT INTO categories (parent_id, name)
SELECT r.id, c.child_name
FROM (
  SELECT 'Thời trang nam' AS root_name, 'Áo thun'   AS child_name UNION ALL
  SELECT 'Thời trang nam', 'Áo sơ mi'  UNION ALL
  SELECT 'Thời trang nam', 'Áo khoác'  UNION ALL
  SELECT 'Thời trang nam', 'Quần jean' UNION ALL
  SELECT 'Thời trang nam', 'Quần short' UNION ALL
  SELECT 'Thời trang nữ',  'Áo thun'   UNION ALL
  SELECT 'Thời trang nữ',  'Áo kiểu'   UNION ALL
  SELECT 'Thời trang nữ',  'Quần đùi'  UNION ALL
  SELECT 'Thời trang nữ',  'Chân váy'  UNION ALL
  SELECT 'Thời trang nữ',  'Áo sơ mi'  UNION ALL
  SELECT 'Thời trang nữ',  'Đầm'       UNION ALL
  -- Bổ sung (ngoài danh sách ban đầu): shop có ảnh quần jean nữ
  SELECT 'Thời trang nữ',  'Quần jean'
) AS c
JOIN categories r ON r.name = c.root_name AND r.parent_id IS NULL;

INSERT INTO sizes (name, sort_order) VALUES
  ('S', 1), ('M', 2), ('L', 3), ('XL', 4), ('XXL', 5),
  ('28', 10), ('29', 11), ('30', 12), ('31', 13), ('32', 14);

INSERT INTO colors (name, hex_code) VALUES
  ('Đen',       '#000000'),
  ('Trắng',     '#FFFFFF'),
  ('Xanh',      '#1F4E9C'),
  ('Xanh nhạt', '#A9C7E8'),
  ('Xanh đậm',  '#1B3A6B'),
  ('Xanh đen',  '#1C2B3A'),
  ('Xám',       '#808080'),
  ('Xám đen',   '#3A3A3A'),
  ('Vàng',      '#F2C94C'),
  ('Hồng',      '#F4A6C0'),
  ('Be',        '#D8C3A5');

-- ---------------------------------------------------------------------
-- Staging 1: sản phẩm (tên, mô tả, giá là DỮ LIỆU MẪU - sửa tùy ý)
-- ---------------------------------------------------------------------
CREATE TEMPORARY TABLE tmp_products (
  product_name VARCHAR(200) NOT NULL,
  root_name    VARCHAR(100) NOT NULL,
  child_name   VARCHAR(100) NOT NULL,
  description  TEXT NOT NULL,
  price        DECIMAL(12,0) NOT NULL
) DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

INSERT INTO tmp_products (product_name, root_name, child_name, description, price) VALUES
  -- Nam
  ('Quần jean nam',      'Thời trang nam', 'Quần jean',  'Quần jean nam co giãn',            450000),
  ('Quần short nam',     'Thời trang nam', 'Quần short', 'Quần short nam thoải mái',        280000),
  ('Áo sơ mi nam',       'Thời trang nam', 'Áo sơ mi',   'Áo sơ mi nam cotton thoáng mát',   320000),
  ('Áo thun nam',        'Thời trang nam', 'Áo thun',    'Áo thun nam cổ tròn basic',        180000),
  ('Hoodie nam',         'Thời trang nam', 'Áo khoác',   'Hoodie nỉ bông, mũ 2 lớp',         390000),
  ('Áo vest nam',        'Thời trang nam', 'Áo khoác',   'Áo vest nam dáng công sở',         890000),
  -- Nữ
  ('Quần jean nữ',       'Thời trang nữ',  'Quần jean',  'Quần jean nữ lưng cao',            420000),
  ('Quần đùi nữ',        'Thời trang nữ',  'Quần đùi',   'Quần đùi nữ năng động',            250000),
  ('Áo babydoll nữ',     'Thời trang nữ',  'Áo kiểu',    'Áo babydoll nữ chất liệu nhẹ',     280000),
  ('Áo cardigan nữ',     'Thời trang nữ',  'Áo kiểu',    'Cardigan len mềm, dáng rộng',      350000),
  ('Áo thun nữ',         'Thời trang nữ',  'Áo thun',    'Áo thun nữ basic',                 170000),
  ('Áo sơ mi nữ tay dài','Thời trang nữ',  'Áo sơ mi',   'Áo sơ mi nữ tay dài thanh lịch',   330000),
  ('Áo sơ mi nữ tay ngắn','Thời trang nữ', 'Áo sơ mi',   'Áo sơ mi nữ tay ngắn thoáng mát',  300000),
  ('Đầm nữ',             'Thời trang nữ',  'Đầm',        'Đầm nữ dáng xòe',                  520000),
  ('Chân váy nữ',        'Thời trang nữ',  'Chân váy',   'Chân váy nữ lưng cao, có lót',     300000);

-- Sản phẩm tạo ở trạng thái inactive (mặc định); chỉ mở bán sau khi
-- đã có ảnh chính và biến thể active (xem UPDATE ở cuối phần seed).
INSERT INTO products (category_id, name, description)
SELECT ch.id, t.product_name, t.description
FROM tmp_products t
JOIN categories rt ON rt.name = t.root_name AND rt.parent_id IS NULL
JOIN categories ch ON ch.name = t.child_name AND ch.parent_id = rt.id;

-- ---------------------------------------------------------------------
-- Staging 2: màu + link ảnh của từng sản phẩm (từ danh_mục_sp_1.docx)
-- sort_order = 1 -> ảnh chính của sản phẩm
-- ---------------------------------------------------------------------
CREATE TEMPORARY TABLE tmp_product_colors (
  product_name VARCHAR(200) NOT NULL,
  color_name   VARCHAR(50)  NOT NULL,
  sort_order   TINYINT      NOT NULL,
  image_path   VARCHAR(500) NOT NULL
) DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

INSERT INTO tmp_product_colors (product_name, color_name, sort_order, image_path) VALUES
  -- ===== NAM =====
  ('Áo sơ mi nam',  'Trắng',    1, 'https://i.pinimg.com/1200x/6c/14/b8/6c14b85d130b8190aac2f43dede7b21d.jpg'),
  ('Áo sơ mi nam',  'Đen',      2, 'https://i.pinimg.com/736x/86/24/a4/8624a4ccddf6a3ce1d160a901a2a7a1f.jpg'),
  ('Quần jean nam', 'Xanh nhạt',1, 'https://i.pinimg.com/736x/6d/b2/d1/6db2d148bbe86b5c039aef72f40da9ba.jpg'),
  ('Quần jean nam', 'Xanh đậm', 2, 'https://i.pinimg.com/736x/50/a9/6d/50a96dce94bd274ffa23d0cc2f229a8c.jpg'),
  ('Quần short nam','Xanh',     1, 'https://i.pinimg.com/736x/4f/f0/89/4ff089d4261e1ec3ed302deec9470af2.jpg'),
  ('Áo thun nam',   'Đen',      1, 'https://i.pinimg.com/1200x/c0/67/1d/c0671db6f93f408d04814157217c36bb.jpg'),
  ('Áo thun nam',   'Trắng',    2, 'https://i.pinimg.com/1200x/4e/fa/ea/4efaeaa4d43697554223e9f21831d3ce.jpg'),
  ('Hoodie nam',    'Xanh đen', 1, 'https://i.pinimg.com/736x/c5/ea/9e/c5ea9e7d399ec610d823c68f00d41788.jpg'),
  ('Hoodie nam',    'Xám đen',  2, 'https://i.pinimg.com/736x/a6/6f/15/a66f150d08d118e9c8839e635718ce27.jpg'),
  ('Áo vest nam',   'Xám',      1, 'https://i.pinimg.com/1200x/f9/20/64/f92064785a5dee093f4617fc76d7b4d7.jpg'),
  ('Áo vest nam',   'Đen',      2, 'https://i.pinimg.com/736x/c9/09/62/c90962be200fefbd750ef08048310bf8.jpg'),
  -- ===== NỮ =====
  ('Quần jean nữ',  'Xanh nhạt',1, 'https://i.pinimg.com/736x/7b/6a/89/7b6a892c1e9633aafe8b07c427a2ee3a.jpg'),
  ('Quần jean nữ',  'Xanh đen', 2, 'https://i.pinimg.com/1200x/ca/85/7f/ca857f34ef4ac9ee48f54a5b27bd8b9f.jpg'),
  ('Quần đùi nữ',   'Xanh nhạt',1, 'https://i.pinimg.com/736x/ab/82/fa/ab82fa13f72f07a458a416c9f9dedcad.jpg'),
  ('Quần đùi nữ',   'Xanh đen', 2, 'https://i.pinimg.com/736x/37/36/5a/37365aba22b765c2a1bd70be4c123e55.jpg'),
  ('Áo babydoll nữ','Trắng',    1, 'https://i.pinimg.com/736x/69/a4/21/69a421c2268f54145a989eeaa640216c.jpg'),
  ('Áo babydoll nữ','Vàng',     2, 'https://i.pinimg.com/1200x/65/79/2b/65792bbc20d839a63031a820640bebf1.jpg'),
  ('Áo cardigan nữ','Vàng',     1, 'https://i.pinimg.com/736x/f8/16/9b/f8169b98fdd3507c3444908dd93323a9.jpg'),
  ('Áo cardigan nữ','Hồng',     2, 'https://i.pinimg.com/736x/17/bb/32/17bb32fe1e4306d41929c601ab63c83e.jpg'),
  ('Áo cardigan nữ','Trắng',    3, 'https://i.pinimg.com/736x/d6/15/95/d61595ec93b29957784ec63fdfd9f767.jpg'),
  ('Áo thun nữ',    'Trắng',    1, 'https://i.pinimg.com/1200x/09/87/51/0987514b5f63246e76ce0ab035c41ee2.jpg'),
  ('Áo thun nữ',    'Đen',      2, 'https://i.pinimg.com/736x/df/53/d7/df53d788eeba16939e145fb13089a9f7.jpg'),
  ('Áo sơ mi nữ tay dài',  'Trắng', 1, 'https://i.pinimg.com/736x/b6/ee/79/b6ee7937bcf79a176bc8f42b24f32043.jpg'),
  ('Áo sơ mi nữ tay ngắn', 'Trắng', 1, 'https://i.pinimg.com/1200x/d9/88/c2/d988c2a5028fd013e97e98f15b19ba61.jpg'),
  ('Đầm nữ',        'Đen',      1, 'https://i.pinimg.com/736x/70/30/b9/7030b920d0227e4ebb8a5916f9f3eba2.jpg'),
  ('Đầm nữ',        'Trắng',    2, 'https://i.pinimg.com/1200x/82/63/bd/8263bdd4c3b221a6776151869f432644.jpg'),
  ('Chân váy nữ',   'Trắng',    1, 'https://i.pinimg.com/736x/81/ad/f1/81adf195d8943ff8cb367421b4c84534.jpg'),
  ('Chân váy nữ',   'Đen',      2, 'https://i.pinimg.com/1200x/36/49/74/36497415f3d47a3a6f691aca5164aae9.jpg');

-- Ảnh: mỗi (sản phẩm, màu) một ảnh; màu đầu tiên là ảnh chính
INSERT INTO product_images (product_id, color_id, image_path, is_main, sort_order)
SELECT p.id, c.id, t.image_path, IF(t.sort_order = 1, 1, 0), t.sort_order
FROM tmp_product_colors t
JOIN products p ON p.name = t.product_name
JOIN colors   c ON c.name = t.color_name;

-- Biến thể: mỗi màu của sản phẩm x size S/M/L; giá lấy từ staging, tồn 20
INSERT INTO product_variants (product_id, size_id, color_id, original_price, stock, status)
SELECT p.id, sz.id, c.id, pr.price, 20, 'active'
FROM tmp_product_colors t
JOIN tmp_products pr ON pr.product_name = t.product_name
JOIN products p  ON p.name = t.product_name
JOIN colors   c  ON c.name = t.color_name
JOIN sizes    sz ON sz.name IN ('S', 'M', 'L');

DROP TEMPORARY TABLE tmp_product_colors;
DROP TEMPORARY TABLE tmp_products;

-- Một sản phẩm có giảm giá 10% trong 30 ngày (mẫu)
UPDATE products
SET discount_percent  = 10,
    discount_start_at = UTC_TIMESTAMP(),
    discount_end_at   = DATE_ADD(UTC_TIMESTAMP(), INTERVAL 30 DAY),
    is_featured       = 1
WHERE name = 'Áo thun nam';

-- Mở bán: chỉ sản phẩm có ảnh chính và ít nhất một biến thể active
-- (id > 0 để chạy được khi Workbench bật Safe Updates)
UPDATE products p
SET p.status = 'active',
    p.published_at = UTC_TIMESTAMP()
WHERE p.id > 0
  AND EXISTS (SELECT 1 FROM product_images i
              WHERE i.product_id = p.id AND i.is_main = 1)
  AND EXISTS (SELECT 1 FROM product_variants v
              WHERE v.product_id = p.id AND v.status = 'active');

-- Mã giảm giá mẫu
INSERT INTO coupons
  (code, description, discount_type, discount_value, max_discount_amount,
   min_order_amount, start_at, end_at, usage_limit, per_user_limit)
VALUES
  ('FLORA10', 'Giảm 10% tối đa 50.000đ cho đơn từ 200.000đ',
   'percent', 10, 50000, 200000,
   UTC_TIMESTAMP(), DATE_ADD(UTC_TIMESTAMP(), INTERVAL 90 DAY), 100, 1),
  ('GIAM30K', 'Giảm 30.000đ cho đơn từ 300.000đ',
   'fixed', 30000, NULL, 300000,
   UTC_TIMESTAMP(), DATE_ADD(UTC_TIMESTAMP(), INTERVAL 90 DAY), NULL, 2);

-- =====================================================================
-- VIEW HỖ TRỢ (không có trong tài liệu - tiện xem chi tiết sản phẩm)
-- Giá hiện hành = làm tròn(original_price x (1 - giảm%/100)) nếu chương
-- trình giảm đang hiệu lực (bắt đầu <= hiện tại < kết thúc), ngược lại
-- bằng original_price. Server PHP vẫn tự tính lại khi đặt hàng.
-- =====================================================================
CREATE VIEW v_product_details AS
SELECT  p.id              AS product_id,
        root.name         AS nhom,
        cat.name          AS danh_muc,
        p.name            AS san_pham,
        p.status          AS trang_thai_sp,
        c.name            AS mau,
        sz.name           AS size,
        v.id              AS variant_id,
        v.original_price  AS gia_goc,
        CASE
          WHEN p.discount_percent > 0
           AND p.discount_start_at <= UTC_TIMESTAMP()
           AND UTC_TIMESTAMP() < p.discount_end_at
          THEN ROUND(v.original_price * (1 - p.discount_percent / 100), 0)
          ELSE v.original_price
        END               AS gia_hien_hanh,
        v.stock           AS ton_kho,
        v.status          AS trang_thai_bien_the,
        img.image_path    AS anh_mau
FROM products p
JOIN categories cat  ON cat.id  = p.category_id
JOIN categories root ON root.id = cat.parent_id
JOIN product_variants v ON v.product_id = p.id
JOIN colors c  ON c.id  = v.color_id
JOIN sizes  sz ON sz.id = v.size_id
LEFT JOIN product_images img
       ON img.product_id = p.id AND img.color_id = v.color_id;

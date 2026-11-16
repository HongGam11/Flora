<?php

declare(strict_types=1);

final class UserModel
{
    public function __construct(private mysqli $conn) {}

    public function findByEmail(string $email): ?array
    {
        $stmt = $this->conn->prepare(
            'SELECT id, full_name, email, password_hash, role, status FROM users WHERE email = ? LIMIT 1'
        );
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $user ?: null;
    }

    public function createCustomer(string $name, string $email, string $hash): int
    {
        $stmt = $this->conn->prepare(
            "INSERT INTO users (full_name, email, password_hash, role, status) VALUES (?, ?, ?, 'customer', 'active')"
        );
        $stmt->bind_param('sss', $name, $email, $hash);
        $stmt->execute();
        $id = (int) $this->conn->insert_id;
        $stmt->close();
        return $id;
    }
    public function findById(int $id): ?array
    {
        $stmt = $this->conn->prepare(
            'SELECT id, full_name, email, phone, address, role, status
            FROM users
            WHERE id = ?
            LIMIT 1'
        );

        $stmt->bind_param('i', $id);
        $stmt->execute();

        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return $user ?: null;
    }

    public static function validateProfile(string $name, string $phone, string $address): void
    {
        if ($name === '' || preg_match('/^.{1,100}$/us', $name) !== 1) {
            throw new DomainException('Họ tên phải có từ 1 đến 100 ký tự.');
        }
        if ($phone !== '' && preg_match('/^0[0-9]{9}$/D', $phone) !== 1) {
            throw new DomainException('Số điện thoại cần có 10 chữ số, bắt đầu bằng 0.');
        }
        if (preg_match('/^.{0,500}$/us', $address) !== 1) {
            throw new DomainException('Địa chỉ tối đa 500 ký tự.');
        }
    }

    public static function validatePassword(string $password): void
    {
        // Bcrypt chỉ sử dụng tối đa 72 byte; không chấp nhận mật khẩu bị cắt ngầm.
        if (preg_match('/\A.{8,}\z/us', $password) !== 1 || strlen($password) > 72 || str_contains($password, "\0")) {
            throw new DomainException('Mật khẩu cần ít nhất 8 ký tự và tối đa 72 byte; ký tự có dấu chiếm nhiều byte.');
        }
    }

    public function updateProfile(int $id, string $name, string $phone, string $address): void
    {
        self::validateProfile($name, $phone, $address);
        $stmt = $this->conn->prepare("UPDATE users SET full_name=?, phone=?, address=? WHERE id=? AND status='active'");
        $stmt->bind_param('sssi', $name, $phone, $address, $id);
        $stmt->execute();
        $stmt->close();
    }

    public function changePassword(int $id, string $old, string $new): void
    {
        self::validatePassword($new);
        $this->conn->begin_transaction();
        try {
            $stmt = $this->conn->prepare("SELECT password_hash, status FROM users WHERE id=? FOR UPDATE");
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            if (!$row || $row['status'] !== 'active' || !password_verify($old, $row['password_hash'])) {
                throw new DomainException('Mật khẩu hiện tại không đúng hoặc tài khoản đã bị khóa.');
            }
            if (password_verify($new, $row['password_hash'])) {
                throw new DomainException('Mật khẩu mới phải khác mật khẩu hiện tại.');
            }
            $hash = password_hash($new, PASSWORD_DEFAULT);
            $stmt = $this->conn->prepare('UPDATE users SET password_hash=? WHERE id=?');
            $stmt->bind_param('si', $hash, $id);
            $stmt->execute();
            $stmt->close();
            $this->conn->commit();
        } catch (Throwable $error) {
            $this->conn->rollback();
            throw $error;
        }
    }

    public function search(string $query, string $role, string $status, int $page): array
    {
        $where = ' WHERE 1=1'; $types = ''; $values = [];
        if ($query !== '') {
            // LOCATE tìm chuỗi nguyên văn: % và _ không trở thành wildcard.
            $where .= ' AND (LOCATE(?,full_name)>0 OR LOCATE(?,email)>0 OR LOCATE(?,COALESCE(phone,\'\'))>0)';
            $types .= 'sss'; $values = [$query, $query, $query];
        }
        foreach (['role' => $role, 'status' => $status] as $column => $value) {
            if ($value !== '') { $where .= " AND $column=?"; $types .= 's'; $values[] = $value; }
        }
        $stmt = $this->conn->prepare('SELECT COUNT(*) FROM users' . $where);
        if ($values) flora_bind($stmt, $types, $values);
        $stmt->execute(); $count = (int) $stmt->get_result()->fetch_row()[0]; $stmt->close();
        $pages = max(1, (int) ceil($count / 20)); $page = min($page, $pages);
        $offset = ($page - 1) * 20;
        $stmt = $this->conn->prepare('SELECT id,full_name,email,phone,role,status,created_at FROM users' . $where . ' ORDER BY id DESC LIMIT 20 OFFSET ?');
        flora_bind($stmt, $types . 'i', [...$values, $offset]);
        $stmt->execute(); $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC); $stmt->close();
        return compact('rows', 'count', 'page', 'pages');
    }

    /** Serialize admin writes using the oldest user row, which is never deleted by this application. */
    public function administer(int $actorId, string $action, array $data): void
    {
        for ($attempt = 0; ; $attempt++) {
            $this->conn->begin_transaction();
            try {
                $this->conn->query('SELECT id FROM users ORDER BY id LIMIT 1 FOR UPDATE')->fetch_assoc();
                $stmt = $this->conn->prepare('SELECT role,status FROM users WHERE id=? FOR UPDATE');
                $stmt->bind_param('i', $actorId); $stmt->execute();
                $actor = $stmt->get_result()->fetch_assoc(); $stmt->close();
                if (!$actor || $actor['role'] !== 'admin' || $actor['status'] !== 'active') {
                    throw new DomainException('Bạn không còn quyền quản trị tài khoản.');
                }
                if ($action === 'create_staff') {
                    self::validateProfile($data['full_name'], $data['phone'], '');
                    self::validatePassword($data['password']);
                    if (strlen($data['email']) > 255 || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                        throw new DomainException('Email không hợp lệ.');
                    }
                    $hash = password_hash($data['password'], PASSWORD_DEFAULT);
                    $stmt = $this->conn->prepare("INSERT INTO users(full_name,email,phone,password_hash,role,status) VALUES(?,?,?,?,'staff','active')");
                    $stmt->bind_param('ssss', $data['full_name'], $data['email'], $data['phone'], $hash);
                } elseif (in_array($action, ['role', 'status'], true)) {
                    $id = flora_catalog_integer($data['id']);
                    $stmt = $this->conn->prepare('SELECT role,status FROM users WHERE id=? FOR UPDATE');
                    $stmt->bind_param('i', $id); $stmt->execute();
                    $target = $stmt->get_result()->fetch_assoc(); $stmt->close();
                    if (!$target) throw new DomainException('Không tìm thấy tài khoản.');
                    $allowed = $action === 'role' ? ['customer','staff','admin'] : ['active','disabled'];
                    $value = $data['value'];
                    if (!in_array($value, $allowed, true)) throw new DomainException('Vai trò hoặc trạng thái không hợp lệ.');
                    $next = $target; $next[$action] = $value;
                    if ($target['role'] === 'admin' && $target['status'] === 'active'
                        && ($next['role'] !== 'admin' || $next['status'] !== 'active')) {
                        // Locking read avoids a stale transaction snapshot after waiting for the guard.
                        $admins = $this->conn->query("SELECT id FROM users WHERE role='admin' AND status='active' ORDER BY id FOR UPDATE")->num_rows;
                        if ($admins <= 1) throw new DomainException('Phải giữ ít nhất một quản trị viên đang hoạt động.');
                    }
                    $stmt = $this->conn->prepare("UPDATE users SET $action=? WHERE id=?");
                    $stmt->bind_param('si', $value, $id);
                } else {
                    throw new DomainException('Thao tác không hợp lệ.');
                }
                $stmt->execute(); $stmt->close();
                $this->conn->commit();
                return;
            } catch (Throwable $error) {
                $this->conn->rollback();
                if ($error instanceof mysqli_sql_exception && in_array($error->getCode(), [1205,1213], true) && $attempt < 2) continue;
                throw $error;
            }
        }
    }
}

<?php
function flora_e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function flora_bind(mysqli_stmt $statement, string $types, array $values): void
{
    if ($values === []) {
        return;
    }

    $arguments = [$types];
    foreach ($values as $key => &$value) {
        $arguments[] = &$value;
    }
    call_user_func_array([$statement, 'bind_param'], $arguments);
}

function flora_money($value): string
{
    return number_format((float) $value, 0, ',', '.') . '₫';
}

// Tương thích các layout cũ dùng e().
function e($value): string
{
    return flora_e($value);
}

// Dùng chung cho các controller quản trị danh mục, sản phẩm và kho.
function flora_catalog_post(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            http_response_code(405);
            header('Allow: POST');
            exit('Chỉ chấp nhận POST.');
        }
        $token = $_POST['csrf_token'] ?? null;
        if (!is_string($token) || !Csrf::verify($token)) {
            http_response_code(403);
            exit('Yêu cầu không hợp lệ. Vui lòng tải lại trang.');
        }
    }

function flora_catalog_integer(mixed $value, int $min = 1, int $max = 4294967295): int
    {
        if (!is_string($value) && !is_int($value)) throw new DomainException('Số nguyên không hợp lệ.');
        $number = filter_var($value, FILTER_VALIDATE_INT);
        if ($number === false || $number < $min || $number > $max) throw new DomainException('Số nguyên nằm ngoài giới hạn.');
        return $number;
    }
function flora_catalog_id(string $name): int { return flora_catalog_integer($_POST[$name] ?? null); }
function flora_catalog_queryId(string $name): int
    {
        try { return flora_catalog_integer($_GET[$name] ?? null); }
        catch (DomainException) { flora_catalog_notFound(); }
    }
function flora_catalog_optionalId(mixed $value): ?int
    {
        return $value === null || $value === '' ? null : flora_catalog_integer($value);
    }
function flora_catalog_text(string $name): string
    {
        $value = $_POST[$name] ?? '';
        if (!is_string($value)) throw new DomainException('Dữ liệu nhập không hợp lệ.');
        return trim($value);
    }
function flora_catalog_notFound(): never
    {
        http_response_code(404);
        require APP_ROOT . '/app/views/errors/404.php';
        exit;
    }
function flora_catalog_page(string $view, array $data = []): void
    {
        $title = 'Quản lý hàng hóa | Flora';
        $csrf = Csrf::token();
        extract($data, EXTR_SKIP);
        require APP_ROOT . '/app/views/layouts/admin_header.php';
        require APP_ROOT . '/app/views/layouts/admin_sidebar.php';
        require APP_ROOT . '/app/views/admin/' . $view . '.php';
        require APP_ROOT . '/app/views/layouts/admin_footer.php';
    }
function flora_catalog_redirect(string $route, array $params = []): never
    {
        header('Location: /flora/index.php?' . http_build_query(['route' => $route] + $params), true, 303);
        exit;
    }
function flora_catalog_handle(callable $action, string $backRoute, array $params = []): void
    {
        flora_catalog_post();
        try { $action(); }
        catch (DomainException $error) {
            $_SESSION['flash_message'] = ['type' => 'error', 'text' => $error->getMessage()];
            flora_catalog_redirect($backRoute, $params);
        } catch (mysqli_sql_exception $error) {
            error_log('Flora catalog SQL code ' . $error->getCode());
            $_SESSION['flash_message'] = ['type' => 'error', 'text' => $error->getCode() === 1062
                ? 'Dữ liệu bị trùng. Kiểm tra tên danh mục hoặc tổ hợp size và màu.'
                : 'Không lưu được dữ liệu. Vui lòng thử lại hoặc xem nhật ký máy chủ.'];
            flora_catalog_redirect($backRoute, $params);
        }
    }
function flora_catalog_success(string $message): void
    {
        $_SESSION['flash_message'] = ['type' => 'success', 'text' => $message];
    }

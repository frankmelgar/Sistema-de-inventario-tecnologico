<?php

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/catalog.php';

function requireAdminAccess(): void
{
    if (!isAdmin()) {
        global $conn;
        if (isset($conn) && $conn instanceof mysqli) {
            auditLog($conn, 'admin_access_denied', 'admin_route', null, [
                'route' => $_SERVER['REQUEST_URI'] ?? '',
            ]);
        }

        header('Location: index.php');
        exit;
    }
}

function adminCategoryBrandMap(): array
{
    return [
        'TELEFONOS' => ['Apple', 'Samsung', 'Google', 'Xiaomi', 'Motorola', 'Honor', 'OnePlus', 'Sony', 'ASUS', 'Oppo', 'vivo', 'Huawei', 'Nokia', 'Poco', 'ZTE'],
        'PORTATILES' => ['Apple', 'Dell', 'HP', 'Lenovo', 'ASUS', 'Acer', 'MSI', 'Razer', 'Gigabyte', 'Samsung', 'LG', 'Microsoft', 'Huawei', 'Framework'],
        'ACCESORIOS_ELECTRONICOS' => ['Sony', 'Apple', 'UGREEN', 'Anker', 'Logitech', 'HyperX', 'JBL', 'Samsung', 'CalDigit', 'SanDisk'],
        'CPUS' => ['Intel', 'AMD'],
        'TABLETS' => ['Apple', 'Samsung', 'Xiaomi', 'Lenovo', 'Microsoft', 'Huawei', 'OnePlus', 'Honor', 'Amazon', 'Nokia', 'Doogee', 'Teclast', 'Blackview']
    ];
}

function adminNavItems(): array
{
    return [
        'dashboard' => ['href' => 'admin.php', 'icon' => 'bi-speedometer2', 'label' => 'Dashboard'],
        'telefonos' => ['href' => 'admin-telefonos.php', 'icon' => 'bi-phone', 'label' => 'Telefonos'],
        'portatiles' => ['href' => 'admin-portatiles.php', 'icon' => 'bi-laptop', 'label' => 'Portatiles'],
        'accesorios' => ['href' => 'admin-accesorios.php', 'icon' => 'bi-headphones', 'label' => 'Accesorios'],
        'procesadores' => ['href' => 'admin-procesadores.php', 'icon' => 'bi-cpu', 'label' => 'Procesadores'],
        'tablets' => ['href' => 'admin-tablets.php', 'icon' => 'bi-tablet', 'label' => 'Tablets'],
        'pedidos' => ['href' => 'admin-pedidos.php', 'icon' => 'bi-bag-check', 'label' => 'Pedidos'],
        'apariencia' => ['href' => 'admin-apariencia.php', 'icon' => 'bi-palette', 'label' => 'Apariencia']
    ];
}

function adminCategoryPageRoutes(): array
{
    return [
        'TELEFONOS' => 'admin-telefonos.php',
        'PORTATILES' => 'admin-portatiles.php',
        'ACCESORIOS_ELECTRONICOS' => 'admin-accesorios.php',
        'CPUS' => 'admin-procesadores.php',
        'TABLETS' => 'admin-tablets.php'
    ];
}

function adminFetchSettings(mysqli $conn): array
{
    return fetchAppSettings($conn);
}

function adminUploadImageOrKeep(string $field, string $currentPath, string $targetDir, string &$error): string
{
    if ($error !== '') {
        return $currentPath;
    }

    if (!isset($_FILES[$field]) || $_FILES[$field]['error'] === UPLOAD_ERR_NO_FILE) {
        return $currentPath;
    }

    if ($_FILES[$field]['error'] !== UPLOAD_ERR_OK) {
        $error = "No se pudo subir el archivo de {$field}.";
        return $currentPath;
    }

    $targetDir = trim(str_replace('\\', '/', $targetDir), '/');
    $allowedTargetDirs = ['assets', 'uploads/productos'];
    if (!in_array($targetDir, $allowedTargetDirs, true)) {
        $error = "Destino de subida no permitido para {$field}.";
        return $currentPath;
    }

    $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp', 'avif'];
    $extension = strtolower(pathinfo($_FILES[$field]['name'], PATHINFO_EXTENSION));

    if ($extension === 'svg') {
        $error = "Por seguridad, no se permiten nuevas subidas SVG en {$field}.";
        return $currentPath;
    }

    if (!in_array($extension, $allowedExtensions, true)) {
        $error = "El archivo {$field} debe ser una imagen valida.";
        return $currentPath;
    }

    if (!is_uploaded_file($_FILES[$field]['tmp_name'])) {
        $error = "La subida de {$field} no es valida.";
        return $currentPath;
    }

    $imageInfo = @getimagesize($_FILES[$field]['tmp_name']);
    if ($imageInfo === false) {
        $error = "El archivo {$field} no es una imagen valida.";
        return $currentPath;
    }

    $allowedMimes = [
        'jpg' => ['image/jpeg'],
        'jpeg' => ['image/jpeg'],
        'png' => ['image/png'],
        'webp' => ['image/webp'],
        'avif' => ['image/avif', 'application/octet-stream'],
    ];
    $detectedMime = '';
    if (function_exists('finfo_open')) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        if ($finfo) {
            $detectedMime = (string) finfo_file($finfo, $_FILES[$field]['tmp_name']);
            finfo_close($finfo);
        }
    }
    if ($detectedMime !== '' && !in_array($detectedMime, $allowedMimes[$extension], true)) {
        $error = "El contenido de {$field} no coincide con una imagen permitida.";
        return $currentPath;
    }

    $maxBytes = max(1, (int) appConfig('UPLOAD_MAX_BYTES', (string) (5 * 1024 * 1024)));
    if ($_FILES[$field]['size'] > $maxBytes) {
        $error = "El archivo {$field} supera el limite configurado.";
        return $currentPath;
    }

    $absoluteTargetDir = dirname(__DIR__) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $targetDir);
    if (!is_dir($absoluteTargetDir)) {
        mkdir($absoluteTargetDir, 0755, true);
    }

    $safeBase = preg_replace('/[^A-Za-z0-9_-]/', '_', pathinfo($_FILES[$field]['name'], PATHINFO_FILENAME));
    $safeBase = trim((string) $safeBase, '_') ?: 'image';
    $fileName = bin2hex(random_bytes(12)) . '_' . $safeBase . '.' . $extension;
    $absolutePath = $absoluteTargetDir . DIRECTORY_SEPARATOR . $fileName;
    $relativePath = $targetDir . '/' . $fileName;

    if (!move_uploaded_file($_FILES[$field]['tmp_name'], $absolutePath)) {
        $error = "No se pudo guardar el archivo de {$field}.";
        return $currentPath;
    }

    return $relativePath;
}

function adminDashboardStats(mysqli $conn): array
{
    $products = $conn->query("
        SELECT
            COUNT(*) AS total_products,
            SUM(is_active = 1) AS active_products,
            SUM(is_featured = 1) AS featured_products,
            COUNT(DISTINCT categoria) AS active_categories
        FROM products
    ")->fetch_assoc() ?: [];

    $orders = $conn->query("
        SELECT
            COUNT(*) AS total_orders,
            SUM(payment_status = 'Pagado') AS paid_orders,
            COALESCE(SUM(total), 0) AS revenue
        FROM orders
    ")->fetch_assoc() ?: [];

    $users = $conn->query("SELECT COUNT(*) AS total_users FROM usuarios")->fetch_assoc() ?: [];

    $latestOrder = $conn->query("SELECT MAX(created_at) AS latest_order FROM orders")->fetch_assoc() ?: [];
    $lastActivity = (string) ($latestOrder['latest_order'] ?? '');

    $categories = [];
    $categoryMap = catalogCategoryMap();
    $routes = adminCategoryPageRoutes();
    $categoryStats = $conn->query("
        SELECT categoria, COUNT(*) AS total, SUM(is_active = 1) AS active_total, SUM(is_featured = 1) AS featured_total
        FROM products
        GROUP BY categoria
        ORDER BY categoria
    ");

    while ($row = $categoryStats->fetch_assoc()) {
        $key = $row['categoria'];
        $categories[] = [
            'key' => $key,
            'label' => $categoryMap[$key] ?? $key,
            'route' => $routes[$key] ?? 'admin.php',
            'total' => (int) ($row['total'] ?? 0),
            'active_total' => (int) ($row['active_total'] ?? 0),
            'featured_total' => (int) ($row['featured_total'] ?? 0)
        ];
    }

    return [
        'summary' => [
            'total_products' => (int) ($products['total_products'] ?? 0),
            'active_products' => (int) ($products['active_products'] ?? 0),
            'featured_products' => (int) ($products['featured_products'] ?? 0),
            'active_categories' => (int) ($products['active_categories'] ?? 0),
            'total_orders' => (int) ($orders['total_orders'] ?? 0),
            'paid_orders' => (int) ($orders['paid_orders'] ?? 0),
            'revenue' => (float) ($orders['revenue'] ?? 0),
            'total_users' => (int) ($users['total_users'] ?? 0),
            'last_activity' => $lastActivity
        ],
        'categories' => $categories
    ];
}

function adminCategoryStats(mysqli $conn, string $category): array
{
    $stmt = $conn->prepare("
        SELECT
            COUNT(*) AS total,
            SUM(is_active = 1) AS active_total,
            SUM(is_featured = 1) AS featured_total,
            COUNT(DISTINCT brand) AS brand_total
        FROM products
        WHERE categoria = ?
    ");
    $stmt->bind_param('s', $category);
    $stmt->execute();

    return $stmt->get_result()->fetch_assoc() ?: ['total' => 0, 'active_total' => 0, 'featured_total' => 0, 'brand_total' => 0];
}

function adminProductsByCategory(mysqli $conn, string $category): mysqli_result
{
    $stmt = $conn->prepare("SELECT * FROM products WHERE categoria = ? ORDER BY is_featured DESC, id DESC");
    $stmt->bind_param('s', $category);
    $stmt->execute();
    return $stmt->get_result();
}

function adminProductById(mysqli $conn, int $productId): ?array
{
    $stmt = $conn->prepare("SELECT * FROM products WHERE id = ? LIMIT 1");
    $stmt->bind_param('i', $productId);
    $stmt->execute();
    $product = $stmt->get_result()->fetch_assoc();

    return $product ?: null;
}

function adminOrdersResult(mysqli $conn): mysqli_result
{
    return $conn->query("
        SELECT
            o.id AS order_id,
            o.*,
            u.nombre
        FROM orders o
        LEFT JOIN usuarios u ON o.user_id = u.id
        ORDER BY o.created_at DESC
    ");
}

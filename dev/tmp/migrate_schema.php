<?php
require __DIR__ . '/../../config/database.php';

function ensureColumn(mysqli $conn, string $table, string $column, string $definition): void {
    $result = $conn->query("SHOW COLUMNS FROM `$table` LIKE '$column'");
    if ($result && $result->num_rows === 0) {
        if (!$conn->query("ALTER TABLE `$table` ADD COLUMN `$column` $definition")) {
            throw new Exception($conn->error);
        }
        echo "added:$table.$column" . PHP_EOL;
    } else {
        echo "exists:$table.$column" . PHP_EOL;
    }
}

ensureColumn($conn, 'products', 'brand', "VARCHAR(100) NULL AFTER categoria");
ensureColumn($conn, 'products', 'subcategoria', "VARCHAR(100) NULL AFTER brand");
ensureColumn($conn, 'products', 'is_featured', "TINYINT(1) NOT NULL DEFAULT 0 AFTER subcategoria");
ensureColumn($conn, 'products', 'is_active', "TINYINT(1) NOT NULL DEFAULT 1 AFTER is_featured");
ensureColumn($conn, 'orders', 'customer_name', "VARCHAR(120) NULL AFTER payment_status");
ensureColumn($conn, 'orders', 'customer_email', "VARCHAR(120) NULL AFTER customer_name");
ensureColumn($conn, 'orders', 'customer_phone', "VARCHAR(40) NULL AFTER customer_email");
ensureColumn($conn, 'orders', 'shipping_address', "VARCHAR(255) NULL AFTER customer_phone");

$featuredSet = ['iPhone 15 Pro Max','Samsung Galaxy S24 Ultra','MacBook Air M3 13','Dell XPS 14','iPad Pro 13 M4','Intel Core i9-14900K'];
$result = $conn->query("SELECT id, name, categoria, brand, subcategoria FROM products");
while ($row = $result->fetch_assoc()) {
    $brand = trim((string) ($row['brand'] ?? ''));
    $sub = trim((string) ($row['subcategoria'] ?? ''));
    $name = $row['name'];
    $category = $row['categoria'];

    if ($brand === '') {
        $brand = preg_split('/\s+/', $name)[0] ?? 'General';
        if (stripos($name, 'iPhone') !== false || stripos($name, 'iPad') !== false || stripos($name, 'MacBook') !== false || stripos($name, 'AirPods') !== false) {
            $brand = 'Apple';
        } elseif (stripos($name, 'Galaxy') !== false) {
            $brand = 'Samsung';
        } elseif (stripos($name, 'Ryzen') !== false || stripos($name, 'Threadripper') !== false) {
            $brand = 'AMD';
        } elseif (stripos($name, 'Intel') !== false || stripos($name, 'Xeon') !== false || stripos($name, 'Core ') !== false) {
            $brand = 'Intel';
        }
    }

    if ($sub === '') {
        $defaults = [
            'TELEFONOS' => 'Gama Alta',
            'PORTATILES' => 'Ultrabook',
            'ACCESORIOS_ELECTRONICOS' => 'Productividad',
            'CPUS' => 'Gaming',
            'TABLETS' => 'Premium'
        ];
        $sub = $defaults[$category] ?? 'General';
    }

    $isFeatured = in_array($name, $featuredSet, true) ? 1 : 0;
    $stmt = $conn->prepare("UPDATE products SET brand = ?, subcategoria = ?, is_featured = CASE WHEN ? = 1 THEN 1 ELSE is_featured END, is_active = 1 WHERE id = ?");
    $stmt->bind_param('ssii', $brand, $sub, $isFeatured, $row['id']);
    $stmt->execute();
}

echo "done" . PHP_EOL;

<?php
require __DIR__ . '/../../config/database.php';
$result = $conn->query("SELECT id, name, categoria, brand, subcategoria, is_featured, is_active FROM products ORDER BY id DESC LIMIT 5");
while ($row = $result->fetch_assoc()) {
    echo implode('|', [$row['id'], $row['name'], $row['categoria'], $row['brand'], $row['subcategoria'], $row['is_featured'], $row['is_active']]) . PHP_EOL;
}

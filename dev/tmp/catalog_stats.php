<?php
require __DIR__ . '/../../config/database.php';
$row = $conn->query('SELECT COUNT(*) AS total, SUM(is_featured = 1) AS featured, SUM(is_active = 1) AS active FROM products')->fetch_assoc();
echo $row['total'] . '|' . $row['featured'] . '|' . $row['active'] . PHP_EOL;

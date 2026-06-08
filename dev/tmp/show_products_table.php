<?php
require __DIR__ . '/../../config/database.php';
$result = $conn->query('SHOW CREATE TABLE products');
$row = $result->fetch_assoc();
echo $row['Create Table'] . PHP_EOL;

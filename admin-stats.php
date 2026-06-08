<?php
require __DIR__ . '/includes/admin_helpers.php';

requireAdminAccess();
header('Content-Type: application/json; charset=utf-8');
echo json_encode(adminDashboardStats($conn), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

<?php
require_once __DIR__ . '/../../includes/bootstrap.php';

auditLog($conn, 'role_switch_forbidden', 'usuarios', isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null, [
    'route' => $_SERVER['REQUEST_URI'] ?? '',
]);
http_response_code(403);
header("Location: index.php");
exit;

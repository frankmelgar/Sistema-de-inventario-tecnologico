<?php
require_once __DIR__ . '/../../includes/bootstrap.php';

if (!isPostRequest()) {
    http_response_code(405);
    exit('Metodo no permitido.');
}

requireValidCsrfToken();

auditLog($conn, 'logout', 'usuarios', isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null);
session_unset();
session_destroy();

header("Location: index.php");
exit;

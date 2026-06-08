<?php
require_once __DIR__ . '/../../includes/bootstrap.php';

if (!isPostRequest()) {
    http_response_code(405);
    exit('Metodo no permitido.');
}

requireValidCsrfToken();
signInUser(null, 'Invitado', 'guest');
auditLog($conn, 'guest_login', 'usuarios', null);

header("Location: index.php");
exit;

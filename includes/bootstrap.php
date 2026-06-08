<?php

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/security.php';

if (PHP_SAPI !== 'cli' && session_status() === PHP_SESSION_NONE) {
    configureSecureSession();
    session_start();
    sendSecurityHeaders();
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/media.php';
require_once __DIR__ . '/validation.php';
require_once __DIR__ . '/settings.php';
require_once __DIR__ . '/audit.php';
require_once __DIR__ . '/rate_limit.php';

ensureSecuritySchema($conn);

<?php

function configureSecureSession(): void
{
    $secureCookies = filter_var((string) appConfig('SESSION_SECURE_COOKIES', 'false'), FILTER_VALIDATE_BOOLEAN);
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');

    // Cookies de sesion endurecidas para reducir robo de sesion y CSRF cross-site.
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => $secureCookies || $isHttps,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
}

function sendSecurityHeaders(): void
{
    if (headers_sent()) {
        return;
    }

    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
}

function isPostRequest(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}

function clientIpAddress(): string
{
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    return substr((string) $ip, 0, 45);
}

function currentUserAgent(): string
{
    return substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255);
}

function csrfToken(): string
{
    if (!isset($_SESSION['_csrf_token']) || !is_string($_SESSION['_csrf_token']) || $_SESSION['_csrf_token'] === '') {
        $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['_csrf_token'];
}

function csrfInput(): string
{
    return '<input type="hidden" name="_csrf" value="' . htmlspecialchars(csrfToken(), ENT_QUOTES, 'UTF-8') . '">';
}

function isValidCsrfToken(?string $token): bool
{
    $sessionToken = $_SESSION['_csrf_token'] ?? '';
    return is_string($token) && is_string($sessionToken) && $sessionToken !== '' && hash_equals($sessionToken, $token);
}

function requireValidCsrfToken(): void
{
    if (!isValidCsrfToken($_POST['_csrf'] ?? null)) {
        http_response_code(419);
        exit('Solicitud invalida. Recarga la pagina e intentalo de nuevo.');
    }
}

function ensureCartSession(): void
{
    if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }
}

function signInUser(?int $userId, string $name, string $role): void
{
    session_regenerate_id(true);
    $_SESSION['user_id'] = $userId;
    $_SESSION['nombre'] = $name;
    $_SESSION['role'] = $role;
    ensureCartSession();
}

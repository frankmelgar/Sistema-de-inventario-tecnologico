<?php

function loadEnvFile(string $path): void
{
    if (!is_file($path) || !is_readable($path)) {
        return;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines === false) {
        return;
    }

    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
            continue;
        }

        [$key, $value] = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value);

        if ($key === '' || isset($_ENV[$key]) || getenv($key) !== false) {
            continue;
        }

        if (
            (str_starts_with($value, '"') && str_ends_with($value, '"')) ||
            (str_starts_with($value, "'") && str_ends_with($value, "'"))
        ) {
            $value = substr($value, 1, -1);
        }

        $_ENV[$key] = $value;
        $_SERVER[$key] = $value;
        putenv($key . '=' . $value);
    }
}

loadEnvFile(__DIR__ . '/../.env');

function envValue(string $key, ?string $default = null): ?string
{
    $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);
    if ($value === false || $value === null || $value === '') {
        return $default;
    }

    return (string) $value;
}

function appConfig(string $key, mixed $default = null): mixed
{
    static $config = null;

    if ($config === null) {
        $config = [
            'APP_ENV' => envValue('APP_ENV', 'local'),
            'APP_NAME' => envValue('APP_NAME', 'SMART TECH SOLUTIONS'),
            'APP_URL' => envValue('APP_URL', 'http://localhost'),
            'DB_HOST' => envValue('DB_HOST'),
            'DB_DATABASE' => envValue('DB_DATABASE'),
            'DB_USERNAME' => envValue('DB_USERNAME'),
            'DB_PASSWORD' => envValue('DB_PASSWORD', ''),
            'SESSION_SECURE_COOKIES' => envValue('SESSION_SECURE_COOKIES', 'false'),
            'LOGIN_MAX_ATTEMPTS' => envValue('LOGIN_MAX_ATTEMPTS', '5'),
            'LOGIN_DECAY_SECONDS' => envValue('LOGIN_DECAY_SECONDS', '900'),
            'LOGIN_LOCK_SECONDS' => envValue('LOGIN_LOCK_SECONDS', '900'),
            'UPLOAD_MAX_BYTES' => envValue('UPLOAD_MAX_BYTES', (string) (5 * 1024 * 1024)),
        ];
    }

    return $config[$key] ?? $default;
}

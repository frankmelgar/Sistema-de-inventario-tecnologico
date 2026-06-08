<?php

function loginRateLimitConfig(): array
{
    return [
        'max_attempts' => max(1, (int) appConfig('LOGIN_MAX_ATTEMPTS', '5')),
        'decay_seconds' => max(60, (int) appConfig('LOGIN_DECAY_SECONDS', '900')),
        'lock_seconds' => max(60, (int) appConfig('LOGIN_LOCK_SECONDS', '900')),
    ];
}

function loginRateLimitCutoff(mysqli $conn, string $ipAddress, int $decaySeconds): int
{
    $cutoff = time() - $decaySeconds;
    $stmt = $conn->prepare('SELECT MAX(attempted_at) AS latest_success FROM login_attempts WHERE ip_address = ? AND successful = 1');
    if (!$stmt) {
        return $cutoff;
    }

    $stmt->bind_param('s', $ipAddress);
    $stmt->execute();
    $latest = $stmt->get_result()->fetch_assoc()['latest_success'] ?? null;
    $latestTime = $latest ? strtotime((string) $latest) : false;

    return $latestTime && $latestTime > $cutoff ? $latestTime : $cutoff;
}

function loginRateLimitState(mysqli $conn): array
{
    $config = loginRateLimitConfig();
    $ipAddress = clientIpAddress();
    $cutoffTime = loginRateLimitCutoff($conn, $ipAddress, $config['decay_seconds']);
    $cutoff = date('Y-m-d H:i:s', $cutoffTime);

    $stmt = $conn->prepare("
        SELECT COUNT(*) AS failed_count, MAX(attempted_at) AS latest_failed
        FROM login_attempts
        WHERE ip_address = ? AND successful = 0 AND attempted_at >= ?
    ");

    if (!$stmt) {
        return ['blocked' => false, 'remaining_seconds' => 0, 'failed_count' => 0];
    }

    $stmt->bind_param('ss', $ipAddress, $cutoff);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc() ?: [];

    $failedCount = (int) ($row['failed_count'] ?? 0);
    $latestFailedTime = !empty($row['latest_failed']) ? strtotime((string) $row['latest_failed']) : false;
    $remainingSeconds = 0;

    if ($failedCount >= $config['max_attempts'] && $latestFailedTime) {
        $remainingSeconds = max(0, ($latestFailedTime + $config['lock_seconds']) - time());
    }

    return [
        'blocked' => $remainingSeconds > 0,
        'remaining_seconds' => $remainingSeconds,
        'failed_count' => $failedCount,
    ];
}

function recordLoginAttempt(mysqli $conn, string $email, bool $successful): void
{
    $ipAddress = clientIpAddress();
    $userAgent = currentUserAgent();
    $email = substr($email, 0, 190);
    $successfulInt = $successful ? 1 : 0;

    // Cada intento queda persistido para auditoria y bloqueo temporal por IP.
    $stmt = $conn->prepare('INSERT INTO login_attempts (ip_address, email, successful, user_agent) VALUES (?, ?, ?, ?)');
    if ($stmt) {
        $stmt->bind_param('ssis', $ipAddress, $email, $successfulInt, $userAgent);
        $stmt->execute();
    }

    auditLog($conn, $successful ? 'login_success' : 'login_failed', 'usuarios', null, [
        'email' => $email,
        'ip' => $ipAddress,
    ]);
}

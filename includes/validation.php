<?php

function cleanText(string $value, int $maxLength): string
{
    $value = trim($value);
    $value = preg_replace('/[\x00-\x1F\x7F]/u', '', $value) ?? '';
    if (function_exists('mb_substr')) {
        return mb_substr($value, 0, $maxLength, 'UTF-8');
    }

    return substr($value, 0, $maxLength);
}

function isValidHexColor(string $value): bool
{
    return preg_match('/^#[0-9A-Fa-f]{6}$/', $value) === 1;
}

function cleanCssFontFamily(string $value): string
{
    $value = cleanText($value, 80);
    if (preg_match('/^[A-Za-z0-9 ,"._-]+$/', $value) !== 1 || preg_match('/[A-Za-z]/', $value) !== 1) {
        return 'Segoe UI';
    }

    return $value;
}

function normalizedQuantity(int $quantity, int $max = 99): int
{
    return max(1, min($max, $quantity));
}

function allowedPaymentMethods(): array
{
    return ['Tarjeta', 'PayPal', 'Transferencia'];
}

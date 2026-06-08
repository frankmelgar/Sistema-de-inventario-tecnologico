<?php

function isPlaceholderLikeImage(?string $path): bool
{
    if ($path === null || trim($path) === '') {
        return true;
    }

    $path = trim($path);
    $knownPlaceholder = 'uploads/productos/1766320008_iphone-15-pro-finish-select-202309-6-7inch-blacktitanium.jpg';

    return $path === $knownPlaceholder || str_contains($path, 'product-placeholder.php');
}

function productImageUrl(array $product): string
{
    $path = trim((string) ($product['image'] ?? ''));

    if ($path !== '' && !isPlaceholderLikeImage($path)) {
        return $path;
    }

    $params = http_build_query([
        'name' => $product['name'] ?? 'Producto',
        'brand' => $product['brand'] ?? '',
        'category' => $product['categoria'] ?? '',
        'subcategory' => $product['subcategoria'] ?? ''
    ]);

    return 'assets/product-placeholder.php?' . $params;
}

<?php
declare(strict_types=1);

$limit = static function (string $value, int $max): string {
    $value = trim(preg_replace('/[\x00-\x1F\x7F]/u', '', $value) ?? '');
    if (function_exists('mb_strimwidth')) {
        return mb_strimwidth($value, 0, $max, '...');
    }

    return strlen($value) > $max ? substr($value, 0, max(0, $max - 3)) . '...' : $value;
};

$name = $limit((string) ($_GET['name'] ?? 'Producto'), 34);
$brand = $limit((string) ($_GET['brand'] ?? 'Marca'), 18);
$category = $limit((string) ($_GET['category'] ?? 'TECNOLOGIA'), 40);
$subcategory = $limit((string) ($_GET['subcategory'] ?? 'Linea premium'), 22);

$palette = [
    'TELEFONOS' => ['#0f172a', '#334155', '#e2e8f0'],
    'PORTATILES' => ['#1f2937', '#4b5563', '#f9fafb'],
    'ACCESORIOS_ELECTRONICOS' => ['#0c4a6e', '#0891b2', '#ecfeff'],
    'CPUS' => ['#3f1d5e', '#7c3aed', '#f5f3ff'],
    'TABLETS' => ['#14532d', '#22c55e', '#f0fdf4']
];

[$bg1, $bg2, $fg] = $palette[$category] ?? ['#111827', '#6b7280', '#ffffff'];

$safe = static function (string $value): string {
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
};

$brand = $brand !== '' ? $brand : 'Marca';
$categoryLabel = str_replace('_', ' ', $category);
$subcategory = $subcategory !== '' ? $subcategory : 'Demo visual';

header('Content-Type: image/svg+xml; charset=UTF-8');
header("Content-Security-Policy: default-src 'none'; style-src 'unsafe-inline'");
header('X-Content-Type-Options: nosniff');
echo <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="1200" height="900" viewBox="0 0 1200 900" role="img" aria-label="{$safe($name)}">
  <defs>
    <linearGradient id="bg" x1="0%" y1="0%" x2="100%" y2="100%">
      <stop offset="0%" stop-color="{$safe($bg1)}"/>
      <stop offset="100%" stop-color="{$safe($bg2)}"/>
    </linearGradient>
    <radialGradient id="glow" cx="50%" cy="35%" r="65%">
      <stop offset="0%" stop-color="rgba(255,255,255,0.35)"/>
      <stop offset="100%" stop-color="rgba(255,255,255,0)"/>
    </radialGradient>
  </defs>
  <rect width="1200" height="900" fill="url(#bg)"/>
  <circle cx="920" cy="180" r="220" fill="rgba(255,255,255,0.08)"/>
  <circle cx="260" cy="760" r="240" fill="rgba(255,255,255,0.07)"/>
  <rect x="120" y="110" rx="42" ry="42" width="960" height="680" fill="rgba(255,255,255,0.08)" stroke="rgba(255,255,255,0.14)"/>
  <rect x="170" y="180" rx="34" ry="34" width="860" height="430" fill="rgba(255,255,255,0.07)"/>
  <rect x="220" y="240" rx="28" ry="28" width="300" height="300" fill="rgba(255,255,255,0.12)"/>
  <rect x="570" y="240" rx="28" ry="28" width="170" height="170" fill="rgba(255,255,255,0.10)"/>
  <rect x="780" y="240" rx="28" ry="28" width="170" height="170" fill="rgba(255,255,255,0.08)"/>
  <rect x="570" y="440" rx="28" ry="28" width="380" height="100" fill="rgba(255,255,255,0.08)"/>
  <text x="220" y="150" fill="{$safe($fg)}" font-size="34" font-family="Segoe UI, Arial, sans-serif" opacity="0.92">{$safe($brand)}</text>
  <text x="220" y="670" fill="{$safe($fg)}" font-size="52" font-weight="700" font-family="Segoe UI, Arial, sans-serif">{$safe($name)}</text>
  <text x="220" y="725" fill="{$safe($fg)}" font-size="26" font-family="Segoe UI, Arial, sans-serif" opacity="0.82">{$safe($subcategory)}</text>
  <text x="220" y="765" fill="{$safe($fg)}" font-size="22" font-family="Segoe UI, Arial, sans-serif" opacity="0.68">{$safe($categoryLabel)} · Catalogo demo premium</text>
  <text x="370" y="410" text-anchor="middle" fill="{$safe($fg)}" font-size="108" font-family="Segoe UI Symbol, Arial, sans-serif" opacity="0.94">⌁</text>
  <text x="655" y="340" text-anchor="middle" fill="{$safe($fg)}" font-size="56" font-family="Segoe UI Symbol, Arial, sans-serif" opacity="0.90">◆</text>
  <text x="865" y="340" text-anchor="middle" fill="{$safe($fg)}" font-size="56" font-family="Segoe UI Symbol, Arial, sans-serif" opacity="0.90">●</text>
  <text x="760" y="500" text-anchor="middle" fill="{$safe($fg)}" font-size="28" font-family="Segoe UI, Arial, sans-serif" opacity="0.88">Imagen lista para reemplazo</text>
  <text x="760" y="535" text-anchor="middle" fill="{$safe($fg)}" font-size="22" font-family="Segoe UI, Arial, sans-serif" opacity="0.64">Branding coherente para demo comercial</text>
  <rect x="220" y="805" rx="18" ry="18" width="260" height="44" fill="rgba(255,255,255,0.10)"/>
  <text x="350" y="835" text-anchor="middle" fill="{$safe($fg)}" font-size="20" font-family="Segoe UI, Arial, sans-serif">HOLAMUNDO TECH STORE</text>
</svg>
SVG;

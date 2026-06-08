<?php
require __DIR__ . '/../../includes/bootstrap.php';

$logo = 'assets/sts-logo-v3.svg';
$banner = 'assets/sts-banner-v3.svg';
$background = 'assets/sts-background-v3.svg';
$siteTitle = 'SMART TECH SOLUTIONS';
$bannerText = 'Tecnología profesional para vender con confianza';
$footerText = 'SMART TECH SOLUTIONS · Catálogo tecnológico premium para empresas, retail y clientes exigentes.';

$stmt = $conn->prepare('UPDATE settings SET logo = ?, banner = ?, background = ?, site_title = ?, banner_text = ?, footer_text = ? WHERE id = 1');
$stmt->bind_param('ssssss', $logo, $banner, $background, $siteTitle, $bannerText, $footerText);
$stmt->execute();

$result = $conn->query('SELECT site_title, logo, banner, background, banner_text, footer_text FROM settings WHERE id = 1');
$row = $result->fetch_assoc();

foreach ($row as $key => $value) {
    echo $key . '=' . $value . PHP_EOL;
}

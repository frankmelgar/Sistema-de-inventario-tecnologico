<?php
require "config/database.php";
$stmt = $conn->prepare("UPDATE settings SET logo=?, banner=?, background=?, banner_text=?, footer_text=? WHERE id = 1");
$logo = 'assets/sts-logo-v2.svg';
$banner = 'assets/sts-banner-v2.svg';
$background = 'assets/sts-background-v2.svg';
$bannerText = 'Soluciones tecnológicas profesionales para empresas, equipos y marcas modernas';
$footerText = 'SMART TECH SOLUTIONS · Tecnología profesional, catálogo premium y experiencia confiable.';
$stmt->bind_param('sssss', $logo, $banner, $background, $bannerText, $footerText);
$stmt->execute();
$result = $conn->query("SELECT logo, banner, background, banner_text FROM settings WHERE id = 1");
$row = $result->fetch_assoc();
echo implode('|', $row) . PHP_EOL;

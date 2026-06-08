<?php
require "config/database.php";
$stmt = $conn->prepare("UPDATE settings SET banner_text = ?, footer_text = ? WHERE id = 1");
$bannerText = 'Soluciones tecnológicas profesionales para marcas, empresas y equipos modernos';
$footerText = 'SMART TECH SOLUTIONS · Tecnología profesional, catálogo premium y experiencia confiable.';
$stmt->bind_param('ss', $bannerText, $footerText);
$stmt->execute();
echo 'updated' . PHP_EOL;

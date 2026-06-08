<?php
require "config/database.php";
$stmt = $conn->prepare("UPDATE settings SET site_title=?, logo=?, banner=?, background=?, primary_color=?, secondary_color=?, font_family=?, font_size=?, banner_text=?, footer_text=? WHERE id=1");
$title = 'SMART TECH SOLUTIONS';
$logo = 'assets/smart-tech-logo.svg';
$banner = 'assets/smart-tech-banner.svg';
$background = 'assets/smart-tech-background.svg';
$primary = '#4fdfff';
$secondary = '#0f2f57';
$font = 'Segoe UI';
$size = 15;
$bannerText = 'Tecnologia premium para negocios que quieren vender mejor';
$footerText = 'SMART TECH SOLUTIONS · Catalogo inteligente, experiencia premium.';
$stmt->bind_param('ssssssssss', $logo, $banner, $background, $primary, $secondary, $font, $size, $title, $bannerText, $footerText);
$stmt->execute();
echo 'updated' . PHP_EOL;

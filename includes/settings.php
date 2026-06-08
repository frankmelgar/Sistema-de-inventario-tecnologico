<?php

function defaultAppSettings(): array
{
    return [
        'logo' => '',
        'banner' => '',
        'background' => '',
        'primary_color' => '#0d6efd',
        'secondary_color' => '#6c757d',
        'font_family' => 'Segoe UI',
        'font_size' => 14,
        'site_title' => 'SMART TECH SOLUTIONS',
        'banner_text' => 'Tecnologia profesional para vender con confianza',
        'footer_text' => 'SMART TECH SOLUTIONS',
    ];
}

function normalizeAppSettings(array $settings): array
{
    $settings = array_merge(defaultAppSettings(), $settings);

    // Normaliza datos heredados antes de renderizarlos en HTML/CSS.
    $settings['logo'] = cleanText((string) $settings['logo'], 255);
    $settings['banner'] = cleanText((string) $settings['banner'], 255);
    $settings['background'] = cleanText((string) $settings['background'], 255);
    $settings['primary_color'] = isValidHexColor((string) $settings['primary_color']) ? $settings['primary_color'] : '#0d6efd';
    $settings['secondary_color'] = isValidHexColor((string) $settings['secondary_color']) ? $settings['secondary_color'] : '#6c757d';
    $settings['font_family'] = cleanCssFontFamily((string) $settings['font_family']);
    $settings['font_size'] = max(12, min(24, (int) $settings['font_size']));
    $settings['site_title'] = cleanText((string) $settings['site_title'], 120);
    $settings['banner_text'] = cleanText((string) $settings['banner_text'], 255);
    $settings['footer_text'] = cleanText((string) $settings['footer_text'], 255);

    return $settings;
}

function fetchAppSettings(mysqli $conn): array
{
    $result = $conn->query("SELECT * FROM settings LIMIT 1");
    $settings = $result ? ($result->fetch_assoc() ?: []) : [];

    return normalizeAppSettings($settings);
}

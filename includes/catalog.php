<?php

function catalogCategoryMap(): array
{
    return [
        'TELEFONOS' => 'Telefonos y Smartphones',
        'PORTATILES' => 'Portatiles y Ultrabooks',
        'ACCESORIOS_ELECTRONICOS' => 'Accesorios y Perifericos',
        'CPUS' => 'Procesadores y Rendimiento',
        'TABLETS' => 'Tablets y Dispositivos Moviles'
    ];
}

function catalogSubcategoryMap(): array
{
    return [
        'TELEFONOS' => ['Gama Alta', 'Gama Media', 'Gaming', 'Fotografia', 'Resistentes'],
        'PORTATILES' => ['Ultrabook', 'Gaming', 'Empresarial', '2 en 1', 'Creator'],
        'ACCESORIOS_ELECTRONICOS' => ['Audio', 'Carga', 'Productividad', 'Conectividad', 'Smart Home'],
        'CPUS' => ['Gaming', 'Workstation', 'Consumo', 'Profesional', 'Eficiencia'],
        'TABLETS' => ['Premium', 'Productividad', 'Entretenimiento', 'Educacion', 'Compactas']
    ];
}

function catalogCategoryRoutes(): array
{
    return [
        'TELEFONOS' => 'telefonos.php',
        'PORTATILES' => 'portatiles.php',
        'ACCESORIOS_ELECTRONICOS' => 'accesorios.php',
        'CPUS' => 'procesadores.php',
        'TABLETS' => 'tablets.php'
    ];
}

function catalogCategoryUrl(string $category): string
{
    $routes = catalogCategoryRoutes();
    return $routes[$category] ?? 'index.php?categoria=' . urlencode($category);
}

function catalogSafeProductIds(array $ids): array
{
    $ids = array_map('intval', $ids);
    $ids = array_values(array_unique(array_filter($ids, fn($id) => $id > 0)));
    sort($ids);

    return $ids;
}

function catalogProductIsActive(mysqli $conn, int $productId): bool
{
    $stmt = $conn->prepare('SELECT id FROM products WHERE id = ? AND is_active = 1 LIMIT 1');
    if (!$stmt) {
        return false;
    }

    $stmt->bind_param('i', $productId);
    $stmt->execute();

    return (bool) $stmt->get_result()->fetch_assoc();
}

function catalogProductsByIds(mysqli $conn, array $ids, bool $activeOnly = true): array
{
    $ids = catalogSafeProductIds($ids);
    if (empty($ids)) {
        return [];
    }

    // El IN se arma solo despues de normalizar a enteros positivos, evitando SQL injection.
    $sql = 'SELECT * FROM products WHERE ' . ($activeOnly ? 'is_active = 1 AND ' : '') . 'id IN (' . implode(',', $ids) . ')';
    $result = $conn->query($sql);
    if (!$result) {
        return [];
    }

    return $result->fetch_all(MYSQLI_ASSOC);
}

function catalogCategoryThemeMap(): array
{
    return [
        'TELEFONOS' => [
            'eyebrow' => 'Movilidad premium',
            'title' => 'Smartphones y telefonos para quienes quieren rendimiento, camara y presencia.',
            'description' => 'Explora modelos pensados para fotografia, productividad y uso intensivo con marcas lideres y equipos listos para destacar.',
            'panel_class' => 'theme-phone',
            'icon' => 'bi-phone',
            'highlights' => ['Fotografia avanzada', 'Gama alta y media', 'Marcas top del mercado']
        ],
        'PORTATILES' => [
            'eyebrow' => 'Trabajo y potencia',
            'title' => 'Portatiles y ultrabooks para oficina, estudio y creacion de contenido.',
            'description' => 'Una categoria enfocada en productividad, portabilidad y rendimiento para perfiles profesionales y usuarios exigentes.',
            'panel_class' => 'theme-laptop',
            'icon' => 'bi-laptop',
            'highlights' => ['Ultrabooks y gaming', 'Equipos empresariales', 'Productividad y diseno']
        ],
        'ACCESORIOS_ELECTRONICOS' => [
            'eyebrow' => 'Setup y ecosistema',
            'title' => 'Accesorios y perifericos para elevar cualquier experiencia digital.',
            'description' => 'Audio, carga, conectividad y productividad en una seleccion pensada para complementar equipos y crear setups completos.',
            'panel_class' => 'theme-accessory',
            'icon' => 'bi-headphones',
            'highlights' => ['Audio y conectividad', 'Carga y movilidad', 'Productividad diaria']
        ],
        'CPUS' => [
            'eyebrow' => 'Rendimiento puro',
            'title' => 'Procesadores y rendimiento para configuraciones que exigen potencia real.',
            'description' => 'Una pagina orientada a rendimiento, eficiencia y escenarios gaming o workstation con foco directo en especificacion y fuerza bruta.',
            'panel_class' => 'theme-cpu',
            'icon' => 'bi-cpu',
            'highlights' => ['Gaming y workstation', 'Intel y AMD', 'Potencia para tareas exigentes']
        ],
        'TABLETS' => [
            'eyebrow' => 'Pantalla y versatilidad',
            'title' => 'Tablets y dispositivos moviles para productividad ligera y entretenimiento.',
            'description' => 'Descubre equipos compactos, premium y versatiles para estudiar, trabajar en movimiento o disfrutar contenido con comodidad.',
            'panel_class' => 'theme-tablet',
            'icon' => 'bi-tablet',
            'highlights' => ['Movilidad total', 'Modelos premium y compactos', 'Trabajo y entretenimiento']
        ]
    ];
}

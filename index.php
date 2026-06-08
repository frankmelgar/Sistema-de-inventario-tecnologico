<?php
require __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/catalog.php';

$settings = fetchAppSettings($conn);
$categoryMap = catalogCategoryMap();

if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

$selectedCategory = cleanText((string) ($_GET['categoria'] ?? ''), 100);
$selectedBrand = cleanText((string) ($_GET['brand'] ?? ''), 100);
if ($selectedCategory !== '' && !isset($categoryMap[$selectedCategory])) {
    $selectedCategory = '';
}

if (isset($_POST['add_to_cart'])) {
    requireValidCsrfToken();
    $pid = (int) ($_POST['product_id'] ?? 0);
    $qty = normalizedQuantity((int) ($_POST['quantity'] ?? 1));
    if ($pid > 0 && catalogProductIsActive($conn, $pid)) {
        $_SESSION['cart'][$pid] = ($_SESSION['cart'][$pid] ?? 0) + $qty;
    } elseif ($pid > 0) {
        auditLog($conn, 'cart_add_rejected', 'products', $pid, ['reason' => 'inactive_or_missing']);
    }

    $params = [];
    if ($selectedCategory !== '') {
        $params['categoria'] = $selectedCategory;
    }
    if ($selectedBrand !== '') {
        $params['brand'] = $selectedBrand;
    }
    header('Location: index.php' . (!empty($params) ? '?' . http_build_query($params) : ''));
    exit;
}

$where = ['is_active = 1'];
$params = [];
$types = '';
if ($selectedCategory !== '') {
    $where[] = 'categoria = ?';
    $params[] = $selectedCategory;
    $types .= 's';
}
if ($selectedBrand !== '') {
    $where[] = 'brand = ?';
    $params[] = $selectedBrand;
    $types .= 's';
}

$sql = 'SELECT * FROM products WHERE ' . implode(' AND ', $where) . ' ORDER BY is_featured DESC, id DESC';
$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$products = $stmt->get_result();

$featuredResult = $conn->query("SELECT * FROM products WHERE is_active = 1 AND is_featured = 1 ORDER BY id DESC LIMIT 8");
$featuredItems = $featuredResult ? $featuredResult->fetch_all(MYSQLI_ASSOC) : [];
$brandMapResult = $conn->query("SELECT DISTINCT categoria, brand FROM products WHERE is_active = 1 AND brand IS NOT NULL AND brand <> '' ORDER BY categoria, brand");
$brandsByCategory = [];
if ($brandMapResult) {
    while ($brandRow = $brandMapResult->fetch_assoc()) {
        $brandsByCategory[$brandRow['categoria']][] = $brandRow['brand'];
    }
}
$brandsQuery = 'SELECT DISTINCT brand FROM products WHERE is_active = 1';
$brandParams = [];
$brandTypes = '';
if ($selectedCategory !== '') {
    $brandsQuery .= ' AND categoria = ?';
    $brandParams[] = $selectedCategory;
    $brandTypes .= 's';
}
$brandsQuery .= ' AND brand IS NOT NULL AND brand <> "" ORDER BY brand';
$brandStmt = $conn->prepare($brandsQuery);
if (!empty($brandParams)) {
    $brandStmt->bind_param($brandTypes, ...$brandParams);
}
$brandStmt->execute();
$brands = $brandStmt->get_result();
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?php echo htmlspecialchars($settings['site_title']); ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
<style>
:root {
    --primary: <?php echo htmlspecialchars($settings['primary_color']); ?>;
    --secondary: <?php echo htmlspecialchars($settings['secondary_color']); ?>;
}
body {
    background:
        linear-gradient(135deg, rgba(11, 22, 46, 0.82), rgba(11, 22, 46, 0.45)),
        url('<?php echo htmlspecialchars($settings['background']); ?>') center/cover fixed;
    color: #10203b;
    font-family: <?php echo htmlspecialchars($settings['font_family']); ?>;
    font-size: <?php echo (int) $settings['font_size']; ?>px;
}
.navbar {
    background: rgba(8, 18, 36, 0.92);
    backdrop-filter: blur(14px);
}
.navbar a, .navbar-brand, .navbar .btn, .navbar .nav-link, .navbar-text { color: #fff !important; }
.nav-link.active-category {
    font-weight: 700;
    color: #9fe8ff !important;
}
.cart-cta {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 16px;
    border-radius: 999px;
    background: linear-gradient(135deg, rgba(79,221,255,0.22), rgba(21,140,255,0.22));
    border: 1px solid rgba(143,211,255,0.32);
    font-weight: 700;
    text-decoration: none;
    white-space: nowrap;
}
.cart-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 24px;
    height: 24px;
    padding: 0 7px;
    border-radius: 999px;
    background: #fff;
    color: #0a2745;
    font-size: 0.85rem;
}
.navbar-actions {
    display: flex;
    align-items: center;
    gap: 12px;
}
.category-rail {
    padding: 14px 0 4px;
}
.category-rail-inner {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    padding: 14px 16px;
    border-radius: 20px;
    background: rgba(8, 18, 36, 0.72);
    backdrop-filter: blur(10px);
    box-shadow: 0 16px 34px rgba(4,16,40,0.14);
}
.category-rail-link {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 14px;
    border-radius: 999px;
    text-decoration: none;
    color: #eaf6ff;
    background: rgba(255,255,255,0.08);
    font-weight: 600;
    transition: 0.2s ease;
}
.category-rail-link:hover,
.category-rail-link.is-active {
    color: #06213a;
    background: linear-gradient(135deg, rgba(79,221,255,0.92), rgba(181,241,255,0.92));
}
@media (max-width: 991px) {
    .navbar-actions {
        width: 100%;
        justify-content: space-between;
        margin-top: 12px;
    }
}
.hero {
    margin: 28px auto 24px;
    padding: 42px;
    border-radius: 28px;
    background: linear-gradient(135deg, rgba(255,255,255,0.94), rgba(239,246,255,0.88));
    box-shadow: 0 24px 50px rgba(4,16,40,0.18);
}
.filter-panel, .section-panel, .product-card {
    background: rgba(255,255,255,0.94);
    border: 0;
    border-radius: 24px;
    box-shadow: 0 18px 36px rgba(10,28,58,0.12);
}
.product-card img { height: 220px; object-fit: cover; border-radius: 18px; }
.category-chip {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 16px;
    border-radius: 999px;
    background: rgba(13,110,253,0.1);
    color: var(--primary);
    text-decoration: none;
    font-weight: 600;
}
.hero h1 { max-width: 680px; }
.small-meta { color: #68758d; }
.footer-box {
    background: rgba(8,18,36,0.9);
    color: #fff;
    border-radius: 18px 18px 0 0;
}
</style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark">
  <div class="container-fluid px-4">
    <a class="navbar-brand d-flex align-items-center gap-2" href="index.php"><img src="<?php echo htmlspecialchars($settings['logo']); ?>" height="48" alt="Logo"><span><?php echo htmlspecialchars($settings['site_title']); ?></span></a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Abrir navegación"><span class="navbar-toggler-icon"></span></button>
    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav me-auto mb-2 mb-lg-0">
        <?php foreach ($categoryMap as $key => $label): ?>
          <li class="nav-item"><a class="nav-link <?php echo $selectedCategory === $key ? 'active-category' : ''; ?>" href="<?php echo htmlspecialchars(catalogCategoryUrl($key)); ?>"><?php echo htmlspecialchars($label); ?></a></li>
        <?php endforeach; ?>
      </ul>
      <div class="navbar-actions">
        <ul class="navbar-nav align-items-lg-center gap-lg-2 mb-0">
          <?php if (isLoggedIn()): ?>
            <li class="nav-item"><span class="navbar-text">Hola: <?php echo htmlspecialchars(currentUserName()); ?></span></li>
            <?php if (isAdmin()): ?><li class="nav-item"><a class="nav-link" href="admin.php">Panel Admin</a></li><?php endif; ?>
            <li class="nav-item">
              <form method="post" action="logout.php" class="m-0">
                <?php echo csrfInput(); ?>
                <button type="submit" class="nav-link border-0 bg-transparent p-0">Salir</button>
              </form>
            </li>
          <?php else: ?>
            <li class="nav-item"><a class="nav-link" href="login.php">Entrar</a></li>
            <li class="nav-item"><a class="nav-link" href="login.php">Modo Invitado</a></li>
          <?php endif; ?>
        </ul>
        <a class="cart-cta" href="carrito.php" aria-label="Ver carrito">
            <i class="bi bi-cart3"></i>
            <span>Carrito</span>
            <span class="cart-badge"><?php echo array_sum($_SESSION['cart']); ?></span>
        </a>
      </div>
    </div>
  </div>
</nav>

<div class="container category-rail">
    <div class="category-rail-inner">
        <a class="category-rail-link <?php echo $selectedCategory === '' ? 'is-active' : ''; ?>" href="index.php">
            <i class="bi bi-grid"></i>
            <span>Todo el catalogo</span>
        </a>
        <?php foreach ($categoryMap as $key => $label): ?>
            <a class="category-rail-link <?php echo $selectedCategory === $key ? 'is-active' : ''; ?>" href="<?php echo htmlspecialchars(catalogCategoryUrl($key)); ?>">
                <i class="bi bi-arrow-right-circle"></i>
                <span><?php echo htmlspecialchars($label); ?></span>
            </a>
        <?php endforeach; ?>
    </div>
</div>

<div class="container py-4">
    <section class="hero">
        <div class="row align-items-center g-4">
            <div class="col-lg-8">
                <span class="category-chip mb-3"><i class="bi bi-stars"></i> Soluciones tecnologicas para retail, oficinas y equipos profesionales</span>
                <h1 class="display-5 fw-bold mb-3"><?php echo htmlspecialchars($settings['banner_text']); ?></h1>
                <p class="lead text-secondary mb-4">Descubre un catálogo curado de dispositivos, marcas líderes y accesorios de alto rendimiento con una experiencia comercial clara y profesional.</p>
                <div class="d-flex flex-wrap gap-2">
                    <?php foreach ($categoryMap as $key => $label): ?>
                        <a class="category-chip" href="<?php echo htmlspecialchars(catalogCategoryUrl($key)); ?>"><?php echo htmlspecialchars($label); ?></a>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="col-lg-4 text-lg-end">
                <?php if (!empty($settings['banner'])): ?><img src="<?php echo htmlspecialchars($settings['banner']); ?>" class="img-fluid rounded-4 shadow" alt="Banner visual"><?php endif; ?>
            </div>
        </div>
    </section>

    <section class="filter-panel p-4 mb-4">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-3">
            <div>
                <h2 class="h4 mb-1">Explora por categoria, marca y tipo de equipo</h2>
                <p class="mb-0 text-secondary">Cada producto publicado desde el panel se refleja aquí con su marca, subcategoría e imagen correspondiente.</p>
            </div>
            <a href="index.php" class="btn btn-outline-secondary">Limpiar filtros</a>
        </div>
        <form class="row g-3" method="get">
            <div class="col-md-5">
                <label class="form-label">Categoria</label>
                <select name="categoria" id="categorySelect" class="form-select">
                    <option value="">Todas las categorias</option>
                    <?php foreach ($categoryMap as $key => $label): ?>
                        <option value="<?php echo $key; ?>" <?php echo $selectedCategory === $key ? 'selected' : ''; ?>><?php echo htmlspecialchars($label); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-5">
                <label class="form-label">Marca</label>
                <select name="brand" id="brandSelect" class="form-select">
                    <option value="">Todas las marcas</option>
                    <?php while ($brandRow = $brands->fetch_assoc()): ?>
                        <option value="<?php echo htmlspecialchars($brandRow['brand']); ?>" <?php echo $selectedBrand === $brandRow['brand'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($brandRow['brand']); ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button class="btn btn-primary w-100" type="submit">Aplicar</button>
            </div>
        </form>
    </section>

    <section class="section-panel p-4 mb-4">
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
            <h2 class="h4 mb-0">Productos destacados</h2>
            <small class="small-meta">Selección destacada del catálogo principal</small>
        </div>
        <?php if (empty($featuredItems)): ?>
            <div class="text-center py-4">
                <i class="bi bi-stars fs-1 text-secondary d-block mb-3"></i>
                <p class="mb-0 text-secondary">Todavia no hay productos destacados configurados desde el panel.</p>
            </div>
        <?php else: ?>
        <div class="row g-4">
            <?php foreach ($featuredItems as $item): ?>
                <div class="col-md-6 col-xl-3">
                    <div class="product-card p-3 h-100">
                        <img src="<?php echo htmlspecialchars(productImageUrl($item)); ?>" alt="Producto" class="w-100 mb-3">
                        <div class="small-meta mb-2"><?php echo htmlspecialchars($categoryMap[$item['categoria']] ?? $item['categoria']); ?> · <?php echo htmlspecialchars($item['brand'] ?: 'Marca'); ?></div>
                        <h3 class="h5"><?php echo htmlspecialchars($item['name']); ?></h3>
                        <p class="small-meta"><?php echo htmlspecialchars($item['subcategoria'] ?: 'Subcategoria general'); ?></p>
                        <p><?php echo htmlspecialchars($item['description']); ?></p>
                        <div class="d-flex justify-content-between align-items-center mt-auto">
                            <strong>$<?php echo number_format($item['price'], 2); ?></strong>
                            <form method="post" class="m-0">
                        <?php echo csrfInput(); ?>
                                <input type="hidden" name="product_id" value="<?php echo (int) $item['id']; ?>">
                                <input type="hidden" name="quantity" value="1">
                                <button type="submit" name="add_to_cart" class="btn btn-sm btn-primary">Agregar</button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </section>

    <section class="section-panel p-4">
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
            <h2 class="h4 mb-0">Catalogo disponible</h2>
            <small class="small-meta"><?php echo $selectedCategory !== '' ? htmlspecialchars($categoryMap[$selectedCategory] ?? $selectedCategory) : 'Todas las categorias'; ?><?php echo $selectedBrand !== '' ? ' · ' . htmlspecialchars($selectedBrand) : ''; ?></small>
        </div>
        <?php if ($products->num_rows === 0): ?>
            <div class="text-center py-4">
                <i class="bi bi-search fs-1 text-secondary d-block mb-3"></i>
                <p class="mb-2 text-secondary">No encontramos productos con los filtros seleccionados.</p>
                <a href="index.php" class="btn btn-outline-secondary">Ver todo el catalogo</a>
            </div>
        <?php else: ?>
        <div class="row g-4">
            <?php while ($p = $products->fetch_assoc()): ?>
                <div class="col-md-6 col-xl-4">
                    <div class="product-card p-3 h-100">
                        <img src="<?php echo htmlspecialchars(productImageUrl($p)); ?>" alt="Producto" class="w-100 mb-3">
                        <div class="small-meta mb-2"><?php echo htmlspecialchars($categoryMap[$p['categoria']] ?? $p['categoria']); ?></div>
                        <h3 class="h5 mb-1"><?php echo htmlspecialchars($p['name']); ?></h3>
                        <p class="small-meta mb-2"><?php echo htmlspecialchars($p['brand'] ?: 'Marca por definir'); ?> · <?php echo htmlspecialchars($p['subcategoria'] ?: 'Subcategoria general'); ?></p>
                        <p><?php echo htmlspecialchars($p['description']); ?></p>
                        <div class="d-flex justify-content-between align-items-center mt-auto gap-3">
                            <strong>$<?php echo number_format($p['price'], 2); ?></strong>
                            <form method="post" class="d-flex gap-2 m-0">
                                <?php echo csrfInput(); ?>
                                <input type="hidden" name="product_id" value="<?php echo (int) $p['id']; ?>">
                                <input type="number" name="quantity" value="1" min="1" class="form-control" style="width:76px;">
                                <button type="submit" name="add_to_cart" class="btn btn-primary">Agregar</button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
        <?php endif; ?>
    </section>
</div>

<footer class="footer-box text-center p-3 mt-4">
    <?php echo htmlspecialchars($settings['footer_text']); ?>
</footer>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
const brandsByCategory = <?php echo json_encode($brandsByCategory, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
const categorySelect = document.getElementById('categorySelect');
const brandSelect = document.getElementById('brandSelect');
const selectedBrand = <?php echo json_encode($selectedBrand, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;

function populateBrandOptions(categoryValue, preferredBrand = '') {
    const brands = categoryValue && brandsByCategory[categoryValue] ? brandsByCategory[categoryValue] : [];
    brandSelect.innerHTML = '';

    const defaultOption = document.createElement('option');
    defaultOption.value = '';
    defaultOption.textContent = 'Todas las marcas';
    brandSelect.appendChild(defaultOption);

    brands.forEach((brand) => {
        const option = document.createElement('option');
        option.value = brand;
        option.textContent = brand;
        if (preferredBrand && preferredBrand === brand) {
            option.selected = true;
        }
        brandSelect.appendChild(option);
    });

    if (!preferredBrand || !brands.includes(preferredBrand)) {
        brandSelect.value = '';
    }
}

if (categorySelect && brandSelect) {
    populateBrandOptions(categorySelect.value, selectedBrand);
    categorySelect.addEventListener('change', () => {
        populateBrandOptions(categorySelect.value);
    });
}
</script>
</body>
</html>


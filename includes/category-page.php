<?php
require __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/catalog.php';

$pageCategory = $pageCategory ?? '';
$categoryMap = catalogCategoryMap();
$categoryThemes = catalogCategoryThemeMap();
$categoryRoutes = catalogCategoryRoutes();

if ($pageCategory === '' || !isset($categoryMap[$pageCategory], $categoryThemes[$pageCategory])) {
    header('Location: index.php');
    exit;
}

$settings = fetchAppSettings($conn);

if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

$selectedBrand = cleanText((string) ($_GET['brand'] ?? ''), 100);

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
    if ($selectedBrand !== '') {
        $params['brand'] = $selectedBrand;
    }
    header('Location: ' . catalogCategoryUrl($pageCategory) . (!empty($params) ? '?' . http_build_query($params) : ''));
    exit;
}

$where = ['is_active = 1', 'categoria = ?'];
$params = [$pageCategory];
$types = 's';
if ($selectedBrand !== '') {
    $where[] = 'brand = ?';
    $params[] = $selectedBrand;
    $types .= 's';
}

$sql = 'SELECT * FROM products WHERE ' . implode(' AND ', $where) . ' ORDER BY is_featured DESC, id DESC';
$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$products = $stmt->get_result();

$countStmt = $conn->prepare('SELECT COUNT(*) total, COUNT(DISTINCT brand) brands, MIN(price) min_price, MAX(price) max_price FROM products WHERE is_active = 1 AND categoria = ?');
$countStmt->bind_param('s', $pageCategory);
$countStmt->execute();
$stats = $countStmt->get_result()->fetch_assoc() ?: ['total' => 0, 'brands' => 0, 'min_price' => 0, 'max_price' => 0];

$featuredStmt = $conn->prepare('SELECT * FROM products WHERE is_active = 1 AND categoria = ? AND is_featured = 1 ORDER BY id DESC LIMIT 4');
$featuredStmt->bind_param('s', $pageCategory);
$featuredStmt->execute();
$featuredProducts = $featuredStmt->get_result()->fetch_all(MYSQLI_ASSOC);

$brandStmt = $conn->prepare("SELECT DISTINCT brand FROM products WHERE is_active = 1 AND categoria = ? AND brand IS NOT NULL AND brand <> '' ORDER BY brand");
$brandStmt->bind_param('s', $pageCategory);
$brandStmt->execute();
$brands = $brandStmt->get_result()->fetch_all(MYSQLI_ASSOC);

$theme = $categoryThemes[$pageCategory];
$pageTitle = $categoryMap[$pageCategory];
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?php echo htmlspecialchars($pageTitle . ' - ' . $settings['site_title']); ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
<style>
:root {
    --primary: <?php echo htmlspecialchars($settings['primary_color']); ?>;
    --secondary: <?php echo htmlspecialchars($settings['secondary_color']); ?>;
}
body {
    background:
        linear-gradient(135deg, rgba(9, 19, 39, 0.92), rgba(11, 30, 56, 0.86)),
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
    border-radius: 24px;
    background: rgba(8, 18, 36, 0.78);
    backdrop-filter: blur(12px);
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
.theme-shell {
    margin: 24px auto;
    padding: 42px;
    border-radius: 32px;
    box-shadow: 0 24px 50px rgba(4,16,40,0.2);
    overflow: hidden;
    position: relative;
}
.theme-shell::after {
    content: "";
    position: absolute;
    inset: auto -80px -120px auto;
    width: 340px;
    height: 340px;
    border-radius: 50%;
    background: rgba(255,255,255,0.09);
}
.theme-phone {
    background: linear-gradient(135deg, rgba(225, 248, 255, 0.96), rgba(246, 251, 255, 0.92));
}
.theme-laptop {
    background: linear-gradient(135deg, rgba(236, 244, 255, 0.96), rgba(245, 248, 255, 0.92));
}
.theme-accessory {
    background: linear-gradient(135deg, rgba(240, 250, 246, 0.96), rgba(248, 253, 250, 0.92));
}
.theme-cpu {
    background: linear-gradient(135deg, rgba(246, 240, 255, 0.96), rgba(252, 248, 255, 0.92));
}
.theme-tablet {
    background: linear-gradient(135deg, rgba(255, 246, 238, 0.96), rgba(255, 251, 247, 0.92));
}
.theme-badge {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    padding: 11px 18px;
    border-radius: 999px;
    background: rgba(18, 49, 90, 0.09);
    color: #1e5da0;
    font-weight: 700;
}
.hero-kpis {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 14px;
}
.kpi-card, .brand-panel, .featured-panel, .catalog-panel, .highlight-panel {
    background: rgba(255,255,255,0.95);
    border-radius: 24px;
    box-shadow: 0 18px 36px rgba(10,28,58,0.12);
}
.kpi-card {
    padding: 18px 20px;
}
.brand-pill {
    display: inline-flex;
    align-items: center;
    padding: 10px 14px;
    border-radius: 999px;
    background: rgba(12, 38, 72, 0.08);
    color: #12345d;
    text-decoration: none;
    font-weight: 600;
}
.brand-pill.active {
    background: linear-gradient(135deg, rgba(79,221,255,0.92), rgba(181,241,255,0.92));
    color: #08233d;
}
.product-card {
    background: rgba(255,255,255,0.96);
    border: 0;
    border-radius: 24px;
    box-shadow: 0 18px 36px rgba(10,28,58,0.12);
}
.product-card img {
    height: 220px;
    object-fit: cover;
    border-radius: 18px;
}
.small-meta { color: #68758d; }
.footer-box {
    background: rgba(8,18,36,0.9);
    color: #fff;
    border-radius: 18px 18px 0 0;
}
@media (max-width: 991px) {
    .navbar-actions {
        width: 100%;
        justify-content: space-between;
        margin-top: 12px;
    }
    .hero-kpis {
        grid-template-columns: 1fr;
    }
}
</style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark">
  <div class="container-fluid px-4">
    <a class="navbar-brand d-flex align-items-center gap-2" href="index.php"><img src="<?php echo htmlspecialchars($settings['logo']); ?>" height="48" alt="Logo"><span><?php echo htmlspecialchars($settings['site_title']); ?></span></a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Abrir navegaciÃ³n"><span class="navbar-toggler-icon"></span></button>
    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav me-auto mb-2 mb-lg-0">
        <?php foreach ($categoryMap as $key => $label): ?>
          <li class="nav-item"><a class="nav-link <?php echo $pageCategory === $key ? 'fw-bold text-info' : ''; ?>" href="<?php echo htmlspecialchars(catalogCategoryUrl($key)); ?>"><?php echo htmlspecialchars($label); ?></a></li>
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
        <a class="category-rail-link" href="index.php">
            <i class="bi bi-grid"></i>
            <span>Todo el catalogo</span>
        </a>
        <?php foreach ($categoryMap as $key => $label): ?>
            <a class="category-rail-link <?php echo $pageCategory === $key ? 'is-active' : ''; ?>" href="<?php echo htmlspecialchars(catalogCategoryUrl($key)); ?>">
                <i class="bi bi-arrow-right-circle"></i>
                <span><?php echo htmlspecialchars($label); ?></span>
            </a>
        <?php endforeach; ?>
    </div>
</div>

<div class="container py-4">
    <section class="theme-shell <?php echo htmlspecialchars($theme['panel_class']); ?>">
        <div class="row g-4 align-items-center position-relative" style="z-index: 1;">
            <div class="col-lg-7">
                <span class="theme-badge mb-3"><i class="bi <?php echo htmlspecialchars($theme['icon']); ?>"></i> <?php echo htmlspecialchars($theme['eyebrow']); ?></span>
                <h1 class="display-4 fw-bold mb-3"><?php echo htmlspecialchars($theme['title']); ?></h1>
                <p class="lead text-secondary mb-4"><?php echo htmlspecialchars($theme['description']); ?></p>
                <div class="d-flex flex-wrap gap-2">
                    <?php foreach ($theme['highlights'] as $highlight): ?>
                        <span class="brand-pill"><?php echo htmlspecialchars($highlight); ?></span>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="col-lg-5">
                <div class="hero-kpis">
                    <div class="kpi-card">
                        <small class="text-secondary d-block mb-1">Productos</small>
                        <strong class="fs-3"><?php echo (int) $stats['total']; ?></strong>
                    </div>
                    <div class="kpi-card">
                        <small class="text-secondary d-block mb-1">Marcas</small>
                        <strong class="fs-3"><?php echo (int) $stats['brands']; ?></strong>
                    </div>
                    <div class="kpi-card">
                        <small class="text-secondary d-block mb-1">Rango</small>
                        <strong class="fs-6">$<?php echo number_format((float) $stats['min_price'], 2); ?> - $<?php echo number_format((float) $stats['max_price'], 2); ?></strong>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="brand-panel p-4 mb-4">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-3">
            <div>
                <h2 class="h4 mb-1">Explora <?php echo htmlspecialchars(strtolower($categoryMap[$pageCategory])); ?> por marca</h2>
                <p class="mb-0 text-secondary">Cada pagina tiene su propio ambiente visual y un catalogo centrado en su especialidad.</p>
            </div>
            <?php if ($selectedBrand !== ''): ?>
                <a href="<?php echo htmlspecialchars(catalogCategoryUrl($pageCategory)); ?>" class="btn btn-outline-secondary">Quitar marca</a>
            <?php endif; ?>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a href="<?php echo htmlspecialchars(catalogCategoryUrl($pageCategory)); ?>" class="brand-pill <?php echo $selectedBrand === '' ? 'active' : ''; ?>">Todas las marcas</a>
            <?php foreach ($brands as $brand): ?>
                <a href="<?php echo htmlspecialchars(catalogCategoryUrl($pageCategory)); ?>?brand=<?php echo urlencode($brand['brand']); ?>" class="brand-pill <?php echo $selectedBrand === $brand['brand'] ? 'active' : ''; ?>"><?php echo htmlspecialchars($brand['brand']); ?></a>
            <?php endforeach; ?>
        </div>
    </section>

    <?php if (!empty($featuredProducts)): ?>
    <section class="featured-panel p-4 mb-4">
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
            <h2 class="h4 mb-0">SelecciÃ³n destacada de <?php echo htmlspecialchars($categoryMap[$pageCategory]); ?></h2>
            <small class="small-meta"><?php echo $selectedBrand !== '' ? htmlspecialchars($selectedBrand) : 'Curaduria destacada'; ?></small>
        </div>
        <div class="row g-4">
            <?php foreach ($featuredProducts as $item): ?>
                <div class="col-md-6 col-xl-3">
                    <div class="product-card p-3 h-100">
                        <img src="<?php echo htmlspecialchars(productImageUrl($item)); ?>" alt="Producto" class="w-100 mb-3">
                        <div class="small-meta mb-2"><?php echo htmlspecialchars($item['brand'] ?: 'Marca'); ?> Â· <?php echo htmlspecialchars($item['subcategoria'] ?: 'General'); ?></div>
                        <h3 class="h5"><?php echo htmlspecialchars($item['name']); ?></h3>
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
    </section>
    <?php endif; ?>

    <section class="catalog-panel p-4">
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
            <h2 class="h4 mb-0">Catalogo de <?php echo htmlspecialchars($categoryMap[$pageCategory]); ?></h2>
            <small class="small-meta"><?php echo $selectedBrand !== '' ? htmlspecialchars($selectedBrand) : 'Todas las marcas'; ?></small>
        </div>
        <?php if ($products->num_rows === 0): ?>
            <div class="text-center py-4">
                <i class="bi bi-search fs-1 text-secondary d-block mb-3"></i>
                <p class="mb-2 text-secondary">No encontramos productos para esa marca dentro de esta categoria.</p>
                <a href="<?php echo htmlspecialchars(catalogCategoryUrl($pageCategory)); ?>" class="btn btn-outline-secondary">Ver toda la categoria</a>
            </div>
        <?php else: ?>
        <div class="row g-4">
            <?php while ($product = $products->fetch_assoc()): ?>
                <div class="col-md-6 col-xl-4">
                    <div class="product-card p-3 h-100">
                        <img src="<?php echo htmlspecialchars(productImageUrl($product)); ?>" alt="Producto" class="w-100 mb-3">
                        <div class="small-meta mb-2"><?php echo htmlspecialchars($product['brand'] ?: 'Marca'); ?> Â· <?php echo htmlspecialchars($product['subcategoria'] ?: 'General'); ?></div>
                        <h3 class="h5 mb-1"><?php echo htmlspecialchars($product['name']); ?></h3>
                        <p><?php echo htmlspecialchars($product['description']); ?></p>
                        <div class="d-flex justify-content-between align-items-center mt-auto gap-3">
                            <strong>$<?php echo number_format($product['price'], 2); ?></strong>
                            <form method="post" class="d-flex gap-2 m-0">
                                <?php echo csrfInput(); ?>
                                <input type="hidden" name="product_id" value="<?php echo (int) $product['id']; ?>">
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
</body>
</html>


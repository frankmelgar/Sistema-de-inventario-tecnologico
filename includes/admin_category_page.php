<?php
require_once __DIR__ . '/admin_helpers.php';

requireAdminAccess();

$categoryMap = catalogCategoryMap();
$subcategoryMap = catalogSubcategoryMap();
$categoryThemes = catalogCategoryThemeMap();
$brandMap = adminCategoryBrandMap();
$settings = adminFetchSettings($conn);

$adminCategoryKey = $adminCategoryKey ?? 'TELEFONOS';
$currentPagePath = $currentPagePath ?? 'admin.php';
$activeAdminPage = $activeAdminPage ?? 'telefonos';

if (!isset($categoryMap[$adminCategoryKey])) {
    $adminCategoryKey = 'TELEFONOS';
}

$admin_error = '';
$admin_success = $_GET['success'] ?? '';
$editingId = isset($_GET['edit']) ? (int) $_GET['edit'] : 0;

if (isset($_POST['save_product'])) {
    requireValidCsrfToken();

    $productId = (int) ($_POST['product_id'] ?? 0);
    $existingProduct = $productId > 0 ? adminProductById($conn, $productId) : null;
    if ($productId > 0 && (!$existingProduct || ($existingProduct['categoria'] ?? '') !== $adminCategoryKey)) {
        auditLog($conn, 'product_update_rejected', 'products', $productId, ['reason' => 'category_mismatch']);
        $admin_error = 'No se encontro un producto valido para editar en esta categoria.';
    }

    $name = cleanText((string) ($_POST['name'] ?? ''), 160);
    $price = (float) ($_POST['price'] ?? 0);
    $categoria = $adminCategoryKey;
    $brand = cleanText((string) ($_POST['brand'] ?? ''), 100);
    $subcategoria = cleanText((string) ($_POST['subcategoria'] ?? ''), 100);
    $description = cleanText((string) ($_POST['description'] ?? ''), 1000);
    $isFeatured = isset($_POST['is_featured']) ? 1 : 0;
    $isActive = isset($_POST['is_active']) ? 1 : 0;
    $currentImage = $existingProduct ? (string) ($existingProduct['image'] ?? '') : '';
    $imagePath = $currentImage;
    if ($admin_error === '') {
        $imagePath = adminUploadImageOrKeep('image', $currentImage, 'uploads/productos', $admin_error);
    }

    if ($admin_error !== '') {
        // Mantiene el mensaje de seguridad generado por validaciones previas.
    } elseif ($name === '' || $description === '' || $brand === '' || $subcategoria === '' || $price <= 0) {
        $admin_error = 'Completa todos los campos principales del producto.';
    } elseif ($imagePath === '') {
        $admin_error = 'Debes asignar una imagen valida al producto.';
    }

    if ($admin_error === '') {
        if ($productId > 0) {
            $stmt = $conn->prepare("UPDATE products SET name=?, price=?, image=?, description=?, categoria=?, brand=?, subcategoria=?, is_featured=?, is_active=? WHERE id=?");
            $stmt->bind_param('sdsssssiii', $name, $price, $imagePath, $description, $categoria, $brand, $subcategoria, $isFeatured, $isActive, $productId);
            $stmt->execute();
            auditLog($conn, 'product_updated', 'products', $productId, ['category' => $categoria]);
        } else {
            $stmt = $conn->prepare("INSERT INTO products (name, price, image, description, categoria, brand, subcategoria, is_featured, is_active) VALUES (?,?,?,?,?,?,?,?,?)");
            $stmt->bind_param('sdsssssii', $name, $price, $imagePath, $description, $categoria, $brand, $subcategoria, $isFeatured, $isActive);
            $stmt->execute();
            auditLog($conn, 'product_created', 'products', (int) $conn->insert_id, ['category' => $categoria]);
        }

        header('Location: ' . $currentPagePath . '?success=product');
        exit;
    }
}

$productForm = [
    'id' => 0,
    'name' => '',
    'price' => '',
    'image' => '',
    'description' => '',
    'categoria' => $adminCategoryKey,
    'brand' => '',
    'subcategoria' => '',
    'is_featured' => 0,
    'is_active' => 1
];

if ($editingId > 0) {
    $productData = adminProductById($conn, $editingId);
    if ($productData && ($productData['categoria'] ?? '') === $adminCategoryKey) {
        $productForm = $productData;
    }
}

$categoryStats = adminCategoryStats($conn, $adminCategoryKey);
$productsResult = adminProductsByCategory($conn, $adminCategoryKey);
$theme = $categoryThemes[$adminCategoryKey];

$heroRightContent = '
    <div class="row g-3">
        <div class="col-6"><div class="metric-card"><small>Total</small><h3 class="mb-0">' . (int) $categoryStats['total'] . '</h3></div></div>
        <div class="col-6"><div class="metric-card"><small>Activos</small><h3 class="mb-0">' . (int) $categoryStats['active_total'] . '</h3></div></div>
        <div class="col-6"><div class="metric-card"><small>Destacados</small><h3 class="mb-0">' . (int) $categoryStats['featured_total'] . '</h3></div></div>
        <div class="col-6"><div class="metric-card"><small>Marcas</small><h3 class="mb-0">' . (int) $categoryStats['brand_total'] . '</h3></div></div>
    </div>
';

$adminPageTitle = $categoryMap[$adminCategoryKey];
$adminPageLead = $theme['description'];
$adminPageBadge = 'Catalogo por categoria';

require __DIR__ . '/admin_header.php';
?>

<?php if ($admin_error !== ''): ?>
    <div class="alert alert-danger"><?php echo htmlspecialchars($admin_error); ?></div>
<?php elseif ($admin_success === 'product'): ?>
    <div class="alert alert-success">El catalogo de esta categoria se actualizo correctamente.</div>
<?php endif; ?>

<section class="catalog-theme <?php echo htmlspecialchars($theme['panel_class']); ?>">
    <div class="row align-items-center g-4">
        <div class="col-lg-7">
            <span class="badge-soft mb-3 d-inline-block"><i class="bi <?php echo htmlspecialchars($theme['icon']); ?> me-2"></i><?php echo htmlspecialchars($theme['eyebrow']); ?></span>
            <h2 class="h2 fw-bold mb-2"><?php echo htmlspecialchars($theme['title']); ?></h2>
            <p class="mb-0 text-secondary"><?php echo htmlspecialchars($theme['description']); ?></p>
        </div>
        <div class="col-lg-5">
            <div class="mini-metrics">
                <?php foreach ($theme['highlights'] as $highlight): ?>
                    <div class="mini-metric">
                        <small class="text-uppercase text-secondary">Foco</small>
                        <div class="fw-bold mt-1"><?php echo htmlspecialchars($highlight); ?></div>
                    </div>
                <?php endforeach; ?>
                <div class="mini-metric">
                    <small class="text-uppercase text-secondary">Pagina publica</small>
                    <div class="fw-bold mt-1"><a href="<?php echo htmlspecialchars(catalogCategoryUrl($adminCategoryKey)); ?>" target="_blank" class="text-decoration-none">Abrir categoria</a></div>
                </div>
            </div>
        </div>
    </div>
</section>

<div class="row g-4">
    <div class="col-xl-4">
        <div class="panel-card p-4">
            <div class="section-title">
                <h3 class="h5 mb-0"><?php echo $productForm['id'] ? 'Editar producto' : 'Nuevo producto'; ?></h3>
                <?php if ($productForm['id']): ?><a href="<?php echo htmlspecialchars($currentPagePath); ?>" class="btn btn-sm btn-outline-secondary">Nuevo</a><?php endif; ?>
            </div>
            <form method="post" enctype="multipart/form-data">
                <?php echo csrfInput(); ?>
                <input type="hidden" name="product_id" value="<?php echo (int) $productForm['id']; ?>">
                <input type="hidden" name="current_image" value="<?php echo htmlspecialchars($productForm['image']); ?>">
                <div class="mb-3">
                    <label class="form-label">Nombre comercial</label>
                    <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($productForm['name']); ?>" required>
                </div>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Marca</label>
                        <input list="brand-options" id="adminBrandInput" name="brand" class="form-control" value="<?php echo htmlspecialchars($productForm['brand']); ?>" required>
                        <datalist id="brand-options">
                            <?php foreach ($brandMap[$adminCategoryKey] ?? [] as $brand): ?>
                                <option value="<?php echo htmlspecialchars($brand); ?>">
                            <?php endforeach; ?>
                        </datalist>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Subcategoria</label>
                        <input list="subcategory-options" id="adminSubcategoryInput" name="subcategoria" class="form-control" value="<?php echo htmlspecialchars($productForm['subcategoria']); ?>" required>
                        <datalist id="subcategory-options">
                            <?php foreach ($subcategoryMap[$adminCategoryKey] ?? [] as $sub): ?>
                                <option value="<?php echo htmlspecialchars($sub); ?>">
                            <?php endforeach; ?>
                        </datalist>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Precio</label>
                        <input type="number" name="price" class="form-control" step="0.01" min="0.01" value="<?php echo htmlspecialchars((string) $productForm['price']); ?>" required>
                    </div>
                </div>
                <div class="mt-3">
                    <label class="form-label">Descripcion visible</label>
                    <textarea name="description" class="form-control" rows="4" required><?php echo htmlspecialchars($productForm['description']); ?></textarea>
                </div>
                <div class="mt-3">
                    <label class="form-label">Imagen</label>
                    <input type="file" name="image" class="form-control" accept=".jpg,.jpeg,.png,.webp,.avif" <?php echo $productForm['id'] ? '' : 'required'; ?>>
                </div>
                <?php if (!empty($productForm['image'])): ?>
                    <div class="mt-3"><img src="<?php echo htmlspecialchars(productImageUrl($productForm)); ?>" alt="Preview" class="img-fluid rounded-4" style="max-height:220px;object-fit:cover;"></div>
                <?php endif; ?>
                <div class="form-check mt-3">
                    <input class="form-check-input" type="checkbox" name="is_featured" id="is_featured" <?php echo !empty($productForm['is_featured']) ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="is_featured">Mostrar como destacado</label>
                </div>
                <div class="form-check mt-2">
                    <input class="form-check-input" type="checkbox" name="is_active" id="is_active" <?php echo !empty($productForm['is_active']) ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="is_active">Producto visible para clientes</label>
                </div>
                <button type="submit" name="save_product" class="btn btn-primary w-100 mt-4"><?php echo $productForm['id'] ? 'Guardar cambios' : 'Crear producto'; ?></button>
            </form>
        </div>
    </div>
    <div class="col-xl-8">
        <div class="panel-card p-4">
            <div class="section-title">
                <h3 class="h5 mb-0">Catalogo publicado</h3>
                <span class="text-secondary small">Gestion dedicada para <?php echo htmlspecialchars($categoryMap[$adminCategoryKey]); ?></span>
            </div>
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                        <tr>
                            <th>Producto</th>
                            <th>Marca</th>
                            <th>Subcategoria</th>
                            <th>Precio</th>
                            <th>Estado</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($product = $productsResult->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center gap-3">
                                        <img src="<?php echo htmlspecialchars(productImageUrl($product)); ?>" class="product-thumb" alt="Producto">
                                        <div>
                                            <strong><?php echo htmlspecialchars($product['name']); ?></strong><br>
                                            <small class="text-secondary"><?php echo htmlspecialchars($categoryMap[$adminCategoryKey]); ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td><?php echo htmlspecialchars($product['brand'] ?: 'Sin marca'); ?></td>
                                <td><?php echo htmlspecialchars($product['subcategoria'] ?: 'Sin subcategoria'); ?></td>
                                <td>$<?php echo number_format($product['price'], 2); ?></td>
                                <td>
                                    <?php if ((int) $product['is_active'] === 1): ?><span class="badge text-bg-success">Visible</span><?php else: ?><span class="badge text-bg-secondary">Oculto</span><?php endif; ?>
                                    <?php if ((int) $product['is_featured'] === 1): ?><span class="badge text-bg-warning">Destacado</span><?php endif; ?>
                                </td>
                                <td class="text-end"><a href="<?php echo htmlspecialchars($currentPagePath); ?>?edit=<?php echo (int) $product['id']; ?>" class="btn btn-sm btn-outline-primary">Editar</a></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            <?php if ((int) $categoryStats['total'] === 0): ?>
                <div class="text-center py-4 text-secondary">
                    <i class="bi bi-box fs-1 d-block mb-3"></i>
                    Todavia no hay productos cargados en esta categoria.
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require __DIR__ . '/admin_footer.php'; ?>

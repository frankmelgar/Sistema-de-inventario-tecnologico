<?php
require __DIR__ . '/includes/admin_helpers.php';

requireAdminAccess();
$settings = adminFetchSettings($conn);
$stats = adminDashboardStats($conn);
$summary = $stats['summary'];
$categories = $stats['categories'];

$heroRightContent = '
    <div class="row g-3">
        <div class="col-6"><div class="metric-card live"><small>Productos</small><h3 class="mb-0" data-stat="total_products">' . (int) $summary['total_products'] . '</h3></div></div>
        <div class="col-6"><div class="metric-card live"><small>Pedidos</small><h3 class="mb-0" data-stat="total_orders">' . (int) $summary['total_orders'] . '</h3></div></div>
        <div class="col-6"><div class="metric-card"><small>Usuarios</small><h3 class="mb-0" data-stat="total_users">' . (int) $summary['total_users'] . '</h3></div></div>
        <div class="col-6"><div class="metric-card"><small>Ingresos</small><h3 class="mb-0" data-stat="revenue">$' . number_format((float) $summary['revenue'], 2) . '</h3></div></div>
    </div>
';

$adminPageTitle = 'Dashboard en tiempo real';
$adminPageLead = 'Vision ejecutiva del proyecto con estadisticas vivas, accesos rapidos a cada categoria y control separado por area.';
$adminPageBadge = 'Dashboard principal';
$activeAdminPage = 'dashboard';

require __DIR__ . '/includes/admin_header.php';
?>

<div class="row g-4 mb-4">
    <div class="col-xl-8">
        <div class="panel-card p-4 h-100">
            <div class="section-title">
                <h2 class="h4 mb-0">KPIs del sistema</h2>
                <span class="live-note">Actualizacion automatica cada 15 segundos</span>
            </div>
            <div class="row g-3">
                <div class="col-md-3 col-6"><div class="metric-card"><small>Productos activos</small><h3 class="mb-0" data-stat="active_products"><?php echo (int) $summary['active_products']; ?></h3></div></div>
                <div class="col-md-3 col-6"><div class="metric-card"><small>Destacados</small><h3 class="mb-0" data-stat="featured_products"><?php echo (int) $summary['featured_products']; ?></h3></div></div>
                <div class="col-md-3 col-6"><div class="metric-card"><small>Categorias activas</small><h3 class="mb-0" data-stat="active_categories"><?php echo (int) $summary['active_categories']; ?></h3></div></div>
                <div class="col-md-3 col-6"><div class="metric-card"><small>Pedidos pagados</small><h3 class="mb-0" data-stat="paid_orders"><?php echo (int) $summary['paid_orders']; ?></h3></div></div>
            </div>
            <div class="mt-4 p-3 rounded-4" style="background: rgba(13,110,253,0.06);">
                <small class="text-uppercase text-secondary d-block mb-1">Ultima actividad</small>
                <strong data-stat="last_activity"><?php echo htmlspecialchars($summary['last_activity'] ?: 'Sin actividad reciente'); ?></strong>
            </div>
        </div>
    </div>
    <div class="col-xl-4">
        <div class="panel-card p-4 h-100">
            <div class="section-title">
                <h2 class="h4 mb-0">Accesos rapidos</h2>
                <span class="text-secondary small">Gestion fragmentada</span>
            </div>
            <div class="quick-grid">
                <a class="quick-card" href="admin-pedidos.php">
                    <small class="text-uppercase opacity-75">Operacion</small>
                    <div class="fs-5 fw-bold mt-2">Pedidos</div>
                    <div class="small opacity-75 mt-1">Seguimiento de checkout y ventas.</div>
                </a>
                <a class="quick-card" href="admin-apariencia.php">
                    <small class="text-uppercase opacity-75">Marca</small>
                    <div class="fs-5 fw-bold mt-2">Apariencia</div>
                    <div class="small opacity-75 mt-1">Logo, banner, fondo y tono visual.</div>
                </a>
                <?php foreach ($categories as $category): ?>
                    <a class="quick-card" href="<?php echo htmlspecialchars($category['route']); ?>">
                        <small class="text-uppercase opacity-75">Categoria</small>
                        <div class="fs-6 fw-bold mt-2"><?php echo htmlspecialchars($category['label']); ?></div>
                        <div class="small opacity-75 mt-1"><?php echo (int) $category['total']; ?> productos, <?php echo (int) $category['featured_total']; ?> destacados.</div>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<div class="panel-card p-4">
    <div class="section-title">
        <h2 class="h4 mb-0">Categorias con pagina propia</h2>
        <span class="text-secondary small">Cada bloque lleva a su vista dedicada</span>
    </div>
    <div class="row g-3" id="category-grid">
        <?php foreach ($categories as $category): ?>
            <div class="col-lg-4 col-md-6">
                <a href="<?php echo htmlspecialchars($category['route']); ?>" class="text-decoration-none text-reset d-block h-100">
                    <div class="metric-card h-100">
                        <small class="text-uppercase text-secondary"><?php echo htmlspecialchars($category['key']); ?></small>
                        <h3 class="h5 mt-2 mb-2"><?php echo htmlspecialchars($category['label']); ?></h3>
                        <div class="d-flex justify-content-between text-secondary small mb-1"><span>Total</span><strong data-category="<?php echo htmlspecialchars($category['key']); ?>" data-field="total"><?php echo (int) $category['total']; ?></strong></div>
                        <div class="d-flex justify-content-between text-secondary small mb-1"><span>Activos</span><strong data-category="<?php echo htmlspecialchars($category['key']); ?>" data-field="active_total"><?php echo (int) $category['active_total']; ?></strong></div>
                        <div class="d-flex justify-content-between text-secondary small"><span>Destacados</span><strong data-category="<?php echo htmlspecialchars($category['key']); ?>" data-field="featured_total"><?php echo (int) $category['featured_total']; ?></strong></div>
                    </div>
                </a>
            </div>
        <?php endforeach; ?>
    </div>
</div>
<?php
$pageScripts = <<<'HTML'
<script>
async function refreshDashboardStats() {
    try {
        const response = await fetch('admin-stats.php', { cache: 'no-store' });
        if (!response.ok) {
            return;
        }

        const payload = await response.json();
        const summary = payload.summary || {};
        const categories = payload.categories || [];

        Object.entries(summary).forEach(([key, value]) => {
            const node = document.querySelector(`[data-stat="${key}"]`);
            if (!node) {
                return;
            }
            if (key === 'revenue') {
                node.textContent = `$${Number(value || 0).toFixed(2)}`;
            } else {
                node.textContent = value || '0';
            }
        });

        categories.forEach((category) => {
            ['total', 'active_total', 'featured_total'].forEach((field) => {
                const node = document.querySelector(`[data-category="${category.key}"][data-field="${field}"]`);
                if (node) {
                    node.textContent = category[field] ?? '0';
                }
            });
        });
    } catch (error) {
        console.error('No se pudieron refrescar las metricas del dashboard.', error);
    }
}

setInterval(refreshDashboardStats, 15000);
</script>
HTML;
require __DIR__ . '/includes/admin_footer.php';

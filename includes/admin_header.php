<?php
$adminNavItems = adminNavItems();
$adminPageTitle = $adminPageTitle ?? 'Panel de control';
$adminPageLead = $adminPageLead ?? 'Gestion centralizada de tienda, catalogo y pedidos.';
$adminPageBadge = $adminPageBadge ?? 'Panel Admin';
$activeAdminPage = $activeAdminPage ?? 'dashboard';
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?php echo htmlspecialchars($adminPageTitle); ?> - <?php echo htmlspecialchars($settings['site_title']); ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
<style>
:root {
    --primary: <?php echo htmlspecialchars($settings['primary_color']); ?>;
    --secondary: <?php echo htmlspecialchars($settings['secondary_color']); ?>;
    --panel: rgba(255,255,255,0.94);
    --navy: #0b1530;
}
body {
    margin: 0;
    min-height: 100vh;
    background:
        linear-gradient(135deg, rgba(6,14,31,0.92), rgba(18,32,58,0.85)),
        url('<?php echo htmlspecialchars($settings['background']); ?>') center/cover fixed;
    font-family: <?php echo htmlspecialchars($settings['font_family']); ?>;
    color: #172033;
}
.sidebar {
    position: fixed;
    top: 0;
    left: 0;
    width: 280px;
    height: 100vh;
    padding: 24px 18px;
    background: linear-gradient(180deg, rgba(10,18,36,0.97), rgba(14,27,56,0.96));
    color: #fff;
    overflow-y: auto;
    box-shadow: 16px 0 40px rgba(0,0,0,0.2);
}
.brand-block {
    padding: 18px;
    border-radius: 22px;
    background: rgba(255,255,255,0.08);
    margin-bottom: 22px;
    border: 1px solid rgba(255,255,255,0.08);
}
.brand-block small { color: rgba(255,255,255,0.72); }
.brand-logo-shell {
    width: 72px;
    height: 72px;
    border-radius: 18px;
    background: rgba(255,255,255,0.08);
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
    flex-shrink: 0;
}
.brand-logo-shell img {
    width: 88%;
    height: 88%;
    object-fit: contain;
}
.sidebar-nav {
    display: grid;
    gap: 8px;
}
.sidebar a {
    display: flex;
    align-items: center;
    gap: 12px;
    color: #f5fbff;
    text-decoration: none;
    padding: 13px 15px;
    border-radius: 16px;
    transition: 0.2s ease;
    font-weight: 600;
}
.sidebar a:hover, .sidebar a.active {
    background: linear-gradient(135deg, rgba(105,224,255,0.18), rgba(255,255,255,0.1));
}
.sidebar a.utility-link {
    font-weight: 500;
    color: rgba(255,255,255,0.9);
}
.main-content {
    margin-left: 280px;
    padding: 30px;
}
.hero-admin {
    background: linear-gradient(135deg, rgba(255,255,255,0.97), rgba(238,245,255,0.93));
    border-radius: 28px;
    padding: 30px;
    box-shadow: 0 22px 44px rgba(8,22,52,0.18);
    margin-bottom: 24px;
}
.panel-card {
    background: var(--panel);
    border: 0;
    border-radius: 24px;
    box-shadow: 0 18px 35px rgba(15,35,70,0.12);
}
.section-title {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 16px;
    margin-bottom: 18px;
}
.badge-soft {
    background: rgba(13,110,253,0.12);
    color: #0d6efd;
    border-radius: 999px;
    padding: 7px 13px;
    font-weight: 700;
}
.metric-card {
    border-radius: 20px;
    padding: 18px;
    height: 100%;
    background: linear-gradient(135deg, rgba(13,110,253,0.12), rgba(94,218,255,0.1));
}
.metric-card.live {
    position: relative;
}
.metric-card.live::after {
    content: '';
    position: absolute;
    top: 14px;
    right: 14px;
    width: 10px;
    height: 10px;
    border-radius: 50%;
    background: #16c47f;
    box-shadow: 0 0 0 6px rgba(22,196,127,0.14);
}
.table thead th {
    background: #f7f9fc;
    border-bottom: 0;
}
.product-thumb {
    width: 58px;
    height: 58px;
    border-radius: 14px;
    object-fit: cover;
}
.catalog-theme {
    border-radius: 24px;
    padding: 24px;
    margin-bottom: 18px;
    box-shadow: 0 18px 35px rgba(15,35,70,0.12);
}
.catalog-theme.theme-phone { background: linear-gradient(135deg, rgba(225,248,255,0.96), rgba(246,251,255,0.92)); }
.catalog-theme.theme-laptop { background: linear-gradient(135deg, rgba(236,244,255,0.96), rgba(245,248,255,0.92)); }
.catalog-theme.theme-accessory { background: linear-gradient(135deg, rgba(240,250,246,0.96), rgba(248,253,250,0.92)); }
.catalog-theme.theme-cpu { background: linear-gradient(135deg, rgba(246,240,255,0.96), rgba(252,248,255,0.92)); }
.catalog-theme.theme-tablet { background: linear-gradient(135deg, rgba(255,246,238,0.96), rgba(255,251,247,0.92)); }
.mini-metrics {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 12px;
}
.mini-metric {
    border-radius: 18px;
    padding: 16px;
    background: rgba(255,255,255,0.76);
}
.quick-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 16px;
}
.quick-card {
    display: block;
    padding: 18px 20px;
    border-radius: 22px;
    background: linear-gradient(135deg, rgba(12,24,48,0.96), rgba(20,44,82,0.92));
    color: #fff;
    text-decoration: none;
    box-shadow: 0 16px 34px rgba(7,15,32,0.2);
}
.quick-card:hover { color: #fff; transform: translateY(-2px); }
.live-note {
    color: #708198;
    font-size: 0.95rem;
}
@media (max-width: 991px) {
    .sidebar { position: static; width: 100%; height: auto; }
    .main-content { margin-left: 0; padding: 18px; }
    .mini-metrics { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .quick-grid { grid-template-columns: 1fr; }
}
</style>
</head>
<body>
<div class="sidebar">
    <div class="brand-block">
        <div class="d-flex align-items-center gap-3 mb-3">
            <div class="brand-logo-shell">
                <img src="<?php echo htmlspecialchars($settings['logo']); ?>" alt="Logo">
            </div>
            <div>
                <strong><?php echo htmlspecialchars($settings['site_title']); ?></strong><br>
                <small>Control comercial y operativo</small>
            </div>
        </div>
        <small>Panel fragmentado por areas para gestionar catalogo, pedidos y branding con una experiencia mas clara.</small>
    </div>

    <div class="sidebar-nav">
        <?php foreach ($adminNavItems as $key => $item): ?>
            <a href="<?php echo htmlspecialchars($item['href']); ?>" class="<?php echo $activeAdminPage === $key ? 'active' : ''; ?>">
                <i class="bi <?php echo htmlspecialchars($item['icon']); ?>"></i>
                <span><?php echo htmlspecialchars($item['label']); ?></span>
            </a>
        <?php endforeach; ?>
        <a href="index.php" target="_blank" class="utility-link"><i class="bi bi-window"></i><span>Ver Tienda</span></a>
        <form method="post" action="logout.php" class="m-0">
            <?php echo csrfInput(); ?>
            <button type="submit" class="utility-link border-0 bg-transparent w-100 text-start"><i class="bi bi-box-arrow-right"></i><span>Cerrar Sesion</span></button>
        </form>
    </div>
</div>

<div class="main-content">
    <section class="hero-admin">
        <div class="row align-items-center g-4">
            <div class="col-lg-8">
                <span class="badge-soft mb-3 d-inline-block"><?php echo htmlspecialchars($adminPageBadge); ?></span>
                <h1 class="display-6 fw-bold mb-2"><?php echo htmlspecialchars($adminPageTitle); ?></h1>
                <p class="mb-0 text-secondary"><?php echo htmlspecialchars($adminPageLead); ?></p>
            </div>
            <div class="col-lg-4">
                <?php if (!empty($heroRightContent ?? '')): ?>
                    <?php echo $heroRightContent; ?>
                <?php endif; ?>
            </div>
        </div>
    </section>

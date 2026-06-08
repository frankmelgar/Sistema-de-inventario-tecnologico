<?php
require __DIR__ . '/includes/admin_helpers.php';

requireAdminAccess();
$settings = adminFetchSettings($conn);
$admin_error = '';
$admin_success = $_GET['success'] ?? '';
$userCounts = $conn->query("SELECT COUNT(*) AS total FROM usuarios")->fetch_assoc() ?: ['total' => 0];

if (isset($_POST['save_settings'])) {
    requireValidCsrfToken();

    $logo = adminUploadImageOrKeep('logo', $settings['logo'], 'assets', $admin_error);
    $banner = adminUploadImageOrKeep('banner', $settings['banner'], 'assets', $admin_error);
    $background = adminUploadImageOrKeep('background', $settings['background'], 'assets', $admin_error);
    $primary_color = cleanText((string) ($_POST['primary_color'] ?? ''), 7);
    $secondary_color = cleanText((string) ($_POST['secondary_color'] ?? ''), 7);
    $font_family = cleanCssFontFamily((string) ($_POST['font_family'] ?? 'Arial'));
    $font_size = max(12, (int) ($_POST['font_size'] ?? 14));
    $site_title = cleanText((string) ($_POST['site_title'] ?? ''), 120);
    $banner_text = cleanText((string) ($_POST['banner_text'] ?? ''), 255);
    $footer_text = cleanText((string) ($_POST['footer_text'] ?? ''), 255);

    if ($site_title === '' || $banner_text === '' || $footer_text === '' || $admin_error !== '') {
        if ($admin_error === '') {
            $admin_error = 'Completa correctamente la configuracion visual.';
        }
    } elseif (!isValidHexColor($primary_color) || !isValidHexColor($secondary_color)) {
        $admin_error = 'Selecciona colores validos para la tienda.';
    } else {
        $stmt = $conn->prepare("UPDATE settings SET logo=?, banner=?, background=?, primary_color=?, secondary_color=?, font_family=?, font_size=?, site_title=?, banner_text=?, footer_text=? WHERE id=1");
        $stmt->bind_param('ssssssisss', $logo, $banner, $background, $primary_color, $secondary_color, $font_family, $font_size, $site_title, $banner_text, $footer_text);
        $stmt->execute();
        auditLog($conn, 'settings_updated', 'settings', 1, ['section' => 'appearance']);
        header('Location: admin-apariencia.php?success=settings');
        exit;
    }

    $settings = adminFetchSettings($conn);
}

$heroRightContent = '
    <div class="row g-3">
        <div class="col-6"><div class="metric-card"><small>Usuarios</small><h3 class="mb-0">' . (int) $userCounts['total'] . '</h3></div></div>
        <div class="col-6"><div class="metric-card"><small>Fuente base</small><h3 class="mb-0">' . (int) ($settings['font_size'] ?? 14) . 'px</h3></div></div>
        <div class="col-12"><div class="metric-card"><small>Marca activa</small><h3 class="mb-0">' . htmlspecialchars($settings['site_title']) . '</h3></div></div>
    </div>
';

$adminPageTitle = 'Apariencia de la tienda';
$adminPageLead = 'Gestion visual desacoplada del resto del admin para ajustar branding, tono grafico y contenido principal con claridad.';
$adminPageBadge = 'Control de marca';
$activeAdminPage = 'apariencia';

require __DIR__ . '/includes/admin_header.php';
?>
<?php if ($admin_error !== ''): ?>
    <div class="alert alert-danger"><?php echo htmlspecialchars($admin_error); ?></div>
<?php elseif ($admin_success === 'settings'): ?>
    <div class="alert alert-success">La apariencia de la tienda se actualizo correctamente.</div>
<?php endif; ?>

<div class="panel-card p-4">
    <div class="section-title">
        <h2 class="h4 mb-0">Identidad visual</h2>
        <span class="text-secondary small"><?php echo (int) $userCounts['total']; ?> usuarios registrados</span>
    </div>
    <form method="post" enctype="multipart/form-data">
        <?php echo csrfInput(); ?>
        <div class="row g-4">
            <div class="col-lg-4">
                <label class="form-label">Logo</label>
                <input type="file" name="logo" class="form-control" accept=".jpg,.jpeg,.png,.webp,.avif">
            </div>
            <div class="col-lg-4">
                <label class="form-label">Banner</label>
                <input type="file" name="banner" class="form-control" accept=".jpg,.jpeg,.png,.webp,.avif">
            </div>
            <div class="col-lg-4">
                <label class="form-label">Fondo general</label>
                <input type="file" name="background" class="form-control" accept=".jpg,.jpeg,.png,.webp,.avif">
            </div>
            <div class="col-md-6">
                <label class="form-label">Titulo del sitio</label>
                <input type="text" name="site_title" class="form-control" value="<?php echo htmlspecialchars($settings['site_title']); ?>" required>
            </div>
            <div class="col-md-6">
                <label class="form-label">Texto principal del banner</label>
                <input type="text" name="banner_text" class="form-control" value="<?php echo htmlspecialchars($settings['banner_text']); ?>" required>
            </div>
            <div class="col-md-6">
                <label class="form-label">Color primario</label>
                <input type="color" name="primary_color" class="form-control form-control-color" value="<?php echo htmlspecialchars($settings['primary_color']); ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label">Color secundario</label>
                <input type="color" name="secondary_color" class="form-control form-control-color" value="<?php echo htmlspecialchars($settings['secondary_color']); ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label">Fuente</label>
                <input type="text" name="font_family" class="form-control" value="<?php echo htmlspecialchars($settings['font_family']); ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label">Tamano base de texto</label>
                <input type="number" name="font_size" class="form-control" min="12" value="<?php echo (int) $settings['font_size']; ?>">
            </div>
            <div class="col-12">
                <label class="form-label">Texto del footer</label>
                <input type="text" name="footer_text" class="form-control" value="<?php echo htmlspecialchars($settings['footer_text']); ?>" required>
            </div>
        </div>
        <button type="submit" name="save_settings" class="btn btn-secondary mt-4">Publicar cambios visuales</button>
    </form>
</div>
<?php require __DIR__ . '/includes/admin_footer.php'; ?>

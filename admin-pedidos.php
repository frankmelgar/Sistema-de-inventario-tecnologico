<?php
require __DIR__ . '/includes/admin_helpers.php';

requireAdminAccess();
$settings = adminFetchSettings($conn);
$orderStats = $conn->query("SELECT COUNT(*) AS total, SUM(payment_status = 'Pagado') AS paid_total, COALESCE(SUM(total), 0) AS revenue FROM orders")->fetch_assoc() ?: ['total' => 0, 'paid_total' => 0, 'revenue' => 0];
$orders = adminOrdersResult($conn);

$heroRightContent = '
    <div class="row g-3">
        <div class="col-6"><div class="metric-card live"><small>Pedidos</small><h3 class="mb-0">' . (int) $orderStats['total'] . '</h3></div></div>
        <div class="col-6"><div class="metric-card"><small>Pagados</small><h3 class="mb-0">' . (int) $orderStats['paid_total'] . '</h3></div></div>
        <div class="col-12"><div class="metric-card"><small>Facturacion</small><h3 class="mb-0">$' . number_format((float) $orderStats['revenue'], 2) . '</h3></div></div>
    </div>
';

$adminPageTitle = 'Pedidos y checkout';
$adminPageLead = 'Seguimiento centralizado de compras, datos de entrega y estado de pago sin mezclarlo con el resto del panel.';
$adminPageBadge = 'Operacion comercial';
$activeAdminPage = 'pedidos';

require __DIR__ . '/includes/admin_header.php';
?>
<div class="panel-card p-4">
    <div class="section-title">
        <h2 class="h4 mb-0">Historial de pedidos</h2>
        <span class="text-secondary small"><?php echo (int) $orderStats['paid_total']; ?> pedidos pagados de <?php echo (int) $orderStats['total']; ?></span>
    </div>
    <div class="table-responsive">
        <table class="table align-middle">
            <thead>
                <tr>
                    <th>Pedido</th>
                    <th>Cliente</th>
                    <th>Contacto</th>
                    <th>Total</th>
                    <th>Pago</th>
                    <th>Entrega</th>
                    <th>Fecha</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($order = $orders->fetch_assoc()): ?>
                    <tr>
                        <td>#<?php echo (int) $order['order_id']; ?></td>
                        <td>
                            <strong><?php echo htmlspecialchars($order['customer_name'] ?: $order['nombre']); ?></strong><br>
                            <small class="text-secondary">Usuario: <?php echo htmlspecialchars($order['nombre'] ?: 'Cuenta no disponible'); ?></small>
                        </td>
                        <td>
                            <small><?php echo htmlspecialchars($order['customer_email'] ?: 'No registrado'); ?></small><br>
                            <small><?php echo htmlspecialchars($order['customer_phone'] ?: 'No registrado'); ?></small>
                        </td>
                        <td>$<?php echo number_format($order['total'], 2); ?></td>
                        <td>
                            <span class="badge text-bg-success"><?php echo htmlspecialchars($order['payment_status']); ?></span><br>
                            <small><?php echo htmlspecialchars($order['payment_method']); ?></small>
                        </td>
                        <td><small><?php echo htmlspecialchars($order['shipping_address'] ?: 'Direccion pendiente'); ?></small></td>
                        <td><?php echo htmlspecialchars($order['created_at']); ?></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
    <?php if ((int) $orderStats['total'] === 0): ?>
        <div class="text-center py-4 text-secondary">
            <i class="bi bi-bag-x fs-1 d-block mb-3"></i>
            Aun no se han registrado pedidos.
        </div>
    <?php endif; ?>
</div>
<?php require __DIR__ . '/includes/admin_footer.php'; ?>

<?php
require __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/catalog.php';

requireAuthenticatedUser();

if (empty($_SESSION['cart'])) {
    header('Location: index.php');
    exit;
}

$settings = fetchAppSettings($conn);
$user_id = (int) $_SESSION['user_id'];
$cart_products = [];
$total = 0;
$checkout_error = '';

$ids = catalogSafeProductIds(array_keys($_SESSION['cart']));
if (empty($ids)) {
    header('Location: carrito.php');
    exit;
}

foreach (catalogProductsByIds($conn, $ids, true) as $row) {
    if (!isset($_SESSION['cart'][$row['id']])) {
        continue;
    }
    $row['quantity'] = $_SESSION['cart'][$row['id']];
    $row['subtotal'] = $row['price'] * $row['quantity'];
    $total += $row['subtotal'];
    $cart_products[] = $row;
}

if (empty($cart_products)) {
    $_SESSION['cart'] = [];
    header('Location: carrito.php');
    exit;
}

$userData = null;
$userStmt = $conn->prepare('SELECT nombre, email FROM usuarios WHERE id = ? LIMIT 1');
$userStmt->bind_param('i', $user_id);
$userStmt->execute();
$userData = $userStmt->get_result()->fetch_assoc();

$form = [
    'customer_name' => $userData['nombre'] ?? currentUserName(),
    'customer_email' => $userData['email'] ?? '',
    'customer_phone' => '',
    'shipping_address' => '',
    'payment_method' => 'Tarjeta'
];

$successOrder = null;
if (isset($_GET['success'], $_GET['order_id'])) {
    $orderId = (int) $_GET['order_id'];
    $stmt = $conn->prepare('SELECT * FROM orders WHERE id = ? AND user_id = ? LIMIT 1');
    $stmt->bind_param('ii', $orderId, $user_id);
    $stmt->execute();
    $successOrder = $stmt->get_result()->fetch_assoc();
}

if (isset($_POST['checkout'])) {
    requireValidCsrfToken();

    $form['customer_name'] = cleanText((string) ($_POST['customer_name'] ?? ''), 120);
    $form['customer_email'] = cleanText((string) ($_POST['customer_email'] ?? ''), 190);
    $form['customer_phone'] = cleanText((string) ($_POST['customer_phone'] ?? ''), 40);
    $form['shipping_address'] = cleanText((string) ($_POST['shipping_address'] ?? ''), 255);
    $form['payment_method'] = cleanText((string) ($_POST['payment_method'] ?? 'Tarjeta'), 40);

    if ($form['customer_name'] === '' || $form['customer_email'] === '' || $form['customer_phone'] === '' || $form['shipping_address'] === '') {
        $checkout_error = 'Completa todos los datos de contacto y entrega.';
    } elseif (!filter_var($form['customer_email'], FILTER_VALIDATE_EMAIL)) {
        $checkout_error = 'Ingresa un email valido para el pedido.';
    } elseif (!in_array($form['payment_method'], allowedPaymentMethods(), true)) {
        $checkout_error = 'Selecciona un metodo de pago valido.';
    }

    if ($checkout_error === '') {
        $payment_status = 'Pagado';
        $conn->begin_transaction();
        try {
            $stmt = $conn->prepare('INSERT INTO orders (user_id, total, payment_method, payment_status, customer_name, customer_email, customer_phone, shipping_address) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
            $stmt->bind_param('idssssss', $user_id, $total, $form['payment_method'], $payment_status, $form['customer_name'], $form['customer_email'], $form['customer_phone'], $form['shipping_address']);
            $stmt->execute();
            $order_id = $stmt->insert_id;

            $stmt_item = $conn->prepare('INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)');
            foreach ($cart_products as $p) {
                $stmt_item->bind_param('iiid', $order_id, $p['id'], $p['quantity'], $p['price']);
                $stmt_item->execute();
            }

            $conn->commit();
            auditLog($conn, 'order_created', 'orders', $order_id, [
                'total' => $total,
                'items' => count($cart_products),
                'payment_method' => $form['payment_method'],
            ]);
            $_SESSION['cart'] = [];
            header('Location: checkout.php?success=1&order_id=' . $order_id);
            exit;
        } catch (Throwable $e) {
            $conn->rollback();
            $checkout_error = 'No se pudo confirmar el pedido. Intenta nuevamente.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Checkout - <?php echo htmlspecialchars($settings['site_title']); ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
<style>
body {
    background:
        linear-gradient(135deg, rgba(10,20,40,0.82), rgba(10,20,40,0.55)),
        url('<?php echo htmlspecialchars($settings['background']); ?>') center/cover fixed;
}
.hero-shell {
    margin-top: 28px;
    margin-bottom: 24px;
    padding: 34px;
    border-radius: 28px;
    background: linear-gradient(135deg, rgba(255,255,255,0.95), rgba(238,246,255,0.88));
    box-shadow: 0 24px 50px rgba(4,16,40,0.18);
}
.checkout-shell {
    padding: 0 0 40px;
}
.checkout-panel {
    background: rgba(255,255,255,0.95);
    border-radius: 26px;
    box-shadow: 0 22px 45px rgba(7,18,39,0.18);
}
.summary-card {
    background: linear-gradient(135deg, rgba(13,110,253,0.08), rgba(108,117,125,0.08));
    border-radius: 22px;
}
.navbar {
    background: rgba(8,18,36,0.92);
    backdrop-filter: blur(14px);
}
.navbar a, .navbar-brand, .navbar .btn, .navbar-text { color: #fff !important; }
.checkout-icon-badge {
    width: 74px;
    height: 74px;
    border-radius: 22px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, rgba(79,221,255,0.18), rgba(21,140,255,0.18));
    color: #0a2745;
    font-size: 2rem;
}
</style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark">
<div class="container-fluid px-4">
    <a class="navbar-brand d-flex align-items-center gap-2" href="index.php"><img src="<?php echo htmlspecialchars($settings['logo']); ?>" alt="Logo" height="46"><span><?php echo htmlspecialchars($settings['site_title']); ?></span></a>
    <div class="ms-auto d-flex align-items-center gap-3">
        <span class="navbar-text">Hola: <?php echo htmlspecialchars(currentUserName()); ?></span>
        <a class="btn btn-outline-light btn-sm" href="carrito.php">Volver al carrito</a>
    </div>
</div>
</nav>

<div class="container checkout-shell">
    <?php if ($successOrder): ?>
        <div class="checkout-panel p-5 text-center">
            <div class="checkout-icon-badge mx-auto mb-3"><i class="bi bi-bag-check"></i></div>
            <div class="mb-3"><span class="badge text-bg-success px-3 py-2">Pedido confirmado</span></div>
            <h1 class="display-6 fw-bold">Pedido #<?php echo (int) $successOrder['id']; ?> confirmado correctamente</h1>
            <p class="lead text-secondary">La compra quedó registrada con datos de contacto, dirección y método de pago en una presentación consistente con el resto de la tienda.</p>
            <div class="row g-3 justify-content-center mt-4 text-start">
                <div class="col-md-3"><div class="summary-card p-3 h-100"><small>Cliente</small><div class="fw-semibold"><?php echo htmlspecialchars($successOrder['customer_name']); ?></div></div></div>
                <div class="col-md-3"><div class="summary-card p-3 h-100"><small>Email</small><div class="fw-semibold"><?php echo htmlspecialchars($successOrder['customer_email']); ?></div></div></div>
                <div class="col-md-3"><div class="summary-card p-3 h-100"><small>Pago</small><div class="fw-semibold"><?php echo htmlspecialchars($successOrder['payment_method']); ?></div></div></div>
                <div class="col-md-3"><div class="summary-card p-3 h-100"><small>Total</small><div class="fw-semibold">$<?php echo number_format($successOrder['total'], 2); ?></div></div></div>
            </div>
            <div class="mt-4">
                <a href="index.php" class="btn btn-primary">Volver a la tienda</a>
                <?php if (isAdmin()): ?><a href="admin.php#pedidos" class="btn btn-outline-secondary ms-2">Ver en admin</a><?php endif; ?>
            </div>
        </div>
    <?php else: ?>
        <section class="hero-shell">
            <div class="row align-items-center g-4">
                <div class="col-lg-8">
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <div class="checkout-icon-badge"><i class="bi bi-credit-card-2-front"></i></div>
                        <div>
                            <span class="badge text-bg-primary mb-2">Checkout</span>
                            <h1 class="h2 mb-1">Completa el pedido con una experiencia clara y profesional</h1>
                        </div>
                    </div>
                    <p class="lead text-secondary mb-0">Mantiene una estructura visual coherente desde la tienda hasta la confirmacion final del pedido.</p>
                </div>
                <div class="col-lg-4">
                    <div class="summary-card p-4">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="text-secondary">Artículos</span>
                            <strong><?php echo array_sum(array_column($cart_products, 'quantity')); ?></strong>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-secondary">Total</span>
                            <strong>$<?php echo number_format($total, 2); ?></strong>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <div class="row g-4">
            <div class="col-lg-7">
                <div class="checkout-panel p-4 p-lg-5">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
                        <div>
                            <span class="badge text-bg-primary mb-2">Datos de entrega</span>
                            <h2 class="h3 mb-1">Información del cliente y forma de pago</h2>
                            <p class="text-secondary mb-0">Completa los datos para cerrar la compra con una presentación más sólida.</p>
                        </div>
                        <i class="bi bi-shield-check fs-1 text-primary"></i>
                    </div>

                    <?php if ($checkout_error !== ''): ?>
                        <div class="alert alert-danger"><?php echo htmlspecialchars($checkout_error); ?></div>
                    <?php endif; ?>

                    <form method="post" class="row g-3">
                        <?php echo csrfInput(); ?>
                        <div class="col-md-6">
                            <label class="form-label">Nombre completo</label>
                            <input type="text" name="customer_name" class="form-control" value="<?php echo htmlspecialchars($form['customer_name']); ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email</label>
                            <input type="email" name="customer_email" class="form-control" value="<?php echo htmlspecialchars($form['customer_email']); ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Telefono</label>
                            <input type="text" name="customer_phone" class="form-control" value="<?php echo htmlspecialchars($form['customer_phone']); ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Metodo de pago</label>
                            <select name="payment_method" class="form-select" required>
                                <option value="Tarjeta" <?php echo $form['payment_method'] === 'Tarjeta' ? 'selected' : ''; ?>>Tarjeta</option>
                                <option value="PayPal" <?php echo $form['payment_method'] === 'PayPal' ? 'selected' : ''; ?>>PayPal</option>
                                <option value="Transferencia" <?php echo $form['payment_method'] === 'Transferencia' ? 'selected' : ''; ?>>Transferencia</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Direccion de entrega</label>
                            <textarea name="shipping_address" class="form-control" rows="3" required><?php echo htmlspecialchars($form['shipping_address']); ?></textarea>
                        </div>
                        <div class="col-12 d-flex gap-2 pt-2">
                            <button type="submit" name="checkout" class="btn btn-success btn-lg">Confirmar compra</button>
                            <a href="carrito.php" class="btn btn-outline-secondary btn-lg">Editar carrito</a>
                        </div>
                    </form>
                </div>
            </div>
            <div class="col-lg-5">
                <div class="checkout-panel p-4 summary-card h-100">
                    <h2 class="h4 mb-3">Resumen del pedido</h2>
                    <?php foreach ($cart_products as $p): ?>
                    <div class="d-flex justify-content-between align-items-start gap-3 border-bottom py-3">
                        <div>
                            <div class="d-flex align-items-center gap-3">
                                <img src="<?php echo htmlspecialchars(productImageUrl($p)); ?>" alt="Producto" style="width:56px;height:56px;border-radius:14px;object-fit:cover;">
                                <div>
                                    <strong><?php echo htmlspecialchars($p['name']); ?></strong><br>
                                    <small class="text-secondary"><?php echo (int) $p['quantity']; ?> x $<?php echo number_format($p['price'], 2); ?></small>
                                </div>
                            </div>
                        </div>
                        <strong>$<?php echo number_format($p['subtotal'], 2); ?></strong>
                    </div>
                    <?php endforeach; ?>
                    <div class="d-flex justify-content-between align-items-center py-3 border-bottom">
                        <span>Entrega estándar</span>
                        <span class="text-success">Incluida</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center py-3 fs-5 fw-bold">
                        <span>Total</span>
                        <span>$<?php echo number_format($total, 2); ?></span>
                    </div>
                    <div class="mt-3 text-secondary small">
                        <div class="mb-2"><i class="bi bi-patch-check me-2 text-primary"></i>Proceso de compra claro y profesional</div>
                        <div class="mb-2"><i class="bi bi-truck me-2 text-primary"></i>Datos de entrega visibles en admin</div>
                        <div><i class="bi bi-credit-card-2-front me-2 text-primary"></i>Métodos de pago configurados para la presentación</div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

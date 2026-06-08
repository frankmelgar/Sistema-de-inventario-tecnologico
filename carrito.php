<?php
require __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/catalog.php';

$settings = fetchAppSettings($conn);

if (!isset($_SESSION['role'])) {
    header('Location: login.php');
    exit;
}

if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

if (isset($_POST['update_cart']) && isset($_POST['quantities']) && is_array($_POST['quantities'])) {
    requireValidCsrfToken();

    foreach ($_POST['quantities'] as $product_id => $quantity) {
        $product_id = (int) $product_id;
        $quantity = (int) $quantity;
        if ($product_id <= 0) {
            continue;
        }
        if ($quantity > 0) {
            $_SESSION['cart'][$product_id] = normalizedQuantity($quantity);
        } else {
            unset($_SESSION['cart'][$product_id]);
        }
    }
    header('Location: carrito.php');
    exit;
}

if (isset($_POST['remove'])) {
    requireValidCsrfToken();

    $id = (int) $_POST['remove'];
    if ($id > 0) {
        unset($_SESSION['cart'][$id]);
    }
    header('Location: carrito.php');
    exit;
}

if (isset($_POST['remove_all'])) {
    requireValidCsrfToken();

    $_SESSION['cart'] = [];
    header('Location: carrito.php');
    exit;
}

$cart_products = [];
$total = 0;
if (!empty($_SESSION['cart'])) {
    foreach (catalogProductsByIds($conn, array_keys($_SESSION['cart']), true) as $row) {
        if (!isset($_SESSION['cart'][$row['id']])) {
            continue;
        }
        $row['quantity'] = $_SESSION['cart'][$row['id']];
        $row['subtotal'] = $row['price'] * $row['quantity'];
        $total += $row['subtotal'];
        $cart_products[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Carrito - <?php echo htmlspecialchars($settings['site_title']); ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
<style>
:root {
    --primary: <?php echo htmlspecialchars($settings['primary_color']); ?>;
    --secondary: <?php echo htmlspecialchars($settings['secondary_color']); ?>;
}
body {
    background:
        linear-gradient(135deg, rgba(8,18,36,0.82), rgba(15,47,87,0.54)),
        url('<?php echo htmlspecialchars($settings['background']); ?>') center/cover fixed;
}
.navbar {
    background: rgba(7,18,36,0.92) !important;
    backdrop-filter: blur(14px);
}
.navbar-brand img { height: 44px; }
.navbar a, .navbar-brand, .navbar .btn, .navbar-text { color: #fff !important; }
.cart-hero {
    margin-top: 28px;
    margin-bottom: 24px;
    padding: 34px;
    border-radius: 28px;
    background: linear-gradient(135deg, rgba(255,255,255,0.95), rgba(238,246,255,0.88));
    box-shadow: 0 24px 50px rgba(4,16,40,0.18);
}
.cart-shell {
    background: rgba(255,255,255,0.96);
    border-radius: 28px;
    padding: 30px;
    box-shadow: 0 22px 45px rgba(7,18,39,0.20);
}
.cart-icon-badge {
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
.summary-strip {
    background: rgba(10,39,69,0.05);
    border-radius: 20px;
    padding: 18px 22px;
}
.btn-primary {
    background-color: var(--primary);
    border-color: var(--primary);
    color: #07213a;
    font-weight: 700;
}
.btn-success {
    font-weight: 700;
}
.product-line {
    display: flex;
    align-items: center;
    gap: 14px;
}
.product-thumb {
    width: 64px;
    height: 64px;
    border-radius: 16px;
    object-fit: cover;
}
</style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark">
<div class="container-fluid px-4">
    <a class="navbar-brand d-flex align-items-center gap-2" href="index.php"><img src="<?php echo htmlspecialchars($settings['logo']); ?>" alt="Logo"><span><?php echo htmlspecialchars($settings['site_title']); ?></span></a>
    <div class="ms-auto d-flex align-items-center gap-3">
        <span class="navbar-text text-white">Hola: <?php echo htmlspecialchars(currentUserName()); ?></span>
        <a class="btn btn-outline-light btn-sm" href="index.php"><i class="bi bi-shop me-1"></i> Tienda</a>
        <form method="post" action="logout.php" class="m-0">
            <?php echo csrfInput(); ?>
            <button type="submit" class="btn btn-outline-light btn-sm">Salir</button>
        </form>
    </div>
</div>
</nav>

<div class="container mt-4 pb-4">
    <section class="cart-hero">
        <div class="row align-items-center g-4">
            <div class="col-lg-8">
                <div class="d-flex align-items-center gap-3 mb-3">
                    <div class="cart-icon-badge"><i class="bi bi-cart3"></i></div>
                    <div>
                        <span class="badge text-bg-primary mb-2">Carrito de compra</span>
                        <h1 class="h2 mb-1">Revisa tu selección antes de confirmar el pedido</h1>
                    </div>
                </div>
                <p class="lead text-secondary mb-0">Controla cantidades, revisa subtotales y pasa a un checkout con una presentacion consistente para el cliente.</p>
            </div>
            <div class="col-lg-4">
                <div class="summary-strip">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="text-secondary">Productos</span>
                        <strong><?php echo array_sum($_SESSION['cart']); ?></strong>
                    </div>
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="text-secondary">Total estimado</span>
                        <strong>$<?php echo number_format($total, 2); ?></strong>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <div class="cart-shell">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
            <div>
                <h2 class="mb-1">Mi carrito</h2>
                <p class="text-secondary mb-0">Mantén el pedido limpio y listo antes de continuar al checkout.</p>
            </div>
            <a href="index.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i> Seguir comprando</a>
        </div>

        <?php if (empty($cart_products)): ?>
            <div class="text-center py-4">
                <div class="cart-icon-badge mx-auto mb-3"><i class="bi bi-bag-x"></i></div>
                <p class="mb-2">Tu carrito está vacío.</p>
                <a href="index.php" class="btn btn-primary">Ir a la tienda</a>
            </div>
        <?php else: ?>
            <form method="post">
                <?php echo csrfInput(); ?>
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead>
                            <tr>
                                <th>Producto</th>
                                <th>Precio</th>
                                <th>Cantidad</th>
                                <th>Subtotal</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($cart_products as $p): ?>
                            <tr>
                                <td>
                                    <div class="product-line">
                                        <img src="<?php echo htmlspecialchars(productImageUrl($p)); ?>" alt="Producto" class="product-thumb">
                                        <div>
                                            <strong><?php echo htmlspecialchars($p['name']); ?></strong><br>
                                            <small class="text-secondary"><?php echo htmlspecialchars($p['brand'] ?? ''); ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td>$<?php echo number_format($p['price'], 2); ?></td>
                                <td><input type="number" name="quantities[<?php echo (int) $p['id']; ?>]" value="<?php echo (int) $p['quantity']; ?>" min="0" class="form-control" style="width:90px;"></td>
                                <td>$<?php echo number_format($p['subtotal'], 2); ?></td>
                                <td class="text-end">
                                    <button type="submit" name="remove" value="<?php echo (int) $p['id']; ?>" class="btn btn-sm btn-outline-danger">Quitar</button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mt-4">
                    <div>
                        <button type="submit" name="update_cart" class="btn btn-primary"><i class="bi bi-arrow-repeat me-1"></i> Actualizar carrito</button>
                        <button type="submit" name="remove_all" value="1" class="btn btn-danger" onclick="return confirm('Deseas eliminar todos los productos del carrito?');"><i class="bi bi-trash3 me-1"></i> Eliminar todo</button>
                        <?php if (($_SESSION['role'] ?? '') === 'guest'): ?>
                            <a href="login.php" class="btn btn-warning"><i class="bi bi-person-lock me-1"></i> Inicia sesión para comprar</a>
                        <?php else: ?>
                            <a href="checkout.php" class="btn btn-success"><i class="bi bi-credit-card-2-front me-1"></i> Ir al checkout</a>
                        <?php endif; ?>
                    </div>
                    <h4 class="mb-0">Total: $<?php echo number_format($total, 2); ?></h4>
                </div>
            </form>
        <?php endif; ?>
    </div>
</div>
</body>
</html>

<?php
require __DIR__ . '/includes/bootstrap.php';

$settings = fetchAppSettings($conn);
$error = cleanText((string) ($_GET['error'] ?? ''), 40);
$nombre = cleanText((string) ($_GET['nombre'] ?? ''), 120);
$email = cleanText((string) ($_GET['email'] ?? ''), 190);

$message = '';
if ($error === 'campos') {
    $message = 'Completa todos los campos para crear tu cuenta.';
} elseif ($error === 'email') {
    $message = 'Ingresa un email valido.';
} elseif ($error === 'password') {
    $message = 'La contraseña debe tener al menos 6 caracteres.';
} elseif ($error === 'duplicado') {
    $message = 'Ese email ya esta registrado.';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Crear cuenta - <?php echo htmlspecialchars($settings['site_title']); ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
<style>
:root {
    --primary: <?php echo htmlspecialchars($settings['primary_color']); ?>;
    --secondary: <?php echo htmlspecialchars($settings['secondary_color']); ?>;
}
body {
    min-height: 100vh;
    background:
        linear-gradient(135deg, rgba(5,16,31,0.88), rgba(15,47,87,0.56)),
        url('<?php echo htmlspecialchars($settings['background']); ?>') center/cover fixed;
}
.register-shell {
    min-height: 100vh;
    display: flex;
    align-items: center;
    padding: 28px 0;
}
.register-card,
.register-side {
    background: rgba(255,255,255,0.96);
    border-radius: 30px;
    box-shadow: 0 24px 50px rgba(4,16,40,0.24);
}
.register-card {
    padding: 34px;
}
.register-side {
    padding: 34px;
    background: linear-gradient(145deg, rgba(255,255,255,0.96), rgba(235,245,255,0.9));
}
.brand-kicker {
    letter-spacing: 0.26em;
    font-size: 0.76rem;
    color: #6b7b93;
    text-transform: uppercase;
    margin-bottom: 12px;
}
.register-logo {
    height: 88px;
    display: block;
    margin-bottom: 18px;
}
.register-banner {
    width: 100%;
    height: 180px;
    border-radius: 20px;
    object-fit: cover;
    box-shadow: 0 18px 32px rgba(10,28,58,0.12);
}
.benefit-card {
    border-radius: 20px;
    padding: 18px 20px;
    background: rgba(10,39,69,0.05);
}
.benefit-card i {
    color: var(--primary);
    font-size: 1.2rem;
}
.btn-primary {
    background-color: var(--primary);
    border-color: var(--primary);
    color: #07213a;
    font-weight: 700;
}
.btn-outline-secondary {
    font-weight: 600;
}
.form-label {
    font-weight: 600;
}
.password-tip {
    color: #6b7b93;
    font-size: 0.92rem;
}
@media (max-width: 991px) {
    .register-side {
        margin-top: 20px;
    }
}
</style>
</head>
<body>
<div class="container register-shell">
    <div class="row g-4 w-100 align-items-stretch">
        <div class="col-lg-5">
            <div class="register-side h-100">
                <img src="<?php echo htmlspecialchars($settings['logo']); ?>" alt="Logo" class="register-logo">
                <div class="brand-kicker">Smart Tech Solutions</div>
                <h1 class="display-6 fw-bold mb-3">Crea tu cuenta y empieza a comprar con una experiencia cuidada de principio a fin.</h1>
                <p class="text-secondary mb-4">Accede a un catalogo profesional, gestiona tu compra con claridad y guarda tus datos para continuar mas rapido en futuras visitas.</p>
                <img src="<?php echo htmlspecialchars($settings['banner']); ?>" alt="Banner" class="register-banner mb-4">
                <div class="benefit-card mb-3">
                    <div class="d-flex align-items-start gap-3">
                        <i class="bi bi-person-check"></i>
                        <div>
                            <strong>Cuenta lista en menos de un minuto</strong>
                            <div class="text-secondary">Un registro simple para entrar directo a la tienda.</div>
                        </div>
                    </div>
                </div>
                <div class="benefit-card mb-3">
                    <div class="d-flex align-items-start gap-3">
                        <i class="bi bi-bag-check"></i>
                        <div>
                            <strong>Proceso de compra mas fluido</strong>
                            <div class="text-secondary">Tus datos quedan listos para avanzar al checkout con menos pasos.</div>
                        </div>
                    </div>
                </div>
                <div class="benefit-card">
                    <div class="d-flex align-items-start gap-3">
                        <i class="bi bi-shield-check"></i>
                        <div>
                            <strong>Acceso seguro</strong>
                            <div class="text-secondary">Tu cuenta se registra con contraseña cifrada y flujo de acceso protegido.</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-7">
            <div class="register-card h-100">
                <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
                    <div>
                        <span class="badge text-bg-primary mb-2">Registro</span>
                        <h2 class="h1 mb-2">Crear cuenta</h2>
                        <p class="text-secondary mb-0">Completa tus datos para acceder a la tienda y gestionar tu compra.</p>
                    </div>
                    <a href="login.php" class="btn btn-outline-secondary"><i class="bi bi-box-arrow-in-right me-1"></i> Ya tengo cuenta</a>
                </div>

                <?php if ($message): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($message); ?></div>
                <?php endif; ?>

                <form action="procesar_registro.php" method="POST" class="row g-3">
                    <?php echo csrfInput(); ?>
                    <div class="col-12">
                        <label class="form-label">Nombre completo</label>
                        <input type="text" name="nombre" class="form-control form-control-lg" placeholder="Tu nombre" value="<?php echo htmlspecialchars($nombre); ?>" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control form-control-lg" placeholder="tuemail@correo.com" value="<?php echo htmlspecialchars($email); ?>" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Contraseña</label>
                        <input type="password" name="password" class="form-control form-control-lg" placeholder="Minimo 6 caracteres" minlength="6" required>
                        <div class="password-tip mt-2">Usa una contraseña facil de recordar para ti y con al menos 6 caracteres.</div>
                    </div>
                    <div class="col-12 pt-2">
                        <button class="btn btn-primary btn-lg w-100">Crear cuenta</button>
                    </div>
                </form>

                <div class="text-center mt-4 text-secondary">
                    Al registrarte, podras comprar como cliente y avanzar al checkout con una experiencia mas completa.
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>


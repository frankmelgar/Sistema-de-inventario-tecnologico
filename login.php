<?php
require __DIR__ . '/includes/bootstrap.php';

$settings = fetchAppSettings($conn);
$error = '';

if (isset($_GET['guest'])) {
    header('Location: login.php');
    exit;
}

if (isPostRequest()) {
    requireValidCsrfToken();

    if (isset($_POST['guest'])) {
        signInUser(null, 'Invitado', 'guest');
        auditLog($conn, 'guest_login', 'usuarios', null);
        header('Location: index.php');
        exit;
    }

    $email = cleanText((string) ($_POST['email'] ?? ''), 190);
    $password = (string) ($_POST['password'] ?? '');

    if ($email && $password) {
        $rateState = loginRateLimitState($conn);
        if ($rateState['blocked']) {
            http_response_code(429);
            auditLog($conn, 'login_rate_limited', 'usuarios', null, [
                'email' => $email,
                'remaining_seconds' => $rateState['remaining_seconds'],
            ]);
            $error = 'Demasiados intentos fallidos. Intenta nuevamente en ' . ceil($rateState['remaining_seconds'] / 60) . ' minuto(s).';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            recordLoginAttempt($conn, $email, false);
            $error = 'Credenciales invalidas';
        } else {
            $stmt = $conn->prepare('SELECT id, nombre, password, role FROM usuarios WHERE email = ?');
            $stmt->bind_param('s', $email);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($row = $result->fetch_assoc()) {
                if (password_verify($password, $row['password'])) {
                    signInUser((int) $row['id'], $row['nombre'], $row['role']);
                    recordLoginAttempt($conn, $email, true);
                    header('Location: ' . ($row['role'] === 'admin' ? 'admin.php' : 'index.php'));
                    exit;
                }
            }

            recordLoginAttempt($conn, $email, false);
            $error = 'Credenciales invalidas';
        }
    } else {
        $error = 'Credenciales invalidas';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Login - <?php echo htmlspecialchars($settings['site_title']); ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
body {
    min-height: 100vh;
    background:
        linear-gradient(135deg, rgba(5,16,31,0.86), rgba(15,47,87,0.52)),
        url('<?php echo htmlspecialchars($settings['background']); ?>') center/cover fixed;
}
.auth-shell {
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 24px;
}
.login-container {
    width: 100%;
    max-width: 460px;
    padding: 34px;
    background: rgba(255,255,255,0.96);
    border-radius: 28px;
    box-shadow: 0 24px 50px rgba(4,16,40,0.24);
}
.login-logo img { display: block; margin: 0 auto 20px auto; height: 94px; }
.login-banner { width: 100%; height: 138px; background-image: url('<?php echo htmlspecialchars($settings['banner']); ?>'); background-size: cover; background-position: center; border-radius: 18px; margin-bottom: 22px; }
.brand-kicker { letter-spacing: 0.26em; font-size: 0.76rem; color: #6b7b93; text-transform: uppercase; text-align: center; margin-bottom: 10px; }
.btn-primary { background-color: <?php echo htmlspecialchars($settings['primary_color']); ?>; border-color: <?php echo htmlspecialchars($settings['primary_color']); ?>; color: #07213a; font-weight: 700; }
.btn-secondary { background-color: <?php echo htmlspecialchars($settings['secondary_color']); ?>; border-color: <?php echo htmlspecialchars($settings['secondary_color']); ?>; color: #fff; }
</style>
</head>
<body>
<div class="auth-shell">
    <div class="login-container">
        <div class="login-logo"><img src="<?php echo htmlspecialchars($settings['logo']); ?>" alt="Logo"></div>
        <div class="login-banner"></div>
        <div class="brand-kicker">Smart Tech Solutions</div>
        <h3 class="text-center mb-3"><?php echo htmlspecialchars($settings['site_title']); ?></h3>
        <p class="text-center text-secondary mb-4">Accede al panel de administración o entra como cliente para recorrer una tienda tecnológica preparada para presentación comercial.</p>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="post">
            <?php echo csrfInput(); ?>
            <div class="mb-3">
                <label>Email</label>
                <input type="email" name="email" class="form-control" required>
            </div>
            <div class="mb-3">
                <label>Contraseña</label>
                <input type="password" name="password" class="form-control" required>
            </div>
            <button class="btn btn-primary w-100 mb-2">Entrar</button>
            <button type="submit" name="guest" class="btn btn-secondary w-100" formnovalidate>Entrar como Invitado</button>
        </form>
        <a href="registro.php" class="d-block text-center mt-3">Crear cuenta</a>
    </div>
</div>
</body>
</html>

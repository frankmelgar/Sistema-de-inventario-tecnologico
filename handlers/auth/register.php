<?php
require_once __DIR__ . '/../../includes/bootstrap.php';

if (!isPostRequest()) {
    http_response_code(405);
    exit('Metodo no permitido.');
}

requireValidCsrfToken();

$nombre = cleanText((string) ($_POST['nombre'] ?? ''), 120);
$email = cleanText((string) ($_POST['email'] ?? ''), 190);
$passwordInput = $_POST['password'] ?? '';

if ($nombre === '' || $email === '' || $passwordInput === '') {
    header("Location: registro.php?error=campos&nombre=" . urlencode($nombre) . "&email=" . urlencode($email));
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header("Location: registro.php?error=email&nombre=" . urlencode($nombre) . "&email=" . urlencode($email));
    exit;
}

if (strlen($passwordInput) < 6) {
    header("Location: registro.php?error=password&nombre=" . urlencode($nombre) . "&email=" . urlencode($email));
    exit;
}

$check = $conn->prepare("SELECT id FROM usuarios WHERE email = ?");
$check->bind_param("s", $email);
$check->execute();
$result = $check->get_result();
if ($result->fetch_assoc()) {
    header("Location: registro.php?error=duplicado&nombre=" . urlencode($nombre) . "&email=" . urlencode($email));
    exit;
}

$password = password_hash($passwordInput, PASSWORD_DEFAULT);
$sql = "INSERT INTO usuarios (nombre, email, password, role)
        VALUES (?, ?, ?, 'cliente')";
$stmt = $conn->prepare($sql);

if (!$stmt) {
    die("Error en prepare: " . $conn->error);
}

$stmt->bind_param("sss", $nombre, $email, $password);

if ($stmt->execute()) {
    $userId = (int) $conn->insert_id;
    signInUser($userId, $nombre, 'cliente');
    auditLog($conn, 'user_created', 'usuarios', $userId, ['email' => $email]);
    header("Location: index.php");
    exit;
}

die("Error al ejecutar: " . $stmt->error);

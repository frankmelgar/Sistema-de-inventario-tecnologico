<?php
require_once __DIR__ . '/app.php';

$host = appConfig('DB_HOST');
$user = appConfig('DB_USERNAME');
$pass = appConfig('DB_PASSWORD', '');
$db = appConfig('DB_DATABASE');

// La conexion falla temprano si faltan variables, evitando credenciales hardcodeadas.
if ($host === null || $user === null || $db === null) {
    http_response_code(500);
    exit('Configuracion de base de datos incompleta. Revisa el archivo .env.');
}

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Error de conexion: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");

// Realizado por Joshua Quesada y Fabio Oconitrillo
<?php
// Archivo: C:\ISW-613\httpdocs\Primer_proyecto\inc\conexion.php
// Establece la conexión a la base de datos "rides" usando credenciales locales

// Parámetros de conexión
$DB_HOST = 'localhost';
$DB_USER = 'admin';
$DB_PASS = '1234';
$DB_NAME = 'rides';

// Crea la conexión con MySQLi
$mysqli = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);

// Valida si hubo error al conectar
if ($mysqli->connect_errno) {
    die("❌ Error al conectar a la base de datos: " . $mysqli->connect_error);
}

// Define el charset a utf8mb4 (soporta caracteres especiales y emojis)
$mysqli->set_charset("utf8mb4");

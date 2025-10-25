<?php
// C:\ISW-613\httpdocs\Primer_proyecto\inc\conexion.php

// Conexión con usuario admin a la base de datos rides
$DB_HOST = 'localhost';
$DB_USER = 'admin';
$DB_PASS = '1234';
$DB_NAME = 'rides';

$mysqli = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);

if ($mysqli->connect_errno) {
    die("❌ Error al conectar a la base de datos: " . $mysqli->connect_error);
}

$mysqli->set_charset("utf8mb4");


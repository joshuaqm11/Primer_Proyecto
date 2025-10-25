<?php
require_once __DIR__ . '/conexion.php';
session_start();

function esAdmin() { return isset($_SESSION['user']) && $_SESSION['user']['tipo'] === 'admin'; }
function esChofer() { return isset($_SESSION['user']) && $_SESSION['user']['tipo'] === 'chofer'; }
function esPasajero() { return isset($_SESSION['user']) && $_SESSION['user']['tipo'] === 'pasajero'; }

function obtenerUsuarioPorEmail($email) {
    global $mysqli;
    $stmt = $mysqli->prepare("SELECT * FROM usuarios WHERE correo = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

